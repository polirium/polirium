<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
$cutoff='2026-09-25 16:54:14';
$branchType='Polirium\\Modules\\Product\\Http\\Model\\ProductBranch';
$logType='Polirium\\Modules\\Product\\Http\\Model\\ProductLog';
$tables=['products','product_elements','product_branches','product_logs','product_payments','product_payment_products','product_refunds','product_refund_products','product_payment_refunds','product_payment_refund_products','product_stocks','product_stock_products','vendor_purchases','vendor_purchase_products','vendor_purchase_refunds','vendor_purchase_refund_products','vendor_transfers','vendor_transfer_products'];
$report=['generated_at'=>now()->toDateTimeString(),'tables'=>[],'branches'=>[],'stock_summary_errors'=>[],'log_magnitude_errors'=>[],'log_direction_errors'=>[],'unlogged_qty_changes'=>[],'history_restore'=>[],'receipt_checks'=>[],'sale_checks'=>[],'refund_checks'=>[],'integrity_errors'=>[]];
$snapshot=storage_path('app/inventory-before-recovery-'.date('Ymd-His').'.jsonl.gz');
$gz=gzopen($snapshot,'wb9'); chmod($snapshot,0600);
DB::transaction(function () use (&$report,$tables,$gz,$cutoff,$branchType,$logType) {
    foreach ($tables as $table) {
        if (!Schema::hasTable($table)) { $report['tables'][$table]='missing'; continue; }
        $report['tables'][$table]=DB::table($table)->count();
        foreach (DB::table($table)->orderBy('id')->cursor() as $row) gzwrite($gz,json_encode(['table'=>$table,'row'=>$row],JSON_UNESCAPED_UNICODE)."\n");
    }
    foreach (DB::table('activity_log')->whereIn('subject_type',[$branchType,$logType,'Polirium\\Modules\\Product\\Http\\Model\\Stock\\Stock','Polirium\\Modules\\Product\\Http\\Model\\Stock\\StockProduct'])->orderBy('id')->cursor() as $row) gzwrite($gz,json_encode(['table'=>'activity_log','row'=>$row],JSON_UNESCAPED_UNICODE)."\n");
    $products=DB::table('products')->get()->keyBy('id');
    $branches=DB::table('product_branches')->get();
    $logs=DB::table('product_logs')->orderBy('id')->get();
    $stockLines=DB::table('product_stock_products')->get()->groupBy('stock_id');
    $verifiedPairs=[];
    foreach($branches->groupBy(fn($b)=>$b->product_id.':'.$b->branch_id) as $key=>$rows) if($rows->count()>1) $report['integrity_errors'][]=['duplicate_branch'=>$key];
    foreach($logs as $log) if(!$products->has($log->product_id)) $report['integrity_errors'][]=['orphan_log'=>$log->id,'product'=>$log->product_id];
    foreach(DB::table('product_stocks')->where('status','completed')->whereNull('deleted_at')->get() as $stock) {
        $lines=$stockLines->get($stock->id,collect())->filter(fn($line)=>$line->deleted_at===null);
        $increase=0; $decrease=0; $quantity=0; $value=0;
        foreach($lines as $line) {
            $diff=(int)$line->actual_stock-(int)$line->amount;
            if($diff!==(int)$line->quantity_difference) $report['stock_summary_errors'][]=['stock'=>$stock->code,'product'=>$line->product_id,'kind'=>'line_difference'];
            $increase+=max(0,$diff); $decrease+=max(0,-$diff); $quantity+=(int)$line->actual_stock; $value+=(int)$line->value_difference;
        }
        if($increase!==(int)$stock->increase_deviation || $decrease!==(int)$stock->decrease_deviation || $quantity!==(int)$stock->amount || $increase-$decrease!==(int)$stock->deviation || $value!==(int)$stock->value) $report['stock_summary_errors'][]=['stock'=>$stock->code,'kind'=>'summary','expected'=>compact('increase','decrease','quantity','value')];
    }
    foreach($logs as $log) {
        $delta=(int)$log->amount_after-(int)$log->amount_before;
        if(abs($delta)!==abs((int)$log->amount)) $report['log_magnitude_errors'][]=['id'=>$log->id,'product'=>$log->product_id,'amount'=>$log->amount,'delta'=>$delta,'at'=>$log->created_at];
        if(($log->direction==='in' && $delta<0)||($log->direction==='out' && $delta>0)) $report['log_direction_errors'][]=['id'=>$log->id,'product'=>$log->product_id];
    }
    foreach($branches as $branch) {
        $product=$products->get($branch->product_id);
        $events=DB::table('activity_log')->where('subject_type',$branchType)->where('subject_id',$branch->id)->orderBy('id')->get();
        $post=$events->filter(fn($e)=>$e->created_at>=$cutoff);
        foreach($events as $e) {
            $d=json_decode($e->properties,true);
            if(isset($d['old']['qty'],$d['attributes']['qty'])) $verifiedPairs[$branch->product_id.':'.$branch->branch_id.':'.$e->created_at.':'.(int)$d['old']['qty'].':'.(int)$d['attributes']['qty']]=true;
        }
        $qtyEvents=[];
        foreach($post as $e) { $d=json_decode($e->properties,true); if(isset($d['old']['qty'],$d['attributes']['qty'])) $qtyEvents[]=['id'=>$e->id,'at'=>$e->created_at,'old'=>(int)$d['old']['qty'],'new'=>(int)$d['attributes']['qty']]; }
        $count=DB::table('product_stock_products as sp')->join('product_stocks as s','s.id','=','sp.stock_id')->where('sp.product_id',$branch->product_id)->where('s.branch_id',$branch->branch_id)->where('s.status','completed')->whereNull('s.deleted_at')->whereNull('sp.deleted_at')->orderByDesc('s.id')->select('s.id','s.code','s.created_at','sp.actual_stock','sp.amount')->first();
        $countExpected=null; $countEvent=null; $gaps=[];
        if($count && $count->created_at>=$cutoff) {
            foreach($qtyEvents as $e) {
                if($countEvent===null && $e['at']>=$count->created_at && $e['old']===(int)$count->amount && $e['new']===(int)$count->actual_stock) { $countEvent=$e['id']; $countExpected=(int)$count->actual_stock; continue; }
                if($countEvent!==null) { if($e['old']!==$countExpected) $gaps[]=$e; $countExpected+=$e['new']-$e['old']; }
            }
            if($count->amount===$count->actual_stock && !$countEvent) $countExpected=(int)$count->actual_stock;
        }
        $item=['row'=>$branch->id,'product'=>$branch->product_id,'code'=>$product?->code,'type'=>$product?->type,'branch'=>$branch->branch_id,'qty'=>(int)$branch->qty,'audit_events'=>$events->count(),'post_qty_events'=>count($qtyEvents),'count'=>$count,'count_expected'=>$countExpected,'count_gaps'=>$gaps];
        if($countExpected!==null && $countExpected!==(int)$branch->qty) $item['problem']='count_anchor_mismatch';
        elseif($product?->type==='service' && (int)$branch->qty!==0) $item['problem']='service_phantom_stock';
        elseif($product?->type!=='service' && (int)$branch->qty<0) $item['problem']='negative_goods_stock';
        $report['branches'][]=$item;
    }
    // Restore only pre-migration snapshots proved by the last audit event,
    // where the row has not subsequently been edited. Do not replay history.
    $ids=DB::table('activity_log')->where('subject_type',$logType)->where('created_at','<',$cutoff)->selectRaw('MAX(id) as id')->groupBy('subject_id')->get()->pluck('id');
    $originals=DB::table('activity_log')->whereIntegerInRaw('id',$ids->all())->get()->keyBy('subject_id');
    foreach($logs as $log) {
        if($log->updated_at>=$cutoff || in_array($products->get($log->product_id)?->code,['TLA.052141','TLA.052172'])) continue;
        $original=$originals->get($log->id); if(!$original) continue;
        $attrs=json_decode($original->properties,true)['attributes']??[];
        if(!isset($attrs['amount_before'],$attrs['amount_after'])) continue;
        if(isset($attrs['amount']) && (int)$attrs['amount']!==(int)$log->amount) continue;
        if(isset($attrs['direction']) && $attrs['direction']!==$log->direction) continue;
        $originalDelta=(int)$attrs['amount_after']-(int)$attrs['amount_before'];
        // Audit snapshots alone can also contain legacy logging defects. Only
        // recover balances corroborated by the actual branch quantity event.
        if(abs($originalDelta)!==abs((int)$log->amount)) continue;
        if(($log->direction==='in' && $originalDelta<0)||($log->direction==='out' && $originalDelta>0)) continue;
        $pair=$log->product_id.':'.($log->branch_id?:1).':'.$original->created_at.':'.(int)$attrs['amount_before'].':'.(int)$attrs['amount_after'];
        if(!isset($verifiedPairs[$pair])) continue;
        if((int)$attrs['amount_before']===(int)$log->amount_before && (int)$attrs['amount_after']===(int)$log->amount_after) continue;
        $report['history_restore'][]=['id'=>$log->id,'product'=>$log->product_id,'branch'=>$log->branch_id,'code'=>$products->get($log->product_id)?->code,'before'=>[$log->amount_before,$log->amount_after],'original'=>[(int)$attrs['amount_before'],(int)$attrs['amount_after']],'event'=>$original->id];
    }
    // Compare every successful purchase's document quantity to the net movements.
    $purchaseType='Polirium\\Modules\\Vendor\\Http\\Model\\Purchase\\Purchase';
    if(Schema::hasTable('vendor_purchase_products')) {
        $allPurchaseLogs=$logs->where('productable_type',$purchaseType)->groupBy('productable_id');
        foreach(DB::table('vendor_purchases')->whereIn('status',['success','refund'])->get() as $p) {
            $lines=DB::table('vendor_purchase_products')->where('vendor_purchase_id',$p->id)->get();
            $expected=[]; $net=[];
            foreach($lines as $line) $expected[$line->product_id]=($expected[$line->product_id]??0)+(int)$line->amount;
            foreach($allPurchaseLogs->get($p->id,collect()) as $log) $net[$log->product_id]=($net[$log->product_id]??0)+($log->direction==='in'?1:-1)*abs((int)$log->amount);
            foreach($expected as $productId=>$amount) if($amount!==($net[$productId]??0) && $products->get($productId)?->type==='product') $report['receipt_checks'][]=['purchase'=>$p->code,'id'=>$p->id,'product'=>$productId,'code'=>$products->get($productId)?->code,'document'=>$amount,'net_logs'=>$net[$productId]??0];
        }
    }
    $paymentType='Polirium\\Modules\\Product\\Http\\Model\\Payment\\Payment';
    $saleLogs=$logs->where('productable_type',$paymentType)->groupBy('productable_id');
    $saleLines=DB::table('product_payment_products')->get()->groupBy('product_payment_id');
    $elements=DB::table('product_elements')->get()->groupBy('product_id');
    $expand=function($id,$amount,$path=[]) use (&$expand,$products,$elements) {
        $p=$products->get($id); if(!$p || $p->type==='service') return [];
        if($p->type!=='combo') return [$id=>$amount];
        if(in_array($id,$path)) throw new RuntimeException('Combo cycle during audit');
        $path[]=$id; $result=[];
        foreach($elements->get($id,collect()) as $e) foreach($expand($e->element_id,$amount*(int)$e->qty,$path) as $child=>$qty) $result[$child]=($result[$child]??0)+$qty;
        return $result;
    };
    foreach(DB::table('product_payments')->get() as $sale) {
        $expected=[]; $net=[]; $hasCombo=false;
        foreach($saleLines->get($sale->id,collect()) as $line) {
            $product=$products->get($line->product_id);
            if(!$product) { $report['integrity_errors'][]=['orphan_sale_line'=>$line->id,'product'=>$line->product_id]; continue; }
            if($product->type==='service') continue;
            foreach($expand($line->product_id,(int)$line->amount) as $id=>$amount) $expected[$id]=($expected[$id]??0)+$amount;
        }
        foreach($saleLogs->get($sale->id,collect()) as $log) $net[$log->product_id]=($net[$log->product_id]??0)+($log->direction==='in'?-1:1)*abs((int)$log->amount);
        $inactive=in_array($sale->status,['temp','draft','cancel','cancelled','failed','delivery_failed']);
        foreach(array_unique(array_merge(array_keys($expected),array_keys($net))) as $id) {
            if($hasCombo && !isset($expected[$id])) continue;
            if(($products->get($id)?->type)!=='product') continue;
            $wanted=$inactive?0:($expected[$id]??0);
            if($wanted!==($net[$id]??0)) $report['sale_checks'][]=['sale'=>$sale->code,'id'=>$sale->id,'status'=>$sale->status,'product'=>$id,'code'=>$products->get($id)?->code,'expected_export'=>$wanted,'net_logs'=>$net[$id]??0,'at'=>$sale->created_at];
        }
    }
    $refundType='Polirium\\Modules\\Vendor\\Http\\Model\\Refund\\Refund';
    $refundLogs=$logs->where('productable_type',$refundType)->groupBy('productable_id');
    $refundLines=DB::table('vendor_purchase_refund_products')->get()->groupBy('vendor_purchase_refund_id');
    foreach(DB::table('vendor_purchase_refunds')->get() as $refund) {
        $expected=[]; $net=[];
        foreach($refundLines->get($refund->id,collect()) as $line) $expected[$line->product_id]=($expected[$line->product_id]??0)+(int)$line->amount;
        foreach($refundLogs->get($refund->id,collect()) as $log) $net[$log->product_id]=($net[$log->product_id]??0)+($log->direction==='in'?-1:1)*abs((int)$log->amount);
        foreach($expected as $id=>$amount) {
            if($products->get($id)?->type!=='product') continue;
            $wanted=in_array($refund->status,['success','completed','paid'])?$amount:0;
            if($wanted!==($net[$id]??0)) $report['refund_checks'][]=['refund'=>$refund->code,'id'=>$refund->id,'status'=>$refund->status,'product'=>$id,'expected_export'=>$wanted,'net_logs'=>$net[$id]??0];
        }
    }
});
gzclose($gz);
$report['backup']=$snapshot;
$path=storage_path('app/inventory-full-audit-20261002.json');
file_put_contents($path,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); chmod($path,0600);
echo 'FULL_AUDIT '.json_encode(['tables'=>$report['tables'],'branch_rows'=>count($report['branches']),'branch_problems'=>array_count_values(array_column($report['branches'],'problem')),'stock_errors'=>count($report['stock_summary_errors']),'magnitude_errors'=>count($report['log_magnitude_errors']),'direction_errors'=>count($report['log_direction_errors']),'proven_original_snapshots'=>count($report['history_restore']),'receipt_discrepancies'=>count($report['receipt_checks']),'sale_discrepancies'=>count($report['sale_checks']),'refund_discrepancies'=>count($report['refund_checks']),'integrity_errors'=>count($report['integrity_errors']),'backup'=>$snapshot,'report'=>$path],JSON_UNESCAPED_UNICODE).PHP_EOL;
foreach($report['branches'] as $row) if(isset($row['problem']) && $row['type']!=='service') echo 'BRANCH_PROBLEM '.json_encode($row,JSON_UNESCAPED_UNICODE).PHP_EOL;
foreach($report['receipt_checks'] as $row) echo 'RECEIPT_PROBLEM '.json_encode($row,JSON_UNESCAPED_UNICODE).PHP_EOL;

<?php

// One-off, evidence-based recovery. Dry run unless explicitly passed --apply.
// Run from the application root after producing the private audit and plan.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;
use Polirium\Modules\Product\Http\Model\ProductLog;
use Polirium\Modules\Product\Http\Model\Stock\Stock;
use Polirium\Modules\Product\Http\Support\StockInventorySupport;

$auditPath = storage_path('app/inventory-full-audit-20261002.json');
$planPath = storage_path('app/inventory-recovery-plan-20261002.json');
$marker = storage_path('app/inventory-recovery-20261002-applied.json');
if (is_file($marker)) {
    echo "RECOVERY_ALREADY_APPLIED\n";
    exit(0);
}
$audit = json_decode(file_get_contents($auditPath), true, 512, JSON_THROW_ON_ERROR);
$plan = json_decode(file_get_contents($planPath), true, 512, JSON_THROW_ON_ERROR);
if (!is_file($audit['backup']) || filesize($audit['backup']) < 1000) throw new RuntimeException('Missing inventory backup');
$physical = array_values(array_filter($plan, fn ($row) => $row['type'] === 'product' && $row['decision'] === 'restore'));
$services = array_values(array_filter($audit['branches'], fn ($row) => ($row['problem'] ?? null) === 'service_phantom_stock'));
$history = $audit['history_restore'];
// This operation fixes only the independently verified remaining goods code.
if (count($physical) !== 1 || $physical[0]['code'] !== 'XG.108555' || $physical[0]['target'] !== 0) {
    throw new RuntimeException('Unexpected recovery scope; review the plan before changing data');
}
echo 'RECOVERY_PLAN ' . json_encode(['goods'=>$physical,'service_rows'=>count($services),'verified_log_snapshots'=>count($history),'backup'=>$audit['backup']], JSON_UNESCAPED_UNICODE) . "\n";
if (!in_array('--apply', $argv, true)) exit(0);

$result = DB::transaction(function () use ($physical, $services, $history, $audit, $marker) {
    if (Stock::withTrashed()->where('code', 'KK.HC/20261002')->exists()) throw new RuntimeException('Recovery document already exists; inspect it before rerunning');
    $goods = $physical[0];
    $balance = ProductBranch::whereKey($goods['branchRow'])->lockForUpdate()->firstOrFail();
    if ((int)$balance->qty !== $goods['current']) throw new RuntimeException('Goods balance changed after audit; rerun reconciliation');
    $stock = new Stock([
        'code'=>'KK.HC/20261002', 'branch_id'=>$goods['branch'], 'status'=>'completed',
        'user_created_id'=>1,
        'note'=>'System: phục hồi XG.108555 từ 13 về 0 theo nhật ký tồn trước 25/09 và không có giao dịch sau đó. Sửa lỗi migration tính lại từ 0, không phải kiểm đếm mới. Backup: '.basename($audit['backup']),
    ]);
    StockInventorySupport::save($stock, [$goods['product']=>[
        'amount'=>$goods['current'], 'actual_stock'=>$goods['target'], 'value'=>0,
        'note'=>'Khôi phục mốc tồn đã xác minh; audit event #'.$goods['preEvent'],
    ]]);
    $productIds = [$goods['product']];
    foreach ($services as $row) {
        $branch = ProductBranch::whereKey($row['row'])->lockForUpdate()->firstOrFail();
        $product = Product::findOrFail($branch->product_id);
        if ($product->type !== 'service' || (int)$branch->qty !== $row['qty']) throw new RuntimeException('Service balance changed after audit');
        $branch->qty=0; $branch->save();
        activity()->performedOn($branch)->withProperties(['reason'=>'Services do not manage physical stock', 'before'=>$row['qty'],'after'=>0,'backup'=>$audit['backup']])->log('inventory recovery 2026-10-02');
        $productIds[]=$branch->product_id;
    }
    $restored=0;
    foreach ($history as $row) {
        $log=ProductLog::whereKey($row['id'])->lockForUpdate()->firstOrFail();
        if ((int)$log->amount_before !== $row['before'][0] || (int)$log->amount_after !== $row['before'][1]) {
            throw new RuntimeException('Historical log changed after audit: #'.$row['id']);
        }
        // These snapshots were corroborated by both the original log audit and
        // the actual branch quantity audit. No amount, document or date changes.
        $log->forceFill(['amount_before'=>$row['original'][0], 'amount_after'=>$row['original'][1]])->save();
        $restored++;
    }
    foreach (array_unique($productIds) as $id) {
        Product::whereKey($id)->update(['qty'=>DB::table('product_branches')->where('product_id',$id)->sum('qty')]);
    }
    $result=['document'=>$stock->code,'goods'=>$goods['code'],'before'=>$goods['current'],'after'=>$goods['target'], 'services'=>count($services),'restored_log_snapshots'=>$restored,'backup'=>$audit['backup'],'completed_at'=>now()->toDateTimeString()];
    activity()->withProperties($result)->log('inventory recovery 2026-10-02 completed');
    return $result;
});
file_put_contents($marker,json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); chmod($marker,0600);
echo 'RECOVERY_APPLIED '.json_encode($result,JSON_UNESCAPED_UNICODE)."\n";

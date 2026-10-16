<?php

// Standalone integration checks: always SQLite :memory:, never the configured DB.
// php tests/inventory-regression.php /absolute/application/root [review-files-dir]
$root = $argv[1] ?? dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
if (isset($argv[2])) {
    foreach (['Product', 'ProductLog', 'ProductSupport', 'ProductInventorySupport', 'DocumentInventorySupport', 'StockInventorySupport', 'PaymentInventorySupport'] as $class) {
        require $argv[2] . '/' . $class . '.php';
    }
}
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'inventory_test', 'database.connections.inventory_test' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
]]);
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;
use Polirium\Modules\Product\Http\Model\ProductLog;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Stock\Stock;
use Polirium\Modules\Product\Http\Support\DocumentInventorySupport;
use Polirium\Modules\Product\Http\Support\PaymentInventorySupport;
use Polirium\Modules\Product\Http\Support\StockInventorySupport;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Transfer\Transfer;

if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe test connection');
}
activity()->disableLogging();
foreach (['products', 'product_elements', 'product_branches', 'product_logs', 'product_stocks', 'product_stock_products', 'product_payments', 'vendor_purchases', 'vendor_transfers','product_refunds','product_payment_refunds','product_units'] as $table) {
    Schema::create($table, function ($t) use ($table) {
        $t->id(); $t->string('uuid')->nullable(); $t->timestamps(); $t->softDeletes();
        switch ($table) {
            case 'products': $t->string('code'); $t->string('type'); $t->integer('cost')->default(10); $t->integer('qty')->default(0); break;
            case 'product_elements': $t->integer('product_id'); $t->integer('element_id'); $t->integer('qty'); break;
            case 'product_branches': $t->integer('product_id'); $t->integer('branch_id'); $t->integer('qty'); $t->unique(['product_id','branch_id']); break;
            case 'product_logs':
                foreach (['product_id','branch_id','productable_id','amount','amount_before','amount_after','value_before','value_after'] as $col) $t->integer($col);
                $t->string('productable_type'); $t->string('direction')->nullable(); break;
            case 'product_stocks':
                foreach (['branch_id','amount','increase_deviation','decrease_deviation','deviation','value'] as $col) $t->integer($col)->default(0);
                $t->string('status'); break;
            case 'product_stock_products':
                foreach (['stock_id','product_id','amount','actual_stock','quantity_difference','value','value_difference'] as $col) $t->integer($col);
                $t->string('note')->nullable(); break;
            case 'product_units': $t->integer('product_id'); $t->string('name')->nullable(); break;
            case 'product_refunds': case 'product_payment_refunds':
                $t->integer('product_payment_id'); $t->string('status')->default('success'); break;
            default: $t->integer('branch_id')->default(1); $t->string('status')->default('success'); $t->string('code')->nullable(); break;
        }
    });
}
$checks = 0;
$equal = function ($actual, $expected, string $label) use (&$checks) {
    if ($actual !== $expected) throw new RuntimeException($label . ': expected ' . json_encode($expected) . ', got ' . json_encode($actual));
    $checks++;
};
$reject = function (callable $action, string $label) use ($equal) {
    try { $action(); } catch (ValidationException) { $equal(true, true, $label); return; }
    throw new RuntimeException('Did not reject: ' . $label);
};
$p = Product::create(['code'=>'REGRESSION', 'type'=>'goods', 'cost'=>10]);
$p2 = Product::create(['code'=>'SECOND', 'type'=>'goods', 'cost'=>20]);
$service = Product::create(['code'=>'SERVICE', 'type'=>'service']);
$set = function (int $product, int $qty, int $branch = 1) { ProductBranch::updateOrCreate(['product_id'=>$product,'branch_id'=>$branch],['qty'=>$qty]); };
$qty = fn (int $product, int $branch = 1) => (int) ProductBranch::where('product_id',$product)->where('branch_id',$branch)->value('qty');
$line = fn (int $product, int $amount, int $branch = 1, bool $increase = true) => ['product_id'=>$product,'branch_id'=>$branch,'amount'=>$amount,'increase'=>$increase];
$countLine = fn (int $before, int $actual) => ['amount'=>$before,'actual_stock'=>$actual,'value'=>0,'quantity_difference'=>999,'value_difference'=>999];

// The original purchase-edit bug: 250 received, 190 sold, 60 remain.
$purchase = Purchase::create(['branch_id'=>1]);
$set($p->id,60);
DocumentInventorySupport::amendReceipt($purchase, [$line($p->id,250)], [$line($p->id,250)]);
$equal($qty($p->id),60,'unchanged consumed receipt does not restock');
$equal(ProductLog::count(),0,'unchanged receipt creates no fake movement');
DocumentInventorySupport::amendReceipt($purchase, [$line($p->id,250)], [$line($p->id,260)]);
$equal($qty($p->id),70,'receipt increase applies only difference');
DocumentInventorySupport::amendReceipt($purchase, [$line($p->id,260)], [$line($p->id,240)]);
$equal($qty($p->id),50,'receipt decrease applies only difference');
$reject(fn () => DocumentInventorySupport::amendReceipt($purchase, [$line($p->id,240)], [$line($p->id,170)]),'receipt reduction below stock');
$equal($qty($p->id),50,'rejected receipt leaves balance intact');
$equal(ProductLog::count(),2,'rejected receipt leaves history intact');

// Customer refund uses the same net receipt mechanism, once per difference.
$refund = new Polirium\Modules\Accounting\Http\Model\Refund\Refund(); $refund->id=999;
DocumentInventorySupport::amendReceipt($refund, [], [$line($p->id,3)]);
$equal($qty($p->id),53,'return adds once');
DocumentInventorySupport::amendReceipt($refund, [$line($p->id,3)], [$line($p->id,3)]);
$equal($qty($p->id),53,'saving completed return does not add again');
$set($p->id,10);
DocumentInventorySupport::amendReceipt($refund, [$line($p->id,30,1,false)], [$line($p->id,30,1,false)]);
$equal($qty($p->id),10,'unchanged supplier return does not require/export full quantity again');
DocumentInventorySupport::amendReceipt($refund, [$line($p->id,30,1,false)], [$line($p->id,35,1,false)]);
$equal($qty($p->id),5,'supplier return edit exports only difference');
$reject(fn () => DocumentInventorySupport::amendReceipt($refund, [$line($p->id,35,1,false)], [$line($p->id,41,1,false)]),'supplier return edit insufficient stock');
$equal($qty($p->id),5,'failed supplier return edit leaves stock intact');

// Completion is atomic, validates a fresh snapshot and recomputes differences.
$set($p->id,20);
$stock = new Stock(['branch_id'=>1,'status'=>'completed']);
StockInventorySupport::save($stock, [$p->id=>$countLine(20,12)]);
$equal($qty($p->id),12,'stock count sets physical quantity');
$equal((int)$stock->fresh()->deviation,-8,'stock count ignores client supplied difference');
$reject(fn () => StockInventorySupport::save($stock, [$p->id=>$countLine(12,4)]),'repeated completion');
$equal($qty($p->id),12,'repeated completion cannot deduct again');
$stale = new Stock(['branch_id'=>1,'status'=>'completed']);
$reject(fn () => StockInventorySupport::save($stale, [$p->id=>$countLine(20,8)]),'stale snapshot');
$equal($stale->exists,false,'stale count saves no document');
$set($p2->id,10);
$partial = new Stock(['branch_id'=>1,'status'=>'completed']);
$reject(fn () => StockInventorySupport::save($partial, [$p->id=>$countLine(12,9),$p2->id=>$countLine(99,8)]),'invalid second count line');
$equal($qty($p->id),12,'multi-line failure rolls back first line');
$later = new Stock(['branch_id'=>1,'status'=>'completed']);
StockInventorySupport::save($later, [$p->id=>$countLine(12,11)]);
$reject(fn () => StockInventorySupport::cancel($stock->id),'cannot undo earlier count anchor');
StockInventorySupport::cancel($later->id);
$equal($qty($p->id),12,'cancel latest count reverses only its difference');
StockInventorySupport::cancel($later->id);
$equal($qty($p->id),12,'cancel count twice is idempotent');

// Services cannot enter physical stock through the partially-selected model.
$beforeLogs = ProductLog::count();
product_logs($service->id,1,Purchase::class,3,0,0,true,1);
$equal($qty($service->id),0,'service receipt changes no stock');
$equal(ProductLog::count(),$beforeLogs,'service has no stock movement');
$reject(fn () => product_logs($p->id,1,Purchase::class,-1,0,0,true,1),'negative movement amount');
$reject(fn () => product_logs(999999,1,Purchase::class,1,0,0,true,1),'missing goods cannot silently skip stock');
$reject(fn () => product_logs($p->id,1,Payment::class,13,0,0,false,1),'insufficient outbound is rejected instead of clamped');
$equal($qty($p->id),12,'failed outbound cannot silently remove partial stock');

// Transfer edits/reversals conserve total stock, including failed destinations.
$transfer = Transfer::create(['branch_id'=>1]);
$set($p->id,10,1); $set($p->id,0,2);
$moves = [$line($p->id,5,1,false),$line($p->id,5,2,true)];
DocumentInventorySupport::amendReceipt($transfer,[],$moves);
$equal([$qty($p->id,1),$qty($p->id,2)],[5,5],'transfer both branches together');
DocumentInventorySupport::amendReceipt($transfer,$moves,$moves);
$equal([$qty($p->id,1),$qty($p->id,2)],[5,5],'transfer unchanged edit does not double apply');
$set($p->id,2,2);
$reject(fn () => DocumentInventorySupport::reverseDocument($transfer),'consumed transfer cannot silently reverse');
$equal([$qty($p->id,1),$qty($p->id,2)],[5,2],'failed transfer reversal rolls back source restoration');
$set($p->id,5,2);
DocumentInventorySupport::reverseDocument($transfer);
DocumentInventorySupport::reverseDocument($transfer);
$equal([$qty($p->id,1),$qty($p->id,2)],[10,0],'double transfer reversal is idempotent');

// Payment cancellation keeps a signed audit trail and cannot restore twice.
$payment = Payment::create(['branch_id'=>1, 'code'=>'TEST-SALE']);
$set($p->id,20);
product_logs($p->id,$payment->id,Payment::class,7,0,0,false,1);
PaymentInventorySupport::restoreExportedStock($payment);
$equal($qty($p->id),20,'cancel restores export');
PaymentInventorySupport::restoreExportedStock($payment);
$equal($qty($p->id),20,'cancel restores only outstanding exports');
$draft = Payment::create(['branch_id'=>1,'status'=>'temp', 'code'=>'TEST-DRAFT']);
PaymentInventorySupport::restoreExportedStock($draft);
$equal($qty($p->id),20,'draft cancellation cannot fabricate stock');
DB::table('product_refunds')->insert(['product_payment_id'=>$payment->id]);
$reject(fn () => PaymentInventorySupport::restoreExportedStock($payment),'cannot restore full invoice after a return');
$equal($qty($p->id),20,'return guard leaves stock intact');
$missingRejected=false;
try { Polirium\Modules\Product\Http\Support\ProductInventorySupport::exportPaymentItems([(object)['product_id'=>999999,'amount'=>1]], $payment); } catch (RuntimeException) { $missingRejected=true; }
$equal($missingRejected,true,'invoice cannot export missing goods');
$combo=Product::create(['code'=>'BAD-COMBO','type'=>'combo']);
Polirium\Modules\Product\Http\Model\ProductElement::create(['product_id'=>$combo->id,'element_id'=>999999,'qty'=>1]);
$missingElementRejected=false;
try { Polirium\Modules\Product\Http\Support\ProductInventorySupport::requirements($combo,1); } catch (RuntimeException) { $missingElementRejected=true; }
$equal($missingElementRejected,true,'combo cannot skip a deleted component');

// A log-save failure rolls back the stock update itself.
$failLog = true;
ProductLog::creating(function () use (&$failLog) { if ($failLog) throw new RuntimeException('simulated log failure'); });
try { product_logs($p->id,999,Purchase::class,2,0,0,true,1); } catch (RuntimeException $e) { if ($e->getMessage() !== 'simulated log failure') throw $e; }
$failLog=false;
$equal($qty($p->id),20,'log write failure rolls back balance');

// Editing an invoice writes only the per-product difference (16/10 stock card noise).
$sync = fn (Payment $sale, array $lines) => Polirium\Modules\Product\Http\Support\ProductInventorySupport::syncPaymentItems(
    array_map(fn ($l) => (object) ['product_id' => $l[0], 'amount' => $l[1]], $lines), $sale);
$logsFor = fn (Payment $sale) => ProductLog::where('productable_type',Payment::class)->where('productable_id',$sale->id)->count();
$sale = Payment::create(['branch_id'=>1, 'code'=>'TEST-EDIT']);
$set($p->id,20); $set($p2->id,10);
$sync($sale, [[$p->id,5],[$p2->id,2]]);
$equal([$qty($p->id),$qty($p2->id),$logsFor($sale)],[15,8,2],'new invoice exports its lines');
$sync($sale, [[$p->id,5],[$p2->id,2]]);
$equal([$qty($p->id),$qty($p2->id),$logsFor($sale)],[15,8,2],'unchanged invoice edit writes no stock card line');
$sync($sale, [[$p->id,2],[$p->id,1],[$p2->id,2]]);
$equal([$qty($p->id),$logsFor($sale)],[17,3],'invoice decrease returns only the difference, duplicate lines merged');
$sync($sale, [[$p2->id,4]]);
$equal([$qty($p->id),$qty($p2->id),$logsFor($sale)],[20,6,5],'removed line restored, increased line exported by difference');
$stockRejected=false; try { $sync($sale, [[$p2->id,99]]); } catch (RuntimeException) { $stockRejected=true; }
$equal($stockRejected,true,'invoice edit beyond stock');
$equal([$qty($p2->id),$logsFor($sale)],[6,5],'rejected invoice edit leaves stock and card intact');
DB::table('product_payment_refunds')->insert(['product_payment_id'=>$sale->id,'status'=>'success']);
$sync($sale, [[$p2->id,4]]);
$equal($qty($p2->id),6,'returned invoice may be saved without quantity change');
$reject(fn () => $sync($sale, [[$p2->id,3]]),'returned invoice cannot change exported quantity');
DB::table('product_payment_refunds')->where('product_payment_id',$sale->id)->delete();
$sale->status = 'cancel'; $sale->save();
$sync($sale, [[$p2->id,4]]);
$equal($qty($p2->id),10,'cancelled invoice restores everything exported');
$equal((int) Product::find($p2->id)->qty, 10, 'products.qty mirrors branch balance');
$inLog = ProductLog::where('productable_type',Payment::class)->where('productable_id',$sale->id)->where('direction','in')->orderByDesc('id')->first();
$equal($inLog->type_name, 'Hủy đơn', 'cancelled invoice reversal is labelled as cancellation');
$live = Payment::create(['branch_id'=>1, 'code'=>'TEST-ADJ']);
$sync($live, [[$p->id,3]]); $sync($live, [[$p->id,1]]);
$equal(ProductLog::where('productable_type',Payment::class)->where('productable_id',$live->id)->where('direction','in')->first()->type_name, 'Điều chỉnh hóa đơn', 'edit of a live invoice is not labelled as cancellation');

// Import and opening stock go through the stock card as a difference.
$set($p->id,20);
$before = ProductLog::count();
Polirium\Modules\Product\Http\Support\ProductSupport::setBranchQuantity($p, 1, 25);
$equal([$qty($p->id), ProductLog::count() - $before], [25, 1], 'import increase is logged');
Polirium\Modules\Product\Http\Support\ProductSupport::setBranchQuantity($p, 1, 25);
$equal(ProductLog::count() - $before, 1, 'import without change logs nothing');
Polirium\Modules\Product\Http\Support\ProductSupport::setBranchQuantity($p, 1, 4);
$last = ProductLog::orderByDesc('id')->first();
$equal([$qty($p->id), $last->direction, (int) $last->amount_before, (int) $last->amount_after], [4, 'out', 25, 4], 'import decrease is logged with balances');
$set($service->id, 3);
Polirium\Modules\Product\Http\Support\ProductSupport::setBranchQuantity($service, 1, 9);
$equal($qty($service->id), 0, 'service cannot receive stock through import');

// Legacy marker sources (not a model class) render instead of breaking the stock card.
$marker = new ProductLog(); $marker->forceFill(['product_id'=>$p->id,'branch_id'=>1,'productable_type'=>'system_fix_negative_stock','productable_id'=>0,'amount'=>1,'direction'=>'in','amount_before'=>0,'amount_after'=>1,'value_before'=>0,'value_after'=>0]);
$equal([$marker->productable, $marker->display_code, $marker->type_name, $marker->order_status, $marker->url], [null, '-', 'Hệ thống hiệu chỉnh tồn âm', null, null], 'legacy marker log renders safely');

// Copying a product copies its definition only: fresh uuid/code, no stock, no history.
$set($p->id, 7);
$copy = $p->fresh()->duplicate();
$equal([$copy->uuid !== $p->uuid, $copy->code, $qty($copy->id), (int) $copy->qty, ProductLog::where('product_id',$copy->id)->count()],
    [true, 'REGRESSION(copy)', 0, 0, 0], 'product copy has fresh identity and no stock or history');
$equal(Product::find($p->id)->duplicate()->code, 'REGRESSION(copy 2)', 'second copy gets a unique code');
$comboCopy = Product::create(['code'=>'COMBO','type'=>'combo']);
Polirium\Modules\Product\Http\Model\ProductElement::create(['product_id'=>$comboCopy->id,'element_id'=>$p2->id,'qty'=>2]);
$comboDup = $comboCopy->duplicate();
$equal(Polirium\Modules\Product\Http\Model\ProductElement::where('product_id',$comboDup->id)->pluck('qty','element_id')->all(), [$p2->id => 2], 'combo copy keeps its components');

// Combo sale exports components, cancellation restores them, components cannot be deleted.
$set($p2->id, 10);
$comboSale = Payment::create(['branch_id'=>1, 'code'=>'TEST-COMBO']);
$sync($comboSale, [[$comboDup->id, 3]]);
$equal([$qty($p2->id), $qty($comboDup->id)], [4, 0], 'combo sale exports 3 x 2 components and holds no stock itself');
$reject(fn () => $p2->delete(), 'component of a combo cannot be deleted');
$comboSale->status = 'cancel'; $comboSale->save();
$sync($comboSale, [[$comboDup->id, 3]]);
$equal($qty($p2->id), 10, 'cancelled combo sale restores its components');

// Type and qty cannot be rewritten on goods with history.
$typed = Product::find($p->id); $typed->type = 'service';
$reject(fn () => $typed->save(), 'goods with stock card cannot become a service');
$equal(Product::find($p->id)->type, 'goods', 'rejected type change keeps type');
$fresh2 = Product::create(['code'=>'FRESH','type'=>'goods']);
$fresh2->type = 'service'; $fresh2->save();
$equal(Product::find($fresh2->id)->type, 'service', 'unused product may change type');
$mirror = Product::find($p2->id); $mirror->qty = 999; $mirror->save();
$equal((int) Product::find($p2->id)->qty, 10, 'products.qty cannot be overwritten by a form');

// Products with history cannot be hard-deleted; unused products take their balances along.
$reject(fn () => $p->delete(), 'product with stock card cannot be deleted');
$equal(Product::whereKey($p->id)->exists(), true, 'rejected delete keeps product');
$unused = Product::create(['code'=>'UNUSED', 'type'=>'goods']);
$set($unused->id, 0);
$unused->delete();
$equal(ProductBranch::where('product_id',$unused->id)->exists(), false, 'deleting unused product removes its balances');
echo "REGRESSION_PASS {$checks} checks, SQLite :memory:\n";

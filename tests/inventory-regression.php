<?php

// Standalone integration checks: always SQLite :memory:, never the configured DB.
// php tests/inventory-regression.php /absolute/application/root [review-files-dir]
$root = $argv[1] ?? dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
if (isset($argv[2])) {
    foreach (['ProductLog', 'ProductSupport', 'DocumentInventorySupport', 'StockInventorySupport', 'PaymentInventorySupport'] as $class) {
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
foreach (['products', 'product_branches', 'product_logs', 'product_stocks', 'product_stock_products', 'product_payments', 'vendor_purchases', 'vendor_transfers','product_refunds','product_payment_refunds'] as $table) {
    Schema::create($table, function ($t) use ($table) {
        $t->id(); $t->string('uuid')->nullable(); $t->timestamps(); $t->softDeletes();
        switch ($table) {
            case 'products': $t->string('code'); $t->string('type'); $t->integer('cost')->default(10); break;
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

// A log-save failure rolls back the stock update itself.
$failLog = true;
ProductLog::creating(function () use (&$failLog) { if ($failLog) throw new RuntimeException('simulated log failure'); });
try { product_logs($p->id,999,Purchase::class,2,0,0,true,1); } catch (RuntimeException $e) { if ($e->getMessage() !== 'simulated log failure') throw $e; }
$failLog=false;
$equal($qty($p->id),20,'log write failure rolls back balance');
echo "REGRESSION_PASS {$checks} checks, SQLite :memory:\n";

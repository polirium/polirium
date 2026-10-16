<?php

// One-off, approved 16/10/2026: delete every stock movement and the documents that
// create them (invoices, purchases, purchase returns, stock counts, cash receipts),
// keep products / customers / vendors / settings, set stock and vendor debt to 0 and
// restart document numbering. Dry run unless --apply. Take a full DB dump first.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$apply = in_array('--apply', $argv, true);

// Child tables first.
$tables = [
    'product_logs',
    'product_payment_refund_products', 'product_payment_refunds',
    'product_refund_products', 'product_refunds',
    'product_payment_deliveries', 'product_payment_products', 'product_payments',
    'accounting_receipts',
    'vendor_purchase_refund_products', 'vendor_purchase_refunds',
    'vendor_purchase_products', 'vendor_purchases',
    'vendor_transfer_products', 'vendor_transfers',
    'product_stock_products', 'product_stocks',
];

$m = 'Polirium\\Modules\\';
$activitySubjects = [
    $m . 'Product\\Http\\Model\\ProductLog',
    $m . 'Product\\Http\\Model\\ProductBranch',
    $m . 'Product\\Http\\Model\\Payment\\Payment',
    $m . 'Product\\Http\\Model\\Payment\\PaymentProduct',
    $m . 'Product\\Http\\Model\\Payment\\PaymentDelivery',
    $m . 'Product\\Http\\Model\\Refund\\Refund',
    $m . 'Product\\Http\\Model\\Refund\\RefundProduct',
    $m . 'Product\\Http\\Model\\Stock\\Stock',
    $m . 'Product\\Http\\Model\\Stock\\StockProduct',
    $m . 'Accounting\\Http\\Model\\Receipt',
    $m . 'Accounting\\Http\\Model\\Refund\\Refund',
    $m . 'Vendor\\Http\\Model\\Purchase\\Purchase',
    $m . 'Vendor\\Http\\Model\\Purchase\\PurchaseProduct',
    $m . 'Vendor\\Http\\Model\\Refund\\Refund',
    $m . 'Vendor\\Http\\Model\\Refund\\RefundProduct',
    $m . 'Vendor\\Http\\Model\\Transfer\\Transfer',
    $m . 'Vendor\\Http\\Model\\Transfer\\TransferProduct',
];

$plan = [];
foreach ($tables as $table) {
    $plan['delete'][$table] = Schema::hasTable($table) ? DB::table($table)->count() : 'missing';
}
$plan['delete']['activity_log'] = DB::table('activity_log')->whereIn('subject_type', $activitySubjects)->count();
$plan['reset'] = [
    'product_branches.qty' => DB::table('product_branches')->where('qty', '!=', 0)->count(),
    'products.qty' => DB::table('products')->where('qty', '!=', 0)->count(),
    'vendors.debt/total_purchase' => DB::table('vendors')->where(fn ($q) => $q->where('debt', '!=', 0)->orWhere('total_purchase', '!=', 0))->count(),
];
$plan['keep'] = [
    'products' => DB::table('products')->count(),
    'product_elements' => DB::table('product_elements')->count(),
    'customers' => DB::table('customers')->count(),
    'vendors' => DB::table('vendors')->count(),
    'users' => DB::table('users')->count(),
    'activity_log_kept' => DB::table('activity_log')->whereNotIn('subject_type', $activitySubjects)->orWhereNull('subject_type')->count(),
];
echo 'RESET_PLAN ' . json_encode(['apply' => $apply] + $plan, JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (! $apply) {
    exit(0);
}

$before = $plan['keep'];

Schema::disableForeignKeyConstraints();
try {
    DB::transaction(function () use ($tables, $activitySubjects) {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        DB::table('activity_log')->whereIn('subject_type', $activitySubjects)->delete();
        DB::table('product_branches')->update(['qty' => 0]);
        DB::table('products')->update(['qty' => 0]);
        DB::table('vendors')->update(['debt' => 0, 'total_purchase' => 0]);
    });
} finally {
    Schema::enableForeignKeyConstraints();
}

// Restart numbering (BH/00001, NH/00001...): codes derive from the next id.
// ALTER TABLE commits implicitly, so it runs after the data transaction.
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
    }
}

$after = [];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $after[$table] = DB::table($table)->count();
    }
}
$kept = [
    'products' => DB::table('products')->count(),
    'product_elements' => DB::table('product_elements')->count(),
    'customers' => DB::table('customers')->count(),
    'vendors' => DB::table('vendors')->count(),
    'users' => DB::table('users')->count(),
];
$stockLeft = (int) DB::table('product_branches')->sum('qty');

if (array_sum($after) !== 0 || $stockLeft !== 0 || array_diff_assoc($kept, array_intersect_key($before, $kept))) {
    throw new RuntimeException('Post-check failed: ' . json_encode(compact('after', 'kept', 'stockLeft')));
}

file_put_contents(storage_path('app/reset-transactions-20261016-applied.json'), json_encode(['applied_at' => now()->toDateTimeString()] + $plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'RESET_APPLIED ' . json_encode(['remaining_rows' => array_sum($after), 'stock_sum' => $stockLeft, 'kept' => $kept], JSON_UNESCAPED_UNICODE) . PHP_EOL;

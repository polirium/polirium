<?php

// One-off cleanup of the reference exceptions left by the 02/10 audit.
// Dry run unless --apply. Never changes the stock of an existing product.
//   --zero-orphan-stock  also zero branch rows of deleted products that still hold stock
//                        (only after the shop confirms those goods no longer exist)
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$marker = storage_path('app/inventory-cleanup-20261016-applied.json');
if (is_file($marker)) {
    echo "CLEANUP_ALREADY_APPLIED\n";
    exit(0);
}
$apply = in_array('--apply', $argv, true);
$zeroOrphanStock = in_array('--zero-orphan-stock', $argv, true);

$plan = function () use ($zeroOrphanStock) {
    $products = DB::table('products')->pluck('id')->flip();

    // 1. products.qty mirrors the branch balances (display-only column).
    $qty = DB::table('products as p')
        ->leftJoin('product_branches as b', 'b.product_id', '=', 'p.id')
        ->groupBy('p.id', 'p.qty')
        ->selectRaw('p.id, p.qty, COALESCE(SUM(b.qty), 0) AS branch_qty')
        ->get()
        ->filter(fn ($r) => (int) $r->qty !== (int) $r->branch_qty)
        ->values();

    // 2. Branch balances of deleted products.
    $orphanBranches = DB::table('product_branches')->get()->filter(fn ($b) => ! $products->has($b->product_id));
    $deleteBranches = $orphanBranches->filter(fn ($b) => (int) $b->qty === 0 || $zeroOrphanStock)->values();
    $keptBranches = $orphanBranches->filter(fn ($b) => (int) $b->qty !== 0 && ! $zeroOrphanStock)->values();

    // 3. Lines whose parent document no longer exists.
    $lines = [];
    foreach ([['product_payment_products', 'product_payment_id', 'product_payments'], ['product_refund_products', 'product_refund_id', 'product_refunds']] as [$child, $foreign, $parent]) {
        $parents = DB::table($parent)->pluck('id')->flip();
        foreach (DB::table($child)->get() as $line) {
            if (! $parents->has($line->$foreign)) {
                $lines[] = ['table' => $child, 'row' => $line];
            }
        }
    }

    return compact('qty', 'deleteBranches', 'keptBranches', 'lines');
};

$p = $plan();
echo 'CLEANUP_PLAN ' . json_encode([
    'apply' => $apply,
    'products_qty_sync' => $p['qty']->count(),
    'orphan_branches_delete' => $p['deleteBranches']->map(fn ($b) => ['row' => $b->id, 'product' => $b->product_id, 'qty' => (int) $b->qty]),
    'orphan_branches_kept_with_stock' => $p['keptBranches']->map(fn ($b) => ['row' => $b->id, 'product' => $b->product_id, 'qty' => (int) $b->qty]),
    'orphan_lines_delete' => array_map(fn ($l) => ['table' => $l['table'], 'row' => $l['row']->id], $p['lines']),
], JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (! $apply) {
    exit(0);
}

$backup = storage_path('app/inventory-before-cleanup-' . date('Ymd-His') . '.json');
file_put_contents($backup, json_encode([
    'products_qty' => $p['qty'],
    'product_branches' => $p['deleteBranches'],
    'lines' => $p['lines'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
chmod($backup, 0600);

$result = DB::transaction(function () use ($plan, $p) {
    // Re-plan under lock; refuse if anything moved since the dry run output.
    DB::table('product_branches')->lockForUpdate()->get();
    $again = $plan();
    if ($again['qty']->count() !== $p['qty']->count() || $again['deleteBranches']->count() !== $p['deleteBranches']->count() || count($again['lines']) !== count($p['lines'])) {
        throw new RuntimeException('Data changed during cleanup, aborting');
    }

    foreach ($again['qty'] as $row) {
        DB::table('products')->where('id', $row->id)->update(['qty' => (int) $row->branch_qty]);
    }
    $branches = DB::table('product_branches')->whereIn('id', $again['deleteBranches']->pluck('id'))->delete();
    $lines = 0;
    foreach ($again['lines'] as $line) {
        $lines += DB::table($line['table'])->where('id', $line['row']->id)->delete();
    }

    return ['applied_at' => now()->toDateTimeString(), 'products_qty' => $again['qty']->count(), 'branches' => $branches, 'lines' => $lines];
});
$result['backup'] = $backup;
file_put_contents($marker, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
chmod($marker, 0600);
echo 'CLEANUP_APPLIED ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;

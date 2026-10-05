<?php

// Read-only importer regression check. Usage: php tests/refund-import-regression.php /app/root
$root = $argv[1] ?? dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Collection;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Vendor\Imports\RefundImport;

$product = Product::where('type', 'product')->whereNotNull('code')->firstOrFail();
$import = new RefundImport();
$import->collection(new Collection([
    new Collection([
        'ma_hang' => $product->code,
        'so_luong' => '1,000',
        'gia_tra_lai' => '41.000',
        'giam_gia_tra_lai' => '1.000',
    ]),
]));
$rows = $import->getProducts();
if (count($rows) !== 1 || (int) $rows[$product->id]['amount'] !== 1000 || (int) $rows[$product->id]['price'] !== 41000 || $import->getErrors() !== []) {
    throw new RuntimeException(json_encode(['rows'=>$rows,'errors'=>$import->getErrors()], JSON_UNESCAPED_UNICODE));
}
echo "REFUND_IMPORT_PASS code={$product->code} amount=1000 price=41000\n";

<?php

// One-off: product copies kept their source uuid. Give every copy a fresh uuid,
// keeping it on the oldest product. uuid is not used to look products up.
// Dry run unless --apply.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$apply = in_array('--apply', $argv, true);
$groups = DB::table('products')->select('uuid')->groupBy('uuid')->havingRaw('COUNT(*) > 1')->pluck('uuid');
$changes = [];
foreach ($groups as $uuid) {
    $ids = DB::table('products')->where('uuid', $uuid)->orderBy('id')->pluck('id');
    foreach ($ids->slice(1) as $id) {
        $changes[] = ['id' => $id, 'old' => $uuid, 'new' => (string) Str::orderedUuid()];
    }
}
echo 'UUID_PLAN ' . json_encode(['apply' => $apply, 'groups' => $groups->count(), 'products' => count($changes)]) . PHP_EOL;
if (! $apply || $changes === []) {
    exit(0);
}

$backup = storage_path('app/products-uuid-before-' . date('Ymd-His') . '.json');
file_put_contents($backup, json_encode($changes, JSON_PRETTY_PRINT));
chmod($backup, 0600);
DB::transaction(function () use ($changes) {
    foreach ($changes as $change) {
        DB::table('products')->where('id', $change['id'])->where('uuid', $change['old'])->update(['uuid' => $change['new']]);
    }
});
$left = DB::table('products')->select('uuid')->groupBy('uuid')->havingRaw('COUNT(*) > 1')->count();
echo 'UUID_APPLIED ' . json_encode(['products' => count($changes), 'duplicate_groups_left' => $left, 'backup' => $backup]) . PHP_EOL;

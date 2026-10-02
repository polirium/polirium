<?php

namespace Polirium\Modules\Product\Http\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DocumentInventorySupport
{
    public static function reverseDocument(Model $document): void
    {
        DB::transaction(function () use ($document) {
            $document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $logs = \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', $document::class)
                ->where('productable_id', $document->id)->lockForUpdate()->get();
            $net = [];
            foreach ($logs as $log) {
                $branch = (int) ($log->branch_id ?: $document->branch_id ?: 1);
                $key = $branch . ':' . $log->product_id;
                $net[$key] ??= ['product_id' => $log->product_id, 'branch_id' => $branch, 'amount' => 0];
                $net[$key]['amount'] += $log->signed_amount;
            }
            $before = [];
            foreach ($net as $line) {
                $line['increase'] = $line['amount'] > 0;
                $line['amount'] = abs($line['amount']);
                $before[] = $line;
            }
            self::amendReceipt($document, $before, []);
        });
    }

    /**
     * Amend a receipt by its net difference. Reversing an entire consumed receipt
     * and importing it again must never replenish goods that have already sold.
     * Callers lock the document and save its lines in the same transaction.
     */
    public static function amendReceipt(Model $document, array $before, array $after): void
    {
        DB::transaction(function () use ($document, $before, $after) {
            $deltas = [];
            foreach ([$before, $after] as $index => $lines) {
                foreach ($lines as $line) {
                    $branch = (int) $line['branch_id'];
                    $product = (int) $line['product_id'];
                    $amount = (int) $line['amount'];
                    if ($amount < 0 || $branch < 1 || $product < 1) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['products' => 'Dòng hàng hoặc số lượng không hợp lệ.']);
                    }
                    $key = $branch . ':' . $product;
                    $deltas[$key] ??= ['product' => $product, 'branch' => $branch, 'amount' => 0];
                    $deltas[$key]['amount'] += ($index === 0 ? -1 : 1) * (($line['increase'] ?? true) ? 1 : -1) * $amount;
                }
            }
            ksort($deltas);
            foreach ($deltas as $delta) {
                if ($delta['amount'] === 0) continue;
                $cost = (int) (\Polirium\Modules\Product\Http\Model\Product::find($delta['product'])?->cost ?? 0);
                product_logs($delta['product'], $document->id, $document::class,
                    abs($delta['amount']), $cost, abs($delta['amount']) * $cost, $delta['amount'] > 0, $delta['branch'], now());
            }
        });
    }
}

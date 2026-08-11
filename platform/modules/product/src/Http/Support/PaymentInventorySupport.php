<?php

namespace Polirium\Modules\Product\Http\Support;

use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductLog;

final class PaymentInventorySupport
{
    /**
     * Restore only quantities that were actually exported for this payment.
     * Draft/temp payments have no outbound logs, so cancelling them cannot alter stock.
     */
    public static function restoreExportedStock(Payment $payment): int
    {
        $logs = ProductLog::query()
            ->where('productable_type', Payment::class)
            ->where('productable_id', $payment->id)
            ->lockForUpdate()
            ->get(['product_id', 'branch_id', 'amount', 'direction', 'amount_before', 'amount_after']);

        $outstanding = [];

        foreach ($logs as $log) {
            $branchId = (int) ($log->branch_id ?: $payment->branch_id ?: 1);
            $key = $log->product_id . ':' . $branchId;
            $amount = abs((int) $log->amount);

            if (! isset($outstanding[$key])) {
                $outstanding[$key] = [
                    'product_id' => (int) $log->product_id,
                    'branch_id' => $branchId,
                    'quantity' => 0,
                ];
            }

            $isInbound = $log->direction === 'in'
                || ($log->direction === null && $log->amount_after > $log->amount_before);

            $outstanding[$key]['quantity'] += $isInbound ? -$amount : $amount;
        }

        $restored = 0;

        foreach ($outstanding as $item) {
            if ($item['quantity'] <= 0) {
                continue;
            }

            $product = Product::find($item['product_id']);

            product_logs(
                $item['product_id'],
                $payment->id,
                Payment::class,
                $item['quantity'],
                $product?->cost ?? 0,
                0,
                true,
                $item['branch_id'],
                now()
            );

            $restored++;
        }

        return $restored;
    }
}

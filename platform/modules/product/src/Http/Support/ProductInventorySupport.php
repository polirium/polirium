<?php

namespace Polirium\Modules\Product\Http\Support;

use Illuminate\Support\Facades\DB;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;
use RuntimeException;

final class ProductInventorySupport
{
    private const INACTIVE_STATUSES = ['temp', 'draft', 'cancel', 'cancelled', 'failed', 'delivery_failed'];

    /**
     * Return the maximum number of units that can be sold from a branch.
     * A combo has no stock of its own; its availability is derived from its
     * component with the lowest available ratio.
     */
    public static function availableQuantity(Product $product, ?int $branchId): int
    {
        if ($product->type === 'service') {
            return PHP_INT_MAX;
        }

        $requirements = self::requirements($product, 1);

        if ($requirements === []) {
            return 0;
        }

        $available = PHP_INT_MAX;

        foreach ($requirements as $requirement) {
            $stock = (int) DB::table('product_branches')
                ->where('product_id', $requirement['product']->id)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->sum('qty');

            $available = min($available, intdiv($stock, $requirement['quantity']));
        }

        return $available === PHP_INT_MAX ? 0 : $available;
    }

    /**
     * Validate and export every inventory item represented by payment lines.
     * Combo lines are expanded and duplicate components are aggregated first.
     */
    public static function exportPaymentItems(iterable $items, Payment $payment): void
    {
        $requirements = [];

        foreach ($items as $item) {
            $product = $item->product ?? Product::find($item->product_id);

            if (! $product) {
                throw new RuntimeException('Hàng hóa trên hóa đơn không còn tồn tại. Vui lòng kiểm tra lại trước khi xuất kho.');
            }

            self::mergeRequirements(
                $requirements,
                self::requirements($product, (int) $item->amount),
            );
        }

        self::assertAvailable($requirements, (int) $payment->branch_id);

        foreach ($requirements as $requirement) {
            $component = $requirement['product'];
            $quantity = $requirement['quantity'];

            product_logs(
                $component->id,
                $payment->id,
                Payment::class,
                $quantity,
                (int) $component->cost,
                (int) $component->cost * $quantity,
                false,
                (int) $payment->branch_id,
                now(),
            );
        }
    }

    /**
     * Bring the stock exported for a payment in line with its current lines,
     * writing only the per-product difference. Editing an invoice without
     * changing quantities must not produce a reversal and re-export on the
     * stock card. Callers save the payment lines in the same transaction.
     */
    public static function syncPaymentItems(iterable $items, Payment $payment): void
    {
        DB::transaction(function () use ($items, $payment) {
            Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $branchId = (int) ($payment->branch_id ?: 1);
            $desired = [];

            if (! in_array($payment->status, self::INACTIVE_STATUSES, true)) {
                foreach ($items as $item) {
                    $product = $item->product ?? Product::find($item->product_id);

                    if (! $product) {
                        throw new RuntimeException('Hàng hóa trên hóa đơn không còn tồn tại. Vui lòng kiểm tra lại trước khi xuất kho.');
                    }

                    self::mergeRequirements($desired, self::requirements($product, (int) $item->amount));
                }
            }

            $exported = [];
            $logs = \Polirium\Modules\Product\Http\Model\ProductLog::query()
                ->where('productable_type', Payment::class)
                ->where('productable_id', $payment->id)
                ->lockForUpdate()
                ->get();

            foreach ($logs as $log) {
                $key = (int) ($log->branch_id ?: $branchId) . ':' . $log->product_id;
                $exported[$key] = ($exported[$key] ?? 0) - $log->signed_amount;
            }

            $deltas = [];

            foreach ($desired as $productId => $requirement) {
                $deltas[$branchId . ':' . $productId] = $requirement['quantity'];
            }

            foreach ($exported as $key => $quantity) {
                $deltas[$key] = ($deltas[$key] ?? 0) - $quantity;
            }

            $deltas = array_filter($deltas);

            if ($deltas === []) {
                return;
            }

            if (DB::table('product_refunds')->where('product_payment_id', $payment->id)->exists()
                || DB::table('product_payment_refunds')->where('product_payment_id', $payment->id)->where('status', 'success')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment' => 'Hóa đơn đã có trả hàng. Không thể thay đổi số lượng xuất kho; vui lòng xử lý phiếu trả hàng trước khi sửa hóa đơn.',
                ]);
            }

            ksort($deltas);

            // Return stock first so a swap between lines never fails for lack of stock.
            foreach ([true, false] as $increase) {
                foreach ($deltas as $key => $delta) {
                    if (($delta < 0) !== $increase) {
                        continue;
                    }

                    [$deltaBranch, $productId] = array_map('intval', explode(':', $key));
                    $product = $desired[$productId]['product'] ?? Product::find($productId);
                    $quantity = abs($delta);

                    if (! $increase) {
                        self::assertAvailable([$productId => ['product' => $product, 'quantity' => $quantity]], $deltaBranch);
                    }

                    product_logs(
                        $productId,
                        $payment->id,
                        Payment::class,
                        $quantity,
                        (int) ($product?->cost ?? 0),
                        (int) ($product?->cost ?? 0) * $quantity,
                        $increase,
                        $deltaBranch,
                        now(),
                    );
                }
            }
        });
    }

    /**
     * Expand a product into real stock-managed products.
     */
    public static function requirements(Product $product, int $quantity, array $path = []): array
    {
        if ($quantity <= 0 || $product->type === 'service') {
            return [];
        }

        if ($product->type !== 'combo') {
            return [
                $product->id => [
                    'product' => $product,
                    'quantity' => $quantity,
                ],
            ];
        }

        if (in_array($product->id, $path, true)) {
            throw new RuntimeException("Combo {$product->name} có thành phần lặp vòng.");
        }

        $path[] = $product->id;
        $elements = $product->elements()->with('element')->get();

        if ($elements->isEmpty()) {
            throw new RuntimeException("Combo {$product->name} chưa có thành phần.");
        }

        $requirements = [];

        foreach ($elements as $element) {
            if (! $element->element) {
                throw new RuntimeException("Combo {$product->name} có thành phần đã bị xóa. Vui lòng kiểm tra lại cấu hình combo.");
            }

            self::mergeRequirements(
                $requirements,
                self::requirements($element->element, $quantity * (int) $element->qty, $path),
            );
        }

        return $requirements;
    }

    private static function assertAvailable(array $requirements, int $branchId): void
    {
        foreach ($requirements as $requirement) {
            $product = $requirement['product'];
            $required = $requirement['quantity'];
            $available = (int) DB::table('product_branches')
                ->where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->value('qty');

            if ($available < $required) {
                throw new RuntimeException(
                    "Không đủ tồn kho hàng {$product->name} (cần {$required}, còn {$available})."
                );
            }
        }
    }

    private static function mergeRequirements(array &$target, array $requirements): void
    {
        foreach ($requirements as $productId => $requirement) {
            if (! isset($target[$productId])) {
                $target[$productId] = $requirement;
                continue;
            }

            $target[$productId]['quantity'] += $requirement['quantity'];
        }
    }
}

<?php

namespace Polirium\Modules\Product\Http\Support;

use Illuminate\Support\Facades\DB;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;
use RuntimeException;

final class ProductInventorySupport
{
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
                continue;
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
                continue;
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

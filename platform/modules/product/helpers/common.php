<?php

use Illuminate\Database\Eloquent\Model;
use Polirium\Modules\Product\Http\Facades\ProductHelper;
use Polirium\Modules\Product\Http\Model\Product;

if (! function_exists('isset_value')) {
    function isset_value(&$variable)
    {
        if (isset($variable) && $variable) {
            return $variable;
        }

        return null;
    }
}

if (! function_exists('discount_value')) {
    /**
     * @param int|float $value
     * @param float $discount
     * @param string $type (vnd,number) hoặc (%,percent)
     * @return float
     */
    function discount_value(int|float $value, float $discount, ?string $type = 'vnd'): float
    {
        $type = $type ?? 'vnd';
        if (empty($discount) || is_null($discount) || $discount == 0) {
            return $value;
        }

        if (in_array($type, ['%', 'percent'])) {
            $discount = $value * ($discount / 100);
        }

        return $value - $discount;
    }
}

if (! function_exists('code_generate')) {
    /**
     * @param string $key
     * @param int $max
     * @return string
     */
    function code_generate(string $key, ?int $max = 0): string
    {
        return "{$key}/" . ((string)sprintf('%05d', ((int)$max + 1)));
    }
}

if (! function_exists('product_logs')) {
    function product_logs(
        int $product_id,
        int $productable_id,
        Model|string|null $productable_type,
        int $amount = 0,
        int $value_before = 0,
        int $value_after = 0,
        bool $increase = true,
        ?int $branch_id = null
    ): void {
        ProductHelper::productLogs($product_id, $productable_id, $productable_type, $amount, $value_before, $value_after, $increase, $branch_id);
    }
}

if (! function_exists('change_product_amount')) {
    function change_product_amount(int|Product $product, int $amount, bool $increase = true, int $branch_id = null): array
    {
        return ProductHelper::changeProductAmount($product, $amount, $increase, $branch_id);
    }
}

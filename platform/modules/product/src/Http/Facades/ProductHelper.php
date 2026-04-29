<?php

namespace Polirium\Modules\Product\Http\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void productLogs(int $product_id, int $productable_id, $productable_type, int $amount = 0, int $value_before = 0, int $value_after = 0, bool $increase = true)
 * @method static array increaseProductAmount(int|Product $product, int $amount, int $branch_id = null)
 * @method static array decreaseProductAmount(int|Product $product, int $amount, int $branch_id = null)
 *
 * @see \Polirium\Modules\Product\Http\Support\ProductSupport
 */
class ProductHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'polirium:product';
    }
}

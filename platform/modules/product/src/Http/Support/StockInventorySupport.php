<?php

namespace Polirium\Modules\Product\Http\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;
use Polirium\Modules\Product\Http\Model\Stock\Stock;

final class StockInventorySupport
{
    public static function save(Stock $stock, array $lines): void
    {
        DB::transaction(function () use ($stock, $lines) {
            if ($stock->exists) {
                $existing = Stock::lockForUpdate()->findOrFail($stock->id);
                if (in_array($existing->status, ['completed', 'cancelled'], true)) {
                    throw ValidationException::withMessages(['stock' => 'Phiếu đã hoàn thành hoặc đã hủy, không thể áp dụng lại tồn kho.']);
                }
            }
            ksort($lines, SORT_NUMERIC);
            foreach ($lines as $productId => &$line) {
                $product = Product::findOrFail($productId);
                if ($product->type === 'service' || $product->type === 'combo') {
                    throw ValidationException::withMessages(['products' => 'Chỉ kiểm kê hàng hóa quản lý tồn kho, không kiểm kê dịch vụ/combo.']);
                }
                if ((int) $line['actual_stock'] < 0) {
                    throw ValidationException::withMessages(['products' => 'Số kiểm đếm không được âm.']);
                }
                $current = (int) ProductBranch::where('product_id', $productId)
                    ->where('branch_id', $stock->branch_id)->lockForUpdate()->value('qty');
                if ($stock->status === 'completed' && $current !== (int) $line['amount']) {
                    throw ValidationException::withMessages(['products' => "Tồn kho {$product->code} đã thay đổi từ {$line['amount']} thành {$current}. Vui lòng tải lại phiếu và đối chiếu giao dịch phát sinh trước khi hoàn thành."]);
                }
                $line['product_id'] = $productId;
                $line['value'] = $product->cost ?? 0;
                $line['quantity_difference'] = (int) $line['actual_stock'] - (int) $line['amount'];
                $line['value_difference'] = $line['quantity_difference'] * $line['value'];
                unset($line['product']);
            }
            unset($line);
            $stock->amount = array_sum(array_column($lines, 'actual_stock'));
            $stock->value = array_sum(array_column($lines, 'value_difference'));
            $stock->increase_deviation = array_sum(array_map(fn ($line) => max(0, $line['quantity_difference']), $lines));
            $stock->decrease_deviation = array_sum(array_map(fn ($line) => max(0, -$line['quantity_difference']), $lines));
            $stock->deviation = $stock->increase_deviation - $stock->decrease_deviation;
            $stock->save();
            $stock->products()->forceDelete();
            foreach ($lines as $line) {
                $stock->products()->create($line);
                $difference = $line['quantity_difference'];
                if ($stock->status === 'completed' && $difference !== 0) {
                    product_logs($line['product_id'], $stock->id, Stock::class, abs($difference),
                        $line['value'], abs($line['value_difference']), $difference > 0, $stock->branch_id, now());
                }
            }
        });
    }

    public static function cancel(int $id, bool $delete = false): void
    {
        DB::transaction(function () use ($id, $delete) {
            $stock = Stock::with('products')->lockForUpdate()->find($id);
            if (! $stock) return;
            if ($stock->status === 'completed') {
                foreach ($stock->products->sortBy('product_id') as $line) {
                    $laterCount = Stock::where('branch_id', $stock->branch_id)->where('status', 'completed')
                        ->where('id', '>', $stock->id)->whereHas('products', fn ($q) => $q->where('product_id', $line->product_id))->exists();
                    if ($laterCount) {
                        throw ValidationException::withMessages(['stock' => 'Sản phẩm đã có phiếu kiểm kê mới hơn. Không thể hủy mốc kiểm kê cũ.']);
                    }
                    $difference = (int) $line->quantity_difference;
                    if ($difference !== 0) {
                        product_logs($line->product_id, $stock->id, Stock::class, abs($difference),
                            $line->value ?? 0, abs($line->value_difference ?? 0), $difference < 0, $stock->branch_id, now());
                    }
                }
            }
            $stock->update(['status' => 'cancelled']);
            if ($delete) $stock->delete();
        });
    }
}

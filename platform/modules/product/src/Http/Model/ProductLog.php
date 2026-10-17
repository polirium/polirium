<?php

namespace Polirium\Modules\Product\Http\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Refund\Refund as SalesRefund;
use Polirium\Modules\Product\Http\Model\Stock\Stock;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund as PurchaseRefund;
use Polirium\Modules\Vendor\Http\Model\Transfer\Transfer;

class ProductLog extends BaseModel
{
    protected $table = 'product_logs';

    protected $fillable = [
        'uuid',
        'product_id',
        'productable_id',
        'productable_type',
        'amount',
        'value_before',
        'value_after',
        'amount_before',
        'amount_after',
    ];

    public function getTypeNameAttribute()
    {
        if ($this->productable_type === Payment::class) {
            // Tồn tăng trên hóa đơn: hủy đơn, hoặc hoàn kho khi sửa hóa đơn còn hiệu lực
            if (! ($this->direction === 'in' || ($this->direction === null && $this->amount_after > $this->amount_before))) {
                return __('Bán hàng');
            }

            $status = $this->productable?->status;

            return $status === null || in_array($status, ['cancel', 'cancelled', 'failed', 'delivery_failed'], true)
                ? __('Hủy đơn')
                : __('Điều chỉnh hóa đơn');
        }

        return match ($this->productable_type) {
            \Polirium\Modules\Product\Http\Model\Refund\Refund::class => __('Trả hàng'),
            \Polirium\Modules\Accounting\Http\Model\Refund\Refund::class => __('Trả hàng'),
            Purchase::class => trans('modules/product::product.purchase_import'),
            Transfer::class => __('Chuyển hàng'),
            Stock::class => trans('modules/product::product.stock_check'),
            PurchaseRefund::class => trans('modules/product::product.purchase_refund'),
            Product::class => __('Cập nhật tồn từ hàng hóa'),
            'system_fix_negative_stock' => __('Hệ thống hiệu chỉnh tồn âm'),
            default => $this->productable_type,
        };
    }

    public function getPartnerNameAttribute()
    {
        return match ($this->productable_type) {
            Payment::class => $this->productable?->customer?->name,
            SalesRefund::class => $this->productable?->payment?->customer?->name,
            PurchaseRefund::class => $this->productable?->vendor?->name,
            Purchase::class => $this->productable?->vendor?->name,
            Stock::class => $this->productable?->userCreated?->name ?? 'System',
            default => null,
        };
    }

    /**
     * Số lượng có dấu: + cho nhập kho (hoặc hoàn kho), - cho xuất kho.
     */
    public function getSignedAmountAttribute(): int
    {
        return ($this->direction === 'in' || ($this->direction === null && $this->amount_after > $this->amount_before))
            ? abs($this->amount)
            : -abs($this->amount);
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->productable_id) {
            return null;
        }

        return match ($this->productable_type) {
            Payment::class => route('accountings.payment.show', $this->productable_id),
            Purchase::class => route('vendors.purchases.show', $this->productable_id),
            Transfer::class => route('vendors.transfers.transfer', $this->productable_id),
            Stock::class => route('products.stock.stock', $this->productable_id),
            PurchaseRefund::class => route('vendors.purchases.refund', $this->productable_id),
            default => null,
        };
    }

    public function productable()
    {
        return $this->morphTo();
    }

    /**
     * Latest stock card lines of a product (newest first) with their document
     * and partner loaded in a few queries instead of several per line.
     */
    public static function forStockCard(int $productId, int $limit = 100): Collection
    {
        $logs = static::query()
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        // Legacy lines may carry a marker instead of a class name: never
        // eager load those (see getProductableAttribute()).
        $documents = $logs->filter(fn (self $log) => $log->productable_type && class_exists($log->productable_type));

        if ($documents->isNotEmpty()) {
            (new Collection($documents->all()))->load(['productable' => fn (MorphTo $morph) => $morph->morphWith([
                Payment::class => ['customer:id,name'],
                SalesRefund::class => ['payment.customer:id,name'],
                Purchase::class => ['vendor:id,name'],
                PurchaseRefund::class => ['vendor:id,name'],
                Stock::class => ['userCreated'],
            ])]);
        }

        return $logs;
    }

    /**
     * Legacy rows use a plain marker (e.g. system_fix_negative_stock) instead of a
     * model class; resolving them must not break the stock card.
     */
    public function getProductableAttribute()
    {
        if (! $this->productable_type || ! class_exists($this->productable_type)) {
            return null;
        }

        return $this->getRelationValue('productable');
    }

    /**
     * Mã hiển thị của chứng từ.
     * Tự động thêm tiền tố TR. cho các phiếu trả hàng.
     */
    public function getDisplayCodeAttribute(): string
    {
        $code = $this->productable?->code ?? '-';

        return match ($this->productable_type) {
            \Polirium\Modules\Product\Http\Model\Refund\Refund::class,
            PurchaseRefund::class => 'TR.' . $code,
            default => $code,
        };
    }

    /**
     * Trạng thái đơn hàng liên quan.
     *
     * @return array{label: string, class: string}|null
     */
    public function getOrderStatusAttribute(): ?array
    {
        if (! $this->productable) {
            return null;
        }

        $status = $this->productable->status ?? null;
        if (! $status) {
            return null;
        }

        return match ($status) {
            'success', 'completed' => ['label' => 'Thành công', 'class' => 'bg-success-lt'],
            'cancel', 'cancelled' => ['label' => 'Hủy', 'class' => 'bg-danger-lt text-danger'],
            'failed', 'delivery_failed' => ['label' => 'Thất bại', 'class' => 'bg-danger-lt text-danger'],
            'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-warning-lt'],
            'processing' => ['label' => 'Đang xử lý', 'class' => 'bg-info-lt'],
            'shipping', 'delivering' => ['label' => 'Đang giao', 'class' => 'bg-cyan-lt'],
            'delivered' => ['label' => 'Đã giao', 'class' => 'bg-success-lt'],
            default => ['label' => $status, 'class' => 'bg-muted-lt'],
        };
    }
}

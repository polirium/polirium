<?php

namespace Polirium\Modules\Product\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
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
            // Nếu tồn kho tăng lên (amount_after > amount_before), đây là log Hủy đơn hoàn kho
            return $this->amount_after > $this->amount_before
                ? __('Hủy đơn')
                : __('Bán hàng');
        }

        return match ($this->productable_type) {
            \Polirium\Modules\Product\Http\Model\Refund\Refund::class => __('Trả hàng'),
            Purchase::class => trans('modules/product::product.purchase_import'),
            Transfer::class => __('Chuyển hàng'),
            Stock::class => trans('modules/product::product.stock_check'),
            PurchaseRefund::class => trans('modules/product::product.purchase_refund'),
            default => $this->productable_type,
        };
    }

    public function getPartnerNameAttribute()
    {
        return match ($this->productable_type) {
            Payment::class => $this->productable?->customer?->name,
            PurchaseRefund::class => $this->productable?->payment?->customer?->name,
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
        return ($this->amount_after >= $this->amount_before)
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

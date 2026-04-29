<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment\Refund\Detail;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Refund\Refund;

class PaymentRefundDetail extends Component
{
    public int $refund_id;

    public function mount($refund_id): void
    {
        $this->refund_id = $refund_id;
    }

    #[Computed]
    public function refund(): ?Refund
    {
        return Refund::with(['payment.customer', 'products.product', 'creator', 'user'])
            ->find($this->refund_id);
    }

    public function render()
    {
        if (! $this->refund) {
            abort(404, 'Không tìm thấy thông tin trả hàng');
        }

        return view('modules/accounting::payment.refund.detail.view');
    }
}

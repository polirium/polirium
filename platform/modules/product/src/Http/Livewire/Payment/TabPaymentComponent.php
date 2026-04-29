<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;
use Str;

class TabPaymentComponent extends Component
{
    public $tab = []; // [ ['id' => '...', 'type' => 'invoice'|'refund', 'label' => '...', 'data' => []] ]

    public $tab_selected;

    public $search = '';

    public function mount()
    {
        $refundId = request()->query('refund_id');

        if ($refundId) {
            $this->addRefundTab($refundId);
        } else {
            $this->addNewPayment();
        }
    }

    #[Computed]
    public function products()
    {
        if (! empty($this->search)) {
            // 'amount' is an accessor, not a column, so we remove it from select
            return Product::select(['id', 'name', 'code', 'unit', 'price', 'type'])
            ->with('branches:id')
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ;
            })
            ->limit(50)
            ->get();
        }

        return null;
    }

    public function render()
    {
        return view('modules/product::payment.tab');
    }

    public function addNewPayment()
    {
        $id = Str::random(10);
        $this->tab[] = [
            'id' => $id,
            'type' => 'invoice',
            'label' => 'Hóa đơn', // Label will be handled in view index
            'data' => [],
        ];
        $this->tab_selected = $id;
    }

    #[\Livewire\Attributes\On('add-refund-tab')]
    public function addRefundTab($invoiceId)
    {
        $paymentId = $invoiceId;
        $payment = Payment::find($paymentId);
        if (! $payment) {
            $this->dispatch('error', 'Hóa đơn không tồn tại');

            return;
        }

        // Check if already open
        foreach ($this->tab as $t) {
            if ($t['type'] === 'refund' && ($t['data']['payment_id'] ?? 0) == $paymentId) {
                $this->tab_selected = $t['id'];

                return;
            }
        }

        $id = Str::random(10);
        $this->tab[] = [
            'id' => $id,
            'type' => 'refund',
            'label' => 'Trả hàng ' . $payment->code,
            'data' => ['payment_id' => $paymentId],
        ];
        $this->tab_selected = $id;
    }

    #[\Livewire\Attributes\On('remove-payment-tab')]
    public function removePayment($tabId)
    {
        // Không cho xóa nếu chỉ còn 1 tab
        if (count($this->tab) <= 1) {
            return;
        }

        // Clean up session for this tab
        $sessionKey = 'payment_v2_tab_' . $tabId;
        session()->forget($sessionKey);

        // Tìm key theo id
        $key = -1;
        foreach ($this->tab as $k => $t) {
            if ($t['id'] === $tabId) {
                $key = $k;

                break;
            }
        }

        if ($key !== -1) {
            unset($this->tab[$key]);
            $this->tab = array_values($this->tab); // Re-index array
        }

        // Chọn tab đầu tiên nếu tab đang chọn bị xóa
        if ($this->tab_selected === $tabId) {
            $this->tab_selected = $this->tab[0]['id'] ?? null;
        }
    }

    public function selectProduct($id)
    {
        // Check current tab type
        $currentTabParams = collect($this->tab)->firstWhere('id', $this->tab_selected);

        if (($currentTabParams['type'] ?? 'invoice') !== 'invoice') {
            $this->js("alert('Vui lòng chọn tab Hóa đơn để thêm sản phẩm!')");

            return;
        }

        // 'amount' is an accessor, not a column, so we remove it from select
        $product = Product::select(['id', 'name', 'type'])
        ->with('branches:id')
        ->find($id);

        // Nếu là dịch vụ thì không cần kiểm tra tồn kho
        // $product->amount is an accessor, so it will be calculated automatically when accessed
        if ($product->type !== 'service' && $product->amount <= 0) {
            $this->js("alert('Sản phẩm \\'{$product->name}\\' đã hết hàng trong kho!')");

            return;
        }

        $this->dispatch('payment-product-selected', product_id: $id, tab_id: $this->tab_selected);
    }

    public function tabSelected($key)
    {
        \Log::info('TabPaymentComponent::tabSelected', ['id' => $key]);
        $this->tab_selected = $key;
    }
}

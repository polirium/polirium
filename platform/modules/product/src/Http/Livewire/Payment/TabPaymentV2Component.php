<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Livewire\Attributes\On;
use Livewire\Component; // Add this
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Str;

class TabPaymentV2Component extends Component
{
    public $tab = []; // [ ['id' => '...', 'type' => 'invoice'|'refund', 'label' => '...', 'data' => []] ]

    public $tab_selected;

    public function mount()
    {
        $refundId = request()->query('refund_id');

        if ($refundId) {
            $this->addRefundTab($refundId);
        } else {
            $this->addNewPayment();
        }
    }

    public function render()
    {
        return view('modules/product::payment.tab-v2');
    }

    public function addNewPayment()
    {
        $id = Str::random(10);
        $this->tab[] = [
            'id' => $id,
            'type' => 'invoice',
            'label' => 'Hóa đơn',
            'data' => [],
        ];
        $this->tab_selected = $id;
    }

    #[On('add-refund-tab')]
    public function addRefundTab($invoiceId)
    {
        // Parameter name change to match event if dispatched as named param,
        // but 'add-refund-tab' dispatch usually uses 'invoiceId'.
        // Renaming local var to match meaning or using named arguments.
        // Let's support both positional and named if Livewire does that, but simple arg is safe.
        $paymentId = $invoiceId;

        $payment = Payment::find($paymentId);
        if (! $payment) {
            $this->dispatch('error', 'Hóa đơn không tồn tại'); // Assuming this event exists or frontend handles it

            return;
        }

        // Check if already open
        foreach ($this->tab as $t) {
            if (($t['type'] ?? 'invoice') === 'refund' && ($t['data']['payment_id'] ?? 0) == $paymentId) {
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

    #[On('remove-payment-tab')]
    public function removePayment($tabId)
    {
        // Не cho xóa nếu chỉ còn 1 tab (Logic cũ: count($this->tab) <= 1)
        // Nhưng nếu người dùng mở Refund tab và đóng Invoice tab?
        // Có lẽ nên luôn giữ ít nhất 1 tab bất kỳ.
        if (count($this->tab) <= 1) {
            return;
        }

        // Clean up session for this tab - keeping V2 logic
        // Use generic key or specific if V2 components use specific keys
        // V2 components probably use $tabId in their session keys.
        // Assuming V2 logic was `payment_v2_tab_` + ID.

        // (Original code didn't implement session cleanup in V2 component shown,
        // but V1 did. I'll add it if standard).

        // Find key
        $key = -1;
        foreach ($this->tab as $k => $t) {
            if ($t['id'] === $tabId) {
                $key = $k;

                break;
            }
        }

        if ($key !== -1) {
            unset($this->tab[$key]);
            $this->tab = array_values($this->tab);
        }

        if ($this->tab_selected === $tabId) {
            $this->tab_selected = $this->tab[0]['id'] ?? null;
        }
    }

    public function tabSelected($key)
    {
        \Log::info('TabPaymentV2Component::tabSelected', ['id' => $key]);
        $this->tab_selected = $key;
    }
}

<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PosCart;
use Polirium\Modules\Product\Http\Model\Product;

/**
 * Point of sale shell: product search and the open invoice/return tabs.
 *
 * Tabs are rows in product_pos_carts, so reopening the page brings back
 * every cart the cashier left open in this branch.
 */
class TabPaymentComponent extends Component
{
    public $tab = []; // [ ['id' => '...', 'type' => 'invoice'|'refund', 'label' => '...', 'data' => []] ]

    public $tab_selected;

    public $search = '';

    public function mount()
    {
        // Carts untouched for a week are abandoned.
        PosCart::ownedBy()->where('updated_at', '<', now()->subDays(7))->delete();

        foreach (PosCart::ownedBy()->where('branch_id', user_branch())->orderBy('position')->orderBy('id')->get() as $cart) {
            $this->tab[] = [
                'id' => $cart->tab_key,
                'type' => $cart->type,
                'label' => $cart->type === 'refund' ? ($cart->state['label'] ?? 'Trả hàng') : 'Hóa đơn',
                'data' => $cart->type === 'refund' ? ['payment_id' => (int) ($cart->state['payment_id'] ?? 0)] : [],
            ];
        }

        $refundId = request()->query('refund_id');

        if ($refundId) {
            $this->addRefundTab($refundId);
        } elseif ($this->tab === []) {
            $this->addNewPayment();
        } else {
            $this->tab_selected = $this->tab[0]['id'];
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
                $term = '%' . addcslashes(trim($this->search), '%_\\') . '%';
                $q->where('name', 'like', $term)
                ->orWhere('code', 'like', $term)
                ;
            })
            // Same order as PaymentComponent::addProduct(), so Enter adds the first row shown.
            ->orderByRaw('code = ? desc', [trim($this->search)])
            ->orderBy('name')
            ->orderBy('id')
            ->limit(20)
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

        $this->storeTab($id, 'invoice');
    }

    #[On('add-refund-tab')]
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
        $label = 'Trả hàng ' . $payment->code;
        $this->tab[] = [
            'id' => $id,
            'type' => 'refund',
            'label' => $label,
            'data' => ['payment_id' => $paymentId],
        ];
        $this->tab_selected = $id;

        $this->storeTab($id, 'refund', ['payment_id' => (int) $paymentId, 'label' => $label]);
    }

    #[On('remove-payment-tab')]
    public function removePayment($tabId)
    {
        // Không cho xóa nếu chỉ còn 1 tab
        if (count($this->tab) <= 1) {
            return;
        }

        PosCart::ownedBy()->where('tab_key', $tabId)->delete();

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

    /**
     * The search box only adds to invoice tabs.
     */
    public function wrongTab(): void
    {
        $this->dispatch('error', 'Vui lòng chọn tab Hóa đơn để thêm sản phẩm!');
    }

    public function tabSelected($key)
    {
        $this->tab_selected = $key;
    }

    private function storeTab(string $tabKey, string $type, ?array $state = null): void
    {
        PosCart::create([
            'user_id' => (int) auth()->id(),
            'branch_id' => user_branch(),
            'tab_key' => $tabKey,
            'type' => $type,
            'position' => (int) PosCart::ownedBy()->max('position') + 1,
            'state' => $state,
        ]);
    }
}

<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Livewire\Component;
use Livewire\WithPagination;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class DraftPaymentListComponent extends Component
{
    use WithPagination;

    public function getListeners(): array
    {
        return [
            'refresh-draft-list' => '$refresh',
            'show-draft-list' => 'showModal',
        ];
    }

    public bool $showModal = false;
    public ?string $tab_id = null;

    public function showModal(?string $tab_id = null): void
    {
        $this->tab_id = $tab_id;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * Lấy danh sách hóa đơn tạm
     * Hiển thị tất cả drafts để user có thể chọn và chuyển branch khi cần
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDraftsProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Payment::query()
            ->with(['customer', 'products', 'user', 'saleChannel', 'branch', 'author'])
            ->where('status', 'draft')
            ->latest()
            ->paginate(10, ['*'], 'draftPage');
    }

    /**
     * Mở lại hóa đơn tạm để tiếp tục chỉnh sửa
     */
    public function openDraft(int $paymentId): void
    {
        $payment = Payment::find($paymentId);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn tạm.');

            return;
        }

        if ($payment->status !== 'draft') {
            $this->dispatch('error', 'Hóa đơn này không phải là hóa đơn tạm.');

            return;
        }

        // Dispatch event để PaymentComponent load lại dữ liệu
        $this->dispatch('open-draft-payment', paymentId: $paymentId, tabId: $this->tab_id);

        // Đóng modal sau khi mở
        $this->closeModal();

        $this->dispatch('success', __('modules/product::payment.draft_opened'));
    }

    /**
     * Xóa hóa đơn tạm
     */
    public function deleteDraft(int $paymentId): void
    {
        $payment = Payment::find($paymentId);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn tạm.');

            return;
        }

        if ($payment->status !== 'draft') {
            $this->dispatch('error', 'Hóa đơn này không phải là hóa đơn tạm.');

            return;
        }

        // Xóa các sản phẩm liên quan
        $payment->products()->delete();

        // Xóa payment delivery nếu có
        $payment->delivery?->delete();

        // Xóa payment
        $payment->delete();

        $this->dispatch('success', 'Đã xóa hóa đơn tạm.');
        $this->dispatch('refresh-draft-list');
        $this->dispatch('refresh-datatable-product-payments');
    }

    public function render(): \Illuminate\View\View
    {
        return view('modules/product::payment.draft-list', [
            'drafts' => $this->drafts,
        ]);
    }
}

<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment;

use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class PaymentNote extends Component
{
    public int $paymentId;
    public string $noteContent = '';
    public bool $isEditing = false;
    public string $currentNote = '';

    public function mount($payment)
    {
        // Handle both Model and stdClass (PowerGrid often returns stdClass)
        $this->paymentId = is_object($payment) ? $payment->id : $payment['id'];
        $this->currentNote = is_object($payment) ? ($payment->note ?? '') : ($payment['note'] ?? '');
        $this->noteContent = $this->currentNote;
    }

    public function edit()
    {
        $this->isEditing = true;
        // Re-fetch fresh note in case it changed elsewhere, or use current
        $this->noteContent = $this->currentNote;
    }

    public function save()
    {
        $this->authorize('accountings.edit');

        $payment = Payment::find($this->paymentId);

        if ($payment) {
            $payment->note = $this->noteContent;
            $payment->save();
            $this->currentNote = $this->noteContent;
            $this->dispatch('success', 'Đã cập nhật ghi chú.');
        } else {
            $this->dispatch('error', 'Không tìm thấy hóa đơn.');
        }

        $this->isEditing = false;
    }

    public function cancel()
    {
        $this->isEditing = false;
        $this->noteContent = $this->currentNote;
    }

    public function render()
    {
        return view('modules/accounting::payment.note');
    }
}

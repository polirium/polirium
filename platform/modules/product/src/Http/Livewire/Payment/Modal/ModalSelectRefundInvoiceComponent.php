<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class ModalSelectRefundInvoiceComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $date_start;
    public $date_end;

    // Pagination theme
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->date_start = now()->subDays(30)->format('Y-m-d'); // Default last 30 days
        $this->date_end = now()->format('Y-m-d');
    }

    #[On('show-modal-select-refund-invoice')]
    public function show()
    {
        $this->dispatch('open-modal', name: 'modal-select-refund-invoice');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectInvoice($id)
    {
        $this->dispatch('close-modal', name: 'modal-select-refund-invoice');

        // Dispatch "add-refund-tab" so TabPaymentComponent (V1 or V2) catches it
        $this->dispatch('add-refund-tab', invoiceId: $id);
    }

    public function render()
    {
        $query = Payment::query()
            ->with(['customer', 'user'])
            ->where('status', 'success');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($sq) {
                      $sq->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('phone', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->date_start) {
            $query->whereDate('created_at', '>=', $this->date_start);
        }

        if ($this->date_end) {
            $query->whereDate('created_at', '<=', $this->date_end);
        }

        $invoices = $query->orderByDesc('id')->paginate(10);

        return view('modules/product::payment.modal.select-refund-invoice', [
            'invoices' => $invoices,
        ]);
    }
}

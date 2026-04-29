<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment\Refund;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'code' => '',
        'status' => '',
    ];

    public $statuses = [];

    public function mount()
    {
        $this->statuses = [
            'pending' => __('modules/accounting::accounting.pending'),
            'completed' => __('modules/accounting::accounting.completed'),
            'cancelled' => __('modules/accounting::accounting.cancelled'),
        ];
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-payment-refund-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['code' => '', 'status' => ''];
        $this->dispatch('datatable-payment-refund-filter-clear');
    }

    public function render()
    {
        return view('modules/accounting::payment.refund.filter-sidebar');
    }
}

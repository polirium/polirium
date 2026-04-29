<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Purchase\Refund;

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
            'pending' => trans('pending'),
            'approved' => trans('approved'),
            'completed' => trans('completed'),
            'cancelled' => trans('cancelled'),
        ];
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch("datatable-purchase-refund-filter", $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['code' => '', 'status' => ''];
        $this->dispatch("datatable-purchase-refund-filter-clear");
    }

    public function render()
    {
        return view('modules/vendor::purchase.refund.filter-sidebar');
    }
}

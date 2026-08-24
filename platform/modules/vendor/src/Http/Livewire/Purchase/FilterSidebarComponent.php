<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Purchase;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'code' => '',
        'status' => '',
        'date' => '',
    ];

    public $statuses = [];

    public function mount()
    {
        $this->statuses = [
            'temp' => trans('pending'),
            'success' => trans('completed'),
            'cancel' => trans('cancelled'),
        ];
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-purchase-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['code' => '', 'status' => '', 'date' => ''];
        $this->dispatch('datatable-purchase-filter-clear');
    }

    public function render()
    {
        return view('modules/vendor::purchase.filter-sidebar');
    }
}

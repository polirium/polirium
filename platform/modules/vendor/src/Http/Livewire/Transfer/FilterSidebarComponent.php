<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Transfer;

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
            'completed' => trans('completed'),
            'cancelled' => trans('cancelled'),
        ];
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch("datatable-transfer-filter", $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['code' => '', 'status' => ''];
        $this->dispatch("datatable-transfer-filter-clear");
    }

    public function render()
    {
        return view('modules/vendor::transfer.filter-sidebar');
    }
}

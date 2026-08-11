<?php

namespace Polirium\Modules\Product\Http\Livewire\Stock;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
        'product_code' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-stock-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = [
            'name' => '',
            'product_code' => '',
        ];
        $this->dispatch('datatable-stock-filter-clear');
    }

    public function render()
    {
        return view('modules/product::stock.filter-sidebar');
    }
}

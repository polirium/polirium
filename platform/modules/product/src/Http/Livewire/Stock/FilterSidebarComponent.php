<?php

namespace Polirium\Modules\Product\Http\Livewire\Stock;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-stock-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['name' => ''];
        $this->dispatch('datatable-stock-filter', '', 'name');
    }

    public function render()
    {
        return view('modules/product::stock.filter-sidebar');
    }
}

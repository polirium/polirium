<?php

namespace Polirium\Modules\Product\Http\Livewire\PriceSetting;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-price-setting-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['name' => ''];
        $this->dispatch('datatable-price-setting-filter', '', 'name');
    }

    public function render()
    {
        return view('modules/product::price-setting.filter-sidebar');
    }
}

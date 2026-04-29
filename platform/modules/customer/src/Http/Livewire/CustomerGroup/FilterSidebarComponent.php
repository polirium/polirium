<?php

namespace Polirium\Modules\Customer\Http\Livewire\CustomerGroup;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-customer-group-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['name' => ''];
        $this->dispatch('datatable-customer-group-filter', '', 'name');
    }

    public function render()
    {
        return view('modules/customer::customer-group.filter-sidebar');
    }
}

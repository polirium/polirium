<?php

namespace Polirium\Modules\Vendor\Http\Livewire\VendorGroup;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch("datatable-vendor-group-filter", $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['name' => ''];
        $this->dispatch("datatable-vendor-group-filter", '', 'name');
    }

    public function render()
    {
        return view('modules/vendor::vendor-group.filter-sidebar');
    }
}

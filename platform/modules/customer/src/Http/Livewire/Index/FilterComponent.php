<?php

namespace Polirium\Modules\Customer\Http\Livewire\Index;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Customer\Http\Model\CustomerGroup;

class FilterComponent extends Component
{
    protected $listeners = [
        'refresh-customer-filter' => '$refresh',
    ];

    public $list = [];

    public $search = [];

    public function mount()
    {
        $this->loadList();
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-customer-filter', $value, $key);
    }

    public function render()
    {
        return view('modules/customer::index.filter');
    }

    #[On('customer-filter-load-list')]
    public function loadList()
    {
        $this->list['customer-groups'] = CustomerGroup::select(['id', 'name'])->pluck('name', 'id')->all();
    }
}

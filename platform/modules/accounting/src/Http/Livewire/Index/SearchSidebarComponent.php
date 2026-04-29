<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Index;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Accounting\Http\Model\AccountingType;
use Polirium\Modules\Accounting\Http\Model\PayPerson;

class SearchSidebarComponent extends Component
{
    public array $search = [
        'code' => '',
        'type_id' => 0,
        'pay_person_id' => 0,
        'date_from' => '',
        'date_to' => '',
        'value_min' => '',
        'value_max' => '',
    ];

    public array $lists = [
        'types' => [],
        'pay_persons' => [],
    ];

    public function mount()
    {
        $this->refreshLists();
    }

    public function updatedSearch(mixed $value, string $key)
    {
        $this->dispatch('accounting-search-sidebar', value: $value, key: $key);
    }

    public function clearFilters()
    {
        $this->search = [
            'code' => '',
            'type_id' => 0,
            'pay_person_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'value_min' => '',
            'value_max' => '',
        ];

        foreach ($this->search as $key => $value) {
            $this->dispatch('accounting-search-sidebar', value: $value, key: $key);
        }
    }

    public function render()
    {
        return view('modules/accounting::index.search-sidebar');
    }

    #[On('accounting-search-sidebar-refresh-lists')]
    public function refreshLists()
    {
        $this->lists['types'] = AccountingType::select('name', 'id')->pluck('name', 'id')->all();
        $this->lists['pay_persons'] = PayPerson::select('name', 'id')->pluck('name', 'id')->all();
    }
}

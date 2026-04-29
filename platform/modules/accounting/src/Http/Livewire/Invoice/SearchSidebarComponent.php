<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Invoice;

use Livewire\Component;

class SearchSidebarComponent extends Component
{
    public array $search = [
        'code' => '',
        'customer_name' => '',
        'date_from' => '',
        'date_to' => '',
        'status' => '',
        'value_min' => '',
        'value_max' => '',
    ];

    public array $lists = [
        'statuses' => [],
    ];

    public function mount()
    {
        $this->lists['statuses'] = [
            'pending' => 'Chờ xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];
    }

    public function updatedSearch(mixed $value, string $key)
    {
        $this->dispatch('invoice-search-sidebar', value: $value, key: $key);
    }

    public function clearFilters()
    {
        $this->search = [
            'code' => '',
            'customer_name' => '',
            'date_from' => '',
            'date_to' => '',
            'status' => '',
            'value_min' => '',
            'value_max' => '',
        ];

        foreach ($this->search as $key => $value) {
            $this->dispatch('invoice-search-sidebar', value: $value, key: $key);
        }
    }

    public function render()
    {
        return view('modules/accounting::invoice.search-sidebar');
    }
}

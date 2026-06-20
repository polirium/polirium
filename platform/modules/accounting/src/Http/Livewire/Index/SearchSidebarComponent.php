<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Index;

use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Accounting\Http\Model\AccountingType;
use Polirium\Modules\Accounting\Http\Model\PayPerson;

class SearchSidebarComponent extends Component
{
    public array $search = [
        'period' => 'today',
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
        if ($key === 'period') {
            $this->applyPeriod((string) $value);

            return;
        }

        if (in_array($key, ['date_from', 'date_to'], true)) {
            $this->search['period'] = 'custom';
            $this->syncDateFilters();

            return;
        }

        $this->dispatch('accounting-search-sidebar', value: $value, key: $key);
    }

    public function clearFilters()
    {
        $this->search = [
            'period' => 'today',
            'code' => '',
            'type_id' => 0,
            'pay_person_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'value_min' => '',
            'value_max' => '',
        ];

        foreach ($this->search as $key => $value) {
            if (in_array($key, ['period', 'date_from', 'date_to'], true)) {
                continue;
            }

            $this->dispatch('accounting-search-sidebar', value: $value, key: $key);
        }

        $this->applyPeriod('today');
    }

    public function render()
    {
        return view('modules/accounting::index.search-sidebar');
    }

    private function applyPeriod(string $period): void
    {
        $today = now();

        [$from, $to] = match ($period) {
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            'last_7_days' => [$today->copy()->subDays(6), $today],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [
                $this->search['date_from'] ? Carbon::parse($this->search['date_from']) : $today,
                $this->search['date_to'] ? Carbon::parse($this->search['date_to']) : $today,
            ],
            default => [$today, $today],
        };

        $this->search['date_from'] = $from->toDateString();
        $this->search['date_to'] = $to->toDateString();

        $this->syncDateFilters();
    }

    private function syncDateFilters(): void
    {
        $this->dispatch('accounting-search-sidebar', value: $this->search['date_from'], key: 'date_from');
        $this->dispatch('accounting-search-sidebar', value: $this->search['date_to'], key: 'date_to');
        $this->dispatch(
            'accounting-dashboard-date-range',
            dateFrom: $this->search['date_from'],
            dateTo: $this->search['date_to'],
        );
    }

    #[On('accounting-search-sidebar-refresh-lists')]
    public function refreshLists()
    {
        $this->lists['types'] = AccountingType::select('name', 'id')->pluck('name', 'id')->all();
        $this->lists['pay_persons'] = PayPerson::select('name', 'id')->pluck('name', 'id')->all();
    }
}

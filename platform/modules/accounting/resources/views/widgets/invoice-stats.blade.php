@php
    $netBalance = $receipts - $expenses;
    $total = $receipts + $expenses;
    $receiptPercent = $total > 0 ? (int) round($receipts / $total * 100) : 0;
    $expensePercent = $total > 0 ? 100 - $receiptPercent : 0;
    $balanceClass = $netBalance >= 0 ? 'text-success' : 'text-danger';
    $periodLabels = [
        'today' => trans('modules/product::product.today'),
        'week' => trans('modules/product::product.last_7_days'),
        'month' => trans('modules/product::product.last_30_days'),
    ];
@endphp

<div class="card dashboard-stat-card">
    <div class="card-header">
        <h3 class="card-title">{{ trans('core/base::general.cash_flow_title') }}</h3>
        <div class="card-actions">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">{{ $periodLabels[$period] }}</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach ($periodLabels as $key => $label)
                        <li><a class="dropdown-item {{ $period === $key ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('{{ $key }}')">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6">
                <div class="subheader text-success">{{ trans('core/base::general.cash_in') }}</div>
                <div class="d-flex align-items-baseline flex-wrap gap-2 mt-1">
                    <div class="h2 mb-0 text-success text-truncate">{{ core_number_format($receipts) }} đ</div>
                    <x-ui::trend :value="$receiptChange" />
                </div>
                <div class="text-secondary small mt-1">{{ trans('core/base::general.receipts_count', ['count' => $receiptCount]) }}</div>
            </div>
            <div class="col-6">
                <div class="subheader text-danger">{{ trans('core/base::general.cash_out') }}</div>
                <div class="d-flex align-items-baseline flex-wrap gap-2 mt-1">
                    <div class="h2 mb-0 text-danger text-truncate">{{ core_number_format($expenses) }} đ</div>
                    <x-ui::trend :value="$expenseChange" inverse />
                </div>
                <div class="text-secondary small mt-1">{{ trans('core/base::general.payments_count', ['count' => $expenseCount]) }}</div>
            </div>
        </div>

        {{-- Share of money in vs out, then the balance. --}}
        <div class="progress progress-separated mt-3" style="height: 0.5rem">
            @if ($total > 0)
                <div class="progress-bar bg-success" style="width: {{ $receiptPercent }}%"></div>
                <div class="progress-bar bg-danger" style="width: {{ $expensePercent }}%"></div>
            @endif
        </div>

        <div class="d-flex justify-content-between align-items-baseline mt-3">
            <span class="subheader mb-0">{{ trans('core/base::general.cash_balance') }}</span>
            <span class="h3 mb-0 {{ $balanceClass }}">{{ $netBalance >= 0 ? '+' : '' }}{{ core_number_format($netBalance) }} đ</span>
        </div>
    </div>
</div>

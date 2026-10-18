@php
    $periodLabels = [
        'today' => trans('modules/product::product.today'),
        'week' => trans('modules/product::product.last_7_days'),
        'month' => trans('modules/product::product.last_30_days'),
    ];
    $kpis = [
        ['label' => trans('modules/product::product.orders_uppercase'), 'value' => number_format($totalOrders, 0, ',', '.'), 'change' => $changes['orders'], 'series' => $series['orders'], 'type' => 'line', 'color' => 'primary'],
        ['label' => trans('modules/product::product.products_sold'), 'value' => core_number_format($totalItems), 'change' => $changes['items'], 'series' => $series['items'], 'type' => 'bar', 'color' => 'info'],
        ['label' => trans('modules/product::product.revenue_uppercase'), 'value' => core_number_format($totalRevenue) . ' đ', 'change' => $changes['revenue'], 'series' => $series['revenue'], 'type' => 'area', 'color' => 'success'],
    ];
@endphp

<div class="card dashboard-stat-card">
    <div class="card-header">
        <h3 class="card-title">{{ trans('modules/product::product.sales_summary') }}</h3>
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
        <div class="stat-grid">
            @foreach ($kpis as $kpi)
                <div>
                    <div class="stat-tile">
                        <div class="subheader">{{ $kpi['label'] }}</div>
                        <div class="d-flex align-items-baseline flex-wrap gap-2 mt-1">
                            <div class="h2 mb-0 text-truncate">{{ $kpi['value'] }}</div>
                            <x-ui::trend :value="$kpi['change']" />
                        </div>
                        <x-ui::sparkline class="mt-2" :values="$kpi['series']" :type="$kpi['type']" :color="$kpi['color']" height="28" />
                    </div>
                </div>
            @endforeach

            <div>
                <div class="stat-tile">
                    <div class="subheader">{{ trans('modules/product::product.avg_per_order') }}</div>
                    <div class="d-flex align-items-baseline flex-wrap gap-2 mt-1">
                        <div class="h2 mb-0 text-truncate">{{ core_number_format($avgOrderValue) }} đ</div>
                        <x-ui::trend :value="$changes['avg']" />
                    </div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>{{ trans('core/base::general.orders_with_customer', ['count' => $customers]) }}</span>
                            <span>{{ $withCustomer }}%</span>
                        </div>
                        <div class="progress progress-xs">
                            <div class="progress-bar bg-primary" style="width: {{ $withCustomer }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($topProducts->count() > 0)
            <div class="hr-text mt-4 mb-3">{{ trans('modules/product::product.top_selling_products') }}</div>
            <div class="stat-grid stat-grid-wide">
                @foreach ($topProducts as $index => $item)
                    <div>
                        <div class="top-product">
                            <span class="top-product-rank">{{ $index + 1 }}</span>
                            <div class="min-w-0 flex-fill">
                                <div class="fw-medium text-truncate" title="{{ $item->product?->name }}">{{ $item->product?->name ?? 'N/A' }}</div>
                                <div class="text-secondary small text-truncate">{{ $item->product?->code }} · {{ core_number_format($item->total_value) }} đ</div>
                            </div>
                            <span class="badge bg-primary-lt flex-shrink-0">× {{ core_number_format($item->total_sold) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

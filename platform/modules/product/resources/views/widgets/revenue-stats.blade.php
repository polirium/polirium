@php
    $periodLabels = [
        'today' => trans('modules/product::product.today'),
        'week' => trans('modules/product::product.last_7_days'),
        'month' => trans('modules/product::product.last_30_days'),
    ];
@endphp

<div class="card dashboard-stat-card">
    <div class="card-header">
        <h3 class="card-title">{{ trans('modules/product::product.revenue_statistics') }}</h3>
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
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <div class="subheader">{{ trans('modules/product::product.total_revenue') }}</div>
                <div class="d-flex align-items-baseline flex-wrap gap-2">
                    <div class="h1 mb-0">{{ core_number_format($totalRevenue) }} đ</div>
                    <x-ui::trend :value="$change" />
                </div>
                <div class="text-secondary small mt-1">
                    {{ $totalOrders }} {{ trans('modules/product::product.orders_count') }} · {{ trans('core/base::general.trend_hint') }}
                </div>
            </div>
            <div class="col-12 col-md-7">
                <x-ui::sparkline :values="$series" type="area" color="primary" height="64" />
            </div>
        </div>

        {{-- Revenue by payment method: one stacked bar, then the figures. --}}
        @if ($totalRevenue > 0)
            <div class="progress progress-separated mt-3" style="height: 0.5rem">
                @foreach ($parts as $part)
                    @if ($part['value'] > 0)
                        <div class="progress-bar bg-{{ $part['color'] }}" style="width: {{ round($part['value'] / $totalRevenue * 100, 2) }}%"
                             title="{{ $part['label'] }}: {{ core_number_format($part['value']) }} đ"></div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <div class="card-footer">
        <div class="stat-grid stat-grid-compact">
            @foreach ($parts as $part)
                <div>
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="avatar avatar-sm bg-{{ $part['color'] }}-lt flex-shrink-0">
                            {!! tabler_icon($part['icon'], ['class' => 'icon text-' . $part['color']]) !!}
                        </span>
                        <div class="min-w-0">
                            <div class="text-secondary small text-truncate">{{ $part['label'] }}</div>
                            <div class="fw-medium text-truncate">{{ core_number_format($part['value']) }} đ</div>
                            <div class="text-secondary small">{{ $totalRevenue > 0 ? round($part['value'] / $totalRevenue * 100) : 0 }}%</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

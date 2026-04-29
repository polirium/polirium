@php
    // Calculate percentages
    $cashValue = $stats[0]['value'] ?? 0;
    $bankValue = $stats[1]['value'] ?? 0;
    $cardValue = $stats[2]['value'] ?? 0;
    $cashPercent = $totalRevenue > 0 ? round($cashValue / $totalRevenue * 100) : 0;
    $bankPercent = $totalRevenue > 0 ? round($bankValue / $totalRevenue * 100) : 0;
    $cardPercent = $totalRevenue > 0 ? round($cardValue / $totalRevenue * 100) : 0;
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ trans('modules/product::product.revenue_statistics') }}</h3>
        <div class="card-actions">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    {{ $period === 'today' ? trans('modules/product::product.today') : ($period === 'week' ? trans('modules/product::product.last_7_days') : trans('modules/product::product.last_30_days')) }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item {{ $period === 'today' ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('today')">{{ trans('modules/product::product.today') }}</a></li>
                    <li><a class="dropdown-item {{ $period === 'week' ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('week')">{{ trans('modules/product::product.last_7_days') }}</a></li>
                    <li><a class="dropdown-item {{ $period === 'month' ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('month')">{{ trans('modules/product::product.last_30_days') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            {{-- Main Revenue Display --}}
            <div class="col-12 col-lg-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="subheader">{{ trans('modules/product::product.total_revenue') }}</div>
                </div>
                <div class="d-flex align-items-baseline">
                    <div class="h1 mb-0 me-2">{{ core_number_format($totalRevenue) }} đ</div>
                    <span class="text-success d-inline-flex align-items-center">
                        {!! tabler_icon('trending-up', ['class' => 'icon icon-sm text-success']) !!}
                    </span>
                </div>
                <div class="text-muted mt-1">
                    <span class="badge bg-blue-lt">{{ $totalOrders }} {{ trans('modules/product::product.orders_count') }}</span>
                </div>
            </div>

            {{-- Sparkline Chart --}}
            <div class="col-12 col-lg-4">
                <div class="chart-sparkline" style="height: 60px;">
                    <svg viewBox="0 0 200 50" preserveAspectRatio="none" style="width:100%;height:100%">
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" class="chart-gradient-primary-stop-1"/>
                                <stop offset="100%" class="chart-gradient-primary-stop-2"/>
                            </linearGradient>
                        </defs>
                        <path d="M0 45 L20 40 L40 42 L60 35 L80 38 L100 30 L120 32 L140 25 L160 20 L180 15 L200 10 L200 50 L0 50 Z" fill="url(#gradient)"/>
                        <path d="M0 45 L20 40 L40 42 L60 35 L80 38 L100 30 L120 32 L140 25 L160 20 L180 15 L200 10" fill="none" class="chart-stroke-primary" stroke-width="2"/>
                    </svg>
                </div>
            </div>

            {{-- Donut Chart --}}
            <div class="col-12 col-lg-4">
                <div class="d-flex justify-content-center">
                    <div class="chart-radial d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <svg viewBox="0 0 36 36" class="position-absolute" style="transform: rotate(-90deg);">
                            <circle cx="18" cy="18" r="15.915" fill="transparent" class="chart-stroke-gray" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.915" fill="transparent" class="chart-stroke-success" stroke-width="3"
                                stroke-dasharray="{{ $cashPercent }} {{ 100 - $cashPercent }}" stroke-dashoffset="0"/>
                            <circle cx="18" cy="18" r="15.915" fill="transparent" class="chart-stroke-primary" stroke-width="3"
                                stroke-dasharray="{{ $bankPercent }} {{ 100 - $bankPercent }}" stroke-dashoffset="-{{ $cashPercent }}"/>
                            <circle cx="18" cy="18" r="15.915" fill="transparent" class="chart-stroke-purple" stroke-width="3"
                                stroke-dasharray="{{ $cardPercent }} {{ 100 - $cardPercent }}" stroke-dashoffset="-{{ $cashPercent + $bankPercent }}"/>
                        </svg>
                        <div class="text-center">
                            <div class="h4 mb-0">{{ $totalOrders }}</div>
                            <div class="text-muted small">{{ trans('modules/product::product.orders') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Breakdown --}}
    <div class="card-footer">
        <div class="row g-2">
            <div class="col-4">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-xs bg-success me-2">
                        {!! tabler_icon('cash', ['class' => 'icon icon-sm text-white']) !!}
                    </span>
                    <div>
                        <div class="text-muted small">{{ trans('modules/product::product.cash') }}</div>
                        <div class="font-weight-medium">{{ core_number_format($cashValue) }} đ</div>
                        <div class="progress progress-xs mt-1" style="width: 80px;">
                            <div class="progress-bar bg-success" style="width: {{ $cashPercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-xs bg-primary me-2">
                        {!! tabler_icon('building-bank', ['class' => 'icon icon-sm text-white']) !!}
                    </span>
                    <div>
                        <div class="text-muted small">{{ trans('modules/product::product.bank_transfer') }}</div>
                        <div class="font-weight-medium">{{ core_number_format($bankValue) }} đ</div>
                        <div class="progress progress-xs mt-1" style="width: 80px;">
                            <div class="progress-bar bg-primary" style="width: {{ $bankPercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-xs bg-purple me-2">
                        {!! tabler_icon('credit-card', ['class' => 'icon icon-sm text-white']) !!}
                    </span>
                    <div>
                        <div class="text-muted small">{{ trans('modules/product::product.card') }}</div>
                        <div class="font-weight-medium">{{ core_number_format($cardValue) }} đ</div>
                        <div class="progress progress-xs mt-1" style="width: 80px;">
                            <div class="progress-bar bg-purple" style="width: {{ $cardPercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

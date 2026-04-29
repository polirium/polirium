@php
    $conversionRate = $totalOrders > 0 ? min(100, $totalOrders) : 0;
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ trans('modules/product::product.sales_summary') }}</h3>
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
        <div class="row row-deck row-cards">
            {{-- Orders --}}
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader">{{ trans('modules/product::product.orders_uppercase') }}</div>
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-0">{{ $totalOrders }}</div>
                            <span class="ms-2 text-success d-inline-flex">
                                {!! tabler_icon('trending-up', ['class' => 'icon icon-sm text-success']) !!}
                            </span>
                        </div>
                    </div>
                    <div class="chart-sparkline" style="height: 30px; padding: 0 0.5rem;">
                        <svg viewBox="0 0 100 20" preserveAspectRatio="none" style="width:100%;height:100%">
                            <path d="M0 18 L10 15 L25 17 L40 12 L55 14 L70 8 L85 10 L100 5" fill="none" class="chart-stroke-primary" stroke-width="2"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Items sold --}}
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader">{{ trans('modules/product::product.products_sold') }}</div>
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-0">{{ core_number_format($totalItems) }}</div>
                        </div>
                    </div>
                    <div class="chart-sparkline" style="height: 30px; padding: 0 0.5rem;">
                        <svg viewBox="0 0 100 20" preserveAspectRatio="none" style="width:100%;height:100%">
                            <rect x="5" y="10" width="8" height="10" class="chart-fill-info" rx="1"/>
                            <rect x="18" y="8" width="8" height="12" class="chart-fill-info" rx="1"/>
                            <rect x="31" y="12" width="8" height="8" class="chart-fill-info" rx="1"/>
                            <rect x="44" y="5" width="8" height="15" class="chart-fill-info" rx="1"/>
                            <rect x="57" y="7" width="8" height="13" class="chart-fill-info" rx="1"/>
                            <rect x="70" y="3" width="8" height="17" class="chart-fill-info" rx="1"/>
                            <rect x="83" y="0" width="8" height="20" class="chart-fill-primary" rx="1"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Revenue --}}
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader">{{ trans('modules/product::product.revenue_uppercase') }}</div>
                        <div class="d-flex align-items-baseline">
                            <div class="h2 mb-0 text-success">{{ core_number_format($totalRevenue) }} đ</div>
                            <span class="ms-2 text-success d-inline-flex">
                                {!! tabler_icon('trending-up', ['class' => 'icon icon-sm text-success']) !!}
                            </span>
                        </div>
                    </div>
                    <div class="chart-sparkline" style="height: 30px; padding: 0 0.5rem;">
                        <svg viewBox="0 0 100 20" preserveAspectRatio="none" style="width:100%;height:100%">
                            <defs>
                                <linearGradient id="green-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" class="chart-gradient-success-stop-1"/>
                                    <stop offset="100%" class="chart-gradient-success-stop-2"/>
                                </linearGradient>
                            </defs>
                            <path d="M0 18 L15 14 L30 16 L45 10 L60 12 L75 6 L90 8 L100 2 L100 20 L0 20 Z" fill="url(#green-gradient)"/>
                            <path d="M0 18 L15 14 L30 16 L45 10 L60 12 L75 6 L90 8 L100 2" fill="none" class="chart-stroke-success" stroke-width="2"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Avg Order --}}
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader">{{ trans('modules/product::product.avg_per_order') }}</div>
                        <div class="d-flex align-items-baseline">
                            <div class="h2 mb-0">{{ core_number_format($avgOrderValue) }} đ</div>
                        </div>
                        <div class="mt-2">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Conversion rate</span>
                                <span>{{ $conversionRate }}%</span>
                            </div>
                            <div class="progress progress-xs">
                                <div class="progress-bar bg-primary" style="width: {{ $conversionRate }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($topProducts->count() > 0)
            <div class="hr-text mt-3">{{ trans('modules/product::product.top_selling_products') }}</div>
            <div class="row row-cards">
                @foreach ($topProducts->take(4) as $item)
                    <div class="col-6 col-lg-3">
                        <div class="card card-sm">
                            <div class="card-body d-flex align-items-center">
                                <span class="avatar bg-blue-lt me-3">
                                    {!! tabler_icon('box', ['class' => 'icon text-primary']) !!}
                                </span>
                                <div class="flex-fill">
                                    <div class="font-weight-medium text-truncate" style="max-width: 150px;">
                                        {{ $item->product?->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-muted small">{{ $item->product?->code ?? '' }}</div>
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-primary">{{ core_number_format($item->total_sold) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

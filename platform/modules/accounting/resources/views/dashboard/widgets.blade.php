<div>
<div class="row row-cards mb-4">
    @can('accountings.dashboard.revenue')
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-primary text-white avatar"><!-- Download SVG icon from http://tabler-icons.io/i/currency-dollar -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2" /><path d="M12 3v3m0 12v3" /></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ number_format($revenue) }}
                        </div>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.total_revenue_upper') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.discount')
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-yellow text-white avatar">
                           <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-discount-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 15l6 -6" /><circle cx="9.5" cy="9.5" r=".5" fill="currentColor" /><circle cx="14.5" cy="14.5" r=".5" fill="currentColor" /><path d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7a2.2 2.2 0 0 0 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1a2.2 2.2 0 0 0 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55z" /></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ number_format($discount) }}
                        </div>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.discount_upper') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.payable')
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-green text-white avatar">
                           <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-receipt"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" /></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ number_format($payable) }}
                        </div>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.customer_refundable_upper') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.paid')
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-teal text-white avatar">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-wallet"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" /><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" /></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ number_format($paid) }}
                        </div>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.customer_paid') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.debt')
    <div class="col-sm-6 col-lg-3 mt-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-red text-white avatar">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-alert-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ number_format($debt) }}
                        </div>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.remaining_receivable_upper') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.cogs')
    <div class="col-sm-6 col-lg-3 mt-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-purple text-white avatar">
                           <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-package"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /><path d="M16 5.25l-8 4.5" /></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ number_format($cogs) }}
                        </div>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.cost_price_upper') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>

<div class="row row-cards mb-4">
    @can('accountings.dashboard.payment_methods')
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ trans('modules/accounting::accounting.payment_method_upper') }}</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($methods as $method => $val)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        {{ ucfirst($method) }}
                        <span>{{ number_format($val) }}</span>
                    </div>
                    @endforeach
                     @if(empty($methods))
                        <div class="text-center text-muted p-2">{{ __('Không có dữ liệu') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.sale_channels')
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Kênh Bán hàng') }}</h3>
            </div>
            <div class="card-body">
                 <div class="list-group list-group-flush">
                    @foreach($channels as $channel => $val)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $channel }}
                        <span>{{ number_format($val) }}</span>
                    </div>
                    @endforeach
                    @if(empty($channels))
                        <div class="text-center text-muted p-2">{{ __('Không có dữ liệu') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('accountings.dashboard.delivery_partners')
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ trans('modules/accounting::accounting.delivery_partner_short') }}</h3>
            </div>
            <div class="card-body">
                 <div class="list-group list-group-flush">
                    @foreach($partners as $partner => $val)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $partner }}
                        <span>{{ number_format($val) }}</span>
                    </div>
                    @endforeach
                    @if(empty($partners))
                        <div class="text-center text-muted p-2">{{ __('Không có dữ liệu') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>
</div>

<div>
    <div class="row g-3">
        {{-- Left Column: Filters --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    {{-- Date Presets --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.time') }}</label>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            @foreach([
                                'today' => 'Hôm nay',
                                'yesterday' => 'Hôm qua',
                                'this_week' => 'Tuần này',
                                'last_week' => 'Tuần trước',
                                'this_month' => 'Tháng này',
                                'last_month' => 'Tháng trước',
                                'this_quarter' => 'Quý này',
                                'last_quarter' => 'Quý trước',
                                'this_year' => 'Năm này',
                                'last_year' => 'Năm trước',
                                'all_time' => 'Toàn thời gian',
                                'custom' => 'Tùy chọn',
                            ] as $key => $label)
                                <button
                                    type="button"
                                    @class([
                                        'btn btn-sm',
                                        'btn-primary' => $datePreset === $key,
                                        'btn-outline-secondary' => $datePreset !== $key,
                                    ])
                                    wire:click="$set('datePreset', '{{ $key }}')"
                                >
                                    {{ __($label) }}
                                </button>
                            @endforeach
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.from') }}</label>
                                <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.to') }}</label>
                                <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <input type="time" class="form-control form-control-sm" wire:model.live="timeFrom" placeholder="00:00">
                            </div>
                            <div class="col-6">
                                <input type="time" class="form-control form-control-sm" wire:model.live="timeTo" placeholder="23:59">
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    {{-- View Mode (Mối quan tâm) --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.customer_interest') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="viewMode">
                            <option value="sales">{{ trans('modules/accounting::accounting.detail_sales') }}</option>
                            <option value="summary">{{ trans('modules/accounting::accounting.summary_settlement') }}</option>
                        </select>
                    </div>

                    <hr class="my-2">

                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.status') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="status">
                            <option value="all">{{ trans('modules/accounting::accounting.all') }}</option>
                            <option value="completed">{{ trans('modules/accounting::accounting.complete') }}</option>
                            <option value="temp">{{ trans('modules/accounting::accounting.draft') }}</option>
                            <option value="cancel">{{ trans('modules/accounting::accounting.cancelled') }}</option>
                        </select>
                    </div>

                    {{-- Customer --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.customer') }}</label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            wire:model.live.debounce.500ms="customerSearch"
                            placeholder="{{ trans('modules/accounting::accounting.search_code_name_phone') }}"
                        >
                    </div>

                    {{-- Seller --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.sales_person_short') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="userId">
                            <option value="">{{ trans('modules/accounting::accounting.all') }}</option>
                            @foreach($this->users as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Author --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.created_by') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="authorId">
                            <option value="">{{ trans('modules/accounting::accounting.all') }}</option>
                            @foreach($this->users as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Payment Method --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.payment_method_filter') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="paymentMethod">
                            <option value="">{{ trans('modules/accounting::accounting.all') }}</option>
                            @foreach($this->paymentMethods as $method)
                                <option value="{{ $method->code }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sale Channel --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ trans('modules/accounting::accounting.sale_channel') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="saleChannelId">
                            <option value="">{{ trans('modules/accounting::accounting.all') }}</option>
                            @foreach($this->saleChannels as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Report Preview --}}
        <div class="col-md-9">
            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted small">
                    {!! tabler_icon('file-report', ['class' => 'icon icon-sm me-1']) !!}
                    {{ $this->summary['count'] }} {{ trans('modules/accounting::accounting.invoice.label') }}
                </div>
                <div class="btn-list">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        {!! tabler_icon('printer', ['class' => 'icon icon-sm me-1']) !!}
                        {{ trans('modules/accounting::accounting.print_report') }}
                    </button>
                </div>
            </div>

            {{-- Report Card (PDF-like) --}}
            <div class="card shadow-sm" id="report-printable">
                <div class="card-body p-4">
                    {{-- Report Header --}}
                    <div class="text-center mb-4">
                        <div class="text-muted small mb-1">
                            {{ trans('modules/accounting::accounting.report_date') }}: {{ now()->format('d/m/Y H:i') }}
                        </div>
                        <h2 class="mb-1">{{ trans('modules/accounting::accounting.sales_report') }}</h2>
                        <div class="text-muted">
                            {{ trans('modules/accounting::accounting.from') }}: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '-' }}
                            {{ trans('modules/accounting::accounting.to_lower') }}: {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '-' }}
                        </div>
                    </div>

                    {{-- Summary Cards --}}
                    <div class="row g-2 mb-4">
                        <div class="col-4 col-md-2">
                            <div class="card card-sm bg-primary-lt border-0">
                                <div class="card-body text-center py-2">
                                    <div class="text-muted small">{{ trans('modules/accounting::accounting.invoice_count_short') }}</div>
                                    <div class="fw-bold text-primary">{{ $this->summary['count'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card card-sm bg-cyan-lt border-0">
                                <div class="card-body text-center py-2">
                                    <div class="text-muted small">{{ trans('modules/accounting::accounting.revenue') }}</div>
                                    <div class="fw-bold text-cyan">{{ core_number_format($this->summary['total_cost']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card card-sm bg-orange-lt border-0">
                                <div class="card-body text-center py-2">
                                    <div class="text-muted small">{{ trans('modules/accounting::accounting.discount') }}</div>
                                    <div class="fw-bold text-orange">{{ core_number_format($this->summary['total_discount']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card card-sm bg-green-lt border-0">
                                <div class="card-body text-center py-2">
                                    <div class="text-muted small">{{ trans('modules/accounting::accounting.receivable') }}</div>
                                    <div class="fw-bold text-green">{{ core_number_format($this->summary['total_value']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card card-sm bg-teal-lt border-0">
                                <div class="card-body text-center py-2">
                                    <div class="text-muted small">{{ trans('modules/accounting::accounting.actual_collected') }}</div>
                                    <div class="fw-bold text-teal">{{ core_number_format($this->summary['total_payment']) }}</div>
                                </div>
                            </div>
                        </div>
                        @can('accountings.dashboard.cogs')
                        <div class="col-4 col-md-2">
                            <div class="card card-sm bg-red-lt border-0">
                                <div class="card-body text-center py-2">
                                    <div class="text-muted small">{{ trans('modules/accounting::accounting.cost_price') }}</div>
                                    <div class="fw-bold text-red">{{ core_number_format($this->summary['total_cogs'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </div>

                    @if($viewMode === 'sales')
                    {{-- === SALES VIEW: Breakdowns + Detailed Table === --}}
                    {{-- Breakdown Tables --}}
                    <div class="row g-3 mb-4">
                        {{-- Payment Method Breakdown --}}
                        <div class="col-md-4">
                            <div class="card border shadow-none">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">
                                        {!! tabler_icon('cash', ['class' => 'icon icon-sm me-1 text-primary']) !!}
                                        {{ trans('modules/accounting::accounting.collect_by_method') }}
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('modules/accounting::accounting.method') }}</th>
                                                <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count_short') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($this->paymentBreakdown as $code => $info)
                                                <tr
                                                    wire:click="$set('paymentMethod', '{{ $paymentMethod === $code ? '' : $code }}')"
                                                    style="cursor: pointer;"
                                                    @class(['table-primary' => $paymentMethod === $code])
                                                >
                                                    <td>
                                                        <span class="badge bg-blue-lt me-1">{{ $info['label'] }}</span>
                                                    </td>
                                                    <td class="text-center">{{ $info['count'] }}</td>
                                                    <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <td>{{ trans('modules/accounting::accounting.sum_total') }}</td>
                                                <td class="text-center">{{ collect($this->paymentBreakdown)->sum('count') }}</td>
                                                <td class="text-end text-primary">{{ core_number_format(collect($this->paymentBreakdown)->sum('total')) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Status Breakdown --}}
                        <div class="col-md-4">
                            <div class="card border shadow-none">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">
                                        {!! tabler_icon('list-check', ['class' => 'icon icon-sm me-1 text-success']) !!}
                                        {{ trans('modules/accounting::accounting.by_status') }}
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('modules/accounting::accounting.status') }}</th>
                                                <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count_short') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.receivable_customer') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($this->statusBreakdown as $statusKey => $info)
                                                <tr
                                                    wire:click="$set('status', '{{ $this->status === $statusKey ? 'all' : $statusKey }}')"
                                                    style="cursor: pointer;"
                                                    @class(['table-primary' => $this->status === $statusKey])
                                                >
                                                    <td>
                                                        @switch($statusKey)
                                                            @case('completed')
                                                                <span class="badge bg-success-lt">{{ $info['label'] }}</span>
                                                                @break
                                                            @case('temp')
                                                                <span class="badge bg-warning-lt">{{ $info['label'] }}</span>
                                                                @break
                                                            @case('cancel')
                                                                <span class="badge bg-danger-lt">{{ $info['label'] }}</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary-lt">{{ $info['label'] }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td class="text-center">{{ $info['count'] }}</td>
                                                    <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                                            @endforelse
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Channel Breakdown --}}
                        <div class="col-md-4">
                            <div class="card border shadow-none">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">
                                        {!! tabler_icon('affiliate', ['class' => 'icon icon-sm me-1 text-cyan']) !!}
                                        {{ trans('modules/accounting::accounting.by_channel') }}
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Kênh') }}</th>
                                                <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count_short') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.actual_collected') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($this->channelBreakdown as $channelId => $info)
                                                <tr
                                                    wire:click="toggleChannelFilter({{ $channelId }})"
                                                    style="cursor: pointer;"
                                                    @class(['table-primary' => $saleChannelId !== null && $saleChannelId !== '' && (int)$saleChannelId === (int)$channelId])
                                                >
                                                    <td>{{ $info['name'] }}</td>
                                                    <td class="text-center">{{ $info['count'] }}</td>
                                                    <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <td>{{ trans('modules/accounting::accounting.sum_total') }}</td>
                                                <td class="text-center">{{ $this->summary['count'] }}</td>
                                                <td class="text-end text-primary">{{ core_number_format(collect($this->channelBreakdown)->sum('total')) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        {{-- Author Breakdown --}}
                        <div class="col-md-6">
                            <div class="card border shadow-none">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">
                                        {!! tabler_icon('user', ['class' => 'icon icon-sm me-1 text-purple']) !!}
                                        {{ trans('modules/accounting::accounting.by_creator') }}
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('modules/accounting::accounting.created_by') }}</th>
                                                <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count_short') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.actual_collected') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($this->authorBreakdown as $aId => $info)
                                                <tr
                                                    wire:click="$set('authorId', {{ $authorId == $aId ? 'null' : $aId }})"
                                                    style="cursor: pointer;"
                                                    @class(['table-primary' => $authorId !== null && (int)$authorId === (int)$aId])
                                                >
                                                    <td>{{ $info['name'] }}</td>
                                                    <td class="text-center">{{ $info['count'] }}</td>
                                                    <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <td>{{ trans('modules/accounting::accounting.sum_total') }}</td>
                                                <td class="text-center">{{ $this->summary['count'] }}</td>
                                                <td class="text-end text-primary">{{ core_number_format(collect($this->authorBreakdown)->sum('total')) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Delivery Partner Breakdown --}}
                        <div class="col-md-6">
                            <div class="card border shadow-none">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">
                                        {!! tabler_icon('truck-delivery', ['class' => 'icon icon-sm me-1 text-orange']) !!}
                                        {{ trans('modules/accounting::accounting.by_delivery_partner') }}
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('modules/accounting::accounting.partner') }}</th>
                                                <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count_short') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.actual_collected') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($this->deliveryPartnerBreakdown as $dpId => $info)
                                                <tr
                                                    wire:click="toggleDeliveryPartnerFilter({{ $dpId }})"
                                                    style="cursor: pointer;"
                                                    @class(['table-primary' => $deliveryPartnerId !== null && $deliveryPartnerId !== '' && (int)$deliveryPartnerId === (int)$dpId])
                                                >
                                                    <td>{{ $info['name'] }}</td>
                                                    <td class="text-center">{{ $info['count'] }}</td>
                                                    <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <td>{{ trans('modules/accounting::accounting.sum_total') }}</td>
                                                <td class="text-center">{{ $this->summary['count'] }}</td>
                                                <td class="text-end text-primary">{{ core_number_format(collect($this->deliveryPartnerBreakdown)->sum('total')) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Active Filter Indicator --}}
                    @if($paymentMethod || $status !== 'all' || $saleChannelId !== null || $authorId || $deliveryPartnerId !== null)
                    <div class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-3" style="font-size: 0.85rem;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            {!! tabler_icon('filter', ['class' => 'icon icon-sm']) !!}
                            <span class="fw-medium">{{ trans('modules/accounting::accounting.filtering') }}</span>
                            @if($paymentMethod)
                                <span class="badge bg-blue-lt">{{ trans('modules/accounting::accounting.pt_label') }} {{ $this->paymentBreakdown[$paymentMethod]['label'] ?? $paymentMethod }}</span>
                            @endif
                            @if($status !== 'all')
                                <span class="badge bg-green-lt">{{ trans('modules/accounting::accounting.tt_label') }} {{ $this->statusBreakdown[$status]['label'] ?? $status }}</span>
                            @endif
                            @if($saleChannelId !== null)
                                <span class="badge bg-cyan-lt">{{ __('Kênh:') }} {{ $this->channelBreakdown[$saleChannelId]['name'] ?? $saleChannelId }}</span>
                            @endif
                            @if($authorId)
                                <span class="badge bg-purple-lt">{{ trans('modules/accounting::accounting.created_by_label') }} {{ $this->authorBreakdown[$authorId]['name'] ?? $authorId }}</span>
                            @endif
                            @if($deliveryPartnerId !== null)
                                <span class="badge bg-indigo-lt">{{ trans('modules/accounting::accounting.delivery_partner_short') }}: {{ $this->deliveryPartnerBreakdown[$deliveryPartnerId]['name'] ?? $deliveryPartnerId }}</span>
                            @endif
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="clearBreakdownFilters"
                        >
                            {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                            {{ trans('modules/accounting::accounting.clear_filter') }}
                        </button>
                    </div>
                    @endif

                    {{-- Data Table --}}
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped table-bordered table-sm" style="font-size: 0.8rem;">
                            <thead>
                                <tr class="bg-primary text-white">
                                    <th class="text-center" style="width: 40px;">#</th>
                                    <th>{{ __('Mã HĐ') }}</th>
                                    <th>{{ trans('modules/accounting::accounting.time') }}</th>
                                    <th>{{ trans('modules/accounting::accounting.customer') }}</th>
                                    <th>{{ __('Kênh bán') }}</th>
                                    <th>{{ trans('modules/accounting::accounting.delivery_partner_short') }}</th>
                                    <th class="text-center">{{ __('SL') }}</th>
                                    <th class="text-end">{{ trans('modules/accounting::accounting.revenue') }}</th>
                                    <th class="text-end">{{ trans('modules/accounting::accounting.discount') }}</th>
                                    <th class="text-end">{{ trans('modules/accounting::accounting.actual_collected') }}</th>
                                    <th>{{ trans('modules/accounting::accounting.payment_method_filter') }}</th>
                                    <th>{{ trans('modules/accounting::accounting.staff') }}</th>
                                    <th class="text-center">{{ trans('modules/accounting::accounting.status') }}</th>
                                    @can('accountings.dashboard.cogs')
                                    <th class="text-end">{{ trans('modules/accounting::accounting.cost_price') }}</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->reportData as $index => $payment)
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('accountings.payment.index', ['search' => $payment->code]) }}" target="_blank" class="text-primary fw-medium">
                                                {{ $payment->code }}
                                            </a>
                                        </td>
                                        <td class="text-nowrap">{{ $payment->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>{{ $payment->customer?->name ?? trans('modules/accounting::accounting.walk_in') }}</td>
                                        <td class="text-nowrap">{{ $payment->saleChannel?->name ?? __('Không xác định') }}</td>
                                        <td class="text-nowrap">{{ $payment->latestDelivery?->partnerDelivery?->name ?? '-' }}</td>
                                        <td class="text-center">{{ $payment->amount_products }}</td>
                                        <td class="text-end text-nowrap">{{ core_number_format($payment->total_cost) }}</td>
                                        <td class="text-end text-nowrap text-danger">
                                            @if($payment->discount_value > 0)
                                                @if($payment->discount_type === 'percent')
                                                    {{ core_number_format($payment->total_cost * $payment->discount_value / 100) }}
                                                    <span class="text-muted">({{ $payment->discount_value }}%)</span>
                                                @else
                                                    {{ core_number_format($payment->discount_value) }}
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap fw-medium text-success">{{ core_number_format($payment->value_payment) }}</td>
                                        <td class="text-nowrap">
                                            @if($payment->type_payment)
                                                @foreach($payment->type_payment as $tp)
                                                    @php $code = $tp['method'] ?? ''; @endphp
                                                    <span class="badge bg-blue-lt">{{ $this->paymentBreakdown[$code]['label'] ?? $tp['label'] ?? $code }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $payment->user?->name ?? '-' }}</td>
                                        <td class="text-center">
                                            @switch($payment->status)
                                                @case('completed')
                                                    <span class="badge bg-success-lt">{{ trans('modules/accounting::accounting.complete') }}</span>
                                                    @break
                                                @case('temp')
                                                    <span class="badge bg-warning-lt">{{ trans('modules/accounting::accounting.draft') }}</span>
                                                    @break
                                                @case('cancel')
                                                    <span class="badge bg-danger-lt">{{ trans('modules/accounting::accounting.cancel') }}</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary-lt">{{ $payment->status }}</span>
                                            @endswitch
                                        </td>
                                        @can('accountings.dashboard.cogs')
                                        <td class="text-end text-nowrap">
                                            @php
                                                $invoiceCogs = $payment->products->sum(function ($item) {
                                                    return ($item->product?->cost ?? 0) * $item->amount;
                                                });
                                            @endphp
                                            {{ core_number_format($invoiceCogs) }}
                                        </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="@can('accountings.dashboard.cogs') 14 @else 13 @endcan" class="text-center text-muted py-4">
                                            {!! tabler_icon('file-report', ['class' => 'icon icon-lg opacity-25 mb-2', 'style' => 'width: 48px; height: 48px;']) !!}
                                            <p class="mb-0">{{ __('Không có dữ liệu trong khoảng thời gian này.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($this->reportData->count() > 0)
                                <tfoot>
                                    <tr class="fw-bold bg-light">
                                        <td colspan="6" class="text-end">{{ trans('modules/accounting::accounting.grand_total') }}:</td>
                                        <td class="text-center">{{ $this->summary['total_products'] }}</td>
                                        <td class="text-end">{{ core_number_format($this->summary['total_cost']) }}</td>
                                        <td class="text-end text-danger">{{ core_number_format($this->summary['total_discount']) }}</td>
                                        <td class="text-end text-success">{{ core_number_format($this->summary['total_payment']) }}</td>
                                        <td colspan="2"></td>
                                        @can('accountings.dashboard.cogs')
                                        <td class="text-end">{{ core_number_format($this->summary['total_cogs'] ?? 0) }}</td>
                                        @endcan
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    @else
                    {{-- === SUMMARY VIEW: KiotViet-style Reconciliation === --}}
                    {{-- 1. Revenue Summary --}}
                    <div class="mb-4">
                        <h3 class="mb-3 border-bottom pb-2">
                            {!! tabler_icon('report-money', ['class' => 'icon me-1 text-primary']) !!}
                            {{ trans('modules/accounting::accounting.section_revenue_summary') }}
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold bg-light" style="width: 50%;">{{ trans('modules/accounting::accounting.total_invoices') }}</td>
                                        <td class="text-end fw-bold">{{ $this->summary['count'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light">{{ trans('modules/accounting::accounting.total_revenue_before_discount') }}</td>
                                        <td class="text-end">{{ core_number_format($this->summary['total_cost']) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light">{{ trans('modules/accounting::accounting.total_discount') }}</td>
                                        <td class="text-end text-danger">- {{ core_number_format($this->summary['total_discount']) }}</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td class="fw-bold">{{ trans('modules/accounting::accounting.total_receivable_after_discount') }}</td>
                                        <td class="text-end fw-bold">{{ core_number_format($this->summary['total_value']) }}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td class="fw-bold">{{ trans('modules/accounting::accounting.total_actual_collected') }}</td>
                                        <td class="text-end fw-bold text-success">{{ core_number_format($this->summary['total_payment']) }}</td>
                                    </tr>
                                    @php
                                        $difference = $this->summary['total_payment'] - $this->summary['total_value'];
                                    @endphp
                                    @if(abs($difference) > 0)
                                    <tr class="{{ $difference >= 0 ? 'table-info' : 'table-danger' }}">
                                        <td class="fw-bold">{{ $difference >= 0 ? trans('modules/accounting::accounting.overpaid') : trans('modules/accounting::accounting.underpaid') }}</td>
                                        <td class="text-end fw-bold {{ $difference >= 0 ? 'text-info' : 'text-danger' }}">
                                            {{ core_number_format(abs($difference)) }}
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 2. Payment Method Breakdown (KiotViet style) --}}
                    <div class="mb-4">
                        <h3 class="mb-3 border-bottom pb-2">
                            {!! tabler_icon('cash', ['class' => 'icon me-1 text-blue']) !!}
                            {{ trans('modules/accounting::accounting.section_payment_method') }}
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                                <thead>
                                    <tr class="bg-blue text-white">
                                        <th>{{ trans('modules/accounting::accounting.method') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.collected_amount') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.percent_ratio') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalPaymentAll = collect($this->paymentBreakdown)->sum('total');
                                    @endphp
                                    @forelse($this->paymentBreakdown as $code => $info)
                                        <tr>
                                            <td>
                                                <span class="badge bg-blue-lt me-1">{{ $info['label'] }}</span>
                                            </td>
                                            <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                            <td class="text-end text-muted">
                                                {{ $totalPaymentAll > 0 ? round($info['total'] / $totalPaymentAll * 100, 1) : 0 }}%
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted">-</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold bg-blue-lt">
                                        <td>{{ trans('modules/accounting::accounting.grand_total') }}</td>
                                        <td class="text-end">{{ core_number_format($totalPaymentAll) }}</td>
                                        <td class="text-end">100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Status Breakdown --}}
                    <div class="mb-4">
                        <h3 class="mb-3 border-bottom pb-2">
                            {!! tabler_icon('list-check', ['class' => 'icon me-1 text-green']) !!}
                            {{ trans('modules/accounting::accounting.section_invoice_status') }}
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                                <thead>
                                    <tr class="bg-green text-white">
                                        <th>{{ trans('modules/accounting::accounting.status') }}</th>
                                        <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.receivable') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.actual_collected') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.difference') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->statusBreakdown as $status => $info)
                                        <tr>
                                            <td>
                                                @switch($status)
                                                    @case('completed')
                                                        <span class="badge bg-success-lt">{{ $info['label'] }}</span>
                                                        @break
                                                    @case('temp')
                                                        <span class="badge bg-warning-lt">{{ $info['label'] }}</span>
                                                        @break
                                                    @case('cancel')
                                                        <span class="badge bg-danger-lt">{{ $info['label'] }}</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary-lt">{{ $info['label'] }}</span>
                                                @endswitch
                                            </td>
                                            <td class="text-center">{{ $info['count'] }}</td>
                                            <td class="text-end">{{ core_number_format($info['total']) }}</td>
                                            <td class="text-end">{{ core_number_format($info['payment']) }}</td>
                                            <td class="text-end {{ ($info['payment'] - $info['total']) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ core_number_format($info['payment'] - $info['total']) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">-</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 4. Channel Breakdown --}}
                    <div class="mb-4">
                        <h3 class="mb-3 border-bottom pb-2">
                            {!! tabler_icon('affiliate', ['class' => 'icon me-1 text-cyan']) !!}
                            {{ trans('modules/accounting::accounting.section_sale_channel') }}
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                                <thead>
                                    <tr class="bg-cyan text-white">
                                        <th>{{ __('Kênh bán') }}</th>
                                        <th class="text-center">{{ trans('modules/accounting::accounting.invoice_count') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.actual_collected') }}</th>
                                        <th class="text-end">{{ trans('modules/accounting::accounting.percent_ratio') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalChannelAll = collect($this->channelBreakdown)->sum('total');
                                    @endphp
                                    @forelse($this->channelBreakdown as $channelId => $info)
                                        <tr>
                                            <td>{{ $info['name'] }}</td>
                                            <td class="text-center">{{ $info['count'] }}</td>
                                            <td class="text-end fw-medium">{{ core_number_format($info['total']) }}</td>
                                            <td class="text-end text-muted">
                                                {{ $totalChannelAll > 0 ? round($info['total'] / $totalChannelAll * 100, 1) : 0 }}%
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">-</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            body * { visibility: hidden; }
            #report-printable, #report-printable * { visibility: visible; }
            #report-printable {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none !important;
                border: none !important;
            }
            .no-print { display: none !important; }
        }
    </style>
</div>

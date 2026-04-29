@php
    $row = \Polirium\Modules\Product\Http\Model\Stock\Stock::with(['products.product', 'branch', 'user', 'userCreated'])->find($id);
    $deviation = (int) ($row->deviation ?? 0);
    $deviationColor = $deviation > 0 ? 'success' : ($deviation < 0 ? 'danger' : 'secondary');
    $matchedProducts = $row->products->where('quantity_difference', 0)->count();
    $diffProducts = $row->products->where('quantity_difference', '!=', 0)->count();
    $totalProducts = $row->products->count();
@endphp

<style>
    .invoice-detail-selectable,
    .invoice-detail-selectable * {
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        cursor: text !important;
    }
</style>

<div class="invoice-detail-selectable rounded border p-3 shadow-sm"
     style="position: sticky; left: 0; background: var(--tblr-bg-surface); z-index: 10;"
     x-data="{
         tab: 1,
         initWidth() {
             const container = this.$el.closest('.table-responsive');
             if (container) {
                 this.$el.style.width = container.clientWidth + 'px';
             }
         }
     }"
     x-init="initWidth();
     window.addEventListener('resize', () => initWidth())">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="d-flex align-items-center mb-2 gap-2">
                <span class="badge bg-{{ $deviationColor }}-lt text-{{ $deviationColor }}">
                    {!! tabler_icon('clipboard-list', ['class' => 'icon icon-sm']) !!}
                    {{ $row->status_name }}
                </span>
                <span class="badge bg-muted-lt">
                    {{ $row->code }}
                </span>
            </div>
            <p class="text-muted small mb-0">
                {!! tabler_icon('calendar', ['class' => 'icon icon-sm text-muted me-1']) !!}
                {{ core_format_date($row->created_at) }}
                @if ($row->branch)
                    <span class="mx-1">•</span>
                    {{ $row->branch->name }}
                @endif
            </p>
        </div>
        <div class="text-end">
            <div class="h2 text-{{ $deviationColor }} mb-0">
                {{ $deviation > 0 ? '+' : '' }}{{ core_number_format($deviation) }}
            </div>
            <div class="text-muted small">Chênh lệch</div>
        </div>
    </div>

    {{-- Deviation Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-sm bg-success-lt">
                <div class="card-body py-3 text-center">
                    <div class="h3 text-success mb-0">+{{ core_number_format((int) ($row->increase_deviation ?? 0)) }}</div>
                    <div class="text-muted small">{{ trans('modules/product::stock.increase_deviation') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sm bg-danger-lt">
                <div class="card-body py-3 text-center">
                    <div class="h3 text-danger mb-0">{{ core_number_format((int) ($row->decrease_deviation ?? 0)) }}</div>
                    <div class="text-muted small">{{ trans('modules/product::stock.decrease_deviation') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sm bg-info-lt">
                <div class="card-body py-3 text-center">
                    <div class="h3 text-info mb-0">{{ core_number_format((int) ($row->value ?? 0)) }} đ</div>
                    <div class="text-muted small">{{ trans('modules/product::stock.value') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active': tab === 1 }" @click="tab = 1">
                {!! tabler_icon('info-circle', ['class' => 'icon me-1']) !!}
                {{ trans('modules/product::stock.stock_info') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active': tab === 2 }" @click="tab = 2">
                {!! tabler_icon('package', ['class' => 'icon me-1']) !!}
                {{ trans('modules/product::stock.products') }}
                <span class="badge bg-muted-lt ms-1">{{ $totalProducts }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active': tab === 3 }" @click="tab = 3">
                {!! tabler_icon('chart-bar', ['class' => 'icon me-1']) !!}
                {{ trans('modules/product::stock.summary') }}
            </a>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
        {{-- Tab 1: Stock Information --}}
        <div class="tab-pane" :class="{ 'show active': tab === 1 }" x-show="tab === 1">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="card card-sm">
                                <div class="card-body py-2">
                                    <div class="text-muted small mb-1">{{ trans('modules/product::stock.amount') }}</div>
                                    <div><strong>{{ core_number_format((int) ($row->amount ?? 0)) }}</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-sm">
                                <div class="card-body py-2">
                                    <div class="text-muted small mb-1">{{ trans('modules/product::stock.deviation') }}</div>
                                    <div>
                                        <strong class="text-{{ $deviationColor }}">
                                            {{ $deviation > 0 ? '+' : '' }}{{ core_number_format($deviation) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-sm">
                                <div class="card-body py-2">
                                    <div class="text-muted small mb-1">{{ trans('modules/product::product.creator') }}</div>
                                    <div><strong>{{ $row->userCreated?->name ?? 'N/A' }}</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-sm">
                                <div class="card-body py-2">
                                    <div class="text-muted small mb-1">{{ trans('modules/product::product.approver') }}</div>
                                    <div><strong>{{ $row->user?->name ?? 'N/A' }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                {!! tabler_icon('file-text', ['class' => 'icon text-muted me-1']) !!}
                                {{ trans('core/base::general.note') }}
                            </h6>
                            <p class="small text-muted mb-0">{{ $row->note ?? 'Không có ghi chú' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 2: Stock Products --}}
        <div class="tab-pane" :class="{ 'show active': tab === 2 }" x-show="tab === 2" x-cloak>
            <x-ui::table striped class="card-table">
                <thead>
                    <tr>
                        <th class="w-1">{{ trans('#') }}</th>
                        <th>{{ trans('modules/product::product.code') }}</th>
                        <th>{{ trans('modules/product::product.name') }}</th>
                        <th class="text-end">{{ trans('modules/product::stock.inventory') }}</th>
                        <th class="text-end">{{ trans('modules/product::stock.actual') }}</th>
                        <th class="text-end">{{ trans('modules/product::stock.quantity_difference') }}</th>
                        <th class="text-end">{{ trans('modules/product::stock.value_difference') }}</th>
                        <th>{{ trans('core/base::general.note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($row->products as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="badge bg-muted-lt">{{ $item->product->code }}</span></td>
                            <td><strong>{{ $item->product->name }}</strong></td>
                            <td class="text-end">{{ core_number_format($item->amount) }}</td>
                            <td class="text-end">{{ core_number_format($item->actual_stock) }}</td>
                            <td class="text-end">
                                <span class="badge {{ $item->quantity_difference > 0 ? 'bg-success-lt text-success' : ($item->quantity_difference < 0 ? 'bg-danger-lt text-danger' : 'bg-muted-lt') }}">
                                    {{ $item->quantity_difference > 0 ? '+' : '' }}{{ core_number_format($item->quantity_difference) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="badge {{ $item->value_difference > 0 ? 'bg-success-lt text-success' : ($item->value_difference < 0 ? 'bg-danger-lt text-danger' : 'bg-muted-lt') }}">
                                    {{ $item->value_difference > 0 ? '+' : '' }}{{ core_number_format($item->value_difference) }}
                                </span>
                            </td>
                            <td><span class="text-muted small">{{ $item->note ?? '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-4 text-center">
                                <span class="text-muted">
                                    {!! tabler_icon('box', ['class' => 'icon text-muted mb-2']) !!}
                                    <br>{{ trans('modules/product::product.no_products') }}
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui::table>
        </div>

        {{-- Tab 3: Summary Statistics --}}
        <div class="tab-pane" :class="{ 'show active': tab === 3 }" x-show="tab === 3" x-cloak>
            <div class="row g-4">
                {{-- Product Statistics --}}
                <div class="col-md-6">
                    <x-ui::card>
                        <x-slot name="header">
                            <h5 class="mb-0">
                                {!! tabler_icon('box', ['class' => 'icon me-1']) !!}
                                {{ trans('modules/product::product.product_stats') }}
                            </h5>
                        </x-slot>
                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <div class="card card-sm bg-primary-lt">
                                    <div class="card-body py-2 text-center">
                                        <div class="h4 mb-0">{{ $totalProducts }}</div>
                                        <div class="text-muted small">Tổng</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-sm bg-success-lt">
                                    <div class="card-body py-2 text-center">
                                        <div class="h4 mb-0">{{ $matchedProducts }}</div>
                                        <div class="text-muted small">Khớp</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-sm bg-warning-lt">
                                    <div class="card-body py-2 text-center">
                                        <div class="h4 mb-0">{{ $diffProducts }}</div>
                                        <div class="text-muted small">Lệch</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <x-ui::table class="table-sm table-borderless">
                            <tr>
                                <td class="text-muted" style="width: 60%;">{{ trans('modules/product::product.increased_products') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-success-lt text-success">
                                        +{{ $row->products->where('quantity_difference', '>', 0)->count() }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ trans('modules/product::product.decreased_products') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-danger-lt text-danger">
                                        {{ $row->products->where('quantity_difference', '<', 0)->count() }}
                                    </span>
                                </td>
                            </tr>
                        </x-ui::table>
                    </x-ui::card>
                </div>

                {{-- Value Statistics --}}
                <div class="col-md-6">
                    <x-ui::card>
                        <x-slot name="header">
                            <h5 class="mb-0">
                                {!! tabler_icon('coin', ['class' => 'icon me-1']) !!}
                                {{ trans('modules/product::product.value_stats') }}
                            </h5>
                        </x-slot>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="card card-sm bg-success-lt">
                                    <div class="card-body py-2 text-center">
                                        <div class="h5 text-success mb-0">
                                            +{{ core_number_format((int) $row->products->where('value_difference', '>', 0)->sum('value_difference')) }}
                                        </div>
                                        <div class="text-muted small">Tăng</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-sm bg-danger-lt">
                                    <div class="card-body py-2 text-center">
                                        <div class="h5 text-danger mb-0">
                                            {{ core_number_format((int) $row->products->where('value_difference', '<', 0)->sum('value_difference')) }}
                                        </div>
                                        <div class="text-muted small">Giảm</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-sm bg-info-lt">
                            <div class="card-body py-2 text-center">
                                <div class="text-muted small mb-1">{{ trans('Chênh lệch ròng') }}</div>
                                <div class="h3 text-info mb-0">
                                    {{ ($row->value ?? 0) > 0 ? '+' : '' }}{{ core_number_format((int) ($row->value ?? 0)) }} đ
                                </div>
                            </div>
                        </div>
                    </x-ui::card>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="action-buttons">
        @can('products.stock.manage')
            <a
               href="{{ route('products.stock.stock', $row->id) }}"
               class="action-btn edit icon-only"
               data-tooltip="Sửa"
               aria-label="Sửa">
                {!! tabler_icon('pencil', ['class' => 'icon']) !!}
                <span class="action-text">Sửa</span>
            </a>
        @endcan
    </div>
</div>

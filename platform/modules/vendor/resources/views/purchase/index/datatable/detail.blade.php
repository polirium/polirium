@php
    $needPay = (float)($row->need_pay ?? 0) - (float)($row->value ?? 0);
    $canViewPrice = auth()->user()?->can('vendors.purchases.view-price');
    $statusColor = match($row->status ?? 'temp') {
        'completed', 'paid' => 'success',
        'pending', 'temp' => 'warning',
        'cancelled' => 'danger',
        default => 'secondary'
    };
@endphp

<div x-data="{ localTab: 1 }">
    <x-ui::card>
        {{-- Header with code and status --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <p class="h3 mb-1"><b>{{ $row->code }}</b></p>
                <p class="text-muted small mb-0">
                    {!! tabler_icon('calendar', ['class' => 'icon icon-sm me-1']) !!}
                    {{ core_format_date($row->created_at) }}
                </p>
            </div>
            <span class="badge bg-{{ $statusColor }}-lt text-{{ $statusColor }} fs-6">
                {{ $row->status_name ?? match($row->status ?? 'temp') {
                    'completed', 'paid' => __('modules/vendor::purchase.status.success'),
                    'pending', 'temp' => __('modules/vendor::purchase.status.temp'),
                    'cancelled' => 'Đã hủy',
                    default => $row->status
                } }}
            </span>
        </div>

        <x-ui::tab>
            <x-slot name="header">
                <x-ui::tab.header @click="localTab = 1" :active="false" x-bind:class="{ 'active': localTab == 1 }" :label="trans('modules/vendor::purchase.detail')" />
                <x-ui::tab.header @click="localTab = 2" :active="false" x-bind:class="{ 'active': localTab == 2 }" :label="trans('modules/vendor::purchase.products')" />
            </x-slot>

            <x-ui::tab.item :show="true" x-show="localTab == 1">
                {{-- Info Cards Grid --}}
                <div class="row g-3 mb-4">
                    {{-- Vendor Info --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="avatar bg-primary-lt me-2">
                                        {!! tabler_icon('building-store', ['class' => 'text-primary icon']) !!}
                                    </span>
                                    <div class="subheader">{{ __('modules/vendor::vendor.name') }}</div>
                                </div>
                                <div class="h4 mb-0">{{ $row->vendor_name ?? $row->vendor?->name ?? '-' }}</div>
                                <div class="text-muted small mt-1">
                                    {{ $row->branch_name ?? $row->branch?->name ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    @if($canViewPrice)
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="avatar bg-success-lt me-2">
                                        {!! tabler_icon('credit-card', ['class' => 'text-success icon']) !!}
                                    </span>
                                    <div class="subheader">{{ __('modules/vendor::purchase.payment') }}</div>
                                </div>
                                <div class="h4 mb-0 text-green">
                                    {{ core_number_format($row->value ?? 0) }} đ
                                </div>
                                @if($needPay > 0)
                                    <div class="text-muted small mt-1">
                                        {{ __('modules/vendor::purchase.need_pay') }}: {{ core_number_format($needPay) }} đ
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Total Info --}}
                    <div class="col-md-12 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="avatar bg-info-lt me-2">
                                        {!! tabler_icon('receipt-2', ['class' => 'text-info icon']) !!}
                                    </span>
                                    <div class="subheader">{{ __('modules/vendor::purchase.total_value') }}</div>
                                </div>
                                <div class="h3 mb-0 text-info">
                                    @if($canViewPrice)
                                        {{ core_number_format($row->total ?? 0) }} đ
                                    @else
                                        ***
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">
                                    {{ __('modules/vendor::purchase.products_count', ['count' => $row->products_count]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Info --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <x-ui::table class="table-sm table-borderless">
                            <tr>
                                <td class="text-muted" style="width: 40%;">{{ __('modules/vendor::purchase.user_created') }}</td>
                                <td><strong>{{ $row->user_created_name ?? $row->userCreated?->name ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ __('modules/vendor::vendor.branch') }}</td>
                                <td><strong>{{ $row->branch_name ?? $row->branch?->name ?? 'N/A' }}</strong></td>
                            </tr>
                        </x-ui::table>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-sm bg-muted-lt">
                            <div class="card-body py-2">
                                <div class="text-muted small mb-1">{{ trans('core/base::general.note') }}</div>
                                <div class="small">{{ $row->note ?? __('modules/vendor::purchase.no_note') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui::tab.item>

            <x-ui::tab.item :show="true" x-show="localTab == 2">
                <x-ui::table striped class="card-table">
                    <thead>
                        <tr>
                            <th class="w-1">{{ trans('core/base::general.id') }}</th>
                            <th>{{ trans('modules/product::product.code') }}</th>
                            <th>{{ trans('modules/product::product.name') }}</th>
                            <th class="text-end">{{ trans('modules/product::product.amount') }}</th>
                            @if($canViewPrice)
                            <th class="text-end">{{ __('modules/vendor::purchase.unit_price') }}</th>
                            <th class="text-end">{{ __('modules/vendor::purchase.discount') }}</th>
                            <th class="text-end">{{ __('modules/vendor::purchase.discount_import') }}</th>
                            <th class="text-end">{{ __('modules/vendor::purchase.total_amount_column') }}</th>
                            @endif
                            <th class="text-center w-1"><x-tabler-icons::settings class="icon" /></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (is_array($row->products) ? $row->products : $row->products ?? [] as $item)
                            <tr>
                                <td>{{ $item['id'] ?? '-' }}</td>
                                <td>
                                    @if(!empty($item['product']['code']) || (!empty($item->product) && !empty($item->product->code)))
                                        <a href="{{ route('products.index', ['code' => $item['product']['code'] ?? $item->product->code]) }}" target="_blank" class="badge bg-primary-lt user-select-all text-decoration-none" title="Xem sản phẩm">
                                            {{ $item['product']['code'] ?? $item->product->code }}
                                        </a>
                                    @else
                                        <span class="badge bg-muted-lt user-select-all">-</span>
                                    @endif
                                </td>
                                <td><strong>{{ $item['product']['name'] ?? $item->product?->name ?? '-' }}</strong></td>
                                <td class="text-end">{{ core_number_format($item['amount'] ?? $item->amount ?? 0) }}</td>
                                @if($canViewPrice)
                                <td class="text-end">{{ core_number_format($item['product']['cost'] ?? $item->product?->cost ?? 0) }}</td>
                                <td class="text-end">
                                    @if(($item['discount_value'] ?? $item->discount_value ?? 0) > 0)
                                        <span class="text-danger">-{{ core_number_format($item['discount_value'] ?? $item->discount_value ?? 0) }}</span>
                                        {{ ($item['discount_type'] ?? $item->discount_type ?? 'percent') === 'percent' ? '%' : 'đ' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ core_number_format($item['price'] ?? $item->price ?? 0) }}</td>
                                <td class="text-end"><strong>{{ core_number_format($item['value'] ?? $item->value ?? 0) }}</strong></td>
                                @endif
                                <td class="text-center">
                                    <x-ui::button
                                        color="secondary"
                                        :ghost="true"
                                        size="sm"
                                        icon="eye"
                                        class="btn-icon"
                                        wire:click="$dispatch('show-modal-create-product', { id: {{ $item['product']['id'] ?? $item->product?->id ?? 0 }}, readonly: true })"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <span class="text-muted">
                                        {!! tabler_icon('box', ['class' => 'icon icon-md mb-2']) !!}
                                        <br>{{ __('modules/vendor::purchase.no_products') }}
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui::table>
                {{-- Ensure modal is available to view product --}}
                @livewire('modules/product::index.modal.modal-create-product')
            </x-ui::tab.item>
        </x-ui::tab>

        @if(!isset($hideToolbar))
        <hr>

        {{-- Action Toolbar --}}
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <div class="d-flex gap-2">
                @if(($row->status ?? '') !== 'cancelled')
                    @can('vendors.purchases.edit')
                    {{-- Cancel Button (soft cancel - giữ record) --}}
                    <x-ui::button
                        color="white"
                        size="sm"
                        icon="ban"
                        class="text-warning"
                        wire:click="$parent.cancel({{ $row->id }})"
                        wire:confirm="Bạn có chắc chắn muốn hủy phiếu nhập hàng này? Phiếu sẽ chuyển sang trạng thái 'Đã hủy' và cập nhật lại tồn kho."
                    >
                        {{ __('Hủy') }}
                    </x-ui::button>
                    @endcan
                @endif

                @can('vendors.purchases.delete')
                {{-- Delete Button (hard delete - chỉ admin) --}}
                <x-ui::button
                    color="white"
                    size="sm"
                    icon="trash"
                    class="text-danger"
                    wire:click="$parent.delete({{ $row->id }})"
                    wire:confirm="Bạn có chắc chắn muốn XÓA HOÀN TOÀN phiếu nhập hàng này? Thao tác này không thể hoàn tác."
                >
                    {{ __('Xóa') }}
                </x-ui::button>
                @endcan

                @if(($row->status ?? '') !== 'cancelled')
                    @can('vendors.purchases.create')
                    {{-- Copy Button --}}
                    <x-ui::button
                        color="white"
                        size="sm"
                        icon="copy"
                        :href="route('vendors.purchases.order', ['copy_id' => $row->id])"
                    >
                        {{ __('Sao chép') }}
                    </x-ui::button>
                    @endcan
                @endif

                @can('vendors.purchases.index')
                {{-- Export Button --}}
                <x-ui::button
                    color="white"
                    size="sm"
                    icon="file-export"
                    :href="route('vendors.purchases.export', $row->id)"
                    target="_blank"
                >
                    {{ __('Xuất file') }}
                </x-ui::button>
                @endcan
            </div>

            <div class="d-flex gap-2">
                @if(($row->status ?? '') !== 'cancelled')
                    @can('vendors.purchases.edit')
                    {{-- Open/Edit Button --}}
                    <x-ui::button
                        color="primary"
                        size="sm"
                        icon="pencil"
                        :href="route('vendors.purchases.order', $id)"
                    >
                        {{ __('Sửa') }}
                    </x-ui::button>

                    {{-- Save Button --}}
                    <x-ui::button
                        color="white"
                        size="sm"
                        icon="device-floppy"
                    >
                        {{ __('Lưu') }}
                    </x-ui::button>
                    @endcan

                    @can('vendors.refunds.index')
                    {{-- Return Purchase Button --}}
                    <x-ui::button
                        color="white"
                        size="sm"
                        icon="arrow-back-up"
                        :href="route('vendors.purchases.refund', ['id' => $row->refund_id ?? 0, 'purchase_id' => $row->id])"
                    >
                        {{ __('Trả hàng nhập') }}
                    </x-ui::button>
                    @endcan
                @endif
            </div>
        </div>
        @endif
    </x-ui::card>
</div>

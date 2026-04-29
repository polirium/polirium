@php
    $refund = \Polirium\Modules\Vendor\Http\Model\Refund\Refund::with(['products.product', 'vendor', 'branch', 'purchase', 'userCreated'])->find($row->id);
    $canViewPrice = auth()->user()?->can('vendors.refunds.view-price');
    $statusColor = match ($refund->status ?? 'pending') {
        'completed', 'paid', 'success' => 'success',
        'pending', 'processing', 'temp' => 'warning',
        'cancelled', 'cancel' => 'danger',
        default => 'secondary',
    };
@endphp

<div x-data="{ localTab: 1 }">
    <x-ui::card>
        {{-- Header with code and status --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <p class="h3 mb-1"><b>{{ $refund->code }}</b></p>
                <p class="text-muted small mb-0">
                    {!! tabler_icon('calendar', ['class' => 'icon icon-sm me-1']) !!}
                    {{ core_format_date($refund->created_at) }}
                </p>
            </div>
            <span class="badge bg-{{ $statusColor }}-lt text-{{ $statusColor }} fs-6">
                {{ $refund->status_name ?? $refund->status }}
            </span>
        </div>

        <x-ui::tab>
            <x-slot name="header">
                <x-ui::tab.header @click="localTab = 1" :active="false" x-bind:class="{ 'active': localTab == 1 }" :label="trans('modules/vendor::purchase.detail')" />
                <x-ui::tab.header @click="localTab = 2" :active="false" x-bind:class="{ 'active': localTab == 2 }" :label="trans('modules/vendor::purchase.refund.name')" />
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
                                <div class="h4 mb-0">{{ $refund->vendor?->name ?? 'N/A' }}</div>
                                <div class="text-muted small mt-1">{{ $refund->branch?->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Purchase Reference --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="avatar bg-info-lt me-2">
                                        {!! tabler_icon('receipt', ['class' => 'text-info icon']) !!}
                                    </span>
                                    <div class="subheader">{{ __('modules/vendor::purchase.name') }}</div>
                                </div>
                                <div class="h4 mb-0">{{ $refund->purchase?->code ?? 'N/A' }}</div>
                                <div class="text-muted small mt-1">{{ $refund->branch?->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- User Created --}}
                    <div class="col-md-12 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="avatar bg-success-lt me-2">
                                        {!! tabler_icon('users', ['class' => 'text-success icon']) !!}
                                    </span>
                                    <div class="subheader">{{ __('modules/vendor::purchase.user_created') }}</div>
                                </div>
                                <div class="h5 mb-0">{{ $refund->userCreated?->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Note --}}
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card card-sm bg-muted-lt">
                            <div class="card-body py-2">
                                <div class="text-muted small mb-1">{{ trans('core/base::general.note') }}</div>
                                <div class="small">{{ $refund->note ?? __('modules/vendor::purchase.no_note') }}</div>
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
                            <th class="text-end">{{ __('modules/vendor::purchase.quantity') }}</th>
                            @if ($canViewPrice)
                                <th class="text-end">{{ __('modules/vendor::purchase.unit_price') }}</th>
                                <th class="text-end">{{ __('modules/vendor::purchase.discount') }}</th>
                                <th class="text-end">{{ __('modules/vendor::purchase.total_amount_column') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($refund->products as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge bg-muted-lt user-select-all">{{ $item->product?->code ?? 'N/A' }}</span></td>
                                <td><strong>{{ $item->product?->name ?? 'N/A' }}</strong></td>
                                <td class="text-end">{{ core_number_format($item->amount) }}</td>
                                @if ($canViewPrice)
                                    <td class="text-end">{{ core_number_format($item->price) }}</td>
                                    <td class="text-end">
                                        @if (($item->discount_value ?? 0) > 0)
                                            <span class="text-danger">-{{ core_number_format($item->discount_value) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end"><strong>{{ core_number_format($item->value) }}</strong></td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center">
                                    <span class="text-muted">
                                        {!! tabler_icon('box', ['class' => 'icon icon-md mb-2']) !!}
                                        <br>{{ __('modules/vendor::purchase.no_products') }}
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui::table>
            </x-ui::tab.item>
        </x-ui::tab>

        {{-- Action Buttons --}}
        @can('vendors.refunds.edit')
            <hr>
            <div class="btn-list mt-3">
                <x-ui::button
                              color="primary"
                              size="sm"
                              icon="pencil"
                              :href="route('vendors.purchases.refund', $id)">
                    {{ __('modules/vendor::purchase.edit') }}
                </x-ui::button>
            </div>
        @endcan
    </x-ui::card>
</div>

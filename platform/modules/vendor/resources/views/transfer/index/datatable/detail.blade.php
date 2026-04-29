@php
    $statusColor = match($row->status ?? 'pending') {
        'completed' => 'success',
        'pending', 'processing' => 'warning',
        'cancelled' => 'danger',
        default => 'secondary'
    };
@endphp

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
            {{ $row->status_name }}
        </span>
    </div>

    <x-ui::tab>
        <x-slot name="header">
            <x-ui::tab.header wire:click="$set('tab', 1)" :active="$tab === 1" :label="trans('modules/vendor::purchase.detail')" />
            <x-ui::tab.header wire:click="$set('tab', 2)" :active="$tab === 2" :label="trans('modules/vendor::purchase.products')" />
        </x-slot>

        <x-ui::tab.item :show="$tab === 1">
            {{-- Info Cards Grid --}}
            <div class="row g-3 mb-4">
                {{-- From Branch --}}
                <div class="col-md-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar bg-danger-lt me-2">
                                    {!! tabler_icon('arrow-up-right', ['class' => 'text-danger icon']) !!}
                                </span>
                                <div class="subheader">{{ __('modules/vendor::transfer.from_branch') }}</div>
                            </div>
                            <div class="h4 mb-0">{{ $row->fromBranch?->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- To Branch --}}
                <div class="col-md-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar bg-success-lt me-2">
                                    {!! tabler_icon('arrow-down-right', ['class' => 'text-success icon']) !!}
                                </span>
                                <div class="subheader">{{ __('modules/vendor::transfer.to_branch') }}</div>
                            </div>
                            <div class="h4 mb-0">{{ $row->toBranch?->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                @can('vendors.transfers.view-price')
                {{-- Value Info --}}
                <div class="col-md-12 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar bg-info-lt me-2">
                                    {!! tabler_icon('coins', ['class' => 'text-info icon']) !!}
                                </span>
                                <div class="subheader">{{ __('modules/vendor::transfer.value') }}</div>
                            </div>
                            <div class="h3 mb-0 text-info">
                                {{ core_number_format($row->value) }} đ
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
            </div>

            {{-- Additional Info --}}
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <x-ui::table class="table-sm table-borderless">
                        <tr>
                            <td class="text-muted" style="width: 40%;">{{ __('modules/vendor::transfer.user_created') }}</td>
                            <td><strong>{{ $row->userCreated?->name ?? '-' }}</strong></td>
                        </tr>
                    </x-ui::table>
                </div>
                <div class="col-md-6">
                    <div class="card card-sm bg-muted-lt">
                        <div class="card-body py-2">
                            <div class="text-muted small mb-1">{{ trans('core/base::general.note') }}</div>
                            <div class="small">{{ $row->note ?? __('modules/vendor::transfer.no_note') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui::tab.item>

        <x-ui::tab.item :show="$tab === 2">
            <x-ui::table striped class="card-table">
                <thead>
                    <tr>
                        <th class="w-1">{{ trans('core/base::general.id') }}</th>
                        <th>{{ trans('modules/product::product.code') }}</th>
                        <th>{{ trans('modules/product::product.name') }}</th>
                        <th class="text-end">{{ trans('modules/product::product.amount') }}</th>
                        @can('vendors.transfers.view-price')
                        <th class="text-end">{{ __('modules/vendor::purchase.unit_price') }}</th>
                        <th class="text-end">{{ __('modules/vendor::purchase.total_amount_column') }}</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($row->products as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td><span class="badge bg-muted-lt">{{ $item->product?->code ?? 'N/A' }}</span></td>
                            <td><strong>{{ $item->product?->name ?? 'N/A' }}</strong></td>
                            <td class="text-end">{{ core_number_format($item->amount) }}</td>
                            @can('vendors.transfers.view-price')
                            <td class="text-end">{{ core_number_format($item->price) }}</td>
                            <td class="text-end"><strong>{{ core_number_format($item->value) }}</strong></td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <span class="text-muted">
                                    {!! tabler_icon('box', ['class' => 'icon icon-md mb-2']) !!}
                                    <br>{{ __('modules/vendor::transfer.no_products') }}
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui::table>
        </x-ui::tab.item>
    </x-ui::tab>

    <hr>

    {{-- Action Buttons --}}
    <div class="btn-list">
        @can('vendors.transfers.edit')
        <x-ui::button
            color="primary"
            size="sm"
            icon="pencil"
            :href="route('vendors.transfers.transfer', ['id' => $id])"
        >
            {{ trans('modules/vendor::transfer.open') }}
        </x-ui::button>
        @endcan
    </div>
</x-ui::card>

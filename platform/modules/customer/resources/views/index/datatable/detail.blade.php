@php
    $groups = data_get($row, 'customerGroups', []);
    if (is_array($groups) || is_object($groups)) {
        $customerGroups = collect($groups)->pluck('name')->join(', ');
    } else {
        $customerGroups = '';
    }
    $customerGroups = $customerGroups ?: __('modules/customer::customer.no_group');
@endphp

<div>
    <x-ui::card>
        {{-- Header with code and basic info --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <p class="h3 mb-1"><b>{{ $row->code }}</b></p>
                <p class="text-muted small mb-0">
                    @if ($row->birthday)
                        {!! tabler_icon('calendar', ['class' => 'icon icon-sm me-1']) !!}
                        {{ core_format_date($row->birthday) }}
                    @else
                        <span class="text-muted">{{ __('modules/customer::customer.no_birthday') }}</span>
                    @endif
                </p>
            </div>
            @if ($customerGroups && $customerGroups !== __('modules/customer::customer.no_group'))
                <span class="badge bg-primary-lt text-primary">
                    {!! tabler_icon('users', ['class' => 'icon icon-sm me-1']) !!}
                    {{ $customerGroups }}
                </span>
            @endif
        </div>

        {{-- Info Cards Grid --}}
        <div class="row g-3 mb-4">
            {{-- Contact Info --}}
            <div class="col-md-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-primary-lt me-2">
                                {!! tabler_icon('user', ['class' => 'text-primary icon']) !!}
                            </span>
                            <div class="subheader">{{ __('modules/customer::customer.personal_info') }}</div>
                        </div>
                        <div class="h4 mb-0">{{ $row->name }}</div>
                        @if ($row->phone)
                            <div class="text-muted small mt-1">
                                {!! tabler_icon('phone', ['class' => 'icon icon-sm me-1']) !!}
                                {{ $row->phone }}
                            </div>
                        @endif
                        @if ($row->email)
                            <div class="text-muted small mt-1">
                                {!! tabler_icon('mail', ['class' => 'icon icon-sm me-1']) !!}
                                {{ $row->email }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Additional Phones --}}
            <div class="col-md-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-success-lt me-2">
                                {!! tabler_icon('address-book', ['class' => 'text-success icon']) !!}
                            </span>
                            <div class="subheader">{{ __('modules/customer::customer.contact') }}</div>
                        </div>
                        @if ($row->phone2)
                            <div class="h5 mb-1">{{ $row->phone2 }}</div>
                            <div class="text-muted small">{{ __('modules/customer::customer.phone2') }}</div>
                        @else
                            <div class="text-muted small">{{ __('modules/customer::customer.no_phone2') }}</div>
                        @endif
                        @if ($row->facebook)
                            <div class="text-muted small mt-1">
                                {!! tabler_icon('brand-facebook', ['class' => 'icon icon-sm me-1']) !!}
                                <a href="{{ $row->facebook }}" target="_blank" class="text-primary">Facebook</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tax & Address --}}
            <div class="col-md-12 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-info-lt me-2">
                                {!! tabler_icon('building', ['class' => 'text-info icon']) !!}
                            </span>
                            <div class="subheader">{{ __('modules/customer::customer.business_info') }}</div>
                        </div>
                        @if ($row->vat)
                            <div class="h5 mb-1">{{ $row->vat }}</div>
                            <div class="text-muted small">{{ __('modules/customer::customer.vat') }}</div>
                        @else
                            <div class="text-muted small">{{ __('modules/customer::customer.no_vat') }}</div>
                        @endif
                        @if ($row->address)
                            <div class="text-muted small mt-2">
                                {!! tabler_icon('map-pin', ['class' => 'icon icon-sm me-1']) !!}
                                {{ $row->address }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Info --}}
        @if ($row->note)
            <div class="row g-3">
                <div class="col-12">
                    <div class="card card-sm bg-muted-lt">
                        <div class="card-body py-2">
                            <div class="text-muted small mb-1">
                                {!! tabler_icon('note', ['class' => 'icon icon-sm me-1']) !!}
                                {{ __('modules/customer::customer.note') }}
                            </div>
                            <div class="small">{{ $row->note }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <hr>

        {{-- Action Buttons V2 - Màu sắc rõ ràng với Badge Labels --}}
        <div class="action-buttons-v2">
            <button
                    class="action-btn-v2 edit"
                    wire:click="$dispatch('show-modal-create-customer', { id: {{ $row->id }} })"
                    aria-label="{{ __('modules/customer::customer.edit') }}">
                {!! tabler_icon('pencil', ['class' => 'icon']) !!}
                <span class="badge-label">Sửa</span>
                <span class="full-label">{{ __('modules/customer::customer.edit') }}</span>
            </button>

            @can('customers.destroy')
                <button
                        type="button"
                        class="action-btn-v2 delete"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-confirm-delete-customer"
                        onclick="window.dispatchEvent(new CustomEvent('set-delete-customer-id', {detail: {id: {{ $row->id }}}}))"
                        aria-label="{{ __('modules/customer::customer.delete') }}">
                    {!! tabler_icon('trash', ['class' => 'icon']) !!}
                    <span class="badge-label">Xóa</span>
                    <span class="full-label">{{ __('modules/customer::customer.delete') }}</span>
                </button>
            @endcan
        </div>
    </x-ui::card>

    {{-- Invoice History --}}
    <x-ui::card class="mt-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('modules/customer::customer.purchase_history') ?? 'Lịch sử mua hàng' }}</h3>
        </div>
        <div class="card-body p-0">
            @livewire('modules/accounting::payment.datatable.payment-table', ['customerId' => $row->id, 'compactMode' => true], key('payment-table-' . $row->id))
        </div>
    </x-ui::card>
</div>

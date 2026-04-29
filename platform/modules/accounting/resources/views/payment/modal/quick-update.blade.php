@php
    use VigStudio\TablerIcons\TablerIcon;
@endphp

<x-ui::modal id="modal-quick-update" header="Chỉnh sửa thông tin">
    <div class="row row-cards">
        {{-- Kênh bán hàng --}}
        <div class="col-12">
            <label class="form-label">{{ trans('modules/accounting::accounting.sale_channel') }}</label>
            <div class="input-group">
                <select class="form-select @error('sale_channel_id') is-invalid @enderror" wire:model="sale_channel_id">
                    <option value="">{{ trans('modules/accounting::accounting.select_sale_channel') }}</option>
                    @foreach ($saleChannels as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-icon btn-ghost-primary" wire:click="$dispatch('show-modal-create-sale-channel')" title="{{ trans('modules/accounting::accounting.add_sale_channel') }}">
                    {!! tabler_icon('plus') !!}
                </button>
            </div>
            @error('sale_channel_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Khách hàng --}}
        <div class="col-12 mb-3">
            <label class="form-label">{{ trans('modules/accounting::accounting.customer') }}</label>
            @if ($customer_id)
                <div class="form-control d-flex align-items-center justify-content-between h-auto p-2">
                    <div class="d-flex align-items-start gap-2">
                        <span class="avatar avatar-xs bg-primary-lt mt-1 flex-shrink-0 rounded">
                            {{ substr($this->customer?->name ?? 'C', 0, 1) }}
                        </span>
                        <div>
                            <div class="font-weight-medium">{{ $this->customer?->name }}</div>
                            <div class="text-muted small">{{ $this->customer?->phone }}</div>
                            @if ($this->customer?->address)
                                <div class="text-muted small" style="white-space: normal;">{{ $this->customer?->address }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="btn-list">
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-warning"
                                wire:click="$dispatch('show-modal-create-customer', { id: {{ $customer_id }} })">
                            {!! tabler_icon('edit') !!}
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-danger"
                                wire:click="removeCustomer">
                            {!! tabler_icon('x') !!}
                        </button>
                    </div>
                </div>
            @else
                <div class="d-flex align-items-end gap-1">
                    <div class="flex-grow-1">
                        <x-form::autocomplete
                                              wire:model.live.debounce.300ms="search_customer"
                                              placeholder="{{ trans('modules/product::product.search_customer_placeholder') }}"
                                              class="mb-0">
                            @if (isset_value($search_customer) && $this->customers?->count() > 0)
                                @foreach ($this->customers as $item)
                                    <x-form::autocomplete.item wire:click="selectCustomer({{ $item->id }})">
                                        <div class="d-flex justify-content-between">
                                            <span><b>{{ $item->name }}</b></span>
                                            <span class="text-primary">{{ $item->phone }}</span>
                                        </div>
                                        <div class="small text-muted">{{ $item->code }}</div>
                                    </x-form::autocomplete.item>
                                @endforeach
                            @elseif (isset_value($search_customer) && $this->customers?->count() == 0)
                                <x-form::autocomplete.item
                                                           wire:click="$dispatch('show-modal-create-customer', { id: 0, name: '{{ addslashes($search_customer) }}' })"
                                                           class="cursor-pointer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">{{ trans('modules/product::product.customer_not_found') }}</span>
                                        <span class="badge bg-success-lt">
                                            {!! tabler_icon('plus', ['class' => 'icon-sm']) !!} {{ trans('core/base::general.add_new') }}
                                        </span>
                                    </div>
                                </x-form::autocomplete.item>
                            @endif
                        </x-form::autocomplete>
                    </div>
                    <button type="button"
                            class="btn btn-icon btn-ghost-success"
                            wire:click="$dispatch('show-modal-create-customer', { id: 0 })"
                            title="{{ trans('modules/accounting::accounting.add_customer') }}">
                        {!! tabler_icon('plus') !!}
                    </button>
                </div>
            @endif
        </div>

        {{-- Đơn vị vận chuyển --}}
        <div class="col-12 col-md-6">
            <label class="form-label">{{ trans('modules/accounting::accounting.transport_partner') }}</label>
            <div class="input-group">
                <select class="form-select @error('partner_delivery_id') is-invalid @enderror" wire:model="partner_delivery_id">
                    <option value="">{{ trans('modules/accounting::accounting.select_partner') }}</option>
                    @foreach ($deliveryPartners as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-icon btn-ghost-primary" wire:click="$dispatch('show-modal-create-partner-delivery')" title="{{ trans('modules/accounting::accounting.add_delivery_partner') }}">
                    {!! tabler_icon('plus') !!}
                </button>
            </div>
            @error('partner_delivery_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Mã vận đơn --}}
        <div class="col-12 col-md-6">
            <label class="form-label">{{ trans('modules/accounting::accounting.delivery_code') }}</label>
            <input type="text" class="form-control @error('delivery_code') is-invalid @enderror" wire:model="delivery_code" placeholder="Nhập mã vận đơn">
            @error('delivery_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Phương thức thanh toán --}}
        <div class="col-12 col-md-6 mb-3">
            <label class="form-label">{{ trans('modules/accounting::accounting.payment_method') }}</label>
            <div class="input-group">
                <select class="form-select @error('payment_method_code') is-invalid @enderror" wire:model="payment_method_code">
                    <option value="">{{ trans('modules/accounting::accounting.select_method_placeholder') }}</option>
                    @foreach ($paymentMethods as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-icon btn-ghost-primary" wire:click="$dispatch('modal-create-payment-method')" title="{{ trans('modules/accounting::accounting.add_payment_method') }}">
                    {!! tabler_icon('plus') !!}
                </button>
            </div>
            @error('payment_method_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('modules/accounting::accounting.close') }}</button>
        @can('accountings.edit')
            <button type="button" class="btn btn-primary ms-auto" wire:click="save">
                {!! TablerIcon::render('check', ['class' => 'icon me-1']) !!}
                {{ trans('modules/accounting::accounting.update') }}
            </button>
        @endcan
    </x-slot>
</x-ui::modal>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-modal-quick-update', () => {
            const el = document.getElementById('modal-quick-update');
            if (el) {
                const myModal = new(window.bootstrap || bootstrap).Modal(el);
                myModal.show();
            } else {
                console.error('Modal element not found');
            }
        });
        Livewire.on('hide-modal-quick-update', () => {
            const el = document.getElementById('modal-quick-update');
            if (el) {
                const modal = (window.bootstrap || bootstrap).Modal.getInstance(el);
                if (modal) {
                    modal.hide();
                }
            }
        });
    });
</script>

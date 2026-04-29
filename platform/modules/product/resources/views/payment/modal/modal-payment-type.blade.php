<div class="professional-modal-wrapper">
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-payment-type-{{ $tab_selected }}" :header="__('modules/product::product.payment_modal.multi_payment')" class="modal-md professional-modal-wrapper">
            <x-ui::errors/>

            <div class="professional-modal-body p-0">
                {{-- Payment Amount Input --}}
                <div class="bg-light p-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label text-muted mb-0 small text-uppercase fw-bold">{{ __('modules/product::product.payment_modal.amount') }}</label>
                        <span class="badge bg-blue-lt">{{ core_number_format($value) }}</span>
                    </div>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            {!! tabler_icon('currency-dong') !!}
                        </span>
                        <input type="text"
                            class="form-control form-control-lg fw-bold fs-2 text-start"
                            x-data="{
                                localValue: '0',
                                formatMoney(val) {
                                    if (!val) return '0';
                                    let numberPattern = val.toString().replace(/\D/g, '');
                                    return new Intl.NumberFormat('vi-VN').format(parseInt(numberPattern) || 0);
                                }
                            }"
                            x-init="
                                localValue = formatMoney($wire.value);
                                $watch('$wire.value', value => {
                                    localValue = formatMoney(value);
                                });
                            "
                            x-model="localValue"
                            @input="localValue = formatMoney($event.target.value)"
                            @blur="$wire.set('value', parseInt(localValue.replace(/\D/g, '')) || 0)"
                            placeholder="0"
                        >
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('value', 50000)">50,000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('value', 100000)">100,000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('value', 200000)">200,000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('value', 500000)">500,000</button>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-sm btn-ghost-primary" wire:click="$set('value', {{ $fixed_value - collect($payment_methods_list)->sum('value') }})">
                                {{ trans('modules/product::product.remaining_all') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Payment Methods Grid --}}
                <div class="p-3">
                    <label class="form-label text-muted mb-3 small text-uppercase fw-bold">{{ __('Chọn phương thức') }}</label>
                    <div class="row g-2">
                        @foreach($payment_methods as $method)
                            @php
                                $icon = match($method['code']) {
                                    'cash' => 'cash',
                                    'bank' => 'building-bank',
                                    'card' => 'credit-card',
                                    'cod' => 'truck-delivery',
                                    'other' => 'dots',
                                    'momo' => 'wallet',
                                    'vnpay' => 'qrcode',
                                    default => 'cash'
                                };
                                $color = match($method['code']) {
                                    'cash' => 'text-green',
                                    'bank' => 'text-blue',
                                    'card' => 'text-purple',
                                    'cod' => 'text-orange',
                                    'other' => 'text-secondary',
                                    'momo' => 'text-pink',
                                    'vnpay' => 'text-red',
                                    default => 'text-muted'
                                };
                                $bg = match($method['code']) {
                                    'cash' => 'bg-green-lt',
                                    'bank' => 'bg-blue-lt',
                                    'card' => 'bg-purple-lt',
                                    'cod' => 'bg-orange-lt',
                                    'other' => 'bg-secondary-lt',
                                    'momo' => 'bg-pink-lt',
                                    'vnpay' => 'bg-red-lt',
                                    default => 'bg-secondary-lt'
                                };
                            @endphp
                            <div class="col-4">
                                <button type="button"
                                    class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 border-dashed hover-shadow-sm transition-all"
                                    wire:click="selectMethod('{{ $method['code'] }}')"
                                    style="min-height: 90px;"
                                >
                                    <div class="{{ $bg }} rounded-circle p-2 mb-2">
                                        {!! tabler_icon($icon, ['class' => 'icon icon-md ' . $color]) !!}
                                    </div>
                                    <span class="fw-medium small">{{ $method['name'] }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Payment Summary List --}}
                @if(count($payment_methods_list) > 0)
                    <div class="border-top">
                        <div class="p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/product::product.paid') }}</span>
                                <span class="fw-bold text-success">{{ core_number_format(collect($payment_methods_list)->sum('value')) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small text-uppercase fw-bold">{{ __('Còn lại') }}</span>
                                <span class="fw-bold text-danger">{{ core_number_format($fixed_value - collect($payment_methods_list)->sum('value')) }}</span>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ((array)$payment_methods_list as $key => $item)
                                <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $icon = match($item['method']) {
                                                'cash' => 'cash',
                                                'bank' => 'building-bank',
                                                'card' => 'credit-card',
                                                'cod' => 'truck-delivery',
                                                'other' => 'dots',
                                                'momo' => 'wallet',
                                                'vnpay' => 'qrcode',
                                                default => 'cash'
                                            };
                                            $color = match($item['method']) {
                                                'cash' => 'text-green',
                                                'bank' => 'text-blue',
                                                'card' => 'text-purple',
                                                'cod' => 'text-orange',
                                                'other' => 'text-secondary',
                                                'momo' => 'text-pink',
                                                'vnpay' => 'text-red',
                                                default => 'text-muted'
                                            };
                                        @endphp
                                        {!! tabler_icon($icon, ['class' => 'icon icon-sm ' . $color]) !!}
                                        <span class="fw-medium">{{ $item['label'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold">{{ core_number_format($item['value']) }}</span>
                                        <button type="button" class="btn btn-icon btn-ghost-danger btn-sm" wire:click="removeMethod('{{ $key }}')">
                                            {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <div class="professional-modal-footer bg-white border-top p-3 d-flex justify-content-between w-100">
                    <div class="d-flex align-items-center gap-2">
                         <span class="text-muted small">{{ trans('modules/product::product.total_amount_label') }}</span>
                         <span class="h3 mb-0 text-primary">{{ core_number_format($fixed_value) }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                            {{ __('core/base::general.close') }}
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            {!! tabler_icon('check', ['class' => 'icon']) !!}
                            {{ trans('modules/product::product.confirm') }}
                        </button>
                    </div>
                </div>
            </x-slot>
        </x-ui::modal>
    </form>
</div>

<div class="payment-view">
    {{-- Summary Section --}}
    <div class="payment-view__summary-section">
        {{-- Total Items Row --}}
        <div class="payment-view__summary-row">
            <span class="payment-view__amount-label">{{ __('modules/product::payment.total_amount') }}</span>
            <span class="badge bg-indigo text-indigo-fg badge-pill">{{ collect($products)->sum('amount') }}</span>
        </div>

        {{-- Total Value Row --}}
        <div class="payment-view__summary-row">
            <span class="payment-view__amount-label">{{ __('modules/product::payment.total_value') }}</span>
            <span class="payment-view__amount-value">{{ core_number_format(collect($products)->sum('total')) }}</span>
        </div>

        {{-- Discount Row --}}
        <div class="payment-view__summary-row"
            x-data="{
                open: false,
                localType: '{{ $payment['discount_type'] ?? 'number' }}',
                localValue: {{ (int)($payment['discount_value'] ?? 0) }},
                apply() {
                    $wire.call('applyDiscount', this.localType, this.localValue);
                    this.open = false;
                }
            }"
            @click.outside="open = false"
        >
            <span class="payment-view__amount-label">{{ __('modules/product::payment.discount') }}</span>
            <div class="position-relative">
                <button
                    type="button"
                    class="payment-view__amount-value cursor-pointer border-0 bg-transparent"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-haspopup="true"
                    role="combobox"
                    :aria-label="@js(__('modules/product::payment.discount')) + ' selector'"
                >
                    <template x-if="localType === 'percent'">
                        <span x-text="localValue + ' %'"></span>
                    </template>
                    <template x-if="localType !== 'percent'">
                        <span x-text="localValue.toLocaleString('vi-VN') + ' VNĐ'"></span>
                    </template>
                    <i class="ti ti-chevron-down ms-1" :class="{ 'rotate': open }"></i>
                </button>

                {{-- Discount Dropdown --}}
                <div
                    class="dropdown-menu show payment-view__discount-dropdown"
                    style="right: 0; left: auto; min-width: 240px;"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    role="menu"
                    @click.stop
                >
                    <div class="p-3">
                        {{-- Type Toggle --}}
                        <div class="btn-group w-100 mb-2" role="group">
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="localType === 'percent' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click.stop="localType = 'percent'; localValue = 0"
                                :aria-pressed="localType === 'percent'"
                            >
                                {!! tabler_icon('percentage', ['class' => 'icon']) !!} %
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="localType === 'number' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click.stop="localType = 'number'; localValue = 0"
                                :aria-pressed="localType === 'number'"
                            >
                                {!! tabler_icon('currency-dong', ['class' => 'icon']) !!} VNĐ
                            </button>
                        </div>

                        {{-- Input --}}
                        <div class="input-group mb-2">
                            <input
                                type="number"
                                x-show="localType === 'percent'"
                                class="form-control text-end"
                                x-model.number="localValue"
                                min="0"
                                max="100"
                                step="1"
                                placeholder="0"
                                @click.stop
                                @keydown.enter="apply()"
                                aria-label="{{ __('modules/product::payment.discount_percent') }}"
                            />
                            <input
                                type="text"
                                x-show="localType === 'number'"
                                class="form-control text-end"
                                :value="localValue.toLocaleString('vi-VN')"
                                @click.stop
                                @input="localValue = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0; $nextTick(() => { $event.target.value = localValue.toLocaleString('vi-VN'); })"
                                placeholder="0"
                                @keydown.enter="apply()"
                                aria-label="{{ __('modules/product::payment.discount_amount') }}"
                            />
                            <span class="input-group-text" x-text="localType === 'percent' ? '%' : 'VNĐ'"></span>
                        </div>

                        {{-- Apply Button --}}
                        <button
                            type="button"
                            class="btn btn-success w-100"
                            @click.stop="apply()"
                        >
                            {!! tabler_icon('check', ['class' => 'icon']) !!} {{ __('modules/product::payment.apply') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer Need Pay Row --}}
        <div class="payment-view__summary-row">
            <span class="payment-view__amount-label">{{ __('modules/product::payment.customer_need_pay') }}</span>
            <span class="payment-view__amount-value payment-view__total-value">{{ core_number_format($total_payment) }}</span>
        </div>
    </div>
</div>

{{-- Payment Input Section with Alpine.js --}}
<div
    class="payment-view"
    x-data="{
        showPopup: false,
        totalPayment: @entangle('total_payment'),
        currentValue: @entangle('payment.value_payment'),

        getPaymentInput() {
            return this.$refs.paymentInputWrapper?.querySelector('input');
        },

        getSuggestedAmounts() {
            const total = this.totalPayment;
            if (total <= 0) return [];

            const suggestions = [];
            const roundAmounts = [50000, 100000, 200000, 500000, 1000000, 2000000, 5000000, 10000000];

            for (const round of roundAmounts) {
                const rounded = Math.ceil(total / round) * round;
                if (rounded > total && !suggestions.includes(rounded)) {
                    suggestions.push(rounded);
                }
                if (suggestions.length >= 4) break;
            }

            return suggestions;
        },

        formatNumber(num) {
            return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        selectAmount(amount) {
            this.currentValue = amount;
            $wire.set('payment.value_payment', amount);
            const input = this.getPaymentInput();
            if (input && typeof Inputmask !== 'undefined' && typeof $ !== 'undefined') {
                $(input).inputmask('setvalue', amount);
            }
            this.showPopup = false;
        },

        addAmount(amount) {
            const newValue = (parseInt(this.currentValue) || 0) + amount;
            this.currentValue = newValue;
            $wire.set('payment.value_payment', newValue);
            const input = this.getPaymentInput();
            if (input && typeof Inputmask !== 'undefined' && typeof $ !== 'undefined') {
                $(input).inputmask('setvalue', newValue);
            }
        },

        clearAmount() {
            this.currentValue = 0;
            $wire.set('payment.value_payment', 0);
            const input = this.getPaymentInput();
            if (input && typeof Inputmask !== 'undefined' && typeof $ !== 'undefined') {
                $(input).inputmask('setvalue', 0);
            }
        },

        get changeAmount() {
            const change = this.currentValue - this.totalPayment;
            return change > 0 ? change : 0;
        },

        get shouldShowChange() {
            return (parseInt(this.currentValue) || 0) > (parseInt(this.totalPayment) || 0) && (parseInt(this.totalPayment) || 0) > 0;
        }
    }"
    @click.away="showPopup = false"
    @update-payment-value-{{ $tab_selected }}.window="const input = getPaymentInput(); if (input && typeof Inputmask !== 'undefined' && typeof $ !== 'undefined') { $(input).inputmask('setvalue', $event.detail.value); }"
>
    {{-- Customer Payment Row --}}
    <div class="payment-view__summary-section">
        <div class="payment-view__summary-row">
            <span class="payment-view__amount-label">{{ __('modules/product::payment.customer_payment') }}</span>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                {{-- Payment Type Button --}}
                @if ($total_payment)
                    <button
                        type="button"
                        class="btn btn-outline-default btn-icon btn-pill"
                        wire:click="$dispatch('show-modal-payment-type-{{ $tab_selected }}', {value: {{ $total_payment }}, tab_selected: '{{ $tab_selected }}', payment_methods: @js($payment['type_payment'] ?? [])})"
                        title="{{ __('modules/product::payment.payment_methods') }}"
                        aria-label="{{ __('modules/product::payment.payment_methods') }}"
                    >
                        {!! tabler_icon('credit-card', ['class' => 'icon']) !!}
                    </button>
                @endif

                {{-- Payment Input Wrapper --}}
                <div class="position-relative" x-ref="paymentInputWrapper">
                    <x-form::currency
                        wire:model.live="payment.value_payment"
                        class="text-end"
                        @focus="showPopup = true"
                        @click="showPopup = true"
                        aria-label="{{ __('modules/product::payment.customer_payment') }}"
                    />

                    {{-- Payment Popup with Suggestions and Numpad --}}
                    <div
                        x-show="showPopup"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="payment-view__payment-popup"
                        role="dialog"
                        aria-modal="false"
                        @click.stop
                    >
                        <div class="payment-view__popup-header">
                            <h3 class="payment-view__popup-title">{{ __('modules/product::payment.enter_amount') }}</h3>
                            <button
                                type="button"
                                class="payment-view__popup-close"
                                @click="showPopup = false"
                                aria-label="{{ __('core/base::general.close') }}"
                            >
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <div class="payment-view__popup-body">
                            {{-- Suggested Amounts --}}
                            <template x-if="getSuggestedAmounts().length > 0">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">{{ __('modules/product::payment.suggestions') }}</small>
                                    <div class="payment-view__quick-amounts">
                                        <template x-for="amount in getSuggestedAmounts()" :key="amount">
                                            <button
                                                type="button"
                                                class="payment-view__quick-amount-btn"
                                                @click="selectAmount(amount)"
                                                x-text="formatNumber(amount)"
                                            ></button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Add Money Numpad --}}
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">{{ __('modules/product::payment.add_money') }}</small>
                                <div class="payment-view__numpad">
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(10000)">+10k</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(20000)">+20k</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(50000)">+50k</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(100000)">+100k</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(200000)">+200k</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(500000)">+500k</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(1000000)">+1M</button>
                                    <button type="button" class="payment-view__numpad-btn" @click="addAmount(2000000)">+2M</button>
                                    <button type="button" class="payment-view__numpad-btn payment-view__numpad-btn--clear" @click="clearAmount()">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Exact Amount Button --}}
                            <button
                                type="button"
                                class="btn btn-success w-100"
                                @click="selectAmount(totalPayment)"
                            >
                                {!! tabler_icon('check', ['class' => 'icon']) !!} {{ __('modules/product::payment.exact_amount') }}
                                (<span x-text="formatNumber(totalPayment)"></span>)
                            </button>
                        </div>
                    </div>
                </div>

                @if (count((array) ($payment['type_payment'] ?? [])) > 1)
                    <span class="text-muted small">{{ $methods_payment }}</span>
                @endif
            </div>
        </div>

        {{-- Change Amount Row --}}
        <template x-if="shouldShowChange">
            <div class="payment-view__summary-row">
                <span class="payment-view__amount-label text-success">{{ __('modules/product::payment.change') }}</span>
                <span class="payment-view__amount-value text-success" x-text="formatNumber(changeAmount)"></span>
            </div>
        </template>
    </div>
</div>

@php
    // Helper to check if a method is selected in the nested array format
    $isMethodSelected = function($method, $typePayment) {
        if (!is_array($typePayment) || empty($typePayment)) {
            return $method === 'cash'; // default to cash
        }
        $first = $typePayment[0] ?? null;
        // Support both formats: ['cash'] or [['method' => 'cash']]
        if (is_string($first)) {
            return in_array($method, $typePayment);
        }
        return ($first['method'] ?? '') === $method;
    };
@endphp

{{-- Payment Method Selection (Compact Scrollable) --}}
@if (count((array) ($payment['type_payment'] ?? [])) <= 1)
    <div class="mt-3 overflow-hidden">
        <div class="row g-1 row-cols-4">
            @foreach($payment_methods as $method)
                @php
                    $icon = match($method['code']) {
                        'cash' => 'coin',
                        'bank' => 'building-bank',
                        'card' => 'credit-card',
                        'cod' => 'truck-delivery',
                        'other' => 'dots',
                        'momo' => 'wallet',
                        'vnpay' => 'qrcode',
                        default => 'wallet'
                    };
                @endphp
                <div class="col">
                    <button
                        type="button"
                        @class([
                            'btn btn-sm w-100 d-flex align-items-center justify-content-center gap-1 px-1',
                            'btn-primary' => $isMethodSelected($method['code'], $payment['type_payment'] ?? []),
                            'btn-outline-primary' => !$isMethodSelected($method['code'], $payment['type_payment'] ?? []),
                        ])
                        wire:click="setPaymentMethod('{{ $method['code'] }}')"
                    >
                        {!! tabler_icon($icon, ['class' => 'icon-sm']) !!}
                        <span class="text-truncate small">{{ $method['name'] }}</span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endif

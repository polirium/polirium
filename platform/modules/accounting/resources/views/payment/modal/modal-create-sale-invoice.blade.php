<div>
    <form wire:submit.prevent="save">
        <x-ui::modal
                     id="modal-create-sale-invoice"
                     :header="!empty($payment['id']) ? trans('modules/accounting::accounting.update_invoice') : __('modules/accounting::accounting.create_invoice')"
                     class="modal-fullscreen">
            <x-ui::errors />

            <div class="row g-4">
                {{-- Left Column: Product Selection & List --}}
                <div class="col-lg-7">
                    {{-- Product Search --}}
                    @if (empty($payment['id']))
                        <div class="mb-3">
                            <label class="form-label d-flex align-items-center gap-2">
                                {!! tabler_icon('search', ['class' => 'icon icon-sm']) !!}
                                <span>{{ __('modules/product::product.search_product') }}</span>
                            </label>
                            <div class="position-relative" x-data="{ show: false }">
                                <input
                                       type="text"
                                       class="form-control"
                                       wire:model.live.debounce.300ms="search.product"
                                       @focus="show = true"
                                       @input="show = true"
                                       placeholder="{{ __('modules/product::product.search_product_placeholder') }}">
                                @if (isset_value($search['product']) && $this->productsSearched->count() > 0)
                                    <div
                                         class="list-group list-group-flush position-absolute w-100 rounded-bottom mt-1 border bg-white shadow-lg shadow-sm"
                                         style="z-index: 1050; max-height: 250px; overflow-y: auto; border-top: none;"
                                         x-show="show"
                                         @click.outside="show = false"
                                         x-cloak>
                                        @foreach ($this->productsSearched as $item)
                                            <button
                                                    type="button"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                                                    wire:click="addProduct({{ $item->id }})">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar avatar-sm bg-blue-lt">
                                                        {!! tabler_icon('box', ['class' => 'icon']) !!}
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                                        <div class="small text-muted mb-0">{{ $item->code }}</div>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-primary">{{ core_number_format($item->price) }}</div>
                                                    <div class="small text-muted">{{ $item->unit }}</div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Products List --}}
                    <div class="card bg-light border-0">
                        <div class="card-body p-0">
                            @if (count($products) > 0)
                                <div class="table-responsive">
                                    <table class="table-vcenter table-nowrap card-table table">
                                        <thead>
                                            <tr>
                                                <th class="w-1">#</th>
                                                <th>{{ __('modules/product::product.name') }}</th>
                                                <th class="text-center" style="width: 130px;">{{ __('modules/product::product.quantity') }}</th>
                                                <th class="text-end" style="width: 140px;">{{ __('modules/product::product.unit_price') }}</th>
                                                <th class="text-end" style="width: 120px;">{{ __('modules/product::product.total') }}</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($products as $productId => $item)
                                                <tr wire:key="product-row-{{ $productId }}">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td class="text-wrap">
                                                        <div class="fw-medium">{{ $item['product']['name'] }}</div>
                                                        @if ($item['product']['code'] ?? false)
                                                            <div class="small text-muted">{{ $item['product']['code'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm flex-nowrap" style="min-width: 90px;">
                                                            @if (empty($payment['id']))
                                                                <button
                                                                        type="button"
                                                                        class="btn btn-outline-secondary px-2"
                                                                        wire:click="$set('products.{{ $productId }}.amount', {{ max(1, $item['amount'] - 1) }})">
                                                                    {!! tabler_icon('minus', ['class' => 'icon icon-sm']) !!}
                                                                </button>
                                                            @endif
                                                            <input
                                                                   type="text"
                                                                   class="form-control px-1 text-center"
                                                                   wire:model.live="products.{{ $productId }}.amount"
                                                                   style="min-width: 40px;"
                                                                   {{ !empty($payment['id']) ? 'readonly' : '' }}>
                                                            @if (empty($payment['id']))
                                                                <button
                                                                        type="button"
                                                                        class="btn btn-outline-secondary px-2"
                                                                        wire:click="$set('products.{{ $productId }}.amount', {{ $item['amount'] + 1 }})">
                                                                    {!! tabler_icon('plus', ['class' => 'icon icon-sm']) !!}
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-end"
                                                        @if (empty($payment['id'])) x-data="{
                                                                open: false,
                                                                localDiscountType: '{{ $item['discount_type'] ?? 'number' }}',
                                                                localDiscountValue: {{ (int) ($item['discount_value'] ?? 0) }},
                                                                originalPrice: {{ (int) $item['value'] }},
                                                                salePrice: 0,
                                                                init() {
                                                                    this.updateSalePrice();
                                                                    this.$watch('localDiscountType', () => this.updateSalePrice());
                                                                    this.$watch('localDiscountValue', () => this.updateSalePrice());
                                                                    // Watch for Livewire updates to unit price
                                                                    this.$watch('originalPrice', () => this.updateSalePrice());
                                                                },
                                                                updateSalePrice() {
                                                                    if (this.localDiscountType === 'percent') {
                                                                        this.salePrice = this.originalPrice - (this.originalPrice * this.localDiscountValue / 100);
                                                                    } else {
                                                                        this.salePrice = this.originalPrice - this.localDiscountValue;
                                                                    }
                                                                    if (this.salePrice < 0) this.salePrice = 0;
                                                                },
                                                                updateDiscountFromSalePrice(newSalePrice) {
                                                                    if (newSalePrice > this.originalPrice) newSalePrice = this.originalPrice;
                                                                    if (newSalePrice < 0) newSalePrice = 0;
                                                                    this.localDiscountType = 'number';
                                                                    this.localDiscountValue = this.originalPrice - newSalePrice;
                                                                },
                                                                applyDiscount() {
                                                                    $wire.set('products.{{ $productId }}.value', this.originalPrice);
                                                                    $wire.set('products.{{ $productId }}.discount_type', this.localDiscountType);
                                                                    $wire.set('products.{{ $productId }}.discount_value', this.localDiscountValue);
                                                                    this.open = false;
                                                                }
                                                            }"
                                                            @click.outside="open = false" @endif>
                                                        <div class="{{ empty($payment['id']) ? 'cursor-pointer' : '' }}" @if (empty($payment['id'])) @click="open = !open" @endif>
                                                            <div class="font-weight-bold">{{ core_number_format((int) $item['value']) }}</div>
                                                            @if ((int) $item['discount_value'] > 0)
                                                                <div class="text-danger small">
                                                                    -{{ core_number_format((int) $item['discount_value']) }} {{ $item['discount_type'] === 'percent' ? '%' : 'VNĐ' }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        @if (empty($payment['id']))
                                                            <div class="dropdown-menu show border-0 p-3 shadow-lg" x-show="open" x-cloak style="min-width: 280px; position: absolute; z-index: 1050; right: 0; border-radius: 12px;">
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small mb-1">{{ trans('modules/accounting::accounting.unit_price') }}</label>
                                                                    <div class="input-group">
                                                                        <input type="text"
                                                                               class="form-control text-end"
                                                                               :value="originalPrice.toLocaleString('vi-VN')"
                                                                               @input="originalPrice = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0; $nextTick(() => { $event.target.value = originalPrice.toLocaleString('vi-VN'); });">
                                                                        <span class="input-group-text">VNĐ</span>
                                                                    </div>
                                                                </div>

                                                                <div class="border-bottom border-light mb-3 pb-3">
                                                                    <label class="form-label text-muted small mb-1">{{ trans('modules/accounting::accounting.selling_price') }}</label>
                                                                    <div class="input-group">
                                                                        <input type="text"
                                                                               class="form-control fw-bold text-primary text-end"
                                                                               :value="salePrice.toLocaleString('vi-VN')"
                                                                               @input="
                                                                                let val = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0;
                                                                                updateDiscountFromSalePrice(val);
                                                                                $nextTick(() => { $event.target.value = salePrice.toLocaleString('vi-VN'); });
                                                                            "
                                                                               @keydown.enter.prevent="applyDiscount()">
                                                                        <span class="input-group-text bg-primary-lt text-primary fw-bold">VNĐ</span>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small mb-1">{{ trans('modules/product::product.discount') }}</label>
                                                                    <div class="btn-group w-100 mb-2">
                                                                        <button type="button"
                                                                                @click.stop="localDiscountType = 'percent'; localDiscountValue = 0"
                                                                                :class="localDiscountType === 'percent' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary'">
                                                                            {{ tabler_icon('percentage') }} %
                                                                        </button>
                                                                        <button type="button"
                                                                                @click.stop="localDiscountType = 'number'; localDiscountValue = 0"
                                                                                :class="localDiscountType === 'number' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary'">
                                                                            {{ tabler_icon('currency-dong') }} VNĐ
                                                                        </button>
                                                                    </div>

                                                                    <div class="input-group mb-3">
                                                                        <input type="number"
                                                                               x-show="localDiscountType === 'percent'"
                                                                               class="form-control text-end"
                                                                               x-model.number="localDiscountValue"
                                                                               min="0" max="100" step="1"
                                                                               @click.stop
                                                                               @keydown.enter="applyDiscount()">
                                                                        <input type="text"
                                                                               x-show="localDiscountType === 'number'"
                                                                               class="form-control text-end"
                                                                               :value="localDiscountValue.toLocaleString('vi-VN')"
                                                                               @click.stop
                                                                               @input="localDiscountValue = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0; $nextTick(() => { $event.target.value = localDiscountValue.toLocaleString('vi-VN'); });"
                                                                               @keydown.enter="applyDiscount()">
                                                                        <span class="input-group-text" x-text="localDiscountType === 'percent' ? '%' : 'VNĐ'"></span>
                                                                    </div>

                                                                    <button type="button" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2 py-2" @click.stop="applyDiscount()">
                                                                        {!! tabler_icon('check') !!}
                                                                        {{ trans('modules/product::product.apply') }}
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="fw-bold text-end">
                                                        {{ core_number_format((int) $item['total']) }}
                                                    </td>
                                                    <td>
                                                        @if (empty($payment['id']))
                                                            <button
                                                                    type="button"
                                                                    class="btn btn-icon btn-sm btn-ghost-danger"
                                                                    wire:click="removeProduct({{ $productId }})">
                                                                {!! tabler_icon('trash', ['class' => 'icon icon-sm']) !!}
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted py-5 text-center">
                                    <div class="mb-3">
                                        {!! tabler_icon('shopping-cart', ['class' => 'icon icon-lg opacity-25', 'style' => 'width: 48px; height: 48px;']) !!}
                                    </div>
                                    <p class="mb-0">{{ __('modules/product::product.cart_empty') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right Column: Payment Info --}}
                <div class="col-lg-5">
                    <div class="card border-0">
                        <div class="card-body">
                            {{-- Customer Selection --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    {!! tabler_icon('user', ['class' => 'icon icon-sm me-1']) !!}
                                    {{ __('modules/accounting::accounting.customer') }}
                                </label>
                                @if ($payment['customer_id'] ?? null)
                                    <div class="form-control d-flex align-items-center justify-content-between p-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs bg-primary-lt rounded">
                                                {{ substr($this->customer?->name ?? 'C', 0, 1) }}
                                            </span>
                                            <div>
                                                <div class="fw-medium">{{ $this->customer?->name }}</div>
                                                <div class="small text-muted">{{ $this->customer?->phone }}</div>
                                            </div>
                                        </div>
                                        <button
                                                type="button"
                                                class="btn btn-icon btn-sm btn-ghost-danger"
                                                wire:click="removeCustomer">
                                            {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                                        </button>
                                    </div>
                                @else
                                    <div class="d-flex align-items-end gap-1" x-data="{ show: false }">
                                        <div class="flex-grow-1 position-relative">
                                            <input
                                                   type="text"
                                                   class="form-control"
                                                   wire:model.live.debounce.300ms="search.customer"
                                                   @focus="show = true"
                                                   @input="show = true"
                                                   placeholder="{{ __('modules/product::product.search_customer_placeholder') }}">
                                            @if (isset_value($search['customer']) && $this->customers->count() > 0)
                                                <div
                                                     class="list-group list-group-flush position-absolute w-100 mt-1 rounded border bg-white shadow-sm"
                                                     style="z-index: 1050; max-height: 200px; overflow-y: auto;"
                                                     x-show="show"
                                                     @click.outside="show = false"
                                                     x-cloak>
                                                    @foreach ($this->customers as $item)
                                                        <button
                                                                type="button"
                                                                class="list-group-item list-group-item-action"
                                                                wire:click="selectCustomer({{ $item->id }})">
                                                            <div class="d-flex justify-content-between">
                                                                <span class="fw-medium">{{ $item->name }}</span>
                                                                <span class="text-primary">{{ $item->phone }}</span>
                                                            </div>
                                                            <div class="small text-muted">{{ $item->code }}</div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
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

                            {{-- Sales Staff & Channel --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small text-muted">{{ __('modules/accounting::accounting.sales_staff') }}</label>
                                    <select
                                            class="form-select form-select-sm"
                                            wire:model="payment_user_id">
                                        @foreach ($lists['users'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">{{ __('modules/accounting::accounting.sales_channel') }}</label>
                                    <div class="input-group input-group-sm">
                                        <select
                                                class="form-select form-select-sm"
                                                wire:model="payment_sale_channel_id">
                                            <option value="">{{ __('core/base::general.all') }}</option>
                                            @foreach ($lists['sale_channels'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-icon btn-ghost-primary" wire:click="$dispatch('show-modal-create-sale-channel')" title="{{ trans('modules/accounting::accounting.add_sale_channel') }}">
                                            {!! tabler_icon('plus') !!}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Delivery Info --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small text-muted">{{ __('modules/accounting::accounting.delivery_partner') }}</label>
                                    <div class="input-group input-group-sm">
                                        <select
                                                class="form-select form-select-sm"
                                                wire:model="payment_delivery.partner_delivery_id">
                                            <option value="">{{ __('core/base::general.none') }}</option>
                                            @foreach ($lists['partner_deliveries'] ?? [] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-icon btn-ghost-primary" wire:click="$dispatch('show-modal-create-partner-delivery')" title="{{ trans('modules/accounting::accounting.add_delivery_partner') }}">
                                            {!! tabler_icon('plus') !!}
                                        </button>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">{{ __('modules/accounting::accounting.bill_of_lading_code') }}</label>
                                    <input
                                           type="text"
                                           class="form-control form-control-sm"
                                           wire:model="payment_delivery.code"
                                           placeholder="{{ trans('modules/accounting::accounting.enter_code') }}">
                                </div>
                            </div>

                            {{-- Note --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted">
                                    {!! tabler_icon('notes', ['class' => 'icon icon-sm me-1']) !!}
                                    {{ trans('modules/accounting::accounting.note') }}
                                </label>
                                <textarea
                                          class="form-control form-control-sm"
                                          wire:model="payment.note"
                                          rows="2"
                                          placeholder="{{ trans('modules/accounting::accounting.invoice_note_placeholder') }}"></textarea>
                            </div>

                            {{-- New Summary & Payment Section --}}
                            <div class="rounded-3 border bg-white p-3 shadow-sm" x-data="{
                                totalPayment: @entangle('total_payment'),
                                valuePayment: @entangle('payment.value_payment')
                            }">
                                {{-- Total Cost --}}
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">{{ __('modules/accounting::accounting.total_cost') }}</span>
                                    <span class="fw-medium">{{ core_number_format((int) $payment['total_cost']) }}</span>
                                </div>

                                {{-- Order Discount with Popover --}}
                                <div class="d-flex justify-content-between align-items-center mb-2"
                                     x-data="{
                                         open: false,
                                         localType: '{{ $payment['discount_type'] ?? 'number' }}',
                                         localValue: {{ (int) ($payment['discount_value'] ?? 0) }},
                                         apply() {
                                             $wire.applyDiscount(this.localType, this.localValue);
                                             this.open = false;
                                         }
                                     }"
                                     @click.outside="open = false">
                                    <span class="text-muted small">{{ trans('modules/accounting::accounting.order_discount') }}</span>
                                    <div class="position-relative">
                                        <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none border-bottom border-danger border-dashed p-0" @click="open = !open">
                                            - {{ core_number_format((int) $payment['discount_value']) }} {{ $payment['discount_type'] === 'percent' ? '%' : 'VNĐ' }}
                                            {!! tabler_icon('chevron-down', ['class' => 'icon icon-sm ms-1']) !!}
                                        </button>

                                        <div class="dropdown-menu show border-0 p-3 shadow-lg" x-show="open" x-cloak style="min-width: 260px; position: absolute; z-index: 1050; right: 0; margin-top: 5px; border-radius: 12px;">
                                            <div class="btn-group w-100 mb-3" role="group">
                                                <button type="button" @click="localType = 'percent'; localValue = 0" :class="localType === 'percent' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary'">
                                                    {!! tabler_icon('percentage', ['class' => 'icon icon-sm me-1']) !!} %
                                                </button>
                                                <button type="button" @click="localType = 'number'; localValue = 0" :class="localType === 'number' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary'">
                                                    {!! tabler_icon('currency-dong', ['class' => 'icon icon-sm me-1']) !!} VNĐ
                                                </button>
                                            </div>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control text-end" :value="localValue.toLocaleString('vi-VN')" @input="localValue = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0; $nextTick(() => { $event.target.value = localValue.toLocaleString('vi-VN'); })">
                                                <span class="input-group-text" x-text="localType === 'percent' ? '%' : 'VNĐ'"></span>
                                            </div>
                                            <button type="button" class="btn btn-success w-100 py-2" @click="apply()">
                                                {!! tabler_icon('check', ['class' => 'icon icon-sm me-1']) !!} {{ trans('modules/accounting::accounting.apply') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Final Amount + Payment Method Selector --}}
                                <div wire:ignore.self x-data="{
                                    showMultiPayment: false,
                                    multiEntries: [],
                                    currentAmount: 0,
                                    selectedMethodCode: '',
                                    selectedMethodLabel: '',
                                    savedEntries: @entangle('payment.type_payment'),
                                    totalPaid() {
                                        return this.multiEntries.reduce((sum, e) => sum + (parseInt(e.value) || 0), 0);
                                    },
                                    remaining() {
                                        return Math.max(0, (parseInt(this.totalPayment) || 0) - this.totalPaid());
                                    },
                                    addEntry(code, label) {
                                        let amount = parseInt(this.currentAmount) || 0;
                                        if (amount <= 0) return;
                                        this.multiEntries.push({
                                            method: code,
                                            label: label,
                                            value: amount,
                                        });
                                        this.currentAmount = this.remaining();
                                    },
                                    removeEntry(index) {
                                        this.multiEntries.splice(index, 1);
                                        this.currentAmount = this.remaining();
                                    },
                                    fillRemaining() {
                                        this.currentAmount = this.remaining();
                                    },
                                    openModal() {
                                        // Load existing entries if multi-payment was already set
                                        let existing = this.savedEntries;
                                        if (Array.isArray(existing) && existing.length > 1) {
                                            this.multiEntries = existing.map(e => ({
                                                method: e.method,
                                                label: e.label || e.method,
                                                value: parseInt(e.value) || 0,
                                            }));
                                            this.currentAmount = this.remaining();
                                        } else {
                                            this.multiEntries = [];
                                            this.currentAmount = parseInt(this.totalPayment) || 0;
                                        }
                                        this.selectedMethodCode = '';
                                        this.selectedMethodLabel = '';
                                        this.showMultiPayment = true;
                                    },
                                    confirmMulti() {
                                        if (this.multiEntries.length === 0) return;
                                        let entries = JSON.parse(JSON.stringify(this.multiEntries));
                                        this.savedEntries = entries;
                                        $wire.setMultiPayment(entries);
                                        this.showMultiPayment = false;
                                    },
                                    totalPayment: @entangle('total_payment'),
                                    valuePayment: @entangle('payment.value_payment'),
                                    formatNumber(n) {
                                        return (parseInt(n) || 0).toLocaleString('vi-VN');
                                    },
                                    parseNumber(str) {
                                        return parseInt(String(str).replace(/[^0-9]/g, '')) || 0;
                                    }
                                }">
                                    <div class="d-flex justify-content-between align-items-center border-top mb-3 pt-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-dark fw-bold">{{ trans('modules/accounting::accounting.grand_total') }}</span>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-circle" @click="openModal()" title="{{ trans('modules/accounting::accounting.multi_payment') }}">
                                                {!! tabler_icon('wallet', ['class' => 'icon icon-sm']) !!}
                                            </button>
                                        </div>
                                        <span class="fw-bold fs-2 text-primary" x-text="formatNumber(totalPayment)"></span>
                                    </div>

                                    {{-- Payment Method Buttons --}}
                                    <div class="mb-3">
                                        <label class="form-label small text-muted mb-2">{{ trans('modules/accounting::accounting.payment_method') }}</label>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($lists['payment_methods'] ?? [] as $method)
                                                <button
                                                        type="button"
                                                        @class([
                                                            'btn btn-sm px-2',
                                                            'btn-primary' =>
                                                                isset($payment['type_payment'][0]['method']) &&
                                                                $payment['type_payment'][0]['method'] === $method->code &&
                                                                count($payment['type_payment'] ?? []) === 1,
                                                            'btn-outline-primary' =>
                                                                !isset($payment['type_payment'][0]['method']) ||
                                                                $payment['type_payment'][0]['method'] !== $method->code ||
                                                                count($payment['type_payment'] ?? []) > 1,
                                                        ])
                                                        wire:click="setPaymentMethod('{{ $method->code }}')">
                                                    {{ $method->name }}
                                                </button>
                                            @endforeach

                                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" wire:click="$dispatch('modal-create-payment-method')" title="{{ trans('modules/accounting::accounting.add_payment_method') }}">
                                                {!! tabler_icon('plus') !!}
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Multi-payment badge summary (Alpine-driven) --}}
                                    <template x-if="Array.isArray(savedEntries) && savedEntries.length > 1">
                                        <div class="d-flex flex-column mt-2 gap-1">
                                            <template x-for="(tp, tpIdx) in savedEntries" :key="tpIdx">
                                                <div class="d-flex justify-content-between align-items-center bg-light rounded-2 px-2 py-1">
                                                    <span class="small text-muted" x-text="tp.label || tp.method"></span>
                                                    <span class="fw-bold small text-primary" x-text="formatNumber(tp.value)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Multi Payment Modal Overlay --}}
                                    <template x-if="showMultiPayment">
                                        <div class="position-fixed w-100 h-100 d-flex align-items-center justify-content-center start-0 top-0"
                                             style="z-index: 1060; background: rgba(0,0,0,0.5);"
                                             @click.self="showMultiPayment = false"
                                             @keydown.escape.window="showMultiPayment = false">
                                            <div class="rounded-4 bg-white shadow-lg" style="width: 100%; max-width: 440px; max-height: 90vh; overflow-y: auto;">
                                                {{-- Header --}}
                                                <div class="d-flex justify-content-between align-items-center border-bottom p-3">
                                                    <h4 class="fw-bold mb-0">{{ trans('modules/accounting::accounting.multi_payment') }}</h4>
                                                    <button type="button" class="btn-close" @click.prevent.stop="showMultiPayment = false"></button>
                                                </div>

                                                <div class="p-3">
                                                    {{-- Amount Input --}}
                                                    <div class="bg-light rounded-3 mb-3 p-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="text-muted small fw-bold text-uppercase">{{ trans('modules/accounting::accounting.amount') }}</span>
                                                            <span class="badge bg-primary-lt" x-text="multiEntries.length" x-show="multiEntries.length > 0"></span>
                                                        </div>
                                                        <div class="input-group input-group-lg mb-2">
                                                            <span class="input-group-text border-end-0 bg-white">
                                                                {!! tabler_icon('cash', ['class' => 'icon text-primary']) !!}
                                                            </span>
                                                            <input type="text"
                                                                   class="form-control border-start-0 fw-bold fs-2 text-end"
                                                                   :value="formatNumber(currentAmount)"
                                                                   @input="currentAmount = parseNumber($event.target.value); $nextTick(() => { $event.target.value = formatNumber(currentAmount); })">
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <template x-for="quickVal in [50000, 100000, 200000, 500000]" :key="quickVal">
                                                                <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1"
                                                                        @click="currentAmount = quickVal"
                                                                        x-text="formatNumber(quickVal)">
                                                                </button>
                                                            </template>
                                                            <a href="javascript:;" class="small text-primary ms-auto" @click="fillRemaining()">{{ trans('modules/accounting::accounting.remaining_all') }}</a>
                                                        </div>
                                                    </div>

                                                    {{-- Payment Method Grid --}}
                                                    <div class="mb-3">
                                                        <div class="text-muted small fw-bold text-uppercase mb-2">{{ trans('modules/accounting::accounting.select_method') }}</div>
                                                        <div class="row g-2">
                                                            @foreach ($lists['payment_methods'] ?? [] as $method)
                                                                <div class="col-4">
                                                                    <button type="button"
                                                                            class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center rounded-3 gap-1 border-2 py-3"
                                                                            style="transition: all .15s;"
                                                                            @click="addEntry('{{ $method->code }}', '{{ $method->name }}')"
                                                                            :class="{ 'border-primary bg-primary-lt': selectedMethodCode === '{{ $method->code }}' }"
                                                                            @mouseenter="selectedMethodCode = '{{ $method->code }}'"
                                                                            @mouseleave="selectedMethodCode = ''">
                                                                        <span class="avatar avatar-sm rounded-circle mb-1"
                                                                              :class="{
                                                                                  'bg-green-lt': '{{ $method->code }}'
                                                                                  === 'cash',
                                                                                  'bg-blue-lt': '{{ $method->code }}'
                                                                                  === 'bank' || '{{ $method->code }}'
                                                                                  === 'transfer',
                                                                                  'bg-purple-lt': '{{ $method->code }}'
                                                                                  === 'card',
                                                                                  'bg-orange-lt': '{{ $method->code }}'
                                                                                  === 'cod',
                                                                                  'bg-secondary-lt': '{{ $method->code }}'
                                                                                  !== 'cash' && '{{ $method->code }}'
                                                                                  !== 'bank' && '{{ $method->code }}'
                                                                                  !== 'transfer' && '{{ $method->code }}'
                                                                                  !== 'card' && '{{ $method->code }}'
                                                                                  !== 'cod',
                                                                              }">
                                                                            @php
                                                                                $iconName = match ($method->code) {
                                                                                    'cash' => 'cash',
                                                                                    'bank', 'transfer' => 'building-bank',
                                                                                    'card' => 'credit-card',
                                                                                    'cod' => 'truck-delivery',
                                                                                    'other' => 'dots',
                                                                                    default => 'wallet',
                                                                                };
                                                                            @endphp
                                                                            {!! tabler_icon($iconName, ['class' => 'icon icon-sm']) !!}
                                                                        </span>
                                                                        <span class="small fw-medium text-dark">{{ $method->name }}</span>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    {{-- Entries List --}}
                                                    <template x-if="multiEntries.length > 0">
                                                        <div class="mb-3">
                                                            <div class="border-top pt-3">
                                                                <div class="d-flex justify-content-between mb-2">
                                                                    <span class="text-muted small fw-bold text-uppercase">{{ trans('modules/accounting::accounting.paid') }}</span>
                                                                    <span class="fw-bold text-success" x-text="formatNumber(totalPaid())"></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-3">
                                                                    <span class="text-muted small fw-bold text-uppercase">{{ trans('modules/accounting::accounting.remaining') }}</span>
                                                                    <span class="fw-bold" :class="remaining() > 0 ? 'text-danger' : 'text-success'" x-text="formatNumber(remaining())"></span>
                                                                </div>
                                                            </div>

                                                            <template x-for="(entry, idx) in multiEntries" :key="idx">
                                                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="text-muted">{!! tabler_icon('dots', ['class' => 'icon icon-sm']) !!}</span>
                                                                        <span class="fw-medium" x-text="entry.label"></span>
                                                                    </div>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="fw-bold" x-text="formatNumber(entry.value)"></span>
                                                                        <button type="button" class="btn btn-icon btn-sm btn-ghost-danger" @click="removeEntry(idx)">
                                                                            {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>

                                                {{-- Footer --}}
                                                <div class="d-flex justify-content-between align-items-center border-top p-3">
                                                    <div>
                                                        <span class="text-muted small">{{ trans('modules/accounting::accounting.total_amount_label') }}</span>
                                                        <span class="fw-bold text-primary ms-1" x-text="formatNumber(totalPaid())"></span>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-secondary" @click.prevent.stop="showMultiPayment = false">{{ trans('modules/accounting::accounting.close') }}</button>
                                                        <button type="button" class="btn btn-primary d-flex align-items-center gap-1"
                                                                @click.prevent.stop="confirmMulti()"
                                                                :disabled="multiEntries.length === 0">
                                                            {!! tabler_icon('check', ['class' => 'icon icon-sm']) !!}
                                                            {{ trans('modules/accounting::accounting.confirm') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Customer Payment Input (single method only) --}}
                                    @if (count($payment['type_payment'] ?? []) <= 1)
                                        <div class="mt-3">
                                            <label class="form-label small text-muted mb-1">{{ trans('modules/accounting::accounting.customer_payment') }}</label>
                                            <div class="input-group shadow-sm">
                                                <input type="text" class="form-control fw-bold text-primary fs-3 text-end"
                                                       :value="valuePayment.toLocaleString('vi-VN')"
                                                       @input="valuePayment = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0; $nextTick(() => { $event.target.value = valuePayment.toLocaleString('vi-VN'); })">
                                                <button type="button" class="btn btn-primary px-3" @click="valuePayment = totalPayment">
                                                    {{ trans('modules/accounting::accounting.all') }}
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Change Amount --}}
                                        <template x-if="valuePayment > totalPayment">
                                            <div class="d-flex justify-content-between bg-success-lt rounded-2 mt-3 p-2">
                                                <span class="text-success fw-medium">{{ trans('modules/accounting::accounting.change_amount') }}</span>
                                                <span class="text-success fw-bold fs-4" x-text="(valuePayment - totalPayment).toLocaleString('vi-VN') + ' VNĐ'"></span>
                                            </div>
                                        </template>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-muted small">
                            <span class="badge bg-primary-lt">{{ count($products) }} {{ __('modules/product::product.products') }}</span>
                        </div>
                        @if (!empty($payment['id']))
                            <label class="form-check form-check-inline m-0 cursor-pointer" style="cursor: pointer;">
                                <input class="form-check-input" type="checkbox" wire:model="force_completed" style="cursor: pointer;">
                                <span class="form-check-label fw-medium text-success user-select-none text-nowrap">
                                    {{ tabler_icon('check', ['class' => 'icon icon-sm text-success']) }} Đánh dấu hoàn thành đơn hàng
                                </span>
                            </label>
                        @endif
                    </div>
                    <div class="btn-list">
                        <button
                                type="button"
                                class="btn btn-secondary"
                                wire:click="closeModal">
                            {{ __('core/base::general.cancel') }}
                        </button>
                        @canany(['accountings.create', 'accountings.edit'])
                            @if (empty($payment['id']) || !in_array($payment['status'] ?? '', ['success', 'completed']))
                                <button
                                        type="button"
                                        class="btn btn-warning"
                                        wire:click="saveDraft"
                                        wire:loading.attr="disabled"
                                        @disabled(empty($payment['id']) && count($products) === 0)>
                                    <span wire:loading.remove wire:target="saveDraft">
                                        {!! tabler_icon('clipboard', ['class' => 'icon icon-sm me-1']) !!}
                                        {{ trans('modules/accounting::accounting.save_draft') }}
                                    </span>
                                    <span wire:loading wire:target="saveDraft">
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        {{ __('core/base::general.processing') }}
                                    </span>
                                </button>
                            @endif
                            <button
                                    type="submit"
                                    class="btn btn-primary"
                                    wire:loading.attr="disabled"
                                    @disabled(empty($payment['id']) && count($products) === 0)>
                                <span wire:loading.remove wire:target="save">
                                    {!! tabler_icon('device-floppy', ['class' => 'icon icon-sm me-1']) !!}
                                    {{ __('modules/accounting::accounting.save_and_print') }}
                                </span>
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                    {{ __('core/base::general.processing') }}
                                </span>
                            </button>
                            @if (!empty($payment['id']))
                                <button
                                        type="button"
                                        class="btn btn-success"
                                        wire:click="saveOnly"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveOnly">
                                        {!! tabler_icon('check', ['class' => 'icon icon-sm me-1']) !!}
                                        {{ trans('modules/accounting::accounting.save') }}
                                    </span>
                                    <span wire:loading wire:target="saveOnly">
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        {{ __('core/base::general.processing') }}
                                    </span>
                                </button>
                            @endif
                        @endcanany
                    </div>
                </div>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

<div class="h-100 payment-layout">
    <div class="row g-2 h-100">
        {{-- Left Column: Product List --}}
        <div class="col-12 col-md-8 d-flex flex-column h-100">
            <x-ui::card class="h-100 border-0 shadow-none">
                <x-slot name="body" class="p-0 d-flex flex-column h-100">
                    <div class="table-responsive flex-grow-1">
                        <x-ui::table class="table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th class="w-1">#</th>
                                    <th class="w-1"></th>
                                    <th class="w-33">{{ __('modules/product::product.name') }}</th>
                                    <th>{{ __('modules/product::product.unit') }}</th>
                                    <th class="w-15">{{ __('modules/product::product.quantity') }}</th>
                                    <th class="w-15 text-end">{{ __('modules/product::product.price') }}</th>
                                    <th class="w-15 text-end">{{ __('modules/product::product.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->products as $item)
                                    <tr wire:key="product-row-{{ $item['product_id'] }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-icon btn-ghost-danger btn-sm"
                                                wire:click="removeProduct({{ $item['product_id'] }})"
                                                title="{{ __('core/base::general.delete') }}"
                                            >
                                                {!! tabler_icon("trash") !!}
                                            </button>
                                        </td>
                                        <td>
                                            <div class="font-weight-medium" style="white-space: normal; min-width: 150px;">{{ $item['product']['name'] }}</div>
                                            @if($item['product']['code'] ?? null)
                                                <div class="text-muted small">{{ $item['product']['code'] }}</div>
                                            @endif

                                            <div class="mt-1">
                                                <input type="text"
                                                    class="form-control form-control-sm form-control-flush"
                                                    wire:model.live.debounce.500ms="products.{{ $item['product_id'] }}.note"
                                                    placeholder="{{ __('modules/product::product.note_placeholder') }}"
                                                    style="min-width: 150px;"
                                                >
                                            </div>
                                        </td>
                                        <td>{{ $item['product']['unit'] }}</td>
                                        <td>
                                            <div class="input-group input-group-sm flex-nowrap" style="min-width: 100px;">
                                                <button type="button"
                                                    class="btn btn-outline-secondary px-2"
                                                    wire:click="$set('products.{{ $item['product_id'] }}.amount', {{ max(1, $item['amount'] - 1) }})"
                                                >
                                                    {!! tabler_icon("minus") !!}
                                                </button>
                                                <input type="text"
                                                    class="form-control text-center px-1"
                                                    wire:model.live="products.{{ $item['product_id'] }}.amount"
                                                >
                                                <button type="button"
                                                    class="btn btn-outline-secondary px-2"
                                                    wire:click="$set('products.{{ $item['product_id'] }}.amount', {{ $item['amount'] + 1 }})"
                                                >
                                                    {!! tabler_icon("plus") !!}
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-end"
                                            x-data="{
                                                open: false,
                                                localDiscountType: '{{ $item['discount_type'] ?? 'number' }}',
                                                localDiscountValue: {{ (int)($item['discount_value'] ?? 0) }},
                                                originalPrice: {{ (int)$item['value'] }},
                                                salePrice: 0,
                                                init() {
                                                    this.updateSalePrice();
                                                    this.$watch('localDiscountType', () => this.updateSalePrice());
                                                    this.$watch('localDiscountValue', () => this.updateSalePrice());
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
                                                    $wire.set('products.{{ $item['product_id'] }}.discount_type', this.localDiscountType);
                                                    $wire.set('products.{{ $item['product_id'] }}.discount_value', this.localDiscountValue);
                                                    this.open = false;
                                                }
                                            }"
                                            @click.outside="open = false"
                                        >
                                            <div class="cursor-pointer" @click="open = !open">
                                                <div class="font-weight-bold">{{ core_number_format((int)$item['value']) }}</div>
                                                @if ((int)$item['discount_value'] > 0)
                                                    <div class="text-danger small">
                                                        -{{ core_number_format((int)$item['discount_value']) }} {{ $item['discount_type'] === 'percent' ? '%' : 'VNĐ' }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="dropdown-menu show p-3" x-show="open" x-cloak style="min-width: 280px; position: absolute; z-index: 1050; right: 0;">
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small mb-1">{{ __('modules/product::product.unit_price') }}</label>
                                                    <x-form::currency
                                                        wire:model.live.debounce.500ms="products.{{ $item['product_id'] }}.value"
                                                    />
                                                </div>

                                                <div class="mb-3 pb-3 border-bottom border-light">
                                                    <label class="form-label text-muted small mb-1">{{ trans('modules/product::product.selling_price') }}</label>
                                                    <div class="input-group">
                                                        <input type="text"
                                                            class="form-control text-end fw-bold text-primary"
                                                            :value="salePrice.toLocaleString('vi-VN')"
                                                            @input="
                                                                let val = parseInt($event.target.value.replace(/[^0-9]/g, '')) || 0;
                                                                updateDiscountFromSalePrice(val);
                                                                $nextTick(() => { $event.target.value = salePrice.toLocaleString('vi-VN'); });
                                                            "
                                                            @keydown.enter.prevent="applyDiscount()"
                                                        >
                                                        <span class="input-group-text bg-primary-lt text-primary fw-bold">VNĐ</span>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">{{ trans('modules/product::product.discount') }}</label>
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

                                                    <div class="input-group mb-2">
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

                                                    <button type="button" class="btn btn-success w-100" @click.stop="applyDiscount()">
                                                        {{ tabler_icon('check') }} {{ trans('modules/product::product.apply') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end font-weight-bold">
                                            {{ core_number_format((int)$item['total']) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <div class="empty-img mb-3">
                                                {!! tabler_icon('shopping-cart', ['class' => 'icon icon-lg opacity-25', 'style' => 'width: 48px; height: 48px;']) !!}
                                            </div>
                                            <p class="empty-title">{{ __('modules/product::product.cart_empty') }}</p>
                                            <p class="empty-subtitle text-secondary">{{ __('modules/product::product.search_hint') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </x-ui::table>
                    </div>

                    {{-- Order Notes Section --}}
                    <div class="card-footer bg-body border-top p-0">
                        <div class="accordion" id="order-notes-accordion">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button py-2 px-4 bg-transparent shadow-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#notes-collapse">
                                        <span class="me-2 text-muted" style="font-size: 0.875rem;">
                                            {!! tabler_icon('note') !!}
                                        </span>
                                        <span class="text-muted" style="font-size: 0.875rem;">{{ trans('modules/product::product.order_note') }}</span>
                                    </button>
                                </h2>
                                <div id="notes-collapse" class="accordion-collapse collapse" data-bs-parent="#order-notes-accordion">
                                    <div class="accordion-body p-4"
                                         x-data="{
                                             note: $wire.entangle('payment.note')
                                         }">
                                        <div class="position-relative order-notes-section">
                                            <textarea
                                                class="form-control resize-vertical textarea-improved"
                                                style="min-height: 60px; max-height: 300px;"
                                                rows="2"
                                                placeholder="{{ trans('modules/product::product.order_note_placeholder') }}"
                                                x-model="note"
                                                @input="
                                                    $nextTick(() => {
                                                        $el.style.height = 'auto';
                                                        $el.style.height = $el.scrollHeight + 'px';
                                                    });
                                                "
                                            ></textarea>
                                            {{-- Character count - only show when typing --}}
                                            <div class="position-absolute bottom-2 end-2 character-counter" x-show="(note || '').length > 0" x-transition>
                                                <span class="badge bg-secondary-lt text-muted" x-text="(note || '').length + '/255'"></span>
                                            </div>
                                        </div>

                                        {{-- Quick action buttons --}}
                                        <div class="mt-3 d-flex gap-2" x-show="(note || '').length > 0" x-transition>
                                            <button type="button"
                                                    class="btn btn-sm btn-ghost-secondary"
                                                    wire:click="$set('payment.note', '')">
                                                {!! tabler_icon('trash', ['class' => 'icon icon-sm']) !!}
                                                {{ __('Xóa') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot>
            </x-ui::card>
        </div>

        {{-- Right Column: Payment & Info --}}
        <div class="col-12 col-md-4 d-flex flex-column h-100"
        x-data
        @update-payment-user-{{ $tab_selected }}.window="(() => {
             var el = document.getElementById('payment_user_id_{{ $tab_selected }}');
             if (el && el.tomselect) {
                  el.tomselect.setValue($event.detail.value);
             } else {
                 window.dispatchEvent(new CustomEvent('update-payment-options', {
                    detail: {
                        id: 'payment_user_id_{{ $tab_selected }}',
                        value: $event.detail.value
                    }
                 }));
             }
        })()"
        @global-update-sale-channels.window="(() => {
             let formattedOptions = $event.detail.options.map(opt => ({
                 id: opt.value,
                 label: opt.text
             }));
             window.dispatchEvent(new CustomEvent('update-payment-options', {
                detail: {
                    id: 'payment_sale_channel_id_{{ $tab_selected }}',
                    options: formattedOptions,
                    value: $event.detail.selected
                }
             }));
        })()"
        @product-print-payment.window="PoliriumPrint.printUrl($event.detail.url)"
        >
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="card-title mb-0">{{ __('modules/product::product.pay_now') }}</h3>
                <button type="button"
                    class="btn btn-sm btn-ghost-secondary"
                    wire:click="$dispatch('show-draft-list')">
                    {!! tabler_icon('files') !!}
                    {{ __('modules/product::payment.draft_list') }}
                </button>
            </div>
            <x-ui::card class="h-100 border-0 shadow-sm d-flex flex-column">
                <x-slot name="body" class="p-3 bg-white d-flex flex-column h-100 overflow-auto">
                    {{-- Customer & Sales Info --}}
                    <div class="row g-2 mb-4">
                        <div class="col-md-6" wire:ignore>
                            <x-form::select
                                id="payment_user_id_{{ $tab_selected }}"
                                wire:model="payment_user_id"
                                :options="$lists['users']"
                                :label="trans('modules/product::product.sales_staff')"
                                tomselect
                            />
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-end gap-1">
                                <div class="flex-grow-1">
                                    <x-form::select
                                        id="payment_sale_channel_id_{{ $tab_selected }}"
                                        wire:model="payment_sale_channel_id"
                                        :options="$lists['sale_channels']"
                                        :label="trans('modules/product::product.sales_channel')"
                                    />
                                </div>
                                <button type="button"
                                    class="btn btn-icon {{ ($payment['sale_channel_id'] ?? null) ? 'btn-warning' : 'btn-ghost-success' }}"
                                    wire:click="$dispatch('show-modal-create-sale-channel', { id: {{ $payment['sale_channel_id'] ?? 0 }} })"
                                >
                                    @if ($payment['sale_channel_id'] ?? null)
                                        {!! tabler_icon("edit") !!}
                                    @else
                                        {!! tabler_icon("plus") !!}
                                    @endif
                                </button>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            @if ($payment['customer_id'] ?? null)
                                <div class="form-control d-flex align-items-center justify-content-between p-2 h-auto">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="avatar avatar-xs rounded bg-primary-lt mt-1 flex-shrink-0">
                                            {{ substr($this->customer?->name ?? 'C', 0, 1) }}
                                        </span>
                                        <div>
                                            <div class="font-weight-medium">{{ $this->customer?->name }}</div>
                                            <div class="text-muted small">{{ $this->customer?->phone }}</div>
                                            @if($this->customer?->address)
                                            <div class="text-muted small" style="white-space: normal;">{{ $this->customer?->address }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-list">
                                        <button type="button" class="btn btn-icon btn-sm btn-ghost-warning"
                                            wire:click="$dispatch('show-modal-create-customer', { id: {{ $payment['customer_id'] }} })">
                                            {!! tabler_icon("edit") !!}
                                        </button>
                                        <button type="button" class="btn btn-icon btn-sm btn-ghost-danger"
                                            wire:click="removeCustomer">
                                            {!! tabler_icon("x") !!}
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-end gap-1">
                                    <div class="flex-grow-1">
                                        <x-form::autocomplete
                                            wire:model.live.debounce.300ms="search.customer"
                                            placeholder="{{ trans('modules/product::product.search_customer_placeholder') }}"
                                            class="mb-0"
                                        >
                                            @if (isset_value($search['customer']) && $this->customers?->count() > 0)
                                                @foreach ($this->customers as $item)
                                                    <x-form::autocomplete.item wire:click="$set('payment.customer_id', {{ $item->id }})">
                                                        <div class="d-flex justify-content-between">
                                                            <span><b>{{ $item->name }}</b></span>
                                                            <span class="text-primary">{{ $item->phone }}</span>
                                                        </div>
                                                        <div class="small text-muted">{{ $item->code }}</div>
                                                    </x-form::autocomplete.item>
                                                @endforeach
                                            @elseif (isset_value($search['customer']) && $this->customers?->count() == 0)
                                                <x-form::autocomplete.item
                                                    wire:click="$dispatch('show-modal-create-customer', { id: 0, name: '{{ addslashes($search['customer']) }}' })"
                                                    class="cursor-pointer"
                                                >
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
                                        title="{{ trans('Thêm khách hàng') }}">
                                        {!! tabler_icon('plus') !!}
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Delivery Info (Below Customer) --}}
                        <div class="col-12 mt-2">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="d-flex align-items-end gap-1">
                                        <div class="flex-grow-1">
                                            <x-form::select
                                                :options="$lists['partner_deliveries'] ?? []"
                                                wire:model.live="payment_delivery.partner_delivery_id"
                                                :label="trans('modules/product::product.delivery_partner_short')"
                                            />
                                        </div>
                                        <button type="button"
                                            wire:click="$dispatch('show-modal-create-partner-delivery', { id: 0 })"
                                            class="btn btn-icon btn-ghost-success">
                                            {!! tabler_icon('plus') !!}
                                        </button>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <x-form::input
                                        wire:model="payment_delivery.code"
                                        :label="trans('modules/product::product.delivery_code')"
                                        placeholder="{{ trans('modules/product::product.enter_code') }}"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Details View --}}
                    <div class="flex-grow-1">
                        @include('modules/product::payment.payment-view')
                    </div>

                    {{-- Actions --}}
                    <div class="mt-auto pt-3 border-top">
                        @if (($payment['id'] ?? null) && ($payment['status'] ?? null) !== 'draft')
                            <div class="d-grid gap-2">
                                <button type="button" onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', ['id' => $payment['id']]) }}')" class="btn btn-outline-secondary w-100">
                                    {!! tabler_icon('printer') !!} {{ trans('modules/product::product.print_invoice') }}
                                </button>
                                <button wire:click="newPayment" class="btn btn-primary w-100">
                                    {!! tabler_icon('plus') !!} {{ trans('modules/product::product.new_order') }}
                                </button>
                            </div>
                        @else
                            <div class="d-grid gap-2">
                                {{-- Nút Lưu tạm --}}
                                <x-ui::button
                                    color="warning"
                                    icon="device-floppy"
                                    wire:click="saveDraft"
                                    size="lg"
                                    :disabled="count((array)$products) === 0"
                                >
                                    {{ __('modules/product::payment.save_draft') }}
                                </x-ui::button>

                                {{-- Nút Thanh toán --}}
                                <button wire:click="save"
                                    wire:loading.attr="disabled"
                                    class="btn btn-primary py-2 fs-3 fw-bold"
                                    @if(!(count((array)$products) > 0 && (($payment['value_payment'] ?? 0) >= (int)$total_payment || $isPendingMethod))) disabled @endif
                                >
                                    <span wire:loading.remove wire:target="save">
                                        {!! tabler_icon('check') !!} {{ trans('modules/product::product.pay_now') }}
                                    </span>
                                    <span wire:loading wire:target="save">
                                        <span class="spinner-border spinner-border-sm me-1"></span> {{ trans('core/base::general.processing') }}
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>
                </x-slot>


            </x-ui::card>
        </div>
    </div>

    {{-- Modals --}}
    @livewire('modules/product::payment.modal.modal-payment-type', compact("tab_selected"), "modules/product::v2.modal.payment-type-{$tab_selected}")
    @livewire('modules/product::payment.draft-payment-list')
    @livewire('modules/product::payment.modal.modal-create-partner-delivery')
    @livewire('modules/product::payment.modal.modal-create-sale-channel')
    @livewire('modules/customer::index.modal.modal-create-customer')

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('close-or-reset-tab', (event) => {
                    // event is an array of parameters in Livewire 3: event[0].tabId
                    let tabId = event[0]?.tabId || event.tabId;
                    if (!tabId) return;

                    let tabCount = document.querySelectorAll('.nav-tabs .nav-item').length;
                    // If more than 1 tab (we count > 2 because of the "add new tab" button)
                    if (tabCount > 2) {
                        Livewire.dispatch('remove-payment-tab', { tabId: tabId });
                    }
                });
            });
        </script>
    @endpush
</div>

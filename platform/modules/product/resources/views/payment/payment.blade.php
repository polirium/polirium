<div class="h-100 payment-layout">
    <div class="pos-invoice" wire:loading.delay.attr="data-pos-syncing">
        {{-- Cart --}}
        <section class="pos-cart">
            <div class="pos-cart-items">
                <table class="table table-vcenter card-table pos-cart-table">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th class="w-100">{{ __('modules/product::product.name') }}</th>
                            <th class="w-1">{{ __('modules/product::product.unit') }}</th>
                            <th class="w-1 text-center">{{ __('modules/product::product.quantity') }}</th>
                            <th class="w-1 text-end">{{ __('modules/product::product.price') }}</th>
                            <th class="w-1 text-end">{{ __('modules/product::product.total') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->products as $item)
                            <tr wire:key="product-row-{{ $item['product_id'] }}">
                                <td class="text-secondary">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-medium text-wrap">{{ $item['product']['name'] }}</div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        @if ($item['product']['code'] ?? null)
                                            <span class="text-secondary text-nowrap">{{ $item['product']['code'] }}</span>
                                        @endif
                                        <input type="text"
                                            class="pos-line-note"
                                            wire:model.blur="products.{{ $item['product_id'] }}.note"
                                            placeholder="{{ __('modules/product::product.note_placeholder') }}"
                                        >
                                    </div>
                                </td>
                                <td class="text-secondary text-nowrap">{{ $item['product']['unit'] }}</td>
                                <td>
                                    {{-- The number changes at once; clicks within 250 ms go to the server as one update.
                                         Keyed by the saved amount so a change made on the server (a second scan) shows. --}}
                                    <div class="pos-qty"
                                        wire:key="qty-{{ $item['product_id'] }}-{{ $item['amount'] }}"
                                        x-data="{
                                            qty: {{ (int) $item['amount'] }},
                                            timer: null,
                                            set(value) {
                                                this.qty = Math.max(1, parseInt(value) || 1);
                                                clearTimeout(this.timer);
                                                this.timer = setTimeout(() => $wire.setQuantity({{ $item['product_id'] }}, this.qty), 250);
                                            },
                                        }"
                                    >
                                        <button type="button" class="btn btn-icon btn-sm btn-ghost-secondary" @click="set(qty - 1)" :disabled="qty <= 1">
                                            {!! tabler_icon('minus') !!}
                                        </button>
                                        <input type="text"
                                            class="form-control form-control-sm text-center"
                                            inputmode="numeric"
                                            :value="qty"
                                            @change="set($event.target.value)"
                                            @keydown.enter.prevent="set($event.target.value)"
                                            @focus="$event.target.select()"
                                        >
                                        <button type="button" class="btn btn-icon btn-sm btn-ghost-secondary" @click="set(qty + 1)">
                                            {!! tabler_icon('plus') !!}
                                        </button>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="pos-price" title="{{ trans('modules/product::product.selling_price') }}"
                                        @click="$dispatch('pos-edit-price', {
                                            id: {{ $item['product_id'] }},
                                            price: {{ (int) $item['value'] }},
                                            type: '{{ ($item['discount_type'] ?? 'number') === 'percent' ? 'percent' : 'number' }}',
                                            discount: {{ (int) ($item['discount_value'] ?? 0) }},
                                            anchor: $el,
                                        })"
                                    >
                                        <span class="text-nowrap">{{ core_number_format((int)$item['value']) }}</span>
                                        @if ((int)$item['discount_value'] > 0)
                                            <span class="d-block text-danger small text-nowrap">
                                                -{{ core_number_format((int)$item['discount_value']) }}{{ $item['discount_type'] === 'percent' ? '%' : '' }}
                                            </span>
                                        @endif
                                    </button>
                                </td>
                                <td class="text-end fw-semibold text-nowrap">
                                    {{ core_number_format((int)$item['total']) }}
                                </td>
                                <td>
                                    <button type="button"
                                        class="btn btn-icon btn-sm btn-ghost-danger"
                                        @click="$el.closest('tr').classList.add('pos-row-removing')"
                                        wire:click="removeProduct({{ $item['product_id'] }})"
                                        title="{{ __('core/base::general.delete') }}"
                                    >
                                        {!! tabler_icon('x') !!}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="pos-cart-empty">
                                <td colspan="7">
                                    {!! tabler_icon('shopping-cart', ['class' => 'icon']) !!}
                                    <div class="fw-medium mt-2">{{ __('modules/product::product.cart_empty') }}</div>
                                    <div class="text-secondary small">{{ __('modules/product::payment.search_placeholder') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- One price/discount editor for every line (it used to be rendered on each row) --}}
            <div class="dropdown-menu show p-3 pos-price-editor"
                wire:ignore
                x-data="{
                    open: false,
                    id: null,
                    price: 0,
                    type: 'number',
                    discount: 0,
                    top: 0,
                    right: 0,
                    get sale() {
                        const off = this.type === 'percent' ? this.price * this.discount / 100 : this.discount;
                        return Math.max(0, Math.round(this.price - off));
                    },
                    fmt(n) { return (Number(n) || 0).toLocaleString('vi-VN'); },
                    num(v) { return parseInt(String(v).replace(/[^0-9]/g, '')) || 0; },
                    setSale(value) {
                        const sale = Math.min(this.price, this.num(value));
                        this.type = 'number';
                        this.discount = this.price - sale;
                    },
                    show(d) {
                        Object.assign(this, { id: d.id, price: d.price, type: d.type, discount: d.discount });
                        const host = $el.parentElement.getBoundingClientRect();
                        const box = d.anchor.getBoundingClientRect();
                        this.top = box.bottom - host.top + 4;
                        this.right = host.right - box.right;
                        this.open = true;
                        $nextTick(() => $refs.sale.select());
                    },
                    apply() {
                        if (this.type === 'percent') this.discount = Math.min(100, this.discount);
                        $wire.updateLinePrice(this.id, this.price, this.type, this.discount);
                        this.open = false;
                    },
                }"
                x-show="open"
                x-cloak
                :style="`top: ${top}px; right: ${right}px`"
                @pos-edit-price.window="show($event.detail)"
                @click.outside="if (!$event.target.closest('.pos-price')) open = false"
                @keydown.escape.window="open = false"
            >
                <div class="mb-3">
                    <label class="form-label text-muted small mb-1">{{ __('modules/product::payment.line_unit_price') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control text-end" :value="fmt(price)"
                            @input="price = num($event.target.value); $nextTick(() => $event.target.value = fmt(price))"
                            @keydown.enter.prevent="apply()">
                        <span class="input-group-text">VNĐ</span>
                    </div>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <label class="form-label text-muted small mb-1">{{ __('modules/product::payment.line_sale_price') }}</label>
                    <div class="input-group">
                        <input type="text" x-ref="sale" class="form-control text-end fw-bold text-primary" :value="fmt(sale)"
                            @input="setSale($event.target.value); $nextTick(() => $event.target.value = fmt(sale))"
                            @keydown.enter.prevent="apply()">
                        <span class="input-group-text">VNĐ</span>
                    </div>
                </div>

                <label class="form-label">{{ trans('modules/product::product.discount') }}</label>
                <div class="btn-group w-100 mb-2">
                    <button type="button" class="btn btn-sm" :class="type === 'percent' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'percent'; discount = 0">
                        {!! tabler_icon('percentage') !!} %
                    </button>
                    <button type="button" class="btn btn-sm" :class="type === 'number' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'number'; discount = 0">
                        {!! tabler_icon('currency-dong') !!} VNĐ
                    </button>
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control text-end" :value="type === 'percent' ? discount : fmt(discount)"
                        @input="discount = num($event.target.value); $nextTick(() => $event.target.value = type === 'percent' ? discount : fmt(discount))"
                        @keydown.enter.prevent="apply()">
                    <span class="input-group-text" x-text="type === 'percent' ? '%' : 'VNĐ'"></span>
                </div>

                <button type="button" class="btn btn-primary w-100" @click="apply()">
                    {!! tabler_icon('check') !!} {{ trans('modules/product::product.apply') }}
                </button>
            </div>

            {{-- Order note --}}
            <div class="pos-cart-footer">
                {!! tabler_icon('note', ['class' => 'icon text-secondary flex-shrink-0']) !!}
                <textarea
                    class="pos-order-note"
                    rows="1"
                    maxlength="255"
                    wire:model="payment.note"
                    placeholder="{{ trans('modules/product::product.order_note_placeholder') }}"
                    x-data
                    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; })"
                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                ></textarea>
            </div>
        </section>

        {{-- Checkout --}}
        <aside class="pos-checkout"
        x-data="{
            busy: false,
            {{-- One save at a time: a second click or F9 while saving does nothing --}}
            run(action) {
                if (this.busy) return;
                this.busy = true;
                $wire[action]().finally(() => { this.busy = false; });
            },
        }"
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
        @keydown.f9.window="if ($el.offsetParent !== null && $refs.pay) { $event.preventDefault(); $refs.pay.disabled || $refs.pay.click(); }"
        >
            <div class="pos-checkout-body">
                {{-- Customer --}}
                <div class="mb-3">
                    @if ($payment['customer_id'] ?? null)
                        <div class="pos-customer">
                            <span class="avatar avatar-sm bg-primary-lt flex-shrink-0">
                                {{ mb_substr($this->customer?->name ?? 'C', 0, 1) }}
                            </span>
                            <div class="min-w-0 flex-fill">
                                <div class="fw-medium text-truncate">{{ $this->customer?->name }}</div>
                                <div class="text-secondary small text-truncate">
                                    {{ $this->customer?->phone }}@if ($this->customer?->address) · {{ $this->customer?->address }}@endif
                                </div>
                            </div>
                            <button type="button" class="btn btn-icon btn-sm btn-ghost-secondary"
                                title="{{ __('core/base::general.edit') }}"
                                wire:click="$dispatch('show-modal-create-customer', { id: {{ (int) $payment['customer_id'] }}, tab: '{{ $tab_selected }}' })">
                                {!! tabler_icon('pencil') !!}
                            </button>
                            <button type="button" class="btn btn-icon btn-sm btn-ghost-secondary"
                                title="{{ trans('modules/product::product.close') }}"
                                wire:click="removeCustomer">
                                {!! tabler_icon('x') !!}
                            </button>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-1">
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
                                            wire:click="$dispatch('show-modal-create-customer', { id: 0, name: {{ \Illuminate\Support\Js::from((string) $search['customer']) }}, tab: '{{ $tab_selected }}' })"
                                            class="cursor-pointer"
                                        >
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">{{ trans('modules/product::product.customer_not_found') }}</span>
                                                <span class="badge bg-primary-lt">
                                                    {!! tabler_icon('plus', ['class' => 'icon-sm']) !!} {{ trans('core/base::general.add_new') }}
                                                </span>
                                            </div>
                                        </x-form::autocomplete.item>
                                    @endif
                                </x-form::autocomplete>
                            </div>
                            <button type="button"
                                class="btn btn-icon btn-ghost-secondary"
                                wire:click="$dispatch('show-modal-create-customer', { id: 0, tab: '{{ $tab_selected }}' })"
                                title="{{ trans('core/base::general.add_new') }}">
                                {!! tabler_icon('user-plus') !!}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Staff & channel --}}
                <div class="row g-2 mb-2">
                    <div class="col-6" wire:ignore>
                        <x-form::select
                            id="payment_user_id_{{ $tab_selected }}"
                            wire:model="payment_user_id"
                            :options="$lists['users']"
                            :label="trans('modules/product::product.sales_staff')"
                            tomselect
                        />
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-end gap-1">
                            <div class="flex-grow-1 min-w-0">
                                <x-form::select
                                    id="payment_sale_channel_id_{{ $tab_selected }}"
                                    wire:model="payment_sale_channel_id"
                                    :options="$lists['sale_channels']"
                                    :label="trans('modules/product::product.sales_channel')"
                                />
                            </div>
                            <button type="button"
                                class="btn btn-icon btn-ghost-secondary"
                                title="{{ ($payment['sale_channel_id'] ?? null) ? __('core/base::general.edit') : trans('core/base::general.add_new') }}"
                                wire:click="$dispatch('show-modal-create-sale-channel', { id: {{ $payment['sale_channel_id'] ?? 0 }} })"
                            >
                                {!! tabler_icon(($payment['sale_channel_id'] ?? null) ? 'pencil' : 'plus') !!}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Delivery: folded until it is needed --}}
                <div class="pos-delivery mb-3"
                    x-data="{ open: {{ ($payment_delivery['partner_delivery_id'] ?? null) || ($payment_delivery['code'] ?? null) ? 'true' : 'false' }} }">
                    <button type="button" class="pos-delivery-toggle" @click="open = !open" :aria-expanded="open">
                        {!! tabler_icon('truck-delivery', ['class' => 'icon']) !!}
                        <span>{{ trans('modules/product::product.delivery_partner_short') }}</span>
                        @if ($payment_delivery['partner_delivery_id'] ?? null)
                            <span class="badge bg-primary-lt">{{ ($lists['partner_deliveries'] ?? [])[$payment_delivery['partner_delivery_id']] ?? '' }}</span>
                        @endif
                        <span class="ms-auto" :class="open && 'rotate-180'">{!! tabler_icon('chevron-down', ['class' => 'icon']) !!}</span>
                    </button>
                    <div class="row g-2 pt-2" x-show="open" x-cloak>
                        <div class="col-6">
                            <div class="d-flex align-items-end gap-1">
                                <div class="flex-grow-1 min-w-0">
                                    <x-form::select
                                        :options="$lists['partner_deliveries'] ?? []"
                                        wire:model.live="payment_delivery.partner_delivery_id"
                                        :label="trans('modules/product::product.delivery_partner_short')"
                                    />
                                </div>
                                <button type="button"
                                    wire:click="$dispatch('show-modal-create-partner-delivery', { id: 0 })"
                                    title="{{ trans('core/base::general.add_new') }}"
                                    class="btn btn-icon btn-ghost-secondary">
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

                {{-- Totals, amount paid, payment method --}}
                @include('modules/product::payment.payment-view')
            </div>

            {{-- Always visible: amount due and the main action --}}
            <div class="pos-checkout-footer">
                <div class="pos-due">
                    <span>{{ __('modules/product::payment.customer_need_pay') }}</span>
                    <strong>{{ core_number_format($total_payment) }}</strong>
                </div>

                @if (($payment['id'] ?? null) && ($payment['status'] ?? null) !== 'draft')
                    <div class="pos-actions-row">
                        <button type="button" onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', ['id' => $payment['id']]) }}')" class="btn btn-outline-secondary">
                            {!! tabler_icon('printer') !!} {{ trans('modules/product::product.print_invoice') }}
                        </button>
                        <button type="button" wire:click="newPayment" class="btn btn-primary flex-fill">
                            {!! tabler_icon('plus') !!} {{ trans('modules/product::product.new_order') }}
                        </button>
                    </div>
                @else
                    <div class="pos-actions-row">
                        <button type="button"
                            class="btn btn-outline-secondary"
                            @click="run('saveDraft')"
                            wire:loading.attr="disabled"
                            title="{{ __('modules/product::payment.save_draft') }}"
                            @disabled(count((array)$products) === 0)
                        >
                            {!! tabler_icon('device-floppy') !!}
                            <span class="d-none d-xxl-inline">{{ __('modules/product::payment.save_draft') }}</span>
                        </button>
                        <button type="button"
                            class="btn btn-ghost-secondary btn-icon"
                            wire:click="$dispatch('show-modal-draft-list', { tab_id: '{{ $tab_selected }}' })"
                            title="{{ __('modules/product::payment.draft_list') }}"
                        >
                            {!! tabler_icon('files') !!}
                        </button>
                        <button type="button"
                            x-ref="pay"
                            @click="run('save')"
                            wire:loading.attr="disabled"
                            class="btn btn-primary btn-lg flex-fill pos-pay-btn"
                            @if(!(count((array)$products) > 0 && (($payment['value_payment'] ?? 0) >= (int)$total_payment || $isPendingMethod))) disabled @endif
                        >
                            <span wire:loading.remove wire:target="save">
                                {!! tabler_icon('check') !!} {{ __('modules/product::payment.checkout') }}
                                <kbd class="ms-2">F9</kbd>
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-1"></span> {{ trans('core/base::general.processing') }}
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        </aside>
    </div>

    {{-- Modals --}}
    @livewire('modules/product::payment.modal.modal-payment-type', compact("tab_selected"), "modules/product::v2.modal.payment-type-{$tab_selected}")
    {{-- Draft list and create-customer/channel/partner modals are shared, in tab.blade.php --}}

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('close-or-reset-tab', (event) => {
                    // event is an array of parameters in Livewire 3: event[0].tabId
                    let tabId = event[0]?.tabId || event.tabId;
                    if (!tabId) return;

                    let tabCount = document.querySelectorAll('.pos-tabs .pos-tab').length;
                    if (tabCount > 1) {
                        Livewire.dispatch('remove-payment-tab', { tabId: tabId });
                    }
                });
            });
        </script>
    @endpush
</div>

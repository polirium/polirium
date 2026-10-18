<div class="pos-app"
    x-data="{
        keepAlive: null,
        init() {
            // Initialize Keep-Alive system for POS page
            // Initialize Keep-Alive system for POS page
            // if (window.PoliriumKeepAlive) {
            //     this.keepAlive = new window.PoliriumKeepAlive({
            //         heartbeatInterval: 3 * 60 * 1000, // 3 minutes (more frequent for POS)
            //         csrfRefreshInterval: 15 * 60 * 1000, // 15 minutes
            //         debug: false,
            //         onSessionExpired: () => {
            //             // Custom handler for POS - save draft before redirect
            //             if (confirm('Phiên làm việc đã hết hạn. Nhấn OK để đăng nhập lại.')) {
            //                 window.location.href = '/admin/login?redirect=' + encodeURIComponent(window.location.pathname);
            //             }
            //         },
            //         onReconnected: () => {
            //             // Refresh Livewire components after reconnection
            //             if (window.Livewire) {
            //                 window.Livewire.dispatch('refresh-payment');
            //             }
            //         }
            //     });
            //     this.keepAlive.start();
            // }
        },
        destroy() {
            if (this.keepAlive) {
                this.keepAlive.stop();
            }
        }
    }"
    x-init="init()"
    @beforeunmount.window="destroy()"
>
    {{-- Top bar: product search, invoice tabs, actions --}}
    <div class="pos-topbar">
        {{-- Product search: arrows move, Enter adds the highlighted (or exact code) match, F3 focuses --}}
        <div class="pos-search"
            x-data="{
                open: false,
                active: 0,
                items() { return [...$refs.list.querySelectorAll('[data-product-id]')]; },
                move(step) {
                    const items = this.items();
                    if (!items.length) return;
                    this.open = true;
                    this.active = (this.active + step + items.length) % items.length;
                    items[this.active].scrollIntoView({ block: 'nearest' });
                },
                pick() {
                    const item = this.items()[this.active];
                    if (this.open && item && this.active > 0) {
                        $wire.selectProduct(Number(item.dataset.productId));
                    } else {
                        $wire.selectFirstMatch($refs.input.value);
                    }
                },
                focus() { $refs.input.focus(); $refs.input.select(); },
            }"
            x-init="$nextTick(() => focus())"
            @keydown.f3.window.prevent="focus()"
            @pos-search-reset.window="open = false; active = 0; $refs.input.value = ''; focus()"
            @click.outside="open = false"
        >
            <span class="pos-search-icon">{!! tabler_icon('search', ['class' => 'icon']) !!}</span>
            <input type="text"
                x-ref="input"
                class="form-control"
                wire:model.live.debounce.250ms="search"
                placeholder="{{ __('modules/product::payment.search_placeholder') }}"
                autocomplete="off"
                @input="open = true; active = 0"
                @focus="open = true"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="pick()"
                @keydown.escape="open = false"
            >
            <span class="pos-search-loading" wire:loading.delay wire:target="search,selectProduct,selectFirstMatch">
                <span class="spinner-border spinner-border-sm text-primary"></span>
            </span>

            <div class="pos-search-results" x-ref="list" x-show="open && $wire.search" x-cloak>
                @if (!empty($this->products) && $this->products->count())
                    @foreach ($this->products as $index => $item)
                        <button type="button"
                            class="pos-search-item"
                            data-product-id="{{ $item->id }}"
                            :class="{ 'active': active === {{ $index }} }"
                            @mouseenter="active = {{ $index }}"
                            wire:click="selectProduct({{ $item->id }})"
                            wire:key="product-search-{{ $item->id }}"
                        >
                            <div class="min-w-0 flex-fill">
                                <div class="fw-medium text-truncate">{{ $item->name }}</div>
                                <div class="small text-secondary">{{ $item->code }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-semibold">{{ core_number_format($item->price) }}</div>
                                <div class="small">
                                    @if ($item->type === 'service')
                                        <span class="text-secondary">{{ trans('modules/product::product.services') }}</span>
                                    @else
                                        <span @class(['text-danger' => $item->amount <= 0, 'text-secondary' => $item->amount > 0])>
                                            {{ trans('modules/product::product.inventory') }}: {{ core_number_format($item->amount) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                    <div class="pos-search-hint">{{ __('modules/product::payment.search_hint') }}</div>
                @elseif (!empty($search))
                    <div class="pos-search-empty">{{ trans('modules/product::product.no_products_found') }}</div>
                @endif
            </div>
        </div>

        {{-- Invoice tabs --}}
        <div class="pos-tabs" role="tablist">
            @php $invoiceCount = 0; @endphp
            @foreach ($tab as $key => $item)
                @php
                    $itemId = $item['id'];
                    $itemType = $item['type'] ?? 'invoice';
                    if ($itemType === 'invoice') $invoiceCount++;
                    $displayLabel = $itemType === 'invoice' ? trans('modules/product::product.invoice') . ' ' . $invoiceCount : ($item['label'] ?? 'Refund');
                @endphp
                <div @class(['pos-tab', 'active' => $itemId == $tab_selected]) role="presentation" wire:key="pos-tab-{{ $itemId }}">
                    <button type="button" class="pos-tab-label" role="tab" wire:click="tabSelected('{{ $itemId }}')">
                        @if ($itemType === 'refund')
                            {!! tabler_icon('arrow-back-up', ['class' => 'icon icon-sm text-danger']) !!}
                        @endif
                        {{ $displayLabel }}
                    </button>
                    @if (count($tab) > 1)
                        <button type="button"
                            class="pos-tab-close"
                            x-on:click.stop="if (confirm(@js(trans('modules/product::product.close_invoice_confirm')))) { $wire.removePayment('{{ $itemId }}') }"
                            title="{{ trans('modules/product::product.close') }}"
                        >
                            {!! tabler_icon('x', ['class' => 'icon']) !!}
                        </button>
                    @endif
                </div>
            @endforeach
            <button type="button"
                wire:click="addNewPayment"
                class="btn btn-icon btn-sm btn-ghost-primary flex-shrink-0"
                title="{{ trans('modules/product::product.add_new_invoice') }}"
            >
                {!! tabler_icon('plus') !!}
            </button>
        </div>

        {{-- Actions --}}
        <div class="pos-actions">
            <button type="button"
                class="btn btn-sm btn-ghost-secondary"
                title="{{ trans('modules/product::product.refund') }}"
                onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-select-refund-invoice')).show()"
            >
                {!! tabler_icon('arrow-back-up', ['class' => 'icon']) !!}
                <span class="d-none d-xl-inline">{{ trans('modules/product::product.refund') }}</span>
            </button>
            <div class="dropdown">
                <button type="button"
                    class="btn btn-sm btn-ghost-secondary"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="{{ trans('modules/product::product.management') }}"
                >
                    {!! tabler_icon('menu-2', ['class' => 'icon']) !!}
                    <span class="d-none d-xl-inline">{{ trans('modules/product::product.management') }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('products.index') }}" target="_blank">
                        {!! tabler_icon('package', ['class' => 'icon icon-inline me-2']) !!}
                        {{ trans('modules/product::product.product_management') }}
                    </a>
                    <a class="dropdown-item" href="{{ route('accountings.payment.index') }}" target="_blank">
                        {!! tabler_icon('file-invoice', ['class' => 'icon icon-inline me-2']) !!}
                        {{ trans('modules/product::product.invoice_management') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('core.index') }}">
                        {!! tabler_icon('home', ['class' => 'icon icon-inline me-2']) !!}
                        {{ trans('modules/product::product.admin_home') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="pos-content">
        @foreach ($tab as $item)
            @php $itemId = $item['id']; $itemType = $item['type'] ?? 'invoice'; @endphp
            <div @class([
                    "h-100",
                    "d-none"    => $itemId != $tab_selected,
                    "d-block"   => $itemId == $tab_selected,
                ])
                wire:key="tab-content-{{ $itemId }}"
            >
                @if($itemType === 'invoice')
                    @livewire('modules/product::payment.payment', [
                        "tab_selected" => $itemId,
                    ], key("modules/product::payment.payment-{$itemId}"))
                @elseif($itemType === 'refund')
                    @livewire('modules/product::refund.refund-component', [
                        "payment_id" => $item['data']['payment_id'] ?? 0,
                        "tab_id" => $itemId,
                    ], key("modules/product::refund.refund-component-{$itemId}"))
                @endif
            </div>
        @endforeach
    </div>

    {{-- Global Modals --}}
    @livewire('modules/product::payment.modal.modal-create-sale-channel')
    @livewire('modules/product::payment.modal.modal-create-partner-delivery')
    @livewire('modules/customer::index.modal.modal-create-customer')
    @livewire('modules/customer::index.modal.modal-create-customer-group')

    {{-- Select Refund Invoice Modal (Shared) --}}
    @livewire('modules/product::payment.modal.modal-select-refund-invoice')
</div>

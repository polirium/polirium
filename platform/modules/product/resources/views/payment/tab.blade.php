<div class="h-100 d-flex flex-column"
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
    {{-- Search Bar & Tabs --}}
    <div class="bg-white border-bottom px-3 py-2 d-flex align-items-center gap-2">
        {{-- Product Search --}}
        <div style="min-width: 300px;">
            <x-form::autocomplete
                wire:model.live.debounce.300ms="search"
                placeholder="{{ trans('modules/product::product.search_product_placeholder') }}"
                class="mb-0"
            >
                @if (!empty($this->products))
                    @foreach ($this->products as $item)
                        <x-form::autocomplete.item
                            class="cursor-pointer"
                            wire:click="selectProduct({{ $item->id }})"
                            wire:key="product-search-{{ $item->id }}"
                        >
                            <div class="d-flex justify-content-between">
                                <span class="fw-medium">{{ $item->name }}</span>
                                <span class="text-primary fw-bold">{{ core_number_format($item->price) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span>{{ $item->code }}</span>
                                @if($item->type === 'service')
                                    <span><span class="badge bg-blue-lt badge-sm">{{ trans('modules/product::product.services') }}</span></span>
                                @else
                                    <span>
                                        {{ trans('modules/product::product.inventory') }}:
                                        <span @class(['text-danger fw-bold' => $item->amount <= 0])>
                                            {{ core_number_format($item->amount) }}
                                        </span>
                                    </span>
                                @endif
                            </div>
                        </x-form::autocomplete.item>
                    @endforeach
                @elseif(!empty($search))
                    <x-form::autocomplete.item class="text-muted text-center py-2">
                        {{ trans('modules/product::product.no_products_found') }}
                    </x-form::autocomplete.item>
                @endif
            </x-form::autocomplete>
        </div>

        {{-- Divider --}}
        <div class="vr mx-2 text-muted opacity-25"></div>

        {{-- Tabs --}}
        <div class="nav nav-tabs border-0 flex-nowrap overflow-x-auto custom-scrollbar flex-grow-1" role="tablist">
            @php $invoiceCount = 0; @endphp
            @foreach ($tab as $key => $item)
                @php
                    $itemId = $item['id'];
                    $itemType = $item['type'] ?? 'invoice';
                    if ($itemType === 'invoice') $invoiceCount++;
                    $displayLabel = $itemType === 'invoice' ? trans('modules/product::product.invoice') . ' ' . $invoiceCount : ($item['label'] ?? 'Refund');
                @endphp
                <div class="nav-item d-flex align-items-center me-2" role="presentation">
                    <button type="button"
                        @class([
                            "nav-link text-nowrap d-flex align-items-center justify-content-between gap-2 px-3 py-2",
                            "active fw-bold border bg-white" => $itemId == $tab_selected,
                            "border-0 bg-transparent text-muted" => $itemId != $tab_selected,
                        ])
                        wire:click="tabSelected('{{ $itemId }}')"
                        style="border-radius: 6px; transition: all 0.2s;"
                    >
                        <span>
                            @if($itemType === 'refund') <span class="text-danger">{!! tabler_icon('arrow-back-up', ['class' => 'icon icon-inline me-1']) !!}</span> @endif
                            {{ $displayLabel }}
                        </span>

                        @if(count($tab) > 1)
                            <span
                                class="close-tab-btn d-inline-flex align-items-center justify-content-center ms-1 flex-shrink-0"
                                style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; border-radius: 50%; background: #d63939; color: #fff; padding: 0; cursor: pointer; transition: all 0.2s;"
                                x-on:click.stop="if(confirm('{{ trans('modules/product::product.close_invoice_confirm') }}')) { $wire.removePayment('{{ $itemId }}') }"
                                title="{{ trans('modules/product::product.close') }}"
                            >
                                {!! tabler_icon('x', ['class' => 'icon', 'style' => 'width: 12px; height: 12px; stroke-width: 3;']) !!}
                            </span>
                        @endif
                    </button>
                </div>
            @endforeach
            <div class="nav-item d-flex align-items-center ms-1">
                <button type="button"
                    wire:click="addNewPayment"
                    class="btn btn-icon btn-primary rounded-circle flex-shrink-0"
                    style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; padding: 0;"
                    title="{{ trans('modules/product::product.add_new_invoice') }}"
                >
                    {!! tabler_icon('plus', ['style' => 'width: 20px; height: 20px;']) !!}
                </button>
            </div>
        </div>

        {{-- Right Actions --}}
        <div class="d-flex align-items-center gap-2 border-start ps-2">
            {{-- Admin Navigation Dropdown --}}
            <div class="dropdown">
                <button type="button"
                    class="btn btn-icon btn-ghost-primary"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="{{ trans('modules/product::product.management') }}"
                >
                    {!! tabler_icon('layout-grid') !!}
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
                    <a class="dropdown-item" href="{{ route('core.index') }}" target="_blank">
                        {!! tabler_icon('home', ['class' => 'icon icon-inline me-2']) !!}
                        {{ trans('modules/product::product.admin_home') }}
                    </a>
                </div>
            </div>

            {{-- Refund Button --}}
            <button type="button"
                class="btn btn-icon btn-ghost-danger"
                title="{{ trans('modules/product::product.refund') }}"
                onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-select-refund-invoice')).show()"
            >
                {!! tabler_icon('arrow-back-up') !!}
            </button>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="flex-grow-1 bg-muted-lt p-3 overflow-hidden">
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

<div class="h-100 d-flex flex-column"
    x-data="{
        keepAlive: null,
        init() {
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
    {{-- Connection Status Indicator --}}
    {{-- Connection Status Indicator Removed --}}

    {{-- Tab Bar --}}
    <div class="bg-white border-bottom px-3 py-2 d-flex align-items-center gap-2">
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
                            "nav-link text-nowrap d-flex align-items-center justify-content-between gap-2",
                            "active fw-bold" => $itemId == $tab_selected,
                        ])
                        wire:click="tabSelected('{{ $itemId }}')"
                        style="height: 42px; border-radius: 6px 6px 0 0; min-width: 140px;"
                    >
                        <span>
                            @if($itemType === 'refund')
                                <span class="text-danger me-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-back-up" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M9 14l-4 -4l4 -4"></path>
                                        <path d="M5 10h11a4 4 0 1 1 0 8h-1"></path>
                                    </svg>
                                </span>
                            @endif
                            {{ $displayLabel }}
                        </span>

                        @if(count($tab) > 1)
                            <span
                                class="close-tab-btn d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                x-on:click.stop="if(confirm('{{ trans('modules/product::product.close_invoice_confirm') }}')) { $wire.removePayment('{{ $itemId }}') }"
                                title="{{ trans('modules/product::product.close') }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="12" height="12" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M18 6l-12 12"></path>
                                    <path d="M6 6l12 12"></path>
                                </svg>
                            </span>
                        @endif
                    </button>
                </div>
            @endforeach
            <li class="nav-item d-flex align-items-center ms-2">
                <button type="button"
                    wire:click="addNewPayment"
                    class="add-tab-btn d-flex align-items-center justify-content-center flex-shrink-0"
                    title="{{ trans('modules/product::product.add_new_invoice') }}"
                    style="width: 32px; height: 32px; min-width: 32px; min-height: 32px;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 5l0 14"></path>
                        <path d="M5 12l14 0"></path>
                    </svg>
                </button>
            </li>
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
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-back-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M9 14l-4 -4l4 -4"></path>
                    <path d="M5 10h11a4 4 0 1 1 0 8h-1"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- Content Area - Render ONLY active tab component to ensure isolation --}}
    <div class="flex-grow-1 bg-muted-lt p-3 overflow-auto" wire:key="tab-wrapper-{{ $tab_selected }}">

        {{-- We use a dynamic component key based on tab ID to force Livewire to see it as a fresh component --}}
        {{-- But state is restored from session in mount() --}}

        @php
            // Find current tab type
            $currentTab = collect($tab)->firstWhere('id', $tab_selected);
            $currentType = $currentTab['type'] ?? 'invoice';
        @endphp

        <div wire:key="wrapper-{{ $tab_selected }}">
            @if($currentType === 'invoice')
                @livewire('modules/product::payment.payment-v2-component', [
                    "tab_selected" => $tab_selected,
                ], key("payment-v2-component-{$tab_selected}"))
            @elseif($currentType === 'refund')
                @livewire('modules/product::refund.refund-component', [
                    "payment_id" => $currentTab['data']['payment_id'] ?? 0,
                    "tab_id" => $tab_selected,
                ], key("modules/product::refund.refund-component-{$tab_selected}"))
            @endif
        </div>
    </div>

    {{-- Global Modals --}}
    @livewire('modules/product::payment.modal.modal-create-sale-channel', [], key('global-sale-channel-modal'))
    @livewire('modules/product::payment.modal.modal-create-partner-delivery', [], key('global-partner-delivery-modal'))
    @livewire('modules/customer::index.modal.modal-create-customer', [], key('global-customer-modal'))
    @livewire('modules/customer::index.modal.modal-create-customer-group', [], key('global-customer-group-modal'))
    @livewire('modules/product::payment.draft-payment-list', [], key('global-draft-list-modal'))

    {{-- Select Refund Invoice Modal (Shared) --}}
    @livewire('modules/product::payment.modal.modal-select-refund-invoice')
</div>

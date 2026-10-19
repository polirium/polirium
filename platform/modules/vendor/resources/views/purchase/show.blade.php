<x-ui.layouts::app>
    <x-slot:title>{{ __('Chi tiết phiếu nhập #:code', ['code' => $purchase->code]) }}</x-slot:title>

    <x-slot:subtitle>
        {{ $purchase->created_at->format('d/m/Y H:i') }} | {{ $purchase->userCreated->name ?? '-' }}
    </x-slot:subtitle>

    @php
        $refundId = $purchase->refund_id ?? ($purchase->relationLoaded('refunds') ? $purchase->refunds->last()?->id : null);
        $canManageRefund = $refundId ? auth()->user()?->can('vendors.refunds.edit') : auth()->user()?->can('vendors.refunds.create');
        $row = $purchase;
        $id = $row->id;
        $hideToolbar = true;
    @endphp

    <div class="d-flex justify-content-end mb-3">
        <div class="btn-list">
            @can('vendors.purchases.create')
                <x-ui::button color="secondary" outline icon="copy" :href="route('vendors.purchases.order', ['copy_id' => $purchase->id])">
                    {{ __('core/base::general.copy') }}
                </x-ui::button>
            @endcan

            @can('vendors.purchases.index')
                <x-ui::button color="secondary" outline icon="file-export" :href="route('vendors.purchases.export', $purchase->id)" target="_blank">
                    {{ __('Xuất file') }}
                </x-ui::button>
            @endcan

            @if ($canManageRefund)
                <x-ui::button color="secondary" outline icon="arrow-back-up" :href="route('vendors.purchases.refund', ['id' => $refundId ?? 0, 'purchase_id' => $purchase->id])">
                    {{ __('Trả hàng nhập') }}
                </x-ui::button>
            @endif

            @can('vendors.purchases.edit')
                <x-ui::button color="primary" icon="pencil" :href="route('vendors.purchases.order', $purchase->id)">
                    {{ __('core/base::general.edit') }}
                </x-ui::button>
            @endcan
        </div>
    </div>

    @include('modules/vendor::purchase.index.datatable.detail')

    @livewire('modules/product::index.modal.modal-create-product')
</x-ui.layouts::app>

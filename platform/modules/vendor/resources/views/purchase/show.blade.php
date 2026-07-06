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
            @can('vendors.purchases.edit')
                <a href="{{ route('vendors.purchases.order', $purchase->id) }}" class="btn btn-warning">
                    {!! tabler_icon('pencil', ['class' => 'icon']) !!}
                    {{ trans('modules/vendor::purchase.edit') ?? 'Sửa' }}
                </a>
            @endcan

            @if ($canManageRefund)
                <a href="{{ route('vendors.purchases.refund', ['id' => $refundId ?? 0, 'purchase_id' => $purchase->id]) }}" class="btn btn-danger">
                    {!! tabler_icon('arrow-back-up', ['class' => 'icon']) !!}
                    {{ __('Trả hàng nhập') }}
                </a>
            @endif

            @can('vendors.purchases.create')
                <a href="{{ route('vendors.purchases.order', ['copy_id' => $purchase->id]) }}" class="btn btn-secondary">
                    {!! tabler_icon('copy', ['class' => 'icon']) !!}
                    {{ __('Sao chép') }}
                </a>
            @endcan

            @can('vendors.purchases.index')
                <a href="{{ route('vendors.purchases.export', $purchase->id) }}" class="btn btn-primary" target="_blank">
                    {!! tabler_icon('file-export', ['class' => 'icon']) !!}
                    {{ __('Xuất file') }}
                </a>
            @endcan
        </div>
    </div>

    @include('modules/vendor::purchase.index.datatable.detail')
</x-ui.layouts::app>

<x-ui::row-actions>
    {{-- View Details --}}
    <x-ui::action-icon
        icon="eye"
        :label="trans('modules/accounting::accounting.view_detail')"
        href="{{ route('accountings.payment.show', ['id' => $row->id]) }}"
    />

    {{-- Print --}}
    <x-ui::action-icon
        icon="printer"
        :label="trans('modules/accounting::accounting.print')"
        onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', ['id' => $row->id]) }}')"
    />

    {{-- Complete --}}
    @can('accountings.edit')
        @if($shouldShowCompleteButton ?? false)
            <x-ui::action-icon
                icon="check"
                :label="trans('modules/accounting::accounting.complete')"
                data-bs-toggle="modal"
                data-bs-target="#modal-action-complete"
                wire:click="setTargetId({{ $row->id }})"
            />
        @endif
    @endcan

    {{-- Cancel (confirmed in #modal-action-cancel) --}}
    @can('accountings.cancel')
        @if(!in_array($row->status, ['cancelled', 'cancel', 'delivery_failed']))
            <x-ui::action-icon
                icon="ban"
                danger
                :label="trans('modules/accounting::accounting.cancel_invoice')"
                data-bs-toggle="modal"
                data-bs-target="#modal-action-cancel"
                wire:click="setTargetId({{ $row->id }})"
            />
        @endif
    @endcan
</x-ui::row-actions>

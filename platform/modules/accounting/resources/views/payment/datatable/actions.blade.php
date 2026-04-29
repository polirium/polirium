@php
    use VigStudio\TablerIcons\TablerIcon;
    $iconStyle = ['class' => 'icon dropdown-item-icon'];
@endphp
<div class="dropdown">
    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-boundary="viewport" data-bs-toggle="dropdown">
        {{ trans('core/base::general.action') }}
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        {{-- View Details --}}
        <a class="dropdown-item" href="{{ route('accountings.payment.show', ['id' => $row->id]) }}">
            {!! TablerIcon::render('eye', $iconStyle) !!}
            {{ trans('modules/accounting::accounting.view_detail') }}
        </a>

        {{-- Complete --}}
        @can('accountings.edit')
            @if($shouldShowCompleteButton ?? false)
                <button
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-action-complete"
                    wire:click="setTargetId({{ $row->id }})"
                >
                    {!! TablerIcon::render('check', $iconStyle) !!}
                    {{ trans('modules/accounting::accounting.complete') }}
                </button>
            @endif
        @endcan

        {{-- Print --}}
        <button
            class="dropdown-item"
            onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', ['id' => $row->id]) }}')"
        >
            {!! TablerIcon::render('printer', $iconStyle) !!}
            {{ trans('modules/accounting::accounting.print') }}
        </button>

        {{-- Cancel --}}
        @can('accountings.cancel')
            @if(!in_array($row->status, ['cancelled', 'cancel', 'delivery_failed']))
                <button
                    class="dropdown-item text-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-action-cancel"
                    wire:click="setTargetId({{ $row->id }})"
                >
                    {!! TablerIcon::render('trash', $iconStyle) !!}
                    {{ trans('modules/accounting::accounting.cancel_invoice') }}
                </button>
            @endif
        @endcan
    </div>
</div>


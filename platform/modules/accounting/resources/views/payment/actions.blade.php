@php
    use VigStudio\TablerIcons\TablerIcon;
    // Force style override using !important because SVG attributes are duplicated
    $iconStyle = [
        'style' => 'width: 16px !important; height: 16px !important; stroke-width: 1.5 !important;',
        'class' => 'me-2'
    ];
@endphp
<div class="d-flex gap-1 flex-wrap">
    <!-- DEBUG INFO: Status is '{{ $status }}' -->

    {{-- Quick Edit --}}
    @if(auth()->user()->can('accountings.edit'))
        <button
            type="button"
            class="btn btn-sm d-inline-flex align-items-center"
            onclick="Livewire.dispatch('show-modal-quick-update', { id: {{ $paymentId }} })"
        >
             {!! TablerIcon::render('pencil', $iconStyle) !!}
             {{ trans('modules/accounting::accounting.quick_edit') }}
        </button>
    @endif


    {{-- Approve Button --}}
    @if($status === 'temp')
        <button
            type="button"
            class="btn btn-primary btn-sm d-inline-flex align-items-center"
            data-bs-toggle="modal"
            data-bs-target="#modal-payment-approve-{{ $paymentId }}"
        >
            {!! TablerIcon::render('check', $iconStyle) !!}
            {{ trans('modules/accounting::accounting.approve') }}
        </button>

        @teleport('body')
        <x-ui::confirm-modal
            id="modal-payment-approve-{{ $paymentId }}"
            title="Duyệt hóa đơn"
            message="Bạn có chắc chắn muốn duyệt hóa đơn này? Kho sẽ được trừ và doanh thu sẽ được ghi nhận."
            confirm-button="Đồng ý, Duyệt ngay"
            confirm-color="primary"
            icon="check"
            wire:click="approve"
        />
        @endteleport
    @endif

    {{-- Complete Button (hoàn thành = thu tiền + đánh dấu hoàn thành) --}}
    @if(!$completedAt && !in_array($status, ['cancelled', 'cancel', 'delivery_failed']))
        <button
            type="button"
            class="btn btn-primary btn-sm d-inline-flex align-items-center"
            data-bs-toggle="modal"
            data-bs-target="#modal-payment-complete-{{ $paymentId }}"
        >
            {!! TablerIcon::render('check', $iconStyle) !!}
            {{ trans('modules/accounting::accounting.complete') }}
        </button>

        @teleport('body')
        <x-ui::confirm-modal
            id="modal-payment-complete-{{ $paymentId }}"
            title="Hoàn thành đơn hàng"
            message="Xác nhận hoàn thành đơn hàng này? Đơn sẽ được thu tiền đầy đủ."
            confirm-button="Hoàn thành"
            confirm-color="primary"
            icon="check"
            wire:click="complete"
        />
        @endteleport
    @endif

    @can('accountings.cancel')
        {{-- Delivery Failed Button --}}
        @if(!in_array($status, ['cancelled', 'cancel', 'delivery_failed']))
            <button
                type="button"
                class="btn btn-warning btn-sm d-inline-flex align-items-center"
                data-bs-toggle="modal"
                data-bs-target="#modal-payment-failed-{{ $paymentId }}"
            >
                {!! TablerIcon::render('truck-off', $iconStyle) !!}
                {{ __('Không giao được') }}
            </button>

            @teleport('body')
            <x-ui::confirm-modal
                id="modal-payment-failed-{{ $paymentId }}"
                title="Đánh dấu không giao được"
                message="Bạn có chắc chắn muốn đánh dấu không giao được? Thao tác này sẽ hoàn lại tồn kho."
                confirm-button="Xác nhận"
                confirm-color="warning"
                icon="alert-triangle"
                wire:click="markAsDeliveryFailed"
            />
            @endteleport
        @endif

        {{-- Cancel Button --}}
        @if(!in_array($status, ['cancelled', 'cancel']))
            <button
                type="button"
                class="btn btn-danger btn-sm d-inline-flex align-items-center"
                data-bs-toggle="modal"
                data-bs-target="#modal-payment-cancel-{{ $paymentId }}"
            >
                {!! TablerIcon::render('trash', $iconStyle) !!}
                {{ trans('modules/accounting::accounting.cancel') }}
            </button>

            @teleport('body')
            <x-ui::confirm-modal
                id="modal-payment-cancel-{{ $paymentId }}"
                title="Hủy hóa đơn"
                message="Bạn có chắc chắn muốn hủy hóa đơn này? Thao tác này sẽ hủy hóa đơn và hoàn lại tồn kho."
                confirm-button="Đồng ý, Hủy đơn"
                confirm-color="danger"
                icon="alert-circle"
                wire:click="cancel"
            />
            @endteleport
        @endif
    @endcan

    {{-- Copy Button --}}
    @can('accountings.create')
    <button
        type="button"
        wire:click="copyInvoice"
        class="btn btn-sm d-inline-flex align-items-center"
    >
        {!! TablerIcon::render('copy', $iconStyle) !!}
        {{ trans('modules/accounting::accounting.copy') }}
    </button>
    @endcan

    {{-- Export Button --}}
    @can('accountings.export')
    <a
        href="{{ route('accountings.payment.export', $paymentId) }}"
        class="btn btn-sm d-inline-flex align-items-center"
        target="_blank"
    >
        {!! TablerIcon::render('file-export', $iconStyle) !!}
        {{ trans('modules/accounting::accounting.export_file') }}
    </a>
    @endcan
</div>

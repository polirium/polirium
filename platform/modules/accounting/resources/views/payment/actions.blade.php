<div class="d-flex gap-2 flex-wrap">
    <!-- DEBUG INFO: Status is '{{ $status }}' -->

    @can('accountings.cancel')
        {{-- Cancel Button --}}
        @if(!in_array($status, ['cancelled', 'cancel']))
            <x-ui::button
                color="warning"
                outline
                size="sm"
                icon="ban"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#modal-payment-cancel-{{ $paymentId }}"
            >
                {{ trans('modules/accounting::accounting.cancel') }}
            </x-ui::button>

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

        {{-- Delivery Failed Button --}}
        @if(!in_array($status, ['cancelled', 'cancel', 'delivery_failed']))
            <x-ui::button
                color="warning"
                outline
                size="sm"
                icon="truck-off"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#modal-payment-failed-{{ $paymentId }}"
            >
                {{ __('Không giao được') }}
            </x-ui::button>

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
    @endcan

    {{-- Approve Button --}}
    @if($status === 'temp')
        <x-ui::button
            color="secondary"
            outline
            size="sm"
            icon="check"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#modal-payment-approve-{{ $paymentId }}"
        >
            {{ trans('modules/accounting::accounting.approve') }}
        </x-ui::button>

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
        <x-ui::button
            color="secondary"
            outline
            size="sm"
            icon="check"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#modal-payment-complete-{{ $paymentId }}"
        >
            {{ trans('modules/accounting::accounting.complete') }}
        </x-ui::button>

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

    {{-- Quick Edit --}}
    @if(auth()->user()->can('accountings.edit'))
        <x-ui::button
            color="secondary"
            outline
            size="sm"
            icon="pencil"
            type="button"
            onclick="Livewire.dispatch('show-modal-quick-update', { id: {{ $paymentId }} })"
        >
            {{ trans('modules/accounting::accounting.quick_edit') }}
        </x-ui::button>
    @endif

    {{-- Copy Button --}}
    @can('accountings.create')
        <x-ui::button
            color="secondary"
            outline
            size="sm"
            icon="copy"
            type="button"
            wire:click="copyInvoice"
        >
            {{ trans('modules/accounting::accounting.copy') }}
        </x-ui::button>
    @endcan

    {{-- Export Button --}}
    @can('accountings.export')
        <x-ui::button
            color="secondary"
            outline
            size="sm"
            icon="file-export"
            href="{{ route('accountings.payment.export', $paymentId) }}"
            target="_blank"
        >
            {{ trans('modules/accounting::accounting.export_file') }}
        </x-ui::button>
    @endcan
</div>

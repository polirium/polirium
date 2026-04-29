<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/accounting::accounting.invoice.name') }}</h3>

    <div class="card-actions">
        <div class="btn-list">
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                {{ trans('modules/accounting::accounting.selected') }} <span x-text="count">0</span> {{ trans('modules/accounting::accounting.voucher') }}
            </span>

            @can('accountings.edit')
                <x-ui::button
                    color="success"
                    size="sm"
                    icon="check"
                    class="btn btn-bulk-complete"
                    x-show="count > 0"
                    style="display: none;"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-bulk-complete"
                >
                    {{ trans('modules/accounting::accounting.complete') }}
                </x-ui::button>
            @endcan

            @can('accountings.cancel')
                <x-ui::button
                    color="warning"
                    size="sm"
                    icon="ban"
                    class="btn btn-bulk-cancel"
                    x-show="count > 0"
                    style="display: none;"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-bulk-cancel"
                >
                    {{ trans('modules/accounting::accounting.cancel_order') }}
                </x-ui::button>
            @endcan

            @can('sales.orders.delete')
                 <x-ui::button
                    color="danger"
                    size="sm"
                    icon="trash"
                    class="btn btn-bulk-delete"
                    x-show="count > 0"
                    style="display: none;"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-bulk-delete"
                >
                    {{ trans('modules/accounting::accounting.delete') }}
                </x-ui::button>
            @endcan
            @can('accountings.export')
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        {!! tabler_icon('file-export', ['class' => 'icon']) !!}
                        {{ __('Xuất Excel') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <button class="dropdown-item" wire:click="exportOverview">
                            {!! tabler_icon('table', ['class' => 'icon dropdown-item-icon']) !!}
                            {{ __('Hóa đơn Tổng quan') }}
                        </button>
                        <button class="dropdown-item" wire:click="exportDetail">
                            {!! tabler_icon('list-details', ['class' => 'icon dropdown-item-icon']) !!}
                            {{ __('Hóa đơn Chi tiết') }}
                        </button>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>

{{-- Modals --}}
<x-ui::confirm-modal
    id="modal-bulk-complete"
    title="{{ trans('modules/accounting::accounting.confirm_complete') }}"
    message="{{ trans('modules/accounting::accounting.confirm_complete_selected') }}"
    confirm-button="{{ trans('modules/accounting::accounting.complete') }}"
    confirm-color="success"
    wire:click="bulkComplete"
/>

<x-ui::confirm-modal
    id="modal-bulk-cancel"
    title="{{ trans('modules/accounting::accounting.confirm_cancel_order') }}"
    message="{{ trans('modules/accounting::accounting.confirm_cancel_selected') }}"
    confirm-button="{{ trans('modules/accounting::accounting.cancel_order_small') }}"
    confirm-color="warning"
    wire:click="bulkCancel"
/>

<x-ui::confirm-modal
    id="modal-bulk-delete"
    title="{{ trans('modules/accounting::accounting.confirm_delete_order') }}"
    message="{{ trans('modules/accounting::accounting.confirm_delete_selected') }}"
    confirm-button="{{ trans('modules/accounting::accounting.delete_order') }}"
    confirm-color="danger"
    wire:click="bulkDelete"
/>

{{-- Single Row Modals --}}
<x-ui::confirm-modal
    id="modal-action-complete"
    title="{{ trans('modules/accounting::accounting.confirm_complete') }}"
    message="{{ trans('modules/accounting::accounting.confirm_complete_order') }}"
    confirm-button="{{ trans('modules/accounting::accounting.complete') }}"
    confirm-color="success"
    wire:click="commitComplete"
/>

<x-ui::confirm-modal
    id="modal-action-cancel"
    title="{{ trans('modules/accounting::accounting.confirm_cancel_invoice') }}"
    message="{{ trans('modules/accounting::accounting.confirm_cancel_invoice_msg') }}"
    confirm-button="{{ trans('modules/accounting::accounting.cancel_invoice') }}"
    confirm-color="danger"
    wire:click="commitDelete"
/>

<div class="card-body border-bottom py-3">
    <div class="d-flex">
        <div class="text-muted">
            {{-- Spacer --}}
        </div>
        <div class="ms-auto text-muted">
            <div class="d-flex align-items-center gap-4 text-nowrap">
                <div class="d-flex flex-column align-items-end">
                    <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/accounting::accounting.total_goods') }}</span>
                    <span class="fw-bold fs-3">{{ core_number_format($this->totals['total_cost']) }}</span>
                </div>
                <div class="vr"></div>
                <div class="d-flex flex-column align-items-end">
                    <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/accounting::accounting.discount') }}</span>
                    <span class="fw-bold fs-3">{{ core_number_format($this->totals['discount_value']) }}</span>
                </div>
                <div class="vr"></div>
                <div class="d-flex flex-column align-items-end">
                    <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/accounting::accounting.customer_refundable') }}</span>
                    <span class="fw-bold fs-3">{{ core_number_format($this->totals['total_need_pay']) }}</span>
                </div>
                <div class="vr"></div>
                <div class="d-flex flex-column align-items-end">
                    <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/accounting::accounting.customer_paid') }}</span>
                    <span class="fw-bold fs-3">{{ core_number_format($this->totals['total_paid']) }}</span>
                </div>
                <div class="vr"></div>
                <div class="d-flex flex-column align-items-end">
                    <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/accounting::accounting.remaining_receivable') }}</span>
                    <span class="fw-bold fs-3 text-danger">{{ core_number_format($this->totals['total_remaining']) }}</span>
                </div>
                @can('accountings.dashboard.cogs')
                <div class="vr"></div>
                <div class="d-flex flex-column align-items-end">
                    <span class="text-muted small text-uppercase fw-bold">{{ trans('modules/accounting::accounting.cost_price') }}</span>
                    <span class="fw-bold fs-3">{{ core_number_format($this->totals['total_cogs'] ?? 0) }}</span>
                </div>
                @endcan
            </div>
        </div>
    </div>
</div>

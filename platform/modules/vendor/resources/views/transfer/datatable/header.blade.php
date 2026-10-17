<div class="card-header d-flex justify-content-between" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <div class="card-title">
        {{ trans('modules/vendor::transfer.index') }}
    </div>
    <div class="card-actions">
        <div class="btn-list">
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> phiếu
            </span>
            @can('vendors.transfers.delete')
            <x-ui::button
                color="danger"
                outline
                icon="trash"
                type="button"
                wire:click="bulkDelete"
                wire:confirm="Bạn có chắc chắn muốn xóa các phiếu chuyển hàng đã chọn? Hành động này sẽ hoàn lại tồn kho."
                x-show="count > 0"
                style="display: none;"
            >
                {{ __('core/base::general.delete_selected') }}
            </x-ui::button>
            @endcan

            @can('vendors.transfers.create')
            <x-ui::button color="primary" icon="plus" class="d-none d-sm-inline-block" type="button"
                    onclick="Livewire.dispatch('redirect-transfer-view')">
                {{ trans('modules/vendor::transfer.create') }}
            </x-ui::button>
            @endcan
        </div>
    </div>
</div>

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
            <button
                type="button"
                class="btn btn-danger btn-sm"
                wire:click="bulkDelete"
                wire:confirm="Bạn có chắc chắn muốn xóa các phiếu chuyển hàng đã chọn? Hành động này sẽ hoàn lại tồn kho."
                x-show="count > 0"
                style="display: none;"
            >
                {!! tabler_icon('trash', ['class' => 'icon']) !!}
                {{ __('Xóa đã chọn') }}
            </button>
            @endcan

            @can('vendors.transfers.create')
            <button class="btn btn-primary d-none d-sm-inline-block"
                    onclick="Livewire.dispatch('redirect-transfer-view')">
                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                {{ trans('modules/vendor::transfer.create') }}
            </button>
            @endcan
        </div>
    </div>
</div>

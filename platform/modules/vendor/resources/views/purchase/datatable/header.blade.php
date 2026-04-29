<div class="card-header d-flex justify-content-between" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <div class="card-title">
        {{ trans('modules/vendor::purchase.index') }}
    </div>
    <div class="card-actions">
        <div class="btn-list">
            @can('vendors.purchases.delete')
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> phiếu
            </span>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                wire:click="bulkDelete"
                wire:confirm="Bạn có chắc chắn muốn xóa các phiếu nhập đã chọn? Thao tác này sẽ hoàn lại tồn kho."
                x-show="count > 0"
                style="display: none;"
            >
                {!! tabler_icon('trash', ['class' => 'icon']) !!}
                {{ __('Xóa đã chọn') }}
            </button>
            @endcan

            @can('vendors.purchases.create')
            <button class="btn btn-primary d-none d-sm-inline-block"
                    onclick="Livewire.dispatch('redirect-purchase-view')">
                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                {{ trans('modules/vendor::purchase.create') }}
            </button>
            @endcan
        </div>
    </div>
</div>

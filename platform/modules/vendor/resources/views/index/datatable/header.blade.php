<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/vendor::vendor.name') }}</h3>
    <div class="card-actions">
        <div class="btn-list">
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> nhà cung cấp
            </span>
            @can('vendors.delete')
                <x-ui::button
                              color="danger"
                              icon="trash"
                              wire:click="bulkDelete"
                              wire:confirm="Bạn có chắc chắn muốn xóa các nhà cung cấp đã chọn? Thao tác này không thể hoàn tác."
                              class="btn-bulk-delete"
                              x-show="count > 0"
                              style="display: none;">
                    {{ __('Xóa đã chọn') }}
                </x-ui::button>
            @endcan

            @can('vendors.create')
                <x-ui::button color="primary" icon="plus" @click="$dispatch('show-modal-create-vendor', [])">
                    {{ trans('modules/vendor::vendor.create') }}
                </x-ui::button>
            @endcan
        </div>
    </div>
</div>

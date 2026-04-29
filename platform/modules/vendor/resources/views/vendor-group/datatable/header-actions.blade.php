<div class="card-header d-flex justify-content-between" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <div class="card-title">
        {{ trans('modules/vendor::vendor.group.index') }}
    </div>
    <div class="card-actions">
        <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
            Đã chọn <span x-text="count">0</span> bản ghi
        </span>
        @can('vendors.groups')
            <x-ui::button
                          color="danger"
                          icon="trash"
                          wire:click="bulkDelete"
                          wire:confirm="Bạn có chắc chắn muốn xóa các bản ghi đã chọn? Thao tác này không thể hoàn tác."
                          class="btn-bulk-delete me-2"
                          x-show="count > 0"
                          style="display: none;">
                {{ __('Xóa đã chọn') }}
            </x-ui::button>
            <button class="btn btn-primary d-none d-sm-inline-block"
                    onclick="Livewire.dispatch('show-modal-create-vendor-group', [])">
                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                {{ trans('modules/vendor::vendor.group.create') }}
            </button>
        @endcan
    </div>
</div>

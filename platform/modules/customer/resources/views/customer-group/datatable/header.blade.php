<div class="card-header d-flex justify-content-between" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <div class="card-title">
        {{ trans('modules/customer::customer.group.index') }}
    </div>
    <div class="card-actions">
        @can('customers.groups')
            <div class="btn-list">
                <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                    Đã chọn <span x-text="count">0</span> bản ghi
                </span>
                <x-ui::button
                              color="danger"
                              outline
                              icon="trash"
                              type="button"
                              wire:click="bulkDelete"
                              wire:confirm="Bạn có chắc chắn muốn xóa các bản ghi đã chọn? Thao tác này không thể hoàn tác."
                              class="btn-bulk-delete"
                              x-show="count > 0"
                              style="display: none;">
                    {{ __('core/base::general.delete_selected') }}
                </x-ui::button>

                <x-ui::button color="primary" icon="plus" type="button" class="d-none d-sm-inline-block"
                              onclick="Livewire.dispatch('show-modal-create-customer-group', [])">
                    {{ trans('modules/customer::customer.group.create') }}
                </x-ui::button>
            </div>
        @endcan
    </div>
</div>

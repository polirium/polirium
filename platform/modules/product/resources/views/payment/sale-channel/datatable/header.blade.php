<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/product::product.sale_channel.label') }}</h3>
    <div class="card-actions">
        <div class="btn-list">
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> kênh bán hàng
            </span>
            @can('products.sale-channel.delete')
                <x-ui::button
                              color="danger"
                              icon="trash"
                              class="btn-bulk-delete"
                              x-show="count > 0"
                              style="display: none;"
                              data-bs-toggle="modal"
                              data-bs-target="#modal-bulk-delete-sale-channel">
                    {{ trans('modules/product::product.delete_selected') }}
                </x-ui::button>
            @endcan

            @can('products.sale-channel.create')
                <x-ui::button color="primary" icon="plus" @click="Livewire.dispatch('show-modal-create-sale-channel')">
                    {{ __('Thêm mới') }}
                </x-ui::button>
            @endcan
        </div>
    </div>
</div>

<x-ui::confirm-modal
                     id="modal-bulk-delete-sale-channel"
                     title="{{ trans('modules/product::product.confirm_delete') }}"
                     message="{{ trans('modules/product::product.confirm_delete_channels') }}"
                     confirm-button="{{ trans('modules/product::product.delete_selected') }}"
                     confirm-color="danger"
                     wire:click="bulkDelete" />

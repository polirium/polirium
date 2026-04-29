<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/product::product.delivery_partner.label') }}</h3>
    <div class="card-actions">
        <div class="btn-list">
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> đối tác
            </span>
            @can('products.delivery-partner.delete')
                <x-ui::button
                              color="danger"
                              icon="trash"
                              class="btn-bulk-delete"
                              x-show="count > 0"
                              style="display: none;"
                              data-bs-toggle="modal"
                              data-bs-target="#modal-bulk-delete-delivery-partner">
                    {{ trans('modules/product::product.delete_selected') }}
                </x-ui::button>
            @endcan

            @can('products.delivery-partner.create')
                <x-ui::button color="primary" icon="plus" @click="Livewire.dispatch('show-modal-create-delivery-partner')">
                    {{ __('Thêm mới') }}
                </x-ui::button>
            @endcan
        </div>
    </div>
</div>

<x-ui::confirm-modal
                     id="modal-bulk-delete-delivery-partner"
                     title="{{ trans('modules/product::product.confirm_delete') }}"
                     message="{{ trans('modules/product::product.confirm_delete_partners') }}"
                     confirm-button="{{ trans('modules/product::product.delete_selected') }}"
                     confirm-color="danger"
                     wire:click="bulkDelete" />

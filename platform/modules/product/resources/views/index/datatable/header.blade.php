<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/product::product.name') }}</h3>

    <div class="card-actions">
        <div class="btn-list">
            @can('products.destroy')
                <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                    Đã chọn <span x-text="count">0</span> sản phẩm
                </span>
                <x-ui::button
                    color="danger"
                    size="sm"
                    icon="trash"
                    wire:click="bulkDelete"
                    wire:confirm="Bạn có chắc chắn muốn xóa các sản phẩm đã chọn? Thao tác này không thể hoàn tác."
                    class="btn-bulk-delete"
                    x-show="count > 0"
                    style="display: none;"
                >
                    {{ trans('modules/product::product.delete_selected') }}
                </x-ui::button>
            @endcan

            @can('products.create')
            <x-ui::dropdown label="{{ trans('modules/product::product.add_product') }}" icon="plus" color="primary">
                <x-ui::dropdown.item label="{{ trans('modules/product::product.product') }}" icon="box" @click="$dispatch('show-modal-create-product', { type: 'product' })" />
                <x-ui::dropdown.item label="{{ trans('modules/product::product.service') }}" icon="settings" @click="$dispatch('show-modal-create-product', { type: 'service' })" />
                <x-ui::dropdown.item label="{{ trans('modules/product::product.combo_package') }}" icon="package" @click="$dispatch('show-modal-create-product', { type: 'combo' })" />
            </x-ui::dropdown>
            @endcan

            <x-ui::button color="success" icon="file-download" wire:click="exportExcelTemplate" class="ms-2">
                {{ trans('modules/product::product.export_excel') }}
            </x-ui::button>

            @can('products.create')
            <x-ui::button color="primary" icon="file-upload" :outline="true" @click="$dispatch('show-modal-import-product')" class="ms-2">
                {{ trans('modules/product::product.import_from_excel') }}
            </x-ui::button>
            @endcan
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        // Listen to PowerGrid events if standard x-on isn't enough,
        // but typically pg:checkbox emits to Livewire.
        // Note: PowerGrid might not emit a window event 'pg-bulk-actions' with the array.
        // It usually updates the component state.
    });
</script>

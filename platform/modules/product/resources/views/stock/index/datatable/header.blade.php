<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/product::stock.name') }}</h3>
    <div class="card-actions">
        <div class="btn-list">
            @can('products.stock.delete')
             <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> phiếu
            </span>
            <x-ui::button
                color="danger"
                icon="trash"
                wire:click="bulkDelete"
                wire:confirm="Bạn có chắc chắn muốn xóa các phiếu kiểm kho đã chọn? Thao tác này không thể hoàn tác."
                class="btn-bulk-delete"
                x-show="count > 0"
                style="display: none;"
            >
                {{ trans('modules/product::product.delete_selected') }}
            </x-ui::button>
            @endcan

            @can('products.stock.create')
            <x-ui.button href="{{ route('products.stock.stock') }}" color="success" icon="plus" :label="trans('modules/product::stock.name')" />
            @endcan
        </div>
    </div>
</div>

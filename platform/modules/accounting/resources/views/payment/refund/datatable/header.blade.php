<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}">
    <h3 class="card-title">{{ trans('modules/accounting::accounting.refund_list') }}</h3>

    <div class="card-actions">
        <div class="btn-list">
            <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                Đã chọn <span x-text="count">0</span> phiếu
            </span>
            @can('accountings.refunds')
                <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        wire:click="bulkDelete"
                        wire:confirm="Bạn có chắc chắn muốn xóa các phiếu trả hàng đã chọn?"
                        x-show="count > 0"
                        style="display: none;">
                    {!! tabler_icon('trash', ['class' => 'icon']) !!}
                    {{ trans('modules/accounting::accounting.delete_selected') }}
                </button>
            @endcan
        </div>
    </div>
</div>

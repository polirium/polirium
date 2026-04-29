<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-category" :header="trans(($category_id ? 'Sửa' : 'Tạo') . ' nhóm hàng')">
            <div class="row g-4">
                <div class="col-12">
                    <x-ui.form.group :label="__('modules/product::product.category_information')" icon="category">
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input_category.name"
                                :label="trans('modules/product::product.product_group')"
                                :placeholder="trans('modules/product::product.enter_group_name')"
                                icon="category"
                                required
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.select
                                wire:model="input_category.parent_id"
                                :label="trans('modules/product::product.parent_group')"
                                :placeholder="trans('Chọn nhóm cha (nếu có)')"
                                :options="$parents->pluck('name', 'id')->toArray()"
                            />
                        </div>
                    </x-ui.form.group>
                </div>
            </div>

            <x-slot name="footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                    {{ trans('Hủy') }}
                </button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="ti ti-device-floppy me-1"></i>
                        {{ trans('Lưu') }}
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="ti ti-loader-2 icon-spin me-1"></i>
                        {{ trans('modules/product::product.saving') }}
                    </span>
                </button>
            </x-slot>
        </x-ui::modal>
    </form>
</div>

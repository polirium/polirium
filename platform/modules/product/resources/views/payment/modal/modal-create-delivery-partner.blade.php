<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-delivery-partner" :header="$partner_id ? __('Cập nhật đối tác giao hàng') : __('Thêm mới đối tác giao hàng')" class="modal-lg">
            <x-ui::errors/>

            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.form.input
                        wire:model="input.name"
                        :label="trans('modules/product::product.partner_name')"
                        :placeholder="trans('modules/product::product.partner_name')"
                        required
                    />
                </div>
                <div class="col-md-6">
                    <x-ui.form.input
                        wire:model="input.code"
                        :label="trans('modules/product::product.code_label')"
                        :placeholder="trans('modules/product::product.code_label')"
                    />
                </div>
                <div class="col-md-6">
                    <x-ui.form.input
                        wire:model="input.phone"
                        :label="trans('modules/product::product.phone_number')"
                        :placeholder="trans('modules/product::product.phone_number')"
                    />
                </div>
                <div class="col-md-6">
                    <x-ui.form.input
                        wire:model="input.email"
                        type="email"
                        :label="trans('modules/product::product.email')"
                        :placeholder="trans('modules/product::product.email')"
                    />
                </div>
                <div class="col-12">
                    <x-ui.form.input
                        wire:model="input.address"
                        :label="trans('modules/product::product.address')"
                        :placeholder="trans('modules/product::product.address')"
                    />
                </div>
                <div class="col-12">
                    <x-ui.form.textarea
                        wire:model="input.note"
                        :label="trans('modules/product::product.note')"
                        :placeholder="trans('modules/product::product.note')"
                        rows="3"
                    />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('Hủy') }}</button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ __('Lưu') }}</span>
                    <span wire:loading wire:target="save" style="display: none;">{{ trans('modules/product::product.saving') }}</span>
                </button>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

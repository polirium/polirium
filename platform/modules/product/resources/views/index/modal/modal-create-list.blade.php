<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-list" :header="trans(($update_id ? 'Sửa' : 'Tạo') . ' ' . $title)">
            <div class="row g-4">
                <div class="col-12">
                    <x-ui.form.group :label="$title" icon="tag">
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.name"
                                :label="$title"
                                :placeholder="trans('modules/product::product.import') . ' ' . $title"
                                icon="tag"
                                required
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

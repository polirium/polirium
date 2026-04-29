<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-accounting-type" :header="trans('modules/accounting::accounting.type.' . ($accounting_type_id ? 'edit' : 'create'))">
            <x-ui::errors />

            <div class="row g-4">
                <div class="col-12">
                    <x-ui.form.group :label="__('modules/accounting::accounting.type.information')" icon="category">
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.name"
                                :label="trans('modules/accounting::accounting.type.name')"
                                :placeholder="trans('modules/accounting::accounting.type.enter_name')"
                                icon="category"
                                required
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.note"
                                :label="trans('core/base::general.note')"
                                :placeholder="trans('core/base::general.enter_note')"
                                icon="note"
                            />
                        </div>
                    </x-ui.form.group>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                    {{ trans('core/base::general.cancel') }}
                </button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="ti ti-device-floppy me-1"></i>
                        {{ trans('core/base::general.save') }}
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="ti ti-loader-2 icon-spin me-1"></i>
                        {{ trans('core/base::general.saving') }}
                    </span>
                </button>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

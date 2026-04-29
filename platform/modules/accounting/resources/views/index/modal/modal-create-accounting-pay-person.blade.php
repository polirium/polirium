<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-accounting-pay-person" :header="trans('modules/accounting::accounting.type.' . ($pay_person_id ? 'edit' : 'create'))">
            <x-ui::errors />

            <div class="row g-4">
                <div class="col-lg-6">
                    <x-ui.form.group :label="__('modules/accounting::accounting.pay_person.information')" icon="user">
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.name"
                                :label="trans('modules/accounting::accounting.pay_person.name')"
                                :placeholder="trans('modules/accounting::accounting.pay_person.enter_name')"
                                icon="user"
                                required
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.phone"
                                :label="trans('core/base::general.phone')"
                                :placeholder="trans('core/base::general.phone_placeholder')"
                                icon="phone"
                                type="tel"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.textarea
                                wire:model="input.address"
                                :label="trans('core/base::general.address')"
                                :placeholder="trans('core/base::general.enter_address')"
                                :rows="2"
                            />
                        </div>
                    </x-ui.form.group>
                </div>

                <div class="col-lg-6">
                    <x-ui.form.group :label="__('core/base::general.location')" icon="map-pin">
                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="input.province_id"
                                :label="trans('core/base::general.city_province')"
                                :placeholder="trans('core/base::general.select_province')"
                                :options="$lists['provinces']"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="input.district_id"
                                :label="trans('core/base::general.district')"
                                :placeholder="trans('core/base::general.select_district')"
                                :options="$lists['districts']"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.select
                                wire:model="input.ward_id"
                                :label="trans('core/base::general.ward')"
                                :placeholder="trans('core/base::general.select_ward')"
                                :options="$lists['wards']"
                            />
                        </div>
                    </x-ui.form.group>

                    <div class="col-12 mt-3">
                        <x-ui.form.textarea
                            wire:model="input.note"
                            :label="trans('core/base::general.note')"
                            :placeholder="trans('core/base::general.enter_note')"
                            :rows="2"
                        />
                    </div>
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

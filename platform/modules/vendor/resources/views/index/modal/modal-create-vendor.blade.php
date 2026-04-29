<form wire:submit.prevent="save">
    <x-ui::modal id="modal-create-vendor" :header="trans('modules/vendor::vendor.' . ($vendor_id ? 'edit' : 'create'))" class="modal-xl">
        <x-ui::errors />

            <div class="row g-3">
                {{-- Basic Info --}}
                <div class="col-lg-6">
                    <x-ui.form.group :label="__('modules/vendor::vendor.basic_info')" icon="building-arch">
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.code"
                                :label="trans('modules/vendor::vendor.code')"
                                :placeholder="__('modules/vendor::vendor.enter_code')"
                                icon="hash"
                            />
                        </div>
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.name"
                                :label="trans('modules/vendor::vendor.name')"
                                :placeholder="__('modules/vendor::vendor.enter_name')"
                                icon="building"
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
                            <x-ui.form.input
                                wire:model="input.company"
                                :label="trans('modules/vendor::vendor.company')"
                                :placeholder="__('modules/vendor::vendor.enter_company')"
                                icon="building-factory-2"
                            />
                        </div>
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.vat"
                                :label="trans('modules/vendor::vendor.vat')"
                                :placeholder="__('modules/vendor::vendor.enter_vat')"
                                icon="hash"
                            />
                        </div>
                    </x-ui.form.group>
                </div>

                {{-- Contact & Location --}}
                <div class="col-lg-6">
                    <x-ui.form.group :label="__('modules/vendor::vendor.contact_location')" icon="map-pin">
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="input.email"
                                :label="trans('core/base::general.email')"
                                :placeholder="trans('core/base::general.enter_email')"
                                icon="mail"
                                type="email"
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
                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="input.province_id"
                                :label="trans('core/base::general.city_province')"
                                :options="$lists['provinces']"
                                placeholder="--"
                            />
                        </div>
                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="input.district_id"
                                :label="trans('core/base::general.district')"
                                :options="$lists['districts']"
                                placeholder="--"
                            />
                        </div>
                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="input.ward_id"
                                :label="trans('core/base::general.ward')"
                                :options="$lists['wards']"
                                placeholder="--"
                            />
                        </div>
                        <div class="col-12 mt-2">
                            <x-ui.form.select
                                wire:key="vendor-group-select-{{ $vendor_id ?? 'new' }}"
                                wire:model="group"
                                :label="trans('modules/vendor::vendor.group.name')"
                                :placeholder="__('modules/vendor::vendor.select_groups')"
                                :options="$lists['group']"
                                multiple
                            />
                        </div>
                    </x-ui.form.group>
                </div>

                <div class="col-12">
                    <x-ui.form.textarea
                        wire:model="input.note"
                        :label="trans('core/base::general.note')"
                        :placeholder="trans('core/base::general.enter_note')"
                        :rows="3"
                    />
                </div>
            </div>

            <x-slot:footer>
                <x-ui::button color="secondary" :ghost="true" type="button" data-bs-dismiss="modal" icon="x">
                    {{ trans('core/base::general.cancel') }}
                </x-ui::button>
                <x-ui::button color="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        {!! tabler_icon('device-floppy', ['class' => 'icon']) !!}
                        {{ trans('core/base::general.save') }}
                    </span>
                    <span wire:loading wire:target="save">
                        {!! tabler_icon('loader-2', ['class' => 'icon icon-spin']) !!}
                        {{ trans('core/base::general.saving') }}
                    </span>
                </x-ui::button>
            </x-slot:footer>
    </x-ui::modal>
</form>

<form wire:submit.prevent="save">
    <x-ui::modal id="modal-create-customer" :header="($customer_id ? __('modules/customer::customer.edit') : __('modules/customer::customer.create'))" class="modal-xl">

            <div class="row g-4">
                {{-- Left Column: Basic Info --}}
                <div class="col-lg-6">
                    <x-ui.form.group
                        :label="__('modules/customer::customer.basic_information')"
                        icon="user"
                    >
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="customer.code"
                                :label="__('modules/customer::customer.code')"
                                :placeholder="__('modules/customer::customer.enter_code')"
                                icon="hash"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="customer.name"
                                :label="__('modules/customer::customer.full_name')"
                                :placeholder="__('modules/customer::customer.enter_full_name')"
                                icon="user"
                                required
                            />
                        </div>

                        <div class="col-md-6">
                            <x-ui.form.input
                                wire:model="customer.phone"
                                :label="__('modules/customer::customer.phone')"
                                :placeholder="__('modules/customer::customer.enter_phone')"
                                icon="phone"
                                type="tel"
                            />
                        </div>

                        <div class="col-md-6">
                            <x-ui.form.input
                                wire:model="customer.phone2"
                                :label="__('modules/customer::customer.phone2')"
                                :placeholder="__('modules/customer::customer.enter_phone2')"
                                icon="phone"
                                type="tel"
                            />
                        </div>

                        <div class="col-md-6">
                            <x-ui.form.input
                                wire:model="customer.birthday"
                                :label="__('modules/customer::customer.birthday')"
                                type="date"
                            />
                        </div>

                        <div class="col-md-6">
                            <x-ui.form.group :label="__('modules/customer::customer.sex')" description="">
                                <div class="d-flex gap-4">
                                    <x-ui.form.radio
                                        wire:model="customer.sex"
                                        value="0"
                                        :label="__('modules/customer::customer.male')"
                                        inline
                                    />
                                    <x-ui.form.radio
                                        wire:model="customer.sex"
                                        value="1"
                                        :label="__('modules/customer::customer.female')"
                                        inline
                                    />
                                </div>
                            </x-ui.form.group>
                        </div>

                        <div class="col-12">
                            <x-ui.form.textarea
                                wire:model="customer.address"
                                :label="__('modules/customer::customer.address')"
                                :placeholder="__('modules/customer::customer.enter_address')"
                                :rows="2"
                            />
                        </div>
                    </x-ui.form.group>
                </div>

                {{-- Right Column: Type, Location & Groups --}}
                <div class="col-lg-6">
                    {{-- Customer Type --}}
                    <x-ui.form.group
                        :label="__('modules/customer::customer.type')"
                        icon="users"
                    >
                        <div class="col-12 mb-3">
                            <div class="d-flex gap-4">
                                <x-ui.form.radio
                                    wire:model.live="customer.type"
                                    value="0"
                                    :label="__('modules/customer::customer.type_individual')"
                                    :description="__('modules/customer::customer.type_individual_desc')"
                                    inline
                                />
                                <x-ui.form.radio
                                    wire:model.live="customer.type"
                                    value="1"
                                    :label="__('modules/customer::customer.type_company')"
                                    :description="__('modules/customer::customer.type_company_desc')"
                                    inline
                                />
                            </div>
                        </div>

                        @if (($customer['type'] ?? 0) == 1)
                            <div class="col-12">
                                <x-ui.form.input
                                    wire:model="customer.company"
                                    :label="__('modules/customer::customer.company_name')"
                                    :placeholder="__('modules/customer::customer.enter_company_name')"
                                    icon="building"
                                />
                            </div>
                        @endif

                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="customer.vat"
                                :label="__('modules/customer::customer.vat')"
                                :placeholder="__('modules/customer::customer.enter_vat')"
                                icon="hash"
                            />
                        </div>
                    </x-ui.form.group>

                    {{-- Location --}}
                    <x-ui.form.group
                        :label="__('modules/customer::customer.location')"
                        icon="map-pin"
                    >
                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="customer.province_id"
                                :label="__('modules/customer::customer.province')"
                                :placeholder="__('modules/customer::customer.select_province')"
                                :options="$list['provinces']"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.select
                                wire:model.live="customer.district_id"
                                :label="__('modules/customer::customer.district')"
                                :placeholder="__('modules/customer::customer.select_district')"
                                :options="$list['districts']"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.select
                                wire:model="customer.ward_id"
                                :label="__('modules/customer::customer.ward')"
                                :placeholder="__('modules/customer::customer.select_ward')"
                                :options="$list['wards']"
                            />
                        </div>
                    </x-ui.form.group>

                    {{-- Contact & Groups --}}
                    <x-ui.form.group
                        :label="__('modules/customer::customer.contact_info')"
                        icon="mail"
                    >
                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="customer.email"
                                :label="__('modules/customer::customer.email')"
                                :placeholder="__('modules/customer::customer.enter_email')"
                                icon="mail"
                                type="email"
                            />
                        </div>

                        <div class="col-12">
                            <x-ui.form.input
                                wire:model="customer.facebook"
                                :label="__('modules/customer::customer.facebook')"
                                :placeholder="__('modules/customer::customer.enter_facebook')"
                                icon="brand-facebook"
                            />
                        </div>

                        <div class="col-12">
                            <label class="form-label">{{ __('modules/customer::customer.group.name') }}</label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <div id="customer-group-wrapper">
                                        <x-ui.form.select
                                            wire:model="customer_groups"
                                            :placeholder="__('modules/customer::customer.select_groups')"
                                            :options="$list['customer-groups']"
                                            multiple
                                        />
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-icon"
                                    style="width: 36px; height: 36px; min-width: 36px;"
                                    onclick="Livewire.dispatch('show-modal-create-customer-group'); event.preventDefault();"
                                >
                                    {!! tabler_icon('plus', ['class' => 'icon']) !!}
                                </button>
                            </div>
                        </div>
                    </x-ui.form.group>

                    {{-- Note --}}
                    <div class="col-12">
                        <x-ui.form.textarea
                            wire:model="customer.note"
                            :label="__('modules/customer::customer.note')"
                            :placeholder="__('modules/customer::customer.enter_note')"
                            :rows="2"
                        />
                    </div>
                </div>
            </div>

        <x-slot name="footer">
            <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                {{ __('modules/customer::customer.cancel') }}
            </button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ __('modules/customer::customer.save') }}
                </span>
                <span wire:loading wire:target="save">
                    <i class="ti ti-loader-2 icon-spin me-1"></i>
                    {{ __('modules/customer::customer.saving') }}
                </span>
            </button>
        </x-slot>
    </x-ui::modal>
</form>
@script
<script>
    Livewire.on('update-customer-group-options', ({ options, newGroupId }) => {
        const wrapper = document.getElementById('customer-group-wrapper');
        if (!wrapper) return;

        // Find the inner element that holds the x-data.
        // We look for element with x-data attribute
        const alpineEl = wrapper.querySelector('[x-data]');

        if (alpineEl && typeof Alpine !== 'undefined') {
            console.log('Polirium: Updating Alpine Component Options', { options, newGroupId });

            // Get Alpine component data scope
            // Note: Alpine V3 uses Alpine.$data(el) to get proxy
            // If strictly using x-data, sometimes we just need the element if it's initialized.
            // But from outside, robust way is using Alpine API.

            // Wait for Alpine to be ready if not yet (though standard Livewire loading implies it is)
            const component = Alpine.$data(alpineEl);

            if (component) {
                // Map options format: {id: name} -> [{id: id, label: name}]
                const newOptions = Object.entries(options).map(([id, label]) => ({
                    id: String(id),
                    label: label
                }));

                component.options = newOptions;

                // If newGroupId exists, select it
                if (newGroupId) {
                    const newIdStr = String(newGroupId);

                    if (component.isMultiple) {
                        // Ensure selectedValue is array
                        if (!Array.isArray(component.selectedValue)) {
                            component.selectedValue = [];
                        }

                        // Check if already selected (as string)
                        if (!component.selectedValue.some(id => String(id) === newIdStr)) {
                            component.selectedValue.push(newIdStr);
                        }
                    } else {
                        component.selectedValue = newIdStr;
                    }

                    // Update hidden input to notify Livewire
                    // Finding the hidden input inside the component
                    // Based on component code: <input type="hidden" x-ref="hiddenInput" ...>
                    // We can't access $refs easily from outside scope unless we use __x.$refs (internal)

                    const hiddenInput = alpineEl.querySelector('input[type="hidden"]');
                    if (hiddenInput) {
                        const val = component.isMultiple ? JSON.stringify(component.selectedValue) : component.selectedValue;
                        hiddenInput.value = val;
                        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            } else {
                 console.error('Polirium: Alpine component scope not found', alpineEl);
            }
        } else {
             console.error('Polirium: Alpine element not found or Alpine undefined', wrapper);
        }
    });

    Livewire.on('modal', (id, action) => {
        if (id === 'modal-create-customer-group' && action === 'hide') {
            const el = document.getElementById('modal-create-customer-group');
            if (el) {
                const instance = bootstrap.Modal.getOrCreateInstance(el);
                instance.hide();

                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    const openModals = document.querySelectorAll('.modal.show');
                    if (backdrops.length > openModals.length) {
                        backdrops[backdrops.length - 1].remove();
                    }
                }, 300);
            }
        }
    });

    // Clean up any leftover modal backdrops
    Livewire.on('cleanup-modal-backdrop', () => {
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            const openModals = document.querySelectorAll('.modal.show');

            // Remove extra backdrops
            if (backdrops.length > openModals.length) {
                for (let i = openModals.length; i < backdrops.length; i++) {
                    backdrops[i].remove();
                }
            }

            // If no modals are open, remove all backdrops and restore body
            if (openModals.length === 0) {
                backdrops.forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        }, 350);
    });
</script>
@endscript

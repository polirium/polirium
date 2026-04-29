<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-customer-group" :header="$item_id ? __('modules/customer::customer.group.edit') : __('modules/customer::customer.group.create')" icon="users-group" style="z-index: 1060;">
            <x-ui::errors/>

            <div class="mb-3">
                {{-- Group Name --}}
                <x-ui.form.input
                    wire:model="group.name"
                    :label="__('modules/customer::customer.group.name')"
                    :placeholder="__('modules/customer::customer.group.enter_name')"
                    icon="tag"
                    required
                />
            </div>

            {{-- Group Type --}}
            <div class="mb-3">
                <label class="ui-form-label d-block mb-2">{{ __('modules/customer::customer.type') }}</label>
                <div class="d-flex flex-column gap-2">
                    <x-ui.form.radio
                        wire:model.live="group.type"
                        value="1"
                        :label="__('modules/customer::customer.group.type_add_condition')"
                    />
                    <x-ui.form.radio
                        wire:model.live="group.type"
                        value="2"
                        :label="__('modules/customer::customer.group.type_update_condition')"
                    />
                    <x-ui.form.radio
                        wire:model.live="group.type"
                        value="3"
                        :label="__('modules/customer::customer.group.type_manual')"
                    />
                </div>
            </div>

            {{-- Note --}}
            <div class="mb-3">
                <x-ui.form.textarea
                    wire:model="group.note"
                    :label="__('modules/customer::customer.note')"
                    :placeholder="__('modules/customer::customer.enter_note')"
                    rows="3"
                />
            </div>

            <x-slot name="footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('modules/customer::customer.cancel') }}
                </button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
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
</div>
@script
<script>
    Livewire.on('modal', (id, action) => {
        if (id === 'modal-create-customer-group' && action === 'hide') {
            const el = document.getElementById('modal-create-customer-group');
            if (el) {
                const instance = bootstrap.Modal.getOrCreateInstance(el);
                instance.hide();

                // Manually cleanup stuck backdrop
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    const openModals = document.querySelectorAll('.modal.show');

                    // If we have excess backdrops (stuck ones), remove the last one (topmost)
                    if (backdrops.length > openModals.length) {
                        backdrops[backdrops.length - 1].remove();
                    }
                }, 300);
            }
        }
    });
</script>
@endscript

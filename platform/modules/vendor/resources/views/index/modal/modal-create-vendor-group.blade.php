<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-vendor-group" :header="trans('modules/vendor::vendor.group.' . ($vendor_group_id ? 'edit' : 'create'))">
            <x-ui::errors/>

            <div class="row g-3">
                <div class="col-12">
                    <x-ui.form.input
                        wire:model="input.name"
                        :label="trans('modules/vendor::vendor.group.name')"
                        :placeholder="__('modules/vendor::vendor.group.enter_name')"
                        icon="users-group"
                        required
                    />
                </div>

                <div class="col-12">
                    <x-ui.form.textarea
                        wire:model="input.note"
                        :label="trans('core/base::general.note')"
                        :placeholder="trans('core/base::general.enter_note')"
                        rows="2"
                    />
                </div>
            </div>

            <x-slot:footer>
                <x-ui::button color="secondary" :ghost="true" type="button" data-bs-dismiss="modal" icon="x">
                    {{ trans('core/base::general.cancel') }}
                </x-ui::button>
                <x-ui::button color="primary" type="submit" icon="device-floppy" wire:loading.attr="disabled">
                    {{ trans('core/base::general.save') }}
                </x-ui::button>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

@push('scripts')
<script>
    window.addEventListener('close-modal-vendor-group', event => {
        const modalEl = document.getElementById('modal-create-vendor-group');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            } else {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                modalEl.removeAttribute('role');
            }
        }

        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 150);
    });
</script>
@endpush

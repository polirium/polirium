<div class="professional-modal-wrapper">
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-sale-channel" :header="__('modules/product::product.sale_channel.title')" class="modal-lg professional-modal-wrapper">
            <x-ui::errors/>

            <div class="professional-modal-body">
                <div class="row g-4">
                    <div class="col-12">
                        <x-ui.form.input
                            wire:model="input.name"
                            :label="__('modules/product::product.sale_channel.name')"
                            :placeholder="__('modules/product::product.sale_channel.name')"
                            icon="tag"
                            required
                        />
                    </div>

                    <div class="col-12">
                        <x-ui.form.textarea
                            wire:model="input.description"
                            :label="__('modules/product::product.sale_channel.description')"
                            :placeholder="__('modules/product::product.sale_channel.description')"
                            rows="2"
                        />
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="professional-modal-footer">
                    <button type="button" class="professional-btn-action secondary" data-bs-dismiss="modal">
                        {!! tabler_icon('x', ['class' => 'icon']) !!}
                        {{ __('core/base::general.cancel') }}
                    </button>
                    <button type="submit" class="professional-btn-action primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove>
                            {!! tabler_icon('device-floppy', ['class' => 'icon']) !!}
                            {{ __('core/base::general.save') }}
                        </span>
                        <span wire:loading style="display: none;" class="align-items-center gap-2">
                            {!! tabler_icon('loader-2', ['class' => 'icon icon-spin']) !!}
                            {{ __('core/base::general.saving') }}
                        </span>
                    </button>
                </div>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

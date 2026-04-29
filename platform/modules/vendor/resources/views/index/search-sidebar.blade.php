<div>
    <div class="mb-3">
        <x-form::select wire:model.live="search.group_id" :label="trans('modules/vendor::vendor.group.name')" :options="$lists['group']" tomselect>
            <x-slot name="append">
                @can('vendors.groups')
                    <x-ui.button type="button"
                                 :color="$search['group_id'] ? 'warning' : 'primary'"
                                 :icon="$search['group_id'] ? 'pencil' : 'plus'"
                                 wire:click="$dispatch('show-modal-create-vendor-group', { id: {{ $search['group_id'] ?? 0 }} })" />
                @endcan
            </x-slot>
        </x-form::select>
    </div>

    <div class="mb-3">
        <x-form::input wire:model.live="search.name" :label="trans('modules/vendor::vendor.name')" />
    </div>

    @livewire('modules/vendor::index.modal.modal-create-vendor-group')
</div>

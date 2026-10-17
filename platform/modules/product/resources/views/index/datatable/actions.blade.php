<x-ui::row-actions>
    @can('products.edit')
        <x-ui::action-icon icon="pencil" :label="__('core/base::general.edit')" wire:click="$dispatch('show-modal-create-product', { id: {{ $id }}, type: '{{ $type }}' })" />
    @endcan
    @can('products.create')
        <x-ui::action-icon icon="copy" :label="__('core/base::general.copy')" wire:click="$dispatch('triggerCopyProduct', { id: {{ $id }} })" />
    @endcan
    @can('products.destroy')
        <x-ui::action-icon icon="trash" danger :label="__('core/base::general.delete')" :confirm="trans('Are you sure you want to delete this product?')" wire:click="$dispatch('triggerRemoveProduct', { id: {{ $id }} })" />
    @endcan
</x-ui::row-actions>

<div class="dropdown">
    <button class="btn btn-icon btn-sm btn-light d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <x-tabler-icons::settings class="icon" />
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @can('products.edit')
        <li>
            <a class="dropdown-item" href="javascript:void(0)" wire:click="$dispatch('show-modal-create-product', { id: {{ $id }}, type: '{{ $type }}' })">
                <x-tabler-icons::edit class="icon me-2"/>
                {{ trans('Sửa') }}
            </a>
        </li>
        @endcan
        @can('products.create')
        <li>
            <a class="dropdown-item" href="javascript:void(0)" wire:click="$dispatch('triggerCopyProduct', { id: {{ $id }} })">
                <x-tabler-icons::copy class="icon me-2"/>
                {{ trans('Copy') }}
            </a>
        </li>
        @endcan
        @can('products.destroy')
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-danger" href="javascript:void(0)" wire:click="$dispatch('triggerRemoveProduct', { id: {{ $id }} })" wire:confirm="{{ trans('Are you sure you want to delete this product?') }}">
                <x-tabler-icons::trash class="icon me-2"/>
                {{ trans('modules/product::product.delete') }}
            </a>
        </li>
        @endcan
    </ul>
</div>

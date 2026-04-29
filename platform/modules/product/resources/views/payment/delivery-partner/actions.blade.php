<div class="d-flex justify-content-center">
    @can('products.delivery-partner.edit')
        <button class="btn btn-icon btn-primary btn-sm me-1"
                onclick="Livewire.dispatch('show-modal-create-delivery-partner', {id: {{ $row->id }}})"
                title="{{ __('Sửa') }}">
            {!! tabler_icon('edit') !!}
        </button>
    @endcan
</div>

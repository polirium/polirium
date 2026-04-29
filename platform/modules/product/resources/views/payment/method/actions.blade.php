<div class="d-flex justify-content-center">
    @can('products.payment-method.edit')
        <button class="btn btn-icon btn-primary btn-sm me-1"
                onclick="Livewire.dispatch('modal-create-payment-method', {id: {{ $row->id }}})"
                title="{{ __('Sửa') }}">
            {!! tabler_icon('edit') !!}
        </button>
    @endcan

    @can('products.payment-method.delete')
        <button class="btn btn-icon btn-outline-danger btn-sm"
                onclick="Livewire.dispatch('delete-payment-method', {id: {{ $row->id }}})"
                title="{{ __('Xóa') }}">
            {!! tabler_icon('trash') !!}
        </button>
    @endcan
</div>

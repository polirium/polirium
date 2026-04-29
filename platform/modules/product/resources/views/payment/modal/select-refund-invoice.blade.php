<x-ui::modal id="modal-select-refund-invoice" :title="__('Chọn hóa đơn trả hàng')" class="modal-xl">
    <div class="row mb-3">
        <div class="col-md-6">
            <x-form::input wire:model.live.debounce.300ms="search" :placeholder="__('Tìm kiếm (Mã HĐ, Khách hàng, SĐT)...')" icon="search" />
        </div>
        <div class="col-md-3">
            <x-form::input type="date" wire:model.live="date_start" :label="trans('modules/product::product.from_date')" />
        </div>
        <div class="col-md-3">
            <x-form::input type="date" wire:model.live="date_end" :label="trans('modules/product::product.to_date')" />
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('modules/product::product.invoice_code') }}</th>
                    <th>{{ trans('modules/product::product.time') }}</th>
                    <th>{{ trans('modules/product::product.staff') }}</th>
                    <th>{{ trans('modules/product::product.customer') }}</th>
                    <th class="text-end">{{ trans('modules/product::product.grand_total') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->code }}</td>
                        <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $invoice->user->name ?? '-' }}</td>
                        <td>{{ $invoice->customer->name ?? 'Khách lẻ' }}</td>
                        <td class="text-end fw-bold">{{ number_format($invoice->total_cost) }}</td>
                        <td class="text-end">
                            <button class="btn btn-primary btn-sm" wire:click="selectInvoice({{ $invoice->id }})">
                                {{ __('Chọn') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="ti ti-inbox fs-2 d-block mb-2"></i>
                            {{ trans('modules/product::product.no_results') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $invoices->links() }}
    </div>
</x-ui::modal>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('close-modal', (event) => {
             // In LW3, event is the parameters object.
             let name = event.name;
             // Helper for inconsistencies
             if (!name && event[0] && event[0].name) name = event[0].name;

             if (name) {
                 const el = document.getElementById(name);
                 if (el) {
                     const modal = bootstrap.Modal.getOrCreateInstance(el);
                     modal.hide();
                 }
             }
        });
    });
</script>
@endpush

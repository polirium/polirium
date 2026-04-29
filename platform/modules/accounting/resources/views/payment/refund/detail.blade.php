<x-ui.layouts::app>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Chi tiết trả hàng #{{ $id }}</h3>
                </div>
        @livewire('modules/accounting::payment.refund-component')
            </div>
        </div>
    </div>
</x-ui.layouts::app>

<x-ui.layouts::app>
    @livewire('modules/product::stock.stock.view', ['stock_id' => $id, 'viewMode' => $viewMode ?? false])
</x-ui.layouts::app>

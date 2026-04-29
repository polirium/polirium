<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/accounting::accounting.sales_report') }}
    </x-slot:title>

    @livewire('modules/accounting::report.sales-report')
</x-ui.layouts::app>

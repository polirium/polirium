<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::task.kanban') }}</x-slot:title>
    <x-slot:actions>
        @include('modules/task::partials.view-switcher')
    </x-slot:actions>

    @include('modules/task::partials.alerts')

    @livewire('modules/task::task.kanban')
</x-ui.layouts::app>

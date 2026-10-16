<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::task.create') }}</x-slot:title>

    @include('modules/task::partials.alerts')

    <form method="POST" action="{{ route('admin.tasks.store') }}" novalidate>
        @csrf
        @include('modules/task::task._form')
    </form>
</x-ui.layouts::app>

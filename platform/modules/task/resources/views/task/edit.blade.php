<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::task.edit') }}: {{ $task->name }}</x-slot:title>
    <x-slot:subtitle><code>{{ $task->code }}</code></x-slot:subtitle>

    @include('modules/task::partials.alerts')

    <form method="POST" action="{{ route('admin.tasks.update', $task->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('modules/task::task._form')
    </form>
</x-ui.layouts::app>

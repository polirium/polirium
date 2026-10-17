@php($current = request()->route()?->getName())
<div class="btn-group" role="group">
    @can('tasks.index')
        <a href="{{ route('admin.tasks.index') }}" class="btn {{ $current === 'admin.tasks.index' ? 'btn-primary' : 'btn-outline-secondary' }}">
            {!! tabler_icon('list', ['class' => 'icon']) !!} {{ __('modules/task::task.list') }}
        </a>
    @endcan
    @can('tasks.kanban')
        <a href="{{ route('admin.tasks.kanban') }}" class="btn {{ $current === 'admin.tasks.kanban' ? 'btn-primary' : 'btn-outline-secondary' }}">
            {!! tabler_icon('layout-kanban', ['class' => 'icon']) !!} {{ __('modules/task::task.kanban') }}
        </a>
    @endcan
    @can('tasks.gantt')
        <a href="{{ route('admin.tasks.gantt') }}" class="btn {{ $current === 'admin.tasks.gantt' ? 'btn-primary' : 'btn-outline-secondary' }}">
            {!! tabler_icon('timeline', ['class' => 'icon']) !!} {{ __('modules/task::task.gantt') }}
        </a>
    @endcan
</div>
@can('tasks.create')
    <x-ui::button color="primary" icon="plus" :href="route('admin.tasks.create')">
        {{ __('modules/task::task.create') }}
    </x-ui::button>
@endcan

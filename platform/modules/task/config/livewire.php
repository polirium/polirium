<?php

return [
    // Project Components
    'modules/task::project.datatable.project-table' => [
        'class' => \Polirium\Modules\Task\Http\Livewire\Project\Datatable\ProjectTable::class,
        'alias' => 'modules/task::project.datatable.project-table',
        'description' => 'Project table view with PowerGrid',
    ],
    'modules/task::project.filter-sidebar' => [
        'class' => \Polirium\Modules\Task\Http\Livewire\Project\FilterSidebarComponent::class,
        'alias' => 'modules/task::project.filter-sidebar',
        'description' => 'Project filter sidebar component',
    ],

    // Task Components
    'modules/task::task.datatable.task-table' => [
        'class' => \Polirium\Modules\Task\Http\Livewire\Task\Datatable\TaskTable::class,
        'alias' => 'modules/task::task.datatable.task-table',
        'description' => 'Task table view with PowerGrid',
    ],
    'modules/task::task.filter-sidebar' => [
        'class' => \Polirium\Modules\Task\Http\Livewire\Task\FilterSidebarComponent::class,
        'alias' => 'modules/task::task.filter-sidebar',
        'description' => 'Task filter sidebar component',
    ],
    'modules/task::task.kanban' => [
        'class' => \Polirium\Modules\Task\Http\Livewire\Task\Kanban\TaskKanbanComponent::class,
        'alias' => 'modules/task::task.kanban',
        'description' => 'Task Kanban board component',
    ],
];

<?php

return [
    [
        'id' => 'module-task',
        'name' => 'modules/task::project.name',
        'icon' => 'chart-bar',
        'route' => null,
        'sort' => 50,
    ],
    [
        'id' => 'module-task-projects',
        'parent' => 'module-task',
        'name' => 'modules/task::project.index',
        'route' => 'admin.projects.index',
        'icon' => 'folder',
        'permission' => 'projects.index',
        'sort' => 1,
    ],
    [
        'id' => 'module-task-tasks',
        'parent' => 'module-task',
        'name' => 'modules/task::task.index',
        'route' => 'admin.tasks.index',
        'icon' => 'checklist',
        'permission' => 'tasks.index',
        'sort' => 2,
    ],
    [
        'id' => 'module-task-kanban',
        'parent' => 'module-task',
        'name' => 'modules/task::task.kanban',
        'route' => 'admin.tasks.kanban',
        'icon' => 'layout-kanban',
        'permission' => 'tasks.kanban',
        'sort' => 3,
    ],
    [
        'id' => 'module-task-gantt',
        'parent' => 'module-task',
        'name' => 'modules/task::task.gantt',
        'route' => 'admin.tasks.gantt',
        'icon' => 'timeline',
        'permission' => 'tasks.gantt',
        'sort' => 4,
    ],
];

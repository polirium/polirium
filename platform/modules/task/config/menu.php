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
];

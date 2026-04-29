<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Dự án & Công việc
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Dự án',
        'flag' => 'projects',
    ],
    [
        'name' => 'Xem danh sách dự án',
        'flag' => 'projects.index',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Tạo dự án',
        'flag' => 'projects.create',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Xem chi tiết dự án',
        'flag' => 'projects.show',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Sửa dự án',
        'flag' => 'projects.edit',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Xóa dự án',
        'flag' => 'projects.destroy',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Công việc',
        'flag' => 'tasks',
    ],
    [
        'name' => 'Xem danh sách công việc',
        'flag' => 'tasks.index',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Xem chi tiết công việc',
        'flag' => 'tasks.show',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Tạo công việc',
        'flag' => 'tasks.create',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Sửa công việc',
        'flag' => 'tasks.edit',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Xóa công việc',
        'flag' => 'tasks.destroy',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Bảng Kanban',
        'flag' => 'tasks.kanban',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Biểu đồ Gantt',
        'flag' => 'tasks.gantt',
        'parent_flag' => 'tasks',
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Mẫu in
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Mẫu in',
        'flag' => 'print-forms',
    ],
    [
        'name' => 'Xem cấu hình mẫu in',
        'flag' => 'print-forms.forms.index',
        'parent_flag' => 'print-forms',
    ],
    [
        'name' => 'Chỉnh sửa mẫu in',
        'flag' => 'print-forms.forms.edit',
        'parent_flag' => 'print-forms',
    ],
];

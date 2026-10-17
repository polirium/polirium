<?php

/*
|--------------------------------------------------------------------------
| Application menu items
|--------------------------------------------------------------------------
| Merged into the admin menu by the core MenuServiceProvider.
*/

return [
    [
        'id' => 'core.system-update',
        'name' => 'system.update',
        'parent' => 'core.setting',
        'route' => 'system.update.index',
        'icon' => 'cloud-download',
        'sort' => 99,
        'permission' => 'system.update',
    ],
];

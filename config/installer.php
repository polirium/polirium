<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    | The name displayed in the installer wizard.
    */
    'name' => 'Polirium ERP',

    /*
    |--------------------------------------------------------------------------
    | Logo Path
    |--------------------------------------------------------------------------
    | Path to your logo image (relative to public/).
    */
    'logo' => '/vendor/polirium/core/ui/assets/polirium-logo.png',

    /*
    |--------------------------------------------------------------------------
    | PHP Requirements
    |--------------------------------------------------------------------------
    */
    'requirements' => [
        'php_version' => '8.3',
        'extensions' => [
            'bcmath',
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'gd',
            'json',
            'mbstring',
            'openssl',
            'pcre',
            'pdo',
            'pdo_mysql',
            'tokenizer',
            'xml',
            'zip',
        ],
        'memory_limit' => '128M',
        'opcache' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Writable Directories
    |--------------------------------------------------------------------------
    | Directories that must be writable for installation.
    */
    'writable_directories' => [
        'storage',
        'storage/app',
        'storage/framework',
        'storage/logs',
        'bootstrap/cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Installation Steps
    |--------------------------------------------------------------------------
    | The ordered list of step classes that form the installation wizard.
    | Each class must implement InstallerStep. You can add, remove, or
    | reorder steps as needed for your application.
    */
    'steps' => [
        \RelayerCore\LaravelInstaller\Steps\CheckRequirements::class,
        \RelayerCore\LaravelInstaller\Steps\CheckPermissions::class,
        \App\Installer\Steps\ConfigurePoliriumEnvironment::class,
        \App\Installer\Steps\ChooseDatabaseAction::class,
        \App\Installer\Steps\InstallPolirium::class,
        \App\Installer\Steps\CreateAdministrator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin User Model
    |--------------------------------------------------------------------------
    | The model class for creating admin users.
    */
    'admin_model' => \Polirium\Core\Base\Http\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | After Admin Created Callback
    |--------------------------------------------------------------------------
    | Called after the admin user is created/saved. Receives the user model
    | instance. Use this to assign roles, permissions, or run any
    | post-creation logic specific to your application.
    |
    | Provide the fully qualified class name of an invokable class.
    |
    | Example:
    |   'on_admin_created' => \App\Installer\Callbacks\AdminCreated::class,
    */
    'on_admin_created' => null,

    /*
    |--------------------------------------------------------------------------
    | Extra Environment Fields
    |--------------------------------------------------------------------------
    | Additional .env variables to write during the environment step.
    | Each entry maps an env key to its UI configuration. These are
    | rendered as extra form fields on the database setup screen.
    |
    | Example:
    |   'environment_fields' => [
    |       'MULTI_TENANT' => [
    |           'type' => 'checkbox',
    |           'label' => 'Enable Multi-Tenancy',
    |           'description' => 'Host multiple businesses under one install.',
    |           'default' => false,
    |           'state_key' => 'multi_tenant',
    |       ],
    |   ],
    */
    'environment_fields' => [
        'APP_NAME' => [
            'type' => 'text',
            'label' => 'Tên doanh nghiệp / ứng dụng',
            'placeholder' => 'Polirium ERP',
            'default' => 'Polirium ERP',
            'state_key' => 'app_name',
        ],
        'APP_URL' => [
            'type' => 'url',
            'label' => 'Địa chỉ truy cập',
            'placeholder' => 'https://erp.example.com',
            'default' => env('APP_URL', 'http://localhost'),
            'state_key' => 'app_url',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Class
    |--------------------------------------------------------------------------
    | The seeder to run if "Load Demo Data" is selected.
    | Default: 'DemoSeeder'
    */
    'seeder' => \Database\Seeders\DatabaseSeeder::class,

    /*
    |--------------------------------------------------------------------------
    | Installation Lock File
    |--------------------------------------------------------------------------
    | The file that marks the application as installed.
    */
    'installed_file' => storage_path('installed'),

    /*
    |--------------------------------------------------------------------------
    | Theme Colors
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'primary' => '#206bc4',
        'primary_dark' => '#1a569d',
    ],

    /*
    |--------------------------------------------------------------------------
    | After Install Callback
    |--------------------------------------------------------------------------
    | Called after successful installation, before the redirect.
    | Receives no arguments. Use this for custom post-install logic
    | like activating modules, sending notifications, etc.
    |
    | Provide the fully qualified class name of an invokable class.
    |
    | Example:
    |   'after_install' => \App\Installer\Callbacks\AfterInstall::class,
    */
    'after_install' => null,

    /*
    |--------------------------------------------------------------------------
    | Redirect After Install
    |--------------------------------------------------------------------------
    */
    'redirect_after_install' => '/login',
];

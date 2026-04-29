<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Load Polirium Modules
|--------------------------------------------------------------------------
|
| Load module namespaces before Laravel bootstraps to ensure provider
| classes can be resolved from packages.php.
|
*/

$modulePath = dirname(__DIR__) . '/platform/modules';

if (is_dir($modulePath)) {
    $modules = array_diff(scandir($modulePath), ['.', '..']);

    foreach ($modules as $module) {
        $moduleDir = $modulePath . '/' . $module;

        if (is_dir($moduleDir)) {
            $composerFile = $moduleDir . '/composer.json';
            if (file_exists($composerFile)) {
                $composer = json_decode(file_get_contents($composerFile), true);

                if (isset($composer['autoload']['psr-4'])) {
                    foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                        $realPath = $moduleDir . '/' . trim($path, '/');
                        spl_autoload_register(function ($class) use ($namespace, $realPath) {
                            if (strpos($class, $namespace) === 0) {
                                $relativeClass = substr($class, strlen($namespace));
                                $file = $realPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                                if (file_exists($file)) {
                                    require_once $file;
                                }
                            }
                        });
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;

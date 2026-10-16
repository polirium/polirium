<?php

declare(strict_types=1);

namespace App\Installer;

use Illuminate\Contracts\Foundation\Application;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use RuntimeException;

class InstallationState implements InstallationStateManager
{
    public function __construct(private readonly Application $app)
    {
    }

    public function isInstalled(): bool
    {
        return $this->app->environment('testing') || file_exists($this->path());
    }

    public function markInstalled(): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException("The installer lock directory is not writable: {$directory}");
        }

        if (file_put_contents($path, now()->toIso8601String(), LOCK_EX) === false) {
            throw new RuntimeException("Unable to create the installer lock file: {$path}");
        }
    }

    private function path(): string
    {
        return (string) config('installer.installed_file', storage_path('installed'));
    }
}

<?php

declare(strict_types=1);

namespace App\Installer;

use App\Installer\Support\ExistingDatabaseInspector;
use Illuminate\Contracts\Foundation\Application;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use RuntimeException;
use Throwable;

class InstallationState implements InstallationStateManager
{
    private bool $detectedLiveDatabase = false;

    public function __construct(private readonly Application $app)
    {
    }

    public function isInstalled(): bool
    {
        if ($this->app->environment('testing') || file_exists($this->path())) {
            return true;
        }

        // Sites deployed before the installer existed have no lock file. A database
        // that already runs Polirium with users is an installation: never send it
        // to the installer (whose fresh mode wipes the database).
        if ($this->detectedLiveDatabase || $this->hasLiveDatabase()) {
            $this->detectedLiveDatabase = true;

            try {
                $this->markInstalled();
            } catch (Throwable) {
                // Read-only storage: keep detecting from the database.
            }

            return true;
        }

        return false;
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

    private function hasLiveDatabase(): bool
    {
        try {
            return ExistingDatabaseInspector::isLive($this->app->make(ExistingDatabaseInspector::class)->inspect());
        } catch (Throwable) {
            // No usable database connection yet (a genuine first install).
            return false;
        }
    }

    private function path(): string
    {
        return (string) config('installer.installed_file', storage_path('installed'));
    }
}

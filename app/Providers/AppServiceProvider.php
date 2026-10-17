<?php

namespace App\Providers;

use App\Installer\InstallationState;
use Illuminate\Support\ServiceProvider;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InstallationStateManager::class, InstallationState::class);

        if (! $this->app->make(InstallationStateManager::class)->isInstalled()) {
            config([
                'app.debug' => false,
                'debugbar.enabled' => false,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app(InstallationStateManager::class)->isInstalled()) {
            config(['app.debug' => false]);
        }

        \App\Support\SystemSettings::register();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Installer\Steps\ChooseDatabaseAction;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use RelayerCore\LaravelInstaller\Http\Livewire\Installer as BaseInstaller;

class Installer extends BaseInstaller
{
    public function finish(): void
    {
        if (session()->get(ChooseDatabaseAction::SESSION_MODE) !== ChooseDatabaseAction::MODE_UPGRADE) {
            parent::finish();

            return;
        }

        $progressFile = storage_path('framework/installer-progress.json');

        if (is_file($progressFile)) {
            @unlink($progressFile);
        }

        app(InstallationStateManager::class)->markInstalled();

        session()->forget([
            self::SESSION_KEY,
            ChooseDatabaseAction::SESSION_MODE,
            ChooseDatabaseAction::SESSION_DATABASE,
            ChooseDatabaseAction::SESSION_HAD_MODULES,
        ]);

        $this->dispatch('installer-finishing');
        $this->dispatch('installation-success', [
            'redirectUrl' => config('installer.redirect_after_install', '/admin'),
        ]);
    }
}

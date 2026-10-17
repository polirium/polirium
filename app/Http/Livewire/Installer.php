<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Installer\Steps\ChooseDatabaseAction;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use RelayerCore\LaravelInstaller\Http\Livewire\Installer as BaseInstaller;

class Installer extends BaseInstaller
{
    public function mount(): void
    {
        parent::mount();
        $this->clearAdministratorFields();
    }

    public function next(): void
    {
        $from = $this->currentStepId;

        parent::next();

        if ($this->currentStepId !== $from) {
            $this->clearAdministratorFields();
        }
    }

    /**
     * The administrator step shares the "username" and "password" keys with
     * the database step. Never offer the database credentials as the admin
     * login: start that form empty.
     */
    protected function clearAdministratorFields(): void
    {
        if ($this->currentStepId !== 'admin') {
            return;
        }

        $this->state['username'] = '';
        $this->state['password'] = '';
        $this->state['password_confirmation'] = '';
    }

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

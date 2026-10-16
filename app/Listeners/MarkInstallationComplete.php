<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Console\Events\CommandFinished;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use Symfony\Component\Console\Command\Command;

final class MarkInstallationComplete
{
    public function __construct(private readonly InstallationStateManager $installationState)
    {
    }

    public function handle(CommandFinished $event): void
    {
        if ($event->command !== 'poli:install' || $event->exitCode !== Command::SUCCESS) {
            return;
        }

        $this->installationState->markInstalled();
    }
}

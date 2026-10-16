<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Listeners\MarkInstallationComplete;
use Illuminate\Console\Events\CommandFinished;
use Mockery;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class MarkInstallationCompleteTest extends TestCase
{
    public function test_successful_cli_install_creates_the_installer_lock(): void
    {
        $state = Mockery::mock(InstallationStateManager::class);
        $state->shouldReceive('markInstalled')->once();

        (new MarkInstallationComplete($state))->handle($this->event('poli:install', 0));
    }

    public function test_failed_or_unrelated_commands_do_not_create_the_installer_lock(): void
    {
        $state = Mockery::mock(InstallationStateManager::class);
        $state->shouldNotReceive('markInstalled');
        $listener = new MarkInstallationComplete($state);

        $listener->handle($this->event('poli:install', 1));
        $listener->handle($this->event('migrate', 0));
    }

    private function event(string $command, int $exitCode): CommandFinished
    {
        return new CommandFinished(
            $command,
            new ArrayInput([]),
            new BufferedOutput(),
            $exitCode,
        );
    }
}

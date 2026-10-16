<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Livewire\Installer;
use App\Installer\Steps\ChooseDatabaseAction;
use Livewire\Livewire;
use RelayerCore\LaravelInstaller\Contracts\InstallationStateManager;
use Tests\TestCase;

class InstallerPasswordStateTest extends TestCase
{
    public function test_environment_submit_does_not_overwrite_the_database_password_from_the_admin_buffer(): void
    {
        $this->bindUninstalledState();

        session()->put('installer.progress', ['requirements', 'permissions']);

        $component = Livewire::test(Installer::class)
            ->assertSet('currentStepId', 'environment');

        $this->assertStringNotContainsString(
            '$wire.set(\'state.password\', pw)',
            $component->html()
        );
    }

    public function test_admin_submit_still_synchronizes_both_admin_password_fields(): void
    {
        $this->bindUninstalledState();

        session()->put('installer.progress', ['requirements', 'permissions', 'environment', 'database_action', 'migrations']);

        $component = Livewire::test(Installer::class)
            ->assertSet('currentStepId', 'admin');

        $this->assertStringContainsString(
            '$wire.set(\'state.password\', pw, false)',
            $component->html()
        );
        $this->assertStringContainsString(
            '$wire.set(\'state.password_confirmation\', pc, false)',
            $component->html()
        );

        $this->assertSame(
            1,
            substr_count($component->html(), "pw: ''"),
            'The admin step must use the form password buffer instead of shadowing it in a nested Alpine scope.'
        );
        $this->assertSame(
            1,
            substr_count($component->html(), "pc: ''"),
            'The admin step must use the form confirmation buffer instead of shadowing it in a nested Alpine scope.'
        );
    }

    public function test_migration_step_renders_polirium_branding_and_realtime_progress(): void
    {
        $this->bindUninstalledState();

        session()->put('installer.progress', ['requirements', 'permissions', 'environment', 'database_action']);

        $component = Livewire::test(Installer::class)
            ->assertSet('currentStepId', 'migrations');

        $html = $component->html();

        $this->assertStringContainsString('/vendor/polirium/core/ui/assets/polirium-logo.png', $html);
        $this->assertStringContainsString('installation-progress', $html);
        $this->assertStringContainsString('install\\/stage', $html);
        $this->assertStringNotContainsString('wire:stream=', $html);
    }

    public function test_installer_page_uses_a_session_backed_csrf_token(): void
    {
        $this->bindUninstalledState();

        $response = $this->get('/install');

        $response->assertOk();
        $response->assertSee('meta name="csrf-token" content="', false);
        $this->assertNotSame('', csrf_token());
    }

    public function test_successful_legacy_upgrade_creates_the_installed_lock_and_clears_upgrade_state(): void
    {
        $state = new class () implements InstallationStateManager {
            public bool $marked = false;

            public function isInstalled(): bool
            {
                return false;
            }

            public function markInstalled(): void
            {
                $this->marked = true;
            }
        };
        $this->app->instance(InstallationStateManager::class, $state);
        session()->put([
            ChooseDatabaseAction::SESSION_MODE => ChooseDatabaseAction::MODE_UPGRADE,
            ChooseDatabaseAction::SESSION_DATABASE => 'legacy_polirium',
            ChooseDatabaseAction::SESSION_HAD_MODULES => true,
            'installer.progress' => ['requirements'],
        ]);

        Livewire::test(Installer::class)
            ->call('finish')
            ->assertDispatched('installation-success');

        $this->assertTrue($state->marked);
        $this->assertNull(session(ChooseDatabaseAction::SESSION_MODE));
        $this->assertNull(session('installer.progress'));
    }

    private function bindUninstalledState(): void
    {
        $this->app->instance(InstallationStateManager::class, new class () implements InstallationStateManager {
            public function isInstalled(): bool
            {
                return false;
            }

            public function markInstalled(): void
            {
            }
        });
    }
}

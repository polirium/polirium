<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Installer\Steps\ChooseDatabaseAction;
use App\Installer\Steps\InstallPolirium;
use App\Installer\Support\BufferedArtisanRunner;
use App\Installer\Support\ExistingDatabaseInspector;
use App\Installer\Support\InstallationProgress;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InstallPoliriumSafetyTest extends TestCase
{
    private string $progressPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->progressPath = tempnam(sys_get_temp_dir(), 'polirium-install-safety-');
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'legacy_polirium',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->progressPath);
        parent::tearDown();
    }

    public function test_upgrade_mode_never_wipes_the_existing_database(): void
    {
        $artisan = Mockery::mock(BufferedArtisanRunner::class);
        $artisan->shouldNotReceive('run');
        $installer = $this->installer($artisan, 'polirium');
        $this->selectMode(ChooseDatabaseAction::MODE_UPGRADE);

        $result = $installer->runNextStage();

        $this->assertFalse($result['done']);
        $this->assertSame('core_migrations', session('installer.database_stage'));
    }

    public function test_reinstall_wipes_only_after_the_confirmed_mode_was_stored(): void
    {
        $artisan = Mockery::mock(BufferedArtisanRunner::class);
        $artisan->shouldReceive('run')->once()->with('db:wipe', ['--force' => true])->andReturn('');
        $installer = $this->installer($artisan, 'polirium');
        $this->selectMode(ChooseDatabaseAction::MODE_FRESH);

        $installer->runNextStage();

        $this->assertSame('core_migrations', session('installer.database_stage'));
    }

    public function test_live_database_with_users_is_never_wiped_even_if_fresh_mode_is_stored(): void
    {
        $artisan = Mockery::mock(BufferedArtisanRunner::class);
        $artisan->shouldNotReceive('run');
        $installer = $this->installer($artisan, 'polirium', true);
        $this->selectMode(ChooseDatabaseAction::MODE_FRESH);

        $this->expectException(RuntimeException::class);
        $installer->runNextStage();
    }

    public function test_stage_endpoint_cannot_start_without_a_stored_database_choice(): void
    {
        $artisan = Mockery::mock(BufferedArtisanRunner::class);
        $artisan->shouldNotReceive('run');
        $installer = $this->installer($artisan, 'polirium');

        $this->expectException(RuntimeException::class);
        $installer->runNextStage();
    }

    public function test_a_failed_upgrade_migration_does_not_trigger_database_cleanup(): void
    {
        $artisan = Mockery::mock(BufferedArtisanRunner::class);
        $artisan->shouldReceive('run')->with('db:wipe', Mockery::any())->never();
        $artisan->shouldReceive('run')
            ->once()
            ->withArgs(fn (string $command): bool => $command === 'migrate')
            ->andThrow(new RuntimeException('migration failed'));
        $installer = $this->installer($artisan, 'polirium');
        $this->selectMode(ChooseDatabaseAction::MODE_UPGRADE);

        $installer->runNextStage();

        $this->expectException(RuntimeException::class);
        $installer->runNextStage();
    }

    private function installer(BufferedArtisanRunner $artisan, string $status, bool $hasUsers = false): InstallPolirium
    {
        $inspector = Mockery::mock(ExistingDatabaseInspector::class);
        $inspector->shouldReceive('inspect')->zeroOrMoreTimes()->andReturn([
            'status' => $status,
            'table_count' => $status === 'empty' ? 0 : 12,
            'has_modules_table' => true,
            'has_users' => $hasUsers,
        ]);

        return new InstallPolirium(
            $artisan,
            new InstallationProgress($this->progressPath),
            $inspector,
        );
    }

    private function selectMode(string $mode): void
    {
        session()->put([
            ChooseDatabaseAction::SESSION_MODE => $mode,
            ChooseDatabaseAction::SESSION_DATABASE => 'legacy_polirium',
            ChooseDatabaseAction::SESSION_HAD_MODULES => true,
        ]);
    }
}

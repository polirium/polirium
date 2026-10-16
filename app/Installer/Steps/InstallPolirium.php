<?php

declare(strict_types=1);

namespace App\Installer\Steps;

use App\Installer\Support\BufferedArtisanRunner;
use App\Installer\Support\ExistingDatabaseInspector;
use App\Installer\Support\InstallationProgress;
use Illuminate\Support\Facades\Log;
use Polirium\Core\Base\Http\Models\Module;
use Polirium\Core\Base\Service\ModuleManager;
use RelayerCore\LaravelInstaller\Contracts\InstallerStep;
use RuntimeException;
use Throwable;

class InstallPolirium implements InstallerStep
{
    private const SESSION_STAGE = 'installer.database_stage';

    private const SESSION_MODULES = 'installer.pending_modules';

    private const SESSION_READY = 'installer.database_ready';

    private const ROOT_MIGRATION_PATHS = [
        'database/migrations',
        'platform/core/base/database/migrations',
        'platform/core/settings/database/migrations',
        'platform/core/media/database/migrations',
        'platform/packages/vietnam-administrative-units/database/migrations',
    ];

    public function __construct(
        private readonly BufferedArtisanRunner $artisan,
        private readonly InstallationProgress $progress,
        private readonly ExistingDatabaseInspector $inspector,
    ) {
    }

    public function id(): string
    {
        return 'migrations';
    }

    public function label(): string
    {
        return __('installer::installer.step_migrations');
    }

    public function view(): string
    {
        return 'installer::steps.migrations';
    }

    public function isSkipped(): bool
    {
        return false;
    }

    public function validate(array $data = []): bool
    {
        if ((bool) session()->get(self::SESSION_READY, false)) {
            return true;
        }

        $this->validateSelectedMode();

        return true;
    }

    public function process(array $data = []): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        if (session()->pull(self::SESSION_READY, false)) {
            $this->clearStageSession();

            return;
        }

        do {
            $result = $this->runNextStage();
        } while (! $result['done']);

        session()->forget(self::SESSION_READY);
        $this->clearStageSession();
    }

    public function runNextStage(): array
    {
        ini_set('memory_limit', '-1');
        set_time_limit(120);

        try {
            $stage = session()->get(self::SESSION_STAGE);

            if ($stage === null) {
                return $this->prepareDatabase();
            }

            return match ($stage) {
                'core_migrations' => $this->runCoreMigrations(),
                'discover_modules' => $this->discoverModules(),
                'enable_modules' => $this->enableModules(),
                'module_migrations' => $this->runModuleMigrations(),
                'administrative_units' => $this->syncAdministrativeUnits(),
                'assets' => $this->publishAssets(),
                default => throw new RuntimeException("Unknown installer stage [{$stage}]."),
            };
        } catch (Throwable $exception) {
            $this->failInstallation($exception);

            throw new RuntimeException(
                __('installer::installer.migrations_error_fallback'),
                previous: $exception,
            );
        }
    }

    public function progressSnapshot(): array
    {
        return $this->progress->snapshot();
    }

    private function prepareDatabase(): array
    {
        $mode = $this->validateSelectedMode();
        $inspection = $this->inspector->inspect();

        $this->progress->reset(
            $mode === ChooseDatabaseAction::MODE_UPGRADE
                ? __('installer::installer.progress_upgrade_starting')
                : __('installer::installer.progress_starting'),
        );

        if ($mode === ChooseDatabaseAction::MODE_FRESH && $inspection['status'] !== 'empty') {
            $this->progress->report(__('installer::installer.progress_wiping_database'), 5);
            $this->runArtisan('db:wipe', ['--force' => true]);
        }

        session()->put(self::SESSION_STAGE, 'core_migrations');

        return $this->result();
    }

    private function runCoreMigrations(): array
    {
        $this->progress->report(__('installer::installer.progress_core_migrations'), 10);
        $this->runArtisan('migrate', [
            '--path' => self::ROOT_MIGRATION_PATHS,
            '--force' => true,
        ]);
        $this->progress->report(__('installer::installer.progress_core_ready'), 40);
        session()->put(self::SESSION_STAGE, 'discover_modules');

        return $this->result();
    }

    private function discoverModules(): array
    {
        /** @var ModuleManager $moduleManager */
        $moduleManager = app(ModuleManager::class);
        $moduleManager->discover();
        $modules = Module::pending()->orderBy('name')->pluck('name')->values()->all();

        $mayEnablePendingModules = session()->get(ChooseDatabaseAction::SESSION_MODE) === ChooseDatabaseAction::MODE_FRESH
            || ! (bool) session()->get(ChooseDatabaseAction::SESSION_HAD_MODULES, false);

        session()->put(self::SESSION_MODULES, $mayEnablePendingModules ? $modules : []);
        session()->put(
            self::SESSION_STAGE,
            $mayEnablePendingModules && $modules !== [] ? 'enable_modules' : 'module_migrations',
        );
        $this->progress->report(__('installer::installer.progress_modules_discovered'), 44);

        return $this->result();
    }

    private function enableModules(): array
    {
        $modules = session()->get(self::SESSION_MODULES, []);

        /** @var ModuleManager $moduleManager */
        $moduleManager = app(ModuleManager::class);

        // Enabling a single module per HTTP request leaves the next request
        // booting a partially-enabled application. Some module menus reference
        // routes supplied by their dependencies, so activation must be atomic.
        foreach ($modules as $moduleName) {
            if (! is_string($moduleName)) {
                continue;
            }

            if (! $moduleManager->enable($moduleName, false)) {
                throw new RuntimeException("Unable to activate module [{$moduleName}].");
            }

            $this->progress->report(
                __('installer::installer.progress_enabling_module', ['module' => $moduleName]),
                66,
            );
        }

        session()->forget(self::SESSION_MODULES);
        session()->put(self::SESSION_STAGE, 'module_migrations');

        return $this->result();
    }

    private function runModuleMigrations(): array
    {
        $migrationPaths = Module::active()
            ->orderBy('name')
            ->pluck('path')
            ->map(fn (string $path): string => $path . '/database/migrations')
            ->filter(fn (string $path): bool => is_dir($path))
            ->values()
            ->all();

        $this->progress->report(__('installer::installer.progress_module_migrations'), 70);

        if ($migrationPaths !== []) {
            $this->runArtisan('migrate', [
                '--path' => $migrationPaths,
                '--realpath' => true,
                '--force' => true,
            ]);
        }

        $this->progress->report(__('installer::installer.progress_module_migrations_ready'), 84);
        session()->put(
            self::SESSION_STAGE,
            session()->get(ChooseDatabaseAction::SESSION_MODE) === ChooseDatabaseAction::MODE_UPGRADE
                ? 'assets'
                : 'administrative_units',
        );

        return $this->result();
    }

    private function syncAdministrativeUnits(): array
    {
        $this->progress->report(__('installer::installer.progress_administrative_units'), 88);
        $this->runArtisan('vietnam-units:sync', [
            '--apply' => true,
            '--archive-missing' => true,
        ]);
        $this->progress->report(__('installer::installer.progress_administrative_units_ready'), 94);
        session()->put(self::SESSION_STAGE, 'assets');

        return $this->result();
    }

    private function publishAssets(): array
    {
        $this->progress->report(__('installer::installer.progress_assets'), 96);
        $this->runArtisan('vendor:publish', [
            '--provider' => 'Polirium\Core\UI\Providers\UIServiceProvider',
            '--force' => true,
        ]);
        $this->progress->complete(__('installer::installer.progress_complete'));
        session()->put(self::SESSION_READY, true);
        $this->clearStageSession();

        return $this->result(true);
    }

    private function result(bool $done = false): array
    {
        return [...$this->progress->snapshot(), 'done' => $done];
    }

    private function failInstallation(Throwable $exception): void
    {
        Log::error('Installer database setup failed.', ['exception' => $exception]);

        $this->progress->fail(__('installer::installer.progress_failed'));
        $this->clearStageSession();
        session()->forget(self::SESSION_READY);
    }

    private function clearStageSession(): void
    {
        session()->forget([
            self::SESSION_STAGE,
            self::SESSION_MODULES,
        ]);
    }

    private function runArtisan(string $command, array $parameters): void
    {
        $output = $this->artisan->run($command, $parameters);

        if ($output !== '') {
            Log::debug('Installer command completed.', [
                'command' => $command,
                'output_bytes' => strlen($output),
            ]);
        }
    }

    private function validateSelectedMode(): string
    {
        $mode = session()->get(ChooseDatabaseAction::SESSION_MODE);
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (! in_array($mode, [ChooseDatabaseAction::MODE_FRESH, ChooseDatabaseAction::MODE_UPGRADE], true)
            || ! hash_equals((string) session()->get(ChooseDatabaseAction::SESSION_DATABASE, ''), $database)) {
            throw new RuntimeException(__('installer::installer.database_action_expired'));
        }

        if ($mode === ChooseDatabaseAction::MODE_UPGRADE && $this->inspector->inspect()['status'] !== 'polirium') {
            throw new RuntimeException(__('installer::installer.database_upgrade_unrecognized'));
        }

        return $mode;
    }
}

<?php

declare(strict_types=1);

namespace App\Installer\Steps;

use App\Installer\Support\ExistingDatabaseInspector;
use RelayerCore\LaravelInstaller\Contracts\InstallerStep;
use RuntimeException;

class ChooseDatabaseAction implements InstallerStep
{
    public const MODE_FRESH = 'fresh';

    public const MODE_UPGRADE = 'upgrade';

    public const SESSION_DATABASE = 'installer.database_name';

    public const SESSION_HAD_MODULES = 'installer.database_had_modules_table';

    public const SESSION_MODE = 'installer.database_mode';

    public function __construct(private readonly ExistingDatabaseInspector $inspector)
    {
    }

    public function id(): string
    {
        return 'database_action';
    }

    public function label(): string
    {
        return __('installer::installer.step_database_action');
    }

    public function view(): string
    {
        return 'installer::steps.database-action';
    }

    public function isSkipped(): bool
    {
        return false;
    }

    public function inspection(): array
    {
        return $this->inspector->inspect();
    }

    public function validate(array $data = []): bool
    {
        $inspection = $this->inspection();

        if ($inspection['status'] === 'empty') {
            return true;
        }

        $mode = $data['install_mode'] ?? null;

        if (! in_array($mode, [self::MODE_UPGRADE, self::MODE_FRESH], true)) {
            throw new RuntimeException(__('installer::installer.database_action_required'));
        }

        if ($mode === self::MODE_UPGRADE && $inspection['status'] !== 'polirium') {
            throw new RuntimeException(__('installer::installer.database_upgrade_unrecognized'));
        }

        if ($mode === self::MODE_FRESH) {
            $database = (string) config('database.connections.' . config('database.default') . '.database');

            if (! hash_equals($database, (string) ($data['reinstall_confirmation'] ?? ''))) {
                throw new RuntimeException(__('installer::installer.database_reinstall_confirmation_invalid'));
            }
        }

        return true;
    }

    public function process(array $data = []): void
    {
        $this->validate($data);
        $inspection = $this->inspection();
        $mode = $inspection['status'] === 'empty'
            ? self::MODE_FRESH
            : (string) $data['install_mode'];

        session()->put([
            self::SESSION_MODE => $mode,
            self::SESSION_DATABASE => (string) config('database.connections.' . config('database.default') . '.database'),
            self::SESSION_HAD_MODULES => $inspection['has_modules_table'],
        ]);
    }
}

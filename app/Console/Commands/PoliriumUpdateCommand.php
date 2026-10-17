<?php

namespace App\Console\Commands;

use App\Updater\ReleaseClient;
use App\Updater\Updater;
use App\Updater\UpdateException;
use App\Updater\Version;
use Illuminate\Console\Command;

class PoliriumUpdateCommand extends Command
{
    protected $signature = 'poli:update
        {version? : Release to install (default: latest)}
        {--check : Only show whether an update is available}
        {--rollback : Restore the code saved by the last update}
        {--force : Reinstall even when the version is not newer}
        {--yes : Do not ask for confirmation}';

    protected $description = 'Update Polirium from a GitHub release package';

    public function handle(Updater $updater, ReleaseClient $releases): int
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->warn('Running as root: file ownership will be returned to the site user afterwards. Prefer: sudo -u <site-user> php artisan poli:update');
        }

        try {
            if ($this->option('rollback')) {
                $backup = $updater->latestBackupInfo();

                if (! $backup) {
                    $this->error(__('system.nothing_to_rollback'));

                    return self::FAILURE;
                }

                if (! $this->option('yes') && ! $this->confirm(__('system.confirm_rollback', ['version' => $backup['from']]), false)) {
                    return self::FAILURE;
                }

                $result = $updater->rollback();
                $this->info(__('system.rolled_back', ['version' => $result['to']]));

                return self::SUCCESS;
            }

            $current = Version::current();
            $latest = $releases->latest(true);
            $this->line(__('system.current_version') . ': <info>' . $current . '</info>');
            $this->line(__('system.latest_version') . ': <info>' . ($latest['version'] ?? '—') . '</info>');

            if ($this->option('check')) {
                return self::SUCCESS;
            }

            $target = $this->argument('version') ?: ($latest['version'] ?? null);

            if (! $target || (! $this->option('force') && ! Version::isNewer($target, $current))) {
                $this->info(__('system.up_to_date'));

                return self::SUCCESS;
            }

            if (! $this->option('yes') && ! $this->confirm(__('system.confirm_update', ['version' => $target]), false)) {
                return self::FAILURE;
            }

            $updater->start($target, (bool) $this->option('force'));
            $state = $updater->runAll(fn (string $stage) => $this->line('→ ' . __('system.stages.' . $stage)));

            foreach ($state['log'] ?? [] as $entry) {
                $this->line('  · ' . $entry['message']);
            }

            $this->info(__('system.done', ['version' => $state['to']]));

            return self::SUCCESS;
        } catch (UpdateException $exception) {
            $this->error(__('system.failed', ['message' => $exception->getMessage()]));

            return self::FAILURE;
        }
    }
}

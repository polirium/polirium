<?php

namespace App\Updater;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Installs a release package in stages: download → verify → install.
 *
 * Each stage is one call (one HTTP request from the admin page, or one step of
 * `poli:update`), so slow hosts never hit a single long request. State lives in
 * storage/app/updater/state.json. Code is replaced by renaming top-level entries
 * into a backup directory, which makes the swap fast, removes stale files and
 * allows an exact rollback. Installation-owned paths (config('updater.preserve'))
 * are never touched.
 */
class Updater
{
    public const STAGES = ['download', 'verify', 'install'];

    public function __construct(private readonly ReleaseClient $releases)
    {
    }

    /**
     * Start an update to $version (default: latest release).
     *
     * @return array<string, mixed>
     */
    public function start(?string $version = null, bool $force = false): array
    {
        $this->assertIdle();

        $release = $version
            ? $this->releases->find($version)
            : (($latest = $this->releases->latest(true)) && $latest['version'] ? $this->releases->find($latest['version']) : null);

        if (! $release) {
            throw new UpdateException(__('system.up_to_date'));
        }

        if (! $force && ! Version::isNewer($release['version'], Version::current())) {
            throw new UpdateException(__('system.not_newer', ['version' => $release['version']]));
        }

        File::ensureDirectoryExists($this->path());

        return $this->saveState([
            'status' => 'running',
            'from' => Version::current(),
            'to' => $release['version'],
            'release' => $release,
            'next' => 'download',
            'started_at' => now()->toIso8601String(),
            'heartbeat' => now()->toIso8601String(),
            'log' => [],
        ]);
    }

    /**
     * Run the next stage. Returns the state; `next` is null when finished.
     *
     * @return array<string, mixed>
     */
    public function runNextStage(): array
    {
        $state = $this->state();

        if (! $state || ($state['status'] ?? null) !== 'running' || empty($state['next'])) {
            throw new UpdateException(__('system.no_update_running'));
        }

        $stage = $state['next'];
        $state['heartbeat'] = now()->toIso8601String();
        $this->saveState($state);

        try {
            $state = match ($stage) {
                'download' => $this->download($state),
                'verify' => $this->verify($state),
                'install' => $this->install($state),
            };
        } catch (Throwable $exception) {
            $state = $this->state() ?? $state;
            $state['status'] = 'failed';
            $state['next'] = null;
            $state['error'] = $exception->getMessage();
            $state = $this->log($state, 'error', $stage . ': ' . $exception->getMessage());
            $this->saveState($state);
            $this->appendHistory($state);
            Log::error('Polirium update failed', ['stage' => $stage, 'exception' => $exception]);

            throw $exception instanceof UpdateException ? $exception : new UpdateException($exception->getMessage(), 0, $exception);
        }

        return $this->saveState($state);
    }

    /**
     * Run every remaining stage (used by `poli:update`).
     *
     * @return array<string, mixed>
     */
    public function runAll(?callable $progress = null): array
    {
        $state = $this->state();

        while (($state['status'] ?? null) === 'running' && ! empty($state['next'])) {
            $progress && $progress($state['next']);
            $state = $this->runNextStage();
        }

        return $state;
    }

    /**
     * Restore the code saved by the most recent update. Data is not changed.
     *
     * @return array<string, mixed>
     */
    public function rollback(): array
    {
        $this->assertIdle();
        $backup = $this->latestBackup();

        if (! $backup) {
            throw new UpdateException(__('system.nothing_to_rollback'));
        }

        $journal = json_decode((string) File::get($backup . '/journal.json'), true);
        $this->maintenance(true);

        try {
            $this->undoSwap($journal['entries'] ?? [], $backup);
            $this->fixOwnership(array_column($journal['entries'] ?? [], 'path'));
            $this->artisan(['optimize:clear']);
        } finally {
            $this->maintenance(false);
        }

        File::deleteDirectory($backup);
        $entry = ['status' => 'rolled_back', 'from' => $journal['to'] ?? null, 'to' => $journal['from'] ?? null, 'finished_at' => now()->toIso8601String()];
        $this->appendHistory($entry);
        $this->clearState();

        return $entry;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function state(): ?array
    {
        $file = $this->path('state.json');

        return is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    }

    public function clearState(): void
    {
        File::delete($this->path('state.json'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function history(): array
    {
        $file = $this->path('history.json');

        return is_file($file) ? array_reverse(json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    public function latestBackup(): ?string
    {
        $dirs = glob($this->path('backups/*'), GLOB_ONLYDIR) ?: [];
        $dirs = array_filter($dirs, fn ($dir) => is_file($dir . '/journal.json'));
        rsort($dirs);

        return $dirs[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestBackupInfo(): ?array
    {
        $backup = $this->latestBackup();

        return $backup ? json_decode((string) File::get($backup . '/journal.json'), true) : null;
    }

    public function path(string $relative = ''): string
    {
        return rtrim((string) config('updater.path'), '/') . ($relative !== '' ? '/' . $relative : '');
    }

    // ------------------------------------------------------------------ stages

    private function download(array $state): array
    {
        $release = $state['release'];
        $dir = $this->path('downloads');
        File::ensureDirectoryExists($dir);

        $zip = $dir . '/' . $release['asset']['name'];
        $this->releases->download($release['asset']['url'], $zip);
        $checksum = $this->releases->fetchText($release['checksum']['url']);

        $state['package'] = $zip;
        $state['expected_sha256'] = strtolower(substr(trim($checksum), 0, 64));
        $state['next'] = 'verify';

        return $this->log($state, 'info', 'downloaded ' . $release['asset']['name'] . ' (' . filesize($zip) . ' bytes)');
    }

    private function verify(array $state): array
    {
        $actual = hash_file('sha256', $state['package']);

        if (! hash_equals((string) $state['expected_sha256'], $actual)) {
            throw new UpdateException(__('system.checksum_mismatch'));
        }

        $target = $this->path('packages/' . $state['to']);
        File::deleteDirectory($target);
        File::ensureDirectoryExists($target);

        $zip = new ZipArchive();

        if ($zip->open($state['package']) !== true) {
            throw new UpdateException('Cannot open package ' . basename($state['package']));
        }

        // Refuse entries escaping the target directory (zip slip).
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                $zip->close();

                throw new UpdateException('Unsafe path in package: ' . $name);
            }
        }

        $zip->extractTo($target);
        $this->restoreSymlinks($zip, $target);
        $zip->close();

        $manifest = is_file($target . '/release.json') ? json_decode((string) File::get($target . '/release.json'), true) : null;

        if (! $manifest || Version::normalize((string) ($manifest['version'] ?? '')) !== $state['to'] || ! is_file($target . '/artisan')) {
            throw new UpdateException('Invalid package: release.json/version mismatch');
        }

        $problems = $this->requirementProblems($manifest, $target);

        if ($problems !== []) {
            throw new UpdateException(implode(' ', $problems));
        }

        $state['extracted'] = $target;
        $state['manifest'] = $manifest;
        $state['next'] = 'install';

        return $this->log($state, 'info', 'package verified (sha256 ' . substr($actual, 0, 12) . '…) and extracted');
    }

    private function install(array $state): array
    {
        $backup = $this->path('backups/' . now()->format('Ymd-His') . '-' . $state['from'] . '-to-' . $state['to']);
        File::ensureDirectoryExists($backup);

        if (config('updater.backup_database')) {
            $state = $this->log($state, 'info', $this->backupDatabase($backup));
        }

        $this->maintenance(true);
        $entries = [];

        try {
            $entries = $this->swap($state['extracted'], $backup);
            File::put($backup . '/journal.json', json_encode([
                'from' => $state['from'],
                'to' => $state['to'],
                'created_at' => now()->toIso8601String(),
                'entries' => $entries,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->fixOwnership(array_column($entries, 'path'));
            $this->resetOpcache();
            $this->artisan(['migrate', '--force']);
            $this->artisan(['optimize:clear']);
        } catch (Throwable $exception) {
            // Put the previous code back; the database backup stays for a manual restore.
            if ($entries !== []) {
                try {
                    $this->undoSwap($entries, $backup);
                    $this->resetOpcache();
                    File::deleteDirectory($backup);
                } catch (Throwable $restore) {
                    Log::critical('Polirium update rollback failed', ['exception' => $restore, 'backup' => $backup]);
                }
            }

            $this->maintenance(false);

            throw $exception;
        }

        $this->maintenance(false);

        File::deleteDirectory($state['extracted']);
        File::delete($state['package']);
        $this->pruneBackups();

        $state['status'] = 'done';
        $state['next'] = null;
        $state['finished_at'] = now()->toIso8601String();
        $state['backup'] = $backup;
        $state = $this->log($state, 'info', 'installed ' . $state['to']);
        $this->appendHistory($state);

        return $state;
    }

    /**
     * ZipArchive writes a symlink entry as a small file holding its target.
     * The package stores vendor/polirium/* → platform/* as relative symlinks
     * (not a second copy); recreate them, or copy the target where symlinks
     * are not allowed. A link pointing outside the package is refused.
     */
    private function restoreSymlinks(ZipArchive $zip, string $target): void
    {
        $root = realpath($target);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (! $zip->getExternalAttributesIndex($i, $opsys, $attributes) || $opsys !== ZipArchive::OPSYS_UNIX) {
                continue;
            }

            if ((($attributes >> 16) & 0170000) !== 0120000) {
                continue;
            }

            $name = rtrim((string) $zip->getNameIndex($i), '/');
            $path = $target . '/' . $name;
            $link = trim((string) $zip->getFromIndex($i));
            $resolved = realpath(dirname($path) . '/' . $link);

            if ($link === '' || str_starts_with($link, '/') || ! $resolved || ! str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)) {
                throw new UpdateException('Unsafe symlink in package: ' . $name);
            }

            File::delete($path);

            if (! @symlink($link, $path)) {
                is_dir($resolved) ? File::copyDirectory($resolved, $path) : File::copy($resolved, $path);
            }
        }
    }

    // ----------------------------------------------------------- file swapping

    /**
     * Move every release entry into place, saving what it replaces.
     *
     * @return list<array{path: string, existed: bool}>
     */
    private function swap(string $source, string $backup): array
    {
        $entries = [];

        foreach ($this->releaseEntries($source) as $relative) {
            $from = $source . '/' . $relative;
            $to = base_path($relative);
            $saved = $backup . '/code/' . $relative;
            $existed = file_exists($to) || is_link($to);

            if ($existed) {
                File::ensureDirectoryExists(dirname($saved));
                $this->move($to, $saved);
            }

            // Keep a replaced file's permissions, e.g. a public/index.php the
            // host made read-only.
            $mode = $existed && is_file($saved) && ! is_link($saved) ? fileperms($saved) & 0777 : null;

            File::ensureDirectoryExists(dirname($to));
            $this->move($from, $to);

            if ($mode !== null && is_file($to) && ! is_link($to)) {
                @chmod($to, $mode);
            }

            $entries[] = ['path' => $relative, 'existed' => $existed];
        }

        return $entries;
    }

    /**
     * @param list<array{path: string, existed: bool}> $entries
     */
    private function undoSwap(array $entries, string $backup): void
    {
        foreach (array_reverse($entries) as $entry) {
            $current = base_path($entry['path']);

            if (is_link($current) || is_file($current)) {
                File::delete($current);
            } elseif (is_dir($current)) {
                File::deleteDirectory($current);
            }

            if ($entry['existed']) {
                $this->move($backup . '/code/' . $entry['path'], $current);
            }
        }
    }

    /**
     * Top-level entries of the package, descending one level into a directory
     * that contains preserved paths (public/uploads, bootstrap/cache…), so those
     * paths and anything the installation added beside them stay untouched.
     *
     * @return list<string>
     */
    public function releaseEntries(string $source, string $prefix = ''): array
    {
        $preserve = array_map(fn ($path) => trim($path, '/'), (array) config('updater.preserve', []));
        $entries = [];

        foreach (scandir($source . ($prefix !== '' ? '/' . $prefix : '')) ?: [] as $name) {
            if ($name === '.' || $name === '..' || ($prefix === '' && $name === 'release.json')) {
                continue;
            }

            $relative = ltrim($prefix . '/' . $name, '/');

            if (in_array($relative, $preserve, true)) {
                continue;
            }

            $containsPreserved = collect($preserve)->contains(fn ($path) => str_starts_with($path, $relative . '/'));

            if ($containsPreserved && is_dir($source . '/' . $relative)) {
                array_push($entries, ...$this->releaseEntries($source, $relative));

                continue;
            }

            $entries[] = $relative;
        }

        return $entries;
    }

    private function move(string $from, string $to): void
    {
        if (@rename($from, $to)) {
            return;
        }

        // Different filesystem: copy, then remove the source.
        if (is_dir($from) && ! is_link($from)) {
            if (! File::copyDirectory($from, $to)) {
                throw new UpdateException(__('system.not_writable', ['path' => $to]));
            }

            File::deleteDirectory($from);

            return;
        }

        if (! @copy($from, $to)) {
            throw new UpdateException(__('system.not_writable', ['path' => $to]));
        }

        @unlink($from);
    }

    // ------------------------------------------------------------------ checks

    /**
     * @return list<string>
     */
    public function requirementProblems(array $manifest, ?string $package = null): array
    {
        $problems = [];
        $php = (string) ($manifest['requires']['php'] ?? '8.3.0');

        if (version_compare(PHP_VERSION, $php, '<')) {
            $problems[] = "PHP {$php}+ (" . PHP_VERSION . ')';
        }

        foreach ((array) ($manifest['requires']['extensions'] ?? []) as $extension) {
            if (! extension_loaded($extension)) {
                $problems[] = "ext-{$extension}";
            }
        }

        foreach ($package ? $this->releaseEntries($package) : [] as $relative) {
            $target = base_path($relative);
            // Files and links are replaced by renaming, which needs only a
            // writable parent; a directory is moved, so it must be writable too.
            $isDirectory = is_dir($target) && ! is_link($target);

            if (! is_writable(dirname($target)) || ($isDirectory && ! is_writable($target))) {
                $problems[] = __('system.not_writable', ['path' => $relative]);
            }
        }

        return array_values(array_unique($problems));
    }

    private function assertIdle(): void
    {
        $state = $this->state();

        if (($state['status'] ?? null) === 'running') {
            // A stage that stopped sending heartbeats 30 minutes ago is abandoned.
            if (now()->diffInMinutes(\Illuminate\Support\Carbon::parse($state['heartbeat'] ?? $state['started_at'] ?? 'now')) < 30) {
                throw new UpdateException(__('system.busy'));
            }
        }
    }

    // ---------------------------------------------------------------- helpers

    private function backupDatabase(string $backup): string
    {
        $connection = config('database.connections.' . config('database.default'));

        if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            return 'database backup skipped (driver ' . ($connection['driver'] ?? '?') . ')';
        }

        $dump = (new ExecutableFinder())->find('mysqldump') ?? (new ExecutableFinder())->find('mariadb-dump');

        if (! $dump || ! $this->canRunProcesses()) {
            return 'database backup skipped (mysqldump not available)';
        }

        $sql = $backup . '/database.sql';
        $process = new Process([
            $dump, '--single-transaction', '--routines', '--triggers',
            '-h', (string) ($connection['host'] ?? '127.0.0.1'),
            '-P', (string) ($connection['port'] ?? 3306),
            '-u', (string) $connection['username'],
            '--result-file=' . $sql,
            (string) $connection['database'],
        ], base_path(), ['MYSQL_PWD' => (string) ($connection['password'] ?? '')], null, 900);
        $process->run();

        if (! $process->isSuccessful() || ! str_contains($this->tail($sql), 'Dump completed')) {
            File::delete($sql);

            throw new UpdateException('Database backup failed: ' . trim($process->getErrorOutput()));
        }

        // Compress in PHP: no shell pipeline, works with any /bin/sh.
        $file = $sql . '.gz';
        $in = fopen($sql, 'rb');
        $out = gzopen($file, 'wb6');

        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1048576));
        }

        fclose($in);
        gzclose($out);
        File::delete($sql);
        @chmod($file, 0600);

        return 'database backed up to ' . str_replace(base_path() . '/', '', $file);
    }

    private function tail(string $file, int $bytes = 300): string
    {
        if (! is_file($file) || ($size = filesize($file)) === 0) {
            return '';
        }

        $handle = fopen($file, 'rb');
        fseek($handle, max(0, $size - $bytes));
        $tail = (string) fread($handle, $bytes);
        fclose($handle);

        return $tail;
    }

    /**
     * Run artisan in a fresh PHP process so the new code is loaded.
     */
    private function artisan(array $arguments): void
    {
        $php = $this->phpBinary();

        if (! $php) {
            Artisan::call($arguments[0], array_slice($arguments, 1) === ['--force'] ? ['--force' => true] : []);

            return;
        }

        $process = new Process([$php, base_path('artisan'), ...$arguments, '--no-interaction'], base_path(), null, null, 900);
        $process->run();

        if (! $process->isSuccessful()) {
            // Keep the meaningful end of the output, without progress dots or ANSI codes.
            $output = preg_replace('/\e\[[\d;]*m|\.{3,}/', '', $process->getErrorOutput() . "\n" . $process->getOutput());
            $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $output))));

            throw new UpdateException('php artisan ' . $arguments[0] . ': ' . mb_substr(implode(' | ', array_slice($lines, -4)), 0, 500));
        }
    }

    private function maintenance(bool $down): void
    {
        try {
            $down ? $this->artisan(['down', '--retry=30']) : $this->artisan(['up']);
        } catch (Throwable $exception) {
            if (! $down) {
                // Never leave the site in maintenance: remove the marker directly.
                File::delete(storage_path('framework/down'));
            }

            Log::warning('Polirium update maintenance toggle failed', ['exception' => $exception]);
        }
    }

    private function phpBinary(): ?string
    {
        if (! $this->canRunProcesses()) {
            return null;
        }

        $candidates = array_filter([
            config('updater.php_binary'),
            PHP_SAPI === 'cli' ? PHP_BINARY : null,
            PHP_BINDIR . '/php',
            (new ExecutableFinder())->find('php'),
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate) && ! str_contains(basename($candidate), 'fpm')) {
                return $candidate;
            }
        }

        return null;
    }

    private function canRunProcesses(): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists('proc_open') && ! in_array('proc_open', $disabled, true);
    }

    /**
     * Files written while running as root must keep the site user as owner,
     * otherwise PHP-FPM cannot write compiled views and caches.
     *
     * @param list<string> $paths
     */
    private function fixOwnership(array $paths): void
    {
        if (! function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            return;
        }

        $owner = fileowner(base_path());
        $group = filegroup(base_path());

        foreach (array_merge($paths, ['storage', 'bootstrap/cache']) as $relative) {
            $path = base_path($relative);

            if (file_exists($path) && $this->canRunProcesses()) {
                (new Process(['chown', '-R', $owner . ':' . $group, $path]))->run();
            }
        }
    }

    private function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    private function pruneBackups(): void
    {
        $dirs = glob($this->path('backups/*'), GLOB_ONLYDIR) ?: [];
        rsort($dirs);

        foreach (array_slice($dirs, max(1, (int) config('updater.keep_backups', 3))) as $old) {
            File::deleteDirectory($old);
        }
    }

    private function log(array $state, string $level, string $message): array
    {
        $state['log'][] = ['at' => now()->toIso8601String(), 'level' => $level, 'message' => $message];
        Log::{$level}('Polirium update: ' . $message, ['to' => $state['to'] ?? null]);

        return $state;
    }

    private function saveState(array $state): array
    {
        File::ensureDirectoryExists($this->path());
        File::put($this->path('state.json'), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $state;
    }

    private function appendHistory(array $state): void
    {
        $file = $this->path('history.json');
        $history = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        $history[] = array_intersect_key($state, array_flip(['status', 'from', 'to', 'started_at', 'finished_at', 'error', 'backup']));
        File::put($file, json_encode(array_slice($history, -50), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

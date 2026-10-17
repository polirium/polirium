<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SystemSettings;
use App\Updater\ReleaseClient;
use App\Updater\Updater;
use App\Updater\UpdateException;
use App\Updater\Version;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class UpdaterTest extends TestCase
{
    private string $sandbox;

    private string $originalBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sandbox = sys_get_temp_dir() . '/polirium-updater-' . uniqid();
        File::ensureDirectoryExists($this->sandbox . '/site');
        $this->originalBase = $this->app->basePath();
        config(['updater.path' => $this->sandbox . '/work', 'updater.preserve' => ['.env', 'storage', 'public/uploads', 'bootstrap/cache']]);
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBase);
        File::deleteDirectory($this->sandbox);

        parent::tearDown();
    }

    public function test_versions_compare_semantically(): void
    {
        $this->assertSame('1.2.0', Version::normalize('v1.2.0'));
        $this->assertTrue(Version::isNewer('1.10.0', '1.9.9'));
        $this->assertFalse(Version::isNewer('1.0.0', '1.0.0'));
        $this->assertTrue(Version::isNewer('1.1.0', '1.1.0-beta.2'));
        $this->assertTrue(Version::isValid('v2.0.0-rc.1'));
        $this->assertFalse(Version::isValid('latest'));
    }

    public function test_release_entries_skip_installation_owned_paths(): void
    {
        $package = $this->makeTree($this->sandbox . '/package', [
            'app/Foo.php', 'artisan', 'release.json', 'storage/logs/.gitignore',
            'public/index.php', 'public/vendor/app.css', 'public/uploads/should-not-ship.png',
            'bootstrap/app.php', 'bootstrap/cache/.gitignore',
        ]);

        $entries = (new Updater(new ReleaseClient()))->releaseEntries($package);
        sort($entries);

        $this->assertSame(['app', 'artisan', 'bootstrap/app.php', 'public/index.php', 'public/vendor'], $entries);
    }

    public function test_swap_replaces_code_keeps_installation_files_and_rolls_back_exactly(): void
    {
        $site = $this->makeTree($this->sandbox . '/site', [
            'app/Old.php' => 'old', 'app/Stale.php' => 'stale', 'artisan' => 'old-artisan',
            '.env' => 'SECRET=1', 'storage/app/keep.txt' => 'data',
            'public/index.php' => 'old-index', 'public/uploads/logo.png' => 'logo', 'public/robots.txt' => 'custom',
        ]);
        $package = $this->makeTree($this->sandbox . '/package', [
            'app/Old.php' => 'new', 'artisan' => 'new-artisan', 'public/index.php' => 'new-index', 'release.json' => '{}',
        ]);
        $this->app->setBasePath($site);
        $updater = new Updater(new ReleaseClient());
        $backup = $this->sandbox . '/backup';

        $entries = $this->callPrivate($updater, 'swap', [$package, $backup]);

        $this->assertSame('new', file_get_contents($site . '/app/Old.php'));
        $this->assertFileDoesNotExist($site . '/app/Stale.php', 'files removed from the release disappear');
        $this->assertSame('new-index', file_get_contents($site . '/public/index.php'));
        $this->assertSame('SECRET=1', file_get_contents($site . '/.env'));
        $this->assertSame('data', file_get_contents($site . '/storage/app/keep.txt'));
        $this->assertSame('logo', file_get_contents($site . '/public/uploads/logo.png'));
        $this->assertSame('custom', file_get_contents($site . '/public/robots.txt'), 'files the installation added beside the release stay');

        $this->callPrivate($updater, 'undoSwap', [$entries, $backup]);

        $this->assertSame('old', file_get_contents($site . '/app/Old.php'));
        $this->assertSame('stale', file_get_contents($site . '/app/Stale.php'));
        $this->assertSame('old-artisan', file_get_contents($site . '/artisan'));
        $this->assertSame('old-index', file_get_contents($site . '/public/index.php'));
        $this->assertSame('logo', file_get_contents($site . '/public/uploads/logo.png'));
    }

    public function test_checksum_mismatch_stops_before_anything_is_installed(): void
    {
        $zip = $this->makeZip(['artisan' => 'x', 'release.json' => json_encode(['version' => '9.0.0'])]);
        $updater = new Updater($this->fakeReleases($zip, str_repeat('0', 64)));
        $this->app->setBasePath($this->sandbox . '/site');
        File::put($this->sandbox . '/site/VERSION', "1.0.0\n");

        $updater->start('9.0.0');
        $updater->runNextStage(); // download

        $this->expectException(UpdateException::class);
        $this->expectExceptionMessage(__('system.checksum_mismatch'));

        try {
            $updater->runNextStage(); // verify
        } finally {
            $this->assertSame('failed', $updater->state()['status']);
            $this->assertDirectoryDoesNotExist($this->sandbox . '/work/packages/9.0.0');
        }
    }

    public function test_package_with_paths_escaping_the_target_is_rejected(): void
    {
        $zip = $this->makeZip(['artisan' => 'x', '../evil.php' => '<?php', 'release.json' => json_encode(['version' => '9.0.0'])]);
        $updater = new Updater($this->fakeReleases($zip, hash_file('sha256', $zip)));
        $this->app->setBasePath($this->sandbox . '/site');
        File::put($this->sandbox . '/site/VERSION', "1.0.0\n");

        $updater->start('9.0.0');
        $updater->runNextStage();

        $this->expectException(UpdateException::class);
        $this->expectExceptionMessage('Unsafe path');
        $updater->runNextStage();
    }

    public function test_vendor_symlinks_are_recreated_and_links_leaving_the_package_refused(): void
    {
        $manifest = json_encode(['version' => '9.0.0', 'requires' => ['php' => '8.0.0', 'extensions' => []]]);
        $zip = $this->makeZip(['artisan' => 'x', 'release.json' => $manifest, 'platform/core/core.txt' => 'core'], ['vendor/polirium/core' => '../../platform/core']);
        $updater = new Updater($this->fakeReleases($zip, hash_file('sha256', $zip)));
        $this->app->setBasePath($this->sandbox . '/site');
        File::put($this->sandbox . '/site/VERSION', "1.0.0\n");

        $updater->start('9.0.0');
        $updater->runNextStage(); // download
        $state = $updater->runNextStage(); // verify

        $link = $state['extracted'] . '/vendor/polirium/core';
        $this->assertSame('core', file_get_contents($link . '/core.txt'));
        $this->assertTrue(is_link($link) || is_dir($link));

        $updater->clearState();
        $evil = $this->makeZip(['artisan' => 'x', 'release.json' => $manifest], ['vendor/evil' => '../../../../etc']);
        $updater = new Updater($this->fakeReleases($evil, hash_file('sha256', $evil)));
        $updater->start('9.0.0');
        $updater->runNextStage();

        $this->expectException(UpdateException::class);
        $this->expectExceptionMessage('Unsafe symlink');
        $updater->runNextStage();
    }

    public function test_an_older_or_equal_release_is_not_installed_without_force(): void
    {
        $zip = $this->makeZip(['artisan' => 'x']);
        $updater = new Updater($this->fakeReleases($zip, hash_file('sha256', $zip), '1.0.0'));
        $this->app->setBasePath($this->sandbox . '/site');
        File::put($this->sandbox . '/site/VERSION', "1.0.0\n");

        $this->expectException(UpdateException::class);
        $updater->start('1.0.0');
    }

    public function test_requirements_report_missing_extensions_and_newer_php(): void
    {
        $problems = (new Updater(new ReleaseClient()))->requirementProblems([
            'requires' => ['php' => '99.0.0', 'extensions' => ['json', 'definitely_missing_ext']],
        ]);

        $this->assertContains('ext-definitely_missing_ext', $problems);
        $this->assertNotContains('ext-json', $problems);
        $this->assertTrue(collect($problems)->contains(fn ($p) => str_starts_with($p, 'PHP 99.0.0+')));
    }

    public function test_homepage_defaults_to_the_website_when_settings_are_unavailable(): void
    {
        $this->assertSame(SystemSettings::HOMEPAGE_WEBSITE, SystemSettings::homepageMode());
    }

    // ---------------------------------------------------------------- helpers

    private function fakeReleases(string $zip, string $sha, string $version = '9.0.0'): ReleaseClient
    {
        return new class ($zip, $sha, $version) extends ReleaseClient {
            public function __construct(private string $zip, private string $sha, private string $version)
            {
            }

            public function find(string $version): array
            {
                return [
                    'version' => $this->version, 'name' => 'v' . $this->version, 'notes' => '',
                    'asset' => ['name' => 'polirium-' . $this->version . '.zip', 'size' => filesize($this->zip), 'url' => 'asset', 'download_url' => 'asset'],
                    'checksum' => ['name' => 'sha', 'url' => 'sha', 'download_url' => 'sha'],
                ];
            }

            public function latest(bool $fresh = false): ?array
            {
                return $this->find($this->version);
            }

            public function download(string $url, string $destination): void
            {
                copy($this->zip, $destination);
            }

            public function fetchText(string $url): string
            {
                return $this->sha . '  polirium.zip';
            }
        };
    }

    private function makeTree(string $root, array $files): string
    {
        foreach ($files as $path => $content) {
            if (is_int($path)) {
                [$path, $content] = [$content, 'x'];
            }

            File::ensureDirectoryExists(dirname($root . '/' . $path));
            File::put($root . '/' . $path, $content);
        }

        return $root;
    }

    private function makeZip(array $files, array $symlinks = []): string
    {
        $path = $this->sandbox . '/package-' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        // A symlink entry as written by `zip -y`: the content is the link target.
        foreach ($symlinks as $name => $target) {
            $zip->addFromString($name, $target);
            $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, (0120777 << 16));
        }

        $zip->close();

        return $path;
    }

    private function callPrivate(object $object, string $method, array $arguments): mixed
    {
        return (fn () => $this->{$method}(...$arguments))->call($object);
    }
}

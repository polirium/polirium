<?php

namespace Tests\Unit;

use App\Updater\Updater;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Base\Traits\GetMenuDataTrait;
use Polirium\Core\Base\Traits\GetPermission;
use Polirium\Core\Base\Traits\LivewireComponentsTrait;
use ReflectionClass;
use Tests\TestCase;

/**
 * Hosting panels turn on open_basedir per site (LarVPS does when a site is
 * moved to another PHP version). PHP then warns, instead of returning false,
 * when code looks at a path outside the site, and Laravel turns the warning
 * into an exception: every request failed with 500 because module discovery
 * looked for platform/core/LICENSE/config/livewire.php.
 *
 * Each test runs in its own process, restricted to the application and the
 * temp directory, the way such a host runs the site.
 */
class OpenBasedirTest extends TestCase
{
    private function restrictToSite(): void
    {
        $allowed = implode(PATH_SEPARATOR, array_unique([
            base_path(),
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR),
        ]));

        $this->assertNotFalse(ini_set('open_basedir', $allowed), 'open_basedir could not be set');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_module_discovery_skips_files_in_platform_folders(): void
    {
        $this->restrictToSite();

        $this->assertFileExists(platform_path('core/LICENSE'), 'the case this test guards needs a file in platform/core');
        $this->assertNotContains('LICENSE', BaseHelper::platformFolders('core'));
        $this->assertContains('base', BaseHelper::platformFolders('core'));

        $livewire = new class () {
            use LivewireComponentsTrait;

            public function forType(string $type): array
            {
                return $this->getComponentForEachType($type);
            }
        };
        $permissions = new class () {
            use GetPermission;

            public function forType(string $type): array
            {
                return $this->getAvailablePermissionForEachType($type);
            }
        };
        $menus = new class () {
            use GetMenuDataTrait;

            public function forType(string $type): array
            {
                return $this->getAvailableMenuForEachType($type);
            }
        };

        foreach (['core', 'packages', 'modules'] as $type) {
            $this->assertIsArray($livewire->forType($type), "livewire components of {$type}");
            $this->assertIsArray($permissions->forType($type), "permissions of {$type}");
            $this->assertIsArray($menus->forType($type), "menus of {$type}");
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_updater_looks_for_php_only_inside_open_basedir(): void
    {
        $this->restrictToSite();

        $updater = (new ReflectionClass(Updater::class))->newInstanceWithoutConstructor();
        $allowed = (new ReflectionClass(Updater::class))->getMethod('allowedByOpenBasedir');
        $phpBinary = (new ReflectionClass(Updater::class))->getMethod('phpBinary');

        $this->assertTrue($allowed->invoke($updater, base_path('artisan')));
        $this->assertFalse($allowed->invoke($updater, DIRECTORY_SEPARATOR . 'polirium-outside' . DIRECTORY_SEPARATOR . 'php'));

        // Must not raise: the system PHP binary is outside the site.
        $binary = $phpBinary->invoke($updater);
        $this->assertTrue($binary === null || $allowed->invoke($updater, $binary));
    }
}

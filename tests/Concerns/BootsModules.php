<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\Module;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\Base\Service\ModuleManager;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * A database with every module active, the way poli:install leaves it.
 *
 * Module migrations use MySQL syntax, so these tests run only against a
 * MySQL/MariaDB test database and are skipped on SQLite:
 * DB_CONNECTION=mysql DB_DATABASE=<empty test db> vendor/bin/phpunit
 */
trait BootsModules
{
    use RefreshDatabase;

    /**
     * Migrated once per run, before the per-test transaction starts
     * (DDL inside it would commit it on MySQL).
     */
    protected function refreshTestDatabase(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Module migrations need a MySQL/MariaDB test database.');
        }

        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $manager = app(ModuleManager::class);
            $manager->discover();
            Module::query()->update(['status' => Module::STATUS_ACTIVE]);

            $paths = Module::active()->orderBy('name')->get()
                ->map(fn (Module $module) => $module->path . '/database/migrations')
                ->filter(fn (string $path) => is_dir($path))
                ->values()->all();
            Artisan::call('migrate', ['--path' => $paths, '--realpath' => true, '--force' => true]);

            $this->app[Kernel::class]->setArtisan(null);
            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /** Called by Laravel's setUpTraits(), after the database is ready. */
    protected function setUpBootsModules(): void
    {
        // The app booted before the modules were active (first test of a run).
        $manager = app(ModuleManager::class);
        foreach (Module::active()->orderBy('name')->get() as $module) {
            $manager->loadModule($module);
        }
        app('router')->getRoutes()->refreshNameLookups();
    }

    protected function makeBranch(string $name): Branch
    {
        return Branch::create(['uuid' => (string) Str::uuid(), 'name' => $name, 'status' => 1, 'user_id' => 0]);
    }

    /**
     * A staff member of the given branches with the given permissions.
     *
     * @param  list<Branch>  $branches
     * @param  list<string>  $permissions
     */
    protected function makeStaff(array $branches, array $permissions = []): User
    {
        $n = (string) Str::random(8);

        $user = User::create([
            'username' => "staff{$n}",
            'first_name' => 'Staff',
            'last_name' => $n,
            'name' => "Staff {$n}",
            'email' => "staff{$n}@example.test",
            'password' => 'secret-password',
            'super_admin' => 0,
            'status' => 'active',
        ]);

        foreach ($branches as $i => $branch) {
            $user->branches()->attach($branch->id, ['active' => $i === 0 ? 1 : 0]);
        }

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}

<?php

namespace App\Support;

use App\Updater\Version;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Data for the public homepage (welcome.blade.php).
 *
 * Everything is optional: a module that is disabled, not installed or missing
 * its routes simply does not appear, and a missing setting falls back to the
 * Polirium defaults, so the page never errors.
 */
final class Homepage
{
    /**
     * Business areas, keyed by module name. `link` is shown to signed-in users
     * when the route exists and they hold the permission.
     */
    private const MODULES = [
        'product' => ['icon' => 'box', 'link' => ['route' => 'products.index', 'can' => 'products.index']],
        'sale' => ['icon' => 'cart', 'link' => ['route' => 'products.payment', 'can' => 'sales.payment.index']],
        'accounting' => ['icon' => 'wallet', 'link' => ['route' => 'accountings.payment.index', 'can' => 'accountings.payments']],
        'vendor' => ['icon' => 'truck', 'link' => ['route' => 'vendors.purchases.index', 'can' => 'vendors.purchases.index']],
        'customer' => ['icon' => 'users', 'link' => ['route' => 'customers.index', 'can' => 'customers.index']],
        'task' => ['icon' => 'checklist', 'link' => ['route' => 'admin.tasks.index', 'can' => 'tasks.index']],
        'print-forms' => ['icon' => 'printer', 'link' => ['route' => 'print-forms.editor.index', 'can' => 'print-forms.forms.index']],
    ];

    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        $modules = self::activeModules();

        return [
            'brand' => self::brand(),
            'modules' => $modules,
            'shortcuts' => self::shortcuts($modules),
            'version' => self::safe(fn () => Version::current(), null),
            'adminUrl' => url(function_exists('admin_prefix') ? admin_prefix() : 'admin'),
            'loginUrl' => Route::has('login') ? route('login') : url('login'),
            'user' => Auth::user(),
        ];
    }

    /**
     * @return array{name: string, logo: string, custom: bool}
     */
    private static function brand(): array
    {
        $title = trim((string) self::safe(fn () => settings('title'), ''));
        $logo = trim((string) self::safe(fn () => settings('logo'), ''));
        $default = 'vendor/polirium/core/ui/assets/polirium-logo.png';

        if ($logo !== '' && ! str_starts_with($logo, 'http') && ! is_file(public_path(ltrim(parse_url($logo, PHP_URL_PATH) ?: $logo, '/')))) {
            $logo = '';
        }

        return [
            'name' => $title !== '' ? $title : config('app.name', 'Polirium'),
            'logo' => $logo !== '' ? (str_starts_with($logo, 'http') ? $logo : asset(ltrim($logo, '/'))) : asset($default),
            'custom' => $logo !== '',
        ];
    }

    /**
     * Known business modules that are active in this installation.
     *
     * @return list<array<string, mixed>>
     */
    private static function activeModules(): array
    {
        $active = self::safe(function () {
            if (! Schema::hasTable('modules')) {
                return null;
            }

            return DB::table('modules')->where('status', 'active')->pluck('name')->all();
        }, null);

        $cards = [];

        foreach (self::MODULES as $name => $config) {
            // Without a modules table (fresh install) fall back to "routes are registered".
            $enabled = $active !== null ? in_array($name, $active, true) : Route::has($config['link']['route']);

            if ($enabled) {
                $cards[] = ['name' => $name, 'icon' => $config['icon'], 'link' => $config['link']];
            }
        }

        return $cards;
    }

    /**
     * Quick links for the signed-in user: dashboard first, then each active
     * module the user can open.
     *
     * @param list<array<string, mixed>> $modules
     * @return list<array<string, string>>
     */
    private static function shortcuts(array $modules): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $links = [];

        if (Route::has('core.index') && self::safe(fn () => $user->can('dashboard.index'), false)) {
            $links[] = ['key' => 'dashboard', 'icon' => 'layout', 'url' => route('core.index')];
        }

        foreach ($modules as $module) {
            $link = $module['link'];

            if (Route::has($link['route']) && self::safe(fn () => $user->can($link['can']), false)) {
                $links[] = ['key' => $module['name'], 'icon' => $module['icon'], 'url' => route($link['route'])];
            }
        }

        return $links;
    }

    private static function safe(callable $callback, mixed $default): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $default;
        }
    }
}

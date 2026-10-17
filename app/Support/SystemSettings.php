<?php

namespace App\Support;

use Polirium\Core\Settings\Facades\SettingRegistry;
use Throwable;

/**
 * Application-level settings shown under Settings → System.
 */
final class SystemSettings
{
    public const HOMEPAGE_WEBSITE = 'website';

    public const HOMEPAGE_ADMIN = 'admin';

    public static function register(): void
    {
        if (! class_exists(SettingRegistry::class)) {
            return;
        }

        SettingRegistry::group('system', [
            'title' => 'system.settings_title',
            'icon' => 'server',
            'description' => 'system.settings_description',
        ])->add('homepage_mode', [
            'type' => 'select',
            'label' => 'system.homepage_mode',
            'description' => 'system.homepage_mode_description',
            'options' => [
                self::HOMEPAGE_WEBSITE => 'system.homepage_website',
                self::HOMEPAGE_ADMIN => 'system.homepage_admin',
            ],
            'default' => self::HOMEPAGE_WEBSITE,
            'required' => true,
            'validation' => ['required', 'in:' . self::HOMEPAGE_WEBSITE . ',' . self::HOMEPAGE_ADMIN],
        ]);
    }

    public static function homepageMode(): string
    {
        try {
            $mode = settings('homepage_mode', self::HOMEPAGE_WEBSITE);
        } catch (Throwable) {
            // Settings table not ready (fresh install): keep the public page.
            return self::HOMEPAGE_WEBSITE;
        }

        return $mode === self::HOMEPAGE_ADMIN ? self::HOMEPAGE_ADMIN : self::HOMEPAGE_WEBSITE;
    }
}

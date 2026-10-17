<?php

namespace App\Updater;

/**
 * The running version comes from the VERSION file shipped with every release.
 */
final class Version
{
    public static function current(): string
    {
        $file = base_path('VERSION');

        return self::normalize(is_file($file) ? trim((string) file_get_contents($file)) : '0.0.0');
    }

    /**
     * "v1.2.0" → "1.2.0".
     */
    public static function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    public static function isValid(string $version): bool
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', self::normalize($version));
    }

    public static function isNewer(string $candidate, string $current): bool
    {
        return version_compare(self::normalize($candidate), self::normalize($current), '>');
    }

    public static function isPrerelease(string $version): bool
    {
        return str_contains(self::normalize($version), '-');
    }
}

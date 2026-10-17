<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Release source
    |--------------------------------------------------------------------------
    | Updates are published as GitHub releases of this repository. Each release
    | carries a self-contained package (code, submodules, vendor, built assets)
    | and its SHA-256 checksum, produced by .github/workflows/release.yml.
    */
    'repository' => env('POLIRIUM_UPDATE_REPOSITORY', 'polirium/polirium'),

    // Optional token: raises the GitHub API rate limit, needed for private forks.
    'token' => env('POLIRIUM_UPDATE_TOKEN'),

    'asset' => 'polirium-{version}.zip',
    'checksum_asset' => 'polirium-{version}.zip.sha256',

    // Pre-releases (e.g. 1.1.0-beta.1) are offered only when enabled.
    'allow_prereleases' => (bool) env('POLIRIUM_UPDATE_PRERELEASES', false),

    // How long the "latest release" lookup is cached.
    'check_ttl' => (int) env('POLIRIUM_UPDATE_CHECK_TTL', 6 * 3600),

    // Working area for downloads, extracted packages and code backups.
    'path' => storage_path('app/updater'),

    // Code backups kept for rollback (oldest are removed first).
    'keep_backups' => 3,

    /*
    |--------------------------------------------------------------------------
    | Paths that belong to the installation, never to a release
    |--------------------------------------------------------------------------
    */
    'preserve' => [
        '.env',
        'storage',
        'public/uploads',
        'public/storage',
        'bootstrap/cache',
    ],

    // Database dump before migrating (skipped when mysqldump is unavailable).
    'backup_database' => (bool) env('POLIRIUM_UPDATE_BACKUP_DATABASE', true),

    // PHP CLI used to run migrations of the new version in a fresh process.
    'php_binary' => env('POLIRIUM_UPDATE_PHP_BINARY'),
];

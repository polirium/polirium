<?php

namespace App\Updater;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads releases of the configured GitHub repository.
 *
 * A release is usable when it has the package asset; its checksum asset is
 * required too (verified before installing).
 */
class ReleaseClient
{
    private const CACHE_KEY = 'polirium.updater.latest';

    /**
     * Latest installable release, cached for config('updater.check_ttl').
     *
     * @return array<string, mixed>|null
     */
    public function latest(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, (int) config('updater.check_ttl', 21600), function () {
            $releases = $this->http()->get($this->api('releases'), ['per_page' => 20])->throw()->json();

            foreach ($releases as $release) {
                if (($release['draft'] ?? false) || (($release['prerelease'] ?? false) && ! config('updater.allow_prereleases'))) {
                    continue;
                }

                $parsed = $this->parse($release);

                if ($parsed && $parsed['asset']) {
                    return $parsed + ['checked_at' => now()->toIso8601String()];
                }
            }

            return ['version' => null, 'checked_at' => now()->toIso8601String()];
        }) ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $version): array
    {
        $tag = 'v' . Version::normalize($version);

        try {
            $release = $this->http()->get($this->api('releases/tags/' . $tag))->throw()->json();
        } catch (Throwable) {
            throw new UpdateException(__('system.release_missing', ['version' => $version]));
        }

        $parsed = $this->parse($release);

        if (! $parsed || ! $parsed['asset'] || ! $parsed['checksum']) {
            throw new UpdateException(__('system.asset_missing', ['version' => $version]));
        }

        return $parsed;
    }

    /**
     * Asset URLs return the file only for "Accept: application/octet-stream"
     * alone; withHeaders() would merge it with the JSON type and GitHub then
     * answers with the asset's metadata, so the header is replaced.
     */
    public function download(string $url, string $destination): void
    {
        $this->http()
            ->replaceHeaders(['Accept' => 'application/octet-stream'])
            ->timeout(600)
            ->sink($destination)
            ->get($url)
            ->throw();
    }

    public function fetchText(string $url): string
    {
        return (string) $this->http()->replaceHeaders(['Accept' => 'application/octet-stream'])->get($url)->throw()->body();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parse(array $release): ?array
    {
        $version = Version::normalize((string) ($release['tag_name'] ?? ''));

        if (! Version::isValid($version)) {
            return null;
        }

        $assets = collect($release['assets'] ?? [])->keyBy('name');
        $asset = $assets->get(str_replace('{version}', $version, config('updater.asset')));
        $checksum = $assets->get(str_replace('{version}', $version, config('updater.checksum_asset')));

        return [
            'version' => $version,
            'name' => $release['name'] ?: 'v' . $version,
            'notes' => (string) ($release['body'] ?? ''),
            'url' => $release['html_url'] ?? null,
            'published_at' => $release['published_at'] ?? null,
            'prerelease' => (bool) ($release['prerelease'] ?? false),
            'asset' => $asset ? ['name' => $asset['name'], 'size' => (int) $asset['size'], 'url' => $asset['url'], 'download_url' => $asset['browser_download_url']] : null,
            'checksum' => $checksum ? ['name' => $checksum['name'], 'url' => $checksum['url'], 'download_url' => $checksum['browser_download_url']] : null,
        ];
    }

    private function api(string $path): string
    {
        return 'https://api.github.com/repos/' . trim((string) config('updater.repository'), '/') . '/' . $path;
    }

    private function http(): PendingRequest
    {
        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Polirium-Updater/' . Version::current(),
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->timeout(30)->retry(2, 500, throw: false);

        return config('updater.token') ? $request->withToken((string) config('updater.token')) : $request;
    }
}

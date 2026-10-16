<?php

namespace Tests\Feature;

use Tests\TestCase;

class MediaRouteNamesTest extends TestCase
{
    public function test_media_api_routes_have_unique_actionable_names(): void
    {
        $names = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route): ?string => $route->getName())
            ->filter()
            ->values();

        $expected = [
            'api.media.index',
            'api.media.upload',
            'api.media.upload-from-url',
            'api.media.upload-from-base64',
            'api.media.show',
            'api.media.update',
            'api.media.destroy',
            'api.media.bulk-delete',
        ];

        foreach ($expected as $name) {
            $this->assertSame(1, $names->filter(fn (string $routeName): bool => $routeName === $name)->count());
        }

        $this->assertSame([], $names->duplicates()->values()->all());
    }
}

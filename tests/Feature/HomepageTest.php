<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Homepage;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_renders_without_modules_or_settings(): void
    {
        // Testing database has no modules/settings tables: the page must still render.
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('welcome.login'));
        $response->assertSee('Polirium');
    }

    public function test_homepage_data_never_fails_and_lists_no_shortcuts_for_guests(): void
    {
        $data = Homepage::data();

        $this->assertNotEmpty($data['brand']['name']);
        $this->assertNotEmpty($data['brand']['logo']);
        $this->assertSame([], $data['shortcuts']);
        $this->assertIsArray($data['modules']);
    }
}

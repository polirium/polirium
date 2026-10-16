<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RouteCommandsTest extends TestCase
{
    public function test_default_and_translated_route_list_commands_are_registered_separately(): void
    {
        $this->assertSame(0, Artisan::call('route:list', ['--path' => 'install']));
        $this->assertStringContainsString('installer.index', Artisan::output());

        $this->assertSame(0, Artisan::call('route:trans:list', [
            'locale' => 'vi',
            '--path' => 'install',
        ]));
    }
}

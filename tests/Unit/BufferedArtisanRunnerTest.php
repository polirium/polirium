<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Installer\Support\BufferedArtisanRunner;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BufferedArtisanRunnerTest extends TestCase
{
    public function test_direct_command_output_cannot_leak_into_the_http_response(): void
    {
        Artisan::command('installer:test-output-buffering', function (): int {
            echo 'direct-output';
            $this->line('console-output');

            return 0;
        });

        ob_start();
        $output = app(BufferedArtisanRunner::class)->run('installer:test-output-buffering');
        $leakedOutput = ob_get_clean();

        $this->assertSame('', $leakedOutput);
        $this->assertStringContainsString('direct-output', $output);
        $this->assertStringContainsString('console-output', $output);
    }
}

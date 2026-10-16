<?php

declare(strict_types=1);

namespace App\Installer\Support;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class BufferedArtisanRunner
{
    public function run(string $command, array $parameters = []): string
    {
        $initialBufferLevel = ob_get_level();
        ob_start();

        try {
            $exitCode = Artisan::call($command, $parameters);
            $directOutput = $this->drainBuffers($initialBufferLevel);
        } catch (Throwable $exception) {
            $this->drainBuffers($initialBufferLevel);

            throw $exception;
        }

        $output = trim($directOutput . PHP_EOL . Artisan::output());

        if ($exitCode !== 0) {
            throw new RuntimeException($output !== '' ? $output : "Command [{$command}] failed.");
        }

        return $output;
    }

    private function drainBuffers(int $initialBufferLevel): string
    {
        $output = '';

        while (ob_get_level() > $initialBufferLevel) {
            $output = (string) ob_get_clean() . $output;
        }

        return $output;
    }
}

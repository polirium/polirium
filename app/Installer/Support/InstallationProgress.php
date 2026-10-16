<?php

declare(strict_types=1);

namespace App\Installer\Support;

class InstallationProgress
{
    public function __construct(private readonly ?string $progressPath = null)
    {
    }

    public function reset(string $message): void
    {
        $this->write([
            'messages' => [$message],
            'percent' => 0,
            'status' => 'running',
            'timestamp' => time(),
        ]);
    }

    public function report(string $message, int $percent): void
    {
        $snapshot = $this->read();
        $snapshot['messages'][] = $message;
        $snapshot['percent'] = max(0, min(100, $percent));
        $snapshot['status'] = 'running';
        $snapshot['timestamp'] = time();

        $this->write($snapshot);
    }

    public function complete(string $message): void
    {
        $this->finish($message, 'complete', 100);
    }

    public function fail(string $message): void
    {
        $this->finish($message, 'failed', (int) ($this->read()['percent'] ?? 0));
    }

    public function snapshot(): array
    {
        return $this->read();
    }

    private function finish(string $message, string $status, int $percent): void
    {
        $snapshot = $this->read();
        $snapshot['messages'][] = $message;
        $snapshot['percent'] = $percent;
        $snapshot['status'] = $status;
        $snapshot['timestamp'] = time();

        $this->write($snapshot);
    }

    private function read(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return ['messages' => [], 'percent' => 0, 'status' => 'idle', 'timestamp' => 0];
        }

        $snapshot = json_decode((string) file_get_contents($path), true);

        if (! is_array($snapshot)) {
            return ['messages' => [], 'percent' => 0, 'status' => 'idle', 'timestamp' => 0];
        }

        $snapshot['messages'] = array_values(array_filter(
            $snapshot['messages'] ?? [],
            fn (mixed $message): bool => is_string($message) && $message !== '',
        ));

        return $snapshot;
    }

    private function write(array $snapshot): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $temporaryPath = $path . '.tmp';
        file_put_contents(
            $temporaryPath,
            json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX,
        );
        rename($temporaryPath, $path);
    }

    private function path(): string
    {
        return $this->progressPath ?? storage_path('framework/installer-progress.json');
    }
}

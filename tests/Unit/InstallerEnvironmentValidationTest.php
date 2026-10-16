<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Installer\Steps\ConfigurePoliriumEnvironment;
use RelayerCore\LaravelInstaller\Contracts\EnvironmentWriter;
use RuntimeException;
use Tests\TestCase;

class InstallerEnvironmentValidationTest extends TestCase
{
    public function test_database_name_rejects_unsafe_identifiers_before_connecting(): void
    {
        $step = new ConfigurePoliriumEnvironment($this->environmentWriter());

        $this->expectException(RuntimeException::class);

        $step->validate([
            'connection' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'polirium`; DROP DATABASE mysql; --',
            'username' => 'root',
            'password' => '',
            'app_name' => 'Polirium ERP',
            'app_url' => 'https://erp.example.com',
        ]);
    }

    public function test_only_supported_database_drivers_are_accepted(): void
    {
        $step = new ConfigurePoliriumEnvironment($this->environmentWriter());

        $this->expectException(RuntimeException::class);

        $step->validate([
            'connection' => 'sqlite',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'polirium',
            'username' => 'root',
            'password' => '',
            'app_name' => 'Polirium ERP',
            'app_url' => 'https://erp.example.com',
        ]);
    }

    private function environmentWriter(): EnvironmentWriter
    {
        return new class () implements EnvironmentWriter {
            public function get(string $key, $default = null): ?string
            {
                return $default;
            }

            public function set(string $key, string $value): void
            {
            }

            public function fill(array $values): void
            {
            }

            public function save(): bool
            {
                return true;
            }
        };
    }
}

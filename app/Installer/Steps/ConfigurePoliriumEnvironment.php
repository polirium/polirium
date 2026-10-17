<?php

declare(strict_types=1);

namespace App\Installer\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RelayerCore\LaravelInstaller\Contracts\EnvironmentWriter;
use RelayerCore\LaravelInstaller\Steps\ConfigureEnvironment;
use RuntimeException;

class ConfigurePoliriumEnvironment extends ConfigureEnvironment
{
    public function __construct(EnvironmentWriter $env)
    {
        parent::__construct($env);
    }

    public function validate(array $data = []): bool
    {
        $validator = Validator::make($data, [
            'connection' => ['required', 'in:mysql,mariadb'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1024'],
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first());
        }

        return parent::validate($data);
    }

    public function process(array $data = []): void
    {
        $this->validate($data);
        parent::process($data);

        $this->env->fill([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ]);
        $this->env->save();

        // The .env written above is only read on the next boot. The following
        // steps run in this same request, so point the live connection at the
        // database that was just validated instead of the example defaults.
        $connection = (string) ($data['connection'] ?? 'mysql');
        config([
            'database.default' => $connection,
            "database.connections.{$connection}.host" => (string) ($data['host'] ?? '127.0.0.1'),
            "database.connections.{$connection}.port" => (string) ($data['port'] ?? '3306'),
            "database.connections.{$connection}.database" => (string) ($data['database'] ?? ''),
            "database.connections.{$connection}.username" => (string) ($data['username'] ?? ''),
            "database.connections.{$connection}.password" => (string) ($data['password'] ?? ''),
        ]);
        DB::purge($connection);
        DB::setDefaultConnection($connection);
    }
}

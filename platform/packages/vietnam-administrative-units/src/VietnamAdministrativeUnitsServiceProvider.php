<?php

declare(strict_types=1);

namespace Polirium\VietnamAdministrativeUnits;

use Illuminate\Support\ServiceProvider;
use Polirium\VietnamAdministrativeUnits\Console\SyncAdministrativeUnitsCommand;

final class VietnamAdministrativeUnitsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // The web installer runs this command through Artisan after migrations.
        $this->commands([
            SyncAdministrativeUnitsCommand::class,
        ]);
    }
}

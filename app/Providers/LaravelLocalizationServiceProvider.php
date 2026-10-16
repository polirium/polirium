<?php

namespace App\Providers;

use App\Console\Commands\RouteTranslationsListCommand;
use Mcamara\LaravelLocalization\Commands\RouteTranslationsCacheCommand;
use Mcamara\LaravelLocalization\Commands\RouteTranslationsClearCommand;
use Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider as BaseServiceProvider;

class LaravelLocalizationServiceProvider extends BaseServiceProvider
{
    protected function registerCommands(): void
    {
        $this->app->singleton('laravellocalizationroutecache.cache', RouteTranslationsCacheCommand::class);
        $this->app->singleton('laravellocalizationroutecache.clear', RouteTranslationsClearCommand::class);
        $this->app->singleton('laravellocalizationroutecache.list', RouteTranslationsListCommand::class);

        $this->commands([
            'laravellocalizationroutecache.cache',
            'laravellocalizationroutecache.clear',
            'laravellocalizationroutecache.list',
        ]);
    }
}

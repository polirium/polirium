<?php

namespace Polirium\Modules\Vendor\Providers;

use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\Base\Helpers\BaseHelper;

class VendorServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/vendor')
            ->loadConfigurations(['vendor'])
              ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();
    }

    public function register()
    {
         BaseHelper::autoload(__DIR__ . '/../../helpers');
    }
}

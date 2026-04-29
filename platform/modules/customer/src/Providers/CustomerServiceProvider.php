<?php

namespace Polirium\Modules\Customer\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;

class CustomerServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/customer')
            ->loadConfigurations(['customer'])
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

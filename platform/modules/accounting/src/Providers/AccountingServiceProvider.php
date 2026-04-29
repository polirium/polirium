<?php

namespace Polirium\Modules\Accounting\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\UI\Facades\Assets;

class AccountingServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/accounting')
            ->loadConfigurations(['accounting', 'livewire'])
              ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();

        Assets::addOptionalJs([
            'accounting-main' => 'modules/accounting/js/accounting.min.js',
        ]);
    }

    public function register()
    {
        BaseHelper::autoload(__DIR__ . '/../../helpers');
    }
}

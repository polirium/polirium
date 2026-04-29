<?php

namespace Polirium\Modules\PrintForms\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\UI\Facades\Assets;

class PrintFormsServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/print-forms')
            ->loadConfigurations(['settings'])
              ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();

        Assets::addOptionalCss([
            'print-forms-editor' => 'modules/print-forms/css/editor.min.css',
        ]);

        Assets::addOptionalJs([
            'print-forms-editor' => 'modules/print-forms/js/editor.min.js',
        ]);
    }

    public function register()
    {
        BaseHelper::autoload(__DIR__ . '/../../helpers');
    }
}

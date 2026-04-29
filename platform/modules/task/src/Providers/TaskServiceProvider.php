<?php

namespace Polirium\Modules\Task\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;

class TaskServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();
    }

    public function register()
    {
        $this
            ->setNamespace('modules/task')
            ->loadConfigurations(['task']);

        BaseHelper::autoload(__DIR__ . '/../../helpers');
    }
}

<?php

namespace Polirium\Modules\Product\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\Product\Http\Support\ProductSupport;

class ProductServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/product')
            ->loadConfigurations(['product', 'menu', 'livewire', 'permissions'])
            ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();

        $this->app->register(EventServiceProvider::class);

        Assets::addOptionalJs([
            'product-main' => 'modules/product/js/product.min.js',
            'print-helper' => 'modules/product/js/print-helper.min.js',
        ]);
    }

    public function register()
    {
        BaseHelper::autoload(__DIR__ . '/../../helpers');

        $this->app->singleton('polirium:product', function () {
            return new ProductSupport();
        });
    }
}

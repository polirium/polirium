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
        $this->registerGlobalSearch();

        $this
            ->setNamespace('modules/product')
            ->loadConfigurations(['product', 'menu', 'livewire', 'permissions', 'vietqr_banks'])
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

    /**
     * Products in the header search (Ctrl+K).
     */
    protected function registerGlobalSearch(): void
    {
        app(\Polirium\Core\Base\Search\GlobalSearch::class)->register('products', [
            'label' => 'core/base::general.search_group_products',
            'icon' => 'package',
            'permission' => 'products.index',
            'order' => 10,
            'search' => function (string $term, int $limit) {
                $like = \Polirium\Core\Base\Search\GlobalSearch::like($term);

                return \Polirium\Modules\Product\Http\Model\Product::query()
                    ->productBranch()
                    ->where(fn ($query) => $query->where('code', 'like', $like . '%')->orWhere('name', 'like', '%' . $like . '%'))
                    ->orderByRaw('code = ? desc', [$term])
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(['id', 'code', 'name'])
                    ->map(fn ($product) => [
                        'title' => $product->name,
                        'subtitle' => $product->code,
                        'url' => route('products.index', ['name' => $product->name]),
                    ]);
            },
        ]);
    }
}

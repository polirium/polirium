<?php

namespace Polirium\Modules\Vendor\Providers;

use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\Base\Helpers\BaseHelper;

class VendorServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this->registerGlobalSearch();

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

    /**
     * Suppliers in the header search (Ctrl+K), within the current branch.
     */
    protected function registerGlobalSearch(): void
    {
        app(\Polirium\Core\Base\Search\GlobalSearch::class)->register('vendors', [
            'label' => 'core/base::general.search_group_vendors',
            'icon' => 'truck',
            'permission' => 'vendors.index',
            'order' => 40,
            'search' => function (string $term, int $limit) {
                $like = \Polirium\Core\Base\Search\GlobalSearch::like($term);

                return \Polirium\Modules\Vendor\Http\Model\Vendor::query()
                    ->when(user_branch(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                    ->where(fn ($query) => $query->where('name', 'like', '%' . $like . '%')
                        ->orWhere('code', 'like', $like . '%')
                        ->orWhere('phone', 'like', $like . '%'))
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(['id', 'code', 'name', 'phone'])
                    ->map(fn ($vendor) => [
                        'title' => $vendor->name,
                        'subtitle' => collect([$vendor->code, $vendor->phone])->filter()->implode(' · '),
                        'url' => route('vendors.index', ['q' => $vendor->code]),
                    ]);
            },
        ]);
    }
}

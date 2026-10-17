<?php

namespace Polirium\Modules\Customer\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;

class CustomerServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this->registerGlobalSearch();

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

    /**
     * Customers in the header search (Ctrl+K), by name, phone or code.
     */
    protected function registerGlobalSearch(): void
    {
        app(\Polirium\Core\Base\Search\GlobalSearch::class)->register('customers', [
            'label' => 'core/base::general.search_group_customers',
            'icon' => 'users',
            'permission' => 'customers.index',
            'order' => 20,
            'search' => function (string $term, int $limit) {
                $like = \Polirium\Core\Base\Search\GlobalSearch::like($term);

                return \Polirium\Modules\Customer\Http\Model\Customer::query()
                    ->where(fn ($query) => $query->where('name', 'like', '%' . $like . '%')
                        ->orWhere('phone', 'like', $like . '%')
                        ->orWhere('code', 'like', $like . '%'))
                    ->orderByRaw('(phone = ? or code = ?) desc', [$term, $term])
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(['id', 'code', 'name', 'phone'])
                    ->map(fn ($customer) => [
                        'title' => $customer->name,
                        'subtitle' => collect([$customer->code, $customer->phone])->filter()->implode(' · '),
                        'url' => route('customers.index', ['q' => $customer->phone ?: $customer->code]),
                    ]);
            },
        ]);
    }
}

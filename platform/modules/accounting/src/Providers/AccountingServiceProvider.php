<?php

namespace Polirium\Modules\Accounting\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\UI\Facades\Assets;

class AccountingServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this->registerGlobalSearch();

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

    /**
     * Sales invoices in the header search (Ctrl+K), by code, within the
     * current branch.
     */
    protected function registerGlobalSearch(): void
    {
        if (! class_exists(\Polirium\Modules\Product\Http\Model\Payment\Payment::class)) {
            return;
        }

        app(\Polirium\Core\Base\Search\GlobalSearch::class)->register('invoices', [
            'label' => 'core/base::general.search_group_invoices',
            'icon' => 'file-invoice',
            'permission' => 'accountings.payments',
            'order' => 30,
            'search' => function (string $term, int $limit) {
                $like = \Polirium\Core\Base\Search\GlobalSearch::like($term);

                return \Polirium\Modules\Product\Http\Model\Payment\Payment::query()
                    ->with('customer:id,name')
                    ->when(user_branch(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                    ->where('code', 'like', '%' . $like . '%')
                    ->orderByRaw('code = ? desc', [$term])
                    ->latest('id')
                    ->limit($limit)
                    ->get(['id', 'code', 'customer_id', 'value', 'created_at'])
                    ->map(fn ($payment) => [
                        'title' => $payment->code,
                        'subtitle' => collect([
                            $payment->customer?->name,
                            number_format((float) $payment->value, 0, ',', '.'),
                            $payment->created_at?->format('d/m/Y'),
                        ])->filter()->implode(' · '),
                        'url' => route('accountings.payment.show', $payment->id),
                    ]);
            },
        ]);
    }
}

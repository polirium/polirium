<?php

namespace Tests\Feature\Pos;

use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;
use Polirium\Modules\Product\Widgets\RevenueStatsWidget;
use Polirium\Modules\Product\Widgets\SalesSummaryWidget;
use Tests\Concerns\BootsModules;
use Tests\TestCase;

class SalesWidgetsTest extends TestCase
{
    use BootsModules;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-19 15:30:00');

        $branch = $this->makeBranch('Shop A');
        $other = $this->makeBranch('Shop B');
        $this->branchId = $branch->id;
        $this->actingAs($this->makeStaff([$branch], ['widgets.sales', 'widgets.revenue']));

        // Today: two sales at 09:xx, one at 14:xx (one of them still owed), plus
        // a draft and another branch's sale that must not count.
        $this->sale('2026-10-19 09:05:00', 100000, [['method' => 'cash', 'value' => 100000]], 3, 1, [[11, 2, 60000], [12, 1, 40000]]);
        $this->sale('2026-10-19 09:40:00', 50000, [['method' => 'bank', 'value' => 50000]], 1, 1, [[11, 1, 50000]]);
        $this->sale('2026-10-19 14:10:00', 80000, [['method' => 'cod', 'value' => 0]], 2, null, [[13, 2, 80000]]);
        $this->sale('2026-10-19 10:00:00', 999000, [['method' => 'cash', 'value' => 999000]], 9, 2, [[14, 9, 999000]], 'draft');
        $this->sale('2026-10-19 10:00:00', 777000, [['method' => 'cash', 'value' => 777000]], 7, 2, [[14, 7, 777000]], 'success', $other->id);
        // Earlier this week.
        $this->sale('2026-10-16 11:00:00', 30000, [['method' => 'card', 'value' => 30000]], 1, 2, [[12, 1, 30000]]);
        // Yesterday up to the same hour: the comparison period of "today".
        $this->sale('2026-10-18 08:00:00', 115000, [['method' => 'cash', 'value' => 115000]], 2, 1, [[11, 2, 115000]]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sale(string $at, int $value, array $lines, int $items, ?int $customerId, array $products, string $status = 'success', ?int $branchId = null): void
    {
        $payment = Payment::create([
            'branch_id' => $branchId ?? $this->branchId,
            'customer_id' => $customerId,
            'code' => 'BH-' . md5($at . $value),
            'amount_products' => $items,
            'value' => $value,
            'value_payment' => $value,
            'total_cost' => $value,
            'type_payment' => $lines,
            'user_id' => 1,
            'author_id' => 1,
            'status' => $status,
        ]);
        $payment->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();

        foreach ($products as [$productId, $amount, $total]) {
            PaymentProduct::create([
                'product_payment_id' => $payment->id,
                'product_id' => $productId,
                'amount' => $amount,
                'value' => $total,
                'total' => $total,
            ]);
        }
    }

    public function test_sales_summary_is_added_up_in_the_database(): void
    {
        $today = Livewire::test(SalesSummaryWidget::class);

        $this->assertSame(3, $today->viewData('totalOrders'));
        $this->assertSame(6, $today->viewData('totalItems'));
        $this->assertEquals(230000, $today->viewData('totalRevenue'));
        $this->assertSame(1, $today->viewData('customers'));
        $this->assertEquals(67, $today->viewData('withCustomer'));
        $this->assertEquals(100.0, $today->viewData('changes')['revenue']);

        $series = $today->viewData('series');
        $this->assertCount(16, $series['orders']);
        $this->assertSame(2, $series['orders'][9]);
        $this->assertSame(1, $series['orders'][14]);
        $this->assertEquals(150000, $series['revenue'][9]);
        $this->assertSame(0, $series['orders'][10]);

        // Product 11: 2 + 1 sold today, product 13: 2, product 12: 1.
        $top = $today->viewData('topProducts');
        $this->assertSame([11, 13, 12], $top->pluck('product_id')->map(fn ($id) => (int) $id)->all());
        $this->assertEquals(3, $top->first()->total_sold);

        $week = Livewire::test(SalesSummaryWidget::class)->call('setPeriod', 'week');
        $this->assertSame(5, $week->viewData('totalOrders'));
        $weekSeries = $week->viewData('series');
        $this->assertSame([0, 0, 0, 1, 0, 1, 3], $weekSeries['orders']);
    }

    public function test_revenue_stats_split_by_payment_method(): void
    {
        $today = Livewire::test(RevenueStatsWidget::class);

        $this->assertEquals(230000, $today->viewData('totalRevenue'));
        $this->assertSame(3, $today->viewData('totalOrders'));
        $parts = collect($today->viewData('parts'))->pluck('value', 'icon');
        $this->assertEquals(100000, $parts['cash']);
        $this->assertEquals(50000, $parts['building-bank']);
        $this->assertEquals(80000, $parts['clock-dollar']);
        $this->assertEquals(150000, $today->viewData('series')[9]);
        $this->assertEquals(80000, $today->viewData('series')[14]);
    }
}

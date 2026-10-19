<?php

namespace Tests\Feature\Pos;

use Livewire\Livewire;
use Polirium\Core\Base\Http\Livewire\Roles\Datatable\RoleTable;
use Polirium\Core\Base\Http\Models\Role;
use Polirium\Modules\Product\Http\Livewire\Refund\RefundComponent;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Tests\Concerns\BootsModules;
use Tests\TestCase;

class InvoiceAccessTest extends TestCase
{
    use BootsModules;

    private function saleIn(int $branchId): Payment
    {
        return Payment::create([
            'branch_id' => $branchId,
            'code' => 'BH-' . $branchId,
            'value' => 50000,
            'value_payment' => 50000,
            'total_cost' => 50000,
            'type_payment' => [['method' => 'cash', 'value' => 50000, 'label' => 'Cash']],
            'user_id' => 1,
            'author_id' => 1,
            'status' => 'success',
        ]);
    }

    public function test_invoice_pages_of_another_branch_are_not_found(): void
    {
        $mine = $this->makeBranch('Shop A');
        $other = $this->makeBranch('Shop B');
        $foreign = $this->saleIn($other->id);
        $this->actingAs($this->makeStaff([$mine], ['accountings.payments', 'sales.print']));

        $this->get(route('accountings.payment.show', $foreign->id))->assertNotFound();
        $this->get(route('accountings.payment.export', $foreign->id))->assertNotFound();
        $this->get(route('accountings.payment.copy', $foreign->id))->assertNotFound();
        $this->get(route('products.print.print-payment', $foreign->id))->assertNotFound();
        $this->assertSame(1, Payment::count());
    }

    public function test_returns_need_the_return_permission_not_product_editing(): void
    {
        $branch = $this->makeBranch('Shop A');
        $sale = $this->saleIn($branch->id);

        $this->actingAs($this->makeStaff([$branch], ['sales.payment.index', 'products.edit']));
        Livewire::test(RefundComponent::class, ['payment_id' => $sale->id])
            ->call('submitRefund')
            ->assertForbidden();

        $this->actingAs($this->makeStaff([$branch], ['sales.payment.index', 'sales.payment.refund']));
        Livewire::test(RefundComponent::class, ['payment_id' => $sale->id])
            ->call('submitRefund')
            ->assertStatus(200);
    }

    public function test_a_role_is_deleted_only_when_nobody_has_it(): void
    {
        $branch = $this->makeBranch('Shop A');
        $admin = $this->makeStaff([$branch], ['roles.index', 'roles.delete']);
        $used = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $unused = Role::create(['name' => 'Old role', 'guard_name' => 'web']);
        $this->makeStaff([$branch])->assignRole($used);
        $this->actingAs($admin);

        Livewire::test(RoleTable::class)
            ->dispatch('delete-role', id: $used->id)
            ->assertDispatched('error')
            ->dispatch('delete-role', id: $unused->id)
            ->assertDispatched('success');

        $this->assertNotNull(Role::find($used->id));
        $this->assertNull(Role::find($unused->id));
    }
}

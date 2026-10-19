<?php

namespace Tests\Feature\Pos;

use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Polirium\Modules\Product\Http\Livewire\Payment\DraftPaymentListComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\PaymentComponent;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;
use Tests\Concerns\BootsModules;
use Tests\TestCase;

class PosPaymentAuthorizationTest extends TestCase
{
    use BootsModules;

    private function draftIn(int $branchId, string $code = 'BH-DRAFT-1'): Payment
    {
        return Payment::create([
            'branch_id' => $branchId,
            'code' => $code,
            'value' => 50000,
            'value_payment' => 0,
            'total_cost' => 50000,
            'type_payment' => [['method' => 'cash', 'value' => 0, 'label' => 'Cash']],
            'user_id' => 1,
            'author_id' => 1,
            'status' => 'draft',
            'note' => 'original',
        ]);
    }

    /** A service, so the cart needs no stock. */
    private function service(): int
    {
        return Product::create(['uuid' => (string) Str::uuid(), 'name' => 'Gift wrapping', 'code' => 'SV-TEST-1', 'type' => 'service', 'price' => 20000, 'user_id' => 1])->id;
    }

    public function test_the_opened_draft_cannot_be_changed_from_the_browser(): void
    {
        $branch = $this->makeBranch('Shop A');
        $this->actingAs($this->makeStaff([$branch], ['sales.payment.index']));

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(PaymentComponent::class, ['tab_selected' => 'tab-1'])->set('draftId', 99);
    }

    public function test_a_payment_id_sent_by_the_browser_does_not_target_another_branchs_draft(): void
    {
        $mine = $this->makeBranch('Shop A');
        $other = $this->makeBranch('Shop B');
        $foreign = $this->draftIn($other->id);
        $this->actingAs($this->makeStaff([$mine], ['sales.payment.index']));

        Livewire::test(PaymentComponent::class, ['tab_selected' => 'tab-1'])
            ->call('addProduct', $this->service())
            ->set('payment.id', $foreign->id)
            ->set('payment.note', 'overwritten')
            ->call('saveDraft');

        $foreign->refresh();
        $this->assertSame('original', $foreign->note);
        $this->assertSame('draft', $foreign->status);
        $this->assertSame(0, $foreign->products()->count());
        // The cart became a new draft of the cashier's own branch instead.
        $this->assertSame(1, Payment::where('branch_id', $mine->id)->where('status', 'draft')->count());
    }

    public function test_a_sale_in_a_branch_the_cashier_does_not_work_in_is_refused(): void
    {
        $mine = $this->makeBranch('Shop A');
        $other = $this->makeBranch('Shop B');
        $this->actingAs($this->makeStaff([$mine], ['sales.payment.index']));

        Livewire::test(PaymentComponent::class, ['tab_selected' => 'tab-1'])
            ->call('addProduct', $this->service())
            ->set('payment.branch_id', $other->id)
            ->call('save')
            ->assertDispatched('error');

        $this->assertSame(0, Payment::count());
    }

    public function test_another_branchs_draft_cannot_be_opened_or_deleted(): void
    {
        $mine = $this->makeBranch('Shop A');
        $other = $this->makeBranch('Shop B');
        $foreign = $this->draftIn($other->id);
        $own = $this->draftIn($mine->id, 'BH-DRAFT-2');
        $this->actingAs($this->makeStaff([$mine], ['sales.payment.index']));

        Livewire::test(PaymentComponent::class, ['tab_selected' => 'tab-1'])
            ->call('openDraft', $foreign->id, 'tab-1')
            ->assertSet('draftId', null)
            ->call('openDraft', $own->id, 'tab-1')
            ->assertSet('draftId', $own->id);

        $list = Livewire::test(DraftPaymentListComponent::class)->call('showModal', 'tab-1');
        $this->assertSame([$own->id], $list->viewData('drafts')->pluck('id')->all());

        $list->call('deleteDraft', $foreign->id);
        $this->assertNotNull(Payment::find($foreign->id));
    }

    public function test_saving_needs_the_pos_permission(): void
    {
        $branch = $this->makeBranch('Shop A');
        $this->actingAs($this->makeStaff([$branch]));

        Livewire::test(PaymentComponent::class, ['tab_selected' => 'tab-1'])
            ->call('addProduct', $this->service())
            ->call('saveDraft')
            ->assertForbidden();

        $this->assertSame(0, Payment::count());
    }

    public function test_drafts_are_completed_only_through_save(): void
    {
        // completeDraft() booked receipts from stored lines without save()'s checks.
        $this->assertFalse(method_exists(PaymentComponent::class, 'completeDraft'));
    }
}

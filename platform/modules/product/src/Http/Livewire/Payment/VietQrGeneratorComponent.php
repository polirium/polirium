<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\BankAccount;
use Polirium\Modules\Product\Support\VietQrService;

class VietQrGeneratorComponent extends Component
{
    public ?int $bank_account_id = null;

    public string $amount = '';

    public string $description = '';

    public string $template = '';

    public function mount(): void
    {
        $default = BankAccount::query()->where('is_active', true)->where('is_default', true)->first()
            ?? BankAccount::query()->where('is_active', true)->first();

        $this->bank_account_id = $default?->id;
        $this->template = $default?->template ?: 'compact';
    }

    public function updatedBankAccountId($value): void
    {
        $account = BankAccount::find($value);
        if ($account) {
            $this->template = $account->template ?: 'compact';
        }
    }

    #[Computed]
    public function accounts()
    {
        return BankAccount::query()->where('is_active', true)->get();
    }

    #[Computed]
    public function qrUrl(): ?string
    {
        if (! $this->bank_account_id) {
            return null;
        }

        $account = BankAccount::find($this->bank_account_id);
        if (! $account) {
            return null;
        }

        $amount = (int) preg_replace('/\D/', '', $this->amount);

        return VietQrService::imageUrl(
            $account,
            $amount > 0 ? $amount : null,
            $this->description !== '' ? $this->description : null,
            $this->template !== '' ? $this->template : null,
        );
    }

    public function render()
    {
        return view('modules/product::payment.vietqr.generator');
    }
}

<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\BankAccount;
use Polirium\Modules\Product\Support\VietQrService;

class VietQrGeneratorComponent extends Component
{
    public ?int $bank_account_id = null;

    /** Display amount with thousand separators, e.g. 100.000 */
    public string $amount = '';

    public string $description = '';

    /** Stable QR URL — only updated after debounce / explicit refresh */
    public ?string $qrUrl = null;

    public function mount(): void
    {
        $default = BankAccount::query()->where('is_active', true)->where('is_default', true)->first()
            ?? BankAccount::query()->where('is_active', true)->first();

        $this->bank_account_id = $default?->id;
        $this->refreshQrUrl();
    }

    public function updatedBankAccountId(): void
    {
        $this->refreshQrUrl();
    }

    public function updatedAmount($value): void
    {
        $this->amount = $this->formatAmountDisplay((string) $value);
        $this->refreshQrUrl();
    }

    public function updatedDescription(): void
    {
        $this->refreshQrUrl();
    }

    #[Computed]
    public function accounts()
    {
        return BankAccount::query()->where('is_active', true)->get();
    }

    #[Computed]
    public function amountValue(): int
    {
        return (int) preg_replace('/\D/', '', $this->amount);
    }

    public function refreshQrUrl(): void
    {
        if (! $this->bank_account_id) {
            $this->setQrUrl(null);

            return;
        }

        $account = BankAccount::find($this->bank_account_id);
        if (! $account) {
            $this->setQrUrl(null);

            return;
        }

        $amount = $this->amountValue;
        $url = VietQrService::imageUrl(
            $account,
            $amount > 0 ? $amount : null,
            $this->description !== '' ? $this->description : null,
            $account->template ?: 'compact',
        );

        $this->setQrUrl($url);
    }

    protected function setQrUrl(?string $url): void
    {
        if ($this->qrUrl === $url) {
            return;
        }

        $this->qrUrl = $url;
        $this->dispatch('vietqr-updated', url: $url);
    }

    protected function formatAmountDisplay(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        // Avoid leading zeros like 000123 → 123 (keep single 0)
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            $digits = '0';
        }

        return number_format((int) $digits, 0, ',', '.');
    }

    public function render()
    {
        return view('modules/product::payment.vietqr.generator');
    }
}

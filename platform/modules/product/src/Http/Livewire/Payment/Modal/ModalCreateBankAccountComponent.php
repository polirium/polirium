<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\BankAccount;
use Polirium\Modules\Product\Support\VietQrService;

class ModalCreateBankAccountComponent extends Component
{
    public ?BankAccount $bankAccount = null;

    public string $name = '';

    public string $account_number = '';

    public string $bank_code = 'VCB';

    public ?string $account_holder = '';

    public ?string $store_name = '';

    public string $template = 'compact';

    public bool $show_info = true;

    public bool $full_account = true;

    public bool $is_active = true;

    public bool $is_default = false;

    public ?string $note = '';

    #[On('modal-create-bank-account')]
    public function open($id = null): void
    {
        abort_unless((int) auth()->id() === 1, 403);

        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        $this->resetInputs();

        if ($id) {
            $this->bankAccount = BankAccount::find($id);
            if ($this->bankAccount) {
                $this->name = $this->bankAccount->name;
                $this->account_number = $this->bankAccount->account_number;
                $this->bank_code = $this->bankAccount->bank_code;
                $this->account_holder = $this->bankAccount->account_holder;
                $this->store_name = $this->bankAccount->store_name;
                $this->template = $this->bankAccount->template ?: 'compact';
                $this->show_info = (bool) $this->bankAccount->show_info;
                $this->full_account = (bool) $this->bankAccount->full_account;
                $this->is_active = (bool) $this->bankAccount->is_active;
                $this->is_default = (bool) $this->bankAccount->is_default;
                $this->note = $this->bankAccount->note;
            }
        }

        $this->dispatch('modal', 'modal-create-bank-account');
    }

    public function render()
    {
        return view('modules/product::payment.modal.modal-create-bank-account', [
            'banks' => VietQrService::banks(),
        ]);
    }

    public function save(): void
    {
        abort_unless((int) auth()->id() === 1, 403);

        $this->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'bank_code' => 'required|string|max:50',
            'account_holder' => 'nullable|string|max:255',
            'store_name' => 'nullable|string|max:255',
            'template' => 'required|in:compact,qronly,standee',
            'show_info' => 'boolean',
            'full_account' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'note' => 'nullable|string|max:1000',
        ]);

        // Allow empty template string from select
        if ($this->template === '') {
            $this->template = 'compact';
        }

        $bankName = null;
        foreach (VietQrService::banks() as $bank) {
            if (($bank['code'] ?? '') === $this->bank_code) {
                $bankName = $bank['short_name'] ?? $bank['name'] ?? $this->bank_code;
                break;
            }
        }

        if ($this->is_default) {
            BankAccount::query()->update(['is_default' => false]);
        }

        BankAccount::updateOrCreate(
            ['id' => $this->bankAccount?->id],
            [
                'name' => $this->name,
                'account_number' => $this->account_number,
                'bank_code' => $this->bank_code,
                'bank_name' => $bankName,
                'account_holder' => $this->account_holder,
                'store_name' => $this->store_name,
                'template' => $this->template ?: 'compact',
                'show_info' => $this->show_info,
                'full_account' => $this->full_account,
                'is_active' => $this->is_active,
                'is_default' => $this->is_default,
                'note' => $this->note,
            ]
        );

        $this->dispatch('pg:eventRefresh-product-bank-account-table');
        $this->dispatch('modal', 'modal-create-bank-account', 'hide');
        $this->resetInputs();
    }

    public function resetInputs(): void
    {
        $this->bankAccount = null;
        $this->name = '';
        $this->account_number = '';
        $this->bank_code = 'VCB';
        $this->account_holder = '';
        $this->store_name = '';
        $this->template = 'compact';
        $this->show_info = true;
        $this->full_account = true;
        $this->is_active = true;
        $this->is_default = false;
        $this->note = '';
        $this->resetErrorBag();
    }
}

<div class="row">
    <div class="col-lg-5">
        <x-ui::card>
            <div class="card-header">
                <h3 class="card-title">{{ __('Thông tin thanh toán') }}</h3>
            </div>
            <div class="card-body">
                @if ($this->accounts->isEmpty())
                    <div class="alert alert-warning mb-0">
                        {{ __('Chưa có tài khoản ngân hàng. Liên hệ admin (user ID 1) để cấu hình.') }}
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tài khoản ngân hàng') }}</label>
                        <select class="form-select" wire:model.live="bank_account_id">
                            @foreach ($this->accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->name }} — {{ $account->bank_name ?: $account->bank_code }} / {{ $account->account_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <x-form::input
                            type="text"
                            label="{{ __('Số tiền') }}"
                            wire:model.live.debounce.300ms="amount"
                            placeholder="100000"
                        />
                    </div>

                    <div class="mb-3">
                        <x-form::input
                            label="{{ __('Nội dung chuyển khoản') }}"
                            wire:model.live.debounce.300ms="description"
                            placeholder="{{ __('Thanh toan don hang') }}"
                        />
                    </div>

                    <div class="mb-0">
                        <label class="form-label">{{ __('Template') }}</label>
                        <select class="form-select" wire:model.live="template">
                            <option value="compact">compact</option>
                            <option value="qronly">qronly</option>
                            <option value="standee">standee</option>
                        </select>
                    </div>
                @endif
            </div>
        </x-ui::card>
    </div>

    <div class="col-lg-7">
        <x-ui::card>
            <div class="card-header">
                <h3 class="card-title">{{ __('Mã QR') }}</h3>
            </div>
            <div class="card-body text-center">
                @if ($this->qrUrl)
                    <img
                        src="{{ $this->qrUrl }}"
                        alt="VietQR"
                        class="img-fluid border rounded bg-white p-2"
                        style="max-width: 420px;"
                        wire:key="vietqr-{{ md5($this->qrUrl) }}"
                    >
                    <div class="mt-3">
                        <a href="{{ $this->qrUrl }}&download=true" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                            {!! tabler_icon('download') !!} {{ __('Tải QR') }}
                        </a>
                        <a href="{{ $this->qrUrl }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
                            {!! tabler_icon('external-link') !!} {{ __('Mở ảnh') }}
                        </a>
                    </div>
                    <div class="text-muted small mt-2 text-break">{{ $this->qrUrl }}</div>
                @else
                    <div class="text-muted py-5">{{ __('Chọn tài khoản để xem QR') }}</div>
                @endif
            </div>
        </x-ui::card>
    </div>
</div>

<div class="vietqr-page">
    <style>
        .vietqr-page .vietqr-amount input {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.02em;
            min-height: 3.25rem;
        }
        .vietqr-page .vietqr-desc input {
            font-size: 1.05rem;
            min-height: 2.75rem;
        }
        .vietqr-page .vietqr-frame {
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 1rem;
            border: 1px solid var(--tblr-border-color, #e6e7e9);
            padding: 1rem;
        }
        .vietqr-page .vietqr-frame img {
            width: min(100%, 320px);
            height: auto;
            transition: opacity .15s ease;
        }
        .vietqr-page .vietqr-frame img.is-loading {
            opacity: .4;
        }
        @media (min-width: 992px) {
            .vietqr-page .vietqr-amount input {
                font-size: 1.5rem;
                text-align: left;
            }
        }
    </style>

    @if ($this->accounts->isEmpty())
        <div class="alert alert-warning">
            {{ __('Chưa có tài khoản ngân hàng. Liên hệ admin để cấu hình.') }}
        </div>
    @else
        <div class="row g-3 g-lg-4 flex-column-reverse flex-lg-row">
            {{-- Form --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        @if ($this->accounts->count() > 1)
                            <div class="mb-3">
                                <label class="form-label">{{ __('Tài khoản') }}</label>
                                <select class="form-select form-select-lg" wire:model.live="bank_account_id">
                                    @foreach ($this->accounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->name }} — {{ $account->account_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            @php $only = $this->accounts->first(); @endphp
                            <div class="mb-3 text-center text-lg-start">
                                <div class="text-secondary small">{{ __('Tài khoản nhận') }}</div>
                                <div class="fw-semibold">{{ $only->name }}</div>
                                <div class="text-secondary">{{ $only->bank_name ?: $only->bank_code }} · {{ $only->account_number }}</div>
                            </div>
                        @endif

                        <div class="mb-3 vietqr-amount">
                            <label class="form-label">{{ __('Số tiền') }}</label>
                            <div class="input-group input-group-lg">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    class="form-control"
                                    wire:model.live.debounce.500ms="amount"
                                    placeholder="0"
                                    autocomplete="off"
                                    aria-label="{{ __('Số tiền') }}"
                                >
                                <span class="input-group-text">đ</span>
                            </div>
                        </div>

                        <div class="mb-0 vietqr-desc">
                            <label class="form-label">{{ __('Nội dung') }}</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                wire:model.live.debounce.500ms="description"
                                placeholder="{{ __('Thanh toan don hang') }}"
                                autocomplete="off"
                                enterkeyhint="done"
                            >
                        </div>
                    </div>
                </div>
            </div>

            {{-- QR: trên cùng trên mobile, không remount khi Livewire re-render --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body text-center">
                        <div
                            class="vietqr-frame mb-2"
                            wire:ignore
                            x-data="{
                                src: @js($qrUrl),
                                ready: true,
                            }"
                            @vietqr-updated.window="
                                if ($event.detail.url === src) return;
                                ready = false;
                                src = $event.detail.url;
                            "
                        >
                            <img
                                x-show="src"
                                x-cloak
                                :src="src"
                                alt="VietQR"
                                :class="{ 'is-loading': !ready }"
                                @load="ready = true"
                                @error="ready = true"
                                decoding="async"
                            >
                            <div class="text-secondary py-5" x-show="!src" x-cloak>
                                {{ __('Nhập số tiền để tạo QR') }}
                            </div>
                        </div>

                        @if ($amount !== '')
                            <div class="fw-bold fs-3 mb-0">{{ $amount }} đ</div>
                        @endif
                        @if ($description !== '')
                            <div class="text-secondary mt-1">{{ $description }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

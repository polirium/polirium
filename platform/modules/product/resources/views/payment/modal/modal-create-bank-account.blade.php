<x-ui::modal id="modal-create-bank-account" :header="$bankAccount ? __('Sửa tài khoản ngân hàng') : __('Thêm tài khoản ngân hàng')" class="modal-lg">
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-6 mb-3">
                <x-form::input label="{{ __('Tên hiển thị') }}" wire:model="name" required />
            </div>
            <div class="col-md-6 mb-3">
                <x-form::input label="{{ __('Số tài khoản') }}" wire:model="account_number" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('Ngân hàng') }} <span class="text-danger">*</span></label>
                <select class="form-select" wire:model="bank_code">
                    @foreach ($banks as $bank)
                        <option value="{{ $bank['code'] }}">
                            {{ $bank['short_name'] ?? $bank['code'] }} — {{ $bank['name'] ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <x-form::input label="{{ __('Chủ tài khoản') }}" wire:model="account_holder" />
            </div>
            <div class="col-md-6 mb-3">
                <x-form::input label="{{ __('Tên cửa hàng') }}" wire:model="store_name" />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('Template QR') }}</label>
                <select class="form-select" wire:model="template">
                    <option value="compact">compact</option>
                    <option value="qronly">qronly</option>
                    <option value="standee">standee</option>
                </select>
            </div>
            <div class="col-12 mb-3">
                <x-form::textarea label="{{ __('Ghi chú') }}" wire:model="note" rows="2" />
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ba_show_info" wire:model="show_info">
                    <label class="form-check-label" for="ba_show_info">{{ __('Hiện thông tin trên QR') }}</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ba_full_account" wire:model="full_account">
                    <label class="form-check-label" for="ba_full_account">{{ __('Hiện đủ số TK') }}</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ba_is_active" wire:model="is_active">
                    <label class="form-check-label" for="ba_is_active">{{ __('Kích hoạt') }}</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ba_is_default" wire:model="is_default">
                    <label class="form-check-label" for="ba_is_default">{{ __('Mặc định') }}</label>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('Hủy') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Lưu') }}</button>
        </div>
    </form>
</x-ui::modal>

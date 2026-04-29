<x-ui::modal id="modal-create-payment-method" :header="$paymentMethod ? __('Sửa phương thức thanh toán') : __('Thêm phương thức thanh toán')" class="modal-md">
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-12 mb-3">
                <x-form::input
                    label="{{ trans('modules/product::product.method_name') }}"
                    name="name"
                    wire:model.defer="name"
                    required
                />
            </div>
            <div class="col-12 mb-3">
                <x-form::input
                    label="{{ trans('modules/product::product.method_code') }}"
                    name="code"
                    wire:model.defer="code"
                    required
                    :disabled="$paymentMethod ? true : false"
                    helper="{{ trans('modules/product::product.code_hint') }}"
                />
            </div>
            <div class="col-12 mb-3">
                <x-form::textarea
                    label="{{ trans('modules/product::product.description') }}"
                    name="description"
                    wire:model.defer="description"
                    rows="3"
                />
            </div>
            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" wire:model.defer="is_active">
                    <label class="form-check-label" for="is_active">{{ __('Kích hoạt') }}</label>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_default" wire:model.defer="is_default">
                    <label class="form-check-label" for="is_default">{{ trans('modules/product::product.set_default') }}</label>
                </div>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">{{ trans('modules/product::product.target_payment_status_hint') }}</label>
                <div class="form-selectgroup">
                    <label class="form-selectgroup-item">
                        <input type="radio" name="target_payment_status" value="completed" class="form-selectgroup-input" wire:model.defer="target_payment_status">
                        <span class="form-selectgroup-label">
                            {!! tabler_icon('checks') !!}
                            {{ trans('modules/product::product.completed_paid') }}
                        </span>
                    </label>
                    <label class="form-selectgroup-item">
                        <input type="radio" name="target_payment_status" value="pending" class="form-selectgroup-input" wire:model.defer="target_payment_status">
                        <span class="form-selectgroup-label">
                            {!! tabler_icon('clock') !!}
                            {{ trans('modules/product::product.pending_cod') }}
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('Hủy') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Lưu') }}</button>
        </div>
    </form>
</x-ui::modal>

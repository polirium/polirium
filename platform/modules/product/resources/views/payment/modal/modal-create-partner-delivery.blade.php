<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-partner-delivery" header="Đối tác giao hàng" class="modal-lg">
            <div class="row">
                <div class="col-md-6 col">
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Loại đối tác</label>
                            </div>
                            <div class="col-md-4">
                                <x-form::radio wire:model="input.type" label="Cá nhân" value="person" />
                            </div>
                            <div class="col-md-4">
                                <x-form::radio wire:model="input.type" label="Công ty" value="company" />
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <x-form::input wire:model="input.name" label="Tên đối tác" />
                    </div>
                    <div class="mb-3">
                        <x-form::input wire:model="input.address" label="Địa chỉ" />
                    </div>
                    <div class="mb-3">
                        <x-form::input wire:model="input.phone" label="Điện thoại" />
                    </div>
                    <div class="mb-3">
                        <x-form::input wire:model="input.email" label="Email" />
                    </div>
                </div>

                <div class="col-md-6 col">
                    <div class="mb-3">
                        <x-form::input wire:model="input.code" label="Mã đối tác" />
                    </div>
                    <div class="mb-3">
                        <x-form::select wire:model.live="input.province_id" :label="trans('modules/product::product.city_province')" :options="$list['provinces']" tomselect />
                    </div>
                    <div class="mb-3">
                        <x-form::select wire:model.live="input.district_id" :label="trans('modules/product::product.district')" :options="$list['districts']" tomselect />
                    </div>
                    <div class="mb-3">
                        <x-form::select wire:model="input.ward_id" :label="trans('modules/product::product.ward')" :options="$list['wards']" tomselect />
                    </div>
                    <div class="mb-3">
                        <x-form::input wire:model="input.note" label="Ghi chú" />
                    </div>
                </div>
            </div>

            <x-slot name="footer">
                <button type="submit" class="btn btn-success">
                    {{ tabler_icon('device-floppy') }}
                    {{ trans('Lưu') }}
                </button>
            </x-slot>
        </x-ui::modal>
    </form>
</div>

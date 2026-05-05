<div x-data="{
    confirmDeleteId: null,
    confirmDeleteInput: '',
    showDeleteModal: false,
    openDeleteConfirm(id) {
        this.confirmDeleteId = id;
        this.confirmDeleteInput = '';
        this.showDeleteModal = true;
        this.$nextTick(() => this.$refs.deleteInput?.focus());
    },
    confirmDelete() {
        if (this.confirmDeleteInput === 'DELETE') {
            $wire.removeProduct(this.confirmDeleteId);
            this.showDeleteModal = false;
        }
    }
}">
    <div class="row">
        <div class="col-md-9">
            <x-ui::card>
                <div class="d-flex gap-2 mb-3">
                    <x-ui.button wire:click="$dispatch('show-modal-create-product')" color="success" icon="plus" :label="trans('modules/product::product.create')" />
                    <x-ui.button wire:click="$dispatch('show-modal-import-purchase')" color="outline-primary" icon="file-upload" :label="trans('modules/vendor::purchase.import_from_excel')" />
                </div>

                <div class="w-100">
                    <x-form::autocomplete wire:model.live="search">
                        @foreach ($lists['products'] as $item)
                            <x-form::autocomplete.item wire:click="selectProduct({{ $item['id'] }})" class="w-100">
                                <b>{{ $item['name'] }} - {{ $item['unit'] }}</b> <br>
                                <span>{{ $item['code'] }}@can('vendors.purchases.view-price') - Giá: {{ core_number_format($item['cost']) }}@endcan</span> <br>
                                <span>Tồn kho: {{ isset($item['amount']) ? $item['amount'] : 0 }}</span>
                            </x-form::autocomplete.item>
                        @endforeach
                    </x-form::autocomplete>
                </div>

                <br>
                <br>

                <div class="table-responsive">
                <x-ui::table striped class="table-bordered" style="table-layout: auto;">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="width: 40px;">#</th>
                            <th style="min-width: 100px;">{{ trans('modules/product::product.code') }}</th>
                            <th style="min-width: 200px;">{{ trans('modules/product::product.name') }}</th>
                            <th style="min-width: 80px; white-space: nowrap;">{{ trans('modules/vendor::purchase.stock') }}</th>
                            <th style="min-width: 140px; white-space: nowrap;">{{ trans('modules/vendor::purchase.quantity') }}</th>
                            @can('vendors.purchases.view-price')
                            <th style="min-width: 180px; white-space: nowrap;">{{ trans('modules/vendor::purchase.unit_price') }}</th>
                            
                            <th style="min-width: 200px; white-space: nowrap;">{{ trans('modules/vendor::purchase.discount') }}</th>
                            <th style="min-width: 180px; white-space: nowrap;">{{ trans('modules/vendor::purchase.total_amount_column') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $key => $item)
                            <tr>
                                <td>
                                    @if(($state['status'] ?? '') !== 'success')
                                        <x-ui::button
                                            color="danger"
                                            size="sm"
                                            icon="trash"
                                            :ghost="true"
                                            wire:click="removeProduct({{ $key }})"
                                            title="{{ trans('core/base::general.delete') }}"
                                        />
                                    @elsecan('vendors.purchases.delete')
                                        <x-ui::button
                                            color="danger"
                                            size="sm"
                                            icon="trash"
                                            :ghost="true"
                                            x-on:click="openDeleteConfirm({{ $key }})"
                                            title="{{ trans('core/base::general.delete') }}"
                                        />
                                    @endif
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td style="white-space: nowrap;">{{ $item['product']['code'] }}</td>
                                <td>
                                    <span>{{ $item['product']['name'] }}</span>
                                    <x-form::input :placeholder="trans('core/base::general.note')" wire:model="products.{{ $key }}.note" class="form-control-sm" />
                                </td>
                                <td style="white-space: nowrap;">{{ isset($item['product']['amount']) ? $item['product']['amount'] : 0 }}</td>
                                <td>
                                    <x-form::input type="number" wire:model.blur="products.{{ $key }}.amount" style="min-width: 100px;" />
                                </td>
                                @can('vendors.purchases.view-price')
                                <td>
                                    <x-form::currency wire:model.blur="products.{{ $key }}.price" />
                                </td>
                                <td>
                                    <x-form::currency wire:model.blur="products.{{ $key }}.discount_value">
                                        <x-slot name="append">
                                            <x-ui.button :color="$item['discount_type'] === 'percent' ? 'primary' : 'default'" icon="percentage" wire:click="$set('products.{{ $key }}.discount_type', 'percent')" />
                                            <x-ui.button :color="$item['discount_type'] === 'number' ? 'primary' : 'default'" icon="currency-dong" wire:click="$set('products.{{ $key }}.discount_type', 'number')" />
                                        </x-slot>
                                    </x-form::currency>
                                </td>
                                <td style="white-space: nowrap;">
                                    {{ core_number_format($item['value']) }}
                                </td>
                                @endcan
                            </tr>
                        @empty

                        @endforelse
                    </tbody>
                </x-ui::table>
                </div>
            </x-ui::card>
        </div>

        <div class="col-md-3">
            <x-ui::card>
                <x-ui::table class="table-bordered">
                    <tr>
                        <td colspan="999">
                            <x-form::select wire:model.live="state.vendor_id" :label="trans('modules/vendor::vendor.name')" :options="$lists['vendors']" tomselect>
                                <x-slot name="append">
                                    <x-ui.button type="button"
                                        :color="$state['vendor_id'] ? 'warning' : 'success'"
                                        :icon="$state['vendor_id'] ? 'pencil' : 'plus'"
                                        wire:click="$dispatch('show-modal-create-vendor', { id: {{ $state['vendor_id'] ?: 0 }} })"
                                    />
                                </x-slot>
                            </x-form::select>
                        </td>
                    </tr>

                    @if ($state['vendor_id'])
                        <tr>
                            <td><b>{{ trans('modules/vendor::purchase.debt') }}:</b></td>
                            <td class="text-end">{{ core_number_format($this->vendor?->debt) }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td class="w-50"><b>{{ trans('modules/vendor::purchase.code') }}:</b></td>
                        <td class="text-end">
                            <x-form::input wire:model="state.code" />
                        </td>
                    </tr>

                    @can('vendors.purchases.view-price')
                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.total_value') }}:</b> <span class="badge badge-primary">{{ count((array)$products) }}</span></td>
                        <td class="text-end">
                            {{ core_number_format($state['total'] ?? 0) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.discount') }}:</b></td>
                        <td class="text-end">
                            <x-form::currency wire:model.live="state.discount_value">
                                <x-slot name="append">
                                    <x-ui.button :color="($state['discount_type'] ?? 'percent') === 'percent' ? 'primary' : 'default'" icon="percentage" wire:click="$set('state.discount_type', 'percent')" />
                                    <x-ui.button :color="($state['discount_type'] ?? 'percent') === 'number' ? 'primary' : 'default'" icon="currency-dong" wire:click="$set('state.discount_type', 'number')" />
                                </x-slot>
                            </x-form::currency>
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.need_pay') }}:</b></td>
                        <td class="text-end">
                            {{ core_number_format($state['need_pay'] ?? 0) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.value') }}:</b></td>
                        <td class="text-end">
                            <x-form::currency wire:model.live="state.value" />
                        </td>
                    </tr>
                    @endcan

                    <tr>
                        <td colspan="999">
                            <x-form::input wire:model.live="state.note" :label="trans('core/base::general.note')" />

                            <x-ui::errors />
                        </td>
                    </tr>

                    <tr>
                        <td>
                            @if(!in_array($state['status'] ?? '', ['success', 'completed', 'paid']))
                                <x-ui.button color="primary" icon="device-floppy" wire:click="save('temp')" :label="trans('modules/vendor::purchase.status.temp')" style="width: 100%;" />
                            @endif
                        </td>
                        <td>
                            @if ($purchase->exists && in_array($state['status'] ?? '', ['success', 'completed', 'paid']))
                                <x-ui.button color="success" icon="device-floppy" wire:click="save('success')" label="Lưu" style="width: 100%;" />
                            @else
                                <x-ui.button color="success" icon="device-floppy" wire:click="save('success')" :label="trans('modules/vendor::purchase.status.success')" style="width: 100%;" />
                            @endif
                        </td>
                    </tr>
                </x-ui::table>
            </x-ui::card>
        </div>
    </div>

    @livewire('modules/product::index.modal.modal-create-product')
    @livewire('modules/vendor::index.modal.modal-create-vendor')
    @livewire('modules/vendor::purchase.modal.modal-import-purchase')

    {{-- Confirm Delete Product Modal --}}
    <div class="modal modal-blur fade" :class="{ 'show': showDeleteModal }" :style="showDeleteModal ? 'display: block;' : 'display: none;'" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <div class="text-danger mb-2">
                        {!! tabler_icon('alert-triangle', ['class' => 'icon-lg']) !!}
                    </div>
                    <h3>{{ __('Xác nhận xóa sản phẩm') }}</h3>
                    <div class="text-secondary mb-3">
                        {{ __('Phiếu đã hoàn thành. Nhập') }} <strong>DELETE</strong> {{ __('để xác nhận xóa.') }}
                    </div>
                    <input
                        type="text"
                        class="form-control text-center fw-bold"
                        x-model="confirmDeleteInput"
                        x-ref="deleteInput"
                        @keydown.enter="confirmDelete()"
                        placeholder="DELETE"
                    >
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button type="button" class="btn w-100" @click="showDeleteModal = false">{{ __('Hủy bỏ') }}</button>
                            </div>
                            <div class="col">
                                <button
                                    type="button"
                                    class="btn btn-danger w-100"
                                    :disabled="confirmDeleteInput !== 'DELETE'"
                                    @click="confirmDelete()"
                                >
                                    {{ __('Xóa sản phẩm') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="{ 'show': showDeleteModal }" x-show="showDeleteModal" @click="showDeleteModal = false"></div>
</div>

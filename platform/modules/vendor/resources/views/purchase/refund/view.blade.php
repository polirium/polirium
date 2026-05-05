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
        <div class="col-md-8">
            <x-ui::card>
                <div class="d-flex mb-3 gap-2">
                    @can('vendors.refunds.edit')
                        <x-ui.button wire:click="$dispatch('show-modal-create-product')" color="success" icon="plus" :label="trans('modules/product::product.create')" />
                        <x-ui.button wire:click="$dispatch('show-modal-import-refund')" color="outline-primary" icon="file-upload" :label="trans('modules/vendor::purchase.import_from_excel')" />
                    @endcan
                </div>

                <div class="w-100">
                    <x-form::autocomplete wire:model.live="search">
                        @foreach ($lists['products'] as $item)
                            <x-form::autocomplete.item wire:click="selectProduct({{ $item['id'] }})" class="w-100">
                                <b>{{ $item['name'] }} - {{ $item['unit'] }}</b> <br>
                                <span>{{ $item['code'] }}@can('vendors.refunds.view-price')
                                    - Giá: {{ core_number_format($item['cost']) }}
                                @endcan
                            </span> <br>
                            <span>{{ trans('modules/vendor::purchase.stock_label') }} {{ isset($item['amount']) ? $item['amount'] : 0 }}</span>
                        </x-form::autocomplete.item>
                    @endforeach
                </x-form::autocomplete>
            </div>

            <br>
            <br>

            <x-ui::table striped class="table-bordered">
                <thead>
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ trans('modules/product::product.code') }}</th>
                        <th>{{ trans('modules/product::product.name') }}</th>
                        <th>{{ trans('modules/product::product.unit') }}</th>
                        <th>{{ trans('modules/vendor::purchase.quantity') }}</th>
                        <th>{{ trans('modules/vendor::purchase.stock') }}</th>
                        @can('vendors.refunds.view-price')
                            <th>{{ trans('modules/vendor::purchase.import_price') }}</th>
                            <th>{{ trans('modules/vendor::purchase.refund_price') }}</th>
                            <th>{{ trans('modules/vendor::purchase.total_amount_column') }}</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $key => $item)
                        <tr>
                            <td>
                                @if (($refund->status ?? '') !== 'success')
                                    <x-ui::button
                                                  color="danger"
                                                  size="sm"
                                                  icon="trash"
                                                  :ghost="true"
                                                  wire:click="removeProduct({{ $key }})"
                                                  title="{{ trans('core/base::general.delete') }}" />
                                @elsecan('vendors.refunds.delete')
                                    <x-ui::button
                                                  color="danger"
                                                  size="sm"
                                                  icon="trash"
                                                  :ghost="true"
                                                  x-on:click="openDeleteConfirm({{ $key }})"
                                                  title="{{ trans('core/base::general.delete') }}" />
                                @endif
                            </td>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item['product']['code'] }}</td>
                            <td>
                                <span>{{ $item['product']['name'] }}</span>
                                <x-form::input :placeholder="trans('core/base::general.note')" wire:model="products.{{ $key }}.note" class="form-control-sm" />
                            </td>
                            <td>{{ $item['product']['unit'] }}</td>
                            <td>
                                <x-form::input type="number" wire:model.live="products.{{ $key }}.amount" />
                            </td>
                            <td>{{ isset($item['product']['amount']) ? $item['product']['amount'] : 0 }}</td>
                            @can('vendors.refunds.view-price')
                                <td>{{ core_number_format($item['product']['cost']) }}</td>
                                <td>
                                    <x-form::currency wire:model.live="products.{{ $key }}.price" />
                                </td>
                                <td>{{ core_number_format($item['value']) }}</td>
                            @endcan
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </x-ui::table>
        </x-ui::card>
    </div>

    <div class="col-md-4">
        <x-ui::card>
            <x-ui::table class="table-bordered">
                <tr>
                    <td colspan="999">
                        <label class="d-block ui-form-label mb-2">{{ trans('modules/vendor::vendor.name') }}</label>
                        <div class="row g-1 align-items-center mb-3">
                            <div class="col">
                                <x-form::select
                                                id="vendor_id_select"
                                                wire:model.live="vendor_id"
                                                :options="$this->vendors"
                                                tomselect
                                                compact
                                                class="mb-0" />
                            </div>
                            <div class="col-auto">
                                <button type="button"
                                        wire:key="vendor-btn-{{ $vendor_id ?: 'new' }}"
                                        class="btn btn-icon {{ $vendor_id ? 'btn-warning' : 'btn-ghost-success' }}"
                                        wire:click="$dispatch('show-modal-create-vendor', { id: {{ $vendor_id ?: 0 }} })"
                                        style="width: 32px; height: 32px; padding: 0;">
                                    {!! tabler_icon($vendor_id ? 'pencil' : 'plus', ['class' => 'icon']) !!}
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @if ($refund->vendor_id)
                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.debt') }}</b></td>
                        <td class="text-end">{{ core_number_format($this->vendor?->debt) }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="w-50"><b>{{ trans('modules/vendor::purchase.refund.code') }}:</b></td>
                    <td class="text-end">
                        <x-form::input wire:model="refund.code" />
                    </td>
                </tr>

                <tr>
                    <td class="w-50"><b>{{ trans('core/base::general.status') }}:</b></td>
                    <td class="text-end">{{ $refund->status_name }}</td>
                </tr>

                @can('vendors.refunds.view-price')
                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.refund.total') }}:</b> <span class="badge badge-primary">{{ count((array) $products) }}</span></td>
                        <td class="text-end">
                            {{ core_number_format($refund->total) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.discount') }}:</b></td>
                        <td class="text-end">
                            <x-form::currency wire:model.live="refund.discount_value">
                                <x-slot name="append">
                                    <x-ui.button :color="$refund->discount_type === 'percent' ? 'primary' : 'default'" icon="percentage" wire:click="$set('refund.discount_type', 'percent')" />
                                    <x-ui.button :color="$refund->discount_type === 'number' ? 'primary' : 'default'" icon="currency-dong" wire:click="$set('refund.discount_type', 'number')" />
                                </x-slot>
                            </x-form::currency>
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.refund.need_paid') }}:</b></td>
                        <td class="text-primary text-end">
                            {{ core_number_format($purchase?->need_pay) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.refund.value') }}:</b></td>
                        <td class="text-end">
                            <x-form::currency wire:model.live="refund.value" />
                        </td>
                    </tr>

                    <tr>
                        <td><b>{{ trans('modules/vendor::purchase.debt_calculate') }}:</b></td>
                        <td class="text-primary text-end">
                            {{ core_number_format($refund?->value - $purchase?->need_pay) }}
                        </td>
                    </tr>
                @endcan

                <tr>
                    <td colspan="999">
                        <x-form::input wire:model.live="refund.note" :label="trans('core/base::general.note')" />

                        <x-ui::errors />
                    </td>
                </tr>

                @can('vendors.refunds.edit')
                    <tr>
                        <td class="w-50">
                            <x-ui.button color="primary" icon="device-floppy" wire:click="save('temp')" :label="trans('modules/vendor::purchase.refund.status.temp')" style="width: 100%;" />
                        </td>
                        <td>
                            @if ($refund->exists && $refund->status === 'success')
                                <x-ui.button color="success" icon="device-floppy" wire:click="save('success')" label="Lưu" style="width: 100%;" />
                            @else
                                <x-ui.button color="success" icon="device-floppy" wire:click="save('success')" :label="trans('modules/vendor::purchase.refund.status.success')" style="width: 100%;" />
                            @endif
                        </td>
                    </tr>
                @endcan
            </x-ui::table>
        </x-ui::card>
    </div>
</div>
@livewire('modules/vendor::index.modal.modal-create-vendor')
@livewire('modules/vendor::purchase.modal.modal-import-refund')

{{-- Confirm Delete Product Modal --}}
<div class="modal modal-blur fade" :class="{ 'show': showDeleteModal }" :style="showDeleteModal ? 'display: block;' : 'display: none;'" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-status bg-danger"></div>
            <div class="modal-body py-4 text-center">
                <div class="text-danger mb-2">
                    {!! tabler_icon('alert-triangle', ['class' => 'icon-lg']) !!}
                </div>
                <h3>{{ __('Xác nhận xóa sản phẩm') }}</h3>
                <div class="text-secondary mb-3">
                    {{ __('Phiếu đã hoàn thành. Nhập') }} <strong>DELETE</strong> {{ __('để xác nhận xóa.') }}
                </div>
                <input
                       type="text"
                       class="form-control fw-bold text-center"
                       x-model="confirmDeleteInput"
                       x-ref="deleteInput"
                       @keydown.enter="confirmDelete()"
                       placeholder="DELETE">
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
                                    @click="confirmDelete()">
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

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
                {{-- Search bar: ẩn khi viewMode --}}
                @if (!$viewMode)
                    <div class="w-100">
                        <x-form::autocomplete wire:model.live="search" :placeholder="trans('modules/product::product.search_placeholder')">
                            @if (!empty($this->searchProducts))
                                @foreach ($this->searchProducts as $item)
                                    <x-form::autocomplete.item
                                                               style="z-index: 999;"
                                                               class="bg-light"
                                                               wire:click="selectProduct({{ $item->id }})">
                                        <div class="d-flex justify-content-between">
                                            <span><b>{{ $item->name }}</b></span>
                                            <span class="text-primary"><b>{{ core_number_format($item->cost ?? 0) }}</b></span>
                                        </div>
                                        <span>{{ $item->code }}</span> <br>
                                        <span>{{ trans('modules/product::stock.inventory') }}: {{ core_number_format($item->amount ?? 0) }}</span>
                                    </x-form::autocomplete.item>
                                @endforeach
                            @endif
                        </x-form::autocomplete>
                    </div>

                    {{-- Import Excel --}}
                    @can('products.stock.create')
                        <div class="d-flex align-items-center mb-3 mt-3 gap-2" x-data="{ showImport: false }">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="showImport = !showImport">
                                {!! tabler_icon('file-import', ['class' => 'icon icon-sm me-1']) !!}
                                {{ __('Nhập từ Excel') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="downloadImportTemplate">
                                {!! tabler_icon('download', ['class' => 'icon icon-sm me-1']) !!}
                                {{ __('Tải file mẫu') }}
                            </button>

                            <div x-show="showImport" x-transition class="d-flex align-items-center ms-2 gap-2">
                                <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="form-control form-control-sm" style="max-width: 250px;">
                                <button type="button" class="btn btn-primary btn-sm" wire:click="importFromExcel" wire:loading.attr="disabled">
                                    <span wire:loading wire:target="importFromExcel" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ __('Nhập') }}
                                </button>
                            </div>
                        </div>
                    @endcan
                    @endif

                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session()->has('warning'))
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Filter Buttons --}}
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <x-ui.button
                                         type="button"
                                         :color="$filter == 'all' ? 'primary' : 'outline-primary'"
                                         wire:click="$set('filter', 'all')"
                                         :label="trans('modules/product::stock.all', ['count' => count($products)])" />

                            <x-ui.button
                                         type="button"
                                         :color="$filter == 'matched' ? 'success' : 'outline-success'"
                                         wire:click="$set('filter', 'matched')"
                                         :label="trans('modules/product::stock.matched', ['count' => $this->matchedCount])" />

                            <x-ui.button
                                         type="button"
                                         :color="$filter == 'mismatched' ? 'danger' : 'outline-danger'"
                                         wire:click="$set('filter', 'mismatched')"
                                         :label="trans('modules/product::stock.mismatched', ['count' => $this->mismatchedCount])" />
                        </div>
                    </div>

                    <x-ui::table striped class="table-bordered">
                        <thead>
                            <tr>
                                @if (!$viewMode)
                                    <th></th>
                                @endif
                                <th>{{ trans('#') }}</th>
                                <th>{{ trans('modules/product::product.code') }}</th>
                                <th>{{ trans('modules/product::product.name') }}</th>
                                <th>{{ trans('modules/product::product.unit') }}</th>
                                <th>{{ trans('modules/product::stock.inventory') }}</th>
                                <th>{{ trans('modules/product::stock.actual') }}</th>
                                <th>{{ trans('modules/product::stock.quantity_difference') }}</th>
                                <th>{{ trans('modules/product::stock.value_difference') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->filteredProducts as $key => $item)
                                <tr>
                                    @if (!$viewMode)
                                        <td>
                                            @if (($stock->status ?? '') !== 'completed')
                                                <x-ui.button color="danger" icon="trash" wire:click="removeProduct({{ $key }})" />
                                            @elsecan('products.stock.delete')
                                                <x-ui.button color="danger" icon="trash"
                                                             x-on:click="openDeleteConfirm({{ $key }})" />
                                            @endif
                                        </td>
                                    @endif
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item['product']['code'] }}</td>
                                    <td>
                                        <span>{{ $item['product']['name'] }}</span>
                                        <br>
                                        @if ($viewMode)
                                            <span class="text-muted small">{{ $item['note'] ?? '' }}</span>
                                        @else
                                            <x-form::input :placeholder="trans('core/base::general.note')" wire:model="products.{{ $key }}.note" size="sm" />
                                        @endif
                                    </td>
                                    <td>{{ $item['product']['unit'] ?? trans('modules/product::product.default_unit') }}</td>
                                    <td>{{ core_number_format($item['amount'] ?? 0) }}</td>
                                    <td>
                                        @if ($viewMode)
                                            {{ core_number_format($item['actual_stock'] ?? 1) }}
                                        @else
                                            <x-form::input type="number" wire:model.live="products.{{ $key }}.actual_stock" value="{{ $item['actual_stock'] ?? 1 }}" min="0" step="1" />
                                        @endif
                                    </td>
                                    <td>
                                        {{ ($item['quantity_difference'] ?? 0) > 0 ? '+' : '' }}{{ core_number_format($item['quantity_difference'] ?? 0) }}
                                    </td>
                                    <td>
                                        {{ ($item['value_difference'] ?? 0) > 0 ? '+' : '' }}{{ core_number_format($item['value_difference'] ?? 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">{{ trans('modules/product::product.no_products') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-ui::table>
                </x-ui::card>
            </div>

            <div class="col-md-4">
                <x-ui::card>
                    <x-ui::table class="table-bordered">
                        <tr>
                            <td class="w-50"><b>{{ trans('modules/product::stock.code') }}:</b></td>
                            <td class="text-end">
                                <x-form::input wire:model="stockCode" disabled />
                            </td>
                        </tr>
                        <tr>
                            <td class="w-50"><b>{{ trans('core/base::general.status') }}:</b></td>
                            <td class="text-end">
                                @php
                                    $statusBadge = match ($stock->status ?? 'draft') {
                                        'completed' => 'bg-success-lt text-success',
                                        'cancelled' => 'bg-danger-lt text-danger',
                                        default => 'bg-warning-lt text-warning',
                                    };
                                    $statusLabel = match ($stock->status ?? 'draft') {
                                        'completed' => trans('modules/product::stock.status.completed'),
                                        'cancelled' => __('Đã hủy'),
                                        default => trans('modules/product::stock.status.draft'),
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-50"><b>{{ trans('modules/product::stock.amount') }}:</b></td>
                            <td class="text-end">
                                {{ core_number_format($stock->amount ?? 0) }}
                            </td>
                        </tr>

                        {{-- Admin: Chỉnh giờ phiếu kiểm kho --}}
                        @if ($stock_id && auth()->user()?->hasRole('admin'))
                            <tr>
                                <td class="w-50"><b>{{ __('Thời gian') }}:</b></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <x-form::input type="datetime-local" wire:model="stockDateTime" size="sm" />
                                        <x-ui.button color="primary" icon="check" size="sm" wire:click="updateStockDateTime" />
                                    </div>
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td colspan="999">
                                <x-form::input wire:model.live="stock.note" :label="trans('core/base::general.note')" />
                                <x-ui::errors />
                            </td>
                        </tr>

                        @if ($viewMode && ($stock->status ?? '') === 'completed')
                            {{-- ViewMode: Chỉ cho lưu ghi chú + Hủy phiếu --}}
                            <tr>
                                <td>
                                    <x-ui.button color="success" icon="device-floppy" wire:click="saveNote" :label="__('Lưu ghi chú')" style="width: 100%;" />
                                </td>
                                @can('products.stock.manage')
                                <td>
                                    <x-ui.button color="danger" icon="ban" wire:click="cancelStock" wire:confirm="Bạn có chắc chắn muốn hủy phiếu kiểm kho? Tồn kho sẽ được hoàn lại." :label="__('Hủy phiếu')" style="width: 100%;" />
                                </td>
                                @endcan
                            </tr>
                        @elseif($viewMode && ($stock->status ?? '') === 'cancelled')
                            <tr>
                                <td colspan="2" class="text-center">
                                    <span class="badge bg-danger-lt text-danger fs-5">{{ __('Phiếu đã bị hủy') }}</span>
                                </td>
                            </tr>
                        @elseif(!$viewMode)
                            {{-- Chế độ tạo mới / chỉnh sửa --}}
                            <tr>
                                @if (!$stock_id || ($stock->status ?? '') === 'draft')
                                    @can('products.stock.create')
                                    <td>
                                        <x-ui.button color="primary" icon="device-floppy" wire:click="save('draft')" :label="trans('modules/product::stock.status.draft')" style="width: 100%;" />
                                    </td>
                                    @endcan
                                @endif
                                @can('products.stock.create')
                                <td>
                                    <x-ui.button color="success" icon="check" wire:click="save('completed')" :label="trans('modules/product::stock.status.completed')" style="width: 100%;" />
                                </td>
                                @endcan
                            </tr>
                        @endif
                    </x-ui::table>
                </x-ui::card>

                {{-- Back to list link --}}
                @if ($viewMode)
                    <div class="mt-3">
                        <a href="{{ route('products.stock.index') }}" class="btn btn-outline-secondary w-100">
                            {!! tabler_icon('arrow-left', ['class' => 'icon']) !!} {{ __('Quay lại danh sách') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Confirm Delete Product Modal --}}
        @if (!$viewMode)
            <div class="modal modal-blur fade" :class="{ 'show': showDeleteModal }" :style="showDeleteModal ? 'display: block;' : 'display: none;'" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-status bg-danger"></div>
                        <div class="modal-body py-4 text-center">
                            <div class="text-danger mb-2">
                                {!! tabler_icon('alert-triangle', ['class' => 'icon-lg']) !!}
                            </div>
                            <h3>{{ trans('modules/product::product.confirm_delete_product') }}</h3>
                            <div class="text-secondary mb-3">
                                {{ trans('modules/product::product.invoice_completed_note') }} <strong>DELETE</strong> {{ trans('modules/product::product.to_confirm_delete') }}
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
        @endif
    </div>

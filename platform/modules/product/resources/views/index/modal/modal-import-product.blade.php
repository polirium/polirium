<div>
    <x-ui::modal id="modal-import-product" :header="trans('modules/product::product.import_product')" class="modal-lg">
        <div class="modal-body">
            {{-- Header link --}}
            <div class="mb-4 text-end">
                <a href="#" wire:click.prevent="downloadTemplate" class="text-primary">
                    {{ trans('modules/product::product.download_template') }} <strong>Excel file</strong>
                </a>
            </div>

            {{-- Import result alert --}}
            @if (!empty($importResult))
                <x-ui::alert
                    :color="$importResult['success'] ? 'success' : 'danger'"
                    :label="$importResult['message']"
                />
                @if (!empty($importResult['errors']))
                    <div class="alert alert-warning mt-2">
                        <strong>{{ trans('modules/product::product.error_details') }}</strong>
                        <ul class="mb-0 mt-2">
                            @foreach (array_slice($importResult['errors'], 0, 10) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @if (count($importResult['errors']) > 10)
                                <li class="text-muted">... và {{ count($importResult['errors']) - 10 }} lỗi khác</li>
                            @endif
                        </ul>
                    </div>
                @endif
            @endif

            <div class="row g-4">
                {{-- Left column --}}
                <div class="col-md-6">
                    {{-- Xử lý trùng mã hàng/mã vạch, khác tên hàng hóa --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ trans('modules/product::product.handle_barcode_name_mismatch') }}
                        </label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="duplicate_code_handling" wire:model="duplicate_code_handling" value="error" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.error_and_stop') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="duplicate_code_handling" wire:model="duplicate_code_handling" value="replace_name" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.replace_old_name') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Xử lý trùng mã vạch, khác mã hàng --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ trans('modules/product::product.handle_barcode_code_mismatch') }}
                            <span class="text-muted" data-bs-toggle="tooltip" title="{{ trans('modules/product::product.handle_duplicate_barcode') }}">
                                {{ tabler_icon('info-circle') }}
                            </span>
                        </label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="duplicate_barcode_handling" wire:model="duplicate_barcode_handling" value="error" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.error_and_stop') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="duplicate_barcode_handling" wire:model="duplicate_barcode_handling" value="replace_code" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.replace_old_code') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Cập nhật tồn kho --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-warning text-dark me-1">{{ trans('Cập nhật tồn kho?') }}</span>
                            <span class="text-muted" data-bs-toggle="tooltip" title="{{ trans('modules/product::product.update_stock_note') }}">
                                {{ tabler_icon('info-circle') }}
                            </span>
                        </label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="update_stock" wire:model="update_stock" value="0" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.no') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="update_stock" wire:model="update_stock" value="1" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('Có') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Cập nhật giá vốn --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ trans('Cập nhật giá vốn?') }}</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="update_cost_price" wire:model="update_cost_price" value="0" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.no') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="update_cost_price" wire:model="update_cost_price" value="1" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('Có') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Right column --}}
                <div class="col-md-6">
                    {{-- Áp dụng giá vốn --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ trans('modules/product::product.apply_cost_scope') }}
                            <span class="text-muted" data-bs-toggle="tooltip" title="{{ trans('Chọn phạm vi áp dụng giá vốn') }}">
                                {{ tabler_icon('info-circle') }}
                            </span>
                        </label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="cost_price_scope" wire:model.live="cost_price_scope" value="global" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.whole_system') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="cost_price_scope" wire:model.live="cost_price_scope" value="branch" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('Chi nhánh') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @if ($cost_price_scope === 'branch')
                            <div class="mt-2">
                                <x-form::select wire:model="cost_price_branch_id" :options="$branches" :placeholder="trans('Chọn chi nhánh áp dụng')" />
                            </div>
                        @endif
                    </div>

                    {{-- Trạng thái kinh doanh áp dụng --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ trans('modules/product::product.business_status_scope') }}
                            <span class="text-muted" data-bs-toggle="tooltip" title="{{ trans('Chọn phạm vi áp dụng trạng thái kinh doanh') }}">
                                {{ tabler_icon('info-circle') }}
                            </span>
                        </label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="business_status_scope" wire:model.live="business_status_scope" value="global" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.whole_system') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="business_status_scope" wire:model.live="business_status_scope" value="branch" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('Chi nhánh') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @if ($business_status_scope === 'branch')
                            <div class="mt-2">
                                <x-form::select wire:model="business_status_branch_id" :options="$branches" :placeholder="trans('Chọn chi nhánh áp dụng')" />
                            </div>
                        @endif
                    </div>

                    {{-- Cập nhật mô tả --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ trans('Cập nhật mô tả?') }}</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="update_description" wire:model="update_description" value="0" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('modules/product::product.no') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="update_description" wire:model="update_description" value="1" class="form-selectgroup-input">
                                <div class="form-selectgroup-label d-flex align-items-center p-2">
                                    <div class="form-selectgroup-label-content d-flex align-items-center">
                                        <span class="form-selectgroup-check me-2"></span>
                                        <span>{{ trans('Có') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- File upload --}}
            <div class="mt-4">
                @error('file')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="mb-3">
                    <input type="file"
                           wire:model="file"
                           class="form-control"
                           accept=".xlsx,.xls,.csv"
                           id="import-file-input">
                </div>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">
                {{ trans('Đóng') }}
            </button>
            <button type="button"
                    class="btn btn-primary"
                    wire:click="import"
                    wire:loading.attr="disabled"
                    {{ !$file ? 'disabled' : '' }}>
                <span wire:loading wire:target="import">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    {{ trans('modules/product::product.processing') }}
                </span>
                <span wire:loading.remove wire:target="import">
                    {{ tabler_icon('upload') }}
                    {{ trans('Chọn file dữ liệu') }}
                </span>
            </button>
        </x-slot:footer>
    </x-ui::modal>
</div>

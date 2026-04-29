<div>
    <x-ui::modal id="modal-import-customer" :header="trans('modules/customer::customer.import_from_excel')" class="modal-lg">
        <div class="modal-body">
            {{-- Download template link - subtle at top right --}}
            <div class="text-end mb-4">
                <a href="#" wire:click.prevent="downloadTemplate" class="text-decoration-none">
                    <span class="text-muted">{{ trans('modules/customer::customer.download') }}</span>
                    <strong class="text-primary">{{ trans('modules/customer::customer.excel_template') }}</strong>
                </a>
            </div>

            {{-- Import result alert --}}
            @if($hasResult)
                @if($importedCount > 0 || $updatedCount > 0)
                    <div class="alert alert-success mb-3">
                        <div class="d-flex">
                            <div>
                                {!! tabler_icon('circle-check', ['class' => 'icon alert-icon']) !!}
                            </div>
                            <div>
                                <h4 class="alert-title">{{ trans('modules/customer::customer.import_success') }}</h4>
                                <div class="text-secondary">
                                    Đã nhập <strong>{{ $importedCount }}</strong> khách hàng mới,
                                    cập nhật <strong>{{ $updatedCount }}</strong> khách hàng.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(count($importErrors) > 0)
                    <div class="alert alert-warning mb-3">
                        <div class="d-flex">
                            <div>
                                {!! tabler_icon('alert-triangle', ['class' => 'icon alert-icon']) !!}
                            </div>
                            <div class="w-100">
                                <h4 class="alert-title">
                                    {{ __('Có :count lỗi', ['count' => count($importErrors)]) }}
                                </h4>
                                <div class="text-secondary">
                                    <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                                        <ul class="mb-0">
                                            @foreach(array_slice($importErrors, 0, 10) as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                            @if(count($importErrors) > 10)
                                                <li class="text-muted fst-italic">
                                                    ... và {{ count($importErrors) - 10 }} lỗi khác
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- File upload area with better styling --}}
            <div class="mb-4">
                @error('file')
                    <div class="alert alert-danger alert-important mb-3">
                        {{ $message }}
                    </div>
                @enderror

                <label class="form-label required">{{ trans('modules/customer::customer.select_excel_file') }}</label>
                <div class="card card-borderless bg-secondary-lt">
                    <div class="card-body">
                        <input type="file"
                               wire:model="file"
                               class="form-control"
                               accept=".xlsx,.xls,.csv"
                               id="import-customer-file-input">

                        {{-- File info when selected --}}
                        @if($file)
                            <div class="mt-3 d-flex align-items-center gap-2">
                                {!! tabler_icon('file-spreadsheet', ['class' => 'icon text-success']) !!}
                                <span class="text-muted">{{ $file->getClientOriginalName() }}</span>
                                <span class="badge bg-secondary-lt text-muted ms-auto">
                                    {{ number_format($file->getSize() / 1024, 1) }} KB
                                </span>
                            </div>
                        @endif

                        {{-- Loading state --}}
                        @if($file && !$hasResult)
                            <div wire:loading wire:target="file" class="mt-3 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Đang tải file...
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Helpful hints - collapsed by default --}}
            <div class="accordion" id="import-hints">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hints-collapse">
                            <span class="me-2">
                                {!! tabler_icon('info-circle', ['class' => 'icon']) !!}
                            </span>
                            {{ trans('modules/customer::customer.guide_notes') }}
                        </button>
                    </h2>
                    <div id="hints-collapse" class="accordion-collapse collapse" data-bs-parent="#import-hints">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-tiny mb-2">Cột bắt buộc</h6>
                                    <ul class="mb-0 small">
                                        <li><strong>Tên khách hàng</strong> - Không được để trống</li>
                                        <li><strong>Điện thoại</strong> - Dùng để kiểm tra trùng</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-tiny mb-2">Lưu ý</h6>
                                    <ul class="mb-0 small">
                                        <li>Mã khách hàng tự động tạo (KH/00001, KH/00002...)</li>
                                        <li>Trùng số điện thoại → Cập nhật thông tin</li>
                                        <li>File .xlsx, .xls, .csv (max 10MB)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot:footer>
            @if($hasResult && empty($importErrors))
                {{-- Success state - single close button --}}
                <x-ui::button color="primary" type="button" wire:click="closeModal" icon="check">
                    {{ trans('modules/customer::customer.done') }}
                </x-ui::button>
            @else
                {{-- Normal/Import state --}}
                <x-ui::button color="secondary" :ghost="true" type="button" wire:click="closeModal" icon="x">
                    {{ trans('modules/customer::customer.cancel') }}
                </x-ui::button>

                <x-ui::button
                    color="primary"
                    type="button"
                    wire:click="import"
                    :disabled="!$file"
                    wire:loading.attr="disabled">
                    <span wire:loading wire:target="import">
                        {!! tabler_icon('loader-2', ['class' => 'icon icon-spin']) !!}
                        {{ trans('modules/customer::customer.processing') }}
                    </span>
                    <span wire:loading.remove wire:target="import">
                        {!! tabler_icon('upload', ['class' => 'icon']) !!}
                        {{ trans('modules/customer::customer.import_data') }}
                    </span>
                </x-ui::button>
            @endif
        </x-slot:footer>
    </x-ui::modal>
</div>

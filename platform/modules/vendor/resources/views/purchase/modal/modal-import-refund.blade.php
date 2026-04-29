<div>
    <x-ui::modal id="modal-import-refund" :header="__('modules/vendor::vendor.import.refund_title')" class="modal-md">
        <div class="modal-body">
            {{-- Header link --}}
            <div class="mb-4 text-end">
                <a href="#" wire:click.prevent="downloadTemplate" class="text-primary">
                    {{ __('modules/vendor::vendor.import.download_template') }} <strong>{{ __('modules/vendor::vendor.import.excel_file') }}</strong>
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
                        <strong>{{ __('modules/vendor::vendor.import.error_details') }}</strong>
                        <ul class="mb-0 mt-2">
                            @foreach (array_slice($importResult['errors'], 0, 10) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @if (count($importResult['errors']) > 10)
                                <li class="text-muted">{{ __('modules/vendor::vendor.import.more_errors', ['count' => count($importResult['errors']) - 10]) }}</li>
                            @endif
                        </ul>
                    </div>
                @endif
            @endif

            <div class="mb-3">
                <p class="text-muted">
                    {{ __('modules/vendor::vendor.import.refund_file_columns') }}
                </p>
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
                           id="import-refund-file-input">
                </div>
            </div>
        </div>

        <x-slot:footer>
            <x-ui::button color="secondary" :ghost="true" type="button" wire:click="closeModal" icon="x">
                {{ __('core/base::general.close') }}
            </x-ui::button>
            <x-ui::button color="primary" type="button" wire:click="import" wire:loading.attr="disabled" :disabled="!$file">
                <span wire:loading wire:target="import">
                    {!! tabler_icon('loader-2', ['class' => 'icon icon-spin']) !!}
                    {{ __('modules/vendor::vendor.import.processing') }}
                </span>
                <span wire:loading.remove wire:target="import">
                    {!! tabler_icon('upload', ['class' => 'icon']) !!}
                    {{ __('modules/vendor::vendor.import.import_products') }}
                </span>
            </x-ui::button>
        </x-slot:footer>
    </x-ui::modal>
</div>

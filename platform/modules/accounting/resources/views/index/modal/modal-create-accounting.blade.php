<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-accounting" :header="$header_modal" class="modal-xl">
            <x-ui::errors />

            <div class="row">
                <div class="col-md-6">
                    {{-- Branch Select --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.branch') }}
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-control tom-select"
                                wire:model="input.branch_id"
                                required>
                            @foreach ($lists['branches'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('input.branch_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Code Input --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.' . $type . '.code') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               wire:model="input.code"
                               value="{{ $input['code'] ?? '' }}">
                        @error('input.code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Date Input --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.date') }}
                        </label>
                        <input type="date"
                               class="form-control"
                               wire:model="input.date"
                               value="{{ $input['date'] ?? '' }}">
                        @error('input.date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Type Select with Button --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.type.name') }}
                        </label>
                        <div class="input-group">
                            <select class="form-control tom-select"
                                    wire:model.live="input.type_id">
                                @foreach ($lists['types'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="input-group-text">
                                <x-ui.button type="button"
                                             :icon="$input['type_id'] ?? null ? 'pencil' : 'plus'"
                                             :color="$input['type_id'] ?? null ? 'warning' : 'success'"
                                             wire:click="$dispatch('show-modal-create-accounting-type', { id: {{ $input['type_id'] ?? 0 }}, type: '{{ $type }}' })" />
                            </span>
                        </div>
                        @error('input.type_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Value Currency Input --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.value') }}
                        </label>
                        @php
                            $currencyRef = 'currency_' . uniqid();
                        @endphp
                        <input type="text"
                               class="form-control"
                               wire:model="input.value"
                               data-inputmask-currency
                               data-x-ref="{{ $currencyRef }}"
                               x-ref="{{ $currencyRef }}"
                               x-init="if (window.PoliriumAccounting) {
                                   window.PoliriumAccounting.initCurrencyInput($refs.{{ $currencyRef }}, @this);
                               }"
                               value="{{ $input['value'] ?? '' }}">
                        @error('input.value')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- User Select --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.user') }}
                        </label>
                        <select class="form-control tom-select"
                                wire:model="input.user_id">
                            @foreach ($lists['users'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('input.user_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    {{-- Finance Type Select --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/accounting::accounting.finance_type') }}
                        </label>
                        <select class="form-control tom-select"
                                wire:model.live="input.finance_type">
                            @foreach ($lists['finance_types'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('input.finance_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Autocomplete Search --}}
                    @if ($input['finance_type'] ?? null)
                        <div class="mb-3">
                            <label class="form-label d-block mb-2">
                                {{ trans('modules/accounting::accounting.search') }}
                            </label>
                            <div class="input-group" style="width: 90%; display: inline-block;">
                                <span class="position-relative d-inline-block w-100"
                                      x-data="{ show: false }">
                                    <input type="text"
                                           class="form-control"
                                           wire:model.live="search_target"
                                           @focus="show = true"
                                           @input="show = true"
                                           @click="show = true"
                                           placeholder="{{ __('core/base::general.search_placeholder') }}">
                                    <div class="list-group list-group-flush bg-light position-absolute w-100 shadow"
                                         style="z-index: 1050; max-height: 300px; overflow-y: auto;"
                                         x-show="show"
                                         @click.outside="setTimeout(() => show = false, 200)"
                                         x-cloak>
                                        @foreach ($lists['target_searched'] as $key => $item)
                                            <button type="button"
                                                    class="list-group-item list-group-item-action"
                                                    wire:click="$set('input.finance_id', {{ $key }})">
                                                <span>{{ $item }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </span>
                                @if (($input['finance_type'] ?? null) == \Polirium\Modules\Accounting\Http\Model\PayPerson::class)
                                    <span class="input-group-text">
                                        <x-ui.button type="button"
                                                     :icon="$input['finance_id'] ?? null ? 'pencil' : 'plus'"
                                                     :color="$input['finance_id'] ?? null ? 'warning' : 'success'"
                                                     wire:click="$dispatch('show-modal-create-accounting-pay-person', { id: {{ $input['finance_id'] ?? 0 }} })" />
                                    </span>
                                @endif
                            </div>
                            @error('input.finance_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    {{-- Selected Target Display --}}
                    @if ($input['finance_id'] ?? null)
                        <div class="mb-3">
                            <span><b>{{ $this->target?->name }}: </b>{{ $this->target?->phone }}</span>
                            <x-ui.button type="button" wire:click="$set('input.finance_id', null)" icon="x" color="danger" />
                        </div>
                    @endif

                    {{-- Business Result Checkbox --}}
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   wire:model="input.business_result"
                                   value="1">
                            <span class="form-check-label ms-2">
                                {{ trans('modules/accounting::accounting.business_result') }}
                            </span>
                        </label>
                    </div>

                    {{-- Note Textarea --}}
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('core/base::general.note') }}
                        </label>
                        <textarea class="form-control"
                                  wire:model="input.note"
                                  rows="3">{{ $input['note'] ?? '' }}</textarea>
                        @error('input.note')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <x-slot:footer>
                @can('accountings.create')
                <x-ui.button type="submit" icon="device-floppy" color="success" :label="trans('core/base::general.save')" />
                @endcan
            </x-slot:footer>
        </x-ui::modal>
    </form>

    @livewire('modules/accounting::index.modal.modal-create-accounting-type')
    @livewire('modules/accounting::index.modal.modal-create-accounting-pay-person')
</div>

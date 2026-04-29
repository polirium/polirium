<div>
    {{-- Filter Panel --}}
    <x-ui::card>
        {{-- Header với icon --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                {!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}
                <span class="fw-semibold">{{ __('core/base::general.filter') }}</span>
            </div>
        </div>

        {{-- Search by code --}}
        <div class="mb-3">
            <label class="form-label small text-muted">{{ __('modules/accounting::accounting.search_by_code') }}</label>
            <div class="input-icon">
                <span class="input-icon-addon">
                    {!! tabler_icon('search', ['class' => 'icon']) !!}
                </span>
                <input
                       type="text"
                       class="form-control"
                       wire:model.live.debounce.300ms="search.code"
                       placeholder="{{ __('core/base::general.search_placeholder') }}">
            </div>
        </div>

        {{-- Filter by Status --}}
        <div class="mb-3">
            <label class="form-label small text-muted">{{ __('modules/accounting::accounting.filter_by_status') }}</label>
            <select class="form-select" wire:model.live="search.status">
                <option value="">{{ __('core/base::general.all') }}</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Active filter indicator --}}
        @if (!empty($search['code']) || !empty($search['status']))
            <div class="bg-primary-lt d-flex align-items-center justify-content-between rounded p-2">
                <span class="small text-primary">
                    {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                    {{ __('core/base::general.filter_active') }}
                </span>
                <button
                        class="btn btn-sm btn-ghost-danger btn-icon"
                        wire:click="clearFilter"
                        title="{{ __('core/base::general.clear_filter') }}">
                    {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                </button>
            </div>
        @endif
    </x-ui::card>

    {{-- Quick Actions Card --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center mb-2 gap-2">
            {!! tabler_icon('bolt', ['class' => 'icon text-warning']) !!}
            <span class="fw-semibold">{{ __('core/base::general.quick_actions') }}</span>
        </div>

        <div class="d-grid gap-2">
            @can('vendors.refunds.edit')
                <a
                   href="{{ route('vendors.purchases.refund') }}"
                   class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2">
                    {!! tabler_icon('plus', ['class' => 'icon']) !!}
                    {{ __('modules/vendor::purchase.refund.create') }}
                </a>
                <button
                        class="btn btn-outline-secondary d-flex align-items-center justify-content-start gap-2"
                        wire:click="$dispatch('show-modal-import-refund')">
                    {!! tabler_icon('file-import', ['class' => 'icon']) !!}
                    {{ __('core/base::general.import_excel') }}
                </button>
            @endcan
        </div>
    </x-ui::card>
</div>

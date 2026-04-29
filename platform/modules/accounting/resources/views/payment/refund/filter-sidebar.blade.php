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
                    placeholder="{{ __('core/base::general.search_placeholder') }}"
                >
            </div>
        </div>

        {{-- Filter by Status --}}
        <div class="mb-3">
            <label class="form-label small text-muted">{{ __('modules/accounting::accounting.filter_by_status') }}</label>
            <select class="form-select" wire:model.live="search.status">
                <option value="">{{ __('core/base::general.all') }}</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Active filter indicator --}}
        @if (!empty($search['code']) || !empty($search['status']))
            <div class="p-2 bg-primary-lt rounded d-flex align-items-center justify-content-between">
                <span class="small text-primary">
                    {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                    {{ __('core/base::general.filter_active') }}
                </span>
                <button
                    class="btn btn-sm btn-ghost-danger btn-icon"
                    wire:click="clearFilter"
                    title="{{ __('core/base::general.clear_filter') }}"
                >
                    {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                </button>
            </div>
        @endif
    </x-ui::card>

    {{-- Info Card --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            {!! tabler_icon('info-circle', ['class' => 'icon text-info']) !!}
            <span class="fw-semibold">{{ __('modules/accounting::accounting.refund_info') }}</span>
        </div>
        <p class="text-muted small mb-0">
            {{ __('modules/accounting::accounting.refund_description') }}
        </p>
    </x-ui::card>
</div>

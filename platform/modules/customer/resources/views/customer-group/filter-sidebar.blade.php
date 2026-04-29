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

        {{-- Search input --}}
        <div class="mb-3">
            <label class="form-label small text-muted">{{ __('core/base::general.search_by_name') }}</label>
            <div class="input-icon">
                <span class="input-icon-addon">
                    {!! tabler_icon('search', ['class' => 'icon']) !!}
                </span>
                <input
                       type="text"
                       class="form-control"
                       wire:model.live.debounce.300ms="search.name"
                       placeholder="{{ __('core/base::general.search_placeholder') }}">
            </div>
        </div>

        {{-- Active filter indicator --}}
        @if (!empty($search['name']))
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
            @can('customers.groups')
                <button
                        class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2"
                        wire:click="$dispatch('show-modal-create-customer-group')">
                    {!! tabler_icon('plus', ['class' => 'icon']) !!}
                    {{ __('core/base::general.create') }}
                </button>
            @endcan
        </div>
    </x-ui::card>
</div>

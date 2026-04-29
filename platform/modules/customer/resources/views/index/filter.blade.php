<div>
    {{-- Filter Panel với hướng dẫn rõ ràng --}}
    <x-ui::card>
        {{-- Header với icon và action --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                {!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}
                <span class="fw-semibold">{{ __('modules/customer::customer.filter_by_group') }}</span>
            </div>
            @can('customers.groups')
                <button
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                        wire:click="$dispatch('show-modal-create-customer-group')"
                        title="{{ __('modules/customer::customer.group.create') }}">
                    {!! tabler_icon('plus', ['class' => 'icon icon-sm']) !!}
                    <span class="d-none d-lg-inline">{{ __('modules/customer::customer.group.add_short') }}</span>
                </button>
            @endcan
        </div>

        {{-- Hint text để hướng dẫn user --}}
        <p class="text-muted small mb-2">
            {!! tabler_icon('info-circle', ['class' => 'icon icon-sm me-1']) !!}
            {{ __('modules/customer::customer.filter_hint') }}
        </p>

        {{-- Select với placeholder rõ ràng --}}
        <div class="d-flex gap-2">
            <div class="flex-grow-1" wire:key="filter-select-{{ $search['customer_group'] ?? 'empty' }}">
                <x-form::select
                                wire:model.live="search.customer_group"
                                :options="$list['customer-groups']"
                                :placeholder="__('modules/customer::customer.all_groups')"
                                tomselect />
            </div>
            @if (isset($search['customer_group']) && $search['customer_group'])
                <button
                        class="btn btn-warning btn-icon"
                        wire:click="$dispatch('show-modal-create-customer-group', { id: {{ $search['customer_group'] }} })"
                        title="{{ __('modules/customer::customer.group.edit') }}">
                    {!! tabler_icon('pencil', ['class' => 'icon']) !!}
                </button>
            @endif
        </div>

        {{-- Quick stats / Active filter indicator --}}
        @if (isset($search['customer_group']) && $search['customer_group'])
            <div class="bg-primary-lt d-flex align-items-center justify-content-between mt-3 rounded p-2">
                <span class="small text-primary">
                    {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                    {{ __('modules/customer::customer.filter_active') }}
                </span>
                <button
                        class="btn btn-sm btn-ghost-danger btn-icon"
                        wire:click="$set('search.customer_group', null)"
                        title="{{ __('modules/customer::customer.clear_filter') }}">
                    {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                </button>
            </div>
        @endif
    </x-ui::card>

    {{-- Quick Actions Card --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center mb-2 gap-2">
            {!! tabler_icon('bolt', ['class' => 'icon text-warning']) !!}
            <span class="fw-semibold">{{ __('modules/customer::customer.quick_actions') }}</span>
        </div>

        <div class="d-grid gap-2">
            @can('customers.create')
            <button
                    class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2"
                    wire:click="$dispatch('show-modal-create-customer')">
                {!! tabler_icon('user-plus', ['class' => 'icon']) !!}
                {{ __('modules/customer::customer.create') }}
            </button>
            @endcan

            @can('customers.groups')
            <button
                    class="btn btn-outline-secondary d-flex align-items-center justify-content-start gap-2"
                    wire:click="$dispatch('show-modal-create-customer-group')">
                {!! tabler_icon('users-group', ['class' => 'icon']) !!}
                {{ __('modules/customer::customer.group.create') }}
            </button>
            @endcan
        </div>
    </x-ui::card>

    @livewire('modules/customer::index.modal.modal-create-customer-group')
</div>

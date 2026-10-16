@php($inspection = $step->inspection())
@php($databaseName = (string) config('database.connections.'.config('database.default').'.database'))

<div class="mb-6">
    <h3 class="section-title">{{ __('installer::installer.database_action_title') }}</h3>
    <p class="section-subtitle">
        {{ __('installer::installer.database_action_subtitle', ['database' => $databaseName]) }}
    </p>
</div>

@if($inspection['status'] === 'empty')
    <div class="database-status database-status--safe">
        <strong>{{ __('installer::installer.database_empty_title') }}</strong>
        <p>{{ __('installer::installer.database_empty_description') }}</p>
    </div>
@else
    <div x-data="{ mode: @entangle('state.install_mode').live }" class="database-action-options">
        <div class="database-status {{ $inspection['status'] === 'polirium' ? 'database-status--info' : 'database-status--warning' }}">
            <strong>
                {{ $inspection['status'] === 'polirium'
                    ? __('installer::installer.database_existing_polirium_title')
                    : __('installer::installer.database_existing_unknown_title') }}
            </strong>
            <p>
                {{ $inspection['status'] === 'polirium'
                    ? __('installer::installer.database_existing_polirium_description', ['count' => $inspection['table_count']])
                    : __('installer::installer.database_existing_unknown_description', ['count' => $inspection['table_count']]) }}
            </p>
        </div>

        <label class="database-action-card {{ $inspection['status'] !== 'polirium' ? 'database-action-card--disabled' : '' }}">
            <input
                type="radio"
                x-model="mode"
                value="upgrade"
                {{ $inspection['status'] !== 'polirium' ? 'disabled' : '' }}
            >
            <span>
                <strong>{{ __('installer::installer.database_keep_title') }}</strong>
                <small>{{ __('installer::installer.database_keep_description') }}</small>
            </span>
        </label>

        <label class="database-action-card database-action-card--danger">
            <input type="radio" x-model="mode" value="fresh">
            <span>
                <strong>{{ __('installer::installer.database_reinstall_title') }}</strong>
                <small>{{ __('installer::installer.database_reinstall_description') }}</small>
            </span>
        </label>

        <div x-show="mode === 'fresh'" x-cloak class="database-confirmation">
            <label for="reinstall_confirmation" class="form-label">
                {{ __('installer::installer.database_reinstall_confirmation', ['database' => $databaseName]) }}
            </label>
            <input
                id="reinstall_confirmation"
                type="text"
                wire:model="state.reinstall_confirmation"
                class="form-input"
                autocomplete="off"
                placeholder="{{ $databaseName }}"
            >
        </div>
    </div>
@endif

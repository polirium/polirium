<div class="mb-6">
    <h3 class="section-title">{{ __('installer::installer.environment_title') }}</h3>
    <p class="section-subtitle">{{ __('installer::installer.environment_subtitle') }}</p>
</div>

<div class="form-grid" x-init="if ($wire.state.database === 'bookflow') { $wire.state.database = 'polirium'; }">
    <div class="col-span-full">
        <label class="form-label">{{ __('installer::installer.environment_connection_type') }}</label>
        <select wire:model.live="state.connection"
            x-on:change="
                const port = { mysql: '3306', mariadb: '3306', pgsql: '5432', sqlsrv: '1433', sqlite: '' };
                $wire.state.port = port[$event.target.value] || '3306';
            "
            class="form-select">
            <option value="mysql">MySQL</option>
            <option value="mariadb">MariaDB</option>
        </select>
    </div>

    @if(($state['connection'] ?? 'mysql') !== 'sqlite')
    <div>
        <label class="form-label">{{ __('installer::installer.environment_host') }}</label>
        <input type="text" wire:model="state.host" class="form-input" placeholder="127.0.0.1">
    </div>
    <div>
        <label class="form-label">{{ __('installer::installer.environment_port') }}</label>
        <input type="text" wire:model="state.port" class="form-input" placeholder="3306">
    </div>
    @endif

    <div class="col-span-full">
        <label class="form-label">{{ __('installer::installer.environment_database_name') }}</label>
        <div class="form-input-wrapper">
            <div class="form-input-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <input type="text" wire:model="state.database" class="form-input form-input--with-icon" placeholder="polirium">
        </div>
        <p class="form-hint">{{ __('installer::installer.environment_database_hint') }}</p>
    </div>

    @if(($state['connection'] ?? 'mysql') !== 'sqlite')
    <div>
        <label class="form-label">{{ __('installer::installer.environment_username') }}</label>
        <input type="text" wire:model="state.username" class="form-input" placeholder="root">
    </div>
    <div>
        <label class="form-label">{{ __('installer::installer.environment_password') }}</label>
        <input type="password" wire:model="state.password" class="form-input" placeholder="• • • • • • • •">
    </div>
    @endif

    {{-- Dynamic Extra Environment Fields --}}
    @foreach(config('installer.environment_fields', []) as $envKey => $fieldConfig)
        @php
            $stateKey = $fieldConfig['state_key'] ?? strtolower($envKey);
            $type = $fieldConfig['type'] ?? 'text';
            $label = $fieldConfig['label'] ?? $envKey;
            $description = $fieldConfig['description'] ?? null;
        @endphp

        @if($type === 'checkbox')
            <div class="col-span-full" style="border-top: 1px solid var(--color-border); padding-top: 1.5rem; margin-top: 0.5rem;">
                <label class="toggle-label">
                    <input type="checkbox" wire:model.live="state.{{ $stateKey }}" class="toggle-input">
                    <div>
                        <span class="toggle-text">{{ $label }}</span>
                        @if($description)
                            <span class="toggle-desc">{{ $description }}</span>
                        @endif
                    </div>
                </label>
            </div>
        @elseif($type === 'select')
            <div class="{{ ($type === 'textarea') ? 'col-span-full' : '' }}">
                <label class="form-label">{{ $label }}</label>
                <select wire:model="state.{{ $stateKey }}" class="form-select">
                    @foreach($fieldConfig['options'] as $value => $optionLabel)
                        <option value="{{ $value }}">{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @if($description)
                    <p class="form-hint">{{ $description }}</p>
                @endif
            </div>
        @else
            <div class="{{ ($type === 'textarea') ? 'col-span-full' : '' }}">
                <label class="form-label">{{ $label }}</label>
                <input type="{{ $type }}" wire:model="state.{{ $stateKey }}" class="form-input" placeholder="{{ $fieldConfig['placeholder'] ?? '' }}">
                @if($description)
                    <p class="form-hint">{{ $description }}</p>
                @endif
            </div>
        @endif
    @endforeach

    <div class="col-span-full" style="padding-top: 1rem;">
        <button type="button" wire:click="testDatabase" wire:loading.attr="disabled" class="test-connection-btn">
            <span wire:loading.remove wire:target="testDatabase">{{ __('installer::installer.environment_test_connection') }}</span>
            <span wire:loading wire:target="testDatabase">{{ __('installer::installer.environment_testing') }}</span>
        </button>

        @if($testConnectionResult)
            <div class="test-connection-result {{ $testConnectionResult['success'] ? 'msg-box--test-pass' : 'msg-box--test-fail' }}">
                <div class="flex-shrink-0">
                    @if($testConnectionResult['success'])
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    @else
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @endif
                </div>
                <span>{{ $testConnectionResult['message'] }}</span>
            </div>
        @endif
    </div>
</div>

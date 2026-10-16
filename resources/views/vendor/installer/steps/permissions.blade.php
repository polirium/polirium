<div class="mb-6">
    <h3 class="section-title">{{ __('installer::installer.permissions_title') }}</h3>
    <p class="section-subtitle">{{ __('installer::installer.permissions_subtitle') }}</p>
</div>

<div class="permission-list">
    @php $permissions = $step->check(); @endphp
    @foreach($permissions as $path => $writable)
        <div class="check-item {{ $writable ? 'check-item--pass' : 'check-item--fail' }}">
            <div class="check-icon {{ $writable ? 'check-icon--pass' : 'check-icon--fail' }}">
                @if($writable)
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
            </div>
            <div class="check-info">
                <p class="check-label">{{ $path }}</p>
            </div>
            <span class="check-badge {{ $writable ? 'check-badge--pass' : 'check-badge--fail' }}">
                {{ $writable ? __('installer::installer.permissions_writable') : __('installer::installer.permissions_fix') }}
            </span>
        </div>
    @endforeach
</div>

@if(in_array(false, $permissions))
    <div class="msg-box msg-box--error">
        <strong>{{ __('installer::installer.permissions_action_needed') }}</strong>
    </div>
@else
    <div class="msg-box msg-box--success">
        <strong>{{ __('installer::installer.permissions_all_correct') }}</strong>
    </div>
@endif

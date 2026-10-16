<div class="mb-6">
    <h3 class="section-title">{{ __('installer::installer.requirements_title') }}</h3>
    <p class="section-subtitle">{{ __('installer::installer.requirements_subtitle') }}</p>
</div>

<div class="check-grid">
    @php $requirements = $step->check(); @endphp
    @foreach($requirements as $requirement => $passed)
        @php
            $fixHint = '';
            if (! $passed) {
                if (str_starts_with($requirement, 'PHP')) {
                    preg_match('/[\d.]+/', $requirement, $m);
                    $fixHint = __('installer::installer.requirements_fix_php', ['version' => $m[0] ?? '']);
                } elseif (str_contains($requirement, 'Extension')) {
                    $extName = strtolower(str_replace(' Extension', '', $requirement));
                    $fixHint = __('installer::installer.requirements_fix_extension', ['name' => $extName]);
                } elseif (str_contains($requirement, 'Memory')) {
                    preg_match('/[\d.]+[MG]/', $requirement, $min);
                    $fixHint = __('installer::installer.requirements_fix_memory', [
                        'min' => $min[0] ?? '',
                        'current' => ini_get('memory_limit'),
                    ]);
                }
            }
        @endphp
        <div class="check-item {{ $passed ? 'check-item--pass' : 'check-item--fail' }}">
            <div class="check-icon {{ $passed ? 'check-icon--pass' : 'check-icon--fail' }}">
                @if($passed)
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                @endif
            </div>
            <div class="check-info">
                <p class="check-label">{{ $requirement }}</p>
                <p class="check-status">{{ $passed ? __('installer::installer.requirements_passed_label') : __('installer::installer.requirements_failed_label') }}</p>
                @if ($fixHint)
                    <p class="check-hint">{{ $fixHint }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if(!in_array(false, $requirements))
    <div class="msg-box msg-box--success">
        <strong>{{ __('installer::installer.requirements_all_passed') }}</strong>
    </div>
@else
    <div class="msg-box msg-box--error">
        <strong>{{ __('installer::installer.requirements_action_needed') }}</strong>
    </div>
@endif

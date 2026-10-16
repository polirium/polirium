<div class="mb-6">
    <h3 class="section-title">{{ __('installer::installer.admin_title') }}</h3>
    <p class="section-subtitle">{{ __('installer::installer.admin_subtitle') }}</p>
</div>

<div class="form-grid" x-data="{ 
        showPw: false,
        showPc: false,
        get score() {
            if (!this.pw) return 0;
            let s = 0;
            if (this.pw.length >= 8) s++;
            if (/[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw)) s++;
            if (/\d/.test(this.pw)) s++;
            if (/[^a-zA-Z0-9]/.test(this.pw)) s++;
            return s;
        },
        get barColor() {
            if (this.score <= 1) return '#dc2626';
            if (this.score === 2) return '#f59e0b';
            return '#22c55e';
        },
        get textColor() {
            if (this.score <= 1) return '#dc2626';
            if (this.score === 2) return '#d97706';
            return '#16a34a';
        },
        get textLabel() {
            if (this.score === 0) return 'Very Weak';
            if (this.score === 1) return 'Weak';
            if (this.score === 2) return 'Fair';
            if (this.score === 3) return '✓ Strong';
            return '✓ Very Strong';
        }
    }">
    <div>
        <label class="form-label">{{ __('installer::installer.admin_first_name') }}</label>
        <input type="text" wire:model="state.first_name" class="form-input" autocomplete="given-name">
    </div>

    <div>
        <label class="form-label">{{ __('installer::installer.admin_last_name') }}</label>
        <input type="text" wire:model="state.last_name" class="form-input" autocomplete="family-name">
    </div>

    <div class="col-span-full">
        <label class="form-label">{{ __('installer::installer.admin_username') }}</label>
        <input type="text" wire:model="state.username" class="form-input" autocomplete="username">
    </div>

    <div class="col-span-full">
        <label class="form-label">{{ __('installer::installer.admin_email') }}</label>
        <div class="form-input-wrapper">
            <div class="form-input-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <input type="email" wire:model="state.email" class="form-input form-input--with-icon" placeholder="{{ __('installer::installer.admin_email_placeholder') }}" autocomplete="email">
        </div>
    </div>

    <div>
        <label class="form-label">{{ __('installer::installer.admin_password') }}</label>
        <div class="form-input-wrapper" style="position: relative;">
            <input :type="showPw ? 'text' : 'password'" wire:model="state.password" x-on:input="pw = $event.target.value" class="form-input form-input--with-icon-right" placeholder="{{ __('installer::installer.admin_password_placeholder') }}" autocomplete="new-password">
            <div class="form-input-icon-right" @click="showPw = !showPw" title="Toggle password visibility" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af; display: flex; align-items: center;">
                <svg x-show="!showPw" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <svg x-show="showPw" x-cloak style="display: none; width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
            </div>
        </div>
        <div class="mt-1.5 flex gap-1" x-show="pw && pw.length > 0" style="margin-top: 0.375rem; display: flex; gap: 0.25rem;">
            <template x-for="i in 4" :key="i">
                <div style="height: 0.375rem; flex: 1 1 0%; border-radius: 0.25rem; transition: background-color 0.3s;"
                     :style="score >= i ? `background-color: ${barColor};` : 'background-color: #e5e7eb;'"></div>
            </template>
        </div>
        <p class="mt-1 text-xs" x-show="pw && pw.length > 0"
           style="margin-top: 0.25rem; font-size: 0.75rem;"
           :style="`color: ${textColor};`"
           x-text="textLabel">
        </p>
    </div>
    <div>
        <label class="form-label">{{ __('installer::installer.admin_password_confirm') }}</label>
        <div class="form-input-wrapper" style="position: relative;">
            <input :type="showPc ? 'text' : 'password'" wire:model="state.password_confirmation" x-on:input="pc = $event.target.value" class="form-input form-input--with-icon-right" placeholder="{{ __('installer::installer.admin_password_confirm_placeholder') }}" autocomplete="new-password">
            <div class="form-input-icon-right" @click="showPc = !showPc" title="Toggle password visibility" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af; display: flex; align-items: center;">
                <svg x-show="!showPc" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <svg x-show="showPc" x-cloak style="display: none; width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
            </div>
        </div>
        <p class="mt-1 text-xs" x-show="pc && pc.length > 0 && pw !== pc" style="margin-top: 0.25rem; font-size: 0.75rem; color: #ef4444;">
            Passwords do not match
        </p>
    </div>
</div>

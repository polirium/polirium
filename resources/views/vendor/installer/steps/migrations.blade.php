<div
    x-data="{
        started: false,
        messages: [],
        percent: 0,
        status: 'idle',
        errorMessage: '',
        async run() {
            if (this.started) return;

            this.started = true;
            this.errorMessage = '';
            this.$dispatch('installer-stage-started');

            try {
                let done = false;

                while (!done) {
                    const response = await fetch(@js(route('installer.stage')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: '{}'
                    });
                    const progress = await response.json();

                    this.apply(progress);

                    if (!response.ok) {
                        this.errorMessage = progress.error || @js(__('installer::installer.migrations_error_fallback'));
                        break;
                    }

                    done = progress.done === true;
                }

                if (done) {
                    await this.$wire.call('next');
                }
            } catch (error) {
                this.status = 'failed';
                this.errorMessage = @js(__('installer::installer.migrations_error_fallback'));
            } finally {
                this.started = false;
                this.$dispatch('installer-stage-finished');
            }
        },
        apply(progress) {
            this.messages = Array.isArray(progress.messages) ? progress.messages : [];
            this.percent = Number.isFinite(Number(progress.percent)) ? Number(progress.percent) : 0;
            this.status = progress.status || 'running';

            this.$nextTick(() => {
                if (this.$refs.log) this.$refs.log.scrollTop = this.$refs.log.scrollHeight;
            });

        }
    }"
    x-on:installer-run-stages.window="run()"
>
<div class="mb-6">
    <h3 class="section-title">
        {{ session(\App\Installer\Steps\ChooseDatabaseAction::SESSION_MODE) === \App\Installer\Steps\ChooseDatabaseAction::MODE_UPGRADE
            ? __('installer::installer.migrations_upgrade_title')
            : __('installer::installer.migrations_title') }}
    </h3>
    <p class="section-subtitle">
        {{ session(\App\Installer\Steps\ChooseDatabaseAction::SESSION_MODE) === \App\Installer\Steps\ChooseDatabaseAction::MODE_UPGRADE
            ? __('installer::installer.migrations_upgrade_subtitle')
            : __('installer::installer.migrations_subtitle') }}
    </p>
</div>

<div class="migration-card flex items-start gap-4">
    <div class="migration-card-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
        </svg>
    </div>
    <div>
        <h4 class="migration-card-heading">
            {{ session(\App\Installer\Steps\ChooseDatabaseAction::SESSION_MODE) === \App\Installer\Steps\ChooseDatabaseAction::MODE_UPGRADE
                ? __('installer::installer.migrations_upgrade_heading')
                : __('installer::installer.migrations_heading') }}
        </h4>
        <p class="migration-card-desc">
            {{ session(\App\Installer\Steps\ChooseDatabaseAction::SESSION_MODE) === \App\Installer\Steps\ChooseDatabaseAction::MODE_UPGRADE
                ? __('installer::installer.migrations_upgrade_description')
                : __('installer::installer.migrations_description') }}
        </p>
    </div>
</div>

<div x-show="started || messages.length > 0 || errorMessage" x-cloak class="installation-progress mt-6" :class="{ 'installation-progress--failed': status === 'failed' }">
    <div class="installation-progress-header">
        <strong>{{ __('installer::installer.progress_title') }}</strong>
        <span class="installation-progress-percent" x-text="`${Math.max(0, Math.min(100, percent))}%`">0%</span>
    </div>
    <div class="installation-progress-track" role="progressbar" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100">
        <div class="installation-progress-fill" :style="`width: ${Math.max(2, Math.min(100, percent))}%`"></div>
    </div>
    <div x-ref="log" class="installation-progress-log" aria-live="polite">
        <p x-show="messages.length === 0">{{ __('installer::installer.progress_waiting') }}</p>
        <template x-for="(message, index) in messages" :key="`${index}-${message}`">
            <p><span aria-hidden="true">•</span> <span x-text="message"></span></p>
        </template>
    </div>
    <p x-show="errorMessage" x-text="errorMessage" class="installation-progress-error"></p>
</div>

<div x-data="{ showHelp: false }" class="mt-6">
    <button type="button" @click="showHelp = !showHelp" class="help-toggle">
        <span x-show="!showHelp">{{ __('installer::installer.migrations_help_toggle_show') }}</span>
        <span x-show="showHelp" style="display: none;">{{ __('installer::installer.migrations_help_toggle_hide') }}</span>
    </button>
    <div x-show="showHelp" style="display: none;" class="help-box">
        <p>&bull; {{ __('installer::installer.migrations_help_1') }}</p>
        <p>&bull; {{ __('installer::installer.migrations_help_2') }}</p>
        <p>&bull; {{ __('installer::installer.migrations_help_3') }}</p>
        <p>&bull; {{ __('installer::installer.migrations_help_4') }}</p>
        <p>&bull; {{ __('installer::installer.migrations_help_5') }}</p>
    </div>
</div>
</div>

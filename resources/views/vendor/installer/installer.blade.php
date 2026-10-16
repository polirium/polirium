<div class="installer-page"
    x-data="{
        loading: @entangle('loading'),
        error: null,
        isFinishing: false,
        isInstalling: false,
        isSuccess: false,
        redirectUrl: '',
        stepKey: 0
    }"
    x-on:installer-finishing.window="isFinishing = true"
    x-on:installer-stage-started.window="isInstalling = true"
    x-on:installer-stage-finished.window="isInstalling = false"
    x-on:installation-success.window="
        isFinishing = false;
        isSuccess = true;
        redirectUrl = $event.detail[0].redirectUrl || '/admin';
        if (typeof confetti !== 'undefined') {
            confetti({
                particleCount: 150,
                spread: 80,
                origin: { y: 0.6 },
                colors: ['#4f46e5', '#3b82f6', '#10b981', '#f59e0b', '#ec4899']
            });
        }
    "
    x-on:step-changed.window="stepKey++"
    x-cloak>

    <div class="installer-card">
        {{-- Left Sidebar --}}
        <div class="installer-sidebar">
            {{-- Thin progress bar at top --}}
            @php
                $allStepIds = array_keys($steps);
                $currentIndex = array_search($step->id(), $allStepIds);
                $progressPct = $currentIndex !== false ? (($currentIndex) / max(count($allStepIds) - 1, 1)) * 100 : 0;
            @endphp
            <div class="installer-progress-bar">
                <div class="installer-progress-bar-fill" style="width: {{ $progressPct }}%"></div>
            </div>

            <div class="installer-sidebar-decoration installer-sidebar-decoration--top-right"></div>
            <div class="installer-sidebar-decoration installer-sidebar-decoration--bottom-left"></div>

            <div class="installer-sidebar-content">
                @if(config('installer.logo'))
                    <img src="{{ config('installer.logo') }}" alt="Logo" class="installer-brand-logo">
                @else
                    <div class="installer-brand">
                        <div class="installer-brand-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="installer-brand-name">{{ config('installer.name', 'Installer') }}</span>
                    </div>
                @endif

                <h2 class="installer-step-title" x-text="$el.textContent">
                    {{ $step->label() }}
                </h2>
                <p class="installer-step-counter">
                    {{ __('installer::installer.step_of', ['current' => $currentIndex + 1, 'total' => count($steps)]) }}
                </p>
            </div>

            {{-- Progress Steps --}}
            <div class="installer-progress">
                <div class="installer-progress-list">
                    @php
                        $currentFound = false;
                    @endphp
                    @foreach($steps as $s)
                        @php
                            $isCurrent = $s->id() === $step->id();
                            if ($isCurrent) $currentFound = true;
                            $isPast = !$currentFound && !$isCurrent;
                        @endphp
                        @if($isPast)
                            <button type="button" wire:click="goToStep('{{ $s->id() }}')" class="installer-progress-item installer-progress-item--past">
                                <div class="installer-progress-indicator installer-progress-indicator--past">✓</div>
                                <span class="installer-progress-label installer-progress-label--past">{{ $s->label() }}</span>
                            </button>
                        @else
                            <div class="installer-progress-item {{ $isCurrent ? 'installer-progress-item--current' : 'installer-progress-item--future' }}">
                                <div class="installer-progress-indicator {{ $isCurrent ? 'installer-progress-indicator--current' : 'installer-progress-indicator--future' }}">
                                    {{ $loop->iteration }}
                                </div>
                                <span class="installer-progress-label {{ $isCurrent ? 'installer-progress-label--current' : 'installer-progress-label--future' }}">
                                    {{ $s->label() }}
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="installer-sidebar-footer">
                {{ __('installer::installer.copyright', ['year' => date('Y'), 'name' => config('installer.name')]) }}
            </div>
        </div>

        {{-- Right Content --}}
        <div class="installer-content">
            @if($errors->has('global'))
                <div class="installer-alert">
                    <div class="installer-alert-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="installer-alert-body">
                        <p class="installer-alert-text">{{ $errors->first('global') }}</p>
                    </div>
                </div>
            @endif

            <form class="installer-form"
                  x-data="{ pw: '', pc: '' }"
                  x-on:submit.prevent="
                      @if($step->id() === 'migrations')
                          $dispatch('installer-run-stages');
                      @elseif($step->id() === 'admin')
                          $wire.set('state.password', pw, false);
                          $wire.set('state.password_confirmation', pc, false);
                          $wire.call('next')
                      @else
                          $wire.call('next')
                      @endif
                  ">
                <div class="installer-form-body"
                     x-data
                     x-show="true"
                     x-transition:enter="installer-step-enter"
                     x-transition:enter-start="installer-step-enter-start"
                     x-transition:enter-end="installer-step-enter-end"
                     :key="stepKey">

                    @include($step->view())
                </div>

                <div class="installer-actions" x-show="!isSuccess">
                    @if(!$isFirstStep)
                        <button type="button" wire:key="back-btn" wire:click="previous" wire:loading.attr="disabled" class="btn btn--back">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ __('installer::installer.btn_back') }}
                        </button>
                    @else
                        <div wire:key="no-back-btn"></div>
                    @endif

                    <button type="submit"
                         wire:loading.attr="disabled"
                         :disabled="isFinishing || isInstalling"
                         class="btn btn--continue">

                         <span wire:loading.remove wire:target="next" x-show="!isFinishing && !isInstalling" class="btn-text-icon">
                             {{ $isLastStep ? __('installer::installer.btn_complete') : __('installer::installer.btn_continue') }}
                             <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                             </svg>
                         </span>

                         <span x-show="isFinishing" style="display: none;" class="btn-text-icon">
                             <div class="spinner"></div>
                             {{ __('installer::installer.btn_finalizing') }}
                         </span>

                         <span x-show="isInstalling" style="display: none;" class="btn-text-icon">
                             <div class="spinner"></div>
                             {{ __('installer::installer.btn_processing') }}
                         </span>

                         <span wire:loading wire:target="next" x-show="!isFinishing && !isInstalling" class="btn-text-icon">
                             <div class="spinner"></div>
                             {{ __('installer::installer.btn_processing') }}
                         </span>
                     </button>
                </div>
            </form>

            {{-- Success Overlay --}}
            <div x-show="isSuccess" x-cloak style="display: none; text-align: center; padding: 3rem 1rem;">
                <div style="width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);">
                    <svg style="width: 40px; height: 40px; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 style="font-size: 2rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">{{ __('installer::installer.success_title') }}</h2>
                <p style="color: #64748b; margin-bottom: 2.5rem; font-size: 1.1rem;">{{ __('installer::installer.success_message', ['name' => config('installer.name')]) }}</p>
                
                <a :href="redirectUrl" class="btn btn--continue" style="font-size: 1.1rem; padding: 1rem 2rem; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);">
                    <span class="btn-text-icon">
                        {{ __('installer::installer.success_login') }}
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

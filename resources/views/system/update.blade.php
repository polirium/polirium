<x-ui.layouts::app>
    <x-slot:title>{{ __('system.update') }}</x-slot:title>
    <x-slot:actions>
        <form method="POST" action="{{ route('system.update.check') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                {!! tabler_icon('refresh', ['class' => 'icon']) !!} {{ __('system.check_now') }}
            </button>
        </form>
    </x-slot:actions>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error') || $error)
        <div class="alert alert-danger">{{ session('error') ?: __('system.check_failed', ['message' => $error]) }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="subheader">{{ __('system.current_version') }}</div>
                            <div class="h1 mb-0">{{ $current }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="subheader">{{ __('system.latest_version') }}</div>
                            <div class="h1 mb-0 {{ $available ? 'text-primary' : '' }}">{{ $latest['version'] ?? '—' }}</div>
                            @if (! empty($latest['checked_at']))
                                <div class="text-secondary small">{{ __('system.checked_at', ['time' => \Illuminate\Support\Carbon::parse($latest['checked_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}</div>
                            @endif
                        </div>
                    </div>

                    @if ($available)
                        <div class="alert alert-info mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <strong>{{ __('system.update_available', ['version' => $latest['version']]) }}</strong>
                                    <div class="small text-secondary">
                                        @if (! empty($latest['published_at']))
                                            {{ __('system.published_at', ['time' => \Illuminate\Support\Carbon::parse($latest['published_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i')]) }}
                                        @endif
                                        @if (! empty($latest['asset']['size']))
                                            · {{ __('system.package_size', ['size' => number_format($latest['asset']['size'] / 1048576, 1, ',', '.') . ' MB']) }}
                                        @endif
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary" id="start-update" data-version="{{ $latest['version'] }}"
                                        data-confirm="{{ __('system.confirm_update', ['version' => $latest['version']]) }}">
                                    {!! tabler_icon('download', ['class' => 'icon']) !!} {{ __('system.update_now', ['version' => $latest['version']]) }}
                                </button>
                            </div>
                        </div>
                    @elseif ($latest && ! empty($latest['version']))
                        <div class="alert alert-success mb-3">{{ __('system.up_to_date') }}</div>
                    @elseif ($latest)
                        <div class="alert alert-secondary mb-3">{{ __('system.no_releases') }}</div>
                    @endif

                    <div id="update-progress" class="d-none mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="spinner-border spinner-border-sm text-primary" id="update-spinner"></span>
                            <strong id="update-status">{{ __('system.running') }}</strong>
                        </div>
                        <ol class="list-unstyled mb-0 small" id="update-steps">
                            @foreach (\App\Updater\Updater::STAGES as $stage)
                                <li data-stage="{{ $stage }}" class="text-secondary">○ {{ __('system.stages.' . $stage) }}</li>
                            @endforeach
                        </ol>
                        <pre class="small bg-light p-2 mt-2 mb-0 d-none" id="update-log"></pre>
                    </div>

                    @if (! empty($latest['notes']))
                        <h3 class="card-title mt-2">{{ __('system.release_notes') }} — {{ $latest['name'] ?? '' }}</h3>
                        <div class="markdown text-break">
                            {!! \Illuminate\Support\Str::markdown($latest['notes'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if ($backup)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="subheader mb-2">{{ __('system.backups') }}</div>
                        <p class="text-secondary small mb-2">{{ $backup['from'] }} → {{ $backup['to'] }} · {{ \Illuminate\Support\Carbon::parse($backup['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                        <form method="POST" action="{{ route('system.update.rollback') }}"
                              onsubmit="return confirm(@js(__('system.confirm_rollback', ['version' => $backup['from']])))">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100">
                                {!! tabler_icon('arrow-back-up', ['class' => 'icon']) !!} {{ __('system.rollback', ['version' => $backup['from']]) }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="subheader mb-2">{{ __('system.history') }}</div>
                    @forelse ($history as $entry)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>{{ $entry['from'] ?? '?' }} → {{ $entry['to'] ?? '?' }}</span>
                            <span class="badge bg-{{ ($entry['status'] ?? '') === 'done' ? 'success' : (($entry['status'] ?? '') === 'failed' ? 'danger' : 'warning') }}-lt">{{ $entry['status'] ?? '' }}</span>
                        </div>
                        @if (! empty($entry['error']))
                            <div class="text-danger small">{{ $entry['error'] }}</div>
                        @endif
                    @empty
                        <p class="text-secondary small mb-0">—</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const button = document.getElementById('start-update');
                if (!button) return;

                const progress = document.getElementById('update-progress');
                const status = document.getElementById('update-status');
                const spinner = document.getElementById('update-spinner');
                const log = document.getElementById('update-log');
                const token = document.querySelector('meta[name="csrf-token"]')?.content || @js(csrf_token());
                const mark = (stage, symbol, cls) => {
                    const item = document.querySelector(`#update-steps [data-stage="${stage}"]`);
                    if (item) { item.className = cls; item.textContent = symbol + ' ' + item.textContent.slice(2); }
                };

                const call = async (body) => {
                    const response = await fetch(@js(route('system.update.stage')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify(body || {}),
                        credentials: 'same-origin',
                    });
                    const data = await response.json().catch(() => ({ error: response.status + ' ' + response.statusText }));
                    if (!response.ok || data.error) throw new Error(data.error || data.message || response.statusText);
                    return data;
                };

                button.addEventListener('click', async () => {
                    if (!confirm(button.dataset.confirm)) return;

                    button.disabled = true;
                    progress.classList.remove('d-none');

                    try {
                        let data = await call({ version: button.dataset.version });

                        while (data.next) {
                            mark(data.next, '◐', 'text-primary');
                            const stage = data.next;
                            data = await call();
                            mark(stage, '✓', 'text-success');
                            log.textContent = (data.log || []).join('\n');
                        }

                        spinner.remove();
                        status.textContent = data.message;
                        status.className = 'text-success';
                        setTimeout(() => window.location.reload(), 2500);
                    } catch (error) {
                        spinner.remove();
                        status.textContent = error.message;
                        status.className = 'text-danger';
                        button.disabled = false;
                    } finally {
                        log.classList.remove('d-none');
                    }
                });
            })();
        </script>
    @endpush
</x-ui.layouts::app>

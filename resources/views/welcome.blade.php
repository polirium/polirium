@php
    /** @var array $brand */
    $icons = [
        'box' => '<path d="M21 8.5 12 3 3 8.5v7L12 21l9-5.5z"/><path d="m3 8.5 9 5.5 9-5.5M12 14v7"/>',
        'cart' => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3.5h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2L21 7.5H6"/>',
        'wallet' => '<path d="M19 7V5.5A1.5 1.5 0 0 0 17.5 4h-12A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V15"/><path d="M21 9h-5a3 3 0 0 0 0 6h5z"/><circle cx="16.5" cy="12" r=".8"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 19.5a6 6 0 0 1 12 0"/><path d="M16 4.6a3.2 3.2 0 0 1 0 6.3M18 13.7a6 6 0 0 1 3 5.8"/>',
        'checklist' => '<path d="m4 6 1.5 1.5L8 5M4 12l1.5 1.5L8 11M4 18l1.5 1.5L8 17M11 6.5h9M11 12.5h9M11 18.5h9"/>',
        'printer' => '<path d="M7 9V3.5h10V9M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6.5H7z"/>',
        'layout' => '<rect x="3" y="3" width="7.5" height="9" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="5" rx="1.5"/><rect x="13.5" y="11" width="7.5" height="10" rx="1.5"/><rect x="3" y="15" width="7.5" height="6" rx="1.5"/>',
        'branches' => '<path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/>',
        'roles' => '<path d="M12 3 4.5 6v5.5c0 4.4 3.2 8.4 7.5 9.5 4.3-1.1 7.5-5.1 7.5-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
        'audit' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'updates' => '<path d="M20 12a8 8 0 1 1-2.4-5.7L20 8.5"/><path d="M20 3.5v5h-5M12 8v4l3 2"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'login' => '<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M10 16l4-4-4-4M14 12H3"/>',
    ];
    $icon = fn (string $name, string $class = 'ico') => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
    $userName = $user?->name ?: $user?->username;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $brand['name'] }}</title>
    <meta name="description" content="{{ __('welcome.meta_description', ['name' => $brand['name']]) }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root {
            --bg: #f6f8fb;
            --surface: #ffffff;
            --surface-2: #f1f4f9;
            --border: #e3e8ef;
            --text: #0f172a;
            --muted: #5b6577;
            --primary: #2563eb;
            --primary-strong: #1d4ed8;
            --primary-soft: #e8efff;
            --accent: #0ea5e9;
            --success: #16a34a;
            --shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 32px -12px rgba(15, 23, 42, .12);
            --radius: 14px;
            --font: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b1120;
                --surface: #111a2e;
                --surface-2: #16213a;
                --border: #233150;
                --text: #e6ebf5;
                --muted: #93a0b8;
                --primary: #5b8cff;
                --primary-strong: #7aa2ff;
                --primary-soft: rgba(91, 140, 255, .14);
                --accent: #38bdf8;
                --success: #4ade80;
                --shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 18px 40px -16px rgba(0, 0, 0, .55);
            }
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            font-family: var(--font);
            font-size: 16px;
            line-height: 1.6;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .ico { width: 20px; height: 20px; flex: none; }
        .wrap { width: 100%; max-width: 1160px; margin: 0 auto; padding: 0 20px; }
        :focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; border-radius: 8px; }

        /* Header */
        .header {
            position: sticky; top: 0; z-index: 10;
            background: color-mix(in srgb, var(--bg) 86%, transparent);
            backdrop-filter: saturate(160%) blur(10px);
            border-bottom: 1px solid var(--border);
        }
        .header .wrap { display: flex; align-items: center; justify-content: space-between; gap: 16px; height: 68px; }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .brand img { height: 38px; width: auto; max-width: 190px; object-fit: contain; }
        .brand span { font-weight: 700; font-size: 17px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header-actions { display: flex; align-items: center; gap: 10px; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 44px; padding: 0 18px; border-radius: 10px; border: 1px solid transparent;
            font: 600 15px/1 var(--font); cursor: pointer; white-space: nowrap;
            transition: background-color .15s ease, border-color .15s ease, transform .15s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 8px 20px -8px color-mix(in srgb, var(--primary) 70%, transparent); }
        .btn-primary:hover { background: var(--primary-strong); }
        .btn-ghost { background: var(--surface); border-color: var(--border); color: var(--text); }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { height: 38px; padding: 0 14px; font-size: 14px; }
        .link-button { background: none; border: 0; padding: 0; font: inherit; color: var(--muted); cursor: pointer; }
        .link-button:hover { color: var(--primary); }

        /* Hero */
        .hero { padding: 72px 0 56px; }
        .hero .wrap { display: grid; grid-template-columns: 1.05fr .95fr; gap: 56px; align-items: center; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
            background: var(--primary-soft); color: var(--primary); font-size: 13px; font-weight: 600;
        }
        .eyebrow::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        h1 {
            margin: 18px 0 16px; font-size: clamp(34px, 5vw, 54px); line-height: 1.08;
            letter-spacing: -.025em; font-weight: 800; text-wrap: balance;
        }
        h1 em {
            font-style: normal; display: block;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .lead { margin: 0 0 28px; font-size: 18px; color: var(--muted); max-width: 34em; }
        .cta { display: flex; flex-wrap: wrap; gap: 12px; }

        .greeting { margin: 0 0 6px; font-weight: 700; font-size: 18px; }
        .greeting-hint { margin: 0 0 16px; color: var(--muted); }
        .shortcuts { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
        .shortcut {
            display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 12px;
            background: var(--surface); border: 1px solid var(--border); font-weight: 600; font-size: 15px;
            transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
        }
        .shortcut:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow); }
        .shortcut .tile { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; background: var(--primary-soft); color: var(--primary); flex: none; }
        .shortcut .go { margin-left: auto; color: var(--muted); width: 16px; height: 16px; }
        .signout { margin-top: 14px; font-size: 14px; }

        /* Product preview (decorative) */
        .preview {
            position: relative; border-radius: 18px; background: var(--surface); border: 1px solid var(--border);
            box-shadow: var(--shadow); padding: 18px; overflow: hidden;
        }
        .preview::before {
            content: ""; position: absolute; inset: -40% -20% auto auto; width: 70%; height: 80%;
            background: radial-gradient(closest-side, color-mix(in srgb, var(--primary) 22%, transparent), transparent);
            pointer-events: none;
        }
        .preview-bar { display: flex; gap: 6px; margin-bottom: 16px; }
        .preview-bar i { width: 10px; height: 10px; border-radius: 50%; background: var(--border); }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
        .kpi { padding: 12px; border-radius: 12px; background: var(--surface-2); }
        .kpi small { display: block; color: var(--muted); font-size: 12px; margin-bottom: 8px; }
        .kpi b { display: block; height: 10px; border-radius: 6px; background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .kpi:nth-child(2) b { width: 70%; }
        .kpi:nth-child(3) b { width: 45%; background: var(--success); }
        .chart { padding: 14px; border-radius: 12px; background: var(--surface-2); }
        .chart small { color: var(--muted); font-size: 12px; }
        .bars { display: flex; align-items: flex-end; gap: 8px; height: 120px; margin-top: 10px; }
        .bars span { flex: 1; border-radius: 6px 6px 3px 3px; background: color-mix(in srgb, var(--primary) 30%, transparent); }
        .bars span:last-child { background: var(--primary); }
        .rows { margin-top: 14px; display: grid; gap: 8px; }
        .rows div { display: flex; align-items: center; gap: 10px; }
        .rows i { width: 28px; height: 28px; border-radius: 8px; background: var(--surface-2); flex: none; }
        .rows em { height: 8px; border-radius: 6px; background: var(--surface-2); flex: 1; }
        .rows em:last-child { flex: 0 0 18%; background: color-mix(in srgb, var(--success) 35%, transparent); }

        /* Sections */
        section.block { padding: 56px 0; }
        .section-head { max-width: 640px; margin-bottom: 28px; }
        .section-head h2 { margin: 0 0 8px; font-size: clamp(24px, 3vw, 32px); letter-spacing: -.02em; line-height: 1.2; }
        .section-head p { margin: 0; color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .card {
            padding: 22px; border-radius: var(--radius); background: var(--surface); border: 1px solid var(--border);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .card:hover { border-color: color-mix(in srgb, var(--primary) 45%, var(--border)); box-shadow: var(--shadow); }
        .card .tile { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 12px; background: var(--primary-soft); color: var(--primary); margin-bottom: 14px; }
        .card .tile .ico { width: 22px; height: 22px; }
        .card h3 { margin: 0 0 6px; font-size: 17px; }
        .card p { margin: 0; color: var(--muted); font-size: 15px; }
        .highlights { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .highlight { display: flex; gap: 14px; align-items: flex-start; }
        .highlight .tile { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--primary); flex: none; }
        .highlight h3 { margin: 0 0 4px; font-size: 16px; }
        .highlight p { margin: 0; color: var(--muted); font-size: 14px; }

        /* Footer */
        .footer { border-top: 1px solid var(--border); padding: 24px 0 32px; color: var(--muted); font-size: 14px; }
        .footer .wrap { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; }
        .footer a { color: var(--text); font-weight: 600; }
        .footer a:hover { color: var(--primary); }

        @media (max-width: 960px) {
            .hero { padding-top: 48px; }
            .hero .wrap { grid-template-columns: 1fr; gap: 40px; }
            .highlights { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 560px) {
            .wrap { padding: 0 16px; }
            .header .wrap { height: 60px; }
            .brand img { height: 32px; max-width: 140px; }
            .header-actions .btn span { display: none; }
            .header-actions .btn { width: 44px; padding: 0; }
            .hero { padding: 36px 0 32px; }
            .lead { font-size: 16px; }
            .cta .btn { flex: 1 1 100%; }
            .highlights { grid-template-columns: 1fr; }
            section.block { padding: 40px 0; }
            .preview { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="wrap">
            <a href="{{ url('/') }}" class="brand" aria-label="{{ $brand['name'] }}">
                <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}">
            </a>
            <div class="header-actions">
                @auth
                    <a href="{{ $adminUrl }}" class="btn btn-primary btn-sm" title="{{ __('welcome.open_admin') }}">
                        {!! $icon('layout') !!}<span>{{ __('welcome.open_admin') }}</span>
                    </a>
                @else
                    <a href="{{ $loginUrl }}" class="btn btn-primary btn-sm" title="{{ __('welcome.login') }}">
                        {!! $icon('login') !!}<span>{{ __('welcome.login') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="wrap">
                <div>
                    <span class="eyebrow">{{ __('welcome.eyebrow') }}</span>
                    <h1>{{ __('welcome.title') }} <em>{{ __('welcome.title_accent') }}</em></h1>
                    <p class="lead">{{ __('welcome.lead', ['name' => $brand['name']]) }}</p>

                    @auth
                        <p class="greeting">{{ __('welcome.greeting', ['name' => $userName]) }}</p>
                        <p class="greeting-hint">{{ __('welcome.greeting_hint') }}</p>

                        @if ($shortcuts)
                            <nav class="shortcuts" aria-label="{{ __('welcome.greeting_hint') }}">
                                @foreach ($shortcuts as $shortcut)
                                    <a href="{{ $shortcut['url'] }}" class="shortcut">
                                        <span class="tile">{!! $icon($shortcut['icon']) !!}</span>
                                        {{ __('welcome.shortcuts.' . $shortcut['key']) }}
                                        {!! $icon('arrow', 'ico go') !!}
                                    </a>
                                @endforeach
                            </nav>
                        @else
                            <div class="cta">
                                <a href="{{ $adminUrl }}" class="btn btn-primary">{{ __('welcome.open_admin') }} {!! $icon('arrow') !!}</a>
                            </div>
                        @endif

                        @if (Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}" class="signout">
                                @csrf
                                <button type="submit" class="link-button">{{ __('welcome.logout') }}</button>
                            </form>
                        @endif
                    @else
                        <div class="cta">
                            <a href="{{ $loginUrl }}" class="btn btn-primary">{!! $icon('login') !!} {{ __('welcome.login') }}</a>
                            <a href="{{ $adminUrl }}" class="btn btn-ghost">{{ __('welcome.open_admin') }} {!! $icon('arrow') !!}</a>
                        </div>
                    @endauth
                </div>

                <div class="preview" aria-hidden="true">
                    <div class="preview-bar"><i></i><i></i><i></i></div>
                    <div class="kpis">
                        <div class="kpi"><small>{{ __('welcome.preview_revenue') }}</small><b></b></div>
                        <div class="kpi"><small>{{ __('welcome.preview_orders') }}</small><b></b></div>
                        <div class="kpi"><small>{{ __('welcome.preview_stock') }}</small><b></b></div>
                    </div>
                    <div class="chart">
                        <small>{{ __('welcome.preview_chart') }}</small>
                        <div class="bars">
                            @foreach ([42, 58, 50, 66, 61, 78, 92] as $height)
                                <span style="height: {{ $height }}%"></span>
                            @endforeach
                        </div>
                    </div>
                    <div class="rows">
                        @foreach ([72, 56, 64] as $width)
                            <div><i></i><em style="flex-basis: {{ $width }}%"></em><em></em></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        @if ($modules)
            <section class="block" id="modules">
                <div class="wrap">
                    <div class="section-head">
                        <h2>{{ __('welcome.modules_title') }}</h2>
                        <p>{{ __('welcome.modules_lead') }}</p>
                    </div>
                    <div class="grid">
                        @foreach ($modules as $module)
                            <article class="card">
                                <span class="tile">{!! $icon($module['icon']) !!}</span>
                                <h3>{{ __('welcome.module.' . $module['name'] . '.title') }}</h3>
                                <p>{{ __('welcome.module.' . $module['name'] . '.desc') }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="block">
            <div class="wrap">
                <div class="section-head">
                    <h2>{{ __('welcome.highlights_title') }}</h2>
                </div>
                <div class="highlights">
                    @foreach (['branches', 'roles', 'audit', 'updates'] as $key)
                        <div class="highlight">
                            <span class="tile">{!! $icon($key) !!}</span>
                            <div>
                                <h3>{{ __('welcome.highlight.' . $key . '.title') }}</h3>
                                <p>{{ __('welcome.highlight.' . $key . '.desc') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="wrap">
            <span>© {{ date('Y') }} {{ $brand['name'] }}. {{ __('welcome.all_rights') }}</span>
            <span>
                {{ __('welcome.powered_by') }} <a href="https://polirium.com" target="_blank" rel="noopener">Polirium</a>
                @if ($version) · {{ __('welcome.version', ['version' => $version]) }} @endif
            </span>
        </div>
    </footer>
</body>
</html>

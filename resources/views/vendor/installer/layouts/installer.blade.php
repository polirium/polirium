<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('installer::installer.title', ['name' => config('installer.name', 'App')]) }}</title>
    <link rel="icon" type="image/png" href="{{ config('installer.favicon', asset('favicon.png')) }}">
    <link rel="stylesheet" href="{{ asset('installer/installer.css') }}">
    <style>
        :root {
            --theme-primary: {{ config('installer.theme.primary', '#6366f1') }};
            --theme-primary-dark: {{ config('installer.theme.primary_dark', '#4f46e5') }};
        }

        /* Critical fallback styles — ensure layout is visible even if installer.css fails to load */
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased; background: #f1f5f9; }
        [x-cloak] { display: none !important; }
        .installer-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .installer-card { width: 90%; max-width: 1920px; min-height: 85vh; display: flex; flex-direction: column; background: #fff; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        @media (min-width: 768px) { .installer-card { flex-direction: row; } }
        .installer-sidebar { width: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 2.5rem; background: #0f172a; color: #fff; flex-shrink: 0; }
        @media (min-width: 768px) { .installer-sidebar { width: 350px; } }
        .installer-content { flex: 1; display: flex; flex-direction: column; padding: 2rem; }
        @media (min-width: 768px) { .installer-content { padding: 3rem; } }
        .btn { display: inline-flex; align-items: center; padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; cursor: pointer; border: none; line-height: 1.5; }
        .btn--continue { background: var(--theme-primary, #6366f1); color: #fff; }
        .btn--back { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; color: #1e293b; background: #fff; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
        .section-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem 0; }
        .section-subtitle { color: #64748b; margin: 0 0 1.5rem 0; font-size: 0.875rem; }
        /* Premium Button Styles */
        .btn { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .btn:active:not(:disabled) { transform: translateY(0); }
        
        /* Glassmorphism Sidebar */
        @media (min-width: 768px) {
            .installer-sidebar {
                background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.98));
                border-right: 1px solid rgba(255,255,255,0.1);
            }
        }
    </style>
    @livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>

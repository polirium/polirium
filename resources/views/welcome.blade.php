<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Polirium ERP - Hệ thống Quản lý Doanh nghiệp</title>
    <meta name="description" content="Polirium ERP - Giải pháp quản lý doanh nghiệp toàn diện: bán hàng, kho, sổ quỹ, nhân sự">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --page-bg: #0f172a;
            --card-bg: #1e293b;
            --card-border: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-primary: #3b82f6;
            --accent-secondary: #8b5cf6;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--page-bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .logo-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .logo-img {
            height: 40px;
            width: auto;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--accent-primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: #2563eb;
            color: #fff;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--card-border);
        }

        .btn-secondary:hover {
            background: var(--card-bg);
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .btn span {
            display: inline;
        }

        /* Main Content */
        .main {
            flex: 1;
            padding: 1.25rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 3rem 0;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 9999px;
            color: var(--accent-primary);
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 0.75rem;
        }

        .hero-title-accent {
            background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 500px;
            margin: 0 auto 2rem;
            line-height: 1.6;
        }

        /* Quick Actions Grid */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .action-card:hover {
            border-color: var(--accent-primary);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }

        .action-icon {
            width: 44px;
            height: 44px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .action-icon svg {
            width: 22px;
            height: 22px;
            color: var(--accent-primary);
        }

        .action-content {
            flex: 1;
        }

        .action-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.125rem;
        }

        .action-desc {
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        .action-arrow {
            color: var(--text-secondary);
            transition: transform 0.2s ease;
        }

        .action-card:hover .action-arrow {
            transform: translateX(3px);
            color: var(--accent-primary);
        }

        /* Features Section */
        .features-section {
            margin-bottom: 3rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }

        .feature-card:hover {
            border-color: var(--accent-primary);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
            color: #fff;
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: rgba(30, 41, 59, 0.5);
            border-top: 1px solid var(--card-border);
            padding: 1.5rem 1.25rem;
            text-align: center;
        }

        .footer-text {
            color: var(--text-secondary);
            font-size: 0.8125rem;
        }

        /* Icons SVG */
        .icon {
            width: 1em;
            height: 1em;
            display: inline-block;
        }

        /* Responsive - Tablet & Desktop */
        @media (min-width: 640px) {
            .main {
                padding: 2rem 1.5rem;
            }

            .hero {
                padding: 4rem 0;
            }

            .hero-badge {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
                margin-bottom: 2rem;
            }

            .hero-title {
                font-size: 2.75rem;
            }

            .hero-subtitle {
                font-size: 1.125rem;
                margin-bottom: 2.5rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-bottom: 3.5rem;
            }

            .action-card {
                padding: 1.25rem 1.5rem;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }

            .feature-card {
                padding: 1.75rem;
            }
        }

        @media (min-width: 1024px) {
            .header-content {
                padding: 1rem 2rem;
            }

            .main {
                padding: 3rem 2rem;
            }

            .hero-title {
                font-size: 3.5rem;
            }

            .hero-subtitle {
                font-size: 1.25rem;
                max-width: 600px;
            }

            .quick-actions {
                grid-template-columns: repeat(3, 1fr);
                margin-bottom: 4rem;
            }

            .features-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }

            .section-header {
                margin-bottom: 3rem;
            }

            .btn span {
                display: inline;
            }
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <header class="header">
        <div class="header-content">
            <a href="/" class="logo-link">
                <img src="{{ asset('vendor/polirium/core/ui/assets/polirium-logo.png') }}" alt="Polirium" class="logo-img">
            </a>

            <div class="header-actions">
                @auth
                    <a href="{{ url('/admin') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ url('/admin') }}" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>Admin</span>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                        <span>Đăng nhập</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="main">
        {{-- Hero Section --}}
        <section class="hero">
            <div class="hero-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s4.77 5 7 5c4 0 5.5-3.5 7-5 1.5-1.5 3.5-3 5.5-3s1 5.5 5.5 5.5c4 0 7-5 7-5"/><path d="M12 12l3-3"/><path d="M12 12l-3 3"/></svg>
                Nền tảng ERP toàn diện
            </div>

            <h1 class="hero-title">
                Quản lý doanh nghiệp<br>
                <span class="hero-title-accent">thông minh & hiệu quả</span>
            </h1>

            <p class="hero-subtitle">
                Polirium ERP cung cấp giải pháp quản lý toàn diện: Bán hàng, Kho, Sổ quỹ, Nhân sự, Báo cáo - tất cả trong một nền tảng.
            </p>
        </section>

        {{-- Quick Actions --}}
        <section class="quick-actions">
            @auth
                {{-- Dashboard --}}
                <a href="{{ url('/admin') }}" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Dashboard</div>
                        <div class="action-desc">Tổng quan hệ thống</div>
                    </div>
                    <div class="action-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/><path d="M15 12H3"/></svg>
                    </div>
                </a>

                {{-- POS V1 --}}
                @if(Route::has('products.payment'))
                    <a href="{{ route('products.payment') }}" target="_blank" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">POS V1</div>
                            <div class="action-desc">Bán hàng nhanh</div>
                        </div>
                        <div class="action-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/><path d="M15 12H3"/></svg>
                        </div>
                    </a>
                @endif

                {{-- POS V2 --}}
                @if(Route::has('products.payment.v2'))
                    <a href="{{ route('products.payment.v2') }}" target="_blank" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M13 17l5-5-5-5"/></svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">POS V2</div>
                            <div class="action-desc">Bán hàng nâng cao</div>
                        </div>
                        <div class="action-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/><path d="M15 12H3"/></svg>
                        </div>
                    </a>
                @endif
            @else
                {{-- For guests --}}
                <a href="{{ route('login') }}" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Đăng nhập</div>
                        <div class="action-desc">Truy cập hệ thống</div>
                    </div>
                    <div class="action-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/><path d="M15 12H3"/></svg>
                    </div>
                </a>

                <a href="{{ url('/admin') }}" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Admin Panel</div>
                        <div class="action-desc">Quản trị hệ thống</div>
                    </div>
                    <div class="action-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/><path d="M15 12H3"/></svg>
                    </div>
                </a>
            @endauth
        </section>

        {{-- Features Section --}}
        <section class="features-section">
            <div class="section-header">
                <h2 class="section-title">Tính năng nổi bật</h2>
                <p class="section-subtitle">Khám phá các tính năng mạnh mẽ của Polirium ERP</p>
            </div>

            <div class="features-grid">
                {{-- Bán hàng --}}
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </div>
                    <h3 class="feature-title">Quản lý Bán hàng</h3>
                    <p class="feature-desc">POS giao diện thân thiện, hỗ trợ đa phương thức thanh toán, quản lý đơn hàng, khuyến mãi.</p>
                </div>

                {{-- Quản lý Kho --}}
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <h3 class="feature-title">Quản lý Kho</h3>
                    <p class="feature-desc">Theo dõi tồn kho thời gian thực, quản lý nhập/xuất/chuyển kho, cảnh báo tồn kho.</p>
                </div>

                {{-- Sổ quỹ --}}
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3 class="feature-title">Sổ quỹ</h3>
                    <p class="feature-desc">Theo dõi thu chi, quản lý tiền mặt và ngân hàng, đối soát doanh thu và báo cáo dòng tiền.</p>
                </div>

                {{-- Nhân sự --}}
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                    <h3 class="feature-title">Quản lý Nhân sự</h3>
                    <p class="feature-desc">Quản lý thông tin nhân viên, chấm công, tính lương, đánh giá hiệu suất và tuyển dụng.</p>
                </div>

                {{-- Báo cáo --}}
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                    <h3 class="feature-title">Báo cáo & Phân tích</h3>
                    <p class="feature-desc">Dashboard trực quan, báo cáo doanh thu, lợi nhuận, xuất nhập tồn và các chỉ số kinh doanh.</p>
                </div>

                {{-- Chi nhánh --}}
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-9h6v9"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
                    </div>
                    <h3 class="feature-title">Đa chi nhánh</h3>
                    <p class="feature-desc">Quản lý nhiều chi nhánh, cửa hàng trong một hệ thống, đồng bộ dữ liệu và báo cáo tổng hợp.</p>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="footer">
        <p class="footer-text">
            © {{ date('Y') }} Polirium ERP. Phiên bản {{ config('app.version', '1.0.0') }}.
            <br class="sm:hidden">Được xây dựng cho doanh nghiệp Việt Nam.
        </p>
    </footer>
</body>
</html>

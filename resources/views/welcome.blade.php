<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Kasir POS') }} — Dashboard Point of Sale</title>
    <meta name="description" content="Dashboard Kasir POS — Sistem manajemen kasir modern dengan fitur lengkap: transaksi, inventori, laporan, dan integrasi mobile.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* ================================================================
           RESET & BASE
           ================================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0B0F0D;
            color: #E8EDE9;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }

        /* ================================================================
           CSS VARIABLES
           ================================================================ */
        :root {
            --primary: #8BAE66;
            --primary-light: #A4C47F;
            --primary-dark: #628141;
            --accent: #C8E6A0;
            --bg-dark: #0B0F0D;
            --bg-card: rgba(255,255,255,0.04);
            --bg-card-hover: rgba(255,255,255,0.07);
            --border: rgba(139,174,102,0.15);
            --border-hover: rgba(139,174,102,0.35);
            --text-primary: #E8EDE9;
            --text-secondary: #8A9B8E;
            --text-muted: #5A6B5E;
            --glass: rgba(11,15,13,0.6);
            --glow: rgba(139,174,102,0.12);
        }

        /* ================================================================
           ANIMATED BACKGROUND
           ================================================================ */
        .bg-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(139,174,102,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(139,174,102,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black 30%, transparent 100%);
        }
        .bg-glow-1 {
            position: fixed;
            top: -30%;
            left: -10%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(139,174,102,0.08) 0%, transparent 70%);
            border-radius: 50%;
            animation: float-glow 15s ease-in-out infinite;
            z-index: 0;
        }
        .bg-glow-2 {
            position: fixed;
            bottom: -20%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139,174,102,0.05) 0%, transparent 70%);
            border-radius: 50%;
            animation: float-glow 20s ease-in-out infinite reverse;
            z-index: 0;
        }
        @keyframes float-glow {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -20px) scale(1.05); }
            66% { transform: translate(-20px, 15px) scale(0.95); }
        }

        /* ================================================================
           NAVBAR
           ================================================================ */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 0 2rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .navbar.scrolled {
            background: rgba(11,15,13,0.85);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border-bottom: 1px solid var(--border);
        }
        .navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.02em;
        }
        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
            box-shadow: 0 0 20px rgba(139,174,102,0.2);
        }
        .logo span { color: var(--primary-light); }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .nav-links a {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: color 0.25s;
            position: relative;
        }
        .nav-links a:hover { color: var(--text-primary); }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            border-radius: 1px;
            transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .nav-links a:hover::after { width: 100%; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            border: none;
            white-space: nowrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 2px 12px rgba(139,174,102,0.25);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(139,174,102,0.35);
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            border-color: var(--border-hover);
            color: var(--text-primary);
            background: var(--bg-card);
        }

        /* ================================================================
           HERO SECTION
           ================================================================ */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 2rem 80px;
        }
        .hero-content {
            max-width: 800px;
            text-align: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px 6px 8px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .hero-badge .dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(139,174,102,0.4); }
            50% { opacity: 0.8; box-shadow: 0 0 0 6px rgba(139,174,102,0); }
        }
        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.2rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.8s 0.1s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .hero h1 .gradient {
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: 1.125rem;
            line-height: 1.7;
            color: var(--text-secondary);
            max-width: 580px;
            margin: 0 auto 2.5rem;
            animation: fadeUp 0.8s 0.2s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            animation: fadeUp 0.8s 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .hero-actions .btn-primary {
            padding: 14px 32px;
            font-size: 0.95rem;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ================================================================
           STATS BAR
           ================================================================ */
        .stats-bar {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: -40px auto 0;
            padding: 0 2rem;
            animation: fadeUp 0.8s 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .stats-bar-inner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .stat-item {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            padding: 1.5rem 1.25rem;
            text-align: center;
        }
        .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary-light);
            letter-spacing: -0.02em;
        }
        .stat-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ================================================================
           FEATURES SECTION
           ================================================================ */
        .section {
            position: relative;
            z-index: 1;
            padding: 100px 2rem;
        }
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        .section-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }
        .section-desc {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 520px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .features-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }
        .feature-card {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary-light), transparent);
            opacity: 0;
            transition: opacity 0.4s;
        }
        .feature-card:hover {
            border-color: var(--border-hover);
            background: var(--bg-card-hover);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        .feature-card:hover::before { opacity: 1; }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(139,174,102,0.15), rgba(139,174,102,0.05));
            border: 1px solid rgba(139,174,102,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 1.25rem;
        }
        .feature-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }
        .feature-card p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.65;
        }

        /* ================================================================
           API SECTION
           ================================================================ */
        .api-section {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        .api-text { padding-right: 1rem; }
        .api-text h2 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }
        .api-text p {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        .api-endpoints { list-style: none; margin-bottom: 2rem; }
        .api-endpoints li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .api-endpoints li:last-child { border-bottom: none; }
        .api-method {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            font-family: 'Inter', monospace;
            letter-spacing: 0.02em;
        }
        .method-get { background: rgba(59,130,246,0.15); color: #60A5FA; }
        .method-post { background: rgba(34,197,94,0.15); color: #4ADE80; }
        .method-put { background: rgba(251,191,36,0.15); color: #FBBF24; }

        .code-window {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .code-titlebar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .code-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .code-dot.red { background: #FF5F56; }
        .code-dot.yellow { background: #FFBD2E; }
        .code-dot.green { background: #27C93F; }
        .code-titlebar span {
            margin-left: 8px;
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .code-body {
            padding: 1.25rem 1.5rem;
            font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
            font-size: 0.8rem;
            line-height: 1.8;
            color: var(--text-secondary);
            overflow-x: auto;
        }
        .code-body .key { color: #7DD3FC; }
        .code-body .str { color: var(--accent); }
        .code-body .num { color: #FBBF24; }
        .code-body .comment { color: var(--text-muted); font-style: italic; }
        .code-body .punct { color: #6B7280; }

        /* ================================================================
           CTA SECTION
           ================================================================ */
        .cta {
            position: relative;
            z-index: 1;
            padding: 80px 2rem;
        }
        .cta-inner {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
            padding: 4rem 3rem;
            background: linear-gradient(135deg, rgba(139,174,102,0.08), rgba(139,174,102,0.02));
            border: 1px solid var(--border);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
        }
        .cta-inner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }
        .cta-inner h2 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }
        .cta-inner p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        /* ================================================================
           FOOTER
           ================================================================ */
        .footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 2.5rem 2rem;
        }
        .footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .footer-left {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .footer-right {
            display: flex;
            gap: 1.5rem;
        }
        .footer-right a {
            font-size: 0.8rem;
            color: var(--text-muted);
            transition: color 0.25s;
        }
        .footer-right a:hover { color: var(--primary-light); }

        /* ================================================================
           SCROLL REVEAL
           ================================================================ */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ================================================================
           RESPONSIVE
           ================================================================ */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .api-section { grid-template-columns: 1fr; }
            .stats-bar-inner { grid-template-columns: repeat(2, 1fr); }
            .hero-actions { flex-direction: column; }
            .footer-inner { flex-direction: column; gap: 1rem; text-align: center; }
            .hero { padding-top: 100px; }
        }
        @media (min-width: 769px) and (max-width: 1024px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <!-- Background Effects -->
    <div class="bg-grid"></div>
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-inner">
            <a href="/" class="logo">
                <div class="logo-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                Kasir <span>POS</span>
            </a>
            <div class="nav-links">
                <a href="#features">Fitur</a>
                <a href="#api">API</a>
                <a href="{{ url('/admin/login') }}" class="btn btn-ghost">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Masuk
                </a>
                <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                    Dashboard
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="dot"></span>
                Versi Terbaru — Siap Produksi
            </div>
            <h1>Kelola Bisnis Anda Lebih <span class="gradient">Cerdas & Efisien</span></h1>
            <p>Sistem Point of Sale modern dengan dashboard real-time, sinkronisasi offline, integrasi pembayaran QRIS, dan API lengkap untuk aplikasi mobile Anda.</p>
            <div class="hero-actions">
                <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                    Masuk Dashboard
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="#api" class="btn btn-ghost">Lihat API Docs</a>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stats-bar-inner">
            <div class="stat-item">
                <div class="stat-number">30+</div>
                <div class="stat-label">API Endpoint</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">Real-time</div>
                <div class="stat-label">Sync Data</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">QRIS</div>
                <div class="stat-label">Pembayaran</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">Mobile</div>
                <div class="stat-label">Flutter Ready</div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <section class="section" id="features">
        <div class="section-header reveal">
            <div class="section-label">Fitur Unggulan</div>
            <h2 class="section-title">Semua yang Anda Butuhkan</h2>
            <p class="section-desc">Dirancang khusus untuk operasional kasir dan manajemen toko yang efisien.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card reveal">
                <div class="feature-icon">📊</div>
                <h3>Dashboard Analytics</h3>
                <p>Pantau penjualan harian, per jam, dan produk terlaris secara real-time dengan grafik interaktif.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">📱</div>
                <h3>Offline-First Sync</h3>
                <p>Transaksi tetap berjalan meski internet mati. Data tersinkronisasi otomatis saat koneksi kembali.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">💳</div>
                <h3>Pembayaran QRIS</h3>
                <p>Terima pembayaran digital via QRIS dengan integrasi Xendit. Verifikasi otomatis via webhook.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">🚚</div>
                <h3>Manajemen Kurir</h3>
                <p>Fitur shift kurir lengkap: mulai shift, tracking pesanan, hingga laporan performa pengiriman.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">📋</div>
                <h3>Sistem Bon (Kasbon)</h3>
                <p>Kelola kasbon pelanggan dengan laporan hutang, jatuh tempo, dan pelunasan dalam satu klik.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">👥</div>
                <h3>Multi User & Import</h3>
                <p>Kelola user dengan role admin, kasir, dan kurir. Import banyak user sekaligus via file CSV.</p>
            </div>
        </div>
    </section>

    <!-- API Section -->
    <section class="section" id="api">
        <div class="api-section">
            <div class="api-text reveal">
                <div class="section-label">REST API</div>
                <h2>API Lengkap untuk <span style="color:var(--primary-light)">Integrasi</span></h2>
                <p>Dokumentasi API yang siap pakai untuk menghubungkan aplikasi Flutter, React Native, atau platform lain ke backend Kasir POS.</p>
                <ul class="api-endpoints">
                    <li><span class="api-method method-post">POST</span> /auth/login — Autentikasi username</li>
                    <li><span class="api-method method-get">GET</span> /products — Daftar produk aktif</li>
                    <li><span class="api-method method-post">POST</span> /transactions — Buat transaksi baru</li>
                    <li><span class="api-method method-post">POST</span> /sync/upload — Upload transaksi offline</li>
                    <li><span class="api-method method-get">GET</span> /analytics/daily — Laporan harian</li>
                    <li><span class="api-method method-put">PUT</span> /delivery/orders/{id}/status — Update kurir</li>
                </ul>
                <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                    Mulai Sekarang
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
            <div class="code-window reveal">
                <div class="code-titlebar">
                    <div class="code-dot red"></div>
                    <div class="code-dot yellow"></div>
                    <div class="code-dot green"></div>
                    <span>POST /api/v1/auth/login</span>
                </div>
                <div class="code-body">
<span class="comment">// Request Body</span>
<span class="punct">{</span>
  <span class="key">"username"</span><span class="punct">:</span> <span class="str">"fathur"</span><span class="punct">,</span>
  <span class="key">"password"</span><span class="punct">:</span> <span class="str">"password123"</span><span class="punct">,</span>
  <span class="key">"device_name"</span><span class="punct">:</span> <span class="str">"Kasir-Tab-01"</span>
<span class="punct">}</span>

<span class="comment">// Response 200 OK</span>
<span class="punct">{</span>
  <span class="key">"token"</span><span class="punct">:</span> <span class="str">"1|abc...xyz"</span><span class="punct">,</span>
  <span class="key">"user"</span><span class="punct">:</span> <span class="punct">{</span>
    <span class="key">"id"</span><span class="punct">:</span> <span class="num">1</span><span class="punct">,</span>
    <span class="key">"name"</span><span class="punct">:</span> <span class="str">"Fathur"</span><span class="punct">,</span>
    <span class="key">"username"</span><span class="punct">:</span> <span class="str">"fathur"</span><span class="punct">,</span>
    <span class="key">"role"</span><span class="punct">:</span> <span class="str">"admin"</span>
  <span class="punct">}</span>
<span class="punct">}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="cta-inner reveal">
            <h2>Siap Mengelola Toko Anda?</h2>
            <p>Masuk ke dashboard admin untuk mulai mengelola produk, transaksi, dan laporan bisnis Anda.</p>
            <a href="{{ url('/admin/login') }}" class="btn btn-primary" style="padding:14px 36px; font-size:1rem;">
                Masuk Dashboard
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-left">
                &copy; {{ date('Y') }} Kasir POS — All rights reserved.
            </div>
            <div class="footer-right">
                <a href="#features">Fitur</a>
                <a href="#api">API</a>
                <a href="{{ url('/admin/login') }}">Dashboard</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        });

        // Scroll reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    // Stagger animation for siblings
                    const siblings = entry.target.parentElement.querySelectorAll('.reveal');
                    const index = Array.from(siblings).indexOf(entry.target);
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    </script>
</body>
</html>

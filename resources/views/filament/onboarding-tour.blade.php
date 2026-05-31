<style>
    /* ============================================
       ONBOARDING TOUR — Styles
       ============================================ */
    .tour-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        z-index: 9998;
        opacity: 0;
        transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }
    .tour-overlay.active {
        opacity: 1;
        pointer-events: all;
    }

    .tour-spotlight {
        position: fixed;
        z-index: 9999;
        border-radius: 12px;
        box-shadow:
            0 0 0 4000px rgba(0, 0, 0, 0.6),
            0 0 0 3px rgba(139, 174, 102, 0.6),
            0 0 30px rgba(139, 174, 102, 0.15);
        transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    .tour-tooltip {
        position: fixed;
        z-index: 10000;
        width: 360px;
        max-width: calc(100vw - 32px);
        background: linear-gradient(135deg, #1B211A 0%, #222A20 100%);
        border: 1px solid rgba(139, 174, 102, 0.3);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(139, 174, 102, 0.08);
        opacity: 0;
        transform: translateY(12px) scale(0.96);
        transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: 'Figtree', 'Inter', system-ui, sans-serif;
    }
    .tour-tooltip.visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    .tour-tooltip::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(139, 174, 102, 0.5), transparent);
    }

    .tour-tooltip-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .tour-tooltip-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(139, 174, 102, 0.2), rgba(139, 174, 102, 0.08));
        border: 1px solid rgba(139, 174, 102, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .tour-tooltip-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #E8EDE9;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }
    .tour-tooltip-step {
        font-size: 0.7rem;
        color: rgba(139, 174, 102, 0.7);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .tour-tooltip-body {
        font-size: 0.85rem;
        color: #8A9B8E;
        line-height: 1.65;
        margin-bottom: 20px;
    }

    .tour-tooltip-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .tour-tooltip-progress {
        display: flex;
        gap: 5px;
    }
    .tour-progress-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(139, 174, 102, 0.2);
        transition: all 0.3s;
    }
    .tour-progress-dot.active {
        background: #8BAE66;
        box-shadow: 0 0 8px rgba(139, 174, 102, 0.4);
    }
    .tour-progress-dot.done {
        background: rgba(139, 174, 102, 0.5);
    }

    .tour-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: inherit;
    }
    .tour-btn-next {
        background: linear-gradient(135deg, #628141, #8BAE66);
        color: white;
        box-shadow: 0 2px 12px rgba(139, 174, 102, 0.3);
    }
    .tour-btn-next:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 18px rgba(139, 174, 102, 0.4);
    }
    .tour-btn-skip {
        background: transparent;
        color: #5A6B5E;
        padding: 8px 12px;
    }
    .tour-btn-skip:hover {
        color: #8A9B8E;
    }
    .tour-btn-actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    /* Welcome Modal */
    .tour-welcome-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.4s;
        pointer-events: none;
    }
    .tour-welcome-overlay.active {
        opacity: 1;
        pointer-events: all;
    }
    .tour-welcome-modal {
        width: 420px;
        max-width: calc(100vw - 32px);
        background: linear-gradient(135deg, #1B211A 0%, #222A20 100%);
        border: 1px solid rgba(139, 174, 102, 0.3);
        border-radius: 20px;
        padding: 36px 32px 28px;
        text-align: center;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
        transform: scale(0.9) translateY(20px);
        transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: 'Figtree', 'Inter', system-ui, sans-serif;
        position: relative;
        overflow: hidden;
    }
    .tour-welcome-modal::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(139, 174, 102, 0.6), transparent);
    }
    .tour-welcome-overlay.active .tour-welcome-modal {
        transform: scale(1) translateY(0);
    }
    .tour-welcome-emoji {
        font-size: 3rem;
        margin-bottom: 16px;
        display: block;
        animation: tour-bounce 1.5s ease-in-out infinite;
    }
    @keyframes tour-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
    .tour-welcome-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #E8EDE9;
        margin-bottom: 8px;
        letter-spacing: -0.02em;
    }
    .tour-welcome-desc {
        font-size: 0.9rem;
        color: #8A9B8E;
        line-height: 1.65;
        margin-bottom: 28px;
    }
    .tour-welcome-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .tour-welcome-start {
        width: 100%;
        padding: 14px;
        border-radius: 12px;
        background: linear-gradient(135deg, #628141, #8BAE66);
        color: white;
        font-size: 0.95rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(139, 174, 102, 0.3);
        transition: all 0.25s;
        font-family: inherit;
    }
    .tour-welcome-start:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(139, 174, 102, 0.4);
    }
    .tour-welcome-dismiss {
        background: none;
        border: none;
        color: #5A6B5E;
        font-size: 0.82rem;
        cursor: pointer;
        padding: 8px;
        font-family: inherit;
        transition: color 0.2s;
    }
    .tour-welcome-dismiss:hover {
        color: #8A9B8E;
    }

    /* Pulse ring animation on spotlight */
    @keyframes tour-pulse-ring {
        0% { box-shadow: 0 0 0 4000px rgba(0,0,0,0.6), 0 0 0 3px rgba(139,174,102,0.6), 0 0 30px rgba(139,174,102,0.15); }
        50% { box-shadow: 0 0 0 4000px rgba(0,0,0,0.6), 0 0 0 5px rgba(139,174,102,0.4), 0 0 40px rgba(139,174,102,0.25); }
        100% { box-shadow: 0 0 0 4000px rgba(0,0,0,0.6), 0 0 0 3px rgba(139,174,102,0.6), 0 0 30px rgba(139,174,102,0.15); }
    }
    .tour-spotlight.pulse {
        animation: tour-pulse-ring 2s ease-in-out infinite;
    }
</style>

@auth
<!-- Welcome Modal -->
<div class="tour-welcome-overlay" id="tourWelcome">
    <div class="tour-welcome-modal">
        <span class="tour-welcome-emoji">👋</span>
        <div class="tour-welcome-title">Selamat Datang di Kasir POS!</div>
        <p class="tour-welcome-desc">
            Ini pertama kalinya Anda masuk. Mari kita lihat fitur-fitur utama dashboard dalam tour singkat.
        </p>
        <div class="tour-welcome-actions">
            <button class="tour-welcome-start" onclick="startTour()">
                🚀 Mulai Tour Singkat
            </button>
            <button class="tour-welcome-dismiss" onclick="dismissTour()">
                Lewati, saya sudah paham
            </button>
        </div>
    </div>
</div>

<!-- Tour Elements -->
<div class="tour-overlay" id="tourOverlay"></div>
<div class="tour-spotlight" id="tourSpotlight" style="display:none;"></div>
<div class="tour-tooltip" id="tourTooltip" style="display:none;">
    <div class="tour-tooltip-header">
        <div class="tour-tooltip-icon" id="tourIcon"></div>
        <div>
            <div class="tour-tooltip-step" id="tourStep"></div>
            <div class="tour-tooltip-title" id="tourTitle"></div>
        </div>
    </div>
    <div class="tour-tooltip-body" id="tourBody"></div>
    <div class="tour-tooltip-footer">
        <div class="tour-tooltip-progress" id="tourProgress"></div>
        <div class="tour-btn-actions">
            <button class="tour-btn tour-btn-skip" id="tourSkip" onclick="endTour()">Lewati</button>
            <button class="tour-btn tour-btn-next" id="tourNext" onclick="nextStep()">
                Lanjut
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const TOUR_KEY = 'kasirpos_tour_completed';

    function initTour() {
        // Already completed? Bail out.
        if (localStorage.getItem(TOUR_KEY)) return;

        // Make sure we're on a real dashboard page (sidebar exists),
        // not on the login page or other non-panel pages.
        const sidebar = document.querySelector('.fi-sidebar, .fi-sidebar-nav, [class*="fi-sidebar"]');
        if (!sidebar) return;

        // Show the welcome modal
        const welcome = document.getElementById('tourWelcome');
        if (welcome) {
            welcome.classList.add('active');
        }
    }

    // Try on DOMContentLoaded first
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initTour, 1500);
        });
    } else {
        // Page already loaded (e.g. Livewire SPA navigation after login)
        setTimeout(initTour, 1500);
    }

    // Also listen for Livewire navigate events (Filament SPA mode)
    document.addEventListener('livewire:navigated', function() {
        setTimeout(initTour, 1500);
    });
})();

// Tour steps configuration
const tourSteps = [
    {
        // Sidebar
        selector: '.fi-sidebar, [class*="fi-sidebar"]',
        fallback: '.fi-sidebar-nav',
        icon: '📋',
        title: 'Menu Navigasi',
        body: 'Gunakan sidebar ini untuk berpindah antar halaman seperti <b>Products</b>, <b>Orders</b>, <b>Categories</b>, <b>Users</b>, dan lainnya.',
        position: 'right'
    },
    {
        // Stats overview widgets
        selector: '.fi-wi-stats-overview, [class*="fi-wi-stats-overview"]',
        fallback: '.fi-page-content > div:first-child',
        icon: '📊',
        title: 'Ringkasan Statistik',
        body: 'Di sini Anda dapat melihat ringkasan penjualan hari ini seperti <b>total pendapatan</b>, <b>jumlah transaksi</b>, dan <b>produk terjual</b> secara real-time.',
        position: 'bottom'
    },
    {
        // Charts
        selector: '.fi-wi-chart, [class*="fi-wi-chart"], canvas',
        fallback: '.fi-page-content > div:nth-child(2)',
        icon: '📈',
        title: 'Grafik Penjualan',
        body: 'Grafik interaktif menampilkan tren penjualan Anda per hari atau per jam. Gunakan untuk menganalisa waktu ramai dan sepi.',
        position: 'top'
    },
    {
        // Topbar / user menu
        selector: '.fi-topbar, [class*="fi-topbar"]',
        fallback: 'header',
        icon: '👤',
        title: 'Menu Akun & Pengaturan',
        body: 'Klik profil Anda di pojok kanan atas untuk mengelola akun, mengganti tema terang/gelap, atau logout dari dashboard.',
        position: 'bottom'
    },
    {
        // Sidebar items: Products, Orders, etc.
        selector: '.fi-sidebar-item:nth-child(2), .fi-sidebar-nav-groups > ul > li:nth-child(1)',
        fallback: '.fi-sidebar-nav',
        icon: '🛒',
        title: 'Kelola Produk & Pesanan',
        body: 'Tambahkan produk, atur kategori, kelola pesanan masuk, dan pantau stok dari menu-menu yang tersedia di sidebar.',
        position: 'right'
    },
    {
        // Done step — target the whole page content
        selector: '.fi-page-content, .fi-main',
        fallback: 'main',
        icon: '🎉',
        title: 'Anda Siap!',
        body: 'Semua fitur sudah tersedia untuk Anda. Mulai kelola bisnis Anda sekarang! Jika butuh bantuan, cek <b>API Documentation</b> untuk integrasi ke aplikasi mobile.',
        position: 'center'
    }
];

let currentStep = 0;

function startTour() {
    document.getElementById('tourWelcome').classList.remove('active');
    currentStep = 0;

    setTimeout(() => {
        document.getElementById('tourOverlay').classList.add('active');
        showStep(0);
    }, 400);
}

function dismissTour() {
    document.getElementById('tourWelcome').classList.remove('active');
    localStorage.setItem('kasirpos_tour_completed', 'true');
}

function showStep(index) {
    const step = tourSteps[index];
    const el = findElement(step.selector, step.fallback);

    const spotlight = document.getElementById('tourSpotlight');
    const tooltip = document.getElementById('tourTooltip');

    // Hide tooltip briefly for transition
    tooltip.classList.remove('visible');

    // First: scroll the target element into view if needed
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
    }

    // Wait for scroll to settle, then position everything
    setTimeout(() => {
        // Fill content first so tooltip has its real height
        document.getElementById('tourIcon').textContent = step.icon;
        document.getElementById('tourStep').textContent = `Langkah ${index + 1} dari ${tourSteps.length}`;
        document.getElementById('tourTitle').textContent = step.title;
        document.getElementById('tourBody').innerHTML = step.body;

        // Progress dots
        const progress = document.getElementById('tourProgress');
        progress.innerHTML = '';
        tourSteps.forEach((_, i) => {
            const dot = document.createElement('div');
            dot.className = 'tour-progress-dot';
            if (i === index) dot.classList.add('active');
            else if (i < index) dot.classList.add('done');
            progress.appendChild(dot);
        });

        // Button text
        const nextBtn = document.getElementById('tourNext');
        if (index === tourSteps.length - 1) {
            nextBtn.innerHTML = 'Selesai ✨';
        } else {
            nextBtn.innerHTML = 'Lanjut <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
        }

        if (el) {
            const rect = el.getBoundingClientRect();
            const pad = 8;
            const vw = window.innerWidth;
            const vh = window.innerHeight;

            // Limit spotlight height for tall elements (e.g. sidebar)
            const maxSpotH = Math.min(rect.height, vh * 0.6);
            const spotTop = Math.max(0, rect.top + (rect.height - maxSpotH) / 2 - pad);
            const spotLeft = Math.max(0, rect.left - pad);
            const spotW = Math.min(rect.width + pad * 2, vw - spotLeft);
            const spotH = maxSpotH + pad * 2;

            spotlight.style.display = 'block';
            spotlight.style.top = spotTop + 'px';
            spotlight.style.left = spotLeft + 'px';
            spotlight.style.width = spotW + 'px';
            spotlight.style.height = spotH + 'px';
            spotlight.classList.add('pulse');

            // Use the clamped spotlight rect for tooltip positioning
            const spotRect = { top: spotTop, left: spotLeft, right: spotLeft + spotW, bottom: spotTop + spotH, width: spotW, height: spotH };
            positionTooltip(tooltip, spotRect, step.position);
        } else {
            spotlight.style.display = 'none';
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
        }

        tooltip.style.display = 'block';
        requestAnimationFrame(() => {
            tooltip.classList.add('visible');
        });
    }, 500);
}

function positionTooltip(tooltip, rect, position) {
    const gap = 16;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const tw = Math.min(360, vw - 32);

    // Reset
    tooltip.style.transform = '';
    tooltip.style.maxHeight = '';
    tooltip.style.overflowY = '';

    // Temporarily show to measure real height
    tooltip.style.display = 'block';
    tooltip.style.width = tw + 'px';
    const th = tooltip.offsetHeight;

    let top, left;

    switch (position) {
        case 'right':
            top = rect.top;
            left = rect.right + gap;
            if (left + tw > vw) {
                // Doesn't fit right, put below instead
                top = rect.bottom + gap;
                left = Math.max(16, Math.min(rect.left, vw - tw - 16));
            }
            break;
        case 'bottom':
            top = rect.bottom + gap;
            left = Math.max(16, Math.min(rect.left, vw - tw - 16));
            break;
        case 'top':
            top = rect.top - th - gap;
            left = Math.max(16, Math.min(rect.left, vw - tw - 16));
            if (top < 16) {
                // Doesn't fit above, put below instead
                top = rect.bottom + gap;
            }
            break;
        case 'center':
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
            return;
        default:
            top = rect.bottom + gap;
            left = Math.max(16, Math.min(rect.left, vw - tw - 16));
    }

    // Clamp: make sure the tooltip doesn't go below the viewport
    if (top + th > vh - 16) {
        // Try to put it above the spotlight instead
        const aboveTop = rect.top - th - gap;
        if (aboveTop >= 16) {
            top = aboveTop;
        } else {
            // Last resort: clamp to bottom of viewport and enable scroll
            top = Math.max(16, vh - th - 16);
            if (th > vh - 32) {
                tooltip.style.maxHeight = (vh - 48) + 'px';
                tooltip.style.overflowY = 'auto';
                top = 24;
            }
        }
    }

    // Clamp: don't go above viewport
    if (top < 16) top = 16;

    // Clamp: don't go off-screen left/right
    left = Math.max(16, Math.min(left, vw - tw - 16));

    tooltip.style.top = top + 'px';
    tooltip.style.left = left + 'px';
}

function findElement(selector, fallback) {
    try {
        let el = document.querySelector(selector);
        if (el) return el;
        if (fallback) return document.querySelector(fallback);
    } catch (e) {
        if (fallback) {
            try { return document.querySelector(fallback); } catch (e2) { }
        }
    }
    return null;
}

function nextStep() {
    currentStep++;
    if (currentStep >= tourSteps.length) {
        endTour();
    } else {
        showStep(currentStep);
    }
}

function endTour() {
    const overlay = document.getElementById('tourOverlay');
    const spotlight = document.getElementById('tourSpotlight');
    const tooltip = document.getElementById('tourTooltip');

    tooltip.classList.remove('visible');
    spotlight.classList.remove('pulse');

    setTimeout(() => {
        overlay.classList.remove('active');
        spotlight.style.display = 'none';
        tooltip.style.display = 'none';
    }, 300);

    localStorage.setItem('kasirpos_tour_completed', 'true');
}
</script>
@endauth

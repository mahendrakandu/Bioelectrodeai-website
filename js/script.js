/**
 * BioElectrode AI — Main Frontend Script
 * Handles sidebar toggle, particles, form states, password toggle
 */

document.addEventListener('DOMContentLoaded', () => {
    initParticles();
    initSidebar();
    initPasswordToggles();
    initFormStates();
    initAnimations();
});

/* ═══ PARTICLES ═══ */
function initParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    const count = 20;
    const colors = [
        'rgba(37,99,235,0.4)',
        'rgba(124,58,237,0.3)',
        'rgba(5,150,105,0.3)',
        'rgba(219,39,119,0.25)',
    ];

    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = (1.5 + Math.random() * 3.5) + 'px';
        const color = colors[Math.floor(Math.random() * colors.length)];
        p.style.cssText = `
            width: ${size};
            height: ${size};
            left: ${Math.random() * 100}vw;
            background: ${color};
            animation-duration: ${10 + Math.random() * 18}s;
            animation-delay: ${-(Math.random() * 20)}s;
        `;
        container.appendChild(p);
    }
}

/* ═══ SIDEBAR TOGGLE ═══ */
function initSidebar() {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main-content');
    if (!toggle || !sidebar) return;

    // Restore state
    const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (collapsed) {
        sidebar.classList.add('collapsed');
        if (main) main.classList.add('expanded');
    }

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        if (main) main.classList.toggle('expanded');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    });
}

/* ═══ PASSWORD TOGGLE ═══ */
function initPasswordToggles() {
    document.querySelectorAll('.toggle-pass, .eye-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            this.textContent = isVisible ? '👁️' : '🙈';
        });
    });
}

/* ═══ FORM LOADING STATE ═══ */
function initFormStates() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            const btn = this.querySelector('button[type="submit"]');
            if (!btn) return;
            if (btn.classList.contains('btn-primary')) {
                btn.disabled = true;
                const spans = btn.querySelectorAll('span');
                if (spans.length) {
                    spans[0].textContent = btn.getAttribute('data-loading-text') || 'Processing...';
                    if (spans[1]) spans[1].textContent = '⏳';
                }
            }
        });
    });
}

/* ═══ SCROLL ANIMATIONS ═══ */
function initAnimations() {
    // Stagger cards on load
    const cards = document.querySelectorAll('.module-card, .action-card, .stat-card');
    cards.forEach((card, i) => {
        card.style.animationDelay = `${i * 0.06}s`;
        card.style.animation = `fadeInUp 0.45s ease both`;
    });

    // Progress bar fill animation
    const fills = document.querySelectorAll('.progress-fill');
    fills.forEach(fill => {
        const target = fill.style.width;
        fill.style.width = '0';
        setTimeout(() => {
            fill.style.width = target;
        }, 300);
    });
}

/* ═══ SIMPLE API HELPER ═══ */
async function apiCall(url, formData) {
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        return { success: false, message: 'Network error. Please try again.' };
    }
}

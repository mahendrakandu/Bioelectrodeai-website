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
            const input = this.parentElement.querySelector('input');
            if (!input) return;

            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';

            const eyeIcon = this.querySelector('svg');
            if (eyeIcon) {
                // Lucide: eye-off (if now visible) / eye (if now hidden)
                eyeIcon.innerHTML = !isVisible
                    ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
                    : '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
            } else {
                this.textContent = !isVisible ? '🙈' : '👁️';
            }
        });
    });
}

/* ═══ FORM LOADING STATE ═══ */
function initFormStates() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            // Only validate email format for register forms
            if (this.id === 'registerForm') {
                const emailInput = this.querySelector('input[type="email"]');
                if (emailInput) {
                    const email = emailInput.value.trim().toLowerCase();
                    if (email && !email.endsWith('@gmail.com')) {
                        e.preventDefault();
                        
                        // Specific handling for BioElectrode AI gmail requirement
                        showFormError(this, 'Email must be in the format: <strong>name@gmail.com</strong>');
                        
                        // Suggest correction if common typo
                        if (email.includes('@gmail.')) {
                            const domain = email.split('@')[1];
                            if (domain !== 'gmail.com') {
                                const corrected = email.split('@')[0] + '@gmail.com';
                                const alert = this.parentElement.querySelector('.alert-error');
                                if (alert) {
                                    alert.innerHTML += `<br><small style="margin-top:5px; display:block; opacity:0.9;">Did you mean <a href="#" class="suggest-correct" style="color:#fff; text-decoration:underline;" data-corrected="${corrected}">${corrected}</a>?</small>`;
                                    alert.querySelector('.suggest-correct').addEventListener('click', function(ev) {
                                        ev.preventDefault();
                                        emailInput.value = this.getAttribute('data-corrected');
                                        alert.remove();
                                    });
                                }
                            }
                        }
                        return;
                    }
                }
            }

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

function showFormError(form, message) {
    // Remove existing
    const existing = form.parentElement.querySelector('.alert-error');
    if (existing) existing.remove();

    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        ${message}
    `;
    
    const container = form.parentElement;
    const h2 = container.querySelector('h2');
    if (h2) {
        h2.insertAdjacentElement('afterend', alert);
    } else {
        container.prepend(alert);
    }
    
    // Smooth scroll to error
    alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

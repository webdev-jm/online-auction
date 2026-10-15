import '@fontsource-variable/manrope';

/**
 * Toasts. Livewire components call $this->dispatch('toast', type: 'success', message: '...'),
 * and session flashes (message_success / message_error) are read from #oa-flash on load.
 */
const toastIcons = {
    success: 'fas fa-check-circle',
    error: 'fas fa-exclamation-circle',
    info: 'fas fa-info-circle',
};

function toastStack() {
    let stack = document.querySelector('.oa-toasts');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'oa-toasts';
        stack.setAttribute('role', 'status');
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }
    return stack;
}

export function toast(type, message) {
    if (!message) {
        return;
    }

    const kind = toastIcons[type] ? type : 'info';
    const item = document.createElement('div');
    item.className = `oa-toast is-${kind}`;

    const icon = document.createElement('i');
    icon.className = toastIcons[kind];
    icon.setAttribute('aria-hidden', 'true');

    const text = document.createElement('p');
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss');
    close.innerHTML = '&times;';
    close.addEventListener('click', () => item.remove());

    item.append(icon, text, close);
    toastStack().appendChild(item);

    setTimeout(() => item.remove(), kind === 'error' ? 9000 : 5000);
}

window.oaToast = toast;

window.addEventListener('toast', (event) => {
    const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
    toast(detail?.type ?? 'success', detail?.message ?? '');
});

function showFlash() {
    const flash = document.getElementById('oa-flash');
    if (!flash) {
        return;
    }
    toast('success', flash.dataset.success);
    toast('error', flash.dataset.error);
    flash.remove();
}

/**
 * Countdowns: <span data-countdown="2026-10-15T17:00:00+08:00" data-countdown-done="Closed">.
 * One timer updates every countdown on the page, including ones Livewire re-renders.
 */
function formatRemaining(ms) {
    const total = Math.max(0, Math.floor(ms / 1000));
    const days = Math.floor(total / 86400);
    const hours = Math.floor((total % 86400) / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    const pad = (value) => String(value).padStart(2, '0');

    if (days > 0) {
        return `${days}d ${pad(hours)}h ${pad(minutes)}m`;
    }

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
}

function tickCountdowns() {
    const now = Date.now();

    document.querySelectorAll('[data-countdown]').forEach((element) => {
        const target = Date.parse(element.dataset.countdown);
        if (Number.isNaN(target)) {
            return;
        }

        const remaining = target - now;
        if (remaining <= 0) {
            element.textContent = element.dataset.countdownDone ?? '00:00:00';
            element.classList.remove('is-urgent');
            return;
        }

        element.textContent = formatRemaining(remaining);
        const urgentBelow = Number(element.dataset.countdownUrgent ?? 3600) * 1000;
        element.classList.toggle('is-urgent', remaining <= urgentBelow);
    });
}

/**
 * Photo gallery thumbnails and the side panel used for auction mechanics.
 */
document.addEventListener('click', (event) => {
    const thumb = event.target.closest('[data-gallery-thumb]');
    if (thumb) {
        const gallery = thumb.closest('.oa-gallery');
        const main = gallery?.querySelector('.oa-gallery-main img');
        if (main) {
            main.src = thumb.dataset.galleryThumb;
            main.alt = thumb.dataset.galleryAlt ?? main.alt;
        }
        gallery?.querySelectorAll('[data-gallery-thumb]').forEach((button) => {
            button.classList.toggle('is-active', button === thumb);
        });
        return;
    }

    const opener = event.target.closest('[data-drawer-open]');
    if (opener) {
        event.preventDefault();
        const drawer = document.getElementById(opener.dataset.drawerOpen);
        if (drawer) {
            drawer.hidden = false;
            drawer.querySelector('[data-drawer-close]')?.focus();
        }
        return;
    }

    const closer = event.target.closest('[data-drawer-close]');
    if (closer || event.target.classList.contains('oa-drawer')) {
        const drawer = (closer ?? event.target).closest('.oa-drawer');
        if (drawer) {
            drawer.hidden = true;
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.oa-drawer:not([hidden])').forEach((drawer) => {
            drawer.hidden = true;
        });
    }
});

function boot() {
    showFlash();
    tickCountdowns();
    setInterval(tickCountdowns, 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

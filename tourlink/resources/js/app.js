document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');

    if (!(toggle instanceof HTMLButtonElement)) {
        return;
    }

    const input = document.getElementById(toggle.dataset.passwordToggle);

    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    toggle.textContent = reveal ? 'Hide' : 'Show';
    toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    toggle.setAttribute('title', reveal ? 'Hide password' : 'Show password');
});

document.querySelectorAll('[data-otp-form]').forEach((form) => {
    const digits = [...form.querySelectorAll('[data-otp-digit]')];
    const value = form.querySelector('[data-otp-value]');
    const submit = form.querySelector('button[type="submit"]');

    const syncValue = () => {
        if (value) {
            value.value = digits.map((digit) => digit.value).join('');
        }
    };

    digits.forEach((digit, index) => {
        digit.addEventListener('input', () => {
            digit.value = digit.value.replace(/\D/g, '').slice(-1);
            syncValue();
            if (digit.value && digits[index + 1]) {
                digits[index + 1].focus();
            }
        });

        digit.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !digit.value && digits[index - 1]) {
                digits[index - 1].focus();
            }
            if (event.key === 'ArrowLeft' && digits[index - 1]) {
                digits[index - 1].focus();
            }
            if (event.key === 'ArrowRight' && digits[index + 1]) {
                digits[index + 1].focus();
            }
        });

        digit.addEventListener('paste', (event) => {
            const pasted = event.clipboardData?.getData('text').replace(/\D/g, '').slice(0, 6) ?? '';
            if (!pasted) {
                return;
            }

            event.preventDefault();
            pasted.split('').forEach((character, pastedIndex) => {
                if (digits[pastedIndex]) {
                    digits[pastedIndex].value = character;
                }
            });
            syncValue();
            (digits[Math.min(pasted.length, digits.length) - 1] ?? digit).focus();
        });
    });

    form.addEventListener('submit', (event) => {
        syncValue();
        if ((value?.value.length ?? 0) !== 6) {
            event.preventDefault();
            digits[0]?.focus();
            return;
        }
        submit?.classList.add('is-loading');
        if (submit) {
            submit.disabled = true;
        }
    });
});

document.querySelectorAll('[data-resend-form]').forEach((form) => {
    const button = form.querySelector('[data-resend-button]');
    const countdown = form.querySelector('[data-resend-countdown]');
    let remaining = Number(form.dataset.resendSeconds ?? 60);

    const tick = () => {
        if (!button || !countdown) {
            return;
        }
        button.disabled = remaining > 0;
        countdown.textContent = remaining > 0 ? `(${remaining}s)` : '';
        if (remaining > 0) {
            remaining -= 1;
            window.setTimeout(tick, 1000);
        }
    };

    tick();
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 *
 * Loaded as a dynamic import so a broadcasting problem can never prevent the
 * listeners above from being registered.
 */

import('./echo').catch(() => {});

if ('serviceWorker' in navigator && window.isSecureContext) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}

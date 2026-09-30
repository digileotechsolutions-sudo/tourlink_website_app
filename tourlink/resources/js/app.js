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

document.querySelectorAll('[data-password-strength]').forEach((indicator) => {
    const input = document.querySelector('[data-strength-input]');
    const label = indicator.querySelector('[data-strength-label]');
    const meter = indicator.querySelector('[data-strength-meter]');
    const bar = indicator.querySelector('[data-strength-bar]');

    if (!(input instanceof HTMLInputElement) || !label || !meter || !bar) {
        return;
    }

    const updateStrength = () => {
        const value = input.value;
        const length = [...value].length;
        const checks = [
            length >= 8,
            /[A-Z]/.test(value),
            /[a-z]/.test(value),
            /\d/.test(value),
            /[^A-Za-z0-9\s]/.test(value),
        ];
        const score = checks.filter(Boolean).length;
        const strength = score < 3 ? 'Weak' : score < 5 || length < 12 ? 'Fair' : 'Strong';
        const progress = strength === 'Strong' ? 100 : strength === 'Fair' ? 66 : 33;

        indicator.dataset.strength = strength.toLowerCase();
        label.textContent = strength;
        meter.setAttribute('aria-valuenow', String(progress));
        bar.style.width = `${progress}%`;
    };

    input.addEventListener('input', updateStrength);
    updateStrength();
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

/**
 * Referral sharing: copy buttons plus the Web Share API where it exists.
 * Falls back to the clipboard so desktop browsers still work.
 */

const copyWithFallback = (value) => {
    if (navigator.clipboard?.writeText) {
        return navigator.clipboard.writeText(value);
    }

    const field = document.createElement('textarea');

    field.value = value;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.appendChild(field);
    field.select();
    document.execCommand('copy');
    document.body.removeChild(field);

    return Promise.resolve();
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const original = button.textContent;
    const label = button.dataset.copiedLabel ?? 'Copied';

    copyWithFallback(button.dataset.copy)
        .then(() => {
            button.textContent = label;
            window.setTimeout(() => {
                button.textContent = original;
            }, 2000);
        })
        .catch(() => {
            button.textContent = 'Copy failed';
            window.setTimeout(() => {
                button.textContent = original;
            }, 2000);
        });
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-share]');

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const link = document.querySelector('[data-referral-link]')?.textContent?.trim() ?? window.location.href;
    const status = document.querySelector('[data-share-status]');

    if (navigator.share) {
        navigator.share({
            title: button.dataset.shareTitle ?? 'TourLink',
            text: button.dataset.shareText ?? '',
            url: link,
        }).catch(() => {});
    } else if (status) {
        copyWithFallback(link)
            .then(() => {
                status.textContent = 'Sharing is not supported here, so the link was copied instead.';
            })
            .catch(() => {
                status.textContent = 'Copy the referral link above to share it.';
            });
    }
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

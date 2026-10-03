const prefetchedAppRoutes = new Set();

if (document.body.classList.contains('admin-app')) {
    document.querySelectorAll('table:not([data-mobile-table="exclude"])').forEach((table) => {
        const headers = [...table.querySelectorAll('thead th')].map((header) => header.textContent.trim());

        if (headers.length === 0) {
            return;
        }

        table.classList.add('admin-mobile-data-table');
        table.querySelectorAll('tbody tr').forEach((row) => {
            [...row.cells].forEach((cell, index) => {
                if (!cell.hasAttribute('colspan') && headers[index]) {
                    cell.dataset.label = headers[index];
                }
            });
        });
    });
}

const portalSidebar = document.querySelector('[data-portal-sidebar]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const portalDrawer = document.querySelector('[data-portal-drawer]');
const drawerOpenButton = document.querySelector('[data-drawer-open]');
const profileReminder = document.querySelector('[data-profile-reminder]');
const printTermsButton = document.querySelector('[data-print-terms]');
const signInPrompt = document.querySelector('[data-sign-in-prompt]');

printTermsButton?.addEventListener('click', () => window.print());

if (signInPrompt && 'showModal' in signInPrompt) {
    const storageKey = 'tourlink:sign-in-prompt-shown';
    let hasBeenShown = false;

    try {
        hasBeenShown = sessionStorage.getItem(storageKey) === 'true';
    } catch {}

    if (!hasBeenShown) {
        window.setTimeout(() => {
            if (signInPrompt.open) {
                return;
            }

            signInPrompt.showModal();
            try {
                sessionStorage.setItem(storageKey, 'true');
            } catch {}
        }, 700);
    }

    signInPrompt.querySelector('[data-sign-in-prompt-close]')?.addEventListener('click', () => {
        signInPrompt.close();
    });
    signInPrompt.addEventListener('click', (event) => {
        if (event.target === signInPrompt) {
            signInPrompt.close();
        }
    });
}

const howSections = document.querySelectorAll('.how-page .how-section');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (howSections.length > 0 && !prefersReducedMotion && 'IntersectionObserver' in window) {
    howSections.forEach((section) => section.classList.add('how-reveal'));
    document.documentElement.classList.add('has-how-reveal');
    const howObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    howSections.forEach((section) => howObserver.observe(section));
}

if (profileReminder) {
    const reminderKey = `tourlink:profile-reminder-dismissed:${profileReminder.dataset.userId}`;

    try {
        profileReminder.hidden = sessionStorage.getItem(reminderKey) === 'true';
    } catch {}

    profileReminder.querySelector('[data-profile-reminder-dismiss]')?.addEventListener('click', () => {
        profileReminder.hidden = true;
        try {
            sessionStorage.setItem(reminderKey, 'true');
        } catch {}
    });

    document.querySelectorAll('form[action$="/logout"]').forEach((form) => {
        form.addEventListener('submit', () => {
            try {
                sessionStorage.removeItem(reminderKey);
            } catch {}
        });
    });
}

document.querySelectorAll('[data-provider-role-fields]').forEach((form) => {
    const roleSelect = form.querySelector('select[name="role"]');
    const businessFields = form.querySelector('[data-provider-business-fields]');
    const businessName = businessFields?.querySelector('[name="business_name"]');

    if (!roleSelect || !businessFields || !businessName) {
        return;
    }

    const updateBusinessFields = () => {
        const isProvider = ['OPERATOR', 'VEHICLE_OWNER'].includes(roleSelect.value);
        businessFields.hidden = !isProvider;
        businessName.required = isProvider;
    };

    updateBusinessFields();
    roleSelect.addEventListener('change', updateBusinessFields);
});

if (portalSidebar && sidebarToggle) {
    const storageKey = 'tourlink:sidebar-collapsed';
    let isCollapsed = false;

    try {
        isCollapsed = localStorage.getItem(storageKey) === 'true';
    } catch {}

    const setSidebarCollapsed = (collapsed) => {
        portalSidebar.classList.toggle('is-collapsed', collapsed);
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        sidebarToggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    };

    setSidebarCollapsed(isCollapsed);
    sidebarToggle.addEventListener('click', () => {
        isCollapsed = !portalSidebar.classList.contains('is-collapsed');
        setSidebarCollapsed(isCollapsed);
        try {
            localStorage.setItem(storageKey, String(isCollapsed));
        } catch {}
    });
}

if (portalDrawer && drawerOpenButton && 'showModal' in portalDrawer) {
    drawerOpenButton.addEventListener('click', () => {
        portalDrawer.showModal();
        drawerOpenButton.setAttribute('aria-expanded', 'true');
    });
    portalDrawer.addEventListener('close', () => drawerOpenButton.setAttribute('aria-expanded', 'false'));
    portalDrawer.addEventListener('click', (event) => {
        if (event.target === portalDrawer) {
            portalDrawer.close();
        }
    });
}

const prefetchAppRoute = (link) => {
    const destination = new URL(link.href, location.href);
    const connection = navigator.connection ?? navigator.mozConnection ?? navigator.webkitConnection;

    if (destination.origin !== location.origin
        || destination.href === location.href
        || prefetchedAppRoutes.has(destination.href)
        || connection?.saveData
        || ['slow-2g', '2g'].includes(connection?.effectiveType)) {
        return;
    }

    prefetchedAppRoutes.add(destination.href);
    const prefetch = document.createElement('link');
    prefetch.rel = 'prefetch';
    prefetch.as = 'document';
    prefetch.href = destination.href;
    prefetch.fetchPriority = 'low';
    document.head.append(prefetch);
};

document.addEventListener('pointerover', (event) => {
    if (event.pointerType === 'touch') {
        return;
    }

    const link = event.target.closest('.mobile-bottom-nav a, .mobile-app-menu a');
    if (link instanceof HTMLAnchorElement) {
        prefetchAppRoute(link);
    }
});

document.addEventListener('focusin', (event) => {
    const link = event.target.closest('.mobile-bottom-nav a, .mobile-app-menu a');
    if (link instanceof HTMLAnchorElement) {
        prefetchAppRoute(link);
    }
});

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
    const label = toggle.querySelector('[data-password-toggle-label]');
    const eye = toggle.querySelector('[data-password-eye]');
    const eyeOff = toggle.querySelector('[data-password-eye-off]');
    const nextLabel = reveal ? 'Hide password' : 'Show password';

    if (label) {
        label.textContent = nextLabel;
        if (eye && eyeOff) {
            eye.hidden = reveal;
            eyeOff.hidden = !reveal;
        }
    } else {
        toggle.textContent = reveal ? 'Hide' : 'Show';
    }

    toggle.setAttribute('aria-label', nextLabel);
    toggle.setAttribute('title', nextLabel);
});

document.querySelectorAll('[data-auth-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-auth-submit]');
        const label = button?.querySelector('[data-auth-submit-label]');

        if (!button) {
            return;
        }

        button.disabled = true;
        button.classList.add('is-loading');
        button.setAttribute('aria-busy', 'true');
        if (label) {
            label.textContent = form.dataset.authLoadingLabel ?? 'Please wait...';
        }
    });
});

document.querySelectorAll('img[loading="lazy"]').forEach((image) => {
    if (image.complete) {
        return;
    }

    image.classList.add('image-skeleton');
    const clearSkeleton = () => image.classList.remove('image-skeleton');
    image.addEventListener('load', clearSkeleton, { once: true });
    image.addEventListener('error', clearSkeleton, { once: true });
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)
        || event.defaultPrevented
        || form.target === '_blank'
        || form.matches('[data-auth-form], [data-otp-form], [data-offline-favorite], [data-no-loading]')) {
        return;
    }

    const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"], button:not([type])');

    if (!(button instanceof HTMLButtonElement) || button.disabled) {
        return;
    }

    button.disabled = true;
    button.classList.add('app-submit-loading');
    button.setAttribute('aria-busy', 'true');
}, true);

const draftToClear = document.querySelector('[data-form-draft-clear]')?.dataset.formDraftClear;
if (draftToClear) {
    try {
        sessionStorage.removeItem(`tourlink-form-draft:${draftToClear}`);
    } catch {}
}

document.querySelectorAll('[data-form-draft]').forEach((form) => {
    const draftKey = `tourlink-form-draft:${form.dataset.formDraft}`;
    const fields = [...form.querySelectorAll('[data-draft-field]')];

    try {
        const draft = JSON.parse(sessionStorage.getItem(draftKey) ?? '{}');
        fields.forEach((field) => {
            if (!field.value && typeof draft[field.name] === 'string') {
                field.value = draft[field.name];
            }
        });
    } catch {}

    form.querySelector('select[name="role"]')?.dispatchEvent(new Event('change', { bubbles: true }));

    const saveDraft = () => {
        try {
            const draft = Object.fromEntries(fields.map((field) => [field.name, field.value]));
            sessionStorage.setItem(draftKey, JSON.stringify(draft));
        } catch {}
    };

    fields.forEach((field) => {
        field.addEventListener('input', saveDraft);
        field.addEventListener('change', saveDraft);
    });
});

document.querySelectorAll('[data-google-auth]').forEach((container) => {
    const clientId = container.dataset.googleClientId;
    const nonce = container.dataset.googleNonce;
    const buttonContainer = container.querySelector('[data-google-button]');
    const credentialForm = container.querySelector('[data-google-credential-form]');
    const credentialInput = credentialForm?.querySelector('input[name="credential"]');
    const errorMessage = container.querySelector('[data-google-auth-error]');

    const showError = (message) => {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.hidden = false;
        }
    };

    const initializeGoogleButton = () => {
        if (!clientId || !nonce || !buttonContainer || !credentialForm || !credentialInput
            || !window.google?.accounts?.id) {
            showError('Google sign-in is unavailable. Please use email and password.');

            return;
        }

        window.google.accounts.id.initialize({
            client_id: clientId,
            nonce,
            auto_select: false,
            callback: (response) => {
                if (!response.credential) {
                    showError('Google could not verify this sign-in. Please try again.');

                    return;
                }

                credentialInput.value = response.credential;
                const roleInput = credentialForm.querySelector('input[name="role"]');
                const selectedRole = document.querySelector('#register-form [name="role"]');
                if (roleInput && selectedRole) {
                    roleInput.value = selectedRole.value;
                }
                container.classList.add('is-processing');
                container.setAttribute('aria-busy', 'true');
                const status = container.querySelector('[data-google-auth-status]');
                if (status) {
                    status.hidden = false;
                    status.textContent = 'Verifying with Google...';
                }
                credentialForm.submit();
            },
        });

        window.google.accounts.id.renderButton(buttonContainer, {
            type: 'standard',
            theme: 'outline',
            size: 'large',
            text: 'continue_with',
            shape: 'rect',
            width: Math.min(400, Math.max(220, Math.floor(container.clientWidth))),
        });
    };

    const googleScript = document.querySelector('script[data-google-identity]') ?? document.createElement('script');
    if (!googleScript.src) {
        googleScript.src = 'https://accounts.google.com/gsi/client';
        googleScript.async = true;
        googleScript.defer = true;
        googleScript.dataset.googleIdentity = 'true';
        googleScript.addEventListener('load', initializeGoogleButton, { once: true });
        googleScript.addEventListener('error', () => {
            showError('Google sign-in could not load. Check your connection or use email and password.');
        }, { once: true });
        document.head.append(googleScript);
    } else if (window.google?.accounts?.id) {
        initializeGoogleButton();
    } else {
        googleScript.addEventListener('load', initializeGoogleButton, { once: true });
        googleScript.addEventListener('error', () => {
            showError('Google sign-in could not load. Check your connection or use email and password.');
        }, { once: true });
    }
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
            const enteredDigits = digit.value.replace(/\D/g, '');
            if (enteredDigits.length > 1) {
                enteredDigits.slice(0, digits.length - index).split('').forEach((character, enteredIndex) => {
                    digits[index + enteredIndex].value = character;
                });
                syncValue();
                (digits[Math.min(index + enteredDigits.length, digits.length) - 1] ?? digit).focus();
                return;
            }
            digit.value = enteredDigits.slice(-1);
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

    const countdown = form.querySelector('[data-otp-expires-at]');
    const expiresAt = Number(countdown?.dataset.otpExpiresAt) * 1000;

    if (countdown && Number.isFinite(expiresAt) && expiresAt > 0) {
        const tick = () => {
            const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
            const minutes = Math.floor(remaining / 60);
            const seconds = String(remaining % 60).padStart(2, '0');
            countdown.textContent = remaining > 0
                ? `This code expires in ${minutes}:${seconds}.`
                : 'This code has expired. Request a new code to continue.';

            if (remaining > 0) {
                window.setTimeout(tick, 1000);
            }
        };

        tick();
    }
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
            title: button.dataset.shareTitle ?? 'Havenedge Tourlink',
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

const pwaStatus = document.querySelector('[data-pwa-status]');
const pwaMessage = pwaStatus?.querySelector('[data-pwa-message]');
const installButton = pwaStatus?.querySelector('[data-pwa-install]');
const updateButton = pwaStatus?.querySelector('[data-pwa-update]');
const onlineOnlyNotice = document.querySelector('[data-online-only-notice]');
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
const isAppleMobile = /iPhone|iPad|iPod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
let installPrompt = null;
let pwaRegistration = null;

const setPwaStatus = (message, state) => {
    if (!pwaStatus || !pwaMessage) {
        return;
    }

    pwaMessage.textContent = message;
    pwaStatus.dataset.state = state;
    const hasVisibleAction = [...pwaStatus.querySelectorAll('button')].some((button) => !button.hidden);
    pwaStatus.classList.toggle('pwa-status--install-only', message === '' && !installButton?.hidden && updateButton?.hidden);
    pwaStatus.hidden = message === '' && !hasVisibleAction;
};

if (installButton && !isStandalone && isAppleMobile) {
    installButton.hidden = false;
}

const database = () => new Promise((resolve, reject) => {
    const request = indexedDB.open('tourlink-offline', 1);

    request.onupgradeneeded = () => {
        if (!request.result.objectStoreNames.contains('actions')) {
            request.result.createObjectStore('actions', { keyPath: 'id' });
        }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});

const queueFavorite = async (form) => {
    const userId = form.dataset.userId;
    const tripId = form.dataset.tripId;

    if (!userId || !tripId) {
        return false;
    }

    const store = await database();
    const id = `favorite-${userId}-${tripId}`;

    await new Promise((resolve, reject) => {
        const transaction = store.transaction('actions', 'readwrite');
        transaction.objectStore('actions').put({
            id,
            type: 'favorite-add',
            userId,
            tripId,
            url: form.action,
            createdAt: Date.now(),
        });
        transaction.oncomplete = resolve;
        transaction.onerror = () => reject(transaction.error);
    });

    const button = form.querySelector('button[type="submit"]');
    if (button) {
        button.textContent = 'Pending synchronization';
        button.disabled = true;
    }
    setPwaStatus('Pending synchronization', 'pending');

    if ('serviceWorker' in navigator) {
        const registration = pwaRegistration ?? await navigator.serviceWorker.ready.catch(() => null);
        if (registration?.sync) {
            await registration.sync.register('tourlink-outbox').catch(() => {});
        }
    }

    return true;
};

window.addEventListener('offline', () => {
    setPwaStatus('You are offline. Some features may be unavailable.', 'offline');
    if (onlineOnlyNotice) {
        onlineOnlyNotice.hidden = false;
    }
});

window.addEventListener('online', () => {
    setPwaStatus('Connection restored. Synchronizing...', 'syncing');
    if (onlineOnlyNotice) {
        onlineOnlyNotice.hidden = true;
    }

    navigator.serviceWorker?.ready.then((registration) => {
        const worker = navigator.serviceWorker.controller ?? registration.active;
        worker?.postMessage({ type: 'SYNC_OUTBOX' });
        if (!worker) {
            setPwaStatus('', 'online');
        }
        if (registration.sync) {
            registration.sync.register('tourlink-outbox').catch(() => {});
        }
    }).catch(() => setPwaStatus('', 'online'));
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.matches('[data-offline-favorite]')) {
        event.preventDefault();

        if (navigator.onLine) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    body: new FormData(form),
                });

                if (response.ok) {
                    if (button) {
                        button.textContent = 'Saved';
                    }
                    setPwaStatus('', 'online');
                    return;
                }

                if (response.status < 500) {
                    if (button) {
                        button.disabled = false;
                    }
                    setPwaStatus('This trip could not be saved. Please refresh and try again.', 'online');
                    return;
                }
            } catch {
                // The server endpoint is idempotent, so a retry is safe if the response was lost.
            }
        }

        try {
            if (await queueFavorite(form)) {
                return;
            }
        } catch {
            setPwaStatus('Could not save this action for synchronization.', 'offline');
            return;
        }
    }

    if (navigator.onLine || form.method.toLowerCase() === 'get' || form.matches('[data-auth-form]')) {
        return;
    }

    event.preventDefault();
    const path = new URL(form.action, location.href).pathname;
    const message = path.includes('/verify')
        ? 'Email OTP verification requires an internet connection. Please reconnect and retry.'
        : path.includes('/payment') || path.includes('/mpesa')
            ? 'You are currently offline. Please reconnect to the internet before making a payment.'
            : 'You are offline. This action requires an internet connection.';

    setPwaStatus(message, 'offline');
    if (onlineOnlyNotice) {
        onlineOnlyNotice.textContent = message;
        onlineOnlyNotice.hidden = false;
    }
});

window.addEventListener('tourlink:offline-payment', () => {
    setPwaStatus('You are currently offline. Please reconnect to the internet before making a payment.', 'offline');
});

const nativeFetch = window.fetch.bind(window);
window.fetch = (input, options) => {
    const requestUrl = typeof input === 'string' ? input : input.url;
    const paymentRequest = new URL(requestUrl, location.href).pathname === '/api/payments/mpesa/stk';
    const paymentMessage = 'You are currently offline. Please reconnect to the internet before making a payment.';

    if (paymentRequest && !navigator.onLine) {
        setPwaStatus(paymentMessage, 'offline');
        return Promise.reject(new Error(paymentMessage));
    }

    return nativeFetch(input, options).catch((error) => {
        if (paymentRequest && error instanceof TypeError) {
            setPwaStatus(paymentMessage, 'offline');
            throw new Error(paymentMessage);
        }
        throw error;
    });
};

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    if (installButton) {
        installButton.hidden = false;
        const label = installButton.querySelector('span');
        if (label) {
            label.textContent = 'Install App';
        }
        installButton.setAttribute('aria-label', 'Install App');
        installButton.setAttribute('title', 'Install App');
    }
    setPwaStatus('', 'install');
});

installButton?.addEventListener('click', async () => {
    if (!installPrompt) {
        const instructions = isAppleMobile
            ? 'To install Havenedge Tourlink, tap Share, then Add to Home Screen.'
            : 'Open your browser menu and choose Install app or Add to Home screen.';

        installButton.hidden = true;
        setPwaStatus(instructions, 'install');

        return;
    }

    const prompt = installPrompt;
    installPrompt = null;
    installButton.disabled = true;

    try {
        await prompt.prompt();
        const choice = await prompt.userChoice;

        if (choice?.outcome === 'accepted') {
            installButton.hidden = true;
            setPwaStatus('Installing Havenedge Tourlink...', 'install');
        } else {
            setPwaStatus('Installation was cancelled. You can install later from your browser menu.', 'install');
        }
    } catch {
        setPwaStatus('The install prompt could not open. Try installing from your browser menu.', 'install');
    } finally {
        installButton.disabled = false;
    }
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    if (installButton) {
        installButton.hidden = true;
    }
    setPwaStatus('Havenedge Tourlink installed', 'online');
});

if ('serviceWorker' in navigator && window.isSecureContext) {
    let hadController = Boolean(navigator.serviceWorker.controller);

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (hadController) {
            location.reload();
        }
        hadController = true;
    });

    navigator.serviceWorker.addEventListener('message', ({ data }) => {
        if (data?.type === 'OUTBOX_SYNCED') {
            setPwaStatus(data.syncedCount > 0 ? 'Pending actions synchronized.' : '', 'online');
        } else if (data?.type === 'OUTBOX_PENDING') {
            setPwaStatus('Some actions are waiting to sync.', 'pending');
        }
    });

    navigator.serviceWorker.register('/sw.js').then((registration) => {
        pwaRegistration = registration;
        const revealUpdate = () => {
            if (updateButton) {
                updateButton.hidden = false;
            }
            setPwaStatus('A new Havenedge Tourlink version is ready.', 'update');
        };

        if (registration.waiting) {
            revealUpdate();
        }

        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;
            worker?.addEventListener('statechange', () => {
                if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                    revealUpdate();
                }
            });
        });
    }).catch(() => {});
}

updateButton?.addEventListener('click', () => {
    pwaRegistration?.waiting?.postMessage({ type: 'SKIP_WAITING' });
    updateButton.disabled = true;
    setPwaStatus('Updating Havenedge Tourlink...', 'update');
});

if (!navigator.onLine) {
    setPwaStatus('You are offline. Some features may be unavailable.', 'offline');
    if (onlineOnlyNotice) {
        onlineOnlyNotice.hidden = false;
    }
}

if ('serviceWorker' in navigator && window.isSecureContext) {
    setPwaStatus(navigator.onLine ? '' : 'You are offline. Some features may be unavailable.', navigator.onLine ? 'online' : 'offline');
}

document.querySelectorAll('[data-support-chat]').forEach((form) => {
    const messageList = document.querySelector('[data-support-messages]');
    const errorMessage = form.querySelector('[data-support-error]');
    const currentSender = form.dataset.currentSender;
    let lastMessageId = form.dataset.lastMessageId || '';
    let isPolling = false;

    if (!messageList || !currentSender || !form.dataset.pollUrl) {
        return;
    }

    const formatMessageTime = (value) => {
        const date = new Date(value);
        return Number.isNaN(date.getTime())
            ? ''
            : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
    };

    messageList.scrollTop = messageList.scrollHeight;

    const appendMessage = (message) => {
        if (messageList.querySelector(`[data-message-id="${CSS.escape(message.id)}"]`)) {
            return;
        }

        messageList.querySelector('.support-welcome, .support-empty-note')?.remove();

        const isOwnMessage = message.sender_type === currentSender;
        const article = document.createElement('article');
        article.className = `support-message ${isOwnMessage ? 'support-message--customer' : 'support-message--admin'}`;
        article.dataset.supportMessage = '';
        article.dataset.messageId = message.id;

        if (currentSender === 'admin') {
            const author = document.createElement('p');
            author.className = 'support-message__author';
            author.textContent = isOwnMessage ? 'You' : message.sender_name;
            article.append(author);
        }

        const text = document.createElement('p');
        text.className = 'support-message__text';
        text.textContent = message.message;
        article.append(text);

        const meta = document.createElement('div');
        meta.className = 'support-message__meta';
        const time = document.createElement('time');
        time.dateTime = message.created_at;
        time.textContent = formatMessageTime(message.created_at);
        meta.append(time);

        if (isOwnMessage) {
            const readStatus = document.createElement('span');
            readStatus.dataset.readStatus = '';
            readStatus.textContent = message.read_at ? 'Read' : 'Sent';
            meta.append(readStatus);
        }

        article.append(meta);
        messageList.append(article);
        lastMessageId = message.id;
        form.dataset.lastMessageId = lastMessageId;
        messageList.scrollTop = messageList.scrollHeight;
    };

    const refreshMessages = async () => {
        if (isPolling || document.visibilityState !== 'visible') {
            return;
        }

        isPolling = true;
        try {
            const pollUrl = new URL(form.dataset.pollUrl, window.location.href);
            if (lastMessageId) {
                pollUrl.searchParams.set('after', lastMessageId);
            }
            const response = await fetch(pollUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) {
                throw new Error(`Support message refresh failed: ${response.status}`);
            }

            const result = await response.json();
            (result.read_message_ids || []).forEach((messageId) => {
                const status = messageList.querySelector(`[data-message-id="${CSS.escape(messageId)}"] [data-read-status]`);
                if (status) {
                    status.textContent = 'Read';
                }
            });
            (result.messages || []).forEach(appendMessage);
            if (errorMessage) {
                errorMessage.hidden = true;
            }
            if (result.status === 'closed') {
                window.location.reload();
            }
        } catch {
            if (errorMessage) {
                errorMessage.hidden = false;
            }
        } finally {
            isPolling = false;
        }
    };

    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && event.target.matches('input[name="message"]')) {
            event.preventDefault();
            if (form.reportValidity()) {
                form.requestSubmit();
            }
        }
    });

    window.setInterval(refreshMessages, 5000);
});

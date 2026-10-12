const prefetchedAppRoutes = new Set();

const idleLogoutPage = document.body.hasAttribute('data-idle-logout');

if (idleLogoutPage) {
    const timeoutMs = Number(document.body.dataset.idleTimeoutMs);
    const activityKey = `tourlink:last-activity:${document.body.dataset.idleUser}`;
    let lastActivityAt = Date.now();
    let lastPersistedAt = 0;
    let timeoutHandle;

    const readLastActivity = () => {
        try {
            const storedActivity = Number(localStorage.getItem(activityKey));
            if (Number.isFinite(storedActivity)) {
                lastActivityAt = Math.max(lastActivityAt, storedActivity);
            }
        } catch {
            // In-memory activity tracking remains available when browser storage is disabled.
        }
    };

    const scheduleLogout = () => {
        window.clearTimeout(timeoutHandle);
        const remainingMs = timeoutMs - (Date.now() - lastActivityAt);
        if (remainingMs > 0) {
            timeoutHandle = window.setTimeout(scheduleLogout, remainingMs);
            return;
        }

        readLastActivity();
        if (Date.now() - lastActivityAt < timeoutMs) {
            scheduleLogout();
            return;
        }

        const controller = new AbortController();
        const requestTimeout = window.setTimeout(() => controller.abort(), 5000);

        window.fetch(document.body.dataset.logoutUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'text/html' },
            body: new URLSearchParams({ _token: document.body.dataset.idleCsrf }),
            signal: controller.signal,
        }).catch(() => {}).finally(() => {
            window.clearTimeout(requestTimeout);
            window.location.assign(document.body.dataset.idleLoginUrl);
        });
    };

    const recordActivity = () => {
        lastActivityAt = Date.now();
        if (lastActivityAt - lastPersistedAt >= 5000) {
            try {
                localStorage.setItem(activityKey, String(lastActivityAt));
                lastPersistedAt = lastActivityAt;
            } catch {
                // In-memory activity tracking remains available when browser storage is disabled.
            }
        }
        scheduleLogout();
    };

    try {
        localStorage.setItem(activityKey, String(lastActivityAt));
        lastPersistedAt = lastActivityAt;
    } catch {
        // In-memory activity tracking remains available when browser storage is disabled.
    }

    window.addEventListener('storage', (event) => {
        if (event.key === activityKey) {
            readLastActivity();
            scheduleLogout();
        }
    });

    ['pointerdown', 'keydown', 'scroll', 'touchstart', 'wheel', 'mousemove'].forEach((eventName) => {
        document.addEventListener(eventName, recordActivity, { passive: true });
    });

    scheduleLogout();
}

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
    const nextLabel = reveal
        ? (toggle.dataset.hideLabel ?? 'Hide password')
        : (toggle.dataset.showLabel ?? 'Show password');

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

document.querySelectorAll('[data-trip-wizard]').forEach((form) => {
    const steps = [...form.querySelectorAll('[data-trip-step]')];
    const links = [...form.querySelectorAll('[data-trip-step-link]')];
    const captionCount = form.querySelector('[data-trip-caption-count]');
    const captionName = form.querySelector('[data-trip-caption-name]');
    const back = form.querySelector('[data-trip-back]');
    const next = form.querySelector('[data-trip-next]');
    const draft = form.querySelector('[data-trip-draft]');
    const submit = form.querySelector('[data-trip-submit]');
    const fileInput = form.querySelector('[data-trip-images]');
    const dropzone = form.querySelector('[data-trip-dropzone]');
    const preview = form.querySelector('[data-trip-image-preview]');
    const uploadStatus = form.querySelector('[data-trip-upload-status]');
    const destinationInput = form.querySelector('[data-trip-destination]');
    const endingPointInput = form.querySelector('[data-trip-ending]');
    let current = 0;
    let uploading = false;
    let dirty = false;
    let objectUrls = [];
    const field = (name) => form.elements.namedItem(name);
    const stepNames = (form.dataset.stepNames ?? 'Trip information|Schedule|Pricing|Media|Publish').split('|');

    const isInvalid = (input) => {
        if (input.type === 'radio') {
            return !form.querySelector(`input[type="radio"][name="${CSS.escape(input.name)}"]:checked`);
        }

        return !input.checkValidity();
    };

    const advanceTripStep = () => {
        const invalid = [...steps[current].querySelectorAll('input, select, textarea')].find(isInvalid);
        if (invalid) {
            const errorPanel = steps[current].querySelector('[data-trip-step-error]');
            if (errorPanel) {
                errorPanel.textContent = 'Complete the highlighted required field(s) before continuing.';
                errorPanel.classList.remove('hidden');
            }
            invalid.focus();
            invalid.reportValidity();
            return;
        }
        const errorPanel = steps[current].querySelector('[data-trip-step-error]');
        errorPanel?.classList.add('hidden');
        showStep(current + 1);
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    next.addEventListener('click', (event) => {
        event.preventDefault();
        advanceTripStep();
    });
    back.addEventListener('click', (event) => {
        event.preventDefault();
        showStep(current - 1);
    });
    links.forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const destination = Number(link.dataset.tripStepLink);
        if (destination <= current) showStep(destination);
    }));

    const departureTimeValue = () => {
        const separateTime = field('departure_time')?.value;
        if (separateTime) {
            return separateTime;
        }
        const combinedValue = field('meeting_point_and_time')?.value ?? '';
        const match = combinedValue.match(/(\d{1,2}):(\d{2})\s*(AM|PM)?\s*$/i);
        if (!match) {
            return '08:00';
        }
        let hour = Number(match[1]);
        if (match[3]) {
            hour = hour % 12 + (match[3].toUpperCase() === 'PM' ? 12 : 0);
        }
        return `${String(hour).padStart(2, '0')}:${match[2]}`;
    };

    const showStep = (index) => {
        current = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, item) => {
            step.hidden = item !== current;
            step.classList.toggle('hidden', item !== current);
        });
        links.forEach((link, item) => {
            const active = item === current;
            const completedThrough = current;
            link.setAttribute('aria-current', active ? 'step' : 'false');
            const segment = link.querySelector('[data-progress-segment]');
            segment?.classList.toggle('bg-emerald-700', item <= completedThrough);
            segment?.classList.toggle('bg-slate-200', item > completedThrough);
        });
        if (captionCount) {
            captionCount.textContent = `Step ${current + 1} of ${steps.length}`;
        }
        if (captionName) {
            captionName.textContent = stepNames[current];
        }
        back.disabled = current === 0;
        next.classList.toggle('hidden', current === steps.length - 1);
        submit.classList.toggle('hidden', current !== steps.length - 1);
        draft.classList.toggle('hidden', current !== steps.length - 1);
        if (current === steps.length - 1) {
            updatePreview();
        }
    };

    const updateDuration = () => {
        const from = field('departure_date')?.value;
        const to = field('return_date')?.value;
        const duration = field('duration_days');
        if (from && to && duration) {
            const departure = new Date(`${from}T${departureTimeValue()}`);
            const returning = new Date(`${to}T${field('return_time')?.value || '18:00'}`);
            const hours = (returning - departure) / 3600000;
            if (Number.isFinite(hours) && hours > 0) {
                duration.value = String(hours <= 24 ? 1 : hours <= 48 ? 2 : hours <= 120 ? 3 : 6);
            }
        }
    };

    const updatePreview = () => {
        const text = (selector, value, fallback) => {
            const node = form.querySelector(selector);
            if (node) {
                node.textContent = value?.trim() || fallback;
            }
        };
        const category = field('category_id');
        const date = (value, time) => {
            const parsed = new Date(value ? `${value}T${time || '00:00'}` : '');
            return value && !Number.isNaN(parsed.getTime())
                ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(parsed)
                : 'Date not set';
        };
        text('[data-preview-title]', field('name')?.value, 'Your trip title');
        text('[data-preview-category]', category?.value ? category.selectedOptions[0].textContent : '', 'Trip category');
        text('[data-preview-destination]', field('destination_name')?.value, 'Destination');
        text('[data-preview-description]', field('description')?.value, 'Your trip description will appear here.');
        text('[data-preview-route]', `${field('starting_point')?.value || 'Start'} → ${field('ending_point')?.value || 'End'}`, 'Start → End');
        text('[data-preview-dates]', `${date(field('departure_date')?.value, departureTimeValue())} → ${date(field('return_date')?.value, field('return_time')?.value)}`, 'Departure → Return');
        text('[data-preview-price]', `KES ${field('price_per_person')?.value || '—'} per adult`, 'KES — per adult');
        text('[data-side-title]', field('name')?.value, 'Your trip title');
        text('[data-side-category]', category?.value ? category.selectedOptions[0].textContent : '', 'Trip category');
        text('[data-side-destination]', field('destination_name')?.value, 'Destination');
        text('[data-side-description]', field('description')?.value, 'Your trip description will appear here.');
        text('[data-side-route]', `${field('starting_point')?.value || 'Start'} → ${field('ending_point')?.value || 'End'}`, 'Start → End');
        text('[data-side-dates]', `${date(field('departure_date')?.value, departureTimeValue())} → ${date(field('return_date')?.value, field('return_time')?.value)}`, 'Departure → Return');
        text('[data-side-price]', `KES ${field('price_per_person')?.value || '—'} / adult`, 'KES — / adult');
        const cover = form.querySelector('[data-preview-cover]');
        const chosenFile = fileInput?.files?.[0];
        const existingCover = preview?.querySelector('[data-existing-image] img');
        const sideCover = form.querySelector('[data-side-cover]');
        if (sideCover && (chosenFile || existingCover)) {
            const image = document.createElement('img');
            image.src = chosenFile ? (objectUrls[0] ?? URL.createObjectURL(chosenFile)) : existingCover.src;
            image.alt = 'Trip cover preview';
            image.className = 'h-44 w-full object-cover';
            sideCover.replaceChildren(image);
        }
        if (cover && (chosenFile || existingCover)) {
            const src = chosenFile ? (objectUrls[0] ?? URL.createObjectURL(chosenFile)) : existingCover.src;
            const image = document.createElement('img');
            image.src = src;
            image.alt = 'Trip cover preview';
            image.className = 'h-56 w-full object-cover sm:h-72';
            cover.replaceChildren(image);
        }
    };

    const reindex = (container) => {
        const type = container.dataset.tripRepeater;
        [...container.children].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((input) => {
                if (type === 'itinerary') {
                    input.name = input.name.replace(/itinerary\[\d+\]/, `itinerary[${index}]`);
                } else if (type === 'availability') {
                    input.name = input.name.replace(/availability\[\d+\]/, `availability[${index}]`);
                } else if (type === 'charges') {
                    input.name = input.name.replace(/additional_charges\[\d+\]/, `additional_charges[${index}]`);
                }
            });
            const number = row.querySelector('[name$="[day]"]');
            if (number) {
                number.value = String(index + 1);
                row.querySelector('[data-day-label]').textContent = String(index + 1);
            }
            const label = row.querySelector('[data-day-label]');
            if (label) label.textContent = String(index + 1);
        });
    };

    const addRow = (type) => {
        const container = form.querySelector(`[data-trip-repeater="${type}"]`);
        if (!container || container.children.length >= 20) {
            return;
        }
        const index = container.children.length;
        const row = document.createElement(type === 'itinerary' ? 'article' : 'div');
        if (type === 'stops') {
            row.className = 'flex gap-2';
            row.innerHTML = `<label class="sr-only" for="trip-stop-${index}">Stop ${index + 1}</label><input id="trip-stop-${index}" name="pickup_points[]" placeholder="Stop or pickup point" class="min-h-11 min-w-0 flex-1 rounded-xl border border-slate-300 px-4"><button type="button" data-remove-row class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold text-red-700">Remove</button>`;
        } else if (type === 'itinerary') {
            row.className = 'rounded-xl border border-slate-200 bg-slate-50 p-4';
            row.innerHTML = `<div class="mb-3 flex items-center justify-between"><p class="text-sm font-black">Day <span data-day-label>${index + 1}</span></p><span class="flex gap-1"><button type="button" data-move-day="up" class="rounded border px-2 py-1 text-xs" aria-label="Move day earlier">↑</button><button type="button" data-move-day="down" class="rounded border px-2 py-1 text-xs" aria-label="Move day later">↓</button><button type="button" data-remove-row class="rounded border px-2 py-1 text-xs font-bold text-red-700">Remove</button></span></div><div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-xs font-bold">Day number<input required type="number" min="1" name="itinerary[${index}][day]" value="${index + 1}" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Day title<input required maxlength="120" name="itinerary[${index}][title]" placeholder="e.g. Arrival and welcome" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Activity start time<input type="time" name="itinerary[${index}][start_time]" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Activity end time<input type="time" name="itinerary[${index}][end_time]" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Activity location<input maxlength="255" name="itinerary[${index}][location]" placeholder="Optional stop or venue" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Meals for the day<input maxlength="500" name="itinerary[${index}][meals]" placeholder="e.g. Breakfast, picnic lunch" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold sm:col-span-2">Activities & details<textarea required maxlength="4000" rows="2" name="itinerary[${index}][detail]" placeholder="Describe the day's schedule and activities" class="rounded-lg border border-slate-300 p-3 text-sm font-normal"></textarea></label></div>`;
        } else if (type === 'availability') {
            row.className = 'grid gap-2 sm:grid-cols-[1fr_160px_auto]';
            row.innerHTML = `<label class="grid gap-1 text-xs font-semibold">Departure date<input type="date" name="availability[${index}][date]" class="min-h-10 rounded-lg border border-slate-300 px-3"></label><label class="grid gap-1 text-xs font-semibold">Available seats<input type="number" min="0" name="availability[${index}][seats]" placeholder="Optional" class="min-h-10 rounded-lg border border-slate-300 px-3"></label><button type="button" data-remove-row class="self-end rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-red-700">Remove</button>`;
        } else {
            row.className = 'grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[1fr_180px_auto]';
            row.innerHTML = `<label class="grid gap-1 text-xs font-bold">Charge name<input name="additional_charges[${index}][name]" placeholder="e.g. Park conservation fee" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Amount (KES)<input type="number" min="0" name="additional_charges[${index}][amount]" placeholder="0" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><button type="button" data-remove-row class="min-h-10 self-end rounded-lg border border-slate-300 px-3 text-xs font-bold text-red-700">Remove</button>`;
        }
        container.append(row);
    };

    form.addEventListener('click', (event) => {
        const target = event.target;
        if (target.closest('[data-add-row]')) {
            addRow(target.closest('[data-add-row]').dataset.addRow);
        } else if (target.closest('[data-remove-row]')) {
            const container = target.closest('[data-trip-repeater]');
            target.closest('[data-trip-repeater] > *')?.remove();
            if (container) {
                reindex(container);
            }
        } else if (target.closest('[data-move-day]')) {
            const row = target.closest('[data-trip-repeater="itinerary"] > *');
            const direction = target.closest('[data-move-day]').dataset.moveDay;
            const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
            const container = row.parentElement;
            if (sibling) {
                container.insertBefore(direction === 'up' ? row : sibling, direction === 'up' ? sibling : row);
                reindex(container);
                dirty = true;
            }
        } else if (target.closest('[data-remove-image]')) {
            target.closest('[data-existing-image]')?.remove();
            reindexTripImages();
            dirty = true;
            updatePreview();
        } else if (target.closest('[data-move-trip-image]')) {
            const card = target.closest('[data-existing-image], [data-new-image]');
            const direction = target.closest('[data-move-trip-image]').dataset.moveTripImage;
            const sibling = direction === 'up' ? card.previousElementSibling : card.nextElementSibling;
            if (sibling) {
                preview.insertBefore(direction === 'up' ? card : sibling, direction === 'up' ? sibling : card);
                reindexTripImages();
                syncTripFilesWithPreview();
                dirty = true;
                updatePreview();
            }
        } else if (target.closest('[data-remove-trip-file]')) {
            target.closest('[data-new-image]')?.remove();
            syncTripFilesWithPreview();
            dirty = true;
            updatePreview();
        }
    });

    const reindexTripImages = () => {
        [...(preview?.querySelectorAll('[data-existing-image]') ?? [])].forEach((card, index) => {
            card.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/images\[\d+\]/, `images[${index}]`);
            });
            const label = card.querySelector('[data-trip-image-label]');
            if (label) label.textContent = index === 0 ? 'Cover image' : 'Gallery image';
        });
        [...(preview?.children ?? [])].forEach((card, index) => {
            const label = card.querySelector('[data-trip-image-label], [data-trip-file-label]');
            if (label) label.textContent = `${index === 0 ? 'Cover image' : 'Gallery image'}${card.dataset.tripFileName ? ` · ${card.dataset.tripFileName}` : ''}`;
        });
    };

    const syncTripFilesWithPreview = () => {
        const cards = [...(preview?.querySelectorAll('[data-new-image]') ?? [])];
        const previousFiles = [...(fileInput?.files ?? [])];
        const transfer = new DataTransfer();
        cards.forEach((card, index) => {
            const oldIndex = Number(card.dataset.tripFileIndex);
            if (previousFiles[oldIndex]) transfer.items.add(previousFiles[oldIndex]);
            card.dataset.tripFileIndex = String(index);
        });
        if (fileInput) fileInput.files = transfer.files;
    };

    const tripMediaOrder = () => [...(preview?.children ?? [])].map((card) => {
        if (card.matches('[data-existing-image]')) return `existing:${card.querySelector('input[name$="[url]"]')?.value ?? ''}`;
        if (card.matches('[data-new-image]')) return `file:${card.dataset.tripFileIndex}`;
        return '';
    }).filter(Boolean);

    form.addEventListener('input', (event) => {
        dirty = true;
        if (event.target === destinationInput && endingPointInput) {
            endingPointInput.value = destinationInput.value;
        }
        if (['departure_date', 'departure_time', 'meeting_point_and_time', 'return_date', 'return_time'].includes(event.target.name)) {
            updateDuration();
        }
        if (current === steps.length - 1) {
            updatePreview();
        }
    });

    form.addEventListener('change', (event) => {
        if (event.target === fileInput) {
            dirty = true;
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
            objectUrls = [];
            const existing = preview?.querySelectorAll('[data-existing-image]').length ?? 0;
            const selectedFiles = [...(fileInput.files ?? [])];
            const oversizedFiles = selectedFiles.filter((file) => file.size > 5 * 1024 * 1024);
            if (oversizedFiles.length > 0) {
                const acceptedFiles = selectedFiles.filter((file) => file.size <= 5 * 1024 * 1024);
                const transfer = new DataTransfer();
                acceptedFiles.forEach((file) => transfer.items.add(file));
                fileInput.files = transfer.files;
                uploadStatus.textContent = `${oversizedFiles.map((file) => file.name).join(', ')} exceeds the 5 MB image size limit. Choose smaller image(s).`;
                uploadStatus.classList.add('font-bold', 'text-red-700');
            }
            if ([...(fileInput.files ?? [])].reduce((total, file) => total + file.size, 0) > 6 * 1024 * 1024) {
                fileInput.value = '';
                preview?.querySelectorAll('[data-new-image]').forEach((node) => node.remove());
                uploadStatus.textContent = 'Combined image upload exceeds the 6 MB limit. Compress or remove images before continuing.';
                uploadStatus.classList.add('font-bold', 'text-red-700');
                return;
            }
            if (existing + (fileInput.files?.length ?? 0) > 12) {
                fileInput.value = '';
                uploadStatus.textContent = 'Keep the total trip image count to 12 or fewer.';
                return;
            }
            preview?.querySelectorAll('[data-new-image]').forEach((node) => node.remove());
            [...(fileInput.files ?? [])].forEach((file, index) => {
                const url = URL.createObjectURL(file);
                objectUrls.push(url);
                const wrapper = document.createElement('div');
                wrapper.dataset.newImage = '';
                wrapper.dataset.tripFileIndex = String(index);
                wrapper.dataset.tripFileName = file.name;
                wrapper.dataset.sortableImage = '';
                wrapper.draggable = true;
                wrapper.className = 'overflow-hidden rounded-xl border border-slate-200';
                const image = document.createElement('img');
                image.src = url;
                image.alt = `Selected trip photo ${index + 1}`;
                image.className = 'h-28 w-full object-cover';
                const toolbar = document.createElement('div');
                toolbar.className = 'flex items-center justify-between gap-1 p-2';
                const name = document.createElement('span');
                name.dataset.tripFileLabel = '';
                name.className = 'truncate text-[10px] font-bold';
                const controls = document.createElement('span');
                controls.className = 'flex gap-1';
                [['up', '↑'], ['down', '↓']].forEach(([direction, label]) => {
                    const move = document.createElement('button');
                    move.type = 'button';
                    move.dataset.moveTripMedia = direction;
                    move.className = 'rounded border px-2 py-1 text-[10px]';
                    move.textContent = label;
                    controls.append(move);
                });
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.dataset.removeTripFile = '';
                remove.className = 'rounded border border-red-200 px-2 py-1 text-[10px] font-bold text-red-700';
                remove.setAttribute('aria-label', 'Remove photo');
                remove.textContent = '×';
                controls.append(remove);
                toolbar.append(name, controls);
                wrapper.append(image, toolbar);
                preview?.append(wrapper);
            });
            reindexTripImages();
            if (oversizedFiles.length === 0) {
                uploadStatus.textContent = fileInput.files?.length ? `${fileInput.files.length} image(s) ready to upload.` : '';
                uploadStatus.classList.remove('font-bold', 'text-red-700');
            }
            updatePreview();
        }
    });

    dropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-emerald-700', 'bg-emerald-100');
    });
    dropzone?.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
    });
    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
        const transfer = new DataTransfer();
        [...(fileInput?.files ?? []), ...(event.dataTransfer?.files ?? [])].forEach((file) => transfer.items.add(file));
        fileInput.files = transfer.files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (uploading) {
            return;
        }
        const invalid = [...form.querySelectorAll('input, select, textarea')].find((input) => input.willValidate && isInvalid(input));
        if (invalid) {
            showStep(Number(invalid.closest('[data-trip-step]')?.dataset.tripStep ?? 0));
            window.setTimeout(() => invalid.reportValidity(), 0);
            return;
        }
        const action = event.submitter?.value ?? 'draft';
        const existing = preview?.querySelectorAll('[data-existing-image]').length ?? 0;
        if (action === 'submit' && existing === 0 && (fileInput?.files?.length ?? 0) === 0) {
            showStep(Number(fileInput?.closest('[data-trip-step]')?.dataset.tripStep ?? 3));
            uploadStatus.textContent = 'Add at least one cover or gallery image before submitting.';
            uploadStatus.classList.add('font-bold', 'text-red-700');
            return;
        }

        const button = event.submitter;
        const originalText = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = 'Saving…';
            button.setAttribute('aria-busy', 'true');
        }
        const data = new FormData(form);
        data.set('submission_action', action);
        tripMediaOrder().forEach((token) => data.append('media_order[]', token));
        dirty = false;
        const request = new XMLHttpRequest();
        uploading = true;
        request.open(form.method.toUpperCase(), form.action);
        request.setRequestHeader('Accept', 'text/html');
        request.upload.addEventListener('progress', (progressEvent) => {
            if (uploadStatus && progressEvent.lengthComputable) {
                uploadStatus.textContent = `Uploading trip photos: ${Math.round((progressEvent.loaded / progressEvent.total) * 100)}%`;
            }
        });
        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 400 && request.responseURL) {
                window.location.assign(request.responseURL);
                return;
            }
            uploading = false;
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
                button.removeAttribute('aria-busy');
            }
            uploadStatus.textContent = 'The trip could not be saved. Check your connection and try again.';
            uploadStatus.classList.add('font-bold', 'text-red-700');
        });
        request.addEventListener('error', () => {
            uploading = false;
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
                button.removeAttribute('aria-busy');
            }
            uploadStatus.textContent = 'The upload was interrupted. Please try again.';
            uploadStatus.classList.add('font-bold', 'text-red-700');
        });
        request.send(data);
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty && !uploading) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    updateDuration();
    showStep(0);
});

document.querySelectorAll('[data-admin-trip-wizard]').forEach((form) => {
    const steps = [...form.querySelectorAll('[data-admin-trip-step]')];
    const links = [...form.querySelectorAll('[data-admin-wizard-link]')];
    const currentInput = form.querySelector('[data-admin-wizard-current]');
    const count = form.querySelector('[data-admin-wizard-count]');
    const name = form.querySelector('[data-admin-wizard-name]');
    const back = form.querySelector('[data-admin-wizard-back]');
    const next = form.querySelector('[data-admin-wizard-next]');
    const submit = form.querySelector('[data-admin-wizard-submit]');
    const cancel = form.querySelector('[data-admin-wizard-cancel]');
    const stepNames = ['Trip information', 'Schedule', 'Pricing', 'Publish'];
    let current = Number(currentInput?.value ?? 0);
    const invalidFieldNames = form.dataset.adminInvalidFields?.split(',').filter(Boolean) ?? [];

    const showStep = (index) => {
        current = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, stepIndex) => {
            step.hidden = stepIndex !== current;
            step.classList.toggle('hidden', stepIndex !== current);
        });
        links.forEach((link, stepIndex) => {
            link.setAttribute('aria-current', stepIndex === current ? 'step' : 'false');
            const segment = link.querySelector('[data-admin-progress-segment]');
            segment?.classList.toggle('bg-emerald-700', stepIndex <= current);
            segment?.classList.toggle('bg-slate-200', stepIndex > current);
        });
        if (currentInput) {
            currentInput.value = String(current);
        }
        if (count) {
            count.textContent = `Step ${current + 1} of ${steps.length}`;
        }
        if (name) {
            name.textContent = stepNames[current];
        }
        back.disabled = current === 0;
        next.classList.toggle('hidden', current === steps.length - 1);
        submit.classList.toggle('hidden', current !== steps.length - 1);
        cancel.classList.toggle('hidden', current !== steps.length - 1);
    };

    if (invalidFieldNames.length > 0) {
        const invalidName = invalidFieldNames[0]
            .replace(/\.(\d+)\./g, '[$1][')
            .replace(/\.(\w+)$/, '[$1]');
        const invalidField = [...form.elements].find((field) => (
            field.name === invalidName
            || (field.name.endsWith('[]') && invalidName.startsWith(field.name.slice(0, -2)))
        ));
        if (invalidField) {
            current = Number(invalidField.closest('[data-admin-trip-step]')?.dataset.adminTripStep ?? current);
        }
    }

    form.addEventListener('click', (event) => {
        const link = event.target.closest('[data-admin-wizard-link]');
        if (link && Number(link.dataset.adminWizardLink) <= current) {
            showStep(Number(link.dataset.adminWizardLink));
        } else if (event.target.closest('[data-admin-wizard-back]')) {
            showStep(current - 1);
        } else if (event.target.closest('[data-admin-wizard-next]')) {
            const invalid = [...steps[current].querySelectorAll('input, select, textarea')]
                .find((input) => input.willValidate && !input.checkValidity());
            if (invalid) {
                invalid.reportValidity();
                return;
            }
            showStep(current + 1);
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    form.addEventListener('submit', (event) => {
        const invalid = [...form.querySelectorAll('input, select, textarea')]
            .find((input) => input.willValidate && !input.checkValidity());
        if (!invalid) {
            return;
        }
        event.preventDefault();
        showStep(Number(invalid.closest('[data-admin-trip-step]')?.dataset.adminTripStep ?? 0));
        window.setTimeout(() => invalid.reportValidity(), 0);
    });

    showStep(current);
    if (invalidFieldNames.length > 0) {
        form.scrollIntoView({ block: 'start' });
    }
});

document.querySelectorAll('[data-admin-itinerary]').forEach((itinerary) => {
    const rows = itinerary.querySelector('[data-admin-itinerary-rows]');

    const reindexRows = () => {
        [...rows.children].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/itinerary\[\d+\]/, `itinerary[${index}]`);
            });
            const dayInput = row.querySelector('[name$="[day]"]');
            const dayNumber = row.querySelector('[data-admin-day-number]');
            if (dayInput) {
                dayInput.value = String(index + 1);
            }
            if (dayNumber) {
                dayNumber.textContent = String(index + 1);
            }
        });
    };

    itinerary.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-admin-add-day]');
        if (addButton) {
            if (rows.children.length >= 30) {
                return;
            }
            const index = rows.children.length;
            const row = document.createElement('article');
            row.dataset.adminItineraryDay = '';
            row.className = 'rounded border border-slate-200 bg-slate-50 p-4';
            row.innerHTML = `<div class="mb-3 flex items-center justify-between"><p class="text-sm font-bold">Day <span data-admin-day-number>${index + 1}</span></p><button type="button" data-admin-remove-day class="text-xs font-bold text-red-700">Remove day</button></div><div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-xs font-bold text-slate-700">Day number<input type="number" min="1" required name="itinerary[${index}][day]" value="${index + 1}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold text-slate-700">Title<input maxlength="120" required name="itinerary[${index}][title]" placeholder="e.g. Arrival and welcome" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold text-slate-700">Start time<input type="time" name="itinerary[${index}][start_time]" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold text-slate-700">End time<input type="time" name="itinerary[${index}][end_time]" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold text-slate-700 sm:col-span-2">Activities and details<textarea maxlength="4000" required rows="2" name="itinerary[${index}][detail]" placeholder="Describe the day's activities and schedule" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal"></textarea></label></div>`;
            rows.append(row);
            return;
        }

        const removeButton = event.target.closest('[data-admin-remove-day]');
        if (!removeButton) {
            return;
        }
        const row = removeButton.closest('[data-admin-itinerary-day]');
        if (rows.children.length === 1) {
            row.querySelectorAll('input:not([type="number"]), textarea').forEach((input) => {
                input.value = '';
            });
            const title = row.querySelector('[name$="[title]"]');
            const detail = row.querySelector('[name$="[detail]"]');
            title.value = '';
            detail.value = '';
            return;
        }
        row.remove();
        reindexRows();
    });
});

document.querySelectorAll('[data-vehicle-wizard]').forEach((form) => {
    const steps = [...form.querySelectorAll('[data-vehicle-step]')];
    const links = [...form.querySelectorAll('[data-vehicle-step-link]')];
    const back = form.querySelector('[data-vehicle-back]');
    const next = form.querySelector('[data-vehicle-next]');
    const draft = form.querySelector('[data-vehicle-draft]');
    const submit = form.querySelector('[data-vehicle-submit]');
    const imageInput = form.querySelector('[data-vehicle-media-input]');
    const imageDropzone = form.querySelector('[data-vehicle-media-dropzone]');
    const imagePreview = form.querySelector('[data-vehicle-media-preview]');
    const imageStatus = form.querySelector('[data-vehicle-media-status]');
    const storageKey = `tourlink-vehicle-draft:${form.dataset.formDraft}`;
    const stepNames = (form.dataset.stepNames ?? 'Details|Features|Multimedia|Pricing|Location|Review').split('|');
    const imageMaxBytes = Number(form.dataset.imageMaxBytes || 5 * 1024 * 1024);
    const imageTotalMaxBytes = Number(form.dataset.imageTotalMaxBytes || 6 * 1024 * 1024);
    const supportedImageTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
    let current = 0;
    let busy = false;
    let dirty = false;
    let previewUrls = [];

    const isInvalid = (field) => {
        if (field.type === 'radio') {
            return !form.querySelector(`input[type="radio"][name="${CSS.escape(field.name)}"]:checked`);
        }
        return !field.checkValidity();
    };

    const advanceVehicleStep = () => {
        if (current === 2 && imagePreview.querySelectorAll('[data-vehicle-existing-image]').length + imageInput.files.length === 0) {
            const imageError = steps[current].querySelector('[data-vehicle-step-error]');
            imageError.textContent = 'Please upload at least one vehicle photo.';
            imageError.classList.remove('hidden');
            imageStatus.textContent = 'Please upload at least one vehicle photo.';
            imageStatus.classList.add('font-bold', 'text-red-700');
            imageInput.focus();
            return;
        }
        const invalid = [...steps[current].querySelectorAll('input, select, textarea')]
            .find((field) => field.willValidate && isInvalid(field));
        if (invalid) {
            const errorPanel = steps[current].querySelector('[data-vehicle-step-error]');
            if (errorPanel) {
                errorPanel.textContent = 'Complete the highlighted required field(s) before continuing.';
                errorPanel.classList.remove('hidden');
            }
            invalid.focus();
            invalid.reportValidity();
            return;
        }
        const errorPanel = steps[current].querySelector('[data-vehicle-step-error]');
        errorPanel?.classList.add('hidden');
        showStep(current + 1);
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    next.addEventListener('click', (event) => {
        event.preventDefault();
        advanceVehicleStep();
    });
    back.addEventListener('click', (event) => {
        event.preventDefault();
        showStep(current - 1);
    });
    links.forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const destination = Number(link.dataset.vehicleStepLink);
        if (destination <= current) showStep(destination);
    }));
    const updatePreview = () => {
        const setText = (selector, value, fallback) => {
            const target = form.querySelector(selector);
            if (target) {
                target.textContent = value?.trim() || fallback;
            }
        };
        const name = form.elements.namedItem('name')?.value;
        const year = form.elements.namedItem('year')?.value;
        const transmission = form.elements.namedItem('transmission')?.value;
        const fuel = form.elements.namedItem('fuel_type')?.value;
        const seats = form.elements.namedItem('seating_capacity')?.value;
        const location = form.elements.namedItem('location')?.value;
        const dailyPrice = form.elements.namedItem('price_per_day')?.value;
        const specs = [year, transmission, fuel, seats ? `${seats} seats` : ''].filter(Boolean).join(' · ');
        setText('[data-vehicle-preview-name]', name, 'Vehicle name');
        setText('[data-vehicle-preview-specs]', specs, 'Year · transmission · fuel · seats');
        setText('[data-vehicle-preview-location]', location, 'Location');
        setText('[data-vehicle-preview-price]', `KES ${dailyPrice || '—'}`, 'KES —');
        const mobile = form.querySelector('[data-vehicle-preview-mobile]');
        if (mobile) {
            mobile.textContent = `${name || 'Vehicle name'} · ${specs || 'Vehicle details'} · ${location || 'Location'} · KES ${dailyPrice || '—'} / day`;
        }
        const imageTarget = form.querySelector('[data-vehicle-preview-image]');
        const firstFile = imageInput?.files?.[0];
        const existingImage = imagePreview?.querySelector('[data-vehicle-existing-image] img');
        if (imageTarget && (firstFile || existingImage)) {
            const image = document.createElement('img');
            image.src = firstFile ? (previewUrls[0] ?? URL.createObjectURL(firstFile)) : existingImage.src;
            image.alt = 'Vehicle cover preview';
            image.className = 'h-52 w-full object-cover';
            imageTarget.replaceChildren(image);
        }
    };

    const reindex = (container, prefix) => {
        [...container.children].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(new RegExp(`${prefix}\\[\\d+\\]`), `${prefix}[${index}]`);
            });
        });
    };

    const reorderNewFiles = (fromIndex, toIndex) => {
        const files = [...(imageInput.files ?? [])];
        if (toIndex < 0 || toIndex >= files.length) return;
        const [file] = files.splice(fromIndex, 1);
        files.splice(toIndex, 0, file);
        const transfer = new DataTransfer();
        files.forEach((item) => transfer.items.add(item));
        imageInput.files = transfer.files;
        imageInput.dispatchEvent(new Event('change', { bubbles: true }));
        dirty = true;
    };

    const reindexVehicleImages = () => {
        [...imagePreview.children].forEach((card, index) => {
            if (card.matches('[data-vehicle-existing-image]')) {
                card.querySelectorAll('[name]').forEach((field) => { field.name = field.name.replace(/images\[\d+\]/, `images[${index}]`); });
                card.querySelector('[data-vehicle-image-label]').textContent = index === 0 ? 'Cover photo' : 'Gallery photo';
            } else if (card.matches('[data-vehicle-new-image]')) {
                const fileName = card.dataset.vehicleFileName;
                const label = card.querySelector('[data-vehicle-new-label]');
                if (label) label.textContent = `${index === 0 ? 'Cover photo' : 'Gallery photo'} · ${fileName}`;
            }
        });
    };
    const vehicleMediaOrder = () => [...imagePreview.children].map((card) => {
        if (card.matches('[data-vehicle-existing-image]')) return `existing:${card.querySelector('input[name$="[url]"]')?.value ?? ''}`;
        if (card.matches('[data-vehicle-new-image]')) return `file:${card.dataset.vehicleFileIndex}`;
        return '';
    }).filter(Boolean);

    const appendSimpleRow = (container, prefix, labels, placeholders) => {
        if (!container || container.children.length >= 50) {
            return;
        }
        const index = container.children.length;
        const row = document.createElement('div');
        row.className = prefix === 'availability'
            ? 'grid gap-2 sm:grid-cols-[1fr_160px_auto]'
            : prefix === 'additional_charges'
                ? 'grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[1fr_160px_auto]'
                : 'flex gap-2';
        if (prefix === 'availability') {
            row.innerHTML = `<label class="grid gap-1 text-xs font-semibold">Date<input type="date" name="availability[${index}][date]" class="min-h-10 rounded-lg border border-slate-300 px-3"></label><label class="flex min-h-10 items-center gap-2 text-xs font-semibold"><input type="checkbox" name="availability[${index}][available]" value="1" checked class="size-4 accent-emerald-800">Available</label><button type="button" data-vehicle-remove-row class="self-end rounded-lg border px-3 py-2 text-xs text-red-700">Remove</button>`;
        } else if (prefix === 'additional_charges') {
            row.innerHTML = `<label class="grid gap-1 text-xs font-semibold">Fee name<input name="additional_charges[${index}][name]" placeholder="${placeholders[0]}" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-semibold">Amount (KES)<input type="number" min="0" name="additional_charges[${index}][amount]" placeholder="0" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><button type="button" data-vehicle-remove-row class="self-end rounded-lg border px-3 py-2 text-xs text-red-700">Remove</button>`;
        } else {
            row.innerHTML = `<input name="service_areas[${index}]" placeholder="${placeholders[0]}" class="min-h-10 min-w-0 flex-1 rounded-xl border border-slate-300 px-3"><button type="button" data-vehicle-remove-row class="rounded-lg border px-3 text-xs text-red-700">Remove</button>`;
        }
        container.append(row);
    };

    const showStep = (index) => {
        current = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, stepIndex) => {
            step.hidden = stepIndex !== current;
            step.classList.toggle('hidden', stepIndex !== current);
        });
        links.forEach((link, stepIndex) => {
            link.setAttribute('aria-current', stepIndex === current ? 'step' : 'false');
            const bar = link.querySelector('[data-vehicle-step-bar]');
            bar?.classList.toggle('bg-emerald-700', stepIndex <= current);
            bar?.classList.toggle('bg-slate-200', stepIndex > current);
        });
        const counter = form.querySelector('[data-vehicle-step-count]');
        const title = form.querySelector('[data-vehicle-step-title]');
        if (counter) counter.textContent = `Step ${current + 1} of ${steps.length}`;
        if (title) title.textContent = stepNames[current];
        back.disabled = current === 0;
        next.classList.toggle('hidden', current === steps.length - 1);
        draft.classList.toggle('hidden', current !== steps.length - 1);
        submit.classList.toggle('hidden', current !== steps.length - 1);
        if (current === steps.length - 1) updatePreview();
    };

    try {
        const draftValues = JSON.parse(sessionStorage.getItem(storageKey) ?? '{}');
        [...form.elements].forEach((field) => {
            if (!field.name || !Object.hasOwn(draftValues, field.name) || field.type === 'file') return;
            if (field.type === 'checkbox') field.checked = Boolean(draftValues[field.name]);
            else if (!field.value) field.value = draftValues[field.name];
        });
    } catch {}

    form.addEventListener('input', (event) => {
        dirty = true;
        const field = event.target;
        if (field.matches('[data-vehicle-draft-field]') || field.name) {
            try {
                const values = Object.fromEntries([...form.elements].filter((item) => item.name && item.type !== 'file').map((item) => [item.name, item.type === 'checkbox' ? item.checked : item.value]));
                sessionStorage.setItem(storageKey, JSON.stringify(values));
            } catch {}
        }
        updatePreview();
    });
    form.addEventListener('change', async (event) => {
        if (event.target !== imageInput) return;
        dirty = true;
        previewUrls.forEach(URL.revokeObjectURL);
        previewUrls = [];
        imagePreview.querySelectorAll('[data-vehicle-new-image]').forEach((node) => node.remove());
        const files = [...(imageInput.files ?? [])];
        const oversized = files.filter((file) => file.size > imageMaxBytes);
        const unsupported = files.filter((file) => {
            const extension = file.name.split('.').pop()?.toLowerCase();
            return !supportedImageTypes.has(file.type) || !['jpg', 'jpeg', 'png', 'webp'].includes(extension);
        });
        if (oversized.length || unsupported.length) {
            const transfer = new DataTransfer();
            files.filter((file) => file.size <= imageMaxBytes && supportedImageTypes.has(file.type) && ['jpg', 'jpeg', 'png', 'webp'].includes(file.name.split('.').pop()?.toLowerCase())).forEach((file) => transfer.items.add(file));
            imageInput.files = transfer.files;
            const errors = [];
            if (oversized.length) errors.push(`${oversized.map((file) => file.name).join(', ')} exceeds the ${Math.round(imageMaxBytes / 1024 / 1024)} MB per-image limit`);
            if (unsupported.length) errors.push(`${unsupported.map((file) => file.name).join(', ')} is not a supported image format`);
            imageStatus.textContent = `${errors.join('; ')}. Choose valid JPG, JPEG, PNG or WebP photos.`;
            imageStatus.classList.add('font-bold', 'text-red-700');
        }
        const dimensionFailures = [];
        const dimensionValidFiles = [];
        for (const file of [...(imageInput.files ?? [])]) {
            try {
                const bitmap = await createImageBitmap(file);
                const isLargeEnough = bitmap.width >= 320 && bitmap.height >= 240;
                bitmap.close();
                if (isLargeEnough) dimensionValidFiles.push(file);
                else dimensionFailures.push(file.name);
            } catch {
                dimensionFailures.push(file.name);
            }
        }
        if (dimensionFailures.length > 0) {
            const transfer = new DataTransfer();
            dimensionValidFiles.forEach((file) => transfer.items.add(file));
            imageInput.files = transfer.files;
            imageStatus.textContent = `${dimensionFailures.join(', ')} could not be read as a clear image of at least 320 × 240 pixels. Choose another photo.`;
            imageStatus.classList.add('font-bold', 'text-red-700');
        }
        if ([...(imageInput.files ?? [])].reduce((total, file) => total + file.size, 0) > imageTotalMaxBytes) {
            imageInput.value = '';
            imageStatus.textContent = `Combined vehicle photos exceed the ${Math.round(imageTotalMaxBytes / 1024 / 1024)} MB limit. Compress or remove photos.`;
            imageStatus.classList.add('font-bold', 'text-red-700');
            return;
        }
        const existingCount = imagePreview.querySelectorAll('[data-vehicle-existing-image]').length;
        if (existingCount + imageInput.files.length > 12) {
            imageInput.value = '';
            imageStatus.textContent = 'A vehicle can have up to 12 photos.';
            imageStatus.classList.add('font-bold', 'text-red-700');
            return;
        }
        [...imageInput.files].forEach((file, index) => {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);
            const card = document.createElement('article');
            card.dataset.vehicleNewImage = '';
            card.dataset.vehicleFileIndex = String(index);
            card.dataset.vehicleFileName = file.name;
            card.className = 'overflow-hidden rounded-xl border border-slate-200';
            const image = document.createElement('img');
            image.src = url;
            image.alt = `Selected vehicle photo ${index + 1}`;
            image.className = 'h-32 w-full object-cover';
            const actions = document.createElement('div');
            actions.className = 'flex items-center justify-between gap-1 p-2';
            const name = document.createElement('span');
            name.dataset.vehicleNewLabel = '';
            name.className = 'truncate text-[10px] font-bold';
            name.textContent = existingCount === 0 && index === 0 ? `${file.name} · Cover photo` : file.name;
            const controls = document.createElement('span');
            controls.className = 'flex gap-1';
            [['up', '↑', 'Move photo earlier'], ['down', '↓', 'Move photo later']].forEach(([direction, label, aria]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.vehicleNewMove = direction;
                button.className = 'rounded border px-2 py-1 text-xs';
                button.setAttribute('aria-label', aria);
                button.textContent = label;
                controls.append(button);
            });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.vehicleRemoveNew = '';
            remove.className = 'rounded border border-red-200 px-2 py-1 text-xs font-bold text-red-700';
            remove.setAttribute('aria-label', 'Remove photo');
            remove.textContent = '×';
            controls.append(remove);
            actions.append(name, controls);
            card.append(image, actions);
            imagePreview.append(card);
        });
        reindexVehicleImages();
        if (!oversized.length && !unsupported.length && !dimensionFailures.length) {
            imageStatus.textContent = imageInput.files.length ? `${imageInput.files.length} photo(s) ready to upload.` : '';
            imageStatus.classList.remove('font-bold', 'text-red-700');
        }
        updatePreview();
    });

    imageDropzone?.addEventListener('dragover', (event) => { event.preventDefault(); imageDropzone.classList.add('border-emerald-700', 'bg-emerald-100'); });
    imageDropzone?.addEventListener('dragleave', () => imageDropzone.classList.remove('border-emerald-700', 'bg-emerald-100'));
    imageDropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        imageDropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
        const transfer = new DataTransfer();
        [...(imageInput.files ?? []), ...(event.dataTransfer?.files ?? [])].forEach((file) => transfer.items.add(file));
        imageInput.files = transfer.files;
        imageInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    form.addEventListener('click', (event) => {
        const target = event.target;
        if (target.closest('[data-vehicle-add-charge]')) {
            appendSimpleRow(form.querySelector('[data-vehicle-charges]'), 'additional_charges', [], ['e.g. Cleaning fee']);
        } else if (target.closest('[data-vehicle-add-area]')) {
            appendSimpleRow(form.querySelector('[data-vehicle-service-areas]'), 'service_areas', [], ['e.g. Mombasa County']);
        } else if (target.closest('[data-vehicle-add-date]')) {
            appendSimpleRow(form.querySelector('[data-vehicle-availability]'), 'availability', [], []);
        } else if (target.closest('[data-vehicle-remove-row]')) {
            const container = target.closest('[data-vehicle-charges], [data-vehicle-service-areas], [data-vehicle-availability]');
            target.closest('.grid, .flex')?.remove();
            if (container) {
                const type = container.matches('[data-vehicle-charges]') ? 'additional_charges' : container.matches('[data-vehicle-service-areas]') ? 'service_areas' : 'availability';
                reindex(container, type);
            }
        } else if (target.closest('[data-vehicle-remove-image]')) {
            target.closest('[data-vehicle-existing-image]')?.remove();
            reindexVehicleImages();
            updatePreview();
        } else if (target.closest('[data-vehicle-move]')) {
            const card = target.closest('[data-vehicle-existing-image], [data-vehicle-new-image]');
            const direction = target.closest('[data-vehicle-move]').dataset.vehicleMove;
            const sibling = direction === 'up' ? card.previousElementSibling : card.nextElementSibling;
            if (sibling) {
                imagePreview.insertBefore(direction === 'up' ? card : sibling, direction === 'up' ? sibling : card);
                const newCards = [...imagePreview.querySelectorAll('[data-vehicle-new-image]')];
                const oldFiles = [...(imageInput.files ?? [])];
                const transfer = new DataTransfer();
                newCards.forEach((newCard, index) => {
                    const file = oldFiles[Number(newCard.dataset.vehicleFileIndex)];
                    if (file) transfer.items.add(file);
                    newCard.dataset.vehicleFileIndex = String(index);
                });
                imageInput.files = transfer.files;
                reindexVehicleImages();
                dirty = true;
                updatePreview();
            }
        } else if (target.closest('[data-vehicle-remove-new]')) {
            const card = target.closest('[data-vehicle-new-image]');
            const selectedIndex = [...imagePreview.querySelectorAll('[data-vehicle-new-image]')].indexOf(card);
            const fileIndex = selectedIndex;
            const files = [...(imageInput.files ?? [])];
            files.splice(fileIndex, 1);
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            imageInput.files = transfer.files;
            imageInput.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (target.closest('[data-vehicle-new-move]')) {
            const card = target.closest('[data-vehicle-new-image]');
            const index = [...imagePreview.querySelectorAll('[data-vehicle-new-image]')].indexOf(card);
            const direction = target.closest('[data-vehicle-new-move]').dataset.vehicleNewMove;
            reorderNewFiles(index, index + (direction === 'up' ? -1 : 1));
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (busy) return;
        const invalid = [...form.querySelectorAll('input, select, textarea')].find((field) => field.willValidate && isInvalid(field));
        if (invalid) {
            showStep(Number(invalid.closest('[data-vehicle-step]')?.dataset.vehicleStep ?? 0));
            window.setTimeout(() => invalid.reportValidity(), 0);
            return;
        }
        const action = event.submitter?.value ?? 'draft';
        const existingCount = imagePreview.querySelectorAll('[data-vehicle-existing-image]').length;
        if (existingCount + imageInput.files.length === 0) {
            showStep(2);
            imageStatus.textContent = 'Please upload at least one vehicle photo.';
            imageStatus.classList.add('font-bold', 'text-red-700');
            return;
        }
        dirty = false;
        const button = event.submitter;
        const originalText = button?.textContent;
        if (button) { button.disabled = true; button.textContent = 'Saving…'; button.setAttribute('aria-busy', 'true'); }
        const data = new FormData(form);
        data.set('submission_action', action);
        vehicleMediaOrder().forEach((token) => data.append('media_order[]', token));
        const request = new XMLHttpRequest();
        busy = true;
        request.open(form.method.toUpperCase(), form.action);
        request.setRequestHeader('Accept', 'text/html');
        request.upload.addEventListener('progress', (progressEvent) => {
            if (progressEvent.lengthComputable) imageStatus.textContent = `Uploading photos: ${Math.round(progressEvent.loaded / progressEvent.total * 100)}%`;
        });
        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 400 && request.responseURL) {
                sessionStorage.removeItem(storageKey);
                window.location.assign(request.responseURL);
                return;
            }
            busy = false;
            if (button) { button.disabled = false; button.textContent = originalText; button.removeAttribute('aria-busy'); }
            imageStatus.textContent = 'Could not save the vehicle. Review the form and try again.';
            imageStatus.classList.add('font-bold', 'text-red-700');
        });
        request.addEventListener('error', () => {
            busy = false;
            if (button) { button.disabled = false; button.textContent = originalText; button.removeAttribute('aria-busy'); }
            imageStatus.textContent = 'The request was interrupted. Please try again.';
            imageStatus.classList.add('font-bold', 'text-red-700');
        });
        request.send(data);
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty && !busy) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    form.addEventListener('change', () => updatePreview());
    if (form.querySelector('[data-form-draft-clear]')) sessionStorage.removeItem(storageKey);
    showStep(0);
    updatePreview();
});

document.querySelectorAll('[data-trip-wizard], [data-vehicle-wizard]').forEach((form) => {
    if (!window.visualViewport) {
        return;
    }
    const updateKeyboardInset = () => {
        const keyboardInset = Math.max(0, window.innerHeight - window.visualViewport.height - window.visualViewport.offsetTop);
        form.style.setProperty('--virtual-keyboard-inset', `${keyboardInset}px`);
    };
    window.visualViewport.addEventListener('resize', updateKeyboardInset);
    window.visualViewport.addEventListener('scroll', updateKeyboardInset);
    updateKeyboardInset();
});

document.querySelectorAll('[data-admin-image-upload]').forEach((uploader) => {
    const input = uploader.querySelector('[data-admin-image-input]');
    const dropzone = uploader.querySelector('[data-admin-dropzone]');
    const preview = uploader.querySelector('[data-admin-image-preview]');
    const status = uploader.querySelector('[data-admin-image-status]');
    let previewUrls = [];

    if (!input || !dropzone || !preview || !status) {
        return;
    }

    const renderFiles = () => {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
        preview.querySelectorAll('[data-admin-new-image]').forEach((image) => image.remove());
        const existingCount = preview.querySelectorAll('[data-admin-existing-image]').length;
        const selectedFiles = [...(input.files ?? [])];
        const oversizedFiles = selectedFiles.filter((file) => file.size > 5 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            const acceptedFiles = selectedFiles.filter((file) => file.size <= 5 * 1024 * 1024);
            const transfer = new DataTransfer();
            acceptedFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            status.textContent = `${oversizedFiles.map((file) => file.name).join(', ')} exceeds the 5 MB image size limit. Choose smaller image(s).`;
            status.classList.add('font-bold', 'text-red-700');
        }
        const files = [...(input.files ?? [])];

        if (files.reduce((total, file) => total + file.size, 0) > 6 * 1024 * 1024) {
            input.value = '';
            status.textContent = 'Combined image upload exceeds the 6 MB limit. Compress or remove images before continuing.';
            status.classList.add('font-bold', 'text-red-700');
            return;
        }

        if (existingCount + files.length > 12) {
            input.value = '';
            status.textContent = 'Keep the total trip image count to 12 or fewer.';
            status.classList.add('font-bold', 'text-red-700');
            return;
        }

        files.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);
            const card = document.createElement('div');
            card.dataset.adminNewImage = '';
            card.className = 'overflow-hidden rounded-lg border border-slate-200';
            const image = document.createElement('img');
            image.src = url;
            image.alt = `Selected trip image ${index + 1}`;
            image.className = 'h-24 w-full object-cover';
            const caption = document.createElement('p');
            caption.className = 'truncate px-2 py-1 text-xs font-semibold';
            caption.textContent = existingCount === 0 && index === 0 ? `${file.name} · Cover` : file.name;
            card.append(image, caption);
            preview.append(card);
        });
        if (oversizedFiles.length === 0) {
            status.textContent = files.length ? `${files.length} image(s) selected and ready to upload.` : '';
            status.classList.remove('font-bold', 'text-red-700');
        }
    };

    input.addEventListener('change', renderFiles);
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-emerald-700', 'bg-emerald-100');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
        const transfer = new DataTransfer();
        [...(input.files ?? []), ...(event.dataTransfer?.files ?? [])].forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        renderFiles();
    });
    uploader.addEventListener('click', (event) => {
        if (event.target.closest('[data-admin-remove-image]')) {
            event.target.closest('[data-admin-existing-image]')?.remove();
            renderFiles();
        }
    });
});

document.querySelectorAll('[data-vehicle-image-input]').forEach((input) => {
    const uploader = input.closest('[data-vehicle-image-upload]');
    const dropzone = uploader?.querySelector('[data-vehicle-image-dropzone]');
    const status = uploader?.querySelector('[data-vehicle-image-status]');
    if (!dropzone || !status) {
        return;
    }

    const validateFiles = () => {
        const files = [...(input.files ?? [])];
        const oversized = files.find((file) => file.size > 5 * 1024 * 1024);
        if (oversized) {
            input.value = '';
            status.textContent = `${oversized.name} exceeds the 5 MB image size limit. Choose a smaller image.`;
            status.classList.add('font-bold', 'text-red-700');
            return;
        }
        if (files.reduce((total, file) => total + file.size, 0) > 6 * 1024 * 1024) {
            input.value = '';
            status.textContent = 'Combined image upload exceeds the 6 MB limit. Compress or remove images before continuing.';
            status.classList.add('font-bold', 'text-red-700');
            return;
        }
        status.textContent = files.length ? `${files.length} image(s) selected and ready to upload.` : '';
        status.classList.remove('font-bold', 'text-red-700');
    };

    input.addEventListener('change', validateFiles);
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-emerald-700', 'bg-emerald-100');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-emerald-700', 'bg-emerald-100');
        const transfer = new DataTransfer();
        [...(input.files ?? []), ...(event.dataTransfer?.files ?? [])].forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        validateFiles();
    });
});

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
            const time = `${minutes}:${seconds}`;
            countdown.textContent = remaining > 0
                ? (countdown.dataset.expiryTemplate ?? 'This code expires in :time.').replace(':time', time)
                : (countdown.dataset.expiredMessage ?? 'This code has expired. Request a new code to continue.');

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
    const messageList = form.closest('.support-chat-panel')?.querySelector('[data-support-messages]');
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

document.querySelectorAll('[data-support-widget][data-start-url]').forEach((widget) => {
    const messageList = widget.querySelector('[data-widget-messages]');
    const notice = widget.querySelector('[data-support-notice]');
    const errorMessage = widget.querySelector('[data-widget-error]');
    const newChatButton = widget.querySelector('[data-support-new-chat]');
    const form = widget.querySelector('[data-support-form]');
    const input = widget.querySelector('[data-support-input]');
    const sendButton = widget.querySelector('[data-support-send]');
    let conversationId = '';
    let lastMessageId = '';
    let isLoading = false;

    if (!messageList || !notice || !errorMessage || !newChatButton || !form || !input || !sendButton) {
        return;
    }

    const endpoint = (template) => template.replace('__CONVERSATION__', encodeURIComponent(conversationId));
    const showError = (message) => {
        errorMessage.textContent = message;
        errorMessage.hidden = false;
    };
    const formatMessageTime = (value) => {
        const date = new Date(value);
        return Number.isNaN(date.getTime())
            ? ''
            : new Intl.DateTimeFormat(undefined, { dateStyle: 'short', timeStyle: 'short' }).format(date);
    };
    const appendMessage = (message) => {
        if (!message?.id || messageList.querySelector(`[data-widget-message-id="${CSS.escape(message.id)}"]`)) {
            return;
        }

        const article = document.createElement('article');
        article.className = `support-widget__message${message.sender_type === 'customer' ? ' support-widget__message--own' : ''}`;
        article.dataset.widgetMessageId = message.id;
        article.setAttribute('aria-label', message.sender_type === 'customer' ? 'You' : 'Support team');
        article.textContent = message.message;

        if (message.created_at) {
            const time = document.createElement('time');
            time.dateTime = message.created_at;
            time.textContent = formatMessageTime(message.created_at);
            article.append(time);
        }

        messageList.append(article);
        lastMessageId = message.id;
        messageList.scrollTop = messageList.scrollHeight;
    };
    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers ?? {}),
            },
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message ?? 'Support chat could not connect. Please try again.');
        }

        return payload;
    };
    const startConversation = async () => {
        const result = await request(widget.dataset.startUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': widget.dataset.csrfToken,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        });
        conversationId = result.conversation_id;
        if (!conversationId) {
            throw new Error('Support chat could not start. Please try again.');
        }
    };
    const refreshMessages = async () => {
        if (!conversationId || isLoading || document.visibilityState !== 'visible') {
            return;
        }

        isLoading = true;
        try {
            const url = new URL(endpoint(widget.dataset.messagesUrl), window.location.href);
            if (lastMessageId) {
                url.searchParams.set('after', lastMessageId);
            }
            const result = await request(url);
            result.messages.forEach(appendMessage);
            if (errorMessage) {
                errorMessage.hidden = true;
            }
            if (result.status === 'closed') {
                notice.textContent = 'This support conversation is closed. Start a new conversation to continue.';
                conversationId = '';
                newChatButton.hidden = false;
            }
        } catch (error) {
            showError(error.message);
        } finally {
            isLoading = false;
        }
    };
    const initialize = async () => {
        if (conversationId || isLoading) {
            return;
        }

        isLoading = true;
        sendButton.disabled = true;
        try {
            await startConversation();
            const result = await request(endpoint(widget.dataset.messagesUrl));
            result.messages.forEach(appendMessage);
            notice.textContent = 'You are connected to the support conversation. Replies will appear here.';
            if (result.status === 'closed') {
                notice.textContent = 'This support conversation is closed. Start a new conversation to continue.';
                conversationId = '';
                newChatButton.hidden = false;
            }
            errorMessage.hidden = true;
        } catch (error) {
            showError(error.message);
        } finally {
            isLoading = false;
            sendButton.disabled = false;
        }
    };

    widget.addEventListener('toggle', () => {
        if (widget.open) {
            initialize();
        }
    });
    newChatButton.addEventListener('click', () => {
        messageList.replaceChildren();
        lastMessageId = '';
        conversationId = '';
        newChatButton.hidden = true;
        notice.textContent = 'Starting a new private chat...';
        initialize();
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity() || isLoading) {
            return;
        }

        isLoading = true;
        sendButton.disabled = true;
        try {
            if (!conversationId) {
                await startConversation();
            }
            const result = await request(endpoint(widget.dataset.sendUrl), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': widget.dataset.csrfToken,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: input.value }),
            });
            appendMessage(result.message);
            input.value = '';
            errorMessage.hidden = true;
        } catch (error) {
            showError(error.message);
        } finally {
            isLoading = false;
            sendButton.disabled = false;
            input.focus();
        }
    });

    window.setInterval(() => {
        if (widget.open) {
            refreshMessages();
        }
    }, 8000);
});

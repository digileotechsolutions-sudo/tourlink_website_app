const VERSION = 'tourlink-pwa-v2';
const STATIC_CACHE = `${VERSION}-static`;
const PAGE_CACHE = `${VERSION}-public-pages`;
const CACHE_PREFIX = 'tourlink-pwa-';
const FONT_HOSTS = new Set(['fonts.googleapis.com', 'fonts.gstatic.com']);
const IMAGE_HOSTS = new Set(['images.unsplash.com']);
const SHELL = ['/offline.html', '/manifest.json', '/manifest.webmanifest', '/favicon.ico', '/icons/tourlink-192.svg', '/icons/tourlink-512.svg'];
const PRIVATE_PATHS = ['/admin', '/account', '/api', '/dashboard', '/login', '/logout', '/operator', '/password', '/referrals', '/register', '/reset-password', '/traveler', '/vehicle-owner', '/verify', '/forgot-password'];
const STATIC_PATHS = [/^\/build\/assets\//, /^\/icons\//, /^\/storage\/(trips|vehicles|destinations|events|blog)\//, /^\/(favicon\.ico|form-image1\.png|manifest\.json|manifest\.webmanifest|offline\.html)$/];

self.addEventListener('install', (event) => {
	event.waitUntil((async () => {
		const cache = await caches.open(STATIC_CACHE);
		await cache.addAll(SHELL);

		try {
			const response = await fetch('/build/manifest.json', { cache: 'no-store' });
			const manifest = await response.json();
			const assets = [manifest['resources/css/app.css']?.file, manifest['resources/js/app.js']?.file, manifest['resources/js/echo.js']?.file]
				.filter(Boolean)
				.map((asset) => `/build/${asset}`);

			await Promise.allSettled(assets.map((asset) => cache.add(asset)));
		} catch {
			return;
		}
	})());
});

self.addEventListener('activate', (event) => {
	event.waitUntil((async () => {
		const keys = await caches.keys();
		const obsoleteCaches = keys.filter((key) => (
			(key.startsWith(CACHE_PREFIX) && !key.startsWith(VERSION)) || key === 'tourlink-shell-v1'
		));
		await Promise.all(obsoleteCaches.map((key) => caches.delete(key)));
		await self.clients.claim();
		if (self.registration.navigationPreload) {
			await self.registration.navigationPreload.enable();
		}
	})());
});

self.addEventListener('message', (event) => {
	if (event.data?.type === 'SKIP_WAITING') {
		self.skipWaiting();
	} else if (event.data?.type === 'SYNC_OUTBOX') {
		event.waitUntil(syncOutbox());
	}
});

self.addEventListener('sync', (event) => {
	if (event.tag === 'tourlink-outbox') {
		event.waitUntil(syncOutbox());
	}
});

self.addEventListener('fetch', (event) => {
	const request = event.request;
	const url = new URL(request.url);

	if (request.method !== 'GET') {
		return;
	}

	if (url.origin !== self.location.origin) {
		if ((FONT_HOSTS.has(url.hostname) && (request.destination === 'font' || url.hostname === 'fonts.googleapis.com'))
			|| (IMAGE_HOSTS.has(url.hostname) && request.destination === 'image')) {
			event.respondWith(cacheFirst(request, true));
		}
		return;
	}

	if (request.mode === 'navigate') {
		event.respondWith(navigate(request, url));
	} else if (STATIC_PATHS.some((path) => path.test(url.pathname))) {
		event.respondWith(cacheFirst(request));
	}
});

async function navigate(request, url) {
	const privatePath = PRIVATE_PATHS.some((path) => url.pathname === path || url.pathname.startsWith(`${path}/`));

	try {
		const response = await fetch(request);

		if (!privatePath && !url.search && response.ok && response.headers.get('X-PWA-Cacheable') === 'public') {
			const cache = await caches.open(PAGE_CACHE);
			await cache.put(request, response.clone());
			await trimCache(cache, 40);
		}

		return response;
	} catch {
		if (!privatePath) {
			const cachedPage = await caches.open(PAGE_CACHE).then((cache) => cache.match(request));

			if (cachedPage) {
				return cachedPage;
			}
		}

		return caches.open(STATIC_CACHE).then((cache) => cache.match('/offline.html'));
	}
}

async function cacheFirst(request, allowOpaque = false) {
	const cache = await caches.open(STATIC_CACHE);
	const cached = await cache.match(request);

	if (cached) {
		return cached;
	}

	const response = await fetch(request);

	if (response.ok && (allowOpaque || response.type !== 'opaque')) {
		await cache.put(request, response.clone());
		await trimCache(cache, 120);
	}

	return response;
}

async function trimCache(cache, maxEntries) {
	const keys = await cache.keys();

	for (const key of keys.slice(0, Math.max(0, keys.length - maxEntries))) {
		await cache.delete(key);
	}
}

function openOutbox() {
	return new Promise((resolve, reject) => {
		const request = indexedDB.open('tourlink-offline', 1);

		request.onupgradeneeded = () => {
			if (!request.result.objectStoreNames.contains('actions')) {
				request.result.createObjectStore('actions', { keyPath: 'id' });
			}
		};
		request.onsuccess = () => resolve(request.result);
		request.onerror = () => reject(request.error);
	});
}

async function syncOutbox() {
	const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
	const notify = (type, detail = {}) => clients.forEach((client) => client.postMessage({ type, ...detail }));

	try {
		const database = await openOutbox();
		const actions = await new Promise((resolve, reject) => {
			const request = database.transaction('actions').objectStore('actions').getAll();
			request.onsuccess = () => resolve(request.result);
			request.onerror = () => reject(request.error);
		});

		if (actions.length === 0) {
			notify('OUTBOX_SYNCED');
			return;
		}

		const csrfResponse = await fetch('/pwa/csrf', { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });

		if (!csrfResponse.ok || !csrfResponse.headers.get('content-type')?.includes('application/json')) {
			notify('OUTBOX_PENDING');
			return;
		}

		const { token, user_id: userId } = await csrfResponse.json();
		let hasPending = false;
		let syncedCount = 0;

		for (const action of actions) {
			if (action.userId !== userId) {
				hasPending = true;
				continue;
			}

			const response = await fetch(action.url, {
				method: 'POST',
				credentials: 'same-origin',
				cache: 'no-store',
				headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
				body: JSON.stringify({ trip_id: action.tripId, expected_user_id: action.userId }),
			});

			if (!response.ok) {
				hasPending = true;
				continue;
			}

			await new Promise((resolve, reject) => {
				const transaction = database.transaction('actions', 'readwrite');
				transaction.objectStore('actions').delete(action.id);
				transaction.oncomplete = resolve;
				transaction.onerror = () => reject(transaction.error);
			});
			syncedCount += 1;
		}

		notify(hasPending ? 'OUTBOX_PENDING' : 'OUTBOX_SYNCED', { syncedCount });
	} catch {
		notify('OUTBOX_PENDING');
		throw new Error('TourLink offline actions could not be synchronized.');
	}
}

/* Service worker — cache aset statis saja.
 *
 * Halaman HTML sengaja TIDAK di-cache: isinya data tagihan dan nilai yang
 * berubah terus, dan menyajikan versi lama lebih berbahaya daripada
 * menampilkan pesan offline. Yang di-cache hanya berkas yang namanya
 * sudah ber-hash dari Vite, jadi tidak pernah basi.
 */
const VERSI  = 'tahfizh-v1';
const OFFLINE = '/offline.html';

const ASET_AWAL = [
    OFFLINE,
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(VERSI)
            .then((c) => c.addAll(ASET_AWAL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    // Buang cache versi lama supaya storage tidak menumpuk tiap rilis.
    e.waitUntil(
        caches.keys()
            .then((k) => Promise.all(k.filter((n) => n !== VERSI).map((n) => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;

    // Hanya GET. POST/PUT/DELETE harus selalu ke server.
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Lewati domain lain (CDN font, WhatsApp, dsb).
    if (url.origin !== self.location.origin) return;

    // Jangan sentuh storage & PDF — berkasnya besar dan bisa berubah.
    if (url.pathname.startsWith('/storage/')) return;

    const asetStatis = url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || /\.(js|css|woff2?|png|jpe?g|svg|webp)$/i.test(url.pathname);

    if (asetStatis) {
        // Cache-first: berkas Vite ber-hash, jadi aman disimpan selamanya.
        e.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                if (res.ok) {
                    const salinan = res.clone();
                    caches.open(VERSI).then((c) => c.put(req, salinan));
                }
                return res;
            }).catch(() => hit))
        );
        return;
    }

    // Navigasi halaman: selalu ke jaringan. Kalau gagal, tampilkan
    // halaman offline — bukan data lama yang menyesatkan.
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
    }
});
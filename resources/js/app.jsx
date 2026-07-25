import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

/**
 * Jaring pengaman response non-Inertia.
 *
 * Kalau server membalas dengan status yang bukan response Inertia, klien akan
 * memunculkan modal error gelap ("419 PAGE EXPIRED" / "403 FORBIDDEN") di atas
 * halaman. Semua itu ditangani manual di sini:
 *
 * - 401 / 403 / 419 → sesi sudah mati (sering terjadi karena request yang masih
 *   berjalan saat logout). Paksa full page load ke login.
 * - 413            → payload melebihi post_max_size, sesi masih hidup.
 *                    Cukup beri tahu, JANGAN redirect.
 */
router.on('invalid', (event) => {
    const status = event.detail.response?.status;

    if ([401, 403, 419].includes(status)) {
        event.preventDefault();
        window.location.href = '/login';
        return;
    }

    if (status === 413) {
        event.preventDefault();
        alert('Data yang dikirim terlalu besar (rekaman/PDF). Coba rekam lebih pendek.');
    }
});

createInertiaApp({
    title: (title) => `${title} - Laporan Tahfizh`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#D97757', // terracotta, samakan dengan palet claude
    },
});
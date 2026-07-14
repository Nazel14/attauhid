import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

/**
 * Jaring pengaman 419.
 * Kalau sesi benar-benar sudah mati, tidak ada gunanya menampilkan halaman
 * "PAGE EXPIRED" — langsung lempar ke login dengan pesan yang jelas.
 */
router.on('invalid', (event) => {
    const status = event.detail.response?.status;

    if (status === 419) {
        event.preventDefault();
        alert('Sesi Anda telah berakhir. Anda akan diarahkan ke halaman login.');
        window.location.href = '/login';
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
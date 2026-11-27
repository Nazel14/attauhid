import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';

/**
 * LaporanNotifier
 *
 * Memantau prop bersama `unreadLaporan` (dishare dari HandleInertiaRequests).
 * Tiga hal yang dikerjakan:
 *   1. Polling ringan tiap 30 detik — HANYA prop unreadLaporan yang diambil
 *      ulang (partial reload), bukan seluruh halaman.
 *   2. Toast saat jumlah unread NAIK, supaya user tahu tanpa harus membuka
 *      halaman laporan.
 *   3. Badge angka di judul tab browser, mis. "(3) Dashboard - Laporan Tahfizh".
 *
 * Props:
 * - href: tujuan saat toast diklik (route index laporan sesuai role).
 */
export default function LaporanNotifier({ href }) {
    const { props, url } = usePage();
    const [unread, setUnread] = useState(props.unreadLaporan ?? 0);

    const sebelumnya = useRef(unread);
    const [toast, setToast] = useState(0);

    // Sinkron saat navigasi antar halaman (prop server terbaru menang)
    useEffect(() => {
        setUnread(props.unreadLaporan ?? 0);
    }, [props.unreadLaporan]);

    // --- 1. Polling via fetch murni (BUKAN Inertia visit) ---
    useEffect(() => {
        const poll = async () => {
            if (document.hidden) return; // tab tidak aktif → skip, hemat server
            try {
                const res = await fetch('/api/laporan/unread', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return; // 419/401/500 → diamkan saja, JANGAN reload
                const data = await res.json();
                if (typeof data.unread === 'number') setUnread(data.unread);
            } catch (e) {
                // network error → diamkan, coba lagi 30 detik berikutnya
            }
        };

        const timer = setInterval(poll, 30000);
        return () => clearInterval(timer);
    }, []);

    // --- 2. Toast saat ada pesan baru ---
    useEffect(() => {
        const naik = unread > sebelumnya.current;
        sebelumnya.current = unread;

        if (!naik) return;

        // Kalau user memang sedang membuka halaman laporan, tidak perlu diganggu.
        try {
            if (route().current('*.laporan.*')) return;
        } catch (e) {}

        setToast(unread);
        const timer = setTimeout(() => setToast(0), 8000);
        return () => clearTimeout(timer);
    }, [unread]);

    // --- 3. Badge di judul tab ---
    useEffect(() => {
        const bersih = document.title.replace(/^\(\d+\)\s*/, '');
        document.title = unread > 0 ? `(${unread}) ${bersih}` : bersih;
    }, [unread, url]);

    if (!toast || !href) return null;

    // Posisi & gaya disamakan dengan toast lain di aplikasi (kanan atas,
    // kartu dengan bayangan). Animasi memakai @keyframes sendiri: kelas
    // animate-in/fade-in sebelumnya milik plugin tailwindcss-animate yang
    // tidak terpasang, jadi toast muncul tanpa gerakan apa pun.
    return (
        <>
            <div
                className="fixed left-4 right-4 top-4 z-[290] sm:left-auto sm:right-4 sm:w-96"
                style={{ animation: 'notifLaporanIn .3s cubic-bezier(.21,1.02,.73,1)' }}
                role="status"
            >
                <Link
                    href={href}
                    onClick={() => setToast(0)}
                    className="flex items-start gap-3 rounded-2xl border border-cream-300 bg-white/95 p-3.5 shadow-2xl backdrop-blur-md transition hover:border-claude-500/40 dark:border-coal-600 dark:bg-coal-700/95 dark:hover:border-claude-500/40"
                >
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-claude-100 text-claude-600 dark:bg-claude-500/20 dark:text-claude-300">
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </span>
                    <div className="min-w-0 flex-1 pt-0.5">
                        <p className="text-xs font-bold text-claude-700 dark:text-claude-300">
                            Pesan laporan baru
                        </p>
                        <p className="mt-0.5 text-sm text-stone-600 dark:text-stone-300">
                            {toast} pesan belum dibaca · ketuk untuk membuka
                        </p>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup notifikasi"
                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setToast(0); }}
                        className="shrink-0 rounded-lg p-1 text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-200"
                    >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </Link>
            </div>
            <style>{`
                @keyframes notifLaporanIn { from { opacity: 0; transform: translateY(-14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
                @media (prefers-reduced-motion: reduce) { [style*="notifLaporanIn"] { animation: none !important; } }
            `}</style>
        </>
    );
}
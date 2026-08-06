import { Link } from '@inertiajs/react';

// Tombol kembali khusus mobile. Route dicek dulu lewat route().has()
// supaya halaman tidak crash bila modul dashboard belum terdaftar.
export default function TombolKembali({ href, label = 'Kembali ke Dashboard', className = '' }) {
    const tujuan = href
        ?? (typeof route === 'function' && route().has('keuangan.dashboard')
            ? route('keuangan.dashboard')
            : '/keuangan/dashboard');

    return (
        <Link
            href={tujuan}
            className={`mb-4 inline-flex items-center gap-1.5 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-stone-600 shadow-sm transition hover:bg-cream-100 active:scale-95 sm:hidden dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:bg-coal-600 ${className}`}
        >
            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {label}
        </Link>
    );
}
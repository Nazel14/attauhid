import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';

/**
 * LogoutButton
 *
 * Tombol keluar dengan konfirmasi. API-nya tidak berubah: tetap menerima
 * `title`, `className`, dan children (ikon), sehingga pemanggilan di
 * AuthenticatedLayout / GuruAuthenticatedLayout / SantriAuthenticatedLayout
 * tidak perlu disentuh.
 *
 * Dialognya dirender lewat portal ke document.body. Ini bukan pilihan gaya:
 * <aside> sidebar memakai class translate-x-*, dan elemen ber-transform
 * menjadi containing block untuk position:fixed — tanpa portal, modal akan
 * terkurung di dalam sidebar selebar 256px.
 */
export default function LogoutButton({ title = 'Keluar', className = '', children }) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (!open) return;

        const onKey = (e) => {
            if (e.key === 'Escape' && !processing) setOpen(false);
        };

        document.addEventListener('keydown', onKey);
        document.body.classList.add('overflow-hidden');

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.classList.remove('overflow-hidden');
        };
    }, [open, processing]);

    const keluar = () => {
        setProcessing(true);
        router.post(route('logout'), {}, {
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <>
            <button type="button" title={title} onClick={() => setOpen(true)} className={className}>
                {children}
            </button>

            {open && createPortal(
                <div className="fixed inset-0 z-[250] flex items-end justify-center p-0 sm:items-center sm:p-4">
                    <div
                        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        onClick={() => !processing && setOpen(false)}
                    />

                    <div
                        role="alertdialog"
                        aria-modal="true"
                        className="relative w-full overflow-hidden rounded-t-3xl bg-white shadow-2xl ring-1 ring-stone-200 sm:max-w-sm sm:rounded-3xl dark:bg-coal-700 dark:ring-coal-600"
                        style={{ animation: 'logoutIn .22s cubic-bezier(.21,1.02,.73,1)' }}
                    >
                        <div className="mx-auto mt-3 h-1 w-10 rounded-full bg-stone-300 sm:hidden dark:bg-coal-500" />

                        <div className="p-6 text-center">
                            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                                <svg className="h-7 w-7" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </div>

                            <h3 className="mt-4 text-base font-bold text-stone-900 dark:text-cream-50">
                                Keluar dari akun?
                            </h3>
                            <p className="mt-1.5 text-sm text-stone-500 dark:text-stone-400">
                                Sesi Anda akan diakhiri dan Anda perlu login kembali untuk mengakses sistem.
                            </p>

                            <div className="mt-6 flex gap-3">
                                <button
                                    type="button"
                                    onClick={() => setOpen(false)}
                                    disabled={processing}
                                    className="flex-1 rounded-xl border border-stone-200 bg-stone-100 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 disabled:opacity-50 dark:border-coal-500 dark:bg-coal-600 dark:text-stone-300 dark:hover:bg-coal-500"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    onClick={keluar}
                                    disabled={processing}
                                    autoFocus
                                    className="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-500 disabled:opacity-50"
                                >
                                    {processing ? 'Keluar…' : 'Ya, Keluar'}
                                </button>
                            </div>
                        </div>
                    </div>

                    <style>{`
                        @keyframes logoutIn { from { opacity: 0; transform: translateY(16px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
                    `}</style>
                </div>,
                document.body
            )}
        </>
    );
}
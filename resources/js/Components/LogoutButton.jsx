import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { router, usePage } from '@inertiajs/react';

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
 *
 * DESAIN
 * - HP: bottom sheet (menempel di bawah, ada pegangan), tombol bertumpuk
 *   selebar layar supaya mudah dijangkau jempol.
 * - Desktop: kartu di tengah, tombol berdampingan.
 * - Kartu akun di atas menunjukkan SIAPA yang akan keluar — berguna kalau
 *   satu HP dipakai bergantian oleh beberapa guru.
 * - Fokus awal di "Batal", bukan "Keluar": tombol Enter yang tak sengaja
 *   tertekan tidak langsung mengeluarkan pengguna.
 */
export default function LogoutButton({ title = 'Keluar', className = '', children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const nama = user?.display_name || user?.name || '';
    const email = user?.email || '';

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

    const tutup = () => !processing && setOpen(false);

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
                <div className="fixed inset-0 z-[250] flex items-end justify-center sm:items-center sm:p-4">
                    {/* Latar */}
                    <div
                        className="absolute inset-0 bg-stone-950/45 backdrop-blur-[3px]"
                        style={{ animation: 'logoutFade .2s ease-out' }}
                        onClick={tutup}
                    />

                    {/* Panel */}
                    <div
                        role="alertdialog"
                        aria-modal="true"
                        aria-labelledby="logout-judul"
                        aria-describedby="logout-teks"
                        className="relative w-full overflow-hidden rounded-t-[28px] bg-white shadow-[0_-12px_40px_-12px_rgba(0,0,0,.25)] ring-1 ring-stone-200/80 sm:max-w-[380px] sm:rounded-[28px] sm:shadow-2xl dark:bg-coal-700 dark:ring-coal-600"
                        style={{ animation: 'logoutIn .26s cubic-bezier(.21,1.02,.73,1)' }}
                    >
                        {/* Pegangan bottom sheet (HP saja) */}
                        <div className="flex justify-center pt-3 sm:hidden">
                            <span className="h-1 w-10 rounded-full bg-stone-300 dark:bg-coal-500" />
                        </div>

                        {/* Tombol tutup (desktop) */}
                        <button
                            type="button"
                            onClick={tutup}
                            disabled={processing}
                            aria-label="Tutup"
                            className="absolute right-4 top-4 hidden h-8 w-8 items-center justify-center rounded-full text-stone-400 transition hover:bg-stone-100 hover:text-stone-600 disabled:opacity-40 sm:flex dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div className="px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-5 sm:px-7 sm:pb-7 sm:pt-7">
                            {/* Ikon */}
                            <div className="relative mx-auto h-16 w-16">
                                <span className="absolute inset-0 rounded-full bg-red-500/10 dark:bg-red-500/15" style={{ animation: 'logoutPulse 2.4s ease-out infinite' }} />
                                <span className="relative flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-red-50 to-red-100 text-red-600 ring-1 ring-red-200/70 dark:from-red-500/15 dark:to-red-500/5 dark:text-red-400 dark:ring-red-500/25">
                                    <svg className="h-7 w-7" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                    </svg>
                                </span>
                            </div>

                            <h3 id="logout-judul" className="mt-4 text-center text-[17px] font-bold tracking-tight text-stone-900 dark:text-cream-50">
                                Keluar dari akun?
                            </h3>
                            <p id="logout-teks" className="mx-auto mt-1.5 max-w-[280px] text-center text-[13px] leading-relaxed text-stone-500 dark:text-stone-400">
                                Sesi Anda akan diakhiri. Untuk membuka sistem lagi, Anda perlu login kembali.
                            </p>

                            {/* Kartu akun */}
                            {nama && (
                                <div className="mt-5 flex items-center gap-3 rounded-2xl bg-cream-100 p-3 ring-1 ring-cream-300 dark:bg-coal-600/50 dark:ring-coal-600">
                                    {user?.profile_photo ? (
                                        <img
                                            src={`/storage/${user.profile_photo}`}
                                            alt=""
                                            className="h-10 w-10 flex-shrink-0 rounded-full object-cover object-top ring-2 ring-white dark:ring-coal-700"
                                        />
                                    ) : (
                                        <span className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 ring-2 ring-white dark:bg-claude-500/20 dark:text-claude-300 dark:ring-coal-700">
                                            {nama.charAt(0).toUpperCase()}
                                        </span>
                                    )}
                                    <div className="min-w-0 flex-1 text-left">
                                        <p className="break-words text-[13px] font-semibold leading-snug text-stone-800 dark:text-cream-50">{nama}</p>
                                        {email && (
                                            <p className="mt-0.5 break-all text-[11px] text-stone-400 dark:text-stone-500">{email}</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Aksi — HP: bertumpuk, Batal paling bawah (pola bottom sheet iOS/Android);
                                desktop: berdampingan (Batal kiri, Keluar kanan). */}
                            <div className="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:gap-3">
                                <button
                                    type="button"
                                    onClick={tutup}
                                    disabled={processing}
                                    autoFocus
                                    className="flex-1 rounded-2xl bg-stone-100 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-stone-400 disabled:opacity-50 dark:bg-coal-600 dark:text-stone-200 dark:hover:bg-coal-500"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    onClick={keluar}
                                    disabled={processing}
                                    className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-red-500 to-red-600 py-3 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:from-red-500 hover:to-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 active:scale-[.98] disabled:opacity-70 dark:focus-visible:ring-offset-coal-700"
                                >
                                    {processing ? (
                                        <>
                                            <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
                                                <path className="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                                            </svg>
                                            Keluar…
                                        </>
                                    ) : (
                                        'Ya, Keluar'
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>

                    <style>{`
                        @keyframes logoutIn { from { opacity: 0; transform: translateY(24px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
                        @keyframes logoutFade { from { opacity: 0; } to { opacity: 1; } }
                        @keyframes logoutPulse { 0% { transform: scale(1); opacity: .9; } 70%, 100% { transform: scale(1.45); opacity: 0; } }
                        @media (prefers-reduced-motion: reduce) {
                            [role="alertdialog"], [role="alertdialog"] * { animation: none !important; }
                        }
                    `}</style>
                </div>,
                document.body
            )}
        </>
    );
}
import { Head, Link } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const HistoryIcon  = (p) => <I {...p} d={<><path d="M3 3v5h5" /><path d="M3.05 13A9 9 0 106 5.3L3 8" /><path d="M12 7v5l3 2" /></>} />;
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />} />;
const ChevR        = (p) => <I {...p} d={<path d="M9 5l7 7-7 7" />} />;
const ArrowLeftIcon= (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;


export default function Index({ ringkasan = [] }) {
    return (
        <GuruAuthenticatedLayout>
            <Head title="Riwayat Santri" />

            <div className="max-w-5xl mx-auto px-3 sm:px-4 py-5">

                {/* Kembali — mobile saja */}
                <Link href={route('guru.dashboard')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl ring-1 ring-stone-200 dark:ring-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-claude-500 flex items-center justify-center flex-shrink-0 shadow-sm shadow-claude-500/30">
                        <HistoryIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Riwayat Santri</h1>
                        <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Santri yang naik/pindah, dikelompokkan per tahun ajaran</p>
                    </div>
                </div>

                {/* ===== DAFTAR TAHUN AJARAN ===== */}
                {ringkasan.length ? (
                    <div className="space-y-2.5">
                        {ringkasan.map((r, i) => {
                            const clickable = !!r.tahun_ajaran_id;
                            const inner = (
                                <>
                                    <span className="w-11 h-11 rounded-xl bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 flex items-center justify-center text-stone-500 dark:text-stone-400 flex-shrink-0">
                                        <CalendarIcon className="w-5 h-5" />
                                    </span>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-bold tracking-tight text-stone-900 dark:text-stone-100">
                                            {r.nama}
                                            {r.semester && <span className="text-[10px] font-medium text-stone-400 dark:text-stone-500 ml-1.5">· {r.semester === 2 ? 'Genap' : 'Ganjil'}</span>}
                                        </p>
                                        <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">
                                            <span className="font-semibold text-claude-600 dark:text-claude-400">{r.jumlah} santri</span> naik/pindah
                                            {r.terakhir && <span className="text-stone-400 dark:text-stone-500"> · terakhir {r.terakhir}</span>}
                                        </p>
                                    </div>
                                    {clickable && <ChevR className="w-4 h-4 text-stone-400 dark:text-stone-500 flex-shrink-0" />}
                                </>
                            );

                            return clickable ? (
                                <Link key={i} href={route('guru.riwayat-santri.detail', r.tahun_ajaran_id)}
                                    className="flex items-center gap-3 px-4 py-3.5 bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl hover:ring-claude-300 dark:hover:ring-claude-500/40 transition-shadow">
                                    {inner}
                                </Link>
                            ) : (
                                <div key={i} className="flex items-center gap-3 px-4 py-3.5 bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl opacity-70">
                                    {inner}
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl py-16 text-center">
                        <span className="inline-flex w-12 h-12 rounded-2xl bg-stone-100 dark:bg-coal-700 items-center justify-center text-stone-400 mb-3">
                            <HistoryIcon className="w-6 h-6" />
                        </span>
                        <p className="text-sm font-semibold text-stone-600 dark:text-stone-300">Belum ada riwayat santri</p>
                        <p className="text-xs text-stone-400 dark:text-stone-500 mt-1.5">Santri yang Anda naikkan/pindahkan akan terkelompok per tahun ajaran di sini.</p>
                    </div>
                )}
            </div>
        </GuruAuthenticatedLayout>
    );
}
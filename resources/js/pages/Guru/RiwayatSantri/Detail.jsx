import { Head, Link } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />} />;
const InfoIcon     = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const RapotIcon    = (p) => <I {...p} d={<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const ArrowLeftIcon= (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;

const initial = (name) => (name || '?').charAt(0).toUpperCase();
const isFemale = (g) => g === 'P' || g === 'Perempuan';

export default function Detail({ tahunAjaran, santriAlumni = [] }) {
    return (
        <GuruAuthenticatedLayout>
            <Head title={`Riwayat Santri — ${tahunAjaran?.nama ?? ''}`} />

            <div className="max-w-5xl mx-auto px-3 sm:px-4 py-5">

                {/* Kembali — mobile */}
                <Link href={route('guru.riwayat-santri')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl ring-1 ring-stone-200 dark:ring-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Riwayat Santri
                </Link>
                {/* Kembali — desktop */}
                <Link href={route('guru.riwayat-santri')}
                    className="mb-4 hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl ring-1 ring-stone-200 dark:ring-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Riwayat Santri
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-4">
                    <div className="w-11 h-11 rounded-2xl bg-claude-500 flex items-center justify-center flex-shrink-0 shadow-sm shadow-claude-500/30">
                        <CalendarIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">
                            {tahunAjaran?.nama ?? 'Tanpa Tahun Ajaran'}
                            {tahunAjaran?.semester && <span className="text-xs font-medium text-stone-400 dark:text-stone-500 ml-1.5">· {tahunAjaran.semester === 2 ? 'Genap' : 'Ganjil'}</span>}
                        </h1>
                        <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">
                            Santri naik/pindah: <span className="text-claude-600 dark:text-claude-400 font-semibold">{santriAlumni.length}</span>
                        </p>
                    </div>
                </div>

                {/* ===== INFO ===== */}
                <div className="flex items-start gap-2.5 rounded-2xl bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-100 dark:ring-claude-500/20 p-3 mb-4">
                    <InfoIcon className="w-4 h-4 text-claude-600 dark:text-claude-400 flex-shrink-0 mt-0.5" />
                    <p className="text-xs text-stone-600 dark:text-stone-300">Anda tetap dapat melihat rapot mereka. Data dikunci pada periode saat mereka masih di kelas Anda.</p>
                </div>

                {/* ===== DAFTAR SANTRI ===== */}
                {santriAlumni.length ? (
                    <div className="space-y-2.5">
                        {santriAlumni.map((s) => (
                            <div key={s.id} className="flex items-center gap-3 px-4 py-3 bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl">
                                {s.photo_url
                                    ? <img src={s.photo_url} alt="" className="w-10 h-10 rounded-full object-cover object-top ring-1 ring-stone-200 dark:ring-coal-700 flex-shrink-0" />
                                    : <span className={`w-10 h-10 rounded-full ring-1 flex items-center justify-center text-sm font-bold flex-shrink-0 ${isFemale(s.gender) ? 'bg-pink-100 dark:bg-pink-950/60 ring-pink-200 dark:ring-pink-800/60 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950/60 ring-blue-200 dark:ring-blue-800/60 text-blue-600 dark:text-blue-400'}`}>{initial(s.name)}</span>}

                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{s.name}</p>
                                    <p className="text-[11px] text-stone-400 dark:text-stone-500 font-mono">{s.nis ?? '-'}</p>
                                    <div className="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">Sekarang: {s.kelas_now}</span>
                                        {s.tanggal_keluar && <span className="text-[10px] text-stone-400 dark:text-stone-500">Keluar {s.tanggal_keluar}</span>}
                                    </div>
                                </div>

                                <Link href={s.rapot_url} className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors active:scale-95 flex-shrink-0">
                                    <RapotIcon className="w-3.5 h-3.5" /> Rapot
                                </Link>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl py-16 text-center">
                        <p className="text-sm font-semibold text-stone-600 dark:text-stone-300">Tidak ada santri pada tahun ajaran ini</p>
                    </div>
                )}
            </div>
        </GuruAuthenticatedLayout>
    );
}
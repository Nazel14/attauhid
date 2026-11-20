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
const UsersIcon    = (p) => <I {...p} d={<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />} />;
const ArrowLeftIcon= (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;

/* ============================================================
 * TOKEN DESAIN — sama dengan Dashboard
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const AVATAR = {
    P: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400',
    L: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400',
};

const initial = (name) => (name || '?').charAt(0).toUpperCase();
const isFemale = (g) => g === 'P' || g === 'Perempuan';

export default function Detail({ tahunAjaran, santriAlumni = [] }) {
    return (
        <GuruAuthenticatedLayout>
            <Head title={`Riwayat Santri — ${tahunAjaran?.nama ?? ''}`} />

            {/* Padding dibuang — <main> di layout sudah memberinya (sama seperti Dashboard). */}
            <div className="max-w-5xl mx-auto">

                <Link href={route('guru.riwayat-santri')} className={`${BTN_SOFT} mb-4`}>
                    <ArrowLeftIcon className="w-4 h-4" /> Riwayat Santri
                </Link>

                {/* ===== HEADER (sama dengan Dashboard) ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0 text-stone-600 dark:text-stone-300">
                        <CalendarIcon className="w-6 h-6" />
                    </div>
                    <div className="min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">
                            {tahunAjaran?.nama ?? 'Tanpa Tahun Ajaran'}
                            {tahunAjaran?.semester && <span className="text-xs font-medium text-stone-400 ml-1.5">· {tahunAjaran.semester === 2 ? 'Genap' : 'Ganjil'}</span>}
                        </h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            Santri naik/pindah <span className="font-semibold text-claude-600 dark:text-claude-400">{santriAlumni.length} santri</span>
                        </p>
                    </div>
                </div>

                {/* ===== INFO ===== */}
                <div className="flex items-start gap-2.5 rounded-xl bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-200 dark:ring-claude-500/20 px-3.5 py-3 mb-4">
                    <InfoIcon className="w-4 h-4 text-claude-600 dark:text-claude-400 flex-shrink-0 mt-0.5" />
                    <p className="text-xs leading-relaxed text-stone-700 dark:text-stone-300">Anda tetap dapat melihat rapot mereka. Data dikunci pada periode saat mereka masih di kelas Anda.</p>
                </div>

                {/* ===== DAFTAR SANTRI ===== */}
                {santriAlumni.length ? (
                    <div className={`${CARD} p-4 sm:p-5`}>
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0">
                                <UsersIcon className="w-3.5 h-3.5" />
                            </span>
                            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Daftar Santri</h2>
                            <span className="ml-auto text-[11px] text-stone-400">{santriAlumni.length} santri</span>
                        </div>
                        <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden divide-y divide-cream-200 dark:divide-coal-600/60">
                            {santriAlumni.map((s) => (
                                <div key={s.id} className="flex items-center gap-3 px-3.5 py-3 hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors">
                                    {s.photo_url
                                        ? <img src={s.photo_url} alt="" className="w-10 h-10 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0" />
                                        : <span className={`w-10 h-10 rounded-full ring-1 flex items-center justify-center text-sm font-bold flex-shrink-0 ${isFemale(s.gender) ? AVATAR.P : AVATAR.L}`}>{initial(s.name)}</span>}

                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold tracking-tight text-stone-800 dark:text-stone-100 truncate">{s.name}</p>
                                        <div className="flex flex-wrap items-center gap-1.5 mt-0.5">
                                            <span className="text-[11px] text-stone-400 font-mono">{s.nis ?? '-'}</span>
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-md ring-1 text-[10px] font-semibold bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 text-stone-600 dark:text-stone-300">
                                                Sekarang: {s.kelas_now}
                                            </span>
                                            {s.tanggal_keluar && <span className="text-[11px] text-stone-400">Keluar {s.tanggal_keluar}</span>}
                                        </div>
                                    </div>

                                    <Link href={s.rapot_url} className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg ring-1 bg-claude-100 dark:bg-claude-500/15 ring-claude-200 dark:ring-claude-500/20 hover:bg-claude-200 dark:hover:bg-claude-500/25 text-claude-700 dark:text-claude-400 text-xs font-semibold transition active:scale-95 flex-shrink-0">
                                        <RapotIcon className="w-3.5 h-3.5" /> Rapot
                                    </Link>
                                </div>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className={`${CARD} py-14 flex flex-col items-center text-center`}>
                        <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                            <UsersIcon className="w-5 h-5" />
                        </span>
                        <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">Tidak ada santri pada tahun ajaran ini</p>
                    </div>
                )}
            </div>
        </GuruAuthenticatedLayout>
    );
}
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/* ============================================================
 * ICONS
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />} />;
const BookIcon = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;

const storageUrl = (path) => (path ? `/storage/${path}` : null);
const fmtDateLabel = (isoStr) => {
    const d = new Date(isoStr + 'T00:00:00');
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
};

const progressBarColor = (p) => {
    if (p >= 80) return 'bg-emerald-500';
    if (p >= 50) return 'bg-blue-500';
    if (p >= 30) return 'bg-amber-500';
    return 'bg-red-500';
};
const progressTextColor = (p) => {
    if (p >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (p >= 50) return 'text-blue-600 dark:text-blue-400';
    if (p >= 30) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
};

function TargetChip({ s }) {
    if (s.is_iqro) {
    return (
        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30">
            {s.metode_label ?? 'Iqro'} Jilid {s.target_jilid ?? 1}
        </span>
    );
}
    return (
        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">
            {Number(s.target_juz).toFixed(2)} Juz
        </span>
    );
}

export default function KelasSantri({ kelas, santriList = [], tanggalMulai, tanggalAkhir, period = '30' }) {
    const query = `?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}&period=${period}`;

    const total = santriList.length;
    const ikhwan = santriList.filter((s) => s.gender === 'Laki-laki').length;
    const akhwat = santriList.filter((s) => s.gender === 'Perempuan').length;

    return (
        <AuthenticatedLayout>
            <Head title={`Monitoring - ${kelas.name}`} />

            <div className="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 py-6">

                {/* Kembali — mobile & desktop */}
                <Link href={route('admin.monitoring.index')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Monitoring
                </Link>
                <Link href={route('admin.monitoring.index')}
                    className="mb-4 hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Monitoring
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <BookIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 truncate">{kelas.name}</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            Guru: <span className="text-claude-600 dark:text-claude-400 font-semibold">{kelas.guru_name || 'Belum ada guru'}</span>
                            {' · '}<span className="text-stone-900 dark:text-stone-100 font-semibold">{total}</span> Santri
                        </p>
                    </div>
                </div>

                

                {/* ===== SUMMARY STATS ===== */}
                <div className="grid grid-cols-3 gap-3 mb-5">
                    <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl px-4 py-4 text-center shadow-sm dark:shadow-none">
                        <p className="text-[11px] text-stone-500 mb-1">Total Santri</p>
                        <p className="text-2xl font-black text-stone-900 dark:text-stone-100">{total}</p>
                    </div>
                    <div className="bg-white dark:bg-coal-800 border border-blue-200 dark:border-blue-800/30 rounded-2xl px-4 py-4 text-center shadow-sm dark:shadow-none">
                        <p className="text-[11px] text-stone-500 mb-1">Ikhwan</p>
                        <p className="text-2xl font-black text-blue-600 dark:text-blue-400">{ikhwan}</p>
                    </div>
                    <div className="bg-white dark:bg-coal-800 border border-pink-200 dark:border-pink-800/30 rounded-2xl px-4 py-4 text-center shadow-sm dark:shadow-none">
                        <p className="text-[11px] text-stone-500 mb-1">Akhwat</p>
                        <p className="text-2xl font-black text-pink-600 dark:text-pink-400">{akhwat}</p>
                    </div>
                </div>

                {/* ===== SANTRI TABLE ===== */}
                <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                    <div className="px-5 py-3.5 border-b border-stone-200 dark:border-coal-700">
                        <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">Daftar Santri</h3>
                    </div>

                    {/* MOBILE CARDS */}
                    <div className="lg:hidden divide-y divide-stone-100 dark:divide-coal-700">
                        {santriList.length === 0 ? (
                            <div className="py-12 text-center"><p className="text-sm text-stone-500">Tidak ada santri di kelas ini</p></div>
                        ) : santriList.map((s) => {
                            const isPerempuan = s.gender === 'Perempuan';
                            const pct = Math.min(s.progress_percent, 100);
                            return (
                                <div key={s.id} className="px-4 py-3.5">
                                    <div className="flex items-center gap-3 mb-2.5">
                                        {s.photo ? (
                                            <img src={storageUrl(s.photo)} alt="" className="w-10 h-10 rounded-full object-cover object-top border border-stone-200 dark:border-coal-600 flex-shrink-0" />
                                        ) : (
                                            <span className={`w-10 h-10 rounded-full border flex items-center justify-center text-sm font-bold flex-shrink-0 ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                                {s.name?.charAt(0).toUpperCase()}
                                            </span>
                                        )}
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-bold text-stone-900 dark:text-stone-100 truncate">{s.name}</p>
                                            <p className="text-[11px] text-stone-500 dark:text-stone-600 font-mono">
                                                {s.nis} · {s.is_iqro ? `${s.metode_label ?? 'Iqro'} Jilid ${s.target_jilid ?? 1}` : `Target ${Number(s.target_juz).toFixed(1)} Juz`}
                                            </p>
                                        </div>
                                        <Link href={`${route('admin.monitoring.santri', s.id)}${query}`}
                                            className="flex-shrink-0 px-2.5 py-1.5 bg-claude-600 hover:bg-claude-500 text-white text-xs font-bold rounded-lg transition">Detail</Link>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="flex-1 bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                            <div className={`h-1.5 rounded-full transition-all ${progressBarColor(pct)}`} style={{ width: `${pct}%` }} />
                                        </div>
                                        <span className={`text-[11px] font-bold flex-shrink-0 ${progressTextColor(pct)}`}>{pct.toFixed(0)}%</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* DESKTOP TABLE */}
                    <table className="min-w-full hidden lg:table">
                        <thead>
                            <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider w-12">No</th>
                                <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">NIS</th>
                                <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Nama Santri</th>
                                <th className="px-5 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Gender</th>
                                <th className="px-5 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Target</th>
                                <th className="px-5 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Progress</th>
                                <th className="px-5 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                            {santriList.length === 0 ? (
                                <tr><td colSpan={7} className="py-12 text-center text-sm text-stone-500">Tidak ada santri di kelas ini</td></tr>
                            ) : santriList.map((s, idx) => {
                                const isPerempuan = s.gender === 'Perempuan';
                                const pct = Math.min(s.progress_percent, 100);
                                return (
                                    <tr key={s.id} className="hover:bg-stone-50 dark:hover:bg-coal-700/25 transition-colors">
                                        <td className="px-5 py-3.5 text-sm text-stone-500 dark:text-stone-600">{idx + 1}</td>
                                        <td className="px-5 py-3.5 text-sm font-mono text-stone-500 dark:text-stone-400">{s.nis}</td>
                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center gap-2.5">
                                                {s.photo ? (
                                                    <img src={storageUrl(s.photo)} alt="" className="w-9 h-9 rounded-full object-cover object-top border border-stone-200 dark:border-coal-600 flex-shrink-0" />
                                                ) : (
                                                    <span className={`w-9 h-9 rounded-full border flex items-center justify-center text-xs font-bold flex-shrink-0 ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                                        {s.name?.charAt(0).toUpperCase()}
                                                    </span>
                                                )}
                                                <span className="text-sm font-medium text-stone-800 dark:text-stone-200">{s.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-3.5 text-center">
                                            {isPerempuan ? (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30">Akhwat</span>
                                            ) : (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30">Ikhwan</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3.5 text-center"><TargetChip s={s} /></td>
                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center gap-2 justify-center">
                                                <div className="w-20 bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                                    <div className={`h-1.5 rounded-full ${progressBarColor(pct)}`} style={{ width: `${pct}%` }} />
                                                </div>
                                                <span className={`text-xs font-bold ${progressTextColor(pct)}`}>{pct.toFixed(0)}%</span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-3.5 text-center">
                                            <Link href={`${route('admin.monitoring.santri', s.id)}${query}`}
                                                className="px-3 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition">Detail</Link>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
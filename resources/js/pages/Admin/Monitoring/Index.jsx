import { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
const ClockIcon = (p) => <I {...p} d={<path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />} />;
const ChevronDownIcon = (p) => <I {...p} d={<path d="M19 9l-7 7-7-7" />} />;
const CheckIcon = (p) => <I {...p} d={<path d="M5 13l4 4L19 7" />} className={p.className || 'w-3.5 h-3.5'} />;
const ChartIcon = (p) => <I {...p} d={<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />} />;

const PERIODS = [
    { label: '1 Hari Terakhir', value: '1' },
    { label: '1 Minggu Terakhir', value: '7' },
    { label: '1 Bulan Terakhir', value: '30' },
    { label: '2 Bulan Terakhir', value: '60' },
    { label: '3 Bulan Terakhir', value: '90' },
    { label: '6 Bulan Terakhir', value: '180' },
    { label: '12 Bulan Terakhir', value: '365' },
];

const fmtDateInput = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const fmtDateLabel = (isoStr) => {
    const d = new Date(isoStr + 'T00:00:00');
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
};

function PeriodDropdown({ period }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        const onClickAway = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onClickAway);
        return () => document.removeEventListener('mousedown', onClickAway);
    }, []);

    const currentLabel = PERIODS.find((p) => p.value === period)?.label || '1 Bulan Terakhir';

    const selectPeriod = (value) => {
        setOpen(false);
        const days = parseInt(value, 10);
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - days);
        router.get(route('admin.monitoring.index'), {
            tanggal_mulai: fmtDateInput(start),
            tanggal_akhir: fmtDateInput(end),
            period: value,
        });
    };

    return (
        <div className="relative" ref={ref}>
            <button onClick={() => setOpen((o) => !o)}
                className="inline-flex items-center gap-2 px-3 py-2 bg-claude-600 hover:bg-claude-500 text-white text-xs font-bold rounded-lg transition">
                <ClockIcon className="w-3.5 h-3.5" />
                <span>{currentLabel}</span>
                <ChevronDownIcon className={`w-3 h-3 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>
            {open && (
                <div className="absolute right-0 mt-1.5 w-52 bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl shadow-2xl z-20 overflow-hidden">
                    {PERIODS.map((p) => (
                        <button key={p.value} onClick={() => selectPeriod(p.value)}
                            className={`w-full text-left px-4 py-2.5 text-xs transition flex items-center justify-between ${period === p.value
                                ? 'bg-claude-500/10 text-claude-600 dark:text-claude-400'
                                : 'text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-coal-700'}`}>
                            <span>{p.label}</span>
                            {period === p.value && <CheckIcon />}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Index({ statistikKelas = [], tanggalMulai, tanggalAkhir, period = '30' }) {
    const query = `?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}&period=${period}`;
    const storageUrl = (path) => (path ? `/storage/${path}` : null);

    return (
        <AuthenticatedLayout>
            <Head title="Monitoring Santri" />

            <div className="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 py-6">

                {/* Kembali — mobile & desktop */}
                <Link href={route('admin.dashboard')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>
                

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <ChartIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100">Monitoring Santri</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Overview setoran dan presensi santri</p>
                    </div>
                </div>

                

                {/* ===== KELAS TABLE ===== */}
                <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                    <div className="px-5 py-3.5 border-b border-stone-200 dark:border-coal-700">
                        <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">Monitoring per Guru & Kelas</h3>
                    </div>

                    {/* MOBILE CARDS */}
                    <div className="lg:hidden divide-y divide-stone-100 dark:divide-coal-700">
                        {statistikKelas.length === 0 ? (
                            <div className="py-12 text-center">
                                <p className="text-sm text-stone-500">Tidak ada data kelas</p>
                            </div>
                        ) : statistikKelas.map((stat, idx) => (
                            <div key={stat.kelas_id} className="px-4 py-3.5 flex items-center gap-3">
                                <div className="w-8 h-8 flex-shrink-0 rounded-lg bg-claude-500/10 border border-claude-500/25 flex items-center justify-center text-claude-600 dark:text-claude-400 text-xs font-black">{idx + 1}</div>
                                {stat.guru_photo ? (
                                    <img src={storageUrl(stat.guru_photo)} alt="" className="w-9 h-9 rounded-full object-cover object-top border border-stone-200 dark:border-coal-600 flex-shrink-0" />
                                ) : (
                                    <span className="w-9 h-9 rounded-full bg-claude-500/10 border border-claude-500/20 text-claude-600 dark:text-claude-400 flex items-center justify-center text-sm font-bold flex-shrink-0">
                                        {stat.guru ? stat.guru.charAt(0).toUpperCase() : '-'}
                                    </span>
                                )}
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold text-stone-900 dark:text-stone-100 truncate">{stat.kelas}</p>
                                    <p className="text-[11px] text-stone-500 truncate">{stat.guru || '-'} · <span className="text-claude-600 dark:text-claude-400 font-bold">{stat.total_santri}</span> santri</p>
                                </div>
                                <Link href={`${route('admin.monitoring.kelas', stat.kelas_id)}${query}`}
                                    className="flex-shrink-0 px-3 py-1.5 bg-claude-600 hover:bg-claude-500 text-white text-xs font-bold rounded-lg transition">Detail →</Link>
                            </div>
                        ))}
                    </div>

                    {/* DESKTOP TABLE */}
                    <div className="hidden lg:block">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                    <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider" style={{ width: 56 }}>No</th>
                                    <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Guru Pembimbing</th>
                                    <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Kelas</th>
                                    <th className="px-5 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider" style={{ width: 160 }}>Jumlah Santri</th>
                                    <th className="px-5 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider" style={{ width: 160 }}>Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                                {statistikKelas.length === 0 ? (
                                    <tr><td colSpan={5} className="py-12 text-center">
                                        <p className="text-sm text-stone-500">Tidak ada data kelas</p>
                                    </td></tr>
                                ) : statistikKelas.map((stat, idx) => (
                                    <tr key={stat.kelas_id} className="hover:bg-stone-50 dark:hover:bg-coal-700/25 transition-colors">
                                        <td className="px-5 py-3.5 text-sm text-stone-500 dark:text-stone-600">{idx + 1}</td>
                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center gap-2.5">
                                                {stat.guru_photo ? (
                                                    <img src={storageUrl(stat.guru_photo)} alt="" className="w-9 h-9 rounded-full object-cover object-top border border-stone-200 dark:border-coal-600 flex-shrink-0" />
                                                ) : (
                                                    <span className="w-9 h-9 rounded-full bg-claude-500/10 border border-claude-500/20 text-claude-600 dark:text-claude-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                        {stat.guru ? stat.guru.charAt(0).toUpperCase() : '-'}
                                                    </span>
                                                )}
                                                <span className="text-sm font-medium text-stone-700 dark:text-stone-300">{stat.guru || '-'}</span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-3.5 text-sm text-stone-700 dark:text-stone-300">{stat.kelas}</td>
                                        <td className="px-5 py-3.5">
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-claude-500/10 text-claude-600 border-claude-500/25 dark:text-claude-400">{stat.total_santri} santri</span>
                                        </td>
                                        <td className="px-5 py-3.5 text-center">
                                            <Link href={`${route('admin.monitoring.kelas', stat.kelas_id)}${query}`}
                                                className="inline-flex items-center justify-center px-4 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition whitespace-nowrap">
                                                Lihat Detail →
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import {
    RadialBarChart, RadialBar, PolarAngleAxis,
    PieChart, Pie, Cell,
    BarChart, Bar, XAxis, YAxis, CartesianGrid,
    Tooltip, ResponsiveContainer,
} from 'recharts';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line, konsisten seantero panel
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const DashboardIcon = (p) => <I {...p} d={<path d="M4 5a1 1 0 011-1h5a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 12a1 1 0 011-1h5a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2zM13 5a1 1 0 011-1h5a1 1 0 011 1v2a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h5a1 1 0 011 1v6a1 1 0 01-1 1h-5a1 1 0 01-1-1v-6z" />} />;
const BookIcon  = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const StarIcon  = (p) => <I {...p} d={<path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />} />;
const CheckIcon = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const LayersIcon= (p) => <I {...p} d={<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />} />;
const InfoIcon  = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const TrendIcon = (p) => <I {...p} d={<path d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5" />} />;
const ChartIcon = (p) => <I {...p} d={<path d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />} />;

/* ============================================================
 * KONSTANTA
 * ============================================================ */
const C = {
    claude: '#D97757',
    amber: '#F59E0B',
    teal: '#14B8A6',
    emerald: '#10B981',
    blue: '#3B82F6',
    red: '#EF4444',
    trackLight: '#F5F4EF',
    trackDark: '#3E3D3A',
    muted: '#A8A29E',
};

const num = (v, d = 0) => Number(v ?? 0).toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });

function gradeOf(n) {
    if (n >= 90) return 'A';
    if (n >= 80) return 'B';
    if (n >= 70) return 'C';
    return 'D';
}

function useDark() {
    const [dark, setDark] = useState(() => typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
    useEffect(() => {
        const el = document.documentElement;
        const obs = new MutationObserver(() => setDark(el.classList.contains('dark')));
        obs.observe(el, { attributes: true, attributeFilter: ['class'] });
        return () => obs.disconnect();
    }, []);
    return dark;
}

/* ============================================================
 * KOMPONEN KECIL — pola sama dengan dashboard guru
 * ============================================================ */
function StatCard({ icon, label, value, sub, tone = 'stone' }) {
    const toneMap = {
        stone: 'bg-stone-100 dark:bg-coal-700 text-stone-500 dark:text-stone-400',
        claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
        emerald: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
        amber: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400',
    };
    return (
        <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-4 flex items-center gap-3">
            <span className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${toneMap[tone]}`}>{icon}</span>
            <div className="min-w-0">
                <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium truncate">{label}</p>
                <p className="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-tight">{value}</p>
                {sub && <p className="text-[10px] text-stone-400 truncate">{sub}</p>}
            </div>
        </div>
    );
}

function MiniStat({ tone, value, label }) {
    const map = {
        emerald: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400',
        blue: 'bg-blue-50 dark:bg-blue-500/10 ring-blue-100 dark:ring-blue-500/20 text-blue-600 dark:text-blue-400',
        amber: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-600 dark:text-amber-400',
        red: 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-600 dark:text-red-400',
    };
    const dot = { emerald: 'bg-emerald-500', blue: 'bg-blue-500', amber: 'bg-amber-500', red: 'bg-red-500' };
    return (
        <div className={`rounded-xl ring-1 p-3 text-center ${map[tone]}`}>
            <p className="text-2xl font-bold tracking-tight leading-none">{value}</p>
            <p className="mt-1.5 flex items-center justify-center gap-1 text-[11px] font-medium">
                <span className={`w-1.5 h-1.5 rounded-full ${dot[tone]}`} /> {label}
            </p>
        </div>
    );
}

/* header card kecil — icon tile w-6 h-6 rounded-lg persis guru */
function CardHead({ icon, title, hint }) {
    return (
        <div className="flex items-center gap-2 mb-4">
            <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                {icon}
            </span>
            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">{title}</h2>
            {hint && <span className="ml-auto text-[11px] text-stone-400">{hint}</span>}
        </div>
    );
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Dashboard({ santris = [], tahunAjaranAktif = null, message }) {
    const list = Array.isArray(santris) ? santris : [];
    const santriName = list[0]?.name ?? 'Santri';

    return (
        <SantriAuthenticatedLayout>
            <Head title="Dashboard Santri" />

            <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 flex items-center justify-center flex-shrink-0">
                        <DashboardIcon className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">
                            Assalamu'alaikum, {santriName}
                        </h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Ringkasan hafalan, nilai &amp; presensi Anda</p>
                    </div>
                    {tahunAjaranAktif && (
                        <span className="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-claude-100 dark:bg-claude-500/15 ring-1 ring-claude-200 dark:ring-claude-500/20 flex-shrink-0">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                            <span className="text-xs font-semibold text-claude-600 dark:text-claude-400 whitespace-nowrap">{tahunAjaranAktif.nama ?? tahunAjaranAktif.name}</span>
                        </span>
                    )}
                </div>

                {list.length === 0 ? (
                    <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl py-16 text-center">
                        <span className="inline-flex w-12 h-12 rounded-2xl bg-stone-100 dark:bg-coal-700 items-center justify-center text-stone-400 mb-3">
                            <InfoIcon className="w-6 h-6" />
                        </span>
                        <p className="text-sm font-semibold text-stone-600 dark:text-stone-300">Data Tidak Ditemukan</p>
                        <p className="text-xs text-stone-400 dark:text-stone-500 mt-1.5">{message ?? 'Hubungi administrator untuk bantuan lebih lanjut.'}</p>
                    </div>
                ) : (
                    list.map((santri, i) => <SantriBoard key={santri.id ?? i} santri={santri} />)
                )}

                {/* ===== INFO NOTE ===== */}
                <div className="mt-5 flex items-start gap-2.5 rounded-2xl bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-100 dark:ring-claude-500/20 p-3">
                    <InfoIcon className="w-4 h-4 text-claude-600 dark:text-claude-400 flex-shrink-0 mt-0.5" />
                    <p className="text-xs text-stone-600 dark:text-stone-300">
                        Statistik adalah <strong className="font-semibold text-stone-900 dark:text-stone-100">akumulasi selama di kelas ini</strong> (semester ganjil &amp; genap). Buka <strong className="font-semibold text-stone-900 dark:text-stone-100">Rapot</strong> di sidebar untuk filter periode custom.
                    </p>
                </div>
            </div>
        </SantriAuthenticatedLayout>
    );
}

function SantriBoard({ santri }) {
    const dark = useDark();
    const s = santri.stats_setoran ?? {};
    const p = santri.stats_presensi ?? {};

    // progress
    const targetJuz = santri.target_juz ?? 30;
    const totalHafalan = s.total_juz_hafalan ?? 0;
    const progressJuz = targetJuz > 0 ? Math.min(100, Math.round((totalHafalan / targetJuz) * 1000) / 10) : 0;

    // metode jilid (Iqro / Aisar)
    const isIqro = s.is_iqro ?? false;
    const metodeLabel = s.metode_label ?? 'Iqro';        // "Iqro" | "Aisar"
    const jilidHal = s.total_halaman_iqro ?? 0;
    const jilidHalTotal = s.total_halaman_jilid ?? 0;
    const jilidAktif = s.target_jilid ?? '-';
    const jilidDiketahui = s.iqro_persen_diketahui ?? false;
    const jilidPersen = isIqro && jilidHalTotal > 0 ? Math.min(100, Math.round((jilidHal / jilidHalTotal) * 1000) / 10) : 0;
    const progressPct = isIqro ? jilidPersen : progressJuz;

    // nilai
    const nilaiOverall = s.rata_nilai ?? 0;
    const totalSetoran = s.total_setoran ?? 0;

    // presensi
    const totHadir = p.total_hadir ?? 0, totIzin = p.total_izin ?? 0, totSakit = p.total_sakit ?? 0, totAlpha = p.total_alpha ?? 0;
    const totHari = p.total_hari ?? 0;
    const pctHadir = santri.persentase_kehadiran ?? 0;

    const presData = [
        { name: 'Hadir', value: totHadir, color: C.emerald },
        { name: 'Izin', value: totIzin, color: C.blue },
        { name: 'Sakit', value: totSakit, color: C.amber },
        { name: 'Alpha', value: totAlpha, color: C.red },
    ].filter((d) => d.value > 0);

    const catData = [
        { name: 'Hafalan', nilai: s.rata_nilai_hafalan ?? 0, color: C.claude },
        { name: 'Murajaah', nilai: s.rata_nilai_murajaah ?? 0, color: C.amber },
        { name: 'Tilawah', nilai: s.rata_nilai_tilawah ?? 0, color: C.teal },
    ];

    const photo = santri.user?.profile_photo;
    const isP = santri.gender === 'P' || santri.gender === 'Perempuan';
    const track = dark ? C.trackDark : C.trackLight;
    const tip = {
        background: dark ? '#30302E' : '#ffffff',
        border: `1px solid ${dark ? 'rgba(255,255,255,0.1)' : '#e7e5e4'}`,
        borderRadius: 12, fontSize: 12, padding: '6px 10px',
        color: dark ? '#FAF9F5' : '#3E3D3A',
    };

    return (
        <>
            {/* ===== PROFIL ===== */}
            <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-4 sm:p-5 mb-5">
                <div className="flex items-center gap-4">
                    {photo ? (
                        <img src={`/storage/${photo}`} alt="Foto" className="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover object-top ring-1 ring-stone-200 dark:ring-coal-700 flex-shrink-0" />
                    ) : (
                        <span className={`w-14 h-14 sm:w-16 sm:h-16 rounded-full ring-1 flex items-center justify-center text-xl font-bold flex-shrink-0 ${isP ? 'bg-pink-100 dark:bg-pink-950/60 ring-pink-200 dark:ring-pink-800/60 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950/60 ring-blue-200 dark:ring-blue-800/60 text-blue-600 dark:text-blue-400'}`}>
                            {(santri.name ?? '?').charAt(0).toUpperCase()}
                        </span>
                    )}
                    <div className="flex-1 min-w-0">
                        <h2 className="text-base sm:text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri.name}</h2>
                        <p className="text-[11px] text-stone-400 dark:text-stone-500 font-mono">{santri.nis ?? '-'}</p>
                        <div className="flex flex-wrap items-center gap-1.5 mt-1">
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{santri.kelas?.name ?? '-'}</span>
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 text-stone-600 dark:text-stone-300 font-semibold">
                                {isIqro ? `${metodeLabel} · Jilid ${jilidAktif}` : 'Al-Quran'}
                            </span>
                        </div>
                    </div>
                    <div className="flex-shrink-0 text-center rounded-xl px-3 py-2 min-w-[74px] bg-claude-500 shadow-sm shadow-claude-500/30">
                        <p className="text-[9px] text-white/75 uppercase tracking-wider font-semibold">Nilai</p>
                        <div className="flex items-baseline justify-center gap-1">
                            <p className="text-2xl font-bold text-white leading-none">{num(nilaiOverall)}</p>
                            <span className="text-xs font-bold bg-white/25 text-white px-1.5 py-0.5 rounded">{gradeOf(nilaiOverall)}</span>
                        </div>
                        <p className="text-[9px] text-white/60 mt-0.5">rata-rata</p>
                    </div>
                </div>
            </div>

            {/* ===== STAT CARDS ===== */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                <StatCard tone="claude" icon={<BookIcon className="w-5 h-5" />} label="Total Setoran" value={totalSetoran} sub="kali setoran" />
                <StatCard tone="amber" icon={<StarIcon className="w-5 h-5" />} label="Rata Nilai" value={num(nilaiOverall, 1)} sub="dari 100 poin" />
                <StatCard
                    tone="stone"
                    icon={<LayersIcon className="w-5 h-5" />}
                    label={isIqro ? `Halaman ${metodeLabel}` : 'Juz Hafalan'}
                    value={isIqro ? jilidHal : num(totalHafalan, 2)}
                    sub={isIqro ? `halaman hafalan · Jilid ${jilidAktif}` : 'juz tercapai'}
                />
                <StatCard tone="emerald" icon={<CheckIcon className="w-5 h-5" />} label="Kehadiran" value={`${pctHadir}%`} sub={`${totHadir}/${totHari} hari`} />
            </div>

            {/* ===== GRID: GAUGE + PRESENSI ===== */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

                {/* ---- GAUGE PROGRESS ---- */}
                <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-5">
                    <CardHead
                        icon={<TrendIcon className="w-3.5 h-3.5" />}
                        title="Progress Hafalan"
                        hint={isIqro ? `${metodeLabel} · Jilid ${jilidAktif}` : null}
                    />
                    {isIqro && !jilidDiketahui ? (
                        <div className="h-44 flex flex-col items-center justify-center text-center">
                            <p className="text-3xl font-bold tracking-tight text-claude-600 dark:text-claude-400 tabular-nums">{jilidHal}</p>
                            <p className="text-xs text-stone-400 mt-1">halaman disetor ({metodeLabel} Jilid {jilidAktif})</p>
                            <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-2 italic px-4">
                                Jumlah halaman jilid ini belum diatur di konfigurasi
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="relative h-44">
                                <ResponsiveContainer width="100%" height="100%">
                                    <RadialBarChart cx="50%" cy="50%" innerRadius="72%" outerRadius="100%" barSize={14} data={[{ value: progressPct }]} startAngle={90} endAngle={-270}>
                                        <PolarAngleAxis type="number" domain={[0, 100]} angleAxisId={0} tick={false} />
                                        <RadialBar background={{ fill: track }} dataKey="value" cornerRadius={12} fill={C.claude} angleAxisId={0} />
                                    </RadialBarChart>
                                </ResponsiveContainer>
                                <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span className="text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums">{progressPct}%</span>
                                    <span className="text-[10px] text-stone-400 -mt-0.5">tercapai</span>
                                </div>
                            </div>
                            <div className="mt-3 flex items-center justify-between text-xs">
                                <span className="flex items-center gap-2 text-stone-600 dark:text-stone-300">
                                    <span className="w-2.5 h-2.5 rounded-full bg-claude-500" />
                                    {isIqro ? 'Halaman' : 'Juz'}
                                </span>
                                <span className="font-bold text-stone-800 dark:text-stone-200">
                                    {isIqro ? `${jilidHal}/${jilidHalTotal}` : `${num(totalHafalan, 2)}/${num(targetJuz, 2)}`}
                                    <span className="text-stone-400 font-normal ml-1">{isIqro ? 'hal' : 'juz'}</span>
                                </span>
                            </div>
                        </>
                    )}
                </div>

                {/* ---- PRESENSI ---- */}
                <div className="lg:col-span-2 bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-5">
                    <CardHead icon={<CheckIcon className="w-3.5 h-3.5" />} title="Presensi" hint={`${totHari} hari tercatat`} />

                    <div className="grid grid-cols-4 gap-2 mb-4">
                        <MiniStat tone="emerald" value={totHadir} label="Hadir" />
                        <MiniStat tone="blue" value={totIzin} label="Izin" />
                        <MiniStat tone="amber" value={totSakit} label="Sakit" />
                        <MiniStat tone="red" value={totAlpha} label="Alpha" />
                    </div>

                    {presData.length === 0 ? (
                        <div className="h-40 flex items-center justify-center text-xs text-stone-400">Belum ada data presensi</div>
                    ) : (
                        <div className="flex items-center gap-4">
                            <div className="relative h-40 flex-1">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={presData} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={48} outerRadius={66} paddingAngle={presData.length > 1 ? 3 : 0} stroke="none" startAngle={90} endAngle={-270}>
                                            {presData.map((d) => <Cell key={d.name} fill={d.color} />)}
                                        </Pie>
                                        <Tooltip contentStyle={tip} formatter={(v, n) => [`${v} hari`, n]} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span className="text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums">{pctHadir}%</span>
                                    <span className="text-[10px] text-stone-400 -mt-0.5">hadir</span>
                                </div>
                            </div>
                            <div className="flex-shrink-0 space-y-2 pr-1">
                                {[
                                    { label: 'Hadir', value: totHadir, dot: 'bg-emerald-500' },
                                    { label: 'Izin', value: totIzin, dot: 'bg-blue-500' },
                                    { label: 'Sakit', value: totSakit, dot: 'bg-amber-500' },
                                    { label: 'Alpha', value: totAlpha, dot: 'bg-red-500' },
                                ].map((l) => (
                                    <div key={l.label} className="flex items-center justify-between gap-4 text-xs">
                                        <span className="flex items-center gap-2 text-stone-600 dark:text-stone-300">
                                            <span className={`w-2.5 h-2.5 rounded-full ${l.dot}`} /> {l.label}
                                        </span>
                                        <span className="font-bold text-stone-800 dark:text-stone-200 tabular-nums">{l.value}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* ===== BAR NILAI PER KATEGORI ===== */}
            <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-5">
                <CardHead icon={<ChartIcon className="w-3.5 h-3.5" />} title="Rata-rata Nilai per Kategori" hint="Hafalan · Murajaah · Tilawah" />
                <div className="h-56">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={catData} margin={{ top: 12, right: 8, left: -18, bottom: 0 }}>
                            <CartesianGrid vertical={false} stroke={dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)'} />
                            <XAxis dataKey="name" tick={{ fontSize: 12, fill: C.muted }} axisLine={false} tickLine={false} />
                            <YAxis domain={[0, 100]} tick={{ fontSize: 11, fill: C.muted }} axisLine={false} tickLine={false} />
                            <Tooltip cursor={{ fill: dark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.03)' }} contentStyle={tip} formatter={(v) => [num(v, 1), 'Nilai']} />
                            <Bar dataKey="nilai" radius={[8, 8, 0, 0]} maxBarSize={72}>
                                {catData.map((d) => <Cell key={d.name} fill={d.color} />)}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>
        </>
    );
}
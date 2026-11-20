import { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
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
const WalletIcon= (p) => <I {...p} d={<path d="M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />} />;
const ArrowIcon = (p) => <I {...p} d={<path d="M13 7l5 5m0 0l-5 5m5-5H6" />} />;

/* ============================================================
 * KONSTANTA
 * ============================================================ */
// Warna SEMANTIK (status presensi & kategori) — sama di semua panel.
// Recharts menerima warna sebagai nilai JS, bukan kelas Tailwind.
const C = {
    emerald: '#10B981',   // Hadir · Hafalan · Al-Qur'an
    amber: '#F59E0B',     // Izin · Murajaah
    orange: '#F97316',    // Sakit · Tilawah
    red: '#EF4444',       // Alpha
    muted: '#A8A29E',
};

/* Warna TEMA (aksen & permukaan) dibaca langsung dari CSS hasil
   tailwind.config.js, BUKAN ditulis manual. Dulu aksen ditulis '#D97757'
   (terracotta lama) dan tidak ikut saat palet claude diganti hijau.
   Class di PROBE harus tertulis utuh supaya dipindai Tailwind. */
const PROBE = {
    accent: 'bg-claude-500',
    trackLight: 'bg-cream-300',
    trackDark: 'bg-coal-600',
    surfaceDark: 'bg-coal-700',
};
const THEME_FALLBACK = { accent: '#7EB842', trackLight: '#E1E4D8', trackDark: '#353B33', surfaceDark: '#262B25' };

function useThemeColors() {
    const [colors, setColors] = useState(THEME_FALLBACK);
    useEffect(() => {
        const out = {};
        Object.entries(PROBE).forEach(([key, cls]) => {
            const el = document.createElement('span');
            el.className = cls;
            el.style.display = 'none';
            document.body.appendChild(el);
            const v = getComputedStyle(el).backgroundColor;
            el.remove();
            out[key] = v && v !== 'rgba(0, 0, 0, 0)' && v !== 'transparent' ? v : THEME_FALLBACK[key];
        });
        setColors(out);
    }, []);
    return colors;
}

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
        stone: 'bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400',
        claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
        emerald: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
        amber: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400',
    };
    return (
        <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-4 flex items-center gap-3">
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
        orange: 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20 text-orange-600 dark:text-orange-400',
        amber: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-600 dark:text-amber-400',
        red: 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-600 dark:text-red-400',
    };
    const dot = { emerald: 'bg-emerald-500', orange: 'bg-orange-500', amber: 'bg-amber-500', red: 'bg-red-500' };
    return (
        <div className={`rounded-xl ring-1 p-3 text-center ${map[tone]}`}>
            <p className="text-2xl font-bold tracking-tight leading-none">{value}</p>
            <p className="mt-1.5 flex items-center justify-center gap-1 text-[11px] font-medium">
                <span className={`w-1.5 h-1.5 rounded-full ${dot[tone]}`} /> {label}
            </p>
        </div>
    );
}

/* Gauge progress — dipakai dua kali untuk santri 2 metode
   (jilid + Al-Qur'an), jadi dijadikan komponen agar tidak duplikat.
   `padat` = mode berdampingan: cincin & angka diperkecil supaya
   dua gauge tetap terbaca di kartu selebar 1/3 grid. */
function GaugeProgress({ persen, warna, track, label, nilai, satuan, padat = false }) {
    const v = Math.min(Math.max(Number(persen) || 0, 0), 100);
    return (
        <div className="min-w-0">
            <div className={padat ? 'relative h-32' : 'relative h-44'}>
                <ResponsiveContainer width="100%" height="100%">
                    <RadialBarChart
                        cx="50%" cy="50%"
                        innerRadius="72%" outerRadius="100%"
                        barSize={padat ? 10 : 14}
                        data={[{ value: v }]}
                        startAngle={90} endAngle={-270}
                    >
                        <PolarAngleAxis type="number" domain={[0, 100]} angleAxisId={0} tick={false} />
                        <RadialBar background={{ fill: track }} dataKey="value" cornerRadius={12} fill={warna} angleAxisId={0} />
                    </RadialBarChart>
                </ResponsiveContainer>
                <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span className={`${padat ? 'text-lg' : 'text-2xl'} font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums`}>
                        {v}%
                    </span>
                    <span className="text-[10px] text-stone-400 -mt-0.5">tercapai</span>
                </div>
            </div>
            <div className="mt-2.5 flex items-center justify-between gap-2 text-xs">
                <span className="flex items-center gap-1.5 min-w-0 text-stone-600 dark:text-stone-300">
                    <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ background: warna }} />
                    <span className="truncate">{label}</span>
                </span>
                <span className="font-bold text-stone-800 dark:text-stone-200 tabular-nums whitespace-nowrap">
                    {nilai}
                    {satuan && <span className="text-stone-400 font-normal ml-1">{satuan}</span>}
                </span>
            </div>
        </div>
    );
}

/* header card kecil — icon tile w-6 h-6 rounded-lg persis guru */
function CardHead({ icon, title, hint }) {
    return (
        <div className="flex items-center gap-2 mb-4">
            <span className="w-6 h-6 bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                {icon}
            </span>
            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">{title}</h2>
            {hint && <span className="ml-auto text-[11px] text-stone-400">{hint}</span>}
        </div>
    );
}

/* Kartu tunggakan — hanya muncul bila modul keuangan aktif DAN masih ada
   tagihan. Wali yang sudah lunas tidak perlu diingatkan soal uang setiap
   kali membuka dashboard. */
function KartuTagihan({ data }) {
    if (!data || (data.jumlah ?? 0) === 0) return null;

    const rp = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
    const fmt = (d) => { try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return d; } };
    const lewat = data.terdekat?.lewat ?? false;
    const href = (() => { try { return route('santri.keuangan.index'); } catch { return null; } })();

    return (
        <div className={`mb-5 rounded-2xl p-4 sm:p-5 ring-1 ${lewat ? 'bg-red-50/70 dark:bg-red-500/5 ring-red-100 dark:ring-red-500/20' : 'bg-claude-50/70 dark:bg-claude-500/5 ring-claude-200 dark:ring-claude-500/20'}`}>
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3 min-w-0">
                    <span className={`w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 ${lewat ? 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400' : 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400'}`}>
                        <WalletIcon className="w-5 h-5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                            Tunggakan · {data.jumlah} tagihan
                        </p>
                        <p className={`text-xl sm:text-2xl font-bold tracking-tight leading-tight tabular-nums ${lewat ? 'text-red-600 dark:text-red-400' : 'text-claude-600 dark:text-claude-400'}`}>
                            {rp(data.total)}
                        </p>
                        {data.terdekat && (
                            <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5 truncate">
                                {lewat ? 'Lewat tempo' : 'Jatuh tempo'} {fmt(data.terdekat.jatuh_tempo)} — {data.terdekat.judul}
                            </p>
                        )}
                    </div>
                </div>

                {href && (
                    <Link
                        href={href}
                        className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-xs font-semibold text-stone-700 dark:text-stone-200 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95"
                    >
                        Lihat Detail <ArrowIcon className="w-3.5 h-3.5" />
                    </Link>
                )}
            </div>
        </div>
    );
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Dashboard({ santris = [], tahunAjaranAktif = null, tagihan = null, message }) {
    const list = Array.isArray(santris) ? santris : [];
    const santriName = list[0]?.name ?? 'Santri';

    return (
        <SantriAuthenticatedLayout>
            <Head title="Dashboard Santri" />

            <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0">
                        <DashboardIcon className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">
                            Selamat Datang, {santriName}
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

                <KartuTagihan data={tagihan} />

                {list.length === 0 ? (
                    <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl py-16 text-center">
                        <span className="inline-flex w-12 h-12 rounded-2xl bg-cream-200 dark:bg-coal-600 items-center justify-center text-stone-400 mb-3">
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
    const theme = useThemeColors();
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

    // metode 2 (opsional): target Al-Qur'an untuk santri jilid
    const hasQuran2 = s.has_quran_secondary ?? false;

    // nilai
    const nilaiOverall = s.rata_nilai ?? 0;
    const totalSetoran = s.total_setoran ?? 0;

    // presensi
    const totHadir = p.total_hadir ?? 0, totIzin = p.total_izin ?? 0, totSakit = p.total_sakit ?? 0, totAlpha = p.total_alpha ?? 0;
    const totHari = p.total_hari ?? 0;
    const pctHadir = santri.persentase_kehadiran ?? 0;

    const presData = [
        { name: 'Hadir', value: totHadir, color: C.emerald },
        { name: 'Izin', value: totIzin, color: C.amber },
        { name: 'Sakit', value: totSakit, color: C.orange },
        { name: 'Alpha', value: totAlpha, color: C.red },
    ].filter((d) => d.value > 0);

    const catData = [
        { name: 'Hafalan', nilai: s.rata_nilai_hafalan ?? 0, color: C.emerald },
        { name: 'Murajaah', nilai: s.rata_nilai_murajaah ?? 0, color: C.amber },
        { name: 'Tilawah', nilai: s.rata_nilai_tilawah ?? 0, color: C.orange },
    ];

    const photo = santri.user?.profile_photo;
    const isP = santri.gender === 'P' || santri.gender === 'Perempuan';
    const track = dark ? theme.trackDark : theme.trackLight;
    const tip = {
        background: dark ? theme.surfaceDark : '#ffffff',
        border: `1px solid ${dark ? theme.trackDark : theme.trackLight}`,
        borderRadius: 12, fontSize: 12, padding: '6px 10px',
        color: dark ? '#E7E5E4' : '#292524',
    };

    return (
        <>
            {/* ===== PROFIL ===== */}
            <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-4 sm:p-5 mb-5">
                <div className="flex items-center gap-4">
                    {photo ? (
                        <img src={`/storage/${photo}`} alt="Foto" className="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0" />
                    ) : (
                        <span className={`w-14 h-14 sm:w-16 sm:h-16 rounded-full ring-1 flex items-center justify-center text-xl font-bold flex-shrink-0 ${isP ? 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400' : 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400'}`}>
                            {(santri.name ?? '?').charAt(0).toUpperCase()}
                        </span>
                    )}
                    <div className="flex-1 min-w-0">
                        <h2 className="text-base sm:text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri.name}</h2>
                        <p className="text-[11px] text-stone-400 dark:text-stone-500 font-mono">{santri.nis ?? '-'}</p>
                        <div className="flex flex-wrap items-center gap-1.5 mt-1">
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{santri.kelas?.name ?? '-'}</span>
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-600 dark:text-stone-300 font-semibold">
                                {isIqro ? `${metodeLabel} · Jilid ${jilidAktif}` : 'Al-Quran'}
                                {hasQuran2 && <span className="opacity-40 mx-1">+</span>}
                                {hasQuran2 && 'Al-Quran'}
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
                    sub={
                        isIqro
                            ? (hasQuran2
                                ? `Jilid ${jilidAktif} · ${num(totalHafalan, 2)} juz Al-Qur'an`
                                : `halaman hafalan · Jilid ${jilidAktif}`)
                            : 'juz tercapai'
                    }
                />
                <StatCard tone="emerald" icon={<CheckIcon className="w-5 h-5" />} label="Kehadiran" value={`${pctHadir}%`} sub={`${totHadir}/${totHari} hari`} />
            </div>

            {/* ===== GRID: GAUGE + PRESENSI ===== */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

                {/* ---- GAUGE PROGRESS ---- */}
                <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-5">
                    <CardHead
                        icon={<TrendIcon className="w-3.5 h-3.5" />}
                        title="Progress Hafalan"
                        hint={isIqro ? `${metodeLabel} · Jilid ${jilidAktif}` : null}
                    />
                    {isIqro && !jilidDiketahui ? (
                        <>
                            <div className="h-44 flex flex-col items-center justify-center text-center">
                                <p className="text-3xl font-bold tracking-tight text-claude-600 dark:text-claude-400 tabular-nums">{jilidHal}</p>
                                <p className="text-xs text-stone-400 mt-1">halaman disetor ({metodeLabel} Jilid {jilidAktif})</p>
                                <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-2 italic px-4">
                                    Jumlah halaman jilid ini belum diatur di konfigurasi
                                </p>
                            </div>

                            {/* Gauge Al-Qur'an tetap tampil meski persentase jilid
                                belum bisa dihitung — datanya tidak saling bergantung. */}
                            {hasQuran2 && (
                                <div className="mt-4 pt-4 border-t border-cream-300 dark:border-coal-600">
                                    <GaugeProgress
                                        persen={progressJuz}
                                        warna={C.emerald}
                                        track={track}
                                        label="Al-Qur'an"
                                        nilai={`${num(totalHafalan, 2)}/${num(targetJuz, 2)}`}
                                        satuan="juz"
                                    />
                                </div>
                            )}
                        </>
                    ) : hasQuran2 ? (
                        <div className="grid grid-cols-2 gap-3">
                            <GaugeProgress
                                padat
                                persen={progressPct}
                                warna={theme.accent}
                                track={track}
                                label={metodeLabel}
                                nilai={`${jilidHal}/${jilidHalTotal}`}
                                satuan="hal"
                            />
                            <GaugeProgress
                                padat
                                persen={progressJuz}
                                warna={C.emerald}
                                track={track}
                                label="Al-Qur'an"
                                nilai={`${num(totalHafalan, 2)}/${num(targetJuz, 2)}`}
                                satuan="juz"
                            />
                        </div>
                    ) : (
                        <GaugeProgress
                            persen={progressPct}
                            warna={theme.accent}
                            track={track}
                            label={isIqro ? 'Halaman' : 'Juz'}
                            nilai={isIqro ? `${jilidHal}/${jilidHalTotal}` : `${num(totalHafalan, 2)}/${num(targetJuz, 2)}`}
                            satuan={isIqro ? 'hal' : 'juz'}
                        />
                    )}
                </div>

                {/* ---- PRESENSI ---- */}
                <div className="lg:col-span-2 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-5">
                    <CardHead icon={<CheckIcon className="w-3.5 h-3.5" />} title="Presensi" hint={`${totHari} hari tercatat`} />

                    <div className="grid grid-cols-4 gap-2 mb-4">
                        <MiniStat tone="emerald" value={totHadir} label="Hadir" />
                        <MiniStat tone="amber" value={totIzin} label="Izin" />
                        <MiniStat tone="orange" value={totSakit} label="Sakit" />
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
                                    { label: 'Izin', value: totIzin, dot: 'bg-amber-500' },
                                    { label: 'Sakit', value: totSakit, dot: 'bg-orange-500' },
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
            <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-5">
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
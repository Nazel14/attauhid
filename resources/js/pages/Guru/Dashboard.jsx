import { useMemo, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from 'recharts';

/* ============================================================
 * IKON — monokrom line, konsisten seantero panel
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const DashboardIcon = (p) => <I {...p} d={<path d="M4 5a1 1 0 011-1h5a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 12a1 1 0 011-1h5a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2zM13 5a1 1 0 011-1h5a1 1 0 011 1v2a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h5a1 1 0 011 1v6a1 1 0 01-1 1h-5a1 1 0 01-1-1v-6z" />} />;
const UsersIcon = (p) => <I {...p} d={<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />} />;
const TrendIcon = (p) => <I {...p} d={<path d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5" />} />;
const CheckIcon = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const BookIcon = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const ChartIcon = (p) => <I {...p} d={<path d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />} />;
const ArrowUpIcon = (p) => <I {...p} d={<path d="M5 10l7-7m0 0l7 7m-7-7v18" />} />;
const TrophyIcon  = (p) => <I {...p} d={<path d="M8 21h8m-4-4v4m6-17H6v4a6 6 0 0012 0V4zM6 6H4a2 2 0 000 4h2m12-4h2a2 2 0 010 4h-2" />} />;

/* ============================================================
 * KONSTANTA
 * ============================================================ */
// Recharts menerima warna sebagai NILAI JavaScript (prop fill/stroke), bukan
// nama kelas. Tailwind memindai teks sumber dan tidak pernah melihat nilai
// ini, jadi palet di tailwind.config.js TIDAK berlaku di sini — angkanya
// harus ditulis manual. Tiga warna diambil dari logo At-Tauhid.
const METODE_COLORS = { quran: '#7EB842', iqro: '#FA902C', aisar: '#36A7DF' };
const STATUS = {
    hadir: { dot: 'bg-emerald-500', label: 'Hadir', text: 'text-emerald-600' },
    sakit: { dot: 'bg-orange-500', label: 'Sakit', text: 'text-orange-600' },
    izin: { dot: 'bg-amber-500', label: 'Izin', text: 'text-amber-600' },
    alpha: { dot: 'bg-red-500', label: 'Alpha', text: 'text-red-600' },
};
const METODE_BADGE = {
    quran: 'bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-claude-200 dark:ring-claude-500/20',
    iqro:  'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20',
    aisar: 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400 ring-sky-100 dark:ring-sky-500/20',
};

const getProgress = (s) => Number(s.progress_persen ?? s.progress ?? s.persentase ?? 0);

const formatTanggalID = (str) => {
    if (!str) return '';
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

/* ============================================================
 * KOMPONEN KECIL
 * ============================================================ */
function StatCard({ icon, label, value, sub, tone = 'stone' }) {
    const toneMap = {
        stone: 'bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400',
        claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
        emerald: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    };
    return (
        <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-4 flex items-center gap-3">
            <span className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${toneMap[tone]}`}>{icon}</span>
            <div className="min-w-0">
                <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium break-words">{label}</p>
                <p className="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-tight">{value}</p>
                {sub && <p className="text-[10px] text-stone-400 break-words">{sub}</p>}
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

function MethodBadge({ meta }) {
    if (!meta) return <span className="text-stone-300 dark:text-coal-500 text-[10px]">–</span>;
    return (
        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-md ring-1 text-[10px] font-semibold whitespace-nowrap ${METODE_BADGE[meta.tone]}`}>
            {meta.primary}
            {meta.hasQuran2 && (
                <>
                    <span className="opacity-40">+</span>
                    <span>Qur'an</span>
                </>
            )}
        </span>
    );
}

function ProgressBar({ value, color = 'bg-claude-500', dot = null, title }) {
    const v = Math.min(Number(value) || 0, 100);
    return (
        <span className="flex items-center gap-1.5 min-w-0" title={title}>
            {dot && <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${dot}`} />}
            <span className="flex-1 h-1 rounded-full bg-cream-300 dark:bg-coal-600 overflow-hidden">
                <span className={`block h-full rounded-full ${color}`} style={{ width: `${v}%` }} />
            </span>
            <span className="text-[10px] font-semibold text-stone-500 dark:text-stone-400 tabular-nums w-7 text-right">{v}%</span>
        </span>
    );
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Dashboard({ santriList = [], santriSiapNaik = [], santriTamat = [], presensiHariIni = null }) {
    const { auth } = usePage().props;
    const guruName = auth?.user?.display_name ?? auth?.user?.name ?? 'Bapak/Ibu Guru';

    const [busyId, setBusyId] = useState(null);
    const sahkanNaik = (s) => {
        if (!window.confirm(`Sahkan ${s.name} naik ke ${s.metode} Jilid ${s.jilid_berikutnya}?`)) return;
        setBusyId(s.id);
        router.post(route('guru.santri.naik-jilid', s.id), {}, {
            preserveScroll: true,
            onFinish: () => setBusyId(null),
        });
    };

    /* ---- distribusi metode (client-side dari santriList) ----
     * Siswa 2-metode (Iqro/Aisar + Al-Qur'an) dihitung di KEDUA metode,
     * jadi total angka bisa > jumlah siswa. */
    const metodeData = useMemo(() => {
        let quran = 0, iqro = 0, aisar = 0;
        santriList.forEach((s) => {
            const m = (s.target_metode || '').toLowerCase();
            if (m === 'aisar') aisar++;
            else if (m === 'iqro' || m === 'jilid') iqro++;
            else quran++;

            // metode sekunder (opsional, hanya quran)
            if ((s.target_metode_2 || '').toLowerCase() === 'quran') quran++;
        });
        return [
            { key: 'quran', name: 'Al-Quran', value: quran, color: METODE_COLORS.quran },
            { key: 'iqro',  name: 'Iqro',     value: iqro,  color: METODE_COLORS.iqro },
            { key: 'aisar', name: 'Aisar',    value: aisar, color: METODE_COLORS.aisar },
        ].filter((d) => d.value > 0);
    }, [santriList]);

    // Jumlah seluruh irisan. Sengaja dipakai sebagai angka di tengah donut,
    // BUKAN santriList.length — karena siswa dua-metode dihitung dua kali,
    // kalau tengahnya memakai jumlah siswa maka angka tengah dan jumlah
    // irisannya tidak cocok dan pembacanya bingung.
    const totalIrisan = useMemo(
        () => metodeData.reduce((a, d) => a + d.value, 0),
        [metodeData]
    );
    const adaDuaMetode = totalIrisan > santriList.length;

    const presensi = presensiHariIni ?? {
        tanggal: new Date().toISOString().slice(0, 10),
        summary: { hadir: 0, sakit: 0, izin: 0, alpha: 0 },
        rows: [],
    };
    const totalSetoran = (presensi.rows || []).reduce((a, r) => a + Number(r.jumlah_setoran || 0), 0);
    const progressById = useMemo(
        () => Object.fromEntries(santriList.map((s) => [s.id, getProgress(s)])),
        [santriList]
    );

    // { id: { primary, tone, hasQuran2, progress2 } } — 1 pill gabungan per siswa
    const metaById = useMemo(() => {
        const map = {};
        santriList.forEach((s) => {
            const m = (s.target_metode || '').toLowerCase();
            let primary, tone;
            if (m === 'aisar')      { primary = `Aisar ${s.target_jilid ?? '-'}`; tone = 'aisar'; }
            else if (m === 'iqro')  { primary = `Iqro ${s.target_jilid ?? '-'}`;  tone = 'iqro'; }
            else                    { primary = 'Al-Quran';                       tone = 'quran'; }

            map[s.id] = {
                primary,
                tone,
                hasQuran2: (s.target_metode_2 || '').toLowerCase() === 'quran',
                progress2: Number(s.progress_juz2 ?? 0),
            };
        });
        return map;
    }, [santriList]);

    return (
        <GuruAuthenticatedLayout>
            <Head title="Dashboard" />

            {/* max-w-6xl dipertahankan — dashboard guru sengaja lebih sempit
                daripada max-w-7xl milik <main> di layout. Padding dan py
                dibuang karena <main> sudah memberinya; ditulis dua kali,
                jaraknya berlipat. */}
            <div className="max-w-6xl mx-auto">

                {/* ===== HEADER ===== */}
                {/* items-start: kalau nama guru panjang dan turun baris, ikon
                    tetap sejajar dengan baris pertama judul. */}
                <div className="flex items-start gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0">
                        <DashboardIcon className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        {/* Nama guru ditampilkan utuh (break-words), tidak dipotong "…". */}
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight leading-snug text-stone-900 dark:text-stone-100 break-words">
                            <span className="block text-sm sm:text-base font-semibold text-stone-500 dark:text-stone-400">Selamat Datang,</span>
                            {guruName}
                        </h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Ringkasan siswa ampuan &amp; presensi hari ini</p>
                    </div>
                </div>

                {/* ===== USULAN NAIK JILID ===== */}
                {(santriSiapNaik.length > 0 || santriTamat.length > 0) && (
                    <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-5 mb-5">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-6 h-6 bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                                <ArrowUpIcon className="w-3.5 h-3.5" />
                            </span>
                            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Usulan Naik Jilid</h2>
                            {santriSiapNaik.length > 0 && (
                                <span className="ml-auto text-[11px] font-semibold px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400">
                                    {santriSiapNaik.length} siap naik
                                </span>
                            )}
                        </div>

                        <div className="space-y-2">
                            {santriSiapNaik.map((s) => (
                                <div key={s.id} className="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600">
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-semibold leading-snug text-stone-800 dark:text-stone-100 break-words">{s.name}</p>
                                        <p className="text-[11px] text-stone-500 dark:text-stone-400">
                                            {s.metode} Jilid {s.jilid_sekarang}
                                            <span className="mx-1 text-claude-500">→</span>
                                            Jilid {s.jilid_berikutnya}
                                            <span className="text-stone-400 dark:text-stone-500"> · {s.halaman_tersetor}/{s.total_halaman} halaman tuntas</span>
                                        </p>
                                    </div>
                                    <button
                                        onClick={() => sahkanNaik(s)}
                                        disabled={busyId === s.id}
                                        className="shrink-0 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 disabled:opacity-50 text-white text-xs font-semibold transition-colors active:scale-95"
                                    >
                                        {busyId === s.id ? 'Memproses…' : 'Sahkan'}
                                    </button>
                                </div>
                            ))}

                            {santriTamat.map((s) => (
                                <div key={`t-${s.id}`} className="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20">
                                    <TrophyIcon className="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-semibold leading-snug text-stone-800 dark:text-stone-100 break-words">{s.name}</p>
                                        <p className="text-[11px] text-stone-500 dark:text-stone-400">Tuntas seluruh jilid {s.metode} (Jilid {s.jilid})</p>
                                    </div>
                                    <span className="shrink-0 text-[10px] font-bold px-2 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">
                                        Selesai
                                    </span>
                                </div>
                            ))}
                        </div>

                        {santriSiapNaik.length === 0 && (
                            <p className="mt-2 text-[11px] text-stone-400">Belum ada siswa yang memenuhi syarat naik jilid.</p>
                        )}
                    </div>
                )}

                {/* ===== STAT CARDS ===== */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    <StatCard tone="claude" icon={<UsersIcon className="w-5 h-5" />} label="Total Siswa" value={santriList.length} />
                    <StatCard tone="emerald" icon={<CheckIcon className="w-5 h-5" />} label="Hadir Hari Ini" value={presensi.summary.hadir} sub={`dari ${santriList.length} siswa`} />
                    <StatCard tone="stone" icon={<BookIcon className="w-5 h-5" />} label="Setoran Hari Ini" value={totalSetoran} sub="total setoran" />
                </div>

                {/* ===== GRID: METODE CHART + PRESENSI ===== */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    {/* ---- DONUT METODE ---- */}
                    <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-6 h-6 bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                                <ChartIcon className="w-3.5 h-3.5" />
                            </span>
                            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Distribusi Metode</h2>
                        </div>

                        {metodeData.length ? (
                            <>
                                <div className="relative h-44">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie data={metodeData} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={52} outerRadius={72} paddingAngle={3} stroke="none">
                                                {metodeData.map((d) => <Cell key={d.key} fill={d.color} />)}
                                            </Pie>
                                            {/* Tooltip Recharts digambar di luar pohon Tailwind, jadi
                                                warnanya tidak bisa ikut kelas dark:. Dibiarkan terang
                                                dengan garis netral supaya terbaca di kedua mode. */}
                                            <Tooltip
                                                contentStyle={{ borderRadius: 12, border: '1px solid #E1E4D8', fontSize: 12, padding: '6px 10px', color: '#2B2F28' }}
                                                formatter={(v, n) => [`${v} target`, n]}
                                            />
                                        </PieChart>
                                    </ResponsiveContainer>
                                    <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span className="text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">{totalIrisan}</span>
                                        <span className="text-[10px] text-stone-400 -mt-0.5">Target</span>
                                    </div>
                                </div>
                                <div className="mt-3 space-y-2">
                                    {metodeData.map((d) => (
                                        <div key={d.key} className="flex items-center justify-between text-xs">
                                            <span className="flex items-center gap-2 text-stone-600 dark:text-stone-300">
                                                <span className="w-2.5 h-2.5 rounded-full" style={{ background: d.color }} />
                                                {d.name}
                                            </span>
                                            <span className="font-bold text-stone-800 dark:text-stone-200">
                                                {d.value}
                                                <span className="text-stone-400 font-normal ml-1">
                                                    ({Math.round((d.value / totalIrisan) * 100)}%)
                                                </span>
                                            </span>
                                        </div>
                                    ))}
                                </div>
                                {adaDuaMetode && (
                                    <p className="mt-3 text-[10px] leading-relaxed text-stone-400">
                                        {santriList.length} siswa, {totalIrisan} target — sebagian siswa
                                        mengambil dua metode sekaligus, jadi dihitung di dua irisan.
                                    </p>
                                )}
                            </>
                        ) : (
                            <div className="h-44 flex items-center justify-center text-xs text-stone-400">Belum ada data siswa</div>
                        )}
                    </div>

                    {/* ---- PRESENSI HARI INI ---- */}
                    <div className="lg:col-span-2 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-3.5 sm:p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-6 h-6 bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                                <CheckIcon className="w-3.5 h-3.5" />
                            </span>
                            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">
                                Presensi Hari Ini
                            </h2>
                            <span className="ml-auto text-[11px] text-stone-400">{formatTanggalID(presensi.tanggal)}</span>
                        </div>

                        {/* mini stats */}
                        <div className="grid grid-cols-4 gap-2 mb-4">
                            <MiniStat tone="emerald" value={presensi.summary.hadir} label="Hadir" />
                            <MiniStat tone="orange" value={presensi.summary.sakit} label="Sakit" />
                            <MiniStat tone="amber" value={presensi.summary.izin} label="Izin" />
                            <MiniStat tone="red" value={presensi.summary.alpha} label="Alpha" />
                        </div>

                        {/* legend */}
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-3 text-[11px] text-stone-500 dark:text-stone-400">
                            {Object.entries(STATUS).map(([k, v]) => (
                                <span key={k} className="flex items-center gap-1.5">
                                    <span className={`w-2 h-2 rounded-full ${v.dot}`} /> {v.label}
                                </span>
                            ))}
                            <span className="flex items-center gap-1.5">
                                <span className="w-4 h-4 rounded bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-400">
                                    <BookIcon className="w-2.5 h-2.5" />
                                </span>
                                Setoran
                            </span>
                        </div>

                        {/* ===== MOBILE: card list ===== */}
                        <div className="sm:hidden space-y-2">
                            {(presensi.rows || []).length ? (
                                presensi.rows.map((r, i) => {
                                    const st = STATUS[(r.status || '').toLowerCase()] ?? null;
                                    const pr = Math.min(progressById[r.id] ?? 0, 100);
                                    return (
                                        <div key={r.id ?? i} className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 p-3">
                                            <div className="flex items-start gap-2.5">
                                                <span className="mt-0.5 w-5 h-5 shrink-0 rounded-md bg-cream-200 dark:bg-coal-600 text-[10px] font-bold text-stone-500 dark:text-stone-400 flex items-center justify-center">
                                                    {i + 1}
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-semibold text-stone-800 dark:text-stone-100 leading-tight break-words">
                                                        {r.name}
                                                    </p>
                                                    <span className="block mt-1">
                                                        <MethodBadge meta={metaById[r.id]} />
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2 shrink-0">
                                                    {Number(r.jumlah_setoran) > 0 && (
                                                        <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-[10px] font-semibold text-stone-600 dark:text-stone-300">
                                                            <BookIcon className="w-3 h-3" /> {r.jumlah_setoran}
                                                        </span>
                                                    )}
                                                    <span className="flex flex-col items-center">
                                                        {st ? (
                                                            <>
                                                                <span className={`w-3.5 h-3.5 rounded-full ${st.dot}`} title={st.label} />
                                                                {r.waktu && <span className="text-[9px] text-stone-400 tabular-nums mt-0.5">{r.waktu}</span>}
                                                            </>
                                                        ) : (
                                                            <span className="w-4 h-4 rounded-full ring-1 ring-cream-400 dark:ring-coal-500" title="Belum presensi" />
                                                        )}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="mt-2.5 flex flex-col gap-1">
                                                <ProgressBar
                                                    value={pr}
                                                    color="bg-claude-500"
                                                    dot={metaById[r.id]?.hasQuran2 ? 'bg-claude-500' : null}
                                                    title={metaById[r.id]?.hasQuran2 ? 'Progress jilid' : 'Progress'}
                                                />
                                                {metaById[r.id]?.hasQuran2 && (
                                                    <ProgressBar
                                                        value={metaById[r.id].progress2}
                                                        color="bg-emerald-500"
                                                        dot="bg-emerald-500"
                                                        title="Progress Al-Qur'an"
                                                    />
                                                )}
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 px-3 py-8 text-center text-xs text-stone-400">
                                    Belum ada presensi tercatat hari ini.
                                </div>
                            )}
                        </div>

                        {/* ===== DESKTOP: tabel ===== */}
                        <div className="hidden sm:block rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden">
                            <div className="grid grid-cols-[32px_1fr_140px_120px_84px] gap-2 px-3 py-2 bg-cream-100 dark:bg-coal-600/40 text-[11px] font-semibold text-stone-500 dark:text-stone-400">
                                <span>No</span>
                                <span>Nama Siswa</span>
                                <span>Metode</span>
                                <span>Progress</span>
                                <span className="text-right">Materi</span>
                            </div>
                            <div className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                {(presensi.rows || []).length ? (
                                    presensi.rows.map((r, i) => {
                                        const st = STATUS[(r.status || '').toLowerCase()] ?? null;
                                        return (
                                            <div key={r.id ?? i} className="grid grid-cols-[32px_1fr_140px_120px_84px] gap-2 px-3 py-2.5 items-center">
                                                <span className="text-xs text-stone-400">{i + 1}</span>
                                                <span className="min-w-0">
                                                    {/* break-words, bukan truncate — nama panjang turun baris
                                                        daripada terpotong elipsis. */}
                                                    <span className="block text-sm leading-snug text-stone-700 dark:text-stone-200 break-words">{r.name}</span>
                                                </span>
                                                {/* metode — 1 pill gabungan */}
                                                <span className="flex items-center">
                                                    <MethodBadge meta={metaById[r.id]} />
                                                </span>
                                                <span className="flex flex-col gap-1 min-w-0 justify-center">
                                                    <ProgressBar
                                                        value={progressById[r.id] ?? 0}
                                                        color="bg-claude-500"
                                                        dot={metaById[r.id]?.hasQuran2 ? 'bg-claude-500' : null}
                                                        title={metaById[r.id]?.hasQuran2 ? 'Progress jilid' : 'Progress'}
                                                    />
                                                    {metaById[r.id]?.hasQuran2 && (
                                                        <ProgressBar
                                                            value={metaById[r.id].progress2}
                                                            color="bg-emerald-500"
                                                            dot="bg-emerald-500"
                                                            title="Progress Al-Qur'an"
                                                        />
                                                    )}
                                                </span>
                                                <span className="flex items-center justify-end gap-3">
                                                    {/* slot badge setoran — lebar tetap */}
                                                    <span className="w-11 flex justify-end">
                                                        {Number(r.jumlah_setoran) > 0 ? (
                                                            <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-[10px] font-semibold text-stone-600 dark:text-stone-300">
                                                                <BookIcon className="w-3 h-3" /> {r.jumlah_setoran}
                                                            </span>
                                                        ) : (
                                                            <span className="text-stone-300 dark:text-coal-500">–</span>
                                                        )}
                                                    </span>
                                                    {/* slot status — lebar tetap */}
                                                    <span className="w-9 flex flex-col items-center">
                                                        {st ? (
                                                            <>
                                                                <span className={`w-3.5 h-3.5 rounded-full ${st.dot}`} title={st.label} />
                                                                {r.waktu && <span className="text-[9px] text-stone-400 tabular-nums mt-0.5">{r.waktu}</span>}
                                                            </>
                                                        ) : (
                                                            <span className="w-4 h-4 rounded-full ring-1 ring-cream-400 dark:ring-coal-500" title="Belum presensi" />
                                                        )}
                                                    </span>
                                                </span>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="px-3 py-8 text-center text-xs text-stone-400">
                                        Belum ada presensi tercatat hari ini.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </GuruAuthenticatedLayout>
    );
}
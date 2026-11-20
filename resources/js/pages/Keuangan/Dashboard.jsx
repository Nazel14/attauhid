import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { formatCurrency, formatNumber } from '@/Utils/formatCurrency';
import {
    BarChart, Bar, AreaChart, Area, XAxis, YAxis,
    CartesianGrid, Tooltip, Legend, ResponsiveContainer,
} from 'recharts';

// Recharts memakai atribut SVG, jadi warnanya tidak bisa lewat kelas Tailwind.
// Nilai di bawah DISALIN dari tailwind.config.js (palet TK Attauhid) — kalau
// palet di sana berubah, angka di sini ikut diubah manual.
const WARNA = {
    hijau:  '#7EB842', // claude-500 / hijau logo — pemasukan
    oranye: '#FA902C', // tauhid.oranye — tunggakan (peringatan, bukan bahaya)
    merah:  '#ef4444', // red-500 — pengeluaran
    terang: { grid: '#E1E4D8', sumbu: '#78716c', kartu: '#ffffff', teks: '#292524' },        // cream-300, stone-500
    gelap:  { grid: '#3F453C', sumbu: '#a8a29e', kartu: '#323730', teks: '#F6F7F2' },        // coal-600, stone-400, coal-700, cream-100
};

// Hook ini mengikuti toggle tema di layout supaya grafik ikut berubah.
function useDarkMode() {
    const [dark, setDark] = useState(
        () => typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
    );

    useEffect(() => {
        const obs = new MutationObserver(() =>
            setDark(document.documentElement.classList.contains('dark')),
        );
        obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        return () => obs.disconnect();
    }, []);

    return dark;
}

// Recharts butuh angka px, bukan kelas Tailwind — jadi breakpoint dibaca
// lewat matchMedia, bukan lewat `sm:`.
function useLayarKecil(query = '(max-width: 639px)') {
    const [kecil, setKecil] = useState(
        () => typeof window !== 'undefined' && window.matchMedia(query).matches,
    );

    useEffect(() => {
        const mq = window.matchMedia(query);
        const ubah = (e) => setKecil(e.matches);

        setKecil(mq.matches);
        mq.addEventListener('change', ubah);
        return () => mq.removeEventListener('change', ubah);
    }, [query]);

    return kecil;
}

function Kartu({ label, nilai, sub, warna, ikon }) {
    return (
        <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 transition hover:ring-cream-400 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
            <div className="flex items-start justify-between gap-2 sm:gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">{label}</p>
                    {/* break-words + text-lg di mobile: "Rp 15.500.000" meluber
                        kalau dipaksa 2xl di layar 360px. */}
                    <h3 className="mt-1.5 break-words text-lg font-bold leading-tight text-stone-800 dark:text-cream-50 sm:mt-2 sm:text-2xl">{nilai}</h3>
                    <div className="mt-1.5 text-[11px] sm:text-xs">{sub}</div>
                </div>
                <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl sm:h-10 sm:w-10 ${warna}`}>
                    <svg className="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                        <path strokeLinecap="round" strokeLinejoin="round" d={ikon} />
                    </svg>
                </div>
            </div>
        </div>
    );
}

// Di luar Dashboard dengan sengaja: komponen yang dibuat di dalam render
// induknya dibongkar-pasang ulang setiap render (mis. saat tema diganti).
function Transaksi({ judul, items, kosong, positif }) {
    return (
        <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
            <h3 className="mb-4 text-sm font-semibold text-stone-800 dark:text-cream-50">{judul}</h3>
            <div className="space-y-3">
                {items?.length > 0 ? items.map((item) => (
                    <div key={item.id} className="flex flex-col gap-1 border-b border-cream-200 pb-3 last:border-0 last:pb-0 dark:border-coal-600 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                        <div className="min-w-0">
                            <p className="truncate text-sm font-medium text-stone-800 dark:text-cream-100">{item.description}</p>
                            <p className="truncate text-xs text-stone-400">{item.date} · {item.method || item.category}</p>
                        </div>
                        {/* Di mobile nominal turun ke baris bawah — dipaksa sebaris
                            akan menekan deskripsi jadi tinggal beberapa huruf. */}
                        <span className={`shrink-0 text-sm font-semibold sm:text-right ${positif ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-700 dark:text-stone-200'}`}>
                            {positif ? '+' : '−'} {formatCurrency(item.amount)}
                        </span>
                    </div>
                )) : (
                    <p className="py-6 text-center text-sm text-stone-400">{kosong}</p>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({ stats, monthlyData, arrearsTrend, recentIncome, recentExpenses, actionSummary, notifications }) {
    const dark = useDarkMode();
    const kecil = useLayarKecil();

    const tema  = dark ? WARNA.gelap : WARNA.terang;
    const grid  = tema.grid;
    const sumbu = tema.sumbu;

    const tooltipStyle = {
        backgroundColor: tema.kartu,
        border: `1px solid ${grid}`,
        borderRadius: '12px',
        fontSize: '12px',
        color: tema.teks,
    };

    // Margin kiri negatif memotong label sumbu Y di layar sempit.
    const marginChart = kecil
        ? { top: 5, right: 5, left: -8, bottom: 0 }
        : { top: 5, right: 5, left: -20, bottom: 0 };

    const fontSumbu = kecil ? 10 : 11;
    const lebarY = kecil ? 38 : 60;

    const I = {
        masuk:  'M12 4v16m8-8H4',
        keluar: 'M20 12H4',
        saldo:  'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        tunggak:'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    };

    const naik = (v) => (v >= 0 ? '↑' : '↓');

    return (
        <KeuanganLayout>
            <Head title="Dashboard Keuangan" />

            <div className="mb-5 flex flex-col gap-1 sm:mb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Dashboard Keuangan</h1>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Ringkasan arus kas dan dokumen yang menunggu tindakan.</p>
                </div>
                <p className="text-xs text-stone-400">
                    {new Date().toLocaleDateString('id-ID', {
                        weekday: kecil ? undefined : 'long',
                        day: 'numeric',
                        month: kecil ? 'short' : 'long',
                        year: 'numeric',
                    })}
                </p>
            </div>

            {notifications?.length > 0 && (
                <div className="mb-5 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20 sm:mb-6">
                    <div className="mb-3 flex items-center gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p className="text-sm font-semibold leading-snug text-amber-900 dark:text-amber-200">{actionSummary}</p>
                    </div>
                    {/* Mobile: chip menumpuk penuh lebar agar judul tidak terpotong. */}
                    <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        {notifications.map((item) => (
                            <Link key={item.key} href={item.url}
                                className="flex items-center justify-between gap-2 rounded-xl bg-white px-3 py-2.5 text-xs font-medium text-stone-700 ring-1 ring-amber-200 transition hover:ring-amber-400 dark:bg-coal-700 dark:text-stone-200 dark:ring-amber-500/20 sm:w-auto sm:justify-start sm:py-2">
                                <span className="min-w-0 truncate sm:max-w-[220px]">{item.title}</span>
                                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                    {item.count}
                                </span>
                            </Link>
                        ))}
                    </div>
                </div>
            )}

            {/* 2 kolom sejak layar terkecil: 4 kartu bertumpuk vertikal
                memakan hampir satu layar penuh sebelum grafik terlihat. */}
            <div className="mb-5 grid grid-cols-2 gap-3 sm:mb-6 sm:gap-4 lg:grid-cols-4">
                <Kartu label="Pemasukan" nilai={`Rp ${formatNumber(stats?.totalIncome || 0)}`}
                    warna="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400" ikon={I.masuk}
                    sub={<>
                        <span className={stats?.incomeGrowth >= 0 ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'font-semibold text-red-600 dark:text-red-400'}>
                            {naik(stats?.incomeGrowth)} {Math.abs(stats?.incomeGrowth || 0)}%
                        </span>
                        <span className="text-stone-400"> vs thn lalu</span>
                    </>} />

                <Kartu label="Pengeluaran" nilai={`Rp ${formatNumber(stats?.totalExpenses || 0)}`}
                    warna="bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400" ikon={I.keluar}
                    sub={<>
                        <span className={stats?.expensesGrowth >= 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'font-semibold text-emerald-600 dark:text-emerald-400'}>
                            {naik(stats?.expensesGrowth)} {Math.abs(stats?.expensesGrowth || 0)}%
                        </span>
                        <span className="text-stone-400"> vs thn lalu</span>
                    </>} />

                <Kartu label="Saldo Bersih" nilai={`Rp ${formatNumber(stats?.savings || 0)}`}
                    warna="bg-claude-500/10 text-claude-600 dark:text-claude-300" ikon={I.saldo}
                    sub={<span className="text-stone-400">tahun berjalan</span>} />

                <Kartu label="Siswa Menunggak" nilai={`${stats?.arrearsPercentage || 0}%`}
                    warna="bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400" ikon={I.tunggak}
                    sub={<span className="text-stone-400">dari siswa aktif</span>} />
            </div>

            <div className="mb-5 grid grid-cols-1 gap-4 sm:mb-6 lg:grid-cols-2">
                <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                    <div className="mb-4 flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Perbandingan Keuangan</h3>
                            <p className="mt-0.5 text-xs text-stone-400">Pemasukan vs pengeluaran (juta rupiah)</p>
                        </div>
                        <span className="shrink-0 rounded-full bg-cream-200 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:bg-coal-600 dark:text-stone-300">6 bulan</span>
                    </div>
                    <div className="h-[220px] w-full sm:h-[260px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={monthlyData || []} margin={marginChart} barGap={kecil ? 2 : 4}>
                                <CartesianGrid strokeDasharray="3 3" stroke={grid} vertical={false} />
                                <XAxis dataKey="month" tick={{ fontSize: fontSumbu, fill: sumbu }} axisLine={false} tickLine={false}
                                    interval={0} />
                                <YAxis width={lebarY} tick={{ fontSize: fontSumbu, fill: sumbu }} axisLine={false} tickLine={false}
                                    tickFormatter={(v) => `${v}Jt`} />
                                <Tooltip contentStyle={tooltipStyle} cursor={{ fill: dark ? '#ffffff08' : '#00000006' }}
                                    formatter={(v, n) => [`Rp ${v} Jt`, n]} />
                                <Legend wrapperStyle={{ fontSize: kecil ? 11 : 12, paddingTop: 8 }} iconType="circle" iconSize={8} />
                                <Bar dataKey="income" name="Pemasukan" fill={WARNA.hijau} radius={[6, 6, 0, 0]} maxBarSize={kecil ? 14 : 26} />
                                <Bar dataKey="expenses" name="Pengeluaran" fill={WARNA.merah} radius={[6, 6, 0, 0]} maxBarSize={kecil ? 14 : 26} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                    <div className="mb-4">
                        <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Tren Tunggakan</h3>
                        <p className="mt-0.5 text-xs text-stone-400">Persentase siswa dengan tagihan belum lunas</p>
                    </div>
                    <div className="h-[220px] w-full sm:h-[260px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={arrearsTrend || []} margin={marginChart}>
                                <defs>
                                    <linearGradient id="gradTunggakan" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={WARNA.oranye} stopOpacity={0.28} />
                                        <stop offset="100%" stopColor={WARNA.oranye} stopOpacity={0.02} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" stroke={grid} vertical={false} />
                                <XAxis dataKey="month" tick={{ fontSize: fontSumbu, fill: sumbu }} axisLine={false} tickLine={false}
                                    interval={0} />
                                <YAxis width={lebarY} tick={{ fontSize: fontSumbu, fill: sumbu }} axisLine={false} tickLine={false}
                                    tickFormatter={(v) => `${v}%`} />
                                <Tooltip contentStyle={tooltipStyle} formatter={(v) => [`${v}%`, 'Tunggakan']} />
                                <Area type="monotone" dataKey="percentage" stroke={WARNA.oranye} strokeWidth={2.5}
                                    fill="url(#gradTunggakan)" dot={{ r: kecil ? 2.5 : 3, fill: WARNA.oranye }} activeDot={{ r: 5 }} />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Transaksi judul="Pemasukan Terbaru" items={recentIncome} kosong="Belum ada transaksi pemasukan" positif />
                <Transaksi judul="Pengeluaran Terbaru" items={recentExpenses} kosong="Belum ada transaksi pengeluaran" />
            </div>
        </KeuanganLayout>
    );
}
import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { formatCurrency, formatNumber } from '@/Utils/formatCurrency';
import {
    BarChart, Bar, AreaChart, Area, XAxis, YAxis,
    CartesianGrid, Tooltip, Legend, ResponsiveContainer,
} from 'recharts';

// Recharts memakai atribut SVG, jadi warnanya tidak bisa lewat kelas Tailwind.
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

function Kartu({ label, nilai, sub, warna, ikon }) {
    return (
        <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 transition hover:ring-stone-300 dark:bg-coal-700 dark:ring-coal-600">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">{label}</p>
                    <h3 className="mt-2 truncate text-2xl font-bold text-stone-800 dark:text-cream-50">{nilai}</h3>
                    <div className="mt-1.5 text-xs">{sub}</div>
                </div>
                <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${warna}`}>
                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                        <path strokeLinecap="round" strokeLinejoin="round" d={ikon} />
                    </svg>
                </div>
            </div>
        </div>
    );
}

export default function Dashboard({ stats, monthlyData, arrearsTrend, recentIncome, recentExpenses, actionSummary, notifications }) {
    const dark = useDarkMode();

    const grid  = dark ? '#3E3D3A' : '#EDEBE4';
    const sumbu = dark ? '#9c9a92' : '#73726c';

    const tooltipStyle = {
        backgroundColor: dark ? '#262624' : '#ffffff',
        border: `1px solid ${grid}`,
        borderRadius: '12px',
        fontSize: '12px',
        color: dark ? '#F5F4EF' : '#3d3d3a',
    };

    const I = {
        masuk:  'M12 4v16m8-8H4',
        keluar: 'M20 12H4',
        saldo:  'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        tunggak:'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    };

    const naik = (v) => (v >= 0 ? '↑' : '↓');

    const Transaksi = ({ judul, items, kosong, positif }) => (
        <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600">
            <h3 className="mb-4 text-sm font-semibold text-stone-800 dark:text-cream-50">{judul}</h3>
            <div className="space-y-3">
                {items?.length > 0 ? items.map((item) => (
                    <div key={item.id} className="flex items-center justify-between gap-3 border-b border-stone-100 pb-3 last:border-0 last:pb-0 dark:border-coal-600">
                        <div className="min-w-0">
                            <p className="truncate text-sm font-medium text-stone-800 dark:text-cream-100">{item.description}</p>
                            <p className="text-xs text-stone-400">{item.date} · {item.method || item.category}</p>
                        </div>
                        <span className={`shrink-0 text-sm font-semibold ${positif ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-700 dark:text-stone-200'}`}>
                            {positif ? '+' : '−'} {formatCurrency(item.amount)}
                        </span>
                    </div>
                )) : (
                    <p className="py-6 text-center text-sm text-stone-400">{kosong}</p>
                )}
            </div>
        </div>
    );

    return (
        <KeuanganLayout>
            <Head title="Dashboard Keuangan" />

            <div className="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Dashboard Keuangan</h1>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Ringkasan arus kas dan dokumen yang menunggu tindakan.</p>
                </div>
                <p className="text-xs text-stone-400">
                    {new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                </p>
            </div>

            {notifications?.length > 0 && (
                <div className="mb-6 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20">
                    <div className="mb-3 flex items-center gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p className="text-sm font-semibold text-amber-900 dark:text-amber-200">{actionSummary}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {notifications.map((item) => (
                            <Link key={item.key} href={item.url}
                                className="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-medium text-stone-700 ring-1 ring-amber-200 transition hover:ring-amber-400 dark:bg-coal-700 dark:text-stone-200 dark:ring-amber-500/20">
                                <span className="max-w-[220px] truncate">{item.title}</span>
                                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                    {item.count}
                                </span>
                            </Link>
                        ))}
                    </div>
                </div>
            )}

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Kartu label="Total Pemasukan" nilai={`Rp ${formatNumber(stats?.totalIncome || 0)}`}
                    warna="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400" ikon={I.masuk}
                    sub={<>
                        <span className={stats?.incomeGrowth >= 0 ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'font-semibold text-red-600 dark:text-red-400'}>
                            {naik(stats?.incomeGrowth)} {Math.abs(stats?.incomeGrowth || 0)}%
                        </span>
                        <span className="text-stone-400"> vs tahun lalu</span>
                    </>} />

                <Kartu label="Total Pengeluaran" nilai={`Rp ${formatNumber(stats?.totalExpenses || 0)}`}
                    warna="bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400" ikon={I.keluar}
                    sub={<>
                        <span className={stats?.expensesGrowth >= 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'font-semibold text-emerald-600 dark:text-emerald-400'}>
                            {naik(stats?.expensesGrowth)} {Math.abs(stats?.expensesGrowth || 0)}%
                        </span>
                        <span className="text-stone-400"> vs tahun lalu</span>
                    </>} />

                <Kartu label="Saldo Bersih" nilai={`Rp ${formatNumber(stats?.savings || 0)}`}
                    warna="bg-claude-500/10 text-claude-600 dark:text-claude-300" ikon={I.saldo}
                    sub={<span className="text-stone-400">tahun berjalan</span>} />

                <Kartu label="Siswa Menunggak" nilai={`${stats?.arrearsPercentage || 0}%`}
                    warna="bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400" ikon={I.tunggak}
                    sub={<span className="text-stone-400">dari siswa aktif</span>} />
            </div>

            <div className="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600">
                    <div className="mb-4 flex items-start justify-between">
                        <div>
                            <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Perbandingan Keuangan</h3>
                            <p className="mt-0.5 text-xs text-stone-400">Pemasukan vs pengeluaran (juta rupiah)</p>
                        </div>
                        <span className="rounded-full bg-stone-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:bg-coal-600 dark:text-stone-300">6 bulan</span>
                    </div>
                    <div className="h-[260px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={monthlyData || []} margin={{ top: 5, right: 5, left: -20, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke={grid} vertical={false} />
                                <XAxis dataKey="month" tick={{ fontSize: 11, fill: sumbu }} axisLine={false} tickLine={false} />
                                <YAxis tick={{ fontSize: 11, fill: sumbu }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v}Jt`} />
                                <Tooltip contentStyle={tooltipStyle} cursor={{ fill: dark ? '#ffffff08' : '#00000006' }}
                                    formatter={(v, n) => [`Rp ${v} Jt`, n]} />
                                <Legend wrapperStyle={{ fontSize: 12, paddingTop: 8 }} iconType="circle" iconSize={8} />
                                <Bar dataKey="income" name="Pemasukan" fill="#10b981" radius={[6, 6, 0, 0]} maxBarSize={26} />
                                <Bar dataKey="expenses" name="Pengeluaran" fill="#ef4444" radius={[6, 6, 0, 0]} maxBarSize={26} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600">
                    <div className="mb-4">
                        <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Tren Tunggakan</h3>
                        <p className="mt-0.5 text-xs text-stone-400">Persentase siswa dengan tagihan belum lunas</p>
                    </div>
                    <div className="h-[260px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={arrearsTrend || []} margin={{ top: 5, right: 5, left: -20, bottom: 0 }}>
                                <defs>
                                    <linearGradient id="gradTunggakan" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor="#D97757" stopOpacity={0.28} />
                                        <stop offset="100%" stopColor="#D97757" stopOpacity={0.02} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" stroke={grid} vertical={false} />
                                <XAxis dataKey="month" tick={{ fontSize: 11, fill: sumbu }} axisLine={false} tickLine={false} />
                                <YAxis tick={{ fontSize: 11, fill: sumbu }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v}%`} />
                                <Tooltip contentStyle={tooltipStyle} formatter={(v) => [`${v}%`, 'Tunggakan']} />
                                <Area type="monotone" dataKey="percentage" stroke="#D97757" strokeWidth={2.5}
                                    fill="url(#gradTunggakan)" dot={{ r: 3, fill: '#D97757' }} activeDot={{ r: 5 }} />
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
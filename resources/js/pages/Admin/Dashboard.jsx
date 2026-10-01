import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ResponsiveContainer, AreaChart, Area, BarChart, Bar,
    XAxis, YAxis, CartesianGrid, Tooltip, Cell,
} from 'recharts';

export default function Dashboard({ stats = {}, tahunAjaranAktif = null, loginTrend = [], recentActivities = [] }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const permissions = auth.permissions || [];
    const can = (p) => permissions.includes(p);
    const isSuperAdmin = can('kelola-admin');

    const [showGuide, setShowGuide] = useState(false);

    const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // ===== Kartu statistik =====
    const statCards = [
        isSuperAdmin && {
            label: 'Total Admin', value: stats.totalAdmins ?? 0,
            sub: `${stats.totalSuperAdmins ?? 0} Super · ${stats.totalRegularAdmins ?? 0} Biasa`,
            color: 'claude',
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        },
        { label: 'Total Guru', value: stats.totalGurus ?? 0, sub: 'Pengajar aktif', color: 'blue',
          icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
        { label: 'Total Kelas', value: stats.totalKelas ?? 0, sub: 'Rombongan belajar', color: 'emerald',
          icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
        { label: 'Total Santri', value: stats.totalSantris ?? 0, sub: 'Peserta didik', color: 'violet',
          icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    ].filter(Boolean);

    // Jumlah kolom mengikuti jumlah kartu, bukan dipatok empat. Admin biasa
    // tidak mendapat kartu 'Total Admin', sehingga tiga kartu di grid empat
    // kolom menyisakan satu sel kosong di kanan — terbaca sebagai kartu yang
    // gagal dimuat, bukan sebagai kartu yang memang tidak ada.
    //
    // Ditulis sebagai string utuh, bukan dirangkai (`lg:grid-cols-${n}`),
    // karena Tailwind memindai teks sumber dan tidak bisa melihat kelas
    // yang baru terbentuk saat dijalankan.
    const kolomStat = {
        1: 'grid-cols-1',
        2: 'grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-3',
        4: 'grid-cols-2 lg:grid-cols-4',
    }[statCards.length] ?? 'grid-cols-2 lg:grid-cols-4';

    const iconTint = {
        claude: 'bg-claude-100 text-claude-600 dark:bg-claude-500/20 dark:text-claude-300',
        blue: 'bg-blue-100 text-blue-600 dark:bg-blue-400/15 dark:text-blue-300',
        emerald: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-300',
        violet: 'bg-violet-100 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300',
        indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-400/15 dark:text-indigo-300',
        pink: 'bg-pink-100 text-pink-600 dark:bg-pink-400/15 dark:text-pink-300',
        rose: 'bg-rose-100 text-rose-600 dark:bg-rose-400/15 dark:text-rose-300',
    };

    const card = 'rounded-2xl border border-cream-300 bg-white dark:border-coal-600 dark:bg-coal-700';

    // ===== Data grafik =====
    const trendData = (loginTrend || []).map((d) => ({
        ...d,
        label: new Date(d.date).toLocaleDateString('id-ID', { weekday: 'short' }),
    }));

    // Kategori Admin hanya ikut bila penggunanya memang berhak melihat
    // angkanya. Tanpa ini admin biasa melihat batang 'Admin' setinggi nol —
    // yang terbaca sebagai data hilang, padahal datanya memang tidak dikirim.
    const komposisiData = [
        isSuperAdmin && { label: 'Admin', value: stats.totalAdmins ?? 0, fill: '#D97757' },
        { label: 'Guru', value: stats.totalGurus ?? 0, fill: '#6A9BCC' },
        { label: 'Santri', value: stats.totalSantris ?? 0, fill: '#C2A6E1' },
    ].filter(Boolean);

    // ===== Aktivitas: gaya per tipe =====
    const actStyle = {
        login: { color: 'emerald', icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' },
        logout: { color: 'rose', icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' },
        create: { color: 'blue', icon: 'M12 4v16m8-8H4' },
        update: { color: 'claude', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
        delete: { color: 'rose', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' },
    };
    const relTime = (iso) => {
        if (!iso) return '';
        const diff = (Date.now() - new Date(iso).getTime()) / 1000;
        if (diff < 60) return 'baru saja';
        if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
        return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    };

    // Tooltip kustom (light & dark)
    const ChartTip = ({ active, payload, label }) => {
        if (!active || !payload?.length) return null;
        return (
            <div className="rounded-lg border border-cream-300 bg-white px-3 py-2 text-xs shadow-lg dark:border-coal-500 dark:bg-coal-800">
                <p className="font-semibold text-stone-700 dark:text-stone-200">{label}</p>
                <p className="text-claude-600 dark:text-claude-400">{payload[0].value} aktivitas</p>
            </div>
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* ===== Header bar ===== */}
                <div className={`${card} flex items-center justify-between px-5 py-4`}>
                    <div>
                        <h1 className="text-lg font-bold text-stone-900 dark:text-cream-50">Dashboard</h1>
                        <p className="text-xs text-stone-400 dark:text-stone-500">Selamat datang, {user.name}</p>
                    </div>
                    <div className="hidden items-center gap-2 sm:flex">
                        {tahunAjaranAktif && (
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20">
                                <span className="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                {tahunAjaranAktif.label_pendek}
                            </span>
                        )}
                        <span className="text-xs font-medium text-stone-400 dark:text-stone-500">{today}</span>
                    </div>
                </div>

                {/* ===== Stat cards ===== */}
                <div className={`grid gap-4 ${kolomStat}`}>
                    {statCards.map((s, i) => (
                        <div key={i} className={`${card} p-5 transition hover:shadow-md hover:shadow-cream-300/50 dark:hover:border-coal-500 dark:hover:shadow-none`}>
                            <div className="mb-3 flex items-center justify-between">
                                <p className="text-xs font-medium uppercase tracking-wider text-stone-400 dark:text-stone-500">{s.label}</p>
                                <div className={`flex h-9 w-9 items-center justify-center rounded-full ${iconTint[s.color]}`}>
                                    <svg style={{ width: 18, height: 18 }} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={s.icon} /></svg>
                                </div>
                            </div>
                            <h3 className="text-3xl font-bold tracking-tight text-stone-900 dark:text-cream-50">{s.value}</h3>
                            {s.sub && <p className="mt-1.5 text-xs text-stone-400 dark:text-stone-500">{s.sub}</p>}
                        </div>
                    ))}
                </div>

                {/* ===== Charts ===== */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {/* Tren login */}
                    <div className={`${card} p-5 lg:col-span-2`}>
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-sm font-bold text-stone-800 dark:text-cream-50">Aktivitas Login</h2>
                                <p className="text-xs text-stone-400 dark:text-stone-500">7 hari terakhir</p>
                            </div>
                            <span className="rounded-full bg-claude-50 px-2.5 py-1 text-[10px] font-semibold text-claude-600 ring-1 ring-inset ring-claude-200 dark:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/25">MINGGU INI</span>
                        </div>
                        <div style={{ width: '100%', height: 240 }}>
                            <ResponsiveContainer>
                                <AreaChart data={trendData} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="loginFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#D97757" stopOpacity={0.35} />
                                            <stop offset="95%" stopColor="#D97757" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#a8a29e" strokeOpacity={0.2} vertical={false} />
                                    <XAxis dataKey="label" tick={{ fontSize: 11, fill: '#a8a29e' }} axisLine={false} tickLine={false} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11, fill: '#a8a29e' }} axisLine={false} tickLine={false} width={35} />
                                    <Tooltip content={<ChartTip />} />
                                    <Area type="monotone" dataKey="count" stroke="#D97757" strokeWidth={2.5} fill="url(#loginFill)" dot={{ r: 3, fill: '#D97757' }} activeDot={{ r: 5 }} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Komposisi pengguna */}
                    <div className={`${card} p-5`}>
                        <div className="mb-4">
                            <h2 className="text-sm font-bold text-stone-800 dark:text-cream-50">Komposisi Pengguna</h2>
                            <p className="text-xs text-stone-400 dark:text-stone-500">{komposisiData.map((d) => d.label).join(' · ')}</p>
                        </div>
                        <div style={{ width: '100%', height: 240 }}>
                            <ResponsiveContainer>
                                <BarChart data={komposisiData} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#a8a29e" strokeOpacity={0.2} vertical={false} />
                                    <XAxis dataKey="label" tick={{ fontSize: 11, fill: '#a8a29e' }} axisLine={false} tickLine={false} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11, fill: '#a8a29e' }} axisLine={false} tickLine={false} width={35} />
                                    <Tooltip cursor={{ fill: '#a8a29e14' }} content={({ active, payload, label }) => (active && payload?.length) ? (
                                        <div className="rounded-lg border border-cream-300 bg-white px-3 py-2 text-xs shadow-lg dark:border-coal-500 dark:bg-coal-800">
                                            <p className="font-semibold text-stone-700 dark:text-stone-200">{label}</p>
                                            <p className="text-stone-500 dark:text-stone-400">{payload[0].value} akun</p>
                                        </div>
                                    ) : null} />
                                    <Bar dataKey="value" radius={[6, 6, 0, 0]} maxBarSize={48}>
                                        {komposisiData.map((d, i) => <Cell key={i} fill={d.fill} />)}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>

                {/* ===== Aktivitas terkini (full width — menu dipindah ke sidebar) ===== */}
                <div className={`${card} p-5`}>
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-sm font-bold text-stone-800 dark:text-cream-50">Aktivitas Terkini</h2>
                        {can('lihat-log-activity') && (
                            <Link href={route('admin.logs.index')} className="text-xs font-medium text-claude-600 hover:text-claude-500 dark:text-claude-400">Lihat semua</Link>
                        )}
                    </div>
                    {recentActivities.length === 0 ? (
                        <p className="py-8 text-center text-xs text-stone-400 dark:text-stone-500">Belum ada aktivitas tercatat.</p>
                    ) : (
                        <div className="grid grid-cols-1 gap-x-6 gap-y-1 md:grid-cols-2">
                            {recentActivities.map((a, i) => {
                                const st = actStyle[a.type] || { color: 'blue', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' };
                                return (
                                    <div key={i} className="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-cream-100 dark:hover:bg-coal-600/40">
                                        <div className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full ${iconTint[st.color]}`}>
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d={st.icon} /></svg>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-xs font-medium text-stone-700 dark:text-stone-200">{a.description || a.type}</p>
                                            <p className="text-[10px] capitalize text-stone-400 dark:text-stone-500">{a.role || '—'}</p>
                                        </div>
                                        <span className="flex-shrink-0 text-[10px] text-stone-400 dark:text-stone-500">{relTime(a.created_at)}</span>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* ===== Panduan (collapsible) ===== */}
                <div className={`${card} p-5`}>
                    <button onClick={() => setShowGuide(!showGuide)} className="flex w-full items-center justify-between">
                        <h2 className="flex items-center gap-2.5 text-sm font-bold text-stone-800 dark:text-cream-50">
                            Panduan Penggunaan
                            <span className="rounded-full bg-claude-50 px-2 py-0.5 text-[10px] font-medium text-claude-600 ring-1 ring-inset ring-claude-200 dark:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/25">6 langkah</span>
                        </h2>
                        <svg className={`h-4 w-4 text-stone-400 transition-transform ${showGuide ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    {showGuide && (
                        <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {[
                                ['1', 'Atur Tahun Ajaran', 'Buat periode semester lalu aktifkan satu untuk mulai input data.'],
                                ['2', 'Kelola Data Master', 'Tambahkan guru, kelas, dan santri sebelum monitoring.'],
                                ['3', 'Atur Sesi Presensi', 'Buat jadwal waktu presensi; bisa diubah kapan saja.'],
                                ['4', 'Monitoring Real-time', 'Pantau progress setoran santri per semester.'],
                                ['5', 'Tutup Semester', 'Arsipkan data dan aktifkan semester berikutnya.'],
                                ['6', 'Audit Trail', 'Gunakan Log Activity untuk melacak aktivitas pengguna.'],
                            ].map(([no, title, desc]) => (
                                <div key={no} className="flex items-start gap-3 rounded-xl bg-cream-100 p-3 dark:bg-coal-600/40">
                                    <div className={`flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full ${iconTint.claude}`}>
                                        <span className="text-[11px] font-bold">{no}</span>
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <h4 className="mb-0.5 text-xs font-semibold text-stone-800 dark:text-stone-100">{title}</h4>
                                        <p className="text-[10px] leading-relaxed text-stone-500 dark:text-stone-400">{desc}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
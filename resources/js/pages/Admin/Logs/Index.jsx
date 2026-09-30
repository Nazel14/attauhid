import { useState, useEffect } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/* ============================================================
 * ICONS (inline, tanpa dependency)
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const XIcon = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const TrashIcon = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const FilterIcon = (p) => <I {...p} d={<path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />} />;
const RefreshIcon = (p) => <I {...p} d={<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const InfoIcon = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const ClipboardIcon = (p) => <I {...p} d={<path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />} />;
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />} />;
const ShieldIcon = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />} />;
const TeacherIcon = (p) => <I {...p} d={<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />} />;
const StudentIcon = (p) => <I {...p} d={<><path d="M12 14l9-5-9-5-9 5 9 5z" /><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></>} />;
const DocIcon = (p) => <I {...p} d={<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;

/* ============================================================
 * SHARED CLASSES
 * ============================================================ */
const INP = "w-full bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-600";
const LBL = "block text-[11px] font-semibold text-stone-600 dark:text-stone-400 mb-1.5";
const BTN_SECONDARY = "flex-1 py-2.5 rounded-xl text-sm font-semibold bg-stone-100 dark:bg-coal-700 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-coal-600 hover:bg-stone-200 dark:hover:bg-coal-600 transition";
const CHIP = "inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border";
const DEFAULT_CHIP = "bg-stone-100 text-stone-600 border-stone-300 dark:bg-coal-700 dark:text-stone-500 dark:border-coal-600";

const ROLE_COLORS = {
    'super-admin': 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30',
    bendahara: 'bg-teal-100 text-teal-700 border-teal-300 dark:bg-teal-950/50 dark:text-teal-400 dark:border-teal-800/30',
    'kepala-sekolah': 'bg-rose-100 text-rose-700 border-rose-300 dark:bg-rose-950/50 dark:text-rose-400 dark:border-rose-800/30',
    'ketua-yayasan': 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-300 dark:bg-fuchsia-950/50 dark:text-fuchsia-400 dark:border-fuchsia-800/30',
    'pihak-yayasan': 'bg-violet-100 text-violet-700 border-violet-300 dark:bg-violet-950/50 dark:text-violet-400 dark:border-violet-800/30',
    hrd: 'bg-sky-100 text-sky-700 border-sky-300 dark:bg-sky-950/50 dark:text-sky-400 dark:border-sky-800/30',
    admin: 'bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-950/50 dark:text-purple-400 dark:border-purple-800/30',
    guru: 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30',
    santri: 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30',
};
const ACTIVITY_COLORS = {
    login: 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30',
    logout: DEFAULT_CHIP,
    create: 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30',
    update: 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30',
    delete: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800/30',
    download: 'bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-950/50 dark:text-orange-400 dark:border-orange-800/30',
    view: 'bg-indigo-100 text-indigo-700 border-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-400 dark:border-indigo-800/30',
    export: 'bg-teal-100 text-teal-700 border-teal-300 dark:bg-teal-950/50 dark:text-teal-400 dark:border-teal-800/30',
    import: 'bg-cyan-100 text-cyan-700 border-cyan-300 dark:bg-cyan-950/50 dark:text-cyan-400 dark:border-cyan-800/30',
    submit: 'bg-violet-100 text-violet-700 border-violet-300 dark:bg-violet-950/50 dark:text-violet-400 dark:border-violet-800/30',
    approve: 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30',
    reject: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800/30',
};
const ACTIVITY_DOT = {
    login: 'bg-emerald-400', logout: 'bg-stone-400', create: 'bg-blue-400', update: 'bg-amber-400',
    delete: 'bg-red-400', download: 'bg-orange-400', view: 'bg-indigo-400', export: 'bg-teal-400',
    import: 'bg-cyan-400', submit: 'bg-violet-400', approve: 'bg-emerald-400', reject: 'bg-red-400',
};

// Warna untuk tiga kartu role teratas, dipakai berurutan.
const KARTU_ROLE = [
    { nilai: 'text-purple-600 dark:text-purple-400',  latar: 'bg-purple-100 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20',    warna: 'text-purple-600 dark:text-purple-400',  ikon: <ShieldIcon /> },
    { nilai: 'text-orange-600 dark:text-orange-400',  latar: 'bg-orange-100 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20',    warna: 'text-orange-600 dark:text-orange-400',  ikon: <TeacherIcon /> },
    { nilai: 'text-emerald-600 dark:text-emerald-400', latar: 'bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20', warna: 'text-emerald-600 dark:text-emerald-400', ikon: <StudentIcon /> },
];

const nf = (n) => Number(n ?? 0).toLocaleString('id-ID');
// Menangani role bertanda hubung: 'kepala-sekolah' -> 'Kepala Sekolah'.
const capitalize = (s) => (s ? String(s).split('-').map((k) => k.charAt(0).toUpperCase() + k.slice(1)).join(' ') : s);

/* ============================================================
 * SUB-COMPONENTS
 * ============================================================ */
function Toast({ notif, onClose, duration = 10000 }) {
    if (!notif) return null;
    const success = notif.type === 'success';
    return (
        <>
            <div
                key={notif.id}
                className="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 z-[300] overflow-hidden rounded-2xl border border-stone-200 dark:border-coal-700 bg-white/95 dark:bg-coal-800/95 shadow-2xl backdrop-blur-md"
                style={{ animation: 'toastIn .3s cubic-bezier(.21,1.02,.73,1)' }}
                role="alert"
            >
                <div className="flex items-start gap-3 p-3.5">
                    <span className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full ${success ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300'}`}>
                        {success ? <CheckCircle className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
                    </span>
                    <div className="min-w-0 flex-1 pt-0.5">
                        <p className={`text-xs font-bold ${success ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'}`}>
                            {success ? 'Berhasil' : 'Gagal'}
                        </p>
                        <p className="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-300" dangerouslySetInnerHTML={{ __html: notif.message }} />
                    </div>
                    <button onClick={onClose} className="flex-shrink-0 rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-600 dark:hover:bg-coal-700 dark:hover:text-stone-200 transition">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                <div className="h-1 w-full bg-stone-200 dark:bg-coal-700">
                    <div className={`h-full ${success ? 'bg-emerald-500' : 'bg-red-500'}`}
                        style={{ animation: `toastBar ${duration}ms linear forwards` }} />
                </div>
            </div>
            <style>{`
                @keyframes toastIn { from { opacity: 0; transform: translateY(-14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
                @keyframes toastBar { from { width: 100%; } to { width: 0%; } }
            `}</style>
        </>
    );
}

function Modal({ open, onClose, title, children, maxWidth = 'sm:max-w-md' }) {
    useEffect(() => {
        if (open) document.body.classList.add('overflow-hidden');
        else document.body.classList.remove('overflow-hidden');
        return () => document.body.classList.remove('overflow-hidden');
    }, [open]);
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-[80] flex flex-col justify-end sm:justify-center sm:items-center sm:p-4">
            <div className="absolute inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm" onClick={onClose} />
            <div className={`relative w-full bg-white dark:bg-coal-800 rounded-t-3xl sm:rounded-3xl border border-stone-200 dark:border-coal-700 shadow-2xl overflow-hidden animate-[slideUp_.25s_ease-out] ${maxWidth}`}>
                <div className="w-10 h-1 bg-stone-300 dark:bg-coal-600 rounded-full mx-auto mt-3 sm:hidden" />
                <div className="flex items-center justify-between px-5 py-3.5 border-b border-stone-100 dark:border-coal-700">
                    <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">{title}</h3>
                    <button onClick={onClose} className="w-7 h-7 rounded-full bg-stone-100 dark:bg-coal-700 flex items-center justify-center text-stone-500 dark:text-stone-400 hover:bg-stone-200 dark:hover:bg-coal-600 transition">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

function StatCard({ label, value, valueClass, icon, iconBg, iconColor, wide = false }) {
    return (
        <div className={`bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl px-4 py-4 shadow-sm dark:shadow-none ${wide ? 'col-span-2 lg:col-span-1' : ''}`}>
            <p className="text-[11px] text-stone-500 font-medium mb-1">{label}</p>
            <p className={`text-2xl font-black ${valueClass || 'text-stone-900 dark:text-stone-100'}`}>{value}</p>
            <div className={`w-8 h-8 rounded-lg flex items-center justify-center mt-2 ${iconBg}`}>
                <span className={`w-4 h-4 ${iconColor}`}>{icon}</span>
            </div>
        </div>
    );
}

function Pagination({ links = [] }) {
    if (links.length <= 3) return null;
    return (
        <div className="flex flex-wrap items-center gap-1 justify-center">
            {links.map((link, i) =>
                link.url === null ? (
                    <span key={i} className="px-3 py-1.5 text-xs rounded-lg text-stone-300 dark:text-stone-700 cursor-not-allowed"
                        dangerouslySetInnerHTML={{ __html: link.label }} />
                ) : (
                    <Link key={i} href={link.url} preserveScroll
                        className={`px-3 py-1.5 text-xs rounded-lg border transition ${link.active
                            ? 'bg-claude-600 text-white border-claude-600'
                            : 'bg-white dark:bg-coal-800 text-stone-600 dark:text-stone-400 border-stone-200 dark:border-coal-700 hover:bg-stone-100 dark:hover:bg-coal-700'}`}
                        dangerouslySetInnerHTML={{ __html: link.label }} />
                )
            )}
        </div>
    );
}

/* ============================================================
 * MAIN PAGE
 * ============================================================ */
export default function Index({ logs, users = [], roles = [], modules = {}, activityTypes = {}, stats = {}, filters = {}, canManage = false }) {
    const { flash = {} } = usePage().props;
    const list = logs?.data ?? [];
    const activeTab = filters.tab || 'all';

    /* ---- Toast dari flash ---- */
    const NOTIF_DURATION = 10000;
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success || flash?.error) {
            setNotif({ message: flash.success || flash.error, type: flash.success ? 'success' : 'error', id: Date.now() });
            const t = setTimeout(() => setNotif(null), NOTIF_DURATION);
            return () => clearTimeout(t);
        }
    }, [flash]);

    /* ---- Helper build query (buang value kosong) ---- */
    const buildQuery = (overrides = {}) => {
        const merged = { ...filters, ...overrides };
        return Object.fromEntries(Object.entries(merged).filter(([, v]) => v !== '' && v !== null && v !== undefined));
    };

    const tabs = [
        { key: 'all', label: 'Semua' },
        { key: 'auth', label: 'Autentikasi' },
        { key: 'crud', label: 'CRUD' },
        { key: 'admin', label: 'Admin Only' },
        { key: 'guru', label: 'Guru Only' },
        { key: 'santri', label: 'Santri Only' },
    ];

    /* ---- Form Filter (GET) ---- */
    const filterForm = useForm({
        role: filters.role || '',
        module: filters.module || '',
        activity_type: filters.activity_type || '',
        user_id: filters.user_id || '',
        date_from: filters.date_from || '',
        date_to: filters.date_to || '',
        search: filters.search || '',
    });

    const submitFilter = (e) => {
        e.preventDefault();
        filterForm.get(route('admin.logs.index', buildQuery({ tab: activeTab, ...filterForm.data })), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters = activeTab !== 'all' ||
        ['role', 'module', 'activity_type', 'user_id', 'date_from', 'date_to', 'search'].some((k) => filters[k]);

    /* ---- Cleanup ---- */
    const [cleanupOpen, setCleanupOpen] = useState(false);
    const cleanupForm = useForm({ days: '30' });
    const submitCleanup = (e) => {
        e.preventDefault();
        cleanupForm.post(route('admin.logs.cleanup'), {
            preserveScroll: true,
            onSuccess: () => setCleanupOpen(false),
        });
    };

    /* ---- Delete ---- */
    const deleteLog = (log) => {
        if (confirm('Apakah Anda yakin ingin menghapus log ini?')) {
            router.delete(route('admin.logs.destroy', log.id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Log Activity" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            <div className="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 py-6">
            {/* Tombol kembali — mobile saja */}
            <Link
                href={route('admin.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-stone-600 shadow-sm transition hover:bg-cream-100 active:scale-95 sm:hidden dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:bg-coal-600"
            >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali ke Dashboard
            </Link>
                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <DocIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100">Log Activity</h1>
                        <p className="text-[11px] text-stone-500 dark:text-stone-500 mt-0.5">Monitor semua aktivitas pengguna di sistem</p>
                    </div>
                    {canManage && (
                        <button onClick={() => setCleanupOpen(true)}
                            className="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-500 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                            <TrashIcon className="w-3.5 h-3.5" />
                            <span className="hidden sm:inline">Cleanup Logs</span>
                        </button>
                    )}
                </div>

                {/* ===== STATS CARDS ===== */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
                    <StatCard label="Total Logs" value={nf(stats.total)}
                        icon={<ClipboardIcon />} iconBg="bg-blue-100 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20" iconColor="text-blue-600 dark:text-blue-400" />
                    <StatCard label="Hari Ini" value={nf(stats.today)} valueClass="text-emerald-600 dark:text-emerald-400"
                        icon={<CalendarIcon />} iconBg="bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20" iconColor="text-emerald-600 dark:text-emerald-400" />
                    {/* Tiga role teraktif, dibaca dari data. Sebelumnya Admin,
                        Guru, dan Santri ditulis tetap — sehingga aktivitas
                        bendahara, super-admin, dan role yayasan tidak pernah
                        terlihat meski jumlahnya besar. */}
                    {Object.entries(stats.by_role ?? {}).slice(0, 3).map(([role, jumlah], i) => (
                        <StatCard
                            key={role}
                            label={capitalize(role)}
                            value={nf(jumlah)}
                            wide={i === 2}
                            valueClass={KARTU_ROLE[i].nilai}
                            icon={KARTU_ROLE[i].ikon}
                            iconBg={KARTU_ROLE[i].latar}
                            iconColor={KARTU_ROLE[i].warna}
                        />
                    ))}
                </div>

                {/* ===== FILTER SECTION ===== */}
                <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl p-4 mb-5 shadow-sm dark:shadow-none">
                    <p className="text-xs font-bold text-stone-600 dark:text-stone-400 mb-3 flex items-center gap-1.5">
                        <FilterIcon className="w-3.5 h-3.5" /> Filter Data
                    </p>
                    <form onSubmit={submitFilter} className="space-y-3">
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                            <div>
                                <label className={LBL}>Role</label>
                                <select value={filterForm.data.role} onChange={(e) => filterForm.setData('role', e.target.value)} className={INP}>
                                    <option value="">Semua Role</option>
                                    {roles.map((r) => <option key={r} value={r}>{capitalize(r)}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className={LBL}>Module</label>
                                <select value={filterForm.data.module} onChange={(e) => filterForm.setData('module', e.target.value)} className={INP}>
                                    <option value="">Semua Module</option>
                                    {Object.entries(modules).map(([key, val]) => <option key={key} value={key}>{val}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className={LBL}>Tipe Aktivitas</label>
                                <select value={filterForm.data.activity_type} onChange={(e) => filterForm.setData('activity_type', e.target.value)} className={INP}>
                                    <option value="">Semua Tipe</option>
                                    {Object.entries(activityTypes).map(([key, val]) => <option key={key} value={key}>{val}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className={LBL}>User</label>
                                <select value={filterForm.data.user_id} onChange={(e) => filterForm.setData('user_id', e.target.value)} className={INP}>
                                    <option value="">Semua User</option>
                                    {users.map((u) => <option key={u.id} value={u.id}>{u.name} ({capitalize(u.role)})</option>)}
                                </select>
                            </div>
                            <div>
                                <label className={LBL}>Dari Tanggal</label>
                                <input type="date" value={filterForm.data.date_from} onChange={(e) => filterForm.setData('date_from', e.target.value)} className={INP} />
                            </div>
                            <div>
                                <label className={LBL}>Sampai Tanggal</label>
                                <input type="date" value={filterForm.data.date_to} onChange={(e) => filterForm.setData('date_to', e.target.value)} className={INP} />
                            </div>
                            <div className="col-span-2">
                                <label className={LBL}>Cari Deskripsi</label>
                                <input type="text" value={filterForm.data.search} onChange={(e) => filterForm.setData('search', e.target.value)}
                                    placeholder="Cari aktivitas..." className={INP} />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button type="submit"
                                className="inline-flex items-center gap-1.5 px-4 py-2 bg-claude-600 hover:bg-claude-500 text-white text-xs font-bold rounded-lg transition">
                                <FilterIcon className="w-3.5 h-3.5" /> Filter
                            </button>
                            <Link href={route('admin.logs.index')}
                                className="inline-flex items-center gap-1.5 px-4 py-2 bg-stone-100 hover:bg-stone-200 dark:bg-coal-700 dark:hover:bg-coal-600 text-stone-700 dark:text-stone-300 text-xs font-semibold rounded-lg transition">
                                <RefreshIcon className="w-3.5 h-3.5" /> Reset
                            </Link>
                        </div>
                    </form>
                </div>

                {/* ===== TABS ===== */}
                <div className="flex flex-wrap gap-2 mb-4">
                    {tabs.map((t) => {
                        const isActive = activeTab === t.key;
                        return (
                            <Link key={t.key} href={route('admin.logs.index', buildQuery({ tab: t.key }))}
                                className={`px-3 py-1.5 text-xs rounded-lg transition ${isActive
                                    ? 'font-bold bg-claude-600 text-white'
                                    : 'font-semibold bg-stone-100 hover:bg-stone-200 text-stone-600 dark:bg-coal-800 dark:hover:bg-coal-700 dark:text-stone-400'}`}>
                                {t.label}
                                {isActive && (
                                    <span className="ml-1 bg-white/25 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                                        {nf(logs?.total)}
                                    </span>
                                )}
                            </Link>
                        );
                    })}
                </div>

                {/* ===== LOGS LIST ===== */}
                <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                    <div className="px-4 py-2.5 border-b border-stone-200 dark:border-coal-700 flex items-center justify-between">
                        <p className="text-[11px] text-stone-500">
                            Menampilkan <span className="text-stone-700 dark:text-stone-300 font-semibold">{nf(logs?.from ?? 0)}</span>–<span className="text-stone-700 dark:text-stone-300 font-semibold">{nf(logs?.to ?? 0)}</span>
                            {' '}dari <span className="text-stone-700 dark:text-stone-300 font-semibold">{nf(logs?.total)}</span> log
                        </p>
                        {hasActiveFilters && (
                            <span className="text-[10px] bg-claude-500/10 border border-claude-500/25 text-claude-600 dark:text-claude-400 px-2 py-0.5 rounded-full font-semibold">Filter Aktif</span>
                        )}
                    </div>

                    {/* MOBILE LIST */}
                    <div className="lg:hidden divide-y divide-stone-100 dark:divide-coal-700">
                        {list.length === 0 ? (
                            <div className="py-16 text-center">
                                <div className="text-4xl mb-3">📋</div>
                                <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Tidak ada log activity</p>
                                <p className="text-xs text-stone-400 dark:text-stone-600 mt-1">Coba ubah filter atau lakukan aktivitas terlebih dahulu</p>
                            </div>
                        ) : list.map((log) => (
                            <div key={log.id}>
                                <Link href={route('admin.logs.show', log.id)}
                                    className="block px-4 py-3 hover:bg-stone-50 dark:hover:bg-coal-700/30 active:bg-stone-100 dark:active:bg-coal-700/50 transition">
                                    <div className="flex items-center gap-2.5 mb-1.5">
                                        <span className={`w-2 h-2 rounded-full flex-shrink-0 ${ACTIVITY_DOT[log.activity_type] || 'bg-stone-400'}`} />
                                        <p className="text-sm font-bold text-stone-900 dark:text-stone-100 flex-1 truncate">{log.user?.name ?? 'N/A'}</p>
                                        <span className="text-[10px] text-stone-500 flex-shrink-0">{log.created_at_short}</span>
                                    </div>
                                    <p className="text-[12px] text-stone-600 dark:text-stone-300 mb-2 line-clamp-2 pl-[18px]">{log.description}</p>
                                    <div className="flex items-center gap-1.5 flex-wrap pl-[18px]">
                                        <span className={`${CHIP} ${ROLE_COLORS[log.role] || DEFAULT_CHIP}`}>{capitalize(log.role)}</span>
                                        <span className={`${CHIP} ${ACTIVITY_COLORS[log.activity_type] || DEFAULT_CHIP}`}>{log.formatted_activity_type}</span>
                                        <span className="text-[10px] text-stone-400">·</span>
                                        <span className="text-[10px] text-stone-500 font-medium">{log.formatted_module}</span>
                                    </div>
                                </Link>
                                {canManage && (
                                    <div className="px-4 pb-2.5 pl-[34px] -mt-1">
                                        <button onClick={() => deleteLog(log)}
                                            className="text-[10px] font-bold text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300">
                                            Hapus
                                        </button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    {/* DESKTOP TABLE */}
                    <div className="hidden lg:block overflow-x-auto">
                        <table className="min-w-full">
                            <thead>
                                <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                    {['No', 'Waktu', 'User', 'Role', 'Aktivitas', 'Module', 'Deskripsi'].map((h) => (
                                        <th key={h} className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">{h}</th>
                                    ))}
                                    <th className="px-4 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                                {list.length === 0 ? (
                                    <tr><td colSpan={8} className="py-16 text-center">
                                        <div className="text-4xl mb-3">📋</div>
                                        <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Tidak ada log activity</p>
                                        <p className="text-xs text-stone-400 dark:text-stone-600 mt-1">Coba ubah filter atau lakukan aktivitas terlebih dahulu</p>
                                    </td></tr>
                                ) : list.map((log, idx) => (
                                    <tr key={log.id} className="hover:bg-stone-50 dark:hover:bg-coal-700/25 transition-colors">
                                        <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-600 text-center">{(logs?.from ?? 1) + idx}</td>
                                        <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-400 whitespace-nowrap">{log.created_at_full}</td>
                                        <td className="px-4 py-3 text-sm font-medium text-stone-700 dark:text-stone-300 whitespace-nowrap">{log.user?.name ?? 'N/A'}</td>
                                        <td className="px-4 py-3"><span className={`${CHIP} ${ROLE_COLORS[log.role] || DEFAULT_CHIP}`}>{capitalize(log.role)}</span></td>
                                        <td className="px-4 py-3"><span className={`${CHIP} ${ACTIVITY_COLORS[log.activity_type] || DEFAULT_CHIP}`}>{log.formatted_activity_type}</span></td>
                                        <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-400 whitespace-nowrap">{log.formatted_module}</td>
                                        <td className="px-4 py-3 text-xs text-stone-500 max-w-xs truncate">{log.description}</td>
                                        <td className="px-4 py-3 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-1.5">
                                                <Link href={route('admin.logs.show', log.id)}
                                                    className="px-2.5 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition">Detail</Link>
                                                {canManage && (
                                                    <button onClick={() => deleteLog(log)}
                                                        className="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:border-red-500/20 dark:text-red-400 text-xs font-semibold rounded-lg transition">Hapus</button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ===== PAGINATION ===== */}
                <div className="mt-5">
                    <Pagination links={logs?.links ?? []} />
                </div>
            </div>

            {/* ===== MODAL: CLEANUP ===== */}
            <Modal open={cleanupOpen} onClose={() => setCleanupOpen(false)} title="🗑️ Cleanup Logs">
                <form onSubmit={submitCleanup} className="p-5 space-y-4">
                    <div>
                        <label className={LBL}>Hapus log lebih dari:</label>
                        <select value={cleanupForm.data.days} onChange={(e) => cleanupForm.setData('days', e.target.value)} className={INP}>
                            <option value="1">1 hari</option>
                            <option value="7">7 hari</option>
                            <option value="30">30 hari</option>
                            <option value="60">60 hari</option>
                            <option value="90">90 hari (3 bulan)</option>
                            <option value="180">180 hari (6 bulan)</option>
                            <option value="365">365 hari (1 tahun)</option>
                        </select>
                    </div>
                    <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 rounded-xl px-4 py-3">
                        <p className="text-xs text-amber-700 dark:text-amber-300/80"><strong>Peringatan:</strong> Aksi ini tidak dapat dibatalkan. Log yang dihapus tidak dapat dikembalikan.</p>
                    </div>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setCleanupOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={cleanupForm.processing}
                            className="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-xl transition disabled:opacity-50">
                            {cleanupForm.processing ? 'Menghapus...' : 'Hapus Logs'}
                        </button>
                    </div>
                </form>
            </Modal>

            <style>{`
                .line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
                input[type="date"]{color-scheme:light}
                html.dark input[type="date"]{color-scheme:dark}
                html.dark select{color-scheme:dark}
                html.dark select option{background:#34302b;color:#f5f5f4}
                select{
                    -webkit-appearance:none;-moz-appearance:none;appearance:none;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2378716c' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 0.75rem center;
                    background-size:1rem;
                    padding-right:2.25rem;
                }
                @keyframes slideUp{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:translateY(0)}}
                @media(min-width:640px){@keyframes slideUp{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}}
            `}</style>
        </AuthenticatedLayout>
    );
}
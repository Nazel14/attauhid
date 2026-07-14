import { Head, Link, router } from '@inertiajs/react';
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
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const TrashIcon = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const DocIcon = (p) => <I {...p} d={<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;

/* ============================================================
 * SHARED CLASSES
 * ============================================================ */
const CARD = "bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl p-4 shadow-sm dark:shadow-none";
const CARD_LABEL = "text-[10px] font-bold text-stone-500 dark:text-stone-600 uppercase tracking-wider mb-3";
const CHIP = "inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border";
const DEFAULT_CHIP = "bg-stone-100 text-stone-600 border-stone-300 dark:bg-coal-700 dark:text-stone-500 dark:border-coal-600";

const ROLE_COLORS = {
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
    export: 'bg-teal-100 text-teal-700 border-teal-300 dark:bg-teal-950/50 dark:text-teal-400 dark:border-teal-800/30',
    import: 'bg-cyan-100 text-cyan-700 border-cyan-300 dark:bg-cyan-950/50 dark:text-cyan-400 dark:border-cyan-800/30',
};

const capitalize = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);

export default function Show({ log, canManage = false }) {
    const deleteLog = () => {
        if (confirm('Apakah Anda yakin ingin menghapus log activity ini?')) {
            router.delete(route('admin.logs.destroy', log.id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Detail Log Activity" />

            <div className="max-w-5xl mx-auto px-3 sm:px-5 lg:px-8 py-6">
                {/* Kembali — mobile saja */}
                <Link href={route('admin.logs.index')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Log Activity
                </Link>
                <Link href={route('admin.logs.index')}
                    className="mb-4 hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Log Activity
                </Link>

                {/* ===== TOP BAR ===== */}
                <div className="flex items-center gap-3 mb-5">
                    
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <DocIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100">Detail Log Activity</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Informasi lengkap aktivitas pengguna</p>
                    </div>
                    {canManage && (
                        <button onClick={deleteLog}
                            className="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-500 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                            <TrashIcon className="w-3.5 h-3.5" />
                            <span className="hidden sm:inline">Hapus Log</span>
                        </button>
                    )}
                </div>

                {/* ===== INFO CARDS GRID ===== */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    <div className={CARD}>
                        <p className={CARD_LABEL}>Informasi User</p>
                        <p className="text-base font-bold text-stone-900 dark:text-stone-100">{log.user?.name ?? 'N/A'}</p>
                        <span className={`${CHIP} ${ROLE_COLORS[log.role] || DEFAULT_CHIP} mt-1`}>{capitalize(log.role)}</span>
                    </div>

                    <div className={CARD}>
                        <p className={CARD_LABEL}>Waktu Aktivitas</p>
                        <p className="text-lg font-black text-stone-900 dark:text-stone-100">{log.created_at_date}</p>
                        <p className="text-sm font-mono text-claude-600 dark:text-claude-400 mt-0.5">{log.created_at_time}</p>
                        <p className="text-[11px] text-stone-500 dark:text-stone-600 mt-2">{log.created_at_human}</p>
                    </div>

                    <div className={CARD}>
                        <p className={CARD_LABEL}>Module & Aktivitas</p>
                        <p className="text-base font-bold text-stone-800 dark:text-stone-200 mb-2">{log.formatted_module}</p>
                        <span className={`${CHIP} ${ACTIVITY_COLORS[log.activity_type] || DEFAULT_CHIP}`}>{log.formatted_activity_type}</span>
                    </div>

                    <div className={CARD}>
                        <p className={CARD_LABEL}>Informasi Jaringan</p>
                        <p className="text-[11px] text-stone-500 mb-1.5">IP Address</p>
                        <code className="text-sm font-mono font-bold text-stone-700 dark:text-stone-300 bg-stone-100 dark:bg-coal-900 border border-stone-200 dark:border-coal-700 px-3 py-1.5 rounded-lg inline-block">{log.ip_address ?? 'N/A'}</code>
                    </div>
                </div>

                {/* ===== DESCRIPTION ===== */}
                <div className={`${CARD} mb-4`}>
                    <p className={CARD_LABEL}>Deskripsi Aktivitas</p>
                    <div className="bg-stone-50 dark:bg-coal-900/50 border border-stone-200 dark:border-coal-700/50 rounded-xl px-4 py-3">
                        <p className="text-sm text-stone-700 dark:text-stone-300 leading-relaxed">{log.description}</p>
                    </div>
                </div>

                {/* ===== USER AGENT ===== */}
                {log.user_agent && (
                    <div className={`${CARD} mb-4`}>
                        <p className={CARD_LABEL}>Device & Browser</p>
                        <div className="bg-stone-50 dark:bg-coal-900/50 border border-stone-200 dark:border-coal-700/50 rounded-xl px-4 py-3">
                            <p className="text-xs font-mono text-stone-500 dark:text-stone-400 break-all">{log.user_agent}</p>
                        </div>
                    </div>
                )}

                {/* ===== DATA CHANGES ===== */}
                {(log.old_values || log.new_values) && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
                        {log.old_values && (
                            <div className="bg-white dark:bg-coal-800 border border-red-200 dark:border-red-800/30 rounded-2xl p-4 shadow-sm dark:shadow-none">
                                <p className="text-[10px] font-bold text-red-600 dark:text-red-500 uppercase tracking-wider mb-3">Data Sebelum Perubahan</p>
                                <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/20 rounded-xl p-3 overflow-x-auto">
                                    <pre className="text-xs text-red-700/80 dark:text-red-300/80 font-mono">{JSON.stringify(log.old_values, null, 2)}</pre>
                                </div>
                            </div>
                        )}
                        {log.new_values && (
                            <div className="bg-white dark:bg-coal-800 border border-emerald-200 dark:border-emerald-800/30 rounded-2xl p-4 shadow-sm dark:shadow-none">
                                <p className="text-[10px] font-bold text-emerald-600 dark:text-emerald-500 uppercase tracking-wider mb-3">Data Setelah Perubahan</p>
                                <div className="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/20 rounded-xl p-3 overflow-x-auto">
                                    <pre className="text-xs text-emerald-700/80 dark:text-emerald-300/80 font-mono">{JSON.stringify(log.new_values, null, 2)}</pre>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* ===== MODEL INFO ===== */}
                {log.model_type && log.model_id && (
                    <div className={CARD}>
                        <p className={CARD_LABEL}>Informasi Database</p>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-stone-50 dark:bg-coal-900/50 border border-stone-200 dark:border-coal-700/50 rounded-xl px-4 py-3">
                                <p className="text-[10px] text-stone-500 dark:text-stone-600 mb-1">Model Type</p>
                                <p className="text-sm font-mono font-bold text-stone-700 dark:text-stone-300">{log.model_type_short}</p>
                            </div>
                            <div className="bg-stone-50 dark:bg-coal-900/50 border border-stone-200 dark:border-coal-700/50 rounded-xl px-4 py-3">
                                <p className="text-[10px] text-stone-500 dark:text-stone-600 mb-1">Model ID</p>
                                <p className="text-sm font-mono font-bold text-stone-700 dark:text-stone-300">{log.model_id}</p>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
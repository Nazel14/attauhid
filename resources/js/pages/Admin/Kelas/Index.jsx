import { useState, useEffect } from 'react';
import { Head, useForm, router, usePage, Link } from '@inertiajs/react';
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
const DownloadIcon = (p) => <I {...p} d={<path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const ImportIcon = (p) => <I {...p} d={<path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />} />;
const ExportIcon = (p) => <I {...p} d={<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />} />;
const RefreshIcon = (p) => <I {...p} d={<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />} />;
const PlusIcon = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const TrashIcon = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const EditIcon = (p) => <I {...p} d={<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />} />;
const SchoolIcon = (p) => <I {...p} d={<path d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-9-6.5V15a9 9 0 0018 0v-2.5" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;

/* ============================================================
 * SHARED CLASSES
 * ============================================================ */
const INP = "w-full bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-600";
const INP_DISABLED = "w-full bg-stone-100 dark:bg-coal-800/60 border border-stone-300 dark:border-coal-600 rounded-xl text-stone-500 dark:text-stone-400 text-sm px-3 py-2.5 outline-none cursor-not-allowed";
const LBL = "block text-[11px] font-semibold text-stone-600 dark:text-stone-400 mb-1.5";
const BTN_SECONDARY = "flex-1 py-2.5 rounded-xl text-sm font-semibold bg-stone-100 dark:bg-coal-700 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-coal-600 hover:bg-stone-200 dark:hover:bg-coal-600 transition";
const FILE_INP = "w-full text-xs text-stone-600 dark:text-stone-400 bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-claude-500/10 file:text-claude-600 dark:file:text-claude-400 hover:file:bg-claude-500/20";

/* Warna badge untuk tiap jenis kelas (gender_restriction) */
const GENDER_BADGE = {
    ikhwan: 'bg-blue-50 text-blue-700 border-blue-200/70 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
    akhwat: 'bg-pink-50 text-pink-700 border-pink-200/70 dark:bg-pink-500/10 dark:text-pink-400 dark:border-pink-500/20',
    campuran: 'bg-violet-50 text-violet-700 border-violet-200/70 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
};
const GENDER_BADGE_DEFAULT = 'bg-stone-100 text-stone-600 border-stone-200 dark:bg-coal-700 dark:text-stone-400 dark:border-coal-600';

/* ============================================================
 * SUB-COMPONENTS
 * ============================================================ */
function Toast({ notif, onClose, duration = 10000 }) {
    if (!notif) return null;
    const success = notif.type === 'success' || notif.type === 'updated' || notif.type === 'deleted';
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

function GenderBadge({ value, label }) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${GENDER_BADGE[value] || GENDER_BADGE_DEFAULT}`}>
            {label}
        </span>
    );
}

/* Huruf pertama tiap kata/angka jadi kapital, sisanya kecil (angka tidak diubah) */
const smartCapitalize = (value) =>
    value.replace(/(\d+|[a-zA-Z]+)/g, (match) =>
        /\d/.test(match) ? match : match.charAt(0).toUpperCase() + match.slice(1).toLowerCase()
    );

/* ============================================================
 * MAIN PAGE
 * ============================================================ */
export default function Index({ kelas = [], gurus = [], genderRestrictions = {}, tahunAjaranAktif = null }) {
    const { flash = {} } = usePage().props;

    /* ---- Toast dari flash ---- */
    const NOTIF_DURATION = 10000;
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success || flash?.updated || flash?.deleted || flash?.error) {
            const type = flash.success ? 'success' : flash.updated ? 'updated' : flash.deleted ? 'deleted' : 'error';
            setNotif({ message: flash.success || flash.updated || flash.deleted || flash.error, type, id: Date.now() });
            const t = setTimeout(() => setNotif(null), NOTIF_DURATION);
            return () => clearTimeout(t);
        }
    }, [flash]);

    /* ---- Seleksi ---- */
    const [selected, setSelected] = useState([]);
    const allIds = kelas.map((k) => k.id);
    const allSelected = allIds.length > 0 && selected.length === allIds.length;
    const toggleAll = () => setSelected(allSelected ? [] : allIds);
    const toggleOne = (id) => setSelected((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));

    /* ---- Modal state ---- */
    const [formOpen, setFormOpen] = useState(false);
    const [isEdit, setIsEdit] = useState(false);
    const [editId, setEditId] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [exportOpen, setExportOpen] = useState(false);
    const [templateOpen, setTemplateOpen] = useState(false);
    const [bulkOpen, setBulkOpen] = useState(false);

    /* ---- Form Kelas ---- */
    const kelasForm = useForm({
        name: '',
        tahun_ajaran: tahunAjaranAktif?.nama || '',
        guru_id: '',
        gender_restriction: '',
        // Dipakai bersama modul keuangan (daya tampung & status kelas).
        tingkat: '',
        kapasitas: 20,
        is_active: true,
    });

    const openCreate = () => {
        setIsEdit(false); setEditId(null);
        kelasForm.clearErrors();
        kelasForm.setData({
            name: '',
            tahun_ajaran: tahunAjaranAktif?.nama || '',
            guru_id: '',
            gender_restriction: '',
            tingkat: '',
            kapasitas: 20,
            is_active: true,
        });
        setFormOpen(true);
    };

    const openEdit = (k) => {
        setIsEdit(true); setEditId(k.id);
        kelasForm.clearErrors();
        kelasForm.setData({
            name: k.name || '',
            tahun_ajaran: k.tahun_ajaran || '',
            guru_id: k.guru_id ?? '',
            gender_restriction: k.gender_restriction || '',
            tingkat: k.tingkat || '',
            kapasitas: k.kapasitas ?? 20,
            is_active: k.is_active ?? true,
        });
        setFormOpen(true);
    };

    const onNameChange = (e) => kelasForm.setData('name', smartCapitalize(e.target.value));

    const submitKelas = (e) => {
        e.preventDefault();
        if (isEdit) {
            kelasForm.put(route('admin.kelas.update', editId), {
                preserveScroll: true,
                onSuccess: () => setFormOpen(false),
            });
        } else {
            kelasForm.post(route('admin.kelas.store'), {
                preserveScroll: true,
                onSuccess: () => setFormOpen(false),
            });
        }
    };

    /* ---- Delete ---- */
    const deleteKelas = (k) => {
        if (confirm(`Hapus Kelas ${k.name}? Kelas yang masih memiliki siswa tidak dapat dihapus.`)) {
            router.delete(route('admin.kelas.destroy', k.id), { preserveScroll: true });
        }
    };
    const bulkDelete = () => {
        if (!selected.length) {
            alert('Pilih minimal 1 kelas untuk dihapus');
            return;
        }
        if (confirm(`Apakah Anda yakin ingin menghapus ${selected.length} kelas yang dipilih?`)) {
            router.delete(route('admin.kelas.bulk-destroy'), {
                data: { kelas_ids: selected },
                preserveScroll: true,
                onSuccess: () => setSelected([]),
            });
        }
    };

    /* ---- Import & Bulk Update ---- */
    const importForm = useForm({ file: null });
    const submitImport = (e) => {
        e.preventDefault();
        importForm.post(route('admin.kelas.import'), {
            forceFormData: true,
            onSuccess: () => { setImportOpen(false); importForm.reset(); },
        });
    };
    const bulkForm = useForm({ file: null });
    const submitBulk = (e) => {
        e.preventDefault();
        bulkForm.post(route('admin.kelas.bulk-update'), {
            forceFormData: true,
            onSuccess: () => { setBulkOpen(false); bulkForm.reset(); },
        });
    };

    const err = (msg) => msg ? <p className="text-[11px] text-red-500 dark:text-red-400 mt-1">{msg}</p> : null;

    return (
        <AuthenticatedLayout>
            <Head title="Management Kelas" />
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
                <div className="flex items-center gap-3 mb-6">
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <SchoolIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100">Management Kelas</h1>
                        <div className="flex items-center gap-2 mt-1">
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-claude-500/10 text-claude-600 dark:text-claude-400 border border-claude-500/20">
                                {kelas.length} kelas
                            </span>
                        </div>
                    </div>
                </div>

                {/* ===== ACTION BUTTONS ===== */}
                <div className="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 mb-4">
                    <button onClick={() => setTemplateOpen(true)} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition active:scale-95 bg-emerald-50 text-emerald-700 border border-emerald-200/70 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 dark:hover:bg-emerald-500/20">
                        <DownloadIcon className="w-3.5 h-3.5" /> Template
                    </button>
                    <button onClick={() => setImportOpen(true)} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition active:scale-95 bg-blue-50 text-blue-700 border border-blue-200/70 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20 dark:hover:bg-blue-500/20">
                        <ImportIcon className="w-3.5 h-3.5" /> Import
                    </button>
                    <button onClick={() => setExportOpen(true)} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition active:scale-95 bg-violet-50 text-violet-700 border border-violet-200/70 hover:bg-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20 dark:hover:bg-violet-500/20">
                        <ExportIcon className="w-3.5 h-3.5" /> Export
                    </button>
                    <button onClick={() => setBulkOpen(true)} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition active:scale-95 bg-amber-50 text-amber-700 border border-amber-200/70 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20 dark:hover:bg-amber-500/20">
                        <RefreshIcon className="w-3.5 h-3.5" /> Update
                    </button>
                    <button onClick={openCreate} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-white bg-claude-600 hover:bg-claude-500 shadow-lg shadow-claude-500/25 transition active:scale-95 col-span-2 sm:col-span-1">
                        <PlusIcon className="w-3.5 h-3.5" /> Tambah Kelas
                    </button>
                </div>

                {/* ===== BULK ACTION BAR ===== */}
                {selected.length > 0 && (
                    <div className="bg-claude-500/8 border border-claude-500/25 rounded-2xl px-4 py-3 mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-claude-700 dark:text-claude-400">
                                {selected.length} dari {allIds.length} kelas dipilih
                            </p>
                            <button onClick={() => setSelected([])} className="text-[11px] text-claude-500 hover:text-claude-600 dark:hover:text-claude-300">
                                Bersihkan pilihan
                            </button>
                        </div>
                        <button onClick={bulkDelete} className="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex-shrink-0">
                            <TrashIcon className="w-3.5 h-3.5" /> Hapus Terpilih
                        </button>
                    </div>
                )}

                {/* ===== MOBILE CARDS ===== */}
                <div className="lg:hidden space-y-2">
                    {kelas.length > 0 && (
                        <div className="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl">
                            <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 focus:ring-claude-500 cursor-pointer" />
                            <span className="text-xs font-semibold text-stone-600 dark:text-stone-400 flex-1">Pilih Semua</span>
                            <span className="text-[11px] text-stone-400 dark:text-stone-600">{kelas.length} data</span>
                        </div>
                    )}

                    {kelas.length === 0 ? (
                        <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl py-16 text-center">
                            <div className="text-4xl mb-3">🏫</div>
                            <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada data kelas</p>
                            <p className="text-xs text-stone-400 dark:text-stone-600 mt-1.5">Tap tombol "Tambah Kelas" di atas</p>
                        </div>
                    ) : kelas.map((k) => {
                        const isSel = selected.includes(k.id);
                        return (
                            <div key={k.id} className={`rounded-2xl border overflow-hidden transition-all ${isSel ? 'border-claude-500/50 ring-1 ring-claude-500/15 bg-claude-500/5' : 'border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800'}`}>
                                <div className="flex items-center gap-3 px-4 py-3">
                                    <input type="checkbox" checked={isSel} onChange={() => toggleOne(k.id)}
                                        className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 flex-shrink-0 cursor-pointer" />
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-bold text-stone-900 dark:text-stone-100 truncate">{k.name}</p>
                                        <p className="text-[11px] text-stone-400 dark:text-stone-500">{k.tahun_ajaran}</p>
                                    </div>
                                    <GenderBadge value={k.gender_restriction} label={genderRestrictions[k.gender_restriction] || '-'} />
                                </div>
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-700">
                                    <div className="px-3 py-2.5 text-center border-r border-stone-100 dark:border-coal-700">
                                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mb-1">Wali Kelas</p>
                                        <p className="text-xs font-bold text-claude-600 dark:text-claude-400 truncate">{k.guru?.name || 'Tidak Ada'}</p>
                                    </div>
                                    <div className="px-3 py-2.5 text-center">
                                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mb-1">Terisi</p>
                                        <p className="text-xs font-bold">
                                            <span className={k.santris_count >= k.kapasitas ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'}>
                                                {k.santris_count ?? 0}
                                            </span>
                                            <span className="text-stone-400"> / {k.kapasitas}</span>
                                        </p>
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-700">
                                    <button onClick={() => openEdit(k)} className="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-claude-600 dark:text-claude-400 hover:bg-claude-500/10 transition border-r border-stone-100 dark:border-coal-700">
                                        <EditIcon className="w-3.5 h-3.5" /> Edit
                                    </button>
                                    <button onClick={() => deleteKelas(k)} className="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
                                        <TrashIcon className="w-3.5 h-3.5" /> Hapus
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* ===== DESKTOP TABLE ===== */}
                <div className="hidden lg:block bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                    <table className="min-w-full">
                        <thead>
                            <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                <th className="px-4 py-3 w-10">
                                    <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                        ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                        className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 cursor-pointer" />
                                </th>
                                {['No', 'Nama Kelas', 'Tahun Ajaran', 'Wali Kelas', 'Jenis Kelas', 'Terisi'].map((h) => (
                                    <th key={h} className="px-4 py-3 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">{h}</th>
                                ))}
                                <th className="px-4 py-3 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                            {kelas.length === 0 ? (
                                <tr><td colSpan={8} className="py-16 text-center">
                                    <div className="text-4xl mb-3">🏫</div>
                                    <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada data kelas</p>
                                    <p className="text-xs text-stone-400 dark:text-stone-600 mt-1">Klik "Tambah Kelas" di atas</p>
                                </td></tr>
                            ) : kelas.map((k, idx) => {
                                const isSel = selected.includes(k.id);
                                return (
                                    <tr key={k.id} className={`transition-colors ${isSel ? 'bg-claude-500/5' : 'hover:bg-stone-50 dark:hover:bg-coal-700/30'}`}>
                                        <td className="px-4 py-3">
                                            <input type="checkbox" checked={isSel} onChange={() => toggleOne(k.id)}
                                                className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 cursor-pointer" />
                                        </td>
                                        <td className="px-4 py-3 text-sm text-stone-400 dark:text-stone-500">{idx + 1}</td>
                                        <td className="px-4 py-3">
                                            <span className="text-sm font-medium text-stone-800 dark:text-stone-200">{k.name}</span>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">{k.tahun_ajaran}</td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-claude-500/10 text-claude-600 dark:text-claude-400 border border-claude-500/20">
                                                {k.guru?.name || 'Tidak Ada Guru'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <GenderBadge value={k.gender_restriction} label={genderRestrictions[k.gender_restriction] || 'Tidak Diketahui'} />
                                            {k.tingkat && <span className="ml-1.5 text-[10px] text-stone-400">Tingkat {k.tingkat}</span>}
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            <span className={`font-bold ${k.santris_count >= k.kapasitas ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'}`}>
                                                {k.santris_count ?? 0}
                                            </span>
                                            <span className="text-stone-400"> / {k.kapasitas}</span>
                                            {k.is_active === false && (
                                                <span className="ml-1.5 inline-flex rounded-full bg-stone-100 dark:bg-coal-700 px-2 py-0.5 text-[10px] font-bold text-stone-500 dark:text-stone-400">Non-aktif</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex gap-2 justify-center">
                                                <button onClick={() => openEdit(k)} className="px-3 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition">Edit</button>
                                                <button onClick={() => deleteKelas(k)} className="px-3 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:border-red-500/20 dark:text-red-400 text-xs font-semibold rounded-lg transition">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* ===== MODAL: TAMBAH / EDIT ===== */}
            <Modal open={formOpen} onClose={() => setFormOpen(false)}
                title={isEdit ? `Edit Kelas: ${kelasForm.data.name}` : 'Tambah Kelas Baru'} maxWidth="sm:max-w-lg">
                <form onSubmit={submitKelas} className="p-5 space-y-4 overflow-y-auto" style={{ maxHeight: '72vh' }}>
                    <div>
                        <label className={LBL}>Nama Kelas <span className="text-red-500">*</span></label>
                        <input name="name" value={kelasForm.data.name} onChange={onNameChange} required
                            placeholder="Contoh: Kelompok A" className={INP} />
                        {err(kelasForm.errors.name)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Huruf awal tiap kata otomatis kapital</p>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className={LBL}>Tahun Ajaran <span className="text-red-500">*</span></label>
                            {isEdit ? (
                                <input type="text" value={kelasForm.data.tahun_ajaran} readOnly className={INP_DISABLED} />
                            ) : tahunAjaranAktif ? (
                                <input type="text" value={tahunAjaranAktif.nama} readOnly className={INP_DISABLED} />
                            ) : (
                                <input type="text" value="Belum ada tahun ajaran aktif" readOnly
                                    className="w-full bg-stone-100 dark:bg-coal-800/60 border border-stone-300 dark:border-coal-600 rounded-xl text-amber-600 dark:text-amber-400 text-sm px-3 py-2.5 outline-none cursor-not-allowed" />
                            )}
                            {err(kelasForm.errors.tahun_ajaran)}
                            <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">
                                {isEdit ? 'Tahun ajaran kelas ini' : 'Otomatis mengikuti tahun ajaran aktif'}
                            </p>
                        </div>
                        <div>
                            <label className={LBL}>Wali Kelas <span className="text-red-500">*</span></label>
                            <select name="guru_id" value={kelasForm.data.guru_id}
                                onChange={(e) => kelasForm.setData('guru_id', e.target.value)} required className={INP}>
                                <option value="" disabled>Pilih Guru</option>
                                {gurus.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                            </select>
                            {err(kelasForm.errors.guru_id)}
                        </div>
                    </div>

                    <div>
                        <label className={LBL}>Jenis Kelas <span className="text-red-500">*</span></label>
                        <select name="gender_restriction" value={kelasForm.data.gender_restriction}
                            onChange={(e) => kelasForm.setData('gender_restriction', e.target.value)} required className={INP}>
                            <option value="" disabled>Pilih Jenis Kelas</option>
                            {Object.entries(genderRestrictions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </select>
                        {err(kelasForm.errors.gender_restriction)}
                    </div>

                    <div className="grid grid-cols-2 gap-3 border-t border-stone-100 dark:border-coal-700 pt-4">
                        <div>
                            <label className={LBL}>Tingkat</label>
                            <input name="tingkat" value={kelasForm.data.tingkat}
                                onChange={(e) => kelasForm.setData('tingkat', e.target.value)}
                                placeholder="Contoh: A" className={INP} />
                            {err(kelasForm.errors.tingkat)}
                            <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Opsional</p>
                        </div>
                        <div>
                            <label className={LBL}>Kapasitas <span className="text-red-500">*</span></label>
                            <input type="number" min="1" max="100" name="kapasitas" value={kelasForm.data.kapasitas}
                                onChange={(e) => kelasForm.setData('kapasitas', e.target.value)} required className={INP} />
                            {err(kelasForm.errors.kapasitas)}
                            <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Daya tampung siswa</p>
                        </div>
                    </div>

                    <label className="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" checked={kelasForm.data.is_active}
                            onChange={(e) => kelasForm.setData('is_active', e.target.checked)}
                            className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 focus:ring-claude-500" />
                        <span className="text-sm text-stone-600 dark:text-stone-300">Kelas Aktif</span>
                    </label>

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={() => setFormOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={kelasForm.processing}
                            className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-claude-600 hover:bg-claude-500 shadow-lg shadow-claude-500/25 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            {kelasForm.processing ? 'Menyimpan...' : isEdit ? 'Perbarui Kelas' : 'Simpan Kelas'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: TEMPLATE ===== */}
            <Modal open={templateOpen} onClose={() => setTemplateOpen(false)} title="Download Template Import Kelas" maxWidth="sm:max-w-sm">
                <div className="p-5 space-y-4">
                    <div className="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs text-emerald-700/90 dark:text-emerald-300/80">
                            Template Excel berisi format kolom yang benar untuk import data kelas. Download template ini sebelum melakukan import.
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <button onClick={() => setTemplateOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <a href={route('admin.kelas.download-template')} onClick={() => setTimeout(() => setTemplateOpen(false), 500)}
                            className="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition text-center">Download Template</a>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL: IMPORT ===== */}
            <Modal open={importOpen} onClose={() => setImportOpen(false)} title="Import Data Kelas dari Excel">
                <form onSubmit={submitImport} className="p-5 space-y-4">
                    <div className="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-2xl px-4 py-3">
                        <p className="text-[11px] text-amber-700 dark:text-amber-400 font-semibold">⚠️ Penting: Download template terlebih dahulu dan isi sesuai format!</p>
                    </div>
                    <div>
                        <label className={LBL}>Pilih File Excel (.xlsx atau .xls) <span className="text-red-500">*</span></label>
                        <input type="file" accept=".xlsx,.xls" required onChange={(e) => importForm.setData('file', e.target.files[0])} className={FILE_INP} />
                        {err(importForm.errors.file)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Maksimal ukuran file: 2MB</p>
                    </div>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setImportOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={importForm.processing} className="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition disabled:opacity-50">
                            {importForm.processing ? 'Mengimport...' : 'Import Sekarang'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: EXPORT ===== */}
            <Modal open={exportOpen} onClose={() => setExportOpen(false)} title="Export Data Kelas untuk Edit" maxWidth="sm:max-w-sm">
                <div className="p-5 space-y-4">
                    <div className="bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs text-violet-700/90 dark:text-violet-300/80">
                            File Excel akan berisi data kelas yang sudah ada. Edit data lalu import kembali untuk update massal.
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <button onClick={() => setExportOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <a href={route('admin.kelas.export-for-edit')} onClick={() => setTimeout(() => setExportOpen(false), 500)}
                            className="flex-1 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold rounded-xl transition text-center">Download Excel</a>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL: BULK UPDATE ===== */}
            <Modal open={bulkOpen} onClose={() => setBulkOpen(false)} title="Bulk Update Data Kelas">
                <form onSubmit={submitBulk} className="p-5 space-y-4">
                    <div className="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1.5">📋 Cara Pakai:</p>
                        <ol className="text-[11px] text-amber-700/80 dark:text-amber-300/70 space-y-0.5 list-decimal list-inside">
                            <li>Klik "Export" terlebih dahulu</li>
                            <li>Edit data di Excel yang terdownload</li>
                            <li>Upload kembali file Excel tersebut di sini</li>
                        </ol>
                    </div>
                    <div>
                        <label className={LBL}>Pilih File Excel yang Sudah Diedit <span className="text-red-500">*</span></label>
                        <input type="file" accept=".xlsx,.xls" required onChange={(e) => bulkForm.setData('file', e.target.files[0])} className={FILE_INP} />
                        {err(bulkForm.errors.file)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">File harus hasil export dari tombol "Export"</p>
                    </div>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setBulkOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={bulkForm.processing} className="flex-1 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold rounded-xl transition disabled:opacity-50">
                            {bulkForm.processing ? 'Mengupdate...' : 'Update Sekarang'}
                        </button>
                    </div>
                </form>
            </Modal>

            <style>{`
                select{
                    -webkit-appearance:none;-moz-appearance:none;appearance:none;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2378716c' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 0.75rem center;
                    background-size:1rem;
                    padding-right:2.25rem;
                }
                html.dark select{color-scheme:dark}
                html.dark select option{background:#323730;color:#F6F7F2}
                @keyframes slideUp{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:translateY(0)}}
                @media(min-width:640px){@keyframes slideUp{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}}
            `}</style>
        </AuthenticatedLayout>
    );
}
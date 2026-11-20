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
const PlusIcon = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const EditIcon = (p) => <I {...p} d={<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />} />;
const TrashIcon = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const InfoIcon = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const PowerIcon = (p) => <I {...p} d={<path d="M18.36 6.64a9 9 0 11-12.73 0M12 3v9" />} />;
const ClockIcon = (p) => <I {...p} d={<><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>} />;

/* ============================================================
 * SHARED CLASSES
 * ============================================================ */
const INP = "w-full bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-600";
const LBL = "block text-[11px] font-semibold text-stone-600 dark:text-stone-400 mb-1.5";
const BTN_SECONDARY = "flex-1 py-2.5 rounded-xl text-sm font-semibold bg-stone-100 dark:bg-coal-700 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-coal-600 hover:bg-stone-200 dark:hover:bg-coal-600 transition";

const capitalizeWords = (value) =>
    value
        .split(' ')
        .map((w) => (w.length > 0 ? w.charAt(0).toUpperCase() + w.slice(1).toLowerCase() : w))
        .join(' ');

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
                <div className="flex items-center justify-between px-5 py-3.5 border-b border-cream-200 dark:border-coal-600">
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

/* ============================================================
 * MAIN PAGE
 * ============================================================ */
export default function Index({ sesis = [] }) {
    const { flash = {} } = usePage().props;

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

    const nextUrutan = sesis.length > 0 ? Math.max(...sesis.map((s) => s.urutan)) + 1 : 1;

    /* ---- Modal state ---- */
    const [createOpen, setCreateOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [editId, setEditId] = useState(null);

    /* ---- Form Create ---- */
    const createForm = useForm({
        sesi_name: '',
        urutan: nextUrutan,
        is_active: true,
    });

    const openCreate = () => {
        createForm.clearErrors();
        createForm.setData({ sesi_name: '', urutan: nextUrutan, is_active: true });
        setCreateOpen(true);
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('admin.presensi-sesis.store'), {
            preserveScroll: true,
            onSuccess: () => setCreateOpen(false),
        });
    };

    /* ---- Form Edit ---- */
    const editForm = useForm({
        sesi_name: '',
        urutan: 0,
        is_active: true,
    });

    const openEdit = (s) => {
        setEditId(s.id);
        editForm.clearErrors();
        editForm.setData({ sesi_name: s.sesi_name, urutan: s.urutan, is_active: s.is_active });
        setEditOpen(true);
    };

    const submitEdit = (e) => {
        e.preventDefault();
        editForm.put(route('admin.presensi-sesis.update', editId), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    /* ---- Toggle & Delete ---- */
    const toggleActive = (s) => {
        router.patch(route('admin.presensi-sesis.toggle', s.id), {}, { preserveScroll: true });
    };

    const deleteSesi = (s) => {
        if (confirm(`Apakah Anda yakin ingin menghapus sesi "${s.sesi_name}"?`)) {
            router.delete(route('admin.presensi-sesis.destroy', s.id), { preserveScroll: true });
        }
    };

    const err = (msg) => (msg ? <p className="text-[11px] text-red-500 dark:text-red-400 mt-1">{msg}</p> : null);

    return (
        <AuthenticatedLayout>
            <Head title="Kelola Sesi Presensi" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            <div>
                {/* Kembali — mobile saja. Tujuannya Dashboard, sama seperti
                    halaman lain di modul admin. Sebelumnya menunjuk
                    admin.santris.index tapi bertuliskan "Daftar Kelas" —
                    rute, tulisan, dan halamannya tiga-tiganya tidak cocok. */}
                <Link href={route('admin.dashboard')}
                    className="mb-4 inline-flex items-center gap-1.5 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-stone-600 shadow-sm transition hover:bg-cream-100 active:scale-95 sm:hidden dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:bg-coal-600">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-4">
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <ClockIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100">Kelola Sesi Presensi</h1>
                        <p className="text-[11px] text-stone-500 dark:text-stone-500 mt-0.5">Atur sesi yang tersedia untuk input presensi siswa</p>
                    </div>
                    <button onClick={openCreate}
                        className="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-white bg-claude-600 hover:bg-claude-500 shadow-lg shadow-claude-500/25 transition active:scale-95">
                        <PlusIcon className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Tambah Sesi</span>
                    </button>
                </div>

                {/* ===== INFO BOX ===== */}
                <div className="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-2xl px-4 py-3 mb-4 flex items-start gap-3">
                    <InfoIcon className="w-4 h-4 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <p className="text-xs text-blue-700/90 dark:text-blue-300/80">
                        Sesi yang <strong className="text-blue-800 dark:text-blue-200">aktif</strong> akan muncul sebagai pilihan saat guru input presensi. Anda bisa menonaktifkan sesi tanpa menghapusnya.
                    </p>
                </div>

                {/* ===== MOBILE CARDS ===== */}
                <div className="lg:hidden space-y-2">
                    {sesis.length === 0 ? (
                        <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl py-16 text-center shadow-sm dark:shadow-none">
                            <div className="text-4xl mb-3">🕐</div>
                            <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada sesi presensi</p>
                            <p className="text-xs text-stone-400 dark:text-stone-600 mt-1.5">Tap tombol "Tambah Sesi" di atas</p>
                        </div>
                    ) : sesis.map((s) => (
                        <div key={s.id} className={`rounded-2xl border border-cream-300 bg-white overflow-hidden transition-all dark:border-coal-600 dark:bg-coal-700 ${s.is_active ? '' : 'opacity-60'}`}>
                            <div className="flex items-center gap-3 px-4 py-3">
                                <div className={`w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-base ${s.is_active ? 'bg-claude-500/10 border border-claude-500/25 text-claude-600 dark:text-claude-400' : 'bg-cream-200 dark:bg-coal-600 border border-cream-300 dark:border-coal-500 text-stone-400 dark:text-stone-600'}`}>
                                    {s.urutan}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold text-stone-900 dark:text-stone-100 truncate">{s.sesi_name}</p>
                                    <p className="text-[11px] text-stone-400 dark:text-stone-600 mt-0.5">{s.updated_at}</p>
                                </div>
                                {s.is_active ? (
                                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-full border flex-shrink-0 bg-emerald-50 text-emerald-700 border-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">Aktif</span>
                                ) : (
                                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-full border flex-shrink-0 bg-cream-200 text-stone-500 border-cream-300 dark:bg-coal-600 dark:text-stone-500 dark:border-coal-500">Nonaktif</span>
                                )}
                            </div>
                            <div className="grid grid-cols-3 border-t border-cream-200 dark:border-coal-600">
                                <button onClick={() => toggleActive(s)}
                                    className={`py-2.5 text-xs font-semibold text-center border-r border-cream-200 dark:border-coal-600 transition ${s.is_active ? 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'}`}>
                                    {s.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                </button>
                                <button onClick={() => openEdit(s)}
                                    className="py-2.5 text-xs font-semibold text-claude-600 dark:text-claude-400 hover:bg-claude-500/10 transition border-r border-cream-200 dark:border-coal-600 flex items-center justify-center gap-1.5">
                                    <EditIcon className="w-3.5 h-3.5" /> Edit
                                </button>
                                <button onClick={() => deleteSesi(s)}
                                    className="py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition flex items-center justify-center gap-1.5">
                                    <TrashIcon className="w-3.5 h-3.5" /> Hapus
                                </button>
                            </div>
                        </div>
                    ))}
                </div>

                {/* ===== DESKTOP TABLE ===== */}
                <div className="hidden lg:block bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                    <table className="min-w-full">
                        <thead>
                            <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                {['Urutan', 'Nama Sesi', 'Status', 'Terakhir Diubah'].map((h) => (
                                    <th key={h} className="px-5 py-3 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">{h}</th>
                                ))}
                                <th className="px-5 py-3 text-right text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                            {sesis.length === 0 ? (
                                <tr><td colSpan={5} className="py-16 text-center">
                                    <div className="text-4xl mb-3">🕐</div>
                                    <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada sesi presensi</p>
                                    <p className="text-xs text-stone-400 dark:text-stone-600 mt-1">Klik "Tambah Sesi" di atas</p>
                                </td></tr>
                            ) : sesis.map((s) => (
                                <tr key={s.id} className={`transition-colors hover:bg-stone-50 dark:hover:bg-coal-700/30 ${!s.is_active ? 'opacity-60' : ''}`}>
                                    <td className="px-5 py-3.5">
                                        <div className={`w-8 h-8 rounded-xl flex items-center justify-center font-black text-sm ${s.is_active ? 'bg-claude-500/10 border border-claude-500/25 text-claude-600 dark:text-claude-400' : 'bg-cream-200 dark:bg-coal-600 border border-cream-300 dark:border-coal-500 text-stone-400 dark:text-stone-600'}`}>
                                            {s.urutan}
                                        </div>
                                    </td>
                                    <td className="px-5 py-3.5 text-sm font-semibold text-stone-800 dark:text-stone-200">{s.sesi_name}</td>
                                    <td className="px-5 py-3.5">
                                        {s.is_active ? (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">✅ Aktif</span>
                                        ) : (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-500 border border-stone-200 dark:bg-coal-700 dark:text-stone-500 dark:border-coal-600">⏸ Nonaktif</span>
                                        )}
                                    </td>
                                    <td className="px-5 py-3.5 text-sm text-stone-500 dark:text-stone-500">{s.updated_at}</td>
                                    <td className="px-5 py-3.5">
                                        <div className="flex items-center justify-end gap-2">
                                            <button onClick={() => toggleActive(s)}
                                                className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition ${s.is_active ? 'bg-amber-50 border-amber-200 text-amber-600 hover:bg-amber-100 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400 dark:hover:bg-amber-500/20' : 'bg-emerald-50 border-emerald-200 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400 dark:hover:bg-emerald-500/20'}`}>
                                                {s.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                            </button>
                                            <button onClick={() => openEdit(s)}
                                                className="px-3 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition">Edit</button>
                                            <button onClick={() => deleteSesi(s)}
                                                className="px-3 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:border-red-500/20 dark:text-red-400 text-xs font-semibold rounded-lg transition">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* ===== MODAL: TAMBAH SESI ===== */}
            <Modal open={createOpen} onClose={() => setCreateOpen(false)} title="➕ Tambah Sesi Presensi">
                <form onSubmit={submitCreate} className="p-5 space-y-4">
                    <div>
                        <label className={LBL}>Nama Sesi <span className="text-red-500">*</span></label>
                        <input
                            value={createForm.data.sesi_name}
                            onChange={(e) => createForm.setData('sesi_name', capitalizeWords(e.target.value))}
                            required placeholder="Contoh: Dzuhur" className={INP} />
                        {err(createForm.errors.sesi_name)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Huruf awal setiap kata otomatis kapital</p>
                    </div>
                    <div>
                        <label className={LBL}>Urutan <span className="text-red-500">*</span></label>
                        <input type="number" min="1" required
                            value={createForm.data.urutan}
                            onChange={(e) => createForm.setData('urutan', e.target.value)} className={INP} />
                        {err(createForm.errors.urutan)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Urutan sesi dari terkecil ke terbesar</p>
                    </div>
                    <div className="flex items-center gap-2.5 py-1">
                        <input type="checkbox" id="create_is_active"
                            checked={createForm.data.is_active}
                            onChange={(e) => createForm.setData('is_active', e.target.checked)}
                            className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 focus:ring-claude-500 cursor-pointer" />
                        <label htmlFor="create_is_active" className="text-sm text-stone-700 dark:text-stone-300 cursor-pointer">Aktifkan sesi ini</label>
                    </div>
                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={() => setCreateOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={createForm.processing}
                            className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-claude-600 hover:bg-claude-500 shadow-lg shadow-claude-500/25 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            {createForm.processing ? 'Menyimpan...' : 'Simpan Sesi'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: EDIT SESI ===== */}
            <Modal open={editOpen} onClose={() => setEditOpen(false)} title={`✏️ Edit Sesi: ${editForm.data.sesi_name || ''}`}>
                <form onSubmit={submitEdit} className="p-5 space-y-4">
                    <div>
                        <label className={LBL}>Nama Sesi <span className="text-red-500">*</span></label>
                        <input
                            value={editForm.data.sesi_name}
                            onChange={(e) => editForm.setData('sesi_name', capitalizeWords(e.target.value))}
                            required className={INP} />
                        {err(editForm.errors.sesi_name)}
                    </div>
                    <div>
                        <label className={LBL}>Urutan <span className="text-red-500">*</span></label>
                        <input type="number" min="1" required
                            value={editForm.data.urutan}
                            onChange={(e) => editForm.setData('urutan', e.target.value)} className={INP} />
                        {err(editForm.errors.urutan)}
                    </div>
                    <div className="flex items-center gap-2.5 py-1">
                        <input type="checkbox" id="edit_is_active"
                            checked={editForm.data.is_active}
                            onChange={(e) => editForm.setData('is_active', e.target.checked)}
                            className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 focus:ring-claude-500 cursor-pointer" />
                        <label htmlFor="edit_is_active" className="text-sm text-stone-700 dark:text-stone-300 cursor-pointer">Aktifkan sesi ini</label>
                    </div>
                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={() => setEditOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={editForm.processing}
                            className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-claude-600 hover:bg-claude-500 shadow-lg shadow-claude-500/25 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            {editForm.processing ? 'Memperbarui...' : 'Perbarui Sesi'}
                        </button>
                    </div>
                </form>
            </Modal>

            <style>{`@keyframes slideUp{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:translateY(0)}}@media(min-width:640px){@keyframes slideUp{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}}`}</style>
        </AuthenticatedLayout>
    );
}
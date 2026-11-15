import { useState, useEffect, useRef } from 'react';
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
const DownloadIcon = (p) => <I {...p} d={<path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const ImportIcon = (p) => <I {...p} d={<path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />} />;
const ExportIcon = (p) => <I {...p} d={<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />} />;
const RefreshIcon = (p) => <I {...p} d={<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />} />;
const PlusIcon = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const TrashIcon = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const EditIcon = (p) => <I {...p} d={<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />} />;
const UserIcon = (p) => <I {...p} d={<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />} />;
const KeyIcon = (p) => <I {...p} d={<path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />} />;
const EyeIcon = (p) => <I {...p} d={<><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></>} />;
const EyeOffIcon = (p) => <I {...p} d={<path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const UsersIcon = (p) => <I {...p} d={<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />} />;

/* ============================================================
 * SHARED CLASSES
 * ============================================================ */
const INP = "w-full bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-600";
const LBL = "block text-[11px] font-semibold text-stone-600 dark:text-stone-400 mb-1.5";
const BTN_SECONDARY = "flex-1 py-2.5 rounded-xl text-sm font-semibold bg-stone-100 dark:bg-coal-700 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-coal-600 hover:bg-stone-200 dark:hover:bg-coal-600 transition";
const FILE_INP = "w-full text-xs text-stone-600 dark:text-stone-400 bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-claude-500/10 file:text-claude-600 dark:file:text-claude-400 hover:file:bg-claude-500/20";

const storageUrl = (path) => (path ? `/storage/${path}` : null);
const smartCapitalize = (value) => value.replace(/\b\w/g, (c) => c.toUpperCase());
const isJilidMetode = (m) => m === 'iqro' || m === 'aisar';
const targetDisplay = (s) => {
    const m = String(s.target_metode ?? 'quran').toLowerCase();
    const hasQuran2 = String(s.target_metode_2 ?? '').toLowerCase() === 'quran';
    const juz = `${Number(s.target_juz ?? 0).toFixed(2)} Juz`;
    if (m === 'iqro')  return `Iqro Jilid ${s.target_jilid ?? '-'}${hasQuran2 ? ` + ${juz}` : ''}`;
    if (m === 'aisar') return `Aisar Jilid ${s.target_jilid ?? '-'}${hasQuran2 ? ` + ${juz}` : ''}`;
    return juz;
};

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

function PasswordReveal({ value }) {
    const [show, setShow] = useState(false);
    return (
        <div className="flex items-center gap-1.5">
            {!value ? (
                <span className="text-[11px] italic text-stone-400 dark:text-stone-500" title="Password akun ini belum tersimpan. Tekan Edit lalu isi password baru (atau Reset) agar bisa dilihat.">
                    Belum tersimpan
                </span>
            ) : (
                <span className={`font-mono text-xs ${show ? 'text-stone-700 dark:text-stone-300' : 'text-stone-400 dark:text-stone-600'}`}>
                    {show ? value : '••••••••'}
                </span>
            )}
            {value && (
                <button onClick={() => setShow((s) => !s)} className="text-stone-400 hover:text-claude-500 dark:text-stone-600 dark:hover:text-claude-400 transition">
                    {show ? <EyeOffIcon className="w-3.5 h-3.5" /> : <EyeIcon className="w-3.5 h-3.5" />}
                </button>
            )}
        </div>
    );
}

function PasswordInput({ value, onChange, placeholder, required }) {
    const [show, setShow] = useState(false);
    return (
        <div className="relative">
            <input type={show ? 'text' : 'password'} value={value} onChange={onChange}
                placeholder={placeholder} required={required} autoComplete="new-password" className={`${INP} pr-9`} />
            <button type="button" onClick={() => setShow((s) => !s)}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-500">
                {show ? <EyeOffIcon /> : <EyeIcon />}
            </button>
        </div>
    );
}

function Pagination({ links = [] }) {
    if (links.length <= 3) return null;
    return (
        <div className="flex flex-wrap items-center gap-1 justify-center sm:justify-end">
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
 * MAIN PAGE — Admin / Santri / Detail
 * ============================================================ */
export default function Detail({
    kelas, santris,
    hasGenderMismatch = false, mismatchedSantris = [],
    targetMetodeOptions = [], jilidMaxPerMetode = { iqro: 6, aisar: 6 },
}) {
    const { flash = {} } = usePage().props;
    const list = santris?.data ?? [];

    /* ---- Toast dari flash ---- */
    const NOTIF_DURATION = 10000;
    const [notif, setNotif] = useState(null);
    const timerRef = useRef(null);

    const showToast = (message, type = 'success') => {
        setNotif({ message, type, id: Date.now() });
        clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => setNotif(null), NOTIF_DURATION);
    };

    useEffect(() => {
        if (flash?.success) showToast(flash.success, 'success');
        else if (flash?.error) showToast(flash.error, 'error');

        const off = router.on('success', (e) => {
            const f = e.detail.page.props.flash ?? {};
            if (f.success) showToast(f.success, 'success');
            else if (f.error) showToast(f.error, 'error');
        });

        return () => {
            off();
            clearTimeout(timerRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    /* ---- Seleksi ---- */
    const [selected, setSelected] = useState([]);
    const allIds = list.map((s) => s.id);
    const allSelected = allIds.length > 0 && selected.length === allIds.length;
    const toggleAll = () => setSelected(allSelected ? [] : allIds);
    const toggleOne = (id) => setSelected((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));

    /* ---- Modal state ---- */
    const [formOpen, setFormOpen] = useState(false);
    const [isEdit, setIsEdit] = useState(false);
    const [editId, setEditId] = useState(null);
    const [photoPreview, setPhotoPreview] = useState(null);
    const [currentPhoto, setCurrentPhoto] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [exportOpen, setExportOpen] = useState(false);
    const [templateOpen, setTemplateOpen] = useState(false);
    const [bulkOpen, setBulkOpen] = useState(false);

    /* ---- Form Santri ---- */
    const santriForm = useForm({
        _method: 'POST',
        nis: '', name: '', gender: 'L',
        target_metode: 'quran', target_juz: '1.00', target_jilid: '1',
        target_metode_2: '',
        kelas_id: kelas.id,
        password: '', password_confirmation: '',
        profile_photo: null, delete_photo: '0',
        // Data wali — dipakai modul keuangan untuk penagihan.
        parent_name: '', parent_contact: '', student_type: 'Reguler',
    });

    const openCreate = () => {
        setIsEdit(false); setEditId(null);
        santriForm.clearErrors();
        santriForm.setData({
            _method: 'POST',
            nis: '', name: '', gender: 'L',
            target_metode: 'quran', target_juz: '1.00', target_jilid: '1',
            target_metode_2: '',
            kelas_id: kelas.id,
            password: '', password_confirmation: '',
            profile_photo: null, delete_photo: '0',
            parent_name: '', parent_contact: '', student_type: 'Reguler',
        });
        setPhotoPreview(null); setCurrentPhoto(null); setFormOpen(true);
    };

    const openEdit = (s) => {
        setIsEdit(true); setEditId(s.id);
        santriForm.clearErrors();
        const metode = String(s.target_metode ?? 'quran').toLowerCase();
        const genderCode = (s.gender === 'P' || s.gender === 'Perempuan') ? 'P' : 'L';
        santriForm.setData({
            _method: 'PUT',
            nis: s.nis || '', name: s.name || '', gender: genderCode,
            target_metode: metode,
            target_metode_2: String(s.target_metode_2 ?? '').toLowerCase() === 'quran' ? 'quran' : '',
            target_juz: s.target_juz ?? '1.00',
            target_jilid: isJilidMetode(metode) ? (s.target_jilid ?? '1') : '',
            kelas_id: s.kelas_id ?? kelas.id,
            password: '', password_confirmation: '',
            profile_photo: null, delete_photo: '0',
            parent_name: s.parent_name ?? '',
            parent_contact: s.parent_contact ?? '',
            student_type: s.student_type ?? 'Reguler',
        });
        setPhotoPreview(null);
        setCurrentPhoto(s.user?.profile_photo ? storageUrl(s.user.profile_photo) : null);
        setFormOpen(true);
    };

    const onNameChange = (e) => santriForm.setData('name', smartCapitalize(e.target.value));

    const onMetodeChange = (value) => {
        santriForm.setData((prev) => ({
            ...prev,
            target_metode: value,
            // Al-Qur'an sebagai metode 1 → tidak ada metode 2
            target_metode_2: value === 'quran' ? '' : prev.target_metode_2,
            target_juz: prev.target_juz || '1.00',
            target_jilid: isJilidMetode(value) ? (prev.target_jilid || '1') : '',
        }));
    };

    const onPhotoChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        santriForm.setData('profile_photo', file);
        santriForm.setData('delete_photo', '0');
        const reader = new FileReader();
        reader.onload = (ev) => setPhotoPreview(ev.target.result);
        reader.readAsDataURL(file);
    };

    const removePhoto = () => {
        setPhotoPreview(null); setCurrentPhoto(null);
        santriForm.setData('profile_photo', null);
        santriForm.setData('delete_photo', '1');
    };

    const submitSantri = (e) => {
        e.preventDefault();
        const url = isEdit
            ? route('admin.santris.update', editId)
            : route('admin.santris.store');
        santriForm.post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setFormOpen(false),
        });
    };

    /* ---- Delete ---- */
    const deleteSantri = (s) => {
        if (confirm(`Apakah Anda yakin ingin menghapus Santri ${s.name}? Akun login juga akan dihapus.`)) {
            router.delete(route('admin.santris.destroy', s.id), { preserveScroll: true });
        }
    };
    const bulkDelete = () => {
        if (!selected.length) return;
        if (confirm(`Apakah Anda yakin ingin menghapus ${selected.length} santri yang dipilih? Akun login mereka juga akan dihapus.`)) {
            router.delete(route('admin.santris.bulk-destroy'), {
                data: { santri_ids: selected, kelas_id: kelas.id },
                preserveScroll: true,
                onSuccess: () => setSelected([]),
            });
        }
    };

    /* ---- Import & Bulk Update ---- */
    const importForm = useForm({ file: null, photos_zip: null });
    const submitImport = (e) => {
        e.preventDefault();
        importForm.post(route('admin.santris.import'), {
            forceFormData: true,
            onSuccess: () => { setImportOpen(false); importForm.reset(); },
        });
    };
    const bulkForm = useForm({ file: null, photos_zip: null, kelas_id: kelas.id });
    const submitBulk = (e) => {
        e.preventDefault();
        bulkForm.post(route('admin.santris.bulk-update'), {
            forceFormData: true,
            onSuccess: () => { setBulkOpen(false); bulkForm.reset('file', 'photos_zip'); },
        });
    };

    const err = (msg) => msg ? <p className="text-[11px] text-red-500 dark:text-red-400 mt-1">{msg}</p> : null;

    const maxJilid = jilidMaxPerMetode?.[santriForm.data.target_metode] ?? 6;
    const jilidOptions = Array.from({ length: maxJilid }, (_, i) => i + 1);

    return (
        <AuthenticatedLayout>
            <Head title={`Santri ${kelas.name}`} />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            <div>

                {/* ===== KEMBALI ===== */}
                <Link href={route('admin.santris.index')}
                    className="mb-4 inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Daftar Kelas
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-4">
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <UsersIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 truncate">Santri Kelas {kelas.name}</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            Guru: <span className="text-claude-600 dark:text-claude-400 font-semibold">{kelas.guru?.name ?? 'Belum ada guru'}</span>
                            {' · '}
                            Total: <span className="text-claude-600 dark:text-claude-400 font-semibold">{santris?.total ?? list.length} santri</span>
                            {/* 'campuran', bukan 'campur'. Salah satu huruf saja
                                membuat kelas campuran ikut diberi label "Khusus
                                Akhwat", karena nilainya lolos syarat lalu jatuh
                                ke cabang terakhir. */}
                            {kelas.gender_restriction && kelas.gender_restriction !== 'campuran' && (
                                <> · Khusus {kelas.gender_restriction === 'ikhwan' ? 'Laki-laki' : 'Perempuan'}</>
                            )}
                        </p>
                    </div>
                </div>

                {/* ===== WARNING GENDER MISMATCH ===== */}
                {hasGenderMismatch && (
                    <div className="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-2xl px-4 py-3 mb-4 flex items-start gap-2.5">
                        <AlertCircle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                        <p className="text-xs text-red-700 dark:text-red-300">
                            <strong>{mismatchedSantris.length} santri</strong> tidak sesuai restriksi gender kelas ini:{' '}
                            {mismatchedSantris.map((s) => s.name).join(', ')}
                        </p>
                    </div>
                )}

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
                        <PlusIcon className="w-3.5 h-3.5" /> Tambah Santri
                    </button>
                </div>

                {/* ===== BULK ACTION BAR ===== */}
                {selected.length > 0 && (
                    <div className="bg-claude-500/8 border border-claude-500/25 rounded-2xl px-4 py-3 mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-claude-700 dark:text-claude-400">
                                {selected.length} dari {allIds.length} santri dipilih
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
                    {list.length > 0 && (
                        <div className="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl">
                            <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 focus:ring-claude-500 cursor-pointer" />
                            <span className="text-xs font-semibold text-stone-600 dark:text-stone-400 flex-1">Pilih Semua</span>
                            <span className="text-[11px] text-stone-400 dark:text-stone-600">{list.length} data</span>
                        </div>
                    )}

                    {list.length === 0 ? (
                        <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl py-16 text-center">
                            <div className="text-4xl mb-3">🎓</div>
                            <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada santri di kelas ini</p>
                            <p className="text-xs text-stone-400 dark:text-stone-600 mt-1.5">Tap tombol "Tambah Santri" di atas</p>
                        </div>
                    ) : list.map((s, idx) => {
                        const isSel = selected.includes(s.id);
                        const isPerempuan = s.gender === 'P' || s.gender === 'Perempuan';
                        const photo = s.user?.profile_photo ? storageUrl(s.user.profile_photo) : null;
                        return (
                            <div key={s.id} className={`rounded-2xl border overflow-hidden transition-all ${isSel ? 'border-claude-500/60 ring-1 ring-claude-500/20 bg-claude-500/5' : 'border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800'}`}>
                                <div className="flex items-center gap-3 px-4 py-3">
                                    <input type="checkbox" checked={isSel} onChange={() => toggleOne(s.id)}
                                        className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 flex-shrink-0 cursor-pointer" />
                                    {photo ? (
                                        <img src={photo} alt="" className="w-10 h-10 rounded-full object-cover object-top border border-stone-200 dark:border-coal-600 flex-shrink-0" />
                                    ) : (
                                        <span className={`w-10 h-10 rounded-full border flex items-center justify-center text-sm font-bold flex-shrink-0 ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                            {s.name?.charAt(0).toUpperCase()}
                                        </span>
                                    )}
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-bold text-stone-900 dark:text-stone-100 truncate">{s.name}</p>
                                        <p className="text-[11px] text-stone-400 font-mono">{s.nis}</p>
                                    </div>
                                    <span className="text-[11px] text-stone-400 dark:text-stone-600 flex-shrink-0">#{(santris?.from ?? 1) + idx}</span>
                                </div>
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-700">
                                    <div className="px-3 py-2.5 text-center border-r border-stone-100 dark:border-coal-700">
                                        <p className="text-[10px] text-stone-400 mb-1">Gender</p>
                                        <p className={`text-xs font-bold ${isPerempuan ? 'text-pink-500 dark:text-pink-400' : 'text-blue-500 dark:text-blue-400'}`}>{isPerempuan ? 'Perempuan' : 'Laki-laki'}</p>
                                    </div>
                                    <div className="px-3 py-2.5 text-center">
                                        <p className="text-[10px] text-stone-400 mb-1">Target</p>
                                        <p className="text-xs font-bold text-emerald-600 dark:text-emerald-400">{targetDisplay(s)}</p>
                                    </div>
                                </div>
                                {s.user?.plain_password && (
                                    <div className="flex items-center gap-2 px-4 py-2 border-t border-stone-100 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                        <KeyIcon className="w-3.5 h-3.5 text-stone-400 dark:text-stone-600 flex-shrink-0" />
                                        <span className="text-[11px] text-stone-400 dark:text-stone-600">Password:</span>
                                        <PasswordReveal value={s.user?.plain_password} />
                                    </div>
                                )}
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-700">
                                    <button onClick={() => openEdit(s)} className="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-claude-600 dark:text-claude-400 hover:bg-claude-500/10 transition border-r border-stone-100 dark:border-coal-700">
                                        <EditIcon className="w-3.5 h-3.5" /> Edit
                                    </button>
                                    <button onClick={() => deleteSantri(s)} className="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
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
                                {['No', 'NIS', 'Nama Santri', 'Gender', 'Target', 'Password'].map((h) => (
                                    <th key={h} className="px-4 py-3 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">{h}</th>
                                ))}
                                <th className="px-4 py-3 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                            {list.length === 0 ? (
                                <tr><td colSpan={8} className="py-16 text-center">
                                    <div className="text-4xl mb-3">🎓</div>
                                    <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada santri di kelas ini</p>
                                    <p className="text-xs text-stone-400 dark:text-stone-600 mt-1">Klik "Tambah Santri" di atas</p>
                                </td></tr>
                            ) : list.map((s, idx) => {
                                const isSel = selected.includes(s.id);
                                const isPerempuan = s.gender === 'P' || s.gender === 'Perempuan';
                                const photo = s.user?.profile_photo ? storageUrl(s.user.profile_photo) : null;
                                return (
                                    <tr key={s.id} className={`transition-colors ${isSel ? 'bg-claude-500/5' : 'hover:bg-stone-50 dark:hover:bg-coal-700/30'}`}>
                                        <td className="px-4 py-3">
                                            <input type="checkbox" checked={isSel} onChange={() => toggleOne(s.id)}
                                                className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 cursor-pointer" />
                                        </td>
                                        <td className="px-4 py-3 text-sm text-stone-400 dark:text-stone-500">{(santris?.from ?? 1) + idx}</td>
                                        <td className="px-4 py-3 text-sm font-mono text-stone-500 dark:text-stone-400">{s.nis}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2.5">
                                                {photo ? (
                                                    <img src={photo} alt="" className="w-9 h-9 rounded-full object-cover object-top border border-stone-200 dark:border-coal-600 flex-shrink-0" />
                                                ) : (
                                                    <span className={`w-9 h-9 rounded-full border flex items-center justify-center text-xs font-bold flex-shrink-0 ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                                        {s.name?.charAt(0).toUpperCase()}
                                                    </span>
                                                )}
                                                <span className="text-sm font-medium text-stone-800 dark:text-stone-200">{s.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${isPerempuan ? 'bg-pink-50 text-pink-700 border-pink-200/70 dark:bg-pink-500/10 dark:text-pink-400 dark:border-pink-500/20' : 'bg-blue-50 text-blue-700 border-blue-200/70 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20'}`}>
                                                {isPerempuan ? 'Perempuan' : 'Laki-laki'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                                {targetDisplay(s)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3"><PasswordReveal value={s.user?.plain_password} /></td>
                                        <td className="px-4 py-3">
                                            <div className="flex gap-2 justify-center">
                                                <button onClick={() => openEdit(s)} className="px-3 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition">Edit</button>
                                                <button onClick={() => deleteSantri(s)} className="px-3 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:border-red-500/20 dark:text-red-400 text-xs font-semibold rounded-lg transition">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {/* ===== PAGINATION ===== */}
                <div className="mt-5">
                    <Pagination links={santris?.links ?? []} />
                </div>
            </div>

            {/* ===== MODAL: TAMBAH / EDIT SANTRI ===== */}
            <Modal open={formOpen} onClose={() => setFormOpen(false)}
                title={isEdit ? 'Edit Data Santri' : 'Tambah Santri Baru'} maxWidth="sm:max-w-2xl">
                <form onSubmit={submitSantri} className="p-5 space-y-4 overflow-y-auto" style={{ maxHeight: '72vh' }}>
                    {/* Foto */}
                    <div className="flex items-center gap-4">
                        <div className="flex-shrink-0">
                            {photoPreview ? (
                                <img src={photoPreview} className="w-16 h-16 rounded-full object-cover object-top border-2 border-claude-500/40" />
                            ) : currentPhoto ? (
                                <img src={currentPhoto} className="w-16 h-16 rounded-full object-cover object-top border-2 border-stone-300 dark:border-coal-600" />
                            ) : (
                                <span className="w-16 h-16 rounded-full bg-claude-500/10 border-2 border-claude-500/20 flex items-center justify-center">
                                    <UserIcon className="w-7 h-7 text-claude-400" />
                                </span>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-claude-500/10 border border-claude-500/20 text-claude-600 dark:text-claude-400 hover:bg-claude-500/20 transition">
                                {(photoPreview || currentPhoto) ? 'Ganti Foto' : 'Pilih Foto (opsional)'}
                                <input type="file" accept="image/jpeg,image/png,image/jpg" className="hidden" onChange={onPhotoChange} />
                            </label>
                            {(photoPreview || currentPhoto) && (
                                <button type="button" onClick={removePhoto} className="text-[11px] font-semibold text-red-600 dark:text-red-400 hover:underline text-left">Hapus Foto</button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className={LBL}>NIS <span className="text-red-500">*</span></label>
                            <input value={santriForm.data.nis} onChange={(e) => santriForm.setData('nis', e.target.value)} required placeholder="20240001" className={INP} />
                            {err(santriForm.errors.nis)}
                        </div>
                        <div>
                            <label className={LBL}>Jenis Kelamin <span className="text-red-500">*</span></label>
                            <select value={santriForm.data.gender} onChange={(e) => santriForm.setData('gender', e.target.value)} required className={INP}>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                            {err(santriForm.errors.gender)}
                        </div>
                    </div>

                    <div>
                        <label className={LBL}>Nama Lengkap <span className="text-red-500">*</span></label>
                        <input value={santriForm.data.name} onChange={onNameChange} required placeholder="Ahmad Zaki" className={INP} />
                        {err(santriForm.errors.name)}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-3">
                            <div>
                                <label className={LBL}>Metode Target <span className="text-red-500">*</span></label>
                                <select value={santriForm.data.target_metode} onChange={(e) => onMetodeChange(e.target.value)} required className={INP}>
                                    {(targetMetodeOptions?.length ? targetMetodeOptions : [
                                        { value: 'quran', label: "Al-Qur'an" },
                                        { value: 'iqro', label: 'Iqro' },
                                        { value: 'aisar', label: 'Aisar' },
                                    ]).map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                                {err(santriForm.errors.target_metode)}
                            </div>

                             {santriForm.data.target_metode === 'quran' ? (
                                <div>
                                    <label className={LBL}>Target Juz</label>
                                    <input type="number" step="0.25" min="0" max="30" placeholder="cth: 5.50"
                                        value={santriForm.data.target_juz} onChange={(e) => santriForm.setData('target_juz', e.target.value)} className={INP} />
                                    {err(santriForm.errors.target_juz)}
                                </div>
                            ) : (
                                <>
                                    <div>
                                        <label className={LBL}>Target Jilid</label>
                                        <select value={santriForm.data.target_jilid} onChange={(e) => santriForm.setData('target_jilid', e.target.value)} className={INP}>
                                            {jilidOptions.map((i) => (
                                                <option key={i} value={i}>
                                                    {santriForm.data.target_metode === 'aisar' ? 'Aisar' : 'Iqro'} Jilid {i}
                                                </option>
                                            ))}
                                        </select>
                                        {err(santriForm.errors.target_jilid)}
                                    </div>

                                    <label className="flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox"
                                            checked={santriForm.data.target_metode_2 === 'quran'}
                                            onChange={(e) => santriForm.setData('target_metode_2', e.target.checked ? 'quran' : '')}
                                            className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 focus:ring-claude-500" />
                                        <span className="text-[11px] font-semibold text-stone-600 dark:text-stone-400">
                                            + Tambah target Al-Qur'an (Juz)
                                        </span>
                                    </label>
                                    {err(santriForm.errors.target_metode_2)}

                                    {santriForm.data.target_metode_2 === 'quran' && (
                                        <div>
                                            <label className={LBL}>Target Juz (Al-Qur'an)</label>
                                            <input type="number" step="0.25" min="0" max="30" placeholder="cth: 2.00"
                                                value={santriForm.data.target_juz} onChange={(e) => santriForm.setData('target_juz', e.target.value)} className={INP} />
                                            {err(santriForm.errors.target_juz)}
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                        <div>
                            <label className={LBL}>Kelas</label>
                            <input type="text" value={kelas.name} readOnly
                                className={`${INP} bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 cursor-not-allowed`} />
                            <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">
                                Terisi {kelas.santris_count ?? 0} dari {kelas.kapasitas ?? '-'} santri.
                            </p>
                            {err(santriForm.errors.kelas_id)}
                        </div>
                    </div>

                    {/* Data wali — dibutuhkan modul keuangan untuk penagihan */}
                    <div className="rounded-2xl border border-stone-200 dark:border-coal-700 p-3.5">
                        <h4 className="mb-3 text-xs font-bold text-stone-700 dark:text-stone-300">Data Orang Tua / Wali</h4>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className={LBL}>Nama Wali <span className="text-red-500">*</span></label>
                                <input value={santriForm.data.parent_name}
                                    onChange={(e) => santriForm.setData('parent_name', e.target.value)}
                                    required placeholder="Bapak Ahmad" className={INP} />
                                {err(santriForm.errors.parent_name)}
                            </div>
                            <div>
                                <label className={LBL}>Kontak Wali <span className="text-red-500">*</span></label>
                                <input value={santriForm.data.parent_contact}
                                    onChange={(e) => santriForm.setData('parent_contact', e.target.value)}
                                    required placeholder="0812..." className={INP} />
                                {err(santriForm.errors.parent_contact)}
                            </div>
                        </div>
                        <div className="mt-3">
                            <label className={LBL}>Tipe Santri <span className="text-red-500">*</span></label>
                            <select value={santriForm.data.student_type}
                                onChange={(e) => santriForm.setData('student_type', e.target.value)} required className={INP}>
                                <option value="Reguler">Reguler</option>
                                <option value="Fullday">Fullday</option>
                            </select>
                            {err(santriForm.errors.student_type)}
                            <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Menentukan tarif di modul keuangan.</p>
                        </div>
                    </div>

                    <div className="bg-claude-500/8 border border-claude-500/20 rounded-2xl p-3.5">
                        <h4 className="text-xs font-bold text-claude-700 dark:text-claude-400 mb-3 flex items-center gap-1.5">
                            <KeyIcon className="w-3.5 h-3.5" /> Akun Login Santri
                        </h4>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className={LBL}>
                                    {isEdit ? 'Password Baru' : 'Password'} {isEdit ? <span className="text-stone-400 text-[10px] font-normal">(kosongkan jika tidak diubah)</span> : <span className="text-red-500">*</span>}
                                </label>
                                <PasswordInput value={santriForm.data.password} onChange={(e) => santriForm.setData('password', e.target.value)} placeholder="Minimal 8 karakter" required={!isEdit} />
                                {err(santriForm.errors.password)}
                            </div>
                            <div>
                                <label className={LBL}>Konfirmasi Password {!isEdit && <span className="text-red-500">*</span>}</label>
                                <PasswordInput value={santriForm.data.password_confirmation} onChange={(e) => santriForm.setData('password_confirmation', e.target.value)} placeholder="Ulangi password" required={!isEdit} />
                                {isEdit && <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Isi hanya jika mengubah password</p>}
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={() => setFormOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={santriForm.processing}
                            className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-claude-600 hover:bg-claude-500 shadow-lg shadow-claude-500/25 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            {santriForm.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Santri'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: TEMPLATE ===== */}
            <Modal open={templateOpen} onClose={() => setTemplateOpen(false)} title="Download Template Import Santri" maxWidth="sm:max-w-sm">
                <div className="p-5 space-y-4">
                    <div className="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs text-emerald-700/90 dark:text-emerald-300/80">
                            Template Excel berisi format kolom yang benar. Kolom metode diisi <b>quran</b>, <b>iqro</b>, atau <b>aisar</b> — quran memakai Target Juz, iqro/aisar memakai Target Jilid.
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <button onClick={() => setTemplateOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <a href={route('admin.santris.download-template')} onClick={() => setTimeout(() => setTemplateOpen(false), 500)}
                            className="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition text-center">Download Template</a>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL: EXPORT ===== */}
            <Modal open={exportOpen} onClose={() => setExportOpen(false)} title="Export Data Santri untuk Edit" maxWidth="sm:max-w-sm">
                <div className="p-5 space-y-4">
                    <div className="bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs text-violet-700/90 dark:text-violet-300/80">
                            File Excel berisi seluruh santri kelas <b>{kelas.name}</b>. Edit data lalu upload kembali lewat tombol Update untuk perubahan massal.
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <button onClick={() => setExportOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <a href={route('admin.santris.export-for-edit', kelas.id)} onClick={() => setTimeout(() => setExportOpen(false), 500)}
                            className="flex-1 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold rounded-xl transition text-center">Download Excel</a>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL: IMPORT ===== */}
            <Modal open={importOpen} onClose={() => setImportOpen(false)} title="Import Data Santri dari Excel">
                <form onSubmit={submitImport} className="p-5 space-y-4">
                    <div>
                        <label className={LBL}>Pilih File Excel <span className="text-red-500">*</span></label>
                        <input type="file" accept=".xlsx,.xls" required onChange={(e) => importForm.setData('file', e.target.files[0] ?? null)} className={FILE_INP} />
                        {err(importForm.errors.file)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                    </div>
                    <div>
                        <label className={LBL}>ZIP Foto (opsional)</label>
                        <input type="file" accept=".zip" onChange={(e) => importForm.setData('photos_zip', e.target.files[0] ?? null)} className={FILE_INP} />
                        {err(importForm.errors.photos_zip)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Nama tiap foto = kolom <b>nama_file_foto</b> di Excel. Maks 20MB.</p>
                    </div>
                    <div className="bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1.5">📋 Langkah-langkah import:</p>
                        <ol className="text-[11px] text-blue-700/80 dark:text-blue-300/70 space-y-0.5 list-decimal list-inside">
                            <li>Download template, isi data + kolom nama_file_foto</li>
                            <li>Kolom metode: quran / iqro / aisar</li>
                            <li>Kumpulkan foto jadi 1 ZIP, upload di kolom ZIP Foto</li>
                            <li>Kolom no_kelas menentukan kelas tiap santri</li>
                        </ol>
                    </div>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setImportOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={importForm.processing || !importForm.data.file} className="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition disabled:opacity-50">
                            {importForm.processing ? 'Mengimport...' : 'Import Data'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: BULK UPDATE ===== */}
            <Modal open={bulkOpen} onClose={() => setBulkOpen(false)} title="Bulk Update Data Santri">
                <form onSubmit={submitBulk} className="p-5 space-y-4">
                    <div className="bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1.5">📋 Langkah-Langkah:</p>
                        <ol className="text-[11px] text-blue-700/80 dark:text-blue-300/70 space-y-0.5 list-decimal list-inside">
                            <li>Klik tombol "Export" untuk download data santri</li>
                            <li>Edit data di Excel (JANGAN ubah kolom NO URUT)</li>
                            <li>Upload Excel yang sudah diedit di sini</li>
                        </ol>
                    </div>
                    <div>
                        <label className={LBL}>File Excel hasil Export <span className="text-red-500">*</span></label>
                        <input type="file" accept=".xlsx,.xls" required onChange={(e) => bulkForm.setData('file', e.target.files[0] ?? null)} className={FILE_INP} />
                        {err(bulkForm.errors.file)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                    </div>
                    <div>
                        <label className={LBL}>ZIP Foto (opsional)</label>
                        <input type="file" accept=".zip" onChange={(e) => bulkForm.setData('photos_zip', e.target.files[0] ?? null)} className={FILE_INP} />
                        {err(bulkForm.errors.photos_zip)}
                        <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1">Kosongkan kalau tidak ubah foto. Maks 20MB.</p>
                    </div>
                    <div className="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-2xl px-4 py-3">
                        <p className="text-xs font-semibold text-red-700 dark:text-red-400 mb-1.5">⚠️ Peringatan:</p>
                        <ul className="text-[11px] text-red-700/80 dark:text-red-300/70 space-y-0.5 list-disc list-inside">
                            <li>NO URUT wajib ada — jangan dihapus/ubah</li>
                            <li>Jangan tambah/hapus baris</li>
                            <li>Metode: quran / iqro / aisar</li>
                            <li>Password tidak bisa diubah via Excel</li>
                        </ul>
                    </div>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setBulkOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={bulkForm.processing || !bulkForm.data.file} className="flex-1 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold rounded-xl transition disabled:opacity-50">
                            {bulkForm.processing ? 'Mengupdate...' : 'Update Semua Data'}
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
                input[type="checkbox"]{accent-color:#7EB842}
                @keyframes slideUp{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:translateY(0)}}
                @media(min-width:640px){@keyframes slideUp{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}}
            `}</style>
        </AuthenticatedLayout>
    );
}
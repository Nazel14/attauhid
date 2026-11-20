import { useState, useEffect, useRef } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line, konsisten dengan Dashboard
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
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
const UsersIcon = (p) => <I {...p} d={<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />} />;
const KeyIcon = (p) => <I {...p} d={<path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />} />;
const EyeIcon = (p) => <I {...p} d={<><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></>} />;
const EyeOffIcon = (p) => <I {...p} d={<path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const InfoIcon = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const WarnIcon = (p) => <I {...p} d={<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />} />;
const ListIcon = (p) => <I {...p} d={<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />} />;

/* ============================================================
 * TOKEN DESAIN — disamakan dengan Dashboard & Input Presensi
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const SUB = 'bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600 rounded-xl';
const TILE = 'w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0';
const INP = 'w-full bg-white dark:bg-coal-600/40 border border-cream-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-500 dark:[color-scheme:dark]';
const LBL = 'block text-xs font-medium text-stone-600 dark:text-stone-400 mb-1.5';
const HINT = 'text-[11px] text-stone-400 dark:text-stone-500 mt-1';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const BTN_PRIMARY = 'flex-1 py-2.5 rounded-xl text-sm font-semibold text-white bg-claude-500 hover:bg-claude-600 transition active:scale-[.98] disabled:opacity-50 disabled:cursor-not-allowed text-center';
const BTN_SECONDARY = 'flex-1 py-2.5 rounded-xl text-sm font-semibold bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-700 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition';
const FILE_INP = 'w-full text-xs text-stone-600 dark:text-stone-400 bg-white dark:bg-coal-600/40 border border-cream-300 dark:border-coal-600 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-claude-100 dark:file:bg-claude-500/15 file:text-claude-700 dark:file:text-claude-400 hover:file:bg-claude-200 dark:hover:file:bg-claude-500/25';
const CHECKBOX = 'w-4 h-4 rounded border-cream-400 dark:border-coal-500 dark:bg-coal-600/40 text-claude-500 focus:ring-claude-500 cursor-pointer';

// Sama dengan METODE_BADGE di Dashboard.jsx
const METODE_BADGE = {
    quran: 'bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-claude-200 dark:ring-claude-500/20',
    iqro:  'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20',
    aisar: 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400 ring-sky-100 dark:ring-sky-500/20',
};
const GENDER = {
    P: { avatar: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400', badge: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-700 dark:text-pink-400' },
    L: { avatar: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400', badge: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-700 dark:text-sky-400' },
};

const storageUrl = (path) => (path ? `/storage/${path}` : null);
const smartCapitalize = (value) => value.replace(/\b\w/g, (c) => c.toUpperCase());
const targetDisplay = (s) => {
    let primary;
    if (s.target_metode === 'iqro')       primary = `Iqro Jilid ${s.target_jilid ?? '-'}`;
    else if (s.target_metode === 'aisar') primary = `Aisar Jilid ${s.target_jilid ?? '-'}`;
    else                                  primary = `${s.target_juz ?? '-'} Juz`;

    if (s.target_metode_2 === 'quran') primary += ` + ${s.target_juz ?? '-'} Juz`;
    return primary;
};
const metodeTone = (s) => (s.target_metode === 'iqro' || s.target_metode === 'aisar') ? s.target_metode : 'quran';

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
                className="fixed top-3 left-3 right-3 sm:left-auto sm:right-3 sm:w-96 z-[300] overflow-hidden bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl shadow-xl"
                style={{ animation: 'toastIn .3s cubic-bezier(.21,1.02,.73,1)' }}
                role="alert"
            >
                <div className="flex items-start gap-3 px-3.5 py-3">
                    <span className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl ${success ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400'}`}>
                        {success ? <CheckCircle className="w-4 h-4" /> : <AlertCircle className="w-4 h-4" />}
                    </span>
                    <div className="min-w-0 flex-1 pt-0.5">
                        <p className={`text-xs font-bold ${success ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'}`}>
                            {success ? 'Berhasil' : 'Gagal'}
                        </p>
                        <p className="mt-0.5 break-words text-sm text-stone-700 dark:text-stone-200" dangerouslySetInnerHTML={{ __html: notif.message }} />
                    </div>
                    <button onClick={onClose} className="flex-shrink-0 mt-0.5 w-6 h-6 rounded-lg flex items-center justify-center text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-cream-200 dark:hover:bg-coal-600 transition">
                        <XIcon className="w-3.5 h-3.5" />
                    </button>
                </div>
                <div className="h-0.5 w-full bg-cream-200 dark:bg-coal-600">
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

function Modal({ open, onClose, title, icon = null, children, maxWidth = 'sm:max-w-md' }) {
    useEffect(() => {
        if (open) document.body.classList.add('overflow-hidden');
        else document.body.classList.remove('overflow-hidden');
        return () => document.body.classList.remove('overflow-hidden');
    }, [open]);
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-[80] flex flex-col justify-end sm:justify-center sm:items-center sm:p-4">
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />
            <div className={`relative w-full bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden animate-[slideUp_.25s_ease-out] ${maxWidth}`}>
                <div className="w-10 h-1 bg-cream-400 dark:bg-coal-500 rounded-full mx-auto mt-3 sm:hidden" />
                <div className="flex items-center gap-2 px-5 py-3.5 border-b border-cream-300 dark:border-coal-600">
                    {icon && <span className={TILE}>{icon}</span>}
                    <h3 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200 flex-1">{title}</h3>
                    <button onClick={onClose} className="w-7 h-7 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

function InfoBox({ tone = 'neutral', icon, title, children }) {
    const map = {
        neutral: { box: SUB, head: 'text-stone-700 dark:text-stone-200', body: 'text-stone-600 dark:text-stone-400', icon: 'text-stone-400' },
        red: { box: 'bg-red-50 dark:bg-red-500/10 ring-1 ring-red-100 dark:ring-red-500/20 rounded-xl', head: 'text-red-700 dark:text-red-400', body: 'text-red-700/80 dark:text-red-300/80', icon: 'text-red-500 dark:text-red-400' },
    };
    const t = map[tone];
    return (
        <div className={`${t.box} px-3.5 py-3`}>
            {title && (
                <p className={`text-xs font-semibold mb-1.5 flex items-center gap-1.5 ${t.head}`}>
                    {icon && <span className={t.icon}>{icon}</span>}{title}
                </p>
            )}
            <div className={`text-[11px] leading-relaxed ${t.body}`}>{children}</div>
        </div>
    );
}

function Avatar({ s, size = 'w-9 h-9 text-xs' }) {
    const photo = s.user?.profile_photo ? storageUrl(s.user.profile_photo) : null;
    const g = (s.gender === 'P' || s.gender === 'Perempuan') ? 'P' : 'L';
    if (photo) {
        return <img src={photo} alt="" className={`${size} rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0`} />;
    }
    return (
        <span className={`${size} rounded-full ring-1 flex items-center justify-center font-bold flex-shrink-0 ${GENDER[g].avatar}`}>
            {s.name?.charAt(0).toUpperCase()}
        </span>
    );
}

function Badge({ className, children }) {
    return <span className={`inline-flex items-center px-2 py-0.5 rounded-md ring-1 text-[10px] font-semibold whitespace-nowrap ${className}`}>{children}</span>;
}

function PasswordReveal({ value }) {
    const [show, setShow] = useState(false);
    return (
        <div className="flex items-center gap-1.5">
            <span className={`font-mono text-xs ${show ? 'text-stone-700 dark:text-stone-300' : 'text-stone-400 dark:text-stone-500'}`}>
                {show ? (value || '-') : '••••••••'}
            </span>
            {value && (
                <button onClick={() => setShow((s) => !s)} className="text-stone-400 hover:text-claude-500 dark:hover:text-claude-400 transition">
                    {show ? <EyeOffIcon className="w-3.5 h-3.5" /> : <EyeIcon className="w-3.5 h-3.5" />}
                </button>
            )}
        </div>
    );
}

function PasswordInput({ value, onChange, name, placeholder, required }) {
    const [show, setShow] = useState(false);
    return (
        <div className="relative">
            <input name={name} type={show ? 'text' : 'password'} value={value} onChange={onChange}
                placeholder={placeholder} required={required} className={`${INP} pr-9`} />
            <button type="button" onClick={() => setShow((s) => !s)}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
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
                    <span key={i} className="px-3 py-1.5 text-xs rounded-lg text-stone-300 dark:text-stone-600 cursor-not-allowed"
                        dangerouslySetInnerHTML={{ __html: link.label }} />
                ) : (
                    <Link key={i} href={link.url} preserveScroll
                        className={`px-3 py-1.5 text-xs font-semibold rounded-lg ring-1 transition ${link.active
                            ? 'bg-claude-500 text-white ring-claude-500'
                            : 'bg-white dark:bg-coal-700 text-stone-600 dark:text-stone-300 ring-cream-300 dark:ring-coal-600 hover:bg-cream-100 dark:hover:bg-coal-600'}`}
                        dangerouslySetInnerHTML={{ __html: link.label }} />
                )
            )}
        </div>
    );
}

function EmptyState({ hint }) {
    return (
        <div className="py-14 flex flex-col items-center text-center">
            <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                <UsersIcon className="w-5 h-5" />
            </span>
            <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">Belum ada data santri</p>
            <p className="text-[11px] text-stone-400 mt-0.5">{hint}</p>
        </div>
    );
}

/* ============================================================
 * MAIN PAGE — Guru / Santri / Index
 * ============================================================ */
export default function Index({ santris, kelasList = [], targetMetodeOptions = [], guru }) {
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
        // flash yang sudah ada saat halaman pertama dimuat
        if (flash?.success) showToast(flash.success, 'success');
        else if (flash?.error) showToast(flash.error, 'error');

        // dipicu setiap kunjungan Inertia yang sukses (edit/hapus/import/dll)
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

    const defaultKelasId = kelasList[0]?.id ?? '';

    /* ---- Form Santri ---- */
    const santriForm = useForm({
        _method: 'POST',
        nis: '', name: '', gender: 'L',
        target_metode: 'quran', target_juz: '1.00', target_jilid: '1',
        target_metode_2: '',
        kelas_id: defaultKelasId,
        password: '', password_confirmation: '',
        profile_photo: null, delete_photo: '0',
    });

    const openCreate = () => {
        setIsEdit(false); setEditId(null);
        santriForm.clearErrors();
        santriForm.setData({
            _method: 'POST',
            nis: '', name: '', gender: 'L',
            target_metode: 'quran', target_juz: '1.00', target_jilid: '1',
            target_metode_2: '',
            kelas_id: defaultKelasId,
            password: '', password_confirmation: '',
            profile_photo: null, delete_photo: '0',
        });
        setPhotoPreview(null); setCurrentPhoto(null); setFormOpen(true);
    };

    const openEdit = (s) => {
        setIsEdit(true); setEditId(s.id);
        santriForm.clearErrors();
        const genderCode = (s.gender === 'P' || s.gender === 'Perempuan') ? 'P' : 'L';
        santriForm.setData({
            _method: 'PUT',
            nis: s.nis || '', name: s.name || '', gender: genderCode,
            target_metode: s.target_metode || 'quran',
            target_juz: s.target_juz ?? '1.00',
            target_jilid: s.target_jilid ?? '1',
            target_metode_2: s.target_metode_2 || '',
            kelas_id: s.kelas_id,
            password: '', password_confirmation: '',
            profile_photo: null, delete_photo: '0',
        });
        setPhotoPreview(null);
        setCurrentPhoto(s.user?.profile_photo ? storageUrl(s.user.profile_photo) : null);
        setFormOpen(true);
    };

    const onNameChange = (e) => santriForm.setData('name', smartCapitalize(e.target.value));

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
        const url = isEdit ? `/guru/santris/${editId}` : route('guru.santris.store');
        santriForm.post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setFormOpen(false),
        });
    };

    /* ---- Delete ---- */
    const deleteSantri = (s) => {
        if (confirm(`Apakah Anda yakin ingin menghapus Santri ${s.name}? Akun login juga akan dihapus.`)) {
            router.delete(route('guru.santris.destroy', s.id), { preserveScroll: true });
        }
    };
    const bulkDelete = () => {
        if (!selected.length) {
            alert('Pilih minimal 1 santri untuk dihapus');
            return;
        }
        if (confirm(`Apakah Anda yakin ingin menghapus ${selected.length} santri yang dipilih? Akun login mereka juga akan dihapus.`)) {
            router.delete(route('guru.santris.bulk-destroy'), {
                data: { santri_ids: selected },
                preserveScroll: true,
                onSuccess: () => setSelected([]),
            });
        }
    };

    /* ---- Import & Bulk Update ---- */
    const importForm = useForm({ file: null, photos_zip: null });
    const submitImport = (e) => {
        e.preventDefault();
        importForm.post(route('guru.santris.import'), {
            forceFormData: true,
            onSuccess: () => { setImportOpen(false); importForm.reset(); },
        });
    };
    const bulkForm = useForm({ file: null, photos_zip: null });
    const submitBulk = (e) => {
        e.preventDefault();
        bulkForm.post(route('guru.santris.bulk-update'), {
            forceFormData: true,
            onSuccess: () => { setBulkOpen(false); bulkForm.reset('file', 'photos_zip'); },
        });
    };

    const err = (msg) => msg ? <p className="text-[11px] text-red-500 dark:text-red-400 mt-1">{msg}</p> : null;
    const total = santris?.total ?? list.length;

    return (
        <GuruAuthenticatedLayout>
            <Head title="Santri Saya" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            {/* Padding & py dibuang — <main> di layout sudah memberinya
                (sama seperti Dashboard). */}
            <div className="max-w-7xl mx-auto">

                {/* Kembali — mobile saja */}
                <Link href={route('guru.dashboard')} className={`${BTN_SOFT} mb-4 sm:hidden`}>
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>

                {/* ===== HEADER (sama dengan Dashboard) ===== */}
                <div className="flex flex-wrap items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0 text-stone-600 dark:text-stone-300">
                        <UsersIcon className="w-6 h-6" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Management Santri</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            {guru?.name && (<>Guru <span className="font-semibold text-claude-600 dark:text-claude-400">{guru.name}</span><span className="mx-1 opacity-40">·</span></>)}
                            <span className="font-semibold text-stone-700 dark:text-stone-300">{total}</span> santri
                        </p>
                    </div>
                </div>

                {/* ===== ACTION BUTTONS ===== */}
                <div className="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 mb-4">
                    <button onClick={() => setTemplateOpen(true)} className={BTN_SOFT}>
                        <DownloadIcon className="w-3.5 h-3.5 text-stone-400" /> Template
                    </button>
                    <button onClick={() => setImportOpen(true)} className={BTN_SOFT}>
                        <ImportIcon className="w-3.5 h-3.5 text-stone-400" /> Import
                    </button>
                    <button onClick={() => setExportOpen(true)} className={BTN_SOFT}>
                        <ExportIcon className="w-3.5 h-3.5 text-stone-400" /> Export
                    </button>
                    <button onClick={() => setBulkOpen(true)} className={BTN_SOFT}>
                        <RefreshIcon className="w-3.5 h-3.5 text-stone-400" /> Update
                    </button>
                    <button onClick={openCreate} className="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-claude-500 hover:bg-claude-600 transition active:scale-95 col-span-2 sm:col-span-1 sm:ml-auto">
                        <PlusIcon className="w-3.5 h-3.5" /> Tambah Santri
                    </button>
                </div>

                {/* ===== BULK ACTION BAR ===== */}
                {selected.length > 0 && (
                    <div className="bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-200 dark:ring-claude-500/20 rounded-2xl px-4 py-3 mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-claude-700 dark:text-claude-400">
                                {selected.length} dari {allIds.length} santri dipilih
                            </p>
                            <button onClick={() => setSelected([])} className="text-[11px] text-stone-500 hover:text-claude-600 dark:text-stone-400 dark:hover:text-claude-400">
                                Bersihkan pilihan
                            </button>
                        </div>
                        <button onClick={bulkDelete} className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold rounded-xl transition active:scale-95 flex-shrink-0">
                            <TrashIcon className="w-3.5 h-3.5" /> Hapus Terpilih
                        </button>
                    </div>
                )}

                {/* ===== MOBILE CARDS ===== */}
                <div className="lg:hidden space-y-2">
                    {list.length > 0 && (
                        <div className={`${CARD} flex items-center gap-3 px-4 py-2.5`}>
                            <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                className={CHECKBOX} />
                            <span className="text-xs font-semibold text-stone-600 dark:text-stone-300 flex-1">Pilih Semua</span>
                            <span className="text-[11px] text-stone-400">{list.length} data</span>
                        </div>
                    )}

                    {list.length === 0 ? (
                        <div className={CARD}><EmptyState hint='Tap tombol "Tambah Santri" di atas' /></div>
                    ) : list.map((s, idx) => {
                        const isSel = selected.includes(s.id);
                        const g = (s.gender === 'P' || s.gender === 'Perempuan') ? 'P' : 'L';
                        return (
                            <div key={s.id} className={`rounded-2xl ring-1 overflow-hidden transition-all ${isSel ? 'ring-2 ring-claude-500 bg-claude-50 dark:bg-claude-500/10' : 'ring-cream-300 dark:ring-coal-600 bg-white dark:bg-coal-700'}`}>
                                <div className="flex items-center gap-3 px-4 py-3">
                                    <input type="checkbox" checked={isSel} onChange={() => toggleOne(s.id)} className={`${CHECKBOX} flex-shrink-0`} />
                                    <Avatar s={s} size="w-10 h-10 text-sm" />
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-stone-800 dark:text-stone-100 truncate">{s.name}</p>
                                        <p className="text-[11px] text-stone-400 font-mono">{s.nis}</p>
                                    </div>
                                    <span className="text-[11px] text-stone-400 tabular-nums flex-shrink-0">#{(santris?.from ?? 1) + idx}</span>
                                </div>
                                <div className="grid grid-cols-3 border-t border-cream-200 dark:border-coal-600/60 divide-x divide-cream-200 dark:divide-coal-600/60">
                                    <div className="px-2 py-2.5 text-center">
                                        <p className="text-[10px] text-stone-400 mb-1">Kelas</p>
                                        <p className="text-xs font-semibold text-stone-700 dark:text-stone-200 truncate">{s.kelas?.name ?? '-'}</p>
                                    </div>
                                    <div className="px-2 py-2.5 text-center">
                                        <p className="text-[10px] text-stone-400 mb-1">Gender</p>
                                        <Badge className={GENDER[g].badge}>{g}</Badge>
                                    </div>
                                    <div className="px-2 py-2.5 text-center">
                                        <p className="text-[10px] text-stone-400 mb-1">Target</p>
                                        <Badge className={METODE_BADGE[metodeTone(s)]}>{targetDisplay(s)}</Badge>
                                    </div>
                                </div>
                                {s.user?.plain_password && (
                                    <div className="flex items-center gap-2 px-4 py-2 border-t border-cream-200 dark:border-coal-600/60 bg-cream-100 dark:bg-coal-600/40">
                                        <KeyIcon className="w-3.5 h-3.5 text-stone-400 flex-shrink-0" />
                                        <span className="text-[11px] text-stone-400">Password:</span>
                                        <PasswordReveal value={s.user?.plain_password} />
                                    </div>
                                )}
                                <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600/60 divide-x divide-cream-200 dark:divide-coal-600/60">
                                    <button onClick={() => openEdit(s)} className="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600/40 transition">
                                        <EditIcon className="w-3.5 h-3.5" /> Edit
                                    </button>
                                    <button onClick={() => deleteSantri(s)} className="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
                                        <TrashIcon className="w-3.5 h-3.5" /> Hapus
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* ===== DESKTOP TABLE (sama dengan tabel Dashboard) ===== */}
                <div className={`hidden lg:block ${CARD} p-5`}>
                    <div className="flex items-center gap-2 mb-4">
                        <span className={TILE}><UsersIcon className="w-3.5 h-3.5" /></span>
                        <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Daftar Santri</h2>
                        <span className="ml-auto text-[11px] text-stone-400">{total} santri</span>
                    </div>
                    <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                    <th className="px-4 py-2.5 w-10 text-left">
                                        <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                            ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                            className={CHECKBOX} />
                                    </th>
                                    {['No', 'NIS', 'Nama Santri', 'Gender', 'Target', 'Kelas', 'Password'].map((h) => (
                                        <th key={h} className="px-4 py-2.5 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400">{h}</th>
                                    ))}
                                    <th className="px-4 py-2.5 text-center text-[11px] font-semibold text-stone-500 dark:text-stone-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                {list.length === 0 ? (
                                    <tr><td colSpan={9}><EmptyState hint='Klik "Tambah Santri" di atas' /></td></tr>
                                ) : list.map((s, idx) => {
                                    const isSel = selected.includes(s.id);
                                    const g = (s.gender === 'P' || s.gender === 'Perempuan') ? 'P' : 'L';
                                    return (
                                        <tr key={s.id} className={`transition-colors ${isSel ? 'bg-claude-50 dark:bg-claude-500/10' : 'hover:bg-cream-100 dark:hover:bg-coal-600/30'}`}>
                                            <td className="px-4 py-3">
                                                <input type="checkbox" checked={isSel} onChange={() => toggleOne(s.id)} className={CHECKBOX} />
                                            </td>
                                            <td className="px-4 py-3 text-xs text-stone-400 tabular-nums">{(santris?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-xs font-mono text-stone-500 dark:text-stone-400">{s.nis}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2.5">
                                                    <Avatar s={s} />
                                                    <span className="text-sm text-stone-700 dark:text-stone-200">{s.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3"><Badge className={GENDER[g].badge}>{g}</Badge></td>
                                            <td className="px-4 py-3"><Badge className={METODE_BADGE[metodeTone(s)]}>{targetDisplay(s)}</Badge></td>
                                            <td className="px-4 py-3">
                                                <Badge className="bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 text-stone-600 dark:text-stone-300">{s.kelas?.name ?? '-'}</Badge>
                                            </td>
                                            <td className="px-4 py-3"><PasswordReveal value={s.user?.plain_password} /></td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1.5 justify-center">
                                                    <button onClick={() => openEdit(s)} className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 hover:bg-cream-300 dark:hover:bg-coal-500 text-stone-600 dark:text-stone-300 text-xs font-semibold transition active:scale-95">
                                                        <EditIcon className="w-3.5 h-3.5" /> Edit
                                                    </button>
                                                    <button onClick={() => deleteSantri(s)} className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-500/10 ring-1 ring-red-100 dark:ring-red-500/20 hover:bg-red-100 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-semibold transition active:scale-95">
                                                        <TrashIcon className="w-3.5 h-3.5" /> Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ===== PAGINATION ===== */}
                <div className="mt-5">
                    <Pagination links={santris?.links ?? []} />
                </div>
            </div>

            {/* ===== MODAL: TAMBAH / EDIT SANTRI ===== */}
            <Modal open={formOpen} onClose={() => setFormOpen(false)}
                icon={isEdit ? <EditIcon className="w-3.5 h-3.5" /> : <PlusIcon className="w-3.5 h-3.5" />}
                title={isEdit ? 'Edit Data Santri' : 'Tambah Santri Baru'} maxWidth="sm:max-w-2xl">
                <form onSubmit={submitSantri} className="p-5 space-y-4 overflow-y-auto" style={{ maxHeight: '72vh' }}>
                    {/* Foto */}
                    <div className="flex items-center gap-4">
                        <div className="flex-shrink-0">
                            {photoPreview ? (
                                <img src={photoPreview} className="w-16 h-16 rounded-full object-cover object-top ring-2 ring-claude-500" />
                            ) : currentPhoto ? (
                                <img src={currentPhoto} className="w-16 h-16 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600" />
                            ) : (
                                <span className="w-16 h-16 rounded-full bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center">
                                    <UserIcon className="w-7 h-7 text-stone-400" />
                                </span>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5 items-start">
                            <label className={`${BTN_SOFT} cursor-pointer`}>
                                {(photoPreview || currentPhoto) ? 'Ganti Foto' : 'Pilih Foto (opsional)'}
                                <input type="file" accept="image/*" className="hidden" onChange={onPhotoChange} />
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
                        <p className={HINT}>Nama harus unik. Jika sudah terdaftar, tambahkan bin/binti atau nama ayah.</p>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-3">
                            <div>
                                <label className={LBL}>Metode Target <span className="text-red-500">*</span></label>
                                <select
                                    value={santriForm.data.target_metode}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        santriForm.setData('target_metode', v);
                                        if (v === 'quran') santriForm.setData('target_metode_2', '');
                                    }}
                                    required className={INP}>
                                    {targetMetodeOptions.map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                                {err(santriForm.errors.target_metode)}
                            </div>

                            {santriForm.data.target_metode === 'quran' && (
                                <div>
                                    <label className={LBL}>Target Juz</label>
                                    <input type="number" step="0.5" min="0" max="30"
                                        value={santriForm.data.target_juz} onChange={(e) => santriForm.setData('target_juz', e.target.value)} className={INP} />
                                    {err(santriForm.errors.target_juz)}
                                </div>
                            )}

                            {(santriForm.data.target_metode === 'iqro' || santriForm.data.target_metode === 'aisar') && (
                                <>
                                    <div>
                                        <label className={LBL}>Target Jilid</label>
                                        <select value={santriForm.data.target_jilid} onChange={(e) => santriForm.setData('target_jilid', e.target.value)} className={INP}>
                                            {[1, 2, 3, 4, 5, 6].map((i) => (
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
                                            className={CHECKBOX} />
                                        <span className="text-xs font-medium text-stone-600 dark:text-stone-400">
                                            Tambah target Al-Qur'an (Juz)
                                        </span>
                                    </label>

                                    {santriForm.data.target_metode_2 === 'quran' && (
                                        <div>
                                            <label className={LBL}>Target Juz (Al-Qur'an)</label>
                                            <input type="number" step="0.5" min="0" max="30"
                                                value={santriForm.data.target_juz} onChange={(e) => santriForm.setData('target_juz', e.target.value)} className={INP} />
                                            {err(santriForm.errors.target_juz)}
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                        <div>
                            <label className={LBL}>Kelas</label>
                            <input
                                type="text"
                                value={kelasList.find((k) => String(k.id) === String(santriForm.data.kelas_id))?.name ?? '-'}
                                readOnly
                                className={`${INP} !bg-cream-100 dark:!bg-coal-600 text-stone-500 dark:text-stone-400 cursor-not-allowed`}
                            />
                            <p className={HINT}>Kelas otomatis mengikuti kelas yang Anda ampu.</p>
                            {err(santriForm.errors.kelas_id)}
                        </div>
                    </div>

                    <div className={`${SUB} p-3.5`}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className={TILE}><KeyIcon className="w-3.5 h-3.5" /></span>
                            <h4 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Akun Login Santri</h4>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className={LBL}>
                                    {isEdit ? 'Password Baru' : 'Password'} {isEdit ? <span className="text-stone-400 text-[11px] font-normal">(kosongkan jika tidak diubah)</span> : <span className="text-red-500">*</span>}
                                </label>
                                <PasswordInput value={santriForm.data.password} onChange={(e) => santriForm.setData('password', e.target.value)} placeholder="Minimal 8 karakter" required={!isEdit} />
                                {err(santriForm.errors.password)}
                            </div>
                            <div>
                                <label className={LBL}>Konfirmasi Password {!isEdit && <span className="text-red-500">*</span>}</label>
                                <PasswordInput value={santriForm.data.password_confirmation} onChange={(e) => santriForm.setData('password_confirmation', e.target.value)} placeholder="Ulangi password" required={!isEdit} />
                                {isEdit && <p className={HINT}>Isi hanya jika mengubah password</p>}
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={() => setFormOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={santriForm.processing} className={BTN_PRIMARY}>
                            {santriForm.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Santri'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: TEMPLATE ===== */}
            <Modal open={templateOpen} onClose={() => setTemplateOpen(false)} icon={<DownloadIcon className="w-3.5 h-3.5" />} title="Download Template Import" maxWidth="sm:max-w-sm">
                <div className="p-5 space-y-4">
                    <InfoBox>
                        Template Excel berisi format kolom yang benar untuk import data santri. Download template ini sebelum melakukan import.
                    </InfoBox>
                    <div className="flex gap-3">
                        <button onClick={() => setTemplateOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <a href={route('guru.santris.download-template')} onClick={() => setTimeout(() => setTemplateOpen(false), 500)} className={BTN_PRIMARY}>
                            Download Template
                        </a>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL: EXPORT ===== */}
            <Modal open={exportOpen} onClose={() => setExportOpen(false)} icon={<ExportIcon className="w-3.5 h-3.5" />} title="Export Data Santri untuk Edit" maxWidth="sm:max-w-sm">
                <div className="p-5 space-y-4">
                    <InfoBox>
                        File Excel akan berisi seluruh data santri Anda. Edit data lalu upload kembali lewat tombol Update untuk update massal.
                    </InfoBox>
                    <div className="flex gap-3">
                        <button onClick={() => setExportOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <a href={route('guru.santris.export-for-edit')} onClick={() => setTimeout(() => setExportOpen(false), 500)} className={BTN_PRIMARY}>
                            Download Excel
                        </a>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL: IMPORT ===== */}
            <Modal open={importOpen} onClose={() => setImportOpen(false)} icon={<ImportIcon className="w-3.5 h-3.5" />} title="Import Data Santri dari Excel">
                <form onSubmit={submitImport} className="p-5 space-y-4">
                    <div>
                        <label className={LBL}>Pilih File Excel <span className="text-red-500">*</span></label>
                        <input type="file" accept=".xlsx,.xls" required onChange={(e) => importForm.setData('file', e.target.files[0])} className={FILE_INP} />
                        {err(importForm.errors.file)}
                        <p className={HINT}>Format: .xlsx / .xls · Maks 2MB</p>
                    </div>
                    <div>
                        <label className={LBL}>ZIP Foto (opsional)</label>
                        <input type="file" accept=".zip" onChange={(e) => importForm.setData('photos_zip', e.target.files[0])} className={FILE_INP} />
                        <p className={HINT}>Nama tiap foto = kolom <b>nama_file_foto</b> di Excel. Maks 20MB.</p>
                    </div>
                    <InfoBox icon={<ListIcon className="w-3.5 h-3.5" />} title="Langkah-langkah import">
                        <ol className="space-y-0.5 list-decimal list-inside">
                            <li>Download template, isi data + kolom nama_file_foto</li>
                            <li>Upload Excel di kolom pertama</li>
                            <li>Kumpulkan foto jadi 1 ZIP, upload di kolom ZIP Foto</li>
                            <li>Santri otomatis tercatat sebagai santri Anda</li>
                        </ol>
                    </InfoBox>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setImportOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={importForm.processing} className={BTN_PRIMARY}>
                            {importForm.processing ? 'Mengimport...' : 'Import Data'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* ===== MODAL: BULK UPDATE ===== */}
            <Modal open={bulkOpen} onClose={() => setBulkOpen(false)} icon={<RefreshIcon className="w-3.5 h-3.5" />} title="Bulk Update Data Santri">
                <form onSubmit={submitBulk} className="p-5 space-y-4">
                    <InfoBox icon={<ListIcon className="w-3.5 h-3.5" />} title="Langkah-langkah">
                        <ol className="space-y-0.5 list-decimal list-inside">
                            <li>Klik tombol "Export" untuk download data santri</li>
                            <li>Edit data di Excel (JANGAN ubah kolom NO URUT)</li>
                            <li>Upload Excel yang sudah diedit di sini</li>
                        </ol>
                    </InfoBox>
                    <div>
                        <label className={LBL}>File Excel yang Sudah Diedit <span className="text-red-500">*</span></label>
                        <input type="file" accept=".xlsx,.xls" required onChange={(e) => bulkForm.setData('file', e.target.files[0])} className={FILE_INP} />
                        {err(bulkForm.errors.file)}
                        <p className={HINT}>Format: .xlsx / .xls · Maks 2MB</p>
                    </div>
                    <div>
                        <label className={LBL}>ZIP Foto (opsional)</label>
                        <input type="file" accept=".zip" onChange={(e) => bulkForm.setData('photos_zip', e.target.files[0])} className={FILE_INP} />
                        <p className={HINT}>Kosongkan kalau tidak ubah foto. Maks 20MB.</p>
                    </div>
                    <InfoBox tone="red" icon={<WarnIcon className="w-3.5 h-3.5" />} title="Peringatan">
                        <ul className="space-y-0.5 list-disc list-inside">
                            <li>NO URUT wajib ada — jangan dihapus/ubah</li>
                            <li>NIS harus unik</li>
                            <li>Kelas tidak bisa diubah via Excel</li>
                            <li>Password tidak bisa diubah via Excel</li>
                        </ul>
                    </InfoBox>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => setBulkOpen(false)} className={BTN_SECONDARY}>Batal</button>
                        <button type="submit" disabled={bulkForm.processing} className={BTN_PRIMARY}>
                            {bulkForm.processing ? 'Mengupdate...' : 'Update Semua Data'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Panah select kustom. color-scheme: dark membuat daftar opsi
                native ikut gelap, jadi tidak perlu warna option manual. */}
            <style>{`
                select{
                    -webkit-appearance:none;-moz-appearance:none;appearance:none;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23a8a29e' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 0.75rem center;
                    background-size:1rem;
                    padding-right:2.25rem;
                }
                html.dark select, html.dark input[type="number"], html.dark input[type="file"]{color-scheme:dark}
                @keyframes slideUp{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:translateY(0)}}
                @media(min-width:640px){@keyframes slideUp{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}}
            `}</style>
        </GuruAuthenticatedLayout>
    );
}
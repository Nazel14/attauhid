import { useState, useMemo, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
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
const SearchIcon = (p) => <I {...p} d={<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />} />;
const ArrowUpIcon = (p) => <I {...p} d={<path d="M5 10l7-7m0 0l7 7m-7-7v18" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const InfoIcon = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const ClockIcon = (p) => <I {...p} d={<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const CheckIcon = (p) => <I {...p} d={<path d="M5 13l4 4L19 7" />} />;
const FileTextIcon = (p) => <I {...p} d={<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const EmptyIcon = (p) => <I {...p} d={<path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertTriangleIcon = (p) => <I {...p} d={<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />} />;
const RapotIcon = (p) => <I {...p} d={<path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const UsersIcon = (p) => <I {...p} d={<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />} />;
const LockIcon = (p) => <I {...p} d={<><rect x="4.5" y="11" width="15" height="10" rx="2" /><path d="M8 11V7a4 4 0 018 0v4" /></>} />;
const UnlockIcon = (p) => <I {...p} d={<><rect x="4.5" y="11" width="15" height="10" rx="2" /><path d="M8 11V7a4 4 0 017.5-2" /></>} />;

/* ============================================================
 * TOKEN DESAIN — sama dengan Dashboard / Input / Kelola Siswa
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const SUB = 'bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600 rounded-xl';
const TILE = 'w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0';
const INP = 'w-full bg-white dark:bg-coal-600/40 border border-cream-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-500 dark:[color-scheme:dark]';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const BTN_SECONDARY = 'flex-1 py-2.5 rounded-xl text-sm font-semibold bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-700 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition';
const BTN_PRIMARY = 'flex-1 py-2.5 rounded-xl text-sm font-semibold text-white bg-claude-500 hover:bg-claude-600 transition active:scale-[.98] disabled:opacity-50 disabled:cursor-not-allowed';
const CHECKBOX = 'w-4 h-4 rounded border-cream-400 dark:border-coal-500 dark:bg-coal-600/40 text-claude-500 focus:ring-claude-500 cursor-pointer';
const ROW_BTN = 'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg ring-1 text-xs font-semibold transition active:scale-95';
const ROW_NEUTRAL = 'bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 hover:bg-cream-300 dark:hover:bg-coal-500 text-stone-600 dark:text-stone-300';
const ROW_OK = 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400';
const ROW_ACC = 'bg-claude-100 dark:bg-claude-500/15 ring-claude-200 dark:ring-claude-500/20 hover:bg-claude-200 dark:hover:bg-claude-500/25 text-claude-700 dark:text-claude-400';

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
const BANNER = {
    ok:      { box: 'bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20 rounded-xl', icon: 'text-emerald-500 dark:text-emerald-400', text: 'text-emerald-800 dark:text-emerald-300' },
    warn:    { box: 'bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 rounded-xl', icon: 'text-amber-500 dark:text-amber-400', text: 'text-amber-800 dark:text-amber-300' },
    acc:     { box: 'bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-200 dark:ring-claude-500/20 rounded-xl', icon: 'text-claude-600 dark:text-claude-400', text: 'text-claude-800 dark:text-claude-300' },
    neutral: { box: SUB, icon: 'text-stone-400', text: 'text-stone-600 dark:text-stone-400' },
};

const metodeTone = (s) => {
    const m = String(s.target_metode || '').toLowerCase();
    if (m === 'iqro' || m === 'aisar') return m;
    if (s.is_iqro) return /aisar/i.test(s.target_display || '') ? 'aisar' : 'iqro';
    return 'quran';
};

const storageUrl = (path) => (path ? `/storage/${path}` : null);

/* ============================================================
 * SUB-COMPONENTS
 * ============================================================ */
function Toast({ notif, onClose }) {
    if (!notif) return null;
    const styles = {
        success: { tile: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400', Icon: CheckCircle },
        warning: { tile: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400', Icon: AlertTriangleIcon },
        error:   { tile: 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400', Icon: AlertCircle },
    };
    const s = styles[notif.type] || styles.success;
    const Icon = s.Icon;
    return (
        <>
            <div key={notif.id} className="fixed top-3 left-3 right-3 sm:left-auto sm:right-3 sm:w-96 z-[300]" style={{ animation: 'slideIn .25s ease-out' }}>
                <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl shadow-xl px-3.5 py-3 flex items-start gap-3">
                    <span className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${s.tile}`}><Icon className="w-4 h-4" /></span>
                    <p className="flex-1 min-w-0 pt-1.5 text-sm text-stone-800 dark:text-stone-100" dangerouslySetInnerHTML={{ __html: notif.message }} />
                    <button onClick={onClose} className="flex-shrink-0 mt-1 w-6 h-6 rounded-lg flex items-center justify-center text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-cream-200 dark:hover:bg-coal-600 transition">
                        <XIcon className="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
            <style>{`@keyframes slideIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }`}</style>
        </>
    );
}

function Modal({ open, onClose, children, maxWidth = 'sm:max-w-md' }) {
    useEffect(() => {
        document.body.classList.toggle('overflow-hidden', open);
        return () => document.body.classList.remove('overflow-hidden');
    }, [open]);
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />
            <div className={`relative bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl shadow-2xl w-full p-5 ${maxWidth}`}
                style={{ animation: 'popIn .2s ease-out' }}>
                {children}
            </div>
            <style>{`@keyframes popIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:scale(1); } }`}</style>
        </div>
    );
}

function Banner({ tone, icon: Icon, children }) {
    const t = BANNER[tone];
    return (
        <div className={`${t.box} px-3.5 py-3 mb-4 flex items-start gap-2.5`}>
            <Icon className={`w-4 h-4 flex-shrink-0 mt-0.5 ${t.icon}`} />
            <p className={`text-xs leading-relaxed ${t.text}`}>{children}</p>
        </div>
    );
}

function Badge({ className, children }) {
    return <span className={`inline-flex items-center px-2 py-0.5 rounded-md ring-1 text-[10px] font-semibold whitespace-nowrap ${className}`}>{children}</span>;
}

function Avatar({ santri, size = 'w-9 h-9 text-xs' }) {
    const photo = santri.user?.profile_photo ? storageUrl(santri.user.profile_photo) : null;
    const g = (santri.gender === 'P' || santri.gender === 'Perempuan') ? 'P' : 'L';
    if (photo) return <img src={photo} alt="" className={`${size} rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0`} />;
    return (
        <span className={`${size} rounded-full ring-1 flex items-center justify-center font-bold flex-shrink-0 ${GENDER[g].avatar}`}>
            {santri.name?.charAt(0).toUpperCase()}
        </span>
    );
}

// Bar progress — sama dengan ProgressBar di Dashboard (aksen untuk utama, emerald untuk Al-Qur'an)
function Bar({ value, color = 'bg-claude-500', label, sub }) {
    const v = Math.min(Number(value) || 0, 100);
    return (
        <div className="min-w-[160px]">
            <div className="flex items-center gap-2">
                <span className="flex-1 h-1.5 rounded-full bg-cream-300 dark:bg-coal-600 overflow-hidden">
                    <span className={`block h-full rounded-full ${color} transition-all duration-500`} style={{ width: `${v}%` }} />
                </span>
                <span className="text-[11px] font-semibold tabular-nums text-stone-500 dark:text-stone-400 w-9 text-right shrink-0">{label ?? `${value}%`}</span>
            </div>
            {sub && <p className="text-[11px] text-stone-400 mt-1">{sub}</p>}
        </div>
    );
}

function EmptyBlock({ icon: Icon, title, hint }) {
    return (
        <div className="py-14 flex flex-col items-center text-center">
            <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                <Icon className="w-5 h-5" />
            </span>
            <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">{title}</p>
            {hint && <p className="text-[11px] text-stone-400 mt-0.5">{hint}</p>}
        </div>
    );
}

/* ============================================================
 * MAIN PAGE — Guru / Rapot / Index
 * ============================================================ */
export default function Index({
    kelas, santriList = [], guru, tahunAjaranAktif,
    bolehNaik = false, tglMulaiNaikFormatted = null,
    kelasTujuanList = [], sudahNaikIds = [], alasanTutup = null,
    bolehAturRapot = false, rapotJendelaText = null, rapotJendelaInfo = null,
}) {
    const { flash = {} } = usePage().props;

    /* ---- Kunci PDF rapot per santri ---- */
    const [rapotBusyId, setRapotBusyId] = useState(null);
    const toggleRapot = (santri) => {
        const aksi = santri.rapot_dibuka ? 'mengunci kembali' : 'membuka';
        if (!confirm(`Yakin ${aksi} akses PDF rapot untuk ${santri.name}?`)) return;
        setRapotBusyId(santri.id);
        router.post(route('guru.rapot.toggle-akses', santri.id), {}, {
            preserveScroll: true,
            onFinish: () => setRapotBusyId(null),
        });
    };

    /* ---- Toast dari flash ---- */
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success || flash?.warning || flash?.error) {
            const type = flash.success ? 'success' : flash.warning ? 'warning' : 'error';
            setNotif({ message: flash.success || flash.warning || flash.error, type, id: Date.now() });
            const t = setTimeout(() => setNotif(null), 5000);
            return () => clearTimeout(t);
        }
    }, [flash]);

    /* ---- Search (client-side, tak mengubah nomor urut) ---- */
    const [search, setSearch] = useState('');
    const query = search.toLowerCase().trim();
    const isVisible = (s) => !query || s.name.toLowerCase().includes(query) || (s.nis || '').toLowerCase().includes(query);
    const visibleCount = useMemo(() => santriList.filter(isVisible).length, [santriList, query]);

    const sudahNaikSet = new Set((sudahNaikIds || []).map(String));
    const isDpNaik = (s) => bolehNaik && !sudahNaikSet.has(String(s.id));

    /* ---- Seleksi bersama: dipakai naik kelas DAN kunci PDF ----
     * Kunci PDF berlaku untuk semua santri, sedangkan naik kelas hanya untuk
     * yang belum naik. Jadi kandidat centang = gabungan keduanya, lalu saat
     * aksi naik kelas dijalankan, pilihan disaring lagi. */
    const [selected, setSelected] = useState([]);
    const bisaPilih = bolehNaik || bolehAturRapot;

    const selectableIds = useMemo(
        () => santriList
            .filter((s) => isVisible(s) && (bolehAturRapot || isDpNaik(s)))
            .map((s) => String(s.id)),
        [santriList, query, bolehNaik, bolehAturRapot, sudahNaikIds]
    );
    const allSelected = selectableIds.length > 0 && selectableIds.every((id) => selected.includes(id));
    const toggleAll = () => setSelected(allSelected ? [] : selectableIds);
    const toggleOne = (id) => setSelected((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));

    // Hanya yang benar-benar memenuhi syarat naik kelas
    const selectedNaikIds = useMemo(
        () => selected.filter((id) => {
            const s = santriList.find((x) => String(x.id) === id);
            return s && isDpNaik(s);
        }),
        [selected, santriList, bolehNaik, sudahNaikIds]
    );

    /* ---- Bulk kunci PDF ---- */
    const [rapotBulkBusy, setRapotBulkBusy] = useState(false);
    const bulkRapot = (aksi) => {
        if (!selected.length) return;
        const label = aksi === 'buka' ? 'membuka' : 'mengunci kembali';
        if (!confirm(`Yakin ${label} akses PDF rapot untuk ${selected.length} santri terpilih?`)) return;
        setRapotBulkBusy(true);
        router.post(route('guru.rapot.bulk-akses'), { santri_ids: selected, aksi }, {
            preserveScroll: true,
            onSuccess: () => setSelected([]),
            onFinish: () => setRapotBulkBusy(false),
        });
    };

    /* ---- Modal Naik Kelas ---- */
    const [showNaik, setShowNaik] = useState(false);
    const [naikLabel, setNaikLabel] = useState('');
    const [naikIds, setNaikIds] = useState([]);      // terpisah dari `selected`
    const [kelasTujuan, setKelasTujuan] = useState('');
    const [naikProcessing, setNaikProcessing] = useState(false);

    const openNaikSingle = (id, name) => {
        setNaikIds([String(id)]);
        setNaikLabel(name);
        setKelasTujuan('');
        setShowNaik(true);
    };
    const openNaikBulk = () => {
        if (!selectedNaikIds.length) return;
        setNaikIds(selectedNaikIds);
        setNaikLabel(`${selectedNaikIds.length} santri terpilih`);
        setKelasTujuan('');
        setShowNaik(true);
    };
    const submitNaik = (e) => {
        e.preventDefault();
        if (!kelasTujuan || !naikIds.length) return;
        if (!confirm(`Naikkan ${naikLabel} ke kelas yang dipilih?`)) return;
        setNaikProcessing(true);
        router.post(route('guru.naik-kelas'), { santri_ids: naikIds, kelas_tujuan_id: kelasTujuan }, {
            preserveScroll: true,
            onSuccess: () => { setShowNaik(false); setSelected([]); setNaikIds([]); },
            onFinish: () => setNaikProcessing(false),
        });
    };

    // Data turunan per santri (dipakai kartu mobile & tabel desktop)
    const rowInfo = (santri) => {
        const persen = santri.hafalan_persen ?? 0;
        const totalJuz = santri.hafalan_total_juz ?? 0;
        const targetJuz = (santri.target_juz ?? 0) > 0 ? santri.target_juz : 30;
        const isIqro = santri.is_iqro;
        return {
            persen, isIqro,
            iqroKnown: santri.iqro_persen_diketahui,
            sudahNaik: sudahNaikSet.has(String(santri.id)),
            dpNaik: isDpNaik(santri),
            g: (santri.gender === 'P' || santri.gender === 'Perempuan') ? 'P' : 'L',
            visible: isVisible(santri),
            capaian: isIqro ? `${santri.total_halaman_iqro ?? 0}/${santri.total_halaman_jilid ?? 0} hal` : `${totalJuz}/${targetJuz} Juz`,
        };
    };

    // Fungsi render biasa (BUKAN komponen) — komponen yang didefinisikan di
    // badan render akan di-unmount/mount ulang setiap state berubah.
    const progressCell = (santri, info) => (
        <div className="space-y-2">
            {info.isIqro && !info.iqroKnown ? (
                <span className="text-[11px] text-stone-400 italic">Halaman Jilid {santri.target_jilid ?? '-'} belum diatur</span>
            ) : (
                <Bar value={info.persen} sub={info.capaian} />
            )}
            {santri.has_quran_secondary && (
                <Bar value={santri.hafalan_persen_2 ?? 0} color="bg-emerald-500"
                    sub={`${santri.capaian_juz_2 ?? 0}/${santri.target_juz_2 ?? 0} Juz (Al-Qur'an)`} />
            )}
        </div>
    );

    const rapotLockBtn = (santri) => (
        <button type="button"
            onClick={() => toggleRapot(santri)}
            disabled={!bolehAturRapot || rapotBusyId === santri.id}
            title={santri.rapot_dibuka ? 'Kunci kembali PDF rapot santri' : 'Buka akses PDF rapot santri'}
            className={`${ROW_BTN} ${santri.rapot_dibuka ? ROW_OK : ROW_NEUTRAL} disabled:opacity-40 disabled:cursor-not-allowed`}>
            {santri.rapot_dibuka
                ? <><UnlockIcon className="w-3.5 h-3.5" /> Terbuka</>
                : <><LockIcon className="w-3.5 h-3.5" /> Terkunci</>}
        </button>
    );

    return (
        <GuruAuthenticatedLayout>
            <Head title="Rapot Santri" />
            <Toast notif={notif} onClose={() => setNotif(null)} />

            {/* Padding dibuang — <main> di layout sudah memberinya (sama seperti Dashboard). */}
            <div className="max-w-7xl mx-auto">

                {/* Kembali — mobile saja */}
                <Link href={route('guru.dashboard')} className={`${BTN_SOFT} mb-4 sm:hidden`}>
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Daftar Kelas
                </Link>

                {/* ===== HEADER (sama dengan Dashboard) ===== */}
                <div className="flex flex-wrap items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0 text-stone-600 dark:text-stone-300">
                        <RapotIcon className="w-6 h-6" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Rapot Santri</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            Santri yang Anda ampu <span className="font-semibold text-claude-600 dark:text-claude-400">{santriList.length} santri</span>
                        </p>
                    </div>
                    {kelas && santriList.length > 0 && (
                        <div className="relative w-full sm:w-64">
                            <SearchIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-400 pointer-events-none" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama / NIS..."
                                className={`${INP} !py-2 !text-xs pl-9 pr-8`}
                            />
                            {search && (
                                <button type="button" onClick={() => setSearch('')}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                                    <XIcon className="w-3.5 h-3.5" />
                                </button>
                            )}
                        </div>
                    )}
                </div>

                {kelas ? (
                    santriList.length > 0 ? (
                        <>
                            {/* ===== BANNER STATUS KENAIKAN ===== */}
                            {bolehNaik ? (
                                <Banner tone="ok" icon={ArrowUpIcon}>
                                    Periode kenaikan kelas <strong>aktif</strong> (semester genap). Centang santri lalu gunakan <strong>Naikkan Terpilih</strong>, atau tombol <strong>Naik</strong> per santri. Santri yang sudah naik tahun ini tidak muncul tombolnya.
                                </Banner>
                            ) : alasanTutup ? (
                                <Banner tone="neutral" icon={InfoIcon}>{alasanTutup}</Banner>
                            ) : tglMulaiNaikFormatted ? (
                                <Banner tone="warn" icon={ClockIcon}>
                                    Fitur <strong>Naik Kelas</strong> akan aktif mulai <strong>{tglMulaiNaikFormatted}</strong> (1 bulan sebelum semester berakhir).
                                </Banner>
                            ) : null}

                            {/* ===== BANNER KUNCI PDF RAPOT ===== */}
                            <Banner tone={bolehAturRapot ? 'acc' : 'neutral'} icon={bolehAturRapot ? UnlockIcon : LockIcon}>
                                {bolehAturRapot ? (
                                    <>Akses <strong>PDF Rapot</strong> santri sedang bisa diatur. Klik tombol gembok pada santri untuk membuka aksesnya.{rapotJendelaText && <> Periode: <strong>{rapotJendelaText}</strong>.</>}</>
                                ) : (
                                    <>{rapotJendelaInfo ?? 'Akses PDF rapot belum bisa diatur.'} PDF santri tetap terkunci di luar periode ini.{rapotJendelaText && <> Periode: <strong>{rapotJendelaText}</strong>.</>}</>
                                )}
                            </Banner>

                            {/* ===== BULK ACTION BAR ===== */}
                            {selected.length > 0 && (
                                <div className="bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-200 dark:ring-claude-500/20 rounded-2xl px-4 py-3 mb-4 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-claude-700 dark:text-claude-400">{selected.length} santri dipilih</p>
                                        <button onClick={() => setSelected([])} className="text-[11px] text-stone-500 hover:text-claude-600 dark:text-stone-400 dark:hover:text-claude-400">Bersihkan pilihan</button>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {bolehAturRapot && (
                                            <>
                                                <button onClick={() => bulkRapot('buka')} disabled={rapotBulkBusy}
                                                    className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-claude-500 hover:bg-claude-600 disabled:opacity-50 text-white text-xs font-semibold rounded-xl transition active:scale-95">
                                                    <UnlockIcon className="w-3.5 h-3.5" /> Buka PDF Terpilih
                                                </button>
                                                <button onClick={() => bulkRapot('kunci')} disabled={rapotBulkBusy}
                                                    className={`${BTN_SOFT} disabled:opacity-50`}>
                                                    <LockIcon className="w-3.5 h-3.5" /> Kunci PDF Terpilih
                                                </button>
                                            </>
                                        )}
                                        {bolehNaik && selectedNaikIds.length > 0 && (
                                            <button onClick={openNaikBulk}
                                                className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl transition active:scale-95">
                                                <ArrowUpIcon className="w-3.5 h-3.5" /> Naikkan Terpilih ({selectedNaikIds.length})
                                            </button>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* ===== MOBILE CARDS ===== */}
                            <div className="lg:hidden space-y-2">
                                {bisaPilih && (
                                    <div className={`${CARD} flex items-center gap-3 px-4 py-2.5`}>
                                        <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                            ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                            className={CHECKBOX} />
                                        <span className="text-xs font-semibold text-stone-600 dark:text-stone-300 flex-1">
                                            Pilih Semua{bolehAturRapot ? '' : ' (yang belum naik)'}
                                        </span>
                                        <span className="text-[11px] text-stone-400">{selectableIds.length} santri</span>
                                    </div>
                                )}

                                {santriList.map((santri, index) => {
                                    const info = rowInfo(santri);
                                    const isSel = selected.includes(String(santri.id));
                                    return (
                                        <div key={santri.id} className={`rounded-2xl ring-1 overflow-hidden transition-all ${isSel ? 'ring-2 ring-claude-500 bg-claude-50 dark:bg-claude-500/10' : 'ring-cream-300 dark:ring-coal-600 bg-white dark:bg-coal-700'} ${info.visible ? '' : 'hidden'}`}>
                                            <div className="flex items-center gap-3 px-4 py-3">
                                                {(bolehAturRapot || info.dpNaik) ? (
                                                    <input type="checkbox" checked={isSel} onChange={() => toggleOne(String(santri.id))} className={`${CHECKBOX} flex-shrink-0`} />
                                                ) : bolehNaik ? (
                                                    <span className="w-4 h-4 flex-shrink-0 flex items-center justify-center text-emerald-500" title="Sudah naik">
                                                        <CheckIcon className="w-4 h-4" />
                                                    </span>
                                                ) : null}
                                                <Avatar santri={santri} size="w-10 h-10 text-sm" />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-semibold text-stone-800 dark:text-stone-100 truncate">{santri.name}</p>
                                                    <p className="text-[11px] text-stone-400 font-mono">{santri.nis ?? '-'}</p>
                                                </div>
                                                {info.sudahNaik && bolehNaik ? (
                                                    <Badge className="bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-700 dark:text-emerald-400 gap-0.5">
                                                        <CheckIcon className="w-3 h-3" /> Naik
                                                    </Badge>
                                                ) : (
                                                    <span className="text-[11px] text-stone-400 tabular-nums flex-shrink-0">#{index + 1}</span>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600/60 divide-x divide-cream-200 dark:divide-coal-600/60">
                                                <div className="px-3 py-2.5 text-center">
                                                    <p className="text-[10px] text-stone-400 mb-1">Gender</p>
                                                    <Badge className={GENDER[info.g].badge}>{info.g}</Badge>
                                                </div>
                                                <div className="px-3 py-2.5 text-center">
                                                    <p className="text-[10px] text-stone-400 mb-1">Target</p>
                                                    <Badge className={METODE_BADGE[metodeTone(santri)]}>{santri.target_display ?? '-'}</Badge>
                                                </div>
                                            </div>

                                            <div className="px-4 py-3 border-t border-cream-200 dark:border-coal-600/60">
                                                {progressCell(santri, info)}
                                            </div>

                                            <div className="border-t border-cream-200 dark:border-coal-600/60 flex divide-x divide-cream-200 dark:divide-coal-600/60">
                                                <Link href={route('guru.rapot.show', santri.id)}
                                                    className="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-claude-700 dark:text-claude-400 hover:bg-claude-50 dark:hover:bg-claude-500/10 transition">
                                                    <FileTextIcon className="w-3.5 h-3.5" /> Lihat Rapot
                                                </Link>
                                                <button type="button"
                                                    onClick={() => toggleRapot(santri)}
                                                    disabled={!bolehAturRapot || rapotBusyId === santri.id}
                                                    title={santri.rapot_dibuka ? 'Kunci kembali PDF rapot santri' : 'Buka akses PDF rapot santri'}
                                                    className={`flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed ${santri.rapot_dibuka ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10' : 'text-stone-500 dark:text-stone-400 hover:bg-cream-100 dark:hover:bg-coal-600/40'}`}>
                                                    {santri.rapot_dibuka
                                                        ? <><UnlockIcon className="w-3.5 h-3.5" /> PDF Terbuka</>
                                                        : <><LockIcon className="w-3.5 h-3.5" /> PDF Terkunci</>}
                                                </button>
                                                {info.dpNaik && (
                                                    <button type="button" onClick={() => openNaikSingle(santri.id, santri.name)}
                                                        className="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition">
                                                        <ArrowUpIcon className="w-3.5 h-3.5" /> Naik
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}

                                {visibleCount === 0 && (
                                    <div className={CARD}><EmptyBlock icon={EmptyIcon} title="Santri tidak ditemukan" /></div>
                                )}
                            </div>

                            {/* ===== DESKTOP TABLE (sama dengan tabel Dashboard) ===== */}
                            <div className={`hidden lg:block ${CARD} p-5`}>
                                <div className="flex items-center gap-2 mb-4">
                                    <span className={TILE}><UsersIcon className="w-3.5 h-3.5" /></span>
                                    <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Daftar Santri</h2>
                                    <span className="ml-auto text-[11px] text-stone-400">{visibleCount} dari {santriList.length} santri</span>
                                </div>
                                <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden">
                                    <table className="min-w-full">
                                        <thead>
                                            <tr className="bg-cream-100 dark:bg-coal-600/40">
                                                {bisaPilih && (
                                                    <th className="px-4 py-2.5 w-10 text-left">
                                                        <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                                            ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                                            className={CHECKBOX} />
                                                    </th>
                                                )}
                                                {['No', 'Nama Santri', 'NIS', 'Gender', 'Target', 'Progress', 'Aksi'].map((h) => (
                                                    <th key={h} className="px-4 py-2.5 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400">{h}</th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                            {santriList.map((santri, index) => {
                                                const info = rowInfo(santri);
                                                const isSel = selected.includes(String(santri.id));
                                                return (
                                                    <tr key={santri.id} className={`transition-colors align-top ${isSel ? 'bg-claude-50 dark:bg-claude-500/10' : 'hover:bg-cream-100 dark:hover:bg-coal-600/30'} ${info.visible ? '' : 'hidden'}`}>
                                                        {bisaPilih && (
                                                            (bolehAturRapot || info.dpNaik) ? (
                                                                <td className="px-4 py-3">
                                                                    <input type="checkbox" checked={isSel} onChange={() => toggleOne(String(santri.id))} className={CHECKBOX} />
                                                                </td>
                                                            ) : (
                                                                <td className="px-4 py-3 text-center">
                                                                    <span className="inline-flex text-emerald-500" title="Sudah naik"><CheckIcon className="w-4 h-4" /></span>
                                                                </td>
                                                            )
                                                        )}
                                                        <td className="px-4 py-3 text-xs text-stone-400 tabular-nums">{index + 1}</td>
                                                        <td className="px-4 py-3">
                                                            <div className="flex items-center gap-2.5">
                                                                <Avatar santri={santri} />
                                                                <span className="text-sm text-stone-700 dark:text-stone-200">{santri.name}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3 text-xs font-mono text-stone-500 dark:text-stone-400">{santri.nis ?? '-'}</td>
                                                        <td className="px-4 py-3"><Badge className={GENDER[info.g].badge}>{info.g}</Badge></td>
                                                        <td className="px-4 py-3"><Badge className={METODE_BADGE[metodeTone(santri)]}>{santri.target_display ?? '-'}</Badge></td>
                                                        <td className="px-4 py-3">{progressCell(santri, info)}</td>
                                                        <td className="px-4 py-3">
                                                            <div className="flex items-center gap-1.5">
                                                                <Link href={route('guru.rapot.show', santri.id)} className={`${ROW_BTN} ${ROW_ACC}`}>
                                                                    <FileTextIcon className="w-3.5 h-3.5" /> Lihat Rapot
                                                                </Link>
                                                                {rapotLockBtn(santri)}
                                                                {info.dpNaik ? (
                                                                    <button type="button" onClick={() => openNaikSingle(santri.id, santri.name)} className={`${ROW_BTN} ${ROW_OK}`}>
                                                                        <ArrowUpIcon className="w-3.5 h-3.5" /> Naik
                                                                    </button>
                                                                ) : bolehNaik ? (
                                                                    <span className={`${ROW_BTN} bg-cream-100 dark:bg-coal-600/40 ring-cream-300 dark:ring-coal-600 text-stone-400`}>
                                                                        <CheckIcon className="w-3.5 h-3.5" /> Sudah naik
                                                                    </span>
                                                                ) : null}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                    {visibleCount === 0 && <EmptyBlock icon={EmptyIcon} title="Santri tidak ditemukan" />}
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className={CARD}>
                            <EmptyBlock icon={UsersIcon} title="Belum ada data santri" hint="Silakan tambahkan santri terlebih dahulu" />
                        </div>
                    )
                ) : (
                    <Banner tone="warn" icon={AlertTriangleIcon}>
                        Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator.
                    </Banner>
                )}
            </div>

            {/* ===== MODAL NAIK KELAS ===== */}
            <Modal open={showNaik} onClose={() => setShowNaik(false)}>
                <div className="flex items-center gap-3 mb-4">
                    <span className="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <ArrowUpIcon className="w-5 h-5" />
                    </span>
                    <div className="min-w-0">
                        <h3 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-100">Naik Kelas</h3>
                        <p className="text-[11px] text-stone-500 truncate">{naikLabel}</p>
                    </div>
                </div>
                {kelasTujuanList.length === 0 ? (
                    <>
                        <Banner tone="warn" icon={AlertTriangleIcon}>Belum ada kelas tujuan lain. Minta admin membuat kelas tujuan dahulu.</Banner>
                        <button type="button" onClick={() => setShowNaik(false)} className={BTN_SECONDARY + ' w-full'}>Tutup</button>
                    </>
                ) : (
                    <form onSubmit={submitNaik}>
                        <label className="block text-xs font-medium text-stone-600 dark:text-stone-400 mb-1.5">Pilih Kelas Tujuan</label>
                        <select value={kelasTujuan} onChange={(e) => setKelasTujuan(e.target.value)} required className={INP + ' mb-1'}>
                            <option value="">— pilih kelas tujuan —</option>
                            {kelasTujuanList.map((k) => (
                                <option key={k.id} value={k.id}>{k.name} · {k.tahun_ajaran} · {k.guru?.name || 'Tanpa Guru'}</option>
                            ))}
                        </select>
                        <p className="text-[11px] text-stone-400 mb-4">Santri langsung pindah ke kelas & wali kelas tujuan, dan tercatat di riwayat.</p>
                        <div className="flex gap-3">
                            <button type="button" onClick={() => setShowNaik(false)} className={BTN_SECONDARY}>Batal</button>
                            <button type="submit" disabled={!kelasTujuan || naikProcessing} className={BTN_PRIMARY}>
                                {naikProcessing ? 'Memproses...' : 'Naikkan'}
                            </button>
                        </div>
                    </form>
                )}
            </Modal>
        </GuruAuthenticatedLayout>
    );
}
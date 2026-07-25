import { useState, useMemo, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

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
const LockIcon = (p) => <I {...p} d={<><rect x="4.5" y="11" width="15" height="10" rx="2" /><path d="M8 11V7a4 4 0 018 0v4" /></>} />;
const UnlockIcon = (p) => <I {...p} d={<><rect x="4.5" y="11" width="15" height="10" rx="2" /><path d="M8 11V7a4 4 0 017.5-2" /></>} />;

/* ============================================================
 * SHARED CLASSES
 * ============================================================ */
const INP = "w-full bg-white dark:bg-coal-900 border border-stone-300 dark:border-coal-600 rounded-xl text-stone-900 dark:text-stone-100 text-sm px-3 py-2.5 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-600";
const BTN_SECONDARY = "flex-1 py-2.5 rounded-xl text-sm font-semibold bg-stone-100 dark:bg-coal-700 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-coal-600 hover:bg-stone-200 dark:hover:bg-coal-600 transition";

const progressColors = (persen) => {
    if (persen >= 80) return { bar: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400', badge: 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400' };
    if (persen >= 50) return { bar: 'bg-blue-500', text: 'text-blue-600 dark:text-blue-400', badge: 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-950 dark:border-blue-800/50 dark:text-blue-400' };
    if (persen >= 25) return { bar: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400', badge: 'bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950 dark:border-amber-800/50 dark:text-amber-400' };
    return { bar: 'bg-red-500', text: 'text-red-600 dark:text-red-400', badge: 'bg-red-50 border-red-200 text-red-700 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400' };
};

/* ============================================================
 * TOAST — support success / warning / error
 * ============================================================ */
function Toast({ notif, onClose, duration = 5000 }) {
    if (!notif) return null;
    const styles = {
        success: { bg: 'bg-emerald-600 border-emerald-500/40', Icon: CheckCircle },
        warning: { bg: 'bg-amber-500 border-amber-400/40', Icon: AlertTriangleIcon },
        error: { bg: 'bg-red-600 border-red-500/40', Icon: AlertCircle },
    };
    const s = styles[notif.type] || styles.success;
    const Icon = s.Icon;
    return (
        <>
            <div key={notif.id} className="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300]" style={{ animation: 'slideIn .25s ease-out' }}>
                <div className={`${s.bg} border text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3`}>
                    <Icon className="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <p className="flex-1 text-sm font-medium" dangerouslySetInnerHTML={{ __html: notif.message }} />
                    <button onClick={onClose} className="opacity-70 hover:opacity-100"><XIcon className="w-4 h-4" /></button>
                </div>
            </div>
            <style>{`@keyframes slideIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }`}</style>
        </>
    );
}

/* ============================================================
 * MODAL wrapper (bottom-sheet mobile / centered desktop)
 * ============================================================ */
function Modal({ open, onClose, children, maxWidth = 'sm:max-w-md' }) {
    useEffect(() => {
        document.body.classList.toggle('overflow-hidden', open);
        return () => document.body.classList.remove('overflow-hidden');
    }, [open]);
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/70 backdrop-blur-sm" onClick={onClose} />
            <div className={`relative bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl shadow-2xl w-full p-5 ${maxWidth}`}
                style={{ animation: 'popIn .2s ease-out' }}>
                {children}
            </div>
            <style>{`@keyframes popIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:scale(1); } }`}</style>
        </div>
    );
}

const storageUrl = (path) => (path ? `/storage/${path}` : null);

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

    return (
        <GuruAuthenticatedLayout>
            <Head title="Rapot Santri" />
            <Toast notif={notif} onClose={() => setNotif(null)} />

            <div className="pb-10">
                <div className="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

                    {/* Kembali — mobile saja */}
                    <Link href={route('guru.dashboard')}
                        className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                        <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Daftar Kelas
                    </Link>

                   {/* ===== TOP BAR ===== */}
<div className="flex items-center gap-3 mb-4">
    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
        <RapotIcon className="w-6 h-6 text-white" />
    </div>
    <div className="flex-1 min-w-0">
        <h1 className="text-base sm:text-xl font-bold text-stone-900 dark:text-stone-100 truncate">Rapot Santri</h1>
                            <p className="text-[11px] text-stone-500 mt-0.5">
                                Santri yang Anda ampu: <span className="text-claude-600 dark:text-claude-400 font-semibold">{santriList.length} Santri</span>
                            </p>
                        </div>
                        {kelas && santriList.length > 0 && (
                            <div className="relative w-40 sm:w-64">
                                <SearchIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-400 pointer-events-none" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nama / NIS..."
                                    className="w-full pl-9 pr-8 py-2 bg-white dark:bg-coal-800 border border-stone-300 dark:border-coal-700 rounded-xl text-xs text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-600 focus:outline-none focus:ring-2 focus:ring-claude-500 focus:border-claude-500 transition"
                                />
                                {search && (
                                    <button type="button" onClick={() => setSearch('')}
                                        className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-600 dark:hover:text-stone-400">
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
                                    <div className="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                                        <ArrowUpIcon className="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                                        <p className="text-xs text-emerald-700 dark:text-emerald-300">Periode kenaikan kelas <strong>aktif</strong> (semester genap). Centang santri lalu gunakan <strong>Naikkan Terpilih</strong>, atau tombol <strong>Naik</strong> per santri. Santri yang sudah naik tahun ini tidak muncul tombolnya.</p>
                                    </div>
                                ) : alasanTutup ? (
                                    <div className="bg-stone-50 dark:bg-coal-800/40 border border-stone-200 dark:border-coal-700/40 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                                        <InfoIcon className="w-4 h-4 text-stone-400 flex-shrink-0 mt-0.5" />
                                        <p className="text-xs text-stone-600 dark:text-stone-400">{alasanTutup}</p>
                                    </div>
                                ) : tglMulaiNaikFormatted ? (
                                    <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                                        <ClockIcon className="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                                        <p className="text-xs text-amber-700 dark:text-amber-300">Fitur <strong>Naik Kelas</strong> akan aktif mulai <strong>{tglMulaiNaikFormatted}</strong> (1 bulan sebelum semester berakhir).</p>
                                    </div>
                                ) : null}

                                {/* ===== BANNER KUNCI PDF RAPOT ===== */}
                                <div className={`rounded-xl p-3 mb-4 flex items-start gap-2.5 border ${bolehAturRapot ? 'bg-claude-50 dark:bg-claude-500/10 border-claude-200 dark:border-claude-500/25' : 'bg-stone-50 dark:bg-coal-800/40 border-stone-200 dark:border-coal-700/40'}`}>
                                    {bolehAturRapot
                                        ? <UnlockIcon className="w-4 h-4 text-claude-500 flex-shrink-0 mt-0.5" />
                                        : <LockIcon className="w-4 h-4 text-stone-400 flex-shrink-0 mt-0.5" />}
                                    <p className={`text-xs ${bolehAturRapot ? 'text-claude-700 dark:text-claude-300' : 'text-stone-600 dark:text-stone-400'}`}>
                                        {bolehAturRapot ? (
                                            <>Akses <strong>PDF Rapot</strong> santri sedang bisa diatur. Klik ikon gembok pada santri untuk membuka aksesnya.{rapotJendelaText && <> Periode: <strong>{rapotJendelaText}</strong>.</>}</>
                                        ) : (
                                            <>{rapotJendelaInfo ?? 'Akses PDF rapot belum bisa diatur.'} PDF santri tetap terkunci di luar periode ini.{rapotJendelaText && <> Periode: <strong>{rapotJendelaText}</strong>.</>}</>
                                        )}
                                    </p>
                                </div>

                                {/* ===== BULK ACTION BAR ===== */}
                                {selected.length > 0 && (
                                    <div className="bg-claude-50 dark:bg-claude-500/10 border border-claude-200 dark:border-claude-500/25 rounded-xl px-4 py-3 mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-claude-700 dark:text-claude-300">{selected.length} santri dipilih</p>
                                            <button onClick={() => setSelected([])} className="text-[11px] text-claude-600 hover:text-claude-500">Bersihkan pilihan</button>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            {bolehAturRapot && (
                                                <>
                                                    <button onClick={() => bulkRapot('buka')} disabled={rapotBulkBusy}
                                                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-claude-600 hover:bg-claude-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition">
                                                        <UnlockIcon className="w-3.5 h-3.5" /> Buka PDF Terpilih
                                                    </button>
                                                    <button onClick={() => bulkRapot('kunci')} disabled={rapotBulkBusy}
                                                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-stone-200 hover:bg-stone-300 border border-stone-300 text-stone-700 disabled:opacity-50 dark:bg-coal-700 dark:hover:bg-coal-600 dark:border-coal-600 dark:text-stone-200 text-xs font-bold rounded-lg transition">
                                                        <LockIcon className="w-3.5 h-3.5" /> Kunci PDF Terpilih
                                                    </button>
                                                </>
                                            )}
                                            {bolehNaik && selectedNaikIds.length > 0 && (
                                                <button onClick={openNaikBulk}
                                                    className="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition">
                                                    <ArrowUpIcon className="w-3.5 h-3.5" /> Naikkan Terpilih ({selectedNaikIds.length})
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* ===== MOBILE CARDS ===== */}
                                <div className="lg:hidden space-y-2">
                                    {bisaPilih && (
                                        <div className="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl">
                                            <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                                ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                                className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 cursor-pointer" />
                                            <span className="text-xs font-semibold text-stone-600 dark:text-stone-400 flex-1">
                                                Pilih Semua{bolehAturRapot ? '' : ' (yang belum naik)'}
                                            </span>
                                            <span className="text-[11px] text-stone-500">{selectableIds.length} santri</span>
                                        </div>
                                    )}

                                    {santriList.map((santri, index) => {
                                        const persen = santri.hafalan_persen ?? 0;
                                        const totalJuz = santri.hafalan_total_juz ?? 0;
                                        const targetJuz = (santri.target_juz ?? 0) > 0 ? santri.target_juz : 30;
                                        const colors = progressColors(persen);
                                        const sudahNaik = sudahNaikSet.has(String(santri.id));
                                        const dpNaik = isDpNaik(santri);
                                        const isPerempuan = santri.gender === 'P' || santri.gender === 'Perempuan';
                                        const photo = santri.user?.profile_photo ? storageUrl(santri.user.profile_photo) : null;
                                        const visible = isVisible(santri);
                                        const isIqro = santri.is_iqro;
                                        const iqroKnown = santri.iqro_persen_diketahui;

                                        return (
                                            <div key={santri.id} className={`rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 overflow-hidden transition-all shadow-sm dark:shadow-none ${visible ? '' : 'hidden'}`}>
                                                <div className="flex items-center gap-3 px-4 py-3">
                                                    {(bolehAturRapot || dpNaik) ? (
                                                        <input type="checkbox" checked={selected.includes(String(santri.id))} onChange={() => toggleOne(String(santri.id))}
                                                            className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 flex-shrink-0 cursor-pointer" />
                                                    ) : bolehNaik ? (
                                                        <span className="w-4 h-4 flex-shrink-0 flex items-center justify-center text-emerald-500" title="Sudah naik">
                                                            <CheckIcon className="w-4 h-4" />
                                                        </span>
                                                    ) : null}
                                                    {photo ? (
                                                        <img src={photo} alt="" className="w-10 h-10 rounded-full object-cover object-top border border-stone-200 dark:border-coal-700 flex-shrink-0" />
                                                    ) : (
                                                        <span className={`w-10 h-10 rounded-full border flex items-center justify-center text-sm font-bold flex-shrink-0 ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                                            {santri.name?.charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-bold text-stone-900 dark:text-stone-100 truncate">{santri.name}</p>
                                                        <p className="text-[11px] text-stone-500 font-mono">{santri.nis ?? '-'}</p>
                                                    </div>
                                                    {sudahNaik && bolehNaik ? (
                                                        <span className="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30 flex-shrink-0">✓ Naik</span>
                                                    ) : (
                                                        <span className="text-[11px] text-stone-400 dark:text-stone-600 flex-shrink-0">#{index + 1}</span>
                                                    )}
                                                </div>

                                                <div className="grid grid-cols-3 border-t border-stone-200 dark:border-coal-700/50">
                                                    <div className="px-3 py-2.5 text-center border-r border-stone-200 dark:border-coal-700/50">
                                                        <p className="text-[10px] text-stone-500 dark:text-stone-600 mb-1">Gender</p>
                                                        <p className={`text-xs font-bold ${isPerempuan ? 'text-pink-500 dark:text-pink-400' : 'text-blue-500 dark:text-blue-400'}`}>{isPerempuan ? 'P' : 'L'}</p>
                                                    </div>
                                                    <div className="px-3 py-2.5 text-center border-r border-stone-200 dark:border-coal-700/50">
                                                        <p className="text-[10px] text-stone-500 dark:text-stone-600 mb-1">Target</p>
                                                        <p className="text-xs font-bold text-emerald-600 dark:text-emerald-400">{santri.target_display ?? '-'}</p>
                                                    </div>
                                                    <div className="px-3 py-2.5 text-center">
                                                        <p className="text-[10px] text-stone-500 dark:text-stone-600 mb-1">Progress</p>
                                                        <span className={`text-xs font-bold px-2 py-0.5 rounded-lg border ${colors.badge}`}>{persen}%</span>
                                                    </div>
                                                </div>

                                                <div className="px-4 py-2.5 border-t border-stone-200 dark:border-coal-700/50 space-y-1.5">
                                                    {isIqro && !iqroKnown ? (
                                                        <span className="text-[10px] text-stone-500 dark:text-stone-600 italic">Halaman Jilid {santri.target_jilid ?? '-'} belum diatur</span>
                                                    ) : (
                                                        <div className="flex items-center gap-2">
                                                            <div className="flex-1 bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                                                <div className={`${colors.bar} h-1.5 rounded-full transition-all duration-500`} style={{ width: `${Math.min(persen, 100)}%` }} />
                                                            </div>
                                                            <span className="text-[11px] text-stone-500 whitespace-nowrap">
                                                                {isIqro ? `${santri.total_halaman_iqro ?? 0}/${santri.total_halaman_jilid ?? 0} hal` : `${totalJuz}/${targetJuz} juz`}
                                                            </span>
                                                        </div>
                                                    )}
                                                    {santri.has_quran_secondary && (
                                                        <div className="flex items-center gap-2">
                                                            <div className="flex-1 bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                                                <div className="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style={{ width: `${Math.min(santri.hafalan_persen_2 ?? 0, 100)}%` }} />
                                                            </div>
                                                            <span className="text-[11px] text-stone-500 whitespace-nowrap">
                                                                {santri.capaian_juz_2 ?? 0}/{santri.target_juz_2 ?? 0} juz
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="border-t border-stone-200 dark:border-coal-700/50 flex divide-x divide-stone-200 dark:divide-coal-700/50">
                                                    <Link href={route('guru.rapot.show', santri.id)}
                                                        className="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-claude-600 dark:text-claude-400 hover:bg-claude-500/10 transition">
                                                        <FileTextIcon className="w-3.5 h-3.5" /> Lihat Rapot
                                                    </Link>
                                                    <button type="button"
                                                        onClick={() => toggleRapot(santri)}
                                                        disabled={!bolehAturRapot || rapotBusyId === santri.id}
                                                        title={santri.rapot_dibuka ? 'Kunci kembali PDF rapot santri' : 'Buka akses PDF rapot santri'}
                                                        className={`flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed ${santri.rapot_dibuka ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' : 'text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-coal-700'}`}>
                                                        {santri.rapot_dibuka
                                                            ? <><UnlockIcon className="w-3.5 h-3.5" /> PDF Terbuka</>
                                                            : <><LockIcon className="w-3.5 h-3.5" /> PDF Terkunci</>}
                                                    </button>
                                                    {dpNaik && (
                                                        <button type="button" onClick={() => openNaikSingle(santri.id, santri.name)}
                                                            className="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition">
                                                            <ArrowUpIcon className="w-3.5 h-3.5" /> Naik
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}

                                    {visibleCount === 0 && (
                                        <div className="text-center py-12">
                                            <EmptyIcon className="w-12 h-12 text-stone-300 dark:text-coal-600 mb-3 mx-auto" />
                                            <p className="text-sm font-semibold text-stone-500">Santri tidak ditemukan</p>
                                        </div>
                                    )}
                                </div>

                                {/* ===== DESKTOP TABLE ===== */}
                                <div className="hidden lg:block bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                                    <table className="min-w-full">
                                        <thead>
                                            <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                                {bisaPilih && (
                                                    <th className="px-4 py-3 w-10">
                                                        <input type="checkbox" checked={allSelected} onChange={toggleAll}
                                                            ref={(el) => { if (el) el.indeterminate = selected.length > 0 && !allSelected; }}
                                                            className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 cursor-pointer" />
                                                    </th>
                                                )}
                                                {['No', 'Nama Santri', 'NIS', 'Gender', 'Target', 'Progress', 'Aksi'].map((h) => (
                                                    <th key={h} className="px-4 py-3 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">{h}</th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-stone-200 dark:divide-coal-700/50">
                                            {santriList.map((santri, index) => {
                                                const persen = santri.hafalan_persen ?? 0;
                                                const totalJuz = santri.hafalan_total_juz ?? 0;
                                                const targetJuz = (santri.target_juz ?? 0) > 0 ? santri.target_juz : 30;
                                                const colors = progressColors(persen);
                                                const sudahNaik = sudahNaikSet.has(String(santri.id));
                                                const dpNaik = isDpNaik(santri);
                                                const isPerempuan = santri.gender === 'P' || santri.gender === 'Perempuan';
                                                const photo = santri.user?.profile_photo ? storageUrl(santri.user.profile_photo) : null;
                                                const visible = isVisible(santri);
                                                const isIqro = santri.is_iqro;
                                                const iqroKnown = santri.iqro_persen_diketahui;
                                                const isSel = selected.includes(String(santri.id));

                                                return (
                                                    <tr key={santri.id} className={`hover:bg-stone-50 dark:hover:bg-coal-700/25 transition-colors ${isSel ? 'bg-claude-50 dark:bg-claude-500/10' : ''} ${visible ? '' : 'hidden'}`}>
                                                        {bisaPilih && (
                                                            (bolehAturRapot || dpNaik) ? (
                                                                <td className="px-4 py-3">
                                                                    <input type="checkbox" checked={isSel} onChange={() => toggleOne(String(santri.id))}
                                                                        className="w-4 h-4 rounded border-stone-300 dark:border-coal-600 text-claude-600 cursor-pointer" />
                                                                </td>
                                                            ) : (
                                                                <td className="px-4 py-3 text-center"><span className="text-emerald-500" title="Sudah naik">✓</span></td>
                                                            )
                                                        )}
                                                        <td className="px-4 py-3 text-sm text-stone-500">{index + 1}</td>
                                                        <td className="px-4 py-3">
                                                            <div className="flex items-center gap-2.5">
                                                                {photo ? (
                                                                    <img src={photo} alt="" className="w-9 h-9 rounded-full object-cover object-top border border-stone-200 dark:border-coal-700 flex-shrink-0" />
                                                                ) : (
                                                                    <span className={`w-9 h-9 rounded-full border flex items-center justify-center text-xs font-bold flex-shrink-0 ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                                                        {santri.name?.charAt(0).toUpperCase()}
                                                                    </span>
                                                                )}
                                                                <span className="text-sm font-medium text-stone-800 dark:text-stone-200">{santri.name}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3 text-sm font-mono text-stone-500 dark:text-stone-400">{santri.nis ?? '-'}</td>
                                                        <td className="px-4 py-3">
                                                            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${isPerempuan ? 'bg-pink-50 text-pink-700 border-pink-200/70 dark:bg-pink-500/10 dark:text-pink-400 dark:border-pink-500/20' : 'bg-blue-50 text-blue-700 border-blue-200/70 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20'}`}>
                                                                {isPerempuan ? 'P' : 'L'}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                                                🎯 {santri.target_display ?? '-'}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {isIqro && !iqroKnown ? (
                                                                <span className="text-[10px] text-stone-500 dark:text-stone-600 italic">Halaman Jilid {santri.target_jilid ?? '-'} belum diatur</span>
                                                            ) : (
                                                                <>
                                                                    <div className="flex items-center gap-2 min-w-[160px]">
                                                                        <div className="flex-1 bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                                                            <div className={`${colors.bar} h-1.5 rounded-full transition-all duration-500`} style={{ width: `${Math.min(persen, 100)}%` }} />
                                                                        </div>
                                                                        <span className={`text-xs font-semibold ${colors.text} w-10 text-right shrink-0`}>{persen}%</span>
                                                                    </div>
                                                                    <p className="text-[11px] text-stone-500 dark:text-stone-600 mt-1">
                                                                        {isIqro ? `${santri.total_halaman_iqro ?? 0}/${santri.total_halaman_jilid ?? 0} hal` : `${totalJuz}/${targetJuz} Juz`}
                                                                    </p>
                                                                </>
                                                            )}
                                                            {santri.has_quran_secondary && (
                                                                <>
                                                                    <div className="flex items-center gap-2 min-w-[160px] mt-2">
                                                                        <div className="flex-1 bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                                                            <div className="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style={{ width: `${Math.min(santri.hafalan_persen_2 ?? 0, 100)}%` }} />
                                                                        </div>
                                                                        <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 w-10 text-right shrink-0">{santri.hafalan_persen_2 ?? 0}%</span>
                                                                    </div>
                                                                    <p className="text-[11px] text-stone-500 dark:text-stone-600 mt-1">
                                                                        {santri.capaian_juz_2 ?? 0}/{santri.target_juz_2 ?? 0} Juz (Al-Qur'an)
                                                                    </p>
                                                                </>
                                                            )}
                                                        </td>                                                        <td className="px-4 py-3">
                                                            <div className="flex items-center gap-2">
                                                                <Link href={route('guru.rapot.show', santri.id)}
                                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-claude-500/10 hover:bg-claude-500/20 border border-claude-500/20 text-claude-600 dark:text-claude-400 text-xs font-semibold rounded-lg transition">
                                                                    <FileTextIcon className="w-3.5 h-3.5" /> Lihat Rapot
                                                                </Link>
                                                                <button type="button"
                                                                    onClick={() => toggleRapot(santri)}
                                                                    disabled={!bolehAturRapot || rapotBusyId === santri.id}
                                                                    title={santri.rapot_dibuka ? 'Kunci kembali PDF rapot santri' : 'Buka akses PDF rapot santri'}
                                                                    className={`inline-flex items-center gap-1.5 px-3 py-1.5 border text-xs font-semibold rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed ${santri.rapot_dibuka ? 'bg-emerald-50 hover:bg-emerald-100 border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 dark:border-emerald-500/20 dark:text-emerald-400' : 'bg-stone-100 hover:bg-stone-200 border-stone-200 text-stone-600 dark:bg-coal-700 dark:hover:bg-coal-600 dark:border-coal-600 dark:text-stone-300'}`}>
                                                                    {santri.rapot_dibuka
                                                                        ? <><UnlockIcon className="w-3.5 h-3.5" /> Terbuka</>
                                                                        : <><LockIcon className="w-3.5 h-3.5" /> Terkunci</>}
                                                                </button>
                                                                {dpNaik ? (
                                                                    <button type="button" onClick={() => openNaikSingle(santri.id, santri.name)}
                                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 text-emerald-700 dark:bg-emerald-600/20 dark:hover:bg-emerald-600/40 dark:border-emerald-600/30 dark:text-emerald-400 text-xs font-semibold rounded-lg transition">
                                                                        <ArrowUpIcon className="w-3.5 h-3.5" /> Naik
                                                                    </button>
                                                                ) : bolehNaik ? (
                                                                    <span className="inline-flex items-center gap-1 px-3 py-1.5 bg-stone-100 dark:bg-coal-800 border border-stone-200 dark:border-coal-700 text-stone-400 dark:text-stone-600 text-xs font-semibold rounded-lg">✓ Sudah naik</span>
                                                                ) : null}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                    {visibleCount === 0 && (
                                        <div className="text-center py-16">
                                            <EmptyIcon className="w-12 h-12 text-stone-300 dark:text-coal-600 mb-3 mx-auto" />
                                            <p className="text-sm font-semibold text-stone-500">Santri tidak ditemukan</p>
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                                <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada data santri</p>
                                <p className="text-xs text-stone-500 dark:text-stone-600 mt-1.5">Silakan tambahkan santri terlebih dahulu</p>
                            </div>
                        )
                    ) : (
                        <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4 flex items-start gap-3">
                            <AlertTriangleIcon className="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                            <p className="text-sm text-amber-700 dark:text-amber-300">Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator.</p>
                        </div>
                    )}

                </div>
            </div>

            {/* ===== MODAL NAIK KELAS ===== */}
            <Modal open={showNaik} onClose={() => setShowNaik(false)}>
                <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-300 dark:border-emerald-600/40 rounded-xl flex items-center justify-center text-lg flex-shrink-0">⬆️</div>
                    <div className="min-w-0">
                        <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">Naik Kelas</h3>
                        <p className="text-xs text-stone-500 truncate">{naikLabel}</p>
                    </div>
                </div>
                {kelasTujuanList.length === 0 ? (
                    <>
                        <p className="text-sm text-amber-600 dark:text-amber-400 mb-4">Belum ada kelas tujuan lain. Minta admin membuat kelas tujuan dahulu.</p>
                        <button type="button" onClick={() => setShowNaik(false)} className={BTN_SECONDARY + ' w-full'}>Tutup</button>
                    </>
                ) : (
                    <form onSubmit={submitNaik}>
                        <label className="block text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1.5">Pilih Kelas Tujuan</label>
                        <select value={kelasTujuan} onChange={(e) => setKelasTujuan(e.target.value)} required className={INP + ' mb-1'}>
                            <option value="">— pilih kelas tujuan —</option>
                            {kelasTujuanList.map((k) => (
                                <option key={k.id} value={k.id}>{k.name} · {k.tahun_ajaran} · {k.guru?.name || 'Tanpa Guru'}</option>
                            ))}
                        </select>
                        <p className="text-[10px] text-stone-500 dark:text-stone-600 mb-4">Santri langsung pindah ke kelas & wali kelas tujuan, dan tercatat di riwayat.</p>
                        <div className="flex gap-3">
                            <button type="button" onClick={() => setShowNaik(false)} className={BTN_SECONDARY}>Batal</button>
                            <button type="submit" disabled={!kelasTujuan || naikProcessing}
                                className="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition">
                                {naikProcessing ? 'Memproses...' : 'Naikkan'}
                            </button>
                        </div>
                    </form>
                )}
            </Modal>
        </GuruAuthenticatedLayout>
    );
}
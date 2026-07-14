import { useState, useMemo, useEffect, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const BookIcon    = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const PlusIcon    = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const ChartIcon   = (p) => <I {...p} d={<path d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />} />;
const CalendarIcon= (p) => <I {...p} d={<><path d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" /></>} />;
const FilterIcon  = (p) => <I {...p} d={<path d="M3 4h18M6 10h12M10 16h4" />} />;
const ResetIcon   = (p) => <I {...p} d={<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />} />;
const PlayIcon    = (p) => <I {...p} d={<><path d="M12 12m-9 0a9 9 0 1018 0 9 9 0 10-18 0" /><path d="M10 8l6 4-6 4V8z" /></>} />;
const PdfIcon     = (p) => <I {...p} d={<path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />} />;
const NoteIcon    = (p) => <I {...p} d={<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />} />;
const TrashIcon   = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const XIcon       = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const CheckIcon   = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const DownloadIcon= (p) => <I {...p} d={<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;

/* ============================================================
 * HELPERS
 * ============================================================ */
const nf2 = (n) => Number(n ?? 0).toFixed(2);
const initial = (name) => (name || '?').charAt(0).toUpperCase();

// warna badge nilai (semantik — dipertahankan)
const nilaiTone = (n) => {
    if (n >= 90) return 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-emerald-200 dark:ring-emerald-500/20';
    if (n >= 75) return 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 ring-blue-200 dark:ring-blue-500/20';
    return 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 ring-amber-200 dark:ring-amber-500/20';
};

// tema per kategori (warna kategori = pengecualian semantik yg dipertahankan)
const KAT = {
    Hafalan:  { text: 'text-emerald-600 dark:text-emerald-400', head: 'bg-emerald-50 dark:bg-emerald-500/10', badge: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' },
    Murajaah: { text: 'text-blue-600 dark:text-blue-400',       head: 'bg-blue-50 dark:bg-blue-500/10',       badge: 'bg-blue-100 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400' },
    Tilawah:  { text: 'text-purple-600 dark:text-purple-400',   head: 'bg-purple-50 dark:bg-purple-500/10',   badge: 'bg-purple-100 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400' },
};

/* ============================================================
 * TABEL SETORAN (per kategori, pagination client-side)
 * ============================================================ */
function SetoranTable({ kategori, rows, isIqro, perPage = 10, onAudio, onNote, onDelete }) {
    const [page, setPage] = useState(1);
    const theme = KAT[kategori];
    const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
    const paged = rows.slice((page - 1) * perPage, page * perPage);

    return (
        <div className="ring-1 ring-stone-200 dark:ring-coal-700 rounded-xl overflow-hidden">
            <div className={`flex items-center justify-between px-4 py-2.5 ${theme.head}`}>
                <h4 className={`text-sm font-bold tracking-tight ${theme.text}`}>{kategori}</h4>
                <span className={`text-[10px] px-2 py-0.5 rounded-full font-semibold ${theme.badge}`}>{rows.length} setoran</span>
            </div>

            {rows.length ? (
                <>
                    <div className="divide-y divide-stone-100 dark:divide-coal-700/60">
                        {paged.map((s, i) => (
                            <div key={s.id} className="px-3 py-2.5">
                                <div className="flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2 min-w-0">
                                        <span className="text-[10px] text-stone-400 dark:text-stone-600 bg-stone-100 dark:bg-coal-700 px-1.5 py-0.5 rounded tabular-nums flex-shrink-0">
                                            #{(page - 1) * perPage + i + 1}
                                        </span>
                                        <span className="text-[11px] font-semibold text-stone-600 dark:text-stone-300 flex-shrink-0">{s.tanggal}</span>
                                    </div>
                                    <div className="flex items-center gap-0.5 flex-shrink-0">
                                        {s.has_audio && (
                                            <button onClick={() => onAudio(s.audio_url)} title="Audio" className="p-1.5 rounded-lg text-stone-500 hover:text-claude-600 hover:bg-stone-100 dark:hover:bg-coal-700 transition-colors">
                                                <PlayIcon className="w-3.5 h-3.5" />
                                            </button>
                                        )}
                                        {s.pdf_url && (
                                            <a href={s.pdf_url} target="_blank" rel="noreferrer" title="PDF koreksi" className="p-1.5 rounded-lg text-stone-500 hover:text-red-500 hover:bg-stone-100 dark:hover:bg-coal-700 transition-colors">
                                                <PdfIcon className="w-3.5 h-3.5" />
                                            </a>
                                        )}
                                        {s.catatan && (
                                            <button onClick={() => onNote(s.catatan)} title="Catatan guru" className="p-1.5 rounded-lg text-stone-500 hover:text-amber-500 hover:bg-stone-100 dark:hover:bg-coal-700 transition-colors">
                                                <NoteIcon className="w-3.5 h-3.5" />
                                            </button>
                                        )}
                                        <button onClick={() => onDelete(s.id)} title="Hapus" className="p-1.5 rounded-lg text-stone-500 hover:text-red-500 hover:bg-stone-100 dark:hover:bg-coal-700 transition-colors">
                                            <TrashIcon className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                                <div className="flex items-center justify-between gap-2 mt-1">
                                    <p className="text-[11px] text-stone-500 dark:text-stone-400 truncate">{s.range}</p>
                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        {!isIqro && s.total_juz > 0 && (
                                            <span className="text-[10px] font-bold text-claude-600 dark:text-claude-400 tabular-nums">{nf2(s.total_juz)} juz</span>
                                        )}
                                        <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-md ring-1 tabular-nums ${nilaiTone(s.nilai)}`}>{s.nilai}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {totalPages > 1 && (
                        <div className="flex items-center justify-between px-4 py-2 bg-stone-50 dark:bg-coal-700/40 border-t border-stone-100 dark:border-coal-700/60">
                            <span className="text-[10px] text-stone-400 dark:text-stone-500">{page}/{totalPages} · {rows.length} data</span>
                            <div className="flex items-center gap-1">
                                <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}
                                    className="px-2 py-1 rounded-md text-xs text-stone-500 dark:text-stone-400 hover:bg-stone-200 dark:hover:bg-coal-600 disabled:opacity-30 disabled:cursor-not-allowed">‹</button>
                                <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page === totalPages}
                                    className="px-2 py-1 rounded-md text-xs text-stone-500 dark:text-stone-400 hover:bg-stone-200 dark:hover:bg-coal-600 disabled:opacity-30 disabled:cursor-not-allowed">›</button>
                            </div>
                        </div>
                    )}
                </>
            ) : (
                <div className="p-6 text-center text-xs text-stone-400 dark:text-stone-500">Belum ada data {kategori.toLowerCase()}</div>
            )}
        </div>
    );
}

/* ============================================================
 * MODAL SHELL
 * ============================================================ */
function Modal({ open, onClose, children, maxW = 'max-w-sm' }) {
    useEffect(() => {
        const onEsc = (e) => e.key === 'Escape' && onClose();
        if (open) { document.addEventListener('keydown', onEsc); document.body.style.overflow = 'hidden'; }
        return () => { document.removeEventListener('keydown', onEsc); document.body.style.overflow = ''; };
    }, [open, onClose]);

    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm" onClick={onClose}>
            <div className={`w-full ${maxW} bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl shadow-2xl p-5`} onClick={(e) => e.stopPropagation()}>
                {children}
            </div>
        </div>
    );
}

/* ============================================================
 * TOAST
 * ============================================================ */
function useToast() {
    const [toast, setToast] = useState(null);
    const timer = useRef(null);
    const show = (type, msg) => {
        setToast({ type, msg, id: Date.now() });
        clearTimeout(timer.current);
        timer.current = setTimeout(() => setToast(null), 4000);
    };
    return { toast, show };
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Detail({
    santri, isIqro, taList = [], taTerpilih, taObjekTerpilih,
    kelasKonteks, isSemesterAktif, filter = {}, progress, stats, setoran,
}) {
    const { flash } = usePage().props;
    const { toast, show } = useToast();

    const [tglMulai, setTglMulai] = useState(filter.tanggal_mulai || '');
    const [tglAkhir, setTglAkhir] = useState(filter.tanggal_akhir || '');

    // modal state
    const [audioUrl, setAudioUrl] = useState(null);
    const [noteText, setNoteText] = useState(null);
    const [deleteId, setDeleteId] = useState(null);

    // toast dari flash (reliable: listen setiap kunjungan sukses)
    useEffect(() => {
        if (flash?.success) show('success', flash.success);
        else if (flash?.error) show('error', flash.error);
        // event.detail.page.props.flash — jangan panggil usePage() di sini (bukan render)
        const off = router.on('success', (event) => {
            const f = event.detail?.page?.props?.flash;
            if (f?.success) show('success', f.success);
            else if (f?.error) show('error', f.error);
        });
        return off;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const pilihSemester = (id) =>
        router.get(route('guru.setorans.detail', santri.id), { ta: id }, { preserveScroll: true, preserveState: false });

    const applyFilter = () =>
        router.get(route('guru.setorans.detail', santri.id),
            { ta: taTerpilih, tanggal_mulai: tglMulai || undefined, tanggal_akhir: tglAkhir || undefined },
            { preserveScroll: true });

    const resetFilter = () => {
        setTglMulai(''); setTglAkhir('');
        router.get(route('guru.setorans.detail', santri.id), { ta: taTerpilih }, { preserveScroll: true });
    };

    const confirmDelete = () => {
        const id = deleteId;
        setDeleteId(null);
        router.delete(route('guru.setorans.destroy', id), { preserveScroll: true });
    };

    const pctBar = Math.min(progress.progress_persen, 100);
    const avatarTone = santri.gender === 'Perempuan'
        ? 'bg-pink-100 dark:bg-pink-950/60 ring-pink-200 dark:ring-pink-800/60 text-pink-600 dark:text-pink-400'
        : 'bg-blue-100 dark:bg-blue-950/60 ring-blue-200 dark:ring-blue-800/60 text-blue-600 dark:text-blue-400';

    const statCards = useMemo(() => ([
        { kat: 'Hafalan',  data: stats.hafalan },
        { kat: 'Murajaah', data: stats.murajaah },
        { kat: 'Tilawah',  data: stats.tilawah },
    ]), [stats]);

    return (
        <GuruAuthenticatedLayout>
            <Head title={`Setoran — ${santri.name}`} />

            {/* TOAST */}
            {toast && (
                <div key={toast.id} className="fixed top-4 right-4 left-4 sm:left-auto sm:w-80 z-[300]">
                    <div className="flex items-start gap-3 bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl shadow-xl p-3">
                        <span className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${toast.type === 'success' ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400'}`}>
                            {toast.type === 'success' ? <CheckIcon className="w-4 h-4" /> : <XIcon className="w-4 h-4" />}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-bold text-stone-800 dark:text-stone-100">{toast.type === 'success' ? 'Berhasil' : 'Gagal'}</p>
                            <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">{toast.msg}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="max-w-5xl mx-auto px-3 sm:px-4 py-5">

                {/* Kembali — mobile saja */}
                <Link href={route('guru.setorans.index')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Setoran
                </Link>
                <Link href={route('guru.setorans.index')}
                    className="mb-4 hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Setoran
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-4">
                    {santri.photo_url
                        ? <img src={santri.photo_url} alt="" className="w-11 h-11 rounded-2xl object-cover object-top ring-1 ring-stone-200 dark:ring-coal-700 flex-shrink-0" />
                        : <span className={`w-11 h-11 rounded-2xl ring-1 flex items-center justify-center text-base font-bold flex-shrink-0 ${avatarTone}`}>{initial(santri.name)}</span>}
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri.name}</h1>
                        <div className="flex flex-wrap items-center gap-1.5 mt-1">
                            <span className="text-[11px] px-2 py-0.5 rounded-full bg-stone-100 dark:bg-coal-700 text-stone-600 dark:text-stone-300 font-mono">{santri.nis}</span>
                            <span className="text-[11px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{kelasKonteks}</span>
                            {taObjekTerpilih && (
                                <span className={`text-[11px] px-2 py-0.5 rounded-full font-semibold ${isSemesterAktif ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' : 'bg-stone-100 dark:bg-coal-700 text-stone-500 dark:text-stone-400'}`}>
                                    {taObjekTerpilih.nama} · Sem {taObjekTerpilih.semester}{isSemesterAktif ? ' · Aktif' : ' · Riwayat'}
                                </span>
                            )}
                        </div>
                    </div>
                    <Link href={route('guru.input.create')} className="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-bold transition-colors active:scale-95">
                        <PlusIcon className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Input Setoran</span>
                        <span className="sm:hidden">Input</span>
                    </Link>
                </div>

                {/* ===== SEMESTER + FILTER ===== */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
                    {/* pilih semester */}
                    <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-4">
                        <p className="text-[11px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-500 mb-2.5">Semester / Tahun Ajaran</p>
                        <div className="relative">
                            <select
                                value={taTerpilih ?? ''}
                                onChange={(e) => pilihSemester(e.target.value)}
                                className="w-full appearance-none bg-white dark:bg-coal-900 ring-1 ring-stone-200 dark:ring-coal-700 rounded-xl text-sm text-stone-800 dark:text-stone-200 px-3 py-2.5 pr-9 outline-none focus:ring-2 focus:ring-claude-400 transition-shadow"
                                style={{ colorScheme: 'auto' }}
                            >
                                {taList.map((ta) => (
                                    <option key={ta.id} value={ta.id}>
                                        {ta.nama} — Semester {ta.semester} ({ta.semester === 1 ? 'Ganjil' : 'Genap'})
                                        {ta.kelas_konteks ? ` · Kelas ${ta.kelas_konteks}` : ''}{ta.aktif ? ' · Aktif' : ''}
                                    </option>
                                ))}
                            </select>
                            <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-stone-400">
                                <I d={<path d="M6 9l6 6 6-6" />} className="w-4 h-4" />
                            </span>
                        </div>
                    </div>

                    {/* filter tanggal */}
                    <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-4">
                        <p className="text-[11px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-500 mb-2.5">Filter Periode</p>
                        <div className="grid grid-cols-2 gap-2 mb-2">
                            <div className="relative">
                                <input type="date" value={tglMulai} onChange={(e) => setTglMulai(e.target.value)}
                                    className="w-full bg-white dark:bg-coal-900 ring-1 ring-stone-200 dark:ring-coal-700 rounded-xl text-sm text-stone-800 dark:text-stone-200 px-3 py-2 outline-none focus:ring-2 focus:ring-claude-400"
                                    style={{ colorScheme: 'light dark' }} />
                            </div>
                            <div className="relative">
                                <input type="date" value={tglAkhir} onChange={(e) => setTglAkhir(e.target.value)}
                                    className="w-full bg-white dark:bg-coal-900 ring-1 ring-stone-200 dark:ring-coal-700 rounded-xl text-sm text-stone-800 dark:text-stone-200 px-3 py-2 outline-none focus:ring-2 focus:ring-claude-400"
                                    style={{ colorScheme: 'light dark' }} />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <button onClick={applyFilter} className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-bold transition-colors active:scale-95">
                                <FilterIcon className="w-3.5 h-3.5" /> Terapkan
                            </button>
                            <button onClick={resetFilter} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 text-stone-600 dark:text-stone-300 text-xs font-bold hover:bg-stone-200 dark:hover:bg-coal-600 transition-colors active:scale-95">
                                <ResetIcon className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* ===== PROGRESS HAFALAN ===== */}
                <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-5 mb-4">
                    <div className="flex items-center gap-2 mb-4">
                        <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                            <ChartIcon className="w-3.5 h-3.5" />
                        </span>
                        <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Progress Hafalan</h2>
                    </div>

                    {isIqro ? (
                        <div className="flex items-center justify-between rounded-xl bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20 px-4 py-3">
                            <div>
                                <p className="text-xs text-stone-500 dark:text-stone-400">Target: Jilid {santri.target_jilid ?? '-'}</p>
                                <p className="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">Progress diukur per halaman yang disetor (Hafalan)</p>
                            </div>
                            <span className="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {progress.total_halaman_iqro}<span className="text-xs font-medium ml-1">hal</span>
                            </span>
                        </div>
                    ) : (
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs text-stone-500 dark:text-stone-400">Target: <strong className="text-stone-800 dark:text-stone-200">{progress.target_juz} Juz</strong></span>
                                <span className="text-sm font-bold text-claude-600 dark:text-claude-400 tabular-nums">{nf2(progress.total_hafalan_juz)} Juz ({nf2(progress.progress_persen)}%)</span>
                            </div>
                            <div className="w-full h-3 rounded-full bg-stone-200 dark:bg-coal-700 overflow-hidden">
                                <div className="h-full rounded-full bg-claude-500 transition-all duration-500" style={{ width: `${pctBar}%` }} />
                            </div>
                            {progress.progress_persen >= 100 ? (
                                <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1.5">Target tercapai.</p>
                            ) : (
                                <p className="text-xs text-stone-400 dark:text-stone-500 mt-1.5">Sisa: {nf2(progress.target_juz - progress.total_hafalan_juz)} Juz</p>
                            )}
                        </div>
                    )}

                    {/* 3 stat kategori */}
                    <div className="grid grid-cols-3 gap-2 mt-4">
                        {statCards.map(({ kat, data }) => (
                            <div key={kat} className={`rounded-xl ring-1 p-3 text-center ${KAT[kat].head} ring-stone-200 dark:ring-coal-700`}>
                                <p className={`text-[10px] font-bold uppercase mb-1 ${KAT[kat].text}`}>{kat}</p>
                                <p className={`text-xl font-bold tracking-tight tabular-nums ${KAT[kat].text}`}>{data.total}<span className="text-xs font-medium">x</span></p>
                                <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-0.5">
                                    {isIqro ? `${data.halaman} halaman` : `${nf2(data.total_juz)} juz`}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ===== RIWAYAT SETORAN ===== */}
                <div className="bg-white dark:bg-coal-800 ring-1 ring-stone-200 dark:ring-coal-700 rounded-2xl p-5">
                    <div className="flex items-center gap-2 mb-4">
                        <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                            <BookIcon className="w-3.5 h-3.5" />
                        </span>
                        <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Riwayat Setoran</h2>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <SetoranTable kategori="Hafalan" rows={setoran.hafalan} isIqro={isIqro} perPage={12}
                            onAudio={setAudioUrl} onNote={setNoteText} onDelete={setDeleteId} />
                        <div className="space-y-4">
                            <SetoranTable kategori="Murajaah" rows={setoran.murajaah} isIqro={isIqro} perPage={6}
                                onAudio={setAudioUrl} onNote={setNoteText} onDelete={setDeleteId} />
                            <SetoranTable kategori="Tilawah" rows={setoran.tilawah} isIqro={isIqro} perPage={6}
                                onAudio={setAudioUrl} onNote={setNoteText} onDelete={setDeleteId} />
                        </div>
                    </div>
                </div>
            </div>

            {/* ===== MODAL AUDIO ===== */}
            <Modal open={!!audioUrl} onClose={() => setAudioUrl(null)}>
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">Rekaman Audio</h3>
                    <button onClick={() => setAudioUrl(null)} className="w-7 h-7 rounded-lg bg-stone-100 dark:bg-coal-700 flex items-center justify-center text-stone-500 hover:bg-stone-200 dark:hover:bg-coal-600 transition-colors">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                {audioUrl && (
                    <>
                        <audio src={audioUrl} controls autoPlay className="w-full rounded-lg" />
                        <div className="mt-3 flex justify-end">
                            <a href={audioUrl} download className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 text-white text-xs font-bold transition-colors">
                                <DownloadIcon className="w-3.5 h-3.5" /> Download
                            </a>
                        </div>
                    </>
                )}
            </Modal>

            {/* ===== MODAL CATATAN ===== */}
            <Modal open={!!noteText} onClose={() => setNoteText(null)} maxW="max-w-md">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">Catatan Guru</h3>
                    <button onClick={() => setNoteText(null)} className="w-7 h-7 rounded-lg bg-stone-100 dark:bg-coal-700 flex items-center justify-center text-stone-500 hover:bg-stone-200 dark:hover:bg-coal-600 transition-colors">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                <div className="rounded-xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 p-4">
                    <p className="text-sm text-stone-700 dark:text-stone-200 whitespace-pre-wrap">{noteText}</p>
                </div>
            </Modal>

            {/* ===== MODAL HAPUS ===== */}
            <Modal open={!!deleteId} onClose={() => setDeleteId(null)}>
                <div className="text-center">
                    <span className="mx-auto w-14 h-14 rounded-full bg-red-100 dark:bg-red-500/15 ring-1 ring-red-200 dark:ring-red-500/20 flex items-center justify-center text-red-500 mb-4">
                        <TrashIcon className="w-7 h-7" />
                    </span>
                    <h3 className="text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 mb-1">Hapus setoran ini?</h3>
                    <p className="text-xs text-stone-400 dark:text-stone-500 mb-5">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                    <div className="grid grid-cols-2 gap-3">
                        <button onClick={() => setDeleteId(null)} className="py-2.5 rounded-xl bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 text-stone-700 dark:text-stone-300 text-sm font-semibold hover:bg-stone-200 dark:hover:bg-coal-600 transition-colors">
                            Batal
                        </button>
                        <button onClick={confirmDelete} className="py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-bold transition-colors">
                            Ya, hapus
                        </button>
                    </div>
                </div>
            </Modal>
        </GuruAuthenticatedLayout>
    );
}
import { useState, useEffect, useRef } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />} />;
const PlusIcon     = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const FilterIcon   = (p) => <I {...p} d={<path d="M3 4h18M6 10h12M10 16h4" />} />;
const EditIcon     = (p) => <I {...p} d={<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />} />;
const TrashIcon    = (p) => <I {...p} d={<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />} />;
const LockIcon     = (p) => <I {...p} d={<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />} />;
const XIcon        = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const CheckIcon    = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const ChevDown     = (p) => <I {...p} d={<path d="M6 9l6 6 6-6" />} />;

/* ============================================================
 * TOKEN DESAIN — sama dengan Dashboard / halaman Input
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const TILE = 'w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const BTN_SECONDARY = 'px-4 py-2 rounded-xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-700 dark:text-stone-300 text-xs font-semibold hover:bg-cream-300 dark:hover:bg-coal-500 transition-colors';
// Field: latar coal-600/40 di dark (tidak hitam pekat). color-scheme lewat class —
// BUKAN style 'light dark', yang mengikuti tema OS, bukan toggle Dark Mode aplikasi.
const INP = 'w-full bg-white dark:bg-coal-600/40 border border-cream-300 dark:border-coal-600 rounded-xl text-sm text-stone-800 dark:text-stone-100 px-3 py-2 outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition placeholder:text-stone-400 dark:placeholder:text-stone-500 [color-scheme:light] dark:[color-scheme:dark]';
const LBL = 'block text-xs font-medium text-stone-600 dark:text-stone-400 mb-1.5';

/* ============================================================
 * KONSTANTA — status (warna semantik, sama dengan Dashboard)
 * ============================================================ */
const STATUS = {
    Hadir: { dot: 'bg-emerald-500', chip: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20', box: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400', active: 'ring-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' },
    Izin:  { dot: 'bg-amber-500',   chip: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20',           box: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-600 dark:text-amber-400',           active: 'ring-amber-500 bg-amber-50 dark:bg-amber-500/10' },
    Sakit: { dot: 'bg-orange-500',  chip: 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 ring-orange-100 dark:ring-orange-500/20',     box: 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20 text-orange-600 dark:text-orange-400',     active: 'ring-orange-500 bg-orange-50 dark:bg-orange-500/10' },
    Alpha: { dot: 'bg-red-500',     chip: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-red-100 dark:ring-red-500/20',                       box: 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-600 dark:text-red-400',                       active: 'ring-red-500 bg-red-50 dark:bg-red-500/10' },
};
const STATUS_ORDER = ['Hadir', 'Izin', 'Sakit', 'Alpha'];
const initial = (name) => (name || '?').charAt(0).toUpperCase();
const PER_PAGE = 13;

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
    return { toast, show, hide: () => setToast(null) };
}

/* ============================================================
 * MODAL SHELL
 * onClose disimpan di ref: kalau dijadikan dependency efek, listener
 * Escape & kunci scroll dilepas-pasang di setiap render (tiap ketikan).
 * ============================================================ */
function Modal({ open, onClose, children, maxW = 'max-w-md' }) {
    const closeRef = useRef(onClose);
    closeRef.current = onClose;
    useEffect(() => {
        if (!open) return;
        const onEsc = (e) => e.key === 'Escape' && closeRef.current();
        document.addEventListener('keydown', onEsc);
        document.body.style.overflow = 'hidden';
        return () => { document.removeEventListener('keydown', onEsc); document.body.style.overflow = ''; };
    }, [open]);
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm" onClick={onClose}>
            <div className={`w-full ${maxW} ${CARD} shadow-2xl`} onClick={(e) => e.stopPropagation()}>
                {children}
            </div>
        </div>
    );
}

function SelectField({ value, onChange, children }) {
    return (
        <div className="relative">
            <select value={value} onChange={onChange} className={`${INP} appearance-none pr-9`}>{children}</select>
            <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-stone-400">
                <ChevDown className="w-4 h-4" />
            </span>
        </div>
    );
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Detail({ santri, tahunAjaranAktif, statistik, sesiList = [], filter = {}, presensi = [] }) {
    const { flash } = usePage().props;
    const { toast, show, hide } = useToast();

    const [tglMulai, setTglMulai] = useState(filter.tanggal_mulai || '');
    const [tglAkhir, setTglAkhir] = useState(filter.tanggal_akhir || '');
    const [sesi, setSesi]         = useState(filter.sesi || '');

    const [page, setPage]         = useState(1);
    const [editItem, setEditItem] = useState(null);
    const [deleteId, setDeleteId] = useState(null);

    // form edit
    const editForm = useForm({ tanggal: '', sesi: '', status: '', keterangan: '' });

    // toast dari flash
    useEffect(() => {
        if (flash?.success) show('success', flash.success);
        else if (flash?.error) show('error', flash.error);
        const off = router.on('success', (event) => {
            const f = event.detail?.page?.props?.flash;
            if (f?.success) show('success', f.success);
            else if (f?.error) show('error', f.error);
        });
        return off;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const totalPages = Math.max(1, Math.ceil(presensi.length / PER_PAGE));
    const paged = presensi.slice((page - 1) * PER_PAGE, page * PER_PAGE);

    const applyFilter = () =>
        router.get(route('guru.presensis.detail', santri.id),
            { tanggal_mulai: tglMulai || undefined, tanggal_akhir: tglAkhir || undefined, sesi: sesi || undefined },
            { preserveScroll: true });

    const openEdit = (p) => {
        editForm.setData({ tanggal: p.tanggal, sesi: p.sesi, status: p.status, keterangan: p.keterangan || '' });
        setEditItem(p);
    };

    const submitEdit = () => {
        editForm.put(route('guru.presensis.update', editItem.id), {
            preserveScroll: true,
            onSuccess: () => setEditItem(null),
        });
    };

    const confirmDelete = () => {
        const id = deleteId;
        setDeleteId(null);
        router.delete(route('guru.presensis.destroy', id), { preserveScroll: true });
    };

    const avatarTone = santri.gender === 'Perempuan'
        ? 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400'
        : 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400';

    const statCards = [
        ['Hadir', statistik.hadir],
        ['Izin',  statistik.izin],
        ['Sakit', statistik.sakit],
        ['Alpha', statistik.alpha],
    ];

    const pgBtn = 'px-3 py-1.5 rounded-lg text-xs font-semibold text-stone-600 dark:text-stone-300 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-cream-100 dark:hover:bg-coal-600 transition-colors';

    return (
        <GuruAuthenticatedLayout>
            <Head title={`Presensi — ${santri.name}`} />

            {/* TOAST */}
            {toast && (
                <div key={toast.id} className="fixed top-3 right-3 left-3 sm:left-auto sm:w-96 z-[300]">
                    <div className={`flex items-start gap-3 ${CARD} shadow-xl px-3.5 py-3`}>
                        <span className={`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${toast.type === 'success' ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400'}`}>
                            {toast.type === 'success' ? <CheckIcon className="w-4 h-4" /> : <XIcon className="w-4 h-4" />}
                        </span>
                        <div className="min-w-0 flex-1 pt-0.5">
                            <p className="text-xs font-bold text-stone-800 dark:text-stone-100">{toast.type === 'success' ? 'Berhasil' : 'Gagal'}</p>
                            <p className="text-xs text-stone-600 dark:text-stone-300 mt-0.5">{toast.msg}</p>
                        </div>
                        <button onClick={hide} className="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-cream-200 dark:hover:bg-coal-600 transition">
                            <XIcon className="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            )}

            {/* Padding dibuang — <main> di layout sudah memberinya (sama seperti Dashboard). */}
            <div className="max-w-4xl mx-auto">
                <Link href={route('guru.presensis.index')} className={`${BTN_SOFT} mb-4`}>
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Presensi
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    {santri.photo_url
                        ? <img src={santri.photo_url} alt="" className="w-11 h-11 rounded-2xl object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0" />
                        : <span className={`w-11 h-11 rounded-2xl ring-1 flex items-center justify-center text-base font-bold flex-shrink-0 ${avatarTone}`}>{initial(santri.name)}</span>}
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri.name}</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            <span className="font-mono">{santri.nis}</span>
                            <span className="mx-1 opacity-40">·</span>
                            Kelas <span className="font-semibold text-claude-600 dark:text-claude-400">{santri.kelas_name}</span>
                            {tahunAjaranAktif && (
                                <>
                                    <span className="mx-1 opacity-40">·</span>{tahunAjaranAktif.nama}
                                    <span className="ml-1.5 px-1.5 py-px rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 text-[10px] font-semibold">Aktif</span>
                                </>
                            )}
                        </p>
                    </div>
                    <Link href={route('guru.input.create')} className="flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors active:scale-95">
                        <PlusIcon className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Input Presensi</span>
                        <span className="sm:hidden">Input</span>
                    </Link>
                </div>

                {/* ===== STATISTIK (sama dengan MiniStat Dashboard) ===== */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                    {statCards.map(([label, val]) => (
                        <div key={label} className={`rounded-xl ring-1 p-3 text-center ${STATUS[label].box}`}>
                            <p className="text-2xl font-bold tracking-tight leading-none tabular-nums">{val}</p>
                            <p className="mt-1.5 flex items-center justify-center gap-1 text-[11px] font-medium">
                                <span className={`w-1.5 h-1.5 rounded-full ${STATUS[label].dot}`} /> {label}
                            </p>
                        </div>
                    ))}
                </div>

                {/* ===== FILTER ===== */}
                <div className={`${CARD} p-4 sm:p-5 mb-4`}>
                    <div className="flex items-center gap-2 mb-3">
                        <span className={TILE}><FilterIcon className="w-3.5 h-3.5" /></span>
                        <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Filter Data</h2>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <input type="date" value={tglMulai} onChange={(e) => setTglMulai(e.target.value)} className={INP} aria-label="Tanggal mulai" />
                        <input type="date" value={tglAkhir} onChange={(e) => setTglAkhir(e.target.value)} className={INP} aria-label="Tanggal akhir" />
                        <SelectField value={sesi} onChange={(e) => setSesi(e.target.value)}>
                            <option value="">Semua Sesi</option>
                            {sesiList.map((s) => <option key={s} value={s}>{s}</option>)}
                        </SelectField>
                        <button onClick={applyFilter} className="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors active:scale-95">
                            <FilterIcon className="w-3.5 h-3.5" /> Terapkan
                        </button>
                    </div>
                </div>

                {/* ===== RIWAYAT PRESENSI ===== */}
                <div className={`${CARD} p-4 sm:p-5`}>
                    <div className="flex items-center gap-2 mb-4">
                        <span className={TILE}><CalendarIcon className="w-3.5 h-3.5" /></span>
                        <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Riwayat Presensi</h2>
                        <span className="ml-auto text-[11px] text-stone-400">{presensi.length} data</span>
                    </div>

                    {presensi.length ? (
                        <>
                            <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden divide-y divide-cream-200 dark:divide-coal-600/60">
                                {paged.map((p, i) => {
                                    const st = STATUS[p.status];
                                    return (
                                        <div key={p.id} className="flex items-center gap-3 px-3 py-2.5 hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors">
                                            <span className="text-[11px] text-stone-400 tabular-nums w-6 flex-shrink-0 text-right">{(page - 1) * PER_PAGE + i + 1}</span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="text-sm font-medium text-stone-700 dark:text-stone-200">{p.tanggal_display}</span>
                                                    <span className="text-[10px] px-1.5 py-0.5 rounded-md ring-1 bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 text-stone-600 dark:text-stone-300 font-semibold">{p.sesi}</span>
                                                    <span className={`inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded-md ring-1 font-semibold ${st?.chip ?? 'bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 text-stone-500'}`}>
                                                        <span className={`w-1.5 h-1.5 rounded-full ${st?.dot ?? 'bg-stone-400'}`} /> {p.status}
                                                    </span>
                                                    {p.has_setoran && (
                                                        <span className="inline-flex items-center gap-0.5 text-[10px] text-stone-400" title="Ada setoran terkait">
                                                            <LockIcon className="w-3 h-3" /> setoran
                                                        </span>
                                                    )}
                                                </div>
                                                {p.keterangan && <p className="text-[11px] text-stone-400 mt-0.5 truncate">{p.keterangan}</p>}
                                            </div>
                                            <div className="flex items-center gap-0.5 flex-shrink-0">
                                                <button onClick={() => openEdit(p)} title="Edit" className="p-1.5 rounded-lg text-stone-500 dark:text-stone-400 hover:text-claude-600 dark:hover:text-claude-400 hover:bg-cream-200 dark:hover:bg-coal-600 transition-colors">
                                                    <EditIcon className="w-3.5 h-3.5" />
                                                </button>
                                                <button
                                                    onClick={() => setDeleteId(p.id)}
                                                    disabled={p.has_setoran}
                                                    title={p.has_setoran ? 'Hapus setoran terkait dulu' : 'Hapus'}
                                                    className="p-1.5 rounded-lg text-stone-500 dark:text-stone-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-stone-500"
                                                >
                                                    <TrashIcon className="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {totalPages > 1 && (
                                <div className="flex items-center justify-between mt-4">
                                    <span className="text-[11px] text-stone-400 tabular-nums">Halaman {page} dari {totalPages}</span>
                                    <div className="flex items-center gap-1">
                                        <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1} className={pgBtn}>Sebelumnya</button>
                                        <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page === totalPages} className={pgBtn}>Berikutnya</button>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="py-12 flex flex-col items-center text-center">
                            <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                                <CalendarIcon className="w-5 h-5" />
                            </span>
                            <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">Belum ada data presensi</p>
                            <p className="text-[11px] text-stone-400 mt-0.5">Coba ubah rentang tanggal filter.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* ===== MODAL EDIT ===== */}
            <Modal open={!!editItem} onClose={() => setEditItem(null)}>
                <div className="flex items-center gap-2 px-5 py-3.5 border-b border-cream-300 dark:border-coal-600">
                    <span className={TILE}><EditIcon className="w-3.5 h-3.5" /></span>
                    <h3 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-100 flex-1">Edit Presensi</h3>
                    <button onClick={() => setEditItem(null)} className="w-7 h-7 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition-colors">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                <div className="p-5">
                    <div className="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label className={LBL}>Tanggal</label>
                            <input type="date" value={editForm.data.tanggal} onChange={(e) => editForm.setData('tanggal', e.target.value)} className={INP} />
                            {editForm.errors.tanggal && <p className="text-[11px] text-red-500 mt-1">{editForm.errors.tanggal}</p>}
                        </div>
                        <div>
                            <label className={LBL}>Sesi</label>
                            <SelectField value={editForm.data.sesi} onChange={(e) => editForm.setData('sesi', e.target.value)}>
                                {sesiList.map((s) => <option key={s} value={s}>{s}</option>)}
                            </SelectField>
                            {editForm.errors.sesi && <p className="text-[11px] text-red-500 mt-1">{editForm.errors.sesi}</p>}
                        </div>
                    </div>

                    <div className="mb-4">
                        <label className={LBL}>Status</label>
                        <div className="grid grid-cols-4 gap-2">
                            {STATUS_ORDER.map((val) => {
                                const s = STATUS[val];
                                const active = editForm.data.status === val;
                                return (
                                    <button key={val} type="button" onClick={() => editForm.setData('status', val)}
                                        className={`flex flex-col items-center gap-1.5 py-2.5 rounded-xl ring-1 transition-colors ${active ? `ring-2 ${s.active}` : 'ring-cream-300 dark:ring-coal-600 bg-cream-100 dark:bg-coal-600/40 hover:bg-cream-200 dark:hover:bg-coal-600'}`}>
                                        <span className={`w-3 h-3 rounded-full ${s.dot}`} />
                                        <span className="text-[11px] font-semibold text-stone-700 dark:text-stone-300">{val}</span>
                                    </button>
                                );
                            })}
                        </div>
                        {editForm.errors.status && <p className="text-[11px] text-red-500 mt-1">{editForm.errors.status}</p>}
                    </div>

                    {editForm.data.status && editForm.data.status !== 'Hadir' && (
                        <div className="mb-4">
                            <label className={LBL}>Keterangan</label>
                            <textarea rows={2} value={editForm.data.keterangan} onChange={(e) => editForm.setData('keterangan', e.target.value)}
                                placeholder="Keterangan tambahan (opsional)…"
                                className={`${INP} resize-none`} />
                        </div>
                    )}

                    <div className="flex justify-end gap-2">
                        <button onClick={() => setEditItem(null)} className={BTN_SECONDARY}>Batal</button>
                        <button onClick={submitEdit} disabled={editForm.processing} className="px-4 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors disabled:opacity-60">
                            {editForm.processing ? 'Menyimpan…' : 'Simpan'}
                        </button>
                    </div>
                </div>
            </Modal>

            {/* ===== MODAL HAPUS ===== */}
            <Modal open={!!deleteId} onClose={() => setDeleteId(null)} maxW="max-w-sm">
                <div className="p-6 text-center">
                    <span className="mx-auto w-14 h-14 rounded-2xl bg-red-50 dark:bg-red-500/10 ring-1 ring-red-100 dark:ring-red-500/20 flex items-center justify-center text-red-500 dark:text-red-400 mb-4">
                        <TrashIcon className="w-7 h-7" />
                    </span>
                    <h3 className="text-base font-bold tracking-tight text-stone-800 dark:text-stone-100 mb-1">Hapus presensi ini?</h3>
                    <p className="text-xs text-stone-500 dark:text-stone-400 mb-5">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                    <div className="grid grid-cols-2 gap-3">
                        <button onClick={() => setDeleteId(null)} className={`${BTN_SECONDARY} !py-2.5 !text-sm`}>Batal</button>
                        <button onClick={confirmDelete} className="py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition-colors">Ya, hapus</button>
                    </div>
                </div>
            </Modal>
        </GuruAuthenticatedLayout>
    );
}
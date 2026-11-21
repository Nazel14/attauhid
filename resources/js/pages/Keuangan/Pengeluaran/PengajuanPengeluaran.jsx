import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import SearchableSelect from '@/Components/SearchableSelect';
import TombolKembali from '@/Components/TombolKembali';
import { formatCurrency } from '@/Utils/formatCurrency';

const LANGKAH = [
    { id: 'draft',     label: 'Draft' },
    { id: 'diperiksa', label: 'Diperiksa' },
    { id: 'disetujui', label: 'Dicairkan' },
    { id: 'selesai',   label: 'Selesai' },
];

// Label tombol + permission untuk memajukan pengajuan ke tahap berikutnya.
// perm berupa daftar: nama izin di database ('approve_final') berbeda dengan
// nama lama di kode ('approve_disetujui'). Salah satu dimiliki = boleh.
// Harus sama dengan PengeluaranController::IZIN.
const AKSI_LANJUT = {
    diperiksa: { label: 'Tandai Diperiksa',   perm: ['pengeluaran.approve_diperiksa'], catatan: 'Pengajuan terkunci dari perubahan.' },
    disetujui: { label: 'Setujui & Cairkan',  perm: ['pengeluaran.approve_final', 'pengeluaran.approve_disetujui'], catatan: 'Saldo rekening berkurang saat tahap ini.' },
};

const TAB = ['semua', 'draft', 'diperiksa', 'disetujui', 'selesai'];

const waktu = (t) => {
    if (!t) return '';
    const d = new Date(t);
    return `${d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })} · ${d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':')}`;
};

const tanggal = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-');

export default function PengajuanPengeluaran({ pengeluaran, rkas_options, rekening_options, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || 'semua');
    const [monthFilter, setMonthFilter] = useState(filters?.monthFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'tanggal_pengajuan');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [terpilih, setTerpilih] = useState(null);
    const aktif = pengeluaran?.data?.find((i) => i.id === terpilih);

    const [isForm, setIsForm] = useState(false);
    const [isEdit, setIsEdit] = useState(false);
    const [isLpj, setIsLpj] = useState(false);
    const [isCair, setIsCair] = useState(false);
    const [isBukti, setIsBukti] = useState(false);
    const [dialog, setDialog] = useState(null);
    const [pesanError, setPesanError] = useState('');
    const [proses, setProses] = useState(false);

    // Anchor auto-scroll saat tombol "Detail" ditekan.
    const detailRef = useRef(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        rkas_id: '',
        tanggal_pengajuan: new Date().toISOString().split('T')[0],
        nominal_pengajuan: '',
        deskripsi: '',
    });

    const lpjForm = useForm({ nominal_realisasi: '', bukti_nota: [] });
    const cairForm = useForm({ stage: 'disetujui', account_id: '' });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.pengeluaran.pengajuan'),
                {
                    search, monthFilter,
                    status: statusFilter === 'semua' ? '' : statusFilter,
                    sort_field: sortField, sort_direction: sortDirection,
                },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, statusFilter, monthFilter, sortField, sortDirection]);

    // Dipanggil dari tombol "Detail" di kolom Aksi, bukan dari klik baris.
    const bukaDetail = (id) => {
        const tutup = terpilih === id;
        setTerpilih(tutup ? null : id);
        if (!tutup) {
            setTimeout(() => detailRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 60);
        }
    };

    const handleSort = (f) => {
        if (sortField === f) setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        else { setSortField(f); setSortDirection('asc'); }
    };

    const SortIcon = ({ field }) => {
        if (sortField !== field) {
            return <svg className="ml-1 h-3.5 w-3.5 text-stone-400 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>;
        }
        return sortDirection === 'asc'
            ? <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 15l7-7 7 7" /></svg>
            : <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" /></svg>;
    };

    const openAdd = () => { setIsEdit(false); reset(); clearErrors(); setIsForm(true); };

    const openEdit = (item) => {
        setIsEdit(true); setTerpilih(item.id); clearErrors();
        setData({
            rkas_id: item.rkas_id,
            tanggal_pengajuan: item.tanggal_pengajuan?.split('T')[0] ?? item.tanggal_pengajuan,
            nominal_pengajuan: Math.floor(item.nominal_pengajuan).toString(),
            deskripsi: item.deskripsi,
        });
        setIsForm(true);
    };

    const submitForm = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsForm(false); reset(); }, preserveScroll: true };
        if (isEdit) put(route('keuangan.pengeluaran.pengajuan.update', terpilih), opts);
        else post(route('keuangan.pengeluaran.pengajuan.store'), opts);
    };

    const submitCair = (e) => {
        e.preventDefault();
        cairForm.post(route('keuangan.pengeluaran.pengajuan.approve', terpilih), {
            onSuccess: () => setIsCair(false),
            preserveScroll: true,
        });
    };

    const submitLpj = (e) => {
        e.preventDefault();
        lpjForm.post(route('keuangan.pengeluaran.pengajuan.store_lpj', terpilih), {
            onSuccess: () => setIsLpj(false),
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const aksi = (url, method = 'post', body = {}) => {
        setProses(true);
        const opts = {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => { setDialog(null); setPesanError(''); },
            onError: (err) => setPesanError(err.approval || err.status || 'Gagal memproses.'),
        };
        if (method === 'delete') router.delete(url, opts);
        else router.post(url, body, opts);
    };

    const badgeStatus = (s) => ({
        'draft':     'bg-cream-200 text-stone-600 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500',
        'diperiksa': 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'disetujui': 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'selesai':   'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
    }[s] || 'bg-cream-200 text-stone-600 ring-cream-300');

    const langkahAktif = aktif ? LANGKAH.findIndex((s) => s.id === aktif.status_approval) : 0;

    // Tahap berikutnya. 'selesai' hanya tercapai lewat LPJ, jadi tidak punya tombol.
    const langkahBerikut = aktif && langkahAktif >= 0 && langkahAktif < LANGKAH.length - 1
        ? LANGKAH[langkahAktif + 1]
        : null;

    const infoLanjut = langkahBerikut ? AKSI_LANJUT[langkahBerikut.id] : null;
    const bisaLanjut = infoLanjut ? infoLanjut.perm.some((p) => can(p)) : false;

    const pelaku = (step) => {
        if (!aktif) return null;
        const map = {
            draft:     [aktif.diajukan_oleh, aktif.created_at],
            diperiksa: [aktif.diperiksa_oleh, aktif.diperiksa_at],
            disetujui: [aktif.disetujui_oleh, aktif.disetujui_at],
            selesai:   [aktif.diajukan_oleh, aktif.updated_at],
        }[step];

        if (!map || !map[0]) return null;

        return (
            <p className="mt-1 text-[10px] leading-tight text-stone-400">
                <span className="font-medium text-stone-600 dark:text-stone-300">{map[0].name}</span><br />{waktu(map[1])}
            </p>
        );
    };

    const sisaRealisasi = (lpjForm.data.nominal_realisasi !== '' && aktif)
        ? (parseFloat(aktif.nominal_pengajuan) - parseFloat(lpjForm.data.nominal_realisasi || 0))
        : null;

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600';
    // color-scheme ikut tema agar ikon kalender bawaan browser tidak silau di dark mode.
    const monthCls = `${fieldCls} pr-2 [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-60 hover:[&::-webkit-calendar-picker-indicator]:opacity-100`;

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const IkonMata = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    );

    const labelStatus = (s) => (s === 'disetujui' ? 'dicairkan' : s);

    return (
        <KeuanganLayout>
            <Head title="Pengajuan Pengeluaran" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Kelola Pengeluaran</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Pengajuan dana dari mata anggaran RKAS hingga pertanggungjawabannya.</p>
            </div>

            {/* Tab digeser horizontal di mobile — 5 tab tidak muat sebaris
                di 360px, dan membungkusnya membuat tinggi header melonjak. */}
            <div className="-mx-4 mb-5 overflow-x-auto px-4 pb-1 sm:mx-0 sm:overflow-visible sm:px-0">
                <div className="flex min-w-max gap-2 sm:min-w-0 sm:flex-wrap">
                    {TAB.map((t) => (
                        <button key={t} onClick={() => setStatusFilter(t)}
                            className={`shrink-0 rounded-xl px-3.5 py-2 text-sm font-medium capitalize transition sm:px-4 sm:py-2.5 ${
                                statusFilter === t
                                    ? 'bg-claude-500 text-white shadow-lg shadow-claude-500/25'
                                    : 'bg-white text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                            }`}>
                            {labelStatus(t)}
                        </button>
                    ))}
                </div>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-sm sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari kegiatan atau deskripsi..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="sm:w-44">
                            <input type="month" value={monthFilter} onChange={(e) => setMonthFilter(e.target.value)}
                                aria-label="Filter periode" className={monthCls} />
                        </div>
                    </div>

                    {can('pengeluaran.buat') && (
                        <PrimaryButton onClick={openAdd} className="h-10 shrink-0 justify-center">Buat Pengajuan</PrimaryButton>
                    )}
                </div>

                {/* Mobile: kartu. Tabel 6 kolom mustahil dibaca di layar sempit. */}
                <div className="space-y-3 sm:hidden">
                    {pengeluaran?.data?.length > 0 ? pengeluaran.data.map((i) => {
                        const dipilih = terpilih === i.id;

                        return (
                            <div key={i.id}
                                className={`rounded-xl p-4 ring-1 ${dipilih
                                    ? 'bg-claude-500/10 ring-claude-500/30'
                                    : 'bg-white ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600'}`}>
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="break-words text-sm font-semibold text-stone-800 dark:text-cream-100">
                                            {i.rkas ? `[${i.rkas.kode_kegiatan}] ${i.rkas.nama_kegiatan}` : '—'}
                                        </p>
                                        <p className="mt-0.5 line-clamp-2 text-xs text-stone-400">{i.deskripsi}</p>
                                    </div>
                                    <span className={`shrink-0 rounded-full px-2.5 py-1 text-[10px] font-medium capitalize ring-1 ${badgeStatus(i.status_approval)}`}>
                                        {labelStatus(i.status_approval)}
                                    </span>
                                </div>

                                {i.is_over_budget_warning && (
                                    <p className="mt-2 rounded-lg bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                        Melebihi pagu mata anggaran
                                    </p>
                                )}

                                <div className="mt-3 flex items-end justify-between gap-3">
                                    <div className="min-w-0 text-xs text-stone-400">
                                        {tanggal(i.tanggal_pengajuan)}<br />
                                        <span className="text-stone-500 dark:text-stone-300">{i.diajukan_oleh?.name || '—'}</span>
                                    </div>
                                    <span className="shrink-0 text-sm font-bold text-red-600 dark:text-red-400">{formatCurrency(i.nominal_pengajuan)}</span>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-2 border-t border-cream-200 pt-3 dark:border-coal-600">
                                    <button type="button" onClick={() => bukaDetail(i.id)}
                                        className={`${btnGhost} flex-1 justify-center ${
                                            dipilih
                                                ? 'bg-claude-500 text-white ring-claude-500'
                                                : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                        }`}>
                                        <IkonMata />
                                        Detail
                                    </button>

                                    {i.status_approval === 'draft' && (
                                        <>
                                            {can('pengeluaran.edit') && (
                                                <button type="button" onClick={() => openEdit(i)}
                                                    className={`${btnGhost} flex-1 justify-center text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                    Edit
                                                </button>
                                            )}
                                            {can('pengeluaran.hapus') && (
                                                <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus', id: i.id, teks: i.deskripsi }); }}
                                                    className={`${btnGhost} flex-1 justify-center text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                    Hapus
                                                </button>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        );
                    }) : (
                        <p className="rounded-xl bg-cream-100 py-10 text-center text-sm text-stone-500 dark:bg-coal-600/40 dark:text-stone-400">Belum ada pengajuan.</p>
                    )}
                </div>

                {/* Desktop: tabel penuh dengan sorting. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th onClick={() => handleSort('tanggal_pengajuan')} className={thCls}><div className="flex items-center">Tanggal <SortIcon field="tanggal_pengajuan" /></div></th>
                                <th className={thPlain}>Pemohon</th>
                                <th className={thPlain}>Mata Anggaran &amp; Keperluan</th>
                                <th onClick={() => handleSort('nominal_pengajuan')} className={thCls}><div className="flex items-center">Nominal <SortIcon field="nominal_pengajuan" /></div></th>
                                <th onClick={() => handleSort('status_approval')} className={thCls}><div className="flex items-center">Status <SortIcon field="status_approval" /></div></th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {pengeluaran?.data?.length > 0 ? pengeluaran.data.map((i) => {
                                const dipilih = terpilih === i.id;
                                return (
                                    <tr key={i.id} className={`transition ${dipilih ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{tanggal(i.tanggal_pengajuan)}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-700 dark:text-stone-200">{i.diajukan_oleh?.name || '—'}</td>
                                        <td className="px-5 py-4 text-sm">
                                            <span className="font-medium text-stone-800 dark:text-cream-100">
                                                {i.rkas ? `[${i.rkas.kode_kegiatan}] ${i.rkas.nama_kegiatan}` : '—'}
                                            </span><br />
                                            <span className="line-clamp-1 max-w-xs text-xs text-stone-400">{i.deskripsi}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-red-600 dark:text-red-400">{formatCurrency(i.nominal_pengajuan)}</td>
                                        <td className="whitespace-nowrap px-5 py-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ${badgeStatus(i.status_approval)}`}>
                                                {labelStatus(i.status_approval)}
                                            </span>
                                            {i.is_over_budget_warning && (
                                                <span className="ml-1.5 inline-flex rounded-full bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">Lewat pagu</span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {/* Detail & jalur persetujuan dibuka lewat tombol, bukan klik baris. */}
                                                <button type="button" onClick={() => bukaDetail(i.id)}
                                                    className={`${btnGhost} ${
                                                        dipilih
                                                            ? 'bg-claude-500 text-white ring-claude-500'
                                                            : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                                    }`}>
                                                    <IkonMata />
                                                    Detail
                                                </button>

                                                {i.status_approval === 'draft' && (
                                                    <>
                                                        {can('pengeluaran.edit') && (
                                                            <button type="button" onClick={() => openEdit(i)}
                                                                className={`${btnGhost} text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                                Edit
                                                            </button>
                                                        )}
                                                        {can('pengeluaran.hapus') && (
                                                            <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus', id: i.id, teks: i.deskripsi }); }}
                                                                className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                                Hapus
                                                            </button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan={6} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada pengajuan.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {pengeluaran.from || 0}–{pengeluaran.to || 0} dari {pengeluaran.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {pengeluaran.links.map((l, i) => (
                            <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <div ref={detailRef} className="scroll-mt-20" />

            {aktif && (
                <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="space-y-4">
                        <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                            <div className="mb-4 flex items-start justify-between gap-2 border-b border-cream-200 pb-3 dark:border-coal-600">
                                <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Informasi Pengajuan</h3>
                                <button type="button" onClick={() => setTerpilih(null)} title="Tutup detail"
                                    className="-mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <dl className="space-y-3 text-sm">
                                <div>
                                    <dt className="text-[11px] uppercase tracking-wider text-stone-400">Nominal diajukan</dt>
                                    <dd className="break-words text-lg font-bold text-claude-600 dark:text-claude-300">{formatCurrency(aktif.nominal_pengajuan)}</dd>
                                </div>
                                {aktif.rekening && (
                                    <div>
                                        <dt className="text-[11px] uppercase tracking-wider text-stone-400">Sumber dana</dt>
                                        <dd className="break-words font-medium text-stone-800 dark:text-cream-100">{aktif.rekening.account_name}</dd>
                                    </div>
                                )}
                                <div>
                                    <dt className="text-[11px] uppercase tracking-wider text-stone-400">Keperluan</dt>
                                    <dd className="break-words text-stone-700 dark:text-stone-200">{aktif.deskripsi}</dd>
                                </div>
                            </dl>

                            {aktif.is_over_budget_warning && (
                                <p className="mt-4 rounded-xl bg-amber-50 p-3 text-xs font-medium text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                    Pengajuan ini melebihi pagu mata anggarannya.
                                </p>
                            )}

                            {aktif.attachments?.length > 0 && (
                                <SecondaryButton onClick={() => setIsBukti(true)} className="mt-4 w-full justify-center">
                                    Lihat {aktif.attachments.length} lampiran
                                </SecondaryButton>
                            )}
                        </div>

                        {(aktif.status_approval === 'disetujui' || aktif.status_approval === 'selesai') && (
                            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                                <h3 className="mb-4 border-b border-cream-200 pb-3 text-sm font-semibold text-stone-800 dark:border-coal-600 dark:text-cream-50">Pertanggungjawaban</h3>

                                {aktif.status_approval === 'disetujui' ? (
                                    <>
                                        <p className="text-sm text-stone-500 dark:text-stone-400">Dana sudah cair. Laporkan realisasi setelah kegiatan selesai.</p>
                                        {can('pengeluaran.buat_lpj') && (
                                            <PrimaryButton onClick={() => { lpjForm.reset(); lpjForm.clearErrors(); setIsLpj(true); }} className="mt-4 w-full justify-center">
                                                Buat LPJ
                                            </PrimaryButton>
                                        )}
                                    </>
                                ) : (
                                    <div className="space-y-2.5 text-sm">
                                        <div className="flex justify-between gap-3 border-b border-cream-200 pb-2 dark:border-coal-600">
                                            <span className="text-stone-500 dark:text-stone-400">Realisasi</span>
                                            <span className="text-right font-medium text-stone-800 dark:text-cream-100">{formatCurrency(aktif.nominal_realisasi)}</span>
                                        </div>
                                        <div className="flex justify-between gap-3 border-b border-cream-200 pb-2 dark:border-coal-600">
                                            <span className="text-stone-500 dark:text-stone-400">Sisa dana</span>
                                            <span className="text-right font-semibold text-emerald-600 dark:text-emerald-400">{formatCurrency(aktif.nominal_refund)}</span>
                                        </div>
                                        <div className="flex items-center justify-between gap-3 pt-1">
                                            <span className="text-xs text-stone-500 dark:text-stone-400">Status pengembalian</span>
                                            {aktif.is_refund_completed ? (
                                                <span className="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Selesai</span>
                                            ) : (
                                                <span className="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">Menunggu</span>
                                            )}
                                        </div>
                                        {!aktif.is_refund_completed && aktif.nominal_refund > 0 && can('pengeluaran.approve_selesai') && (
                                            <PrimaryButton className="mt-2 w-full justify-center"
                                                onClick={() => { setPesanError(''); setDialog({ jenis: 'refund' }); }}>
                                                Konfirmasi Terima Sisa Dana
                                            </PrimaryButton>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5 lg:col-span-2">
                        <h3 className="mb-6 border-b border-cream-200 pb-3 text-sm font-semibold text-stone-800 dark:border-coal-600 dark:text-cream-50">Jalur Persetujuan</h3>

                        {/* Lingkaran murni indikator. Digeser horizontal di mobile. */}
                        <div className="-mx-4 overflow-x-auto px-4 pb-1 sm:mx-0 sm:overflow-visible sm:px-0">
                            <div className="flex min-w-max items-start gap-x-2 gap-y-6 sm:min-w-0 sm:flex-wrap">
                                {LANGKAH.map((step, i) => {
                                    const selesai = i <= langkahAktif;
                                    const berikutnya = i === langkahAktif + 1;

                                    return (
                                        <div key={step.id} className="flex items-start">
                                            <div className="flex w-24 flex-col items-center text-center">
                                                <div className={`flex h-10 w-10 items-center justify-center rounded-full ring-2 ${
                                                    selesai ? 'bg-claude-500 text-white ring-claude-500'
                                                    : berikutnya ? 'bg-white text-claude-600 ring-claude-500 dark:bg-coal-700'
                                                    : 'bg-cream-100 text-stone-400 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600'
                                                }`}>
                                                    {selesai
                                                        ? <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                        : <span className="text-sm font-semibold">{i + 1}</span>}
                                                </div>
                                                <span className={`mt-2 text-xs font-medium ${selesai || berikutnya ? 'text-stone-800 dark:text-cream-100' : 'text-stone-400'}`}>{step.label}</span>
                                                {selesai && pelaku(step.id)}
                                            </div>
                                            {i < LANGKAH.length - 1 && (
                                                <div className={`mt-5 h-0.5 w-6 rounded sm:w-12 ${i < langkahAktif ? 'bg-claude-500' : 'bg-cream-300 dark:bg-coal-600'}`} />
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Aksi persetujuan sebagai tombol eksplisit. */}
                        <div className="mt-6 rounded-xl bg-cream-100 p-4 dark:bg-coal-600/40">
                            {!langkahBerikut ? (
                                <div className="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                                    <svg className="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span className="font-medium">Pengajuan sudah selesai dan dipertanggungjawabkan.</span>
                                </div>
                            ) : langkahBerikut.id === 'selesai' ? (
                                <p className="text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                                    Tahap <strong className="text-stone-800 dark:text-cream-100">Selesai</strong> tercapai otomatis setelah LPJ diisi — gunakan tombol <strong>Buat LPJ</strong> di panel {' '}
                                    <span className="lg:hidden">atas</span><span className="hidden lg:inline">kiri</span>.
                                </p>
                            ) : (
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                                        Tahap berikutnya: <strong className="text-stone-800 dark:text-cream-100">{langkahBerikut.label}</strong>. {infoLanjut?.catatan}
                                        {!bisaLanjut && <><br /><span className="text-stone-400">Anda tidak memiliki wewenang untuk tahap ini.</span></>}
                                    </p>
                                    <PrimaryButton
                                        disabled={!bisaLanjut || proses}
                                        onClick={() => {
                                            setPesanError('');
                                            if (langkahBerikut.id === 'disetujui') {
                                                cairForm.reset(); cairForm.clearErrors();
                                                cairForm.setData('stage', 'disetujui');
                                                setIsCair(true);
                                            } else {
                                                setDialog({ jenis: 'approve', stage: langkahBerikut.id, label: langkahBerikut.label });
                                            }
                                        }}
                                        className="w-full shrink-0 justify-center disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                                        {infoLanjut?.label || 'Lanjutkan'}
                                    </PrimaryButton>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <Modal show={isForm} onClose={() => setIsForm(false)} maxWidth="2xl">
                <form onSubmit={submitForm} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEdit ? 'Edit Pengajuan' : 'Buat Pengajuan Baru'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Mata Anggaran RKAS <span className="text-red-500">*</span></InputLabel>
                            <div className="mt-1.5">
                                <SearchableSelect placeholder="-- Pilih kegiatan RKAS --"
                                    options={rkas_options?.map((r) => ({
                                        id: r.id,
                                        label: `[${r.kode_kegiatan}] ${r.nama_kegiatan} · sisa ${formatCurrency(r.sisa_pagu)}`,
                                    })) || []}
                                    value={data.rkas_id} onChange={(id) => setData('rkas_id', id)} />
                            </div>
                            {(!rkas_options || rkas_options.length === 0) && (
                                <p className="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                    Belum ada mata anggaran tersedia. Pastikan ada RKAS berstatus Final dengan pagu tersisa.
                                </p>
                            )}
                            <InputError message={errors.rkas_id} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel>Tanggal Pengajuan <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="date" value={data.tanggal_pengajuan} onChange={(e) => setData('tanggal_pengajuan', e.target.value)}
                                    className="mt-1.5 block w-full [color-scheme:light] dark:[color-scheme:dark]" />
                                <InputError message={errors.tanggal_pengajuan} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel>Nominal (Rp) <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="text" inputMode="numeric"
                                    value={data.nominal_pengajuan ? data.nominal_pengajuan.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                    onChange={(e) => setData('nominal_pengajuan', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1.5 block w-full" placeholder="0" />
                                <InputError message={errors.nominal_pengajuan} className="mt-1.5" />
                            </div>
                        </div>

                        <div>
                            <InputLabel>Deskripsi Keperluan <span className="text-red-500">*</span></InputLabel>
                            <textarea value={data.deskripsi} onChange={(e) => setData('deskripsi', e.target.value)} rows="3"
                                className="mt-1.5 block w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-stone-800 shadow-sm ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600"
                                placeholder="Jelaskan rincian keperluan dana ini…" />
                            <InputError message={errors.deskripsi} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsForm(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing} className="justify-center">Simpan Pengajuan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isCair} onClose={() => setIsCair(false)} maxWidth="md">
                <form onSubmit={submitCair} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Setujui &amp; Cairkan Dana</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Saldo rekening akan berkurang dan tercatat permanen di buku kas.</p>

                    <div className="mt-5 rounded-xl bg-cream-100 p-4 dark:bg-coal-600/40">
                        <p className="text-[11px] uppercase tracking-wider text-stone-400">Nominal dicairkan</p>
                        <p className="mt-1 break-words text-lg font-bold text-stone-800 dark:text-cream-100">{formatCurrency(aktif?.nominal_pengajuan)}</p>
                    </div>

                    <div className="mt-4">
                        <InputLabel>Sumber Dana <span className="text-red-500">*</span></InputLabel>
                        <div className="mt-1.5">
                            <SearchableSelect placeholder="-- Pilih rekening/kas --"
                                options={rekening_options?.map((r) => ({ id: r.id, label: `${r.account_name} · saldo ${formatCurrency(r.balance)}` })) || []}
                                value={cairForm.data.account_id} onChange={(id) => cairForm.setData('account_id', id)} />
                        </div>
                        <InputError message={cairForm.errors.account_id} className="mt-1.5" />
                        <InputError message={cairForm.errors.approval} className="mt-1.5" />
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsCair(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={cairForm.processing} className="justify-center">Setujui &amp; Cairkan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isLpj} onClose={() => setIsLpj(false)} maxWidth="md">
                <form onSubmit={submitLpj} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Laporan Pertanggungjawaban</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Sisa dana dihitung otomatis dari selisihnya.</p>

                    <div className="mt-5 rounded-xl bg-cream-100 p-4 dark:bg-coal-600/40">
                        <p className="text-[11px] uppercase tracking-wider text-stone-400">Dana dicairkan</p>
                        <p className="mt-1 break-words text-lg font-bold text-stone-800 dark:text-cream-100">{formatCurrency(aktif?.nominal_pengajuan)}</p>
                    </div>

                    <div className="mt-4 space-y-4">
                        <div>
                            <InputLabel>Nominal Realisasi (terpakai) <span className="text-red-500">*</span></InputLabel>
                            <TextInput type="text" inputMode="numeric"
                                value={lpjForm.data.nominal_realisasi ? lpjForm.data.nominal_realisasi.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => lpjForm.setData('nominal_realisasi', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" placeholder="0" />
                            <InputError message={lpjForm.errors.nominal_realisasi} className="mt-1.5" />

                            {sisaRealisasi !== null && sisaRealisasi >= 0 && (
                                <p className="mt-2 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">
                                    Sisa dikembalikan: <strong>{formatCurrency(sisaRealisasi)}</strong>
                                    {sisaRealisasi > 0 && <span className="block text-xs opacity-80">Menunggu konfirmasi penerimaan oleh Yayasan.</span>}
                                </p>
                            )}
                        </div>

                        <div>
                            <InputLabel>Bukti / Nota <span className="text-red-500">*</span></InputLabel>
                            <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf"
                                onChange={(e) => lpjForm.setData('bukti_nota', Array.from(e.target.files))}
                                className="mt-2 w-full text-xs text-stone-500 file:mr-3 file:rounded-lg file:border-0 file:bg-claude-500/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-claude-600 hover:file:bg-claude-500/20 dark:text-stone-400 dark:file:text-claude-300" />
                            <p className="mt-1 text-xs text-stone-400">Maksimal 5 berkas · JPG, PNG, PDF · 2 MB per berkas.</p>
                            <InputError message={lpjForm.errors.bukti_nota} className="mt-1.5" />
                            {Object.keys(lpjForm.errors).filter((k) => k.startsWith('bukti_nota.')).map((k) => (
                                <InputError key={k} message={lpjForm.errors[k]} className="mt-1" />
                            ))}
                            {lpjForm.data.bukti_nota.length > 0 && (
                                <ul className="mt-2 list-inside list-disc text-xs text-stone-600 dark:text-stone-300">
                                    {lpjForm.data.bukti_nota.map((f, i) => <li key={i} className="break-all">{f.name}</li>)}
                                </ul>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsLpj(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={lpjForm.processing} className="justify-center">Simpan LPJ</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isBukti} onClose={() => setIsBukti(false)} maxWidth="lg">
                <div className="p-5 sm:p-6">
                    <h2 className="mb-4 text-base font-semibold text-stone-800 dark:text-cream-50">Lampiran</h2>
                    <ul className="max-h-80 space-y-2 overflow-y-auto pr-1">
                        {aktif?.attachments?.map((f) => (
                            <li key={f.id} className="flex items-center justify-between gap-3 rounded-xl bg-cream-100 p-3 dark:bg-coal-600/40">
                                <span className="truncate text-sm text-stone-700 dark:text-stone-200">{f.file_name}</span>
                                <a href={f.url} target="_blank" rel="noreferrer"
                                    className="shrink-0 rounded-lg bg-claude-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-claude-600">Buka</a>
                            </li>
                        ))}
                    </ul>
                </div>
            </Modal>

            <Modal show={dialog !== null} onClose={() => { setDialog(null); setPesanError(''); }} maxWidth="sm">
                {dialog && (() => {
                    const bahaya = dialog.jenis === 'hapus';
                    const judul = { hapus: 'Hapus Pengajuan', approve: 'Konfirmasi Pemeriksaan', refund: 'Konfirmasi Sisa Dana' }[dialog.jenis];
                    const pesan = {
                        hapus: `Hapus pengajuan "${dialog.teks}"?`,
                        approve: `Tandai pengajuan ini sebagai "${dialog.label}"?`,
                        refund: `Konfirmasi bahwa sisa dana ${formatCurrency(aktif?.nominal_refund)} sudah diterima kembali di kas?`,
                    }[dialog.jenis];

                    const jalankan = () => {
                        if (dialog.jenis === 'hapus') aksi(route('keuangan.pengeluaran.pengajuan.destroy', dialog.id), 'delete');
                        else if (dialog.jenis === 'approve') aksi(route('keuangan.pengeluaran.pengajuan.approve', aktif.id), 'post', { stage: dialog.stage });
                        else aksi(route('keuangan.pengeluaran.pengajuan.complete_refund', aktif.id));
                    };

                    return (
                        <div className="p-5 text-center sm:p-6">
                            <div className={`mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full ${bahaya ? 'bg-red-50 text-red-600 dark:bg-red-500/10' : 'bg-claude-500/10 text-claude-600 dark:text-claude-300'}`}>
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d={bahaya
                                        ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                                        : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'} />
                                </svg>
                            </div>
                            <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">{judul}</h3>
                            <p className="mt-1 break-words text-sm text-stone-500 dark:text-stone-400">{pesan}</p>
                            {pesanError && (
                                <div className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{pesanError}</div>
                            )}
                            <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                                <SecondaryButton onClick={() => { setDialog(null); setPesanError(''); }} disabled={proses} className="justify-center">Batal</SecondaryButton>
                                {bahaya ? (
                                    <button onClick={jalankan} disabled={proses}
                                        className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">Ya, Hapus</button>
                                ) : (
                                    <PrimaryButton onClick={jalankan} disabled={proses} className="justify-center">Ya, Lanjutkan</PrimaryButton>
                                )}
                            </div>
                        </div>
                    );
                })()}
            </Modal>
        </KeuanganLayout>
    );
}
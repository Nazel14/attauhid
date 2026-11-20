import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import TombolKembali from '@/Components/TombolKembali';
import { formatCurrency } from '@/Utils/formatCurrency';

const LANGKAH = [
    { id: 'draft',     label: 'Draft',     value: 'Draft' },
    { id: 'diperiksa', label: 'Diperiksa', value: 'Diperiksa' },
    { id: 'final',     label: 'Final',     value: 'Final' },
    { id: 'closed',    label: 'Ditutup',   value: 'Closed' },
];

// Satuan dibuat pilihan, bukan teks bebas — supaya angka tidak mungkin
// nyasar ke kolom ini dan dikira pengali.
const SATUAN = ['Buah', 'Paket', 'Set', 'Rim', 'Orang', 'Siswa', 'Guru', 'Kelas', 'Kegiatan', 'Hari', 'Bulan', 'Tahun', 'Kali'];

// Label tombol + permission untuk memajukan RKAS ke tahap berikutnya.
const AKSI_LANJUT = {
    diperiksa: { label: 'Ajukan Pemeriksaan', perm: 'rkas.approve_diperiksa',    catatan: 'Rincian anggaran terkunci dari perubahan.' },
    final:     { label: 'Finalkan RKAS',      perm: 'rkas.approve_final',        catatan: 'Mata anggaran menjadi tersedia untuk pengajuan pengeluaran.' },
    closed:    { label: 'Tutup RKAS',         perm: 'rkas.approve_tutup_batch',  catatan: 'RKAS ditutup permanen dan tidak bisa dipakai lagi.' },
};

const waktu = (t) => {
    if (!t) return '';
    const d = new Date(t);
    return `${d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })} · ${d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':')}`;
};

export default function DataRKAS({ headers, details, selectedHeaderId, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.statusFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [expandedIds, setExpandedIds] = useState([]);
    const [konfirmasi, setKonfirmasi] = useState(null);
    const [overBudget, setOverBudget] = useState(false);

    const [isHeaderModal, setIsHeaderModal] = useState(false);
    const [isEditHeader, setIsEditHeader] = useState(false);
    const [editHeaderId, setEditHeaderId] = useState(null);
    const [hapusHeaderId, setHapusHeaderId] = useState(null);

    const [isDetailModal, setIsDetailModal] = useState(false);
    const [isEditDetail, setIsEditDetail] = useState(false);
    const [editDetailId, setEditDetailId] = useState(null);

    // Anchor auto-scroll saat tombol "Rincian" ditekan.
    const rincianRef = useRef(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        tahun_ajaran: '', budget_limit: '',
    });

    const formDetail = useForm({
        parent_id: '', nama_kegiatan: '',
        volume_1: '', satuan_1: '', volume_2: '', satuan_2: '', harga_satuan: '',
    });

    const formGenerate = useForm({});

    const header = headers?.data?.find((h) => h.id == selectedHeaderId);
    const draft = header?.status === 'Draft';
    const langkahAktif = header ? LANGKAH.findIndex((s) => s.value === header.status) : 0;

    // Tahap berikutnya yang tersedia untuk RKAS yang sedang dibuka.
    const langkahBerikut = header && langkahAktif >= 0 && langkahAktif < LANGKAH.length - 1
        ? LANGKAH[langkahAktif + 1]
        : null;

    const infoLanjut = langkahBerikut ? AKSI_LANJUT[langkahBerikut.id] : null;
    const belumAdaRincian = langkahBerikut?.id === 'diperiksa' && !details?.length;
    const bisaLanjut = infoLanjut ? can(infoLanjut.perm) && !belumAdaRincian : false;

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.pengeluaran.rkas'),
                { header_id: selectedHeaderId, search, statusFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, statusFilter, sortField, sortDirection]);

    useEffect(() => {
        if (details?.length > 0) {
            setExpandedIds(details.filter((d) => d.is_parent).map((d) => d.id));
        }
    }, [details]);

    // Peringatan hanya sekali per header, bukan tiap kali props berubah.
    const sudahDiperingatkan = useRef(null);
    useEffect(() => {
        if (!header || header.status !== 'Draft') return;
        if (sudahDiperingatkan.current === header.id) return;

        if (parseFloat(header.total_allocated) > parseFloat(header.budget_limit)) {
            sudahDiperingatkan.current = header.id;
            setOverBudget(true);
        }
    }, [header]);

    // Dipanggil dari tombol "Rincian" di kolom Aksi, bukan dari klik baris.
    const pilihHeader = (id) => {
        if (selectedHeaderId == id) {
            rincianRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        router.get(route('keuangan.pengeluaran.rkas'), { header_id: id }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setTimeout(() => rincianRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
            },
        });
    };

    const handleSort = (field) => {
        if (sortField === field) setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        else { setSortField(field); setSortDirection('asc'); }
    };

    const SortIcon = ({ field }) => {
        if (sortField !== field) {
            return <svg className="ml-1 h-3.5 w-3.5 text-stone-400 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>;
        }
        return sortDirection === 'asc'
            ? <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 15l7-7 7 7" /></svg>
            : <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" /></svg>;
    };

    const openAddHeader = () => {
        setIsEditHeader(false); setEditHeaderId(null);
        setData({ tahun_ajaran: '', budget_limit: '' });
        clearErrors(); setIsHeaderModal(true);
    };

    const openEditHeader = (h) => {
        setIsEditHeader(true); setEditHeaderId(h.id);
        setData({ tahun_ajaran: h.tahun_ajaran, budget_limit: Math.floor(h.budget_limit).toString() });
        clearErrors(); setIsHeaderModal(true);
    };

    const submitHeader = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsHeaderModal(false); reset(); }, preserveScroll: true };
        if (isEditHeader) put(route('keuangan.pengeluaran.rkas.update', editHeaderId), opts);
        else post(route('keuangan.pengeluaran.rkas.store'), opts);
    };

    const openAddDetail = (parentId) => {
        setIsEditDetail(false);
        formDetail.setData({ parent_id: parentId, nama_kegiatan: '', volume_1: '', satuan_1: '', volume_2: '', satuan_2: '', harga_satuan: '' });
        formDetail.clearErrors(); setIsDetailModal(true);
    };

    const openEditDetail = (item) => {
        setIsEditDetail(true); setEditDetailId(item.id);
        formDetail.setData({
            parent_id: item.parent_id || '',
            nama_kegiatan: item.nama_kegiatan,
            volume_1: item.volume_1 || '',
            satuan_1: item.satuan_1 || '',
            volume_2: item.volume_2 || '',
            satuan_2: item.satuan_2 || '',
            harga_satuan: Math.floor(item.harga_satuan).toString(),
        });
        formDetail.clearErrors(); setIsDetailModal(true);
    };

    const submitDetail = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => setIsDetailModal(false), preserveScroll: true };
        if (isEditDetail) formDetail.put(route('keuangan.pengeluaran.rkas.update_kegiatan', editDetailId), opts);
        else formDetail.post(route('keuangan.pengeluaran.rkas.store_kegiatan', selectedHeaderId), opts);
    };

    // Nilai input datang sebagai string. Tanpa Number(), volume_2 = "0"
    // dianggap truthy dan dipakai apa adanya → subtotal 0, padahal backend
    // (?: 1) memperlakukannya sebagai 1. Hasil layar dan hasil simpan beda.
    const angka = (v, kosong = 0) => {
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : kosong;
    };

    const vol1 = angka(formDetail.data.volume_1);
    const vol2 = angka(formDetail.data.volume_2, 1);
    const harga = angka(formDetail.data.harga_satuan);
    const subtotal = vol1 * vol2 * harga;

    const badgeStatus = (s) => ({
        'Draft':     'bg-cream-200 text-stone-600 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500',
        'Diperiksa': 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'Final':     'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'Closed':    'bg-coal-800 text-white ring-coal-800 dark:bg-coal-900 dark:ring-coal-600',
    }[s] || 'bg-cream-200 text-stone-600 ring-cream-300');

    const level = (kode) => (kode ? kode.split('.').length : 1);

    const terlihat = (item) => {
        if (!item.parent_id) return true;
        if (!expandedIds.includes(item.parent_id)) return false;
        const p = details.find((d) => d.id === item.parent_id);
        return p ? terlihat(p) : true;
    };

    const barisCls = (item) => {
        const l = level(item.kode_kegiatan);
        if (l === 1) return 'bg-claude-500/15 font-semibold';
        if (l === 2) return 'bg-cream-200/70 font-medium dark:bg-coal-600/50';
        if (item.is_parent) return 'bg-cream-100 dark:bg-coal-600/40';
        return 'hover:bg-cream-50 dark:hover:bg-coal-600/30';
    };

    const pelaku = (step) => {
        if (!header) return null;
        const map = {
            draft:     [header.dibuat_oleh, header.created_at],
            diperiksa: [header.diperiksa_oleh, header.diperiksa_at],
            final:     [header.difinal_oleh, header.final_at],
            closed:    [header.ditutup_oleh, header.closed_at],
        }[step];

        if (!map || !map[0]) return null;

        return (
            <p className="mt-1 text-[10px] leading-tight text-stone-400">
                <span className="font-medium text-stone-600 dark:text-stone-300">{map[0].name}</span><br />
                {waktu(map[1])}
            </p>
        );
    };

    const adaAksiDetail = can('rkas.buat_rincian') || can('rkas.edit_rincian') || can('rkas.hapus_rincian');

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9 dark:[color-scheme:dark]`;

    // Select di dalam modal: tinggi mengikuti TextInput, panah custom.
    // pr-8/pl-3 dipersempit agar label satuan muat di kolom yang sempit.
    const selectModal = 'block h-[42px] w-full min-w-0 cursor-pointer appearance-none truncate rounded-xl border-0 bg-white pl-3 pr-8 text-sm text-stone-800 shadow-sm ring-1 ring-cream-300 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';

    const ChevronBawah = () => (
        <svg className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    // Alias — dipakai di modal, chevron-nya sama persis.
    const ChevronModal = ChevronBawah;

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const IkonMata = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    );

    // Rincian perhitungan dipakai baris tabel dan kartu mobile.
    const Perhitungan = ({ item }) => {
        // `x && ...` dengan angka 0 me-render "0" di layar — pakai > 0.
        // Volume dan satuan dipisah tegas: "1" + "12" sebelumnya
        // terbaca menyatu jadi "112".
        if (item.is_parent || !(Number(item.volume_1) > 0)) return null;

        return (
            <span className="inline-flex flex-wrap items-center gap-1.5">
                <span className="inline-flex items-baseline gap-1 rounded bg-white px-1.5 py-0.5 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                    <span className="font-semibold text-stone-700 dark:text-stone-200">{item.volume_1}</span>
                    {item.satuan_1 && <span className="text-stone-400">{item.satuan_1}</span>}
                </span>
                {Number(item.volume_2) > 0 && (
                    <>
                        <span className="text-stone-400">×</span>
                        <span className="inline-flex items-baseline gap-1 rounded bg-white px-1.5 py-0.5 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                            <span className="font-semibold text-stone-700 dark:text-stone-200">{item.volume_2}</span>
                            {item.satuan_2 && <span className="text-stone-400">{item.satuan_2}</span>}
                        </span>
                    </>
                )}
                <span className="text-stone-400">×</span>
                <span className="font-medium text-stone-700 dark:text-stone-200">{formatCurrency(item.harga_satuan)}</span>
            </span>
        );
    };

    const TombolExpand = ({ item }) => (
        <button onClick={() => setExpandedIds((p) => p.includes(item.id) ? p.filter((x) => x !== item.id) : [...p, item.id])}
            className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-black/10 transition hover:bg-black/20 dark:bg-white/10 dark:hover:bg-white/20">
            <svg className={`h-3 w-3 transition-transform duration-200 ${expandedIds.includes(item.id) ? 'rotate-90' : ''}`}
                fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    );

    const konfirmasiHapusKegiatan = (item) => setKonfirmasi({
        judul: 'Hapus Kegiatan', bahaya: true,
        pesan: `Hapus kegiatan "${item.nama_kegiatan}"?`,
        aksi: () => router.delete(route('keuangan.pengeluaran.rkas.destroy_kegiatan', item.id), {
            preserveScroll: true, onSuccess: () => setKonfirmasi(null),
        }),
    });

    return (
        <KeuanganLayout>
            <Head title="RKAS" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Management RKAS</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Rencana kegiatan dan anggaran sekolah per tahun ajaran.</p>
            </div>

            {/* p-4 di mobile: p-5 menyisakan lebar isi <300px di layar 360px. */}
            <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari tahun ajaran..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="relative sm:w-44">
                            <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Status</option>
                                <option value="Draft">Draft</option>
                                <option value="Diperiksa">Diperiksa</option>
                                <option value="Final">Final</option>
                                <option value="Closed">Ditutup</option>
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {can('rkas.buat_batch') && (
                        <PrimaryButton onClick={openAddHeader} className="h-10 shrink-0 justify-center">Buat RKAS Baru</PrimaryButton>
                    )}
                </div>

                {/* Mobile: kartu. Tabel 5 kolom berisi nominal panjang tidak
                    mungkin muat di 360px tanpa scroll horizontal yang menyiksa. */}
                <div className="space-y-3 sm:hidden">
                    {headers?.data?.length > 0 ? headers.data.map((h) => {
                        const dipilih = selectedHeaderId == h.id;
                        const lewat = parseFloat(h.total_allocated) > parseFloat(h.budget_limit);

                        return (
                            <div key={h.id}
                                className={`rounded-xl p-4 ring-1 ${dipilih
                                    ? 'bg-claude-500/10 ring-claude-500/30'
                                    : 'bg-white ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600'}`}>
                                <div className="flex items-start justify-between gap-2">
                                    <h3 className="text-sm font-bold text-stone-800 dark:text-cream-100">{h.tahun_ajaran}</h3>
                                    <span className={`shrink-0 rounded-full px-2.5 py-1 text-[10px] font-medium ring-1 ${badgeStatus(h.status)}`}>{h.status}</span>
                                </div>

                                <dl className="mt-3 space-y-1.5 text-xs">
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-stone-400">Pagu Anggaran</dt>
                                        <dd className="text-right font-medium text-stone-700 dark:text-stone-200">{formatCurrency(h.budget_limit)}</dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-stone-400">Teralokasi</dt>
                                        <dd className={`text-right font-semibold ${lewat ? 'text-red-600 dark:text-red-400' : 'text-claude-600 dark:text-claude-300'}`}>
                                            {formatCurrency(h.total_allocated)}
                                        </dd>
                                    </div>
                                </dl>

                                <div className="mt-3 flex flex-wrap gap-2 border-t border-cream-200 pt-3 dark:border-coal-600">
                                    <button type="button" onClick={() => pilihHeader(h.id)}
                                        className={`${btnGhost} flex-1 justify-center ${
                                            dipilih
                                                ? 'bg-claude-500 text-white ring-claude-500'
                                                : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                        }`}>
                                        <IkonMata />
                                        Rincian
                                    </button>

                                    {h.status === 'Draft' && (
                                        <>
                                            {can('rkas.edit_batch') && (
                                                <button type="button" onClick={() => openEditHeader(h)}
                                                    className={`${btnGhost} flex-1 justify-center text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                    Edit
                                                </button>
                                            )}
                                            {can('rkas.hapus_batch') && (
                                                <button type="button" onClick={() => setHapusHeaderId(h.id)}
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
                        <p className="rounded-xl bg-cream-100 py-10 text-center text-sm text-stone-500 dark:bg-coal-600/40 dark:text-stone-400">Belum ada data RKAS.</p>
                    )}
                </div>

                {/* Desktop: tabel penuh dengan sorting. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th onClick={() => handleSort('tahun_ajaran')} className={thCls}><div className="flex items-center">Tahun Ajaran <SortIcon field="tahun_ajaran" /></div></th>
                                <th className={thPlain}>Pagu Anggaran</th>
                                <th className={thPlain}>Teralokasi</th>
                                <th onClick={() => handleSort('status')} className={thCls}><div className="flex items-center">Status <SortIcon field="status" /></div></th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {headers?.data?.length > 0 ? headers.data.map((h) => {
                                const dipilih = selectedHeaderId == h.id;
                                return (
                                    <tr key={h.id} className={`transition ${dipilih ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{h.tahun_ajaran}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{formatCurrency(h.budget_limit)}</td>
                                        <td className={`whitespace-nowrap px-5 py-4 text-sm font-medium ${parseFloat(h.total_allocated) > parseFloat(h.budget_limit) ? 'text-red-600 dark:text-red-400' : 'text-claude-600 dark:text-claude-300'}`}>
                                            {formatCurrency(h.total_allocated)}
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeStatus(h.status)}`}>{h.status}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {/* Rincian dibuka lewat tombol, bukan klik baris. */}
                                                <button type="button" onClick={() => pilihHeader(h.id)}
                                                    className={`${btnGhost} ${
                                                        dipilih
                                                            ? 'bg-claude-500 text-white ring-claude-500'
                                                            : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                                    }`}>
                                                    <IkonMata />
                                                    Rincian
                                                </button>

                                                {h.status === 'Draft' && (
                                                    <>
                                                        {can('rkas.edit_batch') && (
                                                            <button type="button" onClick={() => openEditHeader(h)}
                                                                className={`${btnGhost} text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                                Edit
                                                            </button>
                                                        )}
                                                        {can('rkas.hapus_batch') && (
                                                            <button type="button" onClick={() => setHapusHeaderId(h.id)}
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
                                <tr><td colSpan={5} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada data RKAS.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {headers.from || 0}–{headers.to || 0} dari {headers.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {headers.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${link.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <div ref={rincianRef} className="scroll-mt-20" />

            {header && (
                <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <div className="mb-6 border-b border-cream-200 pb-6 dark:border-coal-600">
                        <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Rincian Anggaran · {header.tahun_ajaran}</h2>
                        <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola kerangka kegiatan dan pagu anggarannya.</p>

                        {/* Lingkaran murni indikator — tidak bisa diklik.
                            Di mobile stepper digeser horizontal: 4 langkah ×96px
                            tidak muat di 360px dan akan membungkus berantakan. */}
                        <div className="-mx-4 mt-6 overflow-x-auto px-4 pb-1 sm:mx-0 sm:overflow-visible sm:px-0">
                            <div className="flex min-w-max items-start gap-x-2 gap-y-6 sm:min-w-0 sm:flex-wrap">
                                {LANGKAH.map((step, i) => {
                                    const selesai = i <= langkahAktif;
                                    const berikutnya = i === langkahAktif + 1;

                                    return (
                                        <div key={step.id} className="flex items-start">
                                            <div className="flex w-24 flex-col items-center text-center">
                                                <div className={`flex h-9 w-9 items-center justify-center rounded-full ring-2 ${
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
                                                <div className={`mt-4 h-0.5 w-6 rounded sm:w-10 ${i < langkahAktif ? 'bg-claude-500' : 'bg-cream-300 dark:bg-coal-600'}`} />
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Aksi status sebagai tombol eksplisit. */}
                        <div className="mt-6 rounded-xl bg-cream-100 p-4 dark:bg-coal-600/40">
                            {!langkahBerikut ? (
                                <div className="flex items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
                                    <svg className="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span className="font-medium">RKAS ini sudah ditutup — tidak ada tahap lanjutan.</span>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                                        Tahap berikutnya: <strong className="text-stone-800 dark:text-cream-100">{langkahBerikut.label}</strong>. {infoLanjut?.catatan}
                                        {belumAdaRincian && <><br /><span className="text-amber-600 dark:text-amber-400">Buat kerangka dan rincian kegiatan terlebih dahulu.</span></>}
                                        {!belumAdaRincian && !bisaLanjut && <><br /><span className="text-stone-400">Anda tidak memiliki wewenang untuk tahap ini.</span></>}
                                    </p>
                                    <PrimaryButton
                                        disabled={!bisaLanjut}
                                        onClick={() => setKonfirmasi({
                                            judul: 'Konfirmasi Status',
                                            pesan: `Teruskan RKAS ${header.tahun_ajaran} ke tahap "${langkahBerikut.label}"?`,
                                            bahaya: langkahBerikut.id === 'closed',
                                            aksi: () => router.post(route('keuangan.pengeluaran.rkas.approve', header.id), { stage: langkahBerikut.id }, {
                                                preserveScroll: true, onSuccess: () => setKonfirmasi(null),
                                            }),
                                        })}
                                        className="w-full shrink-0 justify-center disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                                        {infoLanjut?.label || 'Lanjutkan'}
                                    </PrimaryButton>
                                </div>
                            )}
                        </div>
                    </div>

                    {details?.length > 0 ? (
                        <>
                            {/* Mobile: pohon kegiatan sebagai kartu bertingkat.
                                Indentasi dipertahankan tapi dibatasi 12px/level. */}
                            <div className="space-y-2 sm:hidden">
                                {details.filter(terlihat).map((item) => {
                                    const l = level(item.kode_kegiatan);
                                    const indukUtama = l === 1;

                                    return (
                                        <div key={item.id}
                                            style={{ marginLeft: `${Math.min(l - 1, 3) * 12}px` }}
                                            className={`rounded-xl p-3 ring-1 ring-cream-300 dark:ring-coal-600 ${
                                                indukUtama ? 'bg-claude-500/15' : l === 2 ? 'bg-cream-200/70 dark:bg-coal-600/50' : 'bg-white dark:bg-coal-600/40'
                                            }`}>
                                            <div className="flex items-start gap-2">
                                                {item.is_parent ? <TombolExpand item={item} /> : <span className="w-5 shrink-0 text-center text-stone-300">·</span>}
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-[10px] font-medium text-stone-500 dark:text-stone-400">{item.kode_kegiatan}</p>
                                                    <p className={`break-words text-sm text-stone-800 dark:text-cream-100 ${indukUtama || l === 2 ? 'font-semibold' : ''}`}>
                                                        {item.nama_kegiatan}
                                                    </p>
                                                </div>
                                            </div>

                                            {!item.is_parent && (
                                                <div className="mt-2 pl-7 text-xs text-stone-500 dark:text-stone-400">
                                                    <Perhitungan item={item} />
                                                </div>
                                            )}

                                            <div className="mt-2 flex items-center justify-between gap-2 pl-7">
                                                <span className="text-[10px] uppercase tracking-wider text-stone-400">Jumlah</span>
                                                <span className="text-sm font-bold text-stone-800 dark:text-cream-100">{formatCurrency(item.pagu_anggaran)}</span>
                                            </div>

                                            {adaAksiDetail && draft && (
                                                <div className="mt-2.5 flex gap-2 border-t border-cream-300 pt-2.5 pl-7 dark:border-coal-600">
                                                    {item.is_parent ? (
                                                        can('rkas.buat_rincian') && (
                                                            <button onClick={() => openAddDetail(item.id)}
                                                                className="inline-flex flex-1 items-center justify-center gap-1 rounded-lg bg-claude-500 px-2.5 py-1.5 text-xs font-medium text-white transition hover:bg-claude-600">
                                                                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                                Kegiatan
                                                            </button>
                                                        )
                                                    ) : (
                                                        <>
                                                            {can('rkas.edit_rincian') && (
                                                                <button onClick={() => openEditDetail(item)}
                                                                    className={`${btnGhost} flex-1 justify-center bg-white text-stone-600 ring-cream-300 dark:bg-coal-600/40 dark:text-stone-300 dark:ring-coal-600`}>
                                                                    Edit
                                                                </button>
                                                            )}
                                                            {can('rkas.hapus_rincian') && (
                                                                <button onClick={() => konfirmasiHapusKegiatan(item)}
                                                                    className={`${btnGhost} flex-1 justify-center bg-white text-red-600 ring-red-200 dark:bg-coal-600/40 dark:text-red-400 dark:ring-red-500/20`}>
                                                                    Hapus
                                                                </button>
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}

                                <div className="flex items-center justify-between gap-3 rounded-xl bg-coal-800 px-4 py-3 dark:bg-coal-900">
                                    <span className="text-[11px] font-semibold uppercase tracking-wider text-white">Total Teralokasi</span>
                                    <span className={`text-sm font-bold ${parseFloat(header.total_allocated) > parseFloat(header.budget_limit) ? 'text-red-400' : 'text-emerald-400'}`}>
                                        {formatCurrency(header.total_allocated)}
                                    </span>
                                </div>
                            </div>

                            {/* Desktop: tabel pohon. */}
                            <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 sm:block">
                                <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                                    <thead className="bg-coal-800 text-white dark:bg-coal-900">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Kode &amp; Uraian Kegiatan</th>
                                            <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Rincian Perhitungan</th>
                                            <th className="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider">Jumlah</th>
                                            {adaAksiDetail && <th className="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider">Aksi</th>}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                        {details.filter(terlihat).map((item) => (
                                            <tr key={item.id} className={`transition ${barisCls(item)}`}>
                                                <td className="px-4 py-2.5 text-sm text-stone-800 dark:text-cream-100">
                                                    <div className="flex items-center gap-2" style={{ paddingLeft: `${(level(item.kode_kegiatan) - 1) * 20}px` }}>
                                                        {item.is_parent ? <TombolExpand item={item} /> : <span className="w-5 text-center text-stone-300">·</span>}
                                                        <span className="w-14 shrink-0 text-stone-500 dark:text-stone-400">{item.kode_kegiatan}</span>
                                                        <span>{item.nama_kegiatan}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-2.5 text-xs text-stone-500 dark:text-stone-400">
                                                    <Perhitungan item={item} />
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-2.5 text-right text-sm text-stone-800 dark:text-cream-100">{formatCurrency(item.pagu_anggaran)}</td>
                                                {adaAksiDetail && (
                                                    <td className="whitespace-nowrap px-4 py-2.5 text-center text-xs">
                                                        {draft ? (item.is_parent ? (
                                                            can('rkas.buat_rincian') && (
                                                                <button onClick={() => openAddDetail(item.id)}
                                                                    className="inline-flex items-center gap-1 rounded-lg bg-claude-500 px-2.5 py-1.5 font-medium text-white transition hover:bg-claude-600">
                                                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                                    Kegiatan
                                                                </button>
                                                            )
                                                        ) : (
                                                            <div className="flex items-center justify-center gap-2">
                                                                {can('rkas.edit_rincian') && (
                                                                    <button onClick={() => openEditDetail(item)}
                                                                        className={`${btnGhost} text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                                        Edit
                                                                    </button>
                                                                )}
                                                                {can('rkas.hapus_rincian') && (
                                                                    <button onClick={() => konfirmasiHapusKegiatan(item)}
                                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                                        Hapus
                                                                    </button>
                                                                )}
                                                            </div>
                                                        )) : <span className="text-stone-400">–</span>}
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-coal-800 text-white dark:bg-coal-900">
                                        <tr>
                                            <td colSpan="2" className="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider">Total Teralokasi</td>
                                            <td className={`px-4 py-3.5 text-right text-base font-bold ${parseFloat(header.total_allocated) > parseFloat(header.budget_limit) ? 'text-red-400' : 'text-emerald-400'}`}>
                                                {formatCurrency(header.total_allocated)}
                                            </td>
                                            {adaAksiDetail && <td />}
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </>
                    ) : (
                        <div className="rounded-xl bg-amber-50 p-6 text-center ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20 sm:p-8">
                            <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Kerangka RKAS belum dibuat</h3>
                            {can('rkas.buat_rincian') && draft ? (
                                <>
                                    <p className="mx-auto mt-2 max-w-md text-sm text-stone-600 dark:text-stone-300">
                                        Salin kerangka standar dari Master Template RKAS ke tahun ajaran ini.
                                    </p>
                                    <PrimaryButton onClick={() => formGenerate.post(route('keuangan.pengeluaran.rkas.generate_struktur', selectedHeaderId), { preserveScroll: true })}
                                        disabled={formGenerate.processing} className="mt-5 w-full justify-center sm:w-auto">
                                        {formGenerate.processing ? 'Memproses…' : 'Buat Kerangka Standar'}
                                    </PrimaryButton>
                                    {formGenerate.errors.generate && <p className="mt-3 text-sm text-red-600">{formGenerate.errors.generate}</p>}
                                </>
                            ) : (
                                <p className="mt-2 text-sm text-stone-600 dark:text-stone-300">Belum ada rincian anggaran pada RKAS ini.</p>
                            )}
                        </div>
                    )}
                </div>
            )}

            <Modal show={isHeaderModal} onClose={() => setIsHeaderModal(false)} maxWidth="md">
                {/* overflow-y-auto: tinggi maksimum dibatasi Modal (90vh). */}
                <form onSubmit={submitHeader} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditHeader ? 'Edit RKAS' : 'Buat RKAS Baru'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Tahun Ajaran <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={data.tahun_ajaran} onChange={(e) => setData('tahun_ajaran', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: 2026/2027" />
                            <InputError message={errors.tahun_ajaran} className="mt-1.5" />
                        </div>
                        <div>
                            <InputLabel>Pagu Anggaran Maksimal (Rp) <span className="text-red-500">*</span></InputLabel>
                            <TextInput type="text" inputMode="numeric"
                                value={data.budget_limit ? data.budget_limit.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => setData('budget_limit', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" placeholder="0" />
                            <InputError message={errors.budget_limit} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsHeaderModal(false)} className="justify-center">Batal</SecondaryButton>
                        {/* Sebelumnya memakai formGenerate.processing — flag form yang salah. */}
                        <PrimaryButton type="submit" disabled={processing} className="justify-center">Simpan RKAS</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDetailModal} onClose={() => setIsDetailModal(false)} maxWidth="2xl">
                <form onSubmit={submitDetail} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditDetail ? 'Edit Rincian Kegiatan' : 'Tambah Kegiatan'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Nama / Uraian Kegiatan <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={formDetail.data.nama_kegiatan} onChange={(e) => formDetail.setData('nama_kegiatan', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: Pembelian Buku Mutaba'ah" />
                            <InputError message={formDetail.errors.nama_kegiatan} className="mt-1.5" />
                        </div>

                        {/* Satu baris yang terbaca seperti kalimat:
                            [30] [Buah] × [ ] [—] × [Rp 10.000]
                            Satuan berupa dropdown, jadi angka tidak bisa nyasar ke sana.
                            p-3 di mobile agar isi tidak terhimpit. */}
                        <div className="space-y-3 rounded-xl bg-cream-100 p-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600 sm:p-4">
                            {/* Proporsi kolom dibuat eksplisit: 1fr rata membuat select
                                tersisa ~90px dan teksnya terpotong. minmax(0,...)
                                supaya grid boleh menyusut tanpa overflow. */}
                            <div className="grid grid-cols-1 gap-x-3 gap-y-4 sm:grid-cols-[minmax(0,7fr)_auto_minmax(0,7fr)_auto_minmax(0,6fr)] sm:items-start">
                                <div className="min-w-0">
                                    <InputLabel>Jumlah <span className="text-red-500">*</span></InputLabel>
                                    <div className="mt-1.5 flex gap-2">
                                        <TextInput type="number" min="1" inputMode="numeric"
                                            value={formDetail.data.volume_1}
                                            onChange={(e) => formDetail.setData('volume_1', e.target.value)}
                                            className="block w-16 shrink-0 text-center" placeholder="30" />
                                        <div className="relative min-w-0 flex-1">
                                            <select value={formDetail.data.satuan_1}
                                                onChange={(e) => formDetail.setData('satuan_1', e.target.value)}
                                                className={selectModal}>
                                                <option value="">Satuan…</option>
                                                {SATUAN.map((s) => <option key={s} value={s}>{s}</option>)}
                                            </select>
                                            <ChevronModal />
                                        </div>
                                    </div>
                                    <InputError message={formDetail.errors.volume_1} className="mt-1" />
                                    <InputError message={formDetail.errors.satuan_1} className="mt-1" />
                                </div>

                                <span className="hidden pt-9 text-lg font-medium text-stone-400 sm:block">×</span>

                                <div className="min-w-0">
                                    <InputLabel>
                                        Dikali <span className="font-normal text-stone-400">(opsional)</span>
                                    </InputLabel>
                                    <div className="mt-1.5 flex gap-2">
                                        <TextInput type="number" min="1" inputMode="numeric"
                                            value={formDetail.data.volume_2}
                                            onChange={(e) => formDetail.setData('volume_2', e.target.value)}
                                            className="block w-16 shrink-0 text-center" placeholder="—" />
                                        <div className="relative min-w-0 flex-1">
                                            <select value={formDetail.data.satuan_2}
                                                onChange={(e) => formDetail.setData('satuan_2', e.target.value)}
                                                className={selectModal}>
                                                <option value="">Satuan…</option>
                                                {SATUAN.map((s) => <option key={s} value={s}>{s}</option>)}
                                            </select>
                                            <ChevronModal />
                                        </div>
                                    </div>
                                    <InputError message={formDetail.errors.volume_2} className="mt-1" />
                                    <InputError message={formDetail.errors.satuan_2} className="mt-1" />
                                </div>

                                <span className="hidden pt-9 text-lg font-medium text-stone-400 sm:block">×</span>

                                <div className="min-w-0">
                                    <InputLabel>Harga (Rp) <span className="text-red-500">*</span></InputLabel>
                                    <TextInput type="text" inputMode="numeric"
                                        value={formDetail.data.harga_satuan ? formDetail.data.harga_satuan.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                        onChange={(e) => formDetail.setData('harga_satuan', e.target.value.replace(/\D/g, ''))}
                                        className="mt-1.5 block w-full" placeholder="0" />
                                    <InputError message={formDetail.errors.harga_satuan} className="mt-1" />
                                </div>
                            </div>

                            <p className="text-[11px] leading-relaxed text-stone-400">
                                Contoh: 30 Buah × Rp 10.000. Kolom “Dikali” untuk kebutuhan berulang, mis. 12 Bulan × 8 Guru.
                            </p>
                        </div>

                        {/* Mobile: total turun ke baris bawah — nominal panjang
                            akan menabrak rincian bila dipaksa sebaris. */}
                        <div className="flex flex-col gap-2 rounded-xl bg-claude-500/10 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <div className="min-w-0">
                                <span className="block text-[11px] font-semibold uppercase tracking-wider text-claude-700 dark:text-claude-300">Total</span>
                                <span className="mt-0.5 block break-words text-[11px] text-claude-700/70 dark:text-claude-300/70">
                                    {vol1 || 0} {formDetail.data.satuan_1 || 'satuan'}
                                    {angka(formDetail.data.volume_2) > 0 && ` × ${formDetail.data.volume_2} ${formDetail.data.satuan_2 || 'satuan'}`}
                                    {` × ${formatCurrency(harga)}`}
                                </span>
                            </div>
                            <span className="break-words text-lg font-bold text-claude-700 dark:text-claude-200 sm:shrink-0 sm:text-xl">{formatCurrency(subtotal)}</span>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsDetailModal(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formDetail.processing} className="justify-center">Simpan Kegiatan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={hapusHeaderId !== null} onClose={() => setHapusHeaderId(null)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <svg className="mx-auto mb-3 h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus RKAS</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Seluruh rincian anggaran di dalamnya ikut terhapus.</p>
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <SecondaryButton onClick={() => setHapusHeaderId(null)} className="justify-center">Batal</SecondaryButton>
                        <button onClick={() => router.delete(route('keuangan.pengeluaran.rkas.destroy', hapusHeaderId), { preserveScroll: true, onSuccess: () => setHapusHeaderId(null) })}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">Ya, Hapus</button>
                    </div>
                </div>
            </Modal>

            <Modal show={overBudget} onClose={() => setOverBudget(false)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <svg className="mx-auto mb-3 h-12 w-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Melebihi Pagu Anggaran</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Total alokasi sudah melampaui pagu yang ditetapkan. Periksa kembali rincian kegiatan.</p>
                    <div className="mt-5 flex justify-center">
                        <PrimaryButton onClick={() => setOverBudget(false)} className="w-full justify-center sm:w-auto">Mengerti</PrimaryButton>
                    </div>
                </div>
            </Modal>

            <Modal show={konfirmasi !== null} onClose={() => setKonfirmasi(null)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <div className={`mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full ${konfirmasi?.bahaya ? 'bg-red-50 text-red-600 dark:bg-red-500/10' : 'bg-claude-500/10 text-claude-600 dark:text-claude-300'}`}>
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d={konfirmasi?.bahaya
                                ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                                : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'} />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">{konfirmasi?.judul}</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">{konfirmasi?.pesan}</p>
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <SecondaryButton onClick={() => setKonfirmasi(null)} className="justify-center">Batal</SecondaryButton>
                        {konfirmasi?.bahaya ? (
                            <button onClick={konfirmasi.aksi} className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">Ya, Lanjutkan</button>
                        ) : (
                            <PrimaryButton onClick={konfirmasi?.aksi} className="justify-center">Ya, Lanjutkan</PrimaryButton>
                        )}
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
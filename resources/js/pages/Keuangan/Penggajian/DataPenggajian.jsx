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

const BULAN = [
    { id: '01', nama: 'Januari' }, { id: '02', nama: 'Februari' }, { id: '03', nama: 'Maret' },
    { id: '04', nama: 'April' }, { id: '05', nama: 'Mei' }, { id: '06', nama: 'Juni' },
    { id: '07', nama: 'Juli' }, { id: '08', nama: 'Agustus' }, { id: '09', nama: 'September' },
    { id: '10', nama: 'Oktober' }, { id: '11', nama: 'November' }, { id: '12', nama: 'Desember' },
];

const LANGKAH = [
    { id: 'draft',     label: 'Draft' },
    { id: 'diperiksa', label: 'Diperiksa' },
    { id: 'disetujui', label: 'Disetujui' },
    { id: 'dibayar',   label: 'Dibayar' },
];

// Label tombol + permission untuk memajukan penggajian ke tahap berikutnya.
const AKSI_LANJUT = {
    diperiksa: { label: 'Tandai Diperiksa',  perm: 'penggajian.approve_diperiksa', catatan: 'Rincian gaji terkunci dari perubahan.' },
    disetujui: { label: 'Setujui Penggajian', perm: 'penggajian.approve_disetujui', catatan: 'Menunggu pencairan oleh pihak berwenang.' },
    dibayar:   { label: 'Bayarkan Gaji',      perm: 'penggajian.approve_dibayar',   catatan: 'Saldo rekening berkurang dan tercatat di buku kas.' },
};

const TAHUN = Array.from({ length: 5 }, (_, i) => (new Date().getFullYear() - 1 + i).toString());

const waktu = (t) => {
    if (!t) return '';
    const d = new Date(t);
    return `${d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })} · ${d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':')}`;
};

export default function DataPenggajian({ headers, details, selectedHeaderId, filters, availableEmployees, rekening_options }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [tahun, setTahun] = useState(filters?.tahun || new Date().getFullYear().toString());
    const [bulan, setBulan] = useState(filters?.bulan || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');
    const [searchDetail, setSearchDetail] = useState(filters?.search_detail || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const header = headers?.data?.find((h) => h.id == selectedHeaderId);

    const [isGenerate, setIsGenerate] = useState(false);
    const [isTambah, setIsTambah] = useState(false);
    const [isEditDetail, setIsEditDetail] = useState(false);
    const [isBayar, setIsBayar] = useState(false);
    const [detailAktif, setDetailAktif] = useState(null);
    const [dialog, setDialog] = useState(null);
    const [pesanError, setPesanError] = useState('');
    const [proses, setProses] = useState(false);

    // Anchor auto-scroll saat tombol "Rincian" ditekan.
    const rincianRef = useRef(null);

    const now = new Date();
    const formGenerate = useForm({
        periode_bulan: String(now.getMonth() + 1).padStart(2, '0'),
        periode_tahun: now.getFullYear().toString(),
    });

    const formTambah = useForm({ payroll_header_id: selectedHeaderId || '', employee_id: '', gaji_pokok: '' });
    const formEdit = useForm({ gaji_pokok: '' });
    const formBayar = useForm({ stage: 'dibayar', account_id: '' });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.penggajian.data'), {
                header_id: selectedHeaderId, tahun, bulan, status: statusFilter,
                search_detail: searchDetail, sort_field: sortField, sort_direction: sortDirection,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [tahun, bulan, statusFilter, searchDetail, sortField, sortDirection]);

    // Dipanggil dari tombol "Rincian" di kolom Aksi, bukan dari klik baris.
    const pilihHeader = (id) => {
        if (selectedHeaderId == id) {
            rincianRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        setSearchDetail('');
        router.get(route('keuangan.penggajian.data'), { tahun, bulan, status: statusFilter, header_id: id }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setTimeout(() => rincianRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
            },
        });
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

    const submitGenerate = (e) => {
        e.preventDefault();
        formGenerate.post(route('keuangan.penggajian.generate'), {
            onSuccess: () => setIsGenerate(false), preserveScroll: true,
        });
    };

    const pilihPegawai = (id) => {
        const p = availableEmployees.find((x) => x.id === id);
        formTambah.setData({
            payroll_header_id: selectedHeaderId,
            employee_id: id,
            gaji_pokok: Math.floor(p?.jabatan?.gaji_pokok_default || 0).toString(),
        });
    };

    const submitTambah = (e) => {
        e.preventDefault();
        formTambah.post(route('keuangan.penggajian.store_detail'), {
            onSuccess: () => { setIsTambah(false); formTambah.reset(); }, preserveScroll: true,
        });
    };

    const submitEdit = (e) => {
        e.preventDefault();
        formEdit.put(route('keuangan.penggajian.update_detail', detailAktif.id), {
            onSuccess: () => setIsEditDetail(false), preserveScroll: true,
        });
    };

    const submitBayar = (e) => {
        e.preventDefault();
        formBayar.post(route('keuangan.penggajian.approve', selectedHeaderId), {
            onSuccess: () => setIsBayar(false), preserveScroll: true,
        });
    };

    const aksi = (url, method = 'post', body = {}) => {
        setProses(true);
        const opts = {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => { setDialog(null); setPesanError(''); },
            onError: (err) => setPesanError(err.approve || err.status || 'Gagal memproses.'),
        };
        if (method === 'delete') router.delete(url, opts);
        else router.post(url, body, opts);
    };

    const badgeStatus = (s) => ({
        'draft':     'bg-stone-100 text-stone-600 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500',
        'diperiksa': 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'disetujui': 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'dibayar':   'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
    }[s] || 'bg-stone-100 text-stone-600 ring-stone-200');

    const langkahAktif = header ? LANGKAH.findIndex((s) => s.id === header.status_approval) : 0;

    // Tahap berikutnya untuk periode yang sedang dibuka.
    const langkahBerikut = header && langkahAktif >= 0 && langkahAktif < LANGKAH.length - 1
        ? LANGKAH[langkahAktif + 1]
        : null;

    const infoLanjut = langkahBerikut ? AKSI_LANJUT[langkahBerikut.id] : null;
    const belumAdaRincian = langkahBerikut?.id === 'diperiksa' && !(details?.total > 0);
    const bisaLanjut = infoLanjut ? can(infoLanjut.perm) && !belumAdaRincian : false;

    const pelaku = (step) => {
        if (!header) return null;
        const map = {
            draft:     [header.pembuat, header.created_at],
            diperiksa: [header.diperiksa_oleh, header.diperiksa_at],
            disetujui: [header.penyetuju, header.disetujui_at],
            dibayar:   [header.pembayar, header.dibayar_at],
        }[step];

        if (!map || !map[0]) return null;

        return (
            <p className="mt-1 text-[10px] leading-tight text-stone-400">
                <span className="font-medium text-stone-600 dark:text-stone-300">{map[0].name}</span><br />{waktu(map[1])}
            </p>
        );
    };

    const draft = header?.status_approval === 'draft';
    const adaAksiDetail = can('penggajian.edit_rincian') || can('penggajian.hapus_rincian');

    const namaPeriode = (h) => `${BULAN.find((b) => b.id === h?.periode_bulan)?.nama || ''} ${h?.periode_tahun || ''}`.trim();

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700';

    const ChevronBawah = ({ modal = false }) => (
        <svg className={`pointer-events-none absolute ${modal ? 'right-3.5 top-[calc(50%+3px)]' : 'right-3 top-1/2'} h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500`}
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const IkonMata = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    );

    const IkonPrinter = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-12h6a1 1 0 011 1v3H8V6a1 1 0 011-1zm-1 9h8a1 1 0 011 1v4a1 1 0 01-1 1H8a1 1 0 01-1-1v-4a1 1 0 011-1z" />
        </svg>
    );

    return (
        <KeuanganLayout>
            <Head title="Penggajian" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Management Penggajian</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Perhitungan gaji bulanan berdasarkan absensi dan komponen gaji pegawai.</p>
            </div>

            <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    {/* Tahun & bulan berdampingan di mobile — keduanya pendek,
                        memaksa full-width membuang dua baris. */}
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="flex gap-2">
                            <div className="relative flex-1 sm:w-32 sm:flex-none">
                                <select value={tahun} onChange={(e) => setTahun(e.target.value)} className={selectCls}>
                                    {TAHUN.map((y) => <option key={y} value={y}>{y}</option>)}
                                </select>
                                <ChevronBawah />
                            </div>
                            <div className="relative flex-1 sm:w-44 sm:flex-none">
                                <select value={bulan} onChange={(e) => setBulan(e.target.value)} className={selectCls}>
                                    <option value="">Semua Bulan</option>
                                    {BULAN.map((b) => <option key={b.id} value={b.id}>{b.nama}</option>)}
                                </select>
                                <ChevronBawah />
                            </div>
                        </div>
                        <div className="relative sm:w-44">
                            <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Status</option>
                                {LANGKAH.map((s) => <option key={s.id} value={s.id}>{s.label}</option>)}
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {can('penggajian.generate_batch') && (
                        <PrimaryButton onClick={() => { formGenerate.clearErrors(); setIsGenerate(true); }} className="h-10 shrink-0 justify-center">
                            Generate Penggajian
                        </PrimaryButton>
                    )}
                </div>

                {/* Mobile: kartu periode. */}
                <div className="space-y-3 sm:hidden">
                    {headers?.data?.length > 0 ? headers.data.map((h) => {
                        const dipilih = selectedHeaderId == h.id;
                        const periode = namaPeriode(h);

                        return (
                            <div key={h.id}
                                className={`rounded-xl p-4 ring-1 ${dipilih
                                    ? 'bg-claude-500/10 ring-claude-500/30'
                                    : 'bg-white ring-stone-200 dark:bg-coal-800/40 dark:ring-coal-600'}`}>
                                <div className="flex items-start justify-between gap-2">
                                    <h3 className="text-sm font-bold text-stone-800 dark:text-cream-100">{periode}</h3>
                                    <span className={`shrink-0 rounded-full px-2.5 py-1 text-[10px] font-medium capitalize ring-1 ${badgeStatus(h.status_approval)}`}>{h.status_approval}</span>
                                </div>

                                <dl className="mt-3 space-y-1.5 text-xs">
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-stone-400">Gaji Pokok</dt>
                                        <dd className="text-right font-medium text-stone-700 dark:text-stone-200">{formatCurrency(h.total_gaji_pokok)}</dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-stone-400">Tunjangan</dt>
                                        <dd className="text-right font-medium text-emerald-600 dark:text-emerald-400">+ {formatCurrency(h.total_tunjangan)}</dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-stone-400">Potongan</dt>
                                        <dd className="text-right font-medium text-red-600 dark:text-red-400">− {formatCurrency(h.total_potongan)}</dd>
                                    </div>
                                    {h.total_tambahan > 0 && (
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-stone-400">Subsidi</dt>
                                            <dd className="text-right font-medium text-blue-600 dark:text-blue-400">+ {formatCurrency(h.total_tambahan)}</dd>
                                        </div>
                                    )}
                                    <div className="flex justify-between gap-3 border-t border-stone-100 pt-1.5 dark:border-coal-600">
                                        <dt className="font-medium text-stone-500 dark:text-stone-300">Grand Total</dt>
                                        <dd className="text-right text-sm font-bold text-claude-600 dark:text-claude-300">{formatCurrency(h.grand_total)}</dd>
                                    </div>
                                </dl>

                                <div className="mt-3 flex flex-wrap gap-2 border-t border-stone-100 pt-3 dark:border-coal-600">
                                    <button type="button" onClick={() => pilihHeader(h.id)}
                                        className={`${btnGhost} flex-1 justify-center ${
                                            dipilih
                                                ? 'bg-claude-500 text-white ring-claude-500'
                                                : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                        }`}>
                                        <IkonMata />
                                        Rincian
                                    </button>

                                    {h.status_approval === 'dibayar' && can('penggajian.cetak') && (
                                        <a href={route('keuangan.penggajian.print_bulk', h.id)} target="_blank" rel="noreferrer"
                                            className={`${btnGhost} flex-1 justify-center text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600`}>
                                            <IkonPrinter />
                                            Slip
                                        </a>
                                    )}

                                    {h.status_approval === 'draft' && can('penggajian.hapus_batch') && (
                                        <button type="button"
                                            onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-header', id: h.id, periode }); }}
                                            className={`${btnGhost} flex-1 justify-center text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20`}>
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    }) : (
                        <p className="rounded-xl bg-stone-50 py-10 text-center text-sm text-stone-500 dark:bg-coal-800 dark:text-stone-400">Belum ada periode penggajian.</p>
                    )}
                </div>

                {/* Desktop: tabel. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('periode_bulan')} className={thCls}><div className="flex items-center">Periode <SortIcon field="periode_bulan" /></div></th>
                                <th className={thPlain}>Gaji Pokok</th>
                                <th className={thPlain}>Tunjangan / Potongan</th>
                                <th onClick={() => handleSort('grand_total')} className={thCls}><div className="flex items-center">Grand Total <SortIcon field="grand_total" /></div></th>
                                <th onClick={() => handleSort('status_approval')} className={thCls}><div className="flex items-center">Status <SortIcon field="status_approval" /></div></th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {headers?.data?.length > 0 ? headers.data.map((h) => {
                                const dipilih = selectedHeaderId == h.id;
                                const periode = namaPeriode(h);

                                return (
                                    <tr key={h.id} className={`transition ${dipilih ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{periode}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{formatCurrency(h.total_gaji_pokok)}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-xs">
                                            <span className="text-emerald-600 dark:text-emerald-400">+ {formatCurrency(h.total_tunjangan)}</span><br />
                                            <span className="text-red-600 dark:text-red-400">− {formatCurrency(h.total_potongan)}</span>
                                            {h.total_tambahan > 0 && (
                                                <><br /><span className="text-blue-600 dark:text-blue-400">+ {formatCurrency(h.total_tambahan)} subsidi</span></>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-claude-600 dark:text-claude-300">{formatCurrency(h.grand_total)}</td>
                                        <td className="whitespace-nowrap px-5 py-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ${badgeStatus(h.status_approval)}`}>{h.status_approval}</span>
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

                                                {h.status_approval === 'dibayar' && can('penggajian.cetak') && (
                                                    <a href={route('keuangan.penggajian.print_bulk', h.id)} target="_blank" rel="noreferrer"
                                                        title={`Cetak slip gaji ${periode}`}
                                                        className={`${btnGhost} text-stone-600 ring-stone-200 hover:bg-cream-100 hover:text-stone-800 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                        <IkonPrinter />
                                                        Slip
                                                    </a>
                                                )}

                                                {h.status_approval === 'draft' && can('penggajian.hapus_batch') && (
                                                    <button type="button"
                                                        onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-header', id: h.id, periode }); }}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan="6" className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada periode penggajian.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {headers.from || 0}–{headers.to || 0} dari {headers.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {headers.links.map((l, i) => (
                            <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <div ref={rincianRef} className="scroll-mt-20" />

            {header && details && (
                <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <div className="mb-6 border-b border-stone-100 pb-6 dark:border-coal-600">
                        <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                            Rincian Gaji · {namaPeriode(header)}
                        </h2>
                        <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Slip per pegawai dan alur persetujuannya.</p>

                        {/* Lingkaran murni indikator. Digeser horizontal di mobile. */}
                        <div className="-mx-4 mt-6 overflow-x-auto px-4 pb-1 sm:mx-0 sm:overflow-visible sm:px-0">
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
                                                    : 'bg-stone-50 text-stone-400 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600'
                                                }`}>
                                                    {selesai
                                                        ? <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                        : <span className="text-sm font-semibold">{i + 1}</span>}
                                                </div>
                                                <span className={`mt-2 text-xs font-medium ${selesai || berikutnya ? 'text-stone-800 dark:text-cream-100' : 'text-stone-400'}`}>{step.label}</span>
                                                {selesai && pelaku(step.id)}
                                            </div>
                                            {i < LANGKAH.length - 1 && (
                                                <div className={`mt-5 h-0.5 w-6 rounded sm:w-12 ${i < langkahAktif ? 'bg-claude-500' : 'bg-stone-200 dark:bg-coal-600'}`} />
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Aksi persetujuan sebagai tombol eksplisit. */}
                        <div className="mt-6 rounded-xl bg-cream-100 p-4 dark:bg-coal-900/50">
                            {!langkahBerikut ? (
                                <div className="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                                    <svg className="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span className="font-medium">Gaji sudah dibayarkan dan tercatat di buku kas.</span>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                                        Tahap berikutnya: <strong className="text-stone-800 dark:text-cream-100">{langkahBerikut.label}</strong>. {infoLanjut?.catatan}
                                        {belumAdaRincian && <><br /><span className="text-amber-600 dark:text-amber-400">Belum ada rincian pegawai pada periode ini.</span></>}
                                        {!belumAdaRincian && !bisaLanjut && <><br /><span className="text-stone-400">Anda tidak memiliki wewenang untuk tahap ini.</span></>}
                                    </p>
                                    <PrimaryButton
                                        disabled={!bisaLanjut || proses}
                                        onClick={() => {
                                            setPesanError('');
                                            if (langkahBerikut.id === 'dibayar') {
                                                formBayar.reset(); formBayar.clearErrors();
                                                formBayar.setData('stage', 'dibayar');
                                                setIsBayar(true);
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

                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <input type="text" value={searchDetail} onChange={(e) => setSearchDetail(e.target.value)}
                                placeholder="Cari nama pegawai..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            {draft && availableEmployees?.length > 0 && can('penggajian.buat_rincian') && (
                                <SecondaryButton className="justify-center"
                                    onClick={() => { formTambah.reset(); formTambah.clearErrors(); formTambah.setData('payroll_header_id', selectedHeaderId); setIsTambah(true); }}>
                                    Tambah Pegawai
                                </SecondaryButton>
                            )}
                            {header.status_approval === 'dibayar' && can('penggajian.cetak') && (
                                <a href={route('keuangan.penggajian.print_bulk', header.id)} target="_blank" rel="noreferrer"
                                    className="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-stone-700 ring-1 ring-stone-200 transition hover:bg-stone-50 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600">
                                    Cetak Slip Gaji
                                </a>
                            )}
                        </div>
                    </div>

                    {/* Mobile: satu kartu slip per pegawai. */}
                    <div className="space-y-3 sm:hidden">
                        {details.data.length > 0 ? details.data.map((d) => (
                            <div key={d.id} className="rounded-xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-800/40 dark:ring-coal-600">
                                <div>
                                    <p className="text-sm font-semibold text-stone-800 dark:text-cream-100">{d.employee?.name}</p>
                                    <p className="text-xs text-stone-400">{d.employee?.jabatan?.nama_jabatan || '—'}</p>
                                </div>

                                <div className="mt-3 flex justify-between gap-3 text-xs">
                                    <span className="text-stone-400">Gaji Pokok</span>
                                    <span className="font-medium text-stone-700 dark:text-stone-200">{formatCurrency(d.gaji_pokok)}</span>
                                </div>

                                {d.allowances?.length > 0 && (
                                    <ul className="mt-2 space-y-1 text-xs text-emerald-600 dark:text-emerald-400">
                                        {d.allowances.map((a) => (
                                            <li key={a.id} className="flex justify-between gap-3">
                                                <span className="min-w-0 truncate">{a.nama_tunjangan_snapshot}</span>
                                                <span className="shrink-0 font-medium">+ {formatCurrency(a.nominal_snapshot)}</span>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {d.deductions?.length > 0 && (
                                    <ul className="mt-2 space-y-1 text-xs text-red-600 dark:text-red-400">
                                        {d.deductions.map((x) => (
                                            <li key={x.id} className="flex justify-between gap-3">
                                                <span className="min-w-0 truncate">{x.nama_potongan_snapshot}</span>
                                                <span className="shrink-0 font-medium">− {formatCurrency(x.nominal_snapshot)}</span>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {d.nominal_tambahan > 0 && (
                                    <div className="mt-2 flex justify-between gap-3 text-xs text-blue-600 dark:text-blue-400">
                                        <span className="min-w-0 truncate">{d.keterangan_tambahan || 'Tambahan'}</span>
                                        <span className="shrink-0 font-medium">+ {formatCurrency(d.nominal_tambahan)}</span>
                                    </div>
                                )}

                                <div className="mt-3 flex items-center justify-between gap-3 border-t border-stone-100 pt-3 dark:border-coal-600">
                                    <span className="text-[10px] font-semibold uppercase tracking-wider text-stone-400">Take Home Pay</span>
                                    <span className="text-sm font-bold text-claude-600 dark:text-claude-300">{formatCurrency(d.take_home_pay)}</span>
                                </div>

                                {adaAksiDetail && draft && (
                                    <div className="mt-3 flex gap-2">
                                        {can('penggajian.edit_rincian') && (
                                            <button onClick={() => { setDetailAktif(d); formEdit.setData('gaji_pokok', Math.floor(d.gaji_pokok).toString()); formEdit.clearErrors(); setIsEditDetail(true); }}
                                                className={`${btnGhost} flex-1 justify-center text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600`}>
                                                Edit
                                            </button>
                                        )}
                                        {can('penggajian.hapus_rincian') && (
                                            <button onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-detail', id: d.id, nama: d.employee?.name }); }}
                                                className={`${btnGhost} flex-1 justify-center text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20`}>
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>
                        )) : (
                            <p className="rounded-xl bg-stone-50 py-10 text-center text-sm text-stone-500 dark:bg-coal-800 dark:text-stone-400">Belum ada rincian pegawai.</p>
                        )}
                    </div>

                    {/* Desktop: tabel slip. */}
                    <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 sm:block">
                        <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                            <thead className="bg-stone-800 text-white dark:bg-coal-900">
                                <tr>
                                    <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Pegawai</th>
                                    <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Gaji Pokok</th>
                                    <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Tunjangan</th>
                                    <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Potongan</th>
                                    <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Tambahan</th>
                                    <th className="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider">Take Home Pay</th>
                                    {adaAksiDetail && <th className="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider">Aksi</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                {details.data.length > 0 ? details.data.map((d) => (
                                    <tr key={d.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                        <td className="px-4 py-3 text-sm">
                                            <span className="font-medium text-stone-800 dark:text-cream-100">{d.employee?.name}</span><br />
                                            <span className="text-xs text-stone-400">{d.employee?.jabatan?.nama_jabatan || '—'}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-stone-700 dark:text-stone-200">{formatCurrency(d.gaji_pokok)}</td>
                                        <td className="px-4 py-3 text-xs">
                                            {d.allowances?.length > 0 ? (
                                                <ul className="space-y-1 text-emerald-600 dark:text-emerald-400">
                                                    {d.allowances.map((a) => (
                                                        <li key={a.id} className="flex justify-between gap-3">
                                                            <span className="truncate">{a.nama_tunjangan_snapshot}</span>
                                                            <span className="shrink-0 font-medium">{formatCurrency(a.nominal_snapshot)}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : <span className="text-stone-400">—</span>}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {d.deductions?.length > 0 ? (
                                                <ul className="space-y-1 text-red-600 dark:text-red-400">
                                                    {d.deductions.map((x) => (
                                                        <li key={x.id} className="flex justify-between gap-3">
                                                            <span className="truncate">{x.nama_potongan_snapshot}</span>
                                                            <span className="shrink-0 font-medium">{formatCurrency(x.nominal_snapshot)}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : <span className="text-stone-400">—</span>}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {d.nominal_tambahan > 0 ? (
                                                <span className="text-blue-600 dark:text-blue-400">
                                                    {d.keterangan_tambahan || 'Tambahan'}<br />
                                                    <span className="font-medium">+ {formatCurrency(d.nominal_tambahan)}</span>
                                                </span>
                                            ) : <span className="text-stone-400">—</span>}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-claude-600 dark:text-claude-300">{formatCurrency(d.take_home_pay)}</td>
                                        {adaAksiDetail && (
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-xs">
                                                {draft ? (
                                                    <div className="flex items-center justify-center gap-2">
                                                        {can('penggajian.edit_rincian') && (
                                                            <button onClick={() => { setDetailAktif(d); formEdit.setData('gaji_pokok', Math.floor(d.gaji_pokok).toString()); formEdit.clearErrors(); setIsEditDetail(true); }}
                                                                className={`${btnGhost} text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                                Edit
                                                            </button>
                                                        )}
                                                        {can('penggajian.hapus_rincian') && (
                                                            <button onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-detail', id: d.id, nama: d.employee?.name }); }}
                                                                className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                                Hapus
                                                            </button>
                                                        )}
                                                    </div>
                                                ) : <span className="text-stone-400">—</span>}
                                            </td>
                                        )}
                                    </tr>
                                )) : (
                                    <tr><td colSpan={adaAksiDetail ? 7 : 6} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada rincian pegawai.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {details.data.length > 0 && (
                        <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                            <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {details.from || 0}–{details.to || 0} dari {details.total} entri</p>
                            <div className="flex flex-wrap justify-center gap-1">
                                {details.links.map((l, i) => (
                                    <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                        className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                        dangerouslySetInnerHTML={{ __html: l.label }} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            <Modal show={isGenerate} onClose={() => setIsGenerate(false)} maxWidth="sm">
                <form onSubmit={submitGenerate} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Generate Penggajian</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Semua pegawai aktif ditarik beserta tunjangan, potongan, dan absensinya pada periode ini.
                    </p>

                    <div className="mt-5 grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel>Bulan</InputLabel>
                            <div className="relative">
                                <select value={formGenerate.data.periode_bulan} onChange={(e) => formGenerate.setData('periode_bulan', e.target.value)} className={selectModal}>
                                    {BULAN.map((b) => <option key={b.id} value={b.id}>{b.nama}</option>)}
                                </select>
                                <ChevronBawah modal />
                            </div>
                            <InputError message={formGenerate.errors.periode_bulan} className="mt-1.5" />
                        </div>
                        <div>
                            <InputLabel>Tahun</InputLabel>
                            <div className="relative">
                                <select value={formGenerate.data.periode_tahun} onChange={(e) => formGenerate.setData('periode_tahun', e.target.value)} className={selectModal}>
                                    {TAHUN.map((y) => <option key={y} value={y}>{y}</option>)}
                                </select>
                                <ChevronBawah modal />
                            </div>
                            <InputError message={formGenerate.errors.periode_tahun} className="mt-1.5" />
                        </div>
                    </div>

                    <InputError message={formGenerate.errors.generate} className="mt-3" />

                    <p className="mt-4 rounded-xl bg-cream-100 p-3 text-xs leading-relaxed text-stone-600 dark:bg-coal-900/50 dark:text-stone-300">
                        Pastikan absensi periode ini sudah lengkap — komponen berjenis <strong>Per Kehadiran</strong> dihitung dari data absensi.
                    </p>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsGenerate(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formGenerate.processing} className="justify-center">
                            {formGenerate.processing ? 'Memproses…' : 'Generate'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isTambah} onClose={() => setIsTambah(false)} maxWidth="md">
                <form onSubmit={submitTambah} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Tambah Pegawai ke Penggajian</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tunjangan dan potongan ikut ditarik otomatis dari pengaturan gaji pegawai.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Pegawai <span className="text-red-500">*</span></InputLabel>
                            <div className="mt-1.5">
                                <SearchableSelect placeholder="-- Pilih pegawai --"
                                    options={availableEmployees?.map((p) => ({
                                        id: p.id,
                                        label: `${p.name}${p.nip ? ` · ${p.nip}` : ''}`,
                                    })) || []}
                                    value={formTambah.data.employee_id}
                                    onChange={pilihPegawai} />
                            </div>
                            <InputError message={formTambah.errors.employee_id} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel>Gaji Pokok (Rp) <span className="text-red-500">*</span></InputLabel>
                            <TextInput type="text" inputMode="numeric"
                                value={formTambah.data.gaji_pokok ? formTambah.data.gaji_pokok.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => formTambah.setData('gaji_pokok', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" placeholder="0" />
                            <p className="mt-1 text-xs text-stone-400">Terisi otomatis dari gaji pokok jabatannya; ubah bila perlu.</p>
                            <InputError message={formTambah.errors.gaji_pokok} className="mt-1.5" />
                        </div>
                    </div>

                    <InputError message={formTambah.errors.store_detail} className="mt-3" />

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsTambah(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formTambah.processing} className="justify-center">Tambahkan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isEditDetail} onClose={() => setIsEditDetail(false)} maxWidth="md">
                <form onSubmit={submitEdit} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Ubah Gaji Pokok</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">{detailAktif?.employee?.name}</p>

                    <div className="mt-5">
                        <InputLabel>Gaji Pokok (Rp) <span className="text-red-500">*</span></InputLabel>
                        <TextInput type="text" inputMode="numeric"
                            value={formEdit.data.gaji_pokok ? formEdit.data.gaji_pokok.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                            onChange={(e) => formEdit.setData('gaji_pokok', e.target.value.replace(/\D/g, ''))}
                            className="mt-1.5 block w-full" autoFocus />
                        <InputError message={formEdit.errors.gaji_pokok} className="mt-1.5" />
                    </div>

                    <p className="mt-4 rounded-xl bg-cream-100 p-3 text-xs leading-relaxed text-stone-600 dark:bg-coal-900/50 dark:text-stone-300">
                        Tunjangan dan potongan tidak berubah — keduanya sudah tersimpan sebagai salinan saat penggajian dibuat.
                    </p>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsEditDetail(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formEdit.processing} className="justify-center">Simpan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isBayar} onClose={() => setIsBayar(false)} maxWidth="md">
                <form onSubmit={submitBayar} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Bayarkan Gaji</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Saldo rekening berkurang dan tercatat permanen di buku kas.</p>

                    <div className="mt-5 rounded-xl bg-stone-50 p-4 dark:bg-coal-900/50">
                        <p className="text-[11px] uppercase tracking-wider text-stone-400">Total dibayarkan</p>
                        <p className="mt-1 break-words text-lg font-bold text-stone-800 dark:text-cream-100">{formatCurrency(header?.grand_total)}</p>
                        <p className="mt-1 text-xs text-stone-400">
                            {namaPeriode(header)} · {details?.total || 0} pegawai
                        </p>
                    </div>

                    <div className="mt-4">
                        <InputLabel>Sumber Dana <span className="text-red-500">*</span></InputLabel>
                        <div className="mt-1.5">
                            <SearchableSelect placeholder="-- Pilih rekening/kas --"
                                options={rekening_options?.map((r) => ({ id: r.id, label: `${r.account_name} · saldo ${formatCurrency(r.balance)}` })) || []}
                                value={formBayar.data.account_id}
                                onChange={(id) => formBayar.setData('account_id', id)} />
                        </div>
                        <InputError message={formBayar.errors.account_id} className="mt-1.5" />
                        <InputError message={formBayar.errors.approve} className="mt-1.5" />
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsBayar(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formBayar.processing} className="justify-center">Bayarkan Sekarang</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={dialog !== null} onClose={() => { setDialog(null); setPesanError(''); }} maxWidth="sm">
                {dialog && (() => {
                    const bahaya = dialog.jenis.startsWith('hapus');

                    const judul = {
                        'hapus-header': 'Hapus Periode Penggajian',
                        'hapus-detail': 'Keluarkan Pegawai',
                        'approve':      'Konfirmasi Persetujuan',
                    }[dialog.jenis];

                    const pesan = {
                        'hapus-header': `Hapus periode ${dialog.periode} beserta seluruh rincian gajinya?`,
                        'hapus-detail': `Keluarkan ${dialog.nama} dari daftar gaji periode ini?`,
                        'approve':      `Tandai penggajian ini sebagai "${dialog.label}"?`,
                    }[dialog.jenis];

                    const jalankan = () => {
                        if (dialog.jenis === 'hapus-header') aksi(route('keuangan.penggajian.destroy_header', dialog.id), 'delete');
                        else if (dialog.jenis === 'hapus-detail') aksi(route('keuangan.penggajian.destroy_detail', dialog.id), 'delete');
                        else aksi(route('keuangan.penggajian.approve', selectedHeaderId), 'post', { stage: dialog.stage });
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
                                        className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">Ya, Lanjutkan</button>
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
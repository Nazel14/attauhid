import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import SearchableSelect from '@/Components/SearchableSelect';
import { formatCurrency } from '@/Utils/formatCurrency';

// Cermin dari Tagihan::ALASAN_BATAL di sisi PHP. Kunci harus sama persis;
// validasi server menolak nilai di luar daftar itu.
const ALASAN_BATAL = [
    ['salah_input',   'Salah input'],
    ['santri_keluar', 'Santri keluar atau pindah'],
    ['keringanan',    'Keringanan / dibebaskan'],
    ['lainnya',       'Lainnya'],
];

const tanggal = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-');

export default function KelolaTagihan({ headers, details, selectedHeaderId, summary, listSiswa, listTarif, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    // Modul diport bertahap — route pembayaran & laporan belum ada.
    const has = (n) => { try { return route().has(n); } catch (e) { return false; } };

    const [searchHeader, setSearchHeader] = useState(filters?.search_header || '');
    const [monthHeader, setMonthHeader] = useState(filters?.month_header || '');
    const [searchDetail, setSearchDetail] = useState(filters?.search_detail || '');
    const [statusDetail, setStatusDetail] = useState(filters?.status_detail || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [selectedInvoiceId, setSelectedInvoiceId] = useState(null);
    const invoice = details?.data?.find((d) => d.id === selectedInvoiceId);
    const header = headers?.data?.find((h) => h.id == selectedHeaderId);

    const [isHeaderModal, setIsHeaderModal] = useState(false);
    const [isEditHeader, setIsEditHeader] = useState(false);
    const [editHeaderId, setEditHeaderId] = useState(null);
    const [isDetailModal, setIsDetailModal] = useState(false);
    const [isEditDetail, setIsEditDetail] = useState(false);
    const [editDetailId, setEditDetailId] = useState(null);
    const [dialog, setDialog] = useState(null);
    const [pesanError, setPesanError] = useState('');
    const [proses, setProses] = useState(false);

    const [isBatal, setIsBatal] = useState(false);
    const [batalTarget, setBatalTarget] = useState(null);
    const [batalMassal, setBatalMassal] = useState(false);
    const [terpilihBatal, setTerpilihBatal] = useState([]);

    const formBatal = useForm({ cancel_reason: '', cancel_note: '', ids: [] });

    const formHeader = useForm({ title: '', tariff_id: '', period: '', due_date: '' });
    const formDetail = useForm({ invoice_header_id: selectedHeaderId || '', student_id: '', nominal: '' });

    // Tarif yang sedang dipilih di modal — dipakai untuk pratinjau target
    // sebelum gelombang dibuat.
    const tarifTerpilih = listTarif?.find((t) => t.id == formHeader.data.tariff_id);

    // Anchor untuk auto-scroll saat tombol "Rincian" ditekan.
    const rincianRef = useRef(null);
    const invoiceRef = useRef(null);

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            const params = {
                header_id: selectedHeaderId,
                search_header: searchHeader, month_header: monthHeader,
                search_detail: searchDetail, status_detail: statusDetail,
                sort_field: sortField, sort_direction: sortDirection,
            };

            if (filters?.search_header !== searchHeader || filters?.month_header !== monthHeader) params.header_page = 1;
            if (filters?.search_detail !== searchDetail || filters?.status_detail !== statusDetail) params.detail_page = 1;

            router.get(route('keuangan.pemasukan.tagihan.index'), params,
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [searchHeader, monthHeader, searchDetail, statusDetail, sortField, sortDirection]);

    // Pilihan dibersihkan saat gelombang atau halaman berganti. Id yang
    // tercentang milik baris yang sudah tidak terlihat, dan membatalkan
    // sesuatu yang tidak ada di layar adalah cara tercepat membuat orang
    // berhenti percaya pada tombolnya.
    useEffect(() => {
        setTerpilihBatal([]);
    }, [selectedHeaderId, details?.current_page]);

    // Dipanggil dari tombol "Rincian" di kolom Aksi (bukan lagi dari klik baris).
    const pilihHeader = (id) => {
        if (selectedHeaderId == id) {
            rincianRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        setSelectedInvoiceId(null);
        router.get(route('keuangan.pemasukan.tagihan.index'), { header_id: id, detail_page: 1 }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setTimeout(() => rincianRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
            },
        });
    };

    const bukaInvoice = (id) => {
        const tutup = selectedInvoiceId === id;
        setSelectedInvoiceId(tutup ? null : id);
        if (!tutup) {
            setTimeout(() => invoiceRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 60);
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

    const openAddHeader = () => {
        setIsEditHeader(false);
        formHeader.setData({ title: '', tariff_id: '', period: '', due_date: '' });
        formHeader.clearErrors(); setIsHeaderModal(true);
    };

    const openEditHeader = (h) => {
        setIsEditHeader(true); setEditHeaderId(h.id);
        formHeader.setData({ title: h.title, tariff_id: h.tariff_id, period: h.period, due_date: h.due_date });
        formHeader.clearErrors(); setIsHeaderModal(true);
    };

    const submitHeader = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => setIsHeaderModal(false), preserveScroll: true };
        if (isEditHeader) formHeader.put(route('keuangan.pemasukan.tagihan.update_header', editHeaderId), opts);
        else formHeader.post(route('keuangan.pemasukan.tagihan.store_header'), opts);
    };

    const openAddDetail = () => {
        setIsEditDetail(false);
        formDetail.setData({ invoice_header_id: selectedHeaderId, student_id: '', nominal: '' });
        formDetail.clearErrors(); setIsDetailModal(true);
    };

    const openEditDetail = (d) => {
        setIsEditDetail(true); setEditDetailId(d.id);
        formDetail.setData({ invoice_header_id: selectedHeaderId, student_id: d.student_id, nominal: Math.floor(d.total_amount).toString() });
        formDetail.clearErrors(); setIsDetailModal(true);
    };

    const submitDetail = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => setIsDetailModal(false), preserveScroll: true };
        if (isEditDetail) formDetail.put(route('keuangan.pemasukan.tagihan.update_detail', editDetailId), opts);
        else formDetail.post(route('keuangan.pemasukan.tagihan.store_detail'), opts);
    };

    const pilihSiswa = (id) => {
        formDetail.setData('student_id', id);
        if (!isEditDetail && header?.tarif) {
            formDetail.setData('nominal', Math.floor(header.tarif.nominal).toString());
        }
    };

    const bukaBatal = (d) => {
        setBatalMassal(false);
        setBatalTarget(d);
        formBatal.clearErrors();
        formBatal.setData({ cancel_reason: '', cancel_note: '', ids: [] });
        setIsBatal(true);
    };

    const bukaBatalMassal = () => {
        setBatalMassal(true);
        setBatalTarget(null);
        formBatal.clearErrors();
        formBatal.setData({ cancel_reason: '', cancel_note: '', ids: terpilihBatal });
        setIsBatal(true);
    };

    const submitBatal = (e) => {
        e.preventDefault();

        const opts = {
            preserveScroll: true,
            onSuccess: () => { setIsBatal(false); setTerpilihBatal([]); },
        };

        // Dua route berbeda, satu form. Untuk pembatalan satuan field `ids`
        // ikut terkirim tapi diabaikan server — lebih murah daripada
        // memelihara dua form dengan isi yang sama persis.
        if (batalMassal) {
            formBatal.post(route('keuangan.pemasukan.tagihan.batalkan_massal'), opts);
            return;
        }

        formBatal.post(route('keuangan.pemasukan.tagihan.batalkan', batalTarget.id), opts);
    };

    const aksi = (url, method = 'post') => {
        setProses(true);
        router[method](url, method === 'post' ? {} : undefined, {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => { setDialog(null); setPesanError(''); },
            onError: (err) => setPesanError(err.status_error || err.header_error || err.delete_error || err.batal_error || 'Gagal memproses.'),
        });
    };

    const badgeInvoice = (s) => ({
        'Paid':        'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'Installment': 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'Unpaid':      'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20',
        // Netral, bukan merah: dibatalkan itu keputusan, bukan kesalahan.
        'Cancelled':   'bg-stone-100 text-stone-500 ring-stone-300 dark:bg-coal-600 dark:text-stone-400 dark:ring-coal-500',
    }[s] || 'bg-stone-100 text-stone-600 ring-stone-200');

    const labelInvoice = (s) => ({ Paid: 'Lunas', Installment: 'Dicicil', Unpaid: 'Belum Bayar', Cancelled: 'Dibatalkan' }[s] || s);

    const badgeBatch = (s) => ({
        'Draft':     'bg-stone-100 text-stone-600 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500',
        'Published': 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'Closed':    'bg-stone-800 text-white ring-stone-800 dark:bg-coal-900 dark:ring-coal-600',
    }[s] || 'bg-stone-100 text-stone-600 ring-stone-200');

    // Kolom Aksi header selalu tampil: tombol "Rincian" tersedia untuk semua role.
    const adaAksiDetail = can('tagihan.edit_rincian') || can('tagihan.hapus_rincian');

    // Cetak satuan: permission sama dengan cetak massal, route sudah ada,
    // dan batch sudah dipublish.
    // Pembatalan hanya untuk gelombang yang sudah dipublish — selama masih
    // Draft, tombol Hapus sudah tersedia dan lebih tepat. Tagihan yang sudah
    // menerima pembayaran tidak ditawari sama sekali; servernya juga menolak,
    // tapi tombol yang tidak pernah bisa ditekan hanya memancing percobaan.
    const bisaBatal = (d) =>
        header?.status === 'Published' &&
        can('tagihan.batalkan') &&
        d.status !== 'Cancelled' &&
        Math.abs(Number(d.total_amount) - Number(d.remaining_balance)) < 0.01;

    // Hanya baris yang memang bisa dibatalkan yang punya kotak centang.
    // "Pilih semua" pun hanya menjaring baris itu, bukan ke-23-nya — supaya
    // angka di batang pilihan selalu sama dengan jumlah yang benar-benar
    // akan dibatalkan, bukan janji yang sebagian ditolak server nanti.
    const idBisaBatal = (details?.data ?? []).filter(bisaBatal).map((d) => d.id);
    const semuaTercentang = idBisaBatal.length > 0 && terpilihBatal.length === idBisaBatal.length;

    const toggleSemuaBatal = () => setTerpilihBatal(semuaTercentang ? [] : idBisaBatal);
    const toggleSatuBatal = (id) =>
        setTerpilihBatal((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));

    const nominalTerpilih = (details?.data ?? [])
        .filter((d) => terpilihBatal.includes(d.id))
        .reduce((total, d) => total + Number(d.total_amount), 0);

    const cbCls = 'h-4 w-4 cursor-pointer rounded border-stone-300 text-claude-600 focus:ring-claude-500 dark:border-coal-600';

    const bisaCetakSatuan =
        can('tagihan.cetak_massal') &&
        has('keuangan.pemasukan.tagihan.print') &&
        header?.status === 'Published';

    const thCls = 'cursor-pointer px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-4 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter (input, month, select).
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    // color-scheme mengikuti tema agar ikon kalender bawaan browser tidak silau di dark mode.
    const monthCls = `${fieldCls} pr-2 [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-60 hover:[&::-webkit-calendar-picker-indicator]:opacity-100`;
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;

    // Badge target — dipakai di panel rincian dan kartu batch.
    const badgeTarget = 'inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-600 ring-1 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500';

    const ChevronBawah = () => (
        <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
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
            <Head title="Kelola Tagihan" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-stone-200 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Management Tagihan</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Gelombang penagihan dan rincian tagihan per siswa.</p>
            </div>

            {/* 2 kolom sejak layar terkecil — tiga kartu bertumpuk memakan
                hampir satu layar penuh sebelum tabel terlihat. */}
            <div className="mb-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                {[
                    ['Total Potensi', summary?.totalTagihan, 'text-claude-600 dark:text-claude-300'],
                    ['Sudah Diterima', summary?.totalLunas, 'text-emerald-600 dark:text-emerald-400'],
                    ['Sisa Piutang', summary?.totalBelum, 'text-amber-600 dark:text-amber-400'],
                ].map(([label, nilai, warna], i) => (
                    <div key={label}
                        className={`rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5 ${i === 2 ? 'col-span-2 lg:col-span-1' : ''}`}>
                        <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">{label}</p>
                        <p className={`mt-1.5 break-words text-base font-bold leading-tight sm:mt-2 sm:text-xl ${warna}`}>{formatCurrency(nilai || 0)}</p>
                    </div>
                ))}
            </div>

            <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Gelombang Penagihan</h2>

                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:w-56">
                            <input type="text" value={searchHeader} onChange={(e) => setSearchHeader(e.target.value)}
                                placeholder="Cari batch..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="sm:w-44">
                            <input type="month" value={monthHeader} onChange={(e) => setMonthHeader(e.target.value)}
                                aria-label="Filter periode" className={monthCls} />
                        </div>

                        {can('tagihan.buat_batch') && (
                            <PrimaryButton onClick={openAddHeader} className="h-10 w-full shrink-0 justify-center sm:w-auto">Buat Baru</PrimaryButton>
                        )}
                    </div>
                </div>

                {/* ===== KARTU BATCH — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {headers?.data?.length > 0 ? headers.data.map((h) => {
                        const aktif = selectedHeaderId == h.id;
                        return (
                            <div key={h.id} className={`overflow-hidden rounded-2xl ring-1 transition ${aktif ? 'bg-claude-500/5 ring-claude-500/40' : 'ring-stone-200 dark:ring-coal-600'}`}>
                                <div className="flex items-start justify-between gap-3 p-4">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{h.title}</p>
                                        <p className="mt-0.5 truncate text-xs text-stone-400">{h.batch_number}</p>
                                        {h.target_summary && (
                                            <p className="mt-1.5 truncate text-xs text-stone-500 dark:text-stone-400">{h.target_summary}</p>
                                        )}
                                    </div>
                                    <span className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeBatch(h.status)}`}>{h.status}</span>
                                </div>

                                <div className="grid grid-cols-3 border-t border-stone-100 dark:border-coal-600">
                                    <div className="border-r border-stone-100 px-2 py-2.5 text-center dark:border-coal-600">
                                        <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Tarif</p>
                                        <p className="truncate text-xs font-medium text-stone-700 dark:text-stone-200">{h.tarif?.billing_name || '-'}</p>
                                    </div>
                                    <div className="border-r border-stone-100 px-2 py-2.5 text-center dark:border-coal-600">
                                        <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Tempo</p>
                                        <p className="truncate text-xs font-medium text-red-500 dark:text-red-400">{tanggal(h.due_date)}</p>
                                    </div>
                                    <div className="px-2 py-2.5 text-center">
                                        <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Siswa</p>
                                        <p className="text-xs font-semibold text-stone-700 dark:text-stone-200">{h.total_students}</p>
                                    </div>
                                </div>

                                <div className="flex border-t border-stone-100 dark:border-coal-600">
                                    <button type="button" onClick={() => pilihHeader(h.id)}
                                        className={`flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition ${
                                            aktif ? 'bg-claude-500 text-white' : 'text-claude-600 hover:bg-claude-500/10 dark:text-claude-300'
                                        }`}>
                                        <IkonMata /> Rincian
                                    </button>

                                    {can('tagihan.edit_batch') && h.status !== 'Closed' && (
                                        <button type="button" onClick={() => openEditHeader(h)}
                                            className="flex flex-1 items-center justify-center gap-1.5 border-l border-stone-100 py-2.5 text-xs font-medium text-stone-600 transition hover:bg-cream-100 dark:border-coal-600 dark:text-stone-300 dark:hover:bg-coal-600">
                                            Edit
                                        </button>
                                    )}
                                    {can('tagihan.hapus_batch') && h.status === 'Draft' && (
                                        <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-header', id: h.id }); }}
                                            className="flex flex-1 items-center justify-center gap-1.5 border-l border-stone-100 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:border-coal-600 dark:text-red-400 dark:hover:bg-red-500/10">
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    }) : (
                        <div className="rounded-2xl py-14 text-center text-sm text-stone-500 ring-1 ring-stone-200 dark:text-stone-400 dark:ring-coal-600">
                            Belum ada gelombang penagihan.
                        </div>
                    )}
                </div>

                {/* ===== TABEL BATCH — DESKTOP ===== */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 lg:block">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th className={thPlain}>Batch &amp; Judul</th>
                                <th className={thPlain}>Tarif Dasar</th>
                                <th className={thPlain}>Periode / Tempo</th>
                                <th className={thPlain}>Siswa</th>
                                <th className={thPlain}>Status</th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {headers?.data?.length > 0 ? headers.data.map((h) => {
                                const aktif = selectedHeaderId == h.id;
                                return (
                                    <tr key={h.id} className={`transition ${aktif ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="px-4 py-3.5 text-sm">
                                            <span className="font-semibold text-stone-800 dark:text-cream-100">{h.title}</span><br />
                                            <span className="text-xs text-stone-400">{h.batch_number}</span>
                                        </td>
                                        <td className="px-4 py-3.5 text-sm text-stone-600 dark:text-stone-300">
                                            {h.tarif?.billing_name || '-'}
                                            {/* Snapshot target saat gelombang dibuat — aturan tarif bisa
                                                sudah berubah sejak itu. */}
                                            {h.target_summary && (
                                                <><br /><span className="text-xs text-stone-400">{h.target_summary}</span></>
                                            )}
                                        </td>
                                        <td className="px-4 py-3.5 text-sm text-stone-600 dark:text-stone-300">
                                            {h.period}<br /><span className="text-xs text-red-500 dark:text-red-400">Tempo {tanggal(h.due_date)}</span>
                                        </td>
                                        <td className="px-4 py-3.5 text-sm font-medium text-stone-700 dark:text-stone-200">{h.total_students}</td>
                                        <td className="px-4 py-3.5">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeBatch(h.status)}`}>{h.status}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {/* Tombol eksplisit untuk membuka rincian — tidak lagi bergantung klik baris/badge. */}
                                                <button type="button" onClick={() => pilihHeader(h.id)}
                                                    className={`${btnGhost} ${
                                                        aktif
                                                            ? 'bg-claude-500 text-white ring-claude-500'
                                                            : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                                    }`}>
                                                    <IkonMata /> Rincian
                                                </button>

                                                {can('tagihan.edit_batch') && h.status !== 'Closed' && (
                                                    <button type="button" onClick={() => openEditHeader(h)}
                                                        className={`${btnGhost} text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                        Edit
                                                    </button>
                                                )}
                                                {can('tagihan.hapus_batch') && h.status === 'Draft' && (
                                                    <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-header', id: h.id }); }}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan={6} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada gelombang penagihan.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {headers.from || 0}–{headers.to || 0} dari {headers.total} entri</p>
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
                <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0">
                            <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Rincian Siswa · {header.title}</h2>
                            <div className="mt-1.5 flex flex-wrap items-center gap-2">
                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeBatch(header.status)}`}>{header.status}</span>
                                <span className="text-xs text-stone-400">Tempo {tanggal(header.due_date)}</span>
                                {/* Target yang berlaku saat gelombang ini dibuat. */}
                                {header.target_summary && (
                                    <span className={badgeTarget}>{header.target_summary}</span>
                                )}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                            {header.status === 'Draft' && (
                                <>
                                    {can('tagihan.buat_rincian') && (
                                        <>
                                            <SecondaryButton onClick={() => { setPesanError(''); setDialog({ jenis: 'generate' }); }} className="justify-center">Auto-Generate</SecondaryButton>
                                            <SecondaryButton onClick={openAddDetail} className="justify-center">Tambah Manual</SecondaryButton>
                                        </>
                                    )}
                                    {can('tagihan.publish') && (
                                        <PrimaryButton onClick={() => { setPesanError(''); setDialog({ jenis: 'publish' }); }} className="col-span-2 justify-center sm:col-span-1">Publish Batch</PrimaryButton>
                                    )}
                                </>
                            )}
                            {header.status === 'Published' && (
                                <>
                                    {can('tagihan.cetak_massal') && (
                                        <a href={route('keuangan.pemasukan.tagihan.print_bulk', header.id)} target="_blank" rel="noreferrer"
                                            className="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-stone-700 ring-1 ring-stone-200 transition hover:bg-stone-50 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:hover:bg-coal-600">
                                            Cetak Massal
                                        </a>
                                    )}
                                    {can('tagihan.tutup_batch') && (
                                        <button onClick={() => { setPesanError(''); setDialog({ jenis: 'tutup' }); }}
                                            className="rounded-xl px-4 py-2.5 text-sm font-medium text-red-600 ring-1 ring-red-200 transition hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10">
                                            Tutup Batch
                                        </button>
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                    {terpilihBatal.length > 0 && (
                        <div className="mb-4 flex flex-col gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between dark:bg-amber-500/10 dark:ring-amber-500/25">
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                    {terpilihBatal.length} tagihan terpilih · {formatCurrency(nominalTerpilih)}
                                </p>
                                {/* Nominalnya ikut ditampilkan karena itulah angka
                                    yang akan hilang dari piutang — jumlah baris saja
                                    tidak memberi tahu seberapa besar keputusannya. */}
                                <button type="button" onClick={() => setTerpilihBatal([])}
                                    className="text-[11px] text-amber-700/80 underline-offset-2 hover:underline dark:text-amber-400/80">
                                    Bersihkan pilihan
                                </button>
                            </div>
                            <button type="button" onClick={bukaBatalMassal}
                                className="shrink-0 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500">
                                Batalkan Terpilih
                            </button>
                        </div>
                    )}

                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-sm sm:flex-1">
                            <input type="text" value={searchDetail} onChange={(e) => setSearchDetail(e.target.value)}
                                placeholder="Cari nama siswa atau no. invoice..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="relative sm:w-44">
                            <select value={statusDetail} onChange={(e) => setStatusDetail(e.target.value)} className={selectCls}>
                                <option value="">Semua Status</option>
                                <option value="Paid">Lunas</option>
                                <option value="Installment">Dicicil</option>
                                <option value="Unpaid">Belum Bayar</option>
                                <option value="Cancelled">Dibatalkan</option>
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {/* ===== KARTU INVOICE — MOBILE & TABLET ===== */}
                    <div className="space-y-2.5 lg:hidden">
                        {details.data.length > 0 ? details.data.map((d) => {
                            const aktif = selectedInvoiceId === d.id;
                            const bisaCetak = bisaCetakSatuan && Number(d.remaining_balance) > 0;
                            const bisaUbah = adaAksiDetail && header.status === 'Draft';

                            return (
                                <div key={d.id} className={`overflow-hidden rounded-2xl ring-1 transition ${aktif ? 'bg-claude-500/5 ring-claude-500/40' : 'ring-stone-200 dark:ring-coal-600'}`}>
                                    <div className="flex items-start justify-between gap-3 p-4">
                                        {bisaBatal(d) && (
                                            <input type="checkbox" checked={terpilihBatal.includes(d.id)}
                                                onChange={() => toggleSatuBatal(d.id)}
                                                aria-label={`Pilih tagihan ${d.siswa?.name || ''}`}
                                                className={`mt-0.5 shrink-0 ${cbCls}`} />
                                        )}
                                        {/* flex-1 wajib: kotak centang menambah anak ketiga di
                                            container justify-between, dan tanpa ini badge status
                                            berhenti menempel di kanan. */}
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{d.siswa?.name}</p>
                                            <p className="mt-0.5 truncate text-xs text-stone-400">
                                                <span className="text-claude-600 dark:text-claude-300">{d.invoice_number}</span> · NIS {d.siswa?.nis}
                                            </p>
                                        </div>
                                        <span className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeInvoice(d.status)}`}>{labelInvoice(d.status)}</span>
                                    </div>

                                    <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-600">
                                        <div className="border-r border-stone-100 px-3 py-2.5 text-center dark:border-coal-600">
                                            <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Tagihan</p>
                                            <p className="text-xs font-medium text-stone-700 dark:text-stone-200">{formatCurrency(d.total_amount)}</p>
                                        </div>
                                        <div className="px-3 py-2.5 text-center">
                                            <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Sisa</p>
                                            <p className="text-xs font-semibold text-red-600 dark:text-red-400">{formatCurrency(d.remaining_balance)}</p>
                                        </div>
                                    </div>

                                    <div className="flex border-t border-stone-100 dark:border-coal-600">
                                        <button type="button" onClick={() => bukaInvoice(d.id)}
                                            className={`flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition ${
                                                aktif ? 'bg-claude-500 text-white' : 'text-claude-600 hover:bg-claude-500/10 dark:text-claude-300'
                                            }`}>
                                            <IkonMata /> Detail
                                        </button>

                                        {bisaCetak && (
                                            <a href={route('keuangan.pemasukan.tagihan.print', d.id)} target="_blank" rel="noreferrer"
                                                className="flex flex-1 items-center justify-center gap-1.5 border-l border-stone-100 py-2.5 text-xs font-medium text-stone-600 transition hover:bg-cream-100 dark:border-coal-600 dark:text-stone-300 dark:hover:bg-coal-600">
                                                <IkonPrinter /> Cetak
                                            </a>
                                        )}

                                        {bisaUbah && can('tagihan.edit_rincian') && (
                                            <button type="button" onClick={() => openEditDetail(d)}
                                                className="flex flex-1 items-center justify-center border-l border-stone-100 py-2.5 text-xs font-medium text-stone-600 transition hover:bg-cream-100 dark:border-coal-600 dark:text-stone-300 dark:hover:bg-coal-600">
                                                Edit
                                            </button>
                                        )}
                                        {bisaUbah && can('tagihan.hapus_rincian') && (
                                            <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-detail', id: d.id }); }}
                                                className="flex flex-1 items-center justify-center border-l border-stone-100 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:border-coal-600 dark:text-red-400 dark:hover:bg-red-500/10">
                                                Hapus
                                            </button>
                                        )}

                                        {bisaBatal(d) && (
                                            <button type="button" onClick={() => bukaBatal(d)}
                                                className="flex flex-1 items-center justify-center border-l border-stone-100 py-2.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50 dark:border-coal-600 dark:text-amber-400 dark:hover:bg-amber-500/10">
                                                Batalkan
                                            </button>
                                        )}
                                    </div>
                                </div>
                            );
                        }) : (
                            <div className="rounded-2xl py-14 text-center text-sm text-stone-500 ring-1 ring-stone-200 dark:text-stone-400 dark:ring-coal-600">
                                Belum ada rincian siswa di gelombang ini.
                            </div>
                        )}
                    </div>

                    {/* ===== TABEL INVOICE — DESKTOP ===== */}
                    <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 lg:block">
                        <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                            <thead className="bg-stone-50 dark:bg-coal-800">
                                <tr>
                                    <th className="w-10 px-4 py-3.5">
                                        {idBisaBatal.length > 0 && (
                                            <input type="checkbox" checked={semuaTercentang} onChange={toggleSemuaBatal}
                                                ref={(el) => { if (el) el.indeterminate = terpilihBatal.length > 0 && ! semuaTercentang; }}
                                                title={`Pilih ${idBisaBatal.length} tagihan yang bisa dibatalkan di halaman ini`}
                                                className={cbCls} />
                                        )}
                                    </th>
                                    <th onClick={() => handleSort('invoice_number')} className={thCls}><div className="flex items-center">No. Invoice <SortIcon field="invoice_number" /></div></th>
                                    <th className={thPlain}>Siswa</th>
                                    <th onClick={() => handleSort('total_amount')} className={thCls}><div className="flex items-center">Tagihan <SortIcon field="total_amount" /></div></th>
                                    <th onClick={() => handleSort('remaining_balance')} className={thCls}><div className="flex items-center">Sisa <SortIcon field="remaining_balance" /></div></th>
                                    <th onClick={() => handleSort('status')} className={thCls}><div className="flex items-center">Status <SortIcon field="status" /></div></th>
                                    <th className={thAksi}>Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                {details.data.length > 0 ? details.data.map((d) => {
                                    const aktif = selectedInvoiceId === d.id;
                                    return (
                                        <tr key={d.id} className={`transition ${aktif ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                            <td className="px-4 py-3.5">
                                                {bisaBatal(d) && (
                                                    <input type="checkbox" checked={terpilihBatal.includes(d.id)}
                                                        onChange={() => toggleSatuBatal(d.id)}
                                                        aria-label={`Pilih tagihan ${d.siswa?.name || ''}`}
                                                        className={cbCls} />
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3.5 text-sm font-medium text-claude-600 dark:text-claude-300">{d.invoice_number}</td>
                                            <td className="px-4 py-3.5 text-sm">
                                                <span className="font-medium text-stone-800 dark:text-cream-100">{d.siswa?.name}</span><br />
                                                <span className="text-xs text-stone-400">NIS {d.siswa?.nis}</span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3.5 text-sm text-stone-600 dark:text-stone-300">{formatCurrency(d.total_amount)}</td>
                                            <td className="whitespace-nowrap px-4 py-3.5 text-sm font-medium text-red-600 dark:text-red-400">{formatCurrency(d.remaining_balance)}</td>
                                            <td className="whitespace-nowrap px-4 py-3.5">
                                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeInvoice(d.status)}`}>{labelInvoice(d.status)}</span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3.5 text-right text-xs">
                                                <div className="flex items-center justify-end gap-2">
                                                    {/* Tombol detail invoice dipindah ke kolom Aksi juga. */}
                                                    <button type="button" onClick={() => bukaInvoice(d.id)}
                                                        className={`${btnGhost} ${
                                                            aktif
                                                                ? 'bg-claude-500 text-white ring-claude-500'
                                                                : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                                        }`}>
                                                        <IkonMata /> Detail
                                                    </button>

                                                    {/* Cetak per siswa — hanya untuk batch yang sudah publish
                                                        dan invoice yang masih menyisakan tagihan. */}
                                                    {bisaCetakSatuan && Number(d.remaining_balance) > 0 && (
                                                        <a href={route('keuangan.pemasukan.tagihan.print', d.id)} target="_blank" rel="noreferrer"
                                                            title={`Cetak tagihan ${d.siswa?.name || ''}`}
                                                            className={`${btnGhost} text-stone-600 ring-stone-200 hover:bg-cream-100 hover:text-stone-800 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                            <IkonPrinter /> Cetak
                                                        </a>
                                                    )}

                                                    {adaAksiDetail && header.status === 'Draft' && (
                                                        <>
                                                            {can('tagihan.edit_rincian') && (
                                                                <button type="button" onClick={() => openEditDetail(d)}
                                                                    className={`${btnGhost} text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                                    Edit
                                                                </button>
                                                            )}
                                                            {can('tagihan.hapus_rincian') && (
                                                                <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus-detail', id: d.id }); }}
                                                                    className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                                    Hapus
                                                                </button>
                                                            )}
                                                        </>
                                                    )}

                                                    {bisaBatal(d) && (
                                                        <button type="button" onClick={() => bukaBatal(d)}
                                                            title={`Batalkan tagihan ${d.siswa?.name || ''}`}
                                                            className={`${btnGhost} text-amber-700 ring-amber-200 hover:bg-amber-50 dark:text-amber-400 dark:ring-amber-500/25 dark:hover:bg-amber-500/10`}>
                                                            Batalkan
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                }) : (
                                    <tr><td colSpan={7} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada rincian siswa di gelombang ini.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {details.data.length > 0 && (
                        <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                            <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {details.from || 0}–{details.to || 0} dari {details.total} entri</p>
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

            <div ref={invoiceRef} className="scroll-mt-20" />

            {invoice && (
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                        <div className="mb-4 flex items-start justify-between gap-2 border-b border-stone-100 pb-3 dark:border-coal-600">
                            <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Informasi Tagihan</h3>
                            <button type="button" onClick={() => setSelectedInvoiceId(null)} title="Tutup detail"
                                className="-mt-1 flex h-7 w-7 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div className="rounded-xl bg-stone-50 p-4 text-center dark:bg-coal-900/50">
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-stone-400">{invoice.invoice_number}</p>
                            <p className="mt-1 font-semibold text-stone-800 dark:text-cream-100">{invoice.siswa?.name}</p>
                        </div>
                        <div className="mt-4 space-y-2.5 text-sm">
                            <div className="flex justify-between gap-3 border-b border-stone-100 pb-2 dark:border-coal-600">
                                <span className="text-stone-500 dark:text-stone-400">Tagihan awal</span>
                                <span className="font-medium text-stone-700 dark:text-stone-200">{formatCurrency(invoice.total_amount)}</span>
                            </div>
                            <div className="flex justify-between gap-3 border-b border-stone-100 pb-2 dark:border-coal-600">
                                <span className="text-stone-500 dark:text-stone-400">Sisa</span>
                                <span className="font-semibold text-red-600 dark:text-red-400">{formatCurrency(invoice.remaining_balance)}</span>
                            </div>
                            <div className="pt-1">
                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeInvoice(invoice.status)}`}>{labelInvoice(invoice.status)}</span>
                            </div>
                        </div>

                        {/* Alasan pembatalan ditampilkan apa adanya. Inilah
                            satu-satunya pembeda membatalkan dengan menghapus —
                            kalau keterangannya tidak terlihat di sini, tidak ada
                            gunanya menyimpannya. */}
                        {invoice.status === 'Cancelled' && (
                            <div className="mt-4 rounded-xl bg-stone-50 p-3 ring-1 ring-stone-200 dark:bg-coal-900/50 dark:ring-coal-600">
                                <p className="text-[11px] font-semibold uppercase tracking-wider text-stone-400">Dibatalkan</p>
                                <p className="mt-1 text-sm font-medium text-stone-800 dark:text-cream-100">
                                    {ALASAN_BATAL.find(([k]) => k === invoice.cancel_reason)?.[1] || invoice.cancel_reason || '—'}
                                </p>
                                {invoice.cancel_note && (
                                    <p className="mt-1 break-words text-xs text-stone-600 dark:text-stone-300">{invoice.cancel_note}</p>
                                )}
                                <p className="mt-2 text-[11px] text-stone-400">
                                    {invoice.pembatal?.name || 'Tidak tercatat'} · {tanggal(invoice.cancelled_at)}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                        <h3 className="mb-4 border-b border-stone-100 pb-3 text-sm font-semibold text-stone-800 dark:border-coal-600 dark:text-cream-50">Riwayat Pembayaran</h3>
                        <div className="max-h-72 space-y-2 overflow-y-auto pr-1">
                            {invoice.pembayarans?.length > 0 ? invoice.pembayarans.map((p) => (
                                <div key={p.id} className="rounded-xl p-3 ring-1 ring-stone-200 dark:ring-coal-600">
                                    <div className="mb-1 flex items-start justify-between gap-2">
                                        <span className="text-xs text-stone-400">{tanggal(p.payment_date)}</span>
                                        <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium ${p.status_approval === 'disetujui' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'}`}>
                                            {p.status_approval}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{formatCurrency(p.nominal)}</span>
                                        {p.status_approval === 'disetujui' && can('pembayaran.cetak') && has('keuangan.pemasukan.pembayaran.print') && (
                                            <a href={route('keuangan.pemasukan.pembayaran.print', p.id)} target="_blank" rel="noreferrer" className="shrink-0 text-[11px] font-medium text-claude-600 hover:underline dark:text-claude-300">Kwitansi</a>
                                        )}
                                    </div>
                                    <p className="mt-1 truncate text-[10px] text-stone-400">{p.payment_method} · {p.dibuat_oleh?.name || 'Sistem'}</p>
                                </div>
                            )) : (
                                <p className="rounded-xl border-2 border-dashed border-stone-200 py-6 text-center text-sm text-stone-400 dark:border-coal-600">Belum ada pembayaran masuk.</p>
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                        <h3 className="mb-4 border-b border-stone-100 pb-3 text-sm font-semibold text-stone-800 dark:border-coal-600 dark:text-cream-50">Riwayat Penagihan</h3>
                        <div className="max-h-72 space-y-2 overflow-y-auto pr-1">
                            {invoice.riwayat_penagihans?.length > 0 ? invoice.riwayat_penagihans.map((log) => (
                                <div key={log.id} className="rounded-xl bg-stone-50 p-3 dark:bg-coal-900/50">
                                    <div className="mb-1 flex items-start justify-between gap-2">
                                        <span className="text-xs font-medium text-stone-700 dark:text-stone-200">{log.metode}</span>
                                        <span className="shrink-0 text-[10px] text-stone-400">{tanggal(log.tanggal_penagihan)}</span>
                                    </div>
                                    <p className="text-[11px] leading-snug text-stone-600 dark:text-stone-300">{log.keterangan}</p>
                                    <p className="mt-1.5 text-right text-[10px] italic text-stone-400">{log.petugas?.name}</p>
                                </div>
                            )) : (
                                <p className="rounded-xl border-2 border-dashed border-stone-200 py-6 text-center text-sm text-stone-400 dark:border-coal-600">Belum pernah ditagih.</p>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <Modal show={isHeaderModal} onClose={() => setIsHeaderModal(false)} maxWidth="lg">
                <form onSubmit={submitHeader} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditHeader ? 'Edit Gelombang' : 'Buat Gelombang Baru'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Judul Penagihan <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={formHeader.data.title} onChange={(e) => formHeader.setData('title', e.target.value)} className="mt-1.5 block w-full" placeholder="Contoh: SPP September 2026" />
                            <InputError message={formHeader.errors.title} className="mt-1.5" />
                        </div>
                        <div>
                            <InputLabel>Tarif Dasar <span className="text-red-500">*</span></InputLabel>
                            <div className="mt-1.5">
                                <SearchableSelect placeholder="-- Pilih Tarif Dasar --"
                                    options={listTarif?.map((t) => ({
                                        id: t.id,
                                        label: `${t.billing_name} · ${formatCurrency(t.nominal)} · ${t.target_summary}`,
                                    })) || []}
                                    value={formHeader.data.tariff_id}
                                    onChange={(id) => formHeader.setData('tariff_id', id)} />
                            </div>

                            {/* Pratinjau target sebelum gelombang dibuat — operator perlu
                                tahu siapa yang akan terjaring auto-generate nanti. */}
                            {tarifTerpilih && !isEditHeader && (
                                <div className="mt-2 rounded-xl bg-claude-500/5 p-3 ring-1 ring-claude-500/20">
                                    <p className="text-[11px] font-semibold uppercase tracking-wide text-claude-700 dark:text-claude-300">Akan ditagihkan ke</p>
                                    <p className="mt-1 text-sm text-stone-700 dark:text-stone-200">{tarifTerpilih.target_summary}</p>
                                    {tarifTerpilih.is_donation && (
                                        <p className="mt-1.5 text-xs text-amber-600 dark:text-amber-400">
                                            Tarif donasi — auto-generate tidak tersedia, penyumbang ditambahkan manual.
                                        </p>
                                    )}
                                </div>
                            )}

                            {isEditHeader && <p className="mt-1 text-xs text-stone-400">Tarif dasar tidak dapat diubah setelah gelombang dibuat.</p>}
                            <InputError message={formHeader.errors.tariff_id} className="mt-1.5" />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel>Periode <span className="text-red-500">*</span></InputLabel>
                                <TextInput value={formHeader.data.period} onChange={(e) => formHeader.setData('period', e.target.value)} className="mt-1.5 block w-full" placeholder="Contoh: Sep 2026" />
                                <InputError message={formHeader.errors.period} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel>Jatuh Tempo <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="date" value={formHeader.data.due_date} onChange={(e) => formHeader.setData('due_date', e.target.value)}
                                    className="mt-1.5 block w-full [color-scheme:light] dark:[color-scheme:dark]" />
                                <InputError message={formHeader.errors.due_date} className="mt-1.5" />
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsHeaderModal(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formHeader.processing}>Simpan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDetailModal} onClose={() => setIsDetailModal(false)} maxWidth="lg">
                <form onSubmit={submitDetail} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditDetail ? 'Edit Tagihan Siswa' : 'Tambah Siswa ke Gelombang'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Siswa <span className="text-red-500">*</span></InputLabel>
                            <div className="mt-1.5">
                                <SearchableSelect placeholder="-- Cari nama atau NIS --"
                                    options={listSiswa?.map((s) => ({ id: s.id, label: `${s.nis} · ${s.name}` })) || []}
                                    value={formDetail.data.student_id}
                                    onChange={pilihSiswa} />
                            </div>
                            <InputError message={formDetail.errors.student_id} className="mt-1.5" />
                        </div>
                        <div>
                            <InputLabel>Nominal (Rp) <span className="text-red-500">*</span></InputLabel>
                            <TextInput type="text" inputMode="numeric"
                                value={formDetail.data.nominal ? formDetail.data.nominal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => formDetail.setData('nominal', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" />
                            <p className="mt-1 text-xs text-stone-400">Terisi otomatis dari tarif dasar; ubah bila ada potongan.</p>
                            <InputError message={formDetail.errors.nominal} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsDetailModal(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formDetail.processing}>Simpan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isBatal} onClose={() => setIsBatal(false)} maxWidth="md">
                <form onSubmit={submitBatal} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                        {batalMassal ? `Batalkan ${formBatal.data.ids.length} Tagihan` : 'Batalkan Tagihan'}
                    </h2>
                    <p className="mt-1 break-words text-sm text-stone-500 dark:text-stone-400">
                        {batalMassal
                            ? `Total ${formatCurrency(nominalTerpilih)} keluar dari piutang gelombang ini`
                            : `${batalTarget?.siswa?.name} · ${batalTarget?.invoice_number} · ${formatCurrency(batalTarget?.total_amount || 0)}`}
                    </p>

                    <div className="mt-4 rounded-xl bg-amber-50 p-3 text-xs leading-relaxed text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25">
                        {batalMassal ? (
                            <>
                                Alasan yang Anda pilih berlaku untuk <strong>seluruh</strong> tagihan terpilih.
                                Bila alasannya berbeda-beda, batalkan terpisah — alasan inilah yang nanti
                                dibaca saat yayasan menanyakan dasar pembebasannya.
                            </>
                        ) : (
                            <>
                                Santri ini tidak perlu membayar tagihan tersebut, dan nominalnya keluar dari total piutang.
                                Barisnya tetap tersimpan beserta alasan dan nama Anda.
                            </>
                        )}
                    </div>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Alasan <span className="text-red-500">*</span></InputLabel>
                            <div className="mt-1.5 space-y-2">
                                {ALASAN_BATAL.map(([nilai, label]) => (
                                    <label key={nilai}
                                        className={`flex cursor-pointer items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm ring-1 transition ${
                                            formBatal.data.cancel_reason === nilai
                                                ? 'bg-claude-500/10 text-stone-800 ring-claude-500/40 dark:text-cream-100'
                                                : 'text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600/40'
                                        }`}>
                                        <input type="radio" name="cancel_reason" value={nilai}
                                            checked={formBatal.data.cancel_reason === nilai}
                                            onChange={(e) => formBatal.setData('cancel_reason', e.target.value)}
                                            className="h-4 w-4 border-stone-300 text-claude-500 focus:ring-claude-500" />
                                        {label}
                                    </label>
                                ))}
                            </div>
                            <InputError message={formBatal.errors.cancel_reason} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel value="Keterangan (opsional)" />
                            <TextInput value={formBatal.data.cancel_note}
                                onChange={(e) => formBatal.setData('cancel_note', e.target.value)}
                                className="mt-1.5 block w-full"
                                placeholder="Contoh: surat keterangan tidak mampu dari RT" />
                            <InputError message={formBatal.errors.cancel_note} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsBatal(false)} className="justify-center">Batal</SecondaryButton>
                        <button type="submit" disabled={formBatal.processing || !formBatal.data.cancel_reason}
                            className="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50">
                            {batalMassal ? `Ya, Batalkan ${formBatal.data.ids.length} Tagihan` : 'Ya, Batalkan Tagihan'}
                        </button>
                    </div>
                </form>
            </Modal>

            <Modal show={dialog !== null} onClose={() => { setDialog(null); setPesanError(''); }} maxWidth="sm">
                {dialog && (() => {
                    const isi = {
                        'hapus-header': ['Hapus Gelombang', 'Seluruh rincian tagihan di dalamnya ikut terhapus.', true,
                            () => aksi(route('keuangan.pemasukan.tagihan.destroy_header', dialog.id), 'delete')],
                        // Judulnya dulu "Batalkan Tagihan" padahal yang dijalankan
                        // penghapusan. Sekarang pembatalan sungguhan sudah ada,
                        // jadi dua hal berbeda tidak boleh bernama sama.
                        'hapus-detail': ['Hapus Tagihan', 'Barisnya hilang sepenuhnya. Untuk gelombang yang sudah dipublish, gunakan Batalkan agar jejaknya tetap ada.', true,
                            () => aksi(route('keuangan.pemasukan.tagihan.destroy', dialog.id), 'delete')],
                        // Target disebut eksplisit supaya operator tidak asal klik.
                        'generate':     ['Auto-Generate Tagihan',
                            header?.target_summary
                                ? `Tagihan akan dibuat untuk — ${header.target_summary}.`
                                : 'Sistem membuat tagihan untuk semua siswa yang sesuai tarif dasar.',
                            false,
                            () => aksi(route('keuangan.pemasukan.tagihan.generate', selectedHeaderId))],
                        'publish':      ['Publish Gelombang', 'Setelah dipublish, tagihan resmi menjadi piutang siswa.', false,
                            () => aksi(route('keuangan.pemasukan.tagihan.publish_header', selectedHeaderId))],
                        'tutup':        ['Tutup Gelombang', 'Menutup gelombang menghentikan aktivitas pembayaran pada rinciannya.', true,
                            () => aksi(route('keuangan.pemasukan.tagihan.close_header', selectedHeaderId))],
                    }[dialog.jenis];

                    const [judul, pesan, bahaya, jalankan] = isi;

                    return (
                        <div className="p-6 text-center">
                            <div className={`mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full ${bahaya ? 'bg-red-50 text-red-600 dark:bg-red-500/10' : 'bg-claude-500/10 text-claude-600 dark:text-claude-300'}`}>
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d={bahaya
                                        ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                                        : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'} />
                                </svg>
                            </div>
                            <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">{judul}</h3>
                            <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">{pesan}</p>
                            {pesanError && (
                                <div className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{pesanError}</div>
                            )}
                            <div className="mt-5 flex justify-center gap-2">
                                <SecondaryButton onClick={() => { setDialog(null); setPesanError(''); }} disabled={proses}>Batal</SecondaryButton>
                                {bahaya ? (
                                    <button onClick={jalankan} disabled={proses}
                                        className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">Ya, Lanjutkan</button>
                                ) : (
                                    <PrimaryButton onClick={jalankan} disabled={proses}>Ya, Lanjutkan</PrimaryButton>
                                )}
                            </div>
                        </div>
                    );
                })()}
            </Modal>
        </KeuanganLayout>
    );
}
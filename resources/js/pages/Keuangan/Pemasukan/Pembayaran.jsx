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

// Tagihan (siswa TK) dan infaq Rumah Quran memilih siswa terdaftar. Uang masuk
// (pendaftaran) dibayar calon siswa yang belum tentu diterima → nama
// diisi manual. Daftar siswanya sudah disaring server per kategori.
const PERLU_SISWA = ['tagihan', 'infaq_arahan'];

// Label & placeholder isian nama manual per kategori.
const NAMA_PIHAK = {
    uang_masuk: { label: 'Nama Calon Siswa', wajib: true,  contoh: 'Contoh: Muhammad Fauzan', kolom: 'Calon Siswa' },
    donatur:    { label: 'Nama Donatur / Instansi', wajib: true, contoh: 'Contoh: Hamba Allah / PT Sukses Makmur', kolom: 'Donatur' },
    koperasi:   { label: 'Nama Pihak / Mitra', wajib: false, contoh: 'Contoh: Koperasi Sekolah', kolom: 'Pihak / Keterangan' },
};
const namaPihak = (k) => NAMA_PIHAK[k] || NAMA_PIHAK.koperasi;

const LANGKAH = [
    { id: 'draft',     label: 'Draft' },
    { id: 'diperiksa', label: 'Diperiksa' },
    { id: 'disetujui', label: 'Disetujui' },
];

// Label tombol untuk memajukan dokumen ke tahap berikutnya.
const AKSI_LANJUT = {
    diperiksa: { label: 'Tandai Diperiksa', perm: ['pembayaran.approve_diperiksa'] },
    // Nama izin di database: approve_final. approve_disetujui ikut diterima.
    disetujui: { label: 'Setujui Pembayaran', perm: ['pembayaran.approve_final', 'pembayaran.approve_disetujui'] },
};

const waktu = (t) => {
    if (!t) return '';
    const d = new Date(t);
    return `${d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })} · ${d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':')}`;
};

// Alasan cepat untuk menolak kiriman wali — bendahara tinggal klik, lalu
// boleh disunting. Alasan ini dibaca langsung oleh wali di portal.
const ALASAN_CEPAT = [
    'Dana belum masuk ke rekening',
    'Nominal di bukti tidak sesuai',
    'Bukti tidak terbaca / buram',
    'Bukti untuk tagihan lain',
    'Kiriman ganda',
];

const BadgeWali = ({ kecil = false }) => (
    <span className={`inline-flex shrink-0 items-center gap-1 rounded-full bg-sky-50 font-medium text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20 ${kecil ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-0.5 text-[11px]'}`}
        title="Dikirim wali santri lewat portal">
        <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        Dari wali
    </span>
);

const tanggal = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-');

export default function Pembayaran({ pembayaran, kategoriAktif, daftarKategori, listSiswa, listRekening, listTagihan, menungguWali = {}, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [kategori, setKategori] = useState(kategoriAktif || 'tagihan');
    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.statusFilter || '');
    const [sumberFilter, setSumberFilter] = useState(filters?.sumberFilter || '');
    const [alasanTolak, setAlasanTolak] = useState('');
    const [monthFilter, setMonthFilter] = useState(filters?.monthFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [terpilih, setTerpilih] = useState(null);
    const aktif = pembayaran?.data?.find((p) => p.id === terpilih);

    const [isModal, setIsModal] = useState(false);
    const [isEdit, setIsEdit] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isBukti, setIsBukti] = useState(false);
    const [dialog, setDialog] = useState(null);
    const [pesanError, setPesanError] = useState('');
    const [proses, setProses] = useState(false);

    // Anchor auto-scroll saat tombol "Detail" ditekan.
    const detailRef = useRef(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        kategori: kategoriAktif || 'tagihan',
        payment_date: new Date().toISOString().split('T')[0],
        student_id: '',
        invoice_id: '',
        nama_pihak: '',
        account_id: '',
        payment_method: 'Tunai',
        nominal: '',
        proof_of_payment: null,
        catatan: '',
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.pemasukan.pembayaran.index'),
                { kategori, search, monthFilter, statusFilter, sumberFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [kategori, search, monthFilter, statusFilter, sumberFilter, sortField, sortDirection]);

    const gantiKategori = (k) => { setKategori(k); setTerpilih(null); };

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

    const openAdd = () => {
        setIsEdit(false); setEditId(null); reset(); clearErrors();
        setData({
            kategori,
            payment_date: new Date().toISOString().split('T')[0],
            student_id: '', invoice_id: '', nama_pihak: '', account_id: '',
            payment_method: 'Tunai', nominal: '', proof_of_payment: null, catatan: '',
        });
        setIsModal(true);
    };

    const openEdit = (item) => {
        setIsEdit(true); setEditId(item.id); clearErrors();
        setData({
            kategori: item.kategori,
            payment_date: item.payment_date,
            student_id: item.student_id || '',
            invoice_id: item.invoice_id || '',
            // Data uang masuk lama masih memakai siswa terdaftar dan nama_pihak
            // kosong → isi dari nama siswa supaya validasi nama wajib lolos.
            nama_pihak: item.nama_pihak || (!PERLU_SISWA.includes(item.kategori) ? item.siswa?.name : '') || '',
            account_id: item.account_id,
            payment_method: item.payment_method,
            nominal: Math.floor(item.nominal).toString(),
            proof_of_payment: null,
            catatan: item.catatan || '',
        });
        setIsModal(true);
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModal(false); reset(); }, preserveScroll: true, forceFormData: true };

        if (isEdit) {
            // Method spoofing — PUT tidak bisa membawa FormData.
            router.post(route('keuangan.pemasukan.pembayaran.update', editId), { _method: 'put', ...data }, opts);
        } else {
            post(route('keuangan.pemasukan.pembayaran.store'), opts);
        }
    };

    const aksi = (url, method = 'post', body = {}) => {
        setProses(true);
        const opts = {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => { setDialog(null); setPesanError(''); },
            onError: (err) => setPesanError(err.approve || err.alasan_tolak || 'Gagal memproses.'),
        };
        if (method === 'delete') router.delete(url, opts);
        else router.post(url, body, opts);
    };

    const badgeStatus = (s) => ({
        'draft':     'bg-cream-200 text-stone-600 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500',
        'diperiksa': 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'disetujui': 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'ditolak':   'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20',
    }[s] || 'bg-cream-200 text-stone-600 ring-cream-300');

    // Yang ditolak berhenti di tahap terakhir yang sempat dilewatinya.
    const ditolak = aktif?.status_approval === 'ditolak';
    const langkahAktif = !aktif ? 0
        : ditolak ? (aktif.diperiksa_at ? 1 : 0)
        : LANGKAH.findIndex((s) => s.id === aktif.status_approval);

    // Tahap berikutnya yang tersedia untuk dokumen yang sedang dibuka.
    const langkahBerikut = aktif && !ditolak && langkahAktif >= 0 && langkahAktif < LANGKAH.length - 1
        ? LANGKAH[langkahAktif + 1]
        : null;

    const infoLanjut = langkahBerikut ? AKSI_LANJUT[langkahBerikut.id] : null;
    const bisaLanjut = infoLanjut ? infoLanjut.perm.some(can) : false;

    // Menolak = wewenang tahap pemeriksaan, selama belum disahkan.
    const bisaTolak = aktif && ['draft', 'diperiksa'].includes(aktif.status_approval) && can('pembayaran.approve_diperiksa');

    // Yang ditolak belum pernah menyentuh saldo, jadi boleh dihapus.
    const bisaDihapus = (item) => ['draft', 'ditolak'].includes(item.status_approval) && can('pembayaran.hapus');

    const pelaku = (step) => {
        if (!aktif) return null;
        const map = {
            draft:     [aktif.dibuat_oleh, aktif.created_at],
            diperiksa: [aktif.pemeriksa, aktif.diperiksa_at],
            disetujui: [aktif.penyetuju, aktif.disetujui_at],
        }[step];

        if (!map || !map[0]) return null;

        return (
            <p className="mt-1 text-[10px] leading-tight text-stone-400">
                <span className="font-medium text-stone-600 dark:text-stone-300">{map[0].name}</span><br />{waktu(map[1])}
            </p>
        );
    };

    const perluSiswa = PERLU_SISWA.includes(data.kategori);

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600';
    // color-scheme ikut tema agar ikon kalender bawaan browser tidak silau di dark mode.
    const monthCls = `${fieldCls} pr-2 [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-60 hover:[&::-webkit-calendar-picker-indicator]:opacity-100`;
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9 dark:[color-scheme:dark]`;
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-cream-300 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';

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
            <Head title="Pembayaran Masuk" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-cream-300 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Pembayaran</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Seluruh dana masuk — tagihan siswa TK, infaq santri Rumah Quran, uang pangkal, koperasi, dan donasi.</p>
            </div>

            {/* Tab kategori: grid 2 kolom di mobile agar tidak terpotong. */}
            <div className="mb-5 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                {Object.entries(daftarKategori).filter(([k]) => k !== 'lainnya').map(([k, label]) => (
                    <button key={k} onClick={() => gantiKategori(k)}
                        className={`truncate rounded-xl px-3 py-2.5 text-xs font-medium transition sm:px-4 sm:text-sm ${
                            kategori === k
                                ? 'bg-claude-500 text-white shadow-lg shadow-claude-500/25'
                                : 'bg-white text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                        }`}>
                        {label}
                        {menungguWali?.[k] > 0 && (
                            <span className={`ml-1.5 inline-flex min-w-[1.25rem] justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none ${
                                kategori === k ? 'bg-white/25 text-white' : 'bg-sky-500 text-white'
                            }`} title={`${menungguWali[k]} kiriman wali menunggu diperiksa`}>
                                {menungguWali[k]}
                            </span>
                        )}
                    </button>
                ))}
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari no. kwitansi atau nama..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        {/* Bulan & status berdampingan di mobile — bertumpuk terlalu boros. */}
                        <div className="grid grid-cols-2 gap-2 sm:flex">
                            <div className="sm:w-44 sm:flex-none">
                                <input type="month" value={monthFilter} onChange={(e) => setMonthFilter(e.target.value)}
                                    aria-label="Filter periode" className={monthCls} />
                            </div>

                            <div className="relative sm:w-40 sm:flex-none">
                                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectCls} aria-label="Filter status">
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="diperiksa">Diperiksa</option>
                                    <option value="disetujui">Disetujui</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                                <ChevronBawah />
                            </div>

                            {/* Hanya kategori yang bisa dikirim wali dari portal. */}
                            {PERLU_SISWA.includes(kategori) && (
                                <div className="relative col-span-2 sm:w-44 sm:flex-none">
                                    <select value={sumberFilter} onChange={(e) => setSumberFilter(e.target.value)} className={selectCls} aria-label="Filter sumber">
                                        <option value="">Semua Sumber</option>
                                        <option value="wali">Kiriman Wali</option>
                                        <option value="bendahara">Dicatat Bendahara</option>
                                    </select>
                                    <ChevronBawah />
                                </div>
                            )}
                        </div>
                    </div>

                    {can('pembayaran.buat') && (
                        <PrimaryButton onClick={openAdd} className="h-10 w-full shrink-0 justify-center lg:w-auto">Tambah Pembayaran</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {pembayaran?.data?.length > 0 ? pembayaran.data.map((item) => {
                        const dipilih = terpilih === item.id;
                        const bisaCetak = item.status_approval === 'disetujui' && can('pembayaran.cetak');
                        const bisaUbah = item.status_approval === 'draft';
                        const dariWali = item.sumber === 'wali';

                        return (
                            <div key={item.id} className={`overflow-hidden rounded-2xl ring-1 transition ${dipilih ? 'bg-claude-500/5 ring-claude-500/40' : 'ring-cream-300 dark:ring-coal-600'}`}>
                                <div className="flex items-start justify-between gap-3 p-4">
                                    <div className="min-w-0">
                                        <div className="flex min-w-0 items-center gap-1.5">
                                            <p className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">
                                                {item.siswa?.name || item.nama_pihak || item.catatan || '—'}
                                            </p>
                                            {dariWali && <BadgeWali kecil />}
                                        </div>
                                        <p className="mt-0.5 truncate text-xs text-stone-400">
                                            <span className="text-claude-600 dark:text-claude-300">{item.receipt_number}</span>
                                            {(item.tagihan?.invoice_number || (item.siswa && item.catatan)) && ` · ${item.tagihan?.invoice_number || item.catatan}`}
                                        </p>
                                    </div>
                                    <span className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ${badgeStatus(item.status_approval)}`}>{item.status_approval}</span>
                                </div>

                                <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600">
                                    <div className="border-r border-cream-200 px-3 py-2.5 text-center dark:border-coal-600">
                                        <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Tanggal</p>
                                        <p className="text-xs font-medium text-stone-700 dark:text-stone-200">{tanggal(item.payment_date)}</p>
                                    </div>
                                    <div className="px-3 py-2.5 text-center">
                                        <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Jumlah</p>
                                        <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{formatCurrency(item.nominal)}</p>
                                    </div>
                                </div>

                                <div className="flex border-t border-cream-200 dark:border-coal-600">
                                    <button type="button" onClick={() => bukaDetail(item.id)}
                                        className={`flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition ${
                                            dipilih ? 'bg-claude-500 text-white' : 'text-claude-600 hover:bg-claude-500/10 dark:text-claude-300'
                                        }`}>
                                        <IkonMata /> Detail
                                    </button>

                                    {bisaCetak && (
                                        <a href={route('keuangan.pemasukan.pembayaran.print', item.id)} target="_blank" rel="noreferrer"
                                            className="flex flex-1 items-center justify-center gap-1.5 border-l border-cream-200 py-2.5 text-xs font-medium text-stone-600 transition hover:bg-cream-100 dark:border-coal-600 dark:text-stone-300 dark:hover:bg-coal-600">
                                            <IkonPrinter /> Kwitansi
                                        </a>
                                    )}

                                    {bisaUbah && can('pembayaran.edit') && (
                                        <button type="button" onClick={() => openEdit(item)}
                                            className="flex flex-1 items-center justify-center border-l border-cream-200 py-2.5 text-xs font-medium text-stone-600 transition hover:bg-cream-100 dark:border-coal-600 dark:text-stone-300 dark:hover:bg-coal-600">
                                            Ubah
                                        </button>
                                    )}
                                    {bisaDihapus(item) && (
                                        <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus', id: item.id }); }}
                                            className="flex flex-1 items-center justify-center border-l border-cream-200 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:border-coal-600 dark:text-red-400 dark:hover:bg-red-500/10">
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    }) : (
                        <div className="rounded-2xl py-14 text-center text-sm text-stone-500 ring-1 ring-cream-300 dark:text-stone-400 dark:ring-coal-600">
                            Belum ada data pada kategori ini.
                        </div>
                    )}
                </div>

                {/* ===== TABEL — DESKTOP ===== */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 lg:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th onClick={() => handleSort('receipt_number')} className={thCls}><div className="flex items-center">No. Kwitansi <SortIcon field="receipt_number" /></div></th>
                                <th onClick={() => handleSort('payment_date')} className={thCls}><div className="flex items-center">Tanggal <SortIcon field="payment_date" /></div></th>
                                <th className={thPlain}>{kategori === 'infaq_arahan' ? 'Santri' : PERLU_SISWA.includes(kategori) ? 'Siswa' : namaPihak(kategori).kolom}</th>
                                <th onClick={() => handleSort('nominal')} className={thCls}><div className="flex items-center">Jumlah <SortIcon field="nominal" /></div></th>
                                <th onClick={() => handleSort('status_approval')} className={thCls}><div className="flex items-center">Status <SortIcon field="status_approval" /></div></th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {pembayaran?.data?.length > 0 ? pembayaran.data.map((item) => {
                                const dipilih = terpilih === item.id;
                                return (
                                    <tr key={item.id} className={`transition ${dipilih ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm font-medium text-claude-600 dark:text-claude-300">{item.receipt_number}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{tanggal(item.payment_date)}</td>
                                        <td className="px-5 py-4 text-sm">
                                            <span className="font-medium text-stone-800 dark:text-cream-100">
                                                {item.siswa?.name || item.nama_pihak || item.catatan || '—'}
                                            </span>
                                            {item.sumber === 'wali' && <span className="ml-1.5 align-middle"><BadgeWali kecil /></span>}
                                            {(item.tagihan?.invoice_number || (item.siswa && item.catatan)) && (
                                                <><br /><span className="text-xs text-stone-400">{item.tagihan?.invoice_number || item.catatan}</span></>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{formatCurrency(item.nominal)}</td>
                                        <td className="whitespace-nowrap px-5 py-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ${badgeStatus(item.status_approval)}`}>{item.status_approval}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {/* Detail & jalur persetujuan dibuka lewat tombol, bukan klik baris. */}
                                                <button type="button" onClick={() => bukaDetail(item.id)}
                                                    className={`${btnGhost} ${
                                                        dipilih
                                                            ? 'bg-claude-500 text-white ring-claude-500'
                                                            : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                                    }`}>
                                                    <IkonMata /> Detail
                                                </button>

                                                {item.status_approval === 'disetujui' && can('pembayaran.cetak') && (
                                                    <a href={route('keuangan.pemasukan.pembayaran.print', item.id)} target="_blank" rel="noreferrer"
                                                        title={`Cetak kwitansi ${item.receipt_number}`}
                                                        className={`${btnGhost} text-stone-600 ring-cream-300 hover:bg-cream-100 hover:text-stone-800 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                        <IkonPrinter /> Kwitansi
                                                    </a>
                                                )}

                                                {item.status_approval === 'draft' && can('pembayaran.edit') && (
                                                    <button type="button" onClick={() => openEdit(item)}
                                                        className={`${btnGhost} text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                        Ubah
                                                    </button>
                                                )}
                                                {bisaDihapus(item) && (
                                                    <button type="button" onClick={() => { setPesanError(''); setDialog({ jenis: 'hapus', id: item.id }); }}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan={6} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada data pada kategori ini.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {pembayaran.from || 0}–{pembayaran.to || 0} dari {pembayaran.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {pembayaran.links.map((l, i) => (
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
                    <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                        <div className="mb-4 flex items-start justify-between gap-2 border-b border-cream-200 pb-3 dark:border-coal-600">
                            <h3 className="text-sm font-semibold text-stone-800 dark:text-cream-50">Detail Pembayaran</h3>
                            <button type="button" onClick={() => setTerpilih(null)} title="Tutup detail"
                                className="-mt-1 flex h-7 w-7 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <dl className="space-y-3 text-sm">
                            {[
                                ['No. Kwitansi', aktif.receipt_number],
                                ['Tanggal', tanggal(aktif.payment_date)],
                                ['Metode', aktif.payment_method],
                                ['Rekening Tujuan', aktif.rekening?.account_name],
                                ['Pihak', aktif.siswa?.name || aktif.nama_pihak || '—'],
                                ...(PERLU_SISWA.includes(aktif.kategori)
                                    ? [['Sumber', aktif.sumber === 'wali' ? 'Dikirim wali lewat portal' : 'Dicatat bendahara']]
                                    : []),
                            ].map(([k, v]) => (
                                <div key={k}>
                                    <dt className="text-[11px] uppercase tracking-wider text-stone-400">{k}</dt>
                                    <dd className="break-words font-medium text-stone-800 dark:text-cream-100">{v || '—'}</dd>
                                </div>
                            ))}
                            <div>
                                <dt className="text-[11px] uppercase tracking-wider text-stone-400">Nominal</dt>
                                <dd className="text-lg font-bold text-emerald-600 dark:text-emerald-400">{formatCurrency(aktif.nominal)}</dd>
                            </div>
                            {aktif.catatan && (
                                <div>
                                    <dt className="text-[11px] uppercase tracking-wider text-stone-400">Catatan</dt>
                                    <dd className="break-words text-stone-700 dark:text-stone-200">{aktif.catatan}</dd>
                                </div>
                            )}
                        </dl>

                        <div className="mt-4 space-y-2">
                            {aktif.attachments?.length > 0 && (
                                <SecondaryButton onClick={() => setIsBukti(true)} className="w-full justify-center">
                                    Lihat {aktif.attachments.length} bukti pembayaran
                                </SecondaryButton>
                            )}
                            {aktif.status_approval === 'disetujui' && can('pembayaran.cetak') && (
                                <a href={route('keuangan.pemasukan.pembayaran.print', aktif.id)} target="_blank" rel="noreferrer"
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-stone-700 ring-1 ring-cream-300 transition hover:bg-cream-100 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:hover:bg-coal-600">
                                    <IkonPrinter /> Cetak Kwitansi
                                </a>
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5 lg:col-span-2">
                        <h3 className="mb-6 border-b border-cream-200 pb-3 text-sm font-semibold text-stone-800 dark:border-coal-600 dark:text-cream-50">Jalur Persetujuan</h3>

                        {/* Lingkaran kini murni indikator — tidak bisa diklik.
                            Digulir horizontal di layar sempit, bukan dimampatkan.
                            overflow-x-auto ikut memotong arah vertikal, padahal
                            ring-2 digambar di LUAR lingkaran — beri ruang 4px
                            di atas (pt-1) dan tarik balik dengan -mt-1. */}
                        <div className="-mx-1 -mt-1 overflow-x-auto px-1 pb-1 pt-1">
                            <div className="flex min-w-max items-start gap-x-2 gap-y-6">
                                {LANGKAH.map((step, i) => {
                                    const selesai = i <= langkahAktif;
                                    const berikutnya = !ditolak && i === langkahAktif + 1;

                                    return (
                                        <div key={step.id} className="flex items-start">
                                            <div className="flex w-24 flex-col items-center text-center sm:w-28">
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
                                                <div className={`mt-5 h-0.5 w-8 rounded sm:w-16 ${i < langkahAktif ? 'bg-claude-500' : 'bg-cream-300 dark:bg-coal-600'}`} />
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Aksi persetujuan sebagai tombol eksplisit. */}
                        {aktif.sumber === 'wali' && ['draft', 'diperiksa'].includes(aktif.status_approval) && (
                            <div className="mt-6 flex gap-2.5 rounded-xl bg-sky-50 p-3.5 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/20">
                                <svg className="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>
                                    Kiriman ini dibuat wali santri dari portal. Cocokkan bukti dengan mutasi rekening
                                    <strong> {aktif.rekening?.account_name || 'tujuan'}</strong> sebelum diproses.
                                    Bila tidak cocok, tolak dengan alasan yang jelas — wali akan melihatnya dan bisa mengirim ulang.
                                </span>
                            </div>
                        )}

                        <div className={`${aktif.sumber === 'wali' && ['draft', 'diperiksa'].includes(aktif.status_approval) ? 'mt-3' : 'mt-6'} rounded-xl bg-cream-100 p-4 dark:bg-coal-600/40`}>
                            {ditolak ? (
                                <div className="flex gap-2.5 text-sm">
                                    <svg className="mt-0.5 h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div className="min-w-0">
                                        <p className="font-medium text-red-600 dark:text-red-400">Pembayaran ditolak — tidak menambah saldo kas.</p>
                                        {aktif.alasan_tolak && (
                                            <p className="mt-1 break-words text-stone-700 dark:text-stone-200">Alasan: {aktif.alasan_tolak}</p>
                                        )}
                                        {aktif.penolak && (
                                            <p className="mt-1 text-xs text-stone-400">{aktif.penolak.name} · {waktu(aktif.ditolak_at)}</p>
                                        )}
                                    </div>
                                </div>
                            ) : !langkahBerikut ? (
                                <div className="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                                    <svg className="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span className="font-medium">Pembayaran sudah disetujui dan tercatat di buku kas.</span>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                                        Tahap berikutnya: <strong className="text-stone-800 dark:text-cream-100">{langkahBerikut.label}</strong>.
                                        {langkahBerikut.id === 'disetujui'
                                            ? ' Saldo kas bertambah setelah tahap ini.'
                                            : ' Dokumen akan terkunci dari perubahan.'}
                                        {!bisaLanjut && <><br /><span className="text-stone-400">Anda tidak memiliki wewenang untuk tahap ini.</span></>}
                                    </p>
                                    <div className="flex w-full shrink-0 flex-col-reverse gap-2 sm:w-auto sm:flex-row">
                                        {bisaTolak && (
                                            <button type="button" disabled={proses}
                                                onClick={() => { setPesanError(''); setAlasanTolak(''); setDialog({ jenis: 'tolak' }); }}
                                                className="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-red-600 ring-1 ring-red-200 transition hover:bg-red-50 disabled:opacity-50 dark:bg-coal-700 dark:text-red-400 dark:ring-red-500/30 dark:hover:bg-red-500/10">
                                                Tolak
                                            </button>
                                        )}
                                        <PrimaryButton
                                            disabled={!bisaLanjut || proses}
                                            onClick={() => { setPesanError(''); setDialog({ jenis: 'approve', stage: langkahBerikut.id, label: langkahBerikut.label }); }}
                                            className="justify-center disabled:cursor-not-allowed disabled:opacity-50">
                                            {infoLanjut?.label || 'Lanjutkan'}
                                        </PrimaryButton>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <Modal show={isModal} onClose={() => setIsModal(false)} maxWidth="2xl">
                {/* overflow-y-auto: tinggi maksimum sudah dibatasi Modal (90vh),
                    di sini yang membuat isinya bisa digulir. */}
                <form onSubmit={submit} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                        {isEdit ? 'Edit Pembayaran' : 'Catat Pembayaran Baru'}
                    </h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Kategori: <span className="font-medium text-claude-600 dark:text-claude-300">{daftarKategori[data.kategori]}</span>
                        {' · '}Tanda <span className="text-red-500">*</span> wajib diisi.
                    </p>

                    <div className="mt-5 space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel>Tanggal <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="date" value={data.payment_date} onChange={(e) => setData('payment_date', e.target.value)}
                                    className="mt-1.5 block w-full [color-scheme:light] dark:[color-scheme:dark]" />
                                <InputError message={errors.payment_date} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel>Rekening Tujuan <span className="text-red-500">*</span></InputLabel>
                                <div className="mt-1.5">
                                    <SearchableSelect placeholder="-- Pilih Rekening --"
                                        options={listRekening?.map((r) => ({ id: r.id, label: `${r.account_name} (${r.type})` })) || []}
                                        value={data.account_id} onChange={(id) => setData('account_id', id)} />
                                </div>
                                <InputError message={errors.account_id} className="mt-1.5" />
                            </div>
                        </div>

                        {perluSiswa && (
                            <div className={`grid grid-cols-1 gap-4 rounded-xl bg-cream-100 p-4 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600 ${data.kategori === 'tagihan' ? 'sm:grid-cols-2' : ''}`}>
                                <div>
                                    <InputLabel>{data.kategori === 'infaq_arahan' ? 'Santri Rumah Quran' : 'Siswa'} <span className="text-red-500">*</span></InputLabel>
                                    <div className="mt-1.5">
                                        <SearchableSelect placeholder="-- Cari nama atau NIS --" disabled={isEdit}
                                            options={listSiswa?.map((s) => ({ id: s.id, label: s.nis ? `${s.nis} · ${s.name}` : s.name })) || []}
                                            value={data.student_id}
                                            onChange={(id) => { setData('student_id', id); setData('invoice_id', ''); setData('nominal', ''); }} />
                                    </div>
                                    <InputError message={errors.student_id} className="mt-1.5" />
                                    {data.kategori === 'infaq_arahan' && (
                                        <p className="mt-1.5 text-xs text-stone-400">
                                            Infaq sukarela — nominal bebas, tidak terikat tagihan, dan tidak menimbulkan tunggakan.
                                            {!isEdit && listSiswa?.length === 0 && ' Belum ada santri di kelas Rumah Quran; tandai kelasnya sebagai Rumah Quran di Data Kelas.'}
                                        </p>
                                    )}
                                </div>

                                {data.kategori === 'tagihan' && (
                                    <div>
                                        <InputLabel>Tagihan <span className="text-red-500">*</span></InputLabel>
                                        <div className="mt-1.5">
                                            <SearchableSelect placeholder="-- Pilih Tagihan Aktif --" disabled={isEdit || !data.student_id}
                                                options={listTagihan?.filter((t) => t.student_id == data.student_id)
                                                    .map((t) => ({ id: t.id, label: `${t.invoice_number} · sisa ${formatCurrency(t.remaining_balance)}` })) || []}
                                                value={data.invoice_id}
                                                onChange={(id) => {
                                                    setData('invoice_id', id);
                                                    const t = listTagihan?.find((x) => x.id == id);
                                                    if (t) setData('nominal', Math.floor(t.remaining_balance).toString());
                                                }} />
                                        </div>
                                        <InputError message={errors.invoice_id} className="mt-1.5" />
                                    </div>
                                )}
                            </div>
                        )}

                        {!perluSiswa && (
                            <div>
                                <InputLabel>
                                    {namaPihak(data.kategori).label}
                                    {namaPihak(data.kategori).wajib && <span className="text-red-500"> *</span>}
                                </InputLabel>
                                <TextInput value={data.nama_pihak} onChange={(e) => setData('nama_pihak', e.target.value)}
                                    className="mt-1.5 block w-full"
                                    placeholder={namaPihak(data.kategori).contoh} />
                                {data.kategori === 'uang_masuk' && (
                                    <p className="mt-1 text-xs text-stone-400">
                                        Diisi manual karena calon siswa belum terdaftar — masih tahap seleksi.
                                    </p>
                                )}
                                <InputError message={errors.nama_pihak} className="mt-1.5" />
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel>Metode Pembayaran <span className="text-red-500">*</span></InputLabel>
                                <div className="relative">
                                    <select value={data.payment_method} onChange={(e) => setData('payment_method', e.target.value)} className={selectModal}>
                                        <option value="Tunai">Tunai</option>
                                        <option value="Transfer Bank">Transfer Bank</option>
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <InputError message={errors.payment_method} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel>Nominal (Rp) <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="text" inputMode="numeric"
                                    value={data.nominal ? data.nominal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                    onChange={(e) => setData('nominal', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1.5 block w-full" placeholder="0" />
                                <InputError message={errors.nominal} className="mt-1.5" />
                            </div>
                        </div>

                        {data.payment_method === 'Transfer Bank' && (
                            <div className="rounded-xl border-2 border-dashed border-cream-400 p-4 dark:border-coal-600">
                                <InputLabel>Bukti Transfer <span className="text-red-500">*</span></InputLabel>
                                <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf"
                                    onChange={(e) => setData('proof_of_payment', Array.from(e.target.files))}
                                    className="mt-2 w-full text-xs text-stone-500 file:mr-3 file:rounded-lg file:border-0 file:bg-claude-500/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-claude-600 hover:file:bg-claude-500/20 dark:text-stone-400 dark:file:text-claude-300" />
                                <p className="mt-1 text-xs text-stone-400">Maksimal 3 berkas · JPG, PNG, PDF · 2 MB per berkas.</p>
                                <InputError message={errors.proof_of_payment} className="mt-1.5" />

                                {data.proof_of_payment?.length > 0 && (
                                    <ul className="mt-2 list-inside list-disc text-xs text-stone-600 dark:text-stone-300">
                                        {data.proof_of_payment.map((f, i) => <li key={i} className="truncate">{f.name}</li>)}
                                    </ul>
                                )}
                                {isEdit && aktif?.attachments?.length > 0 && (
                                    <p className="mt-2 text-xs text-stone-400">
                                        {aktif.attachments.length} bukti tersimpan. Mengunggah berkas baru akan menggantinya.
                                    </p>
                                )}
                            </div>
                        )}

                        <div>
                            <InputLabel value="Catatan / Keterangan" />
                            <TextInput value={data.catatan} onChange={(e) => setData('catatan', e.target.value)}
                                className="mt-1.5 block w-full"
                                placeholder={{
                                    koperasi:     'Contoh: Penjualan seragam olahraga',
                                    infaq_arahan: 'Contoh: Infaq bulan November',
                                    uang_masuk:   'Contoh: Uang pendaftaran gelombang 1, TA 2027/2028',
                                }[data.kategori] || 'Keterangan tambahan…'} />
                            <InputError message={errors.catatan} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsModal(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>{isEdit ? 'Simpan Perubahan' : 'Simpan'}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isBukti} onClose={() => setIsBukti(false)} maxWidth="lg">
                <div className="flex max-h-[85vh] flex-col">
                    <div className="flex items-center justify-between gap-3 border-b border-cream-200 px-6 py-4 dark:border-coal-600">
                        <div className="min-w-0">
                            <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Bukti Pembayaran</h2>
                            {aktif && (
                                <p className="mt-0.5 truncate text-xs text-stone-400">{aktif.receipt_number} · {formatCurrency(aktif.nominal)}</p>
                            )}
                        </div>
                        <button type="button" onClick={() => setIsBukti(false)} title="Tutup" aria-label="Tutup"
                            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {/* Foto langsung ditampilkan supaya bisa dicocokkan dengan
                        mutasi rekening tanpa membuka tab baru. PDF tetap lewat tombol. */}
                    <ul className="space-y-3 overflow-y-auto p-6">
                        {aktif?.attachments?.map((f) => {
                            const gambar = /\.(jpe?g|png|webp|gif)$/i.test(f.file_name || '');
                            return (
                                <li key={f.id} className="overflow-hidden rounded-xl bg-cream-100 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                    {gambar && (
                                        <a href={f.url} target="_blank" rel="noreferrer" title="Buka ukuran penuh" className="block bg-white dark:bg-coal-800">
                                            <img src={f.url} alt={f.file_name} loading="lazy" className="mx-auto max-h-80 w-auto object-contain" />
                                        </a>
                                    )}
                                    <div className="flex items-center justify-between gap-3 p-3">
                                        <span className="truncate text-sm text-stone-700 dark:text-stone-200">{f.file_name}</span>
                                        <a href={f.url} target="_blank" rel="noreferrer"
                                            className="shrink-0 rounded-lg bg-claude-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-claude-600">
                                            {gambar ? 'Ukuran Penuh' : 'Buka'}
                                        </a>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </Modal>

            <Modal show={dialog?.jenis === 'tolak'} onClose={() => { setDialog(null); setPesanError(''); }} maxWidth="md">
                {dialog?.jenis === 'tolak' && aktif && (
                    <form className="p-6"
                        onSubmit={(e) => { e.preventDefault(); aksi(route('keuangan.pemasukan.pembayaran.tolak', aktif.id), 'post', { alasan_tolak: alasanTolak }); }}>
                        <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Tolak Pembayaran</h3>
                        <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            {aktif.receipt_number} · {formatCurrency(aktif.nominal)}
                            {aktif.sumber === 'wali' && ' · alasan di bawah akan dibaca wali santri.'}
                        </p>

                        <div className="mt-4 flex flex-wrap gap-1.5">
                            {ALASAN_CEPAT.map((a) => (
                                <button key={a} type="button" onClick={() => setAlasanTolak(a)}
                                    className={`rounded-full px-3 py-1.5 text-xs font-medium ring-1 transition ${
                                        alasanTolak === a
                                            ? 'bg-red-50 text-red-700 ring-red-300 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/40'
                                            : 'bg-white text-stone-600 ring-cream-300 hover:bg-cream-100 dark:bg-coal-600/40 dark:text-stone-300 dark:ring-coal-600'
                                    }`}>
                                    {a}
                                </button>
                            ))}
                        </div>

                        <textarea value={alasanTolak} onChange={(e) => setAlasanTolak(e.target.value)} rows={3} maxLength={255}
                            placeholder="Tulis alasan penolakan..."
                            className="mt-3 block w-full rounded-xl border-0 bg-white px-4 py-3 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-red-400 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600" />
                        <p className="mt-1 text-right text-[11px] text-stone-400">{alasanTolak.length}/255</p>

                        {pesanError && (
                            <div className="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{pesanError}</div>
                        )}

                        <div className="mt-5 flex justify-end gap-2">
                            <SecondaryButton type="button" onClick={() => { setDialog(null); setPesanError(''); }} disabled={proses}>Batal</SecondaryButton>
                            <button type="submit" disabled={proses || !alasanTolak.trim()}
                                className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                                Ya, Tolak
                            </button>
                        </div>
                    </form>
                )}
            </Modal>

            <Modal show={dialog !== null && dialog.jenis !== 'tolak'} onClose={() => { setDialog(null); setPesanError(''); }} maxWidth="sm">
                {dialog && dialog.jenis !== 'tolak' && (
                    <div className="p-6 text-center">
                        <div className={`mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full ${dialog.jenis === 'hapus' ? 'bg-red-50 text-red-600 dark:bg-red-500/10' : 'bg-claude-500/10 text-claude-600 dark:text-claude-300'}`}>
                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round" d={dialog.jenis === 'hapus'
                                    ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                                    : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'} />
                            </svg>
                        </div>
                        <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                            {dialog.jenis === 'hapus' ? 'Hapus Pembayaran' : 'Konfirmasi Persetujuan'}
                        </h3>
                        <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            {dialog.jenis === 'hapus'
                                ? 'Yakin menghapus catatan pembayaran ini? Bukti yang terlampir ikut terhapus.'
                                : dialog.stage === 'disetujui'
                                    ? 'Setelah disetujui, saldo kas bertambah dan tercatat permanen di buku kas.'
                                    : `Tandai pembayaran ini sebagai "${dialog.label}"?`}
                        </p>
                        {pesanError && (
                            <div className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{pesanError}</div>
                        )}
                        <div className="mt-5 flex justify-center gap-2">
                            <SecondaryButton onClick={() => { setDialog(null); setPesanError(''); }} disabled={proses}>Batal</SecondaryButton>
                            {dialog.jenis === 'hapus' ? (
                                <button onClick={() => aksi(route('keuangan.pemasukan.pembayaran.destroy', dialog.id), 'delete')} disabled={proses}
                                    className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">Ya, Hapus</button>
                            ) : (
                                <PrimaryButton onClick={() => aksi(route('keuangan.pemasukan.pembayaran.approve', aktif.id), 'post', { stage: dialog.stage })} disabled={proses}>
                                    Ya, Lanjutkan
                                </PrimaryButton>
                            )}
                        </div>
                    </div>
                )}
            </Modal>
        </KeuanganLayout>
    );
}
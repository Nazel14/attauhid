import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import { formatCurrency } from '@/Utils/formatCurrency';

export default function DataRekening({ rekening, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [typeFilter, setTypeFilter] = useState(filters?.typeFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const { data, setData, processing, errors, reset, clearErrors } = useForm({
        account_name: '',
        type: 'Bank',
        balance: '',
        account_number: '',
        account_holder: '',
        catatan_bayar: '',
        is_public: false,
        qris: null,        // File baru yang diunggah
        hapus_qris: false, // Menandai QRIS lama dihapus tanpa diganti
    });

    // Path QRIS yang tersimpan, untuk pratinjau saat edit.
    const [qrisLama, setQrisLama] = useState(null);

    // Lewati render pertama — versi asli mengirim request duplikat
    // setiap kali halaman dibuka.
    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.rekening'),
                { search, typeFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, typeFilter, sortField, sortDirection]);

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

    const openAddModal = () => { setIsEditing(false); setEditId(null); reset(); clearErrors(); setQrisLama(null); setIsModalOpen(true); };

    const openEditModal = (item) => {
        setIsEditing(true); setEditId(item.id); clearErrors();
        setData({
            account_name:   item.account_name,
            type:           item.type,
            balance:        Math.floor(item.balance).toString(),
            account_number: item.account_number || '',
            account_holder: item.account_holder || '',
            catatan_bayar:  item.catatan_bayar || '',
            is_public:      Boolean(item.is_public),
            qris:           null,
            hapus_qris:     false,
        });
        setQrisLama(item.qris_path || null);
        setIsModalOpen(true);
    };

    const confirmDelete = (id) => { setDeleteError(''); setDeleteId(id); setIsDeleteModalOpen(true); };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.rekening.destroy', deleteId), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteId(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const submit = (e) => {
        e.preventDefault();

        // router.post + forceFormData, bukan put(): PHP tidak membaca berkas
        // dari request PUT, jadi method-nya di-spoof lewat field _method.
        const payload = {
            ...data,
            is_public:  data.is_public ? 1 : 0,
            hapus_qris: data.hapus_qris ? 1 : 0,
            ...(isEditing ? { _method: 'put' } : {}),
        };

        const url = isEditing
            ? route('keuangan.master-data.rekening.update', editId)
            : route('keuangan.master-data.rekening.store');

        router.post(url, payload, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { setIsModalOpen(false); reset(); setQrisLama(null); },
        });
    };

    const typeStyles = (type) => ({
        'Tunai':    'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'Bank':     'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'E-Wallet': 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/20',
    }[type] || 'bg-cream-200 text-stone-600 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500');

    const adaAksi = can('master.rekening.edit') || can('master.rekening.hapus');

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-cream-300 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';

    const ChevronBawah = ({ modal = false }) => (
        <svg className={`pointer-events-none absolute ${modal ? 'right-3.5 top-[calc(50%+3px)]' : 'right-3 top-1/2'} h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500`}
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Tidak ada data rekening ditemukan</p>
            <p className="mt-1 text-xs text-stone-400">Ubah filter atau tambahkan rekening baru</p>
        </>
    );

    return (
        <KeuanganLayout>
            <Head title="Data Rekening" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-cream-300 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Data Rekening</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola rekening kas, bank, dan e-wallet sekolah.</p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-sm sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama rekening..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="relative sm:w-48">
                            <select value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Tipe Kas</option>
                                <option value="Tunai">Tunai</option>
                                <option value="Bank">Bank</option>
                                <option value="E-Wallet">E-Wallet</option>
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {can('master.rekening.buat') && (
                        <PrimaryButton onClick={openAddModal} className="h-10 w-full justify-center sm:w-auto">Tambah Rekening</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {rekening.data.length > 0 ? rekening.data.map((item) => (
                        <div key={item.id} className="overflow-hidden rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600">
                            <div className="p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <p className="min-w-0 truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{item.account_name}</p>
                                    <span className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${typeStyles(item.type)}`}>{item.type}</span>
                                </div>
                                <p className="mt-2 text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    {formatCurrency(item.balance)}
                                    <span className="ml-1 text-[11px] font-normal text-stone-400">saldo terkini</span>
                                </p>
                            </div>

                            {adaAksi && (
                                <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600">
                                    {can('master.rekening.edit') && (
                                        <button onClick={() => openEditModal(item)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300 ${can('master.rekening.hapus') ? 'border-r border-cream-200 dark:border-coal-600' : 'col-span-2'}`}>
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                    )}
                                    {can('master.rekening.hapus') && (
                                        <button onClick={() => confirmDelete(item.id)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 ${!can('master.rekening.edit') ? 'col-span-2' : ''}`}>
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    )) : (
                        <div className="rounded-2xl py-14 text-center ring-1 ring-cream-300 dark:ring-coal-600">{kosong}</div>
                    )}
                </div>

                {/* ===== TABEL — DESKTOP ===== */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 lg:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th onClick={() => handleSort('account_name')} className={thCls}><div className="flex items-center">Nama Rekening <SortIcon field="account_name" /></div></th>
                                <th onClick={() => handleSort('type')} className={thCls}><div className="flex items-center">Tipe Kas <SortIcon field="type" /></div></th>
                                <th onClick={() => handleSort('balance')} className={thCls}><div className="flex items-center">Saldo Terkini <SortIcon field="balance" /></div></th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {rekening.data.length > 0 ? rekening.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.account_name}</td>
                                    <td className="px-5 py-4">
                                        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${typeStyles(item.type)}`}>{item.type}</span>
                                    </td>
                                    <td className="px-5 py-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">{formatCurrency(item.balance)}</td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.rekening.edit') && (
                                                    <button onClick={() => openEditModal(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        Edit
                                                    </button>
                                                )}
                                                {can('master.rekening.hapus') && (
                                                    <button onClick={() => confirmDelete(item.id)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 4 : 3} className="px-6 py-14 text-center">{kosong}</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">
                        Menampilkan {rekening.from || 0}–{rekening.to || 0} dari {rekening.total} entri
                    </p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {rekening.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${
                                    link.active ? 'bg-claude-500 font-medium text-white'
                                        : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Data Rekening' : 'Tambah Data Rekening'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel htmlFor="account_name">Nama Rekening <span className="text-red-500">*</span></InputLabel>
                            <TextInput id="account_name" value={data.account_name} onChange={(e) => setData('account_name', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: Rekening BSI Yayasan" />
                            <InputError message={errors.account_name} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="type">Tipe Kas <span className="text-red-500">*</span></InputLabel>
                                <div className="relative">
                                    <select id="type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={selectModal}>
                                        <option value="Tunai">Tunai</option>
                                        <option value="Bank">Bank</option>
                                        <option value="E-Wallet">E-Wallet</option>
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <InputError message={errors.type} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="balance">Saldo Awal (Rp) <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="balance" type="text" inputMode="numeric"
                                    value={data.balance ? data.balance.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                    onChange={(e) => setData('balance', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1.5 block w-full" placeholder="Contoh: 1.000.000" />
                                <p className="mt-1 text-xs text-stone-400">Gunakan 0 bila rekening baru tanpa saldo.</p>
                                <InputError message={errors.balance} className="mt-1.5" />
                            </div>
                        </div>

                        {/* ===== INFO PEMBAYARAN UNTUK WALI ===== */}
                        {data.type !== 'Tunai' && (
                            <div className="rounded-xl bg-cream-50 p-4 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                <p className="text-sm font-semibold text-stone-700 dark:text-cream-100">Info Pembayaran</p>
                                <p className="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                    Ditampilkan di portal wali siswa bila diaktifkan di bawah.
                                </p>

                                <div className="mt-4 space-y-4">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel htmlFor="account_number">Nomor Rekening / Akun</InputLabel>
                                            <TextInput id="account_number" value={data.account_number}
                                                onChange={(e) => setData('account_number', e.target.value)}
                                                className="mt-1.5 block w-full" placeholder="7123456789" />
                                            <InputError message={errors.account_number} className="mt-1.5" />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="account_holder">Atas Nama</InputLabel>
                                            <TextInput id="account_holder" value={data.account_holder}
                                                onChange={(e) => setData('account_holder', e.target.value)}
                                                className="mt-1.5 block w-full" placeholder="Yayasan Attauhid" />
                                            <InputError message={errors.account_holder} className="mt-1.5" />
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="qris">Gambar QRIS</InputLabel>

                                        {(qrisLama && !data.hapus_qris && !data.qris) && (
                                            <div className="mt-2 flex items-center gap-3">
                                                <img src={`/storage/${qrisLama}`} alt="QRIS tersimpan"
                                                    className="h-20 w-20 rounded-lg bg-white object-contain ring-1 ring-cream-300 dark:ring-coal-600" />
                                                <button type="button" onClick={() => setData('hapus_qris', true)}
                                                    className="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 ring-1 ring-red-200 transition hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10">
                                                    Hapus QRIS
                                                </button>
                                            </div>
                                        )}

                                        {data.hapus_qris && (
                                            <p className="mt-2 text-xs text-red-500">
                                                QRIS akan dihapus saat disimpan.{' '}
                                                <button type="button" onClick={() => setData('hapus_qris', false)} className="underline">Batalkan</button>
                                            </p>
                                        )}

                                        <input id="qris" type="file" accept="image/*"
                                            onChange={(e) => { setData('qris', e.target.files[0] || null); setData('hapus_qris', false); }}
                                            className="mt-2 block w-full text-xs text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-claude-500 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-claude-600 dark:text-stone-300" />
                                        <p className="mt-1 text-xs text-stone-400">JPG/PNG/WebP, maksimal 2 MB.</p>
                                        <InputError message={errors.qris} className="mt-1.5" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="catatan_bayar">Catatan untuk Wali</InputLabel>
                                        <TextInput id="catatan_bayar" value={data.catatan_bayar}
                                            onChange={(e) => setData('catatan_bayar', e.target.value)}
                                            className="mt-1.5 block w-full" placeholder="Mohon transfer sesuai nominal tertera." />
                                        <InputError message={errors.catatan_bayar} className="mt-1.5" />
                                    </div>

                                    <label className="flex cursor-pointer items-start gap-3 rounded-xl bg-white p-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                        <input type="checkbox" checked={data.is_public}
                                            onChange={(e) => setData('is_public', e.target.checked)}
                                            className="mt-0.5 h-4 w-4 rounded border-cream-400 text-claude-500 focus:ring-claude-500" />
                                        <span className="min-w-0">
                                            <span className="block text-sm font-medium text-stone-700 dark:text-cream-100">Tampilkan di portal wali</span>
                                            <span className="block text-xs text-stone-500 dark:text-stone-400">
                                                Nomor rekening dan QRIS di atas akan terlihat oleh seluruh wali siswa
                                                yang masih punya tagihan. Saldo rekening tidak ikut ditampilkan.
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsModalOpen(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>{isEditing ? 'Simpan Perubahan' : 'Simpan Data'}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDeleteModalOpen} onClose={() => setIsDeleteModalOpen(false)} maxWidth="sm">
                <div className="p-6 text-center">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Konfirmasi Hapus</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus rekening ini?</p>
                    {deleteError && (
                        <div className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{deleteError}</div>
                    )}
                    <div className="mt-5 flex justify-center gap-2">
                        <SecondaryButton onClick={() => setIsDeleteModalOpen(false)}>Batal</SecondaryButton>
                        <button onClick={executeDelete} disabled={deleteError !== ''}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
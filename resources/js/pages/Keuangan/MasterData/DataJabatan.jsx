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

export default function DataJabatan({ jabatan, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nama_jabatan: '',
        description: '',
        gaji_pokok_default: '',
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.jabatan'),
                { search, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, sortField, sortDirection]);

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

    const openAddModal = () => { setIsEditing(false); setEditId(null); reset(); clearErrors(); setIsModalOpen(true); };

    const openEditModal = (item) => {
        setIsEditing(true); setEditId(item.id); clearErrors();
        setData({
            nama_jabatan: item.nama_jabatan,
            description: item.description || '',
            gaji_pokok_default: Math.floor(item.gaji_pokok_default).toString(),
        });
        setIsModalOpen(true);
    };

    const confirmDelete = (id) => { setDeleteError(''); setDeleteId(id); setIsDeleteModalOpen(true); };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.jabatan.destroy', deleteId), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteId(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModalOpen(false); reset(); }, preserveScroll: true };
        if (isEditing) put(route('keuangan.master-data.jabatan.update', editId), opts);
        else post(route('keuangan.master-data.jabatan.store'), opts);
    };

    const adaAksi = can('master.jabatan.edit') || can('master.jabatan.hapus');

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Tidak ada data jabatan ditemukan</p>
            <p className="mt-1 text-xs text-stone-400">Ubah kata kunci pencarian atau tambahkan jabatan baru</p>
        </>
    );

    return (
        <KeuanganLayout>
            <Head title="Data Jabatan" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-stone-200 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Data Jabatan</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola jabatan pegawai dan gaji pokok bawaannya.</p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative sm:max-w-sm sm:flex-1">
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama jabatan..." className={`${fieldCls} pl-9`} />
                        <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    {can('master.jabatan.buat') && (
                        <PrimaryButton onClick={openAddModal} className="h-10 w-full justify-center sm:w-auto">Tambah Jabatan</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {jabatan.data.length > 0 ? jabatan.data.map((item) => (
                        <div key={item.id} className="overflow-hidden rounded-2xl ring-1 ring-stone-200 dark:ring-coal-600">
                            <div className="p-4">
                                <p className="text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nama_jabatan}</p>
                                {item.description && (
                                    <p className="mt-0.5 line-clamp-2 text-xs text-stone-400">{item.description}</p>
                                )}
                                <p className="mt-2 text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    {formatCurrency(item.gaji_pokok_default)}
                                    <span className="ml-1 text-[11px] font-normal text-stone-400">gaji pokok</span>
                                </p>
                            </div>

                            {adaAksi && (
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-600">
                                    {can('master.jabatan.edit') && (
                                        <button onClick={() => openEditModal(item)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300 ${can('master.jabatan.hapus') ? 'border-r border-stone-100 dark:border-coal-600' : 'col-span-2'}`}>
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                    )}
                                    {can('master.jabatan.hapus') && (
                                        <button onClick={() => confirmDelete(item.id)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 ${!can('master.jabatan.edit') ? 'col-span-2' : ''}`}>
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
                        <div className="rounded-2xl py-14 text-center ring-1 ring-stone-200 dark:ring-coal-600">{kosong}</div>
                    )}
                </div>

                {/* ===== TABEL — DESKTOP ===== */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 lg:block">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('nama_jabatan')} className={thCls}><div className="flex items-center">Nama Jabatan <SortIcon field="nama_jabatan" /></div></th>
                                <th className={thPlain}>Keterangan</th>
                                <th onClick={() => handleSort('gaji_pokok_default')} className={thCls}><div className="flex items-center">Gaji Pokok Default <SortIcon field="gaji_pokok_default" /></div></th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {jabatan.data.length > 0 ? jabatan.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nama_jabatan}</td>
                                    <td className="max-w-xs truncate px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{item.description || '-'}</td>
                                    <td className="px-5 py-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">{formatCurrency(item.gaji_pokok_default)}</td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.jabatan.edit') && (
                                                    <button onClick={() => openEditModal(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        Edit
                                                    </button>
                                                )}
                                                {can('master.jabatan.hapus') && (
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
                        Menampilkan {jabatan.from || 0}–{jabatan.to || 0} dari {jabatan.total} entri
                    </p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {jabatan.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${
                                    link.active ? 'bg-claude-500 font-medium text-white'
                                        : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Data Jabatan' : 'Tambah Data Jabatan'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel htmlFor="nama_jabatan">Nama Jabatan <span className="text-red-500">*</span></InputLabel>
                            <TextInput id="nama_jabatan" value={data.nama_jabatan} onChange={(e) => setData('nama_jabatan', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: Guru, Bendahara, Satpam" />
                            <InputError message={errors.nama_jabatan} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel htmlFor="gaji_pokok_default">Gaji Pokok Default (Rp) <span className="text-red-500">*</span></InputLabel>
                            <TextInput id="gaji_pokok_default" type="text" inputMode="numeric"
                                value={data.gaji_pokok_default ? data.gaji_pokok_default.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => setData('gaji_pokok_default', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" placeholder="Contoh: 3.500.000" />
                            <InputError message={errors.gaji_pokok_default} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel htmlFor="description" value="Keterangan (Opsional)" />
                            <TextInput id="description" value={data.description} onChange={(e) => setData('description', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Deskripsi singkat jabatan" />
                            <InputError message={errors.description} className="mt-1.5" />
                        </div>
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
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus data jabatan ini?</p>
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
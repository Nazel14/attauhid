import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
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
    const thCls = 'cursor-pointer px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';

    return (
        <KeuanganLayout>
            <Head title="Data Jabatan" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Master Data Jabatan</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola jabatan pegawai dan gaji pokok bawaannya.</p>
            </div>

            <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex w-full max-w-md items-center rounded-xl bg-white px-3.5 py-2 ring-1 ring-stone-200 focus-within:ring-2 focus-within:ring-claude-500 dark:bg-coal-900 dark:ring-coal-600">
                        <svg className="h-4 w-4 flex-shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama jabatan..."
                            className="w-full border-none bg-transparent py-0.5 pl-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-0 dark:text-cream-100" />
                    </div>
                    {can('master.jabatan.buat') && (
                        <PrimaryButton onClick={openAddModal} className="w-full sm:w-auto">Tambah Jabatan</PrimaryButton>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('nama_jabatan')} className={thCls}><div className="flex items-center">Nama Jabatan <SortIcon field="nama_jabatan" /></div></th>
                                <th className="px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Keterangan</th>
                                <th onClick={() => handleSort('gaji_pokok_default')} className={thCls}><div className="flex items-center">Gaji Pokok Default <SortIcon field="gaji_pokok_default" /></div></th>
                                {adaAksi && <th className="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {jabatan.data.length > 0 ? jabatan.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="px-6 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nama_jabatan}</td>
                                    <td className="max-w-xs truncate px-6 py-4 text-sm text-stone-600 dark:text-stone-300">{item.description || '-'}</td>
                                    <td className="px-6 py-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">{formatCurrency(item.gaji_pokok_default)}</td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            {can('master.jabatan.edit') && (
                                                <button onClick={() => openEditModal(item)} className="mr-1 rounded-lg px-2.5 py-1.5 font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300">Edit</button>
                                            )}
                                            {can('master.jabatan.hapus') && (
                                                <button onClick={() => confirmDelete(item.id)} className="rounded-lg px-2.5 py-1.5 font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">Hapus</button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 4 : 3} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada data jabatan ditemukan</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">
                        Menampilkan {jabatan.from || 0}–{jabatan.to || 0} dari {jabatan.total} entri
                    </p>
                    <div className="flex flex-wrap gap-1">
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
                <form onSubmit={submit} className="p-6">
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
                            <TextInput id="gaji_pokok_default" type="text"
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
                    <svg className="mx-auto mb-3 h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
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
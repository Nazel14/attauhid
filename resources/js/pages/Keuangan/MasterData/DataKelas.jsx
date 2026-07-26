import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';

export default function DataKelas({ kelas, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'nama_kelas');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nama_kelas: '',
        tingkat: '',
        kapasitas: 20,
        is_active: true,
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.kelas.index'),
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
            nama_kelas: item.nama_kelas,
            tingkat: item.tingkat || '',
            kapasitas: item.kapasitas,
            is_active: Boolean(item.is_active),
        });
        setIsModalOpen(true);
    };

    const confirmDelete = (id) => { setDeleteError(''); setDeleteId(id); setIsDeleteModalOpen(true); };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.kelas.destroy', deleteId), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteId(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModalOpen(false); reset(); }, preserveScroll: true };
        if (isEditing) put(route('keuangan.master-data.kelas.update', editId), opts);
        else post(route('keuangan.master-data.kelas.store'), opts);
    };

    const adaAksi = can('master.kelas.edit') || can('master.kelas.hapus');
    const thCls = 'cursor-pointer px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';

    return (
        <KeuanganLayout>
            <Head title="Data Kelas" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Master Data Kelas</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola ruang kelas beserta kapasitasnya.</p>
            </div>

            <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex w-full max-w-md items-center rounded-xl bg-white px-3.5 py-2 ring-1 ring-stone-200 focus-within:ring-2 focus-within:ring-claude-500 dark:bg-coal-900 dark:ring-coal-600">
                        <svg className="h-4 w-4 flex-shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama kelas atau tingkat..."
                            className="w-full border-none bg-transparent py-0.5 pl-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-0 dark:text-cream-100" />
                    </div>
                    {can('master.kelas.buat') && (
                        <PrimaryButton onClick={openAddModal} className="w-full sm:w-auto">Tambah Kelas</PrimaryButton>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('nama_kelas')} className={thCls}><div className="flex items-center">Nama Kelas <SortIcon field="nama_kelas" /></div></th>
                                <th onClick={() => handleSort('tingkat')} className={thCls}><div className="flex items-center">Tingkat <SortIcon field="tingkat" /></div></th>
                                <th className="px-6 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Siswa / Kapasitas</th>
                                <th className="px-6 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Pegawai</th>
                                <th onClick={() => handleSort('is_active')} className={`${thCls} text-center`}><div className="flex items-center justify-center">Status <SortIcon field="is_active" /></div></th>
                                {adaAksi && <th className="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {kelas.data.length > 0 ? kelas.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nama_kelas}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">{item.tingkat || '-'}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-center text-sm">
                                        <span className={`font-semibold ${item.siswas_count >= item.kapasitas ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'}`}>{item.siswas_count}</span>
                                        <span className="text-stone-400"> / {item.kapasitas}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-center text-sm font-medium text-blue-600 dark:text-blue-400">{item.pegawais_count} staf</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-center">
                                        {item.is_active ? (
                                            <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
                                        ) : (
                                            <span className="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">Non-Aktif</span>
                                        )}
                                    </td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            {can('master.kelas.edit') && (
                                                <button onClick={() => openEditModal(item)} className="mr-1 rounded-lg px-2.5 py-1.5 font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300">Edit</button>
                                            )}
                                            {can('master.kelas.hapus') && (
                                                <button onClick={() => confirmDelete(item.id)} className="rounded-lg px-2.5 py-1.5 font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">Hapus</button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 6 : 5} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada data kelas ditemukan</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {kelas.from || 0}–{kelas.to || 0} dari {kelas.total} entri</p>
                    <div className="flex flex-wrap gap-1">
                        {kelas.links.map((link, i) => (
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
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Data Kelas' : 'Tambah Data Kelas'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="nama_kelas">Nama Kelas <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="nama_kelas" value={data.nama_kelas} onChange={(e) => setData('nama_kelas', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: TK A - Apel" />
                                <InputError message={errors.nama_kelas} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="tingkat">Tingkat Kelas</InputLabel>
                                <TextInput id="tingkat" value={data.tingkat} onChange={(e) => setData('tingkat', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: I" />
                                <InputError message={errors.tingkat} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="kapasitas">Kapasitas Maksimal <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="kapasitas" type="number" min="1" value={data.kapasitas} onChange={(e) => setData('kapasitas', e.target.value)}
                                    className="mt-1.5 block w-full" />
                                <InputError message={errors.kapasitas} className="mt-1.5" />
                            </div>
                            <div className="flex items-end">
                                <label className="mb-2.5 flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-4 w-4 rounded border-stone-300 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-900" />
                                    <span className="text-sm text-stone-600 dark:text-stone-300">Kelas Sedang Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsModalOpen(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>{isEditing ? 'Simpan Perubahan' : 'Simpan Kelas'}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDeleteModalOpen} onClose={() => setIsDeleteModalOpen(false)} maxWidth="sm">
                <div className="p-6 text-center">
                    <svg className="mx-auto mb-3 h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Data Kelas</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus kelas ini?</p>
                    {deleteError && (
                        <div className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{deleteError}</div>
                    )}
                    <div className="mt-5 flex justify-center gap-2">
                        <SecondaryButton onClick={() => setIsDeleteModalOpen(false)}>Kembali</SecondaryButton>
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
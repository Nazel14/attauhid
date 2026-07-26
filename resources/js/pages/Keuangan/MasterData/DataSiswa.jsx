import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import SearchableSelect from '@/Components/SearchableSelect';

export default function DataSiswa({ siswa, listAngkatan, listKelas, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [typeFilter, setTypeFilter] = useState(filters?.typeFilter || '');
    const [angkatanFilter, setAngkatanFilter] = useState(filters?.angkatanFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nis: '',
        name: '',
        student_type: 'Reguler',
        class: new Date().getFullYear(),
        class_id: '',
        parent_name: '',
        parent_contact: '',
        is_active: true,
        is_raport_held: false,
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.siswa'),
                { search, typeFilter, angkatanFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, typeFilter, angkatanFilter, sortField, sortDirection]);

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
            nis: item.nis,
            name: item.name,
            student_type: item.student_type,
            class: item.class,
            class_id: item.class_id || '',
            parent_name: item.parent_name,
            parent_contact: item.parent_contact,
            is_active: Boolean(item.is_active),
            is_raport_held: Boolean(item.is_raport_held),
        });
        setIsModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModalOpen(false); reset(); }, preserveScroll: true };
        if (isEditing) put(route('keuangan.master-data.siswa.update', editId), opts);
        else post(route('keuangan.master-data.siswa.store'), opts);
    };

    // Tidak ada tombol hapus — permission master.siswa.hapus memang tidak ada.
    const adaAksi = can('master.siswa.edit');
    const thCls = 'cursor-pointer px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const selectCls = 'w-full rounded-xl border-0 bg-white px-3.5 py-2 text-sm text-stone-700 ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600 sm:w-auto';

    return (
        <KeuanganLayout>
            <Head title="Data Siswa" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Master Data Siswa</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola data siswa, kelas, dan kontak orang tua.</p>
            </div>

            <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-1 flex-col gap-3 sm:flex-row">
                        <div className="flex w-full max-w-sm items-center rounded-xl bg-white px-3.5 py-2 ring-1 ring-stone-200 focus-within:ring-2 focus-within:ring-claude-500 dark:bg-coal-900 dark:ring-coal-600">
                            <svg className="h-4 w-4 flex-shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari NIS atau nama siswa..."
                                className="w-full border-none bg-transparent py-0.5 pl-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-0 dark:text-cream-100" />
                        </div>
                        <select value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className={selectCls}>
                            <option value="">Semua Tipe</option>
                            <option value="Reguler">Reguler</option>
                            <option value="Fullday">Fullday</option>
                        </select>
                        <select value={angkatanFilter} onChange={(e) => setAngkatanFilter(e.target.value)} className={selectCls}>
                            <option value="">Semua Angkatan</option>
                            {listAngkatan.map((tahun, i) => (
                                <option key={i} value={tahun}>Angkatan {tahun}</option>
                            ))}
                        </select>
                    </div>
                    {can('master.siswa.buat') && (
                        <PrimaryButton onClick={openAddModal} className="w-full lg:w-auto">Tambah Siswa</PrimaryButton>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('nis')} className={thCls}><div className="flex items-center">NIS <SortIcon field="nis" /></div></th>
                                <th onClick={() => handleSort('name')} className={thCls}><div className="flex items-center">Nama Siswa <SortIcon field="name" /></div></th>
                                <th onClick={() => handleSort('class')} className={thCls}><div className="flex items-center">Kelas & Angkatan <SortIcon field="class" /></div></th>
                                <th className="px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Orang Tua</th>
                                <th onClick={() => handleSort('is_active')} className={thCls}><div className="flex items-center">Status <SortIcon field="is_active" /></div></th>
                                {adaAksi && <th className="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {siswa.data.length > 0 ? siswa.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nis}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-stone-700 dark:text-stone-200">{item.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        {item.kelas ? (
                                            <span className="font-medium text-claude-600 dark:text-claude-300">{item.kelas.nama_kelas}</span>
                                        ) : (
                                            <span className="text-xs italic text-stone-400">Belum ada kelas</span>
                                        )}
                                        <br />
                                        <span className="text-[10px] uppercase tracking-wider text-stone-400">Angkatan {item.class} · {item.student_type}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                                        {item.parent_name}
                                        <br />
                                        <span className="text-xs text-stone-400">{item.parent_contact}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4">
                                        {item.is_active ? (
                                            <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
                                        ) : (
                                            <span className="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">Non-Aktif</span>
                                        )}
                                        {item.is_raport_held && (
                                            <span className="ml-1.5 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">Rapor Ditahan</span>
                                        )}
                                    </td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <button onClick={() => openEditModal(item)} className="rounded-lg px-2.5 py-1.5 font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300">Ubah</button>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 6 : 5} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada data siswa ditemukan</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {siswa.from || 0}–{siswa.to || 0} dari {siswa.total} entri</p>
                    <div className="flex flex-wrap gap-1">
                        {siswa.links.map((link, i) => (
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

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="2xl">
                <form onSubmit={submit} className="p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Data Siswa' : 'Tambah Data Siswa'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="nis">NIS <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="nis" value={data.nis} onChange={(e) => setData('nis', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={errors.nis} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="name">Nama Lengkap <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={errors.name} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="student_type">Tipe Siswa <span className="text-red-500">*</span></InputLabel>
                                <select id="student_type" value={data.student_type} onChange={(e) => setData('student_type', e.target.value)}
                                    className="mt-1.5 block w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-stone-800 shadow-sm ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700">
                                    <option value="Reguler">Reguler</option>
                                    <option value="Fullday">Fullday</option>
                                </select>
                                <InputError message={errors.student_type} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="class">Angkatan <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="class" value={data.class} onChange={(e) => setData('class', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: 2024" />
                                <InputError message={errors.class} className="mt-1.5" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="class_id">Ruangan / Kelas Saat Ini</InputLabel>
                            <div className="mt-1.5">
                                <SearchableSelect
                                    placeholder="-- Pilih Kelas Siswa --"
                                    options={listKelas?.map((k) => ({ id: k.id, label: `${k.nama_kelas}${k.tingkat ? ` (${k.tingkat})` : ''}` })) || []}
                                    value={data.class_id}
                                    onChange={(id) => setData('class_id', id)}
                                />
                            </div>
                            <p className="mt-1 text-xs text-stone-400">Kosongkan bila siswa belum mendapat pembagian kelas.</p>
                            <InputError message={errors.class_id} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="parent_name">Nama Orang Tua <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="parent_name" value={data.parent_name} onChange={(e) => setData('parent_name', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={errors.parent_name} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="parent_contact">Kontak Orang Tua <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="parent_contact" value={data.parent_contact} onChange={(e) => setData('parent_contact', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="0812..." />
                                <InputError message={errors.parent_contact} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-3 border-t border-stone-100 pt-4 dark:border-coal-600 sm:grid-cols-2">
                            <label className="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-stone-300 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-900" />
                                <span className="text-sm text-stone-600 dark:text-stone-300">Siswa Aktif</span>
                            </label>
                            <label className="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" checked={data.is_raport_held} onChange={(e) => setData('is_raport_held', e.target.checked)}
                                    className="h-4 w-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-coal-600 dark:bg-coal-900" />
                                <span className="text-sm text-stone-600 dark:text-stone-300">Rapor Ditahan (ada tunggakan)</span>
                            </label>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsModalOpen(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>{isEditing ? 'Simpan Perubahan' : 'Simpan Data'}</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </KeuanganLayout>
    );
}
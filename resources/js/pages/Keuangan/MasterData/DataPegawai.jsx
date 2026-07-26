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
import { formatCurrency } from '@/Utils/formatCurrency';

export default function DataPegawai({ pegawai, listJabatan, listTunjangan, listPotongan, listKelas, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.statusFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const [isSalaryModalOpen, setIsSalaryModalOpen] = useState(false);
    const [selectedPegawai, setSelectedPegawai] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nip: '',
        name: '',
        position_id: '',
        class_id: '',
        is_active: true,
    });

    const salaryForm = useForm({ allowances: [], deductions: [] });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.pegawai'),
                { search, statusFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, statusFilter, sortField, sortDirection]);

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
            nip: item.nip || '',
            name: item.name,
            position_id: item.position_id || '',
            class_id: item.class_id || '',
            is_active: Boolean(item.is_active),
        });
        setIsModalOpen(true);
    };

    const confirmDelete = (id) => { setDeleteError(''); setDeleteId(id); setIsDeleteModalOpen(true); };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.pegawai.destroy', deleteId), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteId(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModalOpen(false); reset(); }, preserveScroll: true };
        if (isEditing) put(route('keuangan.master-data.pegawai.update', editId), opts);
        else post(route('keuangan.master-data.pegawai.store'), opts);
    };

    const openSalaryModal = (item) => {
        setSelectedPegawai(item);
        salaryForm.setData({
            allowances: item.default_allowances?.map((a) => ({ id: a.id, nominal_override: a.pivot?.nominal_override ?? '' })) || [],
            deductions: item.default_deductions?.map((d) => ({ id: d.id, nominal_override: d.pivot?.nominal_override ?? '' })) || [],
        });
        setIsSalaryModalOpen(true);
    };

    const addRow = (type) => salaryForm.setData(type, [...salaryForm.data[type], { id: '', nominal_override: '' }]);

    const removeRow = (type, index) => {
        const next = [...salaryForm.data[type]];
        next.splice(index, 1);
        salaryForm.setData(type, next);
    };

    const handleRowChange = (type, index, field, value) => {
        const next = [...salaryForm.data[type]];
        next[index] = { ...next[index], [field]: value };
        salaryForm.setData(type, next);
    };

    const submitSalary = (e) => {
        e.preventDefault();
        salaryForm.post(route('keuangan.master-data.pegawai.update_salary', selectedPegawai.id), {
            onSuccess: () => setIsSalaryModalOpen(false),
            preserveScroll: true,
        });
    };

    // Versi asli: hasPermission('...edit' || hasPermission('...hapus')) — kurung salah tempat.
    const adaAksi = can('master.pegawai.kelola_gaji') || can('master.pegawai.edit') || can('master.pegawai.hapus');
    const thCls = 'cursor-pointer px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';

    const KomponenRow = ({ type, row, idx, list, warna, labelKey }) => {
        const terpilih = list?.find((x) => String(x.id) === String(row.id));
        return (
            <div className="flex items-start gap-2 rounded-xl bg-stone-50 p-3 dark:bg-coal-900/50">
                <div className="min-w-0 flex-1">
                    <SearchableSelect
                        placeholder={`-- Pilih ${type === 'allowances' ? 'Tunjangan' : 'Potongan'} --`}
                        options={list?.map((x) => ({ id: x.id, label: `${x[labelKey]} · ${formatCurrency(x.nominal_default)}` })) || []}
                        value={row.id}
                        onChange={(val) => handleRowChange(type, idx, 'id', val)}
                    />
                    <InputError message={salaryForm.errors[`${type}.${idx}.id`]} className="mt-1" />
                    {terpilih && (
                        <p className={`mt-1 pl-1 text-[10px] font-medium ${warna}`}>Default: {formatCurrency(terpilih.nominal_default)}</p>
                    )}
                </div>
                <div className="w-28 shrink-0">
                    <TextInput type="text" placeholder="Override"
                        value={row.nominal_override ? row.nominal_override.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                        onChange={(e) => handleRowChange(type, idx, 'nominal_override', e.target.value.replace(/\D/g, ''))}
                        className="w-full text-xs" title="Kosongkan untuk mengikuti nominal default" />
                    <InputError message={salaryForm.errors[`${type}.${idx}.nominal_override`]} className="mt-1" />
                </div>
                <button type="button" onClick={() => removeRow(type, idx)}
                    className="mt-2.5 shrink-0 text-stone-400 transition hover:text-red-500">
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        );
    };

    return (
        <KeuanganLayout>
            <Head title="Data Pegawai" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Master Data Pegawai</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola data pegawai, jabatan, dan komponen gajinya.</p>
            </div>

            <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 sm:flex-row">
                        <div className="flex w-full max-w-sm items-center rounded-xl bg-white px-3.5 py-2 ring-1 ring-stone-200 focus-within:ring-2 focus-within:ring-claude-500 dark:bg-coal-900 dark:ring-coal-600">
                            <svg className="h-4 w-4 flex-shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari NIY atau nama..."
                                className="w-full border-none bg-transparent py-0.5 pl-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-0 dark:text-cream-100" />
                        </div>
                        <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}
                            className="w-full rounded-xl border-0 bg-white px-3.5 py-2 text-sm text-stone-700 ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600 sm:w-auto">
                            <option value="">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Non-Aktif">Non-Aktif</option>
                        </select>
                    </div>
                    {can('master.pegawai.buat') && (
                        <PrimaryButton onClick={openAddModal} className="w-full sm:w-auto">Tambah Pegawai</PrimaryButton>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('nip')} className={thCls}><div className="flex items-center">NIY <SortIcon field="nip" /></div></th>
                                <th onClick={() => handleSort('name')} className={thCls}><div className="flex items-center">Nama Pegawai <SortIcon field="name" /></div></th>
                                <th className="px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Jabatan & Kelas</th>
                                <th onClick={() => handleSort('is_active')} className={thCls}><div className="flex items-center">Status <SortIcon field="is_active" /></div></th>
                                {adaAksi && <th className="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {pegawai.data.length > 0 ? pegawai.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nip || '-'}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-stone-700 dark:text-stone-200">{item.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <span className="font-medium text-stone-800 dark:text-cream-100">
                                            {item.jabatan?.nama_jabatan || <span className="italic text-red-400">Tidak ada jabatan</span>}
                                        </span>
                                        <br />
                                        {item.kelas ? (
                                            <span className="text-[10px] uppercase tracking-wider text-claude-600 dark:text-claude-300">Wali: {item.kelas.nama_kelas}</span>
                                        ) : (
                                            <span className="text-[10px] italic text-stone-400">Non-guru kelas</span>
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4">
                                        {item.is_active ? (
                                            <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
                                        ) : (
                                            <span className="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">Non-Aktif</span>
                                        )}
                                    </td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            {can('master.pegawai.kelola_gaji') && (
                                                <button onClick={() => openSalaryModal(item)} className="mr-1 rounded-lg px-2.5 py-1.5 font-medium text-emerald-600 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">Atur Gaji</button>
                                            )}
                                            {can('master.pegawai.edit') && (
                                                <button onClick={() => openEditModal(item)} className="mr-1 rounded-lg px-2.5 py-1.5 font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300">Ubah</button>
                                            )}
                                            {can('master.pegawai.hapus') && (
                                                <button onClick={() => confirmDelete(item.id)} className="rounded-lg px-2.5 py-1.5 font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">Hapus</button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 5 : 4} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada data pegawai ditemukan</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {pegawai.from || 0}–{pegawai.to || 0} dari {pegawai.total} entri</p>
                    <div className="flex flex-wrap gap-1">
                        {pegawai.links.map((link, i) => (
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

            <Modal show={isSalaryModalOpen} onClose={() => setIsSalaryModalOpen(false)} maxWidth="4xl">
                <form onSubmit={submitSalary} className="p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Atur Komponen Gaji</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">{selectedPegawai?.name} · {selectedPegawai?.nip}</p>

                    <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <div className="mb-3 flex items-center justify-between border-b border-stone-200 pb-2 dark:border-coal-600">
                                <h3 className="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Tunjangan</h3>
                                <button type="button" onClick={() => addRow('allowances')} className="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">+ Baris</button>
                            </div>
                            <div className="space-y-2">
                                {salaryForm.data.allowances.map((row, idx) => (
                                    <KomponenRow key={idx} type="allowances" row={row} idx={idx} list={listTunjangan}
                                        warna="text-emerald-600 dark:text-emerald-400" labelKey="nama_tunjangan" />
                                ))}
                                {salaryForm.data.allowances.length === 0 && (
                                    <p className="rounded-xl border-2 border-dashed border-stone-200 py-4 text-center text-xs text-stone-400 dark:border-coal-600">Belum ada tunjangan.</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <div className="mb-3 flex items-center justify-between border-b border-stone-200 pb-2 dark:border-coal-600">
                                <h3 className="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">Potongan</h3>
                                <button type="button" onClick={() => addRow('deductions')} className="text-xs font-medium text-red-600 hover:underline dark:text-red-400">+ Baris</button>
                            </div>
                            <div className="space-y-2">
                                {salaryForm.data.deductions.map((row, idx) => (
                                    <KomponenRow key={idx} type="deductions" row={row} idx={idx} list={listPotongan}
                                        warna="text-red-600 dark:text-red-400" labelKey="nama_potongan" />
                                ))}
                                {salaryForm.data.deductions.length === 0 && (
                                    <p className="rounded-xl border-2 border-dashed border-stone-200 py-4 text-center text-xs text-stone-400 dark:border-coal-600">Belum ada potongan.</p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2 border-t border-stone-100 pt-5 dark:border-coal-600">
                        <SecondaryButton type="button" onClick={() => setIsSalaryModalOpen(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={salaryForm.processing}>Simpan Pengaturan Gaji</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="2xl">
                <form onSubmit={submit} className="p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Data Pegawai' : 'Tambah Data Pegawai'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="nip">NIY <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="nip" value={data.nip} onChange={(e) => setData('nip', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={errors.nip} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="name">Nama Pegawai (dengan gelar) <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={errors.name} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="position_id">Jabatan Utama <span className="text-red-500">*</span></InputLabel>
                                <select id="position_id" value={data.position_id} onChange={(e) => setData('position_id', e.target.value)}
                                    className="mt-1.5 block w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-stone-800 shadow-sm ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700">
                                    <option value="">-- Pilih Jabatan --</option>
                                    {listJabatan.map((j) => <option key={j.id} value={j.id}>{j.nama_jabatan}</option>)}
                                </select>
                                <InputError message={errors.position_id} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="class_id">Wali Kelas <span className="font-normal text-stone-400">(opsional)</span></InputLabel>
                                <div className="mt-1.5">
                                    <SearchableSelect
                                        placeholder="-- Pilih Kelas --"
                                        options={listKelas?.map((k) => ({ id: k.id, label: `${k.nama_kelas}${k.tingkat ? ` (${k.tingkat})` : ''}` })) || []}
                                        value={data.class_id}
                                        onChange={(id) => setData('class_id', id)}
                                    />
                                </div>
                                <p className="mt-1 text-xs text-stone-400">Isi hanya bila pegawai ini mengajar di kelas tertentu.</p>
                                <InputError message={errors.class_id} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="border-t border-stone-100 pt-4 dark:border-coal-600">
                            <label className="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-stone-300 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-900" />
                                <span className="text-sm text-stone-600 dark:text-stone-300">Pegawai Aktif</span>
                            </label>
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
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus pegawai ini?</p>
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
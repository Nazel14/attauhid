import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';

export default function DataRkasTemplate({ templates }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [parentObj, setParentObj] = useState(null);
    const [hapusTarget, setHapusTarget] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nama_judul: '',
        parent_id: '',
    });

    const openAddMain = () => {
        setIsEditing(false); setParentObj(null);
        setData({ nama_judul: '', parent_id: '' });
        clearErrors(); setIsModalOpen(true);
    };

    const openAddSub = (parent) => {
        setIsEditing(false); setParentObj(parent);
        setData({ nama_judul: '', parent_id: parent.id });
        clearErrors(); setIsModalOpen(true);
    };

    const openEdit = (item) => {
        setIsEditing(true); setEditId(item.id); setParentObj(null);
        setData({ nama_judul: item.nama_judul, parent_id: item.parent_id || '' });
        clearErrors(); setIsModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModalOpen(false); reset(); }, preserveScroll: true };
        if (isEditing) put(route('keuangan.master-data.rkas_template.update', editId), opts);
        else post(route('keuangan.master-data.rkas_template.store'), opts);
    };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.rkas_template.destroy', hapusTarget.id), {
            preserveScroll: true,
            onSuccess: () => setHapusTarget(null),
        });
    };

    const adaAksi = can('master.rkas.buat') || can('master.rkas.edit') || can('master.rkas.hapus');

    const barisCls = (level) =>
        level === 1 ? 'bg-claude-500/10 font-semibold'
        : level === 2 ? 'bg-cream-100 font-medium dark:bg-coal-600/40'
        : 'hover:bg-cream-50 dark:hover:bg-coal-600/30';

    return (
        <KeuanganLayout>
            <Head title="Template RKAS" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Master Template RKAS</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kerangka ini disalin otomatis saat pembuatan RKAS tahunan baru.</p>
            </div>

            <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Struktur standar, disusun bertingkat induk → sub-judul.</p>
                    {can('master.rkas.buat') && (
                        <PrimaryButton onClick={openAddMain} className="w-full sm:w-auto">Tambah Induk Utama</PrimaryButton>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th className="px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Kode & Uraian Judul</th>
                                {adaAksi && <th className="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {templates?.length > 0 ? templates.map((item) => (
                                <tr key={item.id} className={`transition ${barisCls(item.level)}`}>
                                    <td className="px-6 py-3 text-sm text-stone-800 dark:text-cream-100">
                                        <div className="flex items-center gap-2" style={{ paddingLeft: `${(item.level - 1) * 24}px` }}>
                                            {item.level > 1 && <span className="font-normal text-stone-400">↳</span>}
                                            <span className="w-14 shrink-0 text-stone-500 dark:text-stone-400">{item.kode_judul}</span>
                                            <span>{item.nama_judul}</span>
                                        </div>
                                    </td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-6 py-3 text-right text-xs">
                                            {can('master.rkas.buat') && (
                                                <button onClick={() => openAddSub(item)} className="mr-2 font-medium text-blue-600 hover:underline dark:text-blue-400">+ Sub</button>
                                            )}
                                            {can('master.rkas.edit') && (
                                                <button onClick={() => openEdit(item)} className="mr-2 font-medium text-claude-600 hover:underline dark:text-claude-300">Edit</button>
                                            )}
                                            {can('master.rkas.hapus') && (
                                                <button onClick={() => setHapusTarget(item)} className="font-medium text-red-600 hover:underline dark:text-red-400">Hapus</button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 2 : 1} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada template RKAS.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                        {isEditing ? 'Edit Judul RKAS' : parentObj ? 'Tambah Sub-Judul' : 'Tambah Induk Utama'}
                    </h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    {!isEditing && parentObj && (
                        <div className="mt-4 rounded-xl bg-stone-50 p-3 text-sm ring-1 ring-stone-200 dark:bg-coal-900/50 dark:ring-coal-600">
                            <span className="mb-0.5 block text-[10px] font-semibold uppercase tracking-wider text-stone-400">Induk</span>
                            <span className="font-medium text-stone-800 dark:text-cream-100">{parentObj.kode_judul} · {parentObj.nama_judul}</span>
                        </div>
                    )}

                    <div className="mt-4">
                        <InputLabel htmlFor="nama_judul">Uraian Judul <span className="text-red-500">*</span></InputLabel>
                        <TextInput id="nama_judul" value={data.nama_judul} onChange={(e) => setData('nama_judul', e.target.value)}
                            className="mt-1.5 block w-full" autoFocus />
                        <InputError message={errors.nama_judul} className="mt-1.5" />
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsModalOpen(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>Simpan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={hapusTarget !== null} onClose={() => setHapusTarget(null)} maxWidth="sm">
                <div className="p-6 text-center">
                    <svg className="mx-auto mb-3 h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Struktur</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Hapus <span className="font-medium text-stone-700 dark:text-stone-200">{hapusTarget?.nama_judul}</span>?
                        Seluruh sub-judul di bawahnya ikut terhapus.
                    </p>
                    <div className="mt-5 flex justify-center gap-2">
                        <SecondaryButton onClick={() => setHapusTarget(null)}>Batal</SecondaryButton>
                        <button onClick={executeDelete}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
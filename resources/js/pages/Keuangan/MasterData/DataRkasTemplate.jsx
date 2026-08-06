import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
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

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Belum ada template RKAS</p>
            <p className="mt-1 text-xs text-stone-400">Mulai dengan menambahkan induk utama</p>
        </>
    );

    return (
        <KeuanganLayout>
            <Head title="Template RKAS" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-stone-200 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Template RKAS</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kerangka ini disalin otomatis saat pembuatan RKAS tahunan baru.</p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Struktur standar, disusun bertingkat induk → sub-judul.</p>
                    {can('master.rkas.buat') && (
                        <PrimaryButton onClick={openAddMain} className="h-10 w-full shrink-0 justify-center sm:w-auto">Tambah Induk Utama</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2 lg:hidden">
                    {templates?.length > 0 ? templates.map((item) => (
                        <div key={item.id}
                            className={`overflow-hidden rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 ${item.level === 1 ? 'bg-claude-500/10' : item.level === 2 ? 'bg-cream-100 dark:bg-coal-600/40' : ''}`}
                            // Indentasi hierarki tetap terlihat di mobile, tapi
                            // lebih rapat supaya kartu tidak menyempit berlebihan.
                            style={{ marginLeft: `${(item.level - 1) * 12}px` }}>
                            <div className="flex items-start gap-2 p-3">
                                <span className="shrink-0 rounded bg-white px-1.5 py-0.5 text-[11px] font-semibold text-stone-500 ring-1 ring-stone-200 dark:bg-coal-800 dark:text-stone-400 dark:ring-coal-600">
                                    {item.kode_judul}
                                </span>
                                <span className={`min-w-0 text-sm text-stone-800 dark:text-cream-100 ${item.level <= 2 ? 'font-semibold' : ''}`}>
                                    {item.nama_judul}
                                </span>
                            </div>

                            {adaAksi && (
                                <div className="flex border-t border-stone-200/70 dark:border-coal-600">
                                    {can('master.rkas.buat') && (
                                        <button onClick={() => openAddSub(item)}
                                            className="flex flex-1 items-center justify-center gap-1 border-r border-stone-200/70 py-2 text-[11px] font-medium text-blue-600 transition hover:bg-blue-50 dark:border-coal-600 dark:text-blue-400 dark:hover:bg-blue-500/10">
                                            <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" /></svg>
                                            Sub
                                        </button>
                                    )}
                                    {can('master.rkas.edit') && (
                                        <button onClick={() => openEdit(item)}
                                            className="flex flex-1 items-center justify-center gap-1 border-r border-stone-200/70 py-2 text-[11px] font-medium text-claude-600 transition hover:bg-claude-500/10 dark:border-coal-600 dark:text-claude-300">
                                            Edit
                                        </button>
                                    )}
                                    {can('master.rkas.hapus') && (
                                        <button onClick={() => setHapusTarget(item)}
                                            className="flex flex-1 items-center justify-center gap-1 py-2 text-[11px] font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
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
                                <th className="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Kode &amp; Uraian Judul</th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {templates?.length > 0 ? templates.map((item) => (
                                <tr key={item.id} className={`transition ${barisCls(item.level)}`}>
                                    <td className="px-5 py-3 text-sm text-stone-800 dark:text-cream-100">
                                        <div className="flex items-center gap-2" style={{ paddingLeft: `${(item.level - 1) * 24}px` }}>
                                            {item.level > 1 && (
                                                <svg className="h-3.5 w-3.5 shrink-0 text-stone-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 4v10a2 2 0 002 2h10m0 0l-4-4m4 4l-4 4" />
                                                </svg>
                                            )}
                                            <span className="w-14 shrink-0 text-stone-500 dark:text-stone-400">{item.kode_judul}</span>
                                            <span>{item.nama_judul}</span>
                                        </div>
                                    </td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-3 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.rkas.buat') && (
                                                    <button onClick={() => openAddSub(item)}
                                                        className={`${btnGhost} text-blue-600 ring-blue-200 hover:bg-blue-50 dark:text-blue-400 dark:ring-blue-500/20 dark:hover:bg-blue-500/10`}>
                                                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                        Sub
                                                    </button>
                                                )}
                                                {can('master.rkas.edit') && (
                                                    <button onClick={() => openEdit(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        Edit
                                                    </button>
                                                )}
                                                {can('master.rkas.hapus') && (
                                                    <button onClick={() => setHapusTarget(item)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 2 : 1} className="px-6 py-14 text-center">{kosong}</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="md">
                <form onSubmit={submit} className="overflow-y-auto p-6">
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
                            className="mt-1.5 block w-full" autoFocus placeholder="Contoh: Belanja Pegawai" />
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
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
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
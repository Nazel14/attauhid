import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router, Link } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TombolKembali from '@/Components/TombolKembali';

export default function IndexRole({ roles, filters }) {
    const [search, setSearch] = useState(filters?.search || '');

    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');
    const [isProcessingAction, setIsProcessingAction] = useState(false);

    // Tanpa penjaga pertamaKali, efek ini menembak ulang setiap kali
    // komponen mount dan membatalkan navigasi pagination.
    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const delaySearch = setTimeout(() => {
            router.get(route('keuangan.pengaturan.role.index'), { search },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(delaySearch);
    }, [search]);

    const confirmDelete = (id) => {
        setDeleteError('');
        setDeleteId(id);
        setIsDeleteModalOpen(true);
    };

    const executeDelete = () => {
        router.delete(route('keuangan.pengaturan.role.destroy', deleteId), {
            preserveScroll: true,
            onBefore: () => setIsProcessingAction(true),
            onFinish: () => setIsProcessingAction(false),
            onSuccess: () => setIsDeleteModalOpen(false),
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600';
    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const IkonEdit = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    );

    const IkonGembok = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
    );

    return (
        <KeuanganLayout>
            <Head title="Management Role" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Management Hak Akses</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelompok peran pengguna beserta izin fitur yang dimilikinya.</p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative sm:max-w-sm sm:flex-1">
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama role..." className={`${fieldCls} pl-9`} />
                        <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>

                    <Link href={route('keuangan.pengaturan.role.create')}
                        className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 px-4 text-sm font-semibold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-600 hover:to-claude-700">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat Role Baru
                    </Link>
                </div>

                {/* Mobile: kartu — nama role bisa panjang dan terpotong di tabel. */}
                <div className="space-y-3 sm:hidden">
                    {roles.data.length > 0 ? roles.data.map((item) => (
                        <div key={item.id} className="rounded-xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="break-all text-sm font-semibold text-stone-800 dark:text-cream-100">{item.name}</span>
                                {/* is_locked & is_super ditentukan backend lewat config
                                    modules.roles_terkunci — frontend tidak hardcode nama role. */}
                                {item.is_locked && (
                                    <span className="rounded-full bg-cream-200 px-2 py-0.5 text-[10px] font-semibold text-stone-500 ring-1 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500">
                                        Bawaan sistem
                                    </span>
                                )}
                            </div>

                            <p className="mt-1.5 text-xs text-stone-400">{item.users_count} akun memakai role ini</p>

                            <div className="mt-3 flex gap-2 border-t border-cream-200 pt-3 dark:border-coal-600">
                                {item.is_super ? (
                                    <span className="inline-flex flex-1 items-center justify-center gap-1.5 text-xs italic text-stone-400">
                                        <IkonGembok />
                                        Terkunci sistem
                                    </span>
                                ) : (
                                    <Link href={route('keuangan.pengaturan.role.edit', item.id)}
                                        className={`${btnGhost} flex-1 justify-center text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                        <IkonEdit />
                                        Edit Akses
                                    </Link>
                                )}

                                {!item.is_locked && (
                                    <button type="button" onClick={() => confirmDelete(item.id)}
                                        className={`${btnGhost} flex-1 justify-center text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                        Hapus
                                    </button>
                                )}
                            </div>
                        </div>
                    )) : (
                        <p className="rounded-xl bg-cream-100 py-10 text-center text-sm text-stone-500 dark:bg-coal-600/40 dark:text-stone-400">
                            Tidak ada data role ditemukan.
                        </p>
                    )}
                </div>

                {/* Desktop: tabel. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th className={thPlain}>Nama Peran</th>
                                <th className={thPlain}>Total Pengguna</th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {roles.data.length > 0 ? roles.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="px-5 py-4 text-sm">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-semibold text-stone-800 dark:text-cream-100">{item.name}</span>
                                            {item.is_locked && (
                                                <span className="rounded-full bg-cream-200 px-2 py-0.5 text-[10px] font-semibold text-stone-500 ring-1 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500">
                                                    Bawaan sistem
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                        <span className="inline-flex rounded-full bg-cream-200 px-2.5 py-1 text-xs font-medium text-stone-600 ring-1 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500">
                                            {item.users_count} akun
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-2">
                                            {item.is_super ? (
                                                <span className="inline-flex items-center gap-1.5 text-xs italic text-stone-400">
                                                    <IkonGembok />
                                                    Terkunci sistem
                                                </span>
                                            ) : (
                                                <Link href={route('keuangan.pengaturan.role.edit', item.id)}
                                                    className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                    <IkonEdit />
                                                    Edit Akses
                                                </Link>
                                            )}

                                            {!item.is_locked && (
                                                <button type="button" onClick={() => confirmDelete(item.id)}
                                                    className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                    Hapus
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan="3" className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada data role ditemukan.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {roles.from || 0}–{roles.to || 0} dari {roles.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {roles.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${link.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <Modal show={isDeleteModalOpen} onClose={() => setIsDeleteModalOpen(false)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Role</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus role ini?</p>
                    {deleteError && (
                        <div className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{deleteError}</div>
                    )}
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <SecondaryButton onClick={() => setIsDeleteModalOpen(false)} disabled={isProcessingAction} className="justify-center">Batal</SecondaryButton>
                        <button onClick={executeDelete} disabled={isProcessingAction}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">
                            {isProcessingAction ? 'Menghapus…' : 'Ya, Hapus'}
                        </button>
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
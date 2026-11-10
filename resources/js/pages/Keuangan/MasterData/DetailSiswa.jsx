import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import SearchableSelect from '@/Components/SearchableSelect';

export default function DetailSiswa({ kelas, kelasParam, siswa, listKelas, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [typeFilter, setTypeFilter] = useState(filters?.typeFilter || '');
    const [statusFilter, setStatusFilter] = useState(filters?.statusFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'name');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);

    // Yang disimpan barisnya, bukan cuma id — supaya nama siswa bisa
    // ditampilkan di modal konfirmasi. Menghapus siswa yang salah jauh
    // lebih mahal daripada menghapus rekening.
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteItem, setDeleteItem] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nis: '',
        name: '',
        gender: 'Laki-laki',
        student_type: 'Reguler',
        angkatan: new Date().getFullYear(),
        kelas_id: kelas?.id || '',
        parent_name: '',
        parent_contact: '',
        status: 'aktif',
        is_raport_held: false,
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.siswa.detail', kelasParam),
                { search, typeFilter, statusFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, typeFilter, statusFilter, sortField, sortDirection]);

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

    const openAddModal = () => {
        setIsEditing(false); setEditId(null); reset(); clearErrors();
        // Kelas terisi otomatis dari halaman yang sedang dibuka.
        setData('kelas_id', kelas?.id || '');
        setIsModalOpen(true);
    };

    const openEditModal = (item) => {
        setIsEditing(true); setEditId(item.id); clearErrors();
        setData({
            nis: item.nis,
            name: item.name,
            gender: item.gender || 'Laki-laki',
            student_type: item.student_type || 'Reguler',
            angkatan: item.angkatan || new Date().getFullYear(),
            kelas_id: item.kelas_id || '',
            parent_name: item.parent_name || '',
            parent_contact: item.parent_contact || '',
            status: item.status || 'aktif',
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

    const confirmDelete = (item) => {
        setDeleteError('');
        setDeleteItem(item);
        setIsDeleteModalOpen(true);
    };

    const executeDelete = () => {
        if (!deleteItem) return;

        // router.delete hanya menerima (url, options) — TANPA argumen data,
        // berbeda dari router.post(url, data, options). Menyelipkan apa pun
        // di posisi kedua membuat seluruh callback mendarat di argumen ketiga
        // yang diabaikan Inertia: dialog tidak pernah tertutup dan pesan
        // error tidak pernah tampil.
        router.delete(route('keuangan.master-data.siswa.destroy', deleteItem.id), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteItem(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const adaAksi = can('master.siswa.edit') || can('master.siswa.hapus');
    const judul = kelas ? `Siswa ${kelas.name}` : 'Siswa Tanpa Kelas';

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700';

    const ChevronBawah = ({ modal = false }) => (
        <svg className={`pointer-events-none absolute ${modal ? 'right-3.5 top-[calc(50%+3px)]' : 'right-3 top-1/2'} h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500`}
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const IkonUbah = ({ className = 'h-3.5 w-3.5' }) => (
        <svg className={className} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    );

    const IkonHapus = ({ className = 'h-3.5 w-3.5' }) => (
        <svg className={className} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    );

    // santris.status enum: aktif / lulus / keluar
    const BadgeStatus = ({ item }) => (
        <span className="inline-flex flex-wrap items-center gap-1.5">
            {item.status === 'aktif' ? (
                <span className="inline-flex shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
            ) : (
                <span className="inline-flex shrink-0 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium capitalize text-stone-600 ring-1 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500">{item.status}</span>
            )}
            {item.is_raport_held && (
                <span className="inline-flex shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">Rapor Ditahan</span>
            )}
        </span>
    );

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Belum ada siswa di sini</p>
            <p className="mt-1 text-xs text-stone-400">Ubah filter, atau tambahkan siswa baru</p>
        </>
    );

    return (
        <KeuanganLayout>
            <Head title={judul} />

            <Link href={route('keuangan.master-data.siswa')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-stone-200 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Kelas
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">{judul}</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    {kelas ? (
                        <>
                            {kelas.tahun_ajaran || 'Tanpa tahun ajaran'} · Wali:{' '}
                            <span className="font-medium text-claude-600 dark:text-claude-300">{kelas.guru_name || 'belum ada'}</span>
                            {' · '}Terisi{' '}
                            <span className="font-medium text-claude-600 dark:text-claude-300">
                                {siswa.total}{kelas.kapasitas > 0 && ` / ${kelas.kapasitas}`}
                            </span>
                        </>
                    ) : (
                        <>{siswa.total} siswa belum punya kelas — tagihannya tetap berjalan, jadi datanya perlu tetap lengkap.</>
                    )}
                </p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari NIS atau nama siswa..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        {/* Dua select berdampingan di mobile — bertumpuk vertikal terlalu boros. */}
                        <div className="flex gap-2">
                            <div className="relative flex-1 sm:w-40 sm:flex-none">
                                <select value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className={selectCls}>
                                    <option value="">Semua Tipe</option>
                                    <option value="Reguler">Reguler</option>
                                    <option value="Fullday">Fullday</option>
                                </select>
                                <ChevronBawah />
                            </div>

                            <div className="relative flex-1 sm:w-40 sm:flex-none">
                                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectCls}>
                                    <option value="">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="lulus">Lulus</option>
                                    <option value="keluar">Keluar</option>
                                </select>
                                <ChevronBawah />
                            </div>
                        </div>
                    </div>

                    {can('master.siswa.buat') && (
                        <PrimaryButton onClick={openAddModal} className="h-10 w-full justify-center lg:w-auto">Tambah Siswa</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {siswa.data.length > 0 ? siswa.data.map((item) => (
                        <div key={item.id} className="overflow-hidden rounded-2xl ring-1 ring-stone-200 dark:ring-coal-600">
                            <div className="flex items-start justify-between gap-3 p-4">
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{item.name}</p>
                                    <p className="mt-0.5 text-xs text-stone-400">NIS {item.nis} · Angkatan {item.angkatan || '-'}</p>
                                </div>
                                <BadgeStatus item={item} />
                            </div>

                            <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-600">
                                <div className="border-r border-stone-100 px-3 py-2.5 text-center dark:border-coal-600">
                                    <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Tipe</p>
                                    <p className="truncate text-xs font-medium text-claude-600 dark:text-claude-300">{item.student_type}</p>
                                </div>
                                <div className="px-3 py-2.5 text-center">
                                    <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Orang Tua</p>
                                    <p className="truncate text-xs font-medium text-stone-700 dark:text-stone-200">
                                        {item.parent_name || <span className="italic text-stone-400">—</span>}
                                    </p>
                                    <p className="mt-0.5 truncate text-[10px] text-stone-400">{item.parent_contact || '—'}</p>
                                </div>
                            </div>

                            {adaAksi && (
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-600">
                                    {can('master.siswa.edit') && (
                                        <button onClick={() => openEditModal(item)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300 ${can('master.siswa.hapus') ? 'border-r border-stone-100 dark:border-coal-600' : 'col-span-2'}`}>
                                            <IkonUbah />
                                            Ubah Data
                                        </button>
                                    )}
                                    {can('master.siswa.hapus') && (
                                        <button onClick={() => confirmDelete(item)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 ${!can('master.siswa.edit') ? 'col-span-2' : ''}`}>
                                            <IkonHapus />
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
                                <th onClick={() => handleSort('nis')} className={thCls}><div className="flex items-center">NIS <SortIcon field="nis" /></div></th>
                                <th onClick={() => handleSort('name')} className={thCls}><div className="flex items-center">Nama Siswa <SortIcon field="name" /></div></th>
                                <th onClick={() => handleSort('angkatan')} className={thCls}><div className="flex items-center">Angkatan &amp; Tipe <SortIcon field="angkatan" /></div></th>
                                <th className={thPlain}>Orang Tua</th>
                                <th onClick={() => handleSort('status')} className={thCls}><div className="flex items-center">Status <SortIcon field="status" /></div></th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {siswa.data.length > 0 ? siswa.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nis}</td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-700 dark:text-stone-200">{item.name}</td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">
                                        Angkatan {item.angkatan || '-'}
                                        <br />
                                        <span className="text-[10px] uppercase tracking-wider text-stone-400">{item.student_type}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">
                                        {item.parent_name}
                                        <br />
                                        <span className="text-xs text-stone-400">{item.parent_contact}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4"><BadgeStatus item={item} /></td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.siswa.edit') && (
                                                    <button onClick={() => openEditModal(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        <IkonUbah />
                                                        Ubah
                                                    </button>
                                                )}
                                                {can('master.siswa.hapus') && (
                                                    <button onClick={() => confirmDelete(item)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        <IkonHapus />
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 6 : 5} className="px-6 py-14 text-center">{kosong}</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {siswa.from || 0}–{siswa.to || 0} dari {siswa.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
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
                <form onSubmit={submit} className="overflow-y-auto p-6">
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
                                <div className="relative">
                                    <select id="student_type" value={data.student_type} onChange={(e) => setData('student_type', e.target.value)} className={selectModal}>
                                        <option value="Reguler">Reguler</option>
                                        <option value="Fullday">Fullday</option>
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <p className="mt-1 text-xs text-stone-400">Menentukan tarif yang dipakai saat auto-generate tagihan.</p>
                                <InputError message={errors.student_type} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="angkatan">Angkatan <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="angkatan" type="number" inputMode="numeric"
                                    value={data.angkatan} onChange={(e) => setData('angkatan', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: 2024" />
                                <InputError message={errors.angkatan} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="gender">Jenis Kelamin <span className="text-red-500">*</span></InputLabel>
                                <div className="relative">
                                    <select id="gender" value={data.gender} onChange={(e) => setData('gender', e.target.value)} className={selectModal}>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <p className="mt-1 text-xs text-stone-400">Dipakai modul raport untuk pembatasan kelas ikhwan/akhwat.</p>
                                <InputError message={errors.gender} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="status">Status Santri <span className="text-red-500">*</span></InputLabel>
                                <div className="relative">
                                    <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} className={selectModal}>
                                        <option value="aktif">Aktif</option>
                                        <option value="lulus">Lulus</option>
                                        <option value="keluar">Keluar</option>
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <p className="mt-1 text-xs text-stone-400">
                                    Siswa yang berhenti atau lulus diubah statusnya di sini — bukan dihapus,
                                    supaya tagihan dan setorannya tetap punya pemilik.
                                </p>
                                <InputError message={errors.status} className="mt-1.5" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="kelas_id">Kelas Saat Ini</InputLabel>
                            <div className="mt-1.5">
                                <SearchableSelect
                                    placeholder="-- Pilih Kelas Siswa --"
                                    options={listKelas?.map((k) => ({ id: k.id, label: `${k.name} · ${k.tahun_ajaran}` })) || []}
                                    value={data.kelas_id}
                                    onChange={(id) => setData('kelas_id', id)}
                                />
                            </div>
                            <p className="mt-1 text-xs text-stone-400">
                                Mengubah kelas akan memindahkan siswa keluar dari halaman ini.
                            </p>
                            <InputError message={errors.kelas_id} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="parent_name">Nama Orang Tua <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="parent_name" value={data.parent_name} onChange={(e) => setData('parent_name', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={errors.parent_name} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="parent_contact">Kontak Orang Tua <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="parent_contact" type="tel" inputMode="numeric"
                                    value={data.parent_contact} onChange={(e) => setData('parent_contact', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="0812..." />
                                <p className="mt-1 text-xs text-stone-400">Dipakai tombol WhatsApp di Laporan Tunggakan.</p>
                                <InputError message={errors.parent_contact} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="border-t border-stone-100 pt-4 dark:border-coal-600">
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

            {/* maxWidth md, bukan sm: pesan penolakan dari server panjang karena
                menyebutkan jejak apa saja yang ditemukan dan menunjuk ke jalur
                yang benar (ubah status, bukan hapus). */}
            <Modal show={isDeleteModalOpen} onClose={() => setIsDeleteModalOpen(false)} maxWidth="md">
                <div className="p-6 text-center">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Data Siswa</h3>

                    {/* Nama dan NIS ditampilkan: menghapus baris yang salah di sini
                        jauh lebih mahal daripada di master data lain. */}
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        {deleteItem
                            ? <>Yakin menghapus <span className="font-semibold text-stone-700 dark:text-cream-100">{deleteItem.name}</span> (NIS {deleteItem.nis})?</>
                            : 'Yakin menghapus siswa ini?'}
                    </p>

                    <p className="mt-2 text-xs text-stone-400">
                        Hanya untuk data yang salah diinput. Siswa yang sudah punya tagihan,
                        pembayaran, setoran, atau presensi tidak bisa dihapus.
                    </p>

                    {deleteError && (
                        <div className="mt-4 rounded-xl bg-red-50 p-3 text-left text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">
                            {deleteError}
                        </div>
                    )}

                    <div className="mt-5 flex justify-center gap-2">
                        <SecondaryButton onClick={() => setIsDeleteModalOpen(false)}>
                            {deleteError ? 'Tutup' : 'Batal'}
                        </SecondaryButton>
                        {!deleteError && (
                            <button onClick={executeDelete}
                                className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">
                                Ya, Hapus
                            </button>
                        )}
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import MultiSelectChips from '@/Components/MultiSelectChips';
import TombolKembali from '@/Components/TombolKembali';

const tanggal = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-');
const tanggalPendek = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-');

export default function IndexUser({ users, roles, rolesRaport = [], filters }) {
    const currentUser = usePage().props.auth.user;

    const [search, setSearch] = useState(filters?.search || '');
    const [roleFilter, setRoleFilter] = useState(filters?.roleFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [editPunyaProfil, setEditPunyaProfil] = useState(false);

    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');
    const [isProcessingAction, setIsProcessingAction] = useState(false);

    // Penjaga pertamaKali wajib: tanpa ini efek ikut jalan saat komponen
    // mount ulang setelah pindah halaman, lalu menembak request tanpa
    // parameter `page` — hasilnya pagination selalu balik ke halaman 1.
    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const delaySearch = setTimeout(() => {
            router.get(route('keuangan.pengaturan.user.index'), {
                search,
                roleFilter,
                sort_field: sortField,
                sort_direction: sortDirection,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(delaySearch);
    }, [search, roleFilter, sortField, sortDirection]);

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

    // Tanpa email: tabel users tidak punya kolom itu.
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '',
        password: '',
        // Array: satu akun bisa memegang beberapa peran sekaligus.
        roles: [],
    });

    const [roleProfil, setRoleProfil] = useState(null);

    const isSelfEditing = isEditing && editId === currentUser.id;
    const roleTerkunci = isSelfEditing;

    const openAddModal = () => {
        setIsEditing(false);
        setEditId(null);
        setEditPunyaProfil(false);
        setRoleProfil(null);
        reset();
        clearErrors();
        setIsModalOpen(true);
    };

    const openEditModal = (item) => {
        const punyaProfil = !!item.guru || !!item.santri;

        setIsEditing(true);
        setEditId(item.id);
        setEditPunyaProfil(punyaProfil);
        // Role yang tidak boleh dilepas karena terikat baris profil raport.
        setRoleProfil(punyaProfil ? item.role : null);
        setData({
            name: item.name,
            password: '',
            roles: item.roles?.map((r) => r.name) || (item.role ? [item.role] : []),
        });
        clearErrors();
        setIsModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();
        const options = {
            onSuccess: () => { setIsModalOpen(false); reset(); },
            preserveScroll: true,
        };

        if (isEditing) put(route('keuangan.pengaturan.user.update', editId), options);
        else post(route('keuangan.pengaturan.user.store'), options);
    };

    const confirmDelete = (id) => {
        setDeleteId(id);
        setDeleteError('');
        setIsDeleteModalOpen(true);
    };

    const executeDelete = () => {
        router.delete(route('keuangan.pengaturan.user.destroy', deleteId), {
            preserveScroll: true,
            onBefore: () => setIsProcessingAction(true),
            onFinish: () => setIsProcessingAction(false),
            onSuccess: () => setIsDeleteModalOpen(false),
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thAksi = 'px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;

    const ChevronBawah = () => (
        <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';
    const chipCls = 'rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1';

    // Dipakai tabel (desktop) dan kartu (mobile) — dihitung sekali di sini
    // supaya kedua tampilan tidak pernah beda logika.
    const infoBaris = (item) => {
        const roleName = item.role || item.roles?.[0]?.name;
        return {
            roleName,
            isSelf: currentUser.id === item.id,
            namaFormal: item.guru?.name || item.santri?.name || null,
            punyaProfil: !!item.guru || !!item.santri,
            spatieCocok: item.roles?.some((r) => r.name === roleName),
        };
    };

    const ChipProfil = ({ item, isSelf }) => (
        <>
            {isSelf && <span className={`${chipCls} bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20`}>Anda</span>}
            {item.guru && <span className={`${chipCls} bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20`}>Data Guru</span>}
            {item.santri && <span className={`${chipCls} bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20`}>Data Santri</span>}
        </>
    );

    const DaftarRole = ({ item, roleName, spatieCocok }) => {
        if (!item.roles?.length) return <span className="text-xs italic text-stone-400">Belum ada role</span>;

        return (
            <div className="flex flex-col gap-1.5">
                <div className="flex flex-wrap gap-1.5">
                    {item.roles.map((r) => (
                        <span key={r.id}
                            className={`inline-flex w-fit items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${
                                r.name === 'super-admin'
                                    ? 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/20'
                                    : r.name === roleName
                                        ? 'bg-claude-500/10 text-claude-600 ring-claude-500/20 dark:text-claude-300'
                                        : 'bg-stone-100 text-stone-600 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500'
                            }`}>
                            {r.name}
                            {/* Tanda role utama — yang tersimpan di kolom users.role */}
                            {r.name === roleName && (
                                <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20" aria-label="Role utama">
                                    <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.29 3.97a1 1 0 00.95.69h4.17c.97 0 1.37 1.24.59 1.81l-3.38 2.45a1 1 0 00-.36 1.12l1.29 3.97c.3.92-.76 1.69-1.54 1.12l-3.38-2.45a1 1 0 00-1.18 0l-3.38 2.45c-.78.57-1.84-.2-1.54-1.12l1.29-3.97a1 1 0 00-.36-1.12L2.03 9.4c-.78-.57-.38-1.81.59-1.81H6.8a1 1 0 00.95-.69L9.05 2.93z" />
                                </svg>
                            )}
                        </span>
                    ))}
                </div>
                {/* Kolom users.role harus termasuk dalam daftar role Spatie.
                    Kalau tidak, permission-nya tidak sesuai harapan. */}
                {!spatieCocok && (
                    <span className="text-[10px] font-medium leading-snug text-red-600 dark:text-red-400">
                        Role utama tidak ada di daftar Spatie — simpan ulang akun ini
                    </span>
                )}
            </div>
        );
    };

    return (
        <KeuanganLayout>
            <Head title="Management User" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Management Pengguna</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Akun login untuk seluruh modul, beserta hak aksesnya.</p>
            </div>

            <div className="mb-5 flex gap-3 rounded-2xl bg-claude-500/10 p-4 text-xs leading-relaxed text-stone-700 ring-1 ring-claude-500/20 dark:text-stone-300">
                <svg className="h-5 w-5 flex-shrink-0 text-claude-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>
                    Daftar ini memakai tabel pengguna yang sama dengan panel raport. Akun yang terhubung ke data guru atau santri
                    hanya bisa diganti role dan dihapus dari panel admin raport, agar data kelas, setoran, dan presensinya ikut terjaga.
                </p>
            </div>

            {/* p-4 di mobile: p-5 menyisakan lebar isi <300px di layar 360px. */}
            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-sm sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari username..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="relative sm:w-48">
                            <select value={roleFilter} onChange={(e) => setRoleFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Role</option>
                                {roles.map((r) => <option key={r.id} value={r.name}>{r.name}</option>)}
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    <PrimaryButton onClick={openAddModal} className="h-10 shrink-0 justify-center">Tambah Akun</PrimaryButton>
                </div>

                {/* Mobile: kartu. Tabel 4 kolom di layar 360px hanya menyisakan
                    ~80px per kolom — nama dan role saling menindih. */}
                <div className="space-y-3 sm:hidden">
                    {users.data.length > 0 ? users.data.map((item) => {
                        const { roleName, isSelf, namaFormal, punyaProfil, spatieCocok } = infoBaris(item);

                        return (
                            <div key={item.id}
                                className={`rounded-xl p-4 ring-1 ${isSelf
                                    ? 'bg-amber-50/60 ring-amber-200 dark:bg-amber-500/5 dark:ring-amber-500/20'
                                    : 'bg-white ring-stone-200 dark:bg-coal-800/40 dark:ring-coal-600'}`}>
                                <div className="flex items-start gap-3">
                                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 dark:bg-claude-500/20 dark:text-claude-300">
                                        {item.name.charAt(0).toUpperCase()}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-stone-800 dark:text-cream-100">
                                            <span className="break-all">{namaFormal || item.name}</span>
                                            <ChipProfil item={item} isSelf={isSelf} />
                                        </div>
                                        <div className="mt-0.5 break-all text-xs text-stone-400">
                                            @{item.name} · {tanggalPendek(item.created_at)}
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-3">
                                    <DaftarRole item={item} roleName={roleName} spatieCocok={spatieCocok} />
                                </div>

                                <div className="mt-3 flex gap-2 border-t border-stone-100 pt-3 dark:border-coal-600">
                                    <button type="button" onClick={() => openEditModal(item)}
                                        className={`${btnGhost} flex-1 justify-center text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>
                                    {!isSelf && !punyaProfil && (
                                        <button type="button" onClick={() => confirmDelete(item.id)}
                                            className={`${btnGhost} flex-1 justify-center text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    }) : (
                        <p className="rounded-xl bg-stone-50 py-10 text-center text-sm text-stone-500 dark:bg-coal-800 dark:text-stone-400">
                            Tidak ada data pengguna ditemukan.
                        </p>
                    )}
                </div>

                {/* Desktop: tabel penuh dengan sorting. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('name')} className={thCls}><div className="flex items-center">Info Akun <SortIcon field="name" /></div></th>
                                <th onClick={() => handleSort('role')} className={thCls}><div className="flex items-center">Hak Akses <SortIcon field="role" /></div></th>
                                <th onClick={() => handleSort('created_at')} className={thCls}><div className="flex items-center">Tanggal Dibuat <SortIcon field="created_at" /></div></th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {users.data.length > 0 ? users.data.map((item) => {
                                const { roleName, isSelf, namaFormal, punyaProfil, spatieCocok } = infoBaris(item);

                                return (
                                    <tr key={item.id} className={`transition ${isSelf ? 'bg-amber-50/60 dark:bg-amber-500/5' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="whitespace-nowrap px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 dark:bg-claude-500/20 dark:text-claude-300">
                                                    {item.name.charAt(0).toUpperCase()}
                                                </span>
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-stone-800 dark:text-cream-100">
                                                        {namaFormal || item.name}
                                                        <ChipProfil item={item} isSelf={isSelf} />
                                                    </div>
                                                    <div className="text-xs text-stone-400">@{item.name}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 text-sm">
                                            <DaftarRole item={item} roleName={roleName} spatieCocok={spatieCocok} />
                                        </td>
                                        <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{tanggal(item.created_at)}</td>
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                <button type="button" onClick={() => openEditModal(item)}
                                                    className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </button>

                                                {!isSelf && !punyaProfil && (
                                                    <button type="button" onClick={() => confirmDelete(item.id)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan="4" className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada data pengguna ditemukan.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {users.from || 0}–{users.to || 0} dari {users.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {users.links.map((link, index) => (
                            <button key={index} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${link.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                        {isEditing ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru'}
                    </h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel htmlFor="name">Username <span className="text-red-500">*</span></InputLabel>
                            <TextInput id="name" value={data.name} onChange={(e) => setData('name', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: bendahara01" />
                            <p className="mt-1 text-xs text-stone-400">Dipakai untuk login. Sistem ini tidak memakai email.</p>
                            <InputError message={errors.name} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password">
                                Kata Sandi {isEditing ? <span className="font-normal text-stone-400">(opsional)</span> : <span className="text-red-500">*</span>}
                            </InputLabel>
                            <TextInput id="password" type="password" value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1.5 block w-full"
                                placeholder={isEditing ? 'Kosongkan jika tidak ingin diubah' : 'Minimal 8 karakter'} />
                            <InputError message={errors.password} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel htmlFor="roles">Hak Akses (Role) {!roleTerkunci && <span className="text-red-500">*</span>}</InputLabel>
                            <p className="mt-1 text-xs text-stone-400">Bisa lebih dari satu — mis. guru yang merangkap memegang kas.</p>
                            <div className="mt-1.5">
                                <MultiSelectChips
                                    placeholder="-- Pilih Hak Akses --"
                                    // Value memakai nama role karena controller memvalidasi exists:roles,name
                                    options={roles.map((r) => ({ id: r.name, label: r.name }))}
                                    value={data.roles}
                                    onChange={(val) => {
                                        // Role profil raport dipertahankan meski dilepas di UI —
                                        // backend akan menolaknya, jadi lebih baik dicegah di sini.
                                        if (roleProfil && !val.includes(roleProfil)) return;
                                        setData('roles', val);
                                    }}
                                />
                            </div>
                            <InputError message={errors.roles} className="mt-1.5" />

                            {isSelfEditing && (
                                <p className="mt-2 rounded-xl bg-amber-50 p-3 text-xs text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                    Anda tidak dapat mengubah hak akses akun sendiri, untuk mencegah kehilangan akses.
                                </p>
                            )}
                            {!isSelfEditing && roleProfil && (
                                <p className="mt-2 rounded-xl bg-emerald-50 p-3 text-xs text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">
                                    Akun ini terhubung ke data {roleProfil} di modul raport, jadi role “{roleProfil}” tidak bisa dilepas dari sini. Role lain boleh ditambahkan.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* flex-col-reverse: di mobile tombol utama berada di atas,
                        posisi ibu jari paling mudah dijangkau. */}
                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsModalOpen(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing} className="justify-center">
                            {processing ? 'Menyimpan…' : (isEditing ? 'Simpan Perubahan' : 'Buat Akun')}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDeleteModalOpen} onClose={() => setIsDeleteModalOpen(false)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Akun Pengguna</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus akun ini secara permanen?</p>

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
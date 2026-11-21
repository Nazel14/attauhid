import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import SearchableSelect from '@/Components/SearchableSelect';
import MultiSelectChips from '@/Components/MultiSelectChips';
import { formatCurrency } from '@/Utils/formatCurrency';

export default function DataPegawai({ pegawai, listJabatan, listTunjangan, listPotongan, listRole = [], petaJabatanRole = {}, filters }) {
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
    const [deleteTarget, setDeleteTarget] = useState(null); // baris pegawai yang akan dihapus
    const [deleting, setDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState('');

    const [isSalaryModalOpen, setIsSalaryModalOpen] = useState(false);
    const [selectedPegawai, setSelectedPegawai] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nip: '',
        name: '',
        position_id: '',
        is_active: true,
        // Default tercentang: hampir semua pegawai butuh akun. Masih bisa
        // dimatikan untuk yang memang tidak akan pernah login (satpam, dsb).
        buat_akun: true,
        username: '',
        password: '',
        // Array: pegawai bisa memegang lebih dari satu peran sekaligus.
        roles: [],
    });

    // Pegawai yang sudah punya akun tidak menampilkan blok pembuatan akun;
    // kredensialnya diubah dari Manajemen User.
    const [sudahPunyaAkun, setSudahPunyaAkun] = useState(false);

    // Jabatan Guru diperlakukan khusus: users.role tetap 'guru' agar akses
    // raport tidak putus, role keuangan ditambahkan sebagai role kedua.
    const jabatanGuru = listJabatan.some(
        (j) => String(j.id) === String(data.position_id) && j.nama_jabatan.toLowerCase() === 'guru'
    );

    // Hak akses mengikuti jabatan. Bendahara tidak perlu memilih dua hal
    // yang sudah saling menentukan — tapi tetap bisa diubah bila ada
    // pengecualian (mis. guru yang merangkap memegang kas).
    const pilihJabatan = (positionId) => {
        const bawaan = petaJabatanRole[positionId];

        setData((sebelumnya) => ({
            ...sebelumnya,
            position_id: positionId,
            // Role bawaan ditambahkan, pilihan manual sebelumnya dipertahankan.
            roles: bawaan && !sebelumnya.roles.includes(bawaan)
                ? [...sebelumnya.roles, bawaan]
                : sebelumnya.roles,
        }));
    };

    const roleOtomatis = petaJabatanRole[data.position_id] ?? null;
    const punyaAksesKeuangan = data.roles.some((r) => r !== 'guru');

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

    const openAddModal = () => {
        setIsEditing(false); setEditId(null); setSudahPunyaAkun(false);
        reset(); clearErrors(); setIsModalOpen(true);
    };

    const openEditModal = (item) => {
        setIsEditing(true); setEditId(item.id); clearErrors();
        setSudahPunyaAkun(!!item.user_id);
        setData({
            nip: item.nip || '',
            name: item.name,
            position_id: item.position_id || '',
            is_active: Boolean(item.is_active),
            // Saat edit, akun hanya dibuat bila pegawai belum punya.
            buat_akun: !item.user_id,
            username: '',
            password: '',
            roles: petaJabatanRole[item.position_id] ? [petaJabatanRole[item.position_id]] : [],
        });
        setIsModalOpen(true);
    };

    const confirmDelete = (item) => { setDeleteError(''); setDeleteTarget(item); setIsDeleteModalOpen(true); };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.pegawai.destroy', deleteTarget.id), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => setDeleting(false),
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteTarget(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    // Rincian dari server (App\Services\HapusPegawai::dampak) — sama dengan
    // dialog hapus di Management Guru panel raport.
    const dampak = deleteTarget?.dampak_hapus || {};
    const adaLepas = (dampak.santri || 0) + (dampak.kelas || 0) + (dampak.setoran || 0) + (dampak.presensi || 0) > 0;
    const BarisDampak = ({ n, label, warna }) => (n ? (
        <li className={`flex items-baseline justify-between gap-3 text-xs ${warna}`}>
            <span>{label}</span><span className="font-bold tabular-nums">{n}</span>
        </li>
    ) : null);

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

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-cream-300 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';

    const ChevronBawah = ({ modal = false }) => (
        <svg className={`pointer-events-none absolute ${modal ? 'right-3.5 top-[calc(50%+3px)]' : 'right-3 top-1/2'} h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500`}
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const BadgeAktif = ({ aktif }) => (
        aktif ? (
            <span className="inline-flex shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
        ) : (
            <span className="inline-flex shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">Non-Aktif</span>
        )
    );

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Tidak ada data pegawai ditemukan</p>
            <p className="mt-1 text-xs text-stone-400">Ubah filter atau tambahkan pegawai baru</p>
        </>
    );

    const KomponenRow = ({ type, row, idx, list, warna, labelKey }) => {
        const terpilih = list?.find((x) => String(x.id) === String(row.id));
        return (
            <div className="rounded-xl bg-cream-100 p-3 dark:bg-coal-600/40">
                {/* Mobile: nominal override turun ke baris bawah — sebaris
                    dengan select membuat keduanya terlalu sempit. */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
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

                    <div className="flex items-start gap-2">
                        <div className="flex-1 sm:w-28 sm:flex-none">
                            <TextInput type="text" inputMode="numeric" placeholder="Override"
                                value={row.nominal_override ? row.nominal_override.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => handleRowChange(type, idx, 'nominal_override', e.target.value.replace(/\D/g, ''))}
                                className="w-full text-xs" title="Kosongkan untuk mengikuti nominal default" />
                            <InputError message={salaryForm.errors[`${type}.${idx}.nominal_override`]} className="mt-1" />
                        </div>
                        <button type="button" onClick={() => removeRow(type, idx)} title="Hapus baris"
                            className="mt-2.5 shrink-0 text-stone-400 transition hover:text-red-500">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <KeuanganLayout>
            <Head title="Data Pegawai" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-cream-300 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Data Pegawai</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola data pegawai, jabatan, dan komponen gajinya.</p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-sm sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari NIY atau nama..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="relative sm:w-44">
                            <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Status</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {can('master.pegawai.buat') && (
                        <PrimaryButton onClick={openAddModal} className="h-10 w-full justify-center sm:w-auto">Tambah Pegawai</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {pegawai.data.length > 0 ? pegawai.data.map((item) => (
                        <div key={item.id} className="overflow-hidden rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600">
                            <div className="flex items-start justify-between gap-3 p-4">
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{item.name}</p>
                                    <p className="mt-0.5 truncate text-xs text-stone-400">
                                        NIY {item.nip || '-'}
                                        {item.user
                                            ? <span className="text-claude-600 dark:text-claude-300"> · @{item.user.name}</span>
                                            : <span className="italic"> · tanpa akun</span>}
                                    </p>
                                </div>
                                <BadgeAktif aktif={item.is_active} />
                            </div>

                            <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600">
                                <div className="border-r border-cream-200 px-3 py-2.5 text-center dark:border-coal-600">
                                    <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Jabatan</p>
                                    <p className="truncate text-xs font-medium text-stone-700 dark:text-stone-200">
                                        {item.jabatan?.nama_jabatan || <span className="italic text-red-400">Belum ada</span>}
                                    </p>
                                </div>
                                <div className="px-3 py-2.5 text-center">
                                    <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Wali Kelas</p>
                                    <p className="truncate text-xs font-medium text-claude-600 dark:text-claude-300">
                                        {item.kelas_diampu?.length > 0
                                            ? item.kelas_diampu.map((k) => k.name).join(', ')
                                            : <span className="italic text-stone-400">—</span>}
                                    </p>
                                </div>
                            </div>

                            {adaAksi && (
                                <div className="flex border-t border-cream-200 dark:border-coal-600">
                                    {can('master.pegawai.kelola_gaji') && (
                                        <button onClick={() => openSalaryModal(item)}
                                            className="flex flex-1 items-center justify-center gap-1.5 border-r border-cream-200 py-2.5 text-xs font-medium text-emerald-600 transition hover:bg-emerald-50 dark:border-coal-600 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Gaji
                                        </button>
                                    )}
                                    {can('master.pegawai.edit') && (
                                        <button onClick={() => openEditModal(item)}
                                            className="flex flex-1 items-center justify-center gap-1.5 border-r border-cream-200 py-2.5 text-xs font-medium text-claude-600 transition hover:bg-claude-500/10 dark:border-coal-600 dark:text-claude-300">
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Ubah
                                        </button>
                                    )}
                                    {can('master.pegawai.hapus') && (
                                        <button onClick={() => confirmDelete(item)}
                                            className="flex flex-1 items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
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
                        <div className="rounded-2xl py-14 text-center ring-1 ring-cream-300 dark:ring-coal-600">{kosong}</div>
                    )}
                </div>

                {/* ===== TABEL — DESKTOP ===== */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 lg:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th onClick={() => handleSort('nip')} className={thCls}><div className="flex items-center">NIY <SortIcon field="nip" /></div></th>
                                <th onClick={() => handleSort('name')} className={thCls}><div className="flex items-center">Nama Pegawai <SortIcon field="name" /></div></th>
                                <th className={thPlain}>Jabatan &amp; Kelas</th>
                                <th onClick={() => handleSort('is_active')} className={thCls}><div className="flex items-center">Status <SortIcon field="is_active" /></div></th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {pegawai.data.length > 0 ? pegawai.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.nip || '-'}</td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-700 dark:text-stone-200">
                                        {item.name}
                                        <br />
                                        {item.user
                                            ? <span className="text-[10px] text-claude-600 dark:text-claude-300">@{item.user.name}</span>
                                            : <span className="text-[10px] italic text-stone-400">Tanpa akun login</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                        <span className="font-medium text-stone-800 dark:text-cream-100">
                                            {item.jabatan?.nama_jabatan || <span className="italic text-red-400">Tidak ada jabatan</span>}
                                        </span>
                                        <br />
                                        {item.kelas_diampu?.length > 0
                                            ? <span className="text-[10px] uppercase tracking-wider text-claude-600 dark:text-claude-300">Wali: {item.kelas_diampu.map((k) => k.name).join(', ')}</span>
                                            : <span className="text-[10px] italic text-stone-400">Non-guru kelas</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4"><BadgeAktif aktif={item.is_active} /></td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.pegawai.kelola_gaji') && (
                                                    <button onClick={() => openSalaryModal(item)}
                                                        className={`${btnGhost} text-emerald-600 ring-emerald-200 hover:bg-emerald-50 dark:text-emerald-400 dark:ring-emerald-500/20 dark:hover:bg-emerald-500/10`}>
                                                        Atur Gaji
                                                    </button>
                                                )}
                                                {can('master.pegawai.edit') && (
                                                    <button onClick={() => openEditModal(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        Ubah
                                                    </button>
                                                )}
                                                {can('master.pegawai.hapus') && (
                                                    <button onClick={() => confirmDelete(item)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 5 : 4} className="px-6 py-14 text-center">{kosong}</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {pegawai.from || 0}–{pegawai.to || 0} dari {pegawai.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {pegawai.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${
                                    link.active ? 'bg-claude-500 font-medium text-white'
                                        : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <Modal show={isSalaryModalOpen} onClose={() => setIsSalaryModalOpen(false)} maxWidth="4xl">
                <form onSubmit={submitSalary} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Atur Komponen Gaji</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">{selectedPegawai?.name} · {selectedPegawai?.nip}</p>

                    <div className="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <div className="mb-3 flex items-center justify-between border-b border-cream-300 pb-2 dark:border-coal-600">
                                <h3 className="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Tunjangan</h3>
                                <button type="button" onClick={() => addRow('allowances')} className="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">+ Baris</button>
                            </div>
                            <div className="space-y-2">
                                {salaryForm.data.allowances.map((row, idx) => (
                                    <KomponenRow key={idx} type="allowances" row={row} idx={idx} list={listTunjangan}
                                        warna="text-emerald-600 dark:text-emerald-400" labelKey="nama_tunjangan" />
                                ))}
                                {salaryForm.data.allowances.length === 0 && (
                                    <p className="rounded-xl border-2 border-dashed border-cream-300 py-4 text-center text-xs text-stone-400 dark:border-coal-600">Belum ada tunjangan.</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <div className="mb-3 flex items-center justify-between border-b border-cream-300 pb-2 dark:border-coal-600">
                                <h3 className="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">Potongan</h3>
                                <button type="button" onClick={() => addRow('deductions')} className="text-xs font-medium text-red-600 hover:underline dark:text-red-400">+ Baris</button>
                            </div>
                            <div className="space-y-2">
                                {salaryForm.data.deductions.map((row, idx) => (
                                    <KomponenRow key={idx} type="deductions" row={row} idx={idx} list={listPotongan}
                                        warna="text-red-600 dark:text-red-400" labelKey="nama_potongan" />
                                ))}
                                {salaryForm.data.deductions.length === 0 && (
                                    <p className="rounded-xl border-2 border-dashed border-cream-300 py-4 text-center text-xs text-stone-400 dark:border-coal-600">Belum ada potongan.</p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col justify-end gap-2 border-t border-cream-200 pt-5 dark:border-coal-600 sm:flex-row">
                        <SecondaryButton type="button" onClick={() => setIsSalaryModalOpen(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={salaryForm.processing} className="justify-center">Simpan Pengaturan Gaji</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="2xl">
                {/* overflow-y-auto: tinggi maksimum sudah dibatasi oleh Modal,
                    di sini cukup memastikan isinya yang menggulir. */}
                <form onSubmit={submit} className="overflow-y-auto p-6">
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
                                <p className="mt-1 text-xs text-stone-400">Menentukan gaji pokok di penggajian.</p>
                                <div className="relative">
                                    <select id="position_id" value={data.position_id} onChange={(e) => pilihJabatan(e.target.value)} className={selectModal}>
                                        <option value="">-- Pilih Jabatan --</option>
                                        {listJabatan.map((j) => <option key={j.id} value={j.id}>{j.nama_jabatan}</option>)}
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <InputError message={errors.position_id} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel value="Wali Kelas" />
                                <p className="mt-2 rounded-xl bg-cream-100 px-3.5 py-2.5 text-xs text-stone-500 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:text-stone-400 dark:ring-coal-600">
                                    Penugasan wali kelas diatur dari panel raport, karena terikat tahun ajaran.
                                </p>
                            </div>
                        </div>

                        <div className="border-t border-cream-200 pt-4 dark:border-coal-600">
                            <label className="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-cream-400 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-600/40" />
                                <span className="text-sm text-stone-600 dark:text-stone-300">Pegawai Aktif</span>
                            </label>
                        </div>

                        {/* --- AKUN LOGIN (opsional) --- */}
                        <div className="border-t border-cream-200 pt-4 dark:border-coal-600">
                            {sudahPunyaAkun ? (
                                <p className="rounded-xl bg-cream-100 px-3.5 py-2.5 text-xs leading-relaxed text-stone-500 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:text-stone-400 dark:ring-coal-600">
                                    Pegawai ini sudah punya akun login. Ubah username, password, atau hak aksesnya dari Pengaturan → Manajemen User.
                                </p>
                            ) : (
                                <>
                                    <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <h3 className="text-sm font-semibold text-stone-700 dark:text-stone-200">Akun Login</h3>
                                        <label className="flex shrink-0 cursor-pointer items-center gap-2">
                                            <input type="checkbox" checked={data.buat_akun}
                                                onChange={(e) => setData('buat_akun', e.target.checked)}
                                                className="h-4 w-4 rounded border-cream-400 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-600/40" />
                                            <span className="text-xs text-stone-500 dark:text-stone-400">Pegawai ini perlu login</span>
                                        </label>
                                    </div>

                                    {data.buat_akun ? (
                                        <div className="space-y-4 rounded-xl bg-cream-100 p-4 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <InputLabel htmlFor="username">Username <span className="text-red-500">*</span></InputLabel>
                                                    <TextInput id="username" value={data.username} onChange={(e) => setData('username', e.target.value)}
                                                        className="mt-1.5 block w-full" placeholder="Cth: bendahara01" />
                                                    <p className="mt-1 text-xs text-stone-400">Dipakai untuk login. Sistem ini tidak memakai email.</p>
                                                    <InputError message={errors.username} className="mt-1.5" />
                                                </div>
                                                <div>
                                                    <InputLabel htmlFor="password">Password <span className="text-red-500">*</span></InputLabel>
                                                    <TextInput id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)}
                                                        className="mt-1.5 block w-full" placeholder="Minimal 8 karakter" />
                                                    <InputError message={errors.password} className="mt-1.5" />
                                                </div>
                                            </div>

                                            <div>
                                                <InputLabel htmlFor="roles">Hak Akses <span className="text-red-500">*</span></InputLabel>
                                                <p className="mt-1 text-xs text-stone-400">
                                                    Bisa lebih dari satu — centang semua peran yang dipegang pegawai ini.
                                                    {roleOtomatis && ' Bawaan jabatan sudah terisi.'}
                                                </p>
                                                <div className="mt-1.5">
                                                    <MultiSelectChips
                                                        placeholder="-- Pilih Hak Akses --"
                                                        options={listRole.map((r) => ({ id: r.name, label: r.name }))}
                                                        value={data.roles}
                                                        onChange={(val) => setData('roles', val)}
                                                    />
                                                </div>
                                                <InputError message={errors.roles} className="mt-1.5" />

                                                {jabatanGuru && !punyaAksesKeuangan && (
                                                    <p className="mt-2 rounded-lg bg-blue-50 p-2.5 text-[11px] leading-relaxed font-medium text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                                                        Akun ini hanya bisa membuka panel raport. Datanya tetap tercatat di penggajian sebagai pegawai.
                                                    </p>
                                                )}
                                                {jabatanGuru && punyaAksesKeuangan && (
                                                    <p className="mt-2 rounded-lg bg-amber-50 p-2.5 text-[11px] leading-relaxed font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                                        Jabatannya Guru, jadi role “guru” tetap ditambahkan otomatis agar akses raport tidak putus — meski tidak dicentang.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="rounded-xl bg-cream-100 px-3.5 py-2.5 text-xs leading-relaxed text-stone-500 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:text-stone-400 dark:ring-coal-600">
                                            Pegawai disimpan tanpa akun login. Akun bisa dibuatkan nanti lewat tombol Ubah.
                                        </p>
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsModalOpen(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>{isEditing ? 'Simpan Perubahan' : 'Simpan Data'}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDeleteModalOpen} onClose={() => !deleting && setIsDeleteModalOpen(false)} maxWidth="md">
                {deleteTarget && (
                    <div className="space-y-4 p-6">
                        <div className="flex items-start gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </span>
                            <div className="min-w-0">
                                <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus {deleteTarget.name}</h3>
                                <p className="mt-0.5 text-sm text-stone-500 dark:text-stone-400">Penghapusan ini permanen dan tidak bisa dibatalkan.</p>
                            </div>
                        </div>

                        <div className="rounded-xl bg-red-50/70 px-4 py-3 ring-1 ring-red-200 dark:bg-red-500/10 dark:ring-red-500/20">
                            <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-red-700 dark:text-red-300">Ikut terhapus</p>
                            <ul className="space-y-1">
                                <BarisDampak n={1} label="Data pegawai & komponen gaji" warna="text-red-700 dark:text-red-300" />
                                <BarisDampak n={deleteTarget.user ? 1 : 0} label="Akun login & foto" warna="text-red-700 dark:text-red-300" />
                                <BarisDampak n={dampak.absensi} label="Absensi" warna="text-red-700 dark:text-red-300" />
                                <BarisDampak n={dampak.gaji_draft} label="Baris gaji (periode masih draft)" warna="text-red-700 dark:text-red-300" />
                            </ul>
                        </div>

                        {adaLepas && (
                            <div className="rounded-xl bg-cream-100 px-4 py-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-stone-600 dark:text-stone-300">Tetap ada, tanpa guru</p>
                                <ul className="space-y-1">
                                    <BarisDampak n={dampak.santri} label="Santri bimbingan" warna="text-stone-700 dark:text-stone-300" />
                                    <BarisDampak n={dampak.kelas} label="Kelas yang diampu" warna="text-stone-700 dark:text-stone-300" />
                                    <BarisDampak n={dampak.setoran} label="Setoran hafalan" warna="text-stone-700 dark:text-stone-300" />
                                    <BarisDampak n={dampak.presensi} label="Presensi santri" warna="text-stone-700 dark:text-stone-300" />
                                </ul>
                            </div>
                        )}

                        {dampak.gaji_beku > 0 && (
                            <p className="rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                <strong>{dampak.gaji_beku} baris gaji</strong> yang sudah diproses tetap disimpan dengan nama pegawai, karena sudah tercatat di buku kas.
                            </p>
                        )}

                        {deleteError && (
                            <div className="rounded-xl bg-red-50 p-3 text-sm text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">{deleteError}</div>
                        )}

                        <div className="flex justify-end gap-2 pt-1">
                            <SecondaryButton onClick={() => setIsDeleteModalOpen(false)} disabled={deleting}>Batal</SecondaryButton>
                            <button onClick={executeDelete} disabled={deleting}
                                className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">
                                {deleting ? 'Menghapus...' : 'Hapus Permanen'}
                            </button>
                        </div>
                    </div>
                )}
            </Modal>
        </KeuanganLayout>
    );
}
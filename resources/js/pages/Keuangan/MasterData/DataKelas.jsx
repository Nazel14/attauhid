import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';

// Rumah Quran diberi biru At-Tauhid supaya terbedakan sekilas dari TK (hijau).
const BADGE_LEMBAGA = {
    tk:     'bg-claude-50 text-claude-700 ring-claude-200 dark:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/20',
    arahan: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20',
};

function BadgeLembaga({ lembaga, label }) {
    return (
        <span className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${BADGE_LEMBAGA[lembaga] || BADGE_LEMBAGA.tk}`}>
            {label}
        </span>
    );
}

export default function DataKelas({ kelas, listTahun, listLembaga = { tk: 'TK At-Tauhid', arahan: 'Rumah Quran' }, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [tahunFilter, setTahunFilter] = useState(filters?.tahunFilter || '');
    const [lembagaFilter, setLembagaFilter] = useState(filters?.lembagaFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'name');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '',
        tahun_ajaran: '',
        tingkat: '',
        lembaga: 'tk',
        kapasitas: 20,
        is_active: true,
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.kelas.index'),
                { search, tahunFilter, lembagaFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, tahunFilter, lembagaFilter, sortField, sortDirection]);

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
            name: item.name,
            tahun_ajaran: item.tahun_ajaran || '',
            tingkat: item.tingkat || '',
            lembaga: item.lembaga || 'tk',
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

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;

    const ChevronBawah = () => (
        <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const BadgeStatus = ({ aktif }) => (
        aktif ? (
            <span className="inline-flex shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
        ) : (
            <span className="inline-flex shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">Non-Aktif</span>
        )
    );

    const Terisi = ({ item }) => (
        <>
            <span className={`font-semibold ${item.santris_count >= item.kapasitas ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'}`}>
                {item.santris_count ?? 0}
            </span>
            <span className="text-stone-400"> / {item.kapasitas}</span>
        </>
    );

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-9-6.5V15a9 9 0 0018 0v-2.5" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Tidak ada data kelas ditemukan</p>
            <p className="mt-1 text-xs text-stone-400">Ubah kata kunci pencarian atau tambahkan kelas baru</p>
        </>
    );

    return (
        <KeuanganLayout>
            <Head title="Data Kelas" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-cream-300 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Data Kelas</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Satu kelas berlaku untuk satu tahun ajaran. Lembaga menentukan cara bayar: kelas TK ditagih, kelas Rumah Quran membayar infaq sukarela.
                </p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-sm sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama kelas atau tingkat..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        <div className="relative sm:w-52">
                            <select value={tahunFilter} onChange={(e) => setTahunFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Tahun Ajaran</option>
                                {listTahun.map((t, i) => <option key={i} value={t}>{t}</option>)}
                            </select>
                            <ChevronBawah />
                        </div>

                        <div className="relative sm:w-44">
                            <select value={lembagaFilter} onChange={(e) => setLembagaFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Lembaga</option>
                                {Object.entries(listLembaga).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {can('master.kelas.buat') && (
                        <PrimaryButton onClick={openAddModal} className="h-10 w-full justify-center sm:w-auto">Tambah Kelas</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {kelas.data.length > 0 ? kelas.data.map((item) => (
                        <div key={item.id} className="overflow-hidden rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600">
                            <div className="flex items-start justify-between gap-3 p-4">
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">
                                        {item.name}
                                        {item.tingkat && <span className="ml-1.5 text-xs font-normal text-stone-400">({item.tingkat})</span>}
                                    </p>
                                    <p className="mt-0.5 text-xs text-stone-400">{item.tahun_ajaran || 'Tanpa tahun ajaran'}</p>
                                </div>
                                <div className="flex shrink-0 flex-col items-end gap-1.5">
                                    <BadgeLembaga lembaga={item.lembaga} label={listLembaga[item.lembaga] || listLembaga.tk} />
                                    <BadgeStatus aktif={item.is_active} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600">
                                <div className="border-r border-cream-200 px-3 py-2.5 text-center dark:border-coal-600">
                                    <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Wali Kelas</p>
                                    <p className="truncate text-xs font-medium text-stone-700 dark:text-stone-200">
                                        {item.guru?.name || <span className="italic text-stone-400">Belum ada</span>}
                                    </p>
                                </div>
                                <div className="px-3 py-2.5 text-center">
                                    <p className="mb-1 text-[10px] uppercase tracking-wider text-stone-400">Siswa / Kapasitas</p>
                                    <p className="text-xs"><Terisi item={item} /></p>
                                </div>
                            </div>

                            {adaAksi && (
                                <div className="grid grid-cols-2 border-t border-cream-200 dark:border-coal-600">
                                    {can('master.kelas.edit') && (
                                        <button onClick={() => openEditModal(item)}
                                            className="flex items-center justify-center gap-1.5 border-r border-cream-200 py-2.5 text-xs font-medium text-claude-600 transition hover:bg-claude-500/10 dark:border-coal-600 dark:text-claude-300">
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                    )}
                                    {can('master.kelas.hapus') && (
                                        <button onClick={() => confirmDelete(item.id)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 ${!can('master.kelas.edit') ? 'col-span-2' : ''}`}>
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
                                <th onClick={() => handleSort('name')} className={thCls}><div className="flex items-center">Nama Kelas <SortIcon field="name" /></div></th>
                                <th onClick={() => handleSort('tahun_ajaran')} className={thCls}><div className="flex items-center">Tahun Ajaran <SortIcon field="tahun_ajaran" /></div></th>
                                <th onClick={() => handleSort('lembaga')} className={thCls}><div className="flex items-center">Lembaga <SortIcon field="lembaga" /></div></th>
                                <th className={thPlain}>Wali Kelas</th>
                                <th className={`${thPlain} text-center`}>Siswa / Kapasitas</th>
                                <th onClick={() => handleSort('is_active')} className={`${thCls} text-center`}><div className="flex items-center justify-center">Status <SortIcon field="is_active" /></div></th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {kelas.data.length > 0 ? kelas.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">
                                        {item.name}
                                        {item.tingkat && <span className="ml-1.5 text-xs font-normal text-stone-400">({item.tingkat})</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{item.tahun_ajaran || '-'}</td>
                                    <td className="whitespace-nowrap px-5 py-4"><BadgeLembaga lembaga={item.lembaga} label={listLembaga[item.lembaga] || listLembaga.tk} /></td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                        {item.guru
                                            ? <span className="text-stone-700 dark:text-stone-200">{item.guru.name}</span>
                                            : <span className="text-xs italic text-stone-400">Belum ada wali</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4 text-center text-sm"><Terisi item={item} /></td>
                                    <td className="whitespace-nowrap px-5 py-4 text-center"><BadgeStatus aktif={item.is_active} /></td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.kelas.edit') && (
                                                    <button onClick={() => openEditModal(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                )}
                                                {can('master.kelas.hapus') && (
                                                    <button onClick={() => confirmDelete(item.id)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 7 : 6} className="px-6 py-14 text-center">{kosong}</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {kelas.from || 0}–{kelas.to || 0} dari {kelas.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {kelas.links.map((link, i) => (
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

            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Data Kelas' : 'Tambah Data Kelas'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="name">Nama Kelas <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="name" value={data.name} onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: Kelas 7A" />
                                <InputError message={errors.name} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="tahun_ajaran">Tahun Ajaran <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="tahun_ajaran" value={data.tahun_ajaran} onChange={(e) => setData('tahun_ajaran', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: 2026/2027" />
                                <InputError message={errors.tahun_ajaran} className="mt-1.5" />
                            </div>
                        </div>

                        <div>
                            <InputLabel>Lembaga <span className="text-red-500">*</span></InputLabel>
                            <div className="mt-1.5 grid grid-cols-2 gap-2">
                                {Object.entries(listLembaga).map(([k, label]) => (
                                    <button key={k} type="button" onClick={() => setData('lembaga', k)}
                                        className={`rounded-xl px-3 py-2.5 text-left text-sm transition ring-1 ${
                                            data.lembaga === k
                                                ? 'bg-claude-500/10 font-semibold text-claude-700 ring-2 ring-claude-500 dark:text-claude-300'
                                                : 'text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                        }`}>
                                        {label}
                                        <span className="mt-0.5 block text-xs font-normal text-stone-400">
                                            {k === 'arahan' ? 'Infaq sukarela, tanpa tagihan' : 'Ditagih lewat gelombang tagihan'}
                                        </span>
                                    </button>
                                ))}
                            </div>
                            <InputError message={errors.lembaga} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="tingkat">Tingkat Kelas</InputLabel>
                                <TextInput id="tingkat" value={data.tingkat} onChange={(e) => setData('tingkat', e.target.value)}
                                    className="mt-1.5 block w-full" placeholder="Contoh: VII" />
                                <InputError message={errors.tingkat} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="kapasitas">Kapasitas Maksimal <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="kapasitas" type="number" min="1" value={data.kapasitas} onChange={(e) => setData('kapasitas', e.target.value)}
                                    className="mt-1.5 block w-full" />
                                <InputError message={errors.kapasitas} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="border-t border-cream-200 pt-4 dark:border-coal-600">
                            <label className="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-cream-400 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-600/40" />
                                <span className="text-sm text-stone-600 dark:text-stone-300">Kelas Sedang Aktif</span>
                            </label>
                            <p className="mt-2 text-xs text-stone-400">
                                Wali kelas dan pembagian siswa diatur dari panel raport.
                            </p>
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
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Data Kelas</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelas ini juga dipakai modul raport. Yakin ingin menghapus?</p>
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
import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import { formatCurrency } from '@/Utils/formatCurrency';

export default function DataTarif({ tarif, dimensions, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [periodFilter, setPeriodFilter] = useState(filters?.periodFilter || '');
    const [statusFilter, setStatusFilter] = useState(filters?.statusFilter || '');
    const [kelasFilter, setKelasFilter] = useState(filters?.kelasFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [deleteError, setDeleteError] = useState('');

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        billing_name: '',
        nominal: '',
        period: 'Bulanan',
        is_active: true,
        is_donation: false,
        // [{ type: 'kelas', values: ['1','2'] }, ...]
        targets: [],
    });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.tarif'),
                { search, periodFilter, statusFilter, kelasFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, periodFilter, statusFilter, kelasFilter, sortField, sortDirection]);

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

    /* ---------- builder kriteria ---------- */

    const dimensionKeys = Object.keys(dimensions || {});

    // Dimensi yang belum dipakai — satu dimensi cukup sekali, nilainya
    // digabung di dalamnya (semantik OR).
    const dimensiTersisa = dimensionKeys.filter(
        (k) => !data.targets.some((t) => t.type === k)
    );

    const tambahKriteria = (type) => {
        if (!type) return;
        setData('targets', [...data.targets, { type, values: [] }]);
    };

    const hapusKriteria = (idx) => {
        setData('targets', data.targets.filter((_, i) => i !== idx));
    };

    const toggleNilai = (idx, value) => {
        setData('targets', data.targets.map((t, i) => {
            if (i !== idx) return t;
            const ada = t.values.includes(value);
            return { ...t, values: ada ? t.values.filter((v) => v !== value) : [...t.values, value] };
        }));
    };

    const labelNilai = (type, value) =>
        dimensions?.[type]?.options?.find((o) => o.value === value)?.label ?? value;

    // Ringkasan realtime. Semantik AND/OR mudah disalahpahami dan ini
    // dipakai untuk menagih orang, jadi selalu ditampilkan.
    const ringkasanTarget = () => {
        if (data.is_donation) return 'Donasi (tanpa target santri)';

        const terisi = data.targets.filter((t) => t.values.length > 0);
        if (terisi.length === 0) return 'Semua santri aktif';

        return terisi
            .map((t) => `${dimensions[t.type]?.label ?? t.type}: ${t.values.map((v) => labelNilai(t.type, v)).join(', ')}`)
            .join('  —  dan  —  ');
    };

    /* ---------- modal ---------- */

    const openAddModal = () => {
        setIsEditing(false); setEditId(null); reset(); clearErrors(); setIsModalOpen(true);
    };

    const openEditModal = (item) => {
        setIsEditing(true); setEditId(item.id); clearErrors();

        // Backend mengirim daftar datar [{type, value}], UI butuh
        // dikelompokkan per dimensi.
        const grouped = [];
        (item.targets || []).forEach((r) => {
            const found = grouped.find((g) => g.type === r.type);
            if (found) found.values.push(String(r.value));
            else grouped.push({ type: r.type, values: [String(r.value)] });
        });

        setData({
            billing_name: item.billing_name,
            nominal: Math.floor(item.nominal).toString(),
            period: item.period,
            is_active: Boolean(item.is_active),
            is_donation: Boolean(item.is_donation),
            targets: grouped,
        });
        setIsModalOpen(true);
    };

    const confirmDelete = (id) => { setDeleteError(''); setDeleteId(id); setIsDeleteModalOpen(true); };

    const executeDelete = () => {
        router.delete(route('keuangan.master-data.tarif.destroy', deleteId), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleteModalOpen(false); setDeleteId(null); },
            onError: (err) => { if (err.delete_error) setDeleteError(err.delete_error); },
        });
    };

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsModalOpen(false); reset(); }, preserveScroll: true };
        if (isEditing) put(route('keuangan.master-data.tarif.update', editId), opts);
        else post(route('keuangan.master-data.tarif.store'), opts);
    };

    /* ---------- style ---------- */

    const periodBadge = (p) => ({
        'Bulanan':      'bg-claude-500/10 text-claude-700 ring-claude-500/20 dark:text-claude-300',
        'Semester':     'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'Tahunan':      'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'Sekali Bayar': 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/20',
    }[p] || 'bg-stone-100 text-stone-600 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500');

    const adaAksi = can('master.tarif.edit') || can('master.tarif.hapus');

    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
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

    const BadgeAktif = ({ aktif }) => (
        aktif ? (
            <span className="inline-flex shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">Aktif</span>
        ) : (
            <span className="inline-flex shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">Non-Aktif</span>
        )
    );

    const BadgeTarget = ({ item }) => (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${
            item.is_donation
                ? 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-500/20'
                : 'bg-stone-100 text-stone-600 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500'
        }`}>
            {item.target_summary}
        </span>
    );

    const kosong = (
        <>
            <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <p className="text-sm font-medium text-stone-600 dark:text-stone-300">Tidak ada data tarif ditemukan</p>
            <p className="mt-1 text-xs text-stone-400">Ubah filter atau tambahkan tarif baru</p>
        </>
    );

    return (
        <KeuanganLayout>
            <Head title="Data Tarif" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-stone-200 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Data Tarif</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola jenis tagihan dan nominalnya.</p>
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama tarif..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>

                        {/* Dua select berdampingan di mobile — bertumpuk vertikal terlalu boros. */}
                        <div className="flex gap-2">
                            <div className="relative flex-1 sm:w-40 sm:flex-none">
                                <select value={periodFilter} onChange={(e) => setPeriodFilter(e.target.value)} className={selectCls}>
                                    <option value="">Semua Periode</option>
                                    <option value="Bulanan">Bulanan</option>
                                    <option value="Semester">Semester</option>
                                    <option value="Tahunan">Tahunan</option>
                                    <option value="Sekali Bayar">Sekali Bayar</option>
                                </select>
                                <ChevronBawah />
                            </div>

                            <div className="relative flex-1 sm:w-36 sm:flex-none">
                                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectCls}>
                                    <option value="">Semua Status</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Non-Aktif">Non-Aktif</option>
                                </select>
                                <ChevronBawah />
                            </div>
                        </div>

                        <div className="relative sm:w-44">
                            <select value={kelasFilter} onChange={(e) => setKelasFilter(e.target.value)} className={selectCls}>
                                <option value="">Semua Kelas</option>
                                {(dimensions?.kelas?.options || []).map((o) => (
                                    <option key={o.value} value={o.value}>{o.label}</option>
                                ))}
                            </select>
                            <ChevronBawah />
                        </div>
                    </div>

                    {can('master.tarif.buat') && (
                        <PrimaryButton onClick={openAddModal} className="h-10 w-full justify-center lg:w-auto">Tambah Tarif</PrimaryButton>
                    )}
                </div>

                {/* ===== KARTU — MOBILE & TABLET ===== */}
                <div className="space-y-2.5 lg:hidden">
                    {tarif.data.length > 0 ? tarif.data.map((item) => (
                        <div key={item.id} className="overflow-hidden rounded-2xl ring-1 ring-stone-200 dark:ring-coal-600">
                            <div className="p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <p className="min-w-0 truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{item.billing_name}</p>
                                    <BadgeAktif aktif={item.is_active} />
                                </div>
                                <p className="mt-2 text-lg font-bold text-emerald-600 dark:text-emerald-400">{formatCurrency(item.nominal)}</p>
                                <div className="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${periodBadge(item.period)}`}>{item.period}</span>
                                    <BadgeTarget item={item} />
                                </div>
                            </div>

                            {adaAksi && (
                                <div className="grid grid-cols-2 border-t border-stone-100 dark:border-coal-600">
                                    {can('master.tarif.edit') && (
                                        <button onClick={() => openEditModal(item)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-claude-600 transition hover:bg-claude-500/10 dark:text-claude-300 ${can('master.tarif.hapus') ? 'border-r border-stone-100 dark:border-coal-600' : 'col-span-2'}`}>
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                    )}
                                    {can('master.tarif.hapus') && (
                                        <button onClick={() => confirmDelete(item.id)}
                                            className={`flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 ${!can('master.tarif.edit') ? 'col-span-2' : ''}`}>
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
                        <div className="rounded-2xl py-14 text-center ring-1 ring-stone-200 dark:ring-coal-600">{kosong}</div>
                    )}
                </div>

                {/* ===== TABEL — DESKTOP ===== */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 lg:block">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('billing_name')} className={thCls}><div className="flex items-center">Nama Tarif <SortIcon field="billing_name" /></div></th>
                                <th onClick={() => handleSort('period')} className={thCls}><div className="flex items-center">Periode <SortIcon field="period" /></div></th>
                                <th onClick={() => handleSort('nominal')} className={thCls}><div className="flex items-center">Nominal <SortIcon field="nominal" /></div></th>
                                {/* Target ada di tabel terpisah — tidak bisa di-sort lewat kolom. */}
                                <th className={thPlain}>Target</th>
                                <th onClick={() => handleSort('is_active')} className={thCls}><div className="flex items-center">Status <SortIcon field="is_active" /></div></th>
                                {adaAksi && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {tarif.data.length > 0 ? tarif.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{item.billing_name}</td>
                                    <td className="px-5 py-4">
                                        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${periodBadge(item.period)}`}>{item.period}</span>
                                    </td>
                                    <td className="px-5 py-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">{formatCurrency(item.nominal)}</td>
                                    <td className="max-w-xs px-5 py-4"><BadgeTarget item={item} /></td>
                                    <td className="px-5 py-4"><BadgeAktif aktif={item.is_active} /></td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('master.tarif.edit') && (
                                                    <button onClick={() => openEditModal(item)}
                                                        className={`${btnGhost} text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30`}>
                                                        Edit
                                                    </button>
                                                )}
                                                {can('master.tarif.hapus') && (
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
                                <tr><td colSpan={adaAksi ? 6 : 5} className="px-6 py-14 text-center">{kosong}</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">
                        Menampilkan {tarif.from || 0}–{tarif.to || 0} dari {tarif.total} entri
                    </p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {tarif.links.map((link, i) => (
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
                <form onSubmit={submit} className="max-h-[85vh] overflow-y-auto p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditing ? 'Edit Tarif' : 'Tambah Tarif Baru'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel htmlFor="billing_name">Nama Tarif Tagihan <span className="text-red-500">*</span></InputLabel>
                            <TextInput id="billing_name" value={data.billing_name} onChange={(e) => setData('billing_name', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: SPP Bulanan Kelas 1" />
                            <InputError message={errors.billing_name} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="period">Jenis / Periode <span className="text-red-500">*</span></InputLabel>
                                <div className="relative">
                                    <select id="period" value={data.period} onChange={(e) => setData('period', e.target.value)} className={selectModal}>
                                        <option value="Bulanan">Bulanan</option>
                                        <option value="Semester">Semester</option>
                                        <option value="Tahunan">Tahunan</option>
                                        <option value="Sekali Bayar">Sekali Bayar</option>
                                    </select>
                                    <ChevronBawah modal />
                                </div>
                                <InputError message={errors.period} className="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel htmlFor="nominal">Nominal (Rp) <span className="text-red-500">*</span></InputLabel>
                                <TextInput id="nominal" type="text" inputMode="numeric"
                                    value={data.nominal ? data.nominal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                    onChange={(e) => setData('nominal', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1.5 block w-full" placeholder="Contoh: 350.000" />
                                <InputError message={errors.nominal} className="mt-1.5" />
                            </div>
                        </div>

                        {/* ===== TARIF DONASI ===== */}
                        <div className="rounded-xl bg-stone-50 p-3.5 ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600">
                            <label className="flex cursor-pointer items-start gap-2.5">
                                <input type="checkbox" checked={data.is_donation}
                                    onChange={(e) => setData('is_donation', e.target.checked)}
                                    className="mt-0.5 h-4 w-4 rounded border-stone-300 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-900" />
                                <span>
                                    <span className="text-sm font-medium text-stone-700 dark:text-stone-200">Tarif donasi / infaq</span>
                                    <span className="mt-0.5 block text-xs text-stone-400">
                                        Tidak menargetkan santri manapun. Penyumbang ditambahkan manual, auto-generate dinonaktifkan.
                                    </span>
                                </span>
                            </label>
                        </div>

                        {/* ===== BUILDER KRITERIA TARGET ===== */}
                        {!data.is_donation && (
                            <div>
                                <InputLabel>Target Penerima Tagihan</InputLabel>
                                <p className="mt-1 text-xs text-stone-400">
                                    Menentukan siapa yang terjaring saat auto-generate. Dalam satu kriteria berlaku <b>salah satu</b>, antar kriteria berlaku <b>semuanya</b>.
                                </p>

                                <div className="mt-2.5 space-y-2.5">
                                    {data.targets.map((t, idx) => (
                                        <div key={t.type} className="rounded-xl bg-white p-3.5 ring-1 ring-stone-200 dark:bg-coal-900 dark:ring-coal-700">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                                    {dimensions[t.type]?.label ?? t.type}
                                                </span>
                                                <button type="button" onClick={() => hapusKriteria(idx)}
                                                    className="rounded-lg p-1 text-stone-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div className="mt-2.5 flex flex-wrap gap-1.5">
                                                {(dimensions[t.type]?.options || []).length === 0 && (
                                                    <p className="text-xs italic text-stone-400">Belum ada data untuk kriteria ini.</p>
                                                )}
                                                {(dimensions[t.type]?.options || []).map((o) => {
                                                    const aktif = t.values.includes(o.value);
                                                    return (
                                                        <button key={o.value} type="button" onClick={() => toggleNilai(idx, o.value)}
                                                            className={`rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition ${
                                                                aktif
                                                                    ? 'bg-claude-500 text-white ring-claude-500'
                                                                    : 'bg-white text-stone-600 ring-stone-200 hover:bg-cream-100 dark:bg-coal-800 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-700'
                                                            }`}>
                                                            {o.label}
                                                        </button>
                                                    );
                                                })}
                                            </div>

                                            {t.values.length === 0 && (
                                                <p className="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                                    Pilih minimal satu nilai, atau hapus kriteria ini.
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>

                                {dimensiTersisa.length > 0 && (
                                    <div className="relative mt-2.5">
                                        <select value="" onChange={(e) => tambahKriteria(e.target.value)} className={selectModal}>
                                            <option value="">+ Tambah Kriteria…</option>
                                            {dimensiTersisa.map((k) => (
                                                <option key={k} value={k}>{dimensions[k].label}</option>
                                            ))}
                                        </select>
                                        <ChevronBawah modal />
                                    </div>
                                )}

                                {/* Ringkasan realtime — semantik AND/OR mudah salah dibaca. */}
                                <div className="mt-3 rounded-xl bg-claude-500/5 p-3 ring-1 ring-claude-500/20">
                                    <p className="text-[11px] font-semibold uppercase tracking-wide text-claude-700 dark:text-claude-300">Akan ditagihkan ke</p>
                                    <p className="mt-1 text-sm text-stone-700 dark:text-stone-200">{ringkasanTarget()}</p>
                                </div>

                                {Object.keys(errors).filter((k) => k.startsWith('targets')).map((k) => (
                                    <InputError key={k} message={errors[k]} className="mt-1.5" />
                                ))}
                            </div>
                        )}

                        <div className="border-t border-stone-100 pt-4 dark:border-coal-600">
                            <label className="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-stone-300 text-claude-500 focus:ring-claude-500 dark:border-coal-600 dark:bg-coal-900" />
                                <span className="text-sm text-stone-600 dark:text-stone-300">Status Tarif Aktif</span>
                            </label>
                            <p className="mt-2 text-xs text-stone-400">Tarif non-aktif tidak muncul saat membuat gelombang tagihan baru.</p>
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
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Konfirmasi Hapus</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Yakin ingin menghapus tarif ini?</p>
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
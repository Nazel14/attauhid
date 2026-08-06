import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import TombolKembali from '@/Components/TombolKembali';
import { formatCurrency } from '@/Utils/formatCurrency';

export default function KomponenPenggajian({ tunjangan, potongan, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const lihatT = can('tunjangan.lihat');
    const lihatP = can('potongan.lihat');

    const [tab, setTab] = useState(lihatT ? 'tunjangan' : (lihatP ? 'potongan' : null));

    const [searchT, setSearchT] = useState(filters?.search_tunjangan || '');
    const [sortT, setSortT] = useState(filters?.sort_tunjangan || 'created_at');
    const [dirT, setDirT] = useState(filters?.dir_tunjangan || 'desc');
    const [searchP, setSearchP] = useState(filters?.search_potongan || '');
    const [sortP, setSortP] = useState(filters?.sort_potongan || 'created_at');
    const [dirP, setDirP] = useState(filters?.dir_potongan || 'desc');

    const [isModal, setIsModal] = useState(false);
    const [isEdit, setIsEdit] = useState(false);
    const [editId, setEditId] = useState(null);
    const [hapus, setHapus] = useState(null);
    const [proses, setProses] = useState(false);

    const isT = tab === 'tunjangan';

    const { data, setData, post, put, processing, errors, reset, clearErrors, transform } = useForm({
        nama_komponen: '',
        jenis: 'Tetap',
        nominal_default: '',
    });

    // Backend memakai nama kolom berbeda per tabel; form-nya satu.
    transform((f) => ({
        [isT ? 'nama_tunjangan' : 'nama_potongan']: f.nama_komponen,
        jenis: f.jenis,
        nominal_default: f.nominal_default,
    }));

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.penggajian.komponen'), {
                search_tunjangan: searchT, sort_tunjangan: sortT, dir_tunjangan: dirT,
                search_potongan: searchP, sort_potongan: sortP, dir_potongan: dirP,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [searchT, sortT, dirT, searchP, sortP, dirP]);

    const handleSort = (kolom) => {
        if (isT) {
            if (sortT === kolom) setDirT(dirT === 'asc' ? 'desc' : 'asc');
            else { setSortT(kolom); setDirT('asc'); }
        } else {
            if (sortP === kolom) setDirP(dirP === 'asc' ? 'desc' : 'asc');
            else { setSortP(kolom); setDirP('asc'); }
        }
    };

    const SortIcon = ({ field }) => {
        const aktif = isT ? sortT : sortP;
        const arah = isT ? dirT : dirP;

        if (aktif !== field) {
            return <svg className="ml-1 h-3.5 w-3.5 text-stone-400 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>;
        }
        return arah === 'asc'
            ? <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 15l7-7 7 7" /></svg>
            : <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" /></svg>;
    };

    const openAdd = () => { setIsEdit(false); setEditId(null); reset(); clearErrors(); setIsModal(true); };

    const openEdit = (item) => {
        setIsEdit(true); setEditId(item.id); clearErrors();
        setData({
            nama_komponen: isT ? item.nama_tunjangan : item.nama_potongan,
            jenis: item.jenis,
            nominal_default: Math.floor(item.nominal_default).toString(),
        });
        setIsModal(true);
    };

    const submit = (e) => {
        e.preventDefault();
        const prefix = `keuangan.penggajian.komponen.${isT ? 'tunjangan' : 'potongan'}`;
        const opts = { onSuccess: () => { setIsModal(false); reset(); }, preserveScroll: true };

        if (isEdit) put(route(`${prefix}.update`, editId), opts);
        else post(route(`${prefix}.store`), opts);
    };

    const jalankanHapus = () => {
        setProses(true);
        const prefix = `keuangan.penggajian.komponen.${isT ? 'tunjangan' : 'potongan'}`;

        router.delete(route(`${prefix}.destroy`, hapus.id), {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => setHapus(null),
        });
    };

    const thCls = 'cursor-pointer px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';

    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700';

    const ChevronModal = () => (
        <svg className="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const badgeJenis = (jenis) => (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${
            jenis === 'Tetap'
                ? 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20'
                : 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/20'
        }`}>
            {jenis === 'Tetap' ? 'Tetap' : 'Per Kehadiran'}
        </span>
    );

    // Dipanggil sebagai fungsi biasa, BUKAN <Tabel />. Sebagai komponen
    // yang didefinisikan di dalam render, tipenya baru setiap render —
    // React me-mount ulang tabelnya dan input pencarian kehilangan fokus
    // setiap ketukan tombol.
    const renderTabel = () => {
        const dataset = isT ? tunjangan : potongan;
        const kolomNama = isT ? 'nama_tunjangan' : 'nama_potongan';
        const bisaBuat = can(isT ? 'tunjangan.buat' : 'potongan.buat');
        const bisaEdit = can(isT ? 'tunjangan.edit' : 'potongan.edit');
        const bisaHapus = can(isT ? 'tunjangan.hapus' : 'potongan.hapus');
        const adaAksi = bisaEdit || bisaHapus;

        return (
            <>
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative sm:max-w-sm sm:flex-1">
                        <input type="text"
                            value={isT ? searchT : searchP}
                            onChange={(e) => (isT ? setSearchT : setSearchP)(e.target.value)}
                            placeholder={`Cari nama ${isT ? 'tunjangan' : 'potongan'}...`}
                            className={`${fieldCls} pl-9`} />
                        <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    {bisaBuat && (
                        <PrimaryButton onClick={openAdd} className="h-10 shrink-0 justify-center">Tambah {isT ? 'Tunjangan' : 'Potongan'}</PrimaryButton>
                    )}
                </div>

                {/* Mobile: kartu. */}
                <div className="space-y-3 sm:hidden">
                    {dataset?.data?.length > 0 ? dataset.data.map((item) => (
                        <div key={item.id} className="rounded-xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-800/40 dark:ring-coal-600">
                            <div className="flex items-start justify-between gap-2">
                                <p className="break-words text-sm font-semibold text-stone-800 dark:text-cream-100">
                                    {isT ? item.nama_tunjangan : item.nama_potongan}
                                </p>
                                <span className="shrink-0">{badgeJenis(item.jenis)}</span>
                            </div>

                            <div className="mt-3 flex items-baseline justify-between gap-3">
                                <span className="text-[10px] uppercase tracking-wider text-stone-400">
                                    Nominal {item.jenis === 'Tetap' ? 'per bulan' : 'per hari'}
                                </span>
                                <span className={`text-sm font-bold ${isT ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                    {formatCurrency(item.nominal_default)}
                                </span>
                            </div>

                            {adaAksi && (
                                <div className="mt-3 flex gap-2 border-t border-stone-100 pt-3 dark:border-coal-600">
                                    {bisaEdit && (
                                        <button onClick={() => openEdit(item)}
                                            className={`${btnGhost} flex-1 justify-center text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600`}>
                                            Edit
                                        </button>
                                    )}
                                    {bisaHapus && (
                                        <button onClick={() => setHapus(item)}
                                            className={`${btnGhost} flex-1 justify-center text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20`}>
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    )) : (
                        <p className="rounded-xl bg-stone-50 py-10 text-center text-sm text-stone-500 dark:bg-coal-800 dark:text-stone-400">
                            Belum ada data {isT ? 'tunjangan' : 'potongan'}.
                        </p>
                    )}
                </div>

                {/* Desktop: tabel. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort(kolomNama)} className={thCls}><div className="flex items-center">Nama Komponen <SortIcon field={kolomNama} /></div></th>
                                <th onClick={() => handleSort('jenis')} className={thCls}><div className="flex items-center">Jenis <SortIcon field="jenis" /></div></th>
                                <th onClick={() => handleSort('nominal_default')} className={thCls}><div className="flex items-center">Nominal Default <SortIcon field="nominal_default" /></div></th>
                                {adaAksi && <th className="px-6 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {dataset?.data?.length > 0 ? dataset.data.map((item) => (
                                <tr key={item.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="px-6 py-4 text-sm font-medium text-stone-800 dark:text-cream-100">{isT ? item.nama_tunjangan : item.nama_potongan}</td>
                                    <td className="px-6 py-4">{badgeJenis(item.jenis)}</td>
                                    <td className={`px-6 py-4 text-sm font-medium ${isT ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                        {formatCurrency(item.nominal_default)}
                                    </td>
                                    {adaAksi && (
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {bisaEdit && (
                                                    <button onClick={() => openEdit(item)}
                                                        className={`${btnGhost} text-stone-600 ring-stone-200 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                        Edit
                                                    </button>
                                                )}
                                                {bisaHapus && (
                                                    <button onClick={() => setHapus(item)}
                                                        className={`${btnGhost} text-red-600 ring-red-200 hover:bg-red-50 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/10`}>
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksi ? 4 : 3} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada data {isT ? 'tunjangan' : 'potongan'}.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {dataset.from || 0}–{dataset.to || 0} dari {dataset.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {dataset.links.map((l, i) => (
                            <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </>
        );
    };

    return (
        <KeuanganLayout>
            <Head title="Komponen Gaji" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Komponen Gaji</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Komponen berjenis <strong>Per Kehadiran</strong> dikalikan jumlah hari saat penggajian dihitung.
                </p>
            </div>

            <div className="mb-5 flex gap-2">
                {lihatT && (
                    <button onClick={() => setTab('tunjangan')}
                        className={`flex-1 rounded-xl px-5 py-2.5 text-sm font-medium transition sm:flex-none ${
                            isT ? 'bg-claude-500 text-white shadow-lg shadow-claude-500/25'
                                : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600'
                        }`}>Tunjangan</button>
                )}
                {lihatP && (
                    <button onClick={() => setTab('potongan')}
                        className={`flex-1 rounded-xl px-5 py-2.5 text-sm font-medium transition sm:flex-none ${
                            tab === 'potongan' ? 'bg-claude-500 text-white shadow-lg shadow-claude-500/25'
                                : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600'
                        }`}>Potongan</button>
                )}
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                {tab ? renderTabel() : (
                    <p className="py-12 text-center text-sm text-stone-500 dark:text-stone-400">Anda tidak memiliki akses ke komponen gaji.</p>
                )}
            </div>

            <Modal show={isModal} onClose={() => setIsModal(false)} maxWidth="md">
                <form onSubmit={submit} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                        {isEdit ? 'Edit' : 'Tambah'} {isT ? 'Tunjangan' : 'Potongan'}
                    </h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Komponen ini menjadi pilihan saat mengatur gaji pegawai.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Nama Komponen <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={data.nama_komponen} onChange={(e) => setData('nama_komponen', e.target.value)}
                                className="mt-1.5 block w-full" autoFocus
                                placeholder={isT ? 'Contoh: Tunjangan Transport' : 'Contoh: Potongan Ketidakhadiran'} />
                            <InputError message={errors.nama_tunjangan || errors.nama_potongan} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel>Jenis <span className="text-red-500">*</span></InputLabel>
                            <div className="relative">
                                <select value={data.jenis} onChange={(e) => setData('jenis', e.target.value)} className={selectModal}>
                                    <option value="Tetap">Tetap — nominal utuh setiap bulan</option>
                                    <option value="Kehadiran">Per Kehadiran — dikalikan jumlah hari</option>
                                </select>
                                <span className="mt-0.5 block"><ChevronModal /></span>
                            </div>
                            <InputError message={errors.jenis} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel>Nominal Default (Rp) <span className="text-red-500">*</span></InputLabel>
                            <TextInput type="text" inputMode="numeric"
                                value={data.nominal_default ? data.nominal_default.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => setData('nominal_default', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" placeholder="0" />
                            <p className="mt-1 text-xs leading-relaxed text-stone-400">
                                {data.jenis === 'Kehadiran'
                                    ? 'Nominal per hari. Dikalikan jumlah hari hadir (tunjangan) atau tidak masuk (potongan).'
                                    : 'Nominal per bulan. Bisa di-override per pegawai di Data Pegawai.'}
                            </p>
                            <InputError message={errors.nominal_default} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsModal(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing} className="justify-center">Simpan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={hapus !== null} onClose={() => setHapus(null)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <svg className="mx-auto mb-3 h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus Komponen</h3>
                    <p className="mt-1 break-words text-sm text-stone-500 dark:text-stone-400">
                        Hapus <strong>{hapus && (isT ? hapus.nama_tunjangan : hapus.nama_potongan)}</strong> dari daftar komponen?
                    </p>
                    <p className="mt-2 text-xs leading-relaxed text-stone-400">Slip gaji yang sudah dibuat tidak terpengaruh karena nilainya disimpan sebagai salinan.</p>
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <SecondaryButton onClick={() => setHapus(null)} disabled={proses} className="justify-center">Batal</SecondaryButton>
                        <button onClick={jalankanHapus} disabled={proses}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50">Ya, Hapus</button>
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
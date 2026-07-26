import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import { formatCurrency } from '@/Utils/formatCurrency';

const LANGKAH = [
    { id: 'draft',     label: 'Draft',     value: 'Draft' },
    { id: 'diperiksa', label: 'Diperiksa', value: 'Diperiksa' },
    { id: 'final',     label: 'Final',     value: 'Final' },
    { id: 'closed',    label: 'Ditutup',   value: 'Closed' },
];

const waktu = (t) => {
    if (!t) return '';
    const d = new Date(t);
    return `${d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })} · ${d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':')}`;
};

export default function DataRKAS({ headers, details, selectedHeaderId, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.statusFilter || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [expandedIds, setExpandedIds] = useState([]);
    const [konfirmasi, setKonfirmasi] = useState(null);
    const [overBudget, setOverBudget] = useState(false);

    const [isHeaderModal, setIsHeaderModal] = useState(false);
    const [isEditHeader, setIsEditHeader] = useState(false);
    const [editHeaderId, setEditHeaderId] = useState(null);
    const [hapusHeaderId, setHapusHeaderId] = useState(null);

    const [isDetailModal, setIsDetailModal] = useState(false);
    const [isEditDetail, setIsEditDetail] = useState(false);
    const [editDetailId, setEditDetailId] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        tahun_ajaran: '', budget_limit: '',
    });

    const formDetail = useForm({
        parent_id: '', nama_kegiatan: '',
        volume_1: '', satuan_1: '', volume_2: '', satuan_2: '', harga_satuan: '',
    });

    const formGenerate = useForm({});

    const header = headers?.data?.find((h) => h.id == selectedHeaderId);
    const draft = header?.status === 'Draft';
    const langkahAktif = header ? LANGKAH.findIndex((s) => s.value === header.status) : 0;

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.pengeluaran.rkas'),
                { header_id: selectedHeaderId, search, statusFilter, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, statusFilter, sortField, sortDirection]);

    useEffect(() => {
        if (details?.length > 0) {
            setExpandedIds(details.filter((d) => d.is_parent).map((d) => d.id));
        }
    }, [details]);

    // Peringatan hanya sekali per header, bukan tiap kali props berubah.
    const sudahDiperingatkan = useRef(null);
    useEffect(() => {
        if (!header || header.status !== 'Draft') return;
        if (sudahDiperingatkan.current === header.id) return;

        if (parseFloat(header.total_allocated) > parseFloat(header.budget_limit)) {
            sudahDiperingatkan.current = header.id;
            setOverBudget(true);
        }
    }, [header]);

    const pilihHeader = (id) => {
        if (selectedHeaderId == id) return;
        router.get(route('keuangan.pengeluaran.rkas'), { header_id: id }, { preserveState: true, preserveScroll: true });
    };

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

    const openAddHeader = () => {
        setIsEditHeader(false); setEditHeaderId(null);
        setData({ tahun_ajaran: '', budget_limit: '' });
        clearErrors(); setIsHeaderModal(true);
    };

    const openEditHeader = (e, h) => {
        e.stopPropagation();
        setIsEditHeader(true); setEditHeaderId(h.id);
        setData({ tahun_ajaran: h.tahun_ajaran, budget_limit: Math.floor(h.budget_limit).toString() });
        clearErrors(); setIsHeaderModal(true);
    };

    const submitHeader = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => { setIsHeaderModal(false); reset(); }, preserveScroll: true };
        if (isEditHeader) put(route('keuangan.pengeluaran.rkas.update', editHeaderId), opts);
        else post(route('keuangan.pengeluaran.rkas.store'), opts);
    };

    const openAddDetail = (parentId) => {
        setIsEditDetail(false);
        formDetail.setData({ parent_id: parentId, nama_kegiatan: '', volume_1: '', satuan_1: '', volume_2: '', satuan_2: '', harga_satuan: '' });
        formDetail.clearErrors(); setIsDetailModal(true);
    };

    const openEditDetail = (item) => {
        setIsEditDetail(true); setEditDetailId(item.id);
        formDetail.setData({
            parent_id: item.parent_id || '',
            nama_kegiatan: item.nama_kegiatan,
            volume_1: item.volume_1 || '',
            satuan_1: item.satuan_1 || '',
            volume_2: item.volume_2 || '',
            satuan_2: item.satuan_2 || '',
            harga_satuan: Math.floor(item.harga_satuan).toString(),
        });
        formDetail.clearErrors(); setIsDetailModal(true);
    };

    const submitDetail = (e) => {
        e.preventDefault();
        const opts = { onSuccess: () => setIsDetailModal(false), preserveScroll: true };
        if (isEditDetail) formDetail.put(route('keuangan.pengeluaran.rkas.update_kegiatan', editDetailId), opts);
        else formDetail.post(route('keuangan.pengeluaran.rkas.store_kegiatan', selectedHeaderId), opts);
    };

    const subtotal = (formDetail.data.volume_1 || 0) * (formDetail.data.volume_2 || 1) * (formDetail.data.harga_satuan || 0);

    const bolehKe = (stage) => ({
        diperiksa: 'rkas.approve_diperiksa',
        final: 'rkas.approve_final',
        closed: 'rkas.approve_tutup_batch',
    }[stage] ? can({ diperiksa: 'rkas.approve_diperiksa', final: 'rkas.approve_final', closed: 'rkas.approve_tutup_batch' }[stage]) : false);

    const badgeStatus = (s) => ({
        'Draft':     'bg-stone-100 text-stone-600 ring-stone-200 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500',
        'Diperiksa': 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
        'Final':     'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'Closed':    'bg-stone-800 text-white ring-stone-800 dark:bg-coal-900 dark:ring-coal-600',
    }[s] || 'bg-stone-100 text-stone-600 ring-stone-200');

    const level = (kode) => (kode ? kode.split('.').length : 1);

    const terlihat = (item) => {
        if (!item.parent_id) return true;
        if (!expandedIds.includes(item.parent_id)) return false;
        const p = details.find((d) => d.id === item.parent_id);
        return p ? terlihat(p) : true;
    };

    const barisCls = (item) => {
        const l = level(item.kode_kegiatan);
        if (l === 1) return 'bg-claude-500/15 font-semibold';
        if (l === 2) return 'bg-cream-200/70 font-medium dark:bg-coal-600/50';
        if (item.is_parent) return 'bg-stone-50 dark:bg-coal-800/50';
        return 'hover:bg-cream-50 dark:hover:bg-coal-600/30';
    };

    const pelaku = (step) => {
        if (!header) return null;
        const map = {
            draft:     [header.dibuat_oleh, header.created_at],
            diperiksa: [header.diperiksa_oleh, header.diperiksa_at],
            final:     [header.difinal_oleh, header.final_at],
            closed:    [header.ditutup_oleh, header.closed_at],
        }[step];

        if (!map || !map[0]) return null;

        return (
            <p className="mt-1 text-[10px] leading-tight text-stone-400">
                <span className="font-medium text-stone-600 dark:text-stone-300">{map[0].name}</span><br />
                {waktu(map[1])}
            </p>
        );
    };

    const adaAksiHeader = can('rkas.edit_batch') || can('rkas.hapus_batch');
    const adaAksiDetail = can('rkas.buat_rincian') || can('rkas.edit_rincian') || can('rkas.hapus_rincian');
    const thCls = 'cursor-pointer px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';

    return (
        <KeuanganLayout>
            <Head title="RKAS" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-stone-800 dark:text-cream-50">Manajemen RKAS</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Rencana kegiatan dan anggaran sekolah per tahun ajaran.</p>
            </div>

            <div className="mb-5 rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 sm:flex-row">
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari tahun ajaran..."
                            className="w-full rounded-xl border-0 bg-white px-3.5 py-2 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600 sm:max-w-xs" />
                        <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}
                            className="w-full rounded-xl border-0 bg-white px-3.5 py-2 text-sm text-stone-700 ring-1 ring-stone-200 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600 sm:w-auto">
                            <option value="">Semua Status</option>
                            <option value="Draft">Draft</option>
                            <option value="Diperiksa">Diperiksa</option>
                            <option value="Final">Final</option>
                            <option value="Closed">Ditutup</option>
                        </select>
                    </div>
                    {can('rkas.buat_batch') && <PrimaryButton onClick={openAddHeader} className="w-full sm:w-auto">Buat RKAS Baru</PrimaryButton>}
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                        <thead className="bg-stone-50 dark:bg-coal-800">
                            <tr>
                                <th onClick={() => handleSort('tahun_ajaran')} className={thCls}><div className="flex items-center">Tahun Ajaran <SortIcon field="tahun_ajaran" /></div></th>
                                <th className="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Pagu Anggaran</th>
                                <th className="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Teralokasi</th>
                                <th onClick={() => handleSort('status')} className={thCls}><div className="flex items-center">Status <SortIcon field="status" /></div></th>
                                {adaAksiHeader && <th className="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {headers?.data?.length > 0 ? headers.data.map((h) => (
                                <tr key={h.id} onClick={() => pilihHeader(h.id)}
                                    className={`cursor-pointer transition ${selectedHeaderId == h.id ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-stone-800 dark:text-cream-100">{h.tahun_ajaran}</td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{formatCurrency(h.budget_limit)}</td>
                                    <td className={`whitespace-nowrap px-5 py-4 text-sm font-medium ${parseFloat(h.total_allocated) > parseFloat(h.budget_limit) ? 'text-red-600 dark:text-red-400' : 'text-claude-600 dark:text-claude-300'}`}>
                                        {formatCurrency(h.total_allocated)}
                                    </td>
                                    <td className="whitespace-nowrap px-5 py-4">
                                        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${badgeStatus(h.status)}`}>{h.status}</span>
                                    </td>
                                    {adaAksiHeader && (
                                        <td className="whitespace-nowrap px-5 py-4 text-right text-xs">
                                            {h.status === 'Draft' ? (
                                                <>
                                                    {can('rkas.edit_batch') && <button onClick={(e) => openEditHeader(e, h)} className="mr-2 font-medium text-claude-600 hover:underline dark:text-claude-300">Edit</button>}
                                                    {can('rkas.hapus_batch') && <button onClick={(e) => { e.stopPropagation(); setHapusHeaderId(h.id); }} className="font-medium text-red-600 hover:underline dark:text-red-400">Hapus</button>}
                                                </>
                                            ) : <span className="italic text-stone-400">Terkunci</span>}
                                        </td>
                                    )}
                                </tr>
                            )) : (
                                <tr><td colSpan={adaAksiHeader ? 5 : 4} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada data RKAS.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {headers.from || 0}–{headers.to || 0} dari {headers.total} entri</p>
                    <div className="flex flex-wrap gap-1">
                        {headers.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} disabled={!link.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${link.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            </div>

            {header && (
                <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <div className="mb-6 border-b border-stone-100 pb-6 dark:border-coal-600">
                        <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Rincian Anggaran · {header.tahun_ajaran}</h2>
                        <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Kelola kerangka kegiatan dan pagu anggarannya.</p>

                        <div className="mt-6 flex flex-wrap items-start gap-x-2 gap-y-6">
                            {LANGKAH.map((step, i) => {
                                const selesai = i <= langkahAktif;
                                const berikutnya = i === langkahAktif + 1;
                                const bisa = berikutnya && bolehKe(step.id) && !(step.id === 'diperiksa' && !details?.length);

                                return (
                                    <div key={step.id} className="flex items-start">
                                        <div className="flex w-24 flex-col items-center text-center">
                                            <button disabled={!bisa}
                                                onClick={() => setKonfirmasi({
                                                    judul: 'Konfirmasi Status',
                                                    pesan: `Teruskan RKAS ${header.tahun_ajaran} ke tahap "${step.label}"?`,
                                                    bahaya: false,
                                                    aksi: () => router.post(route('keuangan.pengeluaran.rkas.approve', header.id), { stage: step.id }, {
                                                        preserveScroll: true, onSuccess: () => setKonfirmasi(null),
                                                    }),
                                                })}
                                                title={berikutnya ? `Tandai sebagai ${step.label}` : ''}
                                                className={`flex h-9 w-9 items-center justify-center rounded-full ring-2 transition ${
                                                    selesai ? 'bg-claude-500 text-white ring-claude-500'
                                                    : bisa ? 'cursor-pointer bg-white text-claude-600 ring-claude-500 hover:bg-claude-500/10 dark:bg-coal-700'
                                                    : 'cursor-not-allowed bg-stone-50 text-stone-400 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600'
                                                }`}>
                                                {selesai
                                                    ? <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                    : <span className="text-sm font-semibold">{i + 1}</span>}
                                            </button>
                                            <span className={`mt-2 text-xs font-medium ${selesai || berikutnya ? 'text-stone-800 dark:text-cream-100' : 'text-stone-400'}`}>{step.label}</span>
                                            {selesai && pelaku(step.id)}
                                        </div>
                                        {i < LANGKAH.length - 1 && (
                                            <div className={`mt-4 h-0.5 w-6 rounded sm:w-10 ${i < langkahAktif ? 'bg-claude-500' : 'bg-stone-200 dark:bg-coal-600'}`} />
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {details?.length > 0 ? (
                        <div className="overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600">
                            <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                                <thead className="bg-stone-800 text-white dark:bg-coal-900">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Kode & Uraian Kegiatan</th>
                                        <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider">Rincian Perhitungan</th>
                                        <th className="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider">Jumlah</th>
                                        {adaAksiDetail && <th className="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider">Aksi</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                    {details.filter(terlihat).map((item) => (
                                        <tr key={item.id} className={`transition ${barisCls(item)}`}>
                                            <td className="px-4 py-2.5 text-sm text-stone-800 dark:text-cream-100">
                                                <div className="flex items-center gap-2" style={{ paddingLeft: `${(level(item.kode_kegiatan) - 1) * 20}px` }}>
                                                    {item.is_parent ? (
                                                        <button onClick={() => setExpandedIds((p) => p.includes(item.id) ? p.filter((x) => x !== item.id) : [...p, item.id])}
                                                            className="flex h-5 w-5 items-center justify-center rounded bg-black/10 text-[10px] transition hover:bg-black/20 dark:bg-white/10 dark:hover:bg-white/20">
                                                            {expandedIds.includes(item.id) ? '▼' : '▶'}
                                                        </button>
                                                    ) : <span className="w-5 text-center text-stone-300">·</span>}
                                                    <span className="w-14 shrink-0 text-stone-500 dark:text-stone-400">{item.kode_kegiatan}</span>
                                                    <span>{item.nama_kegiatan}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-xs text-stone-500 dark:text-stone-400">
                                                {!item.is_parent && item.volume_1 && (
                                                    <span className="inline-flex flex-wrap items-center gap-1">
                                                        <span className="rounded bg-white px-1.5 py-0.5 ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600">{item.volume_1} {item.satuan_1}</span>
                                                        {item.volume_2 && <>×<span className="rounded bg-white px-1.5 py-0.5 ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600">{item.volume_2} {item.satuan_2}</span></>}
                                                        ×<span className="font-medium text-stone-700 dark:text-stone-200">{formatCurrency(item.harga_satuan)}</span>
                                                    </span>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-2.5 text-right text-sm text-stone-800 dark:text-cream-100">{formatCurrency(item.pagu_anggaran)}</td>
                                            {adaAksiDetail && (
                                                <td className="whitespace-nowrap px-4 py-2.5 text-center text-xs">
                                                    {draft ? (item.is_parent ? (
                                                        can('rkas.buat_rincian') && (
                                                            <button onClick={() => openAddDetail(item.id)} className="rounded-lg bg-claude-500 px-2.5 py-1 font-medium text-white transition hover:bg-claude-600">+ Kegiatan</button>
                                                        )
                                                    ) : (
                                                        <>
                                                            {can('rkas.edit_rincian') && <button onClick={() => openEditDetail(item)} className="mr-2 font-medium text-claude-600 hover:underline dark:text-claude-300">Edit</button>}
                                                            {can('rkas.hapus_rincian') && (
                                                                <button onClick={() => setKonfirmasi({
                                                                    judul: 'Hapus Kegiatan', bahaya: true,
                                                                    pesan: `Hapus kegiatan "${item.nama_kegiatan}"?`,
                                                                    aksi: () => router.delete(route('keuangan.pengeluaran.rkas.destroy_kegiatan', item.id), {
                                                                        preserveScroll: true, onSuccess: () => setKonfirmasi(null),
                                                                    }),
                                                                })} className="font-medium text-red-600 hover:underline dark:text-red-400">Hapus</button>
                                                            )}
                                                        </>
                                                    )) : <span className="text-stone-400">–</span>}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="bg-stone-800 text-white dark:bg-coal-900">
                                    <tr>
                                        <td colSpan="2" className="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider">Total Teralokasi</td>
                                        <td className={`px-4 py-3.5 text-right text-base font-bold ${parseFloat(header.total_allocated) > parseFloat(header.budget_limit) ? 'text-red-400' : 'text-emerald-400'}`}>
                                            {formatCurrency(header.total_allocated)}
                                        </td>
                                        {adaAksiDetail && <td />}
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    ) : (
                        <div className="rounded-xl bg-amber-50 p-8 text-center ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20">
                            <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Kerangka RKAS belum dibuat</h3>
                            {can('rkas.buat_rincian') && draft ? (
                                <>
                                    <p className="mx-auto mt-2 max-w-md text-sm text-stone-600 dark:text-stone-300">
                                        Salin kerangka standar dari Master Template RKAS ke tahun ajaran ini.
                                    </p>
                                    <PrimaryButton onClick={() => formGenerate.post(route('keuangan.pengeluaran.rkas.generate_struktur', selectedHeaderId), { preserveScroll: true })}
                                        disabled={formGenerate.processing} className="mt-5">
                                        {formGenerate.processing ? 'Memproses…' : 'Buat Kerangka Standar'}
                                    </PrimaryButton>
                                    {formGenerate.errors.generate && <p className="mt-3 text-sm text-red-600">{formGenerate.errors.generate}</p>}
                                </>
                            ) : (
                                <p className="mt-2 text-sm text-stone-600 dark:text-stone-300">Belum ada rincian anggaran pada RKAS ini.</p>
                            )}
                        </div>
                    )}
                </div>
            )}

            <Modal show={isHeaderModal} onClose={() => setIsHeaderModal(false)} maxWidth="md">
                <form onSubmit={submitHeader} className="p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditHeader ? 'Edit RKAS' : 'Buat RKAS Baru'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Tahun Ajaran <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={data.tahun_ajaran} onChange={(e) => setData('tahun_ajaran', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: 2026/2027" />
                            <InputError message={errors.tahun_ajaran} className="mt-1.5" />
                        </div>
                        <div>
                            <InputLabel>Pagu Anggaran Maksimal (Rp) <span className="text-red-500">*</span></InputLabel>
                            <TextInput type="text"
                                value={data.budget_limit ? data.budget_limit.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                onChange={(e) => setData('budget_limit', e.target.value.replace(/\D/g, ''))}
                                className="mt-1.5 block w-full" placeholder="0" />
                            <InputError message={errors.budget_limit} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsHeaderModal(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>Simpan RKAS</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={isDetailModal} onClose={() => setIsDetailModal(false)} maxWidth="2xl">
                <form onSubmit={submitDetail} className="p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">{isEditDetail ? 'Edit Rincian Kegiatan' : 'Tambah Kegiatan'}</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Nama / Uraian Kegiatan <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={formDetail.data.nama_kegiatan} onChange={(e) => formDetail.setData('nama_kegiatan', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Contoh: Pembelian Buku Mutaba'ah" />
                            <InputError message={formDetail.errors.nama_kegiatan} className="mt-1.5" />
                        </div>

                        <div className="grid grid-cols-2 gap-3 rounded-xl bg-stone-50 p-4 ring-1 ring-stone-200 dark:bg-coal-900/50 dark:ring-coal-600 lg:grid-cols-4">
                            <div>
                                <InputLabel>Volume 1 <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="number" min="1" value={formDetail.data.volume_1} onChange={(e) => formDetail.setData('volume_1', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={formDetail.errors.volume_1} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel>Satuan 1 <span className="text-red-500">*</span></InputLabel>
                                <TextInput value={formDetail.data.satuan_1} onChange={(e) => formDetail.setData('satuan_1', e.target.value)} className="mt-1.5 block w-full" placeholder="Keg/Thn" />
                                <InputError message={formDetail.errors.satuan_1} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Volume 2" />
                                <TextInput type="number" min="1" value={formDetail.data.volume_2} onChange={(e) => formDetail.setData('volume_2', e.target.value)} className="mt-1.5 block w-full" />
                                <InputError message={formDetail.errors.volume_2} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Satuan 2" />
                                <TextInput value={formDetail.data.satuan_2} onChange={(e) => formDetail.setData('satuan_2', e.target.value)} className="mt-1.5 block w-full" placeholder="Guru/Siswa" />
                                <InputError message={formDetail.errors.satuan_2} className="mt-1" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 items-end gap-4 lg:grid-cols-2">
                            <div>
                                <InputLabel>Harga Satuan (Rp) <span className="text-red-500">*</span></InputLabel>
                                <TextInput type="text"
                                    value={formDetail.data.harga_satuan ? formDetail.data.harga_satuan.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''}
                                    onChange={(e) => formDetail.setData('harga_satuan', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1.5 block w-full" placeholder="0" />
                                <InputError message={formDetail.errors.harga_satuan} className="mt-1.5" />
                            </div>
                            <div className="rounded-xl bg-claude-500/10 p-4">
                                <span className="block text-[11px] font-semibold uppercase tracking-wider text-claude-700 dark:text-claude-300">Estimasi Subtotal</span>
                                <span className="mt-1 block text-lg font-bold text-claude-700 dark:text-claude-200">{formatCurrency(subtotal)}</span>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setIsDetailModal(false)}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formDetail.processing}>Simpan Kegiatan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={hapusHeaderId !== null} onClose={() => setHapusHeaderId(null)} maxWidth="sm">
                <div className="p-6 text-center">
                    <svg className="mx-auto mb-3 h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Hapus RKAS</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Seluruh rincian anggaran di dalamnya ikut terhapus.</p>
                    <div className="mt-5 flex justify-center gap-2">
                        <SecondaryButton onClick={() => setHapusHeaderId(null)}>Batal</SecondaryButton>
                        <button onClick={() => router.delete(route('keuangan.pengeluaran.rkas.destroy', hapusHeaderId), { preserveScroll: true, onSuccess: () => setHapusHeaderId(null) })}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">Ya, Hapus</button>
                    </div>
                </div>
            </Modal>

            <Modal show={overBudget} onClose={() => setOverBudget(false)} maxWidth="sm">
                <div className="p-6 text-center">
                    <svg className="mx-auto mb-3 h-12 w-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Melebihi Pagu Anggaran</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Total alokasi sudah melampaui pagu yang ditetapkan. Periksa kembali rincian kegiatan.</p>
                    <div className="mt-5 flex justify-center">
                        <PrimaryButton onClick={() => setOverBudget(false)}>Mengerti</PrimaryButton>
                    </div>
                </div>
            </Modal>

            <Modal show={konfirmasi !== null} onClose={() => setKonfirmasi(null)} maxWidth="sm">
                <div className="p-6 text-center">
                    <div className={`mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full ${konfirmasi?.bahaya ? 'bg-red-50 text-red-600 dark:bg-red-500/10' : 'bg-claude-500/10 text-claude-600 dark:text-claude-300'}`}>
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d={konfirmasi?.bahaya
                                ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                                : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'} />
                        </svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">{konfirmasi?.judul}</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">{konfirmasi?.pesan}</p>
                    <div className="mt-5 flex justify-center gap-2">
                        <SecondaryButton onClick={() => setKonfirmasi(null)}>Batal</SecondaryButton>
                        {konfirmasi?.bahaya ? (
                            <button onClick={konfirmasi.aksi} className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">Ya, Lanjutkan</button>
                        ) : (
                            <PrimaryButton onClick={konfirmasi?.aksi}>Ya, Lanjutkan</PrimaryButton>
                        )}
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
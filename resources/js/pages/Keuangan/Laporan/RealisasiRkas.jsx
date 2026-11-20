import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import TombolKembali from '@/Components/TombolKembali';
import { formatCurrency } from '@/Utils/formatCurrency';

export default function RealisasiRkas({ listPeriode, realisasi, summary, filters }) {
    const [periodeId, setPeriodeId] = useState(filters?.periode_id || '');
    const [search, setSearch] = useState(filters?.search || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'kode_kegiatan');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.laporan.realisasi_rkas'),
                { periode_id: periodeId, search, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [periodeId, search, sortField, sortDirection]);

    const handleSort = (f) => {
        if (sortField === f) setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        else { setSortField(f); setSortDirection('asc'); }
    };

    const SortIcon = ({ field }) => {
        if (sortField !== field) {
            return <svg className="ml-1 h-3.5 w-3.5 text-stone-400 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>;
        }
        return sortDirection === 'asc'
            ? <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 15l7-7 7 7" /></svg>
            : <svg className="ml-1 h-3.5 w-3.5 text-claude-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" /></svg>;
    };

    const warnaSerapan = (p) =>
        p >= 100 ? 'bg-red-500' : p >= 80 ? 'bg-amber-500' : p > 0 ? 'bg-emerald-500' : 'bg-cream-400 dark:bg-coal-600';

    const thCls = 'cursor-pointer px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600';
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9 font-medium dark:[color-scheme:dark]`;

    const ChevronBawah = () => (
        <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    return (
        <KeuanganLayout>
            <Head title="Realisasi RKAS" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Realisasi Anggaran RKAS</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Penyerapan anggaran per mata kegiatan pada tahun ajaran terpilih.</p>
            </div>

            {(!listPeriode || listPeriode.length === 0) ? (
                <div className="rounded-2xl bg-white p-8 text-center ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-12">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Belum ada RKAS berstatus Final. Finalkan RKAS terlebih dahulu untuk melihat realisasinya.</p>
                </div>
            ) : (
                <>
                    {/* 2 kolom sejak layar terkecil: 4 kartu bertumpuk vertikal
                        memakan hampir satu layar penuh sebelum tabel terlihat. */}
                    <div className="mb-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                        {[
                            ['Total Anggaran', formatCurrency(summary?.total_anggaran || 0), 'text-stone-700 dark:text-stone-200'],
                            ['Terserap', formatCurrency(summary?.total_realisasi || 0), 'text-red-600 dark:text-red-400'],
                            ['Sisa Anggaran', formatCurrency(summary?.sisa_anggaran || 0), 'text-emerald-600 dark:text-emerald-400'],
                            ['Persentase Serapan', `${summary?.persentase_serapan || 0}%`, 'text-claude-600 dark:text-claude-300'],
                        ].map(([label, nilai, warna]) => (
                            <div key={label} className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                                <p className="truncate text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">{label}</p>
                                <p className={`mt-1.5 break-words text-base font-bold leading-tight sm:mt-2 sm:text-lg ${warna}`}>{nilai}</p>
                            </div>
                        ))}
                    </div>

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                        <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div className="relative sm:w-56">
                                <select value={periodeId} onChange={(e) => setPeriodeId(e.target.value)} className={selectCls}>
                                    {listPeriode.map((p) => (
                                        <option key={p.id} value={p.id}>{p.tahun_ajaran} · {p.status}</option>
                                    ))}
                                </select>
                                <ChevronBawah />
                            </div>

                            <div className="relative sm:max-w-sm sm:flex-1">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kode atau nama kegiatan..." className={`${fieldCls} pl-9`} />
                                <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                    fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                                </svg>
                            </div>
                        </div>

                        <div className="mb-5">
                            <div className="mb-1.5 flex items-center justify-between text-xs">
                                <span className="font-medium text-stone-600 dark:text-stone-300">Serapan keseluruhan</span>
                                <span className="font-semibold text-stone-800 dark:text-cream-100">{summary?.persentase_serapan || 0}%</span>
                            </div>
                            <div className="h-2.5 overflow-hidden rounded-full bg-cream-200 dark:bg-coal-600/40">
                                <div className={`h-full rounded-full transition-all ${warnaSerapan(summary?.persentase_serapan || 0)}`}
                                    style={{ width: `${Math.min(summary?.persentase_serapan || 0, 100)}%` }} />
                            </div>
                        </div>

                        {/* Mobile: kartu per mata kegiatan. */}
                        <div className="space-y-3 sm:hidden">
                            {realisasi?.data?.length > 0 ? realisasi.data.map((r) => {
                                const pagu = parseFloat(r.pagu_anggaran) || 0;
                                const real = parseFloat(r.nominal_realisasi) || 0;
                                const persen = pagu > 0 ? Math.round(real / pagu * 100) : 0;
                                const sisa = pagu - real;

                                return (
                                    <div key={r.id} className="rounded-xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                        <p className="text-[10px] font-medium text-stone-500 dark:text-stone-400">{r.kode_kegiatan}</p>
                                        <p className="mt-0.5 break-words text-sm font-semibold text-stone-800 dark:text-cream-100">{r.nama_kegiatan}</p>

                                        <dl className="mt-3 space-y-1.5 text-xs">
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-stone-400">Pagu</dt>
                                                <dd className="text-right font-medium text-stone-700 dark:text-stone-200">{formatCurrency(pagu)}</dd>
                                            </div>
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-stone-400">Realisasi</dt>
                                                <dd className="text-right font-medium text-red-600 dark:text-red-400">{formatCurrency(real)}</dd>
                                            </div>
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-stone-400">Sisa</dt>
                                                <dd className={`text-right font-medium ${sisa < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'}`}>
                                                    {formatCurrency(sisa)}
                                                </dd>
                                            </div>
                                        </dl>

                                        <div className="mt-3 flex items-center gap-2 border-t border-cream-200 pt-3 dark:border-coal-600">
                                            <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-cream-200 dark:bg-coal-600/40">
                                                <div className={`h-full rounded-full ${warnaSerapan(persen)}`} style={{ width: `${Math.min(persen, 100)}%` }} />
                                            </div>
                                            <span className="shrink-0 text-xs font-semibold text-stone-600 dark:text-stone-300">{persen}%</span>
                                        </div>
                                    </div>
                                );
                            }) : (
                                <p className="rounded-xl bg-cream-100 py-10 text-center text-sm text-stone-500 dark:bg-coal-600/40 dark:text-stone-400">
                                    Tidak ada mata kegiatan pada periode ini.
                                </p>
                            )}

                            <div className="rounded-xl bg-coal-800 p-4 dark:bg-coal-900">
                                <p className="mb-2 text-[10px] font-semibold uppercase tracking-wider text-white/70">Total</p>
                                <div className="space-y-1.5 text-xs">
                                    <div className="flex justify-between gap-3">
                                        <span className="text-white/70">Anggaran</span>
                                        <span className="font-bold text-white">{formatCurrency(summary?.total_anggaran || 0)}</span>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <span className="text-white/70">Realisasi</span>
                                        <span className="font-bold text-red-400">{formatCurrency(summary?.total_realisasi || 0)}</span>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <span className="text-white/70">Sisa</span>
                                        <span className="font-bold text-emerald-400">{formatCurrency(summary?.sisa_anggaran || 0)}</span>
                                    </div>
                                    <div className="flex justify-between gap-3 border-t border-white/15 pt-1.5">
                                        <span className="text-white/70">Serapan</span>
                                        <span className="text-sm font-bold text-white">{summary?.persentase_serapan || 0}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Desktop: tabel penuh dengan sorting. */}
                        <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 sm:block">
                            <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                                <thead className="bg-cream-100 dark:bg-coal-600/40">
                                    <tr>
                                        <th onClick={() => handleSort('kode_kegiatan')} className={thCls}><div className="flex items-center">Kode <SortIcon field="kode_kegiatan" /></div></th>
                                        <th onClick={() => handleSort('nama_kegiatan')} className={thCls}><div className="flex items-center">Uraian Kegiatan <SortIcon field="nama_kegiatan" /></div></th>
                                        <th onClick={() => handleSort('pagu_anggaran')} className={`${thCls} text-right`}><div className="flex items-center justify-end">Pagu <SortIcon field="pagu_anggaran" /></div></th>
                                        <th onClick={() => handleSort('nominal_realisasi')} className={`${thCls} text-right`}><div className="flex items-center justify-end">Realisasi <SortIcon field="nominal_realisasi" /></div></th>
                                        <th className={`${thPlain} text-right`}>Sisa</th>
                                        <th className={thPlain}>Serapan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                    {realisasi?.data?.length > 0 ? realisasi.data.map((r) => {
                                        const pagu = parseFloat(r.pagu_anggaran) || 0;
                                        const real = parseFloat(r.nominal_realisasi) || 0;
                                        const persen = pagu > 0 ? Math.round(real / pagu * 100) : 0;

                                        return (
                                            <tr key={r.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                                <td className="whitespace-nowrap px-4 py-3.5 text-sm text-stone-500 dark:text-stone-400">{r.kode_kegiatan}</td>
                                                <td className="px-4 py-3.5 text-sm text-stone-800 dark:text-cream-100">{r.nama_kegiatan}</td>
                                                <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm text-stone-600 dark:text-stone-300">{formatCurrency(pagu)}</td>
                                                <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-medium text-red-600 dark:text-red-400">{formatCurrency(real)}</td>
                                                <td className={`whitespace-nowrap px-4 py-3.5 text-right text-sm font-medium ${pagu - real < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'}`}>
                                                    {formatCurrency(pagu - real)}
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <div className="flex items-center gap-2">
                                                        <div className="h-1.5 w-16 shrink-0 overflow-hidden rounded-full bg-cream-200 dark:bg-coal-600/40">
                                                            <div className={`h-full rounded-full ${warnaSerapan(persen)}`} style={{ width: `${Math.min(persen, 100)}%` }} />
                                                        </div>
                                                        <span className="text-xs font-medium text-stone-600 dark:text-stone-300">{persen}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    }) : (
                                        <tr><td colSpan="6" className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada mata kegiatan pada periode ini.</td></tr>
                                    )}
                                </tbody>
                                <tfoot className="bg-coal-800 text-white dark:bg-coal-900">
                                    <tr>
                                        <td colSpan="2" className="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider">Total</td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-bold">{formatCurrency(summary?.total_anggaran || 0)}</td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-bold text-red-400">{formatCurrency(summary?.total_realisasi || 0)}</td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-bold text-emerald-400">{formatCurrency(summary?.sisa_anggaran || 0)}</td>
                                        <td className="px-4 py-3.5 text-sm font-bold">{summary?.persentase_serapan || 0}%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {realisasi?.data?.length > 0 && (
                            <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                                <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {realisasi.from || 0}–{realisasi.to || 0} dari {realisasi.total} kegiatan</p>
                                <div className="flex flex-wrap justify-center gap-1">
                                    {realisasi.links.map((l, i) => (
                                        <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                            className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                            dangerouslySetInnerHTML={{ __html: l.label }} />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </>
            )}
        </KeuanganLayout>
    );
}
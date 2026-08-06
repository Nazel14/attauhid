import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import SearchableSelect from '@/Components/SearchableSelect';
import TombolKembali from '@/Components/TombolKembali';
import { formatCurrency } from '@/Utils/formatCurrency';

const tanggal = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-');

export default function BukuKasUmum({ listRekening, mutasi, saldoAwal, ringkas, filters }) {
    const [accountId, setAccountId] = useState(filters?.account_id || '');
    const [dari, setDari] = useState(filters?.from_date || '');
    const [sampai, setSampai] = useState(filters?.to_date || '');

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.laporan.buku_kas_umum'),
                { account_id: accountId, from_date: dari, to_date: sampai },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [accountId, dari, sampai]);

    const rekening = listRekening?.find((r) => r.id == accountId);

    // Saldo berjalan dihitung ulang per halaman, bertumpu pada
    // saldo_setelah_transaksi yang sudah tersimpan di tiap baris.
    const saldoAkhir = mutasi?.data?.length > 0
        ? parseFloat(mutasi.data[mutasi.data.length - 1].saldo_setelah_transaksi)
        : saldoAwal;

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    // color-scheme ikut tema agar ikon kalender bawaan browser tidak silau di dark mode.
    const dateCls = `${fieldCls} pr-2 [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-60 hover:[&::-webkit-calendar-picker-indicator]:opacity-100`;

    const thCls = 'px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const labelCls = 'mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    const IkonPrinter = () => (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-12h6a1 1 0 011 1v3H8V6a1 1 0 011-1zm-1 9h8a1 1 0 011 1v4a1 1 0 01-1 1H8a1 1 0 01-1-1v-4a1 1 0 011-1z" />
        </svg>
    );

    return (
        <KeuanganLayout>
            <Head title="Buku Kas Umum" />

            <TombolKembali />

            <div className="mb-5 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Buku Kas Umum</h1>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Mutasi masuk dan keluar per rekening dalam rentang tanggal.</p>
                </div>
                {accountId && mutasi && (
                    <a href={route('keuangan.laporan.buku_kas_umum.cetak', { account_id: accountId, from_date: dari, to_date: sampai })}
                        target="_blank" rel="noreferrer"
                        className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 text-sm font-medium text-stone-700 ring-1 ring-stone-200 transition hover:bg-stone-50 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:hover:bg-coal-600">
                        <IkonPrinter />
                        Cetak PDF
                    </a>
                )}
            </div>

            <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Rekening</label>
                        <SearchableSelect placeholder="-- Pilih rekening --"
                            options={listRekening?.map((r) => ({ id: r.id, label: `${r.account_name} (${r.type}) · ${formatCurrency(r.balance)}` })) || []}
                            value={accountId} onChange={setAccountId} />
                    </div>
                    {/* Dua tanggal berdampingan sejak layar terkecil — keduanya
                        pendek, memaksa full-width membuang satu baris. */}
                    <div className="grid grid-cols-2 gap-3 sm:col-span-2 sm:grid-cols-2 lg:contents">
                        <div>
                            <label className={labelCls}>Dari Tanggal</label>
                            <input type="date" value={dari} onChange={(e) => setDari(e.target.value)} className={dateCls} />
                        </div>
                        <div>
                            <label className={labelCls}>Sampai Tanggal</label>
                            <input type="date" value={sampai} onChange={(e) => setSampai(e.target.value)} className={dateCls} />
                        </div>
                    </div>
                </div>
            </div>

            {!accountId ? (
                <div className="rounded-2xl bg-white p-8 text-center ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-12">
                    <svg className="mx-auto mb-3 h-12 w-12 text-stone-300 dark:text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p className="text-sm text-stone-500 dark:text-stone-400">Pilih rekening untuk menampilkan buku kas.</p>
                </div>
            ) : (
                <>
                    {/* 2 kolom sejak layar terkecil: 4 kartu bertumpuk vertikal
                        memakan hampir satu layar penuh sebelum tabel terlihat. */}
                    <div className="mb-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                        {[
                            ['Saldo Awal', saldoAwal, 'text-stone-700 dark:text-stone-200'],
                            ['Total Masuk', ringkas?.debit, 'text-emerald-600 dark:text-emerald-400'],
                            ['Total Keluar', ringkas?.kredit, 'text-red-600 dark:text-red-400'],
                            ['Saldo Akhir', saldoAkhir, 'text-claude-600 dark:text-claude-300'],
                        ].map(([label, nilai, warna]) => (
                            <div key={label} className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                                <p className="truncate text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">{label}</p>
                                {/* break-words: "Rp 15.500.000" meluber kalau
                                    dipaksa text-lg di layar 360px dua kolom. */}
                                <p className={`mt-1.5 break-words text-base font-bold leading-tight sm:mt-2 sm:text-lg ${warna}`}>{formatCurrency(nilai || 0)}</p>
                            </div>
                        ))}
                    </div>

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                        <div className="mb-4">
                            <h2 className="break-words text-base font-semibold text-stone-800 dark:text-cream-50">{rekening?.account_name}</h2>
                            <p className="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{tanggal(dari)} — {tanggal(sampai)}</p>
                        </div>

                        {/* Mobile: daftar mutasi sebagai kartu. Tabel 5 kolom
                            dengan tiga kolom nominal tidak terbaca di 360px. */}
                        <div className="space-y-2.5 sm:hidden">
                            <div className="flex items-center justify-between gap-3 rounded-xl bg-cream-50 px-4 py-3 dark:bg-coal-900/40">
                                <span className="text-xs font-semibold text-stone-700 dark:text-stone-200">Saldo awal periode</span>
                                <span className="shrink-0 text-sm font-bold text-stone-800 dark:text-cream-100">{formatCurrency(saldoAwal)}</span>
                            </div>

                            {mutasi?.data?.length > 0 ? mutasi.data.map((m) => {
                                const debit = m.jenis === 'Debit';

                                return (
                                    <div key={m.id} className="rounded-xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-800/40 dark:ring-coal-600">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="break-words text-sm text-stone-800 dark:text-cream-100">{m.keterangan}</p>
                                                <p className="mt-0.5 text-xs text-stone-400">
                                                    {tanggal(m.tanggal_transaksi)}
                                                    {m.pembuat && ` · oleh ${m.pembuat.name}`}
                                                </p>
                                            </div>
                                            <span className={`shrink-0 text-sm font-bold ${debit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                                {debit ? '+' : '−'} {formatCurrency(m.nominal)}
                                            </span>
                                        </div>
                                        <div className="mt-2 flex items-center justify-between gap-3 border-t border-stone-100 pt-2 dark:border-coal-600">
                                            <span className="text-[10px] uppercase tracking-wider text-stone-400">Saldo</span>
                                            <span className="text-xs font-medium text-stone-700 dark:text-stone-200">{formatCurrency(m.saldo_setelah_transaksi)}</span>
                                        </div>
                                    </div>
                                );
                            }) : (
                                <p className="rounded-xl bg-stone-50 py-10 text-center text-sm text-stone-500 dark:bg-coal-800 dark:text-stone-400">
                                    Tidak ada mutasi pada rentang tanggal ini.
                                </p>
                            )}

                            <div className="rounded-xl bg-stone-800 p-4 dark:bg-coal-900">
                                <p className="mb-2 text-[10px] font-semibold uppercase tracking-wider text-white/70">Total periode</p>
                                <div className="space-y-1.5 text-xs">
                                    <div className="flex justify-between gap-3">
                                        <span className="text-white/70">Debit</span>
                                        <span className="font-bold text-emerald-400">{formatCurrency(ringkas?.debit || 0)}</span>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <span className="text-white/70">Kredit</span>
                                        <span className="font-bold text-red-400">{formatCurrency(ringkas?.kredit || 0)}</span>
                                    </div>
                                    <div className="flex justify-between gap-3 border-t border-white/15 pt-1.5">
                                        <span className="text-white/70">Saldo akhir</span>
                                        <span className="text-sm font-bold text-white">{formatCurrency(saldoAkhir)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Desktop: tabel buku kas. */}
                        <div className="hidden overflow-x-auto rounded-xl ring-1 ring-stone-200 dark:ring-coal-600 sm:block">
                            <table className="min-w-full divide-y divide-stone-200 dark:divide-coal-600">
                                <thead className="bg-stone-50 dark:bg-coal-800">
                                    <tr>
                                        <th className={thCls}>Tanggal</th>
                                        <th className={thCls}>Keterangan</th>
                                        <th className={`${thCls} text-right`}>Debit</th>
                                        <th className={`${thCls} text-right`}>Kredit</th>
                                        <th className={`${thCls} text-right`}>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                    <tr className="bg-cream-50 dark:bg-coal-900/40">
                                        <td colSpan="4" className="px-4 py-3 text-sm font-semibold text-stone-700 dark:text-stone-200">Saldo awal periode</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-stone-800 dark:text-cream-100">{formatCurrency(saldoAwal)}</td>
                                    </tr>

                                    {mutasi?.data?.length > 0 ? mutasi.data.map((m) => (
                                        <tr key={m.id} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-stone-600 dark:text-stone-300">{tanggal(m.tanggal_transaksi)}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className="text-stone-800 dark:text-cream-100">{m.keterangan}</span>
                                                {m.pembuat && <><br /><span className="text-xs text-stone-400">oleh {m.pembuat.name}</span></>}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                {m.jenis === 'Debit' ? formatCurrency(m.nominal) : <span className="text-stone-300 dark:text-stone-600">—</span>}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                                {m.jenis === 'Kredit' ? formatCurrency(m.nominal) : <span className="text-stone-300 dark:text-stone-600">—</span>}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-stone-700 dark:text-stone-200">{formatCurrency(m.saldo_setelah_transaksi)}</td>
                                        </tr>
                                    )) : (
                                        <tr><td colSpan="5" className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada mutasi pada rentang tanggal ini.</td></tr>
                                    )}
                                </tbody>
                                <tfoot className="bg-stone-800 text-white dark:bg-coal-900">
                                    <tr>
                                        <td colSpan="2" className="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider">Total periode</td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-bold text-emerald-400">{formatCurrency(ringkas?.debit || 0)}</td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-bold text-red-400">{formatCurrency(ringkas?.kredit || 0)}</td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-sm font-bold">{formatCurrency(saldoAkhir)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {mutasi?.data?.length > 0 && (
                            <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                                <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {mutasi.from || 0}–{mutasi.to || 0} dari {mutasi.total} transaksi</p>
                                <div className="flex flex-wrap justify-center gap-1">
                                    {mutasi.links.map((l, i) => (
                                        <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                            className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
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
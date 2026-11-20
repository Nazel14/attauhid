import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import SearchableSelect from '@/Components/SearchableSelect';
import TombolKembali from '@/Components/TombolKembali';
import { formatCurrency } from '@/Utils/formatCurrency';

const METODE = ['WhatsApp', 'Telepon', 'Surat Peringatan', 'Lainnya'];

const tanggal = (s) => (s ? new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-');

export default function LaporanTunggakan({ tunggakan, listSiswa, ringkas, filters, rekeningBayar = [] }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);

    const [search, setSearch] = useState(filters?.search || '');
    const [studentId, setStudentId] = useState(filters?.student_id || '');
    const [sortField, setSortField] = useState(filters?.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'desc');

    const [terpilih, setTerpilih] = useState(null);
    const [isTagih, setIsTagih] = useState(false);

    const aktif = tunggakan?.data?.find((t) => t.id === terpilih);

    // Anchor auto-scroll saat tombol "Riwayat" ditekan.
    const riwayatRef = useRef(null);

    const formTagih = useForm({ student_id: '', invoice_id: '', metode: 'WhatsApp', keterangan: '' });

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.laporan.tunggakan'),
                { search, student_id: studentId, sort_field: sortField, sort_direction: sortDirection },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, studentId, sortField, sortDirection]);

    // Dipanggil dari tombol "Riwayat" di kolom Aksi, bukan dari klik baris.
    const bukaRiwayat = (id) => {
        const tutup = terpilih === id;
        setTerpilih(tutup ? null : id);
        if (!tutup) {
            setTimeout(() => riwayatRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 60);
        }
    };

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

    const bukaTagih = (item) => {
        setTerpilih(item.id);
        formTagih.clearErrors();
        formTagih.setData({
            student_id: item.student_id,
            invoice_id: item.id,
            metode: 'WhatsApp',
            keterangan: `Penagihan ${item.header?.title || 'tagihan'} · sisa ${formatCurrency(item.remaining_balance)}`,
        });
        setIsTagih(true);
    };

    /** Normalisasi ke format internasional: 0812… → 62812…, +62 → 62. */
    const nomorWa = (raw) => {
        const angka = (raw || '').replace(/\D/g, '');
        if (!angka) return '';
        if (angka.startsWith('62')) return angka;
        if (angka.startsWith('0')) return '62' + angka.slice(1);
        return '62' + angka;
    };

    /** Sudah ditagih hari ini? Dipakai untuk rem, bukan larangan. */
    const ditagihHariIni = (item) => {
        const log = item.riwayat_penagihans?.[item.riwayat_penagihans.length - 1];
        if (!log) return false;
        return new Date(log.tanggal_penagihan).toDateString() === new Date().toDateString();
    };

    const susunPesan = (item) => {
        const baris = [
            `Assalamu'alaikum Bapak/Ibu ${item.siswa?.parent_name || ''},`,
            '',
            `Kami informasikan tagihan ananda *${item.siswa?.name}*:`,
            '',
            `${item.header?.title || 'Tagihan'}`,
            `Jumlah  : *${formatCurrency(item.remaining_balance)}*`,
        ];

        if (item.header?.due_date) {
            baris.push(`Tempo   : ${tanggal(item.header.due_date)}`);
        }

        baris.push(`No. Inv : ${item.invoice_number}`);

        if (rekeningBayar.length) {
            baris.push('', 'Pembayaran dapat dilakukan melalui:');
            rekeningBayar.forEach((r) => {
                baris.push(`${r.nama}: ${r.nomor}${r.pemilik ? ` a.n. ${r.pemilik}` : ''}`);
            });
            baris.push('', 'Mohon kirim bukti transfer setelah pembayaran.');
        } else {
            baris.push('', 'Mohon dapat diselesaikan.');
        }

        baris.push('', 'Jazaakumullahu khairan.');

        return baris.join('\n');
    };

    const kirimWa = (item) => {
        const nomor = nomorWa(item.siswa?.parent_contact);
        if (!nomor) return;

        if (ditagihHariIni(item) &&
            !confirm(`${item.siswa?.name} sudah ditagih hari ini. Kirim lagi?`)) {
            return;
        }

        // window.open dipanggil lebih dulu dan langsung di dalam handler klik —
        // browser memblokir popup yang dibuka setelah operasi asinkron.
window.open(
            `https://api.whatsapp.com/send?phone=${nomor}&text=${encodeURIComponent(susunPesan(item))}`,
            '_blank'
        );

        // Pencatatan menyusul otomatis. Sebelumnya bendahara harus menekan
        // "Catat" terpisah, dan dalam praktiknya langkah itu sering terlewat
        // sehingga riwayat penagihan tidak pernah mencerminkan kenyataan.
        router.post(route('keuangan.penagihan.catat'), {
            student_id: item.student_id,
            invoice_id: item.id,
            metode: 'WhatsApp',
            keterangan: `Pesan WhatsApp dikirim ke ${item.siswa?.parent_contact} · sisa ${formatCurrency(item.remaining_balance)}`,
        }, { preserveScroll: true, preserveState: true });
    };

    const submitTagih = (e) => {
        e.preventDefault();
        formTagih.post(route('keuangan.penagihan.catat'), {
            onSuccess: () => setIsTagih(false),
            preserveScroll: true,
        });
    };

    const bisaCatat = can('tagihan.catat_log');

    const thCls = 'cursor-pointer px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200';
    const thPlain = 'px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
    const thAksi = 'px-4 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600';
    // appearance-none + chevron sendiri: panah bawaan browser tidak mengikuti tema.
    const selectModal = 'mt-1.5 block w-full cursor-pointer appearance-none rounded-xl border-0 bg-white py-2.5 pl-4 pr-10 text-sm text-stone-800 shadow-sm ring-1 ring-cream-300 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';

    const btnGhost = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 transition';

    const IkonJam = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );

    const IkonWa = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 21l1.9-4.2A7.9 7.9 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
    );

    const IkonCatat = () => (
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    );

    // Dipakai kartu mobile dan baris tabel.
    const infoBaris = (t) => ({
        terakhir: t.riwayat_penagihans?.[t.riwayat_penagihans.length - 1],
        lewatTempo: t.header?.due_date && new Date(t.header.due_date) < new Date(),
        dipilih: terpilih === t.id,
        adaRiwayat: t.riwayat_penagihans?.length > 0,
    });

    return (
        <KeuanganLayout>
            <Head title="Laporan Tunggakan" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Laporan Tunggakan</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Piutang siswa dari gelombang tagihan yang sudah dipublish.</p>
            </div>

            {/* Kartu total piutang melebar penuh di mobile — nominalnya
                paling panjang dan paling penting untuk dibaca utuh. */}
            <div className="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                {[
                    ['Total Piutang', formatCurrency(ringkas?.totalPiutang || 0), 'text-red-600 dark:text-red-400', true],
                    ['Siswa Menunggak', `${ringkas?.jumlahSiswa || 0} siswa`, 'text-amber-600 dark:text-amber-400', false],
                    ['Invoice Tertunggak', `${ringkas?.jumlahInvoice || 0} invoice`, 'text-stone-700 dark:text-stone-200', false],
                ].map(([label, nilai, warna, lebar]) => (
                    <div key={label} className={`rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5 ${lebar ? 'col-span-2 sm:col-span-1' : ''}`}>
                        <p className="truncate text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">{label}</p>
                        <p className={`mt-1.5 break-words text-lg font-bold leading-tight sm:mt-2 sm:text-xl ${warna}`}>{nilai}</p>
                    </div>
                ))}
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                <div className="mb-5 flex flex-col gap-2 lg:flex-row lg:items-center">
                    <div className="relative lg:max-w-sm lg:flex-1">
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama, NIS, atau no. invoice..." className={`${fieldCls} pl-9`} />
                        <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                            fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <div className="lg:w-72">
                        <SearchableSelect placeholder="-- Semua siswa --"
                            options={listSiswa?.map((s) => ({ id: s.id, label: `${s.nis} · ${s.name}` })) || []}
                            value={studentId} onChange={setStudentId} />
                    </div>
                </div>

                {/* Mobile: kartu. Tabel 6 kolom berisi nama, kontak, dan tiga
                    nominal tidak mungkin dibaca di layar sempit. */}
                <div className="space-y-3 sm:hidden">
                    {tunggakan?.data?.length > 0 ? tunggakan.data.map((t) => {
                        const { terakhir, lewatTempo, dipilih, adaRiwayat } = infoBaris(t);

                        return (
                            <div key={t.id}
                                className={`rounded-xl p-4 ring-1 ${dipilih
                                    ? 'bg-claude-500/10 ring-claude-500/30'
                                    : 'bg-white ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600'}`}>
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="break-words text-sm font-semibold text-stone-800 dark:text-cream-100">{t.siswa?.name}</p>
                                        <p className="mt-0.5 break-words text-xs text-stone-400">
                                            {t.siswa?.parent_name} · {t.siswa?.parent_contact || 'tanpa kontak'}
                                        </p>
                                    </div>
                                    <span className="shrink-0 text-xs font-medium text-claude-600 dark:text-claude-300">{t.invoice_number}</span>
                                </div>

                                <div className="mt-3 flex items-end justify-between gap-3 border-t border-cream-200 pt-3 dark:border-coal-600">
                                    <div className="min-w-0 text-xs">
                                        <p className="break-words text-stone-600 dark:text-stone-300">{t.header?.title}</p>
                                        <p className={lewatTempo ? 'font-medium text-red-500 dark:text-red-400' : 'text-stone-400'}>
                                            Tempo {tanggal(t.header?.due_date)}{lewatTempo && ' · lewat'}
                                        </p>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        <span className="text-sm font-bold text-red-600 dark:text-red-400">{formatCurrency(t.remaining_balance)}</span>
                                        {t.status === 'Installment' && (
                                            <span className="block text-[10px] text-stone-400">dari {formatCurrency(t.total_amount)}</span>
                                        )}
                                    </div>
                                </div>

                                <p className="mt-2 text-xs">
                                    <span className="text-stone-400">Penagihan terakhir: </span>
                                    {terakhir ? (
                                        <span className="text-stone-600 dark:text-stone-300">
                                            {terakhir.metode} · {tanggal(terakhir.tanggal_penagihan)} · {t.riwayat_penagihans.length}×
                                        </span>
                                    ) : <span className="italic text-stone-400">belum pernah</span>}
                                </p>

                                <div className="mt-3 flex flex-wrap gap-2 border-t border-cream-200 pt-3 dark:border-coal-600">
                                    {adaRiwayat && (
                                        <button type="button" onClick={() => bukaRiwayat(t.id)}
                                            className={`${btnGhost} flex-1 justify-center ${
                                                dipilih
                                                    ? 'bg-claude-500 text-white ring-claude-500'
                                                    : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                            }`}>
                                            <IkonJam />
                                            Riwayat
                                        </button>
                                    )}

                                    <button type="button"
                                        onClick={() => kirimWa(t)}
                                        disabled={!t.siswa?.parent_contact}
                                        className={`${btnGhost} flex-1 justify-center ${t.siswa?.parent_contact
                                            ? 'text-emerald-600 ring-emerald-200 hover:bg-emerald-50 dark:text-emerald-400 dark:ring-emerald-500/20'
                                            : 'cursor-not-allowed text-stone-300 ring-cream-300 dark:text-stone-600 dark:ring-coal-600'}`}>
                                        <IkonWa />
                                        WhatsApp
                                    </button>

                                    {bisaCatat && (
                                        <button type="button" onClick={() => bukaTagih(t)}
                                            className={`${btnGhost} flex-1 justify-center text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600`}>
                                            <IkonCatat />
                                            Catat
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    }) : (
                        <p className="rounded-xl bg-cream-100 py-10 text-center text-sm text-stone-500 dark:bg-coal-600/40 dark:text-stone-400">
                            Tidak ada tunggakan. Semua tagihan lunas.
                        </p>
                    )}
                </div>

                {/* Desktop: tabel penuh dengan sorting. */}
                <div className="hidden overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 sm:block">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th onClick={() => handleSort('invoice_number')} className={thCls}><div className="flex items-center">Invoice <SortIcon field="invoice_number" /></div></th>
                                <th className={thPlain}>Siswa &amp; Kontak</th>
                                <th className={thPlain}>Tagihan / Tempo</th>
                                <th onClick={() => handleSort('remaining_balance')} className={thCls}><div className="flex items-center">Sisa <SortIcon field="remaining_balance" /></div></th>
                                <th className={thPlain}>Penagihan Terakhir</th>
                                <th className={thAksi}>Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {tunggakan?.data?.length > 0 ? tunggakan.data.map((t) => {
                                const { terakhir, lewatTempo, dipilih, adaRiwayat } = infoBaris(t);

                                return (
                                    <tr key={t.id} className={`transition ${dipilih ? 'bg-claude-500/10' : 'hover:bg-cream-50 dark:hover:bg-coal-600/40'}`}>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-sm font-medium text-claude-600 dark:text-claude-300">{t.invoice_number}</td>
                                        <td className="px-4 py-3.5 text-sm">
                                            <span className="font-medium text-stone-800 dark:text-cream-100">{t.siswa?.name}</span><br />
                                            <span className="text-xs text-stone-400">{t.siswa?.parent_name} · {t.siswa?.parent_contact || 'tanpa kontak'}</span>
                                        </td>
                                        <td className="px-4 py-3.5 text-sm text-stone-600 dark:text-stone-300">
                                            {t.header?.title}<br />
                                            <span className={`text-xs ${lewatTempo ? 'font-medium text-red-500 dark:text-red-400' : 'text-stone-400'}`}>
                                                Tempo {tanggal(t.header?.due_date)}{lewatTempo && ' · lewat'}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-sm font-semibold text-red-600 dark:text-red-400">
                                            {formatCurrency(t.remaining_balance)}
                                            {t.status === 'Installment' && (
                                                <><br /><span className="text-[10px] font-normal text-stone-400">dari {formatCurrency(t.total_amount)}</span></>
                                            )}
                                        </td>
                                        <td className="px-4 py-3.5 text-xs">
                                            {terakhir ? (
                                                <>
                                                    <span className="font-medium text-stone-700 dark:text-stone-200">{terakhir.metode}</span><br />
                                                    <span className="text-stone-400">{tanggal(terakhir.tanggal_penagihan)} · {t.riwayat_penagihans.length}×</span>
                                                </>
                                            ) : <span className="italic text-stone-400">Belum pernah</span>}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3.5 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                {/* Riwayat dibuka lewat tombol, bukan klik baris. */}
                                                {adaRiwayat && (
                                                    <button type="button" onClick={() => bukaRiwayat(t.id)}
                                                        className={`${btnGhost} ${
                                                            dipilih
                                                                ? 'bg-claude-500 text-white ring-claude-500'
                                                                : 'text-claude-600 ring-claude-200 hover:bg-claude-500/10 dark:text-claude-300 dark:ring-claude-500/30'
                                                        }`}>
                                                        <IkonJam />
                                                        Riwayat
                                                    </button>
                                                )}

                                                {/* Tetap dirender saat nomor kosong, hanya dinonaktifkan:
                                                    tombol yang hilang tanpa penjelasan membuat bendahara
                                                    tidak pernah tahu datanya perlu dilengkapi. */}
                                                <button type="button"
                                                    onClick={() => kirimWa(t)}
                                                    disabled={!t.siswa?.parent_contact}
                                                    title={t.siswa?.parent_contact
                                                        ? `Kirim WhatsApp ke ${t.siswa?.parent_name || 'wali'}`
                                                        : 'Nomor wali belum diisi di Master Data Siswa'}
                                                    className={`${btnGhost} ${t.siswa?.parent_contact
                                                        ? 'text-emerald-600 ring-emerald-200 hover:bg-emerald-50 dark:text-emerald-400 dark:ring-emerald-500/20 dark:hover:bg-emerald-500/10'
                                                        : 'cursor-not-allowed text-stone-300 ring-cream-300 dark:text-stone-600 dark:ring-coal-600'}`}>
                                                    <IkonWa />
                                                    WhatsApp
                                                </button>

                                                {bisaCatat && (
                                                    <button type="button" onClick={() => bukaTagih(t)}
                                                        className={`${btnGhost} text-stone-600 ring-cream-300 hover:bg-cream-100 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600`}>
                                                        <IkonCatat />
                                                        Catat
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan="6" className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Tidak ada tunggakan. Semua tagihan lunas.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p className="text-xs text-stone-500 dark:text-stone-400 sm:text-sm">Menampilkan {tunggakan.from || 0}–{tunggakan.to || 0} dari {tunggakan.total} entri</p>
                    <div className="flex flex-wrap justify-center gap-1">
                        {tunggakan.links.map((l, i) => (
                            <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <div ref={riwayatRef} className="scroll-mt-20" />

            {aktif && aktif.riwayat_penagihans?.length > 0 && (
                <div className="mt-5 rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <div className="mb-4 flex items-start justify-between gap-2 border-b border-cream-200 pb-3 dark:border-coal-600">
                        <h3 className="break-words text-sm font-semibold text-stone-800 dark:text-cream-50">
                            Riwayat Penagihan · {aktif.invoice_number}
                        </h3>
                        <button type="button" onClick={() => setTerpilih(null)} title="Tutup riwayat"
                            className="-mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <ul className="space-y-2">
                        {[...aktif.riwayat_penagihans].reverse().map((log) => (
                            <li key={log.id} className="rounded-xl bg-cream-100 p-3 dark:bg-coal-600/40">
                                <div className="flex items-start justify-between gap-3">
                                    <span className="text-sm font-medium text-stone-800 dark:text-cream-100">{log.metode}</span>
                                    <span className="shrink-0 text-xs text-stone-400">{tanggal(log.tanggal_penagihan)}</span>
                                </div>
                                <p className="mt-1 break-words text-sm text-stone-600 dark:text-stone-300">{log.keterangan}</p>
                                <p className="mt-1.5 text-xs italic text-stone-400">{log.petugas?.name}</p>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <Modal show={isTagih} onClose={() => setIsTagih(false)} maxWidth="md">
                <form onSubmit={submitTagih} className="max-h-[85vh] overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Catat Tindakan Penagihan</h2>
                    <p className="mt-1 break-words text-sm text-stone-500 dark:text-stone-400">{aktif?.siswa?.name} · {aktif?.invoice_number}</p>

                    <div className="mt-5 space-y-4">
                        <div>
                            <InputLabel>Metode <span className="text-red-500">*</span></InputLabel>
                            <div className="relative">
                                <select value={formTagih.data.metode} onChange={(e) => formTagih.setData('metode', e.target.value)} className={selectModal}>
                                    {METODE.map((m) => <option key={m} value={m}>{m}</option>)}
                                </select>
                                <svg className="pointer-events-none absolute right-3.5 top-[calc(50%+3px)] h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                    fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <InputError message={formTagih.errors.metode} className="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel>Keterangan <span className="text-red-500">*</span></InputLabel>
                            <TextInput value={formTagih.data.keterangan} onChange={(e) => formTagih.setData('keterangan', e.target.value)}
                                className="mt-1.5 block w-full" placeholder="Ringkasan hasil penagihan…" />
                            <InputError message={formTagih.errors.keterangan} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => setIsTagih(false)} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={formTagih.processing} className="justify-center">Simpan Catatan</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </KeuanganLayout>
    );
}
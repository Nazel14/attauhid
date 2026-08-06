import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TombolKembali from '@/Components/TombolKembali';

const GAYA = {
    'Hadir': { kode: 'H', cls: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300' },
    'Sakit': { kode: 'S', cls: 'bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-500/20 dark:text-purple-300' },
    'Izin':  { kode: 'I', cls: 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-500/20 dark:text-blue-300' },
    'Alfa':  { kode: 'A', cls: 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-500/20 dark:text-red-300' },
    null:    { kode: '·', cls: 'bg-stone-50 text-stone-300 hover:bg-stone-200 dark:bg-coal-900 dark:text-stone-600 dark:hover:bg-coal-600' },
};

// Kuas yang dipilih di toolbar. 'Hapus' mengosongkan sel.
const KUAS = [
    { id: 'Hadir', label: 'Hadir', kode: 'H', aktif: 'bg-emerald-500 text-white ring-emerald-500' },
    { id: 'Sakit', label: 'Sakit', kode: 'S', aktif: 'bg-purple-500 text-white ring-purple-500' },
    { id: 'Izin',  label: 'Izin',  kode: 'I', aktif: 'bg-blue-500 text-white ring-blue-500' },
    { id: 'Alfa',  label: 'Alfa',  kode: 'A', aktif: 'bg-red-500 text-white ring-red-500' },
    { id: 'Hapus', label: 'Kosongkan', kode: '·', aktif: 'bg-stone-700 text-white ring-stone-700 dark:bg-coal-900 dark:ring-coal-900' },
];

const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI = ['M', 'S', 'S', 'R', 'K', 'J', 'S'];

export default function DataAbsensi({ pegawai, daysInMonth, filters }) {
    const { auth } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);
    const bisaKelola = can('absensi.kelola');

    const [periode, setPeriode] = useState(`${filters.tahun}-${filters.bulan}`);
    const [search, setSearch] = useState(filters?.search || '');
    const [libur, setLibur] = useState({});
    const [konfirmasi, setKonfirmasi] = useState(false);
    const [proses, setProses] = useState(false);

    // Status yang akan dituliskan saat sel diklik.
    const [kuas, setKuas] = useState('Hadir');

    // Perubahan lokal: props dari server baru menyusul setelah redirect,
    // ini yang membuat kotak langsung berubah begitu diklik.
    const [ubah, setUbah] = useState({});

    const [tahun, bulan] = periode.split('-');

    useEffect(() => {
        let batal = false;

        fetch(route('keuangan.penggajian.absensi.holidays', { year: tahun, month: bulan }))
            .then((r) => r.json())
            .then((data) => {
                if (batal || !Array.isArray(data)) return;
                setLibur(Object.fromEntries(data.map((h) => [h.tanggal, h.keterangan])));
            })
            .catch(() => {});

        return () => { batal = true; };
    }, [periode]);

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            setUbah({});
            router.get(route('keuangan.penggajian.absensi'), { bulan, tahun, search },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [periode, search]);

    const kunci = (employeeId, tanggal) => `${employeeId}|${tanggal}`;

    const klikSel = (employeeId, tanggal, statusSekarang) => {
        if (!bisaKelola) return;

        // Klik ulang pada status yang sama = kosongkan; jadi tidak perlu
        // pindah kuas hanya untuk membatalkan satu sel.
        const target = (kuas === 'Hapus' || statusSekarang === kuas) ? null : kuas;

        setUbah((s) => ({ ...s, [kunci(employeeId, tanggal)]: target }));

        router.post(route('keuangan.penggajian.absensi.update'),
            { employee_id: employeeId, date: tanggal, status: target || '' },
            {
                preserveScroll: true,
                preserveState: true,
                // Tanpa `only` — partial reload hanya berlaku pada visit GET,
                // memakainya di POST membuat props tidak pernah diperbarui.
                onError: () => {
                    setUbah((s) => {
                        const n = { ...s };
                        delete n[kunci(employeeId, tanggal)];
                        return n;
                    });
                },
            });
    };

    const jalankanBulk = () => {
        setProses(true);
        router.post(route('keuangan.penggajian.absensi.bulk_hadir'), { bulan, tahun }, {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => { setKonfirmasi(false); setUbah({}); },
        });
    };

    const tglString = (i) => `${tahun}-${bulan}-${String(i).padStart(2, '0')}`;

    // Info per tanggal dipakai header tabel dan kalender mobile.
    const infoHari = (i) => {
        const tgl = tglString(i);
        const d = new Date(tgl);
        const minggu = d.getDay() === 0;
        const nasional = libur[tgl] !== undefined;

        return { tgl, hari: d.getDay(), minggu, nasional, merah: minggu || nasional };
    };

    // Offset agar kolom kalender mobile sejajar dengan nama harinya.
    const offsetAwal = new Date(tglString(1)).getDay();

    const kolomTanggal = () => {
        const cols = [];

        for (let i = 1; i <= daysInMonth; i++) {
            const { tgl, hari, minggu, nasional, merah } = infoHari(i);

            cols.push(
                <th key={i} title={nasional ? libur[tgl] : (minggu ? 'Minggu' : 'Hari kerja')}
                    className={`min-w-[38px] max-w-[38px] border-b border-r px-1 py-2.5 text-center text-xs font-semibold ${
                        merah
                            ? 'border-stone-200 bg-red-50 text-red-600 dark:border-coal-600 dark:bg-red-500/10 dark:text-red-300'
                            : 'border-stone-200 bg-stone-50 text-stone-500 dark:border-coal-600 dark:bg-coal-800 dark:text-stone-400'
                    }`}>
                    <span className="mb-0.5 block text-[9px] font-normal opacity-70">{HARI[hari]}</span>
                    {i}
                </th>
            );
        }

        return cols;
    };

    // Hitung rekap + resolusi status lokal, dipakai kedua tampilan.
    const rekap = (p) => {
        const peta = {};
        p.absensi.forEach((x) => { peta[x.date] = x.status; });

        // Perubahan lokal ditimpakan di atas data server.
        const status = (tgl) => {
            const k = kunci(p.id, tgl);
            return Object.prototype.hasOwnProperty.call(ubah, k) ? ubah[k] : (peta[tgl] || null);
        };

        let h = 0, s = 0, i = 0, a = 0;

        for (let d = 1; d <= daysInMonth; d++) {
            const st = status(tglString(d));
            if (st === 'Hadir') h++;
            else if (st === 'Sakit') s++;
            else if (st === 'Izin') i++;
            else if (st === 'Alfa') a++;
        }

        return { status, h, s, i, a };
    };

    const stickyKanan = 'sticky z-30 border-b border-l px-2 py-2.5 text-center text-[11px] font-semibold uppercase';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    // color-scheme ikut tema agar ikon kalender bawaan browser tidak silau di dark mode.
    const monthCls = `${fieldCls} pr-2 [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-60 hover:[&::-webkit-calendar-picker-indicator]:opacity-100`;

    const kotakRekap = 'flex flex-1 flex-col items-center rounded-lg py-1.5 text-[10px] font-semibold';

    return (
        <KeuanganLayout>
            <Head title="Absensi Pegawai" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Timesheet Kehadiran</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Pilih status di bawah, lalu ketuk kotak tanggal untuk menandainya. Ketuk ulang kotak dengan status sama untuk mengosongkan.</p>
            </div>

            <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="sm:w-44">
                            <input type="month" value={periode} onChange={(e) => setPeriode(e.target.value)}
                                aria-label="Pilih periode" className={`${monthCls} font-medium`} />
                        </div>

                        <div className="relative sm:w-64">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama pegawai..." className={`${fieldCls} pl-9`} />
                            <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                                fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </div>
                    </div>

                    {bisaKelola && (
                        <PrimaryButton onClick={() => setKonfirmasi(true)} className="h-10 shrink-0 justify-center">Isi Hadir Sebulan</PrimaryButton>
                    )}
                </div>

                {/* Pemilih status — menggantikan siklus klik yang membingungkan.
                    Di mobile digeser horizontal: 5 chip tidak muat sebaris. */}
                {bisaKelola ? (
                    <div className="mt-4 border-t border-stone-100 pt-4 dark:border-coal-600">
                        <span className="mb-2 block text-xs font-medium text-stone-500 dark:text-stone-400">Tandai sebagai:</span>
                        <div className="-mx-4 overflow-x-auto px-4 pb-1 sm:mx-0 sm:overflow-visible sm:px-0">
                            <div className="flex min-w-max gap-2 sm:min-w-0 sm:flex-wrap">
                                {KUAS.map((k) => {
                                    const dipilih = kuas === k.id;
                                    return (
                                        <button key={k.id} type="button" onClick={() => setKuas(k.id)}
                                            className={`inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium ring-1 transition ${
                                                dipilih
                                                    ? k.aktif
                                                    : 'bg-white text-stone-600 ring-stone-200 hover:bg-cream-100 dark:bg-coal-900 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                            }`}>
                                            <span className={`flex h-5 w-5 items-center justify-center rounded text-[10px] font-bold ${
                                                dipilih ? 'bg-white/25' : (k.id === 'Hapus' ? GAYA[null].cls : GAYA[k.id].cls)
                                            }`}>
                                                {k.kode}
                                            </span>
                                            {k.label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="mt-4 flex flex-wrap items-center gap-3 border-t border-stone-100 pt-4 text-xs text-stone-500 dark:border-coal-600 dark:text-stone-400">
                        <span className="font-medium">Keterangan:</span>
                        {Object.entries(GAYA).filter(([k]) => k !== 'null').map(([status, g]) => (
                            <span key={status} className="flex items-center gap-1.5">
                                <span className={`flex h-5 w-5 items-center justify-center rounded text-[10px] font-semibold ${g.cls}`}>{g.kode}</span>
                                {status}
                            </span>
                        ))}
                    </div>
                )}
            </div>

            {/* Mobile: satu kartu kalender per pegawai. Grid 31 kolom dengan
                4 kolom sticky di kanan menyisakan <100px area isi di 360px —
                praktis tidak bisa dipakai. */}
            <div className="space-y-3 sm:hidden">
                {pegawai?.data?.length > 0 ? pegawai.data.map((p) => {
                    const { status, h, s, i, a } = rekap(p);

                    return (
                        <div key={p.id} className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600">
                            <div className="flex flex-wrap items-baseline gap-x-2">
                                <span className="text-sm font-semibold text-stone-800 dark:text-cream-100">{p.name}</span>
                                {p.nip && <span className="text-xs text-stone-400">{p.nip}</span>}
                            </div>

                            <div className="mt-3 grid grid-cols-7 gap-1">
                                {HARI.map((nama, idx) => (
                                    <span key={idx} className={`text-center text-[10px] font-semibold ${idx === 0 ? 'text-red-500' : 'text-stone-400'}`}>
                                        {nama}
                                    </span>
                                ))}

                                {/* Sel kosong sebelum tanggal 1 agar kolom sejajar hari. */}
                                {Array.from({ length: offsetAwal }, (_, idx) => <span key={`kosong-${idx}`} />)}

                                {Array.from({ length: daysInMonth }, (_, idx) => {
                                    const hariKe = idx + 1;
                                    const { tgl, merah, nasional, minggu } = infoHari(hariKe);
                                    const st = status(tgl);
                                    const g = GAYA[st];

                                    return (
                                        <button key={hariKe} disabled={!bisaKelola}
                                            onClick={() => klikSel(p.id, tgl, st)}
                                            title={nasional ? libur[tgl] : (minggu ? 'Minggu' : 'Hari kerja')}
                                            className={`flex aspect-square w-full flex-col items-center justify-center rounded-lg text-[10px] font-semibold transition ${g.cls} ${
                                                merah ? 'ring-1 ring-red-200 dark:ring-red-500/25' : ''
                                            } ${!bisaKelola ? 'cursor-not-allowed' : 'active:scale-95'}`}>
                                            <span className={`text-[9px] font-normal leading-none ${merah ? 'text-red-500 dark:text-red-300' : 'opacity-50'}`}>{hariKe}</span>
                                            <span className="leading-tight">{g.kode}</span>
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="mt-3 flex gap-1.5 border-t border-stone-100 pt-3 dark:border-coal-600">
                                <span className={`${kotakRekap} bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300`}>
                                    <span className="text-sm">{h}</span>Hadir
                                </span>
                                <span className={`${kotakRekap} bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300`}>
                                    <span className="text-sm">{s}</span>Sakit
                                </span>
                                <span className={`${kotakRekap} bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300`}>
                                    <span className="text-sm">{i}</span>Izin
                                </span>
                                <span className={`${kotakRekap} bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300`}>
                                    <span className="text-sm">{a}</span>Alfa
                                </span>
                            </div>
                        </div>
                    );
                }) : (
                    <p className="rounded-2xl bg-white py-12 text-center text-sm text-stone-500 ring-1 ring-stone-200 dark:bg-coal-700 dark:text-stone-400 dark:ring-coal-600">
                        Belum ada pegawai aktif.
                    </p>
                )}

                <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600">
                    <p className="text-xs text-stone-500 dark:text-stone-400">Menampilkan {pegawai.from || 0}–{pegawai.to || 0} dari {pegawai.total} pegawai</p>
                    <div className="mt-3 flex flex-wrap justify-center gap-1">
                        {pegawai.links.map((l, i) => (
                            <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            {/* Desktop: grid timesheet penuh. */}
            <div className="hidden overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:block">
                <div className="overflow-x-auto" style={{ maxHeight: '65vh' }}>
                    <table className="min-w-max border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th className="sticky left-0 z-40 min-w-[220px] border-b border-r border-stone-200 bg-stone-100 px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-600 dark:border-coal-600 dark:bg-coal-800 dark:text-stone-300">
                                    Pegawai
                                </th>
                                {kolomTanggal()}
                                <th className={`${stickyKanan} right-[114px] min-w-[38px] border-stone-200 bg-emerald-50 text-emerald-700 dark:border-coal-600 dark:bg-emerald-500/10 dark:text-emerald-300`}>H</th>
                                <th className={`${stickyKanan} right-[76px] min-w-[38px] border-stone-200 bg-purple-50 text-purple-700 dark:border-coal-600 dark:bg-purple-500/10 dark:text-purple-300`}>S</th>
                                <th className={`${stickyKanan} right-[38px] min-w-[38px] border-stone-200 bg-blue-50 text-blue-700 dark:border-coal-600 dark:bg-blue-500/10 dark:text-blue-300`}>I</th>
                                <th className={`${stickyKanan} right-0 min-w-[38px] border-stone-200 bg-red-50 text-red-700 dark:border-coal-600 dark:bg-red-500/10 dark:text-red-300`}>A</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pegawai?.data?.length > 0 ? pegawai.data.map((p) => {
                                const { status, h, s, i, a } = rekap(p);

                                return (
                                    <tr key={p.id} className="group">
                                        <td className="sticky left-0 z-20 border-b border-r border-stone-200 bg-white px-5 py-2 group-hover:bg-cream-50 dark:border-coal-600 dark:bg-coal-700 dark:group-hover:bg-coal-600/40">
                                            <span className="whitespace-nowrap text-sm font-medium text-stone-800 dark:text-cream-100">{p.name}</span>
                                            {p.nip && <span className="ml-2 text-xs text-stone-400">{p.nip}</span>}
                                        </td>

                                        {Array.from({ length: daysInMonth }, (_, idx) => {
                                            const tgl = tglString(idx + 1);
                                            const st = status(tgl);
                                            const g = GAYA[st];

                                            return (
                                                <td key={idx} className="border-b border-r border-stone-100 p-1 text-center dark:border-coal-600">
                                                    <button disabled={!bisaKelola}
                                                        onClick={() => klikSel(p.id, tgl, st)}
                                                        title={`${p.name} · ${tgl}${st ? ` · ${st}` : ''}`}
                                                        className={`h-7 w-7 rounded-lg text-xs font-semibold transition ${g.cls} ${!bisaKelola ? 'cursor-not-allowed' : ''}`}>
                                                        {g.kode}
                                                    </button>
                                                </td>
                                            );
                                        })}

                                        <td className={`${stickyKanan} right-[114px] border-stone-200 bg-emerald-50/90 text-sm font-semibold text-emerald-700 dark:border-coal-600 dark:bg-emerald-500/10 dark:text-emerald-300`}>{h}</td>
                                        <td className={`${stickyKanan} right-[76px] border-stone-200 bg-purple-50/90 text-sm font-semibold text-purple-700 dark:border-coal-600 dark:bg-purple-500/10 dark:text-purple-300`}>{s}</td>
                                        <td className={`${stickyKanan} right-[38px] border-stone-200 bg-blue-50/90 text-sm font-semibold text-blue-700 dark:border-coal-600 dark:bg-blue-500/10 dark:text-blue-300`}>{i}</td>
                                        <td className={`${stickyKanan} right-0 border-stone-200 bg-red-50/90 text-sm font-semibold text-red-700 dark:border-coal-600 dark:bg-red-500/10 dark:text-red-300`}>{a}</td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan={daysInMonth + 5} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada pegawai aktif.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="flex flex-col items-center justify-between gap-3 border-t border-stone-200 p-4 dark:border-coal-600 sm:flex-row">
                    <p className="text-sm text-stone-500 dark:text-stone-400">Menampilkan {pegawai.from || 0}–{pegawai.to || 0} dari {pegawai.total} pegawai</p>
                    <div className="flex flex-wrap gap-1">
                        {pegawai.links.map((l, i) => (
                            <button key={i} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            <Modal show={konfirmasi} onClose={() => setKonfirmasi(false)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Isi Hadir Sebulan?</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Semua hari kerja {BULAN[parseInt(bulan, 10) - 1]} {tahun} diisi “Hadir”, kecuali Sabtu, Minggu, dan libur nasional.
                    </p>
                    <p className="mt-2 text-xs italic text-stone-400">Status Sakit, Izin, dan Alfa yang sudah ada tidak akan ditimpa.</p>
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <SecondaryButton onClick={() => setKonfirmasi(false)} disabled={proses} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton onClick={jalankanBulk} disabled={proses} className="justify-center">Ya, Isi Hadir</PrimaryButton>
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
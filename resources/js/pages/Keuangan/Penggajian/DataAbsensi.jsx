import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef, useMemo } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TombolKembali from '@/Components/TombolKembali';

const GAYA = {
    'Hadir': { kode: 'H', cls: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/30' },
    'Sakit': { kode: 'S', cls: 'bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:hover:bg-purple-500/30' },
    'Izin':  { kode: 'I', cls: 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-500/20 dark:text-blue-300 dark:hover:bg-blue-500/30' },
    'Alfa':  { kode: 'A', cls: 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-500/20 dark:text-red-300 dark:hover:bg-red-500/30' },
    // Sel kosong dibuat ringan (garis tipis), bukan kotak gelap penuh —
    // supaya yang sudah terisi langsung menonjol.
    null:    { kode: '', cls: 'text-stone-300 ring-1 ring-inset ring-stone-200 hover:bg-stone-100 dark:text-stone-600 dark:ring-coal-600 dark:hover:bg-coal-600' },
};

// Kuas yang dipilih di toolbar. 'Hapus' mengosongkan sel.
const KUAS = [
    { id: 'Hadir', label: 'Hadir', kode: 'H', aktif: 'bg-emerald-500 text-white ring-emerald-500' },
    { id: 'Sakit', label: 'Sakit', kode: 'S', aktif: 'bg-purple-500 text-white ring-purple-500' },
    { id: 'Izin',  label: 'Izin',  kode: 'I', aktif: 'bg-blue-500 text-white ring-blue-500' },
    { id: 'Alfa',  label: 'Alfa',  kode: 'A', aktif: 'bg-red-500 text-white ring-red-500' },
    { id: 'Hapus', label: 'Kosongkan', kode: '', aktif: 'bg-stone-700 text-white ring-stone-700 dark:bg-coal-900 dark:ring-coal-900' },
];

const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI = ['M', 'S', 'S', 'R', 'K', 'J', 'S'];

// Lebar kolom rekap di kanan. Offset `right` dihitung dari angka ini —
// sebelumnya offset 38px tidak cocok dengan lebar asli kolom (ada padding),
// sehingga kolom rekap menimpa tanggal 22–25.
const W_REKAP = 44;

// ── Draf disimpan di localStorage ─────────────────────────────────────
// Kuncinya per user, supaya dua admin di satu komputer tidak saling
// mewarisi draf. Semua akses dibungkus try/catch: mode privat / storage
// penuh tidak boleh membuat halaman crash — draf tinggal tidak awet saja.
const kunciStorage = (userId) => `absensi-draft:${userId ?? 'anon'}`;

const bacaDraf = (key) => {
    try {
        const isi = JSON.parse(window.localStorage.getItem(key) || '{}');
        return isi && typeof isi === 'object' && !Array.isArray(isi) ? isi : {};
    } catch {
        return {};
    }
};

const tulisDraf = (key, draf) => {
    try {
        if (Object.keys(draf).length) window.localStorage.setItem(key, JSON.stringify(draf));
        else window.localStorage.removeItem(key);
    } catch { /* storage tidak tersedia — abaikan */ }
};

export default function DataAbsensi({ pegawai, daysInMonth, filters }) {
    const { auth, errors } = usePage().props;
    const can = (p) => (auth.permissions || []).includes(p);
    const bisaKelola = can('absensi.kelola');

    const [periode, setPeriode] = useState(`${filters.tahun}-${filters.bulan}`);
    const [search, setSearch] = useState(filters?.search || '');
    const [libur, setLibur] = useState({});
    const [konfirmasi, setKonfirmasi] = useState(false);
    const [proses, setProses] = useState(false);
    const [menyimpan, setMenyimpan] = useState(false);

    // Status yang akan dituliskan saat sel diklik.
    const [kuas, setKuas] = useState('Hadir');

    // DRAFT: perubahan yang BELUM disimpan, { "empId|Y-m-d": status|null }.
    // Klik sel hanya mengubah draft; database baru tersentuh saat Simpan.
    // Dimuat dari localStorage → tetap ada setelah pindah menu, pindah
    // bulan, refresh, atau browser ditutup.
    const STORAGE_KEY = kunciStorage(auth?.user?.id);
    const [draft, setDraft] = useState(() => bacaDraf(STORAGE_KEY));

    // Setiap draf berubah → tulis ke localStorage (kosong = dihapus).
    useEffect(() => { tulisDraf(STORAGE_KEY, draft); }, [draft, STORAGE_KEY]);

    const jumlahDraft = Object.keys(draft).length;
    const adaDraft = jumlahDraft > 0;

    // Ref untuk listener keyboard supaya selalu baca nilai terbaru.
    const adaDraftRef = useRef(false);
    adaDraftRef.current = adaDraft;

    const [tahun, bulan] = periode.split('-');

    // Draf bisa mencakup beberapa bulan; yang tidak terlihat ditampilkan
    // terpisah supaya admin tahu masih ada draf di bulan lain.
    const jumlahBulanIni = Object.keys(draft).filter((k) => k.includes(`|${tahun}-${bulan}-`)).length;
    const jumlahBulanLain = jumlahDraft - jumlahBulanIni;

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
            // Draft TIDAK dikosongkan saat mencari nama: kuncinya per pegawai,
            // jadi perubahan pegawai yang sedang tersembunyi tetap ikut disimpan.
            router.get(route('keuangan.penggajian.absensi'), { bulan, tahun, search },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [periode, search]);

    // Tidak ada lagi konfirmasi "tinggalkan halaman?": draf sudah awet di
    // localStorage, jadi pindah menu / refresh tidak menghilangkan apa pun.

    // Ctrl/Cmd + S = Simpan.
    useEffect(() => {
        const onKey = (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                if (adaDraftRef.current) simpan();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    });

    const kunci = (employeeId, tanggal) => `${employeeId}|${tanggal}`;

    // Status dari server (tanpa draft), untuk tahu apakah klik = kembali ke asal.
    const statusServer = useMemo(() => {
        const peta = {};
        (pegawai?.data || []).forEach((p) => {
            p.absensi.forEach((x) => { peta[kunci(p.id, String(x.date).slice(0, 10))] = x.status; });
        });
        return peta;
    }, [pegawai]);

    // Draf yang ternyata SAMA dengan data server dibuang otomatis — misalnya
    // admin lain sudah mengisi status yang sama, atau draf lama dari
    // localStorage sudah tidak relevan. Hanya untuk pegawai & bulan yang
    // sedang tampil, karena hanya itu yang datanya kita punya.
    useEffect(() => {
        const tampil = new Set((pegawai?.data || []).map((p) => String(p.id)));
        const awalan = `${tahun}-${bulan}-`;
        setDraft((s) => {
            let berubah = false;
            const n = { ...s };
            Object.entries(s).forEach(([k, v]) => {
                const [id, tgl] = k.split('|');
                if (!tampil.has(id) || !tgl.startsWith(awalan)) return;
                if ((statusServer[k] ?? null) === v) { delete n[k]; berubah = true; }
            });
            return berubah ? n : s;
        });
    }, [statusServer]);

    const klikSel = (employeeId, tanggal, statusSekarang) => {
        if (!bisaKelola || menyimpan) return;

        // Klik ulang pada status yang sama = kosongkan.
        const target = (kuas === 'Hapus' || statusSekarang === kuas) ? null : kuas;
        const k = kunci(employeeId, tanggal);
        const asal = statusServer[k] ?? null;

        setDraft((s) => {
            const n = { ...s };
            // Kembali ke status asal → bukan perubahan lagi, buang dari draft.
            if (target === asal) delete n[k];
            else n[k] = target;
            return n;
        });
    };

    const simpan = () => {
        if (!adaDraft || menyimpan) return;

        const perubahan = Object.entries(draft).map(([k, status]) => {
            const [employee_id, date] = k.split('|');
            return { employee_id: Number(employee_id), date, status: status || '' };
        });

        setMenyimpan(true);
        router.post(route('keuangan.penggajian.absensi.simpan'), { perubahan }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setDraft({}),
            onFinish: () => setMenyimpan(false),
        });
    };

    // Draf sekarang awet, jadi membuangnya perlu konfirmasi.
    const batalkan = () => {
        if (!window.confirm(`Buang ${jumlahDraft} draf absensi yang belum disimpan?`)) return;
        setDraft({});
    };

    // Pindah bulan TIDAK membuang draf: kuncinya memuat tanggal, jadi draf
    // bulan lain tetap tersimpan dan ikut terkirim saat Simpan.
    const gantiPeriode = (nilai) => {
        if (!nilai) return;
        setPeriode(nilai);
    };

    const jalankanBulk = () => {
        setProses(true);
        router.post(route('keuangan.penggajian.absensi.bulk_hadir'), { bulan, tahun }, {
            preserveScroll: true,
            onFinish: () => setProses(false),
            onSuccess: () => { setKonfirmasi(false); setDraft({}); },
        });
    };

    const tglString = (i) => `${tahun}-${bulan}-${String(i).padStart(2, '0')}`;

    // Info per tanggal dipakai header tabel, sel, dan kalender mobile.
    // `merah` = Minggu / libur nasional, `redup` = Sabtu (ikut dilewati
    // "Isi Hadir Sebulan", jadi ditandai juga supaya konsisten).
    const infoHari = (i) => {
        const tgl = tglString(i);
        const d = new Date(`${tgl}T00:00:00`);
        const hari = d.getDay();
        const minggu = hari === 0;
        const sabtu = hari === 6;
        const nasional = libur[tgl] !== undefined;

        return { tgl, hari, minggu, sabtu, nasional, merah: minggu || nasional, redup: sabtu && !nasional };
    };

    const judulHari = ({ tgl, nasional, minggu, sabtu }) =>
        nasional ? libur[tgl] : (minggu ? 'Minggu' : (sabtu ? 'Sabtu' : 'Hari kerja'));

    // Offset agar kolom kalender mobile sejajar dengan nama harinya.
    const offsetAwal = new Date(`${tglString(1)}T00:00:00`).getDay();

    // Hitung rekap + resolusi status (draft di atas data server).
    const rekap = (p) => {
        const status = (tgl) => {
            const k = kunci(p.id, tgl);
            return Object.prototype.hasOwnProperty.call(draft, k) ? draft[k] : (statusServer[k] ?? null);
        };
        const diubah = (tgl) => Object.prototype.hasOwnProperty.call(draft, kunci(p.id, tgl));

        let h = 0, s = 0, i = 0, a = 0;
        for (let d = 1; d <= daysInMonth; d++) {
            const st = status(tglString(d));
            if (st === 'Hadir') h++;
            else if (st === 'Sakit') s++;
            else if (st === 'Izin') i++;
            else if (st === 'Alfa') a++;
        }

        return { status, diubah, h, s, i, a };
    };

    // Penanda sel yang belum disimpan: cincin amber + titik di pojok.
    const tandaDraft = 'ring-2 ring-amber-400 ring-offset-1 ring-offset-white dark:ring-offset-coal-700';
    const titikDraft = <span className="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-amber-400" />;

    // Kolom rekap kanan: lebar tetap + background PADAT (bukan /10 transparan)
    // supaya tanggal di belakangnya tidak tembus saat tabel digeser.
    const REKAP = [
        { k: 'h', label: 'H', cls: 'bg-emerald-50 text-emerald-700 dark:bg-[#1f3329] dark:text-emerald-300' },
        { k: 's', label: 'S', cls: 'bg-purple-50 text-purple-700 dark:bg-[#2c2338] dark:text-purple-300' },
        { k: 'i', label: 'I', cls: 'bg-blue-50 text-blue-700 dark:bg-[#1e2a3a] dark:text-blue-300' },
        { k: 'a', label: 'A', cls: 'bg-red-50 text-red-700 dark:bg-[#3a2222] dark:text-red-300' },
    ];
    const kananPx = (idx) => (REKAP.length - 1 - idx) * W_REKAP;
    const rekapBase = 'sticky border-b border-stone-200 text-center dark:border-coal-600';

    // Tinggi & radius seragam untuk semua kontrol filter.
    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-stone-200 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-600';
    const monthCls = `${fieldCls} pr-2 [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-60 hover:[&::-webkit-calendar-picker-indicator]:opacity-100`;

    const kotakRekap = 'flex flex-1 flex-col items-center rounded-lg py-1.5 text-[10px] font-semibold';

    const paginasi = (l) => l.url && router.get(l.url, {}, { preserveScroll: true, preserveState: true });

    const tombolSimpan = (
        <PrimaryButton onClick={simpan} disabled={!adaDraft || menyimpan}
            className="h-10 shrink-0 justify-center gap-2 disabled:opacity-50">
            {menyimpan ? 'Menyimpan…' : 'Simpan'}
            {adaDraft && !menyimpan && (
                <span className="rounded-md bg-white/25 px-1.5 py-0.5 text-[11px] font-bold leading-none">{jumlahDraft}</span>
            )}
        </PrimaryButton>
    );

    return (
        <KeuanganLayout>
            <Head title="Absensi Pegawai" />

            <TombolKembali />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Timesheet Kehadiran</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Pilih status, ketuk kotak tanggal untuk menandai, lalu tekan <span className="font-semibold text-stone-700 dark:text-cream-100">Simpan</span>.
                    Ketuk ulang kotak dengan status sama untuk mengosongkan.
                </p>
            </div>

            <div className="mb-5 rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-5">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="sm:w-44">
                            <input type="month" value={periode} onChange={(e) => gantiPeriode(e.target.value)}
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
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            {/* Status simpan selalu terlihat, bukan hanya saat ada draft. */}
                            <span className={`flex items-center gap-1.5 text-xs font-medium sm:mr-2 ${adaDraft ? 'text-amber-600 dark:text-amber-400' : 'text-stone-400'}`}>
                                <span className={`h-2 w-2 rounded-full ${adaDraft ? 'bg-amber-400' : 'bg-emerald-500'}`} />
                                {adaDraft
                                    ? `${jumlahDraft} draf belum disimpan${jumlahBulanLain ? ` (${jumlahBulanLain} di bulan lain)` : ''}`
                                    : 'Semua tersimpan'}
                            </span>
                            <SecondaryButton onClick={() => setKonfirmasi(true)} disabled={adaDraft}
                                title={adaDraft ? 'Simpan atau batalkan perubahan dulu' : undefined}
                                className="h-10 shrink-0 justify-center disabled:opacity-50">
                                Isi Hadir Sebulan
                            </SecondaryButton>
                            {tombolSimpan}
                        </div>
                    )}
                </div>

                {errors?.perubahan && (
                    <p className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">{errors.perubahan}</p>
                )}

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

            {/* ===================== MOBILE ===================== */}
            <div className="space-y-3 sm:hidden">
                {pegawai?.data?.length > 0 ? pegawai.data.map((p) => {
                    const { status, diubah, h, s, i, a } = rekap(p);

                    return (
                        <div key={p.id} className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600">
                            <div className="flex flex-wrap items-baseline gap-x-2">
                                <span className="text-sm font-semibold text-stone-800 dark:text-cream-100">{p.name}</span>
                                {p.nip && <span className="text-xs text-stone-400">{p.nip}</span>}
                            </div>

                            <div className="mt-3 grid grid-cols-7 gap-1.5">
                                {HARI.map((nama, idx) => (
                                    <span key={idx} className={`text-center text-[10px] font-semibold ${idx === 0 ? 'text-red-500' : 'text-stone-400'}`}>
                                        {nama}
                                    </span>
                                ))}

                                {Array.from({ length: offsetAwal }, (_, idx) => <span key={`kosong-${idx}`} />)}

                                {Array.from({ length: daysInMonth }, (_, idx) => {
                                    const hariKe = idx + 1;
                                    const info = infoHari(hariKe);
                                    const st = status(info.tgl);
                                    const g = GAYA[st];
                                    const ubah = diubah(info.tgl);

                                    return (
                                        <button key={hariKe} disabled={!bisaKelola}
                                            onClick={() => klikSel(p.id, info.tgl, st)}
                                            title={judulHari(info)}
                                            className={`relative flex aspect-square w-full flex-col items-center justify-center rounded-lg text-[11px] font-semibold transition ${g.cls} ${
                                                !st && (info.merah || info.redup) ? 'bg-stone-50 dark:bg-white/[0.03]' : ''
                                            } ${ubah ? tandaDraft : ''} ${!bisaKelola ? 'cursor-not-allowed' : 'active:scale-95'}`}>
                                            <span className={`text-[9px] font-normal leading-none ${info.merah ? 'text-red-500 dark:text-red-300' : 'opacity-60'}`}>{hariKe}</span>
                                            <span className="leading-tight">{g.kode}</span>
                                            {ubah && titikDraft}
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="mt-3 flex gap-1.5 border-t border-stone-100 pt-3 dark:border-coal-600">
                                <span className={`${kotakRekap} bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300`}><span className="text-sm">{h}</span>Hadir</span>
                                <span className={`${kotakRekap} bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300`}><span className="text-sm">{s}</span>Sakit</span>
                                <span className={`${kotakRekap} bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300`}><span className="text-sm">{i}</span>Izin</span>
                                <span className={`${kotakRekap} bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300`}><span className="text-sm">{a}</span>Alfa</span>
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
                            <button key={i} onClick={() => paginasi(l)} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            {/* ===================== DESKTOP ===================== */}
            <div className="hidden overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:block">
                <div className="overflow-auto" style={{ maxHeight: '65vh' }}>
                    <table className="min-w-max border-separate border-spacing-0">
                        <thead>
                            <tr>
                                {/* Header ikut sticky di atas saat tabel di-scroll vertikal. */}
                                <th className="sticky left-0 top-0 z-40 min-w-[240px] border-b border-r border-stone-200 bg-stone-100 px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-600 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.15)] dark:border-coal-600 dark:bg-coal-800 dark:text-stone-300">
                                    Pegawai
                                </th>

                                {Array.from({ length: daysInMonth }, (_, idx) => {
                                    const info = infoHari(idx + 1);
                                    return (
                                        <th key={idx} title={judulHari(info)}
                                            className={`sticky top-0 z-20 w-[40px] min-w-[40px] border-b border-stone-200 px-0 py-2 text-center text-xs font-semibold dark:border-coal-600 ${
                                                info.merah
                                                    ? 'bg-red-50 text-red-600 dark:bg-[#3a2626] dark:text-red-300'
                                                    : info.redup
                                                        ? 'bg-stone-100 text-stone-400 dark:bg-coal-900 dark:text-stone-500'
                                                        : 'bg-stone-50 text-stone-500 dark:bg-coal-800 dark:text-stone-400'
                                            }`}>
                                            <span className="mb-0.5 block text-[9px] font-normal opacity-70">{HARI[info.hari]}</span>
                                            {idx + 1}
                                        </th>
                                    );
                                })}

                                {REKAP.map((r, idx) => (
                                    <th key={r.k} style={{ right: kananPx(idx), width: W_REKAP, minWidth: W_REKAP, maxWidth: W_REKAP }}
                                        className={`${rekapBase} top-0 z-40 py-3 text-[11px] font-bold ${idx === 0 ? 'border-l shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.15)]' : ''} ${r.cls}`}>
                                        {r.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {pegawai?.data?.length > 0 ? pegawai.data.map((p) => {
                                const hasil = rekap(p);
                                const { status, diubah } = hasil;

                                return (
                                    <tr key={p.id} className="group">
                                        <td className="sticky left-0 z-30 border-b border-r border-stone-200 bg-white px-5 py-2 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.15)] group-hover:bg-cream-50 dark:border-coal-600 dark:bg-coal-700 dark:group-hover:bg-coal-600">
                                            <span className="block whitespace-nowrap text-sm font-medium text-stone-800 dark:text-cream-100">{p.name}</span>
                                            {p.nip && <span className="text-[11px] text-stone-400">{p.nip}</span>}
                                        </td>

                                        {Array.from({ length: daysInMonth }, (_, idx) => {
                                            const info = infoHari(idx + 1);
                                            const st = status(info.tgl);
                                            const g = GAYA[st];
                                            const ubah = diubah(info.tgl);

                                            return (
                                                <td key={idx} className={`border-b border-stone-100 p-1 text-center dark:border-coal-600 ${
                                                    info.merah ? 'bg-red-50/50 dark:bg-red-500/[0.04]' : info.redup ? 'bg-stone-50 dark:bg-white/[0.02]' : ''
                                                }`}>
                                                    <button disabled={!bisaKelola}
                                                        onClick={() => klikSel(p.id, info.tgl, st)}
                                                        title={`${p.name} · ${info.tgl} · ${judulHari(info)}${st ? ` · ${st}` : ''}${ubah ? ' · belum disimpan' : ''}`}
                                                        className={`relative mx-auto flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold transition ${g.cls} ${ubah ? tandaDraft : ''} ${!bisaKelola ? 'cursor-not-allowed' : ''}`}>
                                                        {g.kode}
                                                        {ubah && titikDraft}
                                                    </button>
                                                </td>
                                            );
                                        })}

                                        {REKAP.map((r, idx) => (
                                            <td key={r.k} style={{ right: kananPx(idx), width: W_REKAP, minWidth: W_REKAP, maxWidth: W_REKAP }}
                                                className={`${rekapBase} z-20 py-2 text-sm font-semibold ${idx === 0 ? 'border-l shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.15)]' : ''} ${r.cls}`}>
                                                {hasil[r.k]}
                                            </td>
                                        ))}
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan={daysInMonth + 5} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">Belum ada pegawai aktif.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="flex flex-col items-center justify-between gap-3 border-t border-stone-200 p-4 dark:border-coal-600 sm:flex-row">
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-stone-500 dark:text-stone-400">
                        <span>Menampilkan {pegawai.from || 0}–{pegawai.to || 0} dari {pegawai.total} pegawai</span>
                        <span className="flex items-center gap-1.5"><span className="h-3 w-3 rounded bg-red-100 dark:bg-red-500/20" />Minggu / libur nasional</span>
                        <span className="flex items-center gap-1.5"><span className="h-3 w-3 rounded bg-stone-200 dark:bg-coal-900" />Sabtu</span>
                        {bisaKelola && <span className="flex items-center gap-1.5"><span className="h-2 w-2 rounded-full bg-amber-400" />Belum disimpan</span>}
                    </div>
                    <div className="flex flex-wrap gap-1">
                        {pegawai.links.map((l, i) => (
                            <button key={i} onClick={() => paginasi(l)} disabled={!l.url}
                                className={`rounded-lg px-3 py-1.5 text-sm transition ${l.active ? 'bg-claude-500 font-medium text-white' : 'text-stone-600 ring-1 ring-stone-200 hover:bg-cream-100 disabled:opacity-40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ))}
                    </div>
                </div>
            </div>

            {/* ── Bar simpan melayang: muncul begitu ada perubahan ───────────
                sticky (bukan fixed) supaya lebarnya ikut area konten, tidak
                menimpa sidebar. */}
            {bisaKelola && adaDraft && (
                <div className="sticky bottom-4 z-50 mt-4">
                    <div className="mx-auto flex max-w-xl items-center gap-3 rounded-2xl bg-stone-900 px-4 py-3 text-white shadow-2xl ring-1 ring-black/10 dark:bg-coal-900 dark:ring-coal-600">
                        <span className="h-2.5 w-2.5 shrink-0 animate-pulse rounded-full bg-amber-400" />
                        <span className="flex-1 text-sm">
                            <span className="font-semibold">{jumlahDraft} draf</span> belum disimpan
                            {jumlahBulanLain > 0 && <span className="text-stone-400"> · {jumlahBulanLain} di bulan lain</span>}
                            <span className="ml-2 hidden text-xs text-stone-400 md:inline">Ctrl+S</span>
                        </span>
                        <button type="button" onClick={batalkan} disabled={menyimpan}
                            className="rounded-lg px-3 py-1.5 text-sm text-stone-300 transition hover:bg-white/10 hover:text-white disabled:opacity-50">
                            Buang draf
                        </button>
                        <button type="button" onClick={simpan} disabled={menyimpan}
                            className="rounded-lg bg-claude-500 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-claude-600 disabled:opacity-60">
                            {menyimpan ? 'Menyimpan…' : 'Simpan'}
                        </button>
                    </div>
                </div>
            )}

            <Modal show={konfirmasi} onClose={() => setKonfirmasi(false)} maxWidth="sm">
                <div className="p-5 text-center sm:p-6">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 className="text-base font-semibold text-stone-800 dark:text-cream-50">Isi Hadir Sebulan?</h3>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Semua hari kerja {BULAN[parseInt(bulan, 10) - 1]} {tahun} diisi “Hadir”, kecuali Sabtu, Minggu, dan libur nasional.
                    </p>
                    <p className="mt-2 text-xs italic text-stone-400">Langsung tersimpan. Status Sakit, Izin, dan Alfa yang sudah ada tidak akan ditimpa.</p>
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <SecondaryButton onClick={() => setKonfirmasi(false)} disabled={proses} className="justify-center">Batal</SecondaryButton>
                        <PrimaryButton onClick={jalankanBulk} disabled={proses} className="justify-center">Ya, Isi Hadir</PrimaryButton>
                    </div>
                </div>
            </Modal>
        </KeuanganLayout>
    );
}
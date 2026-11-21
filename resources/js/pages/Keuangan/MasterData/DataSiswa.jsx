import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';

// Siswa tanpa kelas dihitung TK — sama dengan scopeTk() di model Santri.
const lembagaDari = (k) => (k?.lembaga === 'arahan' ? 'arahan' : 'tk');

const TAB_LEMBAGA = [
    { key: '',       label: 'Semua' },
    { key: 'tk',     label: 'TK At-Tauhid' },
    { key: 'arahan', label: 'Rumah Quran' },
];

function KartuKelas({ item, href, tanpaKelas = false }) {
    const penuh = !tanpaKelas && item.kapasitas > 0 && item.total >= item.kapasitas;
    const arahan = !tanpaKelas && lembagaDari(item) === 'arahan';

    return (
        <Link href={href}
            className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-cream-300 transition hover:-translate-y-0.5 hover:ring-claude-500/40 dark:bg-coal-700 dark:ring-coal-600">
            <div className={`px-4 pb-3.5 pt-4 ${tanpaKelas ? 'bg-amber-50 dark:bg-amber-500/10' : 'bg-cream-100 dark:bg-coal-600/40'}`}>
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <h3 className="truncate text-base font-bold text-stone-800 dark:text-cream-50">
                            {tanpaKelas ? 'Tanpa Kelas' : item.name}
                            {!tanpaKelas && item.tingkat && (
                                <span className="ml-1.5 text-xs font-normal text-stone-400">({item.tingkat})</span>
                            )}
                        </h3>
                        <p className="mt-0.5 truncate text-[11px] text-stone-500 dark:text-stone-400">
                            {tanpaKelas
                                ? 'Belum dapat pembagian kelas'
                                : `${item.tahun_ajaran || 'Tanpa tahun ajaran'} · ${item.guru_name || 'Belum ada wali'}`}
                        </p>
                    </div>

                    <div className="flex shrink-0 flex-col items-end gap-1">
                        {arahan && (
                            <span className="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20">
                                Rumah Quran
                            </span>
                        )}
                        {!tanpaKelas && !item.is_active && (
                            <span className="rounded-full bg-cream-200 px-2 py-0.5 text-[10px] font-semibold text-stone-500 ring-1 ring-cream-300 dark:bg-coal-600 dark:text-stone-300 dark:ring-coal-500">
                                Non-Aktif
                            </span>
                        )}
                    </div>
                </div>

                <div className="mt-3 flex items-baseline gap-1.5">
                    <span className={`text-3xl font-bold ${penuh ? 'text-red-500' : 'text-claude-600 dark:text-claude-300'}`}>
                        {item.total}
                    </span>
                    <span className="text-xs text-stone-500 dark:text-stone-400">
                        {arahan ? 'santri' : 'siswa'}{!tanpaKelas && item.kapasitas > 0 && ` / ${item.kapasitas} kapasitas`}
                    </span>
                </div>
            </div>

            {/* Reguler vs Fullday penting di sini — menentukan tarif tagihan.
                Rumah Quran tidak ditagih, jadi pembagian itu tidak berarti apa-apa. */}
            <div className="flex-1 px-4 py-3">
                {arahan ? (
                    <p className="text-[11px] text-store-500 dark:text-stone-400"></p>
                ) : (
                <div className="grid grid-cols-2 gap-2">
                    <div className="rounded-xl bg-cream-100 px-3 py-2 text-center ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                        <p className="mb-0.5 text-[10px] uppercase tracking-wider text-stone-400">Reguler</p>
                        <p className="text-lg font-bold text-stone-700 dark:text-stone-200">{item.reguler}</p>
                    </div>
                    <div className="rounded-xl bg-cream-100 px-3 py-2 text-center ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                        <p className="mb-0.5 text-[10px] uppercase tracking-wider text-stone-400">Fullday</p>
                        <p className="text-lg font-bold text-stone-700 dark:text-stone-200">{item.fullday}</p>
                    </div>
                </div>
                )}

                {item.ditahan > 0 && (
                    <p className="mt-2 flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                        <svg className="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        {item.ditahan} rapor ditahan
                    </p>
                )}
            </div>

            <div className="px-4 pb-4">
                <span className="flex w-full items-center justify-center gap-1.5 rounded-xl bg-claude-500 py-2.5 text-xs font-semibold text-white shadow-sm shadow-claude-500/20 transition group-hover:bg-claude-600">
                    {arahan ? 'Lihat Daftar Santri' : 'Lihat Daftar Siswa'}
                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </span>
            </div>
        </Link>
    );
}

export default function DataSiswa({ daftarKelas = [], tanpaKelas, listTahun = [], filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [tahunFilter, setTahunFilter] = useState(filters?.tahunFilter || '');
    // Disaring di browser: semua kelas sudah terkirim tanpa paginasi,
    // jadi berganti tab tidak perlu memuat ulang halaman.
    const [lembaga, setLembaga] = useState('');

    const pertamaKali = useRef(true);
    useEffect(() => {
        if (pertamaKali.current) { pertamaKali.current = false; return; }

        const t = setTimeout(() => {
            router.get(route('keuangan.master-data.siswa'), { search, tahunFilter },
                { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(t);
    }, [search, tahunFilter]);

    const kelasTk     = daftarKelas.filter((k) => lembagaDari(k) === 'tk');
    const kelasArahan = daftarKelas.filter((k) => lembagaDari(k) === 'arahan');
    const adaTanpaKelas = tanpaKelas?.total > 0;

    const jumlah = (list) => list.reduce((n, k) => n + k.total, 0);
    const totalTk     = jumlah(kelasTk) + (tanpaKelas?.total || 0);
    const totalArahan = jumlah(kelasArahan);
    const totalSiswa  = totalTk + totalArahan;

    const hitungTab = { '': totalSiswa, tk: totalTk, arahan: totalArahan };

    const tampilTk     = lembaga !== 'arahan';
    const tampilArahan = lembaga !== 'tk';
    const kosong = (!tampilTk || (kelasTk.length === 0 && !adaTanpaKelas))
                && (!tampilArahan || kelasArahan.length === 0);

    const grid = 'grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4';

    // Judul bagian hanya muncul di tab "Semua" — di tab tunggal sudah jelas.
    const judulBagian = (label, total, satuan) => lembaga === '' && (
        <div className="mb-3 flex items-baseline gap-2">
            <h2 className="text-sm font-semibold text-stone-800 dark:text-cream-50">{label}</h2>
            <span className="text-xs text-stone-400">{total} {satuan}</span>
        </div>
    );

    const fieldCls = 'h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600 dark:[color-scheme:dark]';
    const selectCls = `${fieldCls} cursor-pointer appearance-none pr-9`;

    return (
        <KeuanganLayout>
            <Head title="Data Siswa" />

            {/* Hanya mobile: di desktop sidebar sudah menyediakan navigasi. */}
            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-cream-300 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Master Data Siswa</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Pilih kelas untuk melihat dan mengelola siswanya. Total {totalSiswa} terdaftar — {totalTk} siswa TK dan {totalArahan} santri Rumah Quran.
                </p>
            </div>

            <div className="mb-4 grid grid-cols-3 gap-2 sm:flex">
                {TAB_LEMBAGA.map((t) => (
                    <button key={t.key} type="button" onClick={() => setLembaga(t.key)}
                        className={`truncate rounded-xl px-3 py-2.5 text-xs font-medium transition sm:px-4 sm:text-sm ${
                            lembaga === t.key
                                ? 'bg-claude-500 text-white shadow-lg shadow-claude-500/25'
                                : 'bg-white text-stone-600 ring-1 ring-cream-300 hover:bg-cream-100 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                        }`}>
                        {t.label}
                        <span className={`ml-1.5 ${lembaga === t.key ? 'text-white/80' : 'text-stone-400'}`}>{hitungTab[t.key]}</span>
                    </button>
                ))}
            </div>

            <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center">
                <div className="relative sm:max-w-sm sm:flex-1">
                    <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari nama kelas atau tahun ajaran..." className={`${fieldCls} pl-9`} />
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
                    <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
                        fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            {kosong ? (
                <div className="rounded-2xl bg-white py-16 text-center ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600">
                    <svg className="mx-auto mb-3 h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-9-6.5V15a9 9 0 0018 0v-2.5" />
                    </svg>
                    <p className="text-sm font-medium text-stone-600 dark:text-stone-300">
                        {lembaga === 'arahan' ? 'Belum ada kelas Rumah Quran' : 'Tidak ada kelas ditemukan'}
                    </p>
                    <p className="mt-1 text-xs text-stone-400">
                        {lembaga === 'arahan'
                            ? 'Tandai kelas ngaji sebagai Rumah Quran lewat Edit di Master Data Kelas'
                            : 'Ubah kata kunci pencarian atau buat kelas di Master Data Kelas'}
                    </p>
                </div>
            ) : (
                <div className="space-y-7">
                    {tampilTk && (kelasTk.length > 0 || adaTanpaKelas) && (
                        <section>
                            {judulBagian('TK At-Tauhid', totalTk, 'siswa')}
                            <div className={grid}>
                                {kelasTk.map((k) => (
                                    <KartuKelas key={k.id} item={k}
                                        href={route('keuangan.master-data.siswa.detail', k.id)} />
                                ))}

                                {/* Selalu di akhir supaya tidak menyela urutan kelas. */}
                                {adaTanpaKelas && (
                                    <KartuKelas tanpaKelas item={tanpaKelas}
                                        href={route('keuangan.master-data.siswa.detail', 'tanpa-kelas')} />
                                )}
                            </div>
                        </section>
                    )}

                    {tampilArahan && kelasArahan.length > 0 && (
                        <section>
                            {judulBagian('Rumah Quran', totalArahan, 'santri')}
                            <div className={grid}>
                                {kelasArahan.map((k) => (
                                    <KartuKelas key={k.id} item={k}
                                        href={route('keuangan.master-data.siswa.detail', k.id)} />
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            )}
        </KeuanganLayout>
    );
}
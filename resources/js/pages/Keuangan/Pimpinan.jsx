import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head } from '@inertiajs/react';
import { formatCurrency } from '@/Utils/formatCurrency';

const kartu = 'rounded-2xl bg-white p-4 ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600 sm:p-5';
const th    = 'px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';
const thNum = 'px-3 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400';

// Merah hanya di atas sepertiga kelas. Di bawah itu tunggakan masih
// wajar dan mewarnainya merah membuat seluruh tabel terlihat darurat.
const warnaPersen = (p) =>
    p >= 50 ? 'text-red-600 dark:text-red-400'
    : p >= 33 ? 'text-amber-600 dark:text-amber-400'
    : 'text-stone-600 dark:text-stone-300';

const warnaBatang = (p) =>
    p >= 50 ? 'bg-red-500' : p >= 33 ? 'bg-amber-500' : 'bg-claude-500';

// Serapan anggaran dibaca terbalik dari tunggakan: 69% terpakai di
// pertengahan tahun ajaran itu wajar, bukan peringatan. Merah baru
// pantas ketika posnya benar-benar nyaris habis.
const warnaSerapan = (p) =>
    p >= 90 ? 'text-red-600 dark:text-red-400'
    : p >= 75 ? 'text-amber-600 dark:text-amber-400'
    : 'text-stone-600 dark:text-stone-300';

// Urutan dan warna kolom pemasukan per sumber. Biru untuk Rumah Quran,
// sama dengan badge lembaga di Data Kelas.
const SUMBER = [
    { key: 'tk',      label: 'TK At-Tauhid', ket: 'pembayaran tagihan', warna: 'text-claude-600 dark:text-claude-300' },
    { key: 'arahan',  label: 'Rumah Quran',  ket: 'infaq sukarela',     warna: 'text-sky-600 dark:text-sky-300' },
    { key: 'lainnya', label: 'Umum',         ket: 'uang masuk, koperasi, donatur', warna: 'text-stone-700 dark:text-stone-200' },
];

export default function Pimpinan({ tahun, pemasukan, tunggakanKelas, keringanan, bebanGaji, rkas, tahfizh }) {
    const kelas  = tunggakanKelas ?? [];
    const alasan = keringanan ?? [];

    const totalTunggakan  = kelas.reduce((t, k) => t + Number(k.nominal), 0);
    const totalDibebaskan = alasan.reduce((t, k) => t + Number(k.nominal), 0);

    return (
        <KeuanganLayout>
            <Head title="Ringkasan Pimpinan" />

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Ringkasan Pimpinan</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Pemasukan, piutang, keringanan, beban gaji, dan serapan anggaran sepanjang {tahun}.
                </p>
            </div>

            {pemasukan && (
                <div className={`mb-5 ${kartu}`}>
                    <div className="mb-4">
                        <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Pemasukan {tahun} per Sumber</h2>
                        <p className="mt-0.5 text-xs text-stone-400">Hanya pembayaran yang sudah disetujui.</p>
                    </div>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        {SUMBER.map((s) => (
                            <div key={s.key} className="rounded-xl bg-cream-100 p-3.5 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                <p className="text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">{s.label}</p>
                                <p className={`mt-1.5 break-words text-lg font-bold leading-tight ${s.warna}`}>
                                    {formatCurrency(pemasukan[s.key]?.nominal ?? 0)}
                                </p>
                                <p className="mt-1 text-xs text-stone-400">
                                    {pemasukan[s.key]?.jumlah ?? 0} transaksi · {s.ket}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="mb-5 grid grid-cols-2 gap-3 sm:gap-4">
                <div className={kartu}>
                    <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">Piutang Berjalan</p>
                    <p className="mt-1.5 break-words text-base font-bold leading-tight text-amber-600 dark:text-amber-400 sm:mt-2 sm:text-xl">
                        {formatCurrency(totalTunggakan)}
                    </p>
                    <p className="mt-1 text-xs text-stone-400">belum terbayar, belum dibatalkan</p>
                </div>
                <div className={kartu}>
                    <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 sm:text-[11px]">Dibebaskan {tahun}</p>
                    <p className="mt-1.5 break-words text-base font-bold leading-tight text-stone-700 dark:text-stone-200 sm:mt-2 sm:text-xl">
                        {formatCurrency(totalDibebaskan)}
                    </p>
                    <p className="mt-1 text-xs text-stone-400">nilai tagihan yang dibatalkan</p>
                </div>
            </div>

            <div className={`mb-5 ${kartu}`}>
                <div className="mb-4">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Tunggakan per Kelas TK</h2>
                    <p className="mt-0.5 text-xs text-stone-400">Diurutkan dari nominal terbesar — di situlah tindakan paling berarti.</p>
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th className={th}>Kelas</th>
                                <th className={thNum}>Menunggak</th>
                                <th className={th}>Porsi</th>
                                <th className={thNum}>Nominal</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {kelas.length > 0 ? kelas.map((k) => (
                                <tr key={k.nama} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-3 py-3 text-sm font-medium text-stone-800 dark:text-cream-100">{k.nama}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right text-sm text-stone-600 dark:text-stone-300">
                                        {k.nunggak} <span className="text-stone-400">/ {k.total}</span>
                                    </td>
                                    <td className="px-3 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="h-1.5 w-full max-w-[120px] overflow-hidden rounded-full bg-cream-200 dark:bg-coal-600">
                                                <div className={`h-full rounded-full ${warnaBatang(k.persen)}`}
                                                    style={{ width: `${Math.min(k.persen, 100)}%` }} />
                                            </div>
                                            <span className={`shrink-0 text-xs font-semibold ${warnaPersen(k.persen)}`}>{k.persen}%</span>
                                        </div>
                                    </td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right text-sm font-semibold text-stone-700 dark:text-stone-200">
                                        {formatCurrency(k.nominal)}
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan={4} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                                    Tidak ada tunggakan di kelas mana pun.
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className={kartu}>
                <div className="mb-4">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Keringanan &amp; Pembatalan {tahun}</h2>
                    <p className="mt-0.5 text-xs text-stone-400">
                        Nilai penuh tagihan yang dibatalkan — pembatalan hanya mungkin sebelum ada pembayaran masuk.
                    </p>
                </div>

                <div className="overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600">
                    <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                        <thead className="bg-cream-100 dark:bg-coal-600/40">
                            <tr>
                                <th className={th}>Alasan</th>
                                <th className={thNum}>Jumlah Tagihan</th>
                                <th className={thNum}>Nilai Dibebaskan</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                            {alasan.length > 0 ? alasan.map((a) => (
                                <tr key={a.label} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                    <td className="whitespace-nowrap px-3 py-3 text-sm font-medium text-stone-800 dark:text-cream-100">{a.label}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right text-sm text-stone-600 dark:text-stone-300">{a.jumlah}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right text-sm font-semibold text-stone-700 dark:text-stone-200">{formatCurrency(a.nominal)}</td>
                                </tr>
                            )) : (
                                <tr><td colSpan={3} className="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                                    Belum ada tagihan yang dibatalkan tahun ini.
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className={`mt-5 ${kartu}`}>
                <div className="mb-4">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Beban Gaji {tahun}</h2>
                    <p className="mt-0.5 text-xs text-stone-400">
                        Bagian pemasukan yang terpakai untuk penggajian — dihitung dari batch yang sudah dibayarkan.
                    </p>
                </div>

                {!bebanGaji?.ada_data ? (
                    <p className="py-8 text-center text-sm text-stone-500 dark:text-stone-400">
                        Belum ada batch penggajian untuk {tahun}.
                    </p>
                ) : (
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div className="shrink-0">
                            <p className={`text-3xl font-bold leading-none ${warnaPersen(bebanGaji.persen)}`}>
                                {bebanGaji.persen}%
                            </p>
                            <p className="mt-1.5 text-xs text-stone-400">dari pemasukan</p>
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="h-2 w-full overflow-hidden rounded-full bg-cream-200 dark:bg-coal-600">
                                <div className={`h-full rounded-full ${warnaBatang(bebanGaji.persen)}`}
                                    style={{ width: `${Math.min(bebanGaji.persen, 100)}%` }} />
                            </div>
                            <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-stone-500 dark:text-stone-400">
                                <span>Gaji dibayarkan <strong className="font-semibold text-stone-700 dark:text-stone-200">{formatCurrency(bebanGaji.gaji)}</strong></span>
                                <span>Pemasukan <strong className="font-semibold text-stone-700 dark:text-stone-200">{formatCurrency(bebanGaji.pemasukan)}</strong></span>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <div className={`mt-5 ${kartu}`}>
                <div className="mb-4">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">
                        Serapan Anggaran{rkas?.periode ? ` · ${rkas.periode}` : ''}
                    </h2>
                    <p className="mt-0.5 text-xs text-stone-400">
                        Lima pos dengan serapan tertinggi — yang hampir habis itu yang perlu diputuskan.
                    </p>
                </div>

                {rkas ? (
                    <>
                        <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {[
                                ['Pagu', formatCurrency(rkas.anggaran), 'text-stone-700 dark:text-stone-200'],
                                ['Terserap', formatCurrency(rkas.terserap), 'text-claude-600 dark:text-claude-300'],
                                ['Sisa', formatCurrency(rkas.sisa), 'text-emerald-600 dark:text-emerald-400'],
                                ['Serapan', `${rkas.persen}%`, warnaSerapan(rkas.persen)],
                            ].map(([label, nilai, warna]) => (
                                <div key={label} className="rounded-xl bg-cream-100 p-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                                    <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">{label}</p>
                                    <p className={`mt-1 break-words text-sm font-bold leading-tight ${warna}`}>{nilai}</p>
                                </div>
                            ))}
                        </div>

                        <div className="overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600">
                            <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                                <thead className="bg-cream-100 dark:bg-coal-600/40">
                                    <tr>
                                        <th className={th}>Kegiatan</th>
                                        <th className={thNum}>Pagu</th>
                                        <th className={thNum}>Terpakai</th>
                                        <th className={thNum}>Serapan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                    {rkas.pos.map((p) => (
                                        <tr key={p.kode} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                            <td className="px-3 py-3 text-sm">
                                                <span className="font-medium text-stone-800 dark:text-cream-100">{p.nama}</span><br />
                                                <span className="text-xs text-stone-400">{p.kode}</span>
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-right text-sm text-stone-500 dark:text-stone-400">{formatCurrency(p.pagu)}</td>
                                            <td className="whitespace-nowrap px-3 py-3 text-right text-sm text-stone-600 dark:text-stone-300">{formatCurrency(p.realisasi)}</td>
                                            <td className={`whitespace-nowrap px-3 py-3 text-right text-sm font-semibold ${warnaSerapan(p.persen)}`}>{p.persen}%</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <p className="py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                        Belum ada periode RKAS yang difinalkan.
                    </p>
                )}
            </div>

            {/* Blok tahfizh hanya dirender bila modul raport menyala —
                controller mengirim null bila tidak, dan halaman ini tetap
                utuh di instalasi klien yang cuma memakai panel keuangan. */}
            {tahfizh && (
                <div className={`mt-5 ${kartu}`}>
                    <div className="mb-4">
                        <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Ringkasan Tahfizh</h2>
                        <p className="mt-0.5 text-xs text-stone-400">
                            Capaian hafalan terhadap target tahun ajaran berjalan, dan siswa yang berhenti setor.
                        </p>
                    </div>

                    <div className="mb-4 grid grid-cols-3 gap-3">
                        <div className="rounded-xl bg-cream-100 p-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Siswa Aktif</p>
                            <p className="mt-1 text-sm font-bold leading-tight text-stone-700 dark:text-stone-200">{tahfizh.aktif}</p>
                        </div>
                        <div className="rounded-xl bg-cream-100 p-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Rata-rata Capaian</p>
                            <p className="mt-1 text-sm font-bold leading-tight text-claude-600 dark:text-claude-300">{tahfizh.rataCapaian}%</p>
                        </div>
                        <div className="rounded-xl bg-cream-100 p-3 ring-1 ring-cream-300 dark:bg-coal-600/40 dark:ring-coal-600">
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Vakum ≥ {tahfizh.hariVakum} Hari</p>
                            <p className={`mt-1 text-sm font-bold leading-tight ${tahfizh.jumlahVakum > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-stone-700 dark:text-stone-200'}`}>
                                {tahfizh.jumlahVakum} <span className="font-normal text-stone-400">/ {tahfizh.aktif}</span>
                            </p>
                        </div>
                    </div>

                    {tahfizh.vakum.length > 0 ? (
                        <div className="overflow-x-auto rounded-xl ring-1 ring-cream-300 dark:ring-coal-600">
                            <table className="min-w-full divide-y divide-cream-300 dark:divide-coal-600">
                                <thead className="bg-cream-100 dark:bg-coal-600/40">
                                    <tr>
                                        <th className={th}>Siswa</th>
                                        <th className={th}>Setoran Terakhir</th>
                                        <th className={thNum}>Lama Vakum</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-cream-200 bg-white dark:divide-coal-600 dark:bg-coal-700">
                                    {tahfizh.vakum.map((v) => (
                                        <tr key={v.nama} className="transition hover:bg-cream-50 dark:hover:bg-coal-600/40">
                                            <td className="whitespace-nowrap px-3 py-3 text-sm font-medium text-stone-800 dark:text-cream-100">{v.nama}</td>
                                            <td className="whitespace-nowrap px-3 py-3 text-sm text-stone-500 dark:text-stone-400">
                                                {v.terakhir ?? 'Belum pernah setor'}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-right text-sm font-semibold text-amber-600 dark:text-amber-400">
                                                {v.hari !== null ? `${v.hari} hari` : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="py-8 text-center text-sm text-stone-500 dark:text-stone-400">
                            Semua siswa aktif setor dalam {tahfizh.hariVakum} hari terakhir.
                        </p>
                    )}

                    {tahfizh.jumlahVakum > tahfizh.vakum.length && (
                        <p className="mt-3 text-xs text-stone-400">
                            Menampilkan {tahfizh.vakum.length} dari {tahfizh.jumlahVakum} siswa — yang paling lama vakum lebih dulu.
                        </p>
                    )}
                </div>
            )}
        </KeuanganLayout>
    );
}
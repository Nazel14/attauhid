import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Identitas({ identitas }) {
    const form = useForm({
        kop_nama:   identitas?.kop_nama ?? '',
        kop_alamat: identitas?.kop_alamat ?? '',
        nama:    identitas?.nama ?? '',
        jabatan: identitas?.jabatan ?? '',
        aktif:   identitas?.aktif ?? false,
        gambar:  null,
        hapus:   false,
    });

    const fileRef = useRef(null);

    // Pratinjau lokal supaya pengguna melihat hasil pindaiannya sebelum
    // menyimpan. Tanpa ini, satu-satunya cara tahu tanda tangannya
    // terbalik atau terpotong adalah mencetak kwitansi sungguhan.
    const [pratinjau, setPratinjau] = useState(null);

    const gambarTampil = form.data.hapus ? null : (pratinjau ?? identitas?.gambar ?? null);

    const pilihBerkas = (e) => {
        const berkas = e.target.files?.[0] ?? null;
        form.setData((d) => ({ ...d, gambar: berkas, hapus: false }));
        setPratinjau(berkas ? URL.createObjectURL(berkas) : null);
    };

    const hapusGambar = () => {
        form.setData((d) => ({ ...d, gambar: null, hapus: true }));
        setPratinjau(null);
        if (fileRef.current) fileRef.current.value = '';
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('keuangan.pengaturan.identitas.update'), {
            preserveScroll: true,
            onSuccess: () => {
                form.setData('hapus', false);
                setPratinjau(null);
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    return (
        <KeuanganLayout>
            <Head title="Identitas & Tanda Tangan" />

            <Link href={route('keuangan.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-stone-600 ring-1 ring-stone-200 transition hover:bg-cream-100 active:scale-95 dark:bg-coal-700 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600 lg:hidden">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </Link>

            <div className="mb-5 sm:mb-6">
                <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">Identitas &amp; Tanda Tangan</h1>
                <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Tanda tangan yang tercetak di setiap kwitansi pembayaran.
                </p>
            </div>

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-5">

                {/* ---------- Kolom kiri: isian ---------- */}
                <div className="space-y-5 lg:col-span-3">
                    <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                        <h2 className="text-sm font-semibold text-stone-800 dark:text-cream-100">Kop kwitansi</h2>
                        <p className="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            Nama lembaga yang tercetak di sebelah logo.
                        </p>

                        <div className="mt-5 space-y-4">
                            <div>
                                <InputLabel value="Nama lembaga" />
                                <TextInput value={form.data.kop_nama}
                                    onChange={(e) => form.setData('kop_nama', e.target.value)}
                                    className="mt-1.5 block w-full"
                                    placeholder="Contoh: Sekolah At Tauhid" />
                                <InputError message={form.errors.kop_nama} className="mt-1.5" />
                            </div>

                            <div>
                                <InputLabel value="Alamat (opsional)" />
                                <TextInput value={form.data.kop_alamat}
                                    onChange={(e) => form.setData('kop_alamat', e.target.value)}
                                    className="mt-1.5 block w-full"
                                    placeholder="Contoh: Jl. Raya Subang No. 12, Subang" />
                                <InputError message={form.errors.kop_alamat} className="mt-1.5" />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                        <h2 className="text-sm font-semibold text-stone-800 dark:text-cream-100">Penanda tangan</h2>
                        <p className="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            Jabatan tercetak di atas tanda tangan, nama di bawah garis.
                        </p>

                        <div className="mt-5 space-y-4">
                            <div>
                                <InputLabel value="Nama" />
                                <TextInput value={form.data.nama}
                                    onChange={(e) => form.setData('nama', e.target.value)}
                                    className="mt-1.5 block w-full"
                                    placeholder="Contoh: KH. Ahmad Fulan, S.Pd.I" />
                                <InputError message={form.errors.nama} className="mt-1.5" />
                            </div>

                            <div>
                                <InputLabel value="Jabatan" />
                                <TextInput value={form.data.jabatan}
                                    onChange={(e) => form.setData('jabatan', e.target.value)}
                                    className="mt-1.5 block w-full"
                                    placeholder="Contoh: Pimpinan Pondok Pesantren" />
                                <InputError message={form.errors.jabatan} className="mt-1.5" />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                        <h2 className="text-sm font-semibold text-stone-800 dark:text-cream-100">Berkas tanda tangan</h2>
                        <p className="mt-1 text-xs leading-relaxed text-stone-500 dark:text-stone-400">
                            PNG berlatar transparan, maksimal 300 KB. JPG tidak diterima karena
                            kotak putihnya akan menutupi garis nama di bawahnya.
                        </p>

                        <input ref={fileRef} type="file" accept="image/png" onChange={pilihBerkas}
                            className="mt-4 block w-full cursor-pointer text-xs text-stone-500 file:mr-3 file:cursor-pointer file:rounded-xl file:border-0 file:bg-claude-500/10 file:px-4 file:py-2.5 file:text-xs file:font-semibold file:text-claude-700 hover:file:bg-claude-500/20 dark:text-stone-400 dark:file:text-claude-300" />
                        <InputError message={form.errors.gambar} className="mt-2" />

                        {/* Thumbnail berkas tersimpan — TIDAK ikut saklar 'aktif'.
                            Preview kwitansi di sebelah kanan meniru hasil cetak, jadi
                            saat saklarnya mati ia memang menampilkan ruang kosong.
                            Tapi orang yang baru mengunggah perlu melihat berkasnya
                            saat itu juga untuk memastikan tidak terbalik atau
                            terpotong — dan itu tidak boleh bergantung pada saklar
                            yang mengatur hal lain. */}
                        {gambarTampil && (
                            <div className="mt-4 flex items-center gap-4">
                                <div className="flex h-16 w-40 shrink-0 items-center justify-center rounded-xl border border-stone-300 bg-white p-2">
                                    <img src={gambarTampil} alt="Tanda tangan tersimpan"
                                        className="max-h-full max-w-full object-contain" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs text-stone-500 dark:text-stone-400">
                                        {pratinjau ? 'Berkas baru — belum disimpan' : 'Tersimpan'}
                                    </p>
                                    <button type="button" onClick={hapusGambar}
                                        className="mt-1 text-xs font-medium text-red-600 underline-offset-2 hover:underline dark:text-red-400">
                                        Hapus tanda tangan
                                    </button>
                                </div>
                            </div>
                        )}

                        <label className="mt-5 flex cursor-pointer items-start gap-3 rounded-xl bg-cream-50 p-3.5 ring-1 ring-stone-200 dark:bg-coal-600/40 dark:ring-coal-600">
                            <input type="checkbox" checked={form.data.aktif}
                                onChange={(e) => form.setData('aktif', e.target.checked)}
                                className="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-stone-300 text-claude-600 focus:ring-claude-500 dark:border-coal-600" />
                            <span className="min-w-0">
                                <span className="block text-sm font-medium text-stone-800 dark:text-cream-100">
                                    Cetak tanda tangan di kwitansi
                                </span>
                                <span className="mt-0.5 block text-xs text-stone-500 dark:text-stone-400">
                                    Bila dimatikan, kwitansi kembali menyediakan ruang kosong untuk
                                    ditandatangani manual. Nama dan jabatan tetap tersimpan.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={() => window.history.back()} className="justify-center">
                            Kembali
                        </SecondaryButton>
                        <PrimaryButton disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </PrimaryButton>
                    </div>
                </div>

                {/* ---------- Kolom kanan: pratinjau ---------- */}
                <div className="lg:col-span-2">
                    <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                        <h2 className="text-sm font-semibold text-stone-800 dark:text-cream-100">Pratinjau di kwitansi</h2>

                        {/* Sengaja dibuat putih dan serif-netral meski aplikasi
                            sedang gelap: yang ditiru di sini adalah kertas
                            cetak, bukan layar. */}
                        <div className="mt-4 rounded-xl border border-stone-300 bg-white p-5">
                            <div className="border-b-2 border-stone-600 pb-2 text-left">
                                <p className="text-sm font-bold text-[#D97757]">{form.data.kop_nama || 'Nama Lembaga'}</p>
                                {form.data.kop_alamat && (
                                    <p className="mt-0.5 text-[9px] text-stone-500">{form.data.kop_alamat}</p>
                                )}
                            </div>

                            <div className="mt-5 text-center">
                            <p className="text-[10px] text-stone-400">2 Oktober 2026</p>
                            <p className="mt-1 text-[11px] text-stone-700">
                                {(form.data.jabatan || 'Penerima')},
                            </p>

                            {form.data.aktif && gambarTampil ? (
                                <img src={gambarTampil} alt="Tanda tangan"
                                    className="mx-auto mt-1 h-12 w-auto object-contain" />
                            ) : (
                                <div className="h-10" />
                            )}

                            <p className="mx-auto inline-block border-t border-stone-600 px-3 pt-1 text-[11px] font-bold text-stone-800">
                                {form.data.nama || 'Bendahara'}
                            </p>
                            <p className="mt-2 text-[8px] text-stone-400">
                                Sah tanpa tanda tangan basah · dicetak 02/10/2026 18:40
                            </p>
                            </div>
                        </div>

                        {!form.data.aktif && (
                            <p className="mt-3 text-xs text-amber-700 dark:text-amber-400">
                                Tanda tangan sedang dimatikan — kwitansi dicetak dengan ruang kosong.
                                {gambarTampil && ' Berkasnya tetap tersimpan.'}
                            </p>
                        )}

                        {identitas?.diubah && (
                            <p className="mt-3 text-[11px] text-stone-400">
                                Terakhir diubah {identitas.diubah}
                            </p>
                        )}
                    </div>
                </div>
            </form>
        </KeuanganLayout>
    );
}
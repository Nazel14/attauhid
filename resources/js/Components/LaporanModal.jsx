import { useRef } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';

/**
 * Props:
 * - open, onClose
 * - reportableType: 'setoran' | 'presensi' | null
 * - reportableId: number | null
 * - konteks: string
 * - judul: string opsional
 * - kategoriOptions: object {val: label} — override daftar kategori
 * - penerimaOptions: array [{id, name}] — bila diisi, muncul dropdown pilih penerima (admin→guru)
 * - kategoriDefault: string — kategori yang langsung terpilih saat modal dibuka
 * - isiAwal: string — teks awal di kolom penjelasan (tinggal dilengkapi user)
 * - templateIsi: (kategori) => string — kalimat pembuka baru saat kategori diganti
 * - contohIsi: string | (kategori) => string — teks contoh (placeholder) di kolom
 *   penjelasan. Bila tidak diisi, contohnya dipilih otomatis menurut peran
 *   pengirim dan jenis data (lihat contohBawaan di bawah).
 *
 * Catatan: kategoriDefault & isiAwal hanya dibaca sekali saat komponen mount.
 * Bila nilainya berubah tanpa remount, beri prop `key` yang berbeda di pemanggil.
 */
export default function LaporanModal({
    open, onClose,
    reportableType = null, reportableId = null,
    konteks = '', judul = 'Laporkan Kesalahan Data',
    kategoriOptions = null, penerimaOptions = null,
    kategoriDefault = '', isiAwal = '', templateIsi = null,
    contohIsi = null,
}) {
    const { auth } = usePage().props;
    const rolePrefix =
        auth.user.role === 'guru' ? 'guru' :
        auth.user.role === 'santri' ? 'santri' : 'admin';
    const rp = (name, params) => route(`${rolePrefix}.laporan.${name}`, params);

    const { data, setData, post, processing, errors, reset } = useForm({
        reportable_type: reportableType,
        reportable_id: reportableId,
        penerima_id: '',
        kategori: kategoriDefault,
        isi: isiAwal,
    });

    // Template terakhir yang kita tulis sendiri. Dipakai untuk membedakan
    // "teks bawaan yang belum disentuh" dari "kalimat yang sudah diketik
    // user" — yang kedua tidak boleh ditimpa saat kategori diganti.
    const templateTerakhir = useRef(isiAwal);

    if (!open) return null;

    // Selama laporan dikirim, modal tidak boleh ditutup (Escape / klik latar)
    // supaya pengguna tidak mengira laporannya batal.
    const tutup = () => { if (!processing) onClose(); };

    const DEFAULT_KATEGORI =
        reportableType === 'setoran'
            ? { halaman_salah: 'Halaman Salah', nilai_salah: 'Nilai Salah', tanggal_salah: 'Tanggal Salah', lainnya: 'Lainnya' }
            : reportableType === 'presensi'
            ? { status_presensi_salah: 'Status Presensi Salah', sesi_salah: 'Sesi Salah', tanggal_salah: 'Tanggal Salah', lainnya: 'Lainnya' }
            : { data_master: 'Data Master', lainnya: 'Lainnya' };

    const KATEGORI = kategoriOptions ?? DEFAULT_KATEGORI;

    /* Contoh isi (placeholder) yang sesuai dengan SIAPA yang menulis dan
       KE SIAPA. Dulu semua orang melihat contoh salah nama milik siswa —
       termasuk admin yang sedang menghubungi guru. */
    const contohBawaan = () => {
        if (reportableType === 'setoran') return 'Contoh: setoran tanggal 12 November tercatat halaman 5, seharusnya halaman 6.';
        if (reportableType === 'presensi') return 'Contoh: tanggal 12 November saya hadir, tetapi tercatat alpha.';
        if (auth.user.role === 'santri') return 'Contoh: nama saya tertulis Yasir Zaenur, seharusnya Yasir Zainur.';
        if (auth.user.role === 'guru') {
            return penerimaOptions
                ? 'Contoh: setoran hafalan minggu ini belum masuk, mohon disetorkan sebelum hari Jumat.'
                : 'Contoh: siswa atas nama Ahmad belum terdaftar di kelas saya.';
        }
        return 'Contoh: mohon cek kembali data setoran Kelompok A tanggal 12 November, ada yang belum tercatat.';
    };
    const contoh =
        (typeof contohIsi === 'function' ? contohIsi(data.kategori) : contohIsi) || contohBawaan();

    const gantiKategori = (val) => {
        if (!templateIsi) {
            setData('kategori', val);
            return;
        }

        const baru = templateIsi(val);
        const belumDiketik = data.isi === templateTerakhir.current || !data.isi.trim();

        if (baru && belumDiketik) {
            templateTerakhir.current = baru;
            setData({ ...data, kategori: val, isi: baru });
            return;
        }

        setData('kategori', val);
    };

    const submit = (e) => {
        e.preventDefault();
        post(rp('store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    const inputCls =
        'w-full rounded-xl border-0 bg-white px-3 py-2.5 text-sm text-stone-800 ring-1 ring-stone-200 focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700';

    /* Dropdown: panah bawaan browser dimatikan (appearance-none) lalu
       diganti ikon sendiri. Panah bawaan menempel di tepi kanan, warnanya
       tidak ikut mode gelap, dan bentuknya beda di tiap browser.
       pr-10 memberi ruang supaya teks pilihan tidak tertimpa ikon.
       Latar & warna teks <option> ditulis juga — di Windows daftar pilihan
       dropdown kalau tidak diberi warna tampil putih di mode gelap. */
    // Saat belum ada pilihan, teksnya dibuat abu-abu seperti placeholder.
    // Warna teks diganti (bukan ditambah) supaya tidak bentrok dengan
    // text-stone-800 milik inputCls — dua kelas warna bersaing, yang menang
    // ditentukan urutan CSS Tailwind, bukan urutan di className.
    const selectCls = (terisi) =>
        `${terisi ? inputCls : inputCls.replace('text-stone-800', 'text-stone-400').replace('dark:text-cream-100', 'dark:text-stone-500')} cursor-pointer appearance-none pr-10 [&>option]:bg-white [&>option]:text-stone-800 dark:[&>option]:bg-coal-800 dark:[&>option]:text-cream-100`;

    const Panah = () => (
        <svg className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    );

    return (
        // Memakai <Modal> bersama, jadi otomatis dapat: tutup dengan Escape,
        // latar diburamkan, halaman di belakang terkunci, dan atribut dialog
        // untuk pembaca layar. Sebelumnya semua itu tidak ada di modal ini.
        <Modal show={open} onClose={tutup} closeable={!processing} maxWidth="md" label={judul}>
            <div className="overflow-y-auto p-5">
                <h2 className="text-base font-semibold text-stone-800 dark:text-cream-100">{judul}</h2>
                {konteks && (
                    <p className="mt-1 text-sm text-stone-500 dark:text-coal-300">{konteks}</p>
                )}

                <form onSubmit={submit} className="mt-4 space-y-4">
                    {penerimaOptions && (
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-stone-700 dark:text-cream-100">
                                Tujukan Kepada
                            </label>
                            <div className="relative">
                                <select
                                    value={data.penerima_id}
                                    onChange={(e) => setData('penerima_id', e.target.value)}
                                    className={selectCls(!!data.penerima_id)}
                                >
                                    <option value="">Pilih penerima</option>
                                    {penerimaOptions.map((g) => (
                                        <option key={g.id} value={g.id}>{g.name}</option>
                                    ))}
                                </select>
                                <Panah />
                            </div>
                            {errors.penerima_id && <p className="mt-1 text-xs text-red-500">{errors.penerima_id}</p>}
                        </div>
                    )}

                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-stone-700 dark:text-cream-100">
                            Jenis Masalah
                        </label>
                        <div className="relative">
                            <select
                                value={data.kategori}
                                onChange={(e) => gantiKategori(e.target.value)}
                                className={selectCls(!!data.kategori)}
                            >
                                <option value="">Pilih jenis masalah</option>
                                {Object.entries(KATEGORI).map(([val, label]) => (
                                    <option key={val} value={val}>{label}</option>
                                ))}
                            </select>
                            <Panah />
                        </div>
                        {errors.kategori && <p className="mt-1 text-xs text-red-500">{errors.kategori}</p>}
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-stone-700 dark:text-cream-100">
                            Jelaskan Masalahnya
                        </label>
                        <textarea
                            value={data.isi}
                            onChange={(e) => setData('isi', e.target.value)}
                            rows={4}
                            maxLength={2000}
                            placeholder={contoh}
                            className={`${inputCls} resize-none placeholder:text-stone-400 dark:placeholder:text-stone-500`}
                        />
                        <p className="mt-1 text-right text-[11px] tabular-nums text-stone-400 dark:text-stone-500">
                            {data.isi.length}/2000
                        </p>
                        {errors.isi && <p className="mt-1 text-xs text-red-500">{errors.isi}</p>}
                    </div>

                    <div className="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            onClick={tutup}
                            disabled={processing}
                            className="rounded-xl px-4 py-2 text-sm font-medium disabled:opacity-50 text-stone-600 hover:bg-stone-100 dark:text-coal-200 dark:hover:bg-coal-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={processing || !data.kategori || !data.isi.trim() || (penerimaOptions && !data.penerima_id)}
                            className="rounded-xl bg-claude-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-claude-600 disabled:opacity-50"
                        >
                            Kirim Laporan
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
import { useRef } from 'react';
import { useForm, usePage } from '@inertiajs/react';

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

    const DEFAULT_KATEGORI =
        reportableType === 'setoran'
            ? { halaman_salah: 'Halaman Salah', nilai_salah: 'Nilai Salah', tanggal_salah: 'Tanggal Salah', lainnya: 'Lainnya' }
            : reportableType === 'presensi'
            ? { status_presensi_salah: 'Status Presensi Salah', sesi_salah: 'Sesi Salah', tanggal_salah: 'Tanggal Salah', lainnya: 'Lainnya' }
            : { data_master: 'Data Master', lainnya: 'Lainnya' };

    const KATEGORI = kategoriOptions ?? DEFAULT_KATEGORI;

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

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/40" onClick={onClose} />

            <div className="relative w-full max-w-md rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-700">
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
                            <select
                                value={data.penerima_id}
                                onChange={(e) => setData('penerima_id', e.target.value)}
                                className={inputCls}
                            >
                                <option value="">-- Pilih --</option>
                                {penerimaOptions.map((g) => (
                                    <option key={g.id} value={g.id}>{g.name}</option>
                                ))}
                            </select>
                            {errors.penerima_id && <p className="mt-1 text-xs text-red-500">{errors.penerima_id}</p>}
                        </div>
                    )}

                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-stone-700 dark:text-cream-100">
                            Jenis Masalah
                        </label>
                        <select
                            value={data.kategori}
                            onChange={(e) => gantiKategori(e.target.value)}
                            className={inputCls}
                        >
                            <option value="">-- Pilih --</option>
                            {Object.entries(KATEGORI).map(([val, label]) => (
                                <option key={val} value={val}>{label}</option>
                            ))}
                        </select>
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
                            placeholder="Contoh: nama saya tertulis Yasir Zaenur, seharusnya Yasir Zainur…"
                            className={`${inputCls} resize-none placeholder:text-stone-400`}
                        />
                        {errors.isi && <p className="mt-1 text-xs text-red-500">{errors.isi}</p>}
                    </div>

                    <div className="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-xl px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-coal-200 dark:hover:bg-coal-700"
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
        </div>
    );
}
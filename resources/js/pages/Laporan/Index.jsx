import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';
import LaporanModal from '@/Components/LaporanModal';

/* ===== Toast (gaya Kelola Guru) ===== */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const XIcon = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;

function Toast({ notif, onClose, duration = 5000 }) {
    if (!notif) return null;
    const success = notif.type === 'success';
    return (
        <>
            <div
                key={notif.id}
                className="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 z-[300] overflow-hidden rounded-2xl border border-cream-300 dark:border-coal-600 bg-white/95 dark:bg-coal-700/95 shadow-2xl backdrop-blur-md"
                style={{ animation: 'toastIn .3s cubic-bezier(.21,1.02,.73,1)' }}
                role="alert"
            >
                <div className="flex items-start gap-3 p-3.5">
                    <span className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full ${success ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300'}`}>
                        {success ? <CheckCircle className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
                    </span>
                    <div className="min-w-0 flex-1 pt-0.5">
                        <p className={`text-xs font-bold ${success ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'}`}>
                            {success ? 'Berhasil' : 'Gagal'}
                        </p>
                        <p className="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-300">{notif.message}</p>
                    </div>
                    <button onClick={onClose} className="flex-shrink-0 rounded-lg p-1 text-stone-400 hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-200 transition">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                <div className="h-1 w-full bg-cream-300 dark:bg-coal-700">
                    <div className={`h-full ${success ? 'bg-emerald-500' : 'bg-red-500'}`}
                        style={{ animation: `toastBar ${duration}ms linear forwards` }} />
                </div>
            </div>
            <style>{`
                @keyframes toastIn { from { opacity: 0; transform: translateY(-14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
                @keyframes toastBar { from { width: 100%; } to { width: 0%; } }
            `}</style>
        </>
    );
}

const STATUS_BADGE = {
    terbuka: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    dibalas: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    selesai: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
};

const STATUS_LABEL = {
    terbuka: 'Belum Dibalas',
    dibalas: 'Sudah Dibalas',
    selesai: 'Selesai',
};

const FILTERS = ['semua', 'terbuka', 'dibalas', 'selesai'];

// Semua nilai yang sah untuk parameter ?status= — termasuk 'dibisukan' yang
// bukan status laporan, melainkan penanda lawan bicara sedang diblokir.
const SEMUA_STATUS = ['terbuka', 'dibalas', 'selesai', 'dibisukan'];

/* Contoh isi (placeholder) per jenis masalah, untuk tiap arah pesan.
   Kunci '' dipakai sebelum jenis masalah dipilih. */
const CONTOH = {
    guruKeAdmin: {
        '': 'Contoh: siswa atas nama Ahmad belum terdaftar di kelas saya.',
        data_master: 'Contoh: siswa atas nama Ahmad belum terdaftar di kelas saya.',
        lainnya: 'Tuliskan pesan untuk admin…',
    },
    guruKeSiswa: {
        '': 'Tuliskan pesan untuk siswa…',
        pengingat_setoran: 'Contoh: setoran hafalan minggu ini belum masuk, mohon disetorkan sebelum hari Jumat.',
        pengingat_murajaah: 'Contoh: jangan lupa murajaah surat An-Naba sebelum setoran berikutnya.',
        teguran_presensi: 'Contoh: kamu sudah tiga kali tidak hadir minggu ini, ada kendala apa?',
        klarifikasi_data: 'Contoh: mohon konfirmasi, apakah nama lengkapmu sudah benar di rapor?',
        lainnya: 'Tuliskan pesan untuk siswa…',
    },
    adminKeGuru: {
        '': 'Tuliskan pesan untuk guru…',
        koordinasi: 'Contoh: rapat evaluasi tahfizh dipindah ke hari Kamis pukul 13.00.',
        data_master: 'Contoh: mohon cek kembali data setoran Kelompok A tanggal 12 November, ada yang belum tercatat.',
        lainnya: 'Tuliskan pesan untuk guru…',
    },
    siswaKeGuru: {
        '': 'Contoh: nama saya tertulis Yasir Zaenur, seharusnya Yasir Zainur.',
        nama_salah: 'Contoh: nama saya tertulis Yasir Zaenur, seharusnya Yasir Zainur.',
        gender_salah: 'Contoh: jenis kelamin saya tertulis perempuan, seharusnya laki-laki.',
        nis_salah: 'Contoh: NIS saya tertulis 2024015, seharusnya 2024051.',
        kelas_salah: 'Contoh: saya tercatat di Kelompok A, seharusnya Kelompok B.',
        lainnya: 'Jelaskan data yang salah dan seharusnya seperti apa…',
    },
};
const contohDari = (peta) => (kategori) => peta[kategori] ?? peta[''];

const fmtTgl = (d) => {
    try { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }
    catch { return d; }
};

function LaporanCard({ item, authUserId, rp, kategori }) {
    const lawan = item.pelapor.id === authUserId ? item.penerima : item.pelapor;

    // Arah percakapan. Hanya bermakna bila user memang terlibat — admin yang
    // sekadar memantau thread orang lain tidak punya "masuk" atau "terkirim",
    // jadi untuk dia panahnya disembunyikan dan format "A → B" yang dipakai.
    const terlibat = item.pelapor.id === authUserId || item.penerima.id === authUserId;
    const masuk = terlibat && item.penerima.id === authUserId;

    // Kartu ini adalah <Link>, jadi klik tombol harus dihentikan agar tidak
    // ikut membuka thread.
    const hapus = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (confirm('Hapus laporan ini beserta seluruh isinya? Tindakan ini permanen.')) {
            router.delete(rp('destroy', item.id), { preserveScroll: true });
        }
    };

    // Kartu dinaikkan satu tingkat dari latar (coal-700, bukan coal-800) supaya
    // tidak menyatu dengan background di mode gelap. Pesan masuk diberi rona
    // claude agar arah percakapan terbaca tanpa harus melihat panahnya.
    const kartuCls = masuk
        ? 'bg-claude-50 ring-claude-200 dark:bg-claude-500/10 dark:ring-claude-500/25'
        : 'bg-white ring-cream-300 dark:bg-coal-700 dark:ring-coal-600';

    return (
        <Link
            href={rp('show', item.id)}
            className={`block rounded-2xl p-4 shadow-sm ring-1 transition hover:ring-claude-500/40 dark:hover:ring-claude-500/40 ${kartuCls}`}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="inline-flex items-center gap-1.5 font-medium text-stone-800 dark:text-cream-100">
                            {terlibat && (
                                <span
                                    title={masuk ? 'Pesan masuk' : 'Pesan terkirim'}
                                    className={`inline-flex h-4 w-4 shrink-0 items-center justify-center ${
                                        masuk
                                            ? 'text-claude-500'
                                            : 'text-stone-400 dark:text-stone-500'
                                    }`}
                                >
                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                                        {masuk ? (
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m0 0l-6-6m6 6l6-6" />
                                        ) : (
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 20V4m0 0l-6 6m6-6l6 6" />
                                        )}
                                    </svg>
                                </span>
                            )}
                            {terlibat ? lawan.name : `${item.pelapor.name} → ${item.penerima.name}`}
                        </span>
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_BADGE[item.status]}`}>
                            {STATUS_LABEL[item.status]}
                        </span>
                        {item.dibisukan_at && (
                            <span className="rounded-full bg-cream-300 px-2.5 py-0.5 text-xs font-medium text-stone-600 dark:bg-coal-600 dark:text-stone-300">
                                Dibisukan
                            </span>
                        )}
                        {item.unread_count > 0 && (
                            <span className="rounded-full bg-claude-500 px-2 py-0.5 text-xs font-semibold text-white">
                                {item.unread_count}
                            </span>
                        )}
                    </div>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        {kategori[item.kategori] ?? item.kategori}
                        {item.snapshot?.tanggal && ` · ${fmtTgl(item.snapshot.tanggal)}`}
                    </p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                    <span className="text-xs text-stone-400 dark:text-stone-500">
                        {new Date(item.updated_at).toLocaleDateString('id-ID', {
                            day: 'numeric', month: 'short',
                        })}
                    </span>
                    {item.bisa_hapus && (
                        <button
                            type="button"
                            onClick={hapus}
                            title="Hapus laporan"
                            className="rounded-lg p-1.5 text-stone-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    )}
                </div>
            </div>
        </Link>
    );
}

export default function Index({ laporan, filter, kategori, guruList = [], santriList = [] }) {
    const { auth, flash = {} } = usePage().props;

    const NOTIF_DURATION = 5000;
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success || flash?.error) {
            setNotif({ message: flash.success || flash.error, type: flash.success ? 'success' : 'error', id: Date.now() });
            const t = setTimeout(() => setNotif(null), NOTIF_DURATION);
            return () => clearTimeout(t);
        }
    }, [flash]);

    const rolePrefix =
        auth.user.role === 'guru' ? 'guru' :
        auth.user.role === 'santri' ? 'santri' : 'admin';
    const rp = (name, params) => route(`${rolePrefix}.laporan.${name}`, params);

    const Layout =
        auth.user.role === 'guru' ? GuruAuthenticatedLayout :
        auth.user.role === 'santri' ? SantriAuthenticatedLayout :
        AuthenticatedLayout;

    const isGuru = auth.user.role === 'guru';
    const isSantri = auth.user.role === 'santri';
    const isAdmin = !isGuru && !isSantri;
    const [modalAdmin, setModalAdmin] = useState(false); // dipakai ketiga role sebagai "modal hubungi"
    const [modalSantri, setModalSantri] = useState(false); // khusus guru → santri

    // Pemisahan untuk admin berdasarkan ROLE, bukan reportable_type.
    // Alasannya: guru→santri juga tanpa objek, jadi kalau dipisah pakai
    // reportable_type dia ikut masuk ke "Pesan dari Guru".
    const libatSantri = (l) => l.pelapor?.role === 'santri' || l.penerima?.role === 'santri';

    const dariGuru   = isAdmin ? laporan.data.filter((l) => !libatSantri(l)) : [];
    const monitoring = isAdmin ? laporan.data.filter((l) => libatSantri(l)) : [];

    const [cari, setCari] = useState(filter.q ?? '');

    // Satu pintu untuk status + pencarian, supaya keduanya tidak saling menghapus.
    const terapkan = ({ status = filter.status, q = cari }) => {
        const params = {};
        if (status && status !== 'semua') params.status = status;
        if (q && q.trim()) params.q = q.trim();

        router.get(rp('index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const setFilter = (status) => terapkan({ status });

    // Live search dengan debounce 300ms. Tanpa jeda ini, mengetik "tang"
    // memicu 4 request beruntun yang bisa saling mendahului dan menampilkan
    // hasil dari kata kunci yang sudah usang.
    const pertamaKali = useRef(true);

    useEffect(() => {
        // Lewati render pertama, jika tidak halaman langsung request ulang
        // begitu dibuka padahal belum ada yang diketik.
        if (pertamaKali.current) {
            pertamaKali.current = false;
            return;
        }

        // Sudah sinkron dengan server (mis. setelah navigasi) — tak perlu request.
        if (cari.trim() === (filter.q ?? '')) return;

        const timer = setTimeout(() => terapkan({ q: cari }), 300);
        return () => clearTimeout(timer);
    }, [cari]);

    const aktif = (f) =>
        filter.status === f || (f === 'semua' && !SEMUA_STATUS.includes(filter.status));

    return (
        <Layout>
            <Head title="Komplain" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            <div className="mx-auto max-w-6xl px-3 sm:px-4 py-5">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-claude-500/10 ring-1 ring-claude-500/20">
                        <svg className="h-5 w-5 text-claude-500" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 3v18M3 4.5h12.75l-2.25 3 2.25 3H3" />
                        </svg>
                    </div>
                    <div>
                        <h1 className="text-lg font-semibold text-stone-800 dark:text-cream-100">Laporan</h1>
                        <p className="text-sm text-stone-500 dark:text-stone-400">
                            Komplain & koreksi data
                        </p>
                    </div>
                    <div className="ml-auto flex flex-wrap justify-end gap-2">
                        {isGuru && (
                            <button
                                onClick={() => setModalSantri(true)}
                                className="inline-flex items-center gap-2 rounded-xl border border-claude-300 bg-white px-4 py-2.5 text-sm font-semibold text-claude-600 shadow-sm transition hover:bg-claude-50 active:scale-95 dark:border-claude-500/40 dark:bg-coal-600/40 dark:text-claude-400 dark:hover:bg-claude-500/10"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Hubungi Siswa
                            </button>
                        )}
                        <button
                            onClick={() => setModalAdmin(true)}
                            className="inline-flex items-center gap-2 rounded-xl bg-claude-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-claude-600 active:scale-95"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                            {isGuru ? 'Hubungi Admin' : 'Hubungi Guru'}
                        </button>
                    </div>
                </div>

                {/* Pencarian — live, hasil menyaring saat mengetik */}
                <div className="mb-3">
                    <div className="relative">
                        <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                        <input
                            type="text"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') { e.preventDefault(); terapkan({ q: cari }); }
                                if (e.key === 'Escape') setCari('');
                            }}
                            placeholder="Cari nama atau isi pesan…"
                            className="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-9 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:ring-2 focus:ring-claude-500 dark:bg-coal-600/40 dark:text-cream-100 dark:ring-coal-600"
                        />
                        {cari && (
                            <button
                                type="button"
                                onClick={() => setCari('')}
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        )}
                    </div>
                </div>

                {/* Filter status */}
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    {FILTERS.map((f) => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`rounded-full px-4 py-1.5 text-sm font-medium ring-1 transition ${
                                aktif(f)
                                    ? 'bg-claude-500 text-white ring-claude-500'
                                    : 'bg-white text-stone-600 ring-cream-300 hover:bg-cream-100 dark:bg-coal-600/40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                            }`}
                        >
                            {f === 'semua' ? 'Semua' : STATUS_LABEL[f]}
                        </button>
                    ))}

                    {/* Dipisah garis karena ini sumbu berbeda: kondisi USER,
                        bukan status laporan. Santri tidak perlu melihatnya. */}
                    {!isSantri && (
                        <>
                            <span className="mx-1 h-5 w-px bg-cream-300 dark:bg-coal-700" />
                            <button
                                onClick={() => setFilter('dibisukan')}
                                className={`inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium ring-1 transition ${
                                    aktif('dibisukan')
                                        ? 'bg-coal-500 text-white ring-coal-500'
                                        : 'bg-white text-stone-600 ring-cream-300 hover:bg-cream-100 dark:bg-coal-600/40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                }`}
                            >
                                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                Dibisukan
                            </button>
                        </>
                    )}
                </div>

                {/* Daftar laporan */}
                {laporan.data.length === 0 ? (
                    <div className="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-cream-300 dark:bg-coal-700 dark:ring-coal-600">
                        <p className="text-sm text-stone-500 dark:text-stone-400">
                            {filter.q
                                ? `Tidak ada laporan yang cocok dengan "${filter.q}".`
                                : filter.status === 'dibisukan'
                                ? 'Tidak ada percakapan yang sedang dibisukan.'
                                : 'Belum ada laporan.'}
                        </p>
                    </div>
                ) : isAdmin ? (
                    <div className="space-y-6">
                        {/* Section 1: pesan guru ke admin */}
                        {dariGuru.length > 0 && (
                            <div>
                                <p className="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">
                                    Pesan dari Guru
                                </p>
                                <div className="space-y-3">
                                    {dariGuru.map((item) => (
                                        <LaporanCard key={item.id} item={item} authUserId={auth.user.id} rp={rp} kategori={kategori} />
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Section 2: monitoring santri ↔ guru */}
                        {monitoring.length > 0 && (
                            <div>
                                <p className="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">
                                    Monitoring Siswa ↔ Guru
                                </p>
                                <div className="space-y-3">
                                    {monitoring.map((item) => (
                                        <LaporanCard key={item.id} item={item} authUserId={auth.user.id} rp={rp} kategori={kategori} />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="space-y-3">
                        {laporan.data.map((item) => (
                            <LaporanCard key={item.id} item={item} authUserId={auth.user.id} rp={rp} kategori={kategori} />
                        ))}
                    </div>
                )}

                {/* Pagination sederhana */}
                {laporan.links && laporan.last_page > 1 && (    
                    <div className="mt-6 flex flex-wrap justify-center gap-1">
                        {laporan.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`rounded-lg px-3 py-1.5 text-sm ring-1 ${
                                        link.active
                                            ? 'bg-claude-500 text-white ring-claude-500'
                                            : 'bg-white text-stone-600 ring-cream-300 hover:bg-cream-100 dark:bg-coal-600/40 dark:text-stone-300 dark:ring-coal-600'
                                    }`}
                                />
                            ) : (
                                <span
                                    key={i}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className="rounded-lg px-3 py-1.5 text-sm text-stone-400 dark:text-stone-600"
                                />
                            )
                        )}
                    </div>
                )}
            </div>

            {modalAdmin && isGuru && (
                <LaporanModal
                    open={true}
                    onClose={() => setModalAdmin(false)}
                    judul="Hubungi Admin"
                    konteks="Pesan ke Admin"
                    kategoriOptions={{ data_master: 'Data Master', lainnya: 'Lainnya' }}
                    contohIsi={contohDari(CONTOH.guruKeAdmin)}
                />
            )}
            {modalSantri && isGuru && (
                <LaporanModal
                    open={true}
                    onClose={() => setModalSantri(false)}
                    judul="Hubungi Siswa"
                    konteks="Kirim pesan ke siswa bimbingan Anda"
                    penerimaOptions={santriList}
                    kategoriOptions={{
                        pengingat_setoran: 'Pengingat Setoran',
                        pengingat_murajaah: 'Pengingat Murajaah',
                        teguran_presensi: 'Teguran Presensi',
                        klarifikasi_data: 'Klarifikasi Data',
                        lainnya: 'Lainnya',
                    }}
                    contohIsi={contohDari(CONTOH.guruKeSiswa)}
                />
            )}
            {modalAdmin && isAdmin && (
                <LaporanModal
                    open={true}
                    onClose={() => setModalAdmin(false)}
                    judul="Hubungi Guru"
                    konteks="Pesan ke Guru"
                    penerimaOptions={guruList}
                    kategoriOptions={{
                        koordinasi: 'Koordinasi',
                        data_master: 'Data Setoran / Presensi Perlu Dicek',
                        lainnya: 'Lainnya',
                    }}
                    contohIsi={contohDari(CONTOH.adminKeGuru)}
                />
            )}
            {modalAdmin && isSantri && (
                <LaporanModal
                    open={true}
                    onClose={() => setModalAdmin(false)}
                    judul="Hubungi Guru"
                    konteks="Laporkan kesalahan data diri Anda"
                    kategoriOptions={{
                        nama_salah: 'Nama Salah',
                        gender_salah: 'Jenis Kelamin Salah',
                        nis_salah: 'NIS Salah',
                        kelas_salah: 'Kelas Salah',
                        lainnya: 'Lainnya',
                    }}
                    contohIsi={contohDari(CONTOH.siswaKeGuru)}
                />
            )}
        </Layout>
    );
}
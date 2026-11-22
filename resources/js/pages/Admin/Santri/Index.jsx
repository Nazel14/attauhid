import { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/* ============================================================
 * ICONS (inline, tanpa dependency)
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const XIcon = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const ArrowRightIcon = (p) => <I {...p} d={<path d="M14 5l7 7m0 0l-7 7m7-7H3" />} />;
const PlusIcon = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const CheckCircle = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const AlertCircle = (p) => <I {...p} d={<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const GraduationIcon = (p) => <I {...p} d={<path d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-9-6.5V15a9 9 0 0018 0v-2.5" />} />;

/* ============================================================
 * SUB-COMPONENTS
 * ============================================================ */
function Toast({ notif, onClose, duration = 10000 }) {
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
                        <p className="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-300" dangerouslySetInnerHTML={{ __html: notif.message }} />
                    </div>
                    <button onClick={onClose} className="flex-shrink-0 rounded-lg p-1 text-stone-400 hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-200 transition">
                        <XIcon className="w-4 h-4" />
                    </button>
                </div>
                <div className="h-1 w-full bg-cream-300 dark:bg-coal-600">
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

/* Preset visual per jenis kelas (gender_restriction).
 *
 * Kunci objek ini ('campuran', 'ikhwan', 'akhwat') adalah nilai kolom
 * gender_restriction di database — JANGAN diubah. Yang boleh diganti hanya
 * `label`, yaitu tulisan yang dilihat pengguna.
 *
 * `tint` dan `accent` SENGAJA SAMA untuk ketiganya, supaya warna sekolah
 * tetap dominan. Gender cukup terbaca dari lencana kecil di pojok.
 */
const TINT_NETRAL = 'bg-cream-100 dark:bg-coal-600/40';
const ANGKA_HIJAU = 'text-claude-600 dark:text-claude-400';

const GENDER_THEME = {
    campuran: { tint: TINT_NETRAL, accent: ANGKA_HIJAU, badge: 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:border-violet-500/25', dot: 'bg-violet-500', label: 'Campuran' },
    ikhwan:   { tint: TINT_NETRAL, accent: ANGKA_HIJAU, badge: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/25',             dot: 'bg-blue-500',   label: 'Laki-laki' },
    akhwat:   { tint: TINT_NETRAL, accent: ANGKA_HIJAU, badge: 'bg-pink-100 text-pink-700 border-pink-200 dark:bg-pink-500/15 dark:text-pink-300 dark:border-pink-500/25',             dot: 'bg-pink-500',   label: 'Perempuan' },
};

/* Kotak rincian gender — tampil di SEMUA kartu supaya tinggi kartu seragam.
 *
 * Dulu kotak ini hanya muncul di kelas campuran. Karena grid menyamakan
 * tinggi kartu dalam satu baris, kartu kelas khusus jadi punya ruang kosong
 * besar di atas tombol.
 *
 * Di kelas khusus, gender yang bukan peruntukannya diredupkan ("—"). Kalau
 * ternyata berisi siswa, kotaknya ditandai kuning: ada siswa salah kelas.
 */
const KOTAK_GENDER = [
    {
        key: 'ikhwan', label: 'Laki-laki', field: 'laki_laki',
        warna: 'border-blue-200/70 bg-blue-50 dark:border-blue-500/20 dark:bg-blue-500/10',
        teks: 'text-blue-500 dark:text-blue-400',
        angka: 'text-blue-600 dark:text-blue-300',
    },
    {
        key: 'akhwat', label: 'Perempuan', field: 'perempuan',
        warna: 'border-pink-200/70 bg-pink-50 dark:border-pink-500/20 dark:bg-pink-500/10',
        teks: 'text-pink-500 dark:text-pink-400',
        angka: 'text-pink-600 dark:text-pink-300',
    },
];

function KelasCard({ kelas }) {
    const jenis = kelas.gender_restriction;
    const theme = GENDER_THEME[jenis] || GENDER_THEME.campuran;
    const kelasKhusus = jenis === 'ikhwan' || jenis === 'akhwat';

    const kotak = KOTAK_GENDER.map((b) => {
        const nilai = Number(kelas[b.field] ?? 0);
        const diLuar = kelasKhusus && jenis !== b.key;
        return { ...b, nilai, diLuar, salah: diLuar && nilai > 0 };
    });
    const adaSalah = kotak.some((b) => b.salah);

    return (
        <div className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-cream-300 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-black/5 dark:bg-coal-700 dark:ring-coal-600 dark:hover:shadow-black/30">
            {/* Header — tint netral */}
            <div className={`px-4 pt-4 pb-3.5 ${theme.tint}`}>
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <h3 className="truncate text-base font-bold text-stone-900 dark:text-stone-100" title={kelas.name}>{kelas.name}</h3>
                        <p className="mt-0.5 truncate text-[11px] text-stone-500 dark:text-stone-400" title={kelas.guru_name || ''}>{kelas.guru_name || 'Belum ada guru'}</p>
                    </div>
                    <span className={`inline-flex flex-shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold ${theme.badge}`}>
                        <span className={`h-1.5 w-1.5 rounded-full ${theme.dot}`} />
                        {theme.label}
                    </span>
                </div>
                <div className="mt-3 flex items-baseline gap-1.5">
                    <span className={`text-3xl font-black ${theme.accent}`}>{kelas.total_santri}</span>
                    <span className="text-xs text-stone-500 dark:text-stone-400">siswa</span>
                </div>
            </div>

            {/* Rincian gender — semua kartu */}
            <div className="px-4 py-3">
                <div className="grid grid-cols-2 gap-2">
                    {kotak.map((b) => {
                        const kotakCls = b.salah
                            ? 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10'
                            : b.diLuar
                                ? 'border-dashed border-cream-300 dark:border-coal-600'
                                : b.warna;
                        const labelCls = b.salah
                            ? 'text-amber-600 dark:text-amber-400'
                            : b.diLuar ? 'text-stone-400 dark:text-stone-500' : b.teks;
                        const angkaCls = b.salah
                            ? 'text-amber-600 dark:text-amber-300'
                            : b.diLuar ? 'text-stone-300 dark:text-stone-600' : b.angka;
                        return (
                            <div key={b.key} className={`rounded-xl border px-3 py-2 text-center ${kotakCls}`}>
                                <p className={`mb-0.5 text-[10px] ${labelCls}`}>{b.label}</p>
                                <p className={`text-lg font-bold ${angkaCls}`}>{b.diLuar && !b.salah ? '—' : b.nilai}</p>
                            </div>
                        );
                    })}
                </div>
                {adaSalah && (
                    <p className="mt-2 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                        Ada siswa yang tidak sesuai jenis kelas ini.
                    </p>
                )}
            </div>

            {/* Footer — mt-auto menahan tombol tetap di dasar kartu */}
            <div className="mt-auto px-4 pb-4 pt-1">
                <Link href={route('admin.santris.detail', kelas.id)}
                    className="flex w-full items-center justify-center gap-1.5 rounded-xl bg-claude-600 py-2.5 text-center text-xs font-bold text-white shadow-sm shadow-claude-500/20 transition hover:bg-claude-500 active:scale-95">
                    Lihat Detail Siswa <ArrowRightIcon className="w-3.5 h-3.5" />
                </Link>
            </div>
        </div>
    );
}

/* ============================================================
 * MAIN PAGE
 * ============================================================ */
export default function Index({ kelasWithStats = [] }) {
    const { flash = {} } = usePage().props;

    const NOTIF_DURATION = 10000;
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success || flash?.updated || flash?.deleted || flash?.error) {
            const type = (flash.success || flash.updated || flash.deleted) ? 'success' : 'error';
            setNotif({ message: flash.success || flash.updated || flash.deleted || flash.error, type, id: Date.now() });
            const t = setTimeout(() => setNotif(null), NOTIF_DURATION);
            return () => clearTimeout(t);
        }
    }, [flash]);

    return (
        <AuthenticatedLayout>
            <Head title="Management Siswa per Kelas" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            {/* Tanpa max-w / mx-auto / padding: <main> di AuthenticatedLayout
                sudah memberi ketiganya. */}
            <div>

                {/* Kembali ke Dashboard — mobile saja */}
                <Link href={route('admin.dashboard')}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-stone-600 shadow-sm transition hover:bg-cream-100 active:scale-95 dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:bg-coal-600">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-6">
                    <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 flex items-center justify-center shadow-lg shadow-claude-500/25 flex-shrink-0">
                        <GraduationIcon className="w-6 h-6 text-white" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100">Management Siswa per Kelas</h1>
                        <p className="text-[11px] text-stone-500 dark:text-stone-500 mt-0.5">Pilih kelas untuk melihat dan mengelola siswa</p>
                    </div>
                </div>

                {/* ===== GRID KELAS CARDS ===== */}
                {kelasWithStats.length === 0 ? (
                    <div className="bg-white dark:bg-coal-700 border border-cream-300 dark:border-coal-600 rounded-2xl py-20 text-center shadow-sm dark:shadow-none">
                        <GraduationIcon className="w-12 h-12 mx-auto mb-4 text-stone-300 dark:text-stone-600" />
                        <p className="text-sm font-semibold text-stone-600 dark:text-stone-400">Belum ada kelas yang tersedia</p>
                        <p className="text-xs text-stone-400 dark:text-stone-600 mt-1.5">Silakan tambahkan kelas terlebih dahulu</p>
                        <Link href={route('admin.kelas.index')} className="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-claude-600 hover:bg-claude-500 text-white text-xs font-bold rounded-xl transition">
                            <PlusIcon className="w-3.5 h-3.5" /> Tambah Kelas
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                        {kelasWithStats.map((kelas) => (
                            <KelasCard key={kelas.id} kelas={kelas} />
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
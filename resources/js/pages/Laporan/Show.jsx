import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';

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

const SNAPSHOT_LABEL = {
    tanggal: 'Tanggal', surat_start: 'Surat/Jilid Awal', surat_end: 'Surat/Jilid Akhir',
    ayat_start: 'Ayat/Hal. Awal', ayat_end: 'Ayat/Hal. Akhir',
    nilai: 'Nilai', kategori_setoran: 'Kategori', sesi: 'Sesi', status: 'Status',
};

// Sama dengan halaman daftar (Index.jsx), supaya status tidak tampil
// sebagai kata mentah "terbuka"/"dibalas".
const STATUS_LABEL = {
    terbuka: 'Belum Dibalas',
    dibalas: 'Sudah Dibalas',
    selesai: 'Selesai',
};
const STATUS_BADGE = {
    terbuka: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    dibalas: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
    selesai: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
};

// Nilai snapshot berupa tanggal (2026-11-12 atau 2026-11-12T00:00:00Z)
// ditampilkan dalam format Indonesia; nilai lain apa adanya.
const fmtNilai = (k, v) => {
    if (v === null || v === undefined || v === '') return '–';
    if (k === 'tanggal' || /^\d{4}-\d{2}-\d{2}/.test(String(v))) {
        const d = new Date(v);
        if (!isNaN(d)) return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    }
    return String(v);
};

export default function Show({ laporan, bisaBalas, bisaTutup, bisaBisukan = false, batasEditMenit = 15 }) {
    const { auth, flash = {} } = usePage().props;
    const threadRef = useRef(null);
    const [editId, setEditId] = useState(null);
    const [editIsi, setEditIsi] = useState('');

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

    const { data, setData, post, processing, reset } = useForm({ isi: '' });

    // Polling: refresh data laporan tiap 10 detik selama thread terbuka & belum selesai
    useEffect(() => {
        if (laporan.status === 'selesai' || laporan.dibisukan_at) return;
        const t = setInterval(() => {
            router.reload({ only: ['laporan'] });
        }, 10000);
        return () => clearInterval(t);
    }, [laporan.status, laporan.dibisukan_at]);

    // Gulir ke pesan terbaru di dalam KOTAK pesan saja. Sebelumnya memakai
    // scrollIntoView, yang ikut menggulir seluruh halaman — di HP header
    // konteks laporan jadi terdorong keluar layar setiap ada pesan baru.
    useEffect(() => {
        const el = threadRef.current;
        if (el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
    }, [laporan.pesan.length]);

    const kirim = (e) => {
        e.preventDefault();
        post(rp('reply', laporan.id), {
            preserveScroll: true,
            onSuccess: () => reset('isi'),
        });
    };

    const tutup = () => {
        if (confirm('Tandai laporan ini selesai? Thread akan dikunci.')) {
            router.post(rp('selesai', laporan.id), {}, { preserveScroll: true });
        }
    };

    // Jendela edit dihitung di klien agar tombol langsung hilang saat lewat
    // batas; server tetap memvalidasi ulang, jadi ini murni kenyamanan UI.
    const masihBisaEdit = (p) =>
        (Date.now() - new Date(p.created_at).getTime()) / 60000 < batasEditMenit;

    const mulaiEdit = (p) => {
        setEditId(p.id);
        setEditIsi(p.isi);
    };

    const simpanEdit = () => {
        if (!editIsi.trim()) return;

        router.put(rp('pesan.update', [laporan.id, editId]), { isi: editIsi }, {
            preserveScroll: true,
            onSuccess: () => { setEditId(null); setEditIsi(''); },
        });
    };

    const hapusPesan = (p) => {
        if (confirm('Hapus pesan ini? Pesan akan diganti keterangan "pesan telah dihapus".')) {
            router.delete(rp('pesan.destroy', [laporan.id, p.id]), { preserveScroll: true });
        }
    };

    const toggleBisukan = () => {
        const pesan = laporan.dibisukan_at
            ? 'Buka kembali percakapan ini? Balasan akan diizinkan lagi.'
            : 'Bisukan percakapan ini? Tidak ada pesan baru yang bisa dikirim dan thread ini berhenti muncul di notifikasi. Bisa dibuka lagi kapan saja.';

        if (confirm(pesan)) {
            router.post(rp('bisukan', laporan.id), {}, { preserveScroll: true });
        }
    };

    return (
        <Layout>
            <Head title="Detail Komplain" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            {/* Tinggi = layar dikurangi header HP (4rem) dan padding <main> di
                layout (py-6 = 3rem). Sebelumnya hanya dikurangi 2.5rem, jadi
                di HP kolom balasan terdorong ke bawah layar. Di desktop
                header HP tidak ada, jadi cukup dikurangi padding saja. */}
            <div className="mx-auto flex h-[calc(100dvh-7rem)] max-w-6xl flex-col px-0 sm:px-4 lg:h-[calc(100dvh-3rem)]">
                {/* Kembali — mobile saja */}
                <Link href={rp('index')}
                    className="mb-4 self-start shrink-0 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-cream-300 dark:border-coal-600 bg-white dark:bg-coal-600/40 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-cream-200 dark:hover:bg-coal-600 active:scale-95">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Kembali
                </Link>
                <Link href={rp('index')}
                    className="mb-4 self-start hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-cream-300 dark:border-coal-600 bg-white dark:bg-coal-600/40 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-cream-200 dark:hover:bg-coal-600 active:scale-95">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Daftar Laporan
                </Link>

                {/* Konteks data yang dikomplain */}
                <div className="mb-4 rounded-2xl bg-claude-100 p-4 ring-1 ring-claude-200 dark:bg-claude-500/10 dark:ring-claude-500/20">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className="break-words text-sm font-medium text-stone-800 dark:text-cream-100">
                                {laporan.pelapor.name} → {laporan.penerima.name}
                            </p>
                            <p className="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                                <span className={`rounded-full px-2.5 py-0.5 font-medium ${STATUS_BADGE[laporan.status] ?? ''}`}>
                                    {STATUS_LABEL[laporan.status] ?? laporan.status}
                                </span>
                                {laporan.selesai_at &&
                                    <span>ditutup {new Date(laporan.selesai_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</span>}
                            </p>
                        </div>
                        <div className="flex shrink-0 flex-wrap justify-end gap-2">
                            {bisaBisukan && (
                                <button
                                    onClick={toggleBisukan}
                                    title={laporan.dibisukan_at ? 'Buka percakapan' : 'Bisukan percakapan ini'}
                                    className={`rounded-xl px-3 py-1.5 text-xs font-medium transition ${
                                        laporan.dibisukan_at
                                            ? 'bg-coal-500 text-white hover:bg-coal-600'
                                            : 'bg-white text-stone-600 ring-1 ring-cream-300 hover:bg-cream-200 dark:bg-coal-600/40 dark:text-stone-300 dark:ring-coal-600 dark:hover:bg-coal-600'
                                    }`}
                                >
                                    {laporan.dibisukan_at ? 'Buka Percakapan' : 'Bisukan'}
                                </button>
                            )}
                            {bisaTutup && (
                                <button
                                    onClick={tutup}
                                    className="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                >
                                    Tandai Selesai
                                </button>
                            )}
                        </div>
                    </div>

                    {laporan.dibisukan_at && (
                        <p className="mt-2 text-xs font-medium text-stone-500 dark:text-stone-400">
                            Percakapan ini dibisukan — terkunci dan tidak muncul di notifikasi.
                        </p>
                    )}

                    {laporan.snapshot && (
                        <div className="mt-3 grid grid-cols-2 gap-2 border-t border-cream-200 pt-3 dark:border-coal-600 sm:grid-cols-3">
                            {Object.entries(laporan.snapshot).map(([k, v]) => (
                                <div key={k}>
                                    <p className="text-[11px] uppercase tracking-wide text-stone-400 dark:text-stone-500">
                                        {SNAPSHOT_LABEL[k] ?? k}
                                    </p>
                                    <p className="break-words text-sm text-stone-700 dark:text-cream-100">{fmtNilai(k, v)}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Thread pesan */}
                <div ref={threadRef} className="mb-4 min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain pr-1">
                    {laporan.pesan.map((p) => {
                        const milikku = p.user_id === auth.user.id;
                        const dihapus = !!p.dihapus_at;
                        const sedangEdit = editId === p.id;

                        // Nisan pesan terhapus — netral, tidak memakai warna
                        // pengirim, supaya jelas ini bukan pesan lagi.
                        if (dihapus) {
                            return (
                                <div key={p.id} className={`flex ${milikku ? 'justify-end' : 'justify-start'}`}>
                                    <div className="max-w-[80%] rounded-2xl bg-cream-200 px-4 py-2.5 ring-1 ring-cream-300 dark:bg-coal-700/70 dark:ring-coal-600">
                                        <p className="flex items-center gap-1.5 text-sm italic text-stone-400 dark:text-stone-500">
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            Pesan ini telah dihapus
                                        </p>
                                    </div>
                                </div>
                            );
                        }

                        return (
                            <div key={p.id} className={`group flex items-center gap-2 ${milikku ? 'justify-end' : 'justify-start'}`}>
                                {/* Aksi muncul saat hover, di sisi luar bubble */}
                                {milikku && !sedangEdit && bisaBalas && (
                                    /* Di HP tidak ada "hover", jadi dulu tombol edit/hapus
                                       tidak pernah bisa ditekan. Sekarang: selalu tampil
                                       samar di layar sentuh, dan muncul saat disorot di
                                       perangkat bermouse. */
                                    <div className="flex shrink-0 gap-1 opacity-60 transition [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 focus-within:opacity-100">
                                        {masihBisaEdit(p) && (
                                            <button
                                                onClick={() => mulaiEdit(p)}
                                                title="Edit pesan"
                                                className="rounded-lg p-1.5 text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:hover:bg-coal-600"
                                            >
                                                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        )}
                                        <button
                                            onClick={() => hapusPesan(p)}
                                            title="Hapus pesan"
                                            className="rounded-lg p-1.5 text-stone-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                        >
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                )}

                                <div
                                    className={`max-w-[80%] rounded-2xl px-4 py-2.5 shadow-sm ring-1 ${
                                        milikku
                                            ? 'bg-claude-500 text-white ring-claude-500'
                                            : 'bg-white text-stone-800 ring-cream-300 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600'
                                    }`}
                                >
                                    {!milikku && (
                                        <p className="mb-0.5 text-xs font-semibold opacity-70">{p.pengirim.name}</p>
                                    )}

                                    {sedangEdit ? (
                                        <div className="space-y-2">
                                            <textarea
                                                value={editIsi}
                                                onChange={(e) => setEditIsi(e.target.value)}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); simpanEdit(); }
                                                    if (e.key === 'Escape') setEditId(null);
                                                }}
                                                rows={2}
                                                maxLength={2000}
                                                autoFocus
                                                className="w-full resize-none rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-stone-800 ring-1 ring-white/40 focus:ring-2 focus:ring-white"
                                            />
                                            <div className="flex justify-end gap-2">
                                                <button
                                                    onClick={() => setEditId(null)}
                                                    className="rounded-lg px-2.5 py-1 text-xs font-medium text-white/80 hover:bg-white/15"
                                                >
                                                    Batal
                                                </button>
                                                <button
                                                    onClick={simpanEdit}
                                                    disabled={!editIsi.trim()}
                                                    className="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-claude-600 hover:bg-cream-100 disabled:opacity-50"
                                                >
                                                    Simpan
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="whitespace-pre-wrap text-sm">{p.isi}</p>
                                    )}

                                    <p className={`mt-1 text-[11px] ${milikku ? 'text-white/70' : 'text-stone-400 dark:text-stone-500'}`}>
                                        {new Date(p.created_at).toLocaleString('id-ID', {
                                            day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                                        })}
                                        {p.edited_at && ' · diedit'}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Form balas / status terkunci */}
                {bisaBalas ? (
                    <form onSubmit={kirim} className="flex shrink-0 items-end gap-2">
                        <div className="relative flex-1">
                        <textarea
                            value={data.isi}
                            onChange={(e) => setData('isi', e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' && !e.shiftKey && !e.nativeEvent.isComposing) {
                                    e.preventDefault();
                                    if (!processing && data.isi.trim()) kirim(e);
                                }
                            }}
                            rows={2}
                            maxLength={2000}
                            placeholder="Tulis balasan… (Enter kirim, Shift+Enter baris baru)"
                            className="block w-full resize-none rounded-2xl border-0 bg-white px-4 py-3 pb-6 text-sm text-stone-800 ring-1 ring-cream-300 placeholder:text-stone-400 focus:ring-2 focus:ring-claude-500 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:placeholder:text-stone-500"
                        />
                        {data.isi.length > 0 && (
                            <span className="pointer-events-none absolute bottom-1.5 right-3 text-[10px] tabular-nums text-stone-400 dark:text-stone-500">
                                {data.isi.length}/2000
                            </span>
                        )}
                        </div>
                        <button
                            type="submit"
                            disabled={processing || !data.isi.trim()}
                            className="rounded-2xl bg-claude-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-claude-600 disabled:opacity-50"
                        >
                            Kirim
                        </button>
                    </form>
                ) : (
                    <div className="shrink-0 rounded-2xl bg-cream-200 p-4 text-center text-sm text-stone-500 ring-1 ring-cream-300 dark:bg-coal-700/70 dark:text-stone-400 dark:ring-coal-600">
                        {laporan.dibisukan_at
                            ? 'Percakapan ini dibisukan sehingga tidak dapat menerima pesan baru.'
                            : laporan.status === 'selesai'
                            ? (laporan.tutup_otomatis
                                ? 'Laporan ditutup otomatis karena tidak ada aktivitas selama 30 hari. Buat laporan baru bila masih ada masalah.'
                                : 'Laporan sudah ditutup. Buat laporan baru bila masih ada masalah.')
                            : 'Anda hanya dapat memantau laporan ini.'}
                    </div>
                )}
            </div>
        </Layout>
    );
}
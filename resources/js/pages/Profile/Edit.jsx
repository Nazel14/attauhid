import { useEffect, useRef, useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';
import KeuanganLayout from '@/Layouts/KeuanganLayout';
import LaporanModal from '@/Components/LaporanModal';

const CARD = 'rounded-2xl bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-5 sm:p-6';
const BTN_PRIMARY = 'inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 ' +
    'px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed';

const err = (msg) => msg ? <p className="mt-1 text-xs font-medium text-red-500">{msg}</p> : null;

const initials = (name) => (name || '?').split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();

// Fallback tampilan untuk data kosong. `?? ''` saja tidak cukup karena
// kolom seperti NIP bisa berisi string kosong, bukan hanya null.
const nilaiTampil = (v) => (v === null || v === undefined || v === '' ? '—' : v);

// Kalimat pembuka laporan untuk satu field profil. Foto dikecualikan karena
// nilainya bukan teks yang bisa dikutip.
const kalimat = (f) => {
    if (f.kategori === 'foto_salah') {
        return 'Foto profil saya tidak sesuai. Keterangan: ';
    }
    return nilaiTampil(f.value) === '—'
        ? `${f.label} saya masih kosong, seharusnya: `
        : `${f.label} saya tertulis "${f.value}", seharusnya: `;
};

const FlagBtn = ({ label, onClick }) => (
    <button
        type="button"
        onClick={onClick}
        title={`Laporkan kesalahan pada ${label}`}
        className="flex-shrink-0 rounded-lg p-1.5 text-stone-400 transition hover:bg-claude-500/10 hover:text-claude-500 active:scale-90"
    >
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.7" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 3v18M3 4.5h12.75l-2.25 3 2.25 3H3" />
        </svg>
    </button>
);

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

// Baris info read-only. Bendera hanya muncul bila field ini memang bisa
// dilaporkan — data turunan seperti "Jumlah Santri" tidak diinput manual,
// jadi tak ada gunanya dikomplain.
function InfoRow({ label, value, onLapor }) {
    return (
        <div className="flex flex-col gap-1 border-b border-cream-200 py-3 last:border-0 dark:border-coal-600 sm:flex-row sm:items-center sm:justify-between">
            <span className="text-sm font-semibold text-stone-500 dark:text-stone-400">{label}</span>
            <div className="flex min-w-0 items-center gap-1.5">
                <span className="truncate text-sm font-medium text-stone-900 dark:text-stone-100">{value || '—'}</span>
                {onLapor && <FlagBtn label={label} onClick={onLapor} />}
            </div>
        </div>
    );
}

export default function Edit({
    user, panel = 'raport', canUpdatePhoto, nextUpdateDate, photoCooldownDays = 0,
    fields = [], bisaLapor = false, kategoriLapor = {}, tujuanLapor = '',
    fotoLapor = null,
}) {
    const { flash = {} } = usePage().props;

    const role = user.role;
    const roleLabel = user.role_label || role;

    // Hanya guru & admin yang boleh menyentuh foto. Santri: read-only penuh.
    const canManagePhoto = role !== 'santri';
    const isKeuangan = panel === 'keuangan';
    const isAdmin = role === 'admin' && !isKeuangan;

    const NOTIF_DURATION = 5000;
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success || flash?.error) {
            setNotif({ message: flash.success || flash.error, type: flash.success ? 'success' : 'error', id: Date.now() });
            const t = setTimeout(() => setNotif(null), NOTIF_DURATION);
            return () => clearTimeout(t);
        }
    }, [flash]);

    const fileRef = useRef(null);
    const [preview, setPreview] = useState(null);
    const photoForm = useForm({ profile_photo: null });

    // Field yang sedang dilaporkan (null = modal tertutup)
    const [lapor, setLapor] = useState(null);

    const onPickFile = (e) => {
        const f = e.target.files?.[0];
        if (!f) return;
        photoForm.setData('profile_photo', f);
        setPreview(URL.createObjectURL(f));
    };

    const submitPhoto = (e) => {
        e.preventDefault();
        photoForm.post(route('profile.photo.update'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setPreview(null);
                photoForm.reset();
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    const deletePhoto = () => {
        if (!confirm('Hapus foto profil?')) return;
        router.delete(route('profile.photo.delete'), { preserveScroll: true });
    };

    const photoSrc = preview || user.profile_photo;

    // Saat kategori diganti di dalam modal, kalimat pembukanya ikut pindah ke
    // field yang bersangkutan. 'lainnya' dipakai beberapa field sekaligus,
    // jadi diberi kalimat netral kecuali memang field itu yang diklik.
    const templateIsi = (kat) => {
        if (!kat || !lapor) return '';
        if (kat === lapor.kategori) return kalimat(lapor);
        if (kat === 'lainnya') return 'Ada kekeliruan pada data profil saya: ';

        // Foto ikut dicari juga, supaya berpindah ke "Foto Profil Salah" dari
        // dalam modal tetap mengganti kalimat pembukanya.
        const f = [...fields, fotoLapor].find((x) => x && x.kategori === kat);
        return f ? kalimat(f) : '';
    };

    const content = (
        <div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
            <Head title="Profil" />
            <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />

            {/* Header */}
            <div className="flex items-center gap-3">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-claude-500/10 text-claude-500">
                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                    </svg>
                </div>
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">Profil Saya</h1>
                    <span className="inline-flex rounded-full bg-claude-500/10 px-2.5 py-0.5 text-xs font-semibold text-claude-500">{roleLabel}</span>
                </div>
            </div>

            {/* Foto Profil */}
            <div className={CARD}>
                <div className="mb-4 flex items-center gap-1.5">
                    <h2 className="text-base font-bold tracking-tight text-stone-900 dark:text-stone-100">Foto Profil</h2>
                    {bisaLapor && fotoLapor && (
                        <FlagBtn label="Foto Profil" onClick={() => setLapor(fotoLapor)} />
                    )}
                </div>
                <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    {photoSrc ? (
                        <img src={photoSrc} alt={user.name} className="h-28 w-28 flex-shrink-0 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600" />
                    ) : (
                        <div className="flex h-28 w-28 flex-shrink-0 items-center justify-center rounded-full bg-claude-500/10 text-3xl font-bold text-claude-500">
                            {initials(user.name)}
                        </div>
                    )}

                    {!canManagePhoto ? (
                        <p className="text-sm text-stone-500 dark:text-stone-400">
                            Foto profil siswa diatur oleh admin atau guru pembimbing dan tidak dapat diubah sendiri.
                            Klik ikon bendera di atas bila fotonya keliru.
                        </p>
                    ) : !canUpdatePhoto ? (
                        <p className="text-sm text-amber-600 dark:text-amber-400">
                            Foto hanya bisa diubah <span className="font-semibold">1x per {photoCooldownDays} hari</span>.
                            {nextUpdateDate && <> Coba lagi pada <span className="font-semibold">{nextUpdateDate}</span>.</>}
                        </p>
                    ) : (
                        <form onSubmit={submitPhoto} className="flex-1 space-y-3">
                            <input
                                ref={fileRef}
                                type="file"
                                accept="image/jpeg,image/png,image/jpg"
                                onChange={onPickFile}
                                className="block w-full text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-claude-500/10 file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-claude-500 hover:file:bg-claude-500/20"
                            />
                            {err(photoForm.errors.profile_photo)}
                            <div className="flex flex-wrap gap-2">
                                <button type="submit" disabled={!photoForm.data.profile_photo || photoForm.processing} className={BTN_PRIMARY}>
                                    {photoForm.processing ? 'Mengunggah…' : 'Simpan Foto'}
                                </button>
                                {user.profile_photo && (
                                    <button type="button" onClick={deletePhoto}
                                        className="inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold text-red-500 ring-1 ring-red-500/30 transition hover:bg-red-500/10">
                                        Hapus Foto
                                    </button>
                                )}
                            </div>
                            <p className="text-xs text-stone-400">
                                JPG/PNG, maks 2MB.{photoCooldownDays > 0 && ` Dapat diubah 1x per ${photoCooldownDays} hari.`}
                            </p>
                        </form>
                    )}
                </div>
            </div>

            {/* Informasi Profil — read-only untuk semua role */}
            <div className={CARD}>
                <div className="mb-2 flex items-center justify-between">
                    <h2 className="text-base font-bold tracking-tight text-stone-900 dark:text-stone-100">Informasi Profil</h2>
                    <span className="inline-flex items-center gap-1 rounded-full bg-stone-500/10 px-2.5 py-0.5 text-xs font-semibold text-stone-500 dark:text-stone-400">
                        <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Read-only
                    </span>
                </div>

                <div>
                    {fields.map((f) => (
                        <InfoRow
                            key={f.label}
                            label={f.label}
                            value={f.value}
                            onLapor={bisaLapor && f.kategori ? () => setLapor(f) : null}
                        />
                    ))}
                </div>

                <p className="mt-3 text-xs text-stone-400">
                    {isKeuangan
                        ? 'Data pegawai & hak akses dikelola melalui menu Master Data dan Pengaturan Sistem. Hubungi Super Admin bila ada kekeliruan.'
                        : isAdmin
                        ? 'Nama akun tidak dapat diubah dari halaman ini.'
                        : bisaLapor
                        ? `Data profil dikelola oleh ${tujuanLapor.toLowerCase()}. Klik ikon bendera di samping data yang keliru untuk melaporkannya.`
                        : 'Data profil dikelola oleh admin. Hubungi admin bila ada kekeliruan.'}
                </p>
            </div>

            {lapor && (
                <LaporanModal
                    key={lapor.label}
                    open={true}
                    onClose={() => setLapor(null)}
                    judul={`Laporkan: ${lapor.label}`}
                    konteks={`Ditujukan ke ${tujuanLapor} · Data saat ini: ${nilaiTampil(lapor.value)}`}
                    kategoriOptions={kategoriLapor}
                    kategoriDefault={lapor.kategori}
                    isiAwal={kalimat(lapor)}
                    templateIsi={templateIsi}
                />
            )}
        </div>
    );

    // Panel keuangan diperiksa lebih dulu: seorang guru yang juga pegawai
    // keuangan harus tetap melihat sidebar keuangan saat berada di sana.
    if (isKeuangan)        return <KeuanganLayout>{content}</KeuanganLayout>;
    if (role === 'guru')   return <GuruAuthenticatedLayout>{content}</GuruAuthenticatedLayout>;
    if (role === 'santri') return <SantriAuthenticatedLayout>{content}</SantriAuthenticatedLayout>;
    return <AuthenticatedLayout>{content}</AuthenticatedLayout>;
}
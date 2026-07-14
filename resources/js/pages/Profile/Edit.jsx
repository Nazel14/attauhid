import { useRef, useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';

const CARD = 'rounded-2xl bg-white dark:bg-[#262624] ring-1 ring-black/5 dark:ring-white/10 p-5 sm:p-6';
const BTN_PRIMARY = 'inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 ' +
    'px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed';

const err = (msg) => msg ? <p className="mt-1 text-xs font-medium text-red-500">{msg}</p> : null;

const initials = (name) => (name || '?').split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();

// Baris info read-only
function InfoRow({ label, value }) {
    return (
        <div className="flex flex-col gap-1 border-b border-black/5 py-3 last:border-0 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <span className="text-sm font-semibold text-gray-500 dark:text-gray-400">{label}</span>
            <span className="text-sm font-medium text-gray-900 dark:text-gray-100">{value || '—'}</span>
        </div>
    );
}

export default function Edit({ user, canUpdatePhoto, nextUpdateDate, photoCooldownDays = 0 }) {
    const role = user.role;
    const roleLabel = user.role_label || role;

    // Hanya guru & admin yang boleh menyentuh foto. Santri: read-only penuh.
    const canManagePhoto = role !== 'santri';
    const isAdmin = role === 'admin';

    const fileRef = useRef(null);
    const [preview, setPreview] = useState(null);
    const photoForm = useForm({ profile_photo: null });

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

    const content = (
        <div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
            <Head title="Profil" />

            {/* Header */}
            <div className="flex items-center gap-3">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-claude-500/10 text-claude-500">
                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                    </svg>
                </div>
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Profil Saya</h1>
                    <span className="inline-flex rounded-full bg-claude-500/10 px-2.5 py-0.5 text-xs font-semibold text-claude-500">{roleLabel}</span>
                </div>
            </div>

            {/* Foto Profil */}
            <div className={CARD}>
                <h2 className="mb-4 text-base font-bold tracking-tight text-gray-900 dark:text-gray-100">Foto Profil</h2>
                <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    {photoSrc ? (
                        <img src={photoSrc} alt={user.name} className="h-28 w-28 flex-shrink-0 rounded-full object-cover object-top ring-1 ring-black/10 dark:ring-white/10" />
                    ) : (
                        <div className="flex h-28 w-28 flex-shrink-0 items-center justify-center rounded-full bg-claude-500/10 text-3xl font-bold text-claude-500">
                            {initials(user.name)}
                        </div>
                    )}

                    {!canManagePhoto ? (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Foto profil santri diatur oleh admin dan tidak dapat diubah sendiri.
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
                                className="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-claude-500/10 file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-claude-500 hover:file:bg-claude-500/20"
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
                            <p className="text-xs text-gray-400">
                                JPG/PNG, maks 2MB.{photoCooldownDays > 0 && ` Dapat diubah 1x per ${photoCooldownDays} hari.`}
                            </p>
                        </form>
                    )}
                </div>
            </div>

            {/* Informasi Profil — read-only untuk semua role */}
            <div className={CARD}>
                <div className="mb-2 flex items-center justify-between">
                    <h2 className="text-base font-bold tracking-tight text-gray-900 dark:text-gray-100">Informasi Profil</h2>
                    <span className="inline-flex items-center gap-1 rounded-full bg-gray-500/10 px-2.5 py-0.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Read-only
                    </span>
                </div>

                <div>
                    <InfoRow label="Nama" value={user.name} />
                    <InfoRow label="Role" value={roleLabel} />
                </div>

                <p className="mt-3 text-xs text-gray-400">
                    {isAdmin
                        ? 'Nama akun tidak dapat diubah dari halaman ini.'
                        : 'Data profil dikelola oleh admin. Hubungi admin bila ada kekeliruan.'}
                </p>
            </div>
        </div>
    );

    if (role === 'guru')   return <GuruAuthenticatedLayout>{content}</GuruAuthenticatedLayout>;
    if (role === 'santri') return <SantriAuthenticatedLayout>{content}</SantriAuthenticatedLayout>;
    return <AuthenticatedLayout>{content}</AuthenticatedLayout>;
}
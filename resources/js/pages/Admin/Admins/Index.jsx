import { useState, useEffect } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

function SuperBadge() {
    return <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-inset ring-amber-300 dark:bg-amber-400/15 dark:text-amber-300 dark:ring-amber-400/30">👑 Super Admin</span>;
}
function AdminBadge() {
    return <span className="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-800 ring-1 ring-inset ring-blue-300 dark:bg-blue-400/15 dark:text-blue-300 dark:ring-blue-400/30">⚡ Admin</span>;
}
function PemilikBadge() {
    return <span className="inline-flex items-center rounded-full bg-stone-200 px-2 py-0.5 text-[10px] font-semibold text-stone-700 ring-1 ring-inset ring-stone-300 dark:bg-coal-600 dark:text-stone-200 dark:ring-coal-500">🔒 Pemilik Sistem</span>;
}

function Avatar({ url, name, size = 'h-10 w-10' }) {
    if (url) {
        return <img src={url} alt="Foto" className={`${size} flex-shrink-0 rounded-full border border-cream-300 object-cover object-top dark:border-coal-500`} />;
    }
    return (
        <span className={`${size} flex flex-shrink-0 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 ring-1 ring-inset ring-claude-200 dark:bg-claude-500/20 dark:text-claude-300 dark:ring-claude-500/30`}>
            {name?.charAt(0)?.toUpperCase()}
        </span>
    );
}

export default function AdminsIndex({ admins, counts, pemilik = [] }) {
    const { flash } = usePage().props;

    // ---- Notifikasi (dari flash) ----
    const NOTIF_DURATION = 10000;
    const [notif, setNotif] = useState(null);
    useEffect(() => {
        if (flash?.success) setNotif({ type: 'success', message: flash.success, id: Date.now() });
        else if (flash?.error) setNotif({ type: 'error', message: flash.error, id: Date.now() });
    }, [flash]);
    useEffect(() => {
        if (notif) {
            const t = setTimeout(() => setNotif(null), NOTIF_DURATION);
            return () => clearTimeout(t);
        }
    }, [notif]);

    // ---- Modal & form ----
    const [isOpen, setIsOpen] = useState(false);
    const [isEdit, setIsEdit] = useState(false);
    const [currentId, setCurrentId] = useState(null);
    const [photoPreview, setPhotoPreview] = useState(null);
    const [currentPhotoUrl, setCurrentPhotoUrl] = useState(null);
    const [currentPhotoName, setCurrentPhotoName] = useState(null);
    const [showPass, setShowPass] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
    is_super_admin: 0,
    profile_photo: null,
    delete_photo: 0,
});

    const openCreate = () => {
        setIsEdit(false);
        setCurrentId(null);
        setPhotoPreview(null);
        setCurrentPhotoUrl(null);
        setCurrentPhotoName(null);
        form.clearErrors();
        form.setData({ name: '', password: '', password_confirmation: '', is_super_admin: 0, profile_photo: null, delete_photo: 0 });
        setIsOpen(true);
    };

    const openEdit = (admin) => {
        setIsEdit(true);
        setCurrentId(admin.id);
        setPhotoPreview(null);
        setCurrentPhotoUrl(admin.profile_photo_url || null);
        setCurrentPhotoName(admin.profile_photo_name || null);
        form.clearErrors();
        form.setData({
            name: admin.name,
            password: '',
            password_confirmation: '',
            is_super_admin: admin.is_super_admin ? 1 : 0,
            profile_photo: null,
            delete_photo: 0,
        });
        setIsOpen(true);
    };

    const closeModal = () => setIsOpen(false);

    const handleName = (e) => {
        let v = e.target.value;
        if (v.length > 0) v = v.charAt(0).toUpperCase() + v.slice(1);
        form.setData('name', v);
    };

    const handlePhoto = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.setData('profile_photo', file);
        form.setData('delete_photo', 0);
        const reader = new FileReader();
        reader.onload = (ev) => setPhotoPreview(ev.target.result);
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    setPhotoPreview(null);
    setCurrentPhotoUrl(null);
    setCurrentPhotoName(null);
    form.setData('profile_photo', null);
    form.setData('delete_photo', 1);
};

    const submit = (e) => {
        e.preventDefault();
        const opts = { forceFormData: true, preserveScroll: true, onSuccess: () => closeModal() };
        if (isEdit) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(route('admin.admins.update', currentId), opts);
        } else {
            form.transform((data) => data);
            form.post(route('admin.admins.store'), opts);
        }
    };

    const resetPassword = (admin) => {
        if (confirm(`Reset password ${admin.name}?\n\nPassword lama langsung tidak berlaku. Password baru muncul di notifikasi — catat sebelum hilang.`)) {
            router.put(route('admin.admins.reset-password', admin.id), {}, { preserveScroll: true });
        }
    };

    const destroy = (admin) => {
        if (confirm(`Hapus admin ${admin.name}?`)) {
            router.delete(route('admin.admins.destroy', admin.id), { preserveScroll: true });
        }
    };

    const rows = admins?.data ?? [];

    // Kelas input konsisten (light + dark)
    const inputCls =
        'w-full rounded-xl border border-cream-400 bg-white px-3 py-2.5 text-sm text-stone-900 outline-none transition ' +
        'placeholder:text-stone-400 focus:border-transparent focus:ring-2 focus:ring-claude-500 ' +
        'dark:border-coal-500 dark:bg-coal-800 dark:text-cream-50 dark:placeholder:text-stone-500';

    return (
        <AuthenticatedLayout>
            <Head title="Kelola Admin" />

            {/* ===== Notifikasi (toast melayang) ===== */}
            {notif && (
                <>
                    <div
                        key={notif.id}
                        className="fixed left-4 right-4 top-4 z-[100] overflow-hidden rounded-2xl border border-cream-300 bg-white/95 shadow-2xl backdrop-blur-md sm:left-auto sm:right-4 sm:w-96 dark:border-coal-600 dark:bg-coal-700/95"
                        style={{ animation: 'toastIn .3s cubic-bezier(.21,1.02,.73,1)' }}
                        role="alert"
                    >
                        <div className="flex items-start gap-3 p-3.5">
                            {/* Ikon dalam lingkaran berwarna */}
                            <span
                                className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full ${
                                    notif.type === 'success'
                                        ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300'
                                        : 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300'
                                }`}
                            >
                                {notif.type === 'success' ? (
                                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                ) : (
                                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                )}
                            </span>

                            <div className="min-w-0 flex-1 pt-0.5">
                                <p className={`text-xs font-bold ${notif.type === 'success' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'}`}>
                                    {notif.type === 'success' ? 'Berhasil' : 'Gagal'}
                                </p>
                                <p className="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-300">{notif.message}</p>
                            </div>

                            <button onClick={() => setNotif(null)} className="flex-shrink-0 rounded-lg p-1 text-stone-400 transition hover:bg-cream-100 hover:text-stone-600 dark:hover:bg-coal-600 dark:hover:text-stone-200">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {/* Baris progress waktu hilang */}
                        <div className="h-1 w-full bg-cream-200 dark:bg-coal-600">
                            <div
                                className={`h-full ${notif.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'}`}
                                style={{ animation: `toastBar ${NOTIF_DURATION}ms linear forwards` }}
                            />
                        </div>
                    </div>
                    <style>{`
                        @keyframes toastIn { from { opacity: 0; transform: translateY(-14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
                        @keyframes toastBar { from { width: 100%; } to { width: 0%; } }
                    `}</style>
                </>
            )}
            {/* Tombol kembali — mobile saja */}
            <Link
                href={route('admin.dashboard')}
                className="mb-4 inline-flex items-center gap-1.5 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-stone-600 shadow-sm transition hover:bg-cream-100 active:scale-95 sm:hidden dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:bg-coal-600"
            >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali ke Dashboard
            </Link>

            {/* ===== Header ===== */}
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3.5">
                    {/* Ikon tile */}
                    <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-claude-400 to-claude-600 text-white shadow-lg shadow-claude-500/25">
                        <svg className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-stone-900 dark:text-cream-50">Kelola Admin</h1>
<div className="mt-1 flex flex-wrap items-center gap-1.5">
                            {/* Angka diambil apa adanya dari controller — jumlah baris
                                di tabel users, tanpa penjumlahan tambahan di sini. */}
                            <span
                                title="Jumlah akun berlevel Super Admin."
                                className="inline-flex cursor-help items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-inset ring-amber-300 dark:bg-amber-400/15 dark:text-amber-300 dark:ring-amber-400/30"
                            >
                                👑 Super {counts.superAdmin}
                            </span>
                            <span
                                title="Jumlah akun berlevel Admin Biasa."
                                className="inline-flex cursor-help items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-800 ring-1 ring-inset ring-blue-300 dark:bg-blue-400/15 dark:text-blue-300 dark:ring-blue-400/30"
                            >
                                ⚡ Biasa {counts.regularAdmin}
                            </span>
                        </div>
                    </div>
                </div>
                <button onClick={openCreate} className="inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-400 hover:to-claude-500 active:scale-95">
                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
                    <span className="hidden sm:inline">Tambah Admin</span>
                    <span className="sm:hidden">Tambah</span>
                </button>
            </div>

            {/* ===== Tabel (sm+) ===== */}
            <div className="mb-4 hidden overflow-hidden rounded-xl border border-cream-300 bg-white shadow-sm sm:block dark:border-coal-600 dark:bg-coal-700 dark:shadow-none">
                <div className="overflow-x-auto">
                    <table className="min-w-full">
                        <thead className="bg-cream-100 dark:bg-coal-700">
                            <tr className="border-b border-cream-300 dark:border-coal-600">
                                <th className="w-10 px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">No</th>
                                <th className="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Nama Admin</th>
                                <th className="px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Level</th>
                                <th className="w-44 px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-cream-200 dark:divide-coal-600">
                            {/* Baris pemilik sistem. Dinomori sendiri dari 1 karena berada
                                di luar paginasi, dan tanpa tombol aksi karena controller
                                menolak akun yang kolom role-nya bukan 'admin' dengan 404. */}
                            {pemilik.map((p, i) => (
                                <tr key={`pemilik-${p.id}`} className="bg-amber-50/40 dark:bg-amber-400/5">
                                    <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-400">{i + 1}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2.5">
                                            <Avatar url={p.profile_photo_url} name={p.name} />
                                            <p className="text-xs font-semibold text-stone-800 dark:text-stone-100">
                                                {p.name}
                                                {p.is_self && <span className="text-claude-600 dark:text-claude-400"> (Anda)</span>}
                                            </p>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-center"><PemilikBadge /></td>
                                    <td className="px-4 py-3 text-center">
                                        <span className="text-[10px] italic text-stone-400 dark:text-stone-500">Tidak dapat diubah</span>
                                    </td>
                                </tr>
                            ))}

                            {rows.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-10 text-center">
                                        <p className="text-xs font-semibold text-stone-600 dark:text-stone-300">Belum ada admin tambahan</p>
                                        <p className="mx-auto mt-1 max-w-xs text-[11px] leading-relaxed text-stone-400 dark:text-stone-500">
                                            Tekan “Tambah Admin” untuk membuat akun pengelola baru.
                                        </p>
                                    </td>
                                </tr>
                            ) : (
                                rows.map((admin, i) => (
                                    <tr key={admin.id} className="transition hover:bg-cream-100/70 dark:hover:bg-coal-600/40">
                                        <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-400">{pemilik.length + admins.from + i}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2.5">
                                                <Avatar url={admin.profile_photo_url} name={admin.name} />
                                                <p className="text-xs font-semibold text-stone-800 dark:text-stone-100">
                                                    {admin.name}
                                                    {admin.is_self && <span className="text-claude-600 dark:text-claude-400"> (Anda)</span>}
                                                </p>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center">{admin.is_super_admin ? <SuperBadge /> : <AdminBadge />}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-center gap-2">
                                                <button onClick={() => openEdit(admin)} className="rounded-lg bg-claude-100 px-2.5 py-1 text-[10px] font-semibold text-claude-700 ring-1 ring-inset ring-claude-300 transition hover:bg-claude-200 dark:bg-claude-500/15 dark:text-claude-300 dark:ring-claude-500/30 dark:hover:bg-claude-500/25">Edit</button>
                                                <button onClick={() => resetPassword(admin)} title="Buat password baru untuk admin ini" className="rounded-lg bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-300 transition hover:bg-amber-200 dark:bg-amber-400/15 dark:text-amber-300 dark:ring-amber-400/30 dark:hover:bg-amber-400/25">Reset</button>
                                                {!admin.is_super_admin && !admin.is_self ? (
                                                    <button onClick={() => destroy(admin)} className="rounded-lg bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-300 transition hover:bg-red-200 dark:bg-red-400/15 dark:text-red-300 dark:ring-red-400/30 dark:hover:bg-red-400/25">Hapus</button>
                                                ) : admin.is_super_admin ? (
                                                    <span className="text-[10px] italic text-stone-400 dark:text-stone-500">Terlindungi</span>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* ===== Kartu (mobile) ===== */}
            <div className="mb-4 space-y-2 sm:hidden">
                {pemilik.map((p) => (
                    <div key={`pemilik-${p.id}`} className="rounded-xl border border-amber-200 bg-amber-50/50 p-3 dark:border-amber-400/25 dark:bg-amber-400/5">
                        <div className="flex items-start gap-3">
                            <Avatar url={p.profile_photo_url} name={p.name} />
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-xs font-semibold text-stone-800 dark:text-stone-100">
                                        {p.name}
                                        {p.is_self && <span className="text-[10px] text-claude-600 dark:text-claude-400"> (Anda)</span>}
                                    </p>
                                    <PemilikBadge />
                                </div>
                                <p className="mt-1 text-[10px] text-stone-400 dark:text-stone-500">Tidak dapat diubah dari halaman ini</p>
                            </div>
                        </div>
                    </div>
                ))}

                {rows.length === 0 ? (
                    <div className="rounded-xl border border-cream-300 bg-white p-8 text-center dark:border-coal-600 dark:bg-coal-700">
                        <p className="text-xs font-semibold text-stone-600 dark:text-stone-300">Belum ada admin tambahan</p>
                        <p className="mt-1 text-[11px] leading-relaxed text-stone-400 dark:text-stone-500">
                            Tekan “Tambah Admin” untuk membuat akun pengelola baru.
                        </p>
                    </div>
                ) : (
                    rows.map((admin) => (
                        <div key={admin.id} className="rounded-xl border border-cream-300 bg-white p-3 shadow-sm dark:border-coal-600 dark:bg-coal-700 dark:shadow-none">
                            <div className="flex items-start gap-3">
                                <Avatar url={admin.profile_photo_url} name={admin.name} />
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-xs font-semibold text-stone-800 dark:text-stone-100">
                                            {admin.name}
                                            {admin.is_self && <span className="text-[10px] text-claude-600 dark:text-claude-400"> (Anda)</span>}
                                        </p>
                                        {admin.is_super_admin ? <SuperBadge /> : <AdminBadge />}
                                    </div>
                                </div>
                                <div className="flex flex-shrink-0 items-center gap-1.5">
                                    <button onClick={() => openEdit(admin)} className="rounded-lg bg-claude-100 px-2.5 py-1.5 text-[10px] font-bold text-claude-700 ring-1 ring-inset ring-claude-300 transition active:scale-95 dark:bg-claude-500/15 dark:text-claude-300 dark:ring-claude-500/30">Edit</button>
                                    <button onClick={() => resetPassword(admin)} className="rounded-lg bg-amber-100 px-2.5 py-1.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-300 transition active:scale-95 dark:bg-amber-400/15 dark:text-amber-300 dark:ring-amber-400/30">Reset</button>
                                    {!admin.is_super_admin && !admin.is_self && (
                                        <button onClick={() => destroy(admin)} className="rounded-lg bg-red-100 px-2.5 py-1.5 text-[10px] font-bold text-red-700 ring-1 ring-inset ring-red-300 transition active:scale-95 dark:bg-red-400/15 dark:text-red-300 dark:ring-red-400/30">Hapus</button>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* ===== Pagination ===== */}
            {admins?.last_page > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    {admins.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || '#'}
                            preserveScroll
                            className={`min-w-[34px] rounded-lg px-3 py-1.5 text-center text-xs font-medium transition ${
                                link.active
                                    ? 'bg-gradient-to-r from-claude-500 to-claude-600 text-white shadow shadow-claude-500/25'
                                    : link.url
                                    ? 'border border-cream-300 bg-white text-stone-600 hover:bg-cream-100 dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:bg-coal-600'
                                    : 'cursor-default border border-cream-200 bg-cream-100 text-stone-300 dark:border-coal-700 dark:bg-coal-700/50 dark:text-stone-600'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            {/* ===== Modal Tambah/Edit ===== */}
            {isOpen && (
                <div className="fixed inset-0 z-[60] overflow-y-auto">
                    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm" onClick={closeModal} />
                    <div className="relative flex min-h-screen items-end justify-center p-0 sm:items-center sm:p-4">
                        <div className="w-full overflow-hidden rounded-t-2xl border border-cream-300 bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl dark:border-coal-600 dark:bg-coal-700">
                            {/* Header modal */}
                            <div className="flex items-center justify-between border-b border-cream-300 bg-cream-100 px-4 py-3 dark:border-coal-600 dark:bg-coal-700">
                                <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-cream-50">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-claude-100 ring-1 ring-inset ring-claude-300 dark:bg-claude-500/15 dark:ring-claude-500/30">
                                        <svg className="h-3.5 w-3.5 text-claude-600 dark:text-claude-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </span>
                                    {isEdit ? 'Edit Admin' : 'Tambah Admin Baru'}
                                </h3>
                                <button onClick={closeModal} className="flex h-7 w-7 items-center justify-center rounded-lg bg-cream-200 transition hover:bg-cream-300 dark:bg-coal-600 dark:hover:bg-coal-500">
                                    <svg className="h-3.5 w-3.5 text-stone-500 dark:text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <form onSubmit={submit} className="space-y-4 p-4">
                                {/* Foto */}
                                <div className="flex flex-col items-center gap-2">
                                    <div className="relative">
                                        {photoPreview ? (
                                            <img src={photoPreview} className="h-20 w-20 rounded-full border-2 border-claude-300 object-cover object-top dark:border-claude-500/50" />
                                        ) : currentPhotoUrl ? (
                                            <img src={currentPhotoUrl} className="h-20 w-20 rounded-full border-2 border-cream-400 object-cover object-top dark:border-coal-500" />
                                        ) : (
                                            <span className="flex h-20 w-20 items-center justify-center rounded-full border-2 border-claude-200 bg-claude-100 dark:border-claude-500/30 dark:bg-claude-500/15">
                                                <svg className="h-8 w-8 text-claude-400 dark:text-claude-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            </span>
                                        )}
                                    </div>
                                    {currentPhotoName && !photoPreview && (
                                        <p className="max-w-full truncate text-[10px] text-stone-400 dark:text-stone-500" title={currentPhotoName}>
                                            {currentPhotoName}
                                        </p>
                                    )}
                                    <div className="flex items-center gap-3">
                                        <label className="cursor-pointer text-[11px] font-semibold text-claude-600 hover:underline dark:text-claude-400">
                                            {photoPreview || currentPhotoUrl ? 'Ganti Foto' : 'Tambah Foto (opsional)'}
                                            <input type="file" accept="image/*" className="hidden" onChange={handlePhoto} />
                                        </label>
                                        {(photoPreview || currentPhotoUrl) && (
                                            <button type="button" onClick={removePhoto} className="text-[11px] font-semibold text-red-600 hover:underline dark:text-red-400">
                                                Hapus Foto
                                            </button>
                                        )}
                                    </div>
                                    {form.errors.profile_photo && <p className="text-[10px] text-red-600 dark:text-red-400">{form.errors.profile_photo}</p>}
                                </div>

                                {/* Nama */}
                                <div>
                                    <label className="mb-1.5 block text-xs font-semibold text-stone-600 dark:text-stone-300">Nama Lengkap <span className="text-red-500">*</span></label>
                                    <input
                                        type="text"
                                        value={form.data.name}
                                        onChange={handleName}
                                        placeholder="Ahmad Zainudin"
                                        className={inputCls}
                                    />
                                    {form.errors.name && <p className="mt-1 text-[10px] text-red-600 dark:text-red-400">{form.errors.name}</p>}
                                    {!form.errors.name && <p className="mt-1 text-[10px] text-stone-500 dark:text-stone-400">Nama yang akan ditampilkan di sistem</p>}
                                </div>

                                {/* Password (hanya saat create) */}
                                {!isEdit && (
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1.5 block text-xs font-semibold text-stone-600 dark:text-stone-300">Password <span className="text-red-500">*</span></label>
                                            <div className="relative">
                                                <input
                                                    type={showPass ? 'text' : 'password'}
                                                    value={form.data.password}
                                                    onChange={(e) => form.setData('password', e.target.value)}
                                                    placeholder="Min. 8 karakter"
                                                    className={`${inputCls} pr-9`}
                                                />
                                                <button type="button" onClick={() => setShowPass(!showPass)} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300">
                                                    {showPass ? (
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                                    ) : (
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    )}
                                                </button>
                                            </div>
                                            {form.errors.password && <p className="mt-1 text-[10px] text-red-600 dark:text-red-400">{form.errors.password}</p>}
                                        </div>
                                        <div>
                                            <label className="mb-1.5 block text-xs font-semibold text-stone-600 dark:text-stone-300">Konfirmasi <span className="text-red-500">*</span></label>
                                            <div className="relative">
                                                <input
                                                    type={showConfirm ? 'text' : 'password'}
                                                    value={form.data.password_confirmation}
                                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                                    placeholder="Ulangi password"
                                                    className={`${inputCls} pr-9`}
                                                />
                                                <button type="button" onClick={() => setShowConfirm(!showConfirm)} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300">
                                                    {showConfirm ? (
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                                    ) : (
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    )}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Level */}
                                <div>
                                    <label className="mb-2 block text-xs font-semibold text-stone-600 dark:text-stone-300">Level Admin <span className="text-red-500">*</span></label>
                                    <div className="grid grid-cols-2 gap-2">
                                        <label className={`flex cursor-pointer items-start gap-2.5 rounded-xl border-2 p-3 transition ${form.data.is_super_admin === 1 ? 'border-amber-500/60 bg-amber-50 dark:border-amber-400/50 dark:bg-amber-400/10' : 'border-cream-300 bg-cream-100 hover:border-cream-400 dark:border-coal-600 dark:bg-coal-800/60 dark:hover:border-coal-500'}`}>
                                            <input type="radio" name="is_super_admin" checked={form.data.is_super_admin === 1} onChange={() => form.setData('is_super_admin', 1)} className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-amber-500" />
                                            <div>
                                                <span className="block text-xs font-bold text-stone-800 dark:text-stone-100">👑 Super Admin</span>
                                                <p className="mt-0.5 text-[10px] text-stone-500 dark:text-stone-400">Akses penuh, maks 3 akun</p>
                                            </div>
                                        </label>
                                        <label className={`flex cursor-pointer items-start gap-2.5 rounded-xl border-2 p-3 transition ${form.data.is_super_admin === 0 ? 'border-blue-500/60 bg-blue-50 dark:border-blue-400/50 dark:bg-blue-400/10' : 'border-cream-300 bg-cream-100 hover:border-cream-400 dark:border-coal-600 dark:bg-coal-800/60 dark:hover:border-coal-500'}`}>
                                            <input type="radio" name="is_super_admin" checked={form.data.is_super_admin === 0} onChange={() => form.setData('is_super_admin', 0)} className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-blue-500" />
                                            <div>
                                                <span className="block text-xs font-bold text-stone-800 dark:text-stone-100">⚡ Admin Biasa</span>
                                                <p className="mt-0.5 text-[10px] text-stone-500 dark:text-stone-400">Akses terbatas, maks 5 akun</p>
                                            </div>
                                        </label>
                                    </div>
                                    {form.errors.is_super_admin && <p className="mt-1 text-[10px] text-red-600 dark:text-red-400">{form.errors.is_super_admin}</p>}
                                </div>

                                <div className="flex items-start gap-2 rounded-xl border border-claude-200 bg-claude-50 p-3 dark:border-claude-500/30 dark:bg-claude-500/10">
                                    <svg className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-claude-500 dark:text-claude-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p className="text-[10px] leading-relaxed text-claude-700 dark:text-claude-300">Super Admin (maks 3) tidak dapat dihapus. Admin Biasa (maks 5) dapat dikelola sepenuhnya. Password hanya ditampilkan sekali saat dibuat — catat sebelum menutup notifikasi.</p>
                                </div>

                                {isEdit && (
                                    <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-400/30 dark:bg-amber-400/10">
                                        <svg className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-amber-500 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <p className="text-[10px] leading-relaxed text-amber-700 dark:text-amber-300">Password tidak diubah dari form ini. Gunakan tombol <strong>Reset</strong> di daftar untuk membuat password baru.</p>
                                    </div>
                                )}

                                <div className="flex gap-2 border-t border-cream-300 pt-2 dark:border-coal-600">
                                    <button type="button" onClick={closeModal} className="flex-1 rounded-xl border border-cream-400 bg-cream-200 px-4 py-2.5 text-xs font-semibold text-stone-700 transition hover:bg-cream-300 dark:border-coal-500 dark:bg-coal-600 dark:text-stone-200 dark:hover:bg-coal-500">Batal</button>
                                    <button type="submit" disabled={form.processing} className="flex-1 rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-400 hover:to-claude-500 disabled:opacity-60">
                                        {form.processing ? 'Menyimpan…' : isEdit ? 'Simpan Perubahan' : 'Tambah Admin'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
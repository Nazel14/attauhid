import { useState, useEffect } from 'react';
import { Head, useForm, router, usePage, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// ===== Kelas input konsisten (light + dark) =====
const inputCls =
    'w-full rounded-xl border border-cream-400 bg-white px-3 py-2.5 text-sm text-stone-900 outline-none transition ' +
    'placeholder:text-stone-400 focus:border-transparent focus:ring-2 focus:ring-claude-500 ' +
    'dark:border-coal-500 dark:bg-coal-800 dark:text-cream-50 dark:placeholder:text-stone-500';
const inputDisabledCls = 'disabled:cursor-not-allowed disabled:opacity-50';
const labelCls = 'mb-1.5 block text-xs font-medium text-stone-600 dark:text-stone-300';

export default function TahunAjaranIndex({ tahunAjarans = [], aktif = null }) {
    const { flash } = usePage().props;

    // ---- Notifikasi flash ----
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

    const [showFormMobile, setShowFormMobile] = useState(false);

    // ---- Form Tambah ----
    const createForm = useForm({ nama: '', semester: '1', tanggal_mulai: '', tanggal_selesai: '', keterangan: '' });
    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('admin.tahun-ajaran.store'), {
            preserveScroll: true,
            onSuccess: () => createForm.reset(),
        });
    };
    // Buka form otomatis di mobile bila ada error
    useEffect(() => {
        if (Object.keys(createForm.errors).length > 0) setShowFormMobile(true);
    }, [createForm.errors]);

    // ---- Modal Edit ----
    const [editOpen, setEditOpen] = useState(false);
    const [editId, setEditId] = useState(null);
    const [editLocked, setEditLocked] = useState(false); // TA sudah ditutup → hanya keterangan
    const editForm = useForm({ nama: '', semester: '1', tanggal_mulai: '', tanggal_selesai: '', keterangan: '' });
    const openEdit = (ta) => {
        setEditId(ta.id);
        setEditLocked(!!ta.sudah_dipakai);
        editForm.clearErrors();
        editForm.setData({
            nama: ta.nama,
            semester: String(ta.semester),
            tanggal_mulai: ta.tanggal_mulai,
            tanggal_selesai: ta.tanggal_selesai,
            keterangan: ta.keterangan || '',
        });
        setEditOpen(true);
    };
    const submitEdit = (e) => {
        e.preventDefault();
        editForm.transform((d) => ({ ...d, _method: 'put' }));
        editForm.post(route('admin.tahun-ajaran.update', editId), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    // ---- Modal Tutup Semester ----
    const [tutupOpen, setTutupOpen] = useState(false);
    const [tutupLabel, setTutupLabel] = useState('');
    const [tutupId, setTutupId] = useState(null);
    const tutupForm = useForm({ tahun_ajaran_baru_id: '' });
    const openTutup = () => {
        if (!aktif) return;
        setTutupId(aktif.id);
        setTutupLabel(aktif.label_lengkap);
        tutupForm.clearErrors();
        tutupForm.setData('tahun_ajaran_baru_id', '');
        setTutupOpen(true);
    };
    const submitTutup = (e) => {
        e.preventDefault();
        if (!confirm('Tutup semester ini dan aktifkan semester berikutnya? Semester yang ditutup TIDAK BISA dipakai lagi.')) return;
        tutupForm.post(route('admin.tahun-ajaran.tutup-semester', tutupId), {
            preserveScroll: true,
            onSuccess: () => setTutupOpen(false),
        });
    };

    // ---- Aksi baris ----
    const setAktifAction = (ta) => {
        if (confirm(`Jadikan ${ta.label_lengkap} sebagai semester aktif?`)) {
            router.patch(route('admin.tahun-ajaran.set-aktif', ta.id), {}, { preserveScroll: true });
        }
    };
    const destroyAction = (ta) => {
        if (confirm(`Hapus ${ta.label_lengkap}? Data presensi/setoran yang terikat tidak akan bisa dihapus.`)) {
            router.delete(route('admin.tahun-ajaran.destroy', ta.id), { preserveScroll: true });
        }
    };

    // Hanya tahun ajaran yang BELUM PERNAH dipakai yang boleh diaktifkan berikutnya
    const kandidatList = tahunAjarans.filter((ta) => !ta.is_aktif && !ta.sudah_dipakai);

    return (
        <AuthenticatedLayout>
            <Head title="Tahun Ajaran" />
            <style>{`
                input[type="date"]{color-scheme:light}
                html.dark input[type="date"]{color-scheme:dark}
                html.dark select{color-scheme:dark}
                html.dark select option{background:#292623;color:#f5f5f4}
                select{
                    -webkit-appearance:none;-moz-appearance:none;appearance:none;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2378716c' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 0.75rem center;
                    background-size:1rem;
                    padding-right:2.25rem;
                }
            `}</style>

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
            <div className="mb-6 flex items-center gap-3.5">
                <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-claude-400 to-claude-600 text-white shadow-lg shadow-claude-500/25">
                    <svg className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <div>
                    <h1 className="text-xl font-bold tracking-tight text-stone-900 dark:text-cream-50">Tahun Ajaran</h1>
                    <p className="mt-0.5 text-xs text-stone-500 dark:text-stone-400">Kelola periode semester dan proses tutup semester</p>
                </div>
            </div>

            {/* ===== Banner Semester Aktif ===== */}
            {aktif && (
                <div className="mb-5 rounded-2xl bg-gradient-to-r from-claude-500 to-claude-600 p-4 shadow-lg shadow-claude-500/25">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex min-w-0 flex-1 items-center gap-3">
                            <div className="h-2 w-2 flex-shrink-0 animate-pulse rounded-full bg-emerald-300" />
                            <div className="min-w-0">
                                <p className="mb-0.5 text-[10px] text-claude-100">Semester Aktif Sekarang</p>
                                <p className="truncate text-sm font-bold text-white">{aktif.label_lengkap}</p>
                                <p className="mt-0.5 text-[10px] text-claude-100">{aktif.mulai_fmt} — {aktif.selesai_fmt}</p>
                            </div>
                        </div>
                        <button onClick={openTutup} className="flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-white/25 bg-white/15 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/25 active:scale-95">
                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <span className="hidden sm:inline">Tutup Semester</span>
                            <span className="sm:hidden">Tutup</span>
                        </button>
                    </div>
                </div>
            )}

            {/* ===== Toggle form (mobile) ===== */}
            <div className="mb-3 lg:hidden">
                <button onClick={() => setShowFormMobile((v) => !v)} className="flex w-full items-center justify-between rounded-xl border border-cream-300 bg-white px-4 py-3 text-sm font-semibold text-stone-700 transition hover:border-claude-300 hover:text-claude-600 dark:border-coal-600 dark:bg-coal-700 dark:text-stone-300 dark:hover:border-claude-500/40 dark:hover:text-claude-300">
                    <span className="flex items-center gap-2">
                        <svg className="h-4 w-4 text-claude-500 dark:text-claude-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
                        Tambah Tahun Ajaran
                    </span>
                    <svg className={`h-4 w-4 text-stone-400 transition-transform ${showFormMobile ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-3">
                {/* ===== KIRI: Form Tambah ===== */}
                <div className="lg:col-span-1">
                    <div className={`${showFormMobile ? 'block' : 'hidden'} rounded-xl border border-cream-300 bg-white p-4 shadow-sm lg:block dark:border-coal-700 dark:bg-coal-800 dark:shadow-none`}>
                        <h2 className="mb-4 hidden text-xs font-bold uppercase tracking-wider text-stone-500 lg:block dark:text-stone-400">Tambah Tahun Ajaran</h2>
                        <form onSubmit={submitCreate} className="space-y-3">
                            <div>
                                <label className={labelCls}>Nama <span className="text-red-500">*</span></label>
                                <input type="text" value={createForm.data.nama} onChange={(e) => createForm.setData('nama', e.target.value)} placeholder="Contoh: 2025/2026" className={inputCls} />
                                {createForm.errors.nama && <p className="mt-1 text-[10px] text-red-500">{createForm.errors.nama}</p>}
                            </div>
                            <div>
                                <label className={labelCls}>Semester <span className="text-red-500">*</span></label>
                                <select value={createForm.data.semester} onChange={(e) => createForm.setData('semester', e.target.value)} className={inputCls}>
                                    <option value="1">Semester 1 (Ganjil)</option>
                                    <option value="2">Semester 2 (Genap)</option>
                                </select>
                                {createForm.errors.semester && <p className="mt-1 text-[10px] text-red-500">{createForm.errors.semester}</p>}
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className={labelCls}>Tgl Mulai <span className="text-red-500">*</span></label>
                                    <input type="date" value={createForm.data.tanggal_mulai} onChange={(e) => createForm.setData('tanggal_mulai', e.target.value)} className={inputCls} />
                                    {createForm.errors.tanggal_mulai && <p className="mt-1 text-[10px] text-red-500">{createForm.errors.tanggal_mulai}</p>}
                                </div>
                                <div>
                                    <label className={labelCls}>Tgl Selesai <span className="text-red-500">*</span></label>
                                    <input type="date" value={createForm.data.tanggal_selesai} onChange={(e) => createForm.setData('tanggal_selesai', e.target.value)} className={inputCls} />
                                    {createForm.errors.tanggal_selesai && <p className="mt-1 text-[10px] text-red-500">{createForm.errors.tanggal_selesai}</p>}
                                </div>
                            </div>
                            <div>
                                <label className={labelCls}>Keterangan</label>
                                <input type="text" value={createForm.data.keterangan} onChange={(e) => createForm.setData('keterangan', e.target.value)} placeholder="Opsional..." className={inputCls} />
                            </div>
                            <button type="submit" disabled={createForm.processing} className="w-full rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 py-2.5 text-xs font-bold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-400 hover:to-claude-500 active:scale-95 disabled:opacity-60">
                                {createForm.processing ? 'Menyimpan…' : 'Tambah Tahun Ajaran'}
                            </button>
                        </form>
                    </div>
                </div>

                {/* ===== KANAN: Daftar ===== */}
                <div className="space-y-2 sm:space-y-3 lg:col-span-2">
                    <div className="mb-1 hidden items-center justify-between lg:flex">
                        <h2 className="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Daftar Tahun Ajaran</h2>
                        <span className="text-[10px] text-stone-400 dark:text-stone-600">{tahunAjarans.length} periode</span>
                    </div>

                    {tahunAjarans.length === 0 ? (
                        <div className="rounded-xl border border-cream-300 bg-white p-8 text-center shadow-sm dark:border-coal-600 dark:bg-coal-700 dark:shadow-none">
                            <svg className="mx-auto mb-3 h-10 w-10 text-cream-400 dark:text-coal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <p className="text-xs text-stone-500 dark:text-stone-400">Belum ada tahun ajaran</p>
                            <p className="mt-1 text-[10px] text-stone-400 dark:text-stone-600">Tambahkan menggunakan form di samping.</p>
                        </div>
                    ) : (
                        tahunAjarans.map((ta) => (
                            <div
                                key={ta.id}
                                className={`rounded-xl border bg-white p-3 shadow-sm transition sm:p-4 dark:bg-coal-700 dark:shadow-none ${
                                    ta.is_aktif
                                        ? 'border-claude-400 dark:border-claude-500/40'
                                        : ta.sudah_dipakai
                                            ? 'border-cream-300 opacity-70 dark:border-coal-600'
                                            : 'border-cream-300 hover:border-cream-400 dark:border-coal-600 dark:hover:border-coal-500'
                                }`}
                            >
                                <div className="flex items-start gap-3">
                                    {/* Ikon status */}
                                    <div className="mt-0.5 flex-shrink-0">
                                        <div className={`flex h-8 w-8 items-center justify-center rounded-xl sm:h-9 sm:w-9 ${ta.is_aktif ? 'bg-claude-100 ring-1 ring-inset ring-claude-300 dark:bg-claude-500/20 dark:ring-claude-500/30' : 'bg-cream-100 ring-1 ring-inset ring-cream-300 dark:bg-coal-800 dark:ring-coal-600'}`}>
                                            {ta.is_aktif ? (
                                                <span className="h-2 w-2 animate-pulse rounded-full bg-emerald-500 dark:bg-emerald-400" />
                                            ) : ta.sudah_dipakai ? (
                                                <svg className="h-4 w-4 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            ) : (
                                                <svg className="h-4 w-4 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            )}
                                        </div>
                                    </div>

                                    {/* Info */}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-semibold text-stone-800 dark:text-stone-100">{ta.label_lengkap}</p>
                                            {ta.is_aktif ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-300 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30">
                                                    <span className="h-1 w-1 animate-pulse rounded-full bg-emerald-500 dark:bg-emerald-400" />
                                                    Aktif
                                                </span>
                                            ) : ta.sudah_dipakai ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-semibold text-stone-600 ring-1 ring-inset ring-stone-300 dark:bg-coal-800 dark:text-stone-300 dark:ring-coal-600">
                                                    <svg className="h-2.5 w-2.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                    Selesai · Arsip
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center rounded-full bg-cream-100 px-2 py-0.5 text-[10px] text-stone-500 ring-1 ring-inset ring-cream-300 dark:bg-coal-800 dark:text-stone-400 dark:ring-coal-600">Belum Dipakai</span>
                                            )}
                                        </div>
                                        <p className="mt-0.5 text-[10px] text-stone-500 dark:text-stone-400">{ta.mulai_fmt} — {ta.selesai_fmt}</p>
                                        {ta.sudah_dipakai && ta.ditutup_fmt && (
                                            <p className="mt-0.5 text-[10px] text-stone-400 dark:text-stone-500">Ditutup {ta.ditutup_fmt} · tidak dapat dipakai lagi</p>
                                        )}
                                        {ta.keterangan && <p className="mt-0.5 text-[10px] italic text-stone-400 dark:text-stone-500">{ta.keterangan}</p>}
                                        <div className="mt-1.5 flex items-center gap-3">
                                            <span className="text-[10px] text-stone-400 dark:text-stone-500"><span className="font-medium text-stone-700 dark:text-stone-300">{ta.presensis_count}</span> presensi</span>
                                            <span className="text-[10px] text-stone-400 dark:text-stone-500"><span className="font-medium text-stone-700 dark:text-stone-300">{ta.setorans_count}</span> setoran</span>
                                        </div>
                                    </div>

                                    {/* Aksi */}
                                    <div className="flex flex-shrink-0 items-center gap-1">
                                        {!ta.is_aktif && !ta.sudah_dipakai && (
                                            <button onClick={() => setAktifAction(ta)} title="Jadikan Aktif" className="flex h-7 w-7 items-center justify-center rounded-lg border border-transparent text-stone-400 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-500 sm:h-8 sm:w-8 dark:text-stone-500 dark:hover:border-emerald-400/30 dark:hover:bg-emerald-400/10 dark:hover:text-emerald-400">
                                                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </button>
                                        )}
                                        <button onClick={() => openEdit(ta)} title={ta.sudah_dipakai ? 'Edit keterangan' : 'Edit'} className="flex h-7 w-7 items-center justify-center rounded-lg border border-transparent text-stone-400 transition hover:border-claude-300 hover:bg-claude-50 hover:text-claude-500 sm:h-8 sm:w-8 dark:text-stone-500 dark:hover:border-claude-500/30 dark:hover:bg-claude-500/10 dark:hover:text-claude-400">
                                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        {!ta.is_aktif && !ta.sudah_dipakai && (
                                            <button onClick={() => destroyAction(ta)} title="Hapus" className="flex h-7 w-7 items-center justify-center rounded-lg border border-transparent text-stone-400 transition hover:border-red-300 hover:bg-red-50 hover:text-red-500 sm:h-8 sm:w-8 dark:text-stone-500 dark:hover:border-red-400/30 dark:hover:bg-red-400/10 dark:hover:text-red-400">
                                                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            {/* ===== MODAL EDIT ===== */}
            {editOpen && (
                <div className="fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4">
                    <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setEditOpen(false)} />
                    <div className="relative w-full rounded-t-2xl border border-cream-300 bg-white p-4 shadow-2xl sm:max-w-md sm:rounded-2xl sm:p-5 dark:border-coal-600 dark:bg-coal-700">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-cream-50">
                                <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-claude-100 ring-1 ring-inset ring-claude-300 dark:bg-claude-500/15 dark:ring-claude-500/30">
                                    <svg className="h-3.5 w-3.5 text-claude-600 dark:text-claude-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </span>
                                Edit Tahun Ajaran
                            </h3>
                            <button onClick={() => setEditOpen(false)} className="flex h-7 w-7 items-center justify-center rounded-lg bg-cream-200 transition hover:bg-cream-300 dark:bg-coal-600 dark:hover:bg-coal-500">
                                <svg className="h-3.5 w-3.5 text-stone-500 dark:text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {editLocked && (
                            <div className="mb-3 rounded-xl border border-cream-300 bg-cream-100 p-3 dark:border-coal-600 dark:bg-coal-800/50">
                                <p className="text-[10px] text-stone-500 dark:text-stone-400">
                                    Semester ini sudah ditutup dan menjadi arsip. Nama, semester, dan periode terkunci agar data historis tetap konsisten — hanya keterangan yang bisa diubah.
                                </p>
                            </div>
                        )}

                        <form onSubmit={submitEdit} className="space-y-3">
                            <div>
                                <label className={labelCls}>Nama</label>
                                <input type="text" value={editForm.data.nama} onChange={(e) => editForm.setData('nama', e.target.value)} disabled={editLocked} placeholder="Contoh: 2025/2026" className={`${inputCls} ${inputDisabledCls}`} />
                                {editForm.errors.nama && <p className="mt-1 text-[10px] text-red-500">{editForm.errors.nama}</p>}
                            </div>
                            <div>
                                <label className={labelCls}>Semester</label>
                                <select value={editForm.data.semester} onChange={(e) => editForm.setData('semester', e.target.value)} disabled={editLocked} className={`${inputCls} ${inputDisabledCls}`}>
                                    <option value="1">Semester 1 (Ganjil)</option>
                                    <option value="2">Semester 2 (Genap)</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className={labelCls}>Tgl Mulai</label>
                                    <input type="date" value={editForm.data.tanggal_mulai} onChange={(e) => editForm.setData('tanggal_mulai', e.target.value)} disabled={editLocked} className={`${inputCls} ${inputDisabledCls}`} />
                                    {editForm.errors.tanggal_mulai && <p className="mt-1 text-[10px] text-red-500">{editForm.errors.tanggal_mulai}</p>}
                                </div>
                                <div>
                                    <label className={labelCls}>Tgl Selesai</label>
                                    <input type="date" value={editForm.data.tanggal_selesai} onChange={(e) => editForm.setData('tanggal_selesai', e.target.value)} disabled={editLocked} className={`${inputCls} ${inputDisabledCls}`} />
                                    {editForm.errors.tanggal_selesai && <p className="mt-1 text-[10px] text-red-500">{editForm.errors.tanggal_selesai}</p>}
                                </div>
                            </div>
                            <div>
                                <label className={labelCls}>Keterangan</label>
                                <input type="text" value={editForm.data.keterangan} onChange={(e) => editForm.setData('keterangan', e.target.value)} placeholder="Opsional..." className={inputCls} />
                            </div>
                            <div className="flex gap-2 border-t border-cream-300 pt-2 dark:border-coal-600">
                                <button type="button" onClick={() => setEditOpen(false)} className="flex-1 rounded-xl border border-cream-400 bg-cream-200 py-2.5 text-xs font-semibold text-stone-700 transition hover:bg-cream-300 dark:border-coal-500 dark:bg-coal-600 dark:text-stone-200 dark:hover:bg-coal-500">Batal</button>
                                <button type="submit" disabled={editForm.processing} className="flex-1 rounded-xl bg-gradient-to-r from-claude-500 to-claude-600 py-2.5 text-xs font-bold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-400 hover:to-claude-500 active:scale-95 disabled:opacity-60">
                                    {editForm.processing ? 'Menyimpan…' : 'Simpan Perubahan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* ===== MODAL TUTUP SEMESTER ===== */}
            {tutupOpen && (
                <div className="fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4">
                    <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setTutupOpen(false)} />
                    <div className="relative w-full rounded-t-2xl border border-cream-300 bg-white p-4 shadow-2xl sm:max-w-md sm:rounded-2xl sm:p-5 dark:border-coal-600 dark:bg-coal-700">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-cream-50">
                                <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-100 ring-1 ring-inset ring-amber-300 dark:bg-amber-400/15 dark:ring-amber-400/40">
                                    <svg className="h-3.5 w-3.5 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </span>
                                Tutup Semester
                            </h3>
                            <button onClick={() => setTutupOpen(false)} className="flex h-7 w-7 items-center justify-center rounded-lg bg-cream-200 transition hover:bg-cream-300 dark:bg-coal-600 dark:hover:bg-coal-500">
                                <svg className="h-3.5 w-3.5 text-stone-500 dark:text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div className="mb-3 rounded-xl border border-amber-300 bg-amber-50 p-3 dark:border-amber-400/30 dark:bg-amber-400/10">
                            <p className="mb-2 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">⚠️ Proses ini akan:</p>
                            <ul className="list-inside list-disc space-y-0.5 text-[10px] text-amber-700/80 dark:text-amber-300/70">
                                <li>Menutup riwayat kelas semester ini untuk semua santri</li>
                                <li>Membuat riwayat kelas baru di semester berikutnya</li>
                                <li>Menyalin target hafalan ke semester berikutnya</li>
                                <li>Mengaktifkan tahun ajaran yang dipilih</li>
                                <li><strong>Mengunci semester ini permanen — tidak bisa dipakai lagi</strong></li>
                            </ul>
                        </div>

                        <div className="mb-3 flex items-center gap-2 rounded-xl border border-cream-300 bg-cream-100 px-3 py-2 dark:border-coal-600 dark:bg-coal-800/50">
                            <span className="text-[10px] text-stone-500 dark:text-stone-400">Menutup:</span>
                            <span className="text-xs font-semibold text-stone-900 dark:text-cream-50">{tutupLabel}</span>
                        </div>

                        <form onSubmit={submitTutup} className="space-y-3">
                            <div>
                                <label className={labelCls}>Aktifkan Semester Berikutnya <span className="text-red-500">*</span></label>
                                <select
                                    value={tutupForm.data.tahun_ajaran_baru_id}
                                    onChange={(e) => tutupForm.setData('tahun_ajaran_baru_id', e.target.value)}
                                    disabled={kandidatList.length === 0}
                                    className={`w-full rounded-xl border border-cream-400 bg-white px-3 py-2.5 text-sm text-stone-900 outline-none transition focus:border-transparent focus:ring-2 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-coal-500 dark:bg-coal-800 dark:text-cream-50`}
                                >
                                    <option value="">— Pilih Tahun Ajaran —</option>
                                    {kandidatList.map((ta) => (
                                        <option key={ta.id} value={ta.id}>{ta.label_lengkap}</option>
                                    ))}
                                </select>
                                {tutupForm.errors.tahun_ajaran_baru_id && <p className="mt-1 text-[10px] text-red-500">{tutupForm.errors.tahun_ajaran_baru_id}</p>}
                                {kandidatList.length === 0 ? (
                                    <p className="mt-1 text-[10px] font-medium text-red-500">
                                        Tidak ada tahun ajaran yang bisa dipakai. Semua periode lain sudah pernah ditutup — buat periode baru terlebih dahulu.
                                    </p>
                                ) : (
                                    <p className="mt-1 text-[10px] text-stone-400 dark:text-stone-500">Hanya periode yang belum pernah dipakai yang muncul di sini.</p>
                                )}
                            </div>
                            <div className="flex gap-2 border-t border-cream-300 pt-2 dark:border-coal-600">
                                <button type="button" onClick={() => setTutupOpen(false)} className="flex-1 rounded-xl border border-cream-400 bg-cream-200 py-2.5 text-xs font-semibold text-stone-700 transition hover:bg-cream-300 dark:border-coal-500 dark:bg-coal-600 dark:text-stone-200 dark:hover:bg-coal-500">Batal</button>
                                <button type="submit" disabled={tutupForm.processing || kandidatList.length === 0} className="flex-1 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 py-2.5 text-xs font-bold text-white transition hover:from-amber-400 hover:to-amber-500 active:scale-95 disabled:opacity-60">
                                    {tutupForm.processing ? 'Memproses…' : 'Tutup Semester'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
import KeuanganLayout from '@/Layouts/KeuanganLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';
import TombolKembali from '@/Components/TombolKembali';

export default function FormRole({ role, rolePermissions, groupedPermissions }) {
    const isEditing = !!role;

    // Flag dikirim backend (RoleController@tandai), bukan hasil membandingkan
    // nama role di frontend — supaya daftar role terkunci cukup diatur
    // di config/modules.php.
    const isSuper = !!role?.is_super;   // super-admin: nama & permission dikunci total
    const isLocked = !!role?.is_locked; // nama dikunci, permission masih boleh diubah

    const { data, setData, post, put, processing, errors } = useForm({
        name: role?.name || '',
        permissions: rolePermissions || [], // Array string nama permission
    });

    const submit = (e) => {
        e.preventDefault();

        // Backend tetap menolak, ini hanya mencegah request sia-sia.
        if (isSuper) return;

        if (isEditing) put(route('keuangan.pengaturan.role.update', role.id));
        else post(route('keuangan.pengaturan.role.store'));
    };

    const handleCheckboxChange = (permissionName) => {
        if (isSuper) return;

        if (data.permissions.includes(permissionName)) {
            setData('permissions', data.permissions.filter((p) => p !== permissionName));
        } else {
            setData('permissions', [...data.permissions, permissionName]);
        }
    };

    const handleGroupCheckbox = (groupPerms, isChecked) => {
        if (isSuper) return;

        const groupPermNames = groupPerms.map((p) => p.name);

        if (isChecked) {
            setData('permissions', [...new Set([...data.permissions, ...groupPermNames])]);
        } else {
            setData('permissions', data.permissions.filter((p) => !groupPermNames.includes(p)));
        }
    };

    // 'pembayaran.approve_diperiksa' → 'Approve Diperiksa'
    const formatLabel = (name) => {
        const parts = name.split('.');
        if (parts.length < 2) return name;

        // Prefix yang hanya kategori umum — sudah diwakili judul kartu modul,
        // jadi aman dibuang. Selain ini (tunjangan, potongan, pembayaran)
        // dipertahankan utuh karena memang nama sub-modul.
        const genericPrefixes = ['master', 'sistem', 'laporan'];
        const labelParts = genericPrefixes.includes(parts[0]) ? parts.slice(1) : parts;

        return labelParts.join(' ')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (l) => l.toUpperCase());
    };

    const totalDipilih = data.permissions.length;

    const checkboxCls = 'h-4 w-4 rounded border-stone-300 bg-white text-claude-500 transition focus:ring-2 focus:ring-claude-500 focus:ring-offset-0 disabled:opacity-40 dark:border-coal-500 dark:bg-coal-900';

    return (
        <KeuanganLayout>
            <Head title={isEditing ? 'Edit Role' : 'Buat Role'} />

            <TombolKembali />

            <div className="mb-5 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-lg font-bold text-stone-800 dark:text-cream-50 sm:text-xl">
                        {isEditing ? 'Edit Hak Akses' : 'Buat Role Baru'}
                    </h1>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tentukan nama peran dan izin fitur yang dimilikinya.</p>
                </div>

                <Link href={route('keuangan.pengaturan.role.index')}
                    className="hidden h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 text-sm font-medium text-stone-700 ring-1 ring-stone-200 transition hover:bg-stone-50 sm:inline-flex dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:hover:bg-coal-600">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Daftar
                </Link>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Informasi Peran</h2>
                    <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Tanda <span className="text-red-500">*</span> berarti wajib diisi.</p>

                    <div className="mt-5 max-w-xl">
                        <InputLabel htmlFor="name">Nama Role <span className="text-red-500">*</span></InputLabel>
                        <TextInput id="name" value={data.name} onChange={(e) => setData('name', e.target.value)}
                            className="mt-1.5 block w-full" placeholder="Contoh: staf-tata-usaha" disabled={isLocked} />
                        <InputError message={errors.name} className="mt-1.5" />

                        {isLocked && (
                            <p className="mt-2 rounded-xl bg-amber-50 p-3 text-xs leading-relaxed text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                {isSuper
                                    ? 'Role super-admin adalah bawaan sistem: nama maupun hak aksesnya tidak dapat diubah dari sini. Aksesnya dijamin lewat Gate::before, bukan lewat tabel permission.'
                                    : `Nama role “${role.name}” dipakai middleware dan role-gating raport, jadi dikunci. Hak aksesnya masih bisa disesuaikan.`}
                            </p>
                        )}
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-5 ring-1 ring-stone-200 dark:bg-coal-700 dark:ring-coal-600 sm:p-6">
                    <div className="flex flex-col gap-2 border-b border-stone-100 pb-5 dark:border-coal-600 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 className="text-base font-semibold text-stone-800 dark:text-cream-50">Konfigurasi Hak Akses</h2>
                            <p className="mt-1 text-sm text-stone-500 dark:text-stone-400">Centang izin fitur yang boleh diakses peran ini.</p>
                        </div>
                        <span className="inline-flex h-fit shrink-0 rounded-full bg-claude-500/10 px-3 py-1 text-xs font-medium text-claude-600 ring-1 ring-claude-500/20 dark:text-claude-300">
                            {totalDipilih} izin dipilih
                        </span>
                    </div>

                    <InputError message={errors.permissions} className="mt-3" />

                    <div className={`mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 ${isSuper ? 'opacity-60' : ''}`}>
                        {Object.entries(groupedPermissions).map(([groupName, perms]) => {
                            const isAllChecked = perms.every((p) => data.permissions.includes(p.name));
                            const jumlahAktif = perms.filter((p) => data.permissions.includes(p.name)).length;

                            return (
                                <div key={groupName} className="flex flex-col overflow-hidden rounded-xl ring-1 ring-stone-200 transition hover:ring-claude-500/30 dark:ring-coal-600">
                                    <div className="flex items-center justify-between gap-2 border-b border-stone-100 bg-stone-50 px-4 py-3 dark:border-coal-600 dark:bg-coal-800">
                                        <div className="min-w-0">
                                            <h3 className="truncate text-sm font-semibold text-stone-800 dark:text-cream-100">{groupName}</h3>
                                            <p className="text-[10px] text-stone-400">{jumlahAktif} dari {perms.length}</p>
                                        </div>
                                        <label className="flex shrink-0 cursor-pointer items-center gap-1.5">
                                            <input type="checkbox" checked={isAllChecked} disabled={isSuper}
                                                onChange={(e) => handleGroupCheckbox(perms, e.target.checked)}
                                                className={checkboxCls} />
                                            <span className="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Semua</span>
                                        </label>
                                    </div>

                                    <div className="flex-1 space-y-2.5 p-4">
                                        {perms.map((p) => {
                                            const aktif = data.permissions.includes(p.name);
                                            return (
                                                <label key={p.id} className="flex cursor-pointer items-start gap-2.5">
                                                    <input type="checkbox" checked={aktif} disabled={isSuper}
                                                        onChange={() => handleCheckboxChange(p.name)}
                                                        className={`mt-0.5 ${checkboxCls}`} />
                                                    <span className={`text-sm transition ${
                                                        aktif
                                                            ? 'font-medium text-claude-600 dark:text-claude-300'
                                                            : 'text-stone-600 dark:text-stone-400'
                                                    }`}>
                                                        {formatLabel(p.name)}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* flex-col-reverse: di mobile tombol simpan naik ke atas
                        dan melebar penuh agar mudah dijangkau ibu jari. */}
                    <div className="mt-6 flex flex-col-reverse gap-2 border-t border-stone-100 pt-5 dark:border-coal-600 sm:flex-row sm:items-center sm:justify-end">
                        <Link href={route('keuangan.pengaturan.role.index')}
                            className="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-stone-700 ring-1 ring-stone-200 transition hover:bg-stone-50 dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:hover:bg-coal-600">
                            Batal
                        </Link>
                        <PrimaryButton type="submit" disabled={processing || isSuper} className="justify-center">
                            {processing ? 'Menyimpan…' : (isEditing ? 'Simpan Perubahan' : 'Buat Role Baru')}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </KeuanganLayout>
    );
}
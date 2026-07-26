import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import LogoutButton from '@/Components/LogoutButton';
import AppLogo from '@/Components/AppLogo';

function ThemeToggle({ dark, onToggle }) {
    return (
        <button
            type="button"
            onClick={onToggle}
            aria-label="Ganti tema"
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-300 ${dark ? 'bg-claude-500' : 'bg-stone-300'}`}
        >
            <span className={`pointer-events-none mt-0.5 inline-flex h-5 w-5 transform items-center justify-center rounded-full bg-white shadow transition-transform duration-300 ${dark ? 'translate-x-[22px]' : 'translate-x-0.5'}`}>
                {dark ? (
                    <svg className="h-3 w-3 text-claude-600" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" /></svg>
                ) : (
                    <svg className="h-3 w-3 text-amber-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" /><path strokeLinecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
                )}
            </span>
        </button>
    );
}

const ROLE_LABEL = {
    'super-admin': 'Super Admin',
    'bendahara': 'Bendahara',
    'kepala-sekolah': 'Kepala Sekolah',
    'pihak-yayasan': 'Pihak Yayasan',
    'hrd': 'HRD',
};

const I = {
    dash: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    master: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    masuk: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    keluar: 'M17 13l-5 5m0 0l-5-5m5 5V6',
    gaji: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    laporan: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    setting: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    profil: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
};

export default function KeuanganLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const permissions = auth?.permissions || [];
    const can = (p) => permissions.includes(p);

    // Kolom users.role berisi role modul raport ('admin'), bukan role
    // keuangan. Label diturunkan dari permission yang khas tiap role.
    const roleLabel =
        can('sistem.kelola_role')            ? 'Super Admin'
        : can('pengeluaran.approve_disetujui') ? 'Pihak Yayasan'
        : can('pengeluaran.approve_diperiksa') ? 'Kepala Sekolah'
        : can('penggajian.generate_batch')     ? 'HRD'
        : can('tagihan.buat_batch')            ? 'Bendahara'
        : 'Keuangan';

    // Modul keuangan diport bertahap. Menu yang route-nya belum ada
    // disembunyikan otomatis — tanpa ini Ziggy melempar error dan
    // seluruh panel jadi blank.
    const has = (name) => {
        try { return route().has(name); } catch (e) { return false; }
    };

    const [open, setOpen] = useState(false);

    const [collapsed, setCollapsed] = useState(() => {
        if (typeof window === 'undefined') return false;
        return localStorage.getItem('sidebar-collapsed') === '1';
    });
    useEffect(() => {
        try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
    }, [collapsed]);

    const [dark, setDark] = useState(() => {
        if (typeof window === 'undefined') return false;
        return document.documentElement.classList.contains('dark') || localStorage.getItem('theme') === 'dark';
    });
    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
    }, [dark]);

    const closeNav = () => setOpen(false);

    const GROUPS = [
        {
            key: 'master', label: 'Master Data', icon: I.master, prefix: 'keuangan.master-data.',
            items: [
                { name: 'keuangan.master-data.kelas.index', label: 'Data Kelas', perm: 'master.kelas.lihat' },
                { name: 'keuangan.master-data.siswa', label: 'Data Siswa', perm: 'master.siswa.lihat' },
                { name: 'keuangan.master-data.jabatan', label: 'Data Jabatan', perm: 'master.jabatan.lihat' },
                { name: 'keuangan.master-data.pegawai', label: 'Data Pegawai', perm: 'master.pegawai.lihat' },
                { name: 'keuangan.master-data.rekening', label: 'Data Rekening', perm: 'master.rekening.lihat' },
                { name: 'keuangan.master-data.tarif', label: 'Data Tarif', perm: 'master.tarif.lihat' },
                { name: 'keuangan.master-data.rkas_template.index', label: 'Jenis RKAS', perm: 'master.rkas.lihat' },
            ],
        },
        {
            key: 'pemasukan', label: 'Pemasukan', icon: I.masuk, prefix: 'keuangan.pemasukan.',
            items: [
                { name: 'keuangan.pemasukan.tagihan.index', label: 'Kelola Tagihan', perm: 'tagihan.lihat' },
                { name: 'keuangan.pemasukan.pembayaran.index', label: 'Pembayaran', perm: 'pembayaran.lihat' },
            ],
        },
        {
            key: 'pengeluaran', label: 'Anggaran & Pengeluaran', icon: I.keluar, prefix: 'keuangan.pengeluaran.',
            items: [
                { name: 'keuangan.pengeluaran.rkas', label: 'Kelola RKAS', perm: 'rkas.lihat' },
                { name: 'keuangan.pengeluaran.pengajuan', label: 'Kelola Pengeluaran', perm: 'pengeluaran.lihat' },
            ],
        },
        {
            key: 'penggajian', label: 'Penggajian', icon: I.gaji, prefix: 'keuangan.penggajian.',
            items: [
                { name: 'keuangan.penggajian.absensi', label: 'Data Absensi', perm: 'absensi.lihat' },
                { name: 'keuangan.penggajian.komponen', label: 'Komponen Gaji', perm: 'tunjangan.lihat' },
                { name: 'keuangan.penggajian.data', label: 'Kelola Gaji & Slip', perm: 'penggajian.lihat' },
            ],
        },
        {
            key: 'laporan', label: 'Laporan', icon: I.laporan, prefix: 'keuangan.laporan.',
            items: [
                { name: 'keuangan.laporan.buku_kas_umum', label: 'Buku Kas Umum', perm: 'laporan.buku_kas' },
                { name: 'keuangan.laporan.tunggakan', label: 'Laporan Tunggakan', perm: 'laporan.tunggakan' },
                { name: 'keuangan.laporan.realisasi_rkas', label: 'Realisasi RKAS', perm: 'laporan.rkas' },
            ],
        },
        {
            key: 'pengaturan', label: 'Pengaturan Sistem', icon: I.setting, prefix: 'keuangan.pengaturan.',
            items: [
                { name: 'keuangan.pengaturan.role.index', label: 'Manajemen Role', perm: 'sistem.kelola_role' },
                { name: 'keuangan.pengaturan.user.index', label: 'Manajemen User', perm: 'sistem.kelola_user' },
            ],
        },
    ]
        .map((g) => ({ ...g, items: g.items.filter((i) => can(i.perm) && has(i.name)) }))
        .filter((g) => g.items.length > 0);

    const [openGroups, setOpenGroups] = useState(() => {
        const init = {};
        GROUPS.forEach((g) => {
            try { init[g.key] = route().current(g.prefix + '*'); } catch (e) { init[g.key] = false; }
        });
        return init;
    });

    const toggleGroup = (key) => setOpenGroups((s) => ({ ...s, [key]: !s[key] }));

    const isCurrent = (name) => {
        try { return route().current(name); } catch (e) { return false; }
    };

    const SidebarBody = ({ isMini = false }) => (
        <div className="flex h-full flex-col">
            <div className={`flex h-16 items-center px-3 ${isMini ? 'justify-center' : 'justify-between'}`}>
                <div className={`flex items-center gap-2.5 ${isMini ? '' : 'pl-2'}`}>
                    <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-cream-200 ring-1 ring-cream-300 dark:bg-coal-600 dark:ring-coal-500">
                        <AppLogo className="h-5 w-5" />
                    </div>
                    {!isMini && (
                        <div className="leading-tight">
                            <p className="text-sm font-bold text-stone-800 dark:text-cream-50">Laporan Tahfizh</p>
                            <p className="text-[10px] text-stone-400 dark:text-stone-500">Panel Keuangan</p>
                        </div>
                    )}
                </div>
                {!isMini && (
                    <button onClick={() => setCollapsed(true)} title="Ciutkan sidebar"
                        className="hidden h-8 w-8 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 lg:flex dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                    </button>
                )}
            </div>

            {isMini && (
                <div className="flex justify-center pb-1">
                    <button onClick={() => setCollapsed(false)} title="Lebarkan sidebar"
                        className="flex h-8 w-8 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                    </button>
                </div>
            )}

            <nav className={`sidebar-scroll flex-1 space-y-1 overflow-y-auto py-3 ${isMini ? 'px-2' : 'px-3'}`}>
                <Link href={route('keuangan.dashboard')} onClick={closeNav}
                    title={isMini ? 'Dashboard' : undefined}
                    className={`group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${isMini ? 'justify-center' : ''} ${
                        isCurrent('keuangan.dashboard')
                            ? 'bg-gradient-to-r from-claude-500 to-claude-600 text-white shadow-lg shadow-claude-500/25'
                            : 'text-stone-500 hover:bg-cream-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-white'
                    }`}>
                    <svg className={`h-[18px] w-[18px] flex-shrink-0 ${isCurrent('keuangan.dashboard') ? 'text-white' : 'text-stone-400 dark:text-stone-500'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={I.dash} />
                    </svg>
                    {!isMini && <span className="truncate">Dashboard</span>}
                </Link>

                {GROUPS.map((g) => {
                    const aktif = (() => { try { return route().current(g.prefix + '*'); } catch (e) { return false; } })();

                    // Mini: satu ikon per grup, klik melebarkan sidebar.
                    if (isMini) {
                        return (
                            <button key={g.key} onClick={() => { setCollapsed(false); setOpenGroups((s) => ({ ...s, [g.key]: true })); }}
                                title={g.label}
                                className={`flex w-full items-center justify-center rounded-xl px-3 py-2.5 transition ${
                                    aktif ? 'bg-claude-500 text-white' : 'text-stone-400 hover:bg-cream-200 hover:text-stone-600 dark:text-stone-500 dark:hover:bg-coal-600/60'
                                }`}>
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={g.icon} />
                                </svg>
                            </button>
                        );
                    }

                    return (
                        <div key={g.key}>
                            <button onClick={() => toggleGroup(g.key)}
                                className={`flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                    aktif ? 'bg-cream-200 text-stone-900 dark:bg-coal-600/60 dark:text-white'
                                          : 'text-stone-500 hover:bg-cream-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-white'
                                }`}>
                                <span className="flex min-w-0 items-center gap-3">
                                    <svg className={`h-[18px] w-[18px] flex-shrink-0 ${aktif ? 'text-claude-500' : 'text-stone-400 dark:text-stone-500'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={g.icon} />
                                    </svg>
                                    <span className="truncate text-left">{g.label}</span>
                                </span>
                                <svg className={`h-4 w-4 flex-shrink-0 transition-transform duration-200 ${openGroups[g.key] ? 'rotate-180 text-claude-500' : 'text-stone-400'}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div className={`overflow-hidden transition-all duration-200 ${openGroups[g.key] ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'}`}>
                                <div className="mt-1 space-y-0.5 pl-9 pr-1">
                                    {g.items.map((it) => (
                                        <Link key={it.name} href={route(it.name)} onClick={closeNav}
                                            className={`block truncate rounded-lg px-3 py-2 text-sm transition ${
                                                isCurrent(it.name)
                                                    ? 'bg-claude-500/10 font-medium text-claude-600 dark:text-claude-300'
                                                    : 'text-stone-500 hover:bg-cream-200 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-stone-200'
                                            }`}>
                                            {it.label}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                    );
                })}

                {!isMini && (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Akun</p>
                )}
                {isMini && <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" />}
                <Link href={route('profile.edit')} onClick={closeNav} title={isMini ? 'Profil Saya' : undefined}
                    className={`group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${isMini ? 'justify-center' : ''} ${
                        isCurrent('profile.edit')
                            ? 'bg-gradient-to-r from-claude-500 to-claude-600 text-white shadow-lg shadow-claude-500/25'
                            : 'text-stone-500 hover:bg-cream-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-white'
                    }`}>
                    <svg className={`h-[18px] w-[18px] flex-shrink-0 ${isCurrent('profile.edit') ? 'text-white' : 'text-stone-400 dark:text-stone-500'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={I.profil} />
                    </svg>
                    {!isMini && <span className="truncate">Profil Saya</span>}
                </Link>
            </nav>

            <div className={`space-y-3 border-t border-cream-300 py-3 dark:border-coal-600 ${isMini ? 'px-2' : 'px-3'}`}>
                {isMini ? (
                    <div className="flex flex-col items-center gap-2">
                        <button onClick={() => setDark((d) => !d)} title={dark ? 'Dark Mode' : 'Light Mode'}
                            className="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300">
                            {dark ? (
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" /></svg>
                            ) : (
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" /><path strokeLinecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
                            )}
                        </button>
                        {user?.profile_photo ? (
                            <img src={`/storage/${user.profile_photo}`} alt={user?.name} title={user?.name}
                                className="h-9 w-9 flex-shrink-0 rounded-full object-cover object-top ring-2 ring-claude-500/20" />
                        ) : (
                            <span title={user?.name} className="flex h-9 w-9 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 dark:bg-claude-500/20 dark:text-claude-300">
                                {user?.name?.charAt(0)?.toUpperCase()}
                            </span>
                        )}
                        <LogoutButton title="Keluar" className="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-red-50 hover:text-red-600 dark:text-stone-500 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </LogoutButton>
                    </div>
                ) : (
                    <>
                        <div className="flex items-center justify-between rounded-xl px-3 py-2 text-sm text-stone-500 dark:text-stone-400">
                            <span className="flex items-center gap-2 font-medium">
                                {dark ? (
                                    <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" /></svg>
                                ) : (
                                    <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" /><path strokeLinecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
                                )}
                                {dark ? 'Dark Mode' : 'Light Mode'}
                            </span>
                            <ThemeToggle dark={dark} onToggle={() => setDark((d) => !d)} />
                        </div>

                        <div className="flex items-center gap-3 rounded-xl bg-cream-200/70 p-2.5 dark:bg-coal-600/50">
                            {user?.profile_photo ? (
                                <img src={`/storage/${user.profile_photo}`} alt={user?.name}
                                    className="h-9 w-9 flex-shrink-0 rounded-full object-cover object-top ring-2 ring-claude-500/20" />
                            ) : (
                                <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 dark:bg-claude-500/20 dark:text-claude-300">
                                    {user?.name?.charAt(0)?.toUpperCase()}
                                </span>
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-semibold text-stone-800 dark:text-cream-50">{user?.name}</p>
                                <p className="truncate text-[10px] text-stone-400 dark:text-stone-500">
                                    {roleLabel}
                                </p>
                            </div>
                            <LogoutButton title="Keluar" className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-stone-400 transition hover:bg-red-50 hover:text-red-600 dark:text-stone-500 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            </LogoutButton>
                        </div>
                    </>
                )}
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-cream-50 text-stone-900 dark:bg-coal-800 dark:text-cream-50">
            {open && <div className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" onClick={closeNav} />}

            <aside className={`fixed inset-y-0 left-0 z-50 w-64 border-r border-cream-300 bg-cream-100 transition-transform duration-300 lg:hidden dark:border-coal-600 dark:bg-coal-700 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
                <SidebarBody isMini={false} />
            </aside>

            <aside className={`fixed inset-y-0 left-0 z-50 hidden border-r border-cream-300 bg-cream-100 transition-all duration-300 lg:block dark:border-coal-600 dark:bg-coal-700 ${collapsed ? 'w-[76px]' : 'w-64'}`}>
                <SidebarBody isMini={collapsed} />
            </aside>

            <div className={`transition-all duration-300 ${collapsed ? 'lg:pl-[76px]' : 'lg:pl-64'}`}>
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-cream-300 bg-cream-100/80 px-4 backdrop-blur-xl lg:hidden dark:border-coal-600 dark:bg-coal-700/80">
                    <button onClick={() => setOpen(true)} className="flex h-9 w-9 items-center justify-center rounded-lg text-stone-600 hover:bg-cream-200 dark:text-stone-400 dark:hover:bg-coal-600">
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <span className="text-sm font-bold">Panel Keuangan</span>
                    <div className="ml-auto flex items-center">
                        <ThemeToggle dark={dark} onToggle={() => setDark((d) => !d)} />
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}
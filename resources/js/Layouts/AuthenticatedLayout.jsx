import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import LogoutButton from '@/Components/LogoutButton';
import AppLogo from '@/Components/AppLogo';
import LaporanNotifier from '@/Components/LaporanNotifier';

// Nama aplikasi untuk sidebar dan header mobile.
//
// Dibaca dari VITE_APP_NAME, bukan ditulis langsung, supaya salinan TK dan
// salinan pesantren cukup berbeda di .env dan tidak perlu berbeda berkas JSX
// — kalau namanya ditanam di sini, setiap penggabungan perubahan antar kedua
// salinan akan bentrok di baris ini terus-menerus.
//
// Perlu diingat: variabel VITE_ DIBAKAR ke dalam bundel saat build. Mengubah
// .env tidak berpengaruh apa-apa sampai `npm run build` dijalankan ulang
// (atau `npm run dev` dihentikan lalu dinyalakan lagi).
const NAMA_APP = import.meta.env.VITE_APP_NAME || 'TK Attauhid';

// ---- Toggle tema ----
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

function NavItem({ href, active, icon, children, onClick, collapsed, badge = 0 }) {
    return (
        <Link
            href={href}
            onClick={onClick}
            title={collapsed ? children : undefined}
            className={`group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${collapsed ? 'justify-center' : ''} ${
                active
                    ? 'bg-gradient-to-r from-claude-500 to-claude-600 text-white shadow-lg shadow-claude-500/25'
                    : 'text-stone-500 hover:bg-cream-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-white'
            }`}
        >
            <svg className={`h-[18px] w-[18px] flex-shrink-0 ${active ? 'text-white' : 'text-stone-400 group-hover:text-stone-600 dark:text-stone-500 dark:group-hover:text-stone-300'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={icon} />
            </svg>
            {!collapsed && <span className="truncate">{children}</span>}
            {!collapsed && badge > 0 && (
                <span className={`ml-auto rounded-full px-2 py-0.5 text-[10px] font-bold ${active ? 'bg-white/25 text-white' : 'bg-claude-500 text-white'}`}>
                    {badge > 99 ? '99+' : badge}
                </span>
            )}
            {collapsed && badge > 0 && (
                <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-claude-500 ring-2 ring-cream-100 dark:ring-coal-700" />
            )}
        </Link>
    );
}

// Jalur ikon SVG. Konstanta, jadi didefinisikan sekali di level modul
// alih-alih dibuat ulang setiap render.
const I = {
    dash: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    admin: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    tahun: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    guru: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    kelas: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    santri: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    sesi: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    monitoring: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    log: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    profil: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    komplain: 'M3 3v18M3 4.5h12.75l-2.25 3 2.25 3H3',
};

// isMini hanya berlaku di desktop; drawer mobile selalu penuh.
//
// Didefinisikan di luar AuthenticatedLayout dengan sengaja. Bila sebuah
// komponen dibuat ulang di dalam render induknya, React menganggapnya
// tipe komponen yang berbeda setiap kali, lalu membongkar dan memasang
// ulang seluruh pohonnya — posisi scroll menu hilang setiap kali tema
// diganti atau sidebar diciutkan.
function SidebarBody({
    isMini = false,
    user,
    isSuperAdmin,
    bisaKeKeuangan,
    management,
    laporan,
    dark,
    setDark,
    setCollapsed,
    closeNav,
}) {
    return (

        <div className="flex h-full flex-col">
            {/* Logo + tombol collapse */}
            <div className={`flex h-16 items-center px-3 ${isMini ? 'justify-center' : 'justify-between'}`}>
                <div className={`flex items-center gap-2.5 ${isMini ? '' : 'pl-2'}`}>
                    <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-cream-200 ring-1 ring-cream-300 dark:bg-coal-600 dark:ring-coal-500">
                        <AppLogo className="h-5 w-5" />
                    </div>
                    {!isMini && (
                        <div className="leading-tight">
                            <p className="text-sm font-bold text-stone-800 dark:text-cream-50">{NAMA_APP}</p>
                            <p className="text-[10px] text-stone-400 dark:text-stone-500">Panel Admin</p>
                        </div>
                    )}
                </div>
                {!isMini && (
                    <button
                        onClick={() => setCollapsed(true)}
                        title="Ciutkan sidebar"
                        className="hidden h-8 w-8 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 lg:flex dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300"
                    >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                    </button>
                )}
            </div>

            {/* Tombol expand saat mini */}
            {isMini && (
                <div className="flex justify-center pb-1">
                    <button
                        onClick={() => setCollapsed(false)}
                        title="Lebarkan sidebar"
                        className="flex h-8 w-8 items-center justify-center rounded-lg text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300"
                    >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                    </button>
                </div>
            )}

            {/* Nav */}
            <nav className={`sidebar-scroll flex-1 space-y-1 overflow-y-auto py-3 ${isMini ? 'px-2' : 'px-3'}`}>
                <NavItem href={route('admin.dashboard')} active={route().current('admin.dashboard')} icon={I.dash} onClick={closeNav} collapsed={isMini}>Dashboard</NavItem>

                {management.length > 0 && (
                    <>
                        {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                            <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Management</p>
                        )}
                        {management.map((m) => (
                            <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini}>{m.label}</NavItem>
                        ))}
                    </>
                )}

                {laporan.length > 0 && (
                    <>
                        {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                            <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Laporan & Log</p>
                        )}
                        {laporan.map((m) => (
                            <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini} badge={m.badge || 0}>{m.label}</NavItem>
                        ))}
                    </>
                )}

                {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Akun</p>
                )}
                <NavItem href={route('profile.edit')} active={route().current('profile.edit')} icon={I.profil} onClick={closeNav} collapsed={isMini}>Profil Saya</NavItem>
            </nav>

            {/* Bottom */}
            <div className={`space-y-3 border-t border-cream-300 py-3 dark:border-coal-600 ${isMini ? 'px-2' : 'px-3'}`}>
                {isMini ? (
                    <div className="flex flex-col items-center gap-2">
                        {bisaKeKeuangan && (
                            <a href={route('pindah-modul', 'keuangan')} title="Buka Panel Keuangan"
                                className="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-cream-200 hover:text-claude-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-claude-300">
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </a>
                        )}
                        {/* Toggle tema versi ikon */}
                        {/* Ikon menunjukkan tema yang SEDANG aktif, bukan aksinya —
                            supaya sama dengan baris "Dark Mode / Light Mode" di
                            sidebar lebar. Sebelumnya terbalik: mode gelap justru
                            menampilkan ikon matahari. */}
                        <button
                            onClick={() => setDark((d) => !d)}
                            title={dark ? 'Dark Mode' : 'Light Mode'}
                            className="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300"
                        >
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
                        {/* Pindah panel tanpa logout — pilihan tersimpan di session.
                            Pakai <a>, bukan <Link>: route ini me-redirect ke
                            /dashboard, bukan mengembalikan respons Inertia. */}
                        {bisaKeKeuangan && (
                            <a href={route('pindah-modul', 'keuangan')}
                                className="flex items-center gap-2.5 rounded-xl bg-cream-200/70 px-3 py-2.5 text-sm font-medium text-stone-600 transition hover:bg-cream-300 dark:bg-coal-600/50 dark:text-stone-300 dark:hover:bg-coal-600">
                                <svg className="h-[18px] w-[18px] flex-shrink-0 text-claude-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span className="truncate">Buka Panel Keuangan</span>
                            </a>
                        )}

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
                                <p className="truncate text-[10px] text-stone-400 dark:text-stone-500">{isSuperAdmin ? 'Super Admin' : 'Admin'}</p>
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
}

export default function AuthenticatedLayout({ children }) {
    const { auth, unreadLaporan, modulUser } = usePage().props;
    const user = auth?.user;
    const permissions = auth?.permissions || [];
    const can = (p) => permissions.includes(p);
    const isSuperAdmin = can('kelola-admin');

    // Tombol pindah panel hanya untuk akun yang memang punya akses keuangan.
    const bisaKeKeuangan = modulUser?.tersedia?.includes('keuangan');

    const [open, setOpen] = useState(false); // drawer mobile

    // Collapse desktop (persist)
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

    const management = [
        can('kelola-admin') && { href: route('admin.admins.index'), label: 'Kelola Admin', icon: I.admin, active: route().current('admin.admins.*') },
        can('kelola-admin') && { href: route('admin.tahun-ajaran.index'), label: 'Tahun Ajaran', icon: I.tahun, active: route().current('admin.tahun-ajaran.*') },
        can('kelola-guru') && { href: route('admin.gurus.index'), label: 'Guru', icon: I.guru, active: route().current('admin.gurus.*') },
        can('kelola-kelas') && { href: route('admin.kelas.index'), label: 'Kelas', icon: I.kelas, active: route().current('admin.kelas.*') },
        can('kelola-santri') && { href: route('admin.santris.index'), label: 'Santri', icon: I.santri, active: route().current('admin.santris.*') },
        can('kelola-presensi-sesi') && { href: route('admin.presensi-sesis.index'), label: 'Sesi Presensi', icon: I.sesi, active: route().current('admin.presensi-sesis.*') },
    ].filter(Boolean);

    const laporan = [
        can('lihat-monitoring') && { href: route('admin.monitoring.index'), label: 'Monitoring', icon: I.monitoring, active: route().current('admin.monitoring.*') },
        can('lihat-log-activity') && { href: route('admin.logs.index'), label: 'Log Activity', icon: I.log, active: route().current('admin.logs.*') },
        { href: route('admin.laporan.index'), label: 'Komplain Data', icon: I.komplain, active: route().current('admin.laporan.*'), badge: unreadLaporan },
    ].filter(Boolean);

    // WAJIB dideklarasikan SETELAH management dan laporan. Keduanya
    // memakai const, jadi membacanya lebih awal melempar ReferenceError
    // dan membuat seluruh halaman kosong tanpa pesan apa pun di layar.
    const sidebarProps = {
        user, isSuperAdmin, bisaKeKeuangan, management, laporan,
        dark, setDark, setCollapsed, closeNav,
    };

    return (
        <div className="min-h-screen bg-cream-50 text-stone-900 dark:bg-coal-800 dark:text-cream-50">
            {open && <div className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" onClick={closeNav} />}

            {/* Sidebar mobile (drawer, selalu penuh) */}
            <aside className={`fixed inset-y-0 left-0 z-50 w-64 border-r border-cream-300 bg-cream-100 transition-transform duration-300 lg:hidden dark:border-coal-600 dark:bg-coal-700 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
                <SidebarBody isMini={false} {...sidebarProps} />
            </aside>

            {/* Sidebar desktop (bisa collapse) */}
            <aside className={`fixed inset-y-0 left-0 z-50 hidden border-r border-cream-300 bg-cream-100 transition-all duration-300 lg:block dark:border-coal-600 dark:bg-coal-700 ${collapsed ? 'w-[76px]' : 'w-64'}`}>
                <SidebarBody isMini={collapsed} {...sidebarProps} />
            </aside>

            <div className={`transition-all duration-300 ${collapsed ? 'lg:pl-[76px]' : 'lg:pl-64'}`}>
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-cream-300 bg-cream-100/80 px-4 backdrop-blur-xl lg:hidden dark:border-coal-600 dark:bg-coal-700/80">
                    <button onClick={() => setOpen(true)} className="flex h-9 w-9 items-center justify-center rounded-lg text-stone-600 hover:bg-cream-200 dark:text-stone-400 dark:hover:bg-coal-600">
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    {/* Judul ini dan judul di sidebar harus selalu sama. Dulu keduanya
                        ditulis terpisah, jadi mengganti salah satu saja menyisakan
                        nama lama di tampilan HP. */}
                    <span className="truncate text-sm font-bold">{NAMA_APP}</span>

                    {/* Toggle tema mobile */}
                    <div className="ml-auto flex items-center">
                        <ThemeToggle dark={dark} onToggle={() => setDark((d) => !d)} />
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>

            <LaporanNotifier href={route('admin.laporan.index')} />

        </div>
    );
}
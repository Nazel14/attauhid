import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import LogoutButton from '@/Components/LogoutButton';
import AppLogo from '@/Components/AppLogo';


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

function NavItem({ href, active, icon, children, onClick, collapsed }) {
    return (
        <Link
            href={href}
            onClick={onClick}
            title={collapsed ? children : undefined}
            className={`group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${collapsed ? 'justify-center' : ''} ${
                active
                    ? 'bg-gradient-to-r from-claude-500 to-claude-600 text-white shadow-lg shadow-claude-500/25'
                    : 'text-stone-500 hover:bg-cream-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-white'
            }`}
        >
            <svg className={`h-[18px] w-[18px] flex-shrink-0 ${active ? 'text-white' : 'text-stone-400 group-hover:text-stone-600 dark:text-stone-500 dark:group-hover:text-stone-300'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={icon} />
            </svg>
            {!collapsed && <span className="truncate">{children}</span>}
        </Link>
    );
}

export default function SantriAuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [open, setOpen] = useState(false); // drawer mobile

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

    const I = {
        dash: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        rapot: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        setoran: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        presensi: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        profil: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    };

    // route() aman — kalau nama route belum ada, item disembunyikan (hindari blank page)
    const r = (name) => { try { return route(name); } catch { return null; } };
    const cur = (pattern) => { try { return route().current(pattern); } catch { return false; } };

    const closeNav = () => setOpen(false);

    // Menu utama santri
    const laporan = [
        { href: r('santri.rapot.show'),      label: 'Rapot',           icon: I.rapot,    active: cur('santri.rapot.*') },
        { href: r('santri.setorans.detail'), label: 'Detail Setoran',  icon: I.setoran,  active: cur('santri.setorans.*') },
        { href: r('santri.presensis.detail'),label: 'Detail Presensi', icon: I.presensi, active: cur('santri.presensis.*') },
    ].filter((m) => m.href);

    const akun = [
        { href: r('profile.edit'), label: 'Profil Saya', icon: I.profil, active: cur('profile.edit') },
    ].filter((m) => m.href);

    const SidebarBody = ({ isMini = false }) => (
        <div className="flex h-full flex-col">
            {/* Logo + tombol collapse */}
            <div className={`flex h-16 items-center px-3 ${isMini ? 'justify-center' : 'justify-between'}`}>
                <div className={`flex items-center gap-2.5 ${isMini ? '' : 'pl-2'}`}>
                    <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-cream-200 ring-1 ring-cream-300 dark:bg-coal-600 dark:ring-coal-500">
                        <AppLogo className="h-5 w-5" />
                    </div>
                    {!isMini && (
                        <div className="leading-tight">
                            <p className="text-sm font-bold text-stone-800 dark:text-cream-50">Laporan Tahfizh</p>
                            <p className="text-[10px] text-stone-400 dark:text-stone-500">Panel Santri</p>
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
                <NavItem href={r('santri.dashboard')} active={cur('santri.dashboard')} icon={I.dash} onClick={closeNav} collapsed={isMini}>Dashboard</NavItem>

                {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Laporan</p>
                )}
                {laporan.map((m) => (
                    <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini}>{m.label}</NavItem>
                ))}

                {akun.length > 0 && (
                    <>
                        {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                            <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Akun</p>
                        )}
                        {akun.map((m) => (
                            <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini}>{m.label}</NavItem>
                        ))}
                    </>
                )}
            </nav>

            {/* Bottom */}
            <div className={`space-y-3 border-t border-cream-300 py-3 dark:border-coal-600 ${isMini ? 'px-2' : 'px-3'}`}>
                {isMini ? (
                    <div className="flex flex-col items-center gap-2">
                        <button
                            onClick={() => setDark((d) => !d)}
                            title={dark ? 'Mode terang' : 'Mode gelap'}
                            className="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-cream-200 hover:text-stone-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-stone-300"
                        >
                            {dark ? (
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" /><path strokeLinecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
                            ) : (
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" /></svg>
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
                                <p className="truncate text-[10px] text-stone-400 dark:text-stone-500">Santri Tahfizh</p>
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
                    <span className="text-sm font-bold">Rapot Tahfizh</span>
                </header>

                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {header && <div className="mb-5">{header}</div>}
                    {children}
                </main>
            </div>
        </div>
    );
}
import { useState, useEffect, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import LogoutButton from '@/Components/LogoutButton';
import AppLogo from '@/Components/AppLogo';
import LaporanNotifier from '@/Components/LaporanNotifier';


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

// ---- Switcher kelas aktif (guru yang mengampu >1 kelas) ----
function KelasSwitcher({ kelasAktif, kelasList = [], variant = 'full' }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onDown = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onDown);
        return () => document.removeEventListener('mousedown', onDown);
    }, [open]);

    if (!kelasAktif) return null;

    const multi = kelasList.length > 1;
    const inisial = kelasAktif.name?.charAt(0)?.toUpperCase();

    const pilih = (id) => {
        setOpen(false);
        if (Number(id) === Number(kelasAktif.id)) return;
        router.post(route('guru.pilih-kelas.set'), { kelas_id: id });
    };

    const dropdown = open && (
        <div className={`absolute z-50 mt-2 w-52 overflow-hidden rounded-xl border border-cream-300 bg-white py-1 shadow-xl dark:border-coal-600 dark:bg-coal-700 ${variant === 'mini' ? 'left-full top-0 ml-2 mt-0' : 'left-0 top-full'}`}>
            <p className="px-3 pb-1 pt-1.5 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">
                Pindah Kelas
            </p>
            {kelasList.map((k) => {
                const aktif = Number(k.id) === Number(kelasAktif.id);
                return (
                    <button
                        key={k.id}
                        type="button"
                        onClick={() => pilih(k.id)}
                        className={`flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition ${
                            aktif
                                ? 'bg-claude-500/10 font-semibold text-claude-600 dark:text-claude-400'
                                : 'text-stone-600 hover:bg-cream-200 dark:text-stone-300 dark:hover:bg-coal-600'
                        }`}
                    >
                        <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-stone-100 text-[11px] font-bold text-stone-500 dark:bg-coal-600 dark:text-stone-300">
                            {k.name?.charAt(0)?.toUpperCase()}
                        </span>
                        <span className="flex-1 truncate">{k.name}</span>
                        {aktif && (
                            <svg className="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M20 6L9 17l-5-5" />
                            </svg>
                        )}
                    </button>
                );
            })}
        </div>
    );

    // Sidebar mini — cuma tile inisial
    if (variant === 'mini') {
        return (
            <div ref={ref} className="relative flex justify-center">
                <button
                    type="button"
                    disabled={!multi}
                    onClick={() => multi && setOpen((o) => !o)}
                    title={multi ? `Kelas ${kelasAktif.name} — klik untuk pindah` : `Kelas ${kelasAktif.name}`}
                    className={`flex h-9 w-9 items-center justify-center rounded-xl bg-claude-500/10 text-sm font-bold text-claude-600 ring-1 ring-claude-500/20 dark:text-claude-400 ${multi ? 'transition hover:bg-claude-500/20' : 'cursor-default'}`}
                >
                    {inisial}
                </button>
                {dropdown}
            </div>
        );
    }

    // Header mobile — pill ringkas
    if (variant === 'mobile') {
        return (
            <div ref={ref} className="relative">
                <button
                    type="button"
                    disabled={!multi}
                    onClick={() => multi && setOpen((o) => !o)}
                    className={`flex items-center gap-1 rounded-lg bg-claude-500/10 px-2 py-1 text-[11px] font-bold text-claude-600 ring-1 ring-claude-500/20 dark:text-claude-400 ${multi ? 'transition active:scale-95' : 'cursor-default'}`}
                >
                    <span className="max-w-[90px] truncate">{kelasAktif.name}</span>
                    {multi && (
                        <svg className={`h-3 w-3 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    )}
                </button>
                {dropdown}
            </div>
        );
    }

    // Sidebar lebar
    return (
        <div ref={ref} className="relative">
            <button
                type="button"
                disabled={!multi}
                onClick={() => multi && setOpen((o) => !o)}
                className={`flex w-full items-center gap-2.5 rounded-xl bg-claude-500/8 px-3 py-2.5 ring-1 ring-claude-500/20 ${multi ? 'transition hover:bg-claude-500/15' : 'cursor-default'}`}
            >
                <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-claude-500/15 text-sm font-bold text-claude-600 dark:text-claude-400">
                    {inisial}
                </span>
                <span className="min-w-0 flex-1 text-left">
                    <span className="block text-[9px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-500">
                        Kelas Aktif
                    </span>
                    <span className="block truncate text-sm font-bold text-stone-800 dark:text-cream-50">
                        {kelasAktif.name}
                    </span>
                </span>
                {multi && (
                    <svg className={`h-4 w-4 flex-shrink-0 text-stone-400 transition-transform dark:text-stone-500 ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                )}
            </button>
            {dropdown}
        </div>
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

export default function GuruAuthenticatedLayout({ children }) {
    const { auth, unreadLaporan, kelasAktif, kelasDiampu = [] } = usePage().props;
    const user = auth?.user;
    const displayName = user?.display_name || user?.name || '';

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
        input: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        santri: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        rapot: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        setoran: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        presensi: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        riwayat: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        profil: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        komplain: 'M3 3v18M3 4.5h12.75l-2.25 3 2.25 3H3',
    };

    const closeNav = () => setOpen(false);

    const utama = [
        { href: route('guru.input.create'), label: 'Catat Setoran', icon: I.input, active: route().current('guru.input.*') },
    ];
    const dataSantri = [
        { href: route('guru.santris.index'), label: 'Kelola Santri', icon: I.santri, active: route().current('guru.santris.*') },
        { href: route('guru.rapot.index'), label: 'Rapot Santri', icon: I.rapot, active: route().current('guru.rapot.*') },
    ];
    const laporan = [
        { href: route('guru.setorans.index'), label: 'Riwayat Setoran', icon: I.setoran, active: route().current('guru.setorans.*') },
        { href: route('guru.presensis.index'), label: 'Riwayat Presensi', icon: I.presensi, active: route().current('guru.presensis.*') },
        { href: route('guru.riwayat-santri'), label: 'Riwayat Santri', icon: I.riwayat, active: route().current('guru.riwayat-santri*') },
        { href: route('guru.laporan.index'), label: 'Komplain Data', icon: I.komplain, active: route().current('guru.laporan.*'), badge: unreadLaporan },
    ];

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
                            <p className="text-[10px] text-stone-400 dark:text-stone-500">Panel Guru</p>
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

            {/* Kelas aktif */}
            <div className={`pb-1 ${isMini ? 'px-2' : 'px-3'}`}>
                <KelasSwitcher
                    kelasAktif={kelasAktif}
                    kelasList={kelasDiampu}
                    variant={isMini ? 'mini' : 'full'}
                />
            </div>

            {/* Nav */}
            <nav className={`sidebar-scroll flex-1 space-y-1 overflow-y-auto py-3 ${isMini ? 'px-2' : 'px-3'}`}>
                <NavItem href={route('guru.dashboard')} active={route().current('guru.dashboard')} icon={I.dash} onClick={closeNav} collapsed={isMini}>Dashboard</NavItem>

                {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Utama</p>
                )}
                {utama.map((m) => (
                    <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini}>{m.label}</NavItem>
                ))}

                {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Data Santri</p>
                )}
                {dataSantri.map((m) => (
                    <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini}>{m.label}</NavItem>
                ))}

                {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Laporan</p>
                )}
                {laporan.map((m) => (
                    <NavItem key={m.label} href={m.href} active={m.active} icon={m.icon} onClick={closeNav} collapsed={isMini} badge={m.badge || 0}>{m.label}</NavItem>
                ))}

                {isMini ? <div className="mx-2 my-2 h-px bg-cream-300 dark:bg-coal-600" /> : (
                    <p className="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-stone-400 dark:text-stone-600">Akun</p>
                )}
                <NavItem href={route('profile.edit')} active={route().current('profile.edit')} icon={I.profil} onClick={closeNav} collapsed={isMini}>Profil Saya</NavItem>
            </nav>

            {/* Bottom */}
            <div className={`space-y-3 border-t border-cream-300 py-3 dark:border-coal-600 ${isMini ? 'px-2' : 'px-3'}`}>
                {isMini ? (
                    <div className="flex flex-col items-center gap-2">
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
                            <img src={`/storage/${user.profile_photo}`} alt={displayName} title={displayName}
                                className="h-9 w-9 flex-shrink-0 rounded-full object-cover object-top ring-2 ring-claude-500/20" />
                        ) : (
                            <span title={displayName} className="flex h-9 w-9 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 dark:bg-claude-500/20 dark:text-claude-300">
                                {displayName?.charAt(0)?.toUpperCase()}
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
                                <img src={`/storage/${user.profile_photo}`} alt={displayName}
                                    className="h-9 w-9 flex-shrink-0 rounded-full object-cover object-top ring-2 ring-claude-500/20" />
                            ) : (
                                <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-claude-100 text-sm font-bold text-claude-700 dark:bg-claude-500/20 dark:text-claude-300">
                                    {displayName?.charAt(0)?.toUpperCase()}
                                </span>
                            )}
                            <div className="min-w-0 flex-1">
                                <p
                                    className="text-[11px] font-semibold leading-tight text-stone-800 break-words dark:text-cream-50"
                                    title={displayName}
                                    style={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}
                                >
                                    {displayName}
                                </p>
                                <p className="mt-0.5 truncate text-[10px] text-stone-400 dark:text-stone-500">Guru Tahfizh</p>
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
                    <span className="text-sm font-bold">Laporan Tahfizh</span>

                    <div className="ml-auto flex items-center gap-2">
                        <KelasSwitcher
                            kelasAktif={kelasAktif}
                            kelasList={kelasDiampu}
                            variant="mobile"
                        />
                        <ThemeToggle dark={dark} onToggle={() => setDark((d) => !d)} />
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>

            <LaporanNotifier href={route('guru.laporan.index')} />
            
        </div>
    );
}
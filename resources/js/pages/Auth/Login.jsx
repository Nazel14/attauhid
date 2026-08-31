import { useState, useEffect } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';

/* Teks & warna berbeda per panel — dipakai header kartu supaya user
   tahu persis ke mana dia akan masuk sebelum menekan tombol. */
const PANEL = {
    raport: {
        label: 'Tahfizh',
        judul: 'Panel Tahfizh',
        sub: 'Setoran, presensi, dan rapor',
        tombol: 'Masuk ke Tahfizh',
    },
    keuangan: {
        label: 'Keuangan',
        judul: 'Panel Keuangan',
        // Sengaja pendek — harus muat satu baris di kartu selebar 24rem.
        sub: 'Tagihan, kas, dan penggajian',
        tombol: 'Masuk ke Keuangan',
    },
};

export default function Login({ status, flash }) {
    // Dibagikan HandleInertiaRequests — pemilih hanya muncul bila
    // kedua modul memang aktif di instalasi ini.
    const { modules = {} } = usePage().props;
    const duaModul = modules.raport && modules.keuangan;

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
        modul: modules.keuangan && !modules.raport ? 'keuangan' : 'raport',
    });

    // Judul/sub/tombol SELALU mengikuti modul yang aktif — tidak lagi
    // disembunyikan jadi teks generik saat hanya satu modul yang nyala.
    // Tab pemilih (duaModul) yang tetap kondisional, bukan labelnya.
    const panel = PANEL[data.modul] ?? PANEL.raport;

    const [showPw, setShowPw] = useState(false);
    const [dark, setDark] = useState(() => {
        if (typeof window === 'undefined') return false;
        return localStorage.getItem('theme') === 'dark';
    });

    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
    }, [dark]);

    const toggleTheme = () => {
        setDark((d) => {
            const next = !d;
            try { localStorage.setItem('theme', next ? 'dark' : 'light'); } catch (e) {}
            return next;
        });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('login'));
    };

    return (
        <>
            <Head title="Login" />

            <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#ece5db] p-4 dark:bg-[#242019]">
                {/* Light ambient */}
                <div
                    className="pointer-events-none absolute inset-0 z-0 dark:hidden"
                    style={{
                        background:
                            'radial-gradient(circle at 18% 25%, rgba(120,113,108,0.06), transparent 42%), radial-gradient(circle at 82% 78%, rgba(120,113,108,0.05), transparent 45%)',
                    }}
                />
                {/* Dark ambient — warm-dark, diangkat biar tidak hitam pekat */}
                <div
                    className="pointer-events-none absolute inset-0 z-0 hidden dark:block"
                    style={{
                        background:
                            'linear-gradient(180deg, #2b2723 0%, #262220 60%, #211d19 100%)',
                    }}
                />
                {/* Soft glow behind card */}
                <div className="pointer-events-none absolute left-1/2 top-1/2 z-0 h-[440px] w-[440px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-stone-400/10 blur-3xl dark:bg-white/5" />

                {/* Theme toggle */}
                <button
                    type="button"
                    onClick={toggleTheme}
                    aria-label={dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'}
                    className="group absolute right-4 top-4 z-20 flex h-9 w-16 items-center rounded-full border border-stone-200 bg-white/80 px-1 shadow-sm backdrop-blur transition hover:border-claude-400/50 dark:border-[#413b35] dark:bg-[#34302b]/90"
                >
                    <span className="pointer-events-none absolute left-2 text-amber-500 transition-opacity duration-300" style={{ opacity: dark ? 0.35 : 0 }}>
                        <SunIcon className="h-3.5 w-3.5" />
                    </span>
                    <span className="pointer-events-none absolute right-2 text-indigo-300 transition-opacity duration-300" style={{ opacity: dark ? 0 : 0.35 }}>
                        <MoonIcon className="h-3.5 w-3.5" />
                    </span>
                    <span
                        className={`relative flex h-7 w-7 items-center justify-center rounded-full text-white shadow-md transition-transform duration-300 ease-out ${
                            dark
                                ? 'translate-x-7 bg-gradient-to-br from-claude-500 to-claude-600'
                                : 'translate-x-0 bg-gradient-to-br from-amber-400 to-amber-500'
                        }`}
                    >
                        {dark ? <MoonIcon className="h-4 w-4" /> : <SunIcon className="h-4 w-4" />}
                    </span>
                </button>

                {/* Card — overflow-hidden supaya tab menempel rapi di tepi atas */}
                <div className="relative z-10 w-full max-w-sm overflow-hidden rounded-3xl border border-stone-200/80 bg-white/90 shadow-[0_24px_60px_-24px_rgba(120,80,60,0.25)] backdrop-blur-sm dark:border-[#443e37] dark:bg-[#34302b]/95 dark:shadow-[0_24px_60px_-20px_rgba(0,0,0,0.5)]">

                    {/* ===== TAB PEMILIH PANEL ===== */}
                    {duaModul && (
                        <div className="grid grid-cols-2 border-b border-stone-200/80 dark:border-[#443e37]">
                            {[
                                { id: 'raport', icon: <BookIcon className="h-[18px] w-[18px]" /> },
                                { id: 'keuangan', icon: <WalletIcon className="h-[18px] w-[18px]" /> },
                            ].map((m) => {
                                const aktif = data.modul === m.id;
                                return (
                                    <button
                                        key={m.id}
                                        type="button"
                                        onClick={() => setData('modul', m.id)}
                                        aria-pressed={aktif}
                                        // Latar tab dibiarkan transparan agar seragam
                                        // dengan badan kartu — pembedanya hanya warna
                                        // teks dan garis penanda di bawah.
                                        className={`relative flex items-center justify-center gap-2 py-3.5 text-[13px] font-semibold transition ${
                                            aktif
                                                ? 'text-claude-600 dark:text-claude-300'
                                                : 'text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300'
                                        }`}
                                    >
                                        {m.icon}
                                        {PANEL[m.id].label}
                                        {/* Garis penanda tab aktif */}
                                        {aktif && (
                                            <span className="absolute inset-x-0 bottom-0 h-[2.5px] bg-gradient-to-r from-claude-500 to-claude-600" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    {errors.modul && (
                        <div className="px-8 pt-5">
                            <FieldError msg={errors.modul} />
                        </div>
                    )}

                    <div className="p-8">
                        {/* Header — judul & subjudul SELALU mengikuti panel aktif,
                            baik satu modul maupun dua modul. */}
                        <div className="mb-7 flex flex-col items-center text-center">
                            <div className="mb-4 flex h-[68px] w-[68px] items-center justify-center rounded-2xl bg-white p-3 shadow-sm ring-1 ring-stone-200 dark:bg-[#3d3833] dark:ring-white/10">
                                <img src="/favicon.ico" alt="Logo" className="h-full w-full" style={{ objectFit: 'contain' }} />
                            </div>
                            <h1 className="mb-1 text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                                {panel.judul}
                            </h1>
                            <p className="whitespace-nowrap text-xs font-medium text-stone-500 dark:text-stone-400">
                                {panel.sub}
                            </p>
                        </div>

                        {status && (
                            <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                {status}
                            </div>
                        )}

                        {flash?.error && (
                            <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-xs font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                                {flash.error}
                            </div>
                        )}

                        <form onSubmit={submit}>
                            {/* Username */}
                            <div className="mb-4">
                                <label htmlFor="name" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    Username
                                </label>
                                <div className="relative">
                                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-stone-500">
                                        <UserIcon className="h-4 w-4" />
                                    </span>
                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Masukkan username"
                                        autoFocus
                                        autoComplete="username"
                                        className={`w-full rounded-xl border bg-cream-50 py-2.5 pl-9 pr-3 text-[13px] text-stone-800 outline-none transition focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 dark:bg-[#2b2723] dark:text-stone-200 ${
                                            errors.name ? 'border-red-400 dark:border-red-500/50' : 'border-stone-200 dark:border-[#443e37]'
                                        }`}
                                    />
                                </div>
                                {errors.name && <FieldError msg={errors.name} />}
                            </div>

                            {/* Password */}
                            <div className="mb-4">
                                <label htmlFor="password" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    Password
                                </label>
                                <div className="relative">
                                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-stone-500">
                                        <LockIcon className="h-4 w-4" />
                                    </span>
                                    <input
                                        id="password"
                                        type={showPw ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Masukkan password"
                                        autoComplete="current-password"
                                        className={`w-full rounded-xl border bg-cream-50 py-2.5 pl-9 pr-10 text-[13px] text-stone-800 outline-none transition focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 dark:bg-[#2b2723] dark:text-stone-200 ${
                                            errors.password ? 'border-red-400 dark:border-red-500/50' : 'border-stone-200 dark:border-[#443e37]'
                                        }`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPw(!showPw)}
                                        className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-stone-400 transition hover:text-claude-500 dark:text-stone-500 dark:hover:text-claude-400"
                                        tabIndex={-1}
                                    >
                                        {showPw ? <EyeOffIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
                                    </button>
                                </div>
                                {errors.password && <FieldError msg={errors.password} />}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-claude-500 to-claude-600 py-3 text-[13px] font-bold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-500 hover:to-claude-700 active:scale-[0.99] disabled:opacity-60"
                            >
                                <span>{processing ? 'Memproses…' : panel.tombol}</span>
                                {!processing && <ArrowIcon className="h-4 w-4" />}
                            </button>
                        </form>

                        <div className="mt-6 border-t border-stone-200/70 pt-4 text-center dark:border-[#443e37]">
                            <p className="text-[10px] uppercase tracking-wide text-stone-400 dark:text-stone-500">
                                © {new Date().getFullYear()} Sistem Management Tahfizh
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

/* ---------- Small helpers ---------- */
function FieldError({ msg }) {
    return (
        <div className="mt-1.5 flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-[11px] font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
            <svg width="13" height="13" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
            {msg}
        </div>
    );
}

const Svg = ({ children, className = 'h-4 w-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" strokeLinecap="round" strokeLinejoin="round">{children}</svg>
);
const SunIcon = (p) => <Svg {...p}><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></Svg>;
const MoonIcon = (p) => <Svg {...p}><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" /></Svg>;
const UserIcon = (p) => <Svg {...p}><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.12a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75c-2.68 0-5.22-.58-7.5-1.63z" /></Svg>;
const LockIcon = (p) => <Svg {...p}><path d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></Svg>;
const EyeIcon = (p) => <Svg {...p}><path d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18a1 1 0 010 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18z" /><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></Svg>;
const EyeOffIcon = (p) => <Svg {...p}><path d="M3.98 8.22A10.48 10.48 0 001.93 12C3.23 16.34 7.24 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0112 4.5c4.76 0 8.77 3.16 10.07 7.5a10.52 10.52 0 01-4.3 5.77M6.23 6.23L3 3m3.23 3.23l3.65 3.65m7.89 7.89L21 21m-3.23-3.23l-3.65-3.65m0 0a3 3 0 10-4.24-4.24m4.24 4.24L9.88 9.88" /></Svg>;
const ArrowIcon = (p) => <Svg {...p}><path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></Svg>;
const BookIcon = (p) => <Svg {...p}><path d="M12 6.25a5.23 5.23 0 00-3.5-1.33c-1.24 0-2.38.43-3.28 1.15A.75.75 0 005 6.65v10.6c0 .6.63.98 1.15.7a4.5 4.5 0 012.35-.6c1.36 0 2.6.53 3.5 1.4m0-12.5a5.23 5.23 0 013.5-1.33c1.24 0 2.38.43 3.28 1.15a.75.75 0 01.22.53v10.6c0 .6-.63.98-1.15.7a4.5 4.5 0 00-2.35-.6c-1.36 0-2.6.53-3.5 1.4m0-12.5v12.5" /></Svg>;
const WalletIcon = (p) => <Svg {...p}><path d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 100 4.5h3.75A2.25 2.25 0 0021 12zm0 0v3.75A2.25 2.25 0 0118.75 18H5.25A2.25 2.25 0 013 15.75V8.25A2.25 2.25 0 015.25 6h10.5A2.25 2.25 0 0118 8.25v1.5" /></Svg>;
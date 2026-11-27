import { useState, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AppLogo from '@/Components/AppLogo';

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
    const { modules = {}, demoAkun } = usePage().props;
    const duaModul = modules.raport && modules.keuangan;

    // Tombol demo hanya dirender bila server membagikan daftarnya —
    // dan server hanya membagikannya saat DEMO_MODE menyala. Di produksi
    // propnya null, jadi seluruh blok ini tidak pernah muncul.
    const modeDemo = demoAkun?.length > 0;

    // Pintu darurat. Di situs demo pun Anda masih perlu masuk sebagai akun
    // asli untuk memperbaiki data atau seeding ulang, jadi formnya
    // disembunyikan — bukan dihapus.
    const [formManual, setFormManual] = useState(false);
    const tampilForm = !modeDemo || formManual;

    const [demoLoading, setDemoLoading] = useState(null);
    const masukDemo = (peran, modul) => {
        // Satu akun bisa muncul di dua kelompok (Admin ada di Tahfizh dan
        // Keuangan), jadi penanda 'memproses' harus memuat keduanya.
        setDemoLoading(`${modul}:${peran}`);
        router.post(route('demo.masuk'), { peran, modul }, {
            onFinish: () => setDemoLoading(null),
        });
    };

    // Modulnya datang dari server — dibaca dari availableModules() tiap akun,
    // bukan ditulis ulang di sini. Akun yang belum di-seed tidak dikirim sama
    // sekali, jadi kelompok yang kosong otomatis hilang.
    const grupDemo = !modeDemo ? [] : [
        { id: 'raport', label: 'Tahfizh', icon: <BookIcon className="h-3.5 w-3.5" /> },
        { id: 'keuangan', label: 'Keuangan', icon: <WalletIcon className="h-3.5 w-3.5" /> },
    ]
        .map((g) => ({ ...g, akun: demoAkun.filter((a) => a.modul?.includes(g.id)) }))
        .filter((g) => g.akun.length > 0);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
        modul: modules.keuangan && !modules.raport ? 'keuangan' : 'raport',
    });

    // Judul/sub/tombol SELALU mengikuti modul yang aktif — tidak lagi
    // disembunyikan jadi teks generik saat hanya satu modul yang nyala.
    // Tab pemilih (duaModul) yang tetap kondisional, bukan labelnya.
    const panel = PANEL[data.modul] ?? PANEL.raport;

    // Tab pemilih panel tidak ditampilkan di mode demo: peran yang dipilih
    // sudah menentukan panelnya sendiri, dan tab "Tahfizh" di atas tombol
    // "Bendahara" justru saling membantah. Karena tabnya hilang, judulnya
    // ikut dinetralkan — kalau tidak, HRD dan Bendahara berdiri di bawah
    // tulisan "Panel Tahfizh".
    const judulNetral = !tampilForm && duaModul;
    const judul = judulNetral ? 'TK Attauhid' : panel.judul;
    const sub = judulNetral ? 'Setoran, presensi, rapor, dan keuangan' : panel.sub;

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
            <Head title="Login">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap"
                    rel="stylesheet"
                />
            </Head>

            {/*
                Palet disamakan dengan Landing.jsx supaya pengunjung yang
                menekan "Masuk sistem sekolah" tidak merasa pindah situs.

                Warna merek diambil dari piksel berkas logo asli. Pembagian
                perannya sama dengan landing: ORANYE untuk tombol utama,
                HIJAU untuk penanda merek (tab aktif, cincin fokus), BIRU
                untuk aksen pendukung.

                PALET MODE GELAP — empat nilai yang dipakai berulang. Kalau
                terasa terlalu gelap atau terlalu terang, cari-ganti keempatnya
                sekaligus; yang membuat kartu terbaca bukan terangnya melainkan
                SELISIH antara latar halaman dan permukaan kartu.

                  #282C25  latar halaman, dan kolom isian di dalam kartu
                  #353B31  permukaan kartu   (selisih ~13 dari latar)
                  #3B4235  garis pemisah
                  #474E40  garis tepi kartu & kolom isian
            */}
            <style>{`
                :root {
                    --tq-hijau: #7EB842;
                    --tq-oranye: #FA902C;
                    --tq-biru: #36A7DF;

                    --tq-hijau-gelap: #6EA039;
                    --tq-oranye-gelap: #DA7D26;
                }
                .tq-judul { font-family: 'Baloo 2', 'Nunito', system-ui, sans-serif; }
                .tq-teks  { font-family: 'Nunito', system-ui, sans-serif; }

                .tq-btn-oranye {
                    background: var(--tq-oranye);
                    color: #fff;
                    transition: background-color .18s ease, transform .18s ease, opacity .18s ease;
                }
                .tq-btn-oranye:hover:not(:disabled) { background: var(--tq-oranye-gelap); }
                .tq-btn-oranye:active:not(:disabled) { transform: scale(.99); }

                /* Cincin fokus hijau menggantikan focus:ring bawaan Tailwind,
                   supaya warnanya ikut variabel di atas dan tidak perlu
                   mendaftarkan warna baru di tailwind.config. */
                .tq-input { transition: border-color .15s ease, box-shadow .15s ease; }
                .tq-input:focus {
                    outline: none;
                    border-color: var(--tq-hijau);
                    box-shadow: 0 0 0 3px rgba(126,184,66,.22);
                }

                .tq-teks-hijau { color: var(--tq-hijau); }
                .tq-garis-tab  { background: var(--tq-hijau); }

                .tq-demo { transition: border-color .15s ease, background-color .15s ease, color .15s ease, transform .15s ease; }
                .tq-demo:hover:not(:disabled) { border-color: var(--tq-hijau); color: var(--tq-hijau); }
                .tq-demo:active:not(:disabled) { transform: scale(.98); }

                .tq-mata:hover { color: var(--tq-hijau); }
            `}</style>

            <div className="tq-teks relative flex min-h-screen items-center justify-center overflow-hidden bg-[#F6F7F2] p-4 dark:bg-[#282C25]">
                {/* Ambient terang — tiga warna logo, sangat tipis */}
                <div
                    className="pointer-events-none absolute inset-0 z-0 dark:hidden"
                    style={{
                        background:
                            'radial-gradient(circle at 18% 22%, rgba(126,184,66,.16), transparent 44%), radial-gradient(circle at 84% 78%, rgba(54,167,223,.13), transparent 46%), radial-gradient(circle at 60% 98%, rgba(250,144,44,.10), transparent 44%)',
                    }}
                />
                {/* Ambient gelap */}
                <div
                    className="pointer-events-none absolute inset-0 z-0 hidden dark:block"
                    style={{
                        background:
                            'radial-gradient(circle at 18% 22%, rgba(126,184,66,.14), transparent 44%), radial-gradient(circle at 84% 78%, rgba(54,167,223,.12), transparent 46%)',
                    }}
                />
                {/* Cahaya lembut di belakang kartu */}
                <div
                    className="pointer-events-none absolute left-1/2 top-1/2 z-0 h-[440px] w-[440px] -translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl"
                    style={{ background: 'radial-gradient(circle, rgba(126,184,66,.16), transparent 70%)' }}
                />

                {/* Theme toggle — hijau di kedua mode, seragam dengan toggle
                    tema di panel Guru/Admin/Keuangan/Santri. Sebelumnya mode
                    terang memakai bulatan oranye dan matahari kuning. */}
                <button
                    type="button"
                    onClick={toggleTheme}
                    aria-label={dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'}
                    className="group absolute right-4 top-4 z-20 flex h-9 w-16 items-center rounded-full px-1 shadow-sm backdrop-blur transition"
                    style={{
                        background: dark ? 'rgba(126,184,66,.22)' : 'rgba(126,184,66,.14)',
                        boxShadow: 'inset 0 0 0 1px rgba(126,184,66,.35)',
                    }}
                >
                    <span className="tq-teks-hijau pointer-events-none absolute left-2 transition-opacity duration-300" style={{ opacity: dark ? 0.5 : 0 }}>
                        <SunIcon className="h-3.5 w-3.5" />
                    </span>
                    <span className="tq-teks-hijau pointer-events-none absolute right-2 transition-opacity duration-300" style={{ opacity: dark ? 0 : 0.5 }}>
                        <MoonIcon className="h-3.5 w-3.5" />
                    </span>
                    <span
                        className={`relative flex h-7 w-7 items-center justify-center rounded-full text-white shadow-md transition-transform duration-300 ease-out ${
                            dark ? 'translate-x-7' : 'translate-x-0'
                        }`}
                        style={{ background: 'var(--tq-hijau)' }}
                    >
                        {dark ? <MoonIcon className="h-4 w-4" /> : <SunIcon className="h-4 w-4" />}
                    </span>
                </button>

                {/* Card — overflow-hidden supaya tab menempel rapi di tepi atas */}
                <div className="relative z-10 w-full max-w-sm overflow-hidden rounded-3xl border border-stone-200/80 bg-white/90 shadow-[0_24px_60px_-24px_rgba(60,80,40,0.28)] backdrop-blur-sm dark:border-[#474E40] dark:bg-[#353B31]/95 dark:shadow-[0_24px_60px_-20px_rgba(0,0,0,0.5)]">

                    {/* Pita tiga warna logo — penanda merek yang sama dengan
                        kartu-kartu di landing page. */}
                    <div className="h-1.5 w-full" style={{ background: 'linear-gradient(90deg, var(--tq-hijau) 0%, var(--tq-oranye) 50%, var(--tq-biru) 100%)' }} />

                    {/* ===== TAB PEMILIH PANEL ===== */}
                    {duaModul && tampilForm && (
                        <div className="grid grid-cols-2 border-b border-stone-200/80 dark:border-[#474E40]">
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
                                                ? 'tq-teks-hijau'
                                                : 'text-stone-400 hover:text-stone-600 dark:text-stone-400 dark:hover:text-stone-200'
                                        }`}
                                    >
                                        {m.icon}
                                        {PANEL[m.id].label}
                                        {/* Garis penanda tab aktif */}
                                        {aktif && <span className="tq-garis-tab absolute inset-x-0 bottom-0 h-[2.5px]" />}
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
                        <div className={`flex flex-col items-center text-center ${tampilForm ? 'mb-7' : 'mb-5'}`}>
                            <div className="mb-4 flex h-[68px] w-[68px] items-center justify-center rounded-2xl bg-white p-3 shadow-sm ring-1 ring-stone-200 dark:bg-[#282C25] dark:ring-white/10">
                                {/* Logo kubus yang sama dengan sidebar. Sebelumnya
                                    memuat /favicon.ico, yang masih berisi logo "A"
                                    lama — jadi login dan sidebar tidak seragam. */}
                                <AppLogo className="h-full w-full" />
                            </div>
                            <h1 className="tq-judul mb-1 text-xl font-bold tracking-tight text-stone-800 dark:text-stone-50">
                                {judul}
                            </h1>
                            <p className="whitespace-nowrap text-xs font-medium text-stone-500 dark:text-stone-400">
                                {sub}
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

                        {tampilForm && (
                        <form onSubmit={submit}>
                            {/* Username */}
                            <div className="mb-4">
                                <label htmlFor="name" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    Username
                                </label>
                                <div className="relative">
                                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400">
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
                                        className={`tq-input w-full rounded-xl border bg-[#F6F7F2] py-2.5 pl-9 pr-3 text-[13px] text-stone-800 dark:bg-[#282C25] dark:text-stone-200 ${
                                            errors.name ? 'border-red-400 dark:border-red-500/50' : 'border-stone-200 dark:border-[#474E40]'
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
                                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400">
                                        <LockIcon className="h-4 w-4" />
                                    </span>
                                    <input
                                        id="password"
                                        type={showPw ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Masukkan password"
                                        autoComplete="current-password"
                                        className={`tq-input w-full rounded-xl border bg-[#F6F7F2] py-2.5 pl-9 pr-10 text-[13px] text-stone-800 dark:bg-[#282C25] dark:text-stone-200 ${
                                            errors.password ? 'border-red-400 dark:border-red-500/50' : 'border-stone-200 dark:border-[#474E40]'
                                        }`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPw(!showPw)}
                                        className="tq-mata absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1 text-stone-400 transition"
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
                                className="tq-btn-oranye tq-judul mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-3 text-[14px] font-bold shadow-lg disabled:opacity-60"
                            >
                                <span>{processing ? 'Memproses…' : panel.tombol}</span>
                                {!processing && <ArrowIcon className="h-4 w-4" />}
                            </button>
                        </form>
                        )}

                        {modeDemo && !formManual && (
                            <div>
                                <div className="mb-4 flex flex-col items-center gap-2 text-center">
                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-300/70 bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-400">
                                        <FlaskIcon className="h-3 w-3" /> Mode Demo
                                    </span>
                                    <p className="text-[12px] text-stone-500 dark:text-stone-400">
                                        Pilih peran untuk mencoba. Tidak perlu akun.
                                    </p>
                                </div>

                                <div className="space-y-3">
                                    {grupDemo.map((g) => (
                                        <div key={g.id}>
                                            {/* Judul kelompok tidak perlu bila cuma satu
                                                modul yang terpasang di instalasi ini. */}
                                            {grupDemo.length > 1 && (
                                                <p className="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-stone-400">
                                                    {g.icon}
                                                    {g.label}
                                                </p>
                                            )}
                                            {/* Bila jumlahnya ganjil, tombol terakhir
                                                melebar dua kolom. Tanpa ini ada lubang
                                                kosong di kanan yang terlihat seperti
                                                tombol yang gagal dirender. */}
                                            <div className="grid grid-cols-2 gap-2 [&>*:last-child:nth-child(odd)]:col-span-2">
                                                {g.akun.map((a) => {
                                                    const kunci = `${g.id}:${a.peran}`;
                                                    return (
                                                        <button
                                                            key={kunci}
                                                            type="button"
                                                            onClick={() => masukDemo(a.peran, g.id)}
                                                            disabled={demoLoading !== null}
                                                            className="tq-demo flex items-center justify-center rounded-xl border border-stone-200 bg-[#F6F7F2] px-2 py-2.5 text-[12px] font-semibold text-stone-700 disabled:opacity-50 dark:border-[#474E40] dark:bg-[#282C25] dark:text-stone-300"
                                                        >
                                                            {demoLoading === kunci ? 'Memproses…' : a.label}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Pintu darurat pengelola. Sengaja berupa teks kecil, bukan
                            tombol, supaya tidak bersaing dengan tombol peran di atas. */}
                        {modeDemo && (
                            <button
                                type="button"
                                onClick={() => setFormManual((v) => !v)}
                                className="mt-4 w-full text-center text-[11px] font-medium text-stone-400 underline-offset-2 transition hover:text-stone-600 hover:underline dark:hover:text-stone-200"
                            >
                                {formManual ? '← Kembali ke pilihan demo' : 'Masuk dengan akun sendiri'}
                            </button>
                        )}

                        <div className={`border-t border-stone-200/70 pt-4 text-center dark:border-[#3B4235] ${tampilForm ? 'mt-6' : 'mt-5'}`}>
                            <p className="text-[10px] uppercase tracking-wide text-stone-400">
                                © {new Date().getFullYear()} TK Attauhid
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
const FlaskIcon = (p) => <Svg {...p}><path d="M9 3h6M10 3v6.5L4.6 18a2 2 0 001.7 3h11.4a2 2 0 001.7-3L14 9.5V3M7.5 14h9" /></Svg>;
const WalletIcon = (p) => <Svg {...p}><path d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 100 4.5h3.75A2.25 2.25 0 0021 12zm0 0v3.75A2.25 2.25 0 0118.75 18H5.25A2.25 2.25 0 013 15.75V8.25A2.25 2.25 0 015.25 6h10.5A2.25 2.25 0 0118 8.25v1.5" /></Svg>;
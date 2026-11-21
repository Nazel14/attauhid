import { useEffect, useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLogo from '@/Components/AppLogo';

/* =====================================================================
 * LANDING TK ATTAUHID
 * ---------------------------------------------------------------------
 * ATURAN ISI: seluruh teks di halaman ini berasal dari dokumen
 * "Profil TK Attauhid". Tidak ada kalimat karangan, tidak ada ayat
 * tambahan, tidak ada tanya jawab rekaan. Yang membacanya orang tua
 * calon murid, dan mereka akan menganggap setiap kalimat di sini sebagai
 * pernyataan resmi sekolah.
 *
 * Kalau menambah bagian baru, pastikan sumbernya ada di profil. Kalau
 * tidak ada, minta teksnya ke sekolah — jangan ditulis sendiri.
 *
 * WARNA diambil dari piksel berkas logo asli, didefinisikan sekali di
 * blok :root pada <style> di bawah.
 *
 * CATATAN TEKNIS: warna TIDAK ditulis sebagai kelas Tailwind dinamis
 * (bg-[${warna}]) karena Tailwind memindai kelas sebagai teks statis;
 * kelas yang dirakit saat runtime tidak akan pernah ikut ter-build.
 * ===================================================================== */

/* Semua dari halaman "Identitas" di profil. */
const KONTAK_WA = 'https://wa.me/6285117199886';
const KONTAK_WA_TAMPIL = '0851-1719-9886';
const KONTAK_EMAIL = 'sekolahattauhid@gmail.com';
const NIB = '0811240021433';
const YAYASAN = 'Yayasan Islam Lembang At Tauhid';
const ALAMAT = 'Jalan Sukamaju Barat, RT 03/13, Kampung Sukamaju, Desa Kayuambon, Kecamatan Lembang, Kabupaten Bandung Barat';
const ALAMAT_MAPS = 'https://www.google.com/maps/search/?api=1&query=Sekolah+Attauhid+Kayuambon+Lembang+Bandung+Barat';

/* ===================== IKON =====================
   Dideklarasikan di atas array data. Kalau dipindah ke bawah, array
   yang mereferensikannya kena ReferenceError saat modul dimuat. */
const Svg = ({ children, className = 'h-5 w-5' }) => (
    <svg className={className} fill="none" stroke="currentColor" strokeWidth="1.75" viewBox="0 0 24 24" strokeLinecap="round" strokeLinejoin="round">
        {children}
    </svg>
);
const SunIcon = (p) => (
    <Svg {...p}>
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
    </Svg>
);
const MoonIcon = (p) => (
    <Svg {...p}>
        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
    </Svg>
);
const ArrowIcon = (p) => (
    <Svg {...p}>
        <path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
    </Svg>
);
const BookOpenIcon = (p) => (
    <Svg {...p}>
        <path d="M12 6.5a5.2 5.2 0 00-3.5-1.3c-1.2 0-2.4.4-3.3 1.1a.75.75 0 00-.2.5v10.6c0 .6.6 1 1.1.7a4.5 4.5 0 012.4-.6c1.4 0 2.6.5 3.5 1.4m0-12.4a5.2 5.2 0 013.5-1.3c1.2 0 2.4.4 3.3 1.1a.75.75 0 01.2.5v10.6c0 .6-.6 1-1.1.7a4.5 4.5 0 00-2.4-.6c-1.4 0-2.6.5-3.5 1.4m0-12.4v12.4" />
    </Svg>
);
const HeartIcon = (p) => (
    <Svg {...p}>
        <path d="M12 20s-7-4.4-7-9.2A4.2 4.2 0 0112 8a4.2 4.2 0 017 2.8c0 4.8-7 9.2-7 9.2z" />
    </Svg>
);
const QuranIcon = (p) => (
    <Svg {...p}>
        <path d="M5 4.5h11a2 2 0 012 2V20a2 2 0 00-2-2H5V4.5z" />
        <path d="M5 4.5A1.5 1.5 0 003.5 6v13A1.5 1.5 0 005 20.5" />
        <path d="M9 9h5M9 12.5h5" />
    </Svg>
);
const LeafIcon = (p) => (
    <Svg {...p}>
        <path d="M4 20c0-7 5-12 16-12 0 8-5 12-11 12H4z" />
        <path d="M4 20c3-5 7-7 11-8" />
    </Svg>
);
const BusIcon = (p) => (
    <Svg {...p}>
        <rect x="3.5" y="4.5" width="17" height="12" rx="2" />
        <path d="M3.5 11h17M7 20v-3M17 20v-3M7.5 14h.01M16.5 14h.01" />
    </Svg>
);
const StarIcon = (p) => (
    <Svg {...p}>
        <path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1.1 5.9L12 16.9 6.7 19.7l1.1-5.9L3.5 9.7l5.9-.8L12 3.5z" />
    </Svg>
);
const AbcIcon = (p) => (
    <Svg {...p}>
        <path d="M3 17l3-9 3 9M4 14h4M13 8h2.5a2 2 0 010 4H13V8zm0 4h3a2 2 0 010 4h-3v-4zM21 9.5A2.5 2.5 0 0019 8.5a2.5 2.5 0 000 7 2.5 2.5 0 002-1" />
    </Svg>
);
const NumberIcon = (p) => (
    <Svg {...p}>
        <path d="M9 4L7 20M17 4l-2 16M4 9h16M3 15h16" />
    </Svg>
);
const FlaskIcon = (p) => (
    <Svg {...p}>
        <path d="M9.5 3v6.2L4.8 17a2 2 0 001.7 3h11a2 2 0 001.7-3l-4.7-7.8V3" />
        <path d="M8.5 3h7M7.4 14h9.2" />
    </Svg>
);
const CheckIcon = (p) => (
    <Svg {...p}>
        <path d="M5 12.5l5 5L19 6.5" />
    </Svg>
);
const PinIcon = (p) => (
    <Svg {...p}>
        <path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z" />
        <circle cx="12" cy="10" r="2.5" />
    </Svg>
);
const WaIcon = (p) => (
    <Svg {...p}>
        <path d="M21 11.5a8.5 8.5 0 01-12.6 7.4L3.5 20.5l1.7-4.8A8.5 8.5 0 1121 11.5z" />
        <path d="M8.8 9.2c.3 2.6 3.4 5.7 6 6l1-1.6-2-1.1-1 .9c-.9-.5-1.6-1.2-2.1-2.1l.9-1-1.1-2-1.7 1z" />
    </Svg>
);
const MailIcon = (p) => (
    <Svg {...p}>
        <rect x="3" y="5.5" width="18" height="13" rx="2" />
        <path d="M3.5 7l8.5 6 8.5-6" />
    </Svg>
);
const MenuIcon = (p) => (
    <Svg {...p}>
        <path d="M4 7h16M4 12h16M4 17h16" />
    </Svg>
);
const CloseIcon = (p) => (
    <Svg {...p}>
        <path d="M6 18L18 6M6 6l12 12" />
    </Svg>
);
const LoginIcon = (p) => (
    <Svg {...p}>
        <path d="M15 3h3a2 2 0 012 2v14a2 2 0 01-2 2h-3" />
        <path d="M10 17l5-5-5-5M15 12H3" />
    </Svg>
);
const IdIcon = (p) => (
    <Svg {...p}>
        <rect x="3" y="5" width="18" height="14" rx="2" />
        <circle cx="9" cy="11" r="2" />
        <path d="M5.5 16.5c.6-1.5 2-2.3 3.5-2.3s2.9.8 3.5 2.3M15 10h4M15 13.5h4" />
    </Svg>
);

/* Tautan navigasi — dipakai navbar desktop dan menu mobile sekaligus,
   supaya keduanya tidak pernah berbeda isi. */
const NAV = [
    ['#program', 'Program'],
    ['#capaian', 'Capaian'],
    ['#visi', 'Visi & Misi'],
    ['#pendidik', 'Pendidik'],
    ['#fasilitas', 'Fasilitas'],
    ['#kontak', 'Kontak'],
];

/* ===================== DATA =====================
   Urutan dan rumusan mengikuti halaman "Program Sekolah" di profil. */

const PROGRAM = [
    {
        icon: BookOpenIcon,
        warna: 'hijau',
        judul: 'Kurikulum',
        teks: 'Kurikulum TK Attauhid disusun melalui integrasi Kurikulum Merdeka dengan kurikulum khas TK At Tauhid, sehingga pembelajaran tetap mengacu pada capaian perkembangan anak usia dini sekaligus menguatkan pembiasaan tauhid, adab, tahsin, tahfizh, dan karakter Islami.',
    },
    {
        icon: HeartIcon,
        warna: 'oranye',
        judul: 'Tauhid Value',
        teks: 'Implementasi tauhid di setiap pembelajaran.',
    },
    {
        icon: LeafIcon,
        warna: 'hijau',
        judul: 'Petualang Cilik',
        teks: 'Menjelajahi alam sekitar sekolah.',
    },
    {
        icon: BusIcon,
        warna: 'oranye',
        judul: 'Kunjungan Edukasi',
        teks: 'Mengunjungi suatu tempat untuk memberikan edukasi secara langsung.',
    },
    {
        icon: QuranIcon,
        warna: 'biru',
        judul: 'QURBA',
        /* Kepanjangan akronim persis seperti di profil. Dirender sebagai
           chip supaya kartunya tidak jauh lebih tinggi dari tetangganya. */
        rincian: [
            ['Q', 'Quran (belajar baca)'],
            ['U', 'Untaian do’a'],
            ['R', 'Rutinitas ibadah'],
            ['B', 'Biasakan adab'],
            ['A', 'Akrab dengan Al-Qur’an (hafalan)'],
        ],
    },
];

const CAPAIAN = [
    {
        icon: StarIcon,
        warna: 'oranye',
        judul: 'Nilai Agama dan Budi Pekerti',
        teks: 'Anak mengenal Allah sebagai Rabb, Al-Khaliq, dan Ar-Raziq; hafal rukun Islam, rukun iman, doa harian, dan juz ’amma sesuai jenjang; serta terbiasa dengan adab Islami dalam keseharian.',
    },
    {
        icon: AbcIcon,
        warna: 'biru',
        judul: 'Dasar-Dasar Literasi',
        teks: 'Anak mengenal huruf hijaiyah dan mulai membaca Iqra’ sesuai tingkatan individual, serta mengenal huruf latin sebagai jalur literasi yang berjalan beriringan.',
    },
    {
        icon: NumberIcon,
        warna: 'hijau',
        judul: 'Dasar-Dasar Matematika',
        teks: 'Anak mengenal bilangan, berhitung, bentuk, dan pola sederhana dalam konteks kegiatan sehari-hari.',
    },
    {
        icon: FlaskIcon,
        warna: 'biru',
        judul: 'Dasar-Dasar Sains dan Teknologi',
        teks: 'Anak mengamati gejala alam sebagai bentuk tafakur terhadap ciptaan Allah, serta mengenal teknologi sederhana di lingkungan sekitarnya.',
    },
];

const VISI = 'Terwujudnya pendidikan anak usia dini yang Berkarakter Islami, Mandiri, dan Peduli terhadap lingkungan.';

const MISI = [
    'Memfasilitasi kegiatan belajar mengajar untuk mengembangkan karakter islami sesuai dengan manhaj salaf, melalui kegiatan pembiasaan.',
    'Menanamkan pembentukan perilaku kemandirian, kepemimpinan, rasa tanggung jawab, kerjasama dan adaptabilitas melalui kegiatan pembiasaan dan kegiatan bermain yang menyenangkan.',
    'Menanamkan jiwa peduli lingkungan, melalui kegiatan pembelajaran menyenangkan dan bermakna bagi anak.',
];

const FASILITAS = [
    'Ruang kelas A dan B',
    'Kantor guru laki-laki dan perempuan terpisah',
    'Ruang tamu',
    'Lapangan',
    'Arena bermain',
    'Mini aviary',
    'Kamar mandi laki-laki dan perempuan terpisah',
];

const PENDIDIK = [
    { nama: 'Arel Hibatullah Ridwan, A.Md., S.Pd.', peran: 'Direktur Pendidikan' },
    { nama: 'Martha Amelia Gaha, S.Pd., Gr.', peran: 'Kepala Sekolah' },
    { nama: 'Lya Nurannisa, S.Pd.', peran: 'Tenaga Pendidik' },
    { nama: 'Octaviany Diyarizky, SKM.', peran: 'Tenaga Pendidik' },
    { nama: 'Nida Latifah', peran: 'Tenaga Kependidikan' },
];

/* ===================== HALAMAN ===================== */
export default function Landing() {
    const [reduced, setReduced] = useState(false);
    useEffect(() => {
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        setReduced(mq.matches);
        const handler = (e) => setReduced(e.matches);
        mq.addEventListener('change', handler);
        return () => mq.removeEventListener('change', handler);
    }, []);

    // Menu mobile. Di bawah md navigasi section tidak muat di satu baris,
    // jadi dilipat ke panel yang dibuka lewat tombol garis tiga.
    const [menuBuka, setMenuBuka] = useState(false);

    /* Pola dark mode sama dengan Login.jsx supaya pilihan tema ikut
       terbawa saat pengunjung masuk ke sistem. */
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
            try {
                localStorage.setItem('theme', next ? 'dark' : 'light');
            } catch (e) {}
            return next;
        });
    };

    return (
        <>
            <Head title="TK Attauhid — Mendidik dengan Tauhid">
                <meta
                    name="description"
                    content="TK Attauhid — lembaga pendidikan anak usia dini di Lembang, Bandung Barat, di bawah Yayasan Islam Lembang At Tauhid."
                />
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap"
                    rel="stylesheet"
                />
            </Head>

            <style>{`
                :root {
                    /* Diambil langsung dari piksel berkas logo asli. */
                    --tq-hijau: #7EB842;
                    --tq-oranye: #FA902C;
                    --tq-biru: #36A7DF;

                    --tq-hijau-gelap: #6EA039;
                    --tq-oranye-gelap: #DA7D26;
                }

                /* ---------------------------------------------------------
                   PALET MODE GELAP — empat nilai ini dipakai berulang lewat
                   kelas dark:bg-[...] dan dark:border-[...] di bawah. Kalau
                   masih terasa terlalu gelap atau terlalu terang, cari-ganti
                   keempatnya; jangan ubah satu-satu, karena yang membuat
                   kartu terbaca bukan terangnya melainkan SELISIH antara
                   latar halaman dan permukaan kartu.

                     #282C25  latar halaman, dan panel cekung di dalam kartu
                     #353B31  permukaan kartu   (selisih ~13 dari latar)
                     #3B4235  garis antar bagian
                     #474E40  garis tepi kartu
                   --------------------------------------------------------- */
                .tq-judul { font-family: 'Baloo 2', 'Nunito', system-ui, sans-serif; }
                .tq-teks  { font-family: 'Nunito', system-ui, sans-serif; }

                .tq-btn-oranye {
                    background: var(--tq-oranye);
                    color: #fff;
                    transition: background-color .18s ease, transform .18s ease;
                }
                .tq-btn-oranye:hover { background: var(--tq-oranye-gelap); }
                .tq-btn-oranye:active { transform: scale(.985); }

                .tq-btn-hijau {
                    background: var(--tq-hijau);
                    color: #fff;
                    transition: background-color .18s ease, transform .18s ease;
                }
                .tq-btn-hijau:hover { background: var(--tq-hijau-gelap); }
                .tq-btn-hijau:active { transform: scale(.985); }

                .tq-link-nav { transition: color .18s ease; }
                .tq-link-nav:hover { color: var(--tq-hijau); }

                .tq-kartu { transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
                .tq-kartu:hover { transform: translateY(-3px); box-shadow: 0 18px 38px -22px rgba(60,80,40,.45); }

                .tq-ikon-hijau  { background: rgba(126,184,66,.14); color: var(--tq-hijau); }
                .tq-ikon-oranye { background: rgba(250,144,44,.14); color: var(--tq-oranye); }
                .tq-ikon-biru   { background: rgba(54,167,223,.14); color: var(--tq-biru); }

                .tq-garis-hijau  { background: var(--tq-hijau); }
                .tq-garis-oranye { background: var(--tq-oranye); }
                .tq-garis-biru   { background: var(--tq-biru); }
            `}</style>

            <div className="tq-teks relative min-h-screen overflow-x-hidden bg-[#F6F7F2] text-stone-700 dark:bg-[#282C25] dark:text-stone-300">
                {/* Latar lembut — tiga warna logo, sangat tipis */}
                <div
                    className="pointer-events-none absolute inset-0 z-0 dark:hidden"
                    style={{
                        background:
                            'radial-gradient(circle at 12% 2%, rgba(126,184,66,.16), transparent 42%), radial-gradient(circle at 92% 14%, rgba(54,167,223,.14), transparent 44%), radial-gradient(circle at 70% 92%, rgba(250,144,44,.12), transparent 46%)',
                    }}
                />
                <div
                    className="pointer-events-none absolute inset-0 z-0 hidden dark:block"
                    style={{
                        background:
                            'radial-gradient(circle at 12% 2%, rgba(126,184,66,.14), transparent 42%), radial-gradient(circle at 92% 14%, rgba(54,167,223,.12), transparent 44%)',
                    }}
                />

                {/* ============ NAV ============ */}
                <header className="sticky top-0 z-30 border-b border-stone-200/70 bg-[#F6F7F2]/90 backdrop-blur-md dark:border-[#3B4235] dark:bg-[#282C25]/90">
                    <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-6 sm:py-3.5">
                        <a href="#" className="flex min-w-0 items-center gap-2.5" onClick={() => setMenuBuka(false)}>
                            <div className="flex h-9 w-9 shrink-0 sm:h-10 sm:w-10 items-center justify-center rounded-2xl bg-white p-1.5 shadow-sm ring-1 ring-stone-200 dark:bg-[#353B31] dark:ring-white/10">
                                {/* Logo kubus yang sama dengan halaman login dan
                                    sidebar. Sebelumnya memuat /favicon.ico, yang
                                    masih berisi logo "A" lama. */}
                                <AppLogo className="h-full w-full" />
                            </div>
                            <div className="min-w-0 leading-tight">
                                <p className="tq-judul truncate text-[15px] font-bold text-stone-800 dark:text-stone-100">TK Attauhid</p>
                                {/* Tagline disembunyikan di HP: ruangnya habis oleh
                                    tombol, dan yang terpotong "Mendidik deng…"
                                    justru terlihat rusak. Hero sudah memuatnya. */}
                                <p className="hidden truncate text-[11px] text-stone-500 dark:text-stone-400 sm:block">Mendidik dengan Tauhid</p>
                            </div>
                        </a>

                        <nav className="hidden items-center gap-6 md:flex">
                            {NAV.map(([href, label]) => (
                                <a key={href} href={href} className="tq-link-nav text-[13px] font-semibold text-stone-600 dark:text-stone-400">
                                    {label}
                                </a>
                            ))}
                        </nav>

                        <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
                            <button
                                type="button"
                                onClick={toggleTheme}
                                aria-label={dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-stone-200 bg-white/80 text-stone-500 shadow-sm transition hover:text-stone-700 focus:outline-none focus-visible:ring-2 dark:border-[#474E40] dark:bg-[#353B31]/90 dark:text-stone-300"
                            >
                                {dark ? <SunIcon className="h-4 w-4" /> : <MoonIcon className="h-4 w-4" />}
                            </button>
                            {/* Pintu masuk sistem untuk guru, bendahara, dan
                                wali. Dibuat bergaris, bukan blok berwarna, supaya
                                tidak bersaing dengan "Hubungi" — tamu landing ini
                                kebanyakan calon wali murid, bukan pengguna sistem. */}
                            <Link
                                href={route('login')}
                                className="tq-judul flex h-9 items-center gap-1.5 rounded-xl border-2 px-3 text-[13px] font-bold transition sm:px-3.5 hover:bg-white/60 focus:outline-none focus-visible:ring-2 dark:hover:bg-[#353B31]/60"
                                style={{ borderColor: 'var(--tq-hijau)', color: 'var(--tq-hijau)' }}
                            >
                                <LoginIcon className="h-4 w-4" />
                                Masuk
                            </Link>
                            <a
                                href={KONTAK_WA}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="tq-btn-oranye tq-judul hidden h-9 items-center rounded-xl px-4 text-[13px] font-bold shadow-md focus:outline-none focus-visible:ring-2 sm:inline-flex"
                            >
                                Hubungi
                            </a>
                            <button
                                type="button"
                                onClick={() => setMenuBuka((v) => !v)}
                                aria-label={menuBuka ? 'Tutup menu' : 'Buka menu'}
                                aria-expanded={menuBuka}
                                className="flex h-9 w-9 items-center justify-center rounded-xl border border-stone-200 bg-white/80 text-stone-600 shadow-sm transition focus:outline-none focus-visible:ring-2 dark:border-[#474E40] dark:bg-[#353B31]/90 dark:text-stone-300 md:hidden"
                            >
                                {menuBuka ? <CloseIcon className="h-5 w-5" /> : <MenuIcon className="h-5 w-5" />}
                            </button>
                        </div>
                    </div>

                    {/* Panel menu mobile — tetap di dalam header supaya ikut
                        menempel saat digulir. */}
                    {menuBuka && (
                        <div className="border-t border-stone-200/70 px-4 pb-4 pt-2 dark:border-[#3B4235] md:hidden">
                            <nav className="grid grid-cols-2 gap-1">
                                {NAV.map(([href, label]) => (
                                    <a
                                        key={href}
                                        href={href}
                                        onClick={() => setMenuBuka(false)}
                                        className="rounded-xl px-3 py-2.5 text-[14px] font-semibold text-stone-700 transition hover:bg-white/70 dark:text-stone-300 dark:hover:bg-[#353B31]"
                                    >
                                        {label}
                                    </a>
                                ))}
                            </nav>
                            <a
                                href={KONTAK_WA}
                                target="_blank"
                                rel="noopener noreferrer"
                                onClick={() => setMenuBuka(false)}
                                className="tq-btn-oranye tq-judul mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-3 text-[14px] font-bold shadow-md"
                            >
                                <WaIcon className="h-5 w-5" />
                                Hubungi sekolah
                            </a>
                        </div>
                    )}
                </header>

                {/* ============ HERO — dari halaman "Pengantar" ============ */}
                <section className="relative z-10 mx-auto grid max-w-6xl gap-10 px-4 pb-14 pt-8 sm:gap-12 sm:px-6 sm:pb-16 sm:pt-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-14 lg:pt-20">
                    <div>
                        <Eyebrow latin="Lembaga pendidikan anak usia dini · Lembang, Bandung Barat" />

                        <h1 className="tq-judul mt-4 text-[2rem] font-bold leading-[1.12] sm:mt-5 sm:text-[2.6rem] lg:text-[3rem] text-stone-800 dark:text-stone-50">
                            TK Attauhid
                            {/* block: frasa hijau selalu di baris sendiri. Tanpa
                                ini, di HP "Mendidik" menempel di baris nama
                                sekolah dan sisanya tumpah ke baris berikut. */}
                            <span className="block" style={{ color: 'var(--tq-hijau)' }}>Mendidik dengan Tauhid</span>
                        </h1>

                        <p className="mt-4 max-w-lg text-[14.5px] leading-[1.75] sm:mt-5 sm:text-[15px] sm:leading-[1.8] text-stone-600 dark:text-stone-400">
                            Berlandaskan nilai-nilai Al-Qur&rsquo;an dan Sunnah sesuai pemahaman yang lurus, TK Attauhid
                            berkomitmen membangun lingkungan pendidikan yang Islami, nyaman, dan mendukung perkembangan
                            anak secara optimal.
                        </p>

                        <p className="mt-3 max-w-lg text-[14.5px] leading-[1.75] sm:mt-4 sm:text-[15px] sm:leading-[1.8] text-stone-600 dark:text-stone-400">
                            TK Attauhid memandang bahwa masa kanak-kanak merupakan fase penting dalam pembentukan
                            karakter, adab, serta dasar keilmuan anak. Proses pembelajaran dirancang tidak hanya untuk
                            mengembangkan kemampuan akademik dasar, tetapi juga menanamkan nilai-nilai keislaman,
                            kemandirian, kedisiplinan, dan kecintaan terhadap lingkungan sekitar.
                        </p>

                        {/* Di HP kedua tombol ditumpuk selebar layar — sejajar dengan
                            ukuran jari dan tidak menyisakan baris yang timpang. */}
                        <div className="mt-7 grid gap-2.5 sm:mt-8 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
                            <a
                                href={KONTAK_WA}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="tq-btn-oranye tq-judul flex items-center justify-center gap-2 rounded-2xl px-6 py-3.5 text-[14px] font-bold shadow-lg focus:outline-none focus-visible:ring-2"
                            >
                                Hubungi sekolah
                                <ArrowIcon className="h-4 w-4" />
                            </a>
                            <a
                                href="#program"
                                className="tq-judul rounded-2xl border-2 px-6 py-3 text-center text-[14px] font-bold transition hover:bg-white/60 focus:outline-none focus-visible:ring-2 dark:hover:bg-[#353B31]/60"
                                style={{ borderColor: 'var(--tq-biru)', color: 'var(--tq-biru)' }}
                            >
                                Lihat program sekolah
                            </a>
                        </div>
                    </div>

                    <KartuIdentitas />
                </section>

                {/* ============ PROGRAM SEKOLAH ============ */}
                <section id="program" className="relative z-10 scroll-mt-16 sm:scroll-mt-20 border-t border-stone-200/70 dark:border-[#3B4235]">
                    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                        <Eyebrow latin="Program sekolah" />
                        <h2 className="tq-judul mt-3 max-w-2xl text-[1.6rem] font-bold leading-tight text-stone-800 dark:text-stone-50 sm:text-[2.2rem]">
                            Program sekolah
                        </h2>

                        {/*
                            Grid 6 kolom, bukan 3. Lima kartu di grid 3 kolom
                            menyisakan satu petak kosong di kanan bawah; di sini
                            tiga kartu pertama mengambil 2 kolom (baris atas) dan
                            dua terakhir 3 kolom penuh (baris bawah).

                            items-start penting: tanpa itu semua kartu sebaris
                            dipaksa setinggi yang tertinggi, dan sisa tingginya
                            jadi ruang kosong di dalam kartu yang isinya pendek.
                        */}
                        <div className="mt-7 grid sm:mt-10 items-start gap-4 sm:grid-cols-2 lg:grid-cols-6">
                            {PROGRAM.map((p, i) => {
                                const lebar = i < 3 ? 'lg:col-span-2' : 'lg:col-span-3';
                                // Di layar sedang (2 kolom) kartu terakhir juga
                                // sendirian, jadi dilebarkan penuh.
                                const lebarSm = i === PROGRAM.length - 1 ? 'sm:col-span-2' : '';

                                return (
                                    <Reveal key={p.judul} delay={i * 70} reduced={reduced} className={`${lebar} ${lebarSm}`}>
                                        <article className="tq-kartu flex h-full flex-col overflow-hidden rounded-3xl border border-stone-200/80 bg-white/80 dark:border-[#474E40] dark:bg-[#353B31]/80">
                                            <div className={`h-1.5 w-full tq-garis-${p.warna}`} />
                                            <div className="flex flex-1 flex-col p-5 sm:p-6">
                                                {/* Di HP ikon dan judul sebaris — ditumpuk, kartu
                                                    yang isinya satu kalimat jadi setinggi layar. */}
                                                <div className="flex items-center gap-3 sm:block">
                                                    <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl sm:h-12 sm:w-12 tq-ikon-${p.warna}`}>
                                                        <p.icon className="h-[22px] w-[22px] sm:h-6 sm:w-6" />
                                                    </div>
                                                    <h3 className="tq-judul text-[17px] font-bold leading-snug text-stone-800 dark:text-stone-100 sm:mt-4">{p.judul}</h3>
                                                </div>

                                                {p.teks && (
                                                    <p className="mt-2 text-[13.5px] leading-relaxed text-stone-600 dark:text-stone-400">{p.teks}</p>
                                                )}

                                                {p.rincian && (
                                                    <div className="mt-3 flex flex-wrap gap-2">
                                                        {p.rincian.map(([huruf, arti]) => (
                                                            <span
                                                                key={huruf}
                                                                className="flex items-center gap-1.5 rounded-full bg-stone-100 py-1 pl-1 pr-3 text-[12px] font-semibold text-stone-600 dark:bg-[#282C25] dark:text-stone-300"
                                                            >
                                                                <span
                                                                    className="tq-judul flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-bold text-white"
                                                                    style={{ background: 'var(--tq-biru)' }}
                                                                >
                                                                    {huruf}
                                                                </span>
                                                                {arti}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        </article>
                                    </Reveal>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* ============ CAPAIAN PEMBELAJARAN ============ */}
                <section id="capaian" className="relative z-10 scroll-mt-16 sm:scroll-mt-20 border-t border-stone-200/70 dark:border-[#3B4235]">
                    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                        <Eyebrow latin="Capaian pembelajaran" />
                        <h2 className="tq-judul mt-3 max-w-2xl text-[1.6rem] font-bold leading-tight text-stone-800 dark:text-stone-50 sm:text-[2.2rem]">
                            Capaian pembelajaran
                        </h2>

                        <div className="mt-7 grid sm:mt-10 items-start gap-4 sm:grid-cols-2">
                            {CAPAIAN.map((c, i) => (
                                <Reveal key={c.judul} delay={i * 70} reduced={reduced}>
                                    <div className="flex h-full gap-3.5 rounded-3xl border border-stone-200/80 bg-white/70 p-5 sm:gap-4 sm:p-6 dark:border-[#474E40] dark:bg-[#353B31]/70">
                                        <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl tq-ikon-${c.warna}`}>
                                            <c.icon className="h-5 w-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <h3 className="tq-judul text-[16px] font-bold text-stone-800 dark:text-stone-100">{c.judul}</h3>
                                            <p className="mt-1.5 text-[13.5px] leading-relaxed text-stone-600 dark:text-stone-400">{c.teks}</p>
                                        </div>
                                    </div>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ============ VISI & MISI ============ */}
                <section id="visi" className="relative z-10 scroll-mt-16 sm:scroll-mt-20 border-t border-stone-200/70 dark:border-[#3B4235]">
                    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                        <Eyebrow latin="Visi & misi sekolah" />
                        <h2 className="tq-judul mt-3 max-w-2xl text-[1.6rem] font-bold leading-tight text-stone-800 dark:text-stone-50 sm:text-[2.2rem]">
                            Visi &amp; misi sekolah
                        </h2>

                        <div className="mt-7 grid sm:mt-10 gap-5 lg:grid-cols-[0.9fr_1.1fr]">
                            <div
                                className="rounded-3xl p-6 text-white shadow-lg sm:p-7"
                                style={{ background: 'linear-gradient(140deg, var(--tq-hijau) 0%, var(--tq-hijau-gelap) 100%)' }}
                            >
                                <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-white/75">Visi</p>
                                <p className="tq-judul mt-3 text-[18px] font-semibold leading-snug sm:text-[22px]">{VISI}</p>
                            </div>

                            <div className="rounded-3xl border border-stone-200/80 bg-white/70 p-6 sm:p-7 dark:border-[#474E40] dark:bg-[#353B31]/70">
                                <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-stone-400">Misi</p>
                                <ol className="mt-4 space-y-4">
                                    {MISI.map((m, i) => (
                                        <li key={i} className="flex gap-3.5">
                                            <span
                                                className="tq-judul flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white"
                                                style={{ background: i === 0 ? 'var(--tq-hijau)' : i === 1 ? 'var(--tq-oranye)' : 'var(--tq-biru)' }}
                                            >
                                                {i + 1}
                                            </span>
                                            <p className="text-[13.5px] leading-relaxed text-stone-600 dark:text-stone-300">{m}</p>
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        </div>

                        {/* Keterangan di bawah halaman Visi pada profil. */}
                        <div
                            className="mt-5 rounded-2xl border-l-4 bg-white/70 p-5 dark:bg-[#353B31]/70"
                            style={{ borderLeftColor: 'var(--tq-hijau)' }}
                        >
                            <p className="text-[13.5px] leading-relaxed text-stone-600 dark:text-stone-300">
                                Sekolah At Tauhid merupakan lembaga pendidikan Islam yang berfokus pada pembinaan generasi
                                muslim berkarakter Islami dengan berpegang teguh kepada ajaran Tauhid serta nilai-nilai
                                Al-Qur&rsquo;an dan Sunnah. Seluruh proses pendidikan dan kegiatan sekolah dijalankan
                                berdasarkan pemahaman generasi awal Islam{' '}
                                <span className="font-bold text-stone-800 dark:text-stone-100">(Salafush Shalih)</span> yang lurus
                                dan sesuai tuntunan syariat.
                            </p>
                        </div>
                    </div>
                </section>

                {/* ============ PENDIDIK & STRUKTUR ============ */}
                <section id="pendidik" className="relative z-10 scroll-mt-16 sm:scroll-mt-20 border-t border-stone-200/70 dark:border-[#3B4235]">
                    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                        <Eyebrow latin="Profil pendidik" />
                        <h2 className="tq-judul mt-3 max-w-2xl text-[1.6rem] font-bold leading-tight text-stone-800 dark:text-stone-50 sm:text-[2.2rem]">
                            Profil pendidik
                        </h2>
                        <p className="mt-4 max-w-2xl text-[14.5px] leading-[1.8] text-stone-600 dark:text-stone-400">
                            Didukung oleh kepala sekolah yang berpengalaman dalam bidang pendidikan Islam dan teknologi
                            pendidikan, serta para pengajar yang kompeten, sabar, dan mencintai dunia anak, TK At Tauhid
                            berkomitmen membentuk anak-anak yang berkarakter Islami, cinta Al-Qur&rsquo;an, beradab, dan siap
                            tumbuh menjadi generasi yang shalih dan shalihah.
                        </p>

                        <div className="mt-10">
                            <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-stone-400">
                                Struktur organisasi
                            </p>

                            <div className="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                                {PENDIDIK.map((p, i) => (
                                    <Reveal key={p.nama} delay={i * 60} reduced={reduced}>
                                        <div
                                            className="border-t-2 pt-4"
                                            style={{ borderTopColor: i % 3 === 0 ? 'var(--tq-hijau)' : i % 3 === 1 ? 'var(--tq-oranye)' : 'var(--tq-biru)' }}
                                        >
                                            <p className="text-[11px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-400">{p.peran}</p>
                                            <p className="tq-judul mt-1 text-[15px] font-bold text-stone-800 dark:text-stone-100">{p.nama}</p>
                                        </div>
                                    </Reveal>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* ============ FASILITAS ============ */}
                <section id="fasilitas" className="relative z-10 scroll-mt-16 sm:scroll-mt-20 border-t border-stone-200/70 dark:border-[#3B4235]">
                    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                        <Eyebrow latin="Fasilitas" />
                        <h2 className="tq-judul mt-3 max-w-2xl text-[1.6rem] font-bold leading-tight text-stone-800 dark:text-stone-50 sm:text-[2.2rem]">
                            Fasilitas
                        </h2>

                        <div className="mt-7 grid sm:mt-10 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {FASILITAS.map((f, i) => (
                                <Reveal key={f} delay={i * 50} reduced={reduced}>
                                    <div className="flex items-center gap-3 rounded-2xl border border-stone-200/80 bg-white/70 px-5 py-4 dark:border-[#474E40] dark:bg-[#353B31]/70">
                                        <span style={{ color: 'var(--tq-hijau)' }}>
                                            <CheckIcon className="h-5 w-5" />
                                        </span>
                                        <span className="text-[13.5px] font-semibold text-stone-700 dark:text-stone-300">{f}</span>
                                    </div>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ============ KONTAK — dari halaman "Identitas" ============ */}
                <section id="kontak" className="relative z-10 mx-auto max-w-5xl scroll-mt-16 px-4 pb-14 sm:scroll-mt-20 sm:px-6 sm:pb-20">
                    <div className="overflow-hidden rounded-3xl border sm:rounded-[2rem] border-stone-200/80 bg-white/85 shadow-[0_24px_60px_-32px_rgba(60,80,40,.4)] dark:border-[#474E40] dark:bg-[#353B31]/90">
                        <div className="h-2 w-full" style={{ background: 'linear-gradient(90deg, var(--tq-hijau) 0%, var(--tq-oranye) 50%, var(--tq-biru) 100%)' }} />

                        <div className="px-5 py-10 text-center sm:px-10 sm:py-12">
                            <h2 className="tq-judul text-[1.8rem] font-bold leading-tight text-stone-800 dark:text-stone-50 sm:text-[2.2rem]">
                                Kontak
                            </h2>

                            <div className="mt-6 grid gap-2.5 sm:mt-8 sm:flex sm:flex-wrap sm:items-center sm:justify-center sm:gap-3">
                                <a
                                    href={KONTAK_WA}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="tq-btn-hijau tq-judul flex items-center justify-center gap-2 rounded-2xl px-6 py-3.5 text-[14px] font-bold shadow-lg focus:outline-none focus-visible:ring-2"
                                >
                                    <WaIcon className="h-5 w-5" />
                                    {KONTAK_WA_TAMPIL}
                                </a>
                                <a
                                    href={`mailto:${KONTAK_EMAIL}`}
                                    className="tq-judul flex min-w-0 items-center justify-center gap-2 rounded-2xl border-2 px-5 py-3 text-[14px] font-bold sm:px-6 transition hover:bg-stone-50 focus:outline-none dark:hover:bg-[#3B4235]"
                                    style={{ borderColor: 'var(--tq-biru)', color: 'var(--tq-biru)' }}
                                >
                                    <MailIcon className="h-5 w-5 shrink-0" />
                                    {/* break-all: alamat email tidak punya spasi,
                                        tanpa ini ia mendorong kartu melebar di
                                        layar 320px. */}
                                    <span className="break-all">{KONTAK_EMAIL}</span>
                                </a>
                            </div>

                            <a
                                href={ALAMAT_MAPS}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mx-auto mt-6 flex max-w-md items-start gap-3 rounded-2xl sm:mt-8 bg-stone-50 p-4 text-left transition hover:bg-stone-100 dark:bg-[#282C25] dark:hover:bg-[#313629]"
                            >
                                <span className="mt-0.5 shrink-0" style={{ color: 'var(--tq-oranye)' }}>
                                    <PinIcon className="h-5 w-5" />
                                </span>
                                <span className="text-[13px] leading-relaxed text-stone-600 dark:text-stone-400">{ALAMAT}</span>
                            </a>
                        </div>
                    </div>
                </section>

                {/* ============ FOOTER ============ */}
                <footer className="relative z-10 border-t border-stone-200/70 dark:border-[#3B4235]">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-center sm:flex-row sm:px-6 sm:text-left">
                        <div>
                            <p className="tq-judul text-[13px] font-bold text-stone-700 dark:text-stone-300">
                                TK Attauhid · {YAYASAN}
                            </p>
                            <p className="mt-0.5 text-[11px] text-stone-400 dark:text-stone-400">
                                Nomor Izin Berusaha {NIB} · © {new Date().getFullYear()}
                            </p>
                        </div>
                        <Link
                            href={route('login')}
                            className="text-[12px] font-semibold text-stone-400 underline-offset-4 transition hover:underline dark:text-stone-400"
                        >
                            Masuk sistem sekolah
                        </Link>
                    </div>
                </footer>
            </div>
        </>
    );
}

/* ===================== KOMPONEN ===================== */

function Eyebrow({ latin }) {
    return (
        <div className="flex items-start gap-2.5">
            {/* items-start + mt: teks panjang di HP terlipat dua baris, dan
                garisnya harus tetap sejajar dengan baris PERTAMA. */}
            <span className="mt-[7px] h-1 w-6 shrink-0 rounded-full sm:w-8" style={{ background: 'var(--tq-hijau)' }} />
            <span className="text-[10.5px] font-bold uppercase leading-relaxed tracking-[0.12em] text-stone-500 dark:text-stone-400 sm:text-[11px] sm:tracking-[0.14em]">{latin}</span>
        </div>
    );
}

/* Kartu hero berisi halaman "Identitas" apa adanya — nama, nomor izin,
   yayasan, alamat, kontak. Sebelumnya kartu ini berisi tiga kalimat
   karangan; diganti data yang memang tertulis di profil. */
function KartuIdentitas() {
    const BARIS = [
        { label: 'Nama', nilai: 'TK Attauhid' },
        { label: 'Nomor Izin Berusaha', nilai: `${NIB} — di bawah ${YAYASAN}` },
        { label: 'Alamat', nilai: ALAMAT },
        { label: 'Kontak', nilai: `WhatsApp ${KONTAK_WA_TAMPIL} · ${KONTAK_EMAIL}` },
    ];

    return (
        <div className="relative mx-auto w-full max-w-md">
            <div
                className="pointer-events-none absolute -inset-5 rounded-[2.5rem] blur-2xl"
                style={{ background: 'linear-gradient(140deg, rgba(126,184,66,.18), rgba(54,167,223,.14) 60%, rgba(250,144,44,.16))' }}
            />

            <div className="relative overflow-hidden rounded-3xl border sm:rounded-[2rem] border-stone-200/80 bg-white/92 shadow-[0_28px_70px_-30px_rgba(60,80,40,.4)] dark:border-[#474E40] dark:bg-[#353B31]/95">
                <div className="h-2 w-full" style={{ background: 'linear-gradient(90deg, var(--tq-hijau) 0%, var(--tq-oranye) 50%, var(--tq-biru) 100%)' }} />

                <div className="px-5 py-6 sm:px-7 sm:py-7">
                    <div className="flex items-center gap-2.5">
                        <span className="flex h-9 w-9 items-center justify-center rounded-xl tq-ikon-biru">
                            <IdIcon className="h-5 w-5" />
                        </span>
                        <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-stone-400">
                            Identitas sekolah
                        </p>
                    </div>

                    <dl className="mt-6 space-y-5">
                        {BARIS.map((b) => (
                            <div key={b.label}>
                                <dt className="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-400">{b.label}</dt>
                                <dd className="tq-judul mt-1 break-words text-[14px] font-semibold leading-snug text-stone-800 dark:text-stone-100">{b.nilai}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </div>
    );
}

/* className diteruskan supaya pemanggil bisa menentukan lebar kolom grid
   (lg:col-span-*) — tanpa itu span-nya tidak akan pernah sampai ke elemen
   yang benar-benar jadi anak grid. */
function Reveal({ children, delay = 0, reduced, className = '' }) {
    const ref = useRef(null);
    const [visible, setVisible] = useState(reduced);

    useEffect(() => {
        if (reduced) {
            setVisible(true);
            return;
        }
        const el = ref.current;
        if (!el) return;
        const obs = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setVisible(true);
                    obs.disconnect();
                }
            },
            { threshold: 0.12 },
        );
        obs.observe(el);
        return () => obs.disconnect();
    }, [reduced]);

    return (
        <div
            ref={ref}
            className={`h-full ${className}`}
            style={{
                transitionDelay: `${delay}ms`,
                transitionProperty: 'opacity, transform',
                transitionDuration: reduced ? '0ms' : '600ms',
                transitionTimingFunction: 'ease-out',
                opacity: visible ? 1 : 0,
                transform: visible ? 'translateY(0)' : 'translateY(14px)',
            }}
        >
            {children}
        </div>
    );
}
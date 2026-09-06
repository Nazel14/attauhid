import { useEffect, useRef, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';

/* =====================================================================
 * KONTAK — ganti satu tempat ini saja, dipakai di dua tombol.
 * ===================================================================== */
const KONTAK_WA = 'https://wa.me/6281220497013';
const KONTAK_LABEL = 'Hubungi via WhatsApp';

/* =====================================================================
 * IKON
 * Semua komponen ikon WAJIB dideklarasikan di atas array data di bawah.
 * Kalau dipindah ke bawah, FITUR_* yang mereferensikannya kena
 * ReferenceError (temporal dead zone) saat modul dimuat.
 * ===================================================================== */
const Svg = ({ children, className = 'h-4 w-4' }) => (
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
const MicIcon = (p) => (
    <Svg {...p}>
        <path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" />
        <path d="M19 11a7 7 0 01-14 0M12 18v3" />
    </Svg>
);
const CalendarCheckIcon = (p) => (
    <Svg {...p}>
        <rect x="3.5" y="5" width="17" height="16" rx="2" />
        <path d="M8 3v4M16 3v4M3.5 10h17M9 15l2 2 4-4" />
    </Svg>
);
const FileTextIcon = (p) => (
    <Svg {...p}>
        <path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z" />
        <path d="M14 3v5h5M9 13h6M9 17h6" />
    </Svg>
);
const ChartIcon = (p) => (
    <Svg {...p}>
        <path d="M4 20V10M10 20V4M16 20v-7M4 20h16" />
    </Svg>
);
const BookOpenIcon = (p) => (
    <Svg {...p}>
        <path d="M12 6.5a5.2 5.2 0 00-3.5-1.3c-1.2 0-2.4.4-3.3 1.1a.75.75 0 00-.2.5v10.6c0 .6.6 1 1.1.7a4.5 4.5 0 012.4-.6c1.4 0 2.6.5 3.5 1.4m0-12.4a5.2 5.2 0 013.5-1.3c1.2 0 2.4.4 3.3 1.1a.75.75 0 01.2.5v10.6c0 .6-.6 1-1.1.7a4.5 4.5 0 00-2.4-.6c-1.4 0-2.6.5-3.5 1.4m0-12.4v12.4" />
    </Svg>
);
const CoinsIcon = (p) => (
    <Svg {...p}>
        <ellipse cx="12" cy="6" rx="7" ry="3" />
        <path d="M5 6v12c0 1.66 3.13 3 7 3s7-1.34 7-3V6M5 12c0 1.66 3.13 3 7 3s7-1.34 7-3" />
    </Svg>
);
const ReceiptIcon = (p) => (
    <Svg {...p}>
        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
        <path d="M9 8h6M9 12h6" />
    </Svg>
);
const UsersIcon = (p) => (
    <Svg {...p}>
        <circle cx="9" cy="8" r="3.25" />
        <path d="M2.5 20a6.5 6.5 0 0113 0M15.5 6.5a3.25 3.25 0 010 6.3M18 20a6.5 6.5 0 00-4-5.98" />
    </Svg>
);
const TrendingUpIcon = (p) => (
    <Svg {...p}>
        <path d="M3 17l6-6 4 4 8-8M15 7h6v6" />
    </Svg>
);
const CheckIcon = (p) => (
    <Svg {...p}>
        <path d="M5 12.5l5 5L19 6.5" />
    </Svg>
);
const ShieldIcon = (p) => (
    <Svg {...p}>
        <path d="M12 3l7.5 3v5.5c0 4.4-3 8.4-7.5 9.5-4.5-1.1-7.5-5.1-7.5-9.5V6L12 3z" />
        <path d="M9.25 12l1.9 1.9 3.6-3.8" />
    </Svg>
);

/* =====================================================================
 * DATA TAMPILAN
 * ===================================================================== */

/* Angka Arab-Timur untuk nomor juz di kartu mutaba'ah — huruf yang
   dipakai santri sehari-hari di mushaf, bukan angka Latin. */
const ANGKA_ARAB = '٠١٢٣٤٥٦٧٨٩';
const keArab = (n) => String(n).replace(/\d/g, (d) => ANGKA_ARAB[Number(d)]);

/* Contoh santri yang berputar di kartu hero. Data karangan untuk
   ilustrasi — struktur field-nya sengaja sama dengan data asli. */
const CONTOH_SANTRI = [
    { nama: 'Ahmad Fauzi', kelas: 'Kelas 8B', juz: 12, halaman: 7, potongan: 'Al-Baqarah 141–145', jenis: 'Hafalan' },
    { nama: 'Siti Aisyah', kelas: 'Kelas 7A', juz: 5, halaman: 3, potongan: 'An-Nisa 12–15', jenis: "Muroja'ah" },
    { nama: 'Muhammad Rizki', kelas: 'Kelas 9A', juz: 23, halaman: 15, potongan: 'Yasin 1–12', jenis: 'Hafalan' },
    { nama: 'Fatimah Zahra', kelas: 'Kelas 7C', juz: 2, halaman: 11, potongan: 'Al-Baqarah 80–84', jenis: 'Tilawah' },
];

/* Warna status = kosakata yang sama dengan di dalam aplikasi. */
const WARNA_JENIS = {
    Hafalan: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300',
    "Muroja'ah": 'bg-blue-500/10 text-blue-700 ring-blue-500/20 dark:text-blue-300',
    Tilawah: 'bg-purple-500/10 text-purple-700 ring-purple-500/20 dark:text-purple-300',
};

/* Alur ini urutan sungguhan di dalam sistem, bukan penomoran hiasan. */
const ALUR_TAHFIZH = [
    { judul: 'Guru buka kelas', teks: 'Presensi hari itu dicatat lebih dulu, jadi setoran selalu menempel pada kehadiran.' },
    { judul: 'Santri setor', teks: 'Guru menilai per halaman: hafalan baru, muroja\u2019ah, atau tilawah, plus catatan tajwid.' },
    { judul: 'Progres terakumulasi', teks: 'Setoran otomatis menambah peta juz santri — tanpa rekap ulang di buku.' },
    { judul: 'Rapor keluar', teks: 'Akhir semester, rekap hafalan dan presensi tersusun jadi rapor PDF siap cetak.' },
];

const ALUR_KEUANGAN = [
    { judul: 'Diajukan', teks: 'Bendahara membuat tagihan santri atau mengajukan pengeluaran dari pos RKAS.' },
    { judul: 'Diperiksa', teks: 'Kepala sekolah memeriksa kesesuaian pengajuan dengan anggaran yang sudah disahkan.' },
    { judul: 'Disetujui', teks: 'Ketua yayasan memberi persetujuan akhir. Setiap tahap tercatat siapa dan kapan.' },
    { judul: 'Dilaporkan', teks: 'Dana cair, LPJ masuk, dan angkanya langsung muncul di buku kas dan realisasi RKAS.' },
];

const FITUR_TAHFIZH = [
    {
        icon: MicIcon,
        judul: 'Setoran hafalan',
        teks: 'Catat setoran per halaman langsung dari kelas, lengkap dengan jenis setoran dan catatan tajwid.',
    },
    {
        icon: CalendarCheckIcon,
        judul: 'Presensi harian',
        teks: 'Kehadiran tercatat per kelas per hari, siap dipakai wali kelas maupun laporan ke orang tua.',
    },
    {
        icon: FileTextIcon,
        judul: 'Rapor PDF',
        teks: 'Rekap satu semester tersusun otomatis jadi rapor siap cetak, tanpa menyalin ulang dari buku.',
    },
    {
        icon: ChartIcon,
        judul: 'Pantau semua kelas',
        teks: 'Kepala sekolah melihat progres tiap kelas dari satu layar, tanpa menanyai wali kelas satu per satu.',
    },
    {
        icon: BookOpenIcon,
        judul: 'Kurikulum Aisar',
        teks: 'Lima jilid metode Aisar tersedia dalam bentuk halaman digital, dari pengenalan huruf sampai tajwid dan waqaf.',
    },
    {
        icon: UsersIcon,
        judul: 'Panel untuk santri',
        teks: 'Santri punya akun sendiri untuk melihat riwayat setoran dan capaian juznya.',
    },
];

const FITUR_KEUANGAN = [
    {
        icon: CoinsIcon,
        judul: 'RKAS & realisasi',
        teks: 'Susun anggaran per pos, lalu pantau serapannya berjalan — bukan baru ketahuan selisih di akhir tahun.',
    },
    {
        icon: ReceiptIcon,
        judul: 'Tagihan & pembayaran',
        teks: 'SPP, uang masuk, koperasi, dan donatur masuk satu alur. Kwitansi bisa dicetak satuan atau massal.',
    },
    {
        icon: UsersIcon,
        judul: 'Penggajian',
        teks: 'Gaji dihitung dari kehadiran plus komponen tunjangan dan potongan. Slip gaji tinggal cetak.',
    },
    {
        icon: TrendingUpIcon,
        judul: 'Laporan siap audit',
        teks: 'Buku kas umum, daftar tunggakan, dan realisasi RKAS tersedia kapan saja, bukan hanya saat diminta yayasan.',
    },
    {
        icon: ShieldIcon,
        judul: 'Persetujuan berjenjang',
        teks: 'Pengeluaran melewati pemeriksaan dan persetujuan. Riwayat siapa menyetujui apa tersimpan permanen.',
    },
    {
        icon: FileTextIcon,
        judul: 'Dokumen resmi',
        teks: 'Kwitansi, slip gaji, dan buku kas keluar sebagai PDF berkop pesantren, siap diarsipkan.',
    },
];

const PERAN = [
    { nama: 'Admin', teks: 'Kelola santri, guru, kelas, dan tahun ajaran.' },
    { nama: 'Guru', teks: 'Catat setoran dan presensi kelas binaannya.' },
    { nama: 'Santri', teks: 'Lihat riwayat setoran dan capaian hafalan.' },
    { nama: 'Bendahara', teks: 'Buat tagihan, catat pembayaran, ajukan pengeluaran.' },
    { nama: 'Kepala sekolah', teks: 'Pantau progres kelas dan periksa pengajuan dana.' },
    { nama: 'Ketua yayasan', teks: 'Beri persetujuan akhir dan baca laporan keuangan.' },
    { nama: 'Pihak yayasan', teks: 'Memantau laporan keuangan dan realisasi anggaran yayasan.' },
    { nama: 'HRD', teks: 'Mengelola data pegawai dan proses penggajian.' },
];

const TANYA_JAWAB = [
    {
        tanya: 'Data santri kami sudah ada di Excel. Perlu diketik ulang?',
        jawab: 'Tidak. Data santri, guru, dan kelas bisa diimpor dari file Excel. Formatnya kami siapkan mengikuti kolom yang sudah Anda punya.',
    },
    {
        tanya: 'Apakah harus beli dua modul sekaligus?',
        jawab: 'Tidak. Modul Tahfizh dan modul Keuangan bisa dibeli terpisah. Modul yang tidak dibeli tidak akan muncul sama sekali di sistem, termasuk di halaman login.',
    },
    {
        tanya: 'Guru kami belum terbiasa dengan aplikasi.',
        jawab: 'Tampilan guru sengaja dibuat sesederhana mungkin: pilih kelas, pilih santri, catat setoran. Bisa dibuka dari HP, dan kami dampingi saat pelatihan awal.',
    },
    {
        tanya: 'Data pesantren kami disimpan di mana?',
        jawab: 'Di server dan domain milik pesantren Anda sendiri. Anda memegang penuh basis datanya, termasuk kalau suatu saat ingin memindahkannya.',
    },
];

/* =====================================================================
 * HALAMAN
 * ===================================================================== */
export default function Landing() {
    const { modules = {} } = usePage().props;
    const tampilTahfizh = modules.raport !== false;
    const tampilKeuangan = !!modules.keuangan;
    const duaModul = tampilTahfizh && tampilKeuangan;

    /* ===== Hormati prefers-reduced-motion ===== */
    const [reduced, setReduced] = useState(false);
    useEffect(() => {
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        setReduced(mq.matches);
        const handler = (e) => setReduced(e.matches);
        mq.addEventListener('change', handler);
        return () => mq.removeEventListener('change', handler);
    }, []);

    /* ===== Dark mode — pola sama dengan Login.jsx ===== */
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

    /* ===== Kartu mutaba'ah berputar ===== */
    const [idx, setIdx] = useState(0);
    useEffect(() => {
        if (reduced) return;
        const t = setInterval(() => setIdx((i) => (i + 1) % CONTOH_SANTRI.length), 5200);
        return () => clearInterval(t);
    }, [reduced]);
    const santri = CONTOH_SANTRI[idx];

    const judulHero = duaModul ? (
        <>
            Hafalan santri dan kas pesantren,
            <br className="hidden sm:block" /> tercatat di satu tempat.
        </>
    ) : tampilKeuangan ? (
        <>Setiap rupiah pesantren, tercatat sampai jadi laporan.</>
    ) : (
        <>Setiap setoran santri, tercatat sampai jadi rapor.</>
    );

    const subHero = duaModul
        ? 'Guru mencatat setoran dan presensi dari kelas. Bendahara mengurus tagihan, pengeluaran, dan gaji. Keduanya berjalan di satu sistem, dengan panel yang terpisah untuk tiap peran.'
        : tampilKeuangan
          ? 'RKAS, tagihan santri, penggajian, dan laporan kas berjalan dalam satu alur persetujuan yang jelas — dari pengajuan sampai dana cair.'
          : 'Guru mencatat setoran dan presensi langsung dari kelas. Progres juz terkumpul sendiri, dan rapor semester keluar sebagai PDF siap cetak.';

    return (
        <>
            <Head title="Beranda">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500;600&family=Amiri:wght@400;700&display=swap"
                    rel="stylesheet"
                />
            </Head>

            <div className="relative min-h-screen overflow-x-hidden bg-[#ece5db] text-stone-800 dark:bg-[#242019] dark:text-stone-200">
                {/* Ambient — mengikuti pola Login.jsx */}
                <div
                    className="pointer-events-none absolute inset-0 z-0 dark:hidden"
                    style={{
                        background:
                            'radial-gradient(circle at 15% 4%, rgba(120,113,108,0.08), transparent 40%), radial-gradient(circle at 88% 22%, rgba(217,119,87,0.07), transparent 44%)',
                    }}
                />
                <div
                    className="pointer-events-none absolute inset-0 z-0 hidden dark:block"
                    style={{ background: 'linear-gradient(180deg, #2b2723 0%, #262220 45%, #211d19 100%)' }}
                />

                {/* ============ NAV ============ */}
                <header className="sticky top-0 z-30 border-b border-stone-200/60 bg-[#ece5db]/85 backdrop-blur-md dark:border-[#3a352f] dark:bg-[#242019]/85">
                    <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5 sm:px-6">
                        <div className="flex min-w-0 items-center gap-2.5">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm ring-1 ring-stone-200 dark:bg-[#3d3833] dark:ring-white/10">
                                <img src="/favicon.ico" alt="" className="h-full w-full" style={{ objectFit: 'contain' }} />
                            </div>
                            <span className="truncate text-[13px] font-bold tracking-tight text-stone-800 dark:text-stone-100">
                                Sistem Management Tahfizh
                            </span>
                        </div>

                        <nav className="hidden items-center gap-6 md:flex">
                            <a href="#modul" className="text-[13px] font-medium text-stone-600 transition hover:text-claude-600 dark:text-stone-400 dark:hover:text-claude-300">
                                Modul
                            </a>
                            <a href="#peran" className="text-[13px] font-medium text-stone-600 transition hover:text-claude-600 dark:text-stone-400 dark:hover:text-claude-300">
                                Peran
                            </a>
                            <a href="#paket" className="text-[13px] font-medium text-stone-600 transition hover:text-claude-600 dark:text-stone-400 dark:hover:text-claude-300">
                                Paket
                            </a>
                            <a href="#tanya" className="text-[13px] font-medium text-stone-600 transition hover:text-claude-600 dark:text-stone-400 dark:hover:text-claude-300">
                                Tanya jawab
                            </a>
                        </nav>

                        <div className="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                onClick={toggleTheme}
                                aria-label={dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-stone-200 bg-white/80 text-stone-500 shadow-sm transition hover:border-claude-400/50 hover:text-claude-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 dark:border-[#413b35] dark:bg-[#34302b]/90 dark:text-stone-300"
                            >
                                {dark ? <SunIcon className="h-4 w-4" /> : <MoonIcon className="h-4 w-4" />}
                            </button>
                            <Link
                                href={route('login')}
                                className="rounded-xl bg-gradient-to-b from-claude-500 to-claude-600 px-4 py-2 text-[13px] font-bold text-white shadow-md shadow-claude-500/25 transition hover:from-claude-500 hover:to-claude-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 focus-visible:ring-offset-2 active:scale-[0.99] dark:focus-visible:ring-offset-[#242019]"
                            >
                                Masuk
                            </Link>
                        </div>
                    </div>
                </header>

                {/* ============ HERO ============ */}
                <section className="relative z-10 mx-auto grid max-w-6xl gap-12 px-5 pb-20 pt-12 sm:px-6 lg:grid-cols-[1.05fr,0.95fr] lg:items-center lg:gap-16 lg:pt-20">
                    <div>
                        <Eyebrow arab="التحفيظ" latin={duaModul ? 'Tahfizh & keuangan pesantren' : tampilKeuangan ? 'Keuangan pesantren' : 'Tahfizh pesantren'} />

                        <h1
                            className="mt-5 text-[2.1rem] font-semibold leading-[1.12] tracking-tight text-stone-900 dark:text-stone-50 sm:text-[2.9rem]"
                            style={{ fontFamily: "'Fraunces', serif" }}
                        >
                            {judulHero}
                        </h1>

                        <p className="mt-5 max-w-lg text-[15px] leading-[1.75] text-stone-600 dark:text-stone-400">{subHero}</p>

                        <div className="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href={KONTAK_WA}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-center gap-2 rounded-xl bg-gradient-to-b from-claude-500 to-claude-600 px-5 py-3 text-[13px] font-bold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-500 hover:to-claude-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 focus-visible:ring-offset-2 active:scale-[0.99] dark:focus-visible:ring-offset-[#242019]"
                            >
                                Minta demo
                                <ArrowIcon className="h-4 w-4" />
                            </a>
                            <a
                                href="#modul"
                                className="rounded-xl border border-stone-300 bg-white/70 px-5 py-3 text-[13px] font-semibold text-stone-700 backdrop-blur transition hover:border-claude-400/60 hover:text-claude-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 dark:border-[#443e37] dark:bg-[#34302b]/70 dark:text-stone-300"
                            >
                                Lihat isi sistemnya
                            </a>
                        </div>

                        <p className="mt-6 max-w-md text-[12px] leading-relaxed text-stone-500 dark:text-stone-500">
                            Dipasang di domain pesantren Anda sendiri. Modul bisa dibeli terpisah.
                        </p>
                    </div>

                    {/* Signature: kartu mutaba'ah dengan peta 30 juz */}
                    <KartuMutabaah santri={santri} reduced={reduced} idx={idx} />
                </section>

                {/* ============ MODUL ============ */}
                <div id="modul" className="scroll-mt-20" />

                {tampilTahfizh && (
                    <SeksiModul
                        arab="التحفيظ"
                        label="Modul Tahfizh"
                        judul="Dari setoran di kelas sampai rapor siap cetak"
                        alurJudul="Alur setoran harian"
                        alur={ALUR_TAHFIZH}
                        fitur={FITUR_TAHFIZH}
                        reduced={reduced}
                    />
                )}

                {tampilKeuangan && (
                    <SeksiModul
                        arab="المالية"
                        label="Modul Keuangan"
                        judul="Setiap pengeluaran punya jejak persetujuan"
                        alurJudul="Alur pengeluaran"
                        alur={ALUR_KEUANGAN}
                        fitur={FITUR_KEUANGAN}
                        reduced={reduced}
                        garisAtas
                    />
                )}

                {/* ============ PERAN ============ */}
                <section id="peran" className="relative z-10 scroll-mt-20 border-t border-stone-200/70 dark:border-[#3a352f]">
                    <div className="mx-auto max-w-6xl px-5 py-20 sm:px-6">
                        <Eyebrow arab="الصلاحيات" latin="Hak akses" />
                        <h2
                            className="mt-3 max-w-xl text-[1.7rem] font-semibold leading-tight text-stone-900 dark:text-stone-50 sm:text-[2rem]"
                            style={{ fontFamily: "'Fraunces', serif" }}
                        >
                            Setiap orang hanya melihat yang menjadi tanggung jawabnya
                        </h2>
                        <p className="mt-3 max-w-lg text-[14px] leading-relaxed text-stone-600 dark:text-stone-400">
                            Guru tidak melihat data keuangan. Bendahara tidak mengubah nilai setoran. Satu akun bisa memegang lebih
                            dari satu peran bila memang dirangkap.
                        </p>

                        <div className="mt-10 grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-4">
                            {PERAN.map((p, i) => (
                                <Reveal key={p.nama} delay={i * 60} reduced={reduced}>
                                    <div className="border-t border-stone-300/70 pt-4 dark:border-[#443e37]">
                                        <p className="text-[13px] font-bold text-stone-800 dark:text-stone-100">{p.nama}</p>
                                        <p className="mt-1.5 text-[13px] leading-relaxed text-stone-600 dark:text-stone-400">{p.teks}</p>
                                    </div>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ============ PAKET ============ */}
                <section id="paket" className="relative z-10 scroll-mt-20 border-t border-stone-200/70 dark:border-[#3a352f]">
                    <div className="mx-auto max-w-6xl px-5 py-20 sm:px-6">
                        <Eyebrow arab="الباقات" latin="Paket" />
                        <h2
                            className="mt-3 max-w-xl text-[1.7rem] font-semibold leading-tight text-stone-900 dark:text-stone-50 sm:text-[2rem]"
                            style={{ fontFamily: "'Fraunces', serif" }}
                        >
                            Ambil modul yang memang dibutuhkan
                        </h2>

                        <div className={`mt-10 grid gap-5 ${duaModul ? 'lg:grid-cols-3' : 'sm:grid-cols-2'}`}>
                            {tampilTahfizh && (
                                <KartuPaket
                                    nama="Modul Tahfizh"
                                    ringkas="Untuk pesantren yang lebih dulu ingin merapikan pencatatan hafalan."
                                    isi={[
                                        'Setoran, presensi, dan rapor PDF',
                                        'Panel admin, guru, dan santri',
                                        'Kurikulum digital Aisar 5 jilid',
                                        'Impor data santri dari Excel',
                                    ]}
                                />
                            )}
                            {tampilKeuangan && (
                                <KartuPaket
                                    nama="Modul Keuangan"
                                    ringkas="Untuk yayasan yang butuh jejak dana yang bisa dipertanggungjawabkan."
                                    isi={[
                                        'RKAS, tagihan, dan pembayaran',
                                        'Penggajian dan slip gaji',
                                        'Persetujuan berjenjang',
                                        'Buku kas dan laporan tunggakan',
                                    ]}
                                />
                            )}
                            {duaModul && (
                                <KartuPaket
                                    nama="Paket lengkap"
                                    ringkas="Dua modul dalam satu sistem, satu akun bisa berpindah panel tanpa logout."
                                    isi={[
                                        'Seluruh isi kedua modul',
                                        'Perpindahan panel tanpa logout',
                                        'Data guru dan santri dipakai bersama',
                                        'Pendampingan pemasangan awal',
                                    ]}
                                    utama
                                />
                            )}
                        </div>

                        <p className="mt-6 text-[12px] text-stone-500 dark:text-stone-500">
                            Harga menyesuaikan jumlah santri dan kebutuhan penyesuaian. Hubungi kami untuk penawaran.
                        </p>
                    </div>
                </section>

                {/* ============ TANYA JAWAB ============ */}
                <section id="tanya" className="relative z-10 scroll-mt-20 border-t border-stone-200/70 dark:border-[#3a352f]">
                    <div className="mx-auto max-w-3xl px-5 py-20 sm:px-6">
                        <Eyebrow arab="أسئلة" latin="Tanya jawab" />
                        <h2
                            className="mt-3 text-[1.7rem] font-semibold leading-tight text-stone-900 dark:text-stone-50 sm:text-[2rem]"
                            style={{ fontFamily: "'Fraunces', serif" }}
                        >
                            Yang biasanya ditanyakan pengurus
                        </h2>

                        <div className="mt-8 divide-y divide-stone-300/70 border-y border-stone-300/70 dark:divide-[#443e37] dark:border-[#443e37]">
                            {TANYA_JAWAB.map((t) => (
                                <details key={t.tanya} className="group py-4">
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-[14px] font-semibold text-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 dark:text-stone-100">
                                        {t.tanya}
                                        <span className="shrink-0 text-claude-600 transition-transform duration-300 group-open:rotate-45 dark:text-claude-300">
                                            <Svg className="h-4 w-4">
                                                <path d="M12 5v14M5 12h14" />
                                            </Svg>
                                        </span>
                                    </summary>
                                    <p className="mt-3 pr-8 text-[13px] leading-relaxed text-stone-600 dark:text-stone-400">{t.jawab}</p>
                                </details>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ============ CTA ============ */}
                <section className="relative z-10 mx-auto max-w-4xl px-5 pb-20 sm:px-6">
                    <div className="overflow-hidden rounded-3xl border border-stone-200/80 bg-white/80 px-7 py-12 text-center shadow-[0_24px_60px_-30px_rgba(120,80,60,0.3)] backdrop-blur-sm dark:border-[#443e37] dark:bg-[#34302b]/85 sm:px-10">
                        <p className="text-[22px] leading-none text-claude-600/70 dark:text-claude-300/60" style={{ fontFamily: "'Amiri', serif" }} lang="ar" dir="rtl">
                            وَقُل رَّبِّ زِدْنِى عِلْمًا
                        </p>
                        <h2
                            className="mt-5 text-[1.7rem] font-semibold leading-tight text-stone-900 dark:text-stone-50 sm:text-[2rem]"
                            style={{ fontFamily: "'Fraunces', serif" }}
                        >
                            Coba dulu dengan data pesantren Anda
                        </h2>
                        <p className="mx-auto mt-3 max-w-md text-[14px] leading-relaxed text-stone-600 dark:text-stone-400">
                            Kirim satu kelas contoh, kami siapkan demo yang sudah terisi data Anda sendiri — bukan data karangan.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <a
                                href={KONTAK_WA}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="rounded-xl bg-gradient-to-b from-claude-500 to-claude-600 px-5 py-3 text-[13px] font-bold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-500 hover:to-claude-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#34302b]"
                            >
                                {KONTAK_LABEL}
                            </a>
                            <Link
                                href={route('login')}
                                className="rounded-xl border border-stone-300 px-5 py-3 text-[13px] font-semibold text-stone-700 transition hover:border-claude-400/60 hover:text-claude-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 dark:border-[#443e37] dark:text-stone-300"
                            >
                                Masuk ke sistem
                            </Link>
                        </div>
                    </div>
                </section>

                {/* ============ FOOTER ============ */}
                <footer className="relative z-10 border-t border-stone-200/70 dark:border-[#3a352f]">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-5 py-8 text-center sm:flex-row sm:px-6 sm:text-left">
                        <p className="text-[11px] uppercase tracking-wide text-stone-400 dark:text-stone-500">
                            © {new Date().getFullYear()} Sistem Management Tahfizh
                        </p>
                        <p className="text-[11px] text-stone-400 dark:text-stone-500">
                            Dibangun untuk pesantren, dipasang di server Anda sendiri.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

/* =====================================================================
 * SIGNATURE — Kartu Mutaba'ah
 * Peta 30 juz disusun RTL (juz 1 di kanan atas) meniru arah baca mushaf,
 * dan nomornya memakai angka Arab-Timur seperti di kartu setoran fisik.
 * ===================================================================== */
function KartuMutabaah({ santri, reduced, idx }) {
    const juzSelesai = santri.juz;

    return (
        <div className="relative mx-auto w-full max-w-md">
            <div className="pointer-events-none absolute -inset-5 rounded-[2.25rem] bg-gradient-to-br from-emerald-400/10 via-transparent to-claude-400/12 blur-2xl" />

            <div className="relative overflow-hidden rounded-3xl border border-stone-200/80 bg-white/92 shadow-[0_28px_70px_-28px_rgba(120,80,60,0.32)] backdrop-blur-sm dark:border-[#443e37] dark:bg-[#34302b]/95">
                {/* Kepala kartu */}
                <div className="flex items-center justify-between gap-3 border-b border-stone-200/80 px-6 py-4 dark:border-[#443e37]">
                    <div>
                        <p className="text-[10px] font-semibold uppercase tracking-[0.14em] text-stone-400 dark:text-stone-500">
                            Kartu Mutaba&rsquo;ah
                        </p>
                        <p
                            className="mt-1.5 truncate text-[15px] font-bold text-stone-800 dark:text-stone-100"
                            style={{ transition: reduced ? 'none' : 'opacity .4s ease' }}
                            key={`n-${idx}`}
                        >
                            {santri.nama}
                        </p>
                    </div>
                    <span className="shrink-0 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-semibold text-stone-500 dark:bg-[#3d3833] dark:text-stone-300">
                        {santri.kelas}
                    </span>
                </div>

                {/* Peta 30 juz */}
                <div className="px-6 py-5">
                    <div className="flex items-baseline justify-between">
                        <p className="text-[10px] font-semibold uppercase tracking-[0.14em] text-stone-400 dark:text-stone-500">
                            Peta 30 Juz
                        </p>
                        <p className="text-[12px] font-semibold text-stone-600 dark:text-stone-300" style={{ fontFamily: "'IBM Plex Mono', monospace" }}>
                            {juzSelesai}/30 juz
                        </p>
                    </div>

                    <div dir="rtl" className="mt-3 grid grid-cols-10 gap-1.5">
                        {Array.from({ length: 30 }, (_, i) => i + 1).map((juz) => {
                            const selesai = juz <= juzSelesai;
                            const berjalan = juz === juzSelesai + 1;
                            return (
                                <div
                                    key={juz}
                                    title={`Juz ${juz}`}
                                    className={`flex aspect-square items-center justify-center rounded-[5px] text-[10px] leading-none ${
                                        selesai
                                            ? 'bg-gradient-to-br from-emerald-500 to-teal-500 text-white/95 shadow-sm'
                                            : berjalan
                                              ? 'bg-emerald-500/12 text-emerald-700 ring-1 ring-inset ring-emerald-500/45 dark:text-emerald-300'
                                              : 'bg-stone-100 text-stone-400 dark:bg-[#3d3833] dark:text-stone-600'
                                    }`}
                                    style={{
                                        fontFamily: "'Amiri', serif",
                                        transition: reduced ? 'none' : 'background-color .5s ease, color .5s ease',
                                        transitionDelay: reduced ? '0ms' : `${juz * 16}ms`,
                                    }}
                                >
                                    {keArab(juz)}
                                </div>
                            );
                        })}
                    </div>

                    <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[10px] text-stone-500 dark:text-stone-500">
                        <span className="flex items-center gap-1.5">
                            <i className="h-2.5 w-2.5 rounded-[3px] bg-gradient-to-br from-emerald-500 to-teal-500" />
                            Selesai
                        </span>
                        <span className="flex items-center gap-1.5">
                            <i className="h-2.5 w-2.5 rounded-[3px] ring-1 ring-inset ring-emerald-500/45" />
                            Sedang dihafal
                        </span>
                        <span className="flex items-center gap-1.5">
                            <i className="h-2.5 w-2.5 rounded-[3px] bg-stone-200 dark:bg-[#3d3833]" />
                            Belum
                        </span>
                    </div>
                </div>

                {/* Setoran terakhir */}
                <div className="border-t border-stone-200/80 bg-stone-50/70 px-6 py-4 dark:border-[#443e37] dark:bg-[#2f2b27]/60">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className="text-[10px] font-semibold uppercase tracking-[0.14em] text-stone-400 dark:text-stone-500">
                                Setoran terakhir
                            </p>
                            <p
                                className="mt-1.5 truncate text-[13px] font-semibold text-stone-700 dark:text-stone-200"
                                style={{ fontFamily: "'IBM Plex Mono', monospace" }}
                                key={`s-${idx}`}
                            >
                                {santri.potongan}
                            </p>
                            <p className="mt-0.5 text-[11px] text-stone-500 dark:text-stone-500">
                                Juz {santri.juz} · halaman {santri.halaman}
                            </p>
                        </div>
                        <span className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ${WARNA_JENIS[santri.jenis]}`}>
                            {santri.jenis}
                        </span>
                    </div>
                </div>
            </div>

            <p className="mt-3 text-center text-[11px] text-stone-400 dark:text-stone-500">
                Tampilan contoh — datanya akan mengikuti santri di pesantren Anda.
            </p>
        </div>
    );
}

/* =====================================================================
 * BAGIAN MODUL — alur (urutan sungguhan) + daftar fitur
 * ===================================================================== */
function SeksiModul({ arab, label, judul, alurJudul, alur, fitur, reduced, garisAtas = false }) {
    return (
        <section className={`relative z-10 ${garisAtas ? 'border-t border-stone-200/70 dark:border-[#3a352f]' : ''}`}>
            <div className="mx-auto max-w-6xl px-5 py-20 sm:px-6">
                <Eyebrow arab={arab} latin={label} />
                <h2
                    className="mt-3 max-w-xl text-[1.7rem] font-semibold leading-tight text-stone-900 dark:text-stone-50 sm:text-[2rem]"
                    style={{ fontFamily: "'Fraunces', serif" }}
                >
                    {judul}
                </h2>

                {/* Alur: penomoran dipakai karena isinya memang berurutan */}
                <div className="mt-10">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-stone-400 dark:text-stone-500">{alurJudul}</p>
                    <ol className="mt-4 grid gap-px overflow-hidden rounded-2xl bg-stone-200/70 sm:grid-cols-2 lg:grid-cols-4 dark:bg-[#443e37]">
                        {alur.map((a, i) => (
                            <li key={a.judul} className="bg-[#f3eee6] p-5 dark:bg-[#302c27]">
                                <span
                                    className="text-[12px] font-semibold text-claude-600 dark:text-claude-300"
                                    style={{ fontFamily: "'IBM Plex Mono', monospace" }}
                                >
                                    {String(i + 1).padStart(2, '0')}
                                </span>
                                <p className="mt-2 text-[13px] font-bold text-stone-800 dark:text-stone-100">{a.judul}</p>
                                <p className="mt-1.5 text-[12.5px] leading-relaxed text-stone-600 dark:text-stone-400">{a.teks}</p>
                            </li>
                        ))}
                    </ol>
                </div>

                {/* Fitur */}
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {fitur.map((f, i) => (
                        <Reveal key={f.judul} delay={i * 60} reduced={reduced}>
                            <div className="group h-full rounded-2xl border border-stone-200/80 bg-white/70 p-5 backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-claude-400/50 hover:shadow-[0_14px_30px_-18px_rgba(120,80,60,0.35)] dark:border-[#443e37] dark:bg-[#34302b]/70">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-stone-100 text-stone-600 transition group-hover:bg-claude-500/12 group-hover:text-claude-600 dark:bg-[#3d3833] dark:text-stone-300 dark:group-hover:text-claude-300">
                                    <f.icon className="h-5 w-5" />
                                </div>
                                <p className="mt-4 text-[14px] font-bold text-stone-800 dark:text-stone-100">{f.judul}</p>
                                <p className="mt-1.5 text-[13px] leading-relaxed text-stone-600 dark:text-stone-400">{f.teks}</p>
                            </div>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

/* =====================================================================
 * KOMPONEN KECIL
 * ===================================================================== */
function Eyebrow({ arab, latin }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="text-[17px] leading-none text-claude-600/75 dark:text-claude-300/70" style={{ fontFamily: "'Amiri', serif" }} lang="ar" dir="rtl">
                {arab}
            </span>
            <span className="h-3 w-px bg-stone-300 dark:bg-[#4d463e]" />
            <span className="text-[11px] font-semibold uppercase tracking-[0.14em] text-stone-500 dark:text-stone-400">{latin}</span>
        </div>
    );
}

function KartuPaket({ nama, ringkas, isi, utama = false }) {
    return (
        <div
            className={`flex h-full flex-col rounded-2xl border p-6 ${
                utama
                    ? 'border-claude-500/40 bg-white/90 shadow-[0_20px_50px_-28px_rgba(217,119,87,0.55)] dark:border-claude-500/35 dark:bg-[#34302b]/90'
                    : 'border-stone-200/80 bg-white/70 dark:border-[#443e37] dark:bg-[#34302b]/70'
            }`}
        >
            <div className="flex items-center justify-between gap-2">
                <p className="text-[15px] font-bold text-stone-800 dark:text-stone-100">{nama}</p>
                {utama && (
                    <span className="rounded-full bg-claude-500/12 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-claude-600 dark:text-claude-300">
                        Paling lengkap
                    </span>
                )}
            </div>
            <p className="mt-2 text-[13px] leading-relaxed text-stone-600 dark:text-stone-400">{ringkas}</p>

            <ul className="mt-5 flex-1 space-y-2.5">
                {isi.map((b) => (
                    <li key={b} className="flex items-start gap-2.5 text-[13px] leading-relaxed text-stone-700 dark:text-stone-300">
                        <span className="mt-0.5 shrink-0 text-emerald-600 dark:text-emerald-400">
                            <CheckIcon className="h-4 w-4" />
                        </span>
                        {b}
                    </li>
                ))}
            </ul>

            <a
                href={KONTAK_WA}
                target="_blank"
                rel="noopener noreferrer"
                className={`mt-6 block rounded-xl px-4 py-2.5 text-center text-[13px] font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-claude-500 ${
                    utama
                        ? 'bg-gradient-to-b from-claude-500 to-claude-600 text-white shadow-md shadow-claude-500/25 hover:from-claude-500 hover:to-claude-700'
                        : 'border border-stone-300 text-stone-700 hover:border-claude-400/60 hover:text-claude-600 dark:border-[#443e37] dark:text-stone-300'
                }`}
            >
                Tanya harga
            </a>
        </div>
    );
}

function Reveal({ children, delay = 0, reduced }) {
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
            className="h-full"
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
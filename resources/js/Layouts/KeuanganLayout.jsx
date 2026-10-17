import { useState, useEffect, useRef } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import LogoutButton from '@/Components/LogoutButton';
import AppLogo from '@/Components/AppLogo';

/* ============================================================
 * NOTIFIKASI
 *
 * Komponennya disalin dari halaman Santri (Guru/Santri/Index.jsx) supaya
 * seluruh aplikasi memberi kabar dengan cara yang sama — pengguna tidak
 * perlu belajar dua bahasa isyarat untuk dua panel.
 *
 * Dipasang di layout, bukan di tiap halaman: sebelum ini SELURUH panel
 * keuangan menyimpan data dalam diam. Controllernya mengirim
 * ->with('success', ...) tapi tidak ada satu pun yang menampilkannya.
 * ============================================================ */
const NOTIF_DURATION = 10000;

function Toast({ notif, onClose, duration = NOTIF_DURATION }) {
    if (! notif) return null;
    const success = notif.type === 'success';

    return (
        <>
            {/* key={notif.id} memaksa elemen dibuat ulang, sehingga animasi
                masuk dan batang waktunya mengulang dari awal untuk tiap
                notifikasi — tanpa ini notifikasi kedua muncul tanpa gerak. */}
            <div
                key={notif.id}
                className="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 z-[300] overflow-hidden rounded-2xl border border-stone-200 dark:border-coal-700 bg-white/95 dark:bg-coal-800/95 shadow-2xl backdrop-blur-md"
                style={{ animation: 'toastIn .3s cubic-bezier(.21,1.02,.73,1)' }}
                role="alert"
            >
                <div className="flex items-start gap-3 p-3.5">
                    <span className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full ${success ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300'}`}>
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
                            <path d={success
                                ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                                : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'} />
                        </svg>
                    </span>

                    <div className="min-w-0 flex-1 pt-0.5">
                        <p className={`text-xs font-bold ${success ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'}`}>
                            {success ? 'Berhasil' : 'Gagal'}
                        </p>
                        <p className="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-300"
                            dangerouslySetInnerHTML={{ __html: notif.message }} />
                    </div>

                    <button onClick={onClose} aria-label="Tutup notifikasi"
                        className="flex-shrink-0 rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-600 dark:hover:bg-coal-700 dark:hover:text-stone-200 transition">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Batang waktu: pengguna bisa melihat sisa waktu baca,
                    bukan menebak kapan notifikasinya hilang. */}
                <div className="h-1 w-full bg-stone-200 dark:bg-coal-700">
                    <div className={`h-full ${success ? 'bg-emerald-500' : 'bg-red-500'}`}
                        style={{ animation: `toastBar ${duration}ms linear forwards` }} />
                </div>
            </div>

            <style>{`
                @keyframes toastIn { from { opacity: 0; transform: translateY(-14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
                @keyframes toastBar { from { width: 100%; } to { width: 0%; } }
            `}</style>
        </>
    );
}

function Notifikasi() {
    const [notif, setNotif] = useState(null);
    const timerRef = useRef(null);
    const flashAwal = usePage().props?.flash;

    const showToast = (message, type = 'success') => {
        setNotif({ message, type, id: Date.now() });
        clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => setNotif(null), NOTIF_DURATION);
    };

    useEffect(() => {
        // Pesan yang sudah ada saat halaman pertama dimuat.
        if (flashAwal?.success) showToast(flashAwal.success, 'success');
        else if (flashAwal?.error) showToast(flashAwal.error, 'error');

        // Dibaca dari event Inertia, bukan dari useEffect atas props:
        // membatalkan dua tagihan beruntun menghasilkan teks yang persis
        // sama, dan React tidak akan melihat perubahan apa pun — notifikasi
        // keduanya tidak pernah tampil.
        const off = router.on('success', (e) => {
            const f = e.detail.page.props.flash ?? {};
            if (f.success) showToast(f.success, 'success');
            else if (f.error) showToast(f.error, 'error');
        });

        return () => {
            off();
            clearTimeout(timerRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return <Toast notif={notif} onClose={() => setNotif(null)} duration={NOTIF_DURATION} />;
}

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
    'ketua-yayasan': 'Ketua Yayasan',
    'hrd': 'HRD',
};

// Urutan = prioritas label bila satu akun memegang beberapa peran sekaligus.
// super-admin didahulukan supaya tidak pernah tertutup label peran lain.
const ROLE_PRIORITAS = [
    'super-admin',
    'ketua-yayasan',
    'pihak-yayasan',
    'kepala-sekolah',
    'hrd',
    'bendahara',
];

const I = {
    // --- ikon grup ---
    dash: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    pimpinan: 'M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z M8 16v-3 M12 16v-6 M16 16v-4',
    master: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    masuk: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    keluar: 'M17 13l-5 5m0 0l-5-5m5 5V6',
    gaji: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    laporan: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    setting: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    profil: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',

    // --- ikon sub-menu: master data ---
    kelas: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    siswa: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    jabatan: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    pegawai: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    rekening: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    tarif: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
    rkasTemplate: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',

    // --- ikon sub-menu: pemasukan ---
    tagihan: 'M9 12h6m-6 4h6M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16l-3.5-2-3.5 2-3.5-2L5 21z',
    pembayaran: 'M3 10h18M3 10a2 2 0 012-2h14a2 2 0 012 2M3 10v8a2 2 0 002 2h14a2 2 0 002-2v-8m-4 4h.01',

    // --- ikon sub-menu: anggaran ---
    rkas: 'M9 7h6m-6 4h.01M12 11h.01M15 11h.01M9 15h.01M12 15h.01M15 15h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    pengajuan: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',

    // --- ikon sub-menu: penggajian ---
    absensi: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    komponen: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
    slipGaji: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',

    // --- ikon sub-menu: laporan ---
    buku: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    tunggakan: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    realisasi: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',

    // --- ikon sub-menu: pengaturan ---
    role: 'M9 12l2 2 4-4M12 3l7 4v5c0 4.418-2.99 8.166-7 9-4.01-.834-7-4.582-7-9V7l7-4z',
    userMgmt: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    // Pena, bukan coretan tanda tangan. Coretan terbaca bagus pada 32px
    // tapi luruh jadi gumpalan di 16px — dan dalam mode sidebar menciut,
    // ikon adalah satu-satunya petunjuk karena labelnya hilang.
    ttd: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
};

export default function KeuanganLayout({ children }) {
    const { auth, modulUser } = usePage().props;
    const user = auth?.user;
    const permissions = auth?.permissions || [];
    const can = (p) => permissions.includes(p);

    // Tombol pindah panel hanya untuk akun yang memang punya akses ke raport.
    const bisaKeRaport = modulUser?.tersedia?.includes('raport');

    /* ==========================================================
     * LABEL PERAN
     * Dibaca dari role yang benar-benar dimiliki akun, BUKAN ditebak
     * dari permission. Tebakan lewat permission selalu salah untuk akun
     * yang memegang beberapa peran: super-admin punya SEMUA permission,
     * jadi ia selalu tertangkap cabang pertama yang cocok — itulah sebab
     * super-admin sempat berlabel "Kepala Sekolah".
     * Rangkaian permission di bawah hanya cadangan bila daftar role
     * belum dibagikan ke frontend lewat HandleInertiaRequests.
     * ========================================================== */
    const daftarRole = auth?.roles
        || user?.roles?.map((r) => (typeof r === 'string' ? r : r.name))
        || [];

    // Kolom users.role ikut diperiksa: super-admin menyimpannya di sana.
    const punyaRole = (nama) => user?.role === nama || daftarRole.includes(nama);

    const roleAktif = ROLE_PRIORITAS.find(punyaRole);

    // Rute Pengaturan Sistem digembok 'role:super-admin', bukan permission —
    // dan tabel permissions memang tidak punya baris 'sistem.*'. Kalau menunya
    // ikut disaring permission, ia hilang selamanya padahal halamannya sendiri
    // terbuka. Syarat menu harus sama dengan syarat rutenya.
    const isSuperAdmin = punyaRole('super-admin');

    const roleLabel =
        roleAktif ? ROLE_LABEL[roleAktif]
        // Dua nama diterima di tiap baris: penamaan seeder saat ini dan
        // penamaan lama, supaya instalasi lama tidak salah label.
        : can('sistem.role.kelola') || can('sistem.kelola_role') ? ROLE_LABEL['super-admin']
        : can('pengeluaran.approve_final') || can('pengeluaran.approve_disetujui') ? ROLE_LABEL['ketua-yayasan']
        : can('pengeluaran.approve_diperiksa') ? ROLE_LABEL['kepala-sekolah']
        : can('penggajian.generate_batch')     ? ROLE_LABEL['hrd']
        : can('tagihan.buat_batch')            ? ROLE_LABEL['bendahara']
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
                { name: 'keuangan.master-data.kelas.index', label: 'Data Kelas', perm: 'master.kelas.lihat', icon: I.kelas },
                { name: 'keuangan.master-data.siswa', label: 'Data Siswa', perm: 'master.siswa.lihat', icon: I.siswa },
                { name: 'keuangan.master-data.jabatan', label: 'Data Jabatan', perm: 'master.jabatan.lihat', icon: I.jabatan },
                { name: 'keuangan.master-data.pegawai', label: 'Data Pegawai', perm: 'master.pegawai.lihat', icon: I.pegawai },
                { name: 'keuangan.master-data.rekening', label: 'Data Rekening', perm: 'master.rekening.lihat', icon: I.rekening },
                { name: 'keuangan.master-data.tarif', label: 'Data Tarif', perm: 'master.tarif.lihat', icon: I.tarif },
                { name: 'keuangan.master-data.rkas_template.index', label: 'Jenis RKAS', perm: 'master.rkas.lihat', icon: I.rkasTemplate },
            ],
        },
        {
            key: 'pemasukan', label: 'Pemasukan', icon: I.masuk, prefix: 'keuangan.pemasukan.',
            items: [
                { name: 'keuangan.pemasukan.tagihan.index', label: 'Kelola Tagihan', perm: 'tagihan.lihat', icon: I.tagihan },
                { name: 'keuangan.pemasukan.pembayaran.index', label: 'Pembayaran', perm: 'pembayaran.lihat', icon: I.pembayaran },
            ],
        },
        {
            key: 'pengeluaran', label: 'Anggaran', icon: I.keluar, prefix: 'keuangan.pengeluaran.',
            items: [
                { name: 'keuangan.pengeluaran.rkas', label: 'Kelola RKAS', perm: 'rkas.lihat', icon: I.rkas },
                { name: 'keuangan.pengeluaran.pengajuan', label: 'Kelola Pengeluaran', perm: 'pengeluaran.lihat', icon: I.pengajuan },
            ],
        },
        {
            key: 'penggajian', label: 'Penggajian', icon: I.gaji, prefix: 'keuangan.penggajian.',
            items: [
                { name: 'keuangan.penggajian.absensi', label: 'Data Absensi', perm: 'absensi.lihat', icon: I.absensi },
                { name: 'keuangan.penggajian.komponen', label: 'Komponen Gaji', perm: 'tunjangan.lihat', icon: I.komponen },
                { name: 'keuangan.penggajian.data', label: 'Kelola Gaji & Slip', perm: 'penggajian.lihat', icon: I.slipGaji },
            ],
        },
        {
            key: 'laporan', label: 'Laporan', icon: I.laporan, prefix: 'keuangan.laporan.',
            items: [
                { name: 'keuangan.laporan.buku_kas_umum', label: 'Buku Kas Umum', perm: 'laporan.buku_kas', icon: I.buku },
                { name: 'keuangan.laporan.tunggakan', label: 'Laporan Tunggakan', perm: 'laporan.tunggakan', icon: I.tunggakan },
                { name: 'keuangan.laporan.realisasi_rkas', label: 'Realisasi RKAS', perm: 'laporan.rkas', icon: I.realisasi },
            ],
        },
        {
            key: 'pengaturan', label: 'Pengaturan Sistem', icon: I.setting, prefix: 'keuangan.pengaturan.',
            items: [
                // perm berupa array = cukup punya salah satu. Dua penamaan
                // diterima: 'sistem.role.kelola' (seeder saat ini) dan
                // 'sistem.kelola_role' (penamaan lama). Tanpa ini menu
                // Pengaturan hilang di instalasi yang memakai salah satunya.
                { name: 'keuangan.pengaturan.role.index', label: 'Management Role', perm: ['sistem.role.kelola', 'sistem.kelola_role'], icon: I.role },
                { name: 'keuangan.pengaturan.user.index', label: 'Management User', perm: ['sistem.user.kelola', 'sistem.kelola_user'], icon: I.userMgmt },
                { name: 'keuangan.pengaturan.identitas.index', label: 'Identitas & Tanda Tangan', perm: [], icon: I.ttd },
            ],
        },
    ]
        .map((g) => ({
            ...g,
            items: g.items.filter((i) => {
                if (! has(i.name)) return false;

                // Grup pengaturan: super-admin selalu boleh, apa pun isi
                // tabel permissions. Lihat catatan di atas.
                if (g.key === 'pengaturan') return isSuperAdmin;

                const daftarPerm = Array.isArray(i.perm) ? i.perm : [i.perm];
                return daftarPerm.some(can);
            }),
        }))
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

                {/* Tautan tersendiri, bukan di dalam grup Laporan. Pembacanya
                    kemungkinan hanya memegang permission ini saja — menaruhnya
                    di balik accordion membuat satu-satunya halaman yang boleh
                    ia buka justru harus dicari dulu. */}
                {has('keuangan.pimpinan') && can('laporan.pimpinan') && (
                    <Link href={route('keuangan.pimpinan')} onClick={closeNav}
                        title={isMini ? 'Ringkasan Pimpinan' : undefined}
                        className={`group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${isMini ? 'justify-center' : ''} ${
                            isCurrent('keuangan.pimpinan')
                                ? 'bg-gradient-to-r from-claude-500 to-claude-600 text-white shadow-lg shadow-claude-500/25'
                                : 'text-stone-500 hover:bg-cream-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-white'
                        }`}>
                        <svg className={`h-[18px] w-[18px] flex-shrink-0 ${isCurrent('keuangan.pimpinan') ? 'text-white' : 'text-stone-400 dark:text-stone-500'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={I.pimpinan} />
                        </svg>
                        {!isMini && <span className="truncate">Ringkasan Pimpinan</span>}
                    </Link>
                )}

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
                                <div className="mt-1 space-y-0.5 pl-5 pr-1">
                                    {g.items.map((it) => (
                                        <Link key={it.name} href={route(it.name)} onClick={closeNav}
                                            className={`flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition ${
                                                isCurrent(it.name)
                                                    ? 'bg-claude-500/10 font-medium text-claude-600 dark:text-claude-300'
                                                    : 'text-stone-500 hover:bg-cream-200 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-coal-600/60 dark:hover:text-stone-200'
                                            }`}>
                                            <svg className={`h-4 w-4 flex-shrink-0 ${isCurrent(it.name) ? 'text-claude-500 dark:text-claude-300' : 'text-stone-400 dark:text-stone-500'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d={it.icon} />
                                            </svg>
                                            <span className="truncate">{it.label}</span>
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
                        {bisaKeRaport && (
                            <a href={route('pindah-modul', 'raport')} title="Buka Panel Tahfizh"
                                className="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-cream-200 hover:text-claude-600 dark:text-stone-500 dark:hover:bg-coal-600 dark:hover:text-claude-300">
                                <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </a>
                        )}
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
                        {/* Pindah panel tanpa logout — pilihan tersimpan di session.
                            Pakai <a>, bukan <Link>: route ini me-redirect ke
                            /dashboard, bukan mengembalikan respons Inertia. */}
                        {bisaKeRaport && (
                            <a href={route('pindah-modul', 'raport')}
                                className="flex items-center gap-2.5 rounded-xl bg-cream-200/70 px-3 py-2.5 text-sm font-medium text-stone-600 transition hover:bg-cream-300 dark:bg-coal-600/50 dark:text-stone-300 dark:hover:bg-coal-600">
                                <svg className="h-[18px] w-[18px] flex-shrink-0 text-claude-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span className="truncate">Buka Panel Tahfizh</span>
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
            <Notifikasi />

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
import { useEffect, useState } from 'react';

/**
 * Tombol pasang aplikasi.
 *
 * Chrome/Edge memberi event `beforeinstallprompt` yang bisa ditunda dan
 * dipicu lewat tombol sendiri. Safari iOS tidak punya itu sama sekali,
 * jadi di sana yang ditampilkan hanya petunjuk manual — tanpa petunjuk,
 * hampir tidak ada wali yang menemukan menu "Add to Home Screen".
 */

// iPad sejak iPadOS 13 mengaku sebagai "Macintosh" di userAgent, jadi
// pengecekan /ipad/ saja tidak menangkapnya. Mac sungguhan tidak punya
// layar sentuh, sehingga maxTouchPoints > 1 membedakan keduanya.
const deteksiIos = () => {
    const ua = window.navigator.userAgent;
    return /iphone|ipad|ipod/i.test(ua)
        || (/Macintosh/.test(ua) && window.navigator.maxTouchPoints > 1);
};

export default function TombolInstall({ mini = false }) {
    const [prompt, setPrompt] = useState(null);
    const [iosPetunjuk, setIosPetunjuk] = useState(false);
    const [tampil, setTampil] = useState(false);

    useEffect(() => {
        // Sudah dipasang → tidak perlu ditawarkan lagi.
        const terpasang = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        if (terpasang) return;

        if (deteksiIos()) {
            setIosPetunjuk(true);
            setTampil(true);
            return;
        }

        const onPrompt = (e) => {
            e.preventDefault();
            setPrompt(e);
            setTampil(true);
        };
        const onTerpasang = () => setTampil(false);

        window.addEventListener('beforeinstallprompt', onPrompt);
        window.addEventListener('appinstalled', onTerpasang);

        // Chrome menahan `beforeinstallprompt` sampai ia menganggap pengguna
        // cukup berinteraksi — kadang baru di kunjungan kedua. Tanpa cadangan
        // ini, tombolnya seolah tidak pernah ada. Setelah 3 detik tombol tetap
        // ditampilkan, hanya saja isinya petunjuk manual.
        const timer = setTimeout(() => setTampil(true), 3000);

        // Kedua listener dilepas saat komponen dibongkar. Sebelumnya
        // 'appinstalled' tertinggal, dan karena layout dipasang ulang setiap
        // pindah halaman, listener-nya menumpuk satu per halaman.
        return () => {
            window.removeEventListener('beforeinstallprompt', onPrompt);
            window.removeEventListener('appinstalled', onTerpasang);
            clearTimeout(timer);
        };
    }, []);

    if (!tampil) return null;

    const pasang = async () => {
        if (iosPetunjuk) {
            alert(
                'Untuk memasang aplikasi di iPhone/iPad:\n\n' +
                '1. Ketuk tombol Bagikan (kotak dengan panah ke atas)\n' +
                '2. Pilih "Tambahkan ke Layar Utama"\n' +
                '3. Ketuk "Tambah"'
            );
            return;
        }

        if (!prompt) {
            alert(
                'Untuk memasang aplikasi:\n\n' +
                '1. Ketuk menu tiga titik di pojok browser\n' +
                '2. Pilih "Tambahkan ke Layar Utama" atau "Install app"'
            );
            return;
        }

        prompt.prompt();
        const { outcome } = await prompt.userChoice;

        // Event ini sekali pakai — setelah dipicu, harus dibuang.
        setPrompt(null);
        if (outcome === 'accepted') setTampil(false);
    };

    const ikon = (
        <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 19h16" />
        </svg>
    );

    if (mini) {
        return (
            <button
                type="button"
                onClick={pasang}
                title="Pasang Aplikasi"
                aria-label="Pasang Aplikasi"
                className="flex h-9 w-9 items-center justify-center rounded-xl text-claude-600 transition hover:bg-claude-100 dark:text-claude-400 dark:hover:bg-claude-500/15"
            >
                {ikon}
            </button>
        );
    }

    return (
        <button
            type="button"
            onClick={pasang}
            className="flex w-full items-center gap-2 rounded-xl bg-claude-100 px-3 py-2.5 text-sm font-medium text-claude-700 transition hover:bg-claude-200 dark:bg-claude-500/15 dark:text-claude-300 dark:hover:bg-claude-500/25"
        >
            {ikon}
            <span>Pasang Aplikasi</span>
        </button>
    );
}
import { useEffect, useState } from 'react';

/**
 * Tombol pasang aplikasi.
 *
 * Chrome/Edge memberi event `beforeinstallprompt` yang bisa ditunda dan
 * dipicu lewat tombol sendiri. Safari iOS tidak punya itu sama sekali,
 * jadi di sana yang ditampilkan hanya petunjuk manual — tanpa petunjuk,
 * hampir tidak ada wali yang menemukan menu "Add to Home Screen".
 */
export default function TombolInstall({ mini = false }) {
    const [prompt, setPrompt] = useState(null);
    const [iosPetunjuk, setIosPetunjuk] = useState(false);
    const [tampil, setTampil] = useState(false);

    useEffect(() => {
        // Sudah dipasang → tidak perlu ditawarkan lagi.
        const terpasang = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        if (terpasang) return;

        const ios = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

        if (ios) {
            setIosPetunjuk(true);
            setTampil(true);
            return;
        }

        const onPrompt = (e) => {
            e.preventDefault();
            setPrompt(e);
            setTampil(true);
        };

        window.addEventListener('beforeinstallprompt', onPrompt);
        window.addEventListener('appinstalled', () => setTampil(false));

        return () => window.removeEventListener('beforeinstallprompt', onPrompt);
    }, []);

    if (!tampil) return null;

    const pasang = async () => {
        if (iosPetunjuk) {
            alert(
                'Untuk memasang aplikasi di iPhone:\n\n' +
                '1. Ketuk tombol Bagikan di bawah layar\n' +
                '2. Pilih "Tambahkan ke Layar Utama"\n' +
                '3. Ketuk "Tambah"'
            );
            return;
        }

        if (!prompt) return;

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
                onClick={pasang}
                title="Pasang Aplikasi"
                className="flex h-9 w-9 items-center justify-center rounded-xl text-claude-600 transition hover:bg-claude-100 dark:text-claude-400 dark:hover:bg-claude-500/15"
            >
                {ikon}
            </button>
        );
    }

    return (
        <button
            onClick={pasang}
            className="flex w-full items-center gap-2 rounded-xl bg-claude-100 px-3 py-2.5 text-sm font-medium text-claude-700 transition hover:bg-claude-200 dark:bg-claude-500/15 dark:text-claude-300 dark:hover:bg-claude-500/25"
        >
            {ikon}
            <span>Pasang Aplikasi</span>
        </button>
    );
}
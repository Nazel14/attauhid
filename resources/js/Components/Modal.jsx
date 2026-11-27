import { useEffect, useRef } from 'react';

/**
 * Modal dasar aplikasi.
 *
 * Menangani hal yang sama untuk semua modal: tutup dengan Escape, tutup
 * dengan klik latar, latar diburamkan, dan halaman di belakang dikunci
 * supaya tidak ikut tergulir.
 *
 * Props:
 * - show, onClose
 * - maxWidth: sm | md | lg | xl | 2xl | 4xl | 6xl
 * - closeable: false untuk modal yang tidak boleh ditutup (mis. saat menyimpan)
 * - label: judul modal untuk pembaca layar (opsional tapi disarankan)
 */
export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
    label,
}) {
    // Simpan di ref supaya listener Escape selalu memanggil handler terbaru
    // tanpa perlu memasang ulang listener setiap render.
    const closeRef = useRef();
    closeRef.current = () => {
        if (closeable) onClose();
    };

    useEffect(() => {
        if (!show) return;

        const onKey = (e) => {
            if (e.key === 'Escape') closeRef.current();
        };

        document.addEventListener('keydown', onKey);

        // Nilai overflow sebelumnya disimpan lalu dikembalikan, bukan
        // dikosongkan. Kalau dikosongkan, menutup modal yang terbuka di atas
        // modal lain ikut membuka kunci gulir milik modal di bawahnya.
        const sebelumnya = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = sebelumnya;
        };
    }, [show]);

    if (!show) return null;

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '4xl': 'sm:max-w-4xl',
        '6xl': 'sm:max-w-6xl',
    }[maxWidth] ?? 'sm:max-w-2xl';

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* backdrop-blur-sm: halaman di belakang diburamkan.
                bg-black/50 di light, /70 di dark agar kontras modal tetap terjaga. */}
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
                onClick={() => closeRef.current()}
                aria-hidden="true"
            />

            {/* role + aria-modal: pembaca layar tahu ini jendela dialog dan
                isi halaman di belakangnya sedang tidak bisa dipakai.
                max-h di sini, bukan di children: isi modal yang panjang cukup
                memberi overflow-y-auto pada elemen dalamnya. */}
            <div
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className={
                    'relative flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-stone-200 ' +
                    'dark:bg-coal-800 dark:ring-coal-700 ' +
                    maxWidthClass
                }
            >
                {children}
            </div>
        </div>
    );
}
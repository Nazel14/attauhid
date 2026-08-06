import { useEffect, useRef } from 'react';

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
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
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
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
            />

            {/* max-h di sini, bukan di children: isi modal yang panjang cukup
                memberi overflow-y-auto pada elemen dalamnya. */}
            <div
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
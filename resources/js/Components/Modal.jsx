import { useEffect } from 'react';

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}) {
    const close = () => {
        if (closeable) onClose();
    };

    useEffect(() => {
        if (!show) return;

        const onKey = (e) => {
            if (e.key === 'Escape') close();
        };

        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [show, closeable]);

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
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
            <div className="absolute inset-0 bg-black/40" onClick={close} />

            <div
                className={
                    'relative w-full overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-stone-200 ' +
                    'dark:bg-coal-800 dark:ring-coal-700 ' +
                    maxWidthClass
                }
            >
                {children}
            </div>
        </div>
    );
}
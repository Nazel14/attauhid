import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) localRef.current?.focus();
    }, [isFocused]);

    return (
        // Kelas ditulis eksplisit, tidak mengandalkan @tailwindcss/forms —
        // plugin itu mengubah gaya form di SELURUH aplikasi, termasuk
        // halaman raport yang sudah jadi.
        <input
            {...props}
            type={type}
            ref={localRef}
            className={
                'rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-stone-800 shadow-sm ring-1 ring-stone-200 ' +
                'placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-claude-500 ' +
                'disabled:opacity-60 dark:bg-coal-900 dark:text-cream-100 dark:ring-coal-700 ' +
                className
            }
        />
    );
});
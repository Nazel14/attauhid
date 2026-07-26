export default function SecondaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            type={props.type ?? 'button'}
            disabled={disabled}
            className={
                'inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm ' +
                'font-medium text-stone-700 ring-1 ring-stone-200 transition hover:bg-stone-50 ' +
                'focus:outline-none focus:ring-2 focus:ring-claude-500 disabled:opacity-50 ' +
                'dark:bg-coal-700 dark:text-cream-100 dark:ring-coal-600 dark:hover:bg-coal-600 ' +
                className
            }
        >
            {children}
        </button>
    );
}
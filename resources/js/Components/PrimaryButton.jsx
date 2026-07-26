export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            disabled={disabled}
            className={
                'inline-flex items-center justify-center rounded-xl bg-claude-500 px-4 py-2.5 text-sm ' +
                'font-semibold text-white transition hover:bg-claude-600 focus:outline-none ' +
                'focus:ring-2 focus:ring-claude-500 focus:ring-offset-2 disabled:opacity-50 ' +
                'dark:focus:ring-offset-coal-800 ' +
                className
            }
        >
            {children}
        </button>
    );
}
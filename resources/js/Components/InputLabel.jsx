export default function InputLabel({ value, className = '', children, ...props }) {
    return (
        <label
            {...props}
            className={`block text-sm font-medium text-stone-700 dark:text-cream-100 ${className}`}
        >
            {value ? value : children}
        </label>
    );
}
import { useState, useRef, useEffect } from 'react';

/**
 * Dropdown dengan kolom pencarian.
 *
 * Props:
 * - options: [{ id, label }]
 * - value: id terpilih
 * - onChange: (id) => void
 * - placeholder
 */
export default function SearchableSelect({ options = [], value, onChange, placeholder = '-- Pilih --' }) {
    const [open, setOpen] = useState(false);
    const [cari, setCari] = useState('');
    const wrapRef = useRef(null);

    useEffect(() => {
        const onClickLuar = (e) => {
            if (wrapRef.current && !wrapRef.current.contains(e.target)) {
                setOpen(false);
                setCari('');
            }
        };
        document.addEventListener('mousedown', onClickLuar);
        return () => document.removeEventListener('mousedown', onClickLuar);
    }, []);

    const terpilih = options.find((o) => String(o.id) === String(value));
    const hasil = cari.trim()
        ? options.filter((o) => o.label.toLowerCase().includes(cari.toLowerCase()))
        : options;

    const pilih = (id) => {
        onChange(id);
        setOpen(false);
        setCari('');
    };

    return (
        <div ref={wrapRef} className="relative">
            <button type="button" onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between rounded-xl bg-white px-4 py-2.5 text-left text-sm ring-1 ring-stone-200 transition focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:ring-coal-700">
                <span className={terpilih ? 'text-stone-800 dark:text-cream-100' : 'text-stone-400'}>
                    {terpilih ? terpilih.label : placeholder}
                </span>
                <svg className={`h-4 w-4 flex-shrink-0 text-stone-400 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="absolute z-20 mt-1 w-full overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600">
                    <div className="border-b border-stone-100 p-2 dark:border-coal-600">
                        <input type="text" value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari..." autoFocus
                            className="w-full rounded-lg border-0 bg-stone-50 px-3 py-1.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-1 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100" />
                    </div>

                    <div className="max-h-52 overflow-y-auto py-1">
                        <button type="button" onClick={() => pilih('')}
                            className="block w-full px-4 py-2 text-left text-sm italic text-stone-400 transition hover:bg-cream-100 dark:hover:bg-coal-700">
                            {placeholder}
                        </button>

                        {hasil.length > 0 ? hasil.map((o) => (
                            <button key={o.id} type="button" onClick={() => pilih(o.id)}
                                className={`block w-full px-4 py-2 text-left text-sm transition ${
                                    String(o.id) === String(value)
                                        ? 'bg-claude-500/10 font-medium text-claude-600 dark:text-claude-300'
                                        : 'text-stone-700 hover:bg-cream-100 dark:text-stone-300 dark:hover:bg-coal-700'
                                }`}>
                                {o.label}
                            </button>
                        )) : (
                            <p className="px-4 py-3 text-center text-sm text-stone-400">Tidak ditemukan</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
import { useState, useRef, useEffect, useLayoutEffect } from 'react';
import { createPortal } from 'react-dom';

/**
 * Dropdown pilih-banyak dengan chip untuk yang sudah terpilih.
 *
 * Dipakai untuk hak akses: satu pegawai bisa memegang lebih dari satu
 * peran (mis. guru yang merangkap bendahara), dan Spatie memang
 * mendukung banyak role per user.
 *
 * Props:
 * - options: [{ id, label }]
 * - value: array of id
 * - onChange: (arrayOfId) => void
 * - placeholder
 */
export default function MultiSelectChips({ options = [], value = [], onChange, placeholder = '-- Pilih --' }) {
    const [open, setOpen] = useState(false);
    const [cari, setCari] = useState('');
    const [posisi, setPosisi] = useState({ top: 0, left: 0, width: 0, maxHeight: 260 });
    const btnRef = useRef(null);
    const panelRef = useRef(null);

    const hitungPosisi = () => {
        const r = btnRef.current?.getBoundingClientRect();
        if (!r) return;

        const JARAK = 4;
        const MIN_TINGGI = 140;

        const ruangBawah = window.innerHeight - r.bottom - JARAK - 8;
        const ruangAtas = r.top - JARAK - 8;

        // Balik ke atas kalau ruang bawah sempit — panel fixed tidak boleh
        // menembus keluar layar.
        const keAtas = ruangBawah < MIN_TINGGI && ruangAtas > ruangBawah;
        const maxHeight = Math.max(MIN_TINGGI, Math.min(260, keAtas ? ruangAtas : ruangBawah));

        setPosisi({
            top: keAtas ? r.top - JARAK - maxHeight : r.bottom + JARAK,
            left: r.left,
            width: r.width,
            maxHeight,
        });
    };

    useLayoutEffect(() => {
        if (open) hitungPosisi();
    }, [open]);

    useEffect(() => {
        if (!open) return;

        const onClickLuar = (e) => {
            if (
                btnRef.current && !btnRef.current.contains(e.target) &&
                panelRef.current && !panelRef.current.contains(e.target)
            ) {
                setOpen(false);
                setCari('');
            }
        };
        const onEsc = (e) => {
            if (e.key === 'Escape') { setOpen(false); setCari(''); }
        };
        const onScrollResize = () => hitungPosisi();

        document.addEventListener('mousedown', onClickLuar);
        document.addEventListener('keydown', onEsc);
        // capture: true agar scroll di dalam modal ikut tertangkap
        window.addEventListener('scroll', onScrollResize, true);
        window.addEventListener('resize', onScrollResize);

        return () => {
            document.removeEventListener('mousedown', onClickLuar);
            document.removeEventListener('keydown', onEsc);
            window.removeEventListener('scroll', onScrollResize, true);
            window.removeEventListener('resize', onScrollResize);
        };
    }, [open]);

    const terpilih = options.filter((o) => value.includes(o.id));
    const hasil = cari.trim()
        ? options.filter((o) => o.label.toLowerCase().includes(cari.toLowerCase()))
        : options;

    const toggle = (id) => {
        onChange(value.includes(id) ? value.filter((v) => v !== id) : [...value, id]);
    };

    return (
        <div className="relative">
            <button ref={btnRef} type="button" onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between gap-2 rounded-xl bg-white px-3 py-2 text-left text-sm ring-1 ring-stone-200 transition focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:ring-coal-700">
                {terpilih.length > 0 ? (
                    <span className="flex flex-wrap gap-1.5">
                        {terpilih.map((o) => (
                            <span key={o.id}
                                className="inline-flex items-center gap-1 rounded-lg bg-claude-500/10 px-2 py-0.5 text-xs font-medium text-claude-700 dark:text-claude-300">
                                {o.label}
                                {/* span, bukan button — button bersarang tidak valid HTML */}
                                <span role="button" tabIndex={-1}
                                    onClick={(e) => { e.stopPropagation(); toggle(o.id); }}
                                    className="cursor-pointer text-claude-500 transition hover:text-claude-800 dark:hover:text-claude-100">
                                    ×
                                </span>
                            </span>
                        ))}
                    </span>
                ) : (
                    <span className="py-0.5 text-stone-400">{placeholder}</span>
                )}

                <svg className={`h-4 w-4 flex-shrink-0 text-stone-400 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && createPortal(
                <div ref={panelRef}
                    style={{
                        position: 'fixed',
                        top: posisi.top,
                        left: posisi.left,
                        width: posisi.width,
                        maxHeight: posisi.maxHeight,
                    }}
                    className="z-[9999] flex flex-col overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600">
                    <div className="shrink-0 border-b border-stone-100 p-2 dark:border-coal-600">
                        <input type="text" value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari..." autoFocus
                            className="w-full rounded-lg border-0 bg-stone-50 px-3 py-1.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-1 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100" />
                    </div>

                    {/* min-h-0: tanpa ini daftar tidak menyusut di dalam flex container */}
                    <div className="min-h-0 flex-1 overflow-y-auto py-1">
                        {hasil.length > 0 ? hasil.map((o) => {
                            const aktif = value.includes(o.id);
                            return (
                                <button key={o.id} type="button" onClick={() => toggle(o.id)}
                                    className={`flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm transition ${
                                        aktif
                                            ? 'bg-claude-500/10 font-medium text-claude-600 dark:text-claude-300'
                                            : 'text-stone-700 hover:bg-cream-100 dark:text-stone-300 dark:hover:bg-coal-700'
                                    }`}>
                                    <span className={`flex h-4 w-4 shrink-0 items-center justify-center rounded border transition ${
                                        aktif
                                            ? 'border-claude-500 bg-claude-500 text-white'
                                            : 'border-stone-300 dark:border-coal-600'
                                    }`}>
                                        {aktif && (
                                            <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="3" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        )}
                                    </span>
                                    {o.label}
                                </button>
                            );
                        }) : (
                            <p className="px-4 py-3 text-center text-sm text-stone-400">Tidak ditemukan</p>
                        )}
                    </div>
                </div>,
                document.body
            )}
        </div>
    );
}
import { useState, useRef, useEffect, useLayoutEffect } from 'react';
import { createPortal } from 'react-dom';

/**
 * Dropdown dengan kolom pencarian.
 *
 * Panel dirender lewat portal ke body dengan position: fixed, supaya tidak
 * terpotong oleh container ber-overflow (mis. form modal yang bisa di-scroll).
 * Konsekuensinya posisi harus dihitung ulang setiap kali ada scroll di
 * container mana pun — termasuk scroll di dalam modal, bukan hanya window.
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
    const [posisi, setPosisi] = useState({ top: 0, bottom: 'auto', left: 0, width: 0, maxHeight: 260, keAtas: false });
    const btnRef = useRef(null);

    // Kolom "Cari..." hanya langsung fokus di perangkat bermouse. Di HP,
    // fokus otomatis memunculkan keyboard yang menutupi daftar pilihan —
    // padahal biasanya pengguna cuma ingin mengetuk salah satu pilihan.
    const fokusOtomatis = typeof window !== 'undefined'
        && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const panelRef = useRef(null);

    const hitungPosisi = () => {
        const r = btnRef.current?.getBoundingClientRect();
        if (!r) return;

        const JARAK = 4;
        const MIN_TINGGI = 140;

        const ruangBawah = window.innerHeight - r.bottom - JARAK - 8;
        const ruangAtas  = r.top - JARAK - 8;

        // Kalau ruang di bawah tidak cukup tapi di atas lebih lega,
        // panel dibalik ke atas tombol daripada menembus keluar layar.
        const keAtas = ruangBawah < MIN_TINGGI && ruangAtas > ruangBawah;
        const maxHeight = Math.max(MIN_TINGGI, Math.min(260, keAtas ? ruangAtas : ruangBawah));

        // Saat membuka ke ATAS, panel ditambatkan lewat `bottom`, bukan `top`.
        // maxHeight hanya batas atas: bila pilihannya sedikit, panel lebih
        // pendek dari maxHeight, dan rumus lama (top = r.top - maxHeight)
        // menyisakan celah kosong di antara panel dan tombol.
        setPosisi({
            top: keAtas ? 'auto' : r.bottom + JARAK,
            bottom: keAtas ? window.innerHeight - r.top + JARAK : 'auto',
            left: r.left,
            width: r.width,
            maxHeight,
            keAtas,
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

        // Tutup saat Escape — panel fixed di luar modal tidak ikut tertutup
        // oleh handler Escape milik modal.
        const onEsc = (e) => {
            if (e.key === 'Escape') { setOpen(false); setCari(''); }
        };

        const onScrollResize = () => hitungPosisi();

        document.addEventListener('mousedown', onClickLuar);
        document.addEventListener('keydown', onEsc);
        // capture: true agar scroll di container dalam (form modal) ikut tertangkap
        window.addEventListener('scroll', onScrollResize, true);
        window.addEventListener('resize', onScrollResize);

        return () => {
            document.removeEventListener('mousedown', onClickLuar);
            document.removeEventListener('keydown', onEsc);
            window.removeEventListener('scroll', onScrollResize, true);
            window.removeEventListener('resize', onScrollResize);
        };
    }, [open]);

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
        <div className="relative">
            <button ref={btnRef} type="button" onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between rounded-xl bg-white px-4 py-2.5 text-left text-sm ring-1 ring-stone-200 transition focus:outline-none focus:ring-2 focus:ring-claude-500 dark:bg-coal-900 dark:ring-coal-700">
                <span className={terpilih ? 'text-stone-800 dark:text-cream-100' : 'text-stone-400'}>
                    {terpilih ? terpilih.label : placeholder}
                </span>
                <svg className={`h-4 w-4 flex-shrink-0 text-stone-400 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && createPortal(
                <div ref={panelRef}
                    style={{
                        position: 'fixed',
                        top: posisi.top,
                        bottom: posisi.bottom,
                        left: posisi.left,
                        width: posisi.width,
                        maxHeight: posisi.maxHeight,
                    }}
                    className="z-[9999] flex flex-col overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-stone-200 dark:bg-coal-800 dark:ring-coal-600">
                    <div className="shrink-0 border-b border-stone-100 p-2 dark:border-coal-600">
                        <input type="text" value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari..." autoFocus={fokusOtomatis}
                            className="w-full rounded-lg border-0 bg-stone-50 px-3 py-1.5 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-1 focus:ring-claude-500 dark:bg-coal-900 dark:text-cream-100" />
                    </div>

                    {/* flex-1 + min-h-0: tanpa min-h-0 daftar tidak mau menyusut
                        di dalam flex container dan panel jadi melewati maxHeight. */}
                    <div className="min-h-0 flex-1 overflow-y-auto py-1">
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
                </div>,
                document.body
            )}
        </div>
    );
}
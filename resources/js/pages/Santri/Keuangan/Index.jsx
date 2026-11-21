import { Fragment, useState, useEffect, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const Icon = ({ path, className = 'w-4 h-4' }) => (
    <svg className={`shrink-0 ${className}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.7" d={path} />
    </svg>
);
const P = {
    wallet: 'M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
    bill: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    alert: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    receipt: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
    coins: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    chevron: 'M19 9l-7 7-7-7',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    copy: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z',
    qr: 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 4h2m-2 2h6m-6-6h6m0 4v2',
    bank: 'M3 21h18M4 10h16M5 10V7l7-4 7 4v3M6 21v-8m4 8v-8m4 8v-8m4 8v-8',
    chat: 'M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-3.5-7.14L21 4l-1.2 3.6A8.96 8.96 0 0121 12z',
    heart: 'M12 20s-7-4.4-7-9.2A4.2 4.2 0 0112 8a4.2 4.2 0 017 2.8c0 4.8-7 9.2-7 9.2z',
    cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    upload: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
    x: 'M6 18L18 6M6 6l12 12',
    trash: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    refresh: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    photo: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
};

// ── helpers ──
const rp = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
const fmtDate = (d) => {
    if (!d) return '—';
    try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return d; }
};
const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

// Tanggal hari ini menurut jam HP wali. toISOString() memakai UTC, jadi
// sebelum pukul 07.00 WIB hasilnya masih "kemarin".
const hariIni = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const MAKS_BUKTI = 2 * 1024 * 1024; // sama dengan batas server (2 MB)

/* Foto dari kamera HP biasanya 3–6 MB, di atas batas server. Gambar
   diperkecil di browser (sisi terpanjang 1600 px, JPEG 80%) — tetap jelas
   untuk dibaca nominal & nomor rekeningnya. PDF dikirim apa adanya. */
async function kecilkanGambar(file) {
    if (!file.type.startsWith('image/') || file.size < 400 * 1024) return file;

    try {
        const bitmap = await createImageBitmap(file);
        const skala = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * skala);
        canvas.height = Math.round(bitmap.height * skala);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((ok) => canvas.toBlob(ok, 'image/jpeg', 0.8));
        if (!blob || blob.size >= file.size) return file;

        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch {
        return file; // browser lama: biarkan server yang menilai ukurannya
    }
}

// Warna status pembayaran — sah / ditolak / menunggu.
const chipBayar = (b) => b.sah
    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20'
    : b.ditolak
        ? 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-red-100 dark:ring-red-500/20'
        : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20';

function usePaged(rows, perPage) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
    useEffect(() => { if (page > totalPages) setPage(1); }, [totalPages, page]);
    return { page, setPage, totalPages, slice: rows.slice((page - 1) * perPage, page * perPage), total: rows.length, perPage };
}

const Card = ({ className = '', children }) => (
    <div className={`bg-white dark:bg-coal-700 rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 ${className}`}>{children}</div>
);

/* Status tagihan → warna semantik.
   'Lewat tempo' bukan nilai kolom, melainkan turunan due_date — tapi bagi
   wali itulah informasi yang paling menentukan, jadi diberi warna sendiri. */
const TAGIHAN_STATUS = {
    Paid:        { label: 'Lunas',       chip: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20' },
    Installment: { label: 'Cicilan',     chip: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20' },
    Unpaid:      { label: 'Belum Bayar', chip: 'bg-cream-200 dark:bg-coal-600 text-stone-600 dark:text-stone-300 ring-cream-300 dark:ring-coal-500' },
    Lewat:       { label: 'Lewat Tempo', chip: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-red-100 dark:ring-red-500/20' },
    Cancelled:   { label: 'Dibatalkan',  chip: 'bg-cream-100 dark:bg-coal-600/40 text-stone-400 dark:text-stone-500 ring-cream-300 dark:ring-coal-600' },
};
// Batal diperiksa lebih dulu: tagihan yang dibatalkan bukan kewajiban lagi,
// jadi tidak boleh tampil sebagai "Lewat Tempo" meski tanggalnya terlewat.
const statusTagihan = (t) => {
    if (t.status === 'Cancelled') return TAGIHAN_STATUS.Cancelled;
    if (t.lewat_tempo) return TAGIHAN_STATUS.Lewat;
    return TAGIHAN_STATUS[t.status] ?? TAGIHAN_STATUS.Unpaid;
};

function StatCard({ icon, label, value, sub, tone = 'stone' }) {
    const toneMap = {
        stone:   'bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400',
        claude:  'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
        emerald: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
        amber:   'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400',
        red:     'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400',
    };
    return (
        <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl p-4 flex items-center gap-3">
            <span className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${toneMap[tone]}`}>{icon}</span>
            <div className="min-w-0">
                <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium truncate">{label}</p>
                <p className="text-base sm:text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-tight tabular-nums whitespace-nowrap">{value}</p>
                {sub && <p className="text-[10px] text-stone-400 truncate">{sub}</p>}
            </div>
        </div>
    );
}

/* Tombol kwitansi. Sengaja pakai <button> + window.open, bukan anchor,
   karena kwitansi dibuka di tab baru dan tidak perlu di-crawl. */
function Tombolkwitansi({ id }) {
    const buka = () => {
        const url = safeRoute('santri.keuangan.kwitansi', { pembayaran: id });
        if (url !== '#') window.open(url, '_blank', 'noopener,noreferrer');
    };

    return (
        <button
            type="button"
            onClick={buka}
            title="Lihat kwitansi"
            className="inline-flex p-1.5 rounded-lg text-claude-600 dark:text-claude-400 hover:bg-claude-100 dark:hover:bg-claude-500/15 transition-colors active:scale-90"
        >
            <Icon path={P.receipt} className="w-4 h-4" />
        </button>
    );
}

/* Baris nomor rekening dengan tombol salin. Clipboard API butuh HTTPS
   atau localhost — di luar itu dipakai fallback textarea lawas, supaya
   tombolnya tidak diam-diam gagal di koneksi http. */
function BarisSalin({ label, nilai }) {
    const [tersalin, setTersalin] = useState(false);

    const salin = async () => {
        const teks = String(nilai).replace(/[\s-]/g, '');

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(teks);
            } else {
                const ta = document.createElement('textarea');
                ta.value = teks;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            setTersalin(true);
            setTimeout(() => setTersalin(false), 2000);
        } catch {
            setTersalin(false);
        }
    };

    return (
        <div className="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-500">
            <div className="min-w-0">
                <p className="text-[10px] text-stone-400 dark:text-stone-500">{label}</p>
                <p className="text-sm font-bold text-stone-900 dark:text-stone-100 font-mono tracking-wide truncate">{nilai}</p>
            </div>
            <button
                type="button"
                onClick={salin}
                className={`shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition active:scale-95 ${tersalin ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20' : 'bg-white dark:bg-coal-700 text-stone-600 dark:text-stone-300 ring-1 ring-cream-300 dark:ring-coal-600 hover:bg-cream-200 dark:hover:bg-coal-600'}`}
            >
                <Icon path={tersalin ? P.check : P.copy} className="w-3.5 h-3.5" />
                {tersalin ? 'Tersalin' : 'Salin'}
            </button>
        </div>
    );
}

/* Isi panel pembayaran. Dipisah dari pembungkusnya supaya bisa dipakai
   di dalam modal tanpa ikut membawa Card. */
function IsiCaraBayar({ rekening = [], waAdmin, santri, sisa, judul, infaq = false }) {
    const [pilih, setPilih] = useState(0);

    if (!rekening.length) {
        return (
            <p className="text-xs text-stone-500 dark:text-stone-400 text-center py-6">
                Informasi pembayaran belum diatur. Silakan hubungi admin pesantren.
            </p>
        );
    }

    const r = rekening[Math.min(pilih, rekening.length - 1)];

    const pesanWa = () => {
        // Infaq tidak punya nominal tetap — barisnya dibiarkan untuk diisi
        // wali sendiri, bukan dikirim "Rp 0" yang membingungkan bendahara.
        const teks = infaq
            ? `Assalamu'alaikum, saya wali dari:\n\n` +
              `Nama  : ${santri?.name ?? '-'}\n` +
              `NIS   : ${santri?.nis ?? '-'}\n` +
              `Kelas : ${santri?.kelas ?? '-'}\n\n` +
              `Konfirmasi infaq Rumah Quran\n` +
              `Nominal: Rp \n` +
              `Metode : ${r.nama}\n\n` +
              `Bukti transfer saya lampirkan. Jazakumullahu khairan.`
            : `Assalamu'alaikum, saya wali dari:\n\n` +
              `Nama  : ${santri?.name ?? '-'}\n` +
              `NIS   : ${santri?.nis ?? '-'}\n` +
              `Kelas : ${santri?.kelas ?? '-'}\n\n` +
              (judul ? `Tagihan: ${judul}\n` : '') +
              `Nominal: ${rp(sisa)}\n` +
              `Metode : ${r.nama}\n\n` +
              `Bukti transfer saya lampirkan. Terima kasih.`;

        return `https://wa.me/${waAdmin}?text=${encodeURIComponent(teks)}`;
    };

    return (
        <>
            {rekening.length > 1 && (
                <div className="flex gap-1.5 mb-4 overflow-x-auto pb-1">
                    {rekening.map((x, i) => (
                        <button
                            key={x.id}
                            onClick={() => setPilih(i)}
                            className={`shrink-0 px-3 py-2 text-xs font-semibold rounded-xl transition-colors ring-1 ${i === pilih ? 'bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-claude-200 dark:ring-claude-500/20' : 'bg-white dark:bg-coal-700 text-stone-500 dark:text-stone-400 ring-cream-300 dark:ring-coal-600'}`}
                        >
                            {x.nama}
                        </button>
                    ))}
                </div>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                {r.qris && (
                    <div className="rounded-xl p-3 bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600 text-center">
                        <p className="text-[11px] font-semibold text-stone-600 dark:text-stone-300 mb-2 flex items-center justify-center gap-1.5">
                            <Icon path={P.qr} className="w-3.5 h-3.5" /> Scan QRIS
                        </p>
                        <img src={r.qris} alt={`QRIS ${r.nama}`} className="w-full max-w-[220px] mx-auto rounded-lg bg-white" loading="lazy" />
                        <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-2">
                            Buka aplikasi bank atau e-wallet, pilih Bayar/Scan QRIS
                        </p>
                    </div>
                )}

                <div className="space-y-2.5">
                    {sisa > 0 && <BarisSalin label="Jumlah yang harus dibayar" nilai={rp(sisa)} />}
                    {r.nomor && <BarisSalin label={`No. ${r.tipe === 'Bank' ? 'Rekening' : 'Akun'} ${r.nama}`} nilai={r.nomor} />}
                    {r.pemilik && (
                        <div className="rounded-xl px-3 py-2.5 bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-500">
                            <p className="text-[10px] text-stone-400 dark:text-stone-500">Atas Nama</p>
                            <p className="text-sm font-semibold text-stone-900 dark:text-stone-100 truncate">{r.pemilik}</p>
                        </div>
                    )}
                    {r.catatan && (
                        <p className="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed px-1">{r.catatan}</p>
                    )}
                </div>
            </div>

            {waAdmin && (
                <div className="mt-4 pt-4 border-t border-cream-300 dark:border-coal-600">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed max-w-xs">
                            {infaq
                                ? 'Setelah transfer, kirim bukti lewat WhatsApp. Infaq tercatat di riwayat setelah bendahara memverifikasi.'
                                : 'Setelah transfer, kirim bukti lewat WhatsApp. Status lunas diperbarui setelah bendahara memverifikasi.'}
                        </p>
                        <button
                            type="button"
                            onClick={() => window.open(pesanWa(), '_blank', 'noopener,noreferrer')}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold transition-colors shadow-sm active:scale-95"
                        >
                            <Icon path={P.chat} className="w-4 h-4" /> Konfirmasi via WhatsApp
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}

/* Kerangka modal: lembar bawah di HP, kotak tengah di layar lebar.
   onClose disimpan di ref: kalau jadi dependency, listener Escape & kunci
   scroll dilepas-pasang setiap parent render (onClose = arrow function baru). */
function ModalShell({ terbuka, onClose, ikon, judul, sub, lebar = 'sm:max-w-2xl', children, footer = null }) {
    const closeRef = useRef(onClose);
    closeRef.current = onClose;

    // Dikunci hanya saat modal terbuka. Tanpa penjaga `terbuka`, efek ini
    // berjalan sejak halaman dimuat dan mengunci scroll selamanya, karena
    // komponennya memang tidak pernah dilepas.
    useEffect(() => {
        if (!terbuka) return;

        const onKey = (e) => e.key === 'Escape' && closeRef.current();
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [terbuka]);

    if (!terbuka) return null;

    return (
        <div className="fixed inset-0 z-[200] bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4" onClick={onClose}>
            <div onClick={(e) => e.stopPropagation()} className={`w-full ${lebar} bg-white dark:bg-coal-700 rounded-t-2xl sm:rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 shadow-2xl flex flex-col max-h-[92vh] sm:max-h-[90vh]`}>
                <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-cream-300 dark:border-coal-600 shrink-0">
                    <div className="min-w-0">
                        <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 flex items-center gap-2">
                            <span className="w-7 h-7 shrink-0 rounded-lg grid place-items-center bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400">
                                <Icon path={ikon} className="w-3.5 h-3.5" />
                            </span>
                            {judul}
                        </h3>
                        {sub && <p className="text-[11px] text-stone-400 dark:text-stone-500 mt-0.5 truncate">{sub}</p>}
                    </div>
                    <button type="button" onClick={onClose} aria-label="Tutup"
                        className="w-8 h-8 rounded-lg grid place-items-center bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition shrink-0">
                        <Icon path={P.x} className="w-5 h-5" />
                    </button>
                </div>
                <div className="p-4 sm:p-5 overflow-y-auto">{children}</div>
                {footer && (
                    <div className="px-4 sm:px-5 py-3 border-t border-cream-300 dark:border-coal-600 shrink-0 bg-cream-50 dark:bg-coal-700 rounded-b-none sm:rounded-b-2xl">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}

/* Modal cara bayar. Dipakai dua pemicu: total tunggakan dan per tagihan. */
function ModalBayar({ data, onClose, rekening, waAdmin, santri, onKirimBukti = null }) {
    return (
        <ModalShell
            terbuka={Boolean(data)}
            onClose={onClose}
            ikon={P.bank}
            judul={data?.infaq ? 'Cara Berinfaq' : 'Cara Pembayaran'}
            sub={data?.judul}
            footer={onKirimBukti && data ? (
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <p className="text-[11px] text-stone-500 dark:text-stone-400">Sudah transfer? Kirim bukti langsung dari sini.</p>
                    <button type="button" onClick={() => onKirimBukti(data)}
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold transition-colors active:scale-95">
                        <Icon path={P.upload} className="w-4 h-4" /> Kirim Bukti Transfer
                    </button>
                </div>
            ) : null}
        >
            {data && (
                <IsiCaraBayar rekening={rekening} waAdmin={waAdmin} santri={santri} sisa={data.sisa} judul={data.judul} infaq={Boolean(data.infaq)} />
            )}
        </ModalShell>
    );
}

/* Form konfirmasi pembayaran oleh wali.
   Kiriman masuk sebagai draft di halaman Pembayaran bendahara dan baru
   mengurangi tagihan setelah disetujui — form ini tidak mengubah saldo apa pun. */
function ModalKonfirmasi({ data, onClose, onTerkirim, rekening, tagihan, isRq }) {
    const bisaDibayar = tagihan.filter((t) => t.status === 'Unpaid' || t.status === 'Installment');
    const transfer = rekening.filter((r) => r.tipe !== 'Tunai');

    const { data: form, setData, post, processing, errors, reset, clearErrors, setError } = useForm({
        invoice_id: '',
        nominal: '',
        payment_date: hariIni(),
        account_id: '',
        bukti: null,
        catatan: '',
    });

    const [pratinjau, setPratinjau] = useState(null);
    const [menyiapkan, setMenyiapkan] = useState(false);
    const inputFile = useRef(null);
    const inputGanti = useRef(null);

    // Isi awal setiap kali modal dibuka dari tombol yang berbeda.
    useEffect(() => {
        if (!data) return;
        clearErrors();
        const t = data.invoice_id ? bisaDibayar.find((x) => x.id === data.invoice_id) : (bisaDibayar.length === 1 ? bisaDibayar[0] : null);
        setData({
            invoice_id: t?.id ?? '',
            nominal: t ? String(Math.max(0, Math.round(t.sisa - (t.menunggu ?? 0)))) : '',
            payment_date: hariIni(),
            account_id: transfer.length === 1 ? transfer[0].id : '',
            bukti: null,
            catatan: '',
        });
        setPratinjau(null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data]);

    useEffect(() => () => { if (pratinjau) URL.revokeObjectURL(pratinjau); }, [pratinjau]);

    const tagihanDipilih = bisaDibayar.find((t) => String(t.id) === String(form.invoice_id));
    const bolehMaks = tagihanDipilih ? Math.max(0, tagihanDipilih.sisa - (tagihanDipilih.menunggu ?? 0)) : null;

    const pilihTagihan = (id) => {
        const t = bisaDibayar.find((x) => String(x.id) === String(id));
        setData((d) => ({ ...d, invoice_id: id, nominal: t ? String(Math.max(0, Math.round(t.sisa - (t.menunggu ?? 0)))) : d.nominal }));
    };

    const pilihFile = async (e) => {
        const asli = e.target.files?.[0];
        if (!asli) return;

        if (!/^(image\/(jpeg|png)|application\/pdf)$/.test(asli.type)) {
            setError('bukti', 'Bukti harus berupa foto (JPG/PNG) atau PDF.');
            e.target.value = '';
            return;
        }

        setMenyiapkan(true);
        const file = await kecilkanGambar(asli);
        setMenyiapkan(false);

        if (file.size > MAKS_BUKTI) {
            setError('bukti', 'Ukuran file lebih dari 2 MB. Coba kirim tangkapan layar (screenshot) bukti transfer.');
            e.target.value = '';
            return;
        }

        clearErrors('bukti');
        setData('bukti', file);
        setPratinjau(file.type.startsWith('image/') ? URL.createObjectURL(file) : null);
        // Dikosongkan supaya file yang sama tetap bisa dipilih ulang
        // setelah dihapus — tanpa ini onChange tidak terpicu.
        e.target.value = '';
    };

    const hapusFile = () => {
        setData('bukti', null);
        setPratinjau(null);
        clearErrors('bukti');
        if (inputFile.current) inputFile.current.value = '';
        if (inputGanti.current) inputGanti.current.value = '';
    };

    const kirim = (e) => {
        e.preventDefault();
        post(route('santri.keuangan.konfirmasi'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { reset(); setPratinjau(null); onTerkirim?.(); },
        });
    };

    const label = 'block text-[11px] font-semibold text-stone-600 dark:text-stone-300 mb-1.5';
    const field = 'block w-full rounded-xl border-0 bg-white dark:bg-coal-600/40 px-3.5 py-2.5 text-sm text-stone-900 dark:text-stone-100 ring-1 ring-cream-300 dark:ring-coal-600 placeholder:text-stone-400 focus:ring-2 focus:ring-claude-500 dark:[color-scheme:dark]';
    const Salah = ({ k }) => errors[k] ? <p className="mt-1 text-[11px] text-red-600 dark:text-red-400">{errors[k]}</p> : null;

    const nominalTampil = form.nominal ? Number(form.nominal).toLocaleString('id-ID') : '';

    return (
        <ModalShell
            terbuka={Boolean(data)}
            onClose={processing ? () => {} : onClose}
            ikon={P.upload}
            judul={isRq ? 'Kirim Bukti Infaq' : 'Kirim Bukti Pembayaran'}
            sub="Diperiksa bendahara sebelum disahkan"
            lebar="sm:max-w-lg"
            footer={(
                <div className="flex gap-2 justify-end">
                    <button type="button" onClick={onClose} disabled={processing}
                        className="px-4 py-2.5 rounded-xl text-sm font-semibold text-stone-600 dark:text-stone-300 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 hover:bg-cream-100 dark:hover:bg-coal-600 disabled:opacity-50">
                        Batal
                    </button>
                    <button type="submit" form="form-konfirmasi" disabled={processing || menyiapkan || transfer.length === 0}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold transition-colors active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                        <Icon path={P.upload} className="w-4 h-4" />
                        {processing ? 'Mengirim...' : 'Kirim Bukti'}
                    </button>
                </div>
            )}
        >
            <form id="form-konfirmasi" onSubmit={kirim} className="space-y-4">
                {errors.konfirmasi && (
                    <div className="flex items-start gap-2 rounded-xl bg-red-50 dark:bg-red-500/10 ring-1 ring-red-100 dark:ring-red-500/20 p-3 text-xs text-red-700 dark:text-red-300">
                        <Icon path={P.alert} className="w-4 h-4 shrink-0 mt-0.5" /> {errors.konfirmasi}
                    </div>
                )}

                {transfer.length === 0 && (
                    <div className="rounded-xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 p-3 text-xs text-stone-600 dark:text-stone-300">
                        Belum ada rekening transfer yang diumumkan sekolah. Silakan hubungi bendahara.
                    </div>
                )}

                {!isRq && (
                    <div>
                        <label className={label} htmlFor="k-tagihan">Tagihan yang dibayar</label>
                        <select id="k-tagihan" value={form.invoice_id} onChange={(e) => pilihTagihan(e.target.value)} className={field}>
                            <option value="">Pilih tagihan...</option>
                            {bisaDibayar.map((t) => (
                                <option key={t.id} value={t.id}>{t.judul} — sisa {rp(t.sisa)}</option>
                            ))}
                        </select>
                        <Salah k="invoice_id" />
                        {tagihanDipilih?.menunggu > 0 && (
                            <p className="mt-1.5 text-[11px] text-amber-700 dark:text-amber-400">
                                {rp(tagihanDipilih.menunggu)} untuk tagihan ini sedang diperiksa bendahara. Sisa yang bisa dikirim {rp(bolehMaks)}.
                            </p>
                        )}
                    </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className={label} htmlFor="k-nominal">Nominal ditransfer</label>
                        <div className="relative">
                            <span className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-stone-400">Rp</span>
                            <input id="k-nominal" type="text" inputMode="numeric" autoComplete="off" value={nominalTampil}
                                onChange={(e) => setData('nominal', e.target.value.replace(/\D/g, '').slice(0, 10))}
                                placeholder="0" className={`${field} pl-10 tabular-nums font-semibold`} />
                        </div>
                        <Salah k="nominal" />
                    </div>
                    <div>
                        <label className={label} htmlFor="k-tanggal">Tanggal transfer</label>
                        <input id="k-tanggal" type="date" max={hariIni()} value={form.payment_date}
                            onChange={(e) => setData('payment_date', e.target.value)} className={field} />
                        <Salah k="payment_date" />
                    </div>
                </div>

                <div>
                    <label className={label} htmlFor="k-rekening">Ditransfer ke</label>
                    <select id="k-rekening" value={form.account_id} onChange={(e) => setData('account_id', e.target.value)} className={field}>
                        <option value="">Pilih rekening tujuan...</option>
                        {transfer.map((r) => (
                            <option key={r.id} value={r.id}>{r.nama}{r.nomor ? ` · ${r.nomor}` : ''}</option>
                        ))}
                    </select>
                    <Salah k="account_id" />
                </div>

                <div>
                    <span className={label}>Bukti transfer</span>
                    {form.bukti ? (
                        <div className="flex items-center gap-3 rounded-xl p-2.5 bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-500">
                            {pratinjau
                                ? (
                                    <a href={pratinjau} target="_blank" rel="noreferrer" title="Lihat ukuran penuh" className="shrink-0">
                                        <img src={pratinjau} alt="Pratinjau bukti" className="w-14 h-14 rounded-lg object-cover ring-1 ring-cream-300 dark:ring-coal-500" />
                                    </a>
                                )
                                : <span className="w-14 h-14 shrink-0 rounded-lg grid place-items-center bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-400"><Icon path={P.bill} className="w-6 h-6" /></span>}
                            <div className="min-w-0 flex-1">
                                <p className="text-xs font-semibold text-stone-800 dark:text-stone-100 truncate">{form.bukti.name}</p>
                                <p className="text-[10px] text-stone-400">{(form.bukti.size / 1024).toFixed(0)} KB</p>
                            </div>
                            <div className="shrink-0 flex items-center gap-1.5">
                                {/* Ganti langsung membuka pemilih file; Hapus mengosongkan isian. */}
                                <button type="button" onClick={() => inputGanti.current?.click()} disabled={menyiapkan || processing}
                                    title="Ganti file"
                                    className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-stone-600 dark:text-stone-300 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 hover:bg-cream-200 dark:hover:bg-coal-600 disabled:opacity-50">
                                    <Icon path={P.refresh} className="w-3.5 h-3.5" />
                                    <span className="hidden sm:inline">{menyiapkan ? 'Menyiapkan...' : 'Ganti'}</span>
                                </button>
                                <button type="button" onClick={hapusFile} disabled={processing}
                                    title="Hapus file" aria-label="Hapus file"
                                    className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-red-600 dark:text-red-400 bg-white dark:bg-coal-700 ring-1 ring-red-200 dark:ring-red-500/30 hover:bg-red-50 dark:hover:bg-red-500/10 disabled:opacity-50">
                                    <Icon path={P.trash} className="w-3.5 h-3.5" />
                                    <span className="hidden sm:inline">Hapus</span>
                                </button>
                            </div>
                            <input ref={inputGanti} type="file" accept="image/jpeg,image/png,application/pdf" onChange={pilihFile} className="sr-only" tabIndex={-1} />
                        </div>
                    ) : (
                        <label className={`flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed px-4 py-6 text-center cursor-pointer transition ${errors.bukti ? 'border-red-300 dark:border-red-500/40' : 'border-cream-300 dark:border-coal-500 hover:border-claude-400 hover:bg-claude-50/50 dark:hover:bg-claude-500/5'}`}>
                            <Icon path={P.photo} className="w-7 h-7 text-stone-400" />
                            <span className="text-xs font-semibold text-stone-700 dark:text-stone-200">{menyiapkan ? 'Menyiapkan foto...' : 'Pilih foto atau file bukti'}</span>
                            <span className="text-[10px] text-stone-400">JPG, PNG, atau PDF · maks 2 MB</span>
                            <input ref={inputFile} type="file" accept="image/jpeg,image/png,application/pdf" onChange={pilihFile} className="sr-only" />
                        </label>
                    )}
                    <Salah k="bukti" />
                </div>

                <div>
                    <label className={label} htmlFor="k-catatan">Catatan <span className="font-normal text-stone-400">(opsional)</span></label>
                    <input id="k-catatan" type="text" maxLength={255} value={form.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        placeholder={isRq ? 'Contoh: infaq bulan November' : 'Contoh: transfer dari rekening atas nama ibu'}
                        className={field} />
                    <Salah k="catatan" />
                </div>

                <p className="flex items-start gap-2 text-[11px] text-stone-500 dark:text-stone-400">
                    <Icon path={P.info} className="w-3.5 h-3.5 mt-0.5 shrink-0" />
                    {isRq
                        ? 'Infaq tercatat setelah bendahara mencocokkan bukti dengan rekening.'
                        : 'Sisa tagihan berkurang setelah bendahara mencocokkan bukti dengan rekening. Status bisa dipantau di Riwayat Transaksi.'}
                </p>
            </form>
        </ModalShell>
    );
}

/* Batalkan kiriman sendiri — dua langkah agar tidak terhapus karena salah ketuk. */
function TombolBatal({ id, onSelesai }) {
    const [yakin, setYakin] = useState(false);
    const [proses, setProses] = useState(false);

    const batalkan = () => {
        setProses(true);
        router.delete(route('santri.keuangan.konfirmasi.batal', { pembayaran: id }), {
            preserveScroll: true,
            onSuccess: () => onSelesai?.('Kiriman bukti transfer dibatalkan.'),
            onError: (e) => onSelesai?.(e.konfirmasi || 'Gagal membatalkan kiriman.', true),
            onFinish: () => { setProses(false); setYakin(false); },
        });
    };

    if (!yakin) {
        return (
            <button type="button" onClick={() => setYakin(true)}
                className="inline-flex items-center rounded-lg px-2 py-1 text-[10px] font-semibold text-stone-500 dark:text-stone-400 ring-1 ring-cream-300 dark:ring-coal-600 hover:bg-cream-100 dark:hover:bg-coal-600 whitespace-nowrap">
                Batalkan
            </button>
        );
    }

    return (
        <span className="inline-flex items-center gap-1 whitespace-nowrap">
            <button type="button" onClick={batalkan} disabled={proses}
                className="rounded-lg px-2 py-1 text-[10px] font-bold text-white bg-red-600 hover:bg-red-500 disabled:opacity-50">
                {proses ? '...' : 'Ya, batalkan'}
            </button>
            <button type="button" onClick={() => setYakin(false)} disabled={proses}
                className="rounded-lg px-2 py-1 text-[10px] font-semibold text-stone-500 ring-1 ring-cream-300 dark:ring-coal-600">
                Tidak
            </button>
        </span>
    );
}

/* Pesan hasil kirim/batal — hilang sendiri setelah beberapa detik. */
function Pemberitahuan({ pesan, onTutup }) {
    useEffect(() => {
        if (!pesan) return;
        const t = setTimeout(onTutup, 7000);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pesan]);

    if (!pesan) return null;

    return (
        <div role="status" className={`flex items-start gap-2.5 rounded-2xl p-3 ring-1 ${pesan.gagal
            ? 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-700 dark:text-red-300'
            : 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-800 dark:text-emerald-300'}`}>
            <Icon path={pesan.gagal ? P.alert : P.check} className="w-4 h-4 shrink-0 mt-0.5" />
            <p className="text-xs flex-1">{pesan.teks}</p>
            <button type="button" onClick={onTutup} aria-label="Tutup" className="shrink-0 opacity-60 hover:opacity-100">
                <Icon path={P.x} className="w-4 h-4" />
            </button>
        </div>
    );
}

export default function Index({ santri = null, lembaga = 'tk', ringkasan = null, infaq = null, tagihan = [], pembayaran = [], rekening = [], waAdmin = null, message = null }) {
    const [mobileTab, setMobileTab] = useState('tagihan');
    const [bayar, setBayar] = useState(null); // { judul, sisa } — null = modal tertutup
    const [konfirmasi, setKonfirmasi] = useState(null); // { invoice_id? } — null = form tertutup
    const [pesan, setPesan] = useState(null); // { teks, gagal }

    const adaTransfer = rekening.some((x) => x.tipe !== 'Tunai');

    // Dari modal cara bayar langsung ke form kirim bukti, tagihan ikut terbawa.
    const keKonfirmasi = (asal) => { setBayar(null); setKonfirmasi({ invoice_id: asal?.invoice_id ?? null }); };
    const kabari = (teks, gagal = false) => setPesan({ teks, gagal });
    const terkirim = () => {
        setKonfirmasi(null);
        kabari('Bukti transfer terkirim. Bendahara akan memeriksanya — status berubah menjadi "Diterima" setelah disahkan.');
        setMobileTab('bayar');
    };

    if (!santri) {
        return (
            <SantriAuthenticatedLayout>
                <Head title="Keuangan" />
                <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">
                    <div className="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl py-16 text-center">
                        <span className="inline-flex w-12 h-12 rounded-2xl bg-cream-200 dark:bg-coal-600 items-center justify-center text-stone-400 mb-3">
                            <Icon path={P.info} className="w-6 h-6" />
                        </span>
                        <p className="text-sm font-semibold text-stone-600 dark:text-stone-300">Data Tidak Ditemukan</p>
                        <p className="text-xs text-stone-400 dark:text-stone-500 mt-1.5">{message ?? 'Hubungi administrator untuk bantuan lebih lanjut.'}</p>
                    </div>
                </div>
            </SantriAuthenticatedLayout>
        );
    }

    const r = ringkasan ?? {};
    const lunasSemua = (r.total_tunggakan ?? 0) <= 0;
    const isPr = santri?.gender === 'P' || santri?.gender === 'Perempuan';

    // Santri Rumah Quran: tidak ada tagihan, hanya riwayat infaq sukarela.
    if (lembaga === 'arahan') {
        return (
            <SantriAuthenticatedLayout>
                <Head title="Infaq" />
                <HalamanInfaq
                    santri={santri}
                    isPr={isPr}
                    infaq={infaq ?? {}}
                    pembayaran={pembayaran}
                    adaRekening={rekening.length > 0}
                    onCaraBayar={() => setBayar({ judul: 'Infaq Rumah Quran', sisa: 0, infaq: true })}
                    onKirim={adaTransfer ? () => setKonfirmasi({}) : null}
                    pesan={pesan}
                    onTutupPesan={() => setPesan(null)}
                    onKabari={kabari}
                />
                <ModalBayar
                    data={bayar}
                    onClose={() => setBayar(null)}
                    rekening={rekening}
                    waAdmin={waAdmin}
                    santri={santri}
                    onKirimBukti={adaTransfer ? keKonfirmasi : null}
                />
                <ModalKonfirmasi
                    data={konfirmasi}
                    onClose={() => setKonfirmasi(null)}
                    onTerkirim={terkirim}
                    rekening={rekening}
                    tagihan={[]}
                    isRq
                />
            </SantriAuthenticatedLayout>
        );
    }

    return (
        <SantriAuthenticatedLayout>
            <Head title="Keuangan" />

            <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0">
                        <Icon path={P.wallet} className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Keuangan</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Tagihan &amp; riwayat pembayaran Anda</p>
                    </div>
                </div>

                <div className="space-y-4">
                    <Pemberitahuan pesan={pesan} onTutup={() => setPesan(null)} />

                    <KartuSantri santri={santri} isPr={isPr} />

                    {/* Tunggakan — kartu utama, dibedakan supaya langsung terbaca */}
                    <Card className={`p-4 sm:p-5 ${lunasSemua ? 'ring-emerald-100 dark:ring-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-500/5' : 'ring-claude-200 dark:ring-claude-500/20 bg-claude-50/60 dark:bg-claude-500/5'}`}>
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="min-w-0">
                                <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium">Total Tunggakan</p>
                                <p className={`text-2xl sm:text-3xl font-bold tracking-tight leading-none mt-1 tabular-nums ${lunasSemua ? 'text-emerald-600 dark:text-emerald-400' : 'text-claude-600 dark:text-claude-400'}`}>
                                    {rp(r.total_tunggakan)}
                                </p>
                                <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-1.5">
                                    {lunasSemua
                                        ? 'Semua tagihan sudah lunas. Terima kasih.'
                                        : `${r.jumlah_belum ?? 0} tagihan belum lunas`}
                                </p>
                            </div>

                            {!lunasSemua && rekening.length > 0 && (
                                <div className={`w-full sm:w-auto grid gap-2 sm:flex ${adaTransfer ? 'grid-cols-2' : 'grid-cols-1'}`}>
                                    <button
                                        type="button"
                                        onClick={() => setBayar({ judul: 'Seluruh tunggakan', sisa: r.total_tunggakan ?? 0 })}
                                        className="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-[13px] sm:text-sm font-semibold transition-colors shadow-sm active:scale-95 whitespace-nowrap"
                                    >
                                        <Icon path={P.qr} className="w-4 h-4" /> Bayar Sekarang
                                    </button>
                                    {adaTransfer && (
                                        <button
                                            type="button"
                                            onClick={() => setKonfirmasi({})}
                                            className="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl bg-white dark:bg-coal-700 text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/30 hover:bg-claude-50 dark:hover:bg-claude-500/10 text-[13px] sm:text-sm font-semibold transition-colors active:scale-95 whitespace-nowrap"
                                        >
                                            <Icon path={P.upload} className="w-4 h-4" /> Kirim Bukti Bayar
                                        </button>
                                    )}
                                </div>
                            )}

                            {r.tagihan_terdekat && (
                                <div className="rounded-xl px-3.5 py-2.5 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 min-w-[180px]">
                                    <p className="text-[10px] text-stone-400 dark:text-stone-500 font-medium">Jatuh tempo terdekat</p>
                                    <p className="text-xs font-bold text-stone-900 dark:text-stone-100 mt-0.5 truncate">{r.tagihan_terdekat.judul}</p>
                                    <div className="flex items-baseline justify-between gap-3 mt-1">
                                        <span className="text-[11px] text-stone-500 dark:text-stone-400">{fmtDate(r.tagihan_terdekat.jatuh_tempo)}</span>
                                        <span className="text-sm font-bold text-claude-600 dark:text-claude-400 tabular-nums">{rp(r.tagihan_terdekat.sisa)}</span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* Stat cards */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <StatCard tone="claude"  icon={<Icon path={P.bill} className="w-5 h-5" />}  label="Total Tagihan" value={tagihan.length} sub="tagihan terbit" />
                        <StatCard tone="emerald" icon={<Icon path={P.check} className="w-5 h-5" />} label="Sudah Lunas"   value={r.jumlah_lunas ?? 0} sub="tagihan selesai" />
                        <StatCard tone="red"     icon={<Icon path={P.alert} className="w-5 h-5" />} label="Lewat Tempo"   value={r.jumlah_lewat ?? 0} sub="perlu segera dibayar" />
                        <StatCard tone="amber"   icon={<Icon path={P.coins} className="w-5 h-5" />} label="Total Dibayar" value={rp(r.total_dibayar)} sub="sudah disahkan" />
                    </div>

                    {/* Catatan verifikasi — hanya muncul bila memang relevan */}
                    {(r.menunggu_verifikasi ?? 0) > 0 && (
                        <div className="flex items-start gap-2.5 rounded-2xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 p-3">
                            <Icon path={P.info} className="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                            <p className="text-xs text-stone-600 dark:text-stone-300">
                                Ada <strong className="font-semibold text-stone-900 dark:text-stone-100">{r.menunggu_verifikasi} pembayaran</strong> yang
                                masih menunggu verifikasi bendahara. Sisa tagihan baru berkurang setelah pembayaran disahkan.
                            </p>
                        </div>
                    )}

                    {/* Mobile tabs */}
                    <div className="flex gap-2 lg:hidden">
                        <button onClick={() => setMobileTab('tagihan')} className={`flex-1 py-2.5 text-xs font-semibold rounded-xl transition-colors ring-1 ${mobileTab === 'tagihan' ? 'bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-claude-200 dark:ring-claude-500/20' : 'bg-white dark:bg-coal-700 text-stone-500 dark:text-stone-400 ring-cream-300 dark:ring-coal-600'}`}>
                            Tagihan <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{tagihan.length}</span>
                        </button>
                        <button onClick={() => setMobileTab('bayar')} className={`flex-1 py-2.5 text-xs font-semibold rounded-xl transition-colors ring-1 ${mobileTab === 'bayar' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20' : 'bg-white dark:bg-coal-700 text-stone-500 dark:text-stone-400 ring-cream-300 dark:ring-coal-600'}`}>
                            Transaksi <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{pembayaran.length}</span>
                        </button>
                    </div>

                    {/* Panels */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                        <TagihanPanel
                            className={mobileTab === 'tagihan' ? 'lg:block' : 'hidden lg:block'}
                            rows={tagihan}
                            onBayar={rekening.length ? setBayar : null}
                            onKirim={adaTransfer ? (t) => setKonfirmasi({ invoice_id: t.id }) : null}
                        />
                        <RiwayatPanel className={mobileTab === 'bayar' ? 'lg:block' : 'hidden lg:block'} rows={pembayaran} onKabari={kabari} />
                    </div>
                </div>
            </div>

            <ModalBayar
                data={bayar}
                onClose={() => setBayar(null)}
                rekening={rekening}
                waAdmin={waAdmin}
                santri={santri}
                onKirimBukti={adaTransfer ? keKonfirmasi : null}
            />
            <ModalKonfirmasi
                data={konfirmasi}
                onClose={() => setKonfirmasi(null)}
                onTerkirim={terkirim}
                rekening={rekening}
                tagihan={tagihan}
                isRq={false}
            />
        </SantriAuthenticatedLayout>
    );
}

const Th = ({ children, className = '' }) => (
    <th className={`px-3 py-2.5 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400 ${className}`}>{children}</th>
);
const Td = ({ children, className = '' }) => <td className={`px-3 py-2.5 text-xs ${className}`}>{children}</td>;

// ── Panel Tagihan ──
const FILTER_TAGIHAN = [
    ['semua', 'Semua'],
    ['belum', 'Belum Lunas'],
    ['lunas', 'Lunas'],
    ['batal', 'Dibatalkan'],
];

function TagihanPanel({ rows: semua, className = '', onBayar = null, onKirim = null }) {
    const [filter, setFilter] = useState('semua');
    const rows = semua.filter((t) => {
        if (filter === 'belum') return t.status === 'Unpaid' || t.status === 'Installment';
        if (filter === 'lunas') return t.status === 'Paid';
        if (filter === 'batal') return t.status === 'Cancelled';
        return true;
    });
    // Tab "Dibatalkan" hanya muncul bila memang ada — kebanyakan wali tidak
    // pernah punya tagihan batal, dan tab kosong cuma menambah bingung.
    const adaBatal = semua.some((t) => t.status === 'Cancelled');
    const pg = usePaged(rows, 10);
    const [buka, setBuka] = useState(null); // id tagihan yang rinciannya dibuka

    return (
        <div className={`bg-white dark:bg-coal-700 rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 sm:px-5 py-3.5 flex items-center justify-between border-b border-cream-300 dark:border-coal-600">
                <div className="flex items-center gap-2.5">
                    <span className="w-8 h-8 rounded-lg grid place-items-center bg-claude-100 dark:bg-claude-500/15 ring-1 ring-claude-200 dark:ring-claude-500/20 text-claude-600 dark:text-claude-400">
                        <Icon path={P.bill} className="w-4 h-4" />
                    </span>
                    <div>
                        <h4 className="font-bold tracking-tight text-sm text-stone-900 dark:text-stone-100">Tagihan</h4>
                        <p className="text-[10px] text-stone-400 dark:text-stone-500 leading-tight">Ketuk baris untuk rincian</p>
                    </div>
                </div>
                <span className="text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/20">{semua.length} tagihan</span>
            </div>

            {semua.length > 0 && (
                <div className="flex gap-1.5 overflow-x-auto px-4 sm:px-5 py-2.5 border-b border-cream-300 dark:border-coal-600">
                    {FILTER_TAGIHAN.filter(([k]) => k !== 'batal' || adaBatal).map(([k, label]) => (
                        <button key={k} type="button" onClick={() => { setFilter(k); pg.setPage(1); }}
                            className={`shrink-0 px-2.5 py-1 rounded-lg text-[11px] font-semibold ring-1 transition ${filter === k
                                ? 'bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-claude-200 dark:ring-claude-500/20'
                                : 'bg-white dark:bg-coal-700 text-stone-500 dark:text-stone-400 ring-cream-300 dark:ring-coal-600 hover:bg-cream-100 dark:hover:bg-coal-600'}`}>
                            {label}
                        </button>
                    ))}
                </div>
            )}

            {rows.length === 0 ? (
                semua.length === 0
                    ? <Empty text="Belum ada tagihan" subtext="Tagihan akan muncul setelah diterbitkan sekolah" />
                    : <Empty text="Tidak ada tagihan di kategori ini" />
            ) : (
                <>
                    <div className="overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                    <Th>Tagihan</Th>
                                    <Th className="w-28 text-right">Sisa</Th>
                                    <Th className="w-24 text-center">Status</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                {pg.slice.map((t) => {
                                    const st = statusTagihan(t);
                                    const aktif = buka === t.id;
                                    return (
                                        // Fragment bentuk panjang: <> tidak bisa menerima key,
                                        // padahal satu tagihan bisa menghasilkan dua <tr>.
                                        <Fragment key={t.id}>
                                            <tr
                                                onClick={() => setBuka(aktif ? null : t.id)}
                                                className="hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors cursor-pointer"
                                            >
                                                <Td>
                                                    <p className="text-xs text-stone-900 dark:text-stone-100 font-semibold leading-tight flex items-center gap-1.5">
                                                        {t.judul}
                                                        <Icon path={P.chevron} className={`w-3 h-3 text-stone-400 transition-transform ${aktif ? 'rotate-180' : ''}`} />
                                                    </p>
                                                    <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-0.5">
                                                        {t.periode ?? '—'} · Jatuh tempo {fmtDate(t.jatuh_tempo)}
                                                    </p>
                                                </Td>
                                                <Td className="text-right">
                                                    {t.status === 'Cancelled' ? (
                                                        <p className="text-stone-400 dark:text-stone-500 tabular-nums line-through">{rp(t.total)}</p>
                                                    ) : (
                                                        <>
                                                            <p className="font-bold text-stone-900 dark:text-stone-100 tabular-nums">{rp(t.sisa)}</p>
                                                            <p className="text-[10px] text-stone-400 dark:text-stone-500 tabular-nums">dari {rp(t.total)}</p>
                                                        </>
                                                    )}
                                                </Td>
                                                <Td className="text-center">
                                                    <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold ring-1 whitespace-nowrap ${st.chip}`}>{st.label}</span>
                                                </Td>
                                            </tr>

                                            {aktif && (
                                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                                    <td colSpan={3} className="px-3 py-3">
                                                        {t.status === 'Cancelled' && (
                                                            <p className="mb-2.5 rounded-lg bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 px-2.5 py-2 text-[11px] text-stone-500 dark:text-stone-400">
                                                                Tagihan ini dibatalkan sekolah{t.alasan_batal ? ` (${t.alasan_batal})` : ''} — tidak perlu dibayar.
                                                            </p>
                                                        )}
                                                        <p className="text-[11px] font-semibold text-stone-500 dark:text-stone-400 mb-2">Rincian</p>
                                                        {t.rincian?.length ? (
                                                            <div className="space-y-1.5">
                                                                {t.rincian.map((it, i) => (
                                                                    <div key={i} className="flex items-center justify-between gap-3 text-xs">
                                                                        <span className="text-stone-600 dark:text-stone-300 truncate">{it.deskripsi}</span>
                                                                        <span className="font-semibold text-stone-900 dark:text-stone-100 tabular-nums whitespace-nowrap">{rp(it.nominal)}</span>
                                                                    </div>
                                                                ))}
                                                                <div className="flex items-center justify-between gap-3 text-xs pt-2 mt-1 border-t border-cream-300 dark:border-coal-600">
                                                                    <span className="text-stone-500 dark:text-stone-400">Sudah dibayar</span>
                                                                    <span className="font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{rp(t.terbayar)}</span>
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <p className="text-[11px] text-stone-400 italic">Tidak ada rincian item</p>
                                                        )}
                                                        <div className="flex items-center justify-between gap-3 mt-2.5">
                                                            <p className="text-[10px] text-stone-400 dark:text-stone-500 font-mono truncate">{t.nomor}</p>
                                                            {(t.status === 'Unpaid' || t.status === 'Installment') && (onBayar || onKirim) && (
                                                                <div className="shrink-0 flex items-center gap-1.5">
                                                                    {onKirim && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={(e) => { e.stopPropagation(); onKirim(t); }}
                                                                            className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white dark:bg-coal-700 text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/30 hover:bg-claude-50 dark:hover:bg-claude-500/10 text-[11px] font-semibold transition-colors active:scale-95"
                                                                        >
                                                                            <Icon path={P.upload} className="w-3.5 h-3.5" /> Kirim Bukti
                                                                        </button>
                                                                    )}
                                                                    {onBayar && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={(e) => { e.stopPropagation(); onBayar({ judul: t.judul, sisa: t.sisa, invoice_id: t.id }); }}
                                                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 text-white text-[11px] font-semibold transition-colors active:scale-95"
                                                                        >
                                                                            <Icon path={P.qr} className="w-3.5 h-3.5" /> Bayar {rp(t.sisa)}
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                        {t.menunggu > 0 && (
                                                            <p className="mt-2 flex items-center gap-1.5 text-[11px] text-amber-700 dark:text-amber-400">
                                                                <Icon path={P.clock} className="w-3.5 h-3.5 shrink-0" />
                                                                {rp(t.menunggu)} sedang diperiksa bendahara
                                                            </p>
                                                        )}
                                                    </td>
                                                </tr>
                                            )}
                                        </Fragment>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    <Pagination pg={pg} />
                </>
            )}
        </div>
    );
}

// ── Panel Riwayat Transaksi ──
// Dipakai dua halaman: riwayat pembayaran (TK) dan riwayat infaq (Rumah
// Quran). Filter tahun dikerjakan di browser — datanya sudah terkirim utuh.
/* Keterangan tambahan di bawah baris riwayat: alasan tolak & asal kiriman. */
function CatatanBaris({ b }) {
    return (
        <>
            {b.ditolak && b.alasan_tolak && (
                <p className="mt-1 text-[11px] leading-snug text-red-600 dark:text-red-400">
                    Ditolak: {b.alasan_tolak}. Silakan kirim ulang bukti yang benar.
                </p>
            )}
            {b.dari_wali && !b.sah && !b.ditolak && (
                <p className="mt-0.5 text-[10px] text-stone-400 dark:text-stone-500">Dikirim dari portal</p>
            )}
        </>
    );
}

function RiwayatPanel({ rows, className = '', judul = 'Riwayat Transaksi', sub = 'Kwitansi terbit setelah disahkan', satuan = 'transaksi', kosong = 'Belum ada pembayaran', onKabari = null }) {
    const tahunList = [...new Set(rows.map((b) => (b.tanggal ?? '').slice(0, 4)).filter(Boolean))].sort().reverse();
    const [tahun, setTahun] = useState('');
    const terfilter = tahun ? rows.filter((b) => (b.tanggal ?? '').startsWith(tahun)) : rows;
    const totalSah = terfilter.filter((b) => b.sah).reduce((n, b) => n + Number(b.nominal || 0), 0);
    const pg = usePaged(terfilter, 10);

    return (
        <div className={`bg-white dark:bg-coal-700 rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 sm:px-5 py-3.5 flex items-center justify-between gap-3 border-b border-cream-300 dark:border-coal-600">
                <div className="flex items-center gap-2.5 min-w-0">
                    <span className="w-8 h-8 shrink-0 rounded-lg grid place-items-center bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                        <Icon path={P.coins} className="w-4 h-4" />
                    </span>
                    <div className="min-w-0">
                        <h4 className="font-bold tracking-tight text-sm text-stone-900 dark:text-stone-100 truncate">{judul}</h4>
                        <p className="text-[10px] text-stone-400 dark:text-stone-500 leading-tight truncate">{sub}</p>
                    </div>
                </div>
                {tahunList.length > 1 ? (
                    <select value={tahun} onChange={(e) => { setTahun(e.target.value); pg.setPage(1); }}
                        aria-label="Filter tahun"
                        className="shrink-0 rounded-lg border-0 bg-cream-100 dark:bg-coal-600/40 py-1.5 pl-2.5 pr-7 text-[11px] font-semibold text-stone-600 dark:text-stone-300 ring-1 ring-cream-300 dark:ring-coal-600 focus:ring-2 focus:ring-claude-500 dark:[color-scheme:dark]">
                        <option value="">Semua tahun</option>
                        {tahunList.map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                ) : (
                    <span className="shrink-0 text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20">{rows.length} {satuan}</span>
                )}
            </div>

            {terfilter.length === 0 ? (
                <Empty text={kosong} subtext="Muncul setelah dicatat bendahara atau Anda mengirim bukti transfer" />
            ) : (
                <>
                    {/* HP: daftar bertumpuk. Tabel empat kolom terpotong di
                        layar 390px — kolom kwitansi justru yang hilang. */}
                    <div className="sm:hidden divide-y divide-cream-200 dark:divide-coal-600/60">
                        {pg.slice.map((b) => (
                            <div key={b.id} className="px-4 py-3">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold text-stone-900 dark:text-stone-100 tabular-nums">{fmtDate(b.tanggal)}</p>
                                        <p className="text-[11px] text-stone-400 dark:text-stone-500 mt-0.5 leading-snug">
                                            {b.kategori_label}{b.untuk ? ` · ${b.untuk}` : ''} · {b.metode}
                                        </p>
                                        {b.catatan && <p className="text-[11px] text-stone-400 dark:text-stone-500 italic leading-snug">{b.catatan}</p>}
                                        <CatatanBaris b={b} />
                                    </div>
                                    <p className={`shrink-0 text-sm font-bold tabular-nums ${b.ditolak ? 'text-stone-400 line-through' : 'text-stone-900 dark:text-stone-100'}`}>{rp(b.nominal)}</p>
                                </div>
                                <div className="mt-2 flex items-center justify-between gap-3">
                                    <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold ring-1 whitespace-nowrap ${chipBayar(b)}`}>
                                        {b.status}
                                    </span>
                                    {b.bisa_batal && <TombolBatal id={b.id} onSelesai={onKabari} />}
                                    {b.sah && (
                                        <button type="button" onClick={() => { const url = safeRoute('santri.keuangan.kwitansi', { pembayaran: b.id }); if (url !== '#') window.open(url, '_blank', 'noopener,noreferrer'); }}
                                            className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/20 bg-claude-50 dark:bg-claude-500/10 active:scale-95">
                                            <Icon path={P.receipt} className="w-3.5 h-3.5" /> kwitansi
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="hidden sm:block overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                    <Th>Tanggal &amp; Keterangan</Th>
                                    <Th className="text-right">Nominal</Th>
                                    <Th className="w-28 text-center">Status</Th>
                                    <Th className="w-20 text-center">Aksi</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                {pg.slice.map((b) => (
                                    <tr key={b.id} className="hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors">
                                        <Td>
                                            <p className="text-stone-900 dark:text-stone-100 font-semibold leading-tight tabular-nums whitespace-nowrap">{fmtDate(b.tanggal)}</p>
                                            <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-0.5 leading-snug">
                                                {b.kategori_label}{b.untuk ? ` · ${b.untuk}` : ''}
                                            </p>
                                            {b.catatan && <p className="text-[10px] text-stone-400 dark:text-stone-500 italic leading-snug">{b.catatan}</p>}
                                            <CatatanBaris b={b} />
                                        </Td>
                                        <Td className="text-right">
                                            <p className={`font-bold tabular-nums whitespace-nowrap ${b.ditolak ? 'text-stone-400 line-through' : 'text-stone-900 dark:text-stone-100'}`}>{rp(b.nominal)}</p>
                                            <p className="text-[10px] text-stone-400 dark:text-stone-500">{b.metode}</p>
                                        </Td>
                                        <Td className="text-center">
                                            <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold ring-1 whitespace-nowrap ${chipBayar(b)}`}>
                                                {b.status}
                                            </span>
                                        </Td>
                                        <Td className="text-center">
                                            {b.sah
                                                ? <Tombolkwitansi id={b.id} />
                                                : b.bisa_batal
                                                    ? <TombolBatal id={b.id} onSelesai={onKabari} />
                                                    : <span className="text-[10px] text-stone-300 dark:text-stone-600">−</span>}
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {/* Jumlah yang sudah disahkan pada tahun terpilih — angka yang
                        biasanya dicari wali saat mencocokkan dengan catatannya. */}
                    <div className="flex items-center justify-between gap-3 px-4 sm:px-5 py-2.5 border-t border-cream-300 dark:border-coal-600 text-xs">
                        <span className="text-stone-500 dark:text-stone-400">Total diterima{tahun ? ` ${tahun}` : ''}</span>
                        <span className="font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{rp(totalSah)}</span>
                    </div>
                    <Pagination pg={pg} />
                </>
            )}
        </div>
    );
}

function KartuSantri({ santri, isPr }) {
    return (
        <Card className="p-4 sm:p-5">
            <div className="flex items-center gap-4">
                <span className={`w-12 h-12 sm:w-14 sm:h-14 rounded-full ring-1 flex items-center justify-center text-lg font-bold flex-shrink-0 ${isPr ? 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400' : 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400'}`}>
                    {(santri?.name ?? '?').charAt(0).toUpperCase()}
                </span>
                <div className="min-w-0 flex-1">
                    <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri?.name}</h3>
                    <p className="text-[11px] text-stone-400 dark:text-stone-500 font-mono">{santri?.nis ?? '-'}</p>
                    <span className="inline-block mt-1 text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{santri?.kelas ?? '-'}</span>
                </div>
            </div>
        </Card>
    );
}

/* Halaman khusus santri Rumah Quran: infaq sukarela, tanpa tagihan. */
function HalamanInfaq({ santri, isPr, infaq, pembayaran, adaRekening, onCaraBayar, onKirim = null, pesan = null, onTutupPesan, onKabari }) {
    const t = infaq ?? {};

    return (
        <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">
            <div className="flex items-center gap-3 mb-5">
                <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0">
                    <Icon path={P.cash} className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                </div>
                <div className="min-w-0 flex-1">
                    <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Infaq</h1>
                    <p className="text-[11px] text-stone-500 mt-0.5">Riwayat infaq sukarela Anda di Rumah Quran</p>
                </div>
            </div>

            <div className="space-y-4">
                <Pemberitahuan pesan={pesan} onTutup={onTutupPesan} />

                <KartuSantri santri={santri} isPr={isPr} />

                <Card className="p-4 sm:p-5 ring-emerald-100 dark:ring-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-500/5">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium">Infaq tahun {t.tahun}</p>
                            <p className="text-2xl sm:text-3xl font-bold tracking-tight leading-none mt-1 tabular-nums text-emerald-600 dark:text-emerald-400">
                                {rp(t.total_tahun)}
                            </p>
                            <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-1.5">
                                {t.terakhir
                                    ? `Terakhir ${fmtDate(t.terakhir.tanggal)} · ${rp(t.terakhir.nominal)}`
                                    : 'Belum ada infaq yang tercatat.'}
                            </p>
                        </div>
                        {adaRekening && (
                            <div className={`w-full sm:w-auto grid gap-2 sm:flex ${onKirim ? 'grid-cols-2' : 'grid-cols-1'}`}>
                                <button
                                    type="button"
                                    onClick={onCaraBayar}
                                    className="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-[13px] sm:text-sm font-semibold transition-colors shadow-sm active:scale-95 whitespace-nowrap"
                                >
                                    <Icon path={P.qr} className="w-4 h-4" /> Cara Berinfaq
                                </button>
                                {onKirim && (
                                    <button
                                        type="button"
                                        onClick={onKirim}
                                        className="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl bg-white dark:bg-coal-700 text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/30 hover:bg-claude-50 dark:hover:bg-claude-500/10 text-[13px] sm:text-sm font-semibold transition-colors active:scale-95 whitespace-nowrap"
                                    >
                                        <Icon path={P.upload} className="w-4 h-4" /> Kirim Bukti Infaq
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                    <p className="mt-3 flex items-start gap-2 text-[11px] text-stone-500 dark:text-stone-400">
                        <Icon path={P.info} className="w-3.5 h-3.5 mt-0.5 shrink-0" />
                        Infaq di Rumah Quran bersifat sukarela — tidak ada tagihan maupun tunggakan.
                    </p>
                </Card>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <StatCard tone="emerald" icon={<Icon path={P.cash} className="w-5 h-5" />}    label="Total Infaq"   value={rp(t.total_semua)} sub="seluruh periode" />
                    <StatCard tone="claude"  icon={<Icon path={P.calendar} className="w-5 h-5" />} label={`Tahun ${t.tahun ?? ''}`} value={rp(t.total_tahun)} sub="sudah disahkan" />
                    <StatCard tone="stone"   icon={<Icon path={P.receipt} className="w-5 h-5" />}  label="Jumlah Infaq"  value={t.jumlah ?? 0} sub="kali berinfaq" />
                    <StatCard tone="amber"   icon={<Icon path={P.clock} className="w-5 h-5" />}    label="Menunggu"      value={t.menunggu ?? 0} sub="belum diverifikasi" />
                </div>

                {(t.menunggu ?? 0) > 0 && (
                    <div className="flex items-start gap-2.5 rounded-2xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 p-3">
                        <Icon path={P.info} className="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <p className="text-xs text-stone-600 dark:text-stone-300">
                            Ada <strong className="font-semibold text-stone-900 dark:text-stone-100">{t.menunggu} infaq</strong> yang
                            masih menunggu verifikasi bendahara. Infaq baru dijumlahkan setelah disahkan.
                        </p>
                    </div>
                )}

                <RiwayatPanel
                    rows={pembayaran}
                    judul="Riwayat Infaq"
                    sub="kwitansi terbit setelah disahkan"
                    satuan="infaq"
                    kosong="Belum ada infaq"
                    onKabari={onKabari}
                />
            </div>
        </div>
    );
}

function Empty({ text, subtext }) {
    return (
        <div className="flex-1 grid place-items-center p-10 text-center min-h-[160px]">
            <div>
                <div className="w-12 h-12 mx-auto mb-3 rounded-2xl grid place-items-center bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500">
                    <Icon path={P.bill} className="w-6 h-6 text-stone-400 opacity-60" />
                </div>
                <p className="text-xs sm:text-sm font-semibold text-stone-600 dark:text-stone-300">{text}</p>
                {subtext && <p className="text-[10px] sm:text-xs text-stone-400 dark:text-stone-500 mt-1">{subtext}</p>}
            </div>
        </div>
    );
}

function Pagination({ pg }) {
    if (pg.totalPages <= 1) return null;
    const { page, setPage, totalPages, total, perPage } = pg;
    const from = Math.min((page - 1) * perPage + 1, total);
    const to = Math.min(page * perPage, total);
    let sp = Math.max(1, page - 2), ep = Math.min(totalPages, sp + 4);
    if (ep - sp < 4) sp = Math.max(1, ep - 4);
    const nums = []; for (let i = sp; i <= ep; i++) nums.push(i);

    const btn = 'px-2.5 py-1 text-xs rounded-md font-medium transition';
    const normal = `${btn} text-stone-500 dark:text-stone-400 hover:bg-cream-200 dark:hover:bg-coal-600 hover:text-stone-900 dark:hover:text-stone-100`;
    const active = `${btn} font-semibold bg-claude-500 text-white`;
    const disabled = `${btn} text-stone-300 dark:text-stone-600 cursor-not-allowed`;

    return (
        <div className="px-3 py-2.5 border-t border-cream-300 dark:border-coal-600 bg-cream-100 dark:bg-coal-600/40">
            <div className="flex items-center justify-between gap-2 flex-wrap">
                <span className="text-[11px] text-stone-400 dark:text-stone-500 tabular-nums">{from}–{to} dari {total}</span>
                <div className="flex items-center gap-1 flex-wrap">
                    <button disabled={page === 1} onClick={() => setPage(page - 1)} className={page === 1 ? disabled : normal}>‹</button>
                    {sp > 1 && <><button onClick={() => setPage(1)} className={normal}>1</button>{sp > 2 && <span className="text-xs text-stone-400 dark:text-stone-600">…</span>}</>}
                    {nums.map((i) => <button key={i} onClick={() => setPage(i)} className={i === page ? active : normal}>{i}</button>)}
                    {ep < totalPages && <>{ep < totalPages - 1 && <span className="text-xs text-stone-400 dark:text-stone-600">…</span>}<button onClick={() => setPage(totalPages)} className={normal}>{totalPages}</button></>}
                    <button disabled={page === totalPages} onClick={() => setPage(page + 1)} className={page === totalPages ? disabled : normal}>›</button>
                </div>
            </div>
        </div>
    );
}
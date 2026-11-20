import { Fragment, useState, useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const Icon = ({ path, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
};

// ── helpers ──
const rp = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
const fmtDate = (d) => {
    if (!d) return '—';
    try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return d; }
};
const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };

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
};
const statusTagihan = (t) => (t.lewat_tempo ? TAGIHAN_STATUS.Lewat : (TAGIHAN_STATUS[t.status] ?? TAGIHAN_STATUS.Unpaid));

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
                <p className="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-tight tabular-nums">{value}</p>
                {sub && <p className="text-[10px] text-stone-400 truncate">{sub}</p>}
            </div>
        </div>
    );
}

/* Tombol kuitansi. Sengaja pakai <button> + window.open, bukan anchor,
   karena kuitansi dibuka di tab baru dan tidak perlu di-crawl. */
function TombolKuitansi({ id }) {
    const buka = () => {
        const url = safeRoute('santri.keuangan.kwitansi', { pembayaran: id });
        if (url !== '#') window.open(url, '_blank', 'noopener,noreferrer');
    };

    return (
        <button
            type="button"
            onClick={buka}
            title="Lihat kuitansi"
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
function IsiCaraBayar({ rekening = [], waAdmin, santri, sisa, judul }) {
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
        const teks =
            `Assalamu'alaikum, saya wali dari:\n\n` +
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
                            Setelah transfer, kirim bukti lewat WhatsApp. Status lunas diperbarui
                            setelah bendahara memverifikasi.
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

/* Modal pembayaran. Dipakai dua pemicu: total tunggakan dan per tagihan. */
function ModalBayar({ data, onClose, rekening, waAdmin, santri }) {
    const terbuka = Boolean(data);
    // onClose disimpan di ref: kalau jadi dependency, listener Escape & kunci
    // scroll dilepas-pasang setiap parent render (onClose = arrow function baru).
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

    if (!data) return null;

    return (
        <div className="fixed inset-0 z-[200] bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4" onClick={onClose}>
            <div onClick={(e) => e.stopPropagation()} className="w-full sm:max-w-2xl bg-white dark:bg-coal-700 rounded-t-2xl sm:rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div className="flex items-center justify-between px-5 py-4 border-b border-cream-300 dark:border-coal-600 sticky top-0 bg-white dark:bg-coal-700">
                    <div className="min-w-0">
                        <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 flex items-center gap-2">
                            <span className="w-7 h-7 rounded-lg grid place-items-center bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400">
                                <Icon path={P.bank} className="w-3.5 h-3.5" />
                            </span>
                            Cara Pembayaran
                        </h3>
                        <p className="text-[11px] text-stone-400 dark:text-stone-500 mt-0.5 truncate">{data.judul}</p>
                    </div>
                    <button onClick={onClose} className="w-8 h-8 rounded-lg grid place-items-center bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition shrink-0">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="1.7" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div className="p-4 sm:p-5">
                    <IsiCaraBayar rekening={rekening} waAdmin={waAdmin} santri={santri} sisa={data.sisa} judul={data.judul} />
                </div>
            </div>
        </div>
    );
}

export default function Index({ santri = null, ringkasan = null, tagihan = [], pembayaran = [], rekening = [], waAdmin = null, message = null }) {
    const [mobileTab, setMobileTab] = useState('tagihan');
    const [bayar, setBayar] = useState(null); // { judul, sisa } — null = modal tertutup

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
                    {/* Santri info */}
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
                                <button
                                    type="button"
                                    onClick={() => setBayar({ judul: 'Seluruh tunggakan', sisa: r.total_tunggakan ?? 0 })}
                                    className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold transition-colors shadow-sm active:scale-95"
                                >
                                    <Icon path={P.qr} className="w-4 h-4" /> Bayar Sekarang
                                </button>
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
                        <StatCard tone="emerald" icon={<Icon path={P.check} className="w-5 h-5" />} label="Sudah Lunas"   value={tagihan.filter((t) => t.status === 'Paid').length} sub="tagihan selesai" />
                        <StatCard tone="red"     icon={<Icon path={P.alert} className="w-5 h-5" />} label="Lewat Tempo"   value={r.jumlah_lewat ?? 0} sub="perlu segera dibayar" />
                        <StatCard tone="amber"   icon={<Icon path={P.clock} className="w-5 h-5" />} label="Menunggu"      value={r.menunggu_verifikasi ?? 0} sub="belum diverifikasi" />
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
                            Pembayaran <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{pembayaran.length}</span>
                        </button>
                    </div>

                    {/* Panels */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                        <TagihanPanel
                            className={mobileTab === 'tagihan' ? 'lg:block' : 'hidden lg:block'}
                            rows={tagihan}
                            onBayar={rekening.length ? setBayar : null}
                        />
                        <PembayaranPanel className={mobileTab === 'bayar' ? 'lg:block' : 'hidden lg:block'} rows={pembayaran} />
                    </div>
                </div>
            </div>

            <ModalBayar
                data={bayar}
                onClose={() => setBayar(null)}
                rekening={rekening}
                waAdmin={waAdmin}
                santri={santri}
            />
        </SantriAuthenticatedLayout>
    );
}

const Th = ({ children, className = '' }) => (
    <th className={`px-3 py-2.5 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400 ${className}`}>{children}</th>
);
const Td = ({ children, className = '' }) => <td className={`px-3 py-2.5 text-xs ${className}`}>{children}</td>;

// ── Panel Tagihan ──
function TagihanPanel({ rows, className = '', onBayar = null }) {
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
                <span className="text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/20">{rows.length} tagihan</span>
            </div>

            {rows.length === 0 ? (
                <Empty text="Belum ada tagihan" subtext="Tagihan akan muncul setelah diterbitkan pesantren" />
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
                                                    <p className="font-bold text-stone-900 dark:text-stone-100 tabular-nums">{rp(t.sisa)}</p>
                                                    <p className="text-[10px] text-stone-400 dark:text-stone-500 tabular-nums">dari {rp(t.total)}</p>
                                                </Td>
                                                <Td className="text-center">
                                                    <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold ring-1 whitespace-nowrap ${st.chip}`}>{st.label}</span>
                                                </Td>
                                            </tr>

                                            {aktif && (
                                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                                    <td colSpan={3} className="px-3 py-3">
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
                                                            {onBayar && t.status !== 'Paid' && (
                                                                <button
                                                                    type="button"
                                                                    onClick={(e) => { e.stopPropagation(); onBayar({ judul: t.judul, sisa: t.sisa }); }}
                                                                    className="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 text-white text-[11px] font-semibold transition-colors active:scale-95"
                                                                >
                                                                    <Icon path={P.qr} className="w-3.5 h-3.5" /> Bayar {rp(t.sisa)}
                                                                </button>
                                                            )}
                                                        </div>
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

// ── Panel Pembayaran ──
function PembayaranPanel({ rows, className = '' }) {
    const pg = usePaged(rows, 10);

    return (
        <div className={`bg-white dark:bg-coal-700 rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 sm:px-5 py-3.5 flex items-center justify-between border-b border-cream-300 dark:border-coal-600">
                <div className="flex items-center gap-2.5">
                    <span className="w-8 h-8 rounded-lg grid place-items-center bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                        <Icon path={P.coins} className="w-4 h-4" />
                    </span>
                    <div>
                        <h4 className="font-bold tracking-tight text-sm text-stone-900 dark:text-stone-100">Riwayat Pembayaran</h4>
                        <p className="text-[10px] text-stone-400 dark:text-stone-500 leading-tight">Kuitansi terbit setelah disahkan</p>
                    </div>
                </div>
                <span className="text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20">{rows.length} transaksi</span>
            </div>

            {rows.length === 0 ? (
                <Empty text="Belum ada pembayaran" subtext="Riwayat akan muncul setelah pembayaran dicatat" />
            ) : (
                <>
                    <div className="overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                    <Th className="w-24">Tanggal</Th>
                                    <Th className="text-right">Nominal</Th>
                                    <Th className="w-28 text-center">Status</Th>
                                    <Th className="w-14 text-center">Bukti</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                {pg.slice.map((b) => (
                                    <tr key={b.id} className="hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors">
                                        <Td className="text-stone-500 dark:text-stone-400 whitespace-nowrap tabular-nums">{fmtDate(b.tanggal)}</Td>
                                        <Td className="text-right">
                                            <p className="font-bold text-stone-900 dark:text-stone-100 tabular-nums">{rp(b.nominal)}</p>
                                            <p className="text-[10px] text-stone-400 dark:text-stone-500">{b.metode}</p>
                                        </Td>
                                        <Td className="text-center">
                                            <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold ring-1 whitespace-nowrap ${b.sah ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20'}`}>
                                                {b.status}
                                            </span>
                                        </Td>
                                        <Td className="text-center">
                                            {b.sah
                                                ? <TombolKuitansi id={b.id} />
                                                : <span className="text-[10px] text-stone-300 dark:text-stone-600">−</span>}
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination pg={pg} />
                </>
            )}
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
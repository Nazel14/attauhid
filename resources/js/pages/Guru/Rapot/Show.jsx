import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line, konsisten dengan Dashboard
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const XIcon = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const DownloadIcon = (p) => <I {...p} d={<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />} />;
const FilePdfIcon = (p) => <I {...p} d={<path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />} />;
const FileTextIcon = (p) => <I {...p} d={<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />} />;
const ClockIcon = (p) => <I {...p} d={<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const InfoIcon = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const BookIcon = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const CheckBadgeIcon = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const LayersIcon = (p) => <I {...p} d={<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />} />;

/* ============================================================
 * TOKEN DESAIN — sama dengan Dashboard / Input / Kelola Siswa
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const SUB = 'bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600 rounded-xl';
const TILE = 'w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const GENDER_AVATAR = {
    P: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400',
    L: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400',
};
// Kategori — sama dengan Riwayat Setoran di halaman Input
const KATEGORI = {
    Hafalan:  { dot: 'bg-emerald-500', text: 'text-emerald-700 dark:text-emerald-400', chip: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-700 dark:text-emerald-400' },
    Murajaah: { dot: 'bg-amber-500',   text: 'text-amber-700 dark:text-amber-400',     chip: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-700 dark:text-amber-400' },
    Tilawah:  { dot: 'bg-orange-500',  text: 'text-orange-700 dark:text-orange-400',   chip: 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20 text-orange-700 dark:text-orange-400' },
};
// Status presensi — sama dengan STATUS/MiniStat di Dashboard
const PRESENSI = {
    Hadir: { box: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
    Sakit: { box: 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20 text-orange-600 dark:text-orange-400', dot: 'bg-orange-500' },
    Izin:  { box: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-600 dark:text-amber-400', dot: 'bg-amber-500' },
    Alpha: { box: 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-600 dark:text-red-400', dot: 'bg-red-500' },
};

const storageUrl = (path) => (path ? `/storage/${path}` : null);

/* ============================================================
 * FORMAT HELPERS
 * ============================================================ */
const BULAN_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const formatTanggalID = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return `${d.getDate()} ${BULAN_ID[d.getMonth()]} ${d.getFullYear()}`;
};

// Range setoran: dukung "Iqro Jilid X" & "Aisar Jilid X", selain surat Al-Qur'an
function formatRapotRange(data) {
    const suratStart = String(data.surat_start ?? '');
    const m = suratStart.match(/^(Iqro|Aisar) Jilid (\d+)/i);
    if (m) {
        const jilid = m[2];
        const h1 = data.ayat_start_min;
        const h2 = data.ayat_end_max;
        const sep = h1 === h2 ? '' : `–${h2}`;
        return `${m[1]} Jilid ${jilid} · Hal ${h1}${sep}`;
    }
    let range = data.surat_start;
    if (data.surat_start !== data.surat_end) range += ' - ' + data.surat_end;
    range += ` (${data.ayat_start_min}-${data.ayat_end_max})`;
    return range;
}

/* ============================================================
 * KOMPONEN KECIL
 * ============================================================ */
function SectionHead({ icon, title, extra = null }) {
    return (
        <div className="flex items-center gap-2 mb-4">
            <span className={TILE}>{icon}</span>
            <h3 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">{title}</h3>
            {extra}
        </div>
    );
}

// Bar besar — warna sama dengan ProgressBar Dashboard (aksen / emerald)
function BigProgress({ label, value, color = 'bg-claude-500', textColor = 'text-claude-600 dark:text-claude-400', left, right }) {
    const v = Math.min(Number(value) || 0, 100);
    return (
        <div>
            <div className="flex justify-between items-end mb-2">
                <span className="text-xs text-stone-500 dark:text-stone-400">{label}</span>
                <span className={`text-xl font-bold tracking-tight tabular-nums ${textColor}`}>{value}%</span>
            </div>
            <div className="w-full h-2 rounded-full bg-cream-300 dark:bg-coal-600 overflow-hidden">
                <div className={`h-full rounded-full ${color} transition-all duration-500`} style={{ width: `${v}%` }} />
            </div>
            {(left || right) && (
                <div className="flex justify-between text-[11px] text-stone-400 mt-1.5">
                    <span>{left}</span><span>{right}</span>
                </div>
            )}
        </div>
    );
}

function StatTile({ value, label, sub }) {
    return (
        <div className={`${SUB} p-3`}>
            <p className="text-2xl font-bold tracking-tight leading-none text-stone-900 dark:text-stone-100 tabular-nums">{value}</p>
            <p className="text-[11px] text-stone-500 dark:text-stone-400 mt-1.5">{label}</p>
            {sub && <p className="text-[10px] text-stone-400">{sub}</p>}
        </div>
    );
}

/* ============================================================
 * PAGINATION BUTTON CLASSES
 * ============================================================ */
const pgBtnNormal = 'px-2.5 py-1 text-xs rounded-md font-medium text-stone-500 dark:text-stone-400 hover:bg-cream-200 dark:hover:bg-coal-600 transition-colors';
const pgBtnActive = 'px-2.5 py-1 text-xs rounded-md font-semibold bg-claude-500 text-white';
const pgNavBtn = 'px-2 py-1 text-xs rounded-md text-stone-500 dark:text-stone-400 hover:bg-cream-200 dark:hover:bg-coal-600 cursor-pointer';
const pgDisabledBtn = 'px-2 py-1 text-xs rounded-md text-stone-300 dark:text-stone-600 cursor-not-allowed';

/* ============================================================
 * CategoryTable — tabel riwayat setoran per kategori (paginated)
 * ============================================================ */
function CategoryTable({ title, entries, perPage, emptyText }) {
    const [page, setPage] = useState(1);
    useEffect(() => { setPage(1); }, [entries]);
    const k = KATEGORI[title] ?? KATEGORI.Hafalan;

    const head = (count) => (
        <div className="px-4 py-2.5 flex items-center gap-2 border-b border-cream-300 dark:border-coal-600">
            <span className={`w-2 h-2 rounded-full ${k.dot}`} />
            <h4 className={`text-sm font-bold tracking-tight ${k.text}`}>{title}</h4>
            {count != null && (
                <span className={`ml-auto inline-flex items-center px-2 py-0.5 rounded-md ring-1 text-[10px] font-semibold ${k.chip}`}>{count} surat</span>
            )}
        </div>
    );

    if (entries.length === 0) {
        return (
            <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden">
                {head(null)}
                <div className="py-8 flex flex-col items-center text-center">
                    <FileTextIcon className="w-7 h-7 text-stone-300 dark:text-stone-600 mb-1.5" />
                    <p className="text-[11px] text-stone-400">{emptyText}</p>
                </div>
            </div>
        );
    }

    const totalRows = entries.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
    const start = (page - 1) * perPage;
    const rows = entries.slice(start, start + perPage);
    const from = Math.min(start + 1, totalRows);
    const to = Math.min(start + perPage, totalRows);

    let startP = Math.max(1, page - 2);
    let endP = Math.min(totalPages, startP + 4);
    if (endP - startP < 4) startP = Math.max(1, endP - 4);
    const pageNums = [];
    for (let i = startP; i <= endP; i++) pageNums.push(i);

    const nilaiBadge = (n) =>
        n >= 90 ? 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-700 dark:text-emerald-400'
        : n >= 75 ? 'bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 text-stone-700 dark:text-stone-200'
        : 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-700 dark:text-amber-400';

    return (
        <div className="rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden">
            {head(totalRows)}
            <div className="overflow-x-auto">
                <table className="min-w-full">
                    <thead className="bg-cream-100 dark:bg-coal-600/40">
                        <tr>
                            <th className="px-3 py-2 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400 w-10">No</th>
                            <th className="px-3 py-2 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400">Surah & Ayat</th>
                            <th className="px-3 py-2 text-center text-[11px] font-semibold text-stone-500 dark:text-stone-400">Jml</th>
                            <th className="px-3 py-2 text-center text-[11px] font-semibold text-stone-500 dark:text-stone-400">Nilai</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                        {rows.map(([suratKey, data], i) => (
                            <tr key={suratKey} className="hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors">
                                <td className="px-3 py-2.5 text-xs text-stone-400 tabular-nums">{start + i + 1}</td>
                                <td className="px-3 py-2.5 text-xs text-stone-700 dark:text-stone-200">{formatRapotRange(data)}</td>
                                <td className="px-3 py-2.5 text-center">
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-md ring-1 text-[10px] font-semibold tabular-nums bg-cream-200 dark:bg-coal-600 ring-cream-300 dark:ring-coal-500 text-stone-600 dark:text-stone-300">
                                        {data.jumlah_setoran}x
                                    </span>
                                </td>
                                <td className="px-3 py-2.5 text-center">
                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-md ring-1 text-[10px] font-bold tabular-nums ${nilaiBadge(data.rata_nilai)}`}>
                                        {data.rata_nilai}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {totalPages > 1 && (
                <div className="px-4 py-2 border-t border-cream-300 dark:border-coal-600 bg-cream-100 dark:bg-coal-600/40 flex items-center justify-between">
                    <span className="text-[11px] text-stone-500 dark:text-stone-400 tabular-nums">{from}–{to} dari {totalRows}</span>
                    <div className="flex items-center gap-1">
                        <button disabled={page === 1} onClick={() => setPage(page - 1)} className={page === 1 ? pgDisabledBtn : pgNavBtn}>‹ Prev</button>
                        {startP > 1 && (
                            <>
                                <button onClick={() => setPage(1)} className={pgBtnNormal}>1</button>
                                {startP > 2 && <span className="text-xs text-stone-400">…</span>}
                            </>
                        )}
                        {pageNums.map((i) => (
                            <button key={i} onClick={() => setPage(i)} className={i === page ? pgBtnActive : pgBtnNormal}>{i}</button>
                        ))}
                        {endP < totalPages && (
                            <>
                                {endP < totalPages - 1 && <span className="text-xs text-stone-400">…</span>}
                                <button onClick={() => setPage(totalPages)} className={pgBtnNormal}>{totalPages}</button>
                            </>
                        )}
                        <button disabled={page === totalPages} onClick={() => setPage(page + 1)} className={page === totalPages ? pgDisabledBtn : pgNavBtn}>Next ›</button>
                    </div>
                </div>
            )}
        </div>
    );
}

/* ============================================================
 * MAIN PAGE — Guru / Rapot / Show
 * ============================================================ */
export default function Show({
    santri, setorans = {}, statistikSetoran, statistikPresensi,
    tanggalMulai, tanggalAkhir, tahunAjaranAktif, isHistori = false,
    kelasKonteks, pdfBoleh = true, pdfBatasFormatted = null,
    backUrl, backLabel = 'Kembali ke Rapot',
}) {
    const [showPdfPreview, setShowPdfPreview] = useState(false);
    const [pdfLoading, setPdfLoading] = useState(true);
    const pdfDownloadUrl = route('guru.rapot.pdf', { santri: santri.id });
    const pdfPreviewUrl = route('guru.rapot.pdf', { santri: santri.id, preview: 1 });

    const openPdfPreview = () => { setPdfLoading(true); setShowPdfPreview(true); document.body.style.overflow = 'hidden'; };
    const closePdfPreview = () => { setShowPdfPreview(false); document.body.style.overflow = 'auto'; };
    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') closePdfPreview(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, []);

    const g = (santri.gender === 'P' || santri.gender === 'Perempuan') ? 'P' : 'L';
    const photo = santri.user?.profile_photo ? storageUrl(santri.user.profile_photo) : null;

    const dMulai = new Date(tanggalMulai);
    const dAkhir = new Date(tanggalAkhir);
    const awal = dMulai <= dAkhir ? tanggalMulai : tanggalAkhir;
    const akhir = dMulai <= dAkhir ? tanggalAkhir : tanggalMulai;

    const hafalanEntries = Object.entries(setorans.Hafalan || {});
    const murajaahEntries = Object.entries(setorans.Murajaah || {});
    const tilawahEntries = Object.entries(setorans.Tilawah || {});
    const totalKategori = hafalanEntries.length + murajaahEntries.length + tilawahEntries.length;

    const totalHari = statistikPresensi.total_hari;
    const pctHari = (v) => (totalHari > 0 ? Math.round((v / totalHari) * 1000) / 10 : 0);
    const presensiItems = [
        { label: 'Hadir', val: statistikPresensi.hadir },
        { label: 'Sakit', val: statistikPresensi.sakit },
        { label: 'Izin',  val: statistikPresensi.izin },
        { label: 'Alpha', val: statistikPresensi.alpha },
    ];

    return (
        <GuruAuthenticatedLayout>
            <Head title={`Rapot ${santri.name}`} />

            {/* Padding dibuang — <main> di layout sudah memberinya (sama seperti Dashboard). */}
            <div className="max-w-7xl mx-auto">

                {/* Kembali — label dinamis sesuai jalur masuk (Rapot / Riwayat Santri) */}
                <Link href={backUrl} className={`${BTN_SOFT} mb-4`}>
                    <ArrowLeftIcon className="w-4 h-4" /> {backLabel}
                </Link>

                {/* ===== HEADER ===== */}
                <div className="flex flex-wrap items-center gap-3 mb-5">
                    <div className="relative flex-shrink-0">
                        {photo ? (
                            <img src={photo} alt="" className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600" />
                        ) : (
                            <span className={`w-11 h-11 sm:w-12 sm:h-12 rounded-2xl ring-1 flex items-center justify-center text-base font-bold ${GENDER_AVATAR[g]}`}>
                                {santri.name?.charAt(0).toUpperCase()}
                            </span>
                        )}
                        <span className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-claude-500 ring-2 ring-white dark:ring-coal-700 flex items-center justify-center text-white">
                            <BookIcon className="w-2.5 h-2.5" />
                        </span>
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Rapot {santri.name}</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            <span className="font-mono">{santri.nis}</span>
                            <span className="mx-1 opacity-40">·</span>
                            Kelas <span className="font-semibold text-claude-600 dark:text-claude-400">{santri.kelas?.name ?? '-'}</span>
                            <span className="mx-1 opacity-40">·</span>{santri.guru?.name ?? '-'}
                        </p>
                    </div>
                    {tahunAjaranAktif && (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-[11px] font-medium text-stone-600 dark:text-stone-300">
                            <CalendarIcon className="w-3.5 h-3.5 text-stone-400" />
                            {tahunAjaranAktif.nama || tahunAjaranAktif.name}
                            {tahunAjaranAktif.semester && ` · ${tahunAjaranAktif.semester == 2 ? 'Genap' : 'Ganjil'}`}
                            {isHistori ? (
                                <span className="px-1.5 py-px rounded-full bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 text-[10px] font-semibold">Riwayat</span>
                            ) : (
                                <span className="px-1.5 py-px rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 text-[10px] font-semibold">Aktif</span>
                            )}
                        </span>
                    )}
                </div>

                {isHistori && kelasKonteks && (
                    <div className="bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 rounded-xl px-3.5 py-3 mb-4 flex items-start gap-2.5">
                        <ClockIcon className="w-4 h-4 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <p className="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                            Ini rapot <strong>riwayat</strong>. Data dikunci pada periode saat santri masih di <strong>{kelasKonteks.name ?? '-'}</strong> ({formatTanggalID(awal)} – {formatTanggalID(akhir)}). Filter periode dinonaktifkan.
                        </p>
                    </div>
                )}

                {/* ===== INFO PERIODE + AKSI PDF ===== */}
                <div className={`${CARD} p-4 sm:p-5 mb-4 flex flex-col sm:flex-row sm:items-center gap-3`}>
                    <div className="flex items-start gap-2.5 flex-1 min-w-0">
                        <span className={TILE}><CalendarIcon className="w-3.5 h-3.5" /></span>
                        <div className="min-w-0">
                            <p className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">{formatTanggalID(awal)} – {formatTanggalID(akhir)}</p>
                            <p className="text-[11px] text-stone-500 dark:text-stone-400">Menampilkan seluruh setoran & presensi pada periode ini</p>
                        </div>
                    </div>
                    {pdfBoleh ? (
                        <div className="sm:text-right">
                            <button type="button" onClick={openPdfPreview}
                                className="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold rounded-xl transition active:scale-95 w-full sm:w-auto">
                                <FilePdfIcon className="w-3.5 h-3.5" /> {isHistori ? 'Lihat / Unduh PDF Riwayat' : 'Lihat / Unduh PDF Rapot'}
                            </button>
                            {isHistori && pdfBatasFormatted && (
                                <p className="text-[11px] text-stone-400 mt-1.5 sm:max-w-xs">PDF dapat diunduh hingga {pdfBatasFormatted} (1 minggu setelah semester selesai). Setelahnya, statistik tetap bisa dilihat tanpa batas waktu.</p>
                            )}
                        </div>
                    ) : (
                        <div className={`${SUB} px-3 py-2.5 flex items-start gap-2 sm:max-w-sm`}>
                            <InfoIcon className="w-4 h-4 text-stone-400 flex-shrink-0 mt-0.5" />
                            <p className="text-xs text-stone-600 dark:text-stone-400">Masa unduh PDF rapot sudah berakhir{pdfBatasFormatted ? ` (batas: ${pdfBatasFormatted})` : ''}. Statistik di bawah tetap dapat dilihat.</p>
                        </div>
                    )}
                </div>

                {/* ===== STATISTIK CARDS ===== */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

                    {/* Card Statistik Setoran */}
                    <div className={`${CARD} p-4 sm:p-5`}>
                        <SectionHead icon={<BookIcon className="w-3.5 h-3.5" />} title="Statistik Setoran" />

                        <div className="mb-4 space-y-4">
                            {statistikSetoran.is_iqro ? (
                                <>
                                    {statistikSetoran.iqro_persen_diketahui ? (
                                        <BigProgress
                                            label={`Pencapaian Target ${statistikSetoran.metode_label ?? 'Iqro'}`}
                                            value={statistikSetoran.progress_iqro}
                                            left={`Disetor: ${statistikSetoran.total_halaman_iqro} halaman`}
                                            right={`Target Jilid ${statistikSetoran.target_jilid}: ${statistikSetoran.total_halaman_jilid} halaman`}
                                        />
                                    ) : (
                                        <div>
                                            <div className="flex justify-between items-end mb-2">
                                                <span className="text-xs text-stone-500 dark:text-stone-400">Pencapaian Target {statistikSetoran.metode_label ?? 'Iqro'}</span>
                                                <span className="text-xl font-bold tracking-tight tabular-nums text-claude-600 dark:text-claude-400">{statistikSetoran.progress_iqro}%</span>
                                            </div>
                                            <div className={`${SUB} px-3.5 py-2.5`}>
                                                <p className="text-xs text-stone-500 italic">Jumlah halaman Jilid {statistikSetoran.target_jilid} belum diatur di konfigurasi</p>
                                            </div>
                                        </div>
                                    )}

                                    {statistikSetoran.has_quran_secondary && (
                                        <div className="pt-4 border-t border-cream-300 dark:border-coal-600">
                                            <BigProgress
                                                label="Pencapaian Target Al-Qur'an"
                                                value={statistikSetoran.persentase_target}
                                                color="bg-emerald-500"
                                                textColor="text-emerald-600 dark:text-emerald-400"
                                                left={`Tercapai: ${statistikSetoran.total_juz_hafalan} Juz`}
                                                right={`Target: ${statistikSetoran.target_juz} Juz`}
                                            />
                                        </div>
                                    )}
                                </>
                            ) : (
                                <BigProgress
                                    label="Pencapaian Target Hafalan"
                                    value={statistikSetoran.persentase_target}
                                    left={`Tercapai: ${statistikSetoran.total_juz} Juz`}
                                    right={`Target: ${statistikSetoran.target_juz} Juz`}
                                />
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-2 mb-4">
                            <StatTile value={statistikSetoran.total_setoran} label="Total Setoran" />
                            <StatTile value={statistikSetoran.rata_nilai} label="Rata-rata Nilai" />
                            {statistikSetoran.is_iqro ? (
                                <StatTile
                                    value={statistikSetoran.total_halaman_iqro}
                                    label="Total Halaman"
                                    sub={statistikSetoran.has_quran_secondary ? `+ ${statistikSetoran.total_juz_hafalan} Juz` : null}
                                />
                            ) : (
                                <StatTile value={statistikSetoran.total_juz} label="Total Juz" />
                            )}
                            <StatTile value={Number(statistikSetoran.total_kata).toLocaleString('id-ID')} label="Total Kata" />
                        </div>

                        <div className="pt-4 border-t border-cream-300 dark:border-coal-600">
                            <p className="text-xs font-semibold text-stone-600 dark:text-stone-300 mb-2">Kategori Setoran</p>
                            <div className="space-y-1.5">
                                {[
                                    ['Hafalan', statistikSetoran.hafalan],
                                    ['Murajaah', statistikSetoran.murajaah],
                                    ['Tilawah', statistikSetoran.tilawah],
                                ].map(([label, val]) => (
                                    <div key={label} className={`${SUB} px-3 py-2 flex items-center justify-between`}>
                                        <span className="flex items-center gap-2 text-xs text-stone-700 dark:text-stone-300">
                                            <span className={`w-2 h-2 rounded-full ${KATEGORI[label].dot}`} />{label}
                                        </span>
                                        <span className={`text-xs font-bold tabular-nums ${KATEGORI[label].text}`}>{val}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Card Statistik Presensi */}
                    <div className={`${CARD} p-4 sm:p-5`}>
                        <SectionHead icon={<CheckBadgeIcon className="w-3.5 h-3.5" />} title="Statistik Presensi" />

                        <div className="mb-4">
                            <BigProgress
                                label="Persentase Kehadiran"
                                value={statistikPresensi.persentase_kehadiran}
                                color="bg-emerald-500"
                                textColor="text-emerald-600 dark:text-emerald-400"
                                left={`Hadir: ${statistikPresensi.hadir}`}
                                right={`Total: ${statistikPresensi.total_hari} hari`}
                            />
                        </div>

                        {/* Kotak status — sama dengan MiniStat di Dashboard */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                            {presensiItems.map((it) => (
                                <div key={it.label} className={`rounded-xl ring-1 p-3 text-center ${PRESENSI[it.label].box}`}>
                                    <p className="text-2xl font-bold tracking-tight leading-none tabular-nums">{it.val}</p>
                                    <p className="mt-1.5 flex items-center justify-center gap-1 text-[11px] font-medium">
                                        <span className={`w-1.5 h-1.5 rounded-full ${PRESENSI[it.label].dot}`} /> {it.label}
                                    </p>
                                </div>
                            ))}
                        </div>

                        <div className="pt-4 border-t border-cream-300 dark:border-coal-600">
                            <p className="text-xs font-semibold text-stone-600 dark:text-stone-300 mb-2">Distribusi Kehadiran</p>
                            <div className="space-y-2.5">
                                {presensiItems.map((it) => {
                                    const p = pctHari(it.val);
                                    return (
                                        <div key={it.label}>
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="flex items-center gap-1.5 text-[11px] text-stone-500 dark:text-stone-400">
                                                    <span className={`w-1.5 h-1.5 rounded-full ${PRESENSI[it.label].dot}`} />{it.label}
                                                </span>
                                                <span className="text-[11px] font-semibold tabular-nums text-stone-600 dark:text-stone-300">{it.val} <span className="text-stone-400 font-normal">({p}%)</span></span>
                                            </div>
                                            <div className="w-full h-1.5 rounded-full bg-cream-300 dark:bg-coal-600 overflow-hidden">
                                                <div className={`h-full rounded-full ${PRESENSI[it.label].dot} transition-all duration-500`} style={{ width: `${p}%` }} />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                            <div className="mt-4 px-3.5 py-2.5 rounded-xl ring-1 ring-claude-200 dark:ring-claude-500/20 bg-claude-50 dark:bg-claude-500/10 flex items-center justify-between">
                                <span className="text-xs font-semibold text-stone-700 dark:text-stone-300">Total Hari Presensi</span>
                                <span className="text-xl font-bold tracking-tight tabular-nums text-claude-600 dark:text-claude-400">{statistikPresensi.total_hari}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ===== RIWAYAT SETORAN ===== */}
                <div className={`${CARD} p-4 sm:p-5 mb-4`}>
                    <SectionHead icon={<LayersIcon className="w-3.5 h-3.5" />} title="Riwayat Setoran per Kategori"
                        extra={<span className="ml-auto text-[11px] text-stone-400">{totalKategori} surat</span>} />

                    {totalKategori > 0 ? (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <CategoryTable title="Hafalan" entries={hafalanEntries} perPage={15} emptyText="Tidak ada data hafalan" />
                            <div className="space-y-4">
                                <CategoryTable title="Murajaah" entries={murajaahEntries} perPage={6} emptyText="Tidak ada data murajaah" />
                                <CategoryTable title="Tilawah" entries={tilawahEntries} perPage={6} emptyText="Tidak ada data tilawah" />
                            </div>
                        </div>
                    ) : (
                        <div className="py-14 flex flex-col items-center text-center">
                            <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                                <FileTextIcon className="w-5 h-5" />
                            </span>
                            <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">Tidak ada data setoran dalam periode ini</p>
                        </div>
                    )}
                </div>
            </div>

            {/* ===== PDF PREVIEW MODAL ===== */}
            {showPdfPreview && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 overflow-y-auto" onClick={(e) => { if (e.target === e.currentTarget) closePdfPreview(); }}>
                    <div className="min-h-screen px-3 py-6 sm:px-6 sm:py-8">
                        <div className={`relative ${CARD} shadow-2xl max-w-6xl mx-auto overflow-hidden`}>
                            <div className="sticky top-0 z-10 flex items-center gap-2 px-4 py-3.5 border-b border-cream-300 dark:border-coal-600 bg-white dark:bg-coal-700">
                                <span className={TILE}><FilePdfIcon className="w-3.5 h-3.5" /></span>
                                <h3 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-100 flex-1 min-w-0 truncate">
                                    Preview PDF — {santri.name}
                                </h3>
                                <a href={pdfDownloadUrl} className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold rounded-lg transition active:scale-95">
                                    <DownloadIcon className="w-3.5 h-3.5" /> Download
                                </a>
                                <button onClick={closePdfPreview} className="w-7 h-7 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 hover:bg-cream-300 dark:hover:bg-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-300 transition">
                                    <XIcon className="w-4 h-4" />
                                </button>
                            </div>
                            <div className="p-4 relative">
                                {pdfLoading && (
                                    <div className={`absolute inset-4 ${SUB} flex flex-col items-center justify-center gap-3 z-10`}>
                                        <span className="w-9 h-9 rounded-full border-[3px] border-cream-300 dark:border-coal-500 border-t-claude-500 animate-spin" />
                                        <p className="text-xs text-stone-500 dark:text-stone-400">Menyiapkan PDF… (bisa memakan beberapa detik)</p>
                                        <a href={pdfPreviewUrl} target="_blank" rel="noreferrer"
                                            className="text-[11px] font-semibold text-claude-600 dark:text-claude-400 hover:underline">
                                            Buka di tab baru
                                        </a>
                                    </div>
                                )}
                                <iframe
                                    src={pdfPreviewUrl}
                                    onLoad={() => setPdfLoading(false)}
                                    className="w-full rounded-lg ring-1 ring-cream-300 dark:ring-coal-600"
                                    style={{ height: '75vh' }}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </GuruAuthenticatedLayout>
    );
}
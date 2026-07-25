import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * ICONS (inline, tanpa dependency)
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const XIcon = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const SearchIcon = (p) => <I {...p} d={<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />} />;
const DownloadIcon = (p) => <I {...p} d={<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />} />;
const FilePdfIcon = (p) => <I {...p} d={<path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />} />;
const FileTextIcon = (p) => <I {...p} d={<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />} />;
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />} />;
const ClockIcon = (p) => <I {...p} d={<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const InfoIcon = (p) => <I {...p} d={<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const BookIcon = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const CheckBadgeIcon = (p) => <I {...p} d={<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />} />;
const LayersIcon = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;

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
 * PAGINATION BUTTON CLASSES
 * ============================================================ */
const pgBtnNormal = "px-2.5 py-1 text-xs rounded-md font-medium text-stone-500 dark:text-stone-400 hover:bg-stone-200 dark:hover:bg-coal-700 transition-colors";
const pgBtnActive = "px-2.5 py-1 text-xs rounded-md font-medium bg-claude-600 text-white";
const pgNavBtn = "px-2 py-1 text-xs rounded-md text-stone-500 dark:text-stone-400 hover:bg-stone-200 dark:hover:bg-coal-700 cursor-pointer";
const pgDisabledBtn = "px-2 py-1 text-xs rounded-md text-stone-300 dark:text-coal-600 cursor-not-allowed";

/* ============================================================
 * CategoryTable — tabel riwayat setoran per kategori (paginated)
 * ============================================================ */
function CategoryTable({ title, entries, perPage, emptyText, headerBg, headerText, countBg }) {
    const [page, setPage] = useState(1);
    useEffect(() => { setPage(1); }, [entries]);

    if (entries.length === 0) {
        return (
            <div className="border border-stone-200 dark:border-coal-700 rounded-xl p-6 flex items-center justify-center">
                <div className="text-center">
                    <FileTextIcon className="w-9 h-9 text-stone-300 dark:text-coal-600 mb-1.5 mx-auto" />
                    <p className="text-xs text-stone-500 dark:text-stone-600">{emptyText}</p>
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

    return (
        <div className="border border-stone-200 dark:border-coal-700 rounded-xl overflow-hidden">
            <div className={`${headerBg} border-b px-4 py-2.5 flex items-center justify-between`}>
                <h4 className={`text-sm font-bold ${headerText}`}>{title}</h4>
                <span className={`text-[10px] ${countBg} px-2 py-0.5 rounded-full font-semibold`}>{totalRows} surat</span>
            </div>
            <div className="overflow-x-auto">
                <table className="min-w-full">
                    <thead className="bg-stone-50 dark:bg-coal-900/40">
                        <tr>
                            <th className="px-3 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">No</th>
                            <th className="px-3 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Surah & Ayat</th>
                            <th className="px-3 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Jml</th>
                            <th className="px-3 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Nilai</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-stone-200 dark:divide-coal-700/50">
                        {rows.map(([suratKey, data], i) => (
                            <tr key={suratKey} className="hover:bg-stone-50 dark:hover:bg-coal-700/30 transition-colors">
                                <td className="px-3 py-2.5 text-xs text-stone-500 dark:text-stone-600">{start + i + 1}</td>
                                <td className="px-3 py-2.5 text-xs text-stone-700 dark:text-stone-200">{formatRapotRange(data)}</td>
                                <td className="px-3 py-2.5 text-center">
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-claude-50 text-claude-700 border-claude-200/70 dark:bg-claude-500/10 dark:text-claude-400 dark:border-claude-500/20">
                                        {data.jumlah_setoran}x
                                    </span>
                                </td>
                                <td className="px-3 py-2.5 text-center">
                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${
                                        data.rata_nilai >= 90
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20'
                                            : data.rata_nilai >= 75
                                            ? 'bg-blue-50 text-blue-700 border-blue-200/70 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20'
                                            : 'bg-amber-50 text-amber-700 border-amber-200/70 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20'
                                    }`}>
                                        {data.rata_nilai}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {totalPages > 1 && (
                <div className="px-4 py-2 border-t border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40 flex items-center justify-between">
                    <span className="text-[11px] text-stone-500 dark:text-stone-600">{from}–{to} dari {totalRows}</span>
                    <div className="flex items-center gap-1">
                        <button disabled={page === 1} onClick={() => setPage(page - 1)} className={page === 1 ? pgDisabledBtn : pgNavBtn}>‹ Prev</button>
                        {startP > 1 && (
                            <>
                                <button onClick={() => setPage(1)} className={pgBtnNormal}>1</button>
                                {startP > 2 && <span className="text-xs text-stone-400 dark:text-stone-600">…</span>}
                            </>
                        )}
                        {pageNums.map((i) => (
                            <button key={i} onClick={() => setPage(i)} className={i === page ? pgBtnActive : pgBtnNormal}>{i}</button>
                        ))}
                        {endP < totalPages && (
                            <>
                                {endP < totalPages - 1 && <span className="text-xs text-stone-400 dark:text-stone-600">…</span>}
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

    const openPdfPreview = () => { setPdfLoading(true); setShowPdfPreview(true); document.body.style.overflow = 'hidden'; };    const closePdfPreview = () => { setShowPdfPreview(false); document.body.style.overflow = 'auto'; };
    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') closePdfPreview(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, []);

    const isPerempuan = santri.gender === 'P' || santri.gender === 'Perempuan';
    const photo = santri.user?.profile_photo ? storageUrl(santri.user.profile_photo) : null;

    const dMulai = new Date(tanggalMulai);
    const dAkhir = new Date(tanggalAkhir);
    const awal = dMulai <= dAkhir ? tanggalMulai : tanggalAkhir;
    const akhir = dMulai <= dAkhir ? tanggalAkhir : tanggalMulai;

    const hafalanEntries = Object.entries(setorans.Hafalan || {});
    const murajaahEntries = Object.entries(setorans.Murajaah || {});
    const tilawahEntries = Object.entries(setorans.Tilawah || {});
    const totalKategori = hafalanEntries.length + murajaahEntries.length + tilawahEntries.length;

    return (
        <GuruAuthenticatedLayout>
            <Head title={`Rapot ${santri.name}`} />

            <div className="pb-10">
                <div className="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

                    {/* Kembali — label dinamis sesuai jalur masuk (Rapot / Riwayat Santri) */}
                    <Link href={backUrl}
                        className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                        <ArrowLeftIcon className="w-4 h-4" /> {backLabel}
                    </Link>
                    <Link href={backUrl}
                        className="mb-4 hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                        <ArrowLeftIcon className="w-4 h-4" /> {backLabel}
                    </Link>

                    {/* ===== TOP BAR ===== */}
                    <div className="flex items-center gap-3 mb-4">
                        <div className="relative flex-shrink-0">
                            {photo ? (
                                <img src={photo} alt="" className="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover object-top border border-stone-200 dark:border-coal-700" />
                            ) : (
                                <span className={`w-11 h-11 sm:w-12 sm:h-12 rounded-full border flex items-center justify-center text-base font-bold ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                    {santri.name?.charAt(0).toUpperCase()}
                                </span>
                            )}
                            <span className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-gradient-to-br from-claude-500 to-claude-600 border-2 border-white dark:border-coal-900 flex items-center justify-center shadow">
                                <BookIcon className="w-2.5 h-2.5 text-white" />
                            </span>
                        </div>
                        <div className="flex-1 min-w-0">
                            <h1 className="text-base sm:text-xl font-bold text-stone-900 dark:text-stone-100 truncate">Rapot {santri.name}</h1>
                            <p className="text-[11px] text-stone-500 mt-0.5">{santri.nis} • {santri.kelas?.name ?? '-'} • {santri.guru?.name ?? '-'}</p>
                        </div>
                    </div>

                    {/* ===== BADGE TAHUN AJARAN ===== */}
                    {tahunAjaranAktif && (
                        <div className={`inline-flex items-center gap-2 px-3 py-1.5 border rounded-xl text-xs mb-4 ${isHistori ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/25' : 'bg-claude-50 dark:bg-claude-500/10 border-claude-200 dark:border-claude-500/25'}`}>
                            <CalendarIcon className={`w-3.5 h-3.5 flex-shrink-0 ${isHistori ? 'text-amber-500 dark:text-amber-400' : 'text-claude-500 dark:text-claude-400'}`} />
                            <span className="text-stone-900 dark:text-stone-100 font-medium">
                                {tahunAjaranAktif.nama || tahunAjaranAktif.name}
                                {tahunAjaranAktif.semester && ` · ${tahunAjaranAktif.semester == 2 ? 'Genap' : 'Ganjil'}`}
                            </span>
                            {isHistori ? (
                                <span className="px-1.5 py-0.5 bg-amber-500/20 border border-amber-500/30 text-amber-600 dark:text-amber-400 rounded-full text-[10px] font-semibold">Riwayat</span>
                            ) : (
                                <span className="px-1.5 py-0.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-full text-[10px] font-semibold">Aktif</span>
                            )}
                        </div>
                    )}

                    {isHistori && kelasKonteks && (
                        <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                            <ClockIcon className="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                            <p className="text-xs text-amber-700 dark:text-amber-300">
                                Ini rapot <strong>riwayat</strong>. Data dikunci pada periode saat santri masih di <strong>{kelasKonteks.name ?? '-'}</strong> ({formatTanggalID(awal)} – {formatTanggalID(akhir)}). Filter periode dinonaktifkan.
                            </p>
                        </div>
                    )}

                    {/* ===== INFO PERIODE + AKSI PDF ===== */}
                    <div className="mb-4">
                        <p className="text-[11px] text-stone-500 dark:text-stone-500 mb-2">
                            Menampilkan seluruh setoran & presensi periode{' '}
                            <span className="font-semibold text-stone-700 dark:text-stone-300">{formatTanggalID(awal)} – {formatTanggalID(akhir)}</span>
                        </p>
                        {pdfBoleh ? (
                            <>
                                <button type="button" onClick={openPdfPreview} className="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-red-700 hover:bg-red-600 border border-red-700 text-white text-xs font-bold rounded-xl transition active:scale-95">
                                    <FilePdfIcon className="w-3.5 h-3.5" /> {isHistori ? 'Lihat / Unduh PDF Riwayat' : 'Lihat / Unduh PDF Rapot'}
                                </button>
                                {isHistori && pdfBatasFormatted && (
                                    <p className="text-[10px] text-stone-400 dark:text-stone-600 mt-1.5">PDF dapat diunduh hingga {pdfBatasFormatted} (1 minggu setelah semester selesai). Setelahnya, statistik tetap bisa dilihat tanpa batas waktu.</p>
                                )}
                            </>
                        ) : (
                            <div className="bg-stone-50 dark:bg-coal-800/40 border border-stone-200 dark:border-coal-700/40 rounded-xl p-3 flex items-start gap-2.5">
                                <InfoIcon className="w-4 h-4 text-stone-400 flex-shrink-0 mt-0.5" />
                                <p className="text-xs text-stone-600 dark:text-stone-400">Masa unduh PDF rapot sudah berakhir{pdfBatasFormatted ? ` (batas: ${pdfBatasFormatted})` : ''}. Statistik di bawah tetap dapat dilihat.</p>
                            </div>
                        )}
                    </div>

                    {/* ===== STATISTIK CARDS ===== */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

                        {/* Card Statistik Setoran */}
                        <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 className="text-xs font-bold text-stone-600 dark:text-stone-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400"><BookIcon className="w-3.5 h-3.5" /></span>
                                Statistik Setoran
                            </h3>

                            <div className="mb-4">
                                {statistikSetoran.is_iqro ? (
                                    (() => {
                                        const pct = statistikSetoran.progress_iqro;
                                        const bar = pct >= 80 ? 'from-emerald-600 to-emerald-400' : pct >= 50 ? 'from-blue-600 to-blue-400' : pct >= 25 ? 'from-amber-600 to-amber-400' : 'from-red-600 to-red-400';
                                        const txt = pct >= 80 ? 'text-emerald-600 dark:text-emerald-400' : pct >= 50 ? 'text-blue-600 dark:text-blue-400' : pct >= 25 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400';
                                        return (
                                            <>
                                                <div className="flex justify-between items-center mb-2">
                                                    <span className="text-xs text-stone-500">Pencapaian Target {statistikSetoran.metode_label ?? 'Iqro'}</span>
                                                    <span className={`text-xl font-bold ${txt}`}>{pct}%</span>
                                                </div>
                                                {statistikSetoran.iqro_persen_diketahui ? (
                                                    <>
                                                        <div className="w-full bg-stone-200 dark:bg-coal-700 rounded-full h-3">
                                                            <div className={`bg-gradient-to-r ${bar} h-3 rounded-full transition-all duration-500`} style={{ width: `${pct}%` }} />
                                                        </div>
                                                        <div className="flex justify-between text-[10px] text-stone-500 dark:text-stone-600 mt-1">
                                                            <span>Disetor: {statistikSetoran.total_halaman_iqro} halaman</span>
                                                            <span>Target Jilid {statistikSetoran.target_jilid}: {statistikSetoran.total_halaman_jilid} halaman</span>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <div className="bg-stone-50 dark:bg-coal-900/40 border border-stone-200 dark:border-coal-700 rounded-xl px-4 py-2.5">
                                                        <p className="text-xs text-stone-500 italic">Jumlah halaman Jilid {statistikSetoran.target_jilid} belum diatur di konfigurasi</p>
                                                    </div>
                                                )}

                                                {statistikSetoran.has_quran_secondary && (
                                                    <div className="mt-4 pt-3 border-t border-stone-200 dark:border-coal-700">
                                                        <div className="flex justify-between items-center mb-2">
                                                            <span className="text-xs text-stone-500">Pencapaian Target Al-Qur'an</span>
                                                            <span className="text-xl font-bold text-emerald-600 dark:text-emerald-400">{statistikSetoran.persentase_target}%</span>
                                                        </div>
                                                        <div className="w-full bg-stone-200 dark:bg-coal-700 rounded-full h-3">
                                                            <div className="bg-gradient-to-r from-emerald-600 to-emerald-400 h-3 rounded-full transition-all duration-500" style={{ width: `${statistikSetoran.persentase_target}%` }} />
                                                        </div>
                                                        <div className="flex justify-between text-[10px] text-stone-500 dark:text-stone-600 mt-1">
                                                            <span>Tercapai: {statistikSetoran.total_juz_hafalan} Juz</span>
                                                            <span>Target: {statistikSetoran.target_juz} Juz</span>
                                                        </div>
                                                    </div>
                                                )}
                                            </>
                                        );
                                    })()
                                ) : (
                                    <>
                                        <div className="flex justify-between items-center mb-2">
                                            <span className="text-xs text-stone-500">Pencapaian Target Hafalan</span>
                                            <span className="text-xl font-bold text-blue-600 dark:text-blue-400">{statistikSetoran.persentase_target}%</span>
                                        </div>
                                        <div className="w-full bg-stone-200 dark:bg-coal-700 rounded-full h-3">
                                            <div className="bg-gradient-to-r from-blue-600 to-blue-400 h-3 rounded-full transition-all duration-500" style={{ width: `${statistikSetoran.persentase_target}%` }} />
                                        </div>
                                        <div className="flex justify-between text-[10px] text-stone-500 dark:text-stone-600 mt-1">
                                            <span>Tercapai: {statistikSetoran.total_juz} Juz</span>
                                            <span>Target: {statistikSetoran.target_juz} Juz</span>
                                        </div>
                                    </>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-2 mb-4">
                                <div className="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">{statistikSetoran.total_setoran}</div>
                                    <div className="text-[11px] text-blue-500 dark:text-blue-300/70 mt-0.5">Total Setoran</div>
                                </div>
                                <div className="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{statistikSetoran.rata_nilai}</div>
                                    <div className="text-[11px] text-emerald-500 dark:text-emerald-300/70 mt-0.5">Rata-rata Nilai</div>
                                </div>
                                <div className="bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/30 rounded-xl p-3">
                                    {statistikSetoran.is_iqro ? (
                                        <>
                                            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">{statistikSetoran.total_halaman_iqro}</div>
                                            <div className="text-[11px] text-purple-500 dark:text-purple-300/70 mt-0.5">
                                                Total Halaman
                                                {statistikSetoran.has_quran_secondary && (
                                                    <span className="block text-purple-400 dark:text-purple-300/50">+ {statistikSetoran.total_juz_hafalan} Juz</span>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">{statistikSetoran.total_juz}</div>
                                            <div className="text-[11px] text-purple-500 dark:text-purple-300/70 mt-0.5">Total Juz</div>
                                        </>
                                    )}
                                </div>
                                <div className="bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">{Number(statistikSetoran.total_kata).toLocaleString('id-ID')}</div>
                                    <div className="text-[11px] text-orange-500 dark:text-orange-300/70 mt-0.5">Total Kata</div>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-stone-200 dark:border-coal-700">
                                <p className="text-[11px] font-bold text-stone-500 uppercase tracking-wider mb-2">Kategori Setoran</p>
                                <div className="space-y-1.5">
                                    <div className="flex items-center justify-between px-3 py-2 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/20 rounded-lg">
                                        <span className="text-xs text-stone-700 dark:text-stone-300">Hafalan</span>
                                        <span className="text-xs font-bold text-emerald-600 dark:text-emerald-400">{statistikSetoran.hafalan}</span>
                                    </div>
                                    <div className="flex items-center justify-between px-3 py-2 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/20 rounded-lg">
                                        <span className="text-xs text-stone-700 dark:text-stone-300">Murajaah</span>
                                        <span className="text-xs font-bold text-blue-600 dark:text-blue-400">{statistikSetoran.murajaah}</span>
                                    </div>
                                    <div className="flex items-center justify-between px-3 py-2 bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/20 rounded-lg">
                                        <span className="text-xs text-stone-700 dark:text-stone-300">Tilawah</span>
                                        <span className="text-xs font-bold text-purple-600 dark:text-purple-400">{statistikSetoran.tilawah}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Card Statistik Presensi */}
                        <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 className="text-xs font-bold text-stone-600 dark:text-stone-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400"><CheckBadgeIcon className="w-3.5 h-3.5" /></span>
                                Statistik Presensi
                            </h3>

                            <div className="mb-4">
                                <div className="flex justify-between items-center mb-2">
                                    <span className="text-xs text-stone-500">Persentase Kehadiran</span>
                                    <span className="text-xl font-bold text-emerald-600 dark:text-emerald-400">{statistikPresensi.persentase_kehadiran}%</span>
                                </div>
                                <div className="w-full bg-stone-200 dark:bg-coal-700 rounded-full h-3">
                                    <div className="bg-gradient-to-r from-emerald-600 to-emerald-400 h-3 rounded-full transition-all duration-500" style={{ width: `${statistikPresensi.persentase_kehadiran}%` }} />
                                </div>
                                <div className="flex justify-between text-[10px] text-stone-500 dark:text-stone-600 mt-1">
                                    <span>Hadir: {statistikPresensi.hadir}</span>
                                    <span>Total: {statistikPresensi.total_hari} hari</span>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-2 mb-4">
                                <div className="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{statistikPresensi.hadir}</div>
                                    <div className="text-[11px] text-emerald-500 dark:text-emerald-300/70 mt-0.5">Hadir</div>
                                </div>
                                <div className="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">{statistikPresensi.izin}</div>
                                    <div className="text-[11px] text-blue-500 dark:text-blue-300/70 mt-0.5">Izin</div>
                                </div>
                                <div className="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-amber-600 dark:text-amber-400">{statistikPresensi.sakit}</div>
                                    <div className="text-[11px] text-amber-500 dark:text-amber-300/70 mt-0.5">Sakit</div>
                                </div>
                                <div className="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/30 rounded-xl p-3">
                                    <div className="text-2xl font-bold text-red-600 dark:text-red-400">{statistikPresensi.alpha}</div>
                                    <div className="text-[11px] text-red-500 dark:text-red-300/70 mt-0.5">Alpha</div>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-stone-200 dark:border-coal-700">
                                <p className="text-[11px] font-bold text-stone-500 uppercase tracking-wider mb-2">Distribusi Kehadiran</p>
                                {(() => {
                                    const totalHari = statistikPresensi.total_hari;
                                    const pct = (v) => (totalHari > 0 ? Math.round((v / totalHari) * 1000) / 10 : 0);
                                    const items = [
                                        { label: 'Hadir', val: statistikPresensi.hadir, pct: pct(statistikPresensi.hadir), bar: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400' },
                                        { label: 'Izin', val: statistikPresensi.izin, pct: pct(statistikPresensi.izin), bar: 'bg-blue-500', text: 'text-blue-600 dark:text-blue-400' },
                                        { label: 'Sakit', val: statistikPresensi.sakit, pct: pct(statistikPresensi.sakit), bar: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' },
                                        { label: 'Alpha', val: statistikPresensi.alpha, pct: pct(statistikPresensi.alpha), bar: 'bg-red-500', text: 'text-red-600 dark:text-red-400' },
                                    ];
                                    return (
                                        <div className="space-y-2">
                                            {items.map((item) => (
                                                <div key={item.label}>
                                                    <div className="flex items-center justify-between mb-1">
                                                        <span className="text-[11px] text-stone-500">{item.label}</span>
                                                        <span className={`text-[11px] font-bold ${item.text}`}>{item.val} ({item.pct}%)</span>
                                                    </div>
                                                    <div className="w-full bg-stone-200 dark:bg-coal-700 rounded-full h-1.5">
                                                        <div className={`${item.bar} h-1.5 rounded-full transition-all duration-500`} style={{ width: `${item.pct}%` }} />
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    );
                                })()}
                                <div className="mt-3 px-3 py-2 bg-claude-50 dark:bg-claude-500/10 border border-claude-200 dark:border-claude-500/25 rounded-xl flex items-center justify-between">
                                    <span className="text-xs font-semibold text-claude-700 dark:text-claude-300">Total Hari Presensi</span>
                                    <span className="text-xl font-bold text-claude-600 dark:text-claude-400">{statistikPresensi.total_hari}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* ===== RIWAYAT SETORAN ===== */}
                    <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-xl overflow-hidden mb-4 shadow-sm dark:shadow-none">
                        <div className="px-4 py-3 border-b border-stone-200 dark:border-coal-700">
                            <h3 className="text-xs font-bold text-stone-600 dark:text-stone-400 uppercase tracking-wider flex items-center gap-2">
                                <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400"><LayersIcon className="w-3.5 h-3.5" /></span>
                                Riwayat Setoran Berdasarkan Kategori
                            </h3>
                        </div>

                        {totalKategori > 0 ? (
                            <div className="p-4">
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <CategoryTable
                                        title="Hafalan"
                                        entries={hafalanEntries}
                                        perPage={15}
                                        emptyText="Tidak ada data hafalan"
                                        headerBg="bg-emerald-100 dark:bg-emerald-600/20 border-emerald-200 dark:border-emerald-700/30"
                                        headerText="text-emerald-600 dark:text-emerald-400"
                                        countBg="bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400"
                                    />
                                    <div className="space-y-4">
                                        <CategoryTable
                                            title="Murajaah"
                                            entries={murajaahEntries}
                                            perPage={6}
                                            emptyText="Tidak ada data murajaah"
                                            headerBg="bg-blue-100 dark:bg-blue-600/20 border-blue-200 dark:border-blue-700/30"
                                            headerText="text-blue-600 dark:text-blue-400"
                                            countBg="bg-blue-500/20 border border-blue-500/30 text-blue-600 dark:text-blue-400"
                                        />
                                        <CategoryTable
                                            title="Tilawah"
                                            entries={tilawahEntries}
                                            perPage={6}
                                            emptyText="Tidak ada data tilawah"
                                            headerBg="bg-purple-100 dark:bg-purple-600/20 border-purple-200 dark:border-purple-700/30"
                                            headerText="text-purple-600 dark:text-purple-400"
                                            countBg="bg-purple-500/20 border border-purple-500/30 text-purple-600 dark:text-purple-400"
                                        />
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-16">
                                <FileTextIcon className="w-12 h-12 text-stone-300 dark:text-coal-600 mb-3 mx-auto" />
                                <p className="text-sm font-semibold text-stone-500">Tidak ada data setoran dalam periode ini</p>
                            </div>
                        )}
                    </div>

                </div>
            </div>

            {/* ===== PDF PREVIEW MODAL ===== */}
            {showPdfPreview && (
                <div className="fixed inset-0 bg-black/50 dark:bg-black/80 backdrop-blur-sm z-50 overflow-y-auto" onClick={(e) => { if (e.target === e.currentTarget) closePdfPreview(); }}>
                    <div className="min-h-screen px-3 py-6 sm:px-6 sm:py-8">
                        <div className="relative bg-white dark:bg-coal-900 border border-stone-200 dark:border-coal-700 rounded-xl shadow-2xl max-w-6xl mx-auto">
                            <div className="sticky top-0 z-10 flex items-center justify-between p-4 border-b border-stone-200 dark:border-coal-800 bg-white dark:bg-coal-900 rounded-t-xl">
                                <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
                                    <FilePdfIcon className="w-4 h-4 text-red-500 dark:text-red-400" />
                                    Preview PDF — {santri.name}
                                </h3>
                                <div className="flex items-center gap-2">
                                    <a href={pdfDownloadUrl} className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition">
                                        <DownloadIcon className="w-3.5 h-3.5" /> Download
                                    </a>
                                    <button onClick={closePdfPreview} className="w-7 h-7 rounded-lg bg-stone-100 dark:bg-coal-800 hover:bg-stone-200 dark:hover:bg-coal-700 flex items-center justify-center text-stone-500 dark:text-stone-400 transition">
                                        <XIcon className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            <div className="p-4 relative">
                                {pdfLoading && (
                                    <div className="absolute inset-4 rounded-lg border border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-800 flex flex-col items-center justify-center gap-3 z-10">
                                        <span className="w-9 h-9 rounded-full border-[3px] border-stone-300 dark:border-coal-600 border-t-claude-500 animate-spin" />
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
                                    className="w-full rounded-lg border border-stone-200 dark:border-coal-700"
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
import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/* ============================================================
 * ICONS
 * ============================================================ */
const I = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24"
        strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {d}
    </svg>
);
const IF = ({ d, className = 'w-4 h-4' }) => (
    <svg className={className} fill="currentColor" viewBox="0 0 20 20">{d}</svg>
);
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;
const ChevronLeftIcon = (p) => <I {...p} d={<path d="M15 19l-7-7 7-7" />} />;
const ChevronRightIcon = (p) => <I {...p} d={<path d="M9 5l7 7-7 7" />} />;
const ChevronDownIcon = (p) => <I {...p} d={<path d="M19 9l-7 7-7-7" />} />;
const XIcon = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />} />;
const BookIcon = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const ListIcon = (p) => <I {...p} d={<path d="M4 6h16M4 10h16M4 14h16M4 18h16" />} />;
const PdfIcon = (p) => <I {...p} d={<path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />} />;
const NoteIcon = (p) => <I {...p} d={<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />} />;
const AudioIcon = (p) => <IF {...p} d={<path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />} />;
const ClipboardIcon = (p) => <I {...p} d={<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />} />;
const MicIcon = (p) => <I {...p} d={<path d="M19 11a7 7 0 01-14 0m7 7v4m-4 0h8M12 3a3 3 0 00-3 3v5a3 3 0 006 0V6a3 3 0 00-3-3z" />} />;

/* ============================================================
 * HELPERS
 * ============================================================ */
const storageUrl = (path) => (path ? `/storage/${path}` : null);
const fmtDateLabel = (isoStr) => {
    const d = new Date(isoStr + 'T00:00:00');
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
};

const parseSetoranRange = (s) => {
    const m = /^(Iqro|Aisar) Jilid (\d+)/i.exec(s.surat_start || '');
    if (m) {
        const h1 = parseInt(s.ayat_start, 10) || 0;
        const h2 = parseInt(s.ayat_end || s.ayat_start, 10) || h1;
        return {
            isJilid: true,
            rangeLabel: `${m[1]} Jilid ${m[2]} · Hal ${h1}${h1 === h2 ? '' : '–' + h2}`,
            satuanLabel: `${h2 - h1 + 1} hal`,
        };
    }
    return {
        isJilid: false,
        rangeLabel: `${s.surat_start}:${s.ayat_start} → ${s.surat_end}:${s.ayat_end}`,
        satuanLabel: `${Number(s.total_juz).toFixed(2)} juz`,
    };
};
    

const STATUS_COLORS = {
    Hadir: { bg: 'bg-emerald-200/60 dark:bg-emerald-500/30', border: 'border-emerald-400 dark:border-emerald-500', text: 'text-emerald-700 dark:text-emerald-300', dot: 'bg-emerald-500' },
    Izin: { bg: 'bg-blue-200/60 dark:bg-blue-500/30', border: 'border-blue-400 dark:border-blue-500', text: 'text-blue-700 dark:text-blue-300', dot: 'bg-blue-500' },
    Sakit: { bg: 'bg-amber-200/60 dark:bg-amber-500/30', border: 'border-amber-400 dark:border-amber-500', text: 'text-amber-700 dark:text-amber-300', dot: 'bg-amber-500' },
    Alpha: { bg: 'bg-red-200/60 dark:bg-red-500/30', border: 'border-red-400 dark:border-red-500', text: 'text-red-700 dark:text-red-300', dot: 'bg-red-500' },
};
const DEFAULT_STATUS_COLOR = { bg: 'bg-cream-200 dark:bg-coal-600/30', border: 'border-cream-400 dark:border-coal-500', text: 'text-stone-500 dark:text-stone-400', dot: 'bg-stone-500' };

/* ============================================================
 * SUB-COMPONENTS
 * ============================================================ */
function MediaButtons({ s, onAudio, onPdf, onNote, size = 'w-3.5 h-3.5' }) {
    if (!s.has_audio && !s.has_pdf && !s.catatan_guru) return null;
    return (
        <div className="flex items-center gap-1.5">
            {s.has_audio && (
                <button onClick={() => onAudio(s.id)} className="text-indigo-500 hover:text-indigo-400 dark:text-indigo-400 dark:hover:text-indigo-300">
                    <AudioIcon className={size} />
                </button>
            )}
            {s.has_pdf && (
                <button onClick={() => onPdf(s.id)} className="text-red-500 hover:text-red-400 dark:text-red-400 dark:hover:text-red-300">
                    <PdfIcon className={size} />
                </button>
            )}
            {s.catatan_guru && (
                <button onClick={() => onNote(s.catatan_guru, s.guru_name)} className="text-amber-500 hover:text-amber-400 dark:text-amber-400 dark:hover:text-amber-300">
                    <NoteIcon className={size} />
                </button>
            )}
        </div>
    );
}

function Pagination({ links = [] }) {
    if (links.length <= 3) return null;
    return (
        <div className="flex flex-wrap items-center gap-1 justify-center">
            {links.map((link, i) =>
                link.url === null ? (
                    <span key={i} className="px-2.5 py-1 text-xs rounded-lg text-stone-300 dark:text-stone-700 cursor-not-allowed"
                        dangerouslySetInnerHTML={{ __html: link.label }} />
                ) : (
                    <Link key={i} href={link.url} preserveScroll
                        className={`px-2.5 py-1 text-xs rounded-lg border transition ${link.active
                            ? 'bg-claude-600 text-white border-claude-600'
                            : 'bg-white dark:bg-coal-800 text-stone-600 dark:text-stone-400 border-stone-200 dark:border-coal-700 hover:bg-stone-100 dark:hover:bg-coal-700'}`}
                        dangerouslySetInnerHTML={{ __html: link.label }} />
                )
            )}
        </div>
    );
}

function MiniPagination({ paginator }) {
    if (!paginator || paginator.last_page <= 1) return null;
    return (
        <div className="px-4 py-3 border-t border-stone-100 dark:border-coal-700 flex items-center justify-between">
            <span className="text-[10px] text-stone-400 dark:text-stone-600">Hal {paginator.current_page}/{paginator.last_page}</span>
            <div className="flex items-center gap-1">
                {paginator.prev_page_url && (
                    <Link href={paginator.prev_page_url} preserveScroll className="px-2 py-1 text-[10px] text-stone-600 dark:text-stone-400 bg-stone-100 dark:bg-coal-900 hover:bg-stone-200 dark:hover:bg-coal-700 rounded transition">‹</Link>
                )}
                {paginator.next_page_url && (
                    <Link href={paginator.next_page_url} preserveScroll className="px-2 py-1 text-[10px] text-stone-600 dark:text-stone-400 bg-stone-100 dark:bg-coal-900 hover:bg-stone-200 dark:hover:bg-coal-700 rounded transition">›</Link>
                )}
            </div>
        </div>
    );
}

/* ============================================================
 * MAIN PAGE
 * ============================================================ */
export default function DetailSantri({
    santri, isIqro, metodeLabel, targetJuz, targetJilid, totalHalamanJilid, totalHafalanJuz, progressHafalan,
    setoranStats, halamanIqroPerKategori, setoranHafalan, setoranMurajaah, setoranTilawah,
    presensiStats, presensiCount, presensiList = [], calendarMonths = [],
    tanggalMulai, tanggalAkhir, period = '30', prevSantri, nextSantri,
    daftarTahunAjaran = [], tahunAjaranId,
}) {
    // Sertakan tahun_ajaran_id agar semester terpilih ikut terbawa
    // saat navigasi prev/next santri maupun kembali ke daftar kelas.
    const query = `?tahun_ajaran_id=${tahunAjaranId ?? ''}&tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}&period=${period}`;
    const backUrl = `${route('admin.monitoring.kelas', santri.kelas_id)}${query}`;

    const [audioId, setAudioId] = useState(null);
    const [pdfId, setPdfId] = useState(null);
    const [note, setNote] = useState(null); // { content, guru }
    const [presensiView, setPresensiView] = useState('calendar');

    const isPerempuan = santri.gender === 'Perempuan';
    const pctBar = Math.min(progressHafalan, 100);
    const hafalanList = setoranHafalan?.data ?? [];

    // Ganti semester → reload halaman detail santri ini dengan tahun_ajaran_id baru.
    const changeSemester = (id) => {
        router.get(
            route('admin.monitoring.santri', santri.id),
            { tahun_ajaran_id: id },
            { preserveScroll: true, preserveState: false },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Monitoring - ${santri.name}`} />

            <div>

                {/* Kembali — mobile saja */}
                <Link href={backUrl}
                    className="mb-4 sm:hidden inline-flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Kelas
                </Link>
                <Link href={backUrl}
                    className="mb-4 hidden sm:inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 px-3 py-2 text-xs font-semibold text-stone-600 dark:text-stone-300 shadow-sm transition hover:bg-stone-100 dark:hover:bg-coal-700 active:scale-95">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Kelas
                </Link>

                {/* ===== TOP BAR ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="relative flex-shrink-0">
                        {santri.photo ? (
                            <img src={storageUrl(santri.photo)} alt="" className="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover object-top border border-stone-200 dark:border-coal-700" />
                        ) : (
                            <span className={`w-11 h-11 sm:w-12 sm:h-12 rounded-full border flex items-center justify-center text-base font-bold ${isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400'}`}>
                                {santri.name?.charAt(0).toUpperCase()}
                            </span>
                        )}
                        <span className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-gradient-to-br from-claude-500 to-claude-600 border-2 border-white dark:border-coal-800 flex items-center justify-center shadow">
                            <BookIcon className="w-2.5 h-2.5 text-white" />
                        </span>
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-base sm:text-xl font-bold text-stone-900 dark:text-stone-100 truncate">{santri.name}</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">{santri.nis} · {santri.kelas_name}</p>
                    </div>

                    {/* Pilihan Semester / Tahun Ajaran */}
                    {daftarTahunAjaran.length > 0 && (
                        <div className="relative flex-shrink-0">
                            <CalendarIcon className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 dark:text-stone-500" />
                            <select
                                value={tahunAjaranId ?? ''}
                                onChange={(e) => changeSemester(e.target.value)}
                                title="Pilih semester / tahun ajaran"
                                className="appearance-none cursor-pointer w-[190px] sm:w-auto sm:min-w-[260px] max-w-[360px] pl-9 pr-9 py-2.5 text-xs sm:text-sm font-semibold rounded-xl border border-stone-200 dark:border-coal-700 bg-white dark:bg-coal-800 text-stone-700 dark:text-stone-300 shadow-sm transition hover:bg-stone-50 dark:hover:bg-coal-700 focus:outline-none focus:ring-2 focus:ring-claude-500/40 truncate">
                                {daftarTahunAjaran.map((ta) => (
                                    <option key={ta.id} value={ta.id} className="bg-white dark:bg-coal-800 text-stone-700 dark:text-stone-200">
                                        {ta.label}{ta.aktif ? ' • Aktif' : ''}
                                    </option>
                                ))}
                            </select>
                            <ChevronDownIcon className="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 dark:text-stone-500" />
                        </div>
                    )}

                    <div className="hidden sm:flex items-center gap-1 flex-shrink-0">
                        <Link href={prevSantri ? `${route('admin.monitoring.santri', prevSantri.id)}${query}` : '#'}
                            className={`w-8 h-8 rounded-lg border flex items-center justify-center transition ${prevSantri ? 'bg-white dark:bg-coal-800 hover:bg-stone-100 dark:hover:bg-coal-700 border-stone-200 dark:border-coal-700 text-stone-600 dark:text-stone-300' : 'opacity-30 cursor-not-allowed border-stone-200 dark:border-coal-700 text-stone-400 pointer-events-none'}`}
                            title={prevSantri?.name}>
                            <ChevronLeftIcon className="w-4 h-4" />
                        </Link>
                        <Link href={nextSantri ? `${route('admin.monitoring.santri', nextSantri.id)}${query}` : '#'}
                            className={`w-8 h-8 rounded-lg border flex items-center justify-center transition ${nextSantri ? 'bg-white dark:bg-coal-800 hover:bg-stone-100 dark:hover:bg-coal-700 border-stone-200 dark:border-coal-700 text-stone-600 dark:text-stone-300' : 'opacity-30 cursor-not-allowed border-stone-200 dark:border-coal-700 text-stone-400 pointer-events-none'}`}
                            title={nextSantri?.name}>
                            <ChevronRightIcon className="w-4 h-4" />
                        </Link>
                    </div>

                </div>

                {/* ===== PROGRESS & STATS ===== */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

                    {/* Progress Hafalan */}
                    <div className="lg:col-span-2 bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl p-5 shadow-sm dark:shadow-none">
                        <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-stone-100 mb-4">
                            <BookIcon className="w-4 h-4 text-stone-400 dark:text-stone-500" /> Progress Hafalan
                        </h3>
                        <div className="mb-5">
                            <div className="flex justify-between items-center mb-2">
                                {isIqro ? (
                                    <>
                                        <span className="text-xs text-stone-500 dark:text-stone-400">Target: <strong className="text-stone-900 dark:text-stone-100">{metodeLabel ?? 'Iqro'} Jilid {targetJilid}</strong></span>
                                        <span className="text-xs font-bold text-amber-600 dark:text-amber-400">{totalHafalanJuz} hal ({progressHafalan.toFixed(1)}%)</span>
                                    </>
                                ) : (
                                    <>
                                        <span className="text-xs text-stone-500 dark:text-stone-400">Target: <strong className="text-stone-900 dark:text-stone-100">{targetJuz} Juz</strong></span>
                                        <span className="text-xs font-bold text-claude-600 dark:text-claude-400">{totalHafalanJuz.toFixed(2)} Juz ({progressHafalan.toFixed(1)}%)</span>
                                    </>
                                )}
                            </div>
                            <div className="w-full bg-stone-200 dark:bg-coal-700 rounded-full h-4 overflow-hidden">
                                <div className={`h-4 rounded-full flex items-center justify-end pr-2 transition-all duration-500 ${isIqro ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-indigo-500 to-purple-600'}`}
                                    style={{ width: `${pctBar}%`, minWidth: progressHafalan > 0 ? '24px' : '0' }}>
                                    {progressHafalan > 15 && <span className="text-white text-[10px] font-bold">{pctBar.toFixed(0)}%</span>}
                                </div>
                            </div>
                            {progressHafalan >= 100 ? (
                                <p className="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1.5">🎉 Target tercapai!</p>
                            ) : isIqro ? (
                                <p className="text-xs text-stone-500 dark:text-stone-600 mt-1.5">Sisa: {Math.max(0, totalHalamanJilid - totalHafalanJuz)} halaman</p>
                            ) : (
                                <p className="text-xs text-stone-500 dark:text-stone-600 mt-1.5">Sisa: {(targetJuz - totalHafalanJuz).toFixed(2)} Juz</p>
                            )}
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <div className="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-3.5 text-center">
                                <p className="text-[10px] text-emerald-600 font-bold uppercase mb-1">Hafalan</p>
                                <p className="text-2xl font-black text-emerald-600 dark:text-emerald-400">{setoranStats.Hafalan.total}</p>
                                <p className="text-[10px] text-emerald-600 mt-0.5">{isIqro ? `${totalHafalanJuz} hal` : `${setoranStats.Hafalan.total_juz.toFixed(2)} juz`}</p>
                            </div>
                            <div className="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl p-3.5 text-center">
                                <p className="text-[10px] text-blue-600 font-bold uppercase mb-1">Murajaah</p>
                                <p className="text-2xl font-black text-blue-600 dark:text-blue-400">{setoranStats.Murajaah.total}</p>
                                <p className="text-[10px] text-blue-600 mt-0.5">{isIqro ? `${halamanIqroPerKategori.Murajaah ?? 0} hal` : `${setoranStats.Murajaah.total_juz.toFixed(2)} juz`}</p>
                            </div>
                            <div className="bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/30 rounded-xl p-3.5 text-center">
                                <p className="text-[10px] text-purple-600 font-bold uppercase mb-1">Tilawah</p>
                                <p className="text-2xl font-black text-purple-600 dark:text-purple-400">{setoranStats.Tilawah.total}</p>
                                <p className="text-[10px] text-purple-600 mt-0.5">{isIqro ? `${halamanIqroPerKategori.Tilawah ?? 0} hal` : `${setoranStats.Tilawah.total_juz.toFixed(2)} juz`}</p>
                            </div>
                        </div>
                    </div>

                    {/* Presensi Stats */}
                    <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl p-5 shadow-sm dark:shadow-none">
                        <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-stone-100 mb-4">
                            <ClipboardIcon className="w-4 h-4 text-stone-400 dark:text-stone-500" /> Statistik Presensi
                        </h3>
                        <div className="space-y-2.5">
                            <div className="flex items-center justify-between bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl px-4 py-3">
                                <span className="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Hadir</span>
                                <span className="text-xl font-black text-emerald-600 dark:text-emerald-400">{presensiStats.Hadir ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                                <span className="text-sm font-semibold text-blue-700 dark:text-blue-300">Izin</span>
                                <span className="text-xl font-black text-blue-600 dark:text-blue-400">{presensiStats.Izin ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl px-4 py-3">
                                <span className="text-sm font-semibold text-amber-700 dark:text-amber-300">Sakit</span>
                                <span className="text-xl font-black text-amber-600 dark:text-amber-400">{presensiStats.Sakit ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/30 rounded-xl px-4 py-3">
                                <span className="text-sm font-semibold text-red-700 dark:text-red-300">Alpha</span>
                                <span className="text-xl font-black text-red-600 dark:text-red-400">{presensiStats.Alpha ?? 0}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ===== RIWAYAT HAFALAN ===== */}
                <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden mb-4 shadow-sm dark:shadow-none">
                    <div className="flex items-center justify-between px-5 py-3.5 border-b border-stone-200 dark:border-coal-700">
                        <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-stone-100">
                            <BookIcon className="w-4 h-4 text-stone-400 dark:text-stone-500" /> Riwayat Hafalan
                        </h3>
                        <span className="text-[11px] text-stone-400 dark:text-stone-600">{setoranHafalan.total} total · hal {setoranHafalan.current_page}/{setoranHafalan.last_page}</span>
                    </div>

                    {/* MOBILE */}
                    <div className="lg:hidden divide-y divide-stone-100 dark:divide-coal-700">
                        {hafalanList.length === 0 ? (
                            <div className="py-8 text-center text-sm text-stone-500">Belum ada data hafalan</div>
                        ) : hafalanList.map((s, idx) => {
                            const { rangeLabel, satuanLabel } = parseSetoranRange(s);
                            return (
                                <div key={s.id} className="px-4 py-3 bg-emerald-50/50 dark:bg-emerald-950/20 border-l-2 border-emerald-300 dark:border-emerald-800/30">
                                    <div className="flex items-center justify-between mb-1.5">
                                        <div className="flex items-center gap-2">
                                            <span className="text-[10px] font-bold text-stone-500 dark:text-stone-600 bg-cream-200 dark:bg-coal-800 px-1.5 py-0.5 rounded">{(setoranHafalan.from ?? 1) + idx}</span>
                                            <span className="text-xs font-semibold text-stone-700 dark:text-stone-300">{s.tanggal}</span>
                                        </div>
                                        <MediaButtons s={s} onAudio={setAudioId} onPdf={setPdfId} onNote={(c, g) => setNote({ content: c, guru: g })} />
                                    </div>
                                    <p className="text-[11px] text-stone-500 mb-1.5">{rangeLabel}</p>
                                    <div className="flex justify-between items-center">
                                        <div className="flex items-center gap-2">
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-950/50 dark:text-orange-400 dark:border-orange-800/30">{satuanLabel}</span>
                                            <span className="text-[10px] text-stone-500 dark:text-stone-600">{s.guru_name || '-'}</span>
                                        </div>
                                        <span className="text-xs font-bold text-stone-700 dark:text-stone-300">Nilai: {s.nilai}</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* DESKTOP */}
                    <div className="hidden lg:block overflow-x-auto">
                        <table className="min-w-full">
                            <thead>
                                <tr className="border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/40">
                                    <th className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider w-10">No</th>
                                    <th className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Tanggal</th>
                                    <th className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Range</th>
                                    <th className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">{isIqro ? 'Hal' : 'Juz'}</th>
                                    <th className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Nilai</th>
                                    <th className="px-4 py-2.5 text-left text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Guru</th>
                                    <th className="px-4 py-2.5 text-center text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Media</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 dark:divide-coal-700">
                                {hafalanList.length === 0 ? (
                                    <tr><td colSpan={7} className="py-8 text-center text-sm text-stone-500">Belum ada data hafalan</td></tr>
                                ) : hafalanList.map((s, idx) => {
                                    const { rangeLabel, satuanLabel } = parseSetoranRange(s);
                                    return (
                                        <tr key={s.id} className="hover:bg-stone-50 dark:hover:bg-coal-700/25 transition-colors">
                                            <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-600 text-center">{(setoranHafalan.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-400 whitespace-nowrap">{s.tanggal}</td>
                                            <td className="px-4 py-3 text-xs text-stone-500 dark:text-stone-400 whitespace-nowrap">{rangeLabel}</td>
                                            <td className="px-4 py-3"><span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-950/50 dark:text-orange-400 dark:border-orange-800/30">{satuanLabel}</span></td>
                                            <td className="px-4 py-3 text-sm font-bold text-stone-800 dark:text-stone-200">{s.nilai}</td>
                                            <td className="px-4 py-3 text-xs text-stone-500">{s.guru_name || '-'}</td>
                                            <td className="px-4 py-3 text-center">
                                                <div className="flex items-center justify-center">
                                                    <MediaButtons s={s} onAudio={setAudioId} onPdf={setPdfId} onNote={(c, g) => setNote({ content: c, guru: g })} size="w-4 h-4" />
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {setoranHafalan.last_page > 1 && (
                        <div className="px-5 py-3.5 border-t border-stone-200 dark:border-coal-700">
                            <Pagination links={setoranHafalan.links ?? []} />
                        </div>
                    )}
                </div>

                {/* ===== MURAJAAH & TILAWAH ===== */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                    {[
                        { label: 'Murajaah', paginator: setoranMurajaah, border: 'border-blue-200 dark:border-blue-800/30', bg: 'bg-blue-50/50 dark:bg-blue-950/20', color: 'text-blue-600 dark:text-blue-400' },
                        { label: 'Tilawah', paginator: setoranTilawah, border: 'border-purple-200 dark:border-purple-800/30', bg: 'bg-purple-50/50 dark:bg-purple-950/20', color: 'text-purple-600 dark:text-purple-400' },
                    ].map(({ label, paginator, border, bg, color }) => (
                        <div key={label} className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                            <div className="flex items-center justify-between px-5 py-3.5 border-b border-stone-200 dark:border-coal-700">
                                <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">Riwayat {label}</h3>
                                <span className="text-[11px] text-stone-400 dark:text-stone-600">{paginator.total} total</span>
                            </div>
                            <div className="divide-y divide-stone-100 dark:divide-coal-700">
                                {(paginator.data ?? []).length === 0 ? (
                                    <div className="py-8 text-center text-sm text-stone-500">Belum ada data {label}</div>
                                ) : paginator.data.map((s, idx) => {
                                    const { rangeLabel, satuanLabel } = parseSetoranRange(s);
                                    return (
                                        <div key={s.id} className={`px-4 py-3 ${bg} border-l-2 ${border}`}>
                                            <div className="flex items-center justify-between mb-1.5">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-[10px] font-bold text-stone-500 dark:text-stone-600 bg-cream-200 dark:bg-coal-800 px-1.5 py-0.5 rounded">{(paginator.from ?? 1) + idx}</span>
                                                    <span className="text-xs font-semibold text-stone-700 dark:text-stone-300">{s.tanggal}</span>
                                                </div>
                                                <MediaButtons s={s} onAudio={setAudioId} onPdf={setPdfId} onNote={(c, g) => setNote({ content: c, guru: g })} />
                                            </div>
                                            <p className="text-[11px] text-stone-500 mb-1.5">{rangeLabel}</p>
                                            <div className="flex justify-between items-center">
                                                <span className={`text-xs ${color}`}>{satuanLabel}</span>
                                                <span className="text-xs font-bold text-stone-700 dark:text-stone-300">Nilai: {s.nilai}</span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                            <MiniPagination paginator={paginator} />
                        </div>
                    ))}
                </div>

                {/* ===== RIWAYAT PRESENSI: Kalender + List ===== */}
                <div className="bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
                    <div className="flex items-center justify-between px-5 py-3.5 border-b border-stone-200 dark:border-coal-700">
                        <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-stone-100">
                            <CalendarIcon className="w-4 h-4 text-stone-400 dark:text-stone-500" /> Riwayat Presensi
                        </h3>
                        <div className="flex bg-stone-100 dark:bg-coal-900 rounded-lg p-0.5 gap-0.5">
                            <button onClick={() => setPresensiView('calendar')}
                                className={`px-2.5 py-1 text-[10px] font-bold rounded-md transition flex items-center gap-1 ${presensiView === 'calendar' ? 'bg-claude-600 text-white' : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300'}`}>
                                <CalendarIcon className="w-3 h-3" /> Kalender
                            </button>
                            <button onClick={() => setPresensiView('list')}
                                className={`px-2.5 py-1 text-[10px] font-bold rounded-md transition flex items-center gap-1 ${presensiView === 'list' ? 'bg-claude-600 text-white' : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300'}`}>
                                <ListIcon className="w-3 h-3" /> List
                            </button>
                        </div>
                    </div>

                    {presensiCount === 0 ? (
                        <p className="text-center text-sm text-stone-500 py-12">Belum ada data presensi</p>
                    ) : presensiView === 'calendar' ? (
                        <div>
                            {/* Legend */}
                            <div className="flex items-center justify-center gap-3 sm:gap-4 px-4 py-3 border-b border-stone-200 dark:border-coal-700 bg-stone-50 dark:bg-coal-900/20 flex-wrap">
                                {Object.entries(STATUS_COLORS).map(([status, c]) => (
                                    <div key={status} className="flex items-center gap-1.5">
                                        <span className={`w-2.5 h-2.5 rounded-full ${c.dot}`} />
                                        <span className="text-[10px] font-semibold text-stone-500 dark:text-stone-400">{status}</span>
                                    </div>
                                ))}
                            </div>

                            {/* Multi-month grid */}
                            <div className="p-4">
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                    {calendarMonths.map((month, mi) => (
                                        <div key={mi} className="bg-stone-50 dark:bg-coal-900/20 border border-stone-200 dark:border-coal-700 rounded-xl p-3">
                                            <h4 className="text-xs font-bold text-stone-700 dark:text-stone-300 mb-3 flex items-center gap-2">
                                                <span className="w-1 h-3.5 bg-claude-500 rounded-full" />
                                                {month.label}
                                            </h4>
                                            <div className="grid grid-cols-7 gap-1 mb-1.5">
                                                {['M', 'S', 'S', 'R', 'K', 'J', 'S'].map((d, di) => (
                                                    <div key={di} className="text-[9px] font-bold text-stone-400 dark:text-stone-600 text-center py-1">{d}</div>
                                                ))}
                                            </div>
                                            <div className="grid grid-cols-7 gap-1">
                                                {Array.from({ length: month.first_day_of_week }).map((_, i) => <div key={`b${i}`} />)}
                                                {month.days.map((day) => {
                                                    const hasData = day.presensi.length > 0;
                                                    const colors = day.primary_status ? STATUS_COLORS[day.primary_status] : null;
                                                    if (hasData && colors) {
                                                        return (
                                                            <div key={day.date}
                                                                className={`relative aspect-square ${colors.bg} border ${colors.border} rounded-md flex flex-col items-center justify-center cursor-pointer hover:scale-110 transition-transform`}
                                                                title={`${day.date_label} — ${day.presensi.map((p) => `${p.sesi}: ${p.status}`).join(', ')}`}>
                                                                <span className={`text-[10px] font-bold ${colors.text}`}>{day.day}</span>
                                                                {day.presensi.length > 1 && (
                                                                    <div className="flex gap-0.5 mt-0.5">
                                                                        {day.presensi.map((p, pi) => (
                                                                            <span key={pi} className={`w-0.5 h-0.5 rounded-full ${STATUS_COLORS[p.status]?.dot || 'bg-stone-500'}`} />
                                                                        ))}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        );
                                                    }
                                                    return (
                                                        <div key={day.date} className={`aspect-square border rounded-md flex items-center justify-center ${day.is_in_range ? 'bg-cream-200 dark:bg-coal-600/40 border-cream-300 dark:border-coal-600' : 'bg-transparent border-transparent'}`}>
                                                            <span className={`text-[10px] ${day.is_in_range ? 'text-stone-400 dark:text-stone-600' : 'text-stone-300 dark:text-coal-600'}`}>{day.day}</span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="divide-y divide-stone-100 dark:divide-coal-700 max-h-[600px] overflow-y-auto">
                            {presensiList.map((day) => (
                                <div key={day.date} className="px-4 py-3">
                                    <div className="flex items-center justify-between mb-2">
                                        <div>
                                            <p className="text-sm font-bold text-stone-900 dark:text-stone-100">{day.date_label}</p>
                                            <p className="text-[10px] text-stone-400 dark:text-stone-600">{day.items.length} sesi</p>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        {day.items.map((p, pi) => {
                                            const colors = STATUS_COLORS[p.status] || DEFAULT_STATUS_COLOR;
                                            return (
                                                <div key={pi} className={`flex items-center justify-between ${colors.bg} border ${colors.border} rounded-lg px-3 py-2`}>
                                                    <span className="text-[11px] font-semibold text-stone-700 dark:text-stone-300">{p.sesi}</span>
                                                    <div className="flex items-center gap-1.5">
                                                        <span className={`w-1.5 h-1.5 rounded-full ${colors.dot}`} />
                                                        <span className={`text-[10px] font-bold ${colors.text}`}>{p.status}</span>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* ===== AUDIO MODAL ===== */}
            {audioId && (
                <div className="fixed inset-0 bg-black/40 dark:bg-black/75 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                    onClick={(e) => e.target === e.currentTarget && setAudioId(null)}>
                    <div className="relative bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-t-2xl sm:rounded-2xl w-full sm:max-w-sm overflow-hidden">
                        <div className="w-10 h-1 bg-stone-300 dark:bg-coal-600 rounded-full mx-auto mt-3 mb-1 sm:hidden" />
                        <div className="flex items-center justify-between px-5 py-3 border-b border-stone-200 dark:border-coal-700">
                            <h3 className="text-sm font-bold text-stone-900 dark:text-stone-100">🎙️ Rekaman Audio</h3>
                            <button onClick={() => setAudioId(null)} className="text-stone-500 hover:text-stone-700 dark:hover:text-white"><XIcon className="w-4 h-4" /></button>
                        </div>
                        <div className="p-5">
                            <audio key={audioId} controls autoPlay className="w-full">
                                <source src={`/admin/monitoring/setoran/${audioId}/audio`} type="audio/webm" />
                            </audio>
                        </div>
                    </div>
                </div>
            )}

            {/* ===== PDF MODAL ===== */}
            {pdfId && (
                <div className="fixed inset-0 bg-black/60 dark:bg-black/85 z-50 flex items-center justify-center p-4"
                    onClick={(e) => e.target === e.currentTarget && setPdfId(null)}>
                    <div className="relative bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden" style={{ height: '90vh' }}>
                        <div className="flex items-center justify-between px-5 py-3 border-b border-stone-200 dark:border-coal-700">
                            <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-stone-100">
                                <PdfIcon className="w-4 h-4 text-stone-400 dark:text-stone-500" /> Preview PDF Koreksi
                            </h3>
                            <div className="flex items-center gap-2">
                                <a href={`/admin/monitoring/setoran/${pdfId}/pdf`} download
                                    className="px-3 py-1.5 bg-claude-600 hover:bg-claude-500 text-white text-xs font-bold rounded-lg transition">Download</a>
                                <button onClick={() => setPdfId(null)} className="text-stone-500 hover:text-stone-700 dark:hover:text-white"><XIcon className="w-4 h-4" /></button>
                            </div>
                        </div>
                        <iframe src={`/admin/monitoring/setoran/${pdfId}/pdf`} className="w-full" style={{ height: 'calc(90vh - 56px)', border: 0 }} title="Preview PDF" />
                    </div>
                </div>
            )}

            {/* ===== NOTE MODAL ===== */}
            {note && (
                <div className="fixed inset-0 bg-black/40 dark:bg-black/75 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                    onClick={(e) => e.target === e.currentTarget && setNote(null)}>
                    <div className="relative bg-white dark:bg-coal-800 border border-stone-200 dark:border-coal-700 rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md overflow-hidden">
                        <div className="w-10 h-1 bg-stone-300 dark:bg-coal-600 rounded-full mx-auto mt-3 mb-1 sm:hidden" />
                        <div className="flex items-center justify-between px-5 py-3 border-b border-stone-200 dark:border-coal-700">
                            <h3 className="flex items-center gap-2 text-sm font-bold text-stone-900 dark:text-stone-100">
                                <NoteIcon className="w-4 h-4 text-stone-400 dark:text-stone-500" /> Catatan Guru
                            </h3>
                            <button onClick={() => setNote(null)} className="text-stone-500 hover:text-stone-700 dark:hover:text-white"><XIcon className="w-4 h-4" /></button>
                        </div>
                        <div className="p-5">
                            <p className="text-xs text-stone-500 mb-3">Dari: <span className="font-semibold text-stone-700 dark:text-stone-300">{note.guru || 'Guru'}</span></p>
                            <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4">
                                <p className="text-sm text-stone-700 dark:text-stone-300 whitespace-pre-wrap">{note.content}</p>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
import { useEffect, useState } from 'react';
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
    book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    pdf: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    download: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
    close: 'M6 18L18 6M6 6l12 12',
    user: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    doc: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    rapot: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
};

const num = (v, d = 0) => Number(v ?? 0).toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
const safeRoute = (name, params) => { try { return route(name, params); } catch { return '#'; } };
const fmtDate = (d) => { try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return d; } };

/** Deteksi label setoran berbasis jilid: Iqro / Aisar / Ummi */
const JILID_RE = /^(Iqro|Aisar|Ummi)\s+Jilid\s+(\d+)/i;

function nilaiTone(n) {
    if (n >= 90) return 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20';
    if (n >= 75) return 'bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 ring-1 ring-claude-200 dark:ring-claude-500/20';
    return 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20';
}
function usePaged(rows, perPage) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
    useEffect(() => { if (page > totalPages) setPage(1); }, [totalPages, page]);
    return { page, setPage, totalPages, slice: rows.slice((page - 1) * perPage, page * perPage), total: rows.length, perPage };
}

const Card = ({ className = '', children }) => (
    <div className={`bg-white dark:bg-coal-800 rounded-2xl ring-1 ring-stone-200 dark:ring-coal-700 ${className}`}>{children}</div>
);

const CAT = {
    Hafalan:  { dot: 'bg-claude-500', text: 'text-claude-600 dark:text-claude-400', chip: 'bg-claude-100 dark:bg-claude-500/15 ring-claude-200 dark:ring-claude-500/20', perPage: 15 },
    Murajaah: { dot: 'bg-amber-500',  text: 'text-amber-600 dark:text-amber-400',   chip: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20', perPage: 6 },
    Tilawah:  { dot: 'bg-teal-500',   text: 'text-teal-600 dark:text-teal-400',     chip: 'bg-teal-50 dark:bg-teal-500/10 ring-teal-100 dark:ring-teal-500/20', perPage: 6 },
};
const PRES = { Hadir: 'bg-emerald-500', Izin: 'bg-blue-500', Sakit: 'bg-amber-500', Alpha: 'bg-red-500' };

export default function Show({
    santri, setorans = {}, statistikSetoran = {}, statistikPresensi = {},
    tanggalMulai = '', tanggalAkhir = '', semesterLabel = 'Semester Aktif', tahunAjaranAktif = null,
    pdfBoleh = false, pdfPesan = null,
}) {
    const [mobileTab, setMobileTab] = useState('Hafalan');
    const [pdfOpen, setPdfOpen] = useState(false);

    const groups = {
        Hafalan: Object.values(setorans?.Hafalan ?? {}),
        Murajaah: Object.values(setorans?.Murajaah ?? {}),
        Tilawah: Object.values(setorans?.Tilawah ?? {}),
    };
    const hasAny = groups.Hafalan.length || groups.Murajaah.length || groups.Tilawah.length;

    const ss = statistikSetoran, sp = statistikPresensi;
    const isIqro = ss.is_iqro ?? false;
    const metodeLabel = ss.metode_label ?? 'Iqro';   // "Iqro" / "Aisar"
    // Metode 2 (opsional): target Al-Qur'an untuk santri jilid.
    // total_juz_hafalan / target_juz / persentase_target sudah = porsi Al-Qur'an.
    const hasQuran2 = (ss.has_quran_secondary ?? false) && isIqro;

    const photo = santri?.user?.profile_photo;
    const isPr = santri?.gender === 'P' || santri?.gender === 'Perempuan';

    return (
        <SantriAuthenticatedLayout>
            <Head title="Rapot Lengkap" />

            <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 flex items-center justify-center flex-shrink-0">
                        <Icon path={P.rapot} className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Rapot Lengkap</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Ringkasan setoran &amp; presensi semester aktif</p>
                    </div>
                    {tahunAjaranAktif && (
                        <span className="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-claude-100 dark:bg-claude-500/15 ring-1 ring-claude-200 dark:ring-claude-500/20 flex-shrink-0">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                            <span className="text-xs font-semibold text-claude-600 dark:text-claude-400 whitespace-nowrap">{tahunAjaranAktif.nama ?? tahunAjaranAktif.name}</span>
                        </span>
                    )}
                </div>

                <div className="space-y-4">
                    {/* Santri info */}
                    <Card className="p-4 sm:p-5">
                        <div className="flex items-center gap-4">
                            {photo ? (
                                <img src={`/storage/${photo}`} alt="Foto" className="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover object-top ring-1 ring-stone-200 dark:ring-coal-700 flex-shrink-0" />
                            ) : (
                                <span className={`w-12 h-12 sm:w-14 sm:h-14 rounded-full ring-1 flex items-center justify-center text-lg font-bold flex-shrink-0 ${isPr ? 'bg-pink-100 dark:bg-pink-950/60 ring-pink-200 dark:ring-pink-800/60 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950/60 ring-blue-200 dark:ring-blue-800/60 text-blue-600 dark:text-blue-400'}`}>
                                    {(santri?.name ?? '?').charAt(0).toUpperCase()}
                                </span>
                            )}
                            <div className="min-w-0 flex-1">
                                <h3 className="text-base sm:text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri?.name}</h3>
                                <p className="text-[11px] text-stone-400 dark:text-stone-500 font-mono">{santri?.nis ?? '-'}</p>
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{santri?.kelas?.name ?? '-'}</span>
                                    {isIqro && (
                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-stone-100 dark:bg-coal-700 text-stone-600 dark:text-stone-300 font-semibold ring-1 ring-stone-200 dark:ring-coal-600">
                                            {metodeLabel} · Jilid {ss.target_jilid}
                                            {hasQuran2 && <span className="opacity-40 mx-1">+</span>}
                                            {hasQuran2 && "Al-Qur'an"}
                                        </span>
                                    )}
                                    <span className="text-xs text-stone-500 dark:text-stone-400 hidden sm:inline-flex items-center gap-1"><Icon path={P.user} className="w-3 h-3" /> {santri?.guru?.name ?? '-'}</span>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Periode (read-only) + Preview PDF */}
                    <Card className="p-4 sm:p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <span className="w-10 h-10 rounded-xl flex items-center justify-center bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400 flex-shrink-0">
                                    <Icon path={P.calendar} className="w-5 h-5" />
                                </span>
                                <div>
                                    <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium">Periode Rapot</p>
                                    <p className="text-sm font-bold tracking-tight text-stone-900 dark:text-stone-100">{semesterLabel}</p>
                                    <p className="text-[10px] text-stone-400 mt-0.5">{fmtDate(tanggalMulai)} — {fmtDate(tanggalAkhir)}</p>
                                </div>
                            </div>
                            {pdfBoleh ? (
                                <button
                                    onClick={() => setPdfOpen(true)}
                                    className="px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold flex items-center gap-2 transition-colors shadow-sm active:scale-95"
                                >
                                    <Icon path={P.pdf} className="w-4 h-4" /> Preview PDF
                                </button>
                            ) : (
                                <span className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-stone-100 dark:bg-coal-700 text-stone-500 dark:text-stone-400 ring-1 ring-stone-200 dark:ring-coal-600 text-sm font-semibold cursor-not-allowed">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="1.7" viewBox="0 0 24 24">
                                        <rect x="4.5" y="11" width="15" height="10" rx="2" />
                                        <path strokeLinecap="round" d="M8 11V7a4 4 0 018 0v4" />
                                    </svg>
                                    PDF Terkunci
                                </span>
                            )}
                        </div>

                        {!pdfBoleh && pdfPesan && (
                            <p className="mt-3 text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                                {pdfPesan}
                            </p>
                        )}
                    </Card>

                    {/* Ringkasan statistik */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <Card className="overflow-hidden">
                            <SummaryHead icon={P.book} title="Statistik Setoran" />
                            <div className="p-4 sm:p-5 space-y-4">
                                <div className="rounded-xl p-3.5 bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-100 dark:ring-claude-500/20">
                                    <div className="flex justify-between items-start mb-2.5">
                                        <div>
                                            <p className="text-xs text-stone-500 dark:text-stone-400 mb-0.5">Pencapaian Target</p>
                                            <p className="text-xs font-semibold text-stone-900 dark:text-stone-100">{isIqro ? `${metodeLabel} — Jilid ${ss.target_jilid}` : 'Hafalan Juz'}</p>
                                        </div>
                                        <div className="text-2xl font-bold tracking-tight text-claude-600 dark:text-claude-400 leading-none tabular-nums">{isIqro ? ss.progress_iqro : ss.persentase_target}%</div>
                                    </div>
                                    {isIqro && !ss.iqro_persen_diketahui ? (
                                        <p className="text-xs text-stone-500 dark:text-stone-400 italic">Jumlah halaman {metodeLabel} Jilid {ss.target_jilid} belum diatur di konfigurasi</p>
                                    ) : (
                                        <>
                                            <Bar value={isIqro ? ss.progress_iqro : ss.persentase_target} />
                                            <div className="flex justify-between text-[11px] mt-2">
                                                {isIqro ? (
                                                    <>
                                                        <span className="text-stone-500 dark:text-stone-400">Disetor: <strong className="text-stone-900 dark:text-stone-100">{ss.total_halaman_iqro}</strong> hal</span>
                                                        <span className="text-stone-500 dark:text-stone-400">Target: <strong className="text-stone-900 dark:text-stone-100">{ss.total_halaman_jilid}</strong> hal</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <span className="text-stone-500 dark:text-stone-400">Tercapai: <strong className="text-stone-900 dark:text-stone-100">{num(ss.total_juz, 2)}</strong> Juz</span>
                                                        <span className="text-stone-500 dark:text-stone-400">Target: <strong className="text-stone-900 dark:text-stone-100">{num(ss.target_juz, 2)}</strong> Juz</span>
                                                    </>
                                                )}
                                            </div>
                                            {isIqro && (
                                                <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-1.5">Progress dihitung dari halaman unik kategori Hafalan.</p>
                                            )}
                                        </>
                                    )}
                                 </div>

                                {hasQuran2 && (
                                    <div className="rounded-xl p-3.5 bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20">
                                        <div className="flex justify-between items-start mb-2.5">
                                            <div>
                                                <p className="text-xs text-stone-500 dark:text-stone-400 mb-0.5">Pencapaian Target</p>
                                                <p className="text-xs font-semibold text-stone-900 dark:text-stone-100">Al-Qur'an — Hafalan Juz</p>
                                            </div>
                                            <div className="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 leading-none tabular-nums">{ss.persentase_target}%</div>
                                        </div>
                                        <Bar value={ss.persentase_target} tone="emerald" />
                                        <div className="flex justify-between text-[11px] mt-2">
                                            <span className="text-stone-500 dark:text-stone-400">Tercapai: <strong className="text-stone-900 dark:text-stone-100">{num(ss.total_juz_hafalan, 2)}</strong> Juz</span>
                                            <span className="text-stone-500 dark:text-stone-400">Target: <strong className="text-stone-900 dark:text-stone-100">{num(ss.target_juz, 2)}</strong> Juz</span>
                                        </div>
                                    </div>
                                )}

                                <div className="grid grid-cols-2 gap-2.5">
                                    <Mini value={ss.total_setoran} label="Total Setoran" />
                                    <Mini value={num(ss.rata_nilai, 1)} label="Rata-rata Nilai" />
 <Mini
                                        value={isIqro ? ss.total_halaman_iqro : num(ss.total_juz, 2)}
                                        label={isIqro
                                            ? (hasQuran2 ? `Halaman ${metodeLabel} · +${num(ss.total_juz_hafalan, 2)} Juz` : `Halaman ${metodeLabel}`)
                                            : 'Total Juz'}
                                    />                                    <Mini value={num(ss.total_kata)} label="Total Kata" />
                                </div>

                                <div className="pt-4 border-t border-stone-100 dark:border-coal-700">
                                    <p className="text-[11px] font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-2.5">Kategori Setoran</p>
                                    <div className="space-y-1.5">
                                        <CatCount cat="Hafalan" value={ss.hafalan} />
                                        <CatCount cat="Murajaah" value={ss.murajaah} />
                                        <CatCount cat="Tilawah" value={ss.tilawah} />
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <Card className="overflow-hidden">
                            <SummaryHead icon={P.check} title="Statistik Presensi" />
                            <div className="p-4 sm:p-5 space-y-4">
                                <div className="rounded-xl p-3.5 bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20">
                                    <div className="flex justify-between items-start mb-2.5">
                                        <div>
                                            <p className="text-xs text-stone-500 dark:text-stone-400 mb-0.5">Persentase Kehadiran</p>
                                            <p className="text-xs font-semibold text-stone-900 dark:text-stone-100">Periode ini</p>
                                        </div>
                                        <div className="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 leading-none tabular-nums">{sp.persentase_kehadiran}%</div>
                                    </div>
                                    <Bar value={sp.persentase_kehadiran} tone="emerald" />
                                    <div className="flex justify-end text-[11px] text-stone-500 dark:text-stone-400 mt-2">
                                        <span><strong className="text-stone-900 dark:text-stone-100">{sp.hadir}</strong> / {sp.total_hari} hari hadir</span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-2.5">
                                    <MiniSem tone="emerald" value={sp.hadir} label="Hadir" />
                                    <MiniSem tone="blue" value={sp.izin} label="Izin" />
                                    <MiniSem tone="amber" value={sp.sakit} label="Sakit" />
                                    <MiniSem tone="red" value={sp.alpha} label="Alpha" />
                                </div>

                                <div className="pt-4 border-t border-stone-100 dark:border-coal-700">
                                    <p className="text-[11px] font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-2.5">Distribusi Kehadiran</p>
                                    <div className="space-y-2.5">
                                        {['Hadir', 'Izin', 'Sakit', 'Alpha'].map((k) => {
                                            const v = sp[k.toLowerCase()] ?? 0;
                                            const pct = sp.total_hari > 0 ? Math.round((v / sp.total_hari) * 1000) / 10 : 0;
                                            return (
                                                <div key={k}>
                                                    <div className="flex items-center justify-between mb-1">
                                                        <span className="text-[11px] text-stone-500 dark:text-stone-400">{k}</span>
                                                        <span className="text-[11px] font-bold text-stone-900 dark:text-stone-100 tabular-nums">{v} <span className="text-stone-400 dark:text-stone-500 font-normal">({pct}%)</span></span>
                                                    </div>
                                                    <div className="w-full h-1.5 rounded-full bg-stone-100 dark:bg-coal-700 overflow-hidden">
                                                        <div className={`h-full rounded-full transition-all duration-700 ${PRES[k]}`} style={{ width: `${pct}%` }} />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                    <div className="mt-3.5 p-2.5 rounded-lg bg-claude-50 dark:bg-claude-500/10 ring-1 ring-claude-100 dark:ring-claude-500/20 flex items-center justify-between">
                                        <span className="text-xs font-semibold text-claude-600 dark:text-claude-400">Total Hari Presensi</span>
                                        <span className="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums">{sp.total_hari}</span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    {/* Riwayat setoran per kategori */}
                    <Card className="overflow-hidden">
                        <SummaryHead icon={P.book} title="Riwayat Setoran Per Kategori" />
                        {!hasAny ? (
                            <div className="p-10 text-center">
                                <div className="w-14 h-14 mx-auto mb-3 rounded-2xl grid place-items-center bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600">
                                    <Icon path={P.doc} className="w-7 h-7 text-stone-400 opacity-60" />
                                </div>
                                <p className="text-sm text-stone-500 dark:text-stone-400">Tidak ada data setoran pada semester ini</p>
                            </div>
                        ) : (
                            <div className="p-3 sm:p-4 lg:p-5">
                                <div className="flex gap-1.5 mb-4 lg:hidden overflow-x-auto pb-1">
                                    {['Hafalan', 'Murajaah', 'Tilawah'].map((k) => {
                                        const t = CAT[k];
                                        return (
                                            <button key={k} onClick={() => setMobileTab(k)} className={`shrink-0 px-3 py-2 text-xs font-semibold rounded-xl transition-colors ring-1 ${mobileTab === k ? `${t.chip} ${t.text}` : 'bg-white dark:bg-coal-800 text-stone-500 dark:text-stone-400 ring-stone-200 dark:ring-coal-700'}`}>
                                                {k} <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{groups[k].length}</span>
                                            </button>
                                        );
                                    })}
                                </div>

                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                                      <RapotPanel className={`lg:row-span-2 ${mobileTab === 'Hafalan' ? 'lg:block' : 'hidden lg:block'}`} cat="Hafalan" rows={groups.Hafalan} isIqro={isIqro} isDual={hasQuran2} />
                                    <RapotPanel className={mobileTab === 'Murajaah' ? 'lg:block' : 'hidden lg:block'} cat="Murajaah" rows={groups.Murajaah} isIqro={isIqro} isDual={hasQuran2} />
                                    <RapotPanel className={mobileTab === 'Tilawah' ? 'lg:block' : 'hidden lg:block'} cat="Tilawah" rows={groups.Tilawah} isIqro={isIqro} isDual={hasQuran2} />
                                </div>
                            </div>
                        )}
                    </Card>
                </div>
            </div>

            {pdfOpen && (
                <PdfModal
                    onClose={() => setPdfOpen(false)}
                    previewSrc={safeRoute('santri.rapot.pdf', { preview: 1 })}
                    downloadHref={safeRoute('santri.rapot.pdf')}
                />
            )}
        </SantriAuthenticatedLayout>
    );
}

// ── sub-komponen ──
function SummaryHead({ icon, title }) {
    return (
        <div className="px-4 sm:px-5 py-3.5 border-b border-stone-100 dark:border-coal-700">
            <h3 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200 flex items-center gap-2">
                <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400">
                    <Icon path={icon} className="w-3.5 h-3.5" />
                </span>
                {title}
            </h3>
        </div>
    );
}
function Bar({ value, tone = 'claude' }) {
    const fill = tone === 'emerald' ? 'bg-emerald-500' : 'bg-claude-500';
    return (
        <div className="h-2.5 rounded-full overflow-hidden bg-white/60 dark:bg-coal-700 ring-1 ring-stone-200/60 dark:ring-coal-600">
            <div className={`h-full rounded-full transition-all duration-700 ${fill}`} style={{ width: `${Math.max(4, value)}%` }} />
        </div>
    );
}
function Mini({ value, label }) {
    return (
        <div className="rounded-xl p-3 bg-stone-50 dark:bg-coal-700/50 ring-1 ring-stone-200 dark:ring-coal-600">
            <div className="text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-none tabular-nums">{value}</div>
            <div className="text-[11px] text-stone-500 dark:text-stone-400 mt-1">{label}</div>
        </div>
    );
}
const SEM = {
    emerald: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20',
    blue: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 ring-blue-100 dark:ring-blue-500/20',
    amber: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20',
    red: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-red-100 dark:ring-red-500/20',
};
function MiniSem({ tone, value, label }) {
    const dot = { emerald: 'bg-emerald-500', blue: 'bg-blue-500', amber: 'bg-amber-500', red: 'bg-red-500' };
    return (
        <div className={`rounded-xl p-3 ring-1 ${SEM[tone]}`}>
            <div className="text-2xl font-bold tracking-tight leading-none tabular-nums">{value}</div>
            <div className="mt-1 flex items-center gap-1 text-[11px] font-medium">
                <span className={`w-1.5 h-1.5 rounded-full ${dot[tone]}`} /> {label}
            </div>
        </div>
    );
}
function CatCount({ cat, value }) {
    const t = CAT[cat];
    return (
        <div className={`flex items-center justify-between p-2.5 rounded-lg ring-1 ${t.chip}`}>
            <span className="text-xs text-stone-700 dark:text-stone-200 flex items-center gap-2"><span className={`w-1.5 h-1.5 rounded-full ${t.dot}`} /> {cat}</span>
            <span className={`font-bold text-sm tabular-nums ${t.text}`}>{value}</span>
        </div>
    );
}

const Th = ({ children, className = '' }) => (
    <th className={`px-3 py-2.5 text-left text-[10px] font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider ${className}`}>{children}</th>
);
const Td = ({ children, className = '' }) => <td className={`px-3 py-2.5 text-xs ${className}`}>{children}</td>;

function RapotPanel({ cat, rows, className = '', isIqro = false, isDual = false }) {    const t = CAT[cat];
    const pg = usePaged(rows, t.perPage);
    return (
        <div className={`bg-white dark:bg-coal-800 rounded-2xl ring-1 ring-stone-200 dark:ring-coal-700 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 py-3 flex items-center justify-between border-b border-stone-100 dark:border-coal-700">
                <div className="flex items-center gap-2">
                    <span className={`w-2 h-2 rounded-full ${t.dot}`} />
                    <h4 className="font-bold tracking-tight text-sm text-stone-800 dark:text-stone-200">{cat}</h4>
                </div>
                <span className={`text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums ring-1 ${t.chip} ${t.text}`}>{rows.length} {isDual ? 'entri' : (isIqro ? 'jilid' : 'surat')}</span>            </div>

            {rows.length === 0 ? (
                <div className="flex-1 grid place-items-center p-8 text-center min-h-[120px]">
                    <div className="text-stone-400 dark:text-stone-600">
                        <Icon path={P.doc} className="w-9 h-9 mx-auto mb-2 opacity-40" />
                        <p className="text-sm">Tidak ada data {cat.toLowerCase()}</p>
                    </div>
                </div>
            ) : (
                <>
                    <div className="overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-stone-50 dark:bg-coal-700/40">
                                    <Th className="w-8">No</Th>
                                    <Th>{isDual ? 'Materi Setoran' : (isIqro ? 'Jilid & Halaman' : 'Surah & Ayat')}</Th>                                    <Th className="w-12 text-center">Jml</Th>
                                    <Th className="w-16 text-center">Nilai</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 dark:divide-coal-700/60">
                                {pg.slice.map((d, i) => {
                                    const idx = (pg.page - 1) * t.perPage + i + 1;
                                    const isJilid = JILID_RE.test(String(d.surat_start || ''));
                                    const surat = d.surat_start + (d.surat_start !== d.surat_end ? ` — ${d.surat_end}` : '');
                                    const detail = isJilid
                                        ? `Halaman ${d.ayat_start_min}–${d.ayat_end_max}`
                                        : `Ayat ${d.ayat_start_min}–${d.ayat_end_max}`;
                                    return (
                                        <tr key={idx} className="hover:bg-stone-50 dark:hover:bg-coal-700/40 transition-colors">
                                            <Td className="text-stone-400 dark:text-stone-500 tabular-nums">{idx}</Td>
                                            <Td>
                                                <p className="text-xs text-stone-900 dark:text-stone-100 font-semibold leading-tight">{surat}</p>
                                                <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-0.5">{detail}</p>
                                            </Td>
                                            <Td className="text-center">
                                                <span className="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 text-stone-600 dark:text-stone-300 tabular-nums">{d.jumlah_setoran}×</span>
                                            </Td>
                                            <Td className="text-center">
                                                <span className={`inline-flex items-center justify-center font-bold tabular-nums rounded-lg px-2 py-1 text-xs ${nilaiTone(d.rata_nilai)}`}>{d.rata_nilai}</span>
                                            </Td>
                                        </tr>
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

function Pagination({ pg }) {
    if (pg.totalPages <= 1) return null;
    const { page, setPage, totalPages, total, perPage } = pg;
    const from = Math.min((page - 1) * perPage + 1, total);
    const to = Math.min(page * perPage, total);
    let sp = Math.max(1, page - 2), ep = Math.min(totalPages, sp + 4);
    if (ep - sp < 4) sp = Math.max(1, ep - 4);
    const nums = []; for (let i = sp; i <= ep; i++) nums.push(i);

    const btn = 'px-2.5 py-1 text-xs rounded-md font-medium transition';
    const normal = `${btn} text-stone-500 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-coal-700 hover:text-stone-900 dark:hover:text-stone-100`;
    const active = `${btn} font-semibold bg-claude-500 text-white`;
    const disabled = `${btn} text-stone-300 dark:text-stone-600 cursor-not-allowed`;

    return (
        <div className="px-3 py-2.5 border-t border-stone-100 dark:border-coal-700 bg-stone-50 dark:bg-coal-700/30">
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

function PdfModal({ onClose, previewSrc, downloadHref }) {
    const [loaded, setLoaded] = useState(false);
    useEffect(() => {
        const onKey = (e) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; };
    }, [onClose]);

    return (
        <div className="fixed inset-0 z-[200] bg-black/50 backdrop-blur-sm flex items-stretch sm:items-center justify-center p-0 sm:p-4" onClick={onClose}>
            <div onClick={(e) => e.stopPropagation()} className="w-full sm:max-w-5xl bg-white dark:bg-coal-800 sm:rounded-2xl ring-1 ring-stone-200 dark:ring-coal-700 shadow-2xl overflow-hidden flex flex-col">
                <div className="flex items-center justify-between px-4 sm:px-5 py-3.5 border-b border-stone-100 dark:border-coal-700 shrink-0">
                    <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 flex items-center gap-2">
                        <span className="w-7 h-7 rounded-lg grid place-items-center bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400"><Icon path={P.pdf} className="w-3.5 h-3.5" /></span>
                        Preview Rapot PDF
                    </h3>
                    <div className="flex items-center gap-2">
                        <a href={downloadHref} className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-100 dark:ring-emerald-500/20 text-xs font-semibold hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition">
                            <Icon path={P.download} className="w-3.5 h-3.5" /> Download
                        </a>
                        <button onClick={onClose} className="p-1.5 rounded-lg text-stone-500 hover:bg-stone-100 dark:hover:bg-coal-700 transition"><Icon path={P.close} className="w-5 h-5" /></button>
                    </div>
                </div>
                <div className="flex-1 p-3 sm:p-4 relative">
                    {!loaded && (
                        <div className="absolute inset-3 sm:inset-4 rounded-lg grid place-items-center bg-stone-50 dark:bg-coal-900 ring-1 ring-stone-200 dark:ring-coal-700">
                            <div className="text-center">
                                <span className="inline-block w-8 h-8 rounded-full border-2 border-claude-500/30 border-t-claude-500 animate-spin" />
                                <p className="mt-3 text-xs text-stone-500 dark:text-stone-400">Menyiapkan PDF…</p>
                            </div>
                        </div>
                    )}
                    <iframe
                        src={previewSrc}
                        onLoad={() => setLoaded(true)}
                        title="Rapot PDF"
                        className="w-full rounded-lg bg-white ring-1 ring-stone-200"
                        style={{ height: 'calc(100vh - 160px)', minHeight: 320 }}
                    />
                </div>
            </div>
        </div>
    );
}
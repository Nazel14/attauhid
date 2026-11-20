import { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import SantriAuthenticatedLayout from '@/Layouts/SantriAuthenticatedLayout';
import LaporanModal from '@/Components/LaporanModal';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const Icon = ({ path, className = 'w-4 h-4', fill = false }) =>
    fill ? (
        <svg className={className} fill="currentColor" viewBox="0 0 20 20"><path d={path} /></svg>
    ) : (
        <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.7" d={path} />
        </svg>
    );
const P = {
    book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    star: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    filter: 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    doc: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    pdf: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    note: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    audio: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z',
    close: 'M6 18L18 6M6 6l12 12',
    download: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
    user: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    flag: 'M3 3v18M3 4.5h12.75l-2.25 3 2.25 3H3',
};

// ── helpers ──
const fmtDate = (d) => {
    try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
    catch { return d; }
};

/** Deteksi setoran berbasis jilid: Iqro / Aisar / Ummi */
const JILID_RE = /^(Iqro|Aisar|Ummi)\s+Jilid\s+(\d+)/i;

function formatRange(s) {
    const m = JILID_RE.exec(String(s.surat_start || ''));
    if (m) {
        const metode = m[1].charAt(0).toUpperCase() + m[1].slice(1).toLowerCase();
        const h1 = s.ayat_start, h2 = s.ayat_end;
        const sep = String(h1) === String(h2) ? '' : '–' + h2;
        return { range: `${metode} Jilid ${m[2]}`, sub: `Halaman ${h1}${sep}` };
    }
    let range = s.surat_start;
    if (s.surat_start !== s.surat_end) range += ' — ' + s.surat_end;
    return { range, sub: `Ayat ${s.ayat_start}–${s.ayat_end}` };
}
function nilaiTone(n) {
    if (n >= 90) return 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20';
    // ≥75 netral — sama dengan Rapot & Setoran guru
    if (n >= 75) return 'bg-cream-200 dark:bg-coal-600 text-stone-700 dark:text-stone-200 ring-1 ring-cream-300 dark:ring-coal-500';
    return 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20';
}
function usePaged(rows, perPage) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
    useEffect(() => { if (page > totalPages) setPage(1); }, [totalPages, page]);
    const slice = rows.slice((page - 1) * perPage, page * perPage);
    return { page, setPage, totalPages, slice, total: rows.length, perPage };
}

const Card = ({ className = '', children }) => (
    <div className={`bg-white dark:bg-coal-700 rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 ${className}`}>{children}</div>
);
const inputCls =
    // Field sama dengan halaman guru: coal-600/40 di dark (tidak hitam pekat).
    'w-full px-3 py-2.5 text-sm rounded-xl bg-white dark:bg-coal-600/40 text-stone-900 dark:text-stone-100 border border-cream-300 dark:border-coal-600 focus:outline-none focus:border-claude-500 focus:ring-2 focus:ring-claude-500/20 transition [color-scheme:light] dark:[color-scheme:dark]';

/* header card kecil — icon tile w-6 h-6 rounded-lg persis guru */
function CardHead({ icon, title, hint, right }) {
    return (
        <div className="flex items-center gap-2 mb-4">
            <span className="w-6 h-6 bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400 flex-shrink-0">
                {icon}
            </span>
            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">{title}</h2>
            {hint && <span className="ml-auto text-[11px] text-stone-400">{hint}</span>}
            {right && <span className="ml-auto">{right}</span>}
        </div>
    );
}

export default function Detail({
    santri, setoranHafalan = [], setoranMurajaah = [], setoranTilawah = [],
    statistik = {}, kategori = '', tanggalMulai = '', tanggalAkhir = '',
   tahunAjaranAktif = null, isIqro = false, totalHalamanIqro = 0, metodeLabel = 'Iqro',
    hasQuranSecondary = false, totalJuzHafalan = 0, targetJuz = 0,
    tahunAjaranList = [], tahunAjaranId = '',
}) {
    // tanggal_mulai / tanggal_akhir tidak lagi dikirim — periode ditentukan
    // sepenuhnya oleh tahun ajaran yang dipilih.
    const [f, setF] = useState({
        kategori: kategori || '',
        tahun_ajaran_id: tahunAjaranId || '',
    });
    const [mobileTab, setMobileTab] = useState('hafalan');
    const [modal, setModal] = useState(null);

    const apply = () =>
        router.get(route('santri.setorans.detail'), f, { preserveScroll: true, preserveState: true });

    const taTerpilih = tahunAjaranList.find((t) => String(t.id) === String(f.tahun_ajaran_id));

    const photo = santri?.user?.profile_photo;
    const isPr = santri?.gender === 'P' || santri?.gender === 'Perempuan';
    const showOn = (key) => (mobileTab === key ? 'lg:block' : 'hidden lg:block');

    return (
        <SantriAuthenticatedLayout>
            <Head title="Detail Setoran" />

            <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0">
                        <Icon path={P.book} className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Detail Setoran</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Riwayat setoran hafalan, murajaah &amp; tilawah Anda</p>
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
                                <img src={`/storage/${photo}`} alt="Foto" className="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0" />
                            ) : (
                                <span className={`w-12 h-12 sm:w-14 sm:h-14 rounded-full ring-1 flex items-center justify-center text-lg font-bold flex-shrink-0 ${isPr ? 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400' : 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400'}`}>
                                    {(santri?.name ?? '?').charAt(0).toUpperCase()}
                                </span>
                            )}
                            <div className="min-w-0 flex-1">
                                <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri?.name}</h3>
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{santri?.kelas?.name ?? '-'}</span>
                                    {isIqro && (
                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-600 dark:text-stone-300 font-semibold ring-1 ring-cream-300 dark:ring-coal-500">
                                            {metodeLabel} · Jilid {santri?.target_jilid ?? 1}
                                            {hasQuranSecondary && <span className="opacity-40 mx-1">+</span>}
                                            {hasQuranSecondary && "Al-Qur'an"}
                                        </span>
                                    )}
                                    <span className="text-xs text-stone-500 dark:text-stone-400 inline-flex items-center gap-1">
                                        <Icon path={P.user} className="w-3 h-3" /> {santri?.guru?.name ?? '-'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Filter */}
                    <Card className="p-4 sm:p-5">
                        <CardHead
                            icon={<Icon path={P.filter} className="w-3.5 h-3.5" />}
                            title="Filter Data"
                            hint={taTerpilih ? `${taTerpilih.tanggal_mulai} s/d ${taTerpilih.tanggal_selesai ?? '-'}` : null}
                        />
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label className="block text-xs font-medium text-stone-500 dark:text-stone-400 mb-1.5">Tahun Ajaran &amp; Semester</label>
                                <select
                                    value={f.tahun_ajaran_id}
                                    onChange={(e) => setF({ ...f, tahun_ajaran_id: e.target.value })}
                                    className={inputCls}
                                >
                                    {tahunAjaranList.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.label}{t.is_aktif ? ' • Aktif' : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-stone-500 dark:text-stone-400 mb-1.5">Kategori</label>
                                <select value={f.kategori} onChange={(e) => setF({ ...f, kategori: e.target.value })} className={inputCls}>
                                    <option value="">Semua Kategori</option>
                                    <option value="Hafalan">Hafalan</option>
                                    <option value="Murajaah">Murajaah</option>
                                    <option value="Tilawah">Tilawah</option>
                                </select>
                            </div>
                            <div className="flex items-end">
                                <button onClick={apply} className="w-full px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold flex items-center justify-center gap-2 transition-colors shadow-sm active:scale-95">
                                    <Icon path={P.search} className="w-4 h-4" /> Terapkan
                                </button>
                            </div>
                        </div>

                        <p className="mt-2.5 text-[11px] text-stone-400 dark:text-stone-500">
                            Periode:{' '}
                            <span className="font-semibold text-stone-600 dark:text-stone-300">
                                {tanggalMulai} s/d {tanggalAkhir}
                            </span>{' '}
                            — mengikuti tahun ajaran yang dipilih.
                        </p>
                    </Card>

                    {/* Stat cards */}
                     <div className={`grid grid-cols-1 gap-3 ${hasQuranSecondary ? 'sm:grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-3'}`}>
                        <StatCard tone="claude" icon={<Icon path={P.book} className="w-5 h-5" />} label="Total Setoran" value={statistik.total_setoran ?? 0} sub="kali setoran" />
                        <StatCard tone="amber" icon={<Icon path={P.star} className="w-5 h-5" />} label="Rata Nilai" value={statistik.rata_nilai ?? 0} sub="dari 100 poin" />
                        {isIqro ? (
                            <StatCard
                                tone="stone"
                                icon={<Icon path={P.book} className="w-5 h-5" />}
                                label={`Halaman ${metodeLabel}`}
                                value={totalHalamanIqro}
                                sub={`halaman hafalan · Jilid ${santri?.target_jilid ?? 1}`}
                            />
                        ) : (
                            <StatCard tone="stone" icon={<Icon path={P.book} className="w-5 h-5" />} label="Total Juz" value={statistik.total_juz ?? 0} sub="juz tercapai" />
                        )}
                        {hasQuranSecondary && (
                            <StatCard
                                tone="emerald"
                                icon={<Icon path={P.book} className="w-5 h-5" />}
                                label="Juz Al-Qur'an"
                                value={totalJuzHafalan}
                                sub={targetJuz > 0 ? `dari target ${targetJuz} juz` : 'juz hafalan'}
                            />
                        )}
                    </div>

                    {/* Mobile tab switcher */}
                    <div className="flex gap-1.5 lg:hidden overflow-x-auto pb-1">
                        <TabBtn active={mobileTab === 'hafalan'} onClick={() => setMobileTab('hafalan')} tone="emerald" label="Hafalan" count={setoranHafalan.length} />
                        <TabBtn active={mobileTab === 'murajaah'} onClick={() => setMobileTab('murajaah')} tone="amber" label="Murajaah" count={setoranMurajaah.length} />
                        <TabBtn active={mobileTab === 'tilawah'} onClick={() => setMobileTab('tilawah')} tone="orange" label="Tilawah" count={setoranTilawah.length} />
                    </div>

                    {/* Panels */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                         <SetoranPanel className={`lg:row-span-2 ${showOn('hafalan')}`} tone="emerald" title="Hafalan" rows={setoranHafalan} perPage={15} onFile={setModal} isIqro={isIqro} isDual={hasQuranSecondary} />
                        <SetoranPanel className={showOn('murajaah')} tone="amber" title="Murajaah" rows={setoranMurajaah} perPage={6} onFile={setModal} isIqro={isIqro} isDual={hasQuranSecondary} />
                        <SetoranPanel className={showOn('tilawah')} tone="orange" title="Tilawah" rows={setoranTilawah} perPage={6} onFile={setModal} isIqro={isIqro} isDual={hasQuranSecondary} />
                    </div>
                </div>
            </div>

            {/* Modals */}
            {modal?.type === 'audio' && (
                <Modal onClose={() => setModal(null)} title="Rekaman Audio" tone="claude" icon={P.audio} iconFill>
                    <audio controls autoPlay className="w-full rounded-lg">
                        <source src={`/santri/setorans/${modal.id}/audio`} type="audio/webm" />
                    </audio>
                </Modal>
            )}
            {modal?.type === 'pdf' && (
                <Modal onClose={() => setModal(null)} title="Preview PDF Koreksi" tone="stone" icon={P.pdf} wide
                    action={
                        <a href={`/santri/setorans/${modal.id}/pdf`} download className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition active:scale-95">
                            <Icon path={P.download} className="w-3.5 h-3.5" /> Download
                        </a>
                    }>
                    <iframe src={`/santri/setorans/${modal.id}/pdf`} title="PDF" className="w-full rounded-lg bg-white ring-1 ring-cream-300 dark:ring-coal-600" style={{ height: 'calc(100vh - 220px)', minHeight: 320 }} />
                </Modal>
            )}
            {modal?.type === 'note' && (
                <Modal onClose={() => setModal(null)} title="Catatan Guru" tone="amber" icon={P.note}>
                    <p className="text-xs text-stone-400 dark:text-stone-500 mb-3">Dari: <span className="font-semibold text-stone-600 dark:text-stone-300">{modal.guru}</span></p>
                    <div className="rounded-xl p-4 bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20">
                        <p className="text-xs sm:text-sm text-stone-800 dark:text-stone-200 whitespace-pre-wrap leading-relaxed">{modal.text}</p>
                    </div>
                </Modal>
            )}
            {modal?.type === 'lapor' && (
                <LaporanModal
                    key={modal.setoran.id}
                    open={true}
                    onClose={() => setModal(null)}
                    reportableType="setoran"
                    reportableId={modal.setoran.id}
                    konteks={`${formatRange(modal.setoran).range} · ${formatRange(modal.setoran).sub} · ${fmtDate(modal.setoran.tanggal)} · Nilai ${modal.setoran.nilai}`}
                />
            )}
        </SantriAuthenticatedLayout>
    );
}

// ── Stat card — pola guru ──
function StatCard({ icon, label, value, sub, tone = 'stone' }) {
    const toneMap = {
        stone: 'bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400',
        claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
        emerald: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
        amber: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400',
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

// Kategori — SAMA dengan halaman guru: Hafalan emerald, Murajaah amber, Tilawah oranye
const TONE = {
    emerald: { dot: 'bg-emerald-500', text: 'text-emerald-700 dark:text-emerald-400', chip: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20' },
    amber:   { dot: 'bg-amber-500',   text: 'text-amber-700 dark:text-amber-400',     chip: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20' },
    orange:  { dot: 'bg-orange-500',  text: 'text-orange-700 dark:text-orange-400',   chip: 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20' },
};

function TabBtn({ active, onClick, tone, label, count }) {
    const t = TONE[tone] ?? TONE.emerald;
    return (
        <button onClick={onClick} className={`shrink-0 px-3 py-2 text-xs font-semibold rounded-xl transition-colors ring-1 ${active ? `${t.chip} ${t.text}` : 'bg-white dark:bg-coal-700 text-stone-500 dark:text-stone-400 ring-cream-300 dark:ring-coal-600'}`}>
            {label} <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{count}</span>
        </button>
    );
}

// ── Panel per kategori ──
function SetoranPanel({ tone, title, rows, perPage, onFile, className = '', isIqro = false, isDual = false }) {    const t = TONE[tone] ?? TONE.emerald;
    const pg = usePaged(rows, perPage);

    return (
        <div className={`bg-white dark:bg-coal-700 rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 py-3 flex items-center justify-between border-b border-cream-300 dark:border-coal-600">
                <div className="flex items-center gap-2">
                    <span className={`w-2 h-2 rounded-full ${t.dot}`} />
                    <h4 className="font-bold tracking-tight text-sm text-stone-800 dark:text-stone-200">{title}</h4>
                </div>
                <span className={`text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums ring-1 ${t.chip} ${t.text}`}>{rows.length} setoran</span>
            </div>

            {rows.length === 0 ? (
                <div className="flex-1 grid place-items-center p-8 text-center min-h-[120px]">
                    <div className="text-stone-400 dark:text-stone-600">
                        <Icon path={P.doc} className="w-9 h-9 mx-auto mb-2 opacity-40" />
                        <p className="text-sm">Tidak ada data {title.toLowerCase()}</p>
                    </div>
                </div>
            ) : (
                <>
                    <div className="overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-cream-100 dark:bg-coal-600/40">
                                    <Th className="w-8">No</Th>
                                    <Th className="w-24">Tanggal</Th>
                                    <Th>{isDual ? 'Materi Setoran' : (isIqro ? 'Jilid & Halaman' : 'Surah & Ayat')}</Th>                                    <Th className="w-16 text-center">Nilai</Th>
                                    <Th className="w-20 text-center">Aksi</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-cream-200 dark:divide-coal-600/60">
                                {pg.slice.map((s, i) => {
                                    const fmt = formatRange(s);
                                    const idx = (pg.page - 1) * perPage + i + 1;
                                    return (
                                        <tr key={s.id} className="hover:bg-cream-100 dark:hover:bg-coal-600/30 transition-colors">
                                            <Td className="text-stone-400 dark:text-stone-500 tabular-nums">{idx}</Td>
                                            <Td className="text-stone-500 dark:text-stone-400 whitespace-nowrap tabular-nums">{fmtDate(s.tanggal)}</Td>
                                            <Td>
                                                <p className="text-xs text-stone-900 dark:text-stone-100 font-semibold leading-tight">{fmt.range}</p>
                                                {fmt.sub && <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-0.5">{fmt.sub}</p>}
                                            </Td>
                                            <Td className="text-center">
                                                <span className={`inline-flex items-center justify-center font-bold tabular-nums rounded-lg px-2 py-1 text-xs ${nilaiTone(s.nilai)}`}>{s.nilai}</span>
                                            </Td>
                                            <Td>
                                                <div className="flex items-center justify-center gap-0.5">
                                                    {s.audio_path && <ActBtn tone="claude" title="Audio" onClick={() => onFile({ type: 'audio', id: s.id })} icon={P.audio} fill />}
                                                    {s.koreksi_pdf_path && <ActBtn tone="red" title="PDF" onClick={() => onFile({ type: 'pdf', id: s.id })} icon={P.pdf} />}
                                                    {s.catatan_guru && <ActBtn tone="amber" title="Catatan" onClick={() => onFile({ type: 'note', text: s.catatan_guru, guru: s.guru?.name ?? 'Guru' })} icon={P.note} />}
                                                    <ActBtn tone="stone" title="Laporkan kesalahan data" onClick={() => onFile({ type: 'lapor', setoran: s })} icon={P.flag} />
                                                </div>
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

const Th = ({ children, className = '' }) => (
    <th className={`px-3 py-2.5 text-left text-[11px] font-semibold text-stone-500 dark:text-stone-400 ${className}`}>{children}</th>
);
const Td = ({ children, className = '' }) => (
    <td className={`px-3 py-2.5 text-xs ${className}`}>{children}</td>
);

const ACT = {
    claude: 'text-claude-600 dark:text-claude-400 hover:bg-claude-100 dark:hover:bg-claude-500/15',
    red: 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10',
    amber: 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10',
    stone: 'text-stone-400 dark:text-stone-500 hover:text-claude-500 hover:bg-claude-100 dark:hover:bg-claude-500/15',
};
function ActBtn({ tone, title, onClick, icon, fill }) {
    return (
        <button onClick={onClick} title={title} className={`p-1.5 rounded-lg transition-colors active:scale-90 ${ACT[tone]}`}>
            <Icon path={icon} className="w-4 h-4" fill={fill} />
        </button>
    );
}

// ── Pagination ──
function Pagination({ pg }) {
    if (pg.totalPages <= 1) return null;
    const { page, setPage, totalPages, total, perPage } = pg;
    const from = Math.min((page - 1) * perPage + 1, total);
    const to = Math.min(page * perPage, total);

    let sp = Math.max(1, page - 2), ep = Math.min(totalPages, sp + 4);
    if (ep - sp < 4) sp = Math.max(1, ep - 4);
    const nums = [];
    for (let i = sp; i <= ep; i++) nums.push(i);

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
                    {nums.map((i) => (
                        <button key={i} onClick={() => setPage(i)} className={i === page ? active : normal}>{i}</button>
                    ))}
                    {ep < totalPages && <>{ep < totalPages - 1 && <span className="text-xs text-stone-400 dark:text-stone-600">…</span>}<button onClick={() => setPage(totalPages)} className={normal}>{totalPages}</button></>}
                    <button disabled={page === totalPages} onClick={() => setPage(page + 1)} className={page === totalPages ? disabled : normal}>›</button>
                </div>
            </div>
        </div>
    );
}

// ── Modal ──
const MODAL_TONE = {
    claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
    stone: 'bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400',
    amber: 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400',
};
function Modal({ onClose, title, tone = 'claude', icon, iconFill, wide, action, children }) {
    // onClose disimpan di ref: kalau jadi dependency, listener Escape & kunci
    // scroll dilepas-pasang setiap parent render (onClose = arrow function baru).
    const closeRef = useRef(onClose);
    closeRef.current = onClose;
    useEffect(() => {
        const onKey = (e) => e.key === 'Escape' && closeRef.current();
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; };
    }, []);

    return (
        <div className="fixed inset-0 z-[200] bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4" onClick={onClose}>
            <div onClick={(e) => e.stopPropagation()} className={`w-full ${wide ? 'sm:max-w-5xl' : 'sm:max-w-lg'} bg-white dark:bg-coal-700 rounded-t-2xl sm:rounded-2xl ring-1 ring-cream-300 dark:ring-coal-600 shadow-2xl overflow-hidden`}>
                <div className="flex items-center justify-between px-5 py-4 border-b border-cream-300 dark:border-coal-600">
                    <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 flex items-center gap-2">
                        <span className={`w-7 h-7 rounded-lg grid place-items-center ${MODAL_TONE[tone]}`}>
                            <Icon path={icon} className="w-3.5 h-3.5" fill={iconFill} />
                        </span>
                        {title}
                    </h3>
                    <div className="flex items-center gap-2">
                        {action}
                        <button onClick={onClose} className="w-8 h-8 rounded-lg grid place-items-center bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition">
                            <Icon path={P.close} className="w-4 h-4" />
                        </button>
                    </div>
                </div>
                <div className="p-4 sm:p-5">{children}</div>
            </div>
        </div>
    );
}
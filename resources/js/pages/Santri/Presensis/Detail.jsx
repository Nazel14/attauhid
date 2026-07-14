import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
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
    filter: 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    check: 'M5 13l4 4L19 7',
    checkCircle: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    alert: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    trend: 'M3 17l6-6 4 4 8-8m0 0h-5m5 0v5',
    user: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
};

// ── helpers ──
const fmtDate = (d) => { try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return d; } };
const dayName = (d) => { try { return new Date(d).toLocaleDateString('id-ID', { weekday: 'long' }); } catch { return ''; } };

function usePaged(rows, perPage) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
    useEffect(() => { if (page > totalPages) setPage(1); }, [totalPages, page]);
    return { page, setPage, totalPages, slice: rows.slice((page - 1) * perPage, page * perPage), total: rows.length, perPage };
}

const Card = ({ className = '', children }) => (
    <div className={`bg-white dark:bg-coal-800 rounded-2xl ring-1 ring-stone-200 dark:ring-coal-700 ${className}`}>{children}</div>
);
const inputCls =
    'w-full px-3 py-2.5 text-sm rounded-xl bg-stone-50 dark:bg-coal-700 text-stone-900 dark:text-stone-100 ring-1 ring-stone-200 dark:ring-coal-600 focus:outline-none focus:ring-2 focus:ring-claude-500 transition [color-scheme:light] dark:[color-scheme:dark]';

// Status → warna semantik (dipertahankan)
const STATUS = {
    Hadir: { chip: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20', dot: 'bg-emerald-500', bar: 'bg-emerald-500' },
    Izin:  { chip: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 ring-blue-100 dark:ring-blue-500/20',                    dot: 'bg-blue-500',    bar: 'bg-blue-500' },
    Sakit: { chip: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-amber-100 dark:ring-amber-500/20',              dot: 'bg-amber-500',   bar: 'bg-amber-500' },
    Alpha: { chip: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-red-100 dark:ring-red-500/20',                          dot: 'bg-red-500',     bar: 'bg-red-500' },
};

/* MiniStat — persis pola guru */
function MiniStat({ tone, value, label, sub }) {
    const map = {
        emerald: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400',
        blue: 'bg-blue-50 dark:bg-blue-500/10 ring-blue-100 dark:ring-blue-500/20 text-blue-600 dark:text-blue-400',
        amber: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-600 dark:text-amber-400',
        red: 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-600 dark:text-red-400',
    };
    const dot = { emerald: 'bg-emerald-500', blue: 'bg-blue-500', amber: 'bg-amber-500', red: 'bg-red-500' };
    return (
        <div className={`rounded-2xl ring-1 p-3.5 sm:p-4 text-center ${map[tone]}`}>
            <p className="text-2xl sm:text-3xl font-bold tracking-tight leading-none tabular-nums">{value}</p>
            <p className="mt-1.5 flex items-center justify-center gap-1 text-[11px] font-medium">
                <span className={`w-1.5 h-1.5 rounded-full ${dot[tone]}`} /> {label}
            </p>
            {sub && <p className="text-[10px] opacity-70 mt-0.5">{sub}</p>}
        </div>
    );
}

/* header card kecil — icon tile w-6 h-6 rounded-lg persis guru */
function CardHead({ icon, title, hint }) {
    return (
        <div className="flex items-center gap-2 mb-4">
            <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400 flex-shrink-0">
                {icon}
            </span>
            <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">{title}</h2>
            {hint && <span className="ml-auto text-[11px] text-stone-400">{hint}</span>}
        </div>
    );
}

export default function Detail({
    santri, presensisHadir = [], presensisAbsen = [],
    statistik = {}, bulan = '', status = '', tahunAjaranAktif = null,
    tahunAjaranList = [], tahunAjaranId = '', periodeMulai = '', periodeAkhir = '',
}) {
    const [f, setF] = useState({
        bulan,
        status: status || '',
        tahun_ajaran_id: tahunAjaranId || '',
    });
    const [mobileTab, setMobileTab] = useState('hadir');

    const apply = () => router.get(route('santri.presensis.detail'), f, { preserveScroll: true, preserveState: true });
    const reset = () => {
        setF({ bulan: f.bulan, status: '', tahun_ajaran_id: '' });
        router.get(route('santri.presensis.detail'), { bulan: f.bulan }, { preserveScroll: true });
    };

    const taTerpilih = tahunAjaranList.find((t) => String(t.id) === String(f.tahun_ajaran_id));

    const total = statistik.total ?? 0;
    const pct = total > 0 ? Math.round((statistik.hadir / total) * 1000) / 10 : 0;
    const pctTone = pct >= 80 ? 'text-emerald-600 dark:text-emerald-400' : pct >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400';

    const photo = santri?.user?.profile_photo;
    const isPr = santri?.gender === 'P' || santri?.gender === 'Perempuan';
    const seg = (n) => (total > 0 ? Math.round((n / total) * 1000) / 10 : 0);

    return (
        <SantriAuthenticatedLayout>
            <Head title="Detail Presensi" />

            <div className="max-w-6xl mx-auto px-3 sm:px-4 py-5">

                {/* ===== HEADER ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 flex items-center justify-center flex-shrink-0">
                        <Icon path={P.clock} className="w-6 h-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Detail Presensi</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">Rekap kehadiran &amp; ketidakhadiran Anda</p>
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
                                <h3 className="text-sm sm:text-base font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">{santri?.name}</h3>
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-claude-100 dark:bg-claude-500/15 text-claude-700 dark:text-claude-400 font-semibold">{santri?.kelas?.name ?? '-'}</span>
                                    <span className="text-xs text-stone-500 dark:text-stone-400 inline-flex items-center gap-1"><Icon path={P.user} className="w-3 h-3" /> {santri?.guru?.name ?? '-'}</span>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Filter */}
                    <Card className="p-4 sm:p-5">
                        <CardHead
                            icon={<Icon path={P.filter} className="w-3.5 h-3.5" />}
                            title="Filter Data"
                            hint={periodeMulai ? `Periode: ${periodeMulai} s/d ${periodeAkhir}` : null}
                        />
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label className="block text-xs font-medium text-stone-500 dark:text-stone-400 mb-1.5">Tahun Ajaran &amp; Semester</label>
                                <select
                                    value={f.tahun_ajaran_id}
                                    onChange={(e) => setF({ ...f, tahun_ajaran_id: e.target.value })}
                                    className={inputCls}
                                >
                                    <option value="">Per Bulan</option>
                                    {tahunAjaranList.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.label}{t.is_aktif ? ' • Aktif' : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-stone-500 dark:text-stone-400 mb-1.5">Bulan</label>
                                <input
                                    type="month"
                                    value={f.bulan}
                                    onChange={(e) => setF({ ...f, bulan: e.target.value })}
                                    disabled={!!f.tahun_ajaran_id}
                                    className={`${inputCls} disabled:opacity-50 disabled:cursor-not-allowed`}
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-stone-500 dark:text-stone-400 mb-1.5">Status</label>
                                <select value={f.status} onChange={(e) => setF({ ...f, status: e.target.value })} className={inputCls}>
                                    <option value="">Semua Status</option>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Alpha">Alpha</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <button onClick={apply} className="flex-1 px-4 py-2.5 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold flex items-center justify-center gap-2 transition-colors shadow-sm active:scale-95">
                                    <Icon path={P.search} className="w-4 h-4" /> Terapkan
                                </button>
                                {(status || tahunAjaranId) && (
                                    <button onClick={reset} className="px-4 py-2.5 rounded-xl bg-white dark:bg-coal-800 text-stone-700 dark:text-stone-200 ring-1 ring-stone-200 dark:ring-coal-700 text-sm font-medium hover:bg-stone-100 dark:hover:bg-coal-700 transition whitespace-nowrap">Reset</button>
                                )}
                            </div>
                        </div>
                        {taTerpilih && (
                            <p className="mt-2.5 text-[11px] text-stone-400 dark:text-stone-500">
                                Menampilkan seluruh presensi pada <span className="font-semibold text-stone-600 dark:text-stone-300">{taTerpilih.label}</span> ({taTerpilih.tanggal_mulai} s/d {taTerpilih.tanggal_selesai ?? '-'}). Filter bulan dinonaktifkan.
                            </p>
                        )}
                    </Card>

                    {/* Mini stats — pola guru */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <MiniStat tone="emerald" value={statistik.hadir ?? 0} label="Hadir" sub="hari hadir" />
                        <MiniStat tone="blue" value={statistik.izin ?? 0} label="Izin" sub="hari izin" />
                        <MiniStat tone="amber" value={statistik.sakit ?? 0} label="Sakit" sub="hari sakit" />
                        <MiniStat tone="red" value={statistik.alpha ?? 0} label="Alpha" sub="hari alpha" />
                    </div>

                    {/* Progress kehadiran */}
                    <Card className="p-4 sm:p-5">
                        <div className="flex justify-between items-start mb-3">
                            <div className="flex items-center gap-2">
                                <span className="w-6 h-6 bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400 flex-shrink-0">
                                    <Icon path={P.trend} className="w-3.5 h-3.5" />
                                </span>
                                <div>
                                    <h2 className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Persentase Kehadiran</h2>
                                    <p className="text-[11px] text-stone-400 dark:text-stone-500">
                                        {taTerpilih ? `Distribusi status presensi — ${taTerpilih.label}` : 'Distribusi status presensi bulan ini'}
                                    </p>
                                </div>
                            </div>
                            <div className="text-right">
                                <div className={`text-2xl sm:text-3xl font-bold tracking-tight tabular-nums leading-none ${pctTone}`}>{pct}%</div>
                                <p className="text-[10px] text-stone-400 dark:text-stone-500 mt-1">kehadiran</p>
                            </div>
                        </div>
                        <div className="w-full h-3 rounded-full bg-stone-100 dark:bg-coal-700 flex overflow-hidden ring-1 ring-stone-200 dark:ring-coal-600">
                            {['Hadir', 'Izin', 'Sakit', 'Alpha'].map((k) => {
                                const v = seg(statistik[k.toLowerCase()] ?? 0);
                                return v > 0 ? <div key={k} className={`h-full ${STATUS[k].bar} transition-all duration-700`} style={{ width: `${v}%` }} /> : null;
                            })}
                        </div>
                        <div className="grid grid-cols-2 sm:flex sm:flex-wrap gap-x-4 gap-y-2 mt-3.5">
                            {['Hadir', 'Izin', 'Sakit', 'Alpha'].map((k) => (
                                <div key={k} className="flex items-center gap-1.5 text-[11px] text-stone-500 dark:text-stone-400">
                                    <span className={`w-2.5 h-2.5 rounded-full ${STATUS[k].dot}`} />
                                    {k} <span className="text-stone-900 dark:text-stone-100 font-semibold tabular-nums">({statistik[k.toLowerCase()] ?? 0})</span>
                                </div>
                            ))}
                            <div className="col-span-2 sm:ml-auto text-[11px] text-stone-400 dark:text-stone-500 font-medium">
                                Total: <span className="text-stone-900 dark:text-stone-100 font-semibold tabular-nums">{total}</span> hari
                            </div>
                        </div>
                    </Card>

                    {/* Mobile tabs */}
                    <div className="flex gap-2 lg:hidden">
                        <button onClick={() => setMobileTab('hadir')} className={`flex-1 py-2.5 text-xs font-semibold rounded-xl transition-colors ring-1 ${mobileTab === 'hadir' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-emerald-100 dark:ring-emerald-500/20' : 'bg-white dark:bg-coal-800 text-stone-500 dark:text-stone-400 ring-stone-200 dark:ring-coal-700'}`}>
                            Kehadiran <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{presensisHadir.length}</span>
                        </button>
                        <button onClick={() => setMobileTab('absen')} className={`flex-1 py-2.5 text-xs font-semibold rounded-xl transition-colors ring-1 ${mobileTab === 'absen' ? 'bg-stone-100 dark:bg-coal-700 text-stone-700 dark:text-stone-300 ring-stone-200 dark:ring-coal-600' : 'bg-white dark:bg-coal-800 text-stone-500 dark:text-stone-400 ring-stone-200 dark:ring-coal-700'}`}>
                            Ketidakhadiran <span className="ml-1 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums bg-black/5 dark:bg-white/10">{presensisAbsen.length}</span>
                        </button>
                    </div>

                    {/* Panels */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                        <HadirPanel className={mobileTab === 'hadir' ? 'lg:block' : 'hidden lg:block'} rows={presensisHadir} />
                        <AbsenPanel className={mobileTab === 'absen' ? 'lg:block' : 'hidden lg:block'} rows={presensisAbsen} />
                    </div>
                </div>
            </div>
        </SantriAuthenticatedLayout>
    );
}

const Th = ({ children, className = '' }) => (
    <th className={`px-3 sm:px-4 py-3 text-left text-[10px] font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider ${className}`}>{children}</th>
);
const Td = ({ children, className = '' }) => <td className={`px-3 sm:px-4 py-3 ${className}`}>{children}</td>;

// ── Panel Kehadiran ──
function HadirPanel({ rows, className = '' }) {
    const pg = usePaged(rows, 15);
    return (
        <div className={`bg-white dark:bg-coal-800 rounded-2xl ring-1 ring-stone-200 dark:ring-coal-700 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 sm:px-5 py-3.5 flex items-center justify-between border-b border-stone-100 dark:border-coal-700">
                <div className="flex items-center gap-2.5">
                    <span className="w-8 h-8 rounded-lg grid place-items-center bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                        <Icon path={P.check} className="w-4 h-4" />
                    </span>
                    <div>
                        <h4 className="font-bold tracking-tight text-sm text-stone-900 dark:text-stone-100">Kehadiran</h4>
                        <p className="text-[10px] text-stone-400 dark:text-stone-500 leading-tight">Hari masuk untuk setoran</p>
                    </div>
                </div>
                <span className="text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20">{rows.length} hari</span>
            </div>

            {rows.length === 0 ? (
                <Empty text="Tidak ada data kehadiran" />
            ) : (
                <>
                    <div className="overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead><tr className="bg-stone-50 dark:bg-coal-700/40"><Th className="w-8">#</Th><Th>Tanggal</Th><Th className="hidden sm:table-cell">Hari</Th><Th>Sesi</Th></tr></thead>
                            <tbody className="divide-y divide-stone-100 dark:divide-coal-700/60">
                                {pg.slice.map((p, i) => (
                                    <tr key={p.id ?? i} className="hover:bg-stone-50 dark:hover:bg-coal-700/40 transition-colors">
                                        <Td className="text-[11px] text-stone-400 dark:text-stone-500 tabular-nums">{(pg.page - 1) * pg.perPage + i + 1}</Td>
                                        <Td>
                                            <p className="text-xs sm:text-sm font-medium text-stone-900 dark:text-stone-100 whitespace-nowrap tabular-nums">{fmtDate(p.tanggal)}</p>
                                            <p className="text-[9px] sm:hidden text-stone-400 dark:text-stone-500 mt-0.5">{dayName(p.tanggal)}</p>
                                        </Td>
                                        <Td className="text-xs text-stone-500 dark:text-stone-400 whitespace-nowrap hidden sm:table-cell">{dayName(p.tanggal)}</Td>
                                        <Td>
                                            {p.sesi ? <span className="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] sm:text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-500/20">{p.sesi}</span> : <span className="text-[10px] text-stone-300 dark:text-stone-600">−</span>}
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

// ── Panel Ketidakhadiran ──
function AbsenPanel({ rows, className = '' }) {
    const pg = usePaged(rows, 10);
    return (
        <div className={`bg-white dark:bg-coal-800 rounded-2xl ring-1 ring-stone-200 dark:ring-coal-700 overflow-hidden flex flex-col ${className}`}>
            <div className="px-4 sm:px-5 py-3.5 flex items-center justify-between border-b border-stone-100 dark:border-coal-700">
                <div className="flex items-center gap-2.5">
                    <span className="w-8 h-8 rounded-lg grid place-items-center bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600 text-stone-500 dark:text-stone-300">
                        <Icon path={P.alert} className="w-4 h-4" />
                    </span>
                    <div>
                        <h4 className="font-bold tracking-tight text-sm text-stone-900 dark:text-stone-100">Ketidakhadiran</h4>
                        <p className="text-[10px] text-stone-400 dark:text-stone-500 leading-tight">Izin · Sakit · Alpha</p>
                    </div>
                </div>
                <span className="text-[10px] px-2.5 py-1 rounded-full font-semibold tabular-nums bg-stone-100 dark:bg-coal-700 text-stone-600 dark:text-stone-300 ring-1 ring-stone-200 dark:ring-coal-600">{rows.length} hari</span>
            </div>

            {rows.length === 0 ? (
                <Empty text="Tidak ada ketidakhadiran" subtext="Santri hadir penuh pada periode ini" />
            ) : (
                <>
                    <div className="overflow-x-auto flex-1">
                        <table className="min-w-full">
                            <thead><tr className="bg-stone-50 dark:bg-coal-700/40"><Th className="w-8">#</Th><Th>Tanggal</Th><Th className="hidden sm:table-cell">Hari</Th><Th className="w-20 text-center">Status</Th><Th>Keterangan</Th></tr></thead>
                            <tbody className="divide-y divide-stone-100 dark:divide-coal-700/60">
                                {pg.slice.map((p, i) => (
                                    <tr key={p.id ?? i} className="hover:bg-stone-50 dark:hover:bg-coal-700/40 transition-colors">
                                        <Td className="text-[11px] text-stone-400 dark:text-stone-500 tabular-nums">{(pg.page - 1) * pg.perPage + i + 1}</Td>
                                        <Td>
                                            <p className="text-xs sm:text-sm font-medium text-stone-900 dark:text-stone-100 whitespace-nowrap tabular-nums">{fmtDate(p.tanggal)}</p>
                                            <p className="text-[9px] sm:hidden text-stone-400 dark:text-stone-500 mt-0.5">{dayName(p.tanggal)}</p>
                                        </Td>
                                        <Td className="text-xs text-stone-500 dark:text-stone-400 whitespace-nowrap hidden sm:table-cell">{dayName(p.tanggal)}</Td>
                                        <Td className="text-center">
                                            <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] sm:text-xs font-bold ring-1 ${STATUS[p.status]?.chip ?? STATUS.Alpha.chip}`}>{p.status}</span>
                                        </Td>
                                        <Td className="text-[10px] sm:text-sm text-stone-500 dark:text-stone-400">
                                            {p.keterangan ? <span className="italic leading-snug">{p.keterangan}</span> : <span className="text-[9px] sm:text-xs text-stone-300 dark:text-stone-600 italic">{p.status === 'Alpha' ? 'Tanpa keterangan' : '—'}</span>}
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
                <div className="w-12 h-12 mx-auto mb-3 rounded-2xl grid place-items-center bg-stone-100 dark:bg-coal-700 ring-1 ring-stone-200 dark:ring-coal-600">
                    <Icon path={P.checkCircle} className="w-6 h-6 text-emerald-500" />
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
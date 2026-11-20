import { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const CalendarIcon = (p) => <I {...p} d={<path d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />} />;
const UsersIcon    = (p) => <I {...p} d={<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />} />;
const PlusIcon     = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const ChevL        = (p) => <I {...p} d={<path d="M15 19l-7-7 7-7" />} />;
const ChevR        = (p) => <I {...p} d={<path d="M9 5l7 7-7 7" />} />;
const EyeIcon      = (p) => <I {...p} d={<><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></>} />;
const XIcon        = (p) => <I {...p} d={<path d="M6 18L18 6M6 6l12 12" />} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;

/* ============================================================
 * TOKEN DESAIN — sama dengan Dashboard
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const AVATAR = {
    P: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400',
    L: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400',
};

/* ============================================================
 * KONSTANTA — titik status (warna semantik, sama dengan Dashboard)
 * ============================================================ */
const STATUS_DOT = { Hadir: 'bg-emerald-500', Sakit: 'bg-orange-500', Izin: 'bg-amber-500', Alpha: 'bg-red-500' };
const EMPTY_DOT  = 'bg-cream-400 dark:bg-coal-500';
const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const DOW    = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const pad = (n) => String(n).padStart(2, '0');
const todayStr = (() => { const t = new Date(); return `${t.getFullYear()}-${pad(t.getMonth() + 1)}-${pad(t.getDate())}`; })();
const initial = (name) => (name || '?').charAt(0).toUpperCase();

/* ============================================================
 * KALENDER PER SANTRI
 * ============================================================ */
function PresensiCalendar({ calendar = {}, sesiList = [], onDayClick }) {
    const now = new Date();
    const [cur, setCur] = useState({ y: now.getFullYear(), m: now.getMonth() });

    const cells = useMemo(() => {
        const startDow = new Date(cur.y, cur.m, 1).getDay();
        const daysInMonth = new Date(cur.y, cur.m + 1, 0).getDate();
        const arr = [];
        for (let i = 0; i < startDow; i++) arr.push(null);
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${cur.y}-${pad(cur.m + 1)}-${pad(d)}`;
            arr.push({ d, dateStr, data: calendar[dateStr] || {} });
        }
        while (arr.length % 7 !== 0) arr.push(null);
        return arr;
    }, [cur, calendar]);

    const move = (dir) => setCur(({ y, m }) => {
        let nm = m + dir, ny = y;
        if (nm < 0) { nm = 11; ny--; }
        if (nm > 11) { nm = 0; ny++; }
        return { y: ny, m: nm };
    });

    // titik mengecil saat sesi makin banyak
    const dotSize = sesiList.length <= 2 ? 'w-2 h-2' : sesiList.length <= 4 ? 'w-1.5 h-1.5' : 'w-1 h-1';
    const navBtn = 'w-7 h-7 rounded-lg flex items-center justify-center text-stone-500 dark:text-stone-400 hover:bg-cream-200 dark:hover:bg-coal-600 transition-colors';

    return (
        <div>
            {/* nav bulan */}
            <div className="flex items-center justify-between px-2 py-1.5 bg-cream-100 dark:bg-coal-600/40 border-b border-cream-200 dark:border-coal-600/60">
                <button onClick={() => move(-1)} className={navBtn}><ChevL className="w-3.5 h-3.5" /></button>
                <span className="text-xs font-bold tracking-tight text-stone-700 dark:text-stone-200">{MONTHS[cur.m]} {cur.y}</span>
                <button onClick={() => move(1)} className={navBtn}><ChevR className="w-3.5 h-3.5" /></button>
            </div>

            {/* header hari */}
            <div className="grid grid-cols-7 gap-1 px-3 pt-2.5">
                {DOW.map((d) => <span key={d} className="text-center text-[10px] font-semibold text-stone-400">{d}</span>)}
            </div>

            {/* grid tanggal — sel memakai coal-600/40 supaya terlihat di atas kartu coal-700 */}
            <div className="grid grid-cols-7 gap-1 px-3 pb-3 pt-1">
                {cells.map((c, i) => {
                    if (!c) return <div key={i} />;
                    const isToday = c.dateStr === todayStr;
                    return (
                        <button
                            key={i}
                            onClick={() => onDayClick(c)}
                            className={`min-h-[40px] rounded-lg p-1 flex flex-col items-center justify-start gap-0.5 transition-colors ring-1
                                ${isToday
                                    ? 'bg-claude-100 dark:bg-claude-500/15 ring-claude-300 dark:ring-claude-500/40'
                                    : 'bg-cream-100 dark:bg-coal-600/40 ring-cream-200 dark:ring-coal-600/60 hover:bg-cream-200 dark:hover:bg-coal-600'}`}
                        >
                            <span className={`text-[10px] font-bold tabular-nums ${isToday ? 'text-claude-700 dark:text-claude-300' : 'text-stone-600 dark:text-stone-300'}`}>{c.d}</span>
                            <span className="flex flex-wrap justify-center gap-0.5">
                                {sesiList.map((s) => {
                                    const st = c.data[s];
                                    return <span key={s} title={`${s}: ${st || 'Belum absen'}`} className={`${dotSize} rounded-full ${st ? STATUS_DOT[st] : EMPTY_DOT}`} />;
                                })}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Index({ kelas, guruName, genderSummary, tahunAjaranAktif, sesiList = [], santris = [] }) {
    const [selectedDay, setSelectedDay] = useState(null); // { santriName, dateStr, data }

    const genderLabel = genderSummary.ikhwan > 0 && genderSummary.akhwat > 0
        ? 'Campuran' : genderSummary.ikhwan > 0 ? 'Ikhwan' : genderSummary.akhwat > 0 ? 'Akhwat' : '—';

    const fmtDate = (str) => {
        if (!str) return '';
        const [y, m, d] = str.split('-').map(Number);
        return `${d} ${MONTHS[m - 1]} ${y}`;
    };

    return (
        <GuruAuthenticatedLayout>
            <Head title="Presensi Santri" />

            {/* Padding dibuang — <main> di layout sudah memberinya (sama seperti Dashboard). */}
            <div className="max-w-6xl mx-auto">
                {/* Kembali — mobile saja */}
                <Link href={route('guru.dashboard')} className={`${BTN_SOFT} mb-4 sm:hidden`}>
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>

                {/* ===== HEADER (sama dengan Dashboard) ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0 text-stone-600 dark:text-stone-300">
                        <CalendarIcon className="w-6 h-6" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Presensi Santri</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            Kelas <span className="font-semibold text-claude-600 dark:text-claude-400">{kelas.name}</span>
                            <span className="mx-1 opacity-40">·</span>{genderLabel}
                            {tahunAjaranAktif && (
                                <>
                                    <span className="mx-1 opacity-40">·</span>{tahunAjaranAktif.nama}
                                    <span className="ml-1.5 px-1.5 py-px rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 text-[10px] font-semibold">Aktif</span>
                                </>
                            )}
                        </p>
                    </div>
                    <Link href={route('guru.input.create')} className="flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors active:scale-95">
                        <PlusIcon className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Input Presensi</span>
                        <span className="sm:hidden">Input</span>
                    </Link>
                </div>

                {/* ===== STAT + LEGENDA (sama dengan StatCard Dashboard) ===== */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    <div className={`${CARD} p-4 flex items-center gap-3`}>
                        <span className="w-10 h-10 rounded-xl bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400 flex items-center justify-center flex-shrink-0">
                            <UsersIcon className="w-5 h-5" />
                        </span>
                        <div>
                            <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium">Total Santri</p>
                            <p className="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-tight">{genderSummary.total}</p>
                        </div>
                    </div>
                    <div className={`sm:col-span-2 ${CARD} p-4 flex flex-wrap items-center justify-around gap-x-4 gap-y-2`}>
                        {Object.entries(STATUS_DOT).map(([label, dot]) => (
                            <span key={label} className="flex items-center gap-1.5 text-xs text-stone-600 dark:text-stone-300">
                                <span className={`w-2.5 h-2.5 rounded-full ${dot}`} /> {label}
                            </span>
                        ))}
                        <span className="flex items-center gap-1.5 text-xs text-stone-400">
                            <span className={`w-2.5 h-2.5 rounded-full ${EMPTY_DOT}`} /> Belum absen
                        </span>
                    </div>
                </div>

                {/* ===== GRID KARTU SANTRI ===== */}
                {santris.length ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        {santris.map((santri) => (
                            <div key={santri.id} className={`${CARD} overflow-hidden`}>
                                {/* header kartu */}
                                <div className="flex items-center justify-between gap-2 px-4 py-3">
                                    <div className="flex items-center gap-2.5 min-w-0">
                                        {santri.photo_url
                                            ? <img src={santri.photo_url} alt="" className="w-9 h-9 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0" />
                                            : <span className={`w-9 h-9 rounded-full ring-1 flex items-center justify-center text-xs font-bold flex-shrink-0 ${santri.gender === 'Perempuan' ? AVATAR.P : AVATAR.L}`}>{initial(santri.name)}</span>}
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold tracking-tight text-stone-800 dark:text-stone-100 truncate">{santri.name}</p>
                                            <p className="text-[11px] text-stone-400 font-mono">{santri.nis}</p>
                                        </div>
                                    </div>
                                    <Link href={route('guru.presensis.detail', santri.id)} className="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-[11px] font-semibold text-stone-600 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition-colors">
                                        <EyeIcon className="w-3.5 h-3.5" /> Detail
                                    </Link>
                                </div>

                                {/* kalender */}
                                <PresensiCalendar
                                    calendar={santri.calendar || {}}
                                    sesiList={sesiList}
                                    onDayClick={(cell) => setSelectedDay({ santriName: santri.name, dateStr: cell.dateStr, data: cell.data })}
                                />
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className={`${CARD} py-14 flex flex-col items-center text-center`}>
                        <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                            <CalendarIcon className="w-5 h-5" />
                        </span>
                        <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">Belum ada santri</p>
                    </div>
                )}
            </div>

            {/* ===== MODAL DETAIL HARI ===== */}
            {selectedDay && (
                <div className="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm" onClick={() => setSelectedDay(null)}>
                    <div className={`w-full max-w-xs ${CARD} shadow-2xl p-5`} onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-between mb-4">
                            <div className="min-w-0">
                                <p className="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-100 truncate">{selectedDay.santriName}</p>
                                <p className="text-[11px] text-stone-400">{fmtDate(selectedDay.dateStr)}</p>
                            </div>
                            <button onClick={() => setSelectedDay(null)} className="w-7 h-7 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-300 hover:bg-cream-300 dark:hover:bg-coal-500 transition-colors">
                                <XIcon className="w-4 h-4" />
                            </button>
                        </div>
                        <div className="space-y-1.5">
                            {sesiList.map((s) => {
                                const st = selectedDay.data[s];
                                return (
                                    <div key={s} className="flex items-center justify-between px-3 py-2 rounded-xl bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600">
                                        <span className="text-xs text-stone-600 dark:text-stone-300">{s}</span>
                                        <span className="flex items-center gap-1.5 text-xs font-semibold">
                                            <span className={`w-2 h-2 rounded-full ${st ? STATUS_DOT[st] : EMPTY_DOT}`} />
                                            <span className={st ? 'text-stone-700 dark:text-stone-200' : 'text-stone-400'}>{st || 'Belum absen'}</span>
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </GuruAuthenticatedLayout>
    );
}
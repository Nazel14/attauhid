import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * IKON — monokrom line, konsisten seantero panel
 * ============================================================ */
const I = ({ d, className = 'w-5 h-5', ...p }) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" {...p}>{d}</svg>
);
const BookIcon  = (p) => <I {...p} d={<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />} />;
const UsersIcon = (p) => <I {...p} d={<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />} />;
const EyeIcon   = (p) => <I {...p} d={<><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></>} />;
const PlusIcon  = (p) => <I {...p} d={<path d="M12 4v16m8-8H4" />} />;
const TargetIcon= (p) => <I {...p} d={<><path d="M12 12m-9 0a9 9 0 1018 0 9 9 0 10-18 0" /><path d="M12 12m-5 0a5 5 0 1010 0 5 5 0 10-10 0" /><path d="M12 12h.01" /></>} />;
const ArrowLeftIcon = (p) => <I {...p} d={<path d="M10 19l-7-7m0 0l7-7m-7 7h18" />} />;

/* ============================================================
 * TOKEN DESAIN — sama dengan Dashboard / Rapot
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const BTN_SOFT = 'inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 text-stone-600 dark:text-stone-300 hover:bg-cream-100 dark:hover:bg-coal-600 transition active:scale-95';
const GENDER = {
    P: { avatar: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-600 dark:text-pink-400', badge: 'bg-pink-50 dark:bg-pink-500/10 ring-pink-100 dark:ring-pink-500/20 text-pink-700 dark:text-pink-400' },
    L: { avatar: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-600 dark:text-sky-400', badge: 'bg-sky-50 dark:bg-sky-500/10 ring-sky-100 dark:ring-sky-500/20 text-sky-700 dark:text-sky-400' },
};
// Kategori — sama dengan Riwayat Setoran di halaman Input & Rapot
const KAT = {
    Hafalan:  { chip: 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-700 dark:text-emerald-400', text: 'text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
    Murajaah: { chip: 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-700 dark:text-amber-400',           text: 'text-amber-600 dark:text-amber-400',     dot: 'bg-amber-500' },
    Tilawah:  { chip: 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20 text-orange-700 dark:text-orange-400',     text: 'text-orange-600 dark:text-orange-400',   dot: 'bg-orange-500' },
};
// Progress — sama dengan Dashboard & Rapot: utama = aksen, Al-Qur'an sekunder = emerald
const TONE_MAIN  = { bar: 'bg-claude-500',  text: 'text-claude-600 dark:text-claude-400' };
const TONE_QURAN = { bar: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400' };

const initial = (name) => (name || '?').charAt(0).toUpperCase();
const PER_PAGE = 15;

/* ============================================================
 * KOMPONEN KECIL
 * ============================================================ */
function StatPill({ icon, label, value, tone = 'stone' }) {
    const toneMap = {
        stone:  'bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400',
        claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
    };
    return (
        <div className={`${CARD} p-4 flex items-center gap-3`}>
            <span className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${toneMap[tone]}`}>{icon}</span>
            <div className="min-w-0">
                <p className="text-[11px] text-stone-500 dark:text-stone-400 font-medium truncate">{label}</p>
                <p className="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 leading-tight">{value}</p>
            </div>
        </div>
    );
}

function CountChip({ label, value }) {
    return (
        <span className={`inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md ring-1 text-[10px] font-medium ${KAT[label].chip}`}>
            {label} <span className="font-bold tabular-nums">{value}</span>
        </span>
    );
}

function Avatar({ santri }) {
    const g = santri.gender === 'Perempuan' ? 'P' : 'L';
    return santri.photo_url
        ? <img src={santri.photo_url} alt="" className="w-11 h-11 rounded-full object-cover object-top ring-1 ring-cream-300 dark:ring-coal-600 flex-shrink-0" />
        : <span className={`w-11 h-11 rounded-full ring-1 flex items-center justify-center text-sm font-bold flex-shrink-0 ${GENDER[g].avatar}`}>{initial(santri.name)}</span>;
}

// Satu batang progres (label · persen · bar · keterangan).
// Fungsi render biasa, BUKAN komponen di dalam SantriCard — komponen yang
// didefinisikan di badan render di-unmount/mount ulang setiap render.
function ProgressBar({ label, percent, tone, note }) {
    return (
        <div className="min-w-0 flex-1">
            <div className="flex items-center justify-between gap-2">
                <span className="text-[11px] font-medium text-stone-400 truncate">{label}</span>
                <span className={`text-sm font-bold tabular-nums flex-shrink-0 ${tone.text}`}>{percent}%</span>
            </div>
            <span className="mt-1 block h-1.5 rounded-full bg-cream-300 dark:bg-coal-600 overflow-hidden">
                <span className={`block h-full rounded-full transition-all duration-500 ${tone.bar}`} style={{ width: `${Math.min(percent, 100)}%` }} />
            </span>
            <span className="mt-1 block text-[11px] text-stone-400 truncate">{note}</span>
        </div>
    );
}

/* ---- kartu santri (responsif, satu jalur render) ---- */
function SantriCard({ santri, index }) {
    const g = santri.gender === 'Perempuan' ? 'P' : 'L';
    const pct  = santri.progress_persen ?? 0;
    const belumDiatur = santri.is_iqro && !santri.iqro_persen_diketahui;
    const caption = santri.is_iqro
        ? `${santri.total_halaman_iqro}/${santri.total_halaman_jilid} halaman`
        : `${Number(santri.capaian_juz).toFixed(2)} / ${santri.target_display}`;

    // Santri metode ganda (Iqro/Aisar + Al-Qur'an) → tampilkan bar juz sekunder
    const hasQuran2 = !!santri.has_quran_secondary;
    const pct2  = santri.progress_juz2 ?? 0;
    const label1 = santri.is_iqro ? 'Jilid' : 'Progres';

    // layout="row"   → dua bar BERDAMPINGAN (desktop, kolom lebar)
    // layout="stack" → dua bar bertumpuk (mobile, lebar terbatas)
    const renderProgress = (layout) => {
        if (belumDiatur) {
            return <p className="text-[11px] text-stone-400 italic">Halaman Jilid {santri.target_jilid ?? '-'} belum diatur</p>;
        }

        const bar1 = <ProgressBar label={label1} percent={pct} tone={TONE_MAIN} note={caption} />;
        const bar2 = hasQuran2 ? (
            <ProgressBar
                label="Al-Qur'an"
                percent={pct2}
                tone={TONE_QURAN}
                note={`${Number(santri.capaian_juz_2).toFixed(2)} / ${Number(santri.target_juz_2).toFixed(2)} Juz`}
            />
        ) : null;

        if (layout === 'row') {
            return (
                <div className="flex items-start gap-4">
                    {bar1}
                    {bar2 && (
                        <>
                            <span className="w-px self-stretch bg-cream-300 dark:bg-coal-600" />
                            {bar2}
                        </>
                    )}
                </div>
            );
        }

        return (
            <>
                {bar1}
                {bar2 && (
                    <div className="mt-2 pt-2 border-t border-cream-200 dark:border-coal-600/60">
                        {bar2}
                    </div>
                )}
            </>
        );
    };

    const targetChip = (
        <span className="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-[10px] font-semibold text-stone-600 dark:text-stone-300">
            <TargetIcon className="w-3 h-3" /> {santri.target_display}
        </span>
    );
    const detailBtn = 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors active:scale-95';

    return (
        <div className={`${CARD} p-4 hover:ring-claude-300 dark:hover:ring-claude-500/40 transition`}>
            <div className="flex items-center gap-4">
                <span className="hidden sm:block text-[11px] text-stone-400 tabular-nums w-4 text-right flex-shrink-0">{index}</span>
                <Avatar santri={santri} />

                {/* identitas — dibatasi agar kolom progres dapat porsi lebar */}
                <div className="flex-1 min-w-0 sm:max-w-xs">
                    <div className="flex items-center gap-2">
                        <p className="text-sm font-semibold tracking-tight text-stone-800 dark:text-stone-100 truncate">{santri.name}</p>
                        <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-md ring-1 flex-shrink-0 ${GENDER[g].badge}`}>{g}</span>
                    </div>
                    <p className="text-[11px] text-stone-400 font-mono mt-0.5">{santri.nis}</p>
                    <div className="flex flex-wrap items-center gap-1.5 mt-2">
                        <CountChip label="Hafalan"  value={santri.total_hafalan} />
                        <CountChip label="Murajaah" value={santri.total_murajaah} />
                        <CountChip label="Tilawah"  value={santri.total_tilawah} />
                    </div>
                </div>

                {/* Progres (desktop) — melebar mengikuti sisa ruang */}
                <div className="hidden sm:block flex-1 min-w-0 max-w-lg">
                    {renderProgress('row')}
                </div>

                {/* aksi (desktop) */}
                <div className="hidden sm:flex flex-col items-stretch gap-2 w-28 flex-shrink-0">
                    {targetChip}
                    <Link href={route('guru.setorans.detail', santri.id)} className={detailBtn}>
                        <EyeIcon className="w-3.5 h-3.5" /> Detail
                    </Link>
                </div>
            </div>

            {/* progres + aksi (mobile) */}
            <div className="sm:hidden mt-3 pt-3 border-t border-cream-200 dark:border-coal-600/60">
                {renderProgress('stack')}
                <div className="flex items-center justify-between gap-2 mt-3">
                    {targetChip}
                    <Link href={route('guru.setorans.detail', santri.id)} className={detailBtn}>
                        <EyeIcon className="w-3.5 h-3.5" /> Lihat Detail
                    </Link>
                </div>
            </div>
        </div>
    );
}

/* ============================================================
 * HALAMAN
 * ============================================================ */
export default function Index({ kelas, genderSummary, setoranHariIni, tahunAjaranAktif, santris = [] }) {
    const [page, setPage] = useState(1);

    const totalPages = Math.max(1, Math.ceil(santris.length / PER_PAGE));
    const paged = useMemo(
        () => santris.slice((page - 1) * PER_PAGE, page * PER_PAGE),
        [santris, page]
    );

    const totalSetoranHariIni = setoranHariIni.hafalan + setoranHariIni.tilawah + setoranHariIni.murajaah;
    const genderLabel = genderSummary.ikhwan > 0 && genderSummary.akhwat > 0
        ? 'Campuran' : genderSummary.ikhwan > 0 ? 'Ikhwan' : genderSummary.akhwat > 0 ? 'Akhwat' : '—';

    const pgBtn = 'px-3 py-1.5 rounded-lg text-xs font-semibold text-stone-600 dark:text-stone-300 bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-cream-100 dark:hover:bg-coal-600 transition-colors';

    return (
        <GuruAuthenticatedLayout>
            <Head title="Setoran Santri" />

            {/* Padding dibuang — <main> di layout sudah memberinya (sama seperti Dashboard). */}
            <div className="max-w-6xl mx-auto">

                {/* Kembali — mobile saja */}
                <Link href={route('guru.dashboard')} className={`${BTN_SOFT} mb-4 sm:hidden`}>
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke Dashboard
                </Link>

                {/* ===== HEADER (sama dengan Dashboard) ===== */}
                <div className="flex items-center gap-3 mb-5">
                    <div className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0 text-stone-600 dark:text-stone-300">
                        <BookIcon className="w-6 h-6" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Setoran Santri</h1>
                        <p className="text-[11px] text-stone-500 mt-0.5">
                            Kelas <span className="font-semibold text-claude-600 dark:text-claude-400">{kelas.name}</span>
                            <span className="mx-1 opacity-40">·</span>{genderLabel}
                            {tahunAjaranAktif && (<><span className="mx-1 opacity-40">·</span>{tahunAjaranAktif.nama} · Sem {tahunAjaranAktif.semester}</>)}
                        </p>
                    </div>
                    <Link
                        href={route('guru.input.create')}
                        className="flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold transition-colors active:scale-95"
                    >
                        <PlusIcon className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Input Setoran</span>
                        <span className="sm:hidden">Input</span>
                    </Link>
                </div>

                {/* ===== STAT CARDS (sama dengan StatCard Dashboard) ===== */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    <StatPill tone="claude" icon={<UsersIcon className="w-5 h-5" />} label="Total Santri" value={genderSummary.total} />
                    <StatPill tone="stone"  icon={<BookIcon className="w-5 h-5" />}  label="Setoran Hari Ini" value={totalSetoranHariIni} />
                    <div className={`${CARD} p-4 flex items-center justify-around`}>
                        {[
                            ['Hafalan', setoranHariIni.hafalan],
                            ['Murajaah', setoranHariIni.murajaah],
                            ['Tilawah', setoranHariIni.tilawah],
                        ].map(([lbl, val]) => (
                            <div key={lbl} className="text-center">
                                <p className={`text-lg font-bold tracking-tight tabular-nums ${KAT[lbl].text}`}>{val}</p>
                                <p className="text-[11px] text-stone-400 mt-0.5 flex items-center justify-center gap-1">
                                    <span className={`w-1.5 h-1.5 rounded-full ${KAT[lbl].dot}`} />{lbl}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ===== DAFTAR SANTRI ===== */}
                {santris.length ? (
                    <>
                        <div className="space-y-2">
                            {paged.map((s, i) => (
                                <SantriCard key={s.id} santri={s} index={(page - 1) * PER_PAGE + i + 1} />
                            ))}
                        </div>

                        {/* pagination */}
                        {totalPages > 1 && (
                            <div className="flex items-center justify-between mt-5">
                                <span className="text-[11px] text-stone-400 tabular-nums">
                                    Halaman {page} dari {totalPages} · {santris.length} santri
                                </span>
                                <div className="flex items-center gap-1">
                                    <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1} className={pgBtn}>Sebelumnya</button>
                                    <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page === totalPages} className={pgBtn}>Berikutnya</button>
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className={`${CARD} py-14 flex flex-col items-center text-center`}>
                        <span className="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                            <BookIcon className="w-5 h-5" />
                        </span>
                        <p className="text-sm font-semibold text-stone-700 dark:text-stone-300">Belum ada santri</p>
                        <p className="text-[11px] text-stone-400 mt-0.5">Santri belum ditambahkan ke kelas ini.</p>
                    </div>
                )}
            </div>
        </GuruAuthenticatedLayout>
    );
}
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const Svg = ({ children, className = 'h-4 w-4' }) => (
    <svg className={className} fill="none" stroke="currentColor" strokeWidth="2"
        viewBox="0 0 24 24" strokeLinecap="round" strokeLinejoin="round">{children}</svg>
);
const UsersIcon = (p) => <Svg {...p}><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 00-3-3.87" /></Svg>;
const ArrowIcon = (p) => <Svg {...p}><path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></Svg>;
const CheckIcon = (p) => <Svg {...p}><path d="M20 6L9 17l-5-5" /></Svg>;

const GENDER_LABEL = {
    ikhwan: 'Ikhwan',
    akhwat: 'Akhwat',
    campuran: 'Campuran',
};

export default function PilihKelas({ kelasList = [], kelasAktifId = null, guruName = '' }) {
    const [pilih, setPilih] = useState(kelasAktifId);
    const [loading, setLoading] = useState(false);

    const lanjut = () => {
        if (!pilih) return;
        setLoading(true);
        router.post(route('guru.pilih-kelas.set'), { kelas_id: pilih }, {
            onFinish: () => setLoading(false),
        });
    };

    return (
        <>
            <Head title="Pilih Kelas" />

            <div className="min-h-screen bg-cream-100 px-4 py-10 dark:bg-coal-900">
                <div className="mx-auto w-full max-w-lg">

                    <div className="mb-8 text-center">
                        <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-claude-500 to-claude-600 shadow-lg shadow-claude-500/25">
                            <UsersIcon className="h-7 w-7 text-white" />
                        </div>
                        <h1 className="text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                            Pilih Kelas
                        </h1>
                        <p className="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            {guruName ? `${guruName} — ` : ''}Anda mengampu {kelasList.length} kelas.
                            Pilih dulu kelas yang ingin dikelola.
                        </p>
                    </div>

                    <div className="space-y-2.5">
                        {kelasList.map((k) => {
                            const aktif = pilih === k.id;
                            return (
                                <button
                                    key={k.id}
                                    type="button"
                                    onClick={() => setPilih(k.id)}
                                    className={`flex w-full items-center gap-3 rounded-2xl border px-4 py-3.5 text-left transition active:scale-[0.99] ${
                                        aktif
                                            ? 'border-claude-500/50 bg-claude-500/8 ring-1 ring-claude-500/20'
                                            : 'border-stone-200 bg-white hover:bg-stone-50 dark:border-coal-700 dark:bg-coal-800 dark:hover:bg-coal-700/50'
                                    }`}
                                >
                                    <span className={`flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold ${
                                        aktif
                                            ? 'bg-claude-500 text-white'
                                            : 'bg-stone-100 text-stone-500 dark:bg-coal-700 dark:text-stone-400'
                                    }`}>
                                        {k.name?.charAt(0)?.toUpperCase()}
                                    </span>

                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-bold text-stone-900 dark:text-stone-100">
                                            {k.name}
                                        </p>
                                        <p className="mt-0.5 text-[11px] text-stone-400 dark:text-stone-500">
                                            {k.santris_count} santri
                                            {k.tahun_ajaran ? ` · ${k.tahun_ajaran}` : ''}
                                            {k.gender_restriction ? ` · ${GENDER_LABEL[k.gender_restriction] ?? k.gender_restriction}` : ''}
                                        </p>
                                    </div>

                                    {aktif && (
                                        <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-claude-500 text-white">
                                            <CheckIcon className="h-3.5 w-3.5" />
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    <button
                        type="button"
                        onClick={lanjut}
                        disabled={!pilih || loading}
                        className="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-claude-500 to-claude-600 py-3 text-[13px] font-bold text-white shadow-lg shadow-claude-500/25 transition hover:from-claude-500 hover:to-claude-700 active:scale-[0.99] disabled:opacity-50"
                    >
                        <span>{loading ? 'Memuat…' : 'Lanjut'}</span>
                        {!loading && <ArrowIcon className="h-4 w-4" />}
                    </button>

                    <p className="mt-4 text-center text-[11px] text-stone-400 dark:text-stone-600">
                        Anda bisa ganti kelas kapan saja lewat menu di header.
                    </p>
                </div>
            </div>
        </>
    );
}
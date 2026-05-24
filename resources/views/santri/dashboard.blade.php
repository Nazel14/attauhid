@extends('layouts.app')

@section('content')

<style>
    body, #app, main, .main-content, [class*="content"] {
        background-color: #030712 !important;
    }
    .page-bg {
        background:
            radial-gradient(ellipse 80% 50% at 50% -10%, rgba(99, 102, 241, 0.15), transparent),
            radial-gradient(ellipse 60% 40% at 80% 30%, rgba(168, 85, 247, 0.08), transparent),
            #030712;
        min-height: 100vh;
    }

    /* Glass card with subtle gradient border */
    .glass-card {
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.8) 0%, rgba(17, 24, 39, 0.6) 100%);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(99, 102, 241, 0.1);
        border-radius: 1.25rem;
        position: relative;
        overflow: hidden;
    }
    .glass-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.4), transparent);
    }

    /* Score badge with glow effect */
    .score-badge {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        box-shadow:
            0 10px 30px -10px rgba(99, 102, 241, 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.15);
    }

    /* Stat card hover */
    .stat-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.08), transparent 70%);
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
    .stat-card:hover::after { opacity: 1; }

    /* Mini stat panels */
    .mini-panel {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 0.875rem;
        transition: all 0.2s;
    }
    .mini-panel:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(99, 102, 241, 0.25);
    }

    /* Progress bar with shimmer */
    .progress-track {
        background: rgba(99, 102, 241, 0.1);
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
    }
    .progress-fill {
        background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899);
        background-size: 200% 100%;
        animation: shimmer 3s ease-in-out infinite;
        height: 100%;
        border-radius: 9999px;
        position: relative;
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
    }
    @keyframes shimmer {
        0%, 100% { background-position: 0% 0%; }
        50% { background-position: 100% 0%; }
    }

    /* Action button styles */
    .action-btn {
        position: relative;
        overflow: hidden;
        transition: all 0.2s;
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
    }
    .action-btn:active { transform: scale(0.97); }
    .action-btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 8px 20px -8px rgba(99, 102, 241, 0.6);
    }
    .action-btn-primary:hover {
        box-shadow: 0 12px 28px -8px rgba(99, 102, 241, 0.8);
        transform: translateY(-1px);
    }
    .action-btn-secondary {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .action-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(99, 102, 241, 0.3);
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #374151; border-radius: 8px; }
    ::-webkit-scrollbar-thumb:hover { background: #6366f1; }

    /* Pulsing live indicator */
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6); }
        100% { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
    }
    .pulse-ring {
        animation: pulse-ring 2s ease-out infinite;
    }

    

    /* Category card colors */
    .cat-hafalan { background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(99, 102, 241, 0.04)); border: 1px solid rgba(99, 102, 241, 0.2); }
    .cat-murajaah { background: linear-gradient(135deg, rgba(168, 85, 247, 0.12), rgba(168, 85, 247, 0.04)); border: 1px solid rgba(168, 85, 247, 0.2); }
    .cat-tilawah { background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(236, 72, 153, 0.04)); border: 1px solid rgba(236, 72, 153, 0.2); }

    @supports (padding: env(safe-area-inset-bottom)) {
        .page-bg { padding-bottom: env(safe-area-inset-bottom); }
    }

    /* Smooth entry animation */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp 0.5s ease-out backwards; }
</style>

<div class="page-bg">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between mb-5 fade-up">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-block w-1 h-5 bg-gradient-to-b from-indigo-400 to-purple-500 rounded-full"></span>
                    <h1 class="text-lg sm:text-xl font-bold text-white leading-tight tracking-tight">Dashboard Santri</h1>
                </div>
                <p class="text-xs text-gray-400 ml-3">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
            <div class="flex items-center gap-2 px-3.5 py-2 bg-gradient-to-r from-indigo-950/80 to-purple-950/80 border border-indigo-700/30 rounded-full backdrop-blur-sm">
                <div class="relative">
                    <div class="w-2 h-2 bg-emerald-400 rounded-full pulse-ring"></div>
                </div>
                <span class="text-xs font-semibold text-indigo-200 whitespace-nowrap tracking-wide">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
            </div>
            @endif
        </div>

        {{-- EMPTY STATE --}}
        @if($santris->isEmpty())
        <div class="glass-card p-12 text-center fade-up">
            <div class="w-20 h-20 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-gray-700/50">
                <svg class="w-9 h-9 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Data Tidak Ditemukan</h3>
            <p class="text-sm text-gray-400 max-w-xs mx-auto">{{ $message ?? 'Hubungi administrator untuk bantuan lebih lanjut' }}</p>
        </div>

        @else
        @foreach($santris as $index => $santri)
        @php
            $totalHafalan   = $santri->stats_setoran->total_juz_hafalan ?? 0;
            $targetJuz      = $santri->target_juz ?? 30;
            $progressPersen = $targetJuz > 0 ? min(100, round(($totalHafalan / $targetJuz) * 100, 1)) : 0;
            $nilaiHafalan   = $santri->stats_setoran->rata_nilai_hafalan  ?? 0;
            $nilaiMurajaah  = $santri->stats_setoran->rata_nilai_murajaah ?? 0;
            $nilaiTilawah   = $santri->stats_setoran->rata_nilai_tilawah  ?? 0;
            $nilaiOverall   = $santri->stats_setoran->rata_nilai          ?? 0;
            $totalSetoran   = $santri->stats_setoran->total_setoran       ?? 0;
            $pctKehadiran   = $santri->persentase_kehadiran               ?? 0;
            $totalHadir     = $santri->stats_presensi->total_hadir        ?? 0;
            $totalHari      = $santri->stats_presensi->total_hari         ?? 0;
            $totalIzin      = $santri->stats_presensi->total_izin         ?? 0;
            $totalSakit     = $santri->stats_presensi->total_sakit        ?? 0;
            $totalAlpha     = $santri->stats_presensi->total_alpha        ?? 0;

            

            // Grade label
            $grade = 'D';
            $gradeColor = 'text-red-400';
            if ($nilaiOverall >= 90) { $grade = 'A'; $gradeColor = 'text-emerald-400'; }
            elseif ($nilaiOverall >= 80) { $grade = 'B'; $gradeColor = 'text-indigo-400'; }
            elseif ($nilaiOverall >= 70) { $grade = 'C'; $gradeColor = 'text-yellow-400'; }
        @endphp

        <div class="glass-card mb-4 fade-up" style="animation-delay: {{ $index * 0.05 }}s">

            {{-- PROFILE HEADER --}}
            <div class="px-4 sm:px-5 py-4 border-b border-white/5">
                <div class="flex items-center gap-4">
                    
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base sm:text-lg font-bold text-white leading-tight truncate">{{ $santri->name }}</h2>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5">
                            <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0h4"/></svg>
                                {{ $santri->nis }}
                            </span>
                            <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded-md border border-indigo-500/20">
                                {{ $santri->kelas->name ?? '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- Score badge --}}
                    <div class="score-badge flex-shrink-0 text-center rounded-xl px-3 py-2 min-w-[70px]">
                        <p class="text-[9px] text-white/70 mb-0.5 uppercase tracking-wider font-semibold">Nilai</p>
                        <div class="flex items-baseline justify-center gap-1">
                            <p class="text-2xl font-extrabold text-white leading-none">{{ number_format($nilaiOverall, 0) }}</p>
                            <span class="text-xs font-bold {{ $gradeColor }} bg-white/15 px-1.5 py-0.5 rounded">{{ $grade }}</span>
                        </div>
                        <p class="text-[9px] text-white/60 mt-1">rata-rata</p>
                    </div>
                </div>
            </div>

            {{-- CARD BODY --}}
            <div class="p-4 sm:p-5 space-y-4">

                {{-- ROW 1: Progress + Stat Cards --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    {{-- LEFT: Progress Hafalan + Nilai Kategori --}}
                    <div class="bg-gradient-to-br from-indigo-950/40 via-purple-950/30 to-pink-950/20 border border-indigo-500/15 rounded-xl p-4 flex flex-col justify-between gap-4">

                        {{-- Progress Bar --}}
                        <div>
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <div class="w-7 h-7 bg-gradient-to-br from-indigo-500/30 to-purple-500/30 rounded-lg flex items-center justify-center border border-indigo-400/20">
                                            <svg class="w-3.5 h-3.5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-200">Progress Hafalan</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-extrabold bg-gradient-to-r from-indigo-300 to-purple-300 bg-clip-text text-transparent leading-none">{{ $progressPersen }}%</div>
                                </div>
                            </div>
                            <div class="progress-track h-3 mb-2.5">
                                <div class="progress-fill" style="width: {{ max(2, $progressPersen) }}%"></div>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-400">
                                    <span class="text-white font-semibold">{{ number_format($totalHafalan, 2) }}</span> Juz tercapai
                                </span>
                                <span class="text-gray-400">
                                    Target: <span class="text-white font-semibold">{{ number_format($targetJuz, 2) }}</span> Juz
                                </span>
                            </div>
                        </div>

                        {{-- Rata-rata Nilai Per Kategori --}}
                        <div>
                            <p class="text-xs font-medium text-gray-400 mb-2.5 uppercase tracking-wider">Rata-rata Per Kategori</p>
                            <div class="grid grid-cols-3 gap-2.5">
                                {{-- Hafalan --}}
                                <div class="cat-hafalan rounded-xl p-3 text-center">
                                    <p class="text-[10px] text-indigo-300/80 mb-1 font-semibold uppercase tracking-wider">Hafalan</p>
                                    <p class="text-2xl font-extrabold text-white leading-none">{{ $nilaiHafalan > 0 ? number_format($nilaiHafalan, 1) : '−' }}</p>
                                    <div class="mt-2 w-full bg-indigo-950/60 rounded-full h-1">
                                        <div class="bg-gradient-to-r from-indigo-400 to-indigo-500 h-1 rounded-full transition-all duration-700"
                                             style="width:{{ $nilaiHafalan > 0 ? min(100, $nilaiHafalan) : 0 }}%"></div>
                                    </div>
                                </div>

                                {{-- Murajaah --}}
                                <div class="cat-murajaah rounded-xl p-3 text-center">
                                    <p class="text-[10px] text-purple-300/80 mb-1 font-semibold uppercase tracking-wider">Murajaah</p>
                                    <p class="text-2xl font-extrabold text-white leading-none">{{ $nilaiMurajaah > 0 ? number_format($nilaiMurajaah, 1) : '−' }}</p>
                                    <div class="mt-2 w-full bg-purple-950/60 rounded-full h-1">
                                        <div class="bg-gradient-to-r from-purple-400 to-purple-500 h-1 rounded-full transition-all duration-700"
                                             style="width:{{ $nilaiMurajaah > 0 ? min(100, $nilaiMurajaah) : 0 }}%"></div>
                                    </div>
                                </div>

                                {{-- Tilawah --}}
                                <div class="cat-tilawah rounded-xl p-3 text-center">
                                    <p class="text-[10px] text-pink-300/80 mb-1 font-semibold uppercase tracking-wider">Tilawah</p>
                                    <p class="text-2xl font-extrabold text-white leading-none">{{ $nilaiTilawah > 0 ? number_format($nilaiTilawah, 1) : '−' }}</p>
                                    <div class="mt-2 w-full bg-pink-950/60 rounded-full h-1">
                                        <div class="bg-gradient-to-r from-pink-400 to-pink-500 h-1 rounded-full transition-all duration-700"
                                             style="width:{{ $nilaiTilawah > 0 ? min(100, $nilaiTilawah) : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- RIGHT: 2x2 stat cards --}}
                    <div class="grid grid-cols-2 gap-3">

                        {{-- Total Setoran --}}
                        <div class="stat-card relative overflow-hidden rounded-xl p-3.5 text-white"
                             style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); box-shadow: 0 10px 25px -10px rgba(37, 99, 235, 0.5);">
                            <div class="relative z-10">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-[10px] font-semibold opacity-90 uppercase tracking-wider">Setoran</span>
                                    <div class="w-7 h-7 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-2xl sm:text-3xl font-extrabold leading-none">{{ $totalSetoran }}</div>
                                <div class="text-[10px] opacity-70 mt-1">kali setoran</div>
                            </div>
                            <div class="absolute -right-5 -bottom-5 opacity-[0.07]">
                                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                        </div>

                        {{-- Rata-rata Nilai --}}
                        <div class="stat-card relative overflow-hidden rounded-xl p-3.5 text-white"
                             style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 10px 25px -10px rgba(139, 92, 246, 0.5);">
                            <div class="relative z-10">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-[10px] font-semibold opacity-90 uppercase tracking-wider">Nilai</span>
                                    <div class="w-7 h-7 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-2xl sm:text-3xl font-extrabold leading-none">{{ number_format($nilaiOverall, 1) }}</div>
                                <div class="text-[10px] opacity-70 mt-1">dari 100 poin</div>
                            </div>
                            <div class="absolute -right-5 -bottom-5 opacity-[0.07]">
                                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </div>
                        </div>

                        {{-- Kehadiran --}}
                        <div class="stat-card relative overflow-hidden rounded-xl p-3.5 text-white"
                             style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); box-shadow: 0 10px 25px -10px rgba(16, 185, 129, 0.5);">
                            <div class="relative z-10">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-[10px] font-semibold opacity-90 uppercase tracking-wider">Hadir</span>
                                    <div class="w-7 h-7 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-2xl sm:text-3xl font-extrabold leading-none">{{ $pctKehadiran }}%</div>
                                <div class="text-[10px] opacity-70 mt-1">{{ $totalHadir }}/{{ $totalHari }} hari</div>
                                <div class="mt-2 w-full bg-white/20 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-white h-1.5 rounded-full transition-all duration-700" style="width:{{ min(100,max(2,$pctKehadiran)) }}%"></div>
                                </div>
                            </div>
                            <div class="absolute -right-5 -bottom-5 opacity-[0.07]">
                                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>

                        {{-- Detail Absensi --}}
                        <div class="mini-panel p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[11px] font-semibold text-gray-300 uppercase tracking-wider">Absensi</p>
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full ring-2 ring-emerald-400/20"></div>
                                        <span class="text-xs text-gray-300">Hadir</span>
                                    </div>
                                    <span class="text-sm font-bold text-white tabular-nums">{{ $totalHadir }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-yellow-400 rounded-full ring-2 ring-yellow-400/20"></div>
                                        <span class="text-xs text-gray-300">Izin</span>
                                    </div>
                                    <span class="text-sm font-bold text-white tabular-nums">{{ $totalIzin }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-blue-400 rounded-full ring-2 ring-blue-400/20"></div>
                                        <span class="text-xs text-gray-300">Sakit</span>
                                    </div>
                                    <span class="text-sm font-bold text-white tabular-nums">{{ $totalSakit }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-red-400 rounded-full ring-2 ring-red-400/20"></div>
                                        <span class="text-xs text-gray-300">Alpha</span>
                                    </div>
                                    <span class="text-sm font-bold text-white tabular-nums">{{ $totalAlpha }}</span>
                                </div>
                            </div>
                        </div>

                    </div>{{-- END 2x2 grid --}}
                </div>{{-- END ROW 1 --}}

                {{-- ACTION BUTTONS --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                    <a href="{{ route('santri.rapot.show') }}"
                       class="action-btn action-btn-primary flex items-center justify-center gap-2 text-white text-xs sm:text-sm font-semibold py-2.5 px-3 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Rapot Lengkap
                        <svg class="w-3 h-3 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="{{ route('santri.setorans.detail') }}"
                       class="action-btn action-btn-secondary flex items-center justify-center gap-2 text-gray-200 text-xs sm:text-sm font-semibold py-2.5 px-3 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Detail Setoran
                    </a>
                    <a href="{{ route('santri.presensis.detail') }}"
                       class="action-btn action-btn-secondary flex items-center justify-center gap-2 text-gray-200 text-xs sm:text-sm font-semibold py-2.5 px-3 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Detail Presensi
                    </a>
                </div>

            </div>{{-- END CARD BODY --}}
        </div>{{-- END CARD --}}

        @endforeach

        {{-- INFO NOTE --}}
        <div class="relative overflow-hidden rounded-2xl p-4 sm:p-5 fade-up"
             style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.02)); border: 1px solid rgba(59, 130, 246, 0.15);">
            <div class="flex gap-3 sm:gap-4 relative z-10">
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-500/15 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-500/20">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-blue-200 mb-1">Informasi Data</h4>
                    <p class="text-xs text-blue-300/70 leading-relaxed">
                        Statistik yang ditampilkan merupakan data <strong class="text-blue-100 font-semibold">30 hari terakhir</strong>.
                        Klik <strong class="text-blue-100 font-semibold">"Rapot Lengkap"</strong> untuk melihat laporan dengan filter periode custom.
                    </p>
                </div>
            </div>
            <div class="absolute -right-8 -bottom-8 opacity-[0.04]">
                <svg class="w-32 h-32 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        @endif

        <div class="h-8"></div>
    </div>
</div>

@endsection
@extends('layouts.app')

@section('content')

{{-- ============================================================ --}}
{{-- STYLES - Dark glass theme matching new Dashboard Santri        --}}
{{-- ============================================================ --}}
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

    /* Glass card */
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
        pointer-events: none;
    }

    .dark-thead {
        background: rgba(26, 35, 50, 0.6);
        backdrop-filter: blur(8px);
    }
    tr.dark-row, tr.dark-row-hadir { transition: background 0.15s; }
    tr.dark-row:hover td { background: rgba(99, 102, 241, 0.06); }
    tr.dark-row-hadir:hover td { background: rgba(16, 185, 129, 0.06); }

    

    /* Stat card */
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
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card:hover::after { opacity: 1; }

    /* Filter input */
    .filter-input {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: white !important;
        transition: all 0.2s;
    }
    .filter-input:focus {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 8px 20px -8px rgba(99, 102, 241, 0.6);
        transition: all 0.2s;
    }
    .btn-primary:hover {
        box-shadow: 0 12px 28px -8px rgba(99, 102, 241, 0.8);
        transform: translateY(-1px);
    }
    .btn-primary:active { transform: scale(0.97); }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.2s;
    }
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(99, 102, 241, 0.3);
    }

    /* Section gradient headers */
    .sect-header-hadir {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.1), 0 4px 12px -4px rgba(5, 150, 105, 0.4);
    }
    .sect-header-absen {
        background: linear-gradient(135deg, #475569 0%, #334155 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.1), 0 4px 12px -4px rgba(71, 85, 105, 0.4);
    }

    /* Status badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: 700;
        border-radius: 0.5rem;
        white-space: nowrap;
        transition: all 0.15s;
    }
    .status-hadir {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.1));
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .status-izin {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.1));
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    .status-sakit {
        background: linear-gradient(135deg, rgba(234, 179, 8, 0.2), rgba(202, 138, 4, 0.1));
        color: #fbbf24;
        border: 1px solid rgba(234, 179, 8, 0.3);
    }
    .status-alpha {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.1));
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    /* Info notice */
    .info-notice {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02));
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .warn-notice {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02));
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    /* Progress bar shimmer */
    @keyframes shimmer-bar {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.85; }
    }

    /* Pulse dot */
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6); }
        100% { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
    }
    .pulse-ring { animation: pulse-ring 2s ease-out infinite; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #374151; border-radius: 8px; }
    ::-webkit-scrollbar-thumb:hover { background: #6366f1; }
    .touch-manipulation { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }

    @supports (padding: env(safe-area-inset-bottom)) {
        .page-bg { padding-bottom: env(safe-area-inset-bottom); }
    }

    /* Fade animation */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp 0.4s ease-out backwards; }

    /* Input date/month picker dark fix */
    input[type="month"], input[type="date"] { color-scheme: dark; }
    input[type="month"]::-webkit-inner-spin-button,
    input[type="date"]::-webkit-inner-spin-button { display: none; }
    input[type="month"]::-webkit-calendar-picker-indicator,
    input[type="date"]::-webkit-calendar-picker-indicator {
        background-color: transparent;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' viewBox='0 0 24 24'%3E%3Cpath d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' stroke='%23a0aec0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
        background-size: 16px;
        width: 20px; height: 20px;
        cursor: pointer;
        opacity: 1;
    }
    input[type="month"]::-webkit-calendar-picker-indicator:hover,
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 0.7; }

    /* Multi-color progress bar */
    .progress-multi {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.05);
        overflow: hidden;
        border-radius: 9999px;
    }
</style>

<div class="page-bg">

    {{-- ============================================================ --}}
    {{-- MOBILE STICKY TOP BAR                                         --}}
    {{-- ============================================================ --}}
    <div class="sticky top-0 z-30 backdrop-blur-xl bg-gray-900/80 border-b border-white/5 px-4 py-3 md:hidden">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="{{ route('santri.dashboard') }}"
                   class="w-9 h-9 flex items-center justify-center bg-white/5 border border-white/10 rounded-xl flex-shrink-0 active:scale-95 transition-transform touch-manipulation">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white leading-tight flex items-center gap-1.5">
                        <span class="inline-block w-1 h-3.5 bg-gradient-to-b from-indigo-400 to-purple-500 rounded-full"></span>
                        Detail Presensi
                    </p>
                    <p class="text-[10px] text-gray-400 leading-tight truncate mt-0.5">{{ $santri->name }}</p>
                </div>
            </div>
            <button onclick="toggleMobileFilter()"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-xs font-semibold rounded-xl active:scale-95 transition-transform touch-manipulation flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MOBILE FILTER PANEL                                           --}}
    {{-- ============================================================ --}}
    <div id="mobileFilterPanel" class="hidden md:hidden bg-gray-900/90 backdrop-blur-xl border-b border-white/5 px-4 py-4">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-3">Filter Data</p>
        <form method="GET" action="{{ route('santri.presensis.detail') }}" class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Bulan</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                    <select name="status" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                        <option value="">Semua Status</option>
                        <option value="Hadir" {{ $status == 'Hadir' ? 'selected' : '' }}>✅ Hadir</option>
                        <option value="Izin"  {{ $status == 'Izin'  ? 'selected' : '' }}>📋 Izin</option>
                        <option value="Sakit" {{ $status == 'Sakit' ? 'selected' : '' }}>🏥 Sakit</option>
                        <option value="Alpha" {{ $status == 'Alpha' ? 'selected' : '' }}>❌ Alpha</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 py-2.5 text-white text-sm font-semibold rounded-xl touch-manipulation">
                    Terapkan
                </button>
                @if($status)
                <a href="{{ route('santri.presensis.detail', ['bulan' => $bulan]) }}"
                   class="btn-secondary px-4 py-2.5 text-gray-300 text-sm font-medium rounded-xl touch-manipulation">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT                                                   --}}
    {{-- ============================================================ --}}
    <div class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-4 sm:py-6 md:py-8">

        {{-- DESKTOP PAGE HEADER --}}
        <div class="hidden md:flex items-center justify-between mb-6 fade-up">
            <a href="{{ route('santri.dashboard') }}"
               class="btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <div class="text-right">
                <div class="flex items-center justify-end gap-2 mb-1">
                    <h1 class="text-xl lg:text-2xl font-bold text-white tracking-tight">Detail Presensi</h1>
                    <span class="inline-block w-1 h-5 bg-gradient-to-b from-indigo-400 to-purple-500 rounded-full"></span>
                </div>
                @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-500/10 border border-indigo-500/20 rounded-full text-xs">
                    <div class="relative">
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full pulse-ring"></div>
                    </div>
                    <span class="text-indigo-200 font-medium">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- SANTRI INFO CARD --}}
        <div class="glass-card p-4 sm:p-5 mb-4 sm:mb-5 fade-up">
            <div class="flex items-center gap-3 sm:gap-4">
                
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm sm:text-base lg:text-lg font-bold text-white truncate">{{ $santri->name }}</h3>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded-md border border-indigo-500/20">
                            {{ $santri->kelas->name ?? '-' }}
                        </span>
                        <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
                        <span class="text-xs text-gray-400 inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            {{ $santri->guru->name ?? '-' }}
                        </span>
                    </div>
                </div>
                @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <div class="flex-shrink-0 md:hidden">
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-500/10 border border-indigo-500/20 rounded-lg">
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full pulse-ring"></div>
                        <span class="text-[10px] font-semibold text-indigo-300 leading-none">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- DESKTOP FILTER CARD --}}
        <div class="hidden md:block glass-card p-5 mb-5 fade-up">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <h4 class="text-sm font-semibold text-gray-200">Filter Data</h4>
            </div>
            <form method="GET" action="{{ route('santri.presensis.detail') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Bulan</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                    <select name="status" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                        <option value="">Semua Status</option>
                        <option value="Hadir" {{ $status == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="Izin"  {{ $status == 'Izin'  ? 'selected' : '' }}>Izin</option>
                        <option value="Sakit" {{ $status == 'Sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="Alpha" {{ $status == 'Alpha' ? 'selected' : '' }}>Alpha</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary flex-1 px-4 py-2.5 text-white text-sm font-semibold rounded-xl flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Terapkan
                    </button>
                    @if($status)
                    <a href="{{ route('santri.presensis.detail', ['bulan' => $bulan]) }}"
                       class="btn-secondary px-4 py-2.5 text-gray-300 text-sm font-medium rounded-xl whitespace-nowrap">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- STATISTIK 4 CARDS --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-4 mb-5 fade-up">

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); box-shadow: 0 10px 25px -10px rgba(16, 185, 129, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Hadir</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['hadir'] }}</p>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">hari hadir</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-[0.07]">
                    <svg class="w-24 h-24 sm:w-32 sm:h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/></svg>
                </div>
            </div>

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 10px 25px -10px rgba(59, 130, 246, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Izin</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['izin'] }}</p>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">hari izin</p>
                </div>
            </div>

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); box-shadow: 0 10px 25px -10px rgba(234, 179, 8, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Sakit</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['sakit'] }}</p>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">hari sakit</p>
                </div>
            </div>

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); box-shadow: 0 10px 25px -10px rgba(239, 68, 68, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Alpha</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['alpha'] }}</p>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">hari alpha</p>
                </div>
            </div>

        </div>

        {{-- PROGRESS KEHADIRAN --}}
        @php $pct = $statistik['total'] > 0 ? round(($statistik['hadir'] / $statistik['total']) * 100, 1) : 0; @endphp
        <div class="glass-card p-4 sm:p-5 mb-5 fade-up">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="text-sm font-semibold text-gray-200 mb-0.5">Persentase Kehadiran</h4>
                    <p class="text-[11px] text-gray-500">Distribusi status presensi bulan ini</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl sm:text-3xl font-extrabold tabular-nums leading-none {{ $pct >= 80 ? 'text-emerald-400' : ($pct >= 60 ? 'text-yellow-400' : 'text-red-400') }}">{{ $pct }}%</div>
                    <p class="text-[10px] text-gray-500 mt-1">kehadiran</p>
                </div>
            </div>
            <div class="progress-multi w-full h-3 sm:h-3.5 flex">
                @if($statistik['hadir'] > 0)
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-full transition-all duration-700" style="width:{{ $statistik['total'] > 0 ? round(($statistik['hadir']/$statistik['total'])*100,1) : 0 }}%"></div>
                @endif
                @if($statistik['izin'] > 0)
                <div class="bg-gradient-to-r from-blue-500 to-blue-400 h-full transition-all duration-700" style="width:{{ $statistik['total'] > 0 ? round(($statistik['izin']/$statistik['total'])*100,1) : 0 }}%"></div>
                @endif
                @if($statistik['sakit'] > 0)
                <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 h-full transition-all duration-700" style="width:{{ $statistik['total'] > 0 ? round(($statistik['sakit']/$statistik['total'])*100,1) : 0 }}%"></div>
                @endif
                @if($statistik['alpha'] > 0)
                <div class="bg-gradient-to-r from-red-500 to-red-400 h-full transition-all duration-700" style="width:{{ $statistik['total'] > 0 ? round(($statistik['alpha']/$statistik['total'])*100,1) : 0 }}%"></div>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-x-4 gap-y-2 mt-3.5">
                <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-emerald-400/20 inline-block flex-shrink-0"></span>
                    Hadir <span class="text-white font-semibold tabular-nums">({{ $statistik['hadir'] }})</span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-400 ring-2 ring-blue-400/20 inline-block flex-shrink-0"></span>
                    Izin <span class="text-white font-semibold tabular-nums">({{ $statistik['izin'] }})</span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 ring-2 ring-yellow-400/20 inline-block flex-shrink-0"></span>
                    Sakit <span class="text-white font-semibold tabular-nums">({{ $statistik['sakit'] }})</span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-400 ring-2 ring-red-400/20 inline-block flex-shrink-0"></span>
                    Alpha <span class="text-white font-semibold tabular-nums">({{ $statistik['alpha'] }})</span>
                </div>
                <div class="col-span-2 sm:ml-auto text-[11px] text-gray-500 font-medium sm:whitespace-nowrap">
                    Total: <span class="text-white font-semibold tabular-nums">{{ $statistik['total'] }}</span> hari
                </div>
            </div>
        </div>

        {{-- MOBILE TAB SWITCHER --}}
        <div class="flex gap-2 mb-4 lg:hidden">
            <button onclick="switchPresTab('hadir')" id="tab-hadir"
                    class="pres-tab flex-1 py-2.5 text-xs sm:text-sm font-semibold rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-900/30 transition-all touch-manipulation">
                ✅ Kehadiran
                <span class="ml-1 bg-white/25 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ $presensisHadir->count() }}</span>
            </button>
            <button onclick="switchPresTab('absen')" id="tab-absen"
                    class="pres-tab flex-1 py-2.5 text-xs sm:text-sm font-semibold rounded-xl bg-white/5 border border-white/10 text-gray-300 transition-all touch-manipulation">
                ⚠️ Ketidakhadiran
                <span class="ml-1 bg-white/10 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ $presensisAbsen->count() }}</span>
            </button>
        </div>

        {{-- 2 PANEL --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 fade-up">

            {{-- PANEL KIRI: KEHADIRAN --}}
            <div id="panel-hadir" class="glass-card overflow-hidden flex flex-col">
                <div class="sect-header-hadir px-4 sm:px-5 py-3.5 sm:py-4 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm sm:text-base">Kehadiran</h4>
                            <p class="text-emerald-100/80 text-[10px] sm:text-xs leading-tight">Hari masuk untuk setoran</p>
                        </div>
                    </div>
                    <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm text-white px-2.5 py-1 rounded-full font-semibold flex-shrink-0 tabular-nums">{{ $presensisHadir->count() }} hari</span>
                </div>

                <div class="mx-3 sm:mx-4 mt-3 sm:mt-4 px-3 sm:px-4 py-2 sm:py-2.5 info-notice rounded-xl flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[10px] sm:text-xs text-emerald-300">Santri hadir — tidak ada keterangan tambahan</p>
                </div>

                <div class="overflow-x-auto flex-1 mt-3 sm:mt-4" style="-webkit-overflow-scrolling: touch;">
                    <table class="min-w-full">
                        <thead class="dark-thead">
                            <tr>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-8">#</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Tanggal</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Hari</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Sesi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-hadir" class="divide-y divide-white/5">
                            @forelse($presensisHadir as $i => $p)
                            <tr class="row-hadir dark-row-hadir">
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-[10px] sm:text-xs text-gray-600 tabular-nums">{{ $i + 1 }}</td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3">
                                    <p class="text-xs sm:text-sm font-medium text-white whitespace-nowrap tabular-nums">{{ \Carbon\Carbon::parse($p->tanggal)->format('d M Y') }}</p>
                                    <p class="text-[9px] sm:hidden text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($p->tanggal)->locale('id')->isoFormat('dddd') }}</p>
                                </td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-xs text-gray-400 whitespace-nowrap hidden sm:table-cell">{{ \Carbon\Carbon::parse($p->tanggal)->locale('id')->isoFormat('dddd') }}</td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3">
                                    @if($p->sesi)
                                    <span class="status-badge status-hadir px-2 py-0.5 text-[10px] sm:text-xs">{{ $p->sesi }}</span>
                                    @else
                                    <span class="text-[10px] sm:text-xs text-gray-600">−</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-emerald-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-500">Tidak ada data kehadiran</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($presensisHadir->count() > 0)
                <div id="pagination-hadir" class="px-3 sm:px-4 py-2.5 sm:py-3 border-t border-white/5 bg-white/[0.02] flex-shrink-0"></div>
                @endif
            </div>

            {{-- PANEL KANAN: KETIDAKHADIRAN --}}
            <div id="panel-absen" class="hidden lg:flex glass-card overflow-hidden flex-col">
                <div class="sect-header-absen px-4 sm:px-5 py-3.5 sm:py-4 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm sm:text-base">Ketidakhadiran</h4>
                            <p class="text-slate-300 text-[10px] sm:text-xs leading-tight">Izin · Sakit · Alpha</p>
                        </div>
                    </div>
                    <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm text-white px-2.5 py-1 rounded-full font-semibold flex-shrink-0 tabular-nums">{{ $presensisAbsen->count() }} hari</span>
                </div>

                <div class="mx-3 sm:mx-4 mt-3 sm:mt-4 px-3 sm:px-4 py-2 sm:py-2.5 warn-notice rounded-xl flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-[10px] sm:text-xs text-amber-300">
                        @if($presensisAbsen->count() > 0) Terdapat keterangan alasan ketidakhadiran
                        @else Tidak ada ketidakhadiran bulan ini @endif
                    </p>
                </div>

                <div class="overflow-x-auto flex-1 mt-3 sm:mt-4" style="-webkit-overflow-scrolling: touch;">
                    <table class="min-w-full">
                        <thead class="dark-thead">
                            <tr>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-8">#</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Tanggal</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Hari</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-16 sm:w-20">Status</th>
                                <th class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-absen" class="divide-y divide-white/5">
                            @forelse($presensisAbsen as $i => $p)
                            <tr class="row-absen dark-row">
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-[10px] sm:text-xs text-gray-600 tabular-nums">{{ $i + 1 }}</td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3">
                                    <p class="text-xs sm:text-sm font-medium text-white whitespace-nowrap tabular-nums">{{ \Carbon\Carbon::parse($p->tanggal)->format('d M Y') }}</p>
                                    <p class="text-[9px] sm:hidden text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($p->tanggal)->locale('id')->isoFormat('dddd') }}</p>
                                </td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-xs text-gray-400 whitespace-nowrap hidden sm:table-cell">{{ \Carbon\Carbon::parse($p->tanggal)->locale('id')->isoFormat('dddd') }}</td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-center">
                                    @if($p->status === 'Izin')
                                    <span class="status-badge status-izin px-2 sm:px-2.5 py-0.5 sm:py-1 text-[10px] sm:text-xs">📋 Izin</span>
                                    @elseif($p->status === 'Sakit')
                                    <span class="status-badge status-sakit px-2 sm:px-2.5 py-0.5 sm:py-1 text-[10px] sm:text-xs">🏥 Sakit</span>
                                    @else
                                    <span class="status-badge status-alpha px-2 sm:px-2.5 py-0.5 sm:py-1 text-[10px] sm:text-xs">✗ Alpha</span>
                                    @endif
                                </td>
                                <td class="px-2.5 sm:px-4 py-2.5 sm:py-3 text-[10px] sm:text-sm text-gray-400">
                                    @if($p->keterangan)
                                    <span class="italic leading-snug">{{ $p->keterangan }}</span>
                                    @else
                                    <span class="text-[9px] sm:text-xs text-gray-600 italic">@if($p->status === 'Alpha') Tanpa keterangan @else — @endif</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-emerald-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-300 font-semibold">Tidak ada ketidakhadiran</p>
                                    <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Santri hadir penuh bulan ini 🎉</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($presensisAbsen->count() > 0)
                <div id="pagination-absen" class="px-3 sm:px-4 py-2.5 sm:py-3 border-t border-white/5 bg-white/[0.02] flex-shrink-0"></div>
                @endif
            </div>

        </div>

        <div class="h-6 sm:h-0"></div>
    </div>
</div>

<script>
function toggleMobileFilter() { document.getElementById('mobileFilterPanel')?.classList.toggle('hidden'); }

let activePresTab = 'hadir';
function switchPresTab(tab) {
    if (window.innerWidth >= 1024) return;
    activePresTab = tab;
    const tabHadir = document.getElementById('tab-hadir');
    const tabAbsen = document.getElementById('tab-absen');
    const panelHadir = document.getElementById('panel-hadir');
    const panelAbsen = document.getElementById('panel-absen');
    if (tab === 'hadir') {
        tabHadir.classList.remove('bg-white/5','border','border-white/10','text-gray-300');
        tabHadir.classList.add('bg-emerald-600','text-white','shadow-lg','shadow-emerald-900/30');
        tabAbsen.classList.remove('bg-slate-600','text-white','shadow-lg','shadow-slate-900/30');
        tabAbsen.classList.add('bg-white/5','border','border-white/10','text-gray-300');
        panelHadir.classList.remove('hidden'); panelHadir.classList.add('flex');
        panelAbsen.classList.add('hidden'); panelAbsen.classList.remove('flex');
    } else {
        tabAbsen.classList.remove('bg-white/5','border','border-white/10','text-gray-300');
        tabAbsen.classList.add('bg-slate-600','text-white','shadow-lg','shadow-slate-900/30');
        tabHadir.classList.remove('bg-emerald-600','text-white','shadow-lg','shadow-emerald-900/30');
        tabHadir.classList.add('bg-white/5','border','border-white/10','text-gray-300');
        panelAbsen.classList.remove('hidden'); panelAbsen.classList.add('flex');
        panelHadir.classList.add('hidden'); panelHadir.classList.remove('flex');
    }
}

function initPagination(rowClass, tbodyId, paginationId, perPage) {
    const tbody = document.getElementById(tbodyId); if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('.' + rowClass));
    const totalRows = rows.length, totalPages = Math.ceil(totalRows / perPage);
    if (totalPages <= 1) return;
    let cur = 1;
    function show(page) {
        cur = page;
        const s = (page-1)*perPage, e = s+perPage;
        rows.forEach((r,i) => r.style.display = (i>=s && i<e) ? '' : 'none');
        render();
    }
    function render() {
        const c = document.getElementById(paginationId); if (!c) return;
        const from = Math.min((cur-1)*perPage+1,totalRows), to = Math.min(cur*perPage,totalRows);
        const n='px-2.5 py-1 text-xs rounded-md font-medium text-gray-400 hover:bg-white/5 hover:text-white cursor-pointer transition';
        const a='px-2.5 py-1 text-xs rounded-md font-semibold bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-900/30';
        const d='px-2.5 py-1 text-xs rounded-md text-gray-600 cursor-not-allowed';
        let h=`<div class="flex items-center justify-between gap-2 flex-wrap"><span class="text-[11px] text-gray-500 whitespace-nowrap tabular-nums">${from}–${to} dari ${totalRows}</span><div class="flex items-center gap-1 flex-wrap">`;
        h+=`<button onclick="gp_${tbodyId}(${cur-1})" ${cur===1?'disabled':''} class="${cur===1?d:n}">‹</button>`;
        let sp=Math.max(1,cur-2),ep=Math.min(totalPages,sp+4); if(ep-sp<4)sp=Math.max(1,ep-4);
        if(sp>1){h+=`<button onclick="gp_${tbodyId}(1)" class="${n}">1</button>`;if(sp>2)h+=`<span class="text-xs text-gray-600">…</span>`;}
        for(let i=sp;i<=ep;i++)h+=`<button onclick="gp_${tbodyId}(${i})" class="${i===cur?a:n}">${i}</button>`;
        if(ep<totalPages){if(ep<totalPages-1)h+=`<span class="text-xs text-gray-600">…</span>`;h+=`<button onclick="gp_${tbodyId}(${totalPages})" class="${n}">${totalPages}</button>`;}
        h+=`<button onclick="gp_${tbodyId}(${cur+1})" ${cur===totalPages?'disabled':''} class="${cur===totalPages?d:n}">›</button>`;
        h+=`</div></div>`; c.innerHTML = h;
    }
    window[`gp_${tbodyId}`] = p => { if(p>=1&&p<=totalPages)show(p); };
    show(1);
}

document.addEventListener('DOMContentLoaded', () => {
    initPagination('row-hadir','tbody-hadir','pagination-hadir',15);
    initPagination('row-absen','tbody-absen','pagination-absen',10);
    if (window.innerWidth < 1024) {
        const panelAbsen = document.getElementById('panel-absen');
        if (panelAbsen) { panelAbsen.classList.add('hidden'); panelAbsen.classList.remove('lg:flex'); }
    }
    window.addEventListener('resize', () => {
        const panelHadir = document.getElementById('panel-hadir'), panelAbsen = document.getElementById('panel-absen');
        if (window.innerWidth >= 1024) {
            panelHadir?.classList.remove('hidden'); panelHadir?.classList.add('flex');
            panelAbsen?.classList.remove('hidden'); panelAbsen?.classList.add('flex');
        } else {
            if (activePresTab === 'hadir') {
                panelHadir?.classList.remove('hidden'); panelHadir?.classList.add('flex');
                panelAbsen?.classList.add('hidden'); panelAbsen?.classList.remove('flex');
            } else {
                panelAbsen?.classList.remove('hidden'); panelAbsen?.classList.add('flex');
                panelHadir?.classList.add('hidden'); panelHadir?.classList.remove('flex');
            }
        }
    });
});
</script>

@endsection
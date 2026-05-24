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
        pointer-events: none;
    }

    .dark-thead {
        background: rgba(26, 35, 50, 0.6);
        backdrop-filter: blur(8px);
    }
    tr.dark-row { transition: background 0.15s; }
    tr.dark-row:hover td { background: rgba(99, 102, 241, 0.06); }

    
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
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: white;
        transition: all 0.2s;
    }
    .filter-input:focus {
        background: rgba(255, 255, 255, 0.05);
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
    .sect-header-hafalan {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.1), 0 4px 12px -4px rgba(5, 150, 105, 0.4);
    }
    .sect-header-murajaah {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.1), 0 4px 12px -4px rgba(37, 99, 235, 0.4);
    }
    .sect-header-tilawah {
        background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.1), 0 4px 12px -4px rgba(147, 51, 234, 0.4);
    }

    /* Section panel wrapper */
    .sect-panel {
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 0.875rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.02);
    }

    /* Action icon buttons */
    .icon-btn {
        transition: all 0.15s;
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
    }
    .icon-btn:active { transform: scale(0.9); }

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

    /* Input date picker dark fix */
    input[type="date"], input[type="month"] { color-scheme: dark; }
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="month"]::-webkit-calendar-picker-indicator {
        background-color: transparent;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
        background-size: 16px;
        width: 20px; height: 20px;
        cursor: pointer;
        opacity: 1;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover,
    input[type="month"]::-webkit-calendar-picker-indicator:hover { opacity: 0.7; }

    /* Nilai badge */
    .nilai-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        border-radius: 0.5rem;
        transition: all 0.15s;
    }
    .nilai-tinggi {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.1));
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .nilai-sedang {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.1));
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    .nilai-rendah {
        background: linear-gradient(135deg, rgba(234, 179, 8, 0.2), rgba(202, 138, 4, 0.1));
        color: #fbbf24;
        border: 1px solid rgba(234, 179, 8, 0.3);
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
                        Detail Setoran
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
        <form method="GET" action="{{ route('santri.setorans.detail') }}" class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Kategori</label>
                <select name="kategori" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                    <option value="">Semua Kategori</option>
                    <option value="Hafalan"  {{ $kategori == 'Hafalan'  ? 'selected' : '' }}>📚 Hafalan</option>
                    <option value="Murajaah" {{ $kategori == 'Murajaah' ? 'selected' : '' }}>🔄 Murajaah</option>
                    <option value="Tilawah"  {{ $kategori == 'Tilawah'  ? 'selected' : '' }}>📖 Tilawah</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
            </div>
            <button type="submit" class="btn-primary w-full py-2.5 text-white text-sm font-semibold rounded-xl touch-manipulation">
                Terapkan Filter
            </button>
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
                    <h1 class="text-xl lg:text-2xl font-bold text-white tracking-tight">Detail Setoran</h1>
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
               
                <div class="min-w-0 flex-1">
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
            <form method="GET" action="{{ route('santri.setorans.detail') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Kategori</label>
                    <select name="kategori" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                        <option value="">Semua Kategori</option>
                        <option value="Hafalan"  {{ $kategori == 'Hafalan'  ? 'selected' : '' }}>Hafalan</option>
                        <option value="Murajaah" {{ $kategori == 'Murajaah' ? 'selected' : '' }}>Murajaah</option>
                        <option value="Tilawah"  {{ $kategori == 'Tilawah'  ? 'selected' : '' }}>Tilawah</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full px-4 py-2.5 text-white text-sm font-semibold rounded-xl flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        {{-- STATISTIK CARDS --}}
        <div class="grid grid-cols-3 gap-2.5 sm:gap-3 lg:gap-4 mb-5 fade-up">

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); box-shadow: 0 10px 25px -10px rgba(99, 102, 241, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Total Setoran</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['total_setoran'] }}</p>
                        <span class="text-sm font-bold opacity-70">×</span>
                    </div>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">kali setoran</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-[0.07]">
                    <svg class="w-24 h-24 sm:w-32 sm:h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); box-shadow: 0 10px 25px -10px rgba(16, 185, 129, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Rata Nilai</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['rata_nilai'] }}</p>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">dari 100</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-[0.07]">
                    <svg class="w-24 h-24 sm:w-32 sm:h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
            </div>

            <div class="stat-card relative overflow-hidden rounded-2xl p-3.5 sm:p-4 lg:p-5 text-white"
                 style="background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%); box-shadow: 0 10px 25px -10px rgba(168, 85, 247, 0.5);">
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold opacity-90 uppercase tracking-wider">Total Juz</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10 flex-shrink-0 ml-1">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-none tabular-nums">{{ $statistik['total_juz'] }}</p>
                    <p class="text-[10px] sm:text-xs opacity-60 mt-1">juz tercapai</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-[0.07]">
                    <svg class="w-24 h-24 sm:w-32 sm:h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

        </div>

        {{-- TABEL SETORAN PER KATEGORI --}}
        <div class="glass-card overflow-hidden mb-5 fade-up">
            <div class="px-4 sm:px-5 lg:px-6 py-4 border-b border-white/5">
                <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500/20 to-purple-500/20 rounded-lg flex items-center justify-center border border-indigo-400/20">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    Riwayat Setoran Per Kategori
                </h3>
            </div>

            <div class="p-3 sm:p-4 lg:p-5">

                {{-- Mobile Tab Switcher --}}
                <div class="flex gap-1.5 mb-4 md:hidden overflow-x-auto pb-1 snap-x">
                    <button onclick="switchTab('hafalan')" id="tab-hafalan"
                            class="tab-btn flex-shrink-0 snap-start px-3 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white transition-all touch-manipulation shadow-lg shadow-emerald-900/30">
                        📚 Hafalan
                        <span class="ml-1 bg-white/25 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ $setoranHafalan->count() }}</span>
                    </button>
                    <button onclick="switchTab('murajaah')" id="tab-murajaah"
                            class="tab-btn flex-shrink-0 snap-start px-3 py-2 text-xs font-semibold rounded-xl bg-white/5 border border-white/10 text-gray-300 transition-all touch-manipulation">
                        🔄 Murajaah
                        <span class="ml-1 bg-white/10 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ $setoranMurajaah->count() }}</span>
                    </button>
                    <button onclick="switchTab('tilawah')" id="tab-tilawah"
                            class="tab-btn flex-shrink-0 snap-start px-3 py-2 text-xs font-semibold rounded-xl bg-white/5 border border-white/10 text-gray-300 transition-all touch-manipulation">
                        📖 Tilawah
                        <span class="ml-1 bg-white/10 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ $setoranTilawah->count() }}</span>
                    </button>
                </div>

                {{-- Desktop: 2 kolom --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-5 items-stretch">

                    {{-- KOLOM KIRI: HAFALAN --}}
                    <div id="panel-hafalan" class="tab-panel sect-panel flex flex-col">
                        <div class="sect-header-hafalan text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
                            <h4 class="font-bold text-sm sm:text-base flex items-center gap-2">
                                <span class="text-base">📚</span> Hafalan
                            </h4>
                            <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full font-semibold tabular-nums">{{ $setoranHafalan->count() }} setoran</span>
                        </div>
                        @if($setoranHafalan->count() > 0)
                        <div class="overflow-x-auto flex-1" style="-webkit-overflow-scrolling: touch;">
                            <table class="min-w-full">
                                <thead class="dark-thead">
                                    <tr>
                                        <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-8">#</th>
                                        <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-20 sm:w-24">Tanggal</th>
                                        <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Surah & Ayat</th>
                                        <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-12 sm:w-16">Nilai</th>
                                        <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-14 sm:w-20">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbl_hafalan" class="divide-y divide-white/5">
                                    @foreach($setoranHafalan as $i => $setoran)
                                    <tr class="pgrow-hafalan dark-row">
                                        <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-500 tabular-nums">{{ $i + 1 }}</td>
                                        <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-400 whitespace-nowrap tabular-nums">
                                            {{ \Carbon\Carbon::parse($setoran->tanggal)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2.5">
                                            <p class="text-[10px] sm:text-xs text-white font-semibold leading-tight">
                                                {{ $setoran->surat_start }}@if($setoran->surat_start != $setoran->surat_end) — {{ $setoran->surat_end }}@endif
                                            </p>
                                            <p class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5">Ayat {{ $setoran->ayat_start }}–{{ $setoran->ayat_end }}</p>
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                            <span class="nilai-badge px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs
                                                @if($setoran->nilai >= 90) nilai-tinggi
                                                @elseif($setoran->nilai >= 75) nilai-sedang
                                                @else nilai-rendah @endif">
                                                {{ $setoran->nilai }}
                                            </span>
                                        </td>
                                        <td class="px-2.5 sm:px-3 py-2.5">
                                            <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                                                @if($setoran->audio_path)
                                                <button onclick="playAudio({{ $setoran->id }})"
                                                        class="icon-btn p-1.5 text-indigo-400 hover:bg-indigo-500/15 rounded-lg" title="Audio">
                                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                                                </button>
                                                @endif
                                                @if($setoran->koreksi_pdf_path)
                                                <button onclick="viewPDF({{ $setoran->id }})"
                                                        class="icon-btn p-1.5 text-red-400 hover:bg-red-500/15 rounded-lg" title="PDF">
                                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </button>
                                                @endif
                                                @if($setoran->catatan_guru)
                                                <button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}','{{ $setoran->guru->name ?? 'Guru' }}')"
                                                        class="icon-btn p-1.5 text-amber-400 hover:bg-amber-500/15 rounded-lg" title="Catatan">
                                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                @endif
                                                @if(!$setoran->audio_path && !$setoran->koreksi_pdf_path && !$setoran->catatan_guru)
                                                <span class="text-[10px] text-gray-600">−</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="pg_hafalan" class="px-3 sm:px-4 py-2.5 border-t border-white/5 bg-white/[0.02] flex-shrink-0"></div>
                        @else
                        <div class="flex-1 flex items-center justify-center p-8 text-center text-gray-600 min-h-[120px]">
                            <div>
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-sm">Tidak ada data hafalan</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- KOLOM KANAN: MURAJAAH + TILAWAH --}}
                    <div class="space-y-4 lg:space-y-5">

                        {{-- MURAJAAH --}}
                        <div id="panel-murajaah" class="tab-panel hidden md:block sect-panel">
                            <div class="sect-header-murajaah text-white px-4 py-3 flex items-center justify-between">
                                <h4 class="font-bold text-sm sm:text-base flex items-center gap-2">
                                    <span class="text-base">🔄</span> Murajaah
                                </h4>
                                <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full font-semibold tabular-nums">{{ $setoranMurajaah->count() }} setoran</span>
                            </div>
                            @if($setoranMurajaah->count() > 0)
                            <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                                <table class="min-w-full">
                                    <thead class="dark-thead">
                                        <tr>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-8">#</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-20 sm:w-24">Tanggal</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Surah & Ayat</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-12 sm:w-16">Nilai</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-14 sm:w-20">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl_murajaah" class="divide-y divide-white/5">
                                        @foreach($setoranMurajaah as $i => $setoran)
                                        <tr class="pgrow-murajaah dark-row">
                                            <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-500 tabular-nums">{{ $i + 1 }}</td>
                                            <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-400 whitespace-nowrap tabular-nums">{{ \Carbon\Carbon::parse($setoran->tanggal)->format('d/m/Y') }}</td>
                                            <td class="px-2.5 sm:px-3 py-2.5">
                                                <p class="text-[10px] sm:text-xs text-white font-semibold leading-tight">{{ $setoran->surat_start }}@if($setoran->surat_start != $setoran->surat_end) — {{ $setoran->surat_end }}@endif</p>
                                                <p class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5">Ayat {{ $setoran->ayat_start }}–{{ $setoran->ayat_end }}</p>
                                            </td>
                                            <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                <span class="nilai-badge px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs
                                                    @if($setoran->nilai >= 90) nilai-tinggi
                                                    @elseif($setoran->nilai >= 75) nilai-sedang
                                                    @else nilai-rendah @endif">
                                                    {{ $setoran->nilai }}
                                                </span>
                                            </td>
                                            <td class="px-2.5 sm:px-3 py-2.5">
                                                <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                                                    @if($setoran->audio_path)<button onclick="playAudio({{ $setoran->id }})" class="icon-btn p-1.5 text-indigo-400 hover:bg-indigo-500/15 rounded-lg"><svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>@endif
                                                    @if($setoran->koreksi_pdf_path)<button onclick="viewPDF({{ $setoran->id }})" class="icon-btn p-1.5 text-red-400 hover:bg-red-500/15 rounded-lg"><svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></button>@endif
                                                    @if($setoran->catatan_guru)<button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}','{{ $setoran->guru->name ?? 'Guru' }}')" class="icon-btn p-1.5 text-amber-400 hover:bg-amber-500/15 rounded-lg"><svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>@endif
                                                    @if(!$setoran->audio_path && !$setoran->koreksi_pdf_path && !$setoran->catatan_guru)<span class="text-[10px] text-gray-600">−</span>@endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="pg_murajaah" class="px-3 sm:px-4 py-2.5 border-t border-white/5 bg-white/[0.02]"></div>
                            @else
                            <div class="p-8 text-center text-gray-600 min-h-[100px] flex items-center justify-center">
                                <div><svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="text-sm">Tidak ada data murajaah</p></div>
                            </div>
                            @endif
                        </div>

                        {{-- TILAWAH --}}
                        <div id="panel-tilawah" class="tab-panel hidden md:block sect-panel">
                            <div class="sect-header-tilawah text-white px-4 py-3 flex items-center justify-between">
                                <h4 class="font-bold text-sm sm:text-base flex items-center gap-2">
                                    <span class="text-base">📖</span> Tilawah
                                </h4>
                                <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full font-semibold tabular-nums">{{ $setoranTilawah->count() }} setoran</span>
                            </div>
                            @if($setoranTilawah->count() > 0)
                            <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                                <table class="min-w-full">
                                    <thead class="dark-thead">
                                        <tr>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-8">#</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-20 sm:w-24">Tanggal</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Surah & Ayat</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-12 sm:w-16">Nilai</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wider w-14 sm:w-20">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl_tilawah" class="divide-y divide-white/5">
                                        @foreach($setoranTilawah as $i => $setoran)
                                        <tr class="pgrow-tilawah dark-row">
                                            <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-500 tabular-nums">{{ $i + 1 }}</td>
                                            <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-400 whitespace-nowrap tabular-nums">{{ \Carbon\Carbon::parse($setoran->tanggal)->format('d/m/Y') }}</td>
                                            <td class="px-2.5 sm:px-3 py-2.5">
                                                <p class="text-[10px] sm:text-xs text-white font-semibold leading-tight">{{ $setoran->surat_start }}@if($setoran->surat_start != $setoran->surat_end) — {{ $setoran->surat_end }}@endif</p>
                                                <p class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5">Ayat {{ $setoran->ayat_start }}–{{ $setoran->ayat_end }}</p>
                                            </td>
                                            <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                <span class="nilai-badge px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs
                                                    @if($setoran->nilai >= 90) nilai-tinggi
                                                    @elseif($setoran->nilai >= 75) nilai-sedang
                                                    @else nilai-rendah @endif">
                                                    {{ $setoran->nilai }}
                                                </span>
                                            </td>
                                            <td class="px-2.5 sm:px-3 py-2.5">
                                                <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                                                    @if($setoran->audio_path)<button onclick="playAudio({{ $setoran->id }})" class="icon-btn p-1.5 text-indigo-400 hover:bg-indigo-500/15 rounded-lg"><svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>@endif
                                                    @if($setoran->koreksi_pdf_path)<button onclick="viewPDF({{ $setoran->id }})" class="icon-btn p-1.5 text-red-400 hover:bg-red-500/15 rounded-lg"><svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></button>@endif
                                                    @if($setoran->catatan_guru)<button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}','{{ $setoran->guru->name ?? 'Guru' }}')" class="icon-btn p-1.5 text-amber-400 hover:bg-amber-500/15 rounded-lg"><svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>@endif
                                                    @if(!$setoran->audio_path && !$setoran->koreksi_pdf_path && !$setoran->catatan_guru)<span class="text-[10px] text-gray-600">−</span>@endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="pg_tilawah" class="px-3 sm:px-4 py-2.5 border-t border-white/5 bg-white/[0.02]"></div>
                            @else
                            <div class="p-8 text-center text-gray-600 min-h-[100px] flex items-center justify-center">
                                <div><svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="text-sm">Tidak ada data tilawah</p></div>
                            </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="h-6 sm:h-0"></div>
    </div>
</div>

{{-- AUDIO MODAL --}}
<div id="audioModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="w-full sm:w-auto sm:min-w-[400px] sm:max-w-md glass-card rounded-t-2xl sm:rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-white/5">
            <h3 class="text-sm sm:text-base font-semibold text-white flex items-center gap-2">
                <span class="w-7 h-7 bg-indigo-500/15 border border-indigo-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                </span>
                Rekaman Audio
            </h3>
            <button onclick="closeAudioModal()"
                    class="icon-btn p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4">
            <audio id="modalAudioPlayer" controls class="w-full rounded-lg">
                <source id="audioSource" src="" type="audio/webm">
            </audio>
        </div>
        <div class="pb-4 sm:pb-0"></div>
    </div>
</div>

{{-- PDF MODAL --}}
<div id="pdfModal" class="hidden fixed inset-0 bg-black/85 backdrop-blur-sm z-[200] overflow-y-auto">
    <div class="flex flex-col min-h-screen md:block md:py-6 md:px-4">
        <div class="flex flex-col flex-1 md:flex-none glass-card md:rounded-2xl shadow-2xl md:max-w-5xl md:mx-auto overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 sm:px-5 sm:py-4 border-b border-white/5 flex-shrink-0">
                <h3 class="text-sm sm:text-base font-semibold text-white flex items-center gap-2">
                    <span class="w-7 h-7 bg-red-500/15 border border-red-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </span>
                    Preview PDF Koreksi
                </h3>
                <div class="flex items-center gap-2">
                    <a id="downloadPdfBtn" href="#" download
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 rounded-lg transition text-xs font-semibold touch-manipulation">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </a>
                    <button onclick="closePDFModal()"
                            class="icon-btn p-1.5 text-gray-400 hover:text-white hover:bg-white/5 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 p-3 sm:p-4" style="min-height: 300px;">
                <iframe id="pdfFrame" src="" class="w-full h-full rounded-lg border border-white/5 bg-white" style="height: calc(100vh - 130px); min-height: 300px;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- NOTE MODAL --}}
<div id="noteModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="w-full sm:max-w-lg glass-card rounded-t-2xl sm:rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-white/5">
            <h3 class="text-sm sm:text-base font-semibold text-white flex items-center gap-2">
                <span class="w-7 h-7 bg-amber-500/15 border border-amber-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                Catatan Guru
            </h3>
            <button onclick="closeNoteModal()"
                    class="icon-btn p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4">
            <p class="text-xs text-gray-500 mb-3">Dari: <span id="noteGuru" class="font-semibold text-gray-300"></span></p>
            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4">
                <p id="noteContent" class="text-xs sm:text-sm text-gray-200 whitespace-pre-wrap leading-relaxed"></p>
            </div>
        </div>
        <div class="pb-5 px-5">
            <button onclick="closeNoteModal()"
                    class="btn-secondary w-full py-2.5 text-gray-300 text-sm font-semibold rounded-xl touch-manipulation">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
function toggleMobileFilter() { document.getElementById('mobileFilterPanel')?.classList.toggle('hidden'); }

const TAB_ACTIVE = {
    hafalan:  ['bg-emerald-600','text-white','shadow-lg','shadow-emerald-900/30'],
    murajaah: ['bg-blue-600','text-white','shadow-lg','shadow-blue-900/30'],
    tilawah:  ['bg-purple-600','text-white','shadow-lg','shadow-purple-900/30'],
};
const TAB_INACTIVE = ['bg-white/5','border','border-white/10','text-gray-300'];
let activeTab = 'hafalan';

function switchTab(tab) {
    if (window.innerWidth >= 768) return;
    activeTab = tab;
    Object.keys(TAB_ACTIVE).forEach(key => {
        const btn = document.getElementById('tab-' + key);
        if (!btn) return;
        const all = Object.values(TAB_ACTIVE).flat().concat(TAB_INACTIVE);
        btn.classList.remove(...all);
        btn.classList.add(...(key === tab ? TAB_ACTIVE[key] : TAB_INACTIVE));
    });
    Object.keys(TAB_ACTIVE).forEach(key => {
        const el = document.getElementById('panel-' + key);
        if (!el) return;
        if (key === tab) { el.classList.remove('hidden'); }
        else { el.classList.add('hidden'); if (key !== 'hafalan') el.classList.add('md:block'); }
    });
}

const pgState = {};
function initPagination(tbodyId, rowClass, pgId, perPage) {
    const tbody = document.getElementById(tbodyId); if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('.' + rowClass)); if (!rows.length) return;
    const totalRows = rows.length, totalPages = Math.ceil(totalRows / perPage);
    pgState[tbodyId] = { rows, totalRows, totalPages, perPage, currentPage: 1 };
    if (totalPages <= 1) return;
    showPage(tbodyId, pgId, 1);
}
function showPage(tbodyId, pgId, page) {
    const s = pgState[tbodyId]; if (!s || page < 1 || page > s.totalPages) return;
    s.currentPage = page;
    const start = (page-1)*s.perPage, end = start+s.perPage;
    s.rows.forEach((row,i) => { row.style.display = (i>=start && i<end) ? '' : 'none'; });
    renderPagination(tbodyId, pgId);
}
function renderPagination(tbodyId, pgId) {
    const s = pgState[tbodyId], container = document.getElementById(pgId); if (!s||!container) return;
    const {currentPage,totalPages,totalRows,perPage} = s;
    const from = Math.min((currentPage-1)*perPage+1,totalRows), to = Math.min(currentPage*perPage,totalRows);
    const active = 'px-2.5 py-1 text-xs rounded-md font-semibold bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-900/30';
    const normal = 'px-2.5 py-1 text-xs rounded-md font-medium text-gray-400 hover:bg-white/5 hover:text-white cursor-pointer transition';
    const disabled = 'px-2.5 py-1 text-xs rounded-md text-gray-600 cursor-not-allowed';
    let html = `<div class="flex items-center justify-between gap-2 flex-wrap"><span class="text-[11px] text-gray-500 whitespace-nowrap tabular-nums">${from}–${to} dari ${totalRows}</span><div class="flex items-center gap-1 flex-wrap">`;
    if (currentPage===1) html+=`<button disabled class="${disabled}">‹</button>`;
    else html+=`<button onclick="showPage('${tbodyId}','${pgId}',${currentPage-1})" class="${normal}">‹</button>`;
    let sp=Math.max(1,currentPage-2), ep=Math.min(totalPages,sp+4); if(ep-sp<4)sp=Math.max(1,ep-4);
    if(sp>1){html+=`<button onclick="showPage('${tbodyId}','${pgId}',1)" class="${normal}">1</button>`;if(sp>2)html+=`<span class="text-xs text-gray-600">…</span>`;}
    for(let i=sp;i<=ep;i++)html+=`<button onclick="showPage('${tbodyId}','${pgId}',${i})" class="${i===currentPage?active:normal}">${i}</button>`;
    if(ep<totalPages){if(ep<totalPages-1)html+=`<span class="text-xs text-gray-600">…</span>`;html+=`<button onclick="showPage('${tbodyId}','${pgId}',${totalPages})" class="${normal}">${totalPages}</button>`;}
    if(currentPage===totalPages)html+=`<button disabled class="${disabled}">›</button>`;
    else html+=`<button onclick="showPage('${tbodyId}','${pgId}',${currentPage+1})" class="${normal}">›</button>`;
    html+=`</div></div>`;
    container.innerHTML = html;
}

function playAudio(id) { document.getElementById('audioSource').src=`/santri/setorans/${id}/audio`; document.getElementById('modalAudioPlayer').load(); document.getElementById('audioModal').classList.remove('hidden'); document.body.style.overflow='hidden'; document.getElementById('modalAudioPlayer').play(); }
function closeAudioModal() { document.getElementById('modalAudioPlayer').pause(); document.getElementById('audioModal').classList.add('hidden'); document.body.style.overflow=''; }
function viewPDF(id) { const url=`/santri/setorans/${id}/pdf`; document.getElementById('pdfFrame').src=url; document.getElementById('downloadPdfBtn').href=url; document.getElementById('pdfModal').classList.remove('hidden'); document.body.style.overflow='hidden'; }
function closePDFModal() { document.getElementById('pdfFrame').src=''; document.getElementById('pdfModal').classList.add('hidden'); document.body.style.overflow=''; }
function showNote(note,guru) { document.getElementById('noteContent').textContent=note; document.getElementById('noteGuru').textContent=guru; document.getElementById('noteModal').classList.remove('hidden'); document.body.style.overflow='hidden'; }
function closeNoteModal() { document.getElementById('noteModal').classList.add('hidden'); document.body.style.overflow=''; }

document.addEventListener('DOMContentLoaded', function () {
    initPagination('tbl_hafalan','pgrow-hafalan','pg_hafalan',15);
    initPagination('tbl_murajaah','pgrow-murajaah','pg_murajaah',6);
    initPagination('tbl_tilawah','pgrow-tilawah','pg_tilawah',6);
    if (window.innerWidth < 768) {
        ['panel-murajaah','panel-tilawah'].forEach(id => document.getElementById(id)?.classList.add('hidden'));
    }
    document.getElementById('audioModal')?.addEventListener('click', e => { if(e.target===document.getElementById('audioModal')) closeAudioModal(); });
    document.getElementById('pdfModal')?.addEventListener('click', e => { if(e.target===document.getElementById('pdfModal')) closePDFModal(); });
    document.getElementById('noteModal')?.addEventListener('click', e => { if(e.target===document.getElementById('noteModal')) closeNoteModal(); });
    document.addEventListener('keydown', e => { if(e.key==='Escape'){ closeAudioModal(); closePDFModal(); closeNoteModal(); } });
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            ['panel-hafalan','panel-murajaah','panel-tilawah'].forEach(id => document.getElementById(id)?.classList.remove('hidden'));
        } else { switchTab(activeTab); }
    });
});
</script>

@endsection
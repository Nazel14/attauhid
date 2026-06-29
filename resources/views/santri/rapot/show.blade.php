@extends('layouts.app')

@section('content')

{{-- ============================================================ --}}
{{-- STYLES - Light/Dark glass theme matching Dashboard Santri      --}}
{{-- ============================================================ --}}
<style>
    .page-bg {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #faf5ff 100%);
        min-height: 100vh;
    }
    html.dark .page-bg {
        background:
            radial-gradient(ellipse 80% 50% at 50% -10%, rgba(99, 102, 241, 0.15), transparent),
            radial-gradient(ellipse 60% 40% at 80% 30%, rgba(168, 85, 247, 0.08), transparent),
            #0f172a;
    }

    /* Glass card */
    .glass-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 1.25rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .glass-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.3), transparent);
        pointer-events: none;
    }
    html.dark .glass-card {
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.8) 0%, rgba(17, 24, 39, 0.6) 100%);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(99, 102, 241, 0.1);
        box-shadow: 0 4px 20px rgba(0,0,0,0.35);
    }
    html.dark .glass-card::before {
        background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.4), transparent);
    }

    .table-thead { background: #f9fafb; }
    html.dark .table-thead { background: rgba(26, 35, 50, 0.6); backdrop-filter: blur(8px); }

    tr.table-row { transition: background 0.15s; }
    tr.table-row:hover td { background: rgba(99, 102, 241, 0.06); }

    /* Filter input */
    .filter-input {
        background: #f9fafb !important;
        border: 1px solid #e5e7eb;
        color: #111827 !important;
        transition: all 0.2s;
    }
    .filter-input:focus {
        background: #ffffff !important;
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }
    html.dark .filter-input {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: white !important;
    }
    html.dark .filter-input:focus {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(99, 102, 241, 0.5);
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #ffffff;
        box-shadow: 0 8px 20px -8px rgba(99, 102, 241, 0.6);
        transition: all 0.2s;
    }
    .btn-primary:hover { box-shadow: 0 12px 28px -8px rgba(99, 102, 241, 0.8); transform: translateY(-1px); }
    .btn-primary:active { transform: scale(0.97); }

    .btn-danger {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: #ffffff;
        box-shadow: 0 8px 20px -8px rgba(220, 38, 38, 0.6);
        transition: all 0.2s;
    }
    .btn-danger:hover { box-shadow: 0 12px 28px -8px rgba(220, 38, 38, 0.8); transform: translateY(-1px); }

    .btn-secondary {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #374151;
        transition: all 0.2s;
    }
    .btn-secondary:hover { background: #e5e7eb; border-color: rgba(99, 102, 241, 0.4); }
    html.dark .btn-secondary {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #d1d5db;
    }
    html.dark .btn-secondary:hover { background: rgba(255, 255, 255, 0.07); border-color: rgba(99, 102, 241, 0.3); }

    /* Stat mini card */
    .stat-mini { transition: all 0.2s; }
    .stat-mini:hover { transform: translateY(-1px); }

    /* Section gradient headers (same in both modes - white text on colored gradient) */
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

    /* Section panel */
    .sect-panel {
        border: 1px solid #e5e7eb;
        border-radius: 0.875rem;
        overflow: hidden;
        background: #f9fafb;
    }
    html.dark .sect-panel {
        border: 1px solid rgba(255, 255, 255, 0.06);
        background: rgba(255, 255, 255, 0.02);
    }

    /* Pulse */
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6); }
        100% { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
    }
    .pulse-ring { animation: pulse-ring 2s ease-out infinite; }

    /* Progress bar */
    .progress-track { background: rgba(99, 102, 241, 0.12); border-radius: 9999px; overflow: hidden; }
    html.dark .progress-track { background: rgba(99, 102, 241, 0.1); }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 8px; }
    html.dark ::-webkit-scrollbar-thumb { background: #374151; }
    ::-webkit-scrollbar-thumb:hover { background: #6366f1; }

    /* Touch */
    .touch-btn { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
    .touch-btn:active { opacity: 0.85; transform: scale(0.98); }

    @supports (padding: env(safe-area-inset-bottom)) { .page-bg { padding-bottom: env(safe-area-inset-bottom); } }

    /* Fade animation */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp 0.4s ease-out backwards; }

    /* Nilai badge */
    .nilai-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        border-radius: 0.5rem;
    }
    .nilai-tinggi { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.06)); color: #047857; border: 1px solid rgba(16, 185, 129, 0.3); }
    .nilai-sedang { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.06)); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.3); }
    .nilai-rendah { background: linear-gradient(135deg, rgba(234, 179, 8, 0.15), rgba(202, 138, 4, 0.06)); color: #a16207; border: 1px solid rgba(234, 179, 8, 0.3); }
    .count-badge  { background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(79, 70, 229, 0.06)); color: #4f46e5; border: 1px solid rgba(99, 102, 241, 0.3); }
    html.dark .nilai-tinggi { background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.1)); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    html.dark .nilai-sedang { background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.1)); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    html.dark .nilai-rendah { background: linear-gradient(135deg, rgba(234, 179, 8, 0.2), rgba(202, 138, 4, 0.1)); color: #fbbf24; border: 1px solid rgba(234, 179, 8, 0.3); }
    html.dark .count-badge  { background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.1)); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }

    /* Tab buttons */
    .tab-btn.tab-active-green  { background: linear-gradient(135deg, #059669, #047857); color: #fff; box-shadow: 0 4px 12px -4px rgba(5, 150, 105, 0.5); }
    .tab-btn.tab-active-blue   { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; box-shadow: 0 4px 12px -4px rgba(37, 99, 235, 0.5); }
    .tab-btn.tab-active-purple { background: linear-gradient(135deg, #9333ea, #7e22ce); color: #fff; box-shadow: 0 4px 12px -4px rgba(147, 51, 234, 0.5); }
    .tab-btn-inactive { background: #f3f4f6; border: 1px solid #e5e7eb; color: #4b5563; }
    html.dark .tab-btn-inactive { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: #d1d5db; }

    /* Input date picker */
    input[type="date"], input[type="month"] { color-scheme: light; }
    html.dark input[type="date"], html.dark input[type="month"] { color-scheme: dark; }
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
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 0.7; }

    /* Distribution bar gradient */
    .dist-bar-emerald { background: linear-gradient(90deg, #10b981, #34d399); }
    .dist-bar-blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .dist-bar-yellow { background: linear-gradient(90deg, #eab308, #fbbf24); }
    .dist-bar-red { background: linear-gradient(90deg, #ef4444, #f87171); }

    .dist-track { background: #e5e7eb; }
    html.dark .dist-track { background: rgba(255, 255, 255, 0.05); }
</style>

<div class="page-bg">

    {{-- ============================================================ --}}
    {{-- MOBILE STICKY TOP BAR                                         --}}
    {{-- ============================================================ --}}
    <div class="sticky top-0 z-30 backdrop-blur-xl bg-white/80 dark:bg-gray-900/80 border-b border-gray-200 dark:border-white/5 px-4 py-3 md:hidden">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="{{ route('santri.dashboard') }}"
                   class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl flex-shrink-0 touch-btn">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight flex items-center gap-1.5">
                        <span class="inline-block w-1 h-3.5 bg-gradient-to-b from-indigo-400 to-purple-500 rounded-full"></span>
                        Rapot Lengkap
                    </p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight truncate mt-0.5">{{ $santri->name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button onclick="toggleMobileFilter()"
                        class="touch-btn flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-500/15 border border-indigo-200 dark:border-indigo-500/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-semibold rounded-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                <button onclick="previewPDF()"
                        class="touch-btn flex items-center gap-1 px-2.5 py-1.5 bg-red-50 dark:bg-red-500/15 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-300 text-[11px] font-semibold rounded-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PDF
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MOBILE FILTER DRAWER                                          --}}
    {{-- ============================================================ --}}
    <div id="mobileFilterPanel" class="hidden md:hidden bg-white/95 dark:bg-gray-900/90 backdrop-blur-xl border-b border-gray-200 dark:border-white/5 px-4 py-4">
        <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Filter Periode</p>
        <form method="GET" action="{{ route('santri.rapot.show') }}" class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
            </div>
            <button type="submit" class="btn-primary w-full py-2.5 text-white text-sm font-semibold rounded-xl touch-btn">
                Terapkan Filter
            </button>
        </form>
    </div>

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT                                                   --}}
    {{-- ============================================================ --}}
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">

        {{-- DESKTOP HEADER --}}
        <div class="hidden md:flex items-center justify-between mb-5 fade-up">
            <a href="{{ route('santri.dashboard') }}"
               class="btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 touch-btn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <div class="text-right">
                <div class="flex items-center justify-end gap-2 mb-1">
                    <h1 class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white tracking-tight">Rapot Lengkap</h1>
                    <span class="inline-block w-1 h-5 bg-gradient-to-b from-indigo-400 to-purple-500 rounded-full"></span>
                </div>
                @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-full text-xs">
                    <div class="relative">
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full pulse-ring"></div>
                    </div>
                    <span class="text-indigo-700 dark:text-indigo-200 font-medium">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- SANTRI INFO CARD --}}
        <div class="glass-card p-4 sm:p-5 mb-4 fade-up">
            <div class="flex items-center gap-3 sm:gap-4">
                @php $santriPhoto = $santri->user->profile_photo ?? (auth()->user()->profile_photo ?? null); @endphp
                @if($santriPhoto)
                    <img src="{{ asset('storage/' . $santriPhoto) }}" alt="Foto" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover object-top border-2 border-indigo-200 dark:border-indigo-700 flex-shrink-0">
                @else
                    <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-full {{ ($santri->gender === 'P' || $santri->gender === 'Perempuan') ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-indigo-100 dark:bg-indigo-950 border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400' }} border-2 flex items-center justify-center text-lg font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</h3>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $santri->nis }}</span>
                        <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded-md border border-indigo-200 dark:border-indigo-500/20">
                            {{ $santri->kelas->name ?? '-' }}
                        </span>
                        <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full hidden sm:inline-block"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            {{ $santri->guru->name ?? '-' }}
                        </span>
                    </div>
                </div>
                @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <div class="flex-shrink-0 md:hidden">
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-lg">
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full pulse-ring"></div>
                        <span class="text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 leading-none">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- DESKTOP FILTER --}}
        <div class="hidden md:block glass-card p-4 sm:p-5 mb-4 fade-up">
            <div class="flex items-center gap-2 mb-3.5">
                <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Filter Periode</h3>
            </div>
            <form method="GET" action="{{ route('santri.rapot.show') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="filter-input w-full px-3 py-2.5 text-sm rounded-xl">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full px-4 py-2.5 text-white text-sm font-semibold rounded-xl flex items-center justify-center gap-2 touch-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Filter
                    </button>
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="previewPDF()" class="btn-danger w-full px-4 py-2.5 text-white text-sm font-semibold rounded-xl flex items-center justify-center gap-2 touch-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Preview PDF
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- STATISTIK CARDS                                               --}}
        {{-- ============================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4 fade-up">

            {{-- CARD STATISTIK SETORAN --}}
            <div class="glass-card overflow-hidden">
                <div class="px-4 sm:px-5 py-3.5 border-b border-gray-100 dark:border-white/5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-500/20 dark:to-indigo-500/20 rounded-lg flex items-center justify-center border border-blue-200 dark:border-blue-400/20">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        Statistik Setoran
                    </h3>
                </div>
                <div class="p-4 sm:p-5">

                    {{-- Progress Target Hafalan / Iqro --}}
                    <div class="mb-4 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-950/40 dark:via-indigo-950/30 dark:to-purple-950/20 border border-blue-200 dark:border-blue-500/15 rounded-xl p-3.5">
                        @if($statistikSetoran['is_iqro'] ?? false)
                            <div class="flex justify-between items-start mb-2.5">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Pencapaian Target</p>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Iqro — Jilid {{ $statistikSetoran['target_jilid'] }}</p>
                                </div>
                                <div class="text-2xl font-extrabold bg-gradient-to-r from-emerald-600 to-blue-600 dark:from-emerald-300 dark:to-blue-300 bg-clip-text text-transparent leading-none tabular-nums">{{ $statistikSetoran['progress_iqro'] }}%</div>
                            </div>
                            @if($statistikSetoran['iqro_persen_diketahui'])
                                <div class="progress-track h-2.5 mb-2">
                                    <div class="h-full rounded-full transition-all duration-700"
                                         style="width: {{ max(4, $statistikSetoran['progress_iqro']) }}%; background: linear-gradient(90deg, #10b981, #3b82f6); box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);">
                                    </div>
                                </div>
                                <div class="flex justify-between text-[11px]">
                                    <span class="text-gray-500 dark:text-gray-400">Disetor: <span class="text-gray-900 dark:text-white font-semibold tabular-nums">{{ $statistikSetoran['total_halaman_iqro'] }}</span> hal</span>
                                    <span class="text-gray-500 dark:text-gray-400">Target Jilid {{ $statistikSetoran['target_jilid'] }}: <span class="text-gray-900 dark:text-white font-semibold tabular-nums">{{ $statistikSetoran['total_halaman_jilid'] }}</span> hal</span>
                                </div>
                            @else
                                <div class="bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-3 py-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">Jumlah halaman Jilid {{ $statistikSetoran['target_jilid'] }} belum diatur di konfigurasi</p>
                                </div>
                            @endif
                        @else
                            <div class="flex justify-between items-start mb-2.5">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Pencapaian Target</p>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Hafalan Juz</p>
                                </div>
                                <div class="text-2xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-300 dark:to-indigo-300 bg-clip-text text-transparent leading-none tabular-nums">{{ $statistikSetoran['persentase_target'] }}%</div>
                            </div>
                            <div class="progress-track h-2.5 mb-2">
                                <div class="h-full rounded-full transition-all duration-700"
                                     style="width: {{ max(4, $statistikSetoran['persentase_target']) }}%; background: linear-gradient(90deg, #3b82f6, #6366f1, #a855f7); box-shadow: 0 0 12px rgba(99, 102, 241, 0.4);">
                                </div>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-500 dark:text-gray-400">Tercapai: <span class="text-gray-900 dark:text-white font-semibold tabular-nums">{{ $statistikSetoran['total_juz'] }}</span> Juz</span>
                                <span class="text-gray-500 dark:text-gray-400">Target: <span class="text-gray-900 dark:text-white font-semibold tabular-nums">{{ $statistikSetoran['target_juz'] }}</span> Juz</span>
                            </div>
                        @endif
                    </div>

                    {{-- Stats Grid 2x2 --}}
                    <div class="grid grid-cols-2 gap-2.5 mb-4">
                        <div class="stat-mini bg-blue-50 dark:bg-blue-500/[0.08] border border-blue-200 dark:border-blue-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-blue-700 dark:text-blue-300 tabular-nums leading-none">{{ $statistikSetoran['total_setoran'] }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Total Setoran</div>
                        </div>
                        <div class="stat-mini bg-emerald-50 dark:bg-emerald-500/[0.08] border border-emerald-200 dark:border-emerald-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-300 tabular-nums leading-none">{{ $statistikSetoran['rata_nilai'] }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Rata-rata Nilai</div>
                        </div>
                        <div class="stat-mini bg-purple-50 dark:bg-purple-500/[0.08] border border-purple-200 dark:border-purple-500/20 p-3 rounded-xl">
                            @if($statistikSetoran['is_iqro'] ?? false)
                                <div class="text-2xl font-extrabold text-purple-700 dark:text-purple-300 tabular-nums leading-none">{{ $statistikSetoran['total_halaman_iqro'] }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Total Halaman</div>
                            @else
                                <div class="text-2xl font-extrabold text-purple-700 dark:text-purple-300 tabular-nums leading-none">{{ $statistikSetoran['total_juz'] }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Total Juz</div>
                            @endif
                        </div>
                        <div class="stat-mini bg-orange-50 dark:bg-orange-500/[0.08] border border-orange-200 dark:border-orange-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-orange-700 dark:text-orange-300 tabular-nums leading-none">{{ number_format($statistikSetoran['total_kata']) }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Total Kata</div>
                        </div>
                    </div>

                    {{-- Kategori Setoran --}}
                    <div class="pt-4 border-t border-gray-100 dark:border-white/5">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5">Kategori Setoran</p>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between p-2.5 bg-emerald-50 dark:bg-emerald-500/[0.06] border border-emerald-200 dark:border-emerald-500/20 rounded-lg">
                                <span class="text-xs text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full ring-2 ring-emerald-400/20"></span>
                                    Hafalan
                                </span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-300 text-sm tabular-nums">{{ $statistikSetoran['hafalan'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-2.5 bg-blue-50 dark:bg-blue-500/[0.06] border border-blue-200 dark:border-blue-500/20 rounded-lg">
                                <span class="text-xs text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-blue-400 rounded-full ring-2 ring-blue-400/20"></span>
                                    Murajaah
                                </span>
                                <span class="font-bold text-blue-700 dark:text-blue-300 text-sm tabular-nums">{{ $statistikSetoran['murajaah'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-2.5 bg-purple-50 dark:bg-purple-500/[0.06] border border-purple-200 dark:border-purple-500/20 rounded-lg">
                                <span class="text-xs text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-purple-400 rounded-full ring-2 ring-purple-400/20"></span>
                                    Tilawah
                                </span>
                                <span class="font-bold text-purple-700 dark:text-purple-300 text-sm tabular-nums">{{ $statistikSetoran['tilawah'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD STATISTIK PRESENSI --}}
            <div class="glass-card overflow-hidden">
                <div class="px-4 sm:px-5 py-3.5 border-b border-gray-100 dark:border-white/5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-500/20 dark:to-green-500/20 rounded-lg flex items-center justify-center border border-emerald-200 dark:border-emerald-400/20">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        Statistik Presensi
                    </h3>
                </div>
                <div class="p-4 sm:p-5">

                    {{-- Progress Kehadiran --}}
                    <div class="mb-4 bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 dark:from-emerald-950/40 dark:via-green-950/30 dark:to-teal-950/20 border border-emerald-200 dark:border-emerald-500/15 rounded-xl p-3.5">
                        <div class="flex justify-between items-start mb-2.5">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Persentase Kehadiran</p>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Bulan Ini</p>
                            </div>
                            <div class="text-2xl font-extrabold bg-gradient-to-r from-emerald-600 to-green-600 dark:from-emerald-300 dark:to-green-300 bg-clip-text text-transparent leading-none tabular-nums">{{ $statistikPresensi['persentase_kehadiran'] }}%</div>
                        </div>
                        <div class="progress-track h-2.5 mb-1 bg-emerald-100 dark:bg-emerald-500/10">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="width: {{ max(4, $statistikPresensi['persentase_kehadiran']) }}%; background: linear-gradient(90deg, #10b981, #34d399); box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);">
                            </div>
                        </div>
                        <div class="flex justify-end text-[11px] text-gray-500 dark:text-gray-400">
                            <span><span class="text-gray-900 dark:text-white font-semibold tabular-nums">{{ $statistikPresensi['hadir'] }}</span> / {{ $statistikPresensi['total_hari'] }} hari hadir</span>
                        </div>
                    </div>

                    {{-- Stats Grid 2x2 --}}
                    <div class="grid grid-cols-2 gap-2.5 mb-4">
                        <div class="stat-mini bg-emerald-50 dark:bg-emerald-500/[0.08] border border-emerald-200 dark:border-emerald-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-300 tabular-nums leading-none">{{ $statistikPresensi['hadir'] }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Hadir</div>
                        </div>
                        <div class="stat-mini bg-blue-50 dark:bg-blue-500/[0.08] border border-blue-200 dark:border-blue-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-blue-700 dark:text-blue-300 tabular-nums leading-none">{{ $statistikPresensi['izin'] }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Izin</div>
                        </div>
                        <div class="stat-mini bg-yellow-50 dark:bg-yellow-500/[0.08] border border-yellow-200 dark:border-yellow-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-yellow-700 dark:text-yellow-300 tabular-nums leading-none">{{ $statistikPresensi['sakit'] }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Sakit</div>
                        </div>
                        <div class="stat-mini bg-red-50 dark:bg-red-500/[0.08] border border-red-200 dark:border-red-500/20 p-3 rounded-xl">
                            <div class="text-2xl font-extrabold text-red-700 dark:text-red-300 tabular-nums leading-none">{{ $statistikPresensi['alpha'] }}</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Alpha</div>
                        </div>
                    </div>

                    {{-- Distribusi Kehadiran --}}
                    <div class="pt-4 border-t border-gray-100 dark:border-white/5">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5">Distribusi Kehadiran</p>
                        @php
                            $totalHari = $statistikPresensi['total_hari'];
                            $pctHadir  = $totalHari > 0 ? round(($statistikPresensi['hadir']  / $totalHari) * 100, 1) : 0;
                            $pctIzin   = $totalHari > 0 ? round(($statistikPresensi['izin']   / $totalHari) * 100, 1) : 0;
                            $pctSakit  = $totalHari > 0 ? round(($statistikPresensi['sakit']  / $totalHari) * 100, 1) : 0;
                            $pctAlpha  = $totalHari > 0 ? round(($statistikPresensi['alpha']  / $totalHari) * 100, 1) : 0;
                        @endphp
                        <div class="space-y-2.5">
                            @foreach([
                                ['label'=>'Hadir','val'=>$statistikPresensi['hadir'],'pct'=>$pctHadir,'bar'=>'dist-bar-emerald','text'=>'text-emerald-700 dark:text-emerald-300'],
                                ['label'=>'Izin','val'=>$statistikPresensi['izin'],'pct'=>$pctIzin,'bar'=>'dist-bar-blue','text'=>'text-blue-700 dark:text-blue-300'],
                                ['label'=>'Sakit','val'=>$statistikPresensi['sakit'],'pct'=>$pctSakit,'bar'=>'dist-bar-yellow','text'=>'text-yellow-700 dark:text-yellow-300'],
                                ['label'=>'Alpha','val'=>$statistikPresensi['alpha'],'pct'=>$pctAlpha,'bar'=>'dist-bar-red','text'=>'text-red-700 dark:text-red-300'],
                            ] as $item)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                                    <span class="text-[11px] font-bold {{ $item['text'] }} tabular-nums">{{ $item['val'] }} <span class="text-gray-400 dark:text-gray-500 font-normal">({{ $item['pct'] }}%)</span></span>
                                </div>
                                <div class="w-full h-1.5 dist-track rounded-full overflow-hidden">
                                    <div class="{{ $item['bar'] }} h-full rounded-full transition-all duration-700" style="width:{{ $item['pct'] }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-3.5 p-2.5 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-500/10 dark:to-purple-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-lg flex items-center justify-between">
                            <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-200">Total Hari Presensi</span>
                            <span class="text-lg font-extrabold text-indigo-700 dark:text-indigo-200 tabular-nums">{{ $statistikPresensi['total_hari'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- END STATISTIK GRID --}}

        {{-- ============================================================ --}}
        {{-- RIWAYAT SETORAN                                               --}}
        {{-- ============================================================ --}}
        <div class="glass-card overflow-hidden mb-4 fade-up">
            <div class="px-4 sm:px-5 py-3.5 border-b border-gray-100 dark:border-white/5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-7 h-7 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-500/20 dark:to-purple-500/20 rounded-lg flex items-center justify-center border border-indigo-200 dark:border-indigo-400/20">
                        <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    Riwayat Setoran Per Kategori
                </h3>
            </div>

            @if(count($setorans) > 0)
            <div class="p-3 sm:p-4 lg:p-5">

                {{-- Mobile Tab Switcher --}}
                <div class="flex gap-1.5 mb-4 md:hidden overflow-x-auto pb-1 snap-x">
                    <button onclick="switchTab('hafalan')" id="tab-hafalan"
                            class="tab-btn flex-shrink-0 snap-start px-3 py-2 text-xs font-semibold rounded-xl tab-active-green touch-btn">
                        📚 Hafalan
                        @if(isset($setorans['Hafalan'])) <span class="ml-1 bg-white/25 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ count($setorans['Hafalan']) }}</span> @endif
                    </button>
                    <button onclick="switchTab('murajaah')" id="tab-murajaah"
                            class="tab-btn flex-shrink-0 snap-start px-3 py-2 text-xs font-semibold rounded-xl tab-btn-inactive touch-btn">
                        🔄 Murajaah
                        @if(isset($setorans['Murajaah'])) <span class="ml-1 bg-gray-200 dark:bg-white/10 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ count($setorans['Murajaah']) }}</span> @endif
                    </button>
                    <button onclick="switchTab('tilawah')" id="tab-tilawah"
                            class="tab-btn flex-shrink-0 snap-start px-3 py-2 text-xs font-semibold rounded-xl tab-btn-inactive touch-btn">
                        📖 Tilawah
                        @if(isset($setorans['Tilawah'])) <span class="ml-1 bg-gray-200 dark:bg-white/10 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded-full text-[10px] tabular-nums">{{ count($setorans['Tilawah']) }}</span> @endif
                    </button>
                </div>

                {{-- Desktop: 2 kolom --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-5 items-stretch">

                    {{-- KOLOM KIRI: HAFALAN --}}
                    <div id="panel-hafalan" class="tab-panel flex flex-col">
                        @if(isset($setorans['Hafalan']) && count($setorans['Hafalan']) > 0)
                        <div class="sect-panel flex flex-col flex-1">
                            <div class="sect-header-hafalan text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
                                <h4 class="font-bold text-sm flex items-center gap-2">
                                    <span class="text-base">📚</span> Hafalan
                                </h4>
                                <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full font-semibold tabular-nums">{{ count($setorans['Hafalan']) }} surat</span>
                            </div>
                            <div class="overflow-x-auto flex-1" style="-webkit-overflow-scrolling: touch;">
                                <table class="min-w-full">
                                    <thead class="table-thead">
                                        <tr>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">No</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Surah & Ayat</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">Jml</th>
                                            <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-14">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rtbl_hafalan" class="divide-y divide-gray-100 dark:divide-white/5">
                                        @php $no = 1; @endphp
                                        @foreach($setorans['Hafalan'] as $suratKey => $data)
                                        <tr class="rpgrow-hafalan table-row">
                                            <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 tabular-nums">{{ $no++ }}</td>
                                            <td class="px-2.5 sm:px-3 py-2.5">
                                                <p class="text-[10px] sm:text-xs text-gray-900 dark:text-white font-semibold leading-tight">{{ $data['surat_start'] }}@if($data['surat_start'] != $data['surat_end']) — {{ $data['surat_end'] }}@endif</p>
                                                <p class="text-[9px] sm:text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Ayat {{ $data['ayat_start_min'] }}–{{ $data['ayat_end_max'] }}</p>
                                            </td>
                                            <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                <span class="nilai-badge count-badge px-1.5 sm:px-2 py-0.5 text-[10px]">{{ $data['jumlah_setoran'] }}×</span>
                                            </td>
                                            <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                <span class="nilai-badge px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs
                                                    @if($data['rata_nilai'] >= 90) nilai-tinggi
                                                    @elseif($data['rata_nilai'] >= 75) nilai-sedang
                                                    @else nilai-rendah @endif">
                                                    {{ $data['rata_nilai'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="rpg_hafalan" class="px-3 sm:px-4 py-2.5 border-t border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/[0.02] flex-shrink-0"></div>
                        </div>
                        @else
                        <div class="sect-panel p-8 flex items-center justify-center min-h-[120px] flex-1">
                            <div class="text-center text-gray-400 dark:text-gray-600">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-sm">Tidak ada data hafalan</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- KOLOM KANAN: MURAJAAH + TILAWAH --}}
                    <div class="space-y-4">

                        {{-- MURAJAAH --}}
                        <div id="panel-murajaah" class="tab-panel hidden md:block">
                            @if(isset($setorans['Murajaah']) && count($setorans['Murajaah']) > 0)
                            <div class="sect-panel">
                                <div class="sect-header-murajaah text-white px-4 py-3 flex items-center justify-between">
                                    <h4 class="font-bold text-sm flex items-center gap-2">
                                        <span class="text-base">🔄</span> Murajaah
                                    </h4>
                                    <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full font-semibold tabular-nums">{{ count($setorans['Murajaah']) }} surat</span>
                                </div>
                                <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                                    <table class="min-w-full">
                                        <thead class="table-thead">
                                            <tr>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">No</th>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Surah & Ayat</th>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">Jml</th>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-14">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody id="rtbl_murajaah" class="divide-y divide-gray-100 dark:divide-white/5">
                                            @php $no = 1; @endphp
                                            @foreach($setorans['Murajaah'] as $suratKey => $data)
                                            <tr class="rpgrow-murajaah table-row">
                                                <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 tabular-nums">{{ $no++ }}</td>
                                                <td class="px-2.5 sm:px-3 py-2.5">
                                                    <p class="text-[10px] sm:text-xs text-gray-900 dark:text-white font-semibold leading-tight">{{ $data['surat_start'] }}@if($data['surat_start'] != $data['surat_end']) — {{ $data['surat_end'] }}@endif</p>
                                                    <p class="text-[9px] sm:text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Ayat {{ $data['ayat_start_min'] }}–{{ $data['ayat_end_max'] }}</p>
                                                </td>
                                                <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                    <span class="nilai-badge count-badge px-1.5 sm:px-2 py-0.5 text-[10px]">{{ $data['jumlah_setoran'] }}×</span>
                                                </td>
                                                <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                    <span class="nilai-badge px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs
                                                        @if($data['rata_nilai'] >= 90) nilai-tinggi
                                                        @elseif($data['rata_nilai'] >= 75) nilai-sedang
                                                        @else nilai-rendah @endif">
                                                        {{ $data['rata_nilai'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div id="rpg_murajaah" class="px-3 sm:px-4 py-2.5 border-t border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/[0.02]"></div>
                            </div>
                            @else
                            <div class="sect-panel p-8 flex items-center justify-center min-h-[100px]">
                                <div class="text-center text-gray-400 dark:text-gray-600">
                                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm">Tidak ada data murajaah</p>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- TILAWAH --}}
                        <div id="panel-tilawah" class="tab-panel hidden md:block">
                            @if(isset($setorans['Tilawah']) && count($setorans['Tilawah']) > 0)
                            <div class="sect-panel">
                                <div class="sect-header-tilawah text-white px-4 py-3 flex items-center justify-between">
                                    <h4 class="font-bold text-sm flex items-center gap-2">
                                        <span class="text-base">📖</span> Tilawah
                                    </h4>
                                    <span class="text-[10px] sm:text-xs bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full font-semibold tabular-nums">{{ count($setorans['Tilawah']) }} surat</span>
                                </div>
                                <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                                    <table class="min-w-full">
                                        <thead class="table-thead">
                                            <tr>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">NO</th>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Surah & Ayat</th>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">Jml</th>
                                                <th class="px-2.5 sm:px-3 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-14">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody id="rtbl_tilawah" class="divide-y divide-gray-100 dark:divide-white/5">
                                            @php $no = 1; @endphp
                                            @foreach($setorans['Tilawah'] as $suratKey => $data)
                                            <tr class="rpgrow-tilawah table-row">
                                                <td class="px-2.5 sm:px-3 py-2.5 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 tabular-nums">{{ $no++ }}</td>
                                                <td class="px-2.5 sm:px-3 py-2.5">
                                                    <p class="text-[10px] sm:text-xs text-gray-900 dark:text-white font-semibold leading-tight">{{ $data['surat_start'] }}@if($data['surat_start'] != $data['surat_end']) — {{ $data['surat_end'] }}@endif</p>
                                                    <p class="text-[9px] sm:text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Ayat {{ $data['ayat_start_min'] }}–{{ $data['ayat_end_max'] }}</p>
                                                </td>
                                                <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                    <span class="nilai-badge count-badge px-1.5 sm:px-2 py-0.5 text-[10px]">{{ $data['jumlah_setoran'] }}×</span>
                                                </td>
                                                <td class="px-2.5 sm:px-3 py-2.5 text-center">
                                                    <span class="nilai-badge px-1.5 sm:px-2 py-0.5 sm:py-1 text-[10px] sm:text-xs
                                                        @if($data['rata_nilai'] >= 90) nilai-tinggi
                                                        @elseif($data['rata_nilai'] >= 75) nilai-sedang
                                                        @else nilai-rendah @endif">
                                                        {{ $data['rata_nilai'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div id="rpg_tilawah" class="px-3 sm:px-4 py-2.5 border-t border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/[0.02]"></div>
                            </div>
                            @else
                            <div class="sect-panel p-8 flex items-center justify-center min-h-[100px]">
                                <div class="text-center text-gray-400 dark:text-gray-600">
                                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm">Tidak ada data tilawah</p>
                                </div>
                            </div>
                            @endif
                        </div>

                    </div>{{-- END KOLOM KANAN --}}
                </div>{{-- END GRID KATEGORI --}}
            </div>

            @else
            <div class="text-center py-12 text-gray-400 dark:text-gray-600 px-4">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 flex items-center justify-center">
                    <svg class="w-7 h-7 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-500">Tidak ada data setoran dalam periode ini</p>
            </div>
            @endif
        </div>{{-- END RIWAYAT SETORAN --}}

        <div class="h-4 sm:h-2"></div>
    </div>{{-- END MAIN CONTENT --}}
</div>{{-- END PAGE --}}

{{-- ============================================================ --}}
{{-- PDF PREVIEW MODAL                                             --}}
{{-- ============================================================ --}}
<div id="pdfPreviewModal" class="hidden fixed inset-0 bg-black/60 dark:bg-black/85 backdrop-blur-sm z-[200] overflow-y-auto">
    <div class="min-h-screen flex flex-col md:block md:px-4 md:py-6">
        <div class="relative glass-card md:max-w-6xl md:mx-auto flex flex-col flex-1 md:flex-none shadow-2xl">
            <div class="flex items-center justify-between px-4 sm:px-5 py-3.5 sm:py-4 border-b border-gray-100 dark:border-white/5 flex-shrink-0">
                <h3 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-7 h-7 bg-red-50 dark:bg-red-500/15 border border-red-200 dark:border-red-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </span>
                    Preview Rapot PDF
                </h3>
                <div class="flex items-center gap-2">
                    <a href="{{ route('santri.rapot.pdf', ['tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalAkhir]) }}"
                       class="touch-btn inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/15 dark:hover:bg-emerald-500/25 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-300 rounded-lg transition text-xs font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                    <button onclick="closePDFPreview()"
                            class="touch-btn p-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 p-3 sm:p-4">
                <iframe id="pdfFrame"
                        src="{{ route('santri.rapot.pdf', ['tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalAkhir, 'preview' => 1]) }}"
                        class="w-full rounded-lg border border-gray-200 dark:border-white/5 bg-white"
                        style="height: calc(100vh - 130px); min-height: 300px;">
                </iframe>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- JAVASCRIPT                                                    --}}
{{-- ============================================================ --}}
<script>
function toggleMobileFilter() {
    document.getElementById('mobileFilterPanel')?.classList.toggle('hidden');
}

const TAB_CONFIG = {
    hafalan:  { color: 'tab-active-green',  panel: 'panel-hafalan'  },
    murajaah: { color: 'tab-active-blue',   panel: 'panel-murajaah' },
    tilawah:  { color: 'tab-active-purple', panel: 'panel-tilawah'  },
};
let activeTab = 'hafalan';

function switchTab(tab) {
    if (window.innerWidth >= 768) return;
    activeTab = tab;
    Object.keys(TAB_CONFIG).forEach(key => {
        const btn = document.getElementById('tab-' + key);
        if (!btn) return;
        btn.classList.remove('tab-active-green','tab-active-blue','tab-active-purple','text-white','tab-btn-inactive');
        if (key === tab) { btn.classList.add(TAB_CONFIG[key].color, 'text-white'); }
        else { btn.classList.add('tab-btn-inactive'); }
    });
    Object.keys(TAB_CONFIG).forEach(key => {
        const panel = document.getElementById(TAB_CONFIG[key].panel);
        if (!panel) return;
        if (key === tab) { panel.classList.remove('hidden'); }
        else { panel.classList.add('hidden'); if (key !== 'hafalan') panel.classList.add('md:block'); }
    });
}

const pgState = {};
function initPagination(tbodyId, rowClass, pgId, perPage) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('.' + rowClass));
    const totalRows = rows.length, totalPages = Math.ceil(totalRows / perPage);
    pgState[tbodyId] = { rows, totalRows, totalPages, perPage, currentPage: 1 };
    if (totalPages <= 1) return;
    showPage(tbodyId, pgId, 1);
}
function showPage(tbodyId, pgId, page) {
    const s = pgState[tbodyId];
    if (!s || page < 1 || page > s.totalPages) return;
    s.currentPage = page;
    const start = (page - 1) * s.perPage, end = start + s.perPage;
    s.rows.forEach((row, i) => { row.style.display = (i >= start && i < end) ? '' : 'none'; });
    renderPagination(tbodyId, pgId);
}
function renderPagination(tbodyId, pgId) {
    const s = pgState[tbodyId], container = document.getElementById(pgId);
    if (!s || !container) return;
    const { currentPage, totalPages, totalRows, perPage } = s;
    const from = Math.min((currentPage-1)*perPage+1, totalRows), to = Math.min(currentPage*perPage, totalRows);
    const activeBtn = 'px-2.5 py-1 text-xs rounded-md font-semibold bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-900/30';
    const normalBtn = 'px-2.5 py-1 text-xs rounded-md font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white cursor-pointer transition';
    const disabledBtn = 'px-2.5 py-1 text-xs rounded-md text-gray-300 dark:text-gray-600 cursor-not-allowed';
    let html = `<div class="flex items-center justify-between gap-2 flex-wrap"><span class="text-[11px] text-gray-400 dark:text-gray-500 whitespace-nowrap tabular-nums">${from}–${to} dari ${totalRows}</span><div class="flex items-center gap-1 flex-wrap">`;
    if (currentPage===1) html+=`<button disabled class="${disabledBtn}">‹</button>`;
    else html+=`<button onclick="showPage('${tbodyId}','${pgId}',${currentPage-1})" class="${normalBtn}">‹</button>`;
    let startP=Math.max(1,currentPage-2), endP=Math.min(totalPages,startP+4);
    if (endP-startP<4) startP=Math.max(1,endP-4);
    if (startP>1) { html+=`<button onclick="showPage('${tbodyId}','${pgId}',1)" class="${normalBtn}">1</button>`; if(startP>2) html+=`<span class="text-xs text-gray-400 dark:text-gray-600">…</span>`; }
    for (let i=startP;i<=endP;i++) html+=`<button onclick="showPage('${tbodyId}','${pgId}',${i})" class="${i===currentPage?activeBtn:normalBtn}">${i}</button>`;
    if (endP<totalPages) { if(endP<totalPages-1) html+=`<span class="text-xs text-gray-400 dark:text-gray-600">…</span>`; html+=`<button onclick="showPage('${tbodyId}','${pgId}',${totalPages})" class="${normalBtn}">${totalPages}</button>`; }
    if (currentPage===totalPages) html+=`<button disabled class="${disabledBtn}">›</button>`;
    else html+=`<button onclick="showPage('${tbodyId}','${pgId}',${currentPage+1})" class="${normalBtn}">›</button>`;
    html+=`</div></div>`;
    container.innerHTML = html;
}

function previewPDF() { document.getElementById('pdfPreviewModal').classList.remove('hidden'); document.body.style.overflow='hidden'; }
function closePDFPreview() { document.getElementById('pdfPreviewModal').classList.add('hidden'); document.body.style.overflow=''; }

document.addEventListener('DOMContentLoaded', function () {
    initPagination('rtbl_hafalan','rpgrow-hafalan','rpg_hafalan',15);
    initPagination('rtbl_murajaah','rpgrow-murajaah','rpg_murajaah',6);
    initPagination('rtbl_tilawah','rpgrow-tilawah','rpg_tilawah',6);
    if (window.innerWidth < 768) {
        ['panel-murajaah','panel-tilawah'].forEach(id => document.getElementById(id)?.classList.add('hidden'));
    }
    document.getElementById('pdfPreviewModal')?.addEventListener('click', function(e) { if(e.target===this) closePDFPreview(); });
    document.addEventListener('keydown', e => { if(e.key==='Escape') closePDFPreview(); });
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            ['panel-hafalan','panel-murajaah','panel-tilawah'].forEach(id => document.getElementById(id)?.classList.remove('hidden'));
        } else { switchTab(activeTab); }
    });
});
</script>

@endsection
@extends('layouts.app')

@section('content')
<style>
    .menu-card {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        padding: 18px 16px 16px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    .menu-card:hover {
        transform: translateY(-3px);
    }
    .menu-card:active { transform: translateY(0) scale(0.98); }

    /* Glow blob background per card */
    .menu-card::before {
        content: '';
        position: absolute;
        width: 90px; height: 90px;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.25;
        top: -20px; right: -20px;
        transition: opacity 0.3s ease, transform 0.3s ease;
        pointer-events: none;
    }
    .menu-card:hover::before { opacity: 0.45; transform: scale(1.2); }

    .menu-card .card-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    .menu-card:hover .card-icon { transform: scale(1.08); }

    .menu-card .card-label {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }
    .menu-card .card-sub {
        font-size: 11px;
        margin: 0;
        opacity: 0.55;
        line-height: 1.3;
    }
    .menu-card .card-arrow {
        position: absolute;
        bottom: 14px; right: 14px;
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .menu-card:hover .card-arrow { opacity: 1; transform: translateX(0); }

    /* Color variants */
    .card-yellow  { background: linear-gradient(135deg, #1c1400 0%, #221a00 100%); border: 1px solid rgba(202,138,4,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-yellow::before  { background: #facc15; }
    .card-yellow .card-icon  { background: rgba(202,138,4,0.15); border: 1px solid rgba(202,138,4,0.25); }
    .card-yellow .card-label { color: #fde68a; }
    .card-yellow .card-sub   { color: #ca8a04; }
    .card-yellow .card-arrow svg { color: #facc15; }

    .card-indigo  { background: linear-gradient(135deg, #0c0e1f 0%, #11133a 100%); border: 1px solid rgba(99,102,241,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-indigo::before  { background: #6366f1; }
    .card-indigo .card-icon  { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25); }
    .card-indigo .card-label { color: #c7d2fe; }
    .card-indigo .card-sub   { color: #6366f1; }
    .card-indigo .card-arrow svg { color: #818cf8; }

    .card-blue    { background: linear-gradient(135deg, #071220 0%, #0c1a2e 100%); border: 1px solid rgba(59,130,246,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-blue::before    { background: #3b82f6; }
    .card-blue .card-icon    { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.22); }
    .card-blue .card-label   { color: #bfdbfe; }
    .card-blue .card-sub     { color: #3b82f6; }
    .card-blue .card-arrow svg { color: #60a5fa; }

    .card-emerald { background: linear-gradient(135deg, #041a10 0%, #062015 100%); border: 1px solid rgba(16,185,129,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-emerald::before { background: #10b981; }
    .card-emerald .card-icon { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.22); }
    .card-emerald .card-label{ color: #a7f3d0; }
    .card-emerald .card-sub  { color: #10b981; }
    .card-emerald .card-arrow svg { color: #34d399; }

    .card-violet  { background: linear-gradient(135deg, #100b20 0%, #180f2e 100%); border: 1px solid rgba(139,92,246,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-violet::before  { background: #8b5cf6; }
    .card-violet .card-icon  { background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.22); }
    .card-violet .card-label { color: #ddd6fe; }
    .card-violet .card-sub   { color: #8b5cf6; }
    .card-violet .card-arrow svg { color: #a78bfa; }

    .card-purple  { background: linear-gradient(135deg, #130b1e 0%, #1c0f2c 100%); border: 1px solid rgba(168,85,247,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-purple::before  { background: #a855f7; }
    .card-purple .card-icon  { background: rgba(168,85,247,0.12); border: 1px solid rgba(168,85,247,0.22); }
    .card-purple .card-label { color: #e9d5ff; }
    .card-purple .card-sub   { color: #a855f7; }
    .card-purple .card-arrow svg { color: #c084fc; }

    .card-orange  { background: linear-gradient(135deg, #1a0d04 0%, #221208 100%); border: 1px solid rgba(249,115,22,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-orange::before  { background: #f97316; }
    .card-orange .card-icon  { background: rgba(249,115,22,0.12); border: 1px solid rgba(249,115,22,0.22); }
    .card-orange .card-label { color: #fed7aa; }
    .card-orange .card-sub   { color: #f97316; }
    .card-orange .card-arrow svg { color: #fb923c; }

    .card-pink    { background: linear-gradient(135deg, #1a0614 0%, #220a1c 100%); border: 1px solid rgba(236,72,153,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-pink::before    { background: #ec4899; }
    .card-pink .card-icon    { background: rgba(236,72,153,0.12); border: 1px solid rgba(236,72,153,0.22); }
    .card-pink .card-label   { color: #fbcfe8; }
    .card-pink .card-sub     { color: #ec4899; }
    .card-pink .card-arrow svg { color: #f472b6; }

    /* Stats card */
    .stat-card {
        background: #111318;
        border: 1px solid #1f2229;
        border-radius: 14px;
        padding: 16px;
        position: relative;
        overflow: hidden;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(135deg, rgba(255,255,255,0.02) 0%, transparent 60%);
        pointer-events: none;
    }

    /* Section title */
    .section-title {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #374151;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(to right, #1f2937, transparent);
    }

    /* Animate in */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .menu-card { animation: fadeUp 0.35s ease both; }
    .menu-card:nth-child(1) { animation-delay: 0.05s; }
    .menu-card:nth-child(2) { animation-delay: 0.10s; }
    .menu-card:nth-child(3) { animation-delay: 0.15s; }
    .menu-card:nth-child(4) { animation-delay: 0.20s; }
    .menu-card:nth-child(5) { animation-delay: 0.25s; }
    .menu-card:nth-child(6) { animation-delay: 0.30s; }
    .menu-card:nth-child(7) { animation-delay: 0.35s; }
    .menu-card:nth-child(8) { animation-delay: 0.40s; }
</style>

<div class="py-3 sm:py-6 lg:py-8">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        {{-- Header --}}
        <div class="mb-5 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-white">
                Selamat Datang, <span class="text-indigo-400">{{ auth()->user()->name }}</span>
            </h1>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                @can('kelola-admin')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-950 border border-yellow-700/50 text-yellow-400">
                        👑 Super Admin
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-950 border border-blue-700/50 text-blue-400">
                        ⚡ Admin
                    </span>
                @endcan
                @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <a href="{{ route('admin.tahun-ajaran.index') }}"
                   class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950 border border-emerald-700/50 text-emerald-400 hover:bg-emerald-900/40 transition-colors">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse inline-block"></span>
                    {{ $tahunAjaranAktif->label_lengkap }}
                </a>
                @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-950 border border-amber-700/50 text-amber-400">
                    ⚠️ Belum ada semester aktif
                </span>
                @endif
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-2 gap-2 sm:gap-3 mb-5 sm:mb-6 @can('kelola-admin') lg:grid-cols-4 @else lg:grid-cols-3 @endcan">

            @can('kelola-admin')
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] text-yellow-600 font-semibold uppercase tracking-wide mb-1">Total Admin</p>
                        <p class="text-3xl font-black text-yellow-300 leading-none mb-1">{{ $totalAdmins ?? 0 }}</p>
                        <p class="text-[10px] text-gray-600 hidden sm:block">
                            👑 {{ $totalSuperAdmins ?? 0 }} Super &middot; ⚡ {{ $totalRegularAdmins ?? 0 }} Biasa
                        </p>
                    </div>
                    <div class="w-9 h-9 bg-yellow-900/30 border border-yellow-700/20 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            @endcan

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] text-blue-600 font-semibold uppercase tracking-wide mb-1">Total Guru</p>
                        <p class="text-3xl font-black text-white leading-none">{{ $totalGurus ?? 0 }}</p>
                    </div>
                    <div class="w-9 h-9 bg-blue-900/20 border border-blue-800/20 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] text-emerald-600 font-semibold uppercase tracking-wide mb-1">Total Kelas</p>
                        <p class="text-3xl font-black text-white leading-none">{{ $totalKelas ?? 0 }}</p>
                    </div>
                    <div class="w-9 h-9 bg-emerald-900/20 border border-emerald-800/20 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] text-indigo-600 font-semibold uppercase tracking-wide mb-1">Total Santri</p>
                        <p class="text-3xl font-black text-white leading-none">{{ $totalSantris ?? 0 }}</p>
                    </div>
                    <div class="w-9 h-9 bg-indigo-900/20 border border-indigo-800/20 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menu Utama --}}
        <div class="mb-5">
            <p class="section-title">Menu Utama</p>

            @can('kelola-admin')
            {{-- SUPER ADMIN --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-3">

                {{-- Kelola Admin --}}
                <a href="{{ route('admin.admins.index') }}" class="menu-card card-yellow">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Admin</p>
                        <p class="card-sub">Management admin</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

                {{-- Tahun Ajaran --}}
                <a href="{{ route('admin.tahun-ajaran.index') }}" class="menu-card card-indigo">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Tahun Ajaran</p>
                        <p class="card-sub">
                            @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                                {{ $tahunAjaranAktif->label_pendek }}
                            @else
                                Belum ada aktif
                            @endif
                        </p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

                @can('kelola-guru')
                <a href="{{ route('admin.gurus.index') }}" class="menu-card card-blue">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Guru</p>
                        <p class="card-sub">Management guru</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
                @endcan

                @can('kelola-kelas')
                <a href="{{ route('admin.kelas.index') }}" class="menu-card card-emerald">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Kelas</p>
                        <p class="card-sub">Management kelas</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
                @endcan
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-5">

                @can('kelola-santri')
                <a href="{{ route('admin.santris.index') }}" class="menu-card card-violet">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Santri</p>
                        <p class="card-sub">Managemnt santri</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
                @endcan

                @can('kelola-presensi-sesi')
                <a href="{{ route('admin.presensi-sesis.index') }}" class="menu-card card-purple">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Sesi Presensi</p>
                        <p class="card-sub">Jadwal presensi</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
                @endcan

                @can('lihat-monitoring')
                <a href="{{ route('admin.monitoring.index') }}" class="menu-card card-orange">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Monitoring</p>
                        <p class="card-sub">Setoran & presensi</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
                @endcan

                @can('lihat-log-activity')
                <a href="{{ route('admin.logs.index') }}" class="menu-card card-pink">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Log Activity</p>
                        <p class="card-sub">Aktivitas sistem</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
                @endcan
            </div>

            @else
            {{-- ADMIN BIASA --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                @can('kelola-guru')
                <a href="{{ route('admin.gurus.index') }}" class="menu-card card-blue">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Guru</p>
                        <p class="card-sub">Management guru</p>
                    </div>
                    <span class="card-arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></span>
                </a>
                @endcan

                @can('kelola-kelas')
                <a href="{{ route('admin.kelas.index') }}" class="menu-card card-emerald">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Kelas</p>
                        <p class="card-sub">Management kelas</p>
                    </div>
                    <span class="card-arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></span>
                </a>
                @endcan

                @can('kelola-santri')
                <a href="{{ route('admin.santris.index') }}" class="menu-card card-violet">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Santri</p>
                        <p class="card-sub">Management santri</p>
                    </div>
                    <span class="card-arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></span>
                </a>
                @endcan
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
                @can('kelola-presensi-sesi')
                <a href="{{ route('admin.presensi-sesis.index') }}" class="menu-card card-purple">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Sesi Presensi</p>
                        <p class="card-sub">Jadwal presensi</p>
                    </div>
                    <span class="card-arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></span>
                </a>
                @endcan

                @can('lihat-monitoring')
                <a href="{{ route('admin.monitoring.index') }}" class="menu-card card-orange">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Monitoring</p>
                        <p class="card-sub">Setoran & presensi</p>
                    </div>
                    <span class="card-arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></span>
                </a>
                @endcan

                @can('lihat-log-activity')
                <a href="{{ route('admin.logs.index') }}" class="menu-card card-pink">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Log Activity</p>
                        <p class="card-sub">Aktivitas sistem</p>
                    </div>
                    <span class="card-arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></span>
                </a>
                @endcan
            </div>
            @endcan
        </div>

        {{-- Panduan Penggunaan --}}
        <div class="bg-gray-900/60 border border-gray-800/60 rounded-xl p-3 sm:p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    Panduan Penggunaan
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-950 border border-indigo-800/50 text-indigo-400 rounded-full">6 langkah</span>
                </h2>
                <button onclick="toggleGuide()" class="text-[10px] sm:text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1 transition-colors">
                    <span id="guide-toggle-text">Sembunyikan</span>
                    <svg id="guide-toggle-icon" class="w-3 h-3 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <div id="user-guide" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                @foreach([
                    ['1', 'Atur Tahun Ajaran', 'Buat periode semester terlebih dahulu. Aktifkan satu semester untuk mulai input data presensi dan setoran.', 'text-indigo-400', 'bg-indigo-950 border-indigo-800/50'],
                    ['2', 'Kelola Data Master', 'Tambahkan data guru, kelas, dan santri. Pastikan semua data lengkap sebelum mulai monitoring.', 'text-blue-400', 'bg-blue-950 border-blue-800/50'],
                    ['3', 'Atur Sesi Presensi', 'Buat jadwal waktu presensi sesuai kebutuhan. Anda dapat menambah, edit, atau nonaktifkan sesi kapan saja.', 'text-purple-400', 'bg-purple-950 border-purple-800/50'],
                    ['4', 'Monitoring Real-time', 'Pantau progress setoran santri melalui dashboard monitoring dengan statistik lengkap per semester.', 'text-orange-400', 'bg-orange-950 border-orange-800/50'],
                    ['5', 'Tutup Semester', 'Di akhir semester, gunakan fitur Tutup Semester untuk mengarsipkan data dan mengaktifkan semester berikutnya.', 'text-emerald-400', 'bg-emerald-950 border-emerald-800/50'],
                    ['6', 'Audit Trail', 'Gunakan Log Activity untuk melacak semua aktivitas pengguna. Filter berdasarkan role, modul, dan waktu.', 'text-pink-400', 'bg-pink-950 border-pink-800/50'],
                ] as $guide)
                <div class="flex items-start gap-2.5 p-2.5 rounded-lg bg-gray-800/50 border border-gray-800">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 {{ $guide[4] }} border rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="text-[11px] font-bold {{ $guide[3] }}">{{ $guide[0] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-semibold text-gray-200 mb-0.5">{{ $guide[1] }}</h4>
                        <p class="text-[10px] text-gray-500 leading-relaxed">{{ $guide[2] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

<script>
    function toggleGuide() {
        const guide = document.getElementById('user-guide');
        const text  = document.getElementById('guide-toggle-text');
        const icon  = document.getElementById('guide-toggle-icon');
        if (guide.classList.contains('hidden')) {
            guide.classList.remove('hidden');
            guide.classList.add('grid');
            text.innerText = 'Sembunyikan';
            icon.style.transform = 'rotate(0deg)';
        } else {
            guide.classList.remove('grid');
            guide.classList.add('hidden');
            text.innerText = 'Tampilkan';
            icon.style.transform = 'rotate(-90deg)';
        }
    }
    if (window.innerWidth < 640) { toggleGuide(); }
</script>
@endsection
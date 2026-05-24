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
    .menu-card:hover { transform: translateY(-3px); }
    .menu-card:active { transform: translateY(0) scale(0.98); }

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
    .card-indigo  { background: linear-gradient(135deg, #0c0e1f 0%, #11133a 100%); border: 1px solid rgba(99,102,241,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-indigo::before  { background: #6366f1; }
    .card-indigo .card-icon  { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25); }
    .card-indigo .card-label { color: #c7d2fe; }
    .card-indigo .card-sub   { color: #6366f1; }
    .card-indigo .card-arrow svg { color: #818cf8; }

    .card-emerald { background: linear-gradient(135deg, #041a10 0%, #062015 100%); border: 1px solid rgba(16,185,129,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-emerald::before { background: #10b981; }
    .card-emerald .card-icon { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.22); }
    .card-emerald .card-label{ color: #a7f3d0; }
    .card-emerald .card-sub  { color: #10b981; }
    .card-emerald .card-arrow svg { color: #34d399; }

    .card-orange  { background: linear-gradient(135deg, #1a0d04 0%, #221208 100%); border: 1px solid rgba(249,115,22,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-orange::before  { background: #f97316; }
    .card-orange .card-icon  { background: rgba(249,115,22,0.12); border: 1px solid rgba(249,115,22,0.22); }
    .card-orange .card-label { color: #fed7aa; }
    .card-orange .card-sub   { color: #f97316; }
    .card-orange .card-arrow svg { color: #fb923c; }

    .card-amber   { background: linear-gradient(135deg, #1c1400 0%, #221a00 100%); border: 1px solid rgba(202,138,4,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-amber::before   { background: #f59e0b; }
    .card-amber .card-icon   { background: rgba(202,138,4,0.15); border: 1px solid rgba(202,138,4,0.25); }
    .card-amber .card-label  { color: #fde68a; }
    .card-amber .card-sub    { color: #ca8a04; }
    .card-amber .card-arrow svg { color: #facc15; }

    .card-blue    { background: linear-gradient(135deg, #071220 0%, #0c1a2e 100%); border: 1px solid rgba(59,130,246,0.25); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    .card-blue::before    { background: #3b82f6; }
    .card-blue .card-icon    { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.22); }
    .card-blue .card-label   { color: #bfdbfe; }
    .card-blue .card-sub     { color: #3b82f6; }
    .card-blue .card-arrow svg { color: #60a5fa; }

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

    /* Mobile: Rapot Santri full width — row direction */
    @media (max-width: 639px) {
        .card-orange-mobile-full {
            flex-direction: row !important;
            align-items: center !important;
            padding: 14px 16px !important;
            gap: 14px !important;
        }
        .card-orange-mobile-full .card-arrow {
            position: static !important;
            opacity: 1 !important;
            transform: none !important;
            margin-left: auto;
        }
    }
</style>

<div class="min-h-screen bg-gray-950 py-3 sm:py-6 lg:py-8">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        {{-- Header --}}
        <div class="mb-5 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-white">
                Ahlan Wa Sahlan, <span class="text-indigo-400">{{ Auth::user()->name }}</span>
            </h1>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-950 border border-indigo-700/50 text-indigo-400">
                    🎓 Guru Tahfizh
                </span>
                @if($kelas)
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950 border border-emerald-700/50 text-emerald-400">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse inline-block"></span>
                    Wali Kelas {{ $kelas->nama }}
                </span>
                @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-950 border border-amber-700/50 text-amber-400">
                    ⚠️ Belum ditugaskan sebagai wali kelas
                </span>
                @endif
            </div>
        </div>

        {{-- Menu Utama --}}
        <div class="mb-5">
            <p class="section-title">Menu Utama</p>

            {{-- Baris 1: Catat Setoran + Kelola Santri + Rapot Santri --}}
            {{-- Mobile: 2 kolom, Rapot Santri col-span-2 (full width) --}}
            {{-- Desktop sm+: 3 kolom, semua 1 kolom --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">

                {{-- Catat Setoran --}}
                <a href="{{ route('guru.input.create') }}" class="menu-card card-indigo">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Catat Setoran</p>
                        <p class="card-sub">Input hafalan & presensi</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

                {{-- Kelola Santri --}}
                <a href="{{ route('guru.santris.index') }}" class="menu-card card-emerald">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Kelola Santri</p>
                        <p class="card-sub">Tambah, edit, hapus</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

                {{-- Rapot Santri: col-span-2 di mobile = full width, col-span-1 di sm+ = 1/3 --}}
                <a href="{{ route('guru.rapot.index') }}" class="menu-card card-orange card-orange-mobile-full col-span-2 sm:col-span-1">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Rapot Santri</p>
                        <p class="card-sub">Lihat & cetak laporan</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

            </div>

            {{-- Baris 2: Riwayat Setoran + Riwayat Presensi --}}
            <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 mb-5">

                {{-- Riwayat Setoran --}}
                <a href="{{ route('guru.setorans.index') }}" class="menu-card card-blue">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Riwayat Setoran</p>
                        <p class="card-sub">Rekap data hafalan</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

                {{-- Riwayat Presensi --}}
                <a href="{{ route('guru.presensis.index') }}" class="menu-card card-amber">
                    <div class="card-icon">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="card-label">Riwayat Presensi</p>
                        <p class="card-sub">Rekap kehadiran</p>
                    </div>
                    <span class="card-arrow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

            </div>
        </div>

        {{-- Daftar Santri --}}
        @if($santriList && $santriList->count() > 0)
        <div class="bg-gray-900/60 border border-gray-800/60 rounded-xl p-3 sm:p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    Daftar Santri
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-950 border border-indigo-800/50 text-indigo-400 rounded-full">
                        {{ $santriList->count() }}
                    </span>
                </h2>
            </div>

            {{-- Tabel: tablet+ --}}
            <div class="hidden sm:block overflow-x-auto rounded-lg border border-gray-800">
                <table class="min-w-full">
                    <thead class="bg-gray-800/60">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider w-8">No</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">NIS</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-10">L/P</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider min-w-[160px]">Progress Hafalan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach($santriList as $index => $santri)
                        @php
                            $persen    = $santri->hafalan_persen ?? 0;
                            $totalJuz  = $santri->hafalan_total_juz ?? 0;
                            $targetJuz = $santri->target_juz > 0 ? $santri->target_juz : 30;
                            $barColor  = match(true) {
                                $persen >= 80 => 'bg-emerald-500',
                                $persen >= 50 => 'bg-blue-500',
                                $persen >= 25 => 'bg-amber-500',
                                default       => 'bg-red-500',
                            };
                            $textColor = match(true) {
                                $persen >= 80 => 'text-emerald-400',
                                $persen >= 50 => 'text-blue-400',
                                $persen >= 25 => 'text-amber-400',
                                default       => 'text-red-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-800/30 transition-colors">
                            <td class="px-3 py-2.5 text-xs text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-200">{{ $santri->name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-gray-500 font-mono">{{ $santri->nis ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-xs text-center text-gray-500">{{ $santri->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-950 border border-emerald-800/50 text-emerald-400 font-medium">
                                    {{ number_format($santri->target_juz, 1) }} Juz
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-800 rounded-full h-1.5">
                                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($persen, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $textColor }} whitespace-nowrap">{{ $persen }}%</span>
                                    <span class="text-[10px] text-gray-600 whitespace-nowrap hidden lg:inline">({{ $totalJuz }}/{{ $targetJuz }})</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Card: mobile --}}
            <div class="sm:hidden space-y-1.5">
                @foreach($santriList as $index => $santri)
                @php
                    $persen    = $santri->hafalan_persen ?? 0;
                    $totalJuz  = $santri->hafalan_total_juz ?? 0;
                    $targetJuz = $santri->target_juz > 0 ? $santri->target_juz : 30;
                    $barColor  = match(true) { $persen >= 80 => 'bg-emerald-500', $persen >= 50 => 'bg-blue-500', $persen >= 25 => 'bg-amber-500', default => 'bg-red-500' };
                    $badgeClass = match(true) { $persen >= 80 => 'bg-emerald-950 border-emerald-800/50 text-emerald-400', $persen >= 50 => 'bg-blue-950 border-blue-800/50 text-blue-400', $persen >= 25 => 'bg-amber-950 border-amber-800/50 text-amber-400', default => 'bg-red-950 border-red-800/50 text-red-400' };
                @endphp
                <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-gray-800/50 border border-gray-800">
                    <span class="text-[10px] text-gray-600 w-4 text-center flex-shrink-0">{{ $index + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-200 truncate">{{ $santri->name }}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <div class="flex-1 bg-gray-700 rounded-full h-1">
                                <div class="{{ $barColor }} h-1 rounded-full" style="width: {{ min($persen, 100) }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ $totalJuz }}/{{ $targetJuz }} juz</span>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg border {{ $badgeClass }} flex-shrink-0">{{ $persen }}%</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
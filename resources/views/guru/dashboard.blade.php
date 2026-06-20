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
        opacity: 0.75;
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

    /* ====================================================== */
    /* COLOR VARIANTS — LIGHT MODE (default)                  */
    /* ====================================================== */
    .card-indigo  { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border: 1px solid rgba(99,102,241,0.25); box-shadow: 0 4px 14px rgba(99,102,241,0.10); }
    .card-indigo::before  { background: #6366f1; }
    .card-indigo .card-icon  { background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); }
    .card-indigo .card-icon svg { color: #4f46e5; }
    .card-indigo .card-label { color: #3730a3; }
    .card-indigo .card-sub   { color: #6366f1; }
    .card-indigo .card-arrow svg { color: #6366f1; }

    .card-emerald { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid rgba(16,185,129,0.25); box-shadow: 0 4px 14px rgba(16,185,129,0.10); }
    .card-emerald::before { background: #10b981; }
    .card-emerald .card-icon { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.22); }
    .card-emerald .card-icon svg { color: #059669; }
    .card-emerald .card-label{ color: #065f46; }
    .card-emerald .card-sub  { color: #10b981; }
    .card-emerald .card-arrow svg { color: #10b981; }

    .card-orange  { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1px solid rgba(249,115,22,0.25); box-shadow: 0 4px 14px rgba(249,115,22,0.10); }
    .card-orange::before  { background: #f97316; }
    .card-orange .card-icon  { background: rgba(249,115,22,0.12); border: 1px solid rgba(249,115,22,0.22); }
    .card-orange .card-icon svg { color: #ea580c; }
    .card-orange .card-label { color: #9a3412; }
    .card-orange .card-sub   { color: #f97316; }
    .card-orange .card-arrow svg { color: #f97316; }

    .card-amber   { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid rgba(202,138,4,0.25); box-shadow: 0 4px 14px rgba(202,138,4,0.10); }
    .card-amber::before   { background: #f59e0b; }
    .card-amber .card-icon   { background: rgba(202,138,4,0.12); border: 1px solid rgba(202,138,4,0.25); }
    .card-amber .card-icon svg { color: #d97706; }
    .card-amber .card-label  { color: #92400e; }
    .card-amber .card-sub    { color: #ca8a04; }
    .card-amber .card-arrow svg { color: #ca8a04; }

    .card-blue    { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid rgba(59,130,246,0.25); box-shadow: 0 4px 14px rgba(59,130,246,0.10); }
    .card-blue::before    { background: #3b82f6; }
    .card-blue .card-icon    { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.22); }
    .card-blue .card-icon svg { color: #2563eb; }
    .card-blue .card-label   { color: #1e40af; }
    .card-blue .card-sub     { color: #3b82f6; }
    .card-blue .card-arrow svg { color: #3b82f6; }

    /* ====================================================== */
    /* COLOR VARIANTS — DARK MODE                             */
    /* ====================================================== */
    html.dark .card-indigo  { background: linear-gradient(135deg, #0c0e1f 0%, #11133a 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    html.dark .card-indigo .card-icon  { background: rgba(99,102,241,0.15); }
    html.dark .card-indigo .card-icon svg { color: #818cf8; }
    html.dark .card-indigo .card-label { color: #c7d2fe; }
    html.dark .card-indigo .card-sub   { color: #6366f1; }
    html.dark .card-indigo .card-arrow svg { color: #818cf8; }

    html.dark .card-emerald { background: linear-gradient(135deg, #041a10 0%, #062015 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    html.dark .card-emerald .card-icon { background: rgba(16,185,129,0.12); }
    html.dark .card-emerald .card-icon svg { color: #34d399; }
    html.dark .card-emerald .card-label{ color: #a7f3d0; }
    html.dark .card-emerald .card-sub  { color: #10b981; }
    html.dark .card-emerald .card-arrow svg { color: #34d399; }

    html.dark .card-orange  { background: linear-gradient(135deg, #1a0d04 0%, #221208 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    html.dark .card-orange .card-icon  { background: rgba(249,115,22,0.12); }
    html.dark .card-orange .card-icon svg { color: #fb923c; }
    html.dark .card-orange .card-label { color: #fed7aa; }
    html.dark .card-orange .card-sub   { color: #f97316; }
    html.dark .card-orange .card-arrow svg { color: #fb923c; }

    html.dark .card-amber   { background: linear-gradient(135deg, #1c1400 0%, #221a00 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    html.dark .card-amber .card-icon   { background: rgba(202,138,4,0.15); }
    html.dark .card-amber .card-icon svg { color: #facc15; }
    html.dark .card-amber .card-label  { color: #fde68a; }
    html.dark .card-amber .card-sub    { color: #ca8a04; }
    html.dark .card-amber .card-arrow svg { color: #facc15; }

    html.dark .card-blue    { background: linear-gradient(135deg, #071220 0%, #0c1a2e 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    html.dark .card-blue .card-icon    { background: rgba(59,130,246,0.12); }
    html.dark .card-blue .card-icon svg { color: #60a5fa; }
    html.dark .card-blue .card-label   { color: #bfdbfe; }
    html.dark .card-blue .card-sub     { color: #3b82f6; }
    html.dark .card-blue .card-arrow svg { color: #60a5fa; }

    /* Stats card */
    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
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
        background: linear-gradient(135deg, rgba(0,0,0,0.015) 0%, transparent 60%);
        pointer-events: none;
    }
    html.dark .stat-card { background: #1f2937; border-color: #374151; }
    html.dark .stat-card::after { background: linear-gradient(135deg, rgba(255,255,255,0.02) 0%, transparent 60%); }

    /* Section title */
    .section-title {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(to right, #e5e7eb, transparent);
    }
    html.dark .section-title { color: #374151; }
    html.dark .section-title::after { background: linear-gradient(to right, #1f2937, transparent); }

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

<div class="py-3 sm:py-6 lg:py-8">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        {{-- NOTIFIKASI FLASH --}}
        @if(session('success') || session('warning') || session('error'))
        @php
            $notifType = session('success') ? 'success' : (session('warning') ? 'warning' : 'error');
            $notifMsg  = session('success') ?? session('warning') ?? session('error');
            $notifStyle = match($notifType) {
                'success' => 'bg-emerald-600 border-emerald-500/40',
                'warning' => 'bg-amber-500 border-amber-400/40',
                default   => 'bg-red-600 border-red-500/40',
            };
        @endphp
        <div id="dash-notif" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300]"
             style="animation: dashNotifIn .25s ease-out;">
            <div class="{{ $notifStyle }} border text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-2.5">
                <span class="flex-1 text-sm font-medium">{{ $notifMsg }}</span>
                <button onclick="document.getElementById('dash-notif').remove()" class="opacity-70 hover:opacity-100 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <style>@keyframes dashNotifIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }</style>
        <script>
            setTimeout(() => { const n = document.getElementById('dash-notif'); if (n) { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); } }, 4000);
        </script>
        @endif

        {{-- Header --}}
        <div class="mb-5 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                Ahlan Wa Sahlan, <span class="text-indigo-600 dark:text-indigo-400">{{ Auth::user()->name }}</span>
            </h1>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 border border-indigo-200 text-indigo-700 dark:bg-indigo-950 dark:border-indigo-700/50 dark:text-indigo-400">
                    🎓 Guru Tahfizh
                </span>
                @if($kelas)
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-700/50 dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse inline-block"></span>
                    Wali Kelas {{ $kelas->nama }}
                </span>
                @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 border border-amber-200 text-amber-700 dark:bg-amber-950 dark:border-amber-700/50 dark:text-amber-400">
                    ⚠️ Belum ditugaskan sebagai wali kelas
                </span>
                @endif
            </div>
        </div>

        {{-- Menu Utama --}}
        <div class="mb-5">
            <p class="section-title">Menu Utama</p>

            {{-- Baris 1: Catat Setoran + Kelola Santri + Rapot Santri --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">

                {{-- Catat Setoran --}}
                <a href="{{ route('guru.input.create') }}" class="menu-card card-indigo">
                    <div class="card-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                {{-- Rapot Santri --}}
                <a href="{{ route('guru.rapot.index') }}" class="menu-card card-orange card-orange-mobile-full col-span-2 sm:col-span-1">
                    <div class="card-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        {{-- Panel: Santri Siap Naik Jilid --}}
        @if(isset($santriSiapNaik) && $santriSiapNaik->isNotEmpty())
        <div class="mb-5 bg-amber-50 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-700/40 rounded-2xl p-4 sm:p-5"
             x-data="{ konfirmasiNaik: false }">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-9 h-9 bg-amber-100 border border-amber-300 dark:bg-amber-500/20 dark:border-amber-600/40 rounded-xl flex items-center justify-center text-lg flex-shrink-0">🔔</div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-amber-800 dark:text-amber-200">{{ $santriSiapNaik->count() }} santri siap naik jilid</h3>
                    <p class="text-xs text-amber-700/80 dark:text-amber-300/70 mt-0.5">Sudah menyelesaikan semua halaman di jilidnya. Centang yang ingin dinaikkan, lalu setujui.</p>
                </div>
            </div>

            <form action="{{ route('guru.naik-jilid') }}" method="POST" x-ref="naikForm">
                @csrf
                <div class="space-y-1.5 mb-3">
                    @foreach($santriSiapNaik as $s)
                    <label class="flex items-center gap-3 px-3 py-2.5 bg-white border border-gray-200 dark:bg-gray-900/60 dark:border-gray-800 rounded-lg cursor-pointer hover:border-amber-400 dark:hover:border-amber-700/40 transition">
                        <input type="checkbox" name="santri_ids[]" value="{{ $s->id }}" checked
                               class="w-4 h-4 rounded border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800 text-amber-500 focus:ring-amber-500/40">
                        <span class="flex-1 text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $s->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Jilid {{ $s->jilid_sekarang }}
                            <span class="text-amber-600 dark:text-amber-400 mx-1">→</span>
                            Jilid {{ $s->jilid_berikutnya }}
                        </span>
                    </label>
                    @endforeach
                </div>
                <button type="button" @click="konfirmasiNaik = true"
                        class="w-full sm:w-auto px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-xl transition">
                    ⬆️ Setujui &amp; Naikkan Jilid
                </button>

                {{-- Modal konfirmasi --}}
                <div x-show="konfirmasiNaik" class="fixed inset-0 z-[200] flex items-center justify-center p-4" style="display:none;" x-cloak>
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="konfirmasiNaik = false"></div>
                    <div class="relative bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm p-5"
                         x-transition:enter="ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-amber-100 border border-amber-300 dark:bg-amber-500/20 dark:border-amber-600/40 rounded-xl flex items-center justify-center text-lg flex-shrink-0">⬆️</div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Naikkan Jilid</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">Naikkan jilid untuk santri yang dicentang?</p>
                        <div class="flex gap-3">
                            <button type="button" @click="konfirmasiNaik = false"
                                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">
                                Batal
                            </button>
                            <button type="button" @click="$refs.naikForm.submit()"
                                    class="flex-1 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-xl transition">
                                Ya, Naikkan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @endif

        {{-- Daftar Santri --}}
        @if($santriList && $santriList->count() > 0)
        <div class="bg-white border border-gray-200 dark:bg-gray-900/60 dark:border-gray-800/60 rounded-xl p-3 sm:p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    Daftar Santri
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-50 border border-indigo-200 text-indigo-700 dark:bg-indigo-950 dark:border-indigo-800/50 dark:text-indigo-400 rounded-full">
                        {{ $santriList->count() }}
                    </span>
                </h2>
            </div>

            {{-- Tabel: tablet+ --}}
            <div class="hidden sm:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider w-8">No</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">NIS</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-10">L/P</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider min-w-[160px]">Progress Hafalan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
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
                                $persen >= 80 => 'text-emerald-600 dark:text-emerald-400',
                                $persen >= 50 => 'text-blue-600 dark:text-blue-400',
                                $persen >= 25 => 'text-amber-600 dark:text-amber-400',
                                default       => 'text-red-600 dark:text-red-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-3 py-2.5 text-xs text-gray-400 dark:text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $santri->name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-gray-500 font-mono">{{ $santri->nis ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-xs text-center text-gray-500">{{ $santri->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400 font-medium">
                                    {{ $santri->target_display }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                @if(($santri->is_iqro_progress ?? false))
                                    @if($santri->iqro_persen_diketahui ?? false)
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-800 rounded-full h-1.5">
                                            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($persen, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold {{ $textColor }} whitespace-nowrap">{{ $persen }}%</span>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-600 whitespace-nowrap hidden lg:inline">(Hal {{ $santri->iqro_hal_sudah }}/{{ $santri->iqro_hal_total }})</span>
                                    </div>
                                    @else
                                    <span class="text-[10px] text-gray-400 dark:text-gray-600 italic">Jumlah halaman Jilid {{ $santri->iqro_jilid }} belum diatur</span>
                                    @endif
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-800 rounded-full h-1.5">
                                            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($persen, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold {{ $textColor }} whitespace-nowrap">{{ $persen }}%</span>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-600 whitespace-nowrap hidden lg:inline">({{ $totalJuz }}/{{ $targetJuz }})</span>
                                    </div>
                                @endif
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
                    $badgeClass = match(true) {
                        $persen >= 80 => 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400',
                        $persen >= 50 => 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-950 dark:border-blue-800/50 dark:text-blue-400',
                        $persen >= 25 => 'bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950 dark:border-amber-800/50 dark:text-amber-400',
                        default       => 'bg-red-50 border-red-200 text-red-700 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400',
                    };
                @endphp
                <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-gray-50 border border-gray-200 dark:bg-gray-800/50 dark:border-gray-800">
                    <span class="text-[10px] text-gray-400 dark:text-gray-600 w-4 text-center flex-shrink-0">{{ $index + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $santri->name }}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1">
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
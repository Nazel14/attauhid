@extends('layouts.app')

@section('content')
<div class="pb-10">

    @php
    /**
     * Format range setoran — Iqro: "Jilid 1 · Hal 1–5", Quran: "Al-Baqarah:1 → Al-Baqarah:10"
     */
    function formatRange($setoran) {
        if (str_starts_with((string)$setoran->surat_start, 'Iqro')) {
            preg_match('/Iqro Jilid (\d+)/', (string)$setoran->surat_start, $m);
            $jilid = $m[1] ?? '?';
            $h1 = $setoran->ayat_start;
            $h2 = $setoran->ayat_end;
            $sep = ($h1 == $h2) ? '' : '–' . $h2;
            return "Jilid {$jilid} · Hal {$h1}{$sep}";
        }
        return $setoran->surat_start . ':' . $setoran->ayat_start
             . ' → '
             . $setoran->surat_end . ':' . $setoran->ayat_end;
    }
@endphp

    {{-- NOTIFICATIONS --}}
    @if(session('success'))
    <div id="notif-ok" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-emerald-600 border border-emerald-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{{ session('success') }}</p>
            <button onclick="document.getElementById('notif-ok').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.setorans.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> Detail Setoran — {{ $santri->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $santri->nis }} • {{ $santri->kelas->name ?? '-' }}</p>
            </div>
            <a href="{{ route('guru.input.create') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">Input Setoran</span>
                <span class="sm:hidden">Input</span>
            </a>
        </div>

        {{-- INFO SANTRI + PERIODE --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4 shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $santri->name }}</p>
                        <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis }} • {{ $santri->kelas->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600">Periode</p>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4 shadow-sm dark:shadow-none">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Filter Periode</p>
            <form method="GET" action="{{ route('guru.setorans.detail', $santri->id) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="fi-lbl">📅 Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="fi-inp">
                </div>
                <div>
                    <label class="fi-lbl">📅 Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="fi-inp">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        Filter
                    </button>
                    <a href="{{ route('guru.setorans.detail', $santri->id) }}" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition-all active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- PROGRESS HAFALAN --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4 shadow-sm dark:shadow-none">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-5 h-5 bg-indigo-100 dark:bg-indigo-500/20 border border-indigo-200 dark:border-indigo-500/30 rounded flex items-center justify-center text-xs">📚</span>
                Progress Hafalan
            </p>

            <div class="mb-4">
                @if($isIqro)
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-500">Target: Jilid {{ $santri->target_jilid ?? '-' }}</span>
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $totalHalamanIqro }} Halaman disetor (Hafalan)</span>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl px-4 py-2.5">
                        <p class="text-xs text-emerald-700 dark:text-emerald-300">📗 Santri Iqro — progress diukur per halaman yang disetor</p>
                    </div>
                @else
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-500">Target: {{ $targetJuz }} Juz</span>
                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totalHafalanJuz, 2) }} Juz ({{ number_format($progressHafalan, 2) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div id="progress-bar" data-progress="{{ min($progressHafalan, 100) }}"
                             class="bg-gradient-to-r from-indigo-600 to-purple-500 h-3 rounded-full transition-all duration-500"
                             style="width:0%"></div>
                    </div>
                    @if($progressHafalan >= 100)
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1.5">🎉 Target tercapai!</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Sisa: {{ number_format($targetJuz - $totalHafalanJuz, 2) }} Juz</p>
                    @endif
                @endif
            </div>

            {{-- Stats 3 kolom --}}
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400/70 font-bold uppercase mb-1">Hafalan</p>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $setoranStats->get('Hafalan')->total ?? 0 }}<span class="text-xs">x</span></p>
                    @if($isIqro)
                        <p class="text-[10px] text-emerald-500 dark:text-emerald-400/60 mt-0.5">{{ $halamanIqroPerKategori['Hafalan'] ?? 0 }} halaman</p>
                    @else
                        <p class="text-[10px] text-emerald-500 dark:text-emerald-400/60 mt-0.5">{{ number_format($setoranStats->get('Hafalan')->total_juz ?? 0, 2) }} juz</p>
                    @endif
                </div>
                <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/30 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-blue-600 dark:text-blue-400/70 font-bold uppercase mb-1">Murajaah</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $setoranStats->get('Murajaah')->total ?? 0 }}<span class="text-xs">x</span></p>
                    @if($isIqro)
                        <p class="text-[10px] text-blue-500 dark:text-blue-400/60 mt-0.5">{{ $halamanIqroPerKategori['Murajaah'] ?? 0 }} halaman</p>
                    @else
                        <p class="text-[10px] text-blue-500 dark:text-blue-400/60 mt-0.5">{{ number_format($setoranStats->get('Murajaah')->total_juz ?? 0, 2) }} juz</p>
                    @endif
                </div>
                <div class="bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/30 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-purple-600 dark:text-purple-400/70 font-bold uppercase mb-1">Tilawah</p>
                    <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $setoranStats->get('Tilawah')->total ?? 0 }}<span class="text-xs">x</span></p>
                    @if($isIqro)
                        <p class="text-[10px] text-purple-500 dark:text-purple-400/60 mt-0.5">{{ $halamanIqroPerKategori['Tilawah'] ?? 0 }} halaman</p>
                    @else
                        <p class="text-[10px] text-purple-500 dark:text-purple-400/60 mt-0.5">{{ number_format($setoranStats->get('Tilawah')->total_juz ?? 0, 2) }} juz</p>
                    @endif
                </div>
            </div>
        </div>

       {{-- TABEL SETORAN PER KATEGORI --}}
<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mb-4 shadow-sm dark:shadow-none">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
            <span class="w-6 h-6 bg-purple-100 dark:bg-purple-500/20 border border-purple-200 dark:border-purple-500/30 rounded-lg flex items-center justify-center text-sm">📖</span>
            Riwayat Setoran Per Kategori
        </h3>
    </div>

    <div class="p-3 sm:p-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- ============================================ --}}
            {{-- HAFALAN                                       --}}
            {{-- ============================================ --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="bg-emerald-100 dark:bg-emerald-600/20 border-b border-emerald-200 dark:border-emerald-700/30 px-4 py-2.5 flex items-center justify-between">
                    <h4 class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Hafalan</h4>
                    <span class="text-[10px] bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full font-semibold">{{ $setoranHafalan->count() }} setoran</span>
                </div>

                @if($setoranHafalan->count() > 0)

                    {{-- MOBILE CARDS (< md) --}}
                    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700/50" id="tbl_hafalan_mobile">
                        @foreach($setoranHafalan as $setoran)
                        <div class="pgrow-hafalan-m px-3 py-2.5">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="pg-no-m text-[10px] font-bold text-gray-500 dark:text-gray-600 bg-gray-100 dark:bg-gray-900/40 px-1.5 py-0.5 rounded"></span>
                                    <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">{{ $setoran->tanggal->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($setoran->audio_path)
                                    <button onclick="playAudio({{ $setoran->id }})" class="p-1 text-indigo-500 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>
                                    @endif
                                    @if($setoran->koreksi_pdf_path)
                                    <a href="{{ asset('storage/' . $setoran->koreksi_pdf_path) }}" target="_blank" class="p-1 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>
                                    @endif
                                    @if($setoran->catatan_guru)
                                    <button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}')" class="p-1 text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/50 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                    @endif
                                    <button onclick="confirmDelete({{ $setoran->id }})" class="p-1 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded ml-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-1">{{ formatRange($setoran) }}</p>
                            <div class="flex justify-between items-center">
                                @if(!$isIqro && $setoran->total_juz > 0)
                                <span class="text-[10px] font-bold text-orange-500 dark:text-orange-400">{{ number_format($setoran->total_juz, 2) }} juz</span>
                                @else
                                <span></span>
                                @endif
                                <span class="chip text-[10px] {{ $setoran->nilai >= 90 ? 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30' : ($setoran->nilai >= 75 ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30' : 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30') }}">{{ $setoran->nilai }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- DESKTOP TABLE (≥ md) — COMPACT --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <th class="th2">Tanggal</th>
                                    <th class="th2">Range · Juz</th>
                                    <th class="th2 text-center">Nilai</th>
                                    <th class="th2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbl_hafalan" class="divide-y divide-gray-200 dark:divide-gray-700/50">
                                @foreach($setoranHafalan as $setoran)
                                <tr class="pgrow-hafalan hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="pg-no text-[10px] text-gray-500 dark:text-gray-600 bg-gray-100 dark:bg-gray-900/40 px-1.5 py-0.5 rounded"></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $setoran->tanggal->format('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="text-xs text-gray-700 dark:text-gray-200">{{ formatRange($setoran) }}</p>
                                        @if(!$isIqro && $setoran->total_juz > 0)
                                        <p class="text-[10px] text-orange-500 dark:text-orange-400 font-bold mt-0.5">{{ number_format($setoran->total_juz, 2) }} juz</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="chip {{ $setoran->nilai >= 90 ? 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30' : ($setoran->nilai >= 75 ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30' : 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30') }}">{{ $setoran->nilai }}</span>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center justify-center gap-0.5">
                                            @if($setoran->audio_path)
                                            <button onclick="playAudio({{ $setoran->id }})" class="p-1.5 text-indigo-500 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors" title="Audio"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>
                                            @endif
                                            @if($setoran->koreksi_pdf_path)
                                            <a href="{{ asset('storage/' . $setoran->koreksi_pdf_path) }}" target="_blank" class="p-1.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-lg transition-colors" title="PDF"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>
                                            @endif
                                            @if($setoran->catatan_guru)
                                            <button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}')" class="p-1.5 text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/50 rounded-lg transition-colors" title="Catatan"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                            @endif
                                            <button onclick="confirmDelete({{ $setoran->id }})" class="p-1.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-lg transition-colors" title="Hapus"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="pg_hafalan" class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40"></div>

                @else
                <div class="p-8 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-600">Belum ada data hafalan</p>
                </div>
                @endif
            </div>

            {{-- ============================================ --}}
            {{-- MURAJAAH & TILAWAH                            --}}
            {{-- ============================================ --}}
            <div class="space-y-4">

                {{-- MURAJAAH --}}
                @php
                    $kategoriData = [
                        ['name'=>'Murajaah', 'data'=>$setoranMurajaah, 'color'=>'blue', 'tblId'=>'tbl_murajaah', 'mTblId'=>'tbl_murajaah_mobile', 'rowCls'=>'pgrow-murajaah', 'mRowCls'=>'pgrow-murajaah-m', 'pgId'=>'pg_murajaah'],
                        ['name'=>'Tilawah', 'data'=>$setoranTilawah, 'color'=>'purple', 'tblId'=>'tbl_tilawah', 'mTblId'=>'tbl_tilawah_mobile', 'rowCls'=>'pgrow-tilawah', 'mRowCls'=>'pgrow-tilawah-m', 'pgId'=>'pg_tilawah'],
                    ];
                    $kelasHeadLight = ['blue'=>'bg-blue-100 border-blue-200', 'purple'=>'bg-purple-100 border-purple-200'];
                    $kelasHeadDark  = ['blue'=>'dark:bg-blue-600/20 dark:border-blue-700/30', 'purple'=>'dark:bg-purple-600/20 dark:border-purple-700/30'];
                    $kelasTitle     = ['blue'=>'text-blue-600 dark:text-blue-400', 'purple'=>'text-purple-600 dark:text-purple-400'];
                    $kelasBadge     = ['blue'=>'bg-blue-500/20 border-blue-500/30 text-blue-600 dark:text-blue-400', 'purple'=>'bg-purple-500/20 border-purple-500/30 text-purple-600 dark:text-purple-400'];
                @endphp

                @foreach($kategoriData as $k)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="{{ $kelasHeadLight[$k['color']] }} {{ $kelasHeadDark[$k['color']] }} border-b px-4 py-2.5 flex items-center justify-between">
                        <h4 class="text-sm font-bold {{ $kelasTitle[$k['color']] }}">{{ $k['name'] }}</h4>
                        <span class="text-[10px] border {{ $kelasBadge[$k['color']] }} px-2 py-0.5 rounded-full font-semibold">{{ $k['data']->count() }} setoran</span>
                    </div>

                    @if($k['data']->count() > 0)

                        {{-- MOBILE CARDS --}}
                        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700/50" id="{{ $k['mTblId'] }}">
                            @foreach($k['data'] as $setoran)
                            <div class="{{ $k['mRowCls'] }} px-3 py-2.5">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="pg-no-m text-[10px] font-bold text-gray-500 dark:text-gray-600 bg-gray-100 dark:bg-gray-900/40 px-1.5 py-0.5 rounded"></span>
                                        <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">{{ $setoran->tanggal->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @if($setoran->audio_path)<button onclick="playAudio({{ $setoran->id }})" class="p-1 text-indigo-500 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>@endif
                                        @if($setoran->koreksi_pdf_path)<a href="{{ asset('storage/' . $setoran->koreksi_pdf_path) }}" target="_blank" class="p-1 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>@endif
                                        @if($setoran->catatan_guru)<button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}')" class="p-1 text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/50 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>@endif
                                        <button onclick="confirmDelete({{ $setoran->id }})" class="p-1 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded ml-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ formatRange($setoran) }}</p>
                                    <span class="chip text-[10px] {{ $setoran->nilai >= 90 ? 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30' : ($setoran->nilai >= 75 ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30' : 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30') }}">{{ $setoran->nilai }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- DESKTOP TABLE — COMPACT --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50 dark:bg-gray-900/40">
                                    <tr>
                                        <th class="th2">Tanggal</th>
                                        <th class="th2">Range</th>
                                        <th class="th2 text-center">Nilai</th>
                                        <th class="th2 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="{{ $k['tblId'] }}" class="divide-y divide-gray-200 dark:divide-gray-700/50">
                                    @foreach($k['data'] as $setoran)
                                    <tr class="{{ $k['rowCls'] }} hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-3 py-2.5 whitespace-nowrap">
                                            <div class="flex items-center gap-1.5">
                                                <span class="pg-no text-[10px] text-gray-500 dark:text-gray-600 bg-gray-100 dark:bg-gray-900/40 px-1.5 py-0.5 rounded"></span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $setoran->tanggal->format('d/m/Y') }}</span>
                                            </div>
                                        </td>
                                       <td class="px-3 py-2.5 text-xs text-gray-700 dark:text-gray-200">{{ formatRange($setoran) }}</td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="chip {{ $setoran->nilai >= 90 ? 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30' : ($setoran->nilai >= 75 ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30' : 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30') }}">{{ $setoran->nilai }}</span>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center justify-center gap-0.5">
                                                @if($setoran->audio_path)<button onclick="playAudio({{ $setoran->id }})" class="p-1.5 text-indigo-500 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>@endif
                                                @if($setoran->koreksi_pdf_path)<a href="{{ asset('storage/' . $setoran->koreksi_pdf_path) }}" target="_blank" class="p-1.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-lg transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>@endif
                                                @if($setoran->catatan_guru)<button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}')" class="p-1.5 text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/50 rounded-lg transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>@endif
                                                <button onclick="confirmDelete({{ $setoran->id }})" class="p-1.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-lg transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="{{ $k['pgId'] }}" class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40"></div>

                    @else
                    <div class="p-6 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-600">Belum ada data {{ strtolower($k['name']) }}</p>
                    </div>
                    @endif
                </div>
                @endforeach

            </div>{{-- end kolom kanan --}}
        </div>
    </div>
</div>

    </div>
</div>

{{-- AUDIO MODAL --}}
<div id="audioModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center px-4">
    <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl w-full max-w-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">🎤 Rekaman Audio</h3>
            <button onclick="closeAudioModal()" class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <audio id="modalAudioPlayer" controls class="w-full rounded-lg">
            <source id="audioSource" src="" type="audio/webm">
        </audio>
        <div class="mt-3 flex justify-end">
            <a id="downloadLink" href="#" download class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Audio
            </a>
        </div>
    </div>
</div>

{{-- NOTE MODAL --}}
<div id="noteModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center px-4">
    <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">📝 Catatan Guru</h3>
            <button onclick="closeNoteModal()" class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4">
            <p id="noteContent" class="text-gray-700 dark:text-gray-200 text-sm whitespace-pre-wrap"></p>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center px-4">
    <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-100 dark:bg-red-950/50 border border-red-200 dark:border-red-800/30 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Hapus Setoran?</h3>
            <p class="text-xs text-gray-500 mb-5">Tindakan ini permanen dan tidak dapat dikembalikan.</p>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="closeDeleteModal()" class="py-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">Batal</button>
                <form id="deleteForm" method="POST" class="w-full">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full py-2.5 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-xl transition">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .th2 { padding:10px 12px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#4b5563; margin-bottom:5px; }
    html.dark .fi-lbl { color:#9ca3af; }
    .fi-inp { width:100%; background:#fff; border:1px solid #d1d5db; border-radius:10px; color:#111827; font-size:13px; padding:9px 12px; outline:none; transition:border-color .15s; }
    html.dark .fi-inp { background:#0d1117; border:1px solid #374151; color:#e5e7eb; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    input[type="date"]::-webkit-calendar-picker-indicator { cursor:pointer; opacity:.6; }
    html.dark input[type="date"]::-webkit-calendar-picker-indicator { filter:invert(1); opacity:.7; }
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity:1; }
    @keyframes slide-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .animate-slide-in { animation:slide-in .25s ease-out; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>

<script>
// ============================================================
// PAGINATION
// ============================================================
var paginationConfig = [
    { tbodyId: 'tbl_hafalan',  mTblId: 'tbl_hafalan_mobile',  rowClass: 'pgrow-hafalan',  mRowClass: 'pgrow-hafalan-m',  pgId: 'pg_hafalan',  perPage: 13 },
    { tbodyId: 'tbl_murajaah', mTblId: 'tbl_murajaah_mobile', rowClass: 'pgrow-murajaah', mRowClass: 'pgrow-murajaah-m', pgId: 'pg_murajaah', perPage: 6  },
    { tbodyId: 'tbl_tilawah',  mTblId: 'tbl_tilawah_mobile',  rowClass: 'pgrow-tilawah',  mRowClass: 'pgrow-tilawah-m',  pgId: 'pg_tilawah',  perPage: 6  }
];

var pgState = {};

function initPagination(cfg) {
    var tbody  = document.getElementById(cfg.tbodyId);
    var mTbody = document.getElementById(cfg.mTblId);
    if (!tbody && !mTbody) return;

    var rows  = tbody  ? Array.prototype.slice.call(tbody.querySelectorAll('.' + cfg.rowClass))  : [];
    var mRows = mTbody ? Array.prototype.slice.call(mTbody.querySelectorAll('.' + cfg.mRowClass)) : [];

    var totalRows  = Math.max(rows.length, mRows.length);
    var totalPages = Math.ceil(totalRows / cfg.perPage);

    pgState[cfg.tbodyId] = {
        rows: rows,
        mRows: mRows,
        totalRows: totalRows,
        totalPages: totalPages,
        perPage: cfg.perPage,
        currentPage: 1,
        pgId: cfg.pgId
    };
    showPage(cfg.tbodyId, 1);
}

function showPage(tbodyId, page) {
    var s = pgState[tbodyId];
    if (!s) return;
    s.currentPage = page;
    var start = (page - 1) * s.perPage;
    var end   = start + s.perPage;

    // Desktop rows
    for (var i = 0; i < s.rows.length; i++) {
        var visible = (i >= start && i < end);
        s.rows[i].style.display = visible ? '' : 'none';
        if (visible) {
            var noCell = s.rows[i].querySelector('.pg-no');
            if (noCell) noCell.textContent = '#' + (i + 1);
        }
    }

    // Mobile rows
    for (var j = 0; j < s.mRows.length; j++) {
        var mVisible = (j >= start && j < end);
        s.mRows[j].style.display = mVisible ? '' : 'none';
        if (mVisible) {
            var mNoCell = s.mRows[j].querySelector('.pg-no-m');
            if (mNoCell) mNoCell.textContent = '#' + (j + 1);
        }
    }

    renderPagination(tbodyId);
}

function pgMakeBtn(tbodyId, cp, p) {
    var cls = p === cp ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700';
    return '<button onclick="showPage(\'' + tbodyId + '\',' + p + ')" class="px-2.5 py-1 text-xs rounded-md font-medium ' + cls + '">' + p + '</button>';
}

function pgMakePrev(tbodyId, cp) {
    if (cp > 1) return '<button onclick="showPage(\'' + tbodyId + '\',' + (cp - 1) + ')" class="px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md">&#8249;</button>';
    return '<button disabled class="px-2 py-1 text-xs text-gray-300 dark:text-gray-700 rounded-md">&#8249;</button>';
}

function pgMakeNext(tbodyId, cp, tp) {
    if (cp < tp) return '<button onclick="showPage(\'' + tbodyId + '\',' + (cp + 1) + ')" class="px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md">&#8250;</button>';
    return '<button disabled class="px-2 py-1 text-xs text-gray-300 dark:text-gray-700 rounded-md">&#8250;</button>';
}

function renderPagination(tbodyId) {
    var s = pgState[tbodyId];
    var container = document.getElementById(s.pgId);
    if (!s || !container) return;

    if (s.totalPages <= 1) {
        container.innerHTML = '<div class="text-[11px] text-gray-500 dark:text-gray-600 text-center py-1">Menampilkan ' + s.totalRows + ' data</div>';
        return;
    }

    var cp = s.currentPage;
    var tp = s.totalPages;
    var from = Math.min((cp - 1) * s.perPage + 1, s.totalRows);
    var to   = Math.min(cp * s.perPage, s.totalRows);

    var sp = Math.max(1, cp - 1);
    var ep = Math.min(tp, cp + 1);

    var btns = '';
    if (sp > 1) {
        btns += pgMakeBtn(tbodyId, cp, 1);
        if (sp > 2) btns += '<span class="text-xs text-gray-400 dark:text-gray-700">&hellip;</span>';
    }
    for (var i = sp; i <= ep; i++) btns += pgMakeBtn(tbodyId, cp, i);
    if (ep < tp) {
        if (ep < tp - 1) btns += '<span class="text-xs text-gray-400 dark:text-gray-700">&hellip;</span>';
        btns += pgMakeBtn(tbodyId, cp, tp);
    }

    container.innerHTML =
        '<div class="flex items-center justify-between">' +
            '<span class="text-[11px] text-gray-500 dark:text-gray-600">' + from + '&ndash;' + to + ' / ' + s.totalRows + '</span>' +
            '<div class="flex items-center gap-1">' + pgMakePrev(tbodyId, cp) + btns + pgMakeNext(tbodyId, cp, tp) + '</div>' +
        '</div>';
}

document.addEventListener('DOMContentLoaded', function () {
    for (var i = 0; i < paginationConfig.length; i++) {
        initPagination(paginationConfig[i]);
    }
    var pb = document.getElementById('progress-bar');
    if (pb) pb.style.width = pb.dataset.progress + '%';
});

// ============================================================
// SCROLL LOCK
// ============================================================
function lockScroll()   { document.body.style.overflow = 'hidden'; }
function unlockScroll() { document.body.style.overflow = ''; }

// ============================================================
// AUDIO MODAL
// ============================================================
function playAudio(setoranId) {
    var url = '/guru/setorans/' + setoranId + '/audio';
    document.getElementById('audioSource').src = url;
    document.getElementById('downloadLink').href = url;
    document.getElementById('modalAudioPlayer').load();
    document.getElementById('audioModal').classList.remove('hidden');
    lockScroll();
    document.getElementById('modalAudioPlayer').play().catch(function () {
        alert('Gagal memutar audio.');
    });
}

function closeAudioModal() {
    var p = document.getElementById('modalAudioPlayer');
    p.pause();
    p.currentTime = 0;
    document.getElementById('audioModal').classList.add('hidden');
    unlockScroll();
}

// ============================================================
// NOTE MODAL
// ============================================================
function showNote(note) {
    document.getElementById('noteContent').textContent = note;
    document.getElementById('noteModal').classList.remove('hidden');
    lockScroll();
}

function closeNoteModal() {
    document.getElementById('noteModal').classList.add('hidden');
    unlockScroll();
}

// ============================================================
// DELETE MODAL
// ============================================================
function confirmDelete(id) {
    document.getElementById('deleteForm').action = '/guru/setorans/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    lockScroll();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    unlockScroll();
}

// ============================================================
// CLOSE MODALS ON BACKDROP CLICK & ESC
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    var modals = ['audioModal', 'noteModal', 'deleteModal'];
    for (var m = 0; m < modals.length; m++) {
        (function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function (e) {
                if (e.target === this) {
                    if (id === 'audioModal')  closeAudioModal();
                    else if (id === 'noteModal')   closeNoteModal();
                    else                           closeDeleteModal();
                }
            });
        })(modals[m]);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeAudioModal();
        closeNoteModal();
        closeDeleteModal();
    }
});

// ============================================================
// AUTO-DISMISS NOTIF
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    var n = document.getElementById('notif-ok');
    if (n) {
        setTimeout(function () {
            n.style.transition = 'opacity .4s';
            n.style.opacity = '0';
            setTimeout(function () { n.remove(); }, 400);
        }, 5000);
    }
});
</script>
@endsection
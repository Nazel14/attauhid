@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-950 pb-10">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.monitoring.index') }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white truncate"> {{ $santri->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $santri->nis }} · {{ $santri->kelas->name ?? '-' }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-[10px] text-gray-600">Periode</p>
                <p class="text-xs font-semibold text-gray-400">{{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- PROGRESS & STATS --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

            {{-- Progress Hafalan --}}
            <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">📖 Progress Hafalan</h3>
                <div class="mb-5">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-400">Target: <strong class="text-white">{{ $targetJuz }} Juz</strong></span>
                        <span class="text-xs font-bold text-indigo-400">{{ number_format($totalHafalanJuz, 2) }} Juz ({{ number_format($progressHafalan, 1) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-4 rounded-full flex items-center justify-end pr-2 transition-all duration-500"
                             style="width: {{ min($progressHafalan, 100) }}%; min-width: {{ $progressHafalan > 0 ? '24px' : '0' }}">
                            @if($progressHafalan > 15)
                            <span class="text-white text-[10px] font-bold">{{ number_format(min($progressHafalan, 100), 0) }}%</span>
                            @endif
                        </div>
                    </div>
                    @if($progressHafalan >= 100)
                        <p class="text-xs text-emerald-400 font-semibold mt-1.5">🎉 Target tercapai!</p>
                    @else
                        <p class="text-xs text-gray-600 mt-1.5">Sisa: {{ number_format($targetJuz - $totalHafalanJuz, 2) }} Juz</p>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-emerald-950/30 border border-emerald-800/30 rounded-xl p-3.5 text-center">
                        <p class="text-[10px] text-emerald-600 font-bold uppercase mb-1">Hafalan</p>
                        <p class="text-2xl font-black text-emerald-400">{{ $setoranStats->get('Hafalan')->total ?? 0 }}</p>
                        <p class="text-[10px] text-emerald-600 mt-0.5">{{ number_format($setoranStats->get('Hafalan')->total_juz ?? 0, 2) }} juz</p>
                    </div>
                    <div class="bg-blue-950/30 border border-blue-800/30 rounded-xl p-3.5 text-center">
                        <p class="text-[10px] text-blue-600 font-bold uppercase mb-1">Murajaah</p>
                        <p class="text-2xl font-black text-blue-400">{{ $setoranStats->get('Murajaah')->total ?? 0 }}</p>
                        <p class="text-[10px] text-blue-600 mt-0.5">{{ number_format($setoranStats->get('Murajaah')->total_juz ?? 0, 2) }} juz</p>
                    </div>
                    <div class="bg-purple-950/30 border border-purple-800/30 rounded-xl p-3.5 text-center">
                        <p class="text-[10px] text-purple-600 font-bold uppercase mb-1">Tilawah</p>
                        <p class="text-2xl font-black text-purple-400">{{ $setoranStats->get('Tilawah')->total ?? 0 }}</p>
                        <p class="text-[10px] text-purple-600 mt-0.5">{{ number_format($setoranStats->get('Tilawah')->total_juz ?? 0, 2) }} juz</p>
                    </div>
                </div>
            </div>

            {{-- Presensi Stats --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">📋 Statistik Presensi</h3>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between bg-emerald-950/30 border border-emerald-800/30 rounded-xl px-4 py-3">
                        <span class="text-sm font-semibold text-emerald-300">Hadir</span>
                        <span class="text-xl font-black text-emerald-400">{{ $presensiStats->get('Hadir', 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-blue-950/30 border border-blue-800/30 rounded-xl px-4 py-3">
                        <span class="text-sm font-semibold text-blue-300">Izin</span>
                        <span class="text-xl font-black text-blue-400">{{ $presensiStats->get('Izin', 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-amber-950/30 border border-amber-800/30 rounded-xl px-4 py-3">
                        <span class="text-sm font-semibold text-amber-300">Sakit</span>
                        <span class="text-xl font-black text-amber-400">{{ $presensiStats->get('Sakit', 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-red-950/30 border border-red-800/30 rounded-xl px-4 py-3">
                        <span class="text-sm font-semibold text-red-300">Alpha</span>
                        <span class="text-xl font-black text-red-400">{{ $presensiStats->get('Alpha', 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIWAYAT HAFALAN --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden mb-4">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-800">
        <h3 class="text-sm font-bold text-white">📖 Riwayat Hafalan</h3>
        <span class="text-[11px] text-gray-600">{{ $setoranHafalan->total() }} total · hal {{ $setoranHafalan->currentPage() }}/{{ $setoranHafalan->lastPage() }}</span>
    </div>

    {{-- ============================== --}}
    {{-- MOBILE: CARD LAYOUT (< lg)     --}}
    {{-- ============================== --}}
    <div class="lg:hidden divide-y divide-gray-800/50">
        @forelse($setoranHafalan as $index => $setoran)
        <div class="px-4 py-3 bg-emerald-950/20 border-l-2 border-emerald-800/30">
            <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-600 bg-gray-800 px-1.5 py-0.5 rounded">{{ $setoranHafalan->firstItem() + $index }}</span>
                    <span class="text-xs font-semibold text-gray-300">{{ $setoran->tanggal->format('d/m/Y') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    @if($setoran->audio_path)
                    <button onclick="playAudio({{ $setoran->id }})" class="text-indigo-400 hover:text-indigo-300">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                    </button>
                    @endif
                    @if($setoran->koreksi_pdf_path)
                    <button onclick="viewPDF({{ $setoran->id }})" class="text-red-400 hover:text-red-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </button>
                    @endif
                    @if($setoran->catatan_guru)
                    <button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}', '{{ $setoran->guru->user->name ?? 'Guru' }}')" class="text-amber-400 hover:text-amber-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    @endif
                </div>
            </div>
            <p class="text-[11px] text-gray-500 mb-1.5">{{ $setoran->surat_start }}:{{ $setoran->ayat_start }} → {{ $setoran->surat_end }}:{{ $setoran->ayat_end }}</p>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="chip bg-orange-950/50 text-orange-400 border-orange-800/30">{{ number_format($setoran->total_juz, 2) }} juz</span>
                    <span class="text-[10px] text-gray-600">{{ $setoran->guru->user->name ?? '-' }}</span>
                </div>
                <span class="text-xs font-bold text-gray-300">Nilai: {{ $setoran->nilai }}</span>
            </div>
        </div>
        @empty
        <div class="py-8 text-center text-sm text-gray-500">Belum ada data hafalan</div>
        @endforelse
    </div>

    {{-- ============================== --}}
    {{-- DESKTOP: TABLE LAYOUT (≥ lg)   --}}
    {{-- ============================== --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-800 bg-gray-800/40">
                    <th class="th w-10">No</th>
                    <th class="th">Tanggal</th>
                    <th class="th">Range</th>
                    <th class="th">Juz</th>
                    <th class="th">Nilai</th>
                    <th class="th">Guru</th>
                    <th class="th text-center">Media</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/50">
                @forelse($setoranHafalan as $index => $setoran)
                <tr class="hover:bg-gray-800/25 transition-colors">
                    <td class="px-4 py-3 text-xs text-gray-600 text-center">{{ $setoranHafalan->firstItem() + $index }}</td>
                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $setoran->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $setoran->surat_start }}:{{ $setoran->ayat_start }} → {{ $setoran->surat_end }}:{{ $setoran->ayat_end }}</td>
                    <td class="px-4 py-3"><span class="chip bg-orange-950/50 text-orange-400 border-orange-800/30">{{ number_format($setoran->total_juz, 2) }}</span></td>
                    <td class="px-4 py-3 text-sm font-bold text-gray-200">{{ $setoran->nilai }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $setoran->guru->user->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            @if($setoran->audio_path)
                            <button onclick="playAudio({{ $setoran->id }})" class="text-indigo-400 hover:text-indigo-300">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                            </button>
                            @endif
                            @if($setoran->koreksi_pdf_path)
                            <button onclick="viewPDF({{ $setoran->id }})" class="text-red-400 hover:text-red-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </button>
                            @endif
                            @if($setoran->catatan_guru)
                            <button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}', '{{ $setoran->guru->user->name ?? 'Guru' }}')" class="text-amber-400 hover:text-amber-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-sm text-gray-500">Belum ada data hafalan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============================== --}}
    {{-- PAGINATION (Mobile & Desktop)  --}}
    {{-- ============================== --}}
    @if($setoranHafalan->hasPages())
    <div class="px-5 py-3.5 border-t border-gray-800 flex items-center justify-between">
        <p class="text-xs text-gray-600">{{ $setoranHafalan->firstItem() }}-{{ $setoranHafalan->lastItem() }} dari {{ $setoranHafalan->total() }}</p>
        <div class="flex items-center gap-1">
            @if($setoranHafalan->onFirstPage())
                <span class="px-2.5 py-1 text-xs text-gray-600 bg-gray-800 rounded-lg cursor-not-allowed">‹</span>
            @else
                <a href="{{ $setoranHafalan->previousPageUrl() }}" class="px-2.5 py-1 text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">‹</a>
            @endif

            {{-- Page numbers - hide some on mobile --}}
            @php
                $currentPage = $setoranHafalan->currentPage();
                $lastPage = $setoranHafalan->lastPage();
                $start = max(1, $currentPage - 1);
                $end = min($lastPage, $currentPage + 1);
            @endphp

            @if($start > 1)
                <a href="{{ $setoranHafalan->url(1) }}" class="hidden sm:inline-block px-2.5 py-1 text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">1</a>
                @if($start > 2)
                    <span class="hidden sm:inline-block text-gray-600 text-xs">...</span>
                @endif
            @endif

            @for($page = $start; $page <= $end; $page++)
                @if($page == $currentPage)
                    <span class="px-2.5 py-1 text-xs font-bold text-white bg-indigo-600 rounded-lg">{{ $page }}</span>
                @else
                    <a href="{{ $setoranHafalan->url($page) }}" class="px-2.5 py-1 text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">{{ $page }}</a>
                @endif
            @endfor

            @if($end < $lastPage)
                @if($end < $lastPage - 1)
                    <span class="hidden sm:inline-block text-gray-600 text-xs">...</span>
                @endif
                <a href="{{ $setoranHafalan->url($lastPage) }}" class="hidden sm:inline-block px-2.5 py-1 text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">{{ $lastPage }}</a>
            @endif

            @if($setoranHafalan->hasMorePages())
                <a href="{{ $setoranHafalan->nextPageUrl() }}" class="px-2.5 py-1 text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">›</a>
            @else
                <span class="px-2.5 py-1 text-xs text-gray-600 bg-gray-800 rounded-lg cursor-not-allowed">›</span>
            @endif
        </div>
    </div>
    @endif
</div>

        {{-- MURAJAAH & TILAWAH --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

            @foreach([['Murajaah', $setoranMurajaah, 'border-blue-800/30', 'bg-blue-950/20', 'text-blue-400'], ['Tilawah', $setoranTilawah, 'border-purple-800/30', 'bg-purple-950/20', 'text-purple-400']] as [$label, $setorans, $borderClass, $bgClass, $colorClass])
            <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-800">
                    <h3 class="text-sm font-bold text-white">Riwayat {{ $label }}</h3>
                    <span class="text-[11px] text-gray-600">{{ $setorans->total() }} total</span>
                </div>
                <div class="divide-y divide-gray-800/50">
                    @forelse($setorans as $index => $setoran)
                    <div class="px-4 py-3 {{ $bgClass }} border-l-2 {{ $borderClass }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-gray-600 bg-gray-800 px-1.5 py-0.5 rounded">{{ $setorans->firstItem() + $index }}</span>
                                <span class="text-xs font-semibold text-gray-300">{{ $setoran->tanggal->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($setoran->audio_path)
                                <button onclick="playAudio({{ $setoran->id }})" class="text-indigo-400 hover:text-indigo-300"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg></button>
                                @endif
                                @if($setoran->koreksi_pdf_path)
                                <button onclick="viewPDF({{ $setoran->id }})" class="text-red-400 hover:text-red-300"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></button>
                                @endif
                                @if($setoran->catatan_guru)
                                <button onclick="showNote('{{ addslashes($setoran->catatan_guru) }}', '{{ $setoran->guru->user->name ?? 'Guru' }}')" class="text-amber-400 hover:text-amber-300"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                @endif
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-500 mb-1.5">{{ $setoran->surat_start }}:{{ $setoran->ayat_start }} → {{ $setoran->surat_end }}:{{ $setoran->ayat_end }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-xs {{ $colorClass }}">{{ number_format($setoran->total_juz, 2) }} juz</span>
                            <span class="text-xs font-bold text-gray-300">Nilai: {{ $setoran->nilai }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-sm text-gray-500">Belum ada data {{ $label }}</div>
                    @endforelse
                </div>
                @if($setorans->hasPages())
                <div class="px-4 py-3 border-t border-gray-800 flex items-center justify-between">
                    <span class="text-[10px] text-gray-600">Hal {{ $setorans->currentPage() }}/{{ $setorans->lastPage() }}</span>
                    <div class="flex items-center gap-1">
                        @if(!$setorans->onFirstPage())
                            <a href="{{ $setorans->previousPageUrl() }}" class="px-2 py-1 text-[10px] text-gray-400 bg-gray-800 hover:bg-gray-700 rounded transition-colors">‹</a>
                        @endif
                        @if($setorans->hasMorePages())
                            <a href="{{ $setorans->nextPageUrl() }}" class="px-2 py-1 text-[10px] text-gray-400 bg-gray-800 hover:bg-gray-700 rounded transition-colors">›</a>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endforeach

        </div>

        {{-- RIWAYAT PRESENSI: Kalender + List --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden"
     x-data="{ view: 'calendar' }">

    {{-- Header dengan toggle view --}}
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-800">
        <h3 class="text-sm font-bold text-white">📅 Riwayat Presensi</h3>
        <div class="flex bg-gray-800 rounded-lg p-0.5 gap-0.5">
            <button @click="view = 'calendar'"
                    :class="view === 'calendar' ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:text-gray-300'"
                    class="px-2.5 py-1 text-[10px] font-bold rounded-md transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Kalender
            </button>
            <button @click="view = 'list'"
                    :class="view === 'list' ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:text-gray-300'"
                    class="px-2.5 py-1 text-[10px] font-bold rounded-md transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                List
            </button>
        </div>
    </div>

    @php
        // Group presensi data by date
        $presensiByDate = [];
        foreach($presensiData as $p) {
            $key = $p->tanggal->format('Y-m-d');
            if (!isset($presensiByDate[$key])) {
                $presensiByDate[$key] = [];
            }
            $presensiByDate[$key][] = $p;
        }

        // Determine date range for calendar
        $start = \Carbon\Carbon::parse($tanggalMulai);
        $end = \Carbon\Carbon::parse($tanggalAkhir);

        // Calendar months in range
        $calendarMonths = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $calendarMonths[] = $cursor->copy();
            $cursor->addMonth();
        }

        $statusColors = [
            'Hadir' => ['bg' => 'bg-emerald-500/30', 'border' => 'border-emerald-500', 'text' => 'text-emerald-300', 'dot' => 'bg-emerald-500'],
            'Izin'  => ['bg' => 'bg-blue-500/30',    'border' => 'border-blue-500',    'text' => 'text-blue-300',    'dot' => 'bg-blue-500'],
            'Sakit' => ['bg' => 'bg-amber-500/30',   'border' => 'border-amber-500',   'text' => 'text-amber-300',   'dot' => 'bg-amber-500'],
            'Alpha' => ['bg' => 'bg-red-500/30',     'border' => 'border-red-500',     'text' => 'text-red-300',     'dot' => 'bg-red-500'],
        ];
    @endphp

    @if($presensiData->count() === 0)
        <p class="text-center text-sm text-gray-500 py-12">Belum ada data presensi</p>
    @else

    {{-- ============================== --}}
{{-- VIEW: CALENDAR (default)       --}}
{{-- ============================== --}}
<div x-show="view === 'calendar'" x-cloak>

    {{-- Legend --}}
    <div class="flex items-center justify-center gap-3 sm:gap-4 px-4 py-3 border-b border-gray-800 bg-gray-800/20 flex-wrap">
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="text-[10px] font-semibold text-gray-400">Hadir</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            <span class="text-[10px] font-semibold text-gray-400">Izin</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <span class="text-[10px] font-semibold text-gray-400">Sakit</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
            <span class="text-[10px] font-semibold text-gray-400">Alpha</span>
        </div>
    </div>

    {{-- Multi-month grid: responsive 1/2/3 columns --}}
    <div class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($calendarMonths as $monthDate)
            @php
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                $firstDayOfWeek = $monthStart->dayOfWeek;
                $daysInMonth = $monthEnd->day;
            @endphp
            <div class="bg-gray-800/20 border border-gray-800 rounded-xl p-3">
                {{-- Month header --}}
                <h4 class="text-xs font-bold text-gray-300 mb-3 flex items-center gap-2">
                    <span class="w-1 h-3.5 bg-indigo-500 rounded-full"></span>
                    {{ $monthDate->locale('id')->isoFormat('MMMM Y') }}
                </h4>

                {{-- Day-of-week header --}}
                <div class="grid grid-cols-7 gap-1 mb-1.5">
                    @foreach(['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $dayLabel)
                    <div class="text-[9px] font-bold text-gray-600 text-center py-1">{{ $dayLabel }}</div>
                    @endforeach
                </div>

                {{-- Calendar grid --}}
                <div class="grid grid-cols-7 gap-1">
                    {{-- Empty cells before month start --}}
                    @for($i = 0; $i < $firstDayOfWeek; $i++)
                    <div></div>
                    @endfor

                    {{-- Days --}}
                    @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dateKey = $monthStart->copy()->day($day)->format('Y-m-d');
                        $dayPresensi = $presensiByDate[$dateKey] ?? [];
                        $isInRange = $monthStart->copy()->day($day)->between($start, $end);

                        // Get primary status (priority: Alpha > Sakit > Izin > Hadir)
                        $primaryStatus = null;
                        $statusPriority = ['Alpha' => 4, 'Sakit' => 3, 'Izin' => 2, 'Hadir' => 1];
                        $highestPriority = 0;
                        foreach($dayPresensi as $dp) {
                            $prio = $statusPriority[$dp->status] ?? 0;
                            if ($prio > $highestPriority) {
                                $highestPriority = $prio;
                                $primaryStatus = $dp->status;
                            }
                        }

                        $hasMultiSesi = count($dayPresensi) > 1;
                        $colors = $primaryStatus ? $statusColors[$primaryStatus] : null;
                    @endphp

                    @if(!empty($dayPresensi))
                        {{-- Has presensi data --}}
                        <div class="relative group aspect-square {{ $colors['bg'] }} border {{ $colors['border'] }} rounded-md flex flex-col items-center justify-center cursor-pointer hover:scale-110 transition-transform"
                             title="{{ $monthStart->copy()->day($day)->locale('id')->isoFormat('dddd, D MMMM Y') }} — {{ collect($dayPresensi)->map(fn($p) => $p->sesi.': '.$p->status)->join(', ') }}">
                            <span class="text-[10px] font-bold {{ $colors['text'] }}">{{ $day }}</span>
                            @if($hasMultiSesi)
                            <div class="flex gap-0.5 mt-0.5">
                                @foreach($dayPresensi as $dp)
                                <span class="w-0.5 h-0.5 rounded-full {{ $statusColors[$dp->status]['dot'] ?? 'bg-gray-500' }}"></span>
                                @endforeach
                            </div>
                            @endif

                            {{-- Tooltip on hover (desktop) --}}
                            <div class="hidden lg:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 bg-gray-950 border border-gray-700 rounded-md text-[10px] text-gray-300 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                {{ $monthStart->copy()->day($day)->format('d/m') }}: {{ collect($dayPresensi)->map(fn($p) => $p->sesi.' '.$p->status)->join(' • ') }}
                            </div>
                        </div>
                    @else
                        {{-- No data --}}
                        <div class="aspect-square {{ $isInRange ? 'bg-gray-800/40' : 'bg-transparent' }} border {{ $isInRange ? 'border-gray-800' : 'border-transparent' }} rounded-md flex items-center justify-center">
                            <span class="text-[10px] {{ $isInRange ? 'text-gray-600' : 'text-gray-700' }}">{{ $day }}</span>
                        </div>
                    @endif
                    @endfor
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

    {{-- ============================== --}}
    {{-- VIEW: LIST (Detail per Tanggal)--}}
    {{-- ============================== --}}
    <div x-show="view === 'list'" x-cloak class="divide-y divide-gray-800/50 max-h-[600px] overflow-y-auto">
        @foreach($presensiByDate as $dateKey => $dayPresensi)
        @php
            $dateObj = \Carbon\Carbon::parse($dateKey);
        @endphp
        <div class="px-4 py-3">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ $dateObj->locale('id')->isoFormat('dddd, D MMM Y') }}</p>
                    <p class="text-[10px] text-gray-600">{{ count($dayPresensi) }} sesi</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($dayPresensi as $p)
                @php
                    $colors = $statusColors[$p->status] ?? ['bg' => 'bg-gray-700/30', 'border' => 'border-gray-600', 'text' => 'text-gray-400', 'dot' => 'bg-gray-500'];
                @endphp
                <div class="flex items-center justify-between {{ $colors['bg'] }} border {{ $colors['border'] }} rounded-lg px-3 py-2">
                    <span class="text-[11px] font-semibold text-gray-300">{{ $p->sesi }}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $colors['dot'] }}"></span>
                        <span class="text-[10px] font-bold {{ $colors['text'] }}">{{ $p->status }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    @endif
</div>

    </div>
</div>

{{-- AUDIO MODAL --}}
<div id="audioModal" class="hidden fixed inset-0 bg-black/75 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="relative bg-gray-900 border border-gray-700 rounded-t-2xl sm:rounded-2xl w-full sm:max-w-sm overflow-hidden">
        <div class="w-10 h-1 bg-gray-700 rounded-full mx-auto mt-3 mb-1 sm:hidden"></div>
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-800">
            <h3 class="text-sm font-bold text-white">🎙️ Rekaman Audio</h3>
            <button onclick="closeAudioModal()" class="text-gray-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-5">
            <audio id="modalAudioPlayer" controls class="w-full"><source id="audioSource" src="" type="audio/webm"></audio>
        </div>
    </div>
</div>

{{-- PDF MODAL --}}
<div id="pdfModal" class="hidden fixed inset-0 bg-black/85 z-50 flex items-center justify-center p-4">
    <div class="relative bg-gray-900 border border-gray-700 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden" style="height:90vh;">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-800">
            <h3 class="text-sm font-bold text-white">📄 Preview PDF Koreksi</h3>
            <div class="flex items-center gap-2">
                <a id="downloadPdfBtn" href="#" download class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Download</a>
                <button onclick="closePDFModal()" class="text-gray-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>
        <iframe id="pdfFrame" src="" class="w-full" style="height:calc(90vh - 56px); border:0;"></iframe>
    </div>
</div>

{{-- NOTE MODAL --}}
<div id="noteModal" class="hidden fixed inset-0 bg-black/75 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="relative bg-gray-900 border border-gray-700 rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md overflow-hidden">
        <div class="w-10 h-1 bg-gray-700 rounded-full mx-auto mt-3 mb-1 sm:hidden"></div>
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-800">
            <h3 class="text-sm font-bold text-white">📝 Catatan Guru</h3>
            <button onclick="closeNoteModal()" class="text-gray-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-5">
            <p class="text-xs text-gray-500 mb-3">Dari: <span id="noteGuru" class="font-semibold text-gray-300"></span></p>
            <div class="bg-amber-950/20 border border-amber-800/30 rounded-xl p-4">
                <p id="noteContent" class="text-sm text-gray-300 whitespace-pre-wrap"></p>
            </div>
        </div>
    </div>
</div>

<style>
    body { background:#030712; }
    .th { padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
</style>

<script>
function playAudio(id) {
    document.getElementById('audioSource').src = `/admin/monitoring/setoran/${id}/audio`;
    document.getElementById('modalAudioPlayer').load();
    document.getElementById('audioModal').classList.remove('hidden');
    document.getElementById('modalAudioPlayer').play();
}
function closeAudioModal() {
    document.getElementById('modalAudioPlayer').pause();
    document.getElementById('audioModal').classList.add('hidden');
}
function viewPDF(id) {
    const url = `/admin/monitoring/setoran/${id}/pdf`;
    document.getElementById('pdfFrame').src = url;
    document.getElementById('downloadPdfBtn').href = url;
    document.getElementById('pdfModal').classList.remove('hidden');
}
function closePDFModal() { document.getElementById('pdfFrame').src = ''; document.getElementById('pdfModal').classList.add('hidden'); }
function showNote(note, guru) {
    document.getElementById('noteContent').textContent = note;
    document.getElementById('noteGuru').textContent = guru;
    document.getElementById('noteModal').classList.remove('hidden');
}
function closeNoteModal() { document.getElementById('noteModal').classList.add('hidden'); }
document.getElementById('audioModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) closeAudioModal(); });
document.getElementById('pdfModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) closePDFModal(); });
document.getElementById('noteModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) closeNoteModal(); });
document.addEventListener('keydown', e => { if(e.key==='Escape') { closeAudioModal(); closePDFModal(); closeNoteModal(); } });
</script>
@endsection
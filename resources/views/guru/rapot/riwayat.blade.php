@extends('layouts.app')

@section('content')
<div class="pb-10">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('guru.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate">Riwayat Santri</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Rekap santri yang naik/pindah, dikelompokkan per tahun ajaran</p>
            </div>
        </div>

        @if($ringkasan->count() > 0)
        <div class="space-y-2">
            @foreach($ringkasan as $r)
            <a href="{{ $r['tahun_ajaran_id'] ? route('guru.riwayat-santri.detail', $r['tahun_ajaran_id']) : '#' }}"
               class="flex items-center gap-3 px-4 py-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm dark:shadow-none hover:border-indigo-300 dark:hover:border-indigo-700/50 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/15 border border-indigo-200 dark:border-indigo-600/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $r['nama'] }}
                        @if($r['semester'])
                        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500">· {{ $r['semester'] == 2 ? 'Genap' : 'Ganjil' }}</span>
                        @endif
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $r['jumlah'] }} santri</span> naik/pindah
                        @if($r['terakhir'])
                        · terakhir {{ \Illuminate\Support\Carbon::parse($r['terakhir'])->translatedFormat('d M Y') }}
                        @endif
                    </p>
                </div>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-700 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada riwayat santri</p>
            <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Santri yang sudah Anda naikkan/pindahkan akan terkelompok per tahun ajaran di sini.</p>
        </div>
        @endif

    </div>
</div>
@endsection
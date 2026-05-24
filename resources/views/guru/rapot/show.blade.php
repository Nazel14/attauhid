@extends('layouts.app')

@section('content')
<div class="min-h-screen pb-10">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.rapot.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white truncate"> Rapot {{ $santri->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $santri->nis }} • {{ $santri->kelas->name ?? '-' }} • {{ $santri->guru->name ?? '-' }}</p>
            </div>
            
        </div>

        {{-- BADGE TAHUN AJARAN --}}
        @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-950/50 border border-indigo-800/40 rounded-xl text-xs mb-4">
            <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-white font-medium">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
            <span class="px-1.5 py-0.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-full text-[10px] font-semibold">Aktif</span>
        </div>
        @endif

        {{-- FILTER PERIODE --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-4">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter Periode
            </h3>
            <form method="GET" action="{{ route('guru.rapot.show', $santri->id) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="fi-lbl">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="fi-inp">
                </div>
                <div>
                    <label class="fi-lbl">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="fi-inp">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        Filter
                    </button>
                    <button type="button" onclick="previewPDF()" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-700 hover:bg-red-600 border border-red-700 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </button>
                </div>
            </form>
        </div>

        {{-- STATISTIK CARDS --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

            {{-- Card Statistik Setoran --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-blue-500/20 border border-blue-500/30 rounded-lg flex items-center justify-center text-sm">📖</span>
                    Statistik Setoran
                </h3>

                {{-- Progress Target --}}
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-500">Pencapaian Target Hafalan</span>
                        <span class="text-xl font-bold text-blue-400">{{ $statistikSetoran['persentase_target'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-400 h-3 rounded-full transition-all duration-500"
                             style="width: {{ $statistikSetoran['persentase_target'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-600 mt-1">
                        <span>Tercapai: {{ $statistikSetoran['total_juz'] }} Juz</span>
                        <span>Target: {{ $statistikSetoran['target_juz'] }} Juz</span>
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-blue-950/40 border border-blue-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-blue-400">{{ $statistikSetoran['total_setoran'] }}</div>
                        <div class="text-[11px] text-blue-300/70 mt-0.5">Total Setoran</div>
                    </div>
                    <div class="bg-emerald-950/40 border border-emerald-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-emerald-400">{{ $statistikSetoran['rata_nilai'] }}</div>
                        <div class="text-[11px] text-emerald-300/70 mt-0.5">Rata-rata Nilai</div>
                    </div>
                    <div class="bg-purple-950/40 border border-purple-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-purple-400">{{ $statistikSetoran['total_juz'] }}</div>
                        <div class="text-[11px] text-purple-300/70 mt-0.5">Total Juz</div>
                    </div>
                    <div class="bg-orange-950/40 border border-orange-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-orange-400">{{ number_format($statistikSetoran['total_kata']) }}</div>
                        <div class="text-[11px] text-orange-300/70 mt-0.5">Total Kata</div>
                    </div>
                </div>

                {{-- Kategori --}}
                <div class="pt-3 border-t border-gray-800">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Kategori Setoran</p>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between px-3 py-2 bg-emerald-950/30 border border-emerald-800/20 rounded-lg">
                            <span class="text-xs text-gray-300">Hafalan</span>
                            <span class="text-xs font-bold text-emerald-400">{{ $statistikSetoran['hafalan'] }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2 bg-blue-950/30 border border-blue-800/20 rounded-lg">
                            <span class="text-xs text-gray-300">Murajaah</span>
                            <span class="text-xs font-bold text-blue-400">{{ $statistikSetoran['murajaah'] }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2 bg-purple-950/30 border border-purple-800/20 rounded-lg">
                            <span class="text-xs text-gray-300">Tilawah</span>
                            <span class="text-xs font-bold text-purple-400">{{ $statistikSetoran['tilawah'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Statistik Presensi --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-emerald-500/20 border border-emerald-500/30 rounded-lg flex items-center justify-center text-sm">✅</span>
                    Statistik Presensi
                </h3>

                {{-- Progress Kehadiran --}}
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-500">Persentase Kehadiran</span>
                        <span class="text-xl font-bold text-emerald-400">{{ $statistikPresensi['persentase_kehadiran'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-3">
                        <div class="bg-gradient-to-r from-emerald-600 to-emerald-400 h-3 rounded-full transition-all duration-500"
                             style="width: {{ $statistikPresensi['persentase_kehadiran'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-600 mt-1">
                        <span>Hadir: {{ $statistikPresensi['hadir'] }}</span>
                        <span>Total: {{ $statistikPresensi['total_hari'] }} hari</span>
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-emerald-950/40 border border-emerald-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-emerald-400">{{ $statistikPresensi['hadir'] }}</div>
                        <div class="text-[11px] text-emerald-300/70 mt-0.5">Hadir</div>
                    </div>
                    <div class="bg-blue-950/40 border border-blue-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-blue-400">{{ $statistikPresensi['izin'] }}</div>
                        <div class="text-[11px] text-blue-300/70 mt-0.5">Izin</div>
                    </div>
                    <div class="bg-amber-950/40 border border-amber-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-amber-400">{{ $statistikPresensi['sakit'] }}</div>
                        <div class="text-[11px] text-amber-300/70 mt-0.5">Sakit</div>
                    </div>
                    <div class="bg-red-950/40 border border-red-800/30 rounded-xl p-3">
                        <div class="text-2xl font-bold text-red-400">{{ $statistikPresensi['alpha'] }}</div>
                        <div class="text-[11px] text-red-300/70 mt-0.5">Alpha</div>
                    </div>
                </div>

                {{-- Distribusi --}}
                <div class="pt-3 border-t border-gray-800">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Distribusi Kehadiran</p>
                    @php
                        $totalHari = $statistikPresensi['total_hari'];
                        $pctHadir  = $totalHari > 0 ? round(($statistikPresensi['hadir']  / $totalHari) * 100, 1) : 0;
                        $pctIzin   = $totalHari > 0 ? round(($statistikPresensi['izin']   / $totalHari) * 100, 1) : 0;
                        $pctSakit  = $totalHari > 0 ? round(($statistikPresensi['sakit']  / $totalHari) * 100, 1) : 0;
                        $pctAlpha  = $totalHari > 0 ? round(($statistikPresensi['alpha']  / $totalHari) * 100, 1) : 0;
                    @endphp
                    <div class="space-y-2">
                        @foreach([
                            ['label'=>'Hadir',  'val'=>$statistikPresensi['hadir'],  'pct'=>$pctHadir,  'bar'=>'bg-emerald-500', 'text'=>'text-emerald-400'],
                            ['label'=>'Izin',   'val'=>$statistikPresensi['izin'],   'pct'=>$pctIzin,   'bar'=>'bg-blue-500',    'text'=>'text-blue-400'],
                            ['label'=>'Sakit',  'val'=>$statistikPresensi['sakit'],  'pct'=>$pctSakit,  'bar'=>'bg-amber-500',   'text'=>'text-amber-400'],
                            ['label'=>'Alpha',  'val'=>$statistikPresensi['alpha'],  'pct'=>$pctAlpha,  'bar'=>'bg-red-500',     'text'=>'text-red-400'],
                        ] as $item)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] text-gray-500">{{ $item['label'] }}</span>
                                <span class="text-[11px] font-bold {{ $item['text'] }}">{{ $item['val'] }} ({{ $item['pct'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-800 rounded-full h-1.5">
                                <div class="{{ $item['bar'] }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $item['pct'] }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3 px-3 py-2 bg-indigo-950/40 border border-indigo-800/30 rounded-xl flex items-center justify-between">
                        <span class="text-xs font-semibold text-indigo-300">Total Hari Presensi</span>
                        <span class="text-xl font-bold text-indigo-400">{{ $statistikPresensi['total_hari'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIWAYAT SETORAN --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden mb-4">
            <div class="px-4 py-3 border-b border-gray-800">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-6 h-6 bg-purple-500/20 border border-purple-500/30 rounded-lg flex items-center justify-center text-sm">📚</span>
                    Riwayat Setoran Berdasarkan Kategori
                </h3>
            </div>

            @if(count($setorans) > 0)
            <div class="p-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    {{-- HAFALAN --}}
                    @if(isset($setorans['Hafalan']) && count($setorans['Hafalan']) > 0)
                    <div class="border border-gray-800 rounded-xl overflow-hidden">
                        <div class="bg-emerald-600/20 border-b border-emerald-700/30 px-4 py-2.5 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-emerald-400">Hafalan</h4>
                            <span class="text-[10px] bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-2 py-0.5 rounded-full font-semibold">{{ count($setorans['Hafalan']) }} surat</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-800/50">
                                    <tr>
                                        <th class="th2">No</th>
                                        <th class="th2">Surah & Ayat</th>
                                        <th class="th2 text-center">Jml</th>
                                        <th class="th2 text-center">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody id="rtbl_hafalan" class="divide-y divide-gray-800/50">
                                    @php $no = 1; @endphp
                                    @foreach($setorans['Hafalan'] as $suratKey => $data)
                                    <tr class="rpgrow-hafalan hover:bg-gray-800/30 transition-colors">
                                        <td class="px-3 py-2.5 text-xs text-gray-600">{{ $no++ }}</td>
                                        <td class="px-3 py-2.5 text-xs text-gray-200">
                                            <span class="font-semibold">{{ $data['surat_start'] }}</span>
                                            @if($data['surat_start'] != $data['surat_end']) - <span class="font-semibold">{{ $data['surat_end'] }}</span>@endif
                                            <span class="text-gray-600 ml-1">({{ $data['ayat_start_min'] }}-{{ $data['ayat_end_max'] }})</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="chip bg-indigo-950/50 text-indigo-400 border-indigo-800/30">{{ $data['jumlah_setoran'] }}x</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="chip {{ $data['rata_nilai'] >= 90 ? 'bg-emerald-950/50 text-emerald-400 border-emerald-800/30' : ($data['rata_nilai'] >= 75 ? 'bg-blue-950/50 text-blue-400 border-blue-800/30' : 'bg-amber-950/50 text-amber-400 border-amber-800/30') }}">
                                                {{ $data['rata_nilai'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="rpg_hafalan" class="px-4 py-2 border-t border-gray-800 bg-gray-800/20"></div>
                    </div>
                    @else
                    <div class="border border-gray-800 rounded-xl p-8 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-10 h-10 text-gray-700 mb-2 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-xs text-gray-600">Tidak ada data hafalan</p>
                        </div>
                    </div>
                    @endif

                    {{-- MURAJAAH + TILAWAH --}}
                    <div class="space-y-4">

                        @if(isset($setorans['Murajaah']) && count($setorans['Murajaah']) > 0)
                        <div class="border border-gray-800 rounded-xl overflow-hidden">
                            <div class="bg-blue-600/20 border-b border-blue-700/30 px-4 py-2.5 flex items-center justify-between">
                                <h4 class="text-sm font-bold text-blue-400">Murajaah</h4>
                                <span class="text-[10px] bg-blue-500/20 border border-blue-500/30 text-blue-400 px-2 py-0.5 rounded-full font-semibold">{{ count($setorans['Murajaah']) }} surat</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-800/50">
                                        <tr>
                                            <th class="th2">No</th>
                                            <th class="th2">Surah & Ayat</th>
                                            <th class="th2 text-center">Jml</th>
                                            <th class="th2 text-center">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rtbl_murajaah" class="divide-y divide-gray-800/50">
                                        @php $no = 1; @endphp
                                        @foreach($setorans['Murajaah'] as $suratKey => $data)
                                        <tr class="rpgrow-murajaah hover:bg-gray-800/30 transition-colors">
                                            <td class="px-3 py-2.5 text-xs text-gray-600">{{ $no++ }}</td>
                                            <td class="px-3 py-2.5 text-xs text-gray-200">
                                                <span class="font-semibold">{{ $data['surat_start'] }}</span>
                                                @if($data['surat_start'] != $data['surat_end']) - <span class="font-semibold">{{ $data['surat_end'] }}</span>@endif
                                                <span class="text-gray-600 ml-1">({{ $data['ayat_start_min'] }}-{{ $data['ayat_end_max'] }})</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="chip bg-indigo-950/50 text-indigo-400 border-indigo-800/30">{{ $data['jumlah_setoran'] }}x</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="chip {{ $data['rata_nilai'] >= 90 ? 'bg-emerald-950/50 text-emerald-400 border-emerald-800/30' : ($data['rata_nilai'] >= 75 ? 'bg-blue-950/50 text-blue-400 border-blue-800/30' : 'bg-amber-950/50 text-amber-400 border-amber-800/30') }}">
                                                    {{ $data['rata_nilai'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="rpg_murajaah" class="px-4 py-2 border-t border-gray-800 bg-gray-800/20"></div>
                        </div>
                        @else
                        <div class="border border-gray-800 rounded-xl p-6 text-center">
                            <svg class="w-8 h-8 text-gray-700 mb-1.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-xs text-gray-600">Tidak ada data murajaah</p>
                        </div>
                        @endif

                        @if(isset($setorans['Tilawah']) && count($setorans['Tilawah']) > 0)
                        <div class="border border-gray-800 rounded-xl overflow-hidden">
                            <div class="bg-purple-600/20 border-b border-purple-700/30 px-4 py-2.5 flex items-center justify-between">
                                <h4 class="text-sm font-bold text-purple-400">Tilawah</h4>
                                <span class="text-[10px] bg-purple-500/20 border border-purple-500/30 text-purple-400 px-2 py-0.5 rounded-full font-semibold">{{ count($setorans['Tilawah']) }} surat</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-800/50">
                                        <tr>
                                            <th class="th2">No</th>
                                            <th class="th2">Surah & Ayat</th>
                                            <th class="th2 text-center">Jml</th>
                                            <th class="th2 text-center">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rtbl_tilawah" class="divide-y divide-gray-800/50">
                                        @php $no = 1; @endphp
                                        @foreach($setorans['Tilawah'] as $suratKey => $data)
                                        <tr class="rpgrow-tilawah hover:bg-gray-800/30 transition-colors">
                                            <td class="px-3 py-2.5 text-xs text-gray-600">{{ $no++ }}</td>
                                            <td class="px-3 py-2.5 text-xs text-gray-200">
                                                <span class="font-semibold">{{ $data['surat_start'] }}</span>
                                                @if($data['surat_start'] != $data['surat_end']) - <span class="font-semibold">{{ $data['surat_end'] }}</span>@endif
                                                <span class="text-gray-600 ml-1">({{ $data['ayat_start_min'] }}-{{ $data['ayat_end_max'] }})</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="chip bg-indigo-950/50 text-indigo-400 border-indigo-800/30">{{ $data['jumlah_setoran'] }}x</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="chip {{ $data['rata_nilai'] >= 90 ? 'bg-emerald-950/50 text-emerald-400 border-emerald-800/30' : ($data['rata_nilai'] >= 75 ? 'bg-blue-950/50 text-blue-400 border-blue-800/30' : 'bg-amber-950/50 text-amber-400 border-amber-800/30') }}">
                                                    {{ $data['rata_nilai'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="rpg_tilawah" class="px-4 py-2 border-t border-gray-800 bg-gray-800/20"></div>
                        </div>
                        @else
                        <div class="border border-gray-800 rounded-xl p-6 text-center">
                            <svg class="w-8 h-8 text-gray-700 mb-1.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-xs text-gray-600">Tidak ada data tilawah</p>
                        </div>
                        @endif

                    </div>{{-- end kolom kanan --}}
                </div>
            </div>
            @else
            <div class="text-center py-16">
                <svg class="w-12 h-12 text-gray-700 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-500">Tidak ada data setoran dalam periode ini</p>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- PDF PREVIEW MODAL --}}
<div id="pdfPreviewModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 overflow-y-auto">
    <div class="min-h-screen px-3 py-6 sm:px-6 sm:py-8">
        <div class="relative bg-gray-900 border border-gray-700 rounded-xl shadow-2xl max-w-6xl mx-auto">
            <div class="sticky top-0 z-10 flex items-center justify-between p-4 border-b border-gray-800 bg-gray-900 rounded-t-xl">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Preview PDF — {{ $santri->name }}
                </h3>
                <div class="flex items-center gap-2">
                    <a href="{{ route('guru.rapot.pdf', ['santri' => $santri->id, 'tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalAkhir]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </a>
                    <button onclick="closePDFPreview()" class="w-7 h-7 rounded-lg bg-gray-800 hover:bg-gray-700 flex items-center justify-center text-gray-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-4">
                <iframe id="pdfFrame"
                        src="{{ route('guru.rapot.pdf', ['santri' => $santri->id, 'tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalAkhir, 'preview' => 1]) }}"
                        class="w-full rounded-lg border border-gray-700"
                        style="height: 75vh;">
                </iframe>
            </div>
        </div>
    </div>
</div>

<style>
    html, body { background:#030712; min-height:100%; }
    .th2 { padding:10px 12px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#9ca3af; margin-bottom:5px; }
    .fi-inp { width:100%; background:#0d1117; border:1px solid #374151; border-radius:10px; color:#e5e7eb; font-size:13px; padding:9px 12px; outline:none; transition:border-color .15s; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
        opacity: 0.7;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
</style>

<script>
const pgState = {};

function initPagination(tbodyId, rowClass, pgId, perPage) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('.' + rowClass));
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / perPage);
    pgState[tbodyId] = { rows, totalRows, totalPages, perPage, currentPage: 1 };
    if (totalPages <= 1) return;
    showPage(tbodyId, pgId, 1);
}

function showPage(tbodyId, pgId, page) {
    const s = pgState[tbodyId];
    if (!s || page < 1 || page > s.totalPages) return;
    s.currentPage = page;
    const start = (page - 1) * s.perPage;
    const end   = start + s.perPage;
    s.rows.forEach((row, i) => { row.style.display = (i >= start && i < end) ? '' : 'none'; });
    renderPagination(tbodyId, pgId);
}

function renderPagination(tbodyId, pgId) {
    const s = pgState[tbodyId];
    const container = document.getElementById(pgId);
    if (!s || !container) return;
    const { currentPage, totalPages, totalRows, perPage } = s;
    const from = Math.min((currentPage - 1) * perPage + 1, totalRows);
    const to   = Math.min(currentPage * perPage, totalRows);
    const btnClass    = 'px-2.5 py-1 text-xs rounded-md font-medium transition-colors';
    const activeBtn   = `${btnClass} bg-indigo-600 text-white`;
    const normalBtn   = `${btnClass} text-gray-400 hover:bg-gray-700`;
    const disabledBtn = 'px-2 py-1 text-xs rounded-md text-gray-700 cursor-not-allowed';
    const navBtn      = 'px-2 py-1 text-xs rounded-md text-gray-400 hover:bg-gray-700 cursor-pointer';
    let html = `<div class="flex items-center justify-between">
        <span class="text-[11px] text-gray-600">${from}–${to} dari ${totalRows}</span>
        <div class="flex items-center gap-1">`;
    html += currentPage === 1
        ? `<button disabled class="${disabledBtn}">‹ Prev</button>`
        : `<button onclick="showPage('${tbodyId}','${pgId}',${currentPage - 1})" class="${navBtn}">‹ Prev</button>`;
    let startP = Math.max(1, currentPage - 2);
    let endP   = Math.min(totalPages, startP + 4);
    if (endP - startP < 4) startP = Math.max(1, endP - 4);
    if (startP > 1) { html += `<button onclick="showPage('${tbodyId}','${pgId}',1)" class="${normalBtn}">1</button>`; if (startP > 2) html += `<span class="text-xs text-gray-600">…</span>`; }
    for (let i = startP; i <= endP; i++) html += `<button onclick="showPage('${tbodyId}','${pgId}',${i})" class="${i === currentPage ? activeBtn : normalBtn}">${i}</button>`;
    if (endP < totalPages) { if (endP < totalPages - 1) html += `<span class="text-xs text-gray-600">…</span>`; html += `<button onclick="showPage('${tbodyId}','${pgId}',${totalPages})" class="${normalBtn}">${totalPages}</button>`; }
    html += currentPage === totalPages
        ? `<button disabled class="${disabledBtn}">Next ›</button>`
        : `<button onclick="showPage('${tbodyId}','${pgId}',${currentPage + 1})" class="${navBtn}">Next ›</button>`;
    html += `</div></div>`;
    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function () {
    initPagination('rtbl_hafalan',  'rpgrow-hafalan',  'rpg_hafalan',  15);
    initPagination('rtbl_murajaah', 'rpgrow-murajaah', 'rpg_murajaah', 6);
    initPagination('rtbl_tilawah',  'rpgrow-tilawah',  'rpg_tilawah',  6);
});

function previewPDF() {
    document.getElementById('pdfPreviewModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closePDFPreview() {
    document.getElementById('pdfPreviewModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('pdfPreviewModal')?.addEventListener('click', function(e) { if (e.target === this) closePDFPreview(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePDFPreview(); });
});
</script>
@endsection
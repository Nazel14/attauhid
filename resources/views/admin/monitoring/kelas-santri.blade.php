@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-950 pb-10">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.monitoring.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white truncate"> {{ $kelas->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Guru: <span class="text-indigo-400 font-semibold">{{ $kelas->guru->name ?? 'Belum ada guru' }}</span> · <span class="text-white font-semibold">{{ $santriList->count() }}</span> Santri</p>
            </div>
        </div>

        {{-- PERIOD INFO --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-3 mb-5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-xs text-gray-400">Periode: <strong class="text-white">{{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }}</strong> — <strong class="text-white">{{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}</strong></span>
            </div>
        </div>

        {{-- SUMMARY STATS --}}
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-4 text-center">
                <p class="text-[11px] text-gray-500 mb-1">Total Santri</p>
                <p class="text-2xl font-black text-white">{{ $santriList->count() }}</p>
            </div>
            <div class="bg-gray-900 border border-blue-800/30 rounded-xl px-4 py-4 text-center">
                <p class="text-[11px] text-gray-500 mb-1">Ikhwan</p>
                <p class="text-2xl font-black text-blue-400">{{ $santriList->where('gender', 'Laki-laki')->count() }}</p>
            </div>
            <div class="bg-gray-900 border border-pink-800/30 rounded-xl px-4 py-4 text-center">
                <p class="text-[11px] text-gray-500 mb-1">Akhwat</p>
                <p class="text-2xl font-black text-pink-400">{{ $santriList->where('gender', 'Perempuan')->count() }}</p>
            </div>
        </div>

        {{-- SANTRI TABLE --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-800">
                <h3 class="text-sm font-bold text-white">Daftar Santri</h3>
            </div>

            {{-- Mobile cards --}}
            <div class="lg:hidden divide-y divide-gray-800/50">
                @forelse($santriList as $index => $santri)
                @php
    $totalHafalan = $santri->setorans()
        ->where('kategori_setoran', 'Hafalan')
        ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
        ->sum('total_juz');
    $progressPercent = $santri->target_juz > 0 
        ? ($totalHafalan / $santri->target_juz) * 100 
        : 0;
    $isPerempuan = $santri->gender === 'Perempuan';
@endphp
                <div class="px-4 py-3.5">
                    <div class="flex items-center gap-3 mb-2.5">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ $santri->name }}</p>
                            <p class="text-[11px] text-gray-600 font-mono">{{ $santri->nis }} · Target {{ number_format($santri->target_juz, 1) }} Juz</p>
                        </div>
                        <a href="{{ route('admin.monitoring.santri', $santri->id) }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
                           class="flex-shrink-0 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Detail</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-800 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all
                                @if($progressPercent >= 80) bg-emerald-500
                                @elseif($progressPercent >= 50) bg-blue-500
                                @elseif($progressPercent >= 30) bg-amber-500
                                @else bg-red-500 @endif"
                                style="width: {{ min($progressPercent, 100) }}%"></div>
                        </div>
                        <span class="text-[11px] font-bold flex-shrink-0
                            @if($progressPercent >= 80) text-emerald-400
                            @elseif($progressPercent >= 50) text-blue-400
                            @elseif($progressPercent >= 30) text-amber-400
                            @else text-red-400 @endif">
                            {{ number_format(min($progressPercent, 100), 0) }}%
                        </span>
                    </div>
                </div>
                @empty
                <div class="py-12 text-center">
                    <p class="text-sm text-gray-500">Tidak ada santri di kelas ini</p>
                </div>
                @endforelse
            </div>

            {{-- Desktop table --}}
            <table class="min-w-full hidden lg:table">
                <thead>
                    <tr class="border-b border-gray-800 bg-gray-800/40">
                        <th class="th w-12">No</th>
                        <th class="th">NIS</th>
                        <th class="th">Nama Santri</th>
                        <th class="th text-center">Gender</th>
                        <th class="th text-center">Target Juz</th>
                        <th class="th text-center">Progress</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($santriList as $index => $santri)
                    @php
    $totalHafalan = $santri->setorans()
        ->where('kategori_setoran', 'Hafalan')
        ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
        ->sum('total_juz');
    $progressPercent = $santri->target_juz > 0 
        ? ($totalHafalan / $santri->target_juz) * 100 
        : 0;
    $isPerempuan = $santri->gender === 'Perempuan';
@endphp
                    <tr class="hover:bg-gray-800/25 transition-colors">
                        <td class="px-5 py-3.5 text-sm text-gray-600">{{ $index+1 }}</td>
                        <td class="px-5 py-3.5 text-sm font-mono text-gray-400">{{ $santri->nis }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                
                                <span class="text-sm font-medium text-gray-200">{{ $santri->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($isPerempuan)
                                <span class="chip bg-pink-950/50 text-pink-400 border-pink-800/30"> Akhwat</span>
                            @else
                                <span class="chip bg-blue-950/50 text-blue-400 border-blue-800/30"> Ikhwan</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="chip bg-emerald-950/50 text-emerald-400 border-emerald-800/30">{{ number_format($santri->target_juz, 2) }} Juz</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-20 bg-gray-800 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full
                                        @if($progressPercent >= 80) bg-emerald-500
                                        @elseif($progressPercent >= 50) bg-blue-500
                                        @elseif($progressPercent >= 30) bg-amber-500
                                        @else bg-red-500 @endif"
                                        style="width: {{ min($progressPercent, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold
                                    @if($progressPercent >= 80) text-emerald-400
                                    @elseif($progressPercent >= 50) text-blue-400
                                    @elseif($progressPercent >= 30) text-amber-400
                                    @else text-red-400 @endif">
                                    {{ number_format(min($progressPercent, 100), 0) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <a href="{{ route('admin.monitoring.santri', $santri->id) }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
                               class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/40 border border-indigo-600/30 text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="py-12 text-center text-sm text-gray-500">Tidak ada santri di kelas ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

<style>
    body { background:#030712; }
    .th { padding:10px 20px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
</style>
@endsection
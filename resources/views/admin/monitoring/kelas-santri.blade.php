@extends('layouts.app')

@section('content')
<div class="pb-10">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.monitoring.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> {{ $kelas->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Guru: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $kelas->guru->name ?? 'Belum ada guru' }}</span> · <span class="text-gray-900 dark:text-white font-semibold">{{ $santriList->count() }}</span> Santri</p>
            </div>
        </div>

        {{-- PERIOD INFO --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 mb-5 flex items-center justify-between gap-3 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-xs text-gray-500 dark:text-gray-400">Periode: <strong class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }}</strong> — <strong class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}</strong></span>
            </div>
        </div>

        {{-- SUMMARY STATS --}}
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-4 text-center shadow-sm dark:shadow-none">
                <p class="text-[11px] text-gray-500 mb-1">Total Santri</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $santriList->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-4 text-center shadow-sm dark:shadow-none">
                <p class="text-[11px] text-gray-500 mb-1">Ikhwan</p>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $santriList->where('gender', 'Laki-laki')->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-pink-200 dark:border-pink-800/30 rounded-xl px-4 py-4 text-center shadow-sm dark:shadow-none">
                <p class="text-[11px] text-gray-500 mb-1">Akhwat</p>
                <p class="text-2xl font-black text-pink-600 dark:text-pink-400">{{ $santriList->where('gender', 'Perempuan')->count() }}</p>
            </div>
        </div>

        {{-- SANTRI TABLE --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Daftar Santri</h3>
            </div>

            {{-- Mobile cards --}}
            <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700/50">
                @forelse($santriList as $index => $santri)
                @php
    $isIqroSantri = ($santri->target_metode?->value ?? 'quran') === 'iqro';
    $isPerempuan  = $santri->gender === 'Perempuan';

    if ($isIqroSantri) {
        $targetJilid       = (int)($santri->target_jilid ?? 1);
        $totalHalamanJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);
        $totalHafalan      = 0;
        $setoransIqro = $santri->setorans()
            ->where('kategori_setoran', 'Hafalan')
            ->when($santri->kelas_id, fn($q) => $q->where('kelas_id', $santri->kelas_id))
            ->get();
        foreach ($setoransIqro as $s) {
            // Hanya hitung halaman dari jilid target sekarang,
            // agar progress reset ke nol saat naik jilid.
            if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
            if ((int)$mj[1] !== $targetJilid) continue;
            $h1 = (int)$s->ayat_start;
            $h2 = (int)($s->ayat_end ?: $s->ayat_start);
            if ($h1 > 0 && $h2 >= $h1) $totalHafalan += ($h2 - $h1 + 1);
        }
        $progressPercent = $totalHalamanJilid > 0
            ? min(100, ($totalHafalan / $totalHalamanJilid) * 100)
            : 0;
    } else {
        $totalHafalan    = $santri->setorans()
            ->where('kategori_setoran', 'Hafalan')
            ->when($santri->kelas_id, fn($q) => $q->where('kelas_id', $santri->kelas_id))
            ->sum('total_juz');
        $progressPercent = $santri->target_juz > 0
            ? ($totalHafalan / $santri->target_juz) * 100
            : 0;
    }
@endphp
                <div class="px-4 py-3.5">
                    <div class="flex items-center gap-3 mb-2.5">
                        @if($santri->user && $santri->user->profile_photo)
                            <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                        @else
                            <span class="w-10 h-10 rounded-full {{ $isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-sm font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-600 font-mono">
    {{ $santri->nis }} ·
    @if($isIqroSantri)
        Iqro Jilid {{ $santri->target_jilid ?? 1 }}
    @else
        Target {{ number_format($santri->target_juz, 1) }} Juz
    @endif
</p>
                        </div>
                        <a href="{{ route('admin.monitoring.santri', $santri->id) }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
                           class="flex-shrink-0 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Detail</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all
                                @if($progressPercent >= 80) bg-emerald-500
                                @elseif($progressPercent >= 50) bg-blue-500
                                @elseif($progressPercent >= 30) bg-amber-500
                                @else bg-red-500 @endif"
                                style="width: {{ min($progressPercent, 100) }}%"></div>
                        </div>
                        <span class="text-[11px] font-bold flex-shrink-0
                            @if($progressPercent >= 80) text-emerald-600 dark:text-emerald-400
                            @elseif($progressPercent >= 50) text-blue-600 dark:text-blue-400
                            @elseif($progressPercent >= 30) text-amber-600 dark:text-amber-400
                            @else text-red-600 dark:text-red-400 @endif">
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
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                        <th class="th w-12">No</th>
                        <th class="th">NIS</th>
                        <th class="th">Nama Santri</th>
                        <th class="th" style="text-align:right; padding-right:95px;">Gender</th>
                        <th class="th" style="text-align:right; padding-right:90px;">Target Juz</th>
                        <th class="th" style="text-align:right; padding-right:70px;">Progress</th>
                        <th class="th" style="text-align:right; padding-right:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse($santriList as $index => $santri)
                    @php
    $isIqroSantri = ($santri->target_metode?->value ?? 'quran') === 'iqro';
    $isPerempuan  = $santri->gender === 'Perempuan';

    if ($isIqroSantri) {
        $targetJilid       = (int)($santri->target_jilid ?? 1);
        $totalHalamanJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);
        $totalHafalan      = 0;
        $setoransIqro = $santri->setorans()
            ->where('kategori_setoran', 'Hafalan')
            ->when($santri->kelas_id, fn($q) => $q->where('kelas_id', $santri->kelas_id))
            ->get();
        foreach ($setoransIqro as $s) {
            // Hanya hitung halaman dari jilid target sekarang,
            // agar progress reset ke nol saat naik jilid.
            if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
            if ((int)$mj[1] !== $targetJilid) continue;
            $h1 = (int)$s->ayat_start;
            $h2 = (int)($s->ayat_end ?: $s->ayat_start);
            if ($h1 > 0 && $h2 >= $h1) $totalHafalan += ($h2 - $h1 + 1);
        }
        $progressPercent = $totalHalamanJilid > 0
            ? min(100, ($totalHafalan / $totalHalamanJilid) * 100)
            : 0;
    } else {
        $totalHafalan    = $santri->setorans()
            ->where('kategori_setoran', 'Hafalan')
            ->when($santri->kelas_id, fn($q) => $q->where('kelas_id', $santri->kelas_id))
            ->sum('total_juz');
        $progressPercent = $santri->target_juz > 0
            ? ($totalHafalan / $santri->target_juz) * 100
            : 0;
    }
@endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors">
                        <td class="px-5 py-3.5 text-sm text-gray-500 dark:text-gray-600">{{ $index+1 }}</td>
                        <td class="px-5 py-3.5 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $santri->nis }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                @if($santri->user && $santri->user->profile_photo)
                                    <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-9 h-9 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                @else
                                    <span class="w-9 h-9 rounded-full {{ $isPerempuan ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-xs font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                                @endif
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $santri->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($isPerempuan)
                                <span class="chip bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30"> Akhwat</span>
                            @else
                                <span class="chip bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30"> Ikhwan</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($isIqroSantri)
                                <span class="chip bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30">Iqro Jilid {{ $santri->target_jilid ?? 1 }}</span>
                            @else
                                <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">{{ number_format($santri->target_juz, 2) }} Juz</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full
                                        @if($progressPercent >= 80) bg-emerald-500
                                        @elseif($progressPercent >= 50) bg-blue-500
                                        @elseif($progressPercent >= 30) bg-amber-500
                                        @else bg-red-500 @endif"
                                        style="width: {{ min($progressPercent, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold
                                    @if($progressPercent >= 80) text-emerald-600 dark:text-emerald-400
                                    @elseif($progressPercent >= 50) text-blue-600 dark:text-blue-400
                                    @elseif($progressPercent >= 30) text-amber-600 dark:text-amber-400
                                    @else text-red-600 dark:text-red-400 @endif">
                                    {{ number_format(min($progressPercent, 100), 0) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <a href="{{ route('admin.monitoring.santri', $santri->id) }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
                               class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Detail</a>
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
    .th { padding:10px 20px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>
@endsection
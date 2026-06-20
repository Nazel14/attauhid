@extends('layouts.app')

@section('content')
<div class="pb-10"
     x-data="{
         selectedPeriod: '{{ request('period', '30') }}',
         isDropdownOpen: false,
         periods: [
             { label: '1 Hari Terakhir', value: '1' },
             { label: '1 Minggu Terakhir', value: '7' },
             { label: '1 Bulan Terakhir', value: '30' },
             { label: '2 Bulan Terakhir', value: '60' },
             { label: '3 Bulan Terakhir', value: '90' },
             { label: '6 Bulan Terakhir', value: '180' },
             { label: '12 Bulan Terakhir', value: '365' }
         ],
         get currentPeriodLabel() {
             const period = this.periods.find(p => p.value === this.selectedPeriod);
             return period ? period.label : '1 Bulan Terakhir';
         },
         selectPeriod(value) {
             this.selectedPeriod = value;
             this.isDropdownOpen = false;
             this.applyPeriod();
         },
         applyPeriod() {
             const days = parseInt(this.selectedPeriod);
             const endDate = new Date();
             const startDate = new Date();
             startDate.setDate(endDate.getDate() - days);
             const fmt = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
             const url = new URL(window.location.href);
             url.searchParams.set('tanggal_mulai', fmt(startDate));
             url.searchParams.set('tanggal_akhir', fmt(endDate));
             url.searchParams.set('period', this.selectedPeriod);
             window.location.href = url.toString();
         }
     }"
     @click.away="isDropdownOpen = false">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white"> Monitoring Santri</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Overview setoran dan presensi santri</p>
            </div>
        </div>

        {{-- PERIOD FILTER --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 mb-5 shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Periode: <strong class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }}</strong> — <strong class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}</strong></span>
                </div>
                <div class="relative">
                    <button @click="isDropdownOpen = !isDropdownOpen"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        <span x-text="currentPeriodLabel"></span>
                        <svg class="w-3 h-3 transition-transform" :class="isDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="isDropdownOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 mt-1.5 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl z-20 overflow-hidden"
                         style="display:none;">
                        <template x-for="period in periods" :key="period.value">
                            <button @click="selectPeriod(period.value)"
                                :class="selectedPeriod === period.value ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                <span x-text="period.label"></span>
                                <svg x-show="selectedPeriod === period.value" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- KELAS TABLE --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white"> Monitoring per Guru & Kelas</h3>
            </div>

            {{-- Mobile cards --}}
            <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700/50">
                @forelse($statistikKelas as $index => $stat)
                <div class="px-4 py-3.5 flex items-center gap-3">
                    <div class="w-8 h-8 flex-shrink-0 rounded-lg bg-indigo-100 border border-indigo-300 dark:bg-indigo-500/15 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-black">{{ $index+1 }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $stat['kelas'] }}</p>
                        <p class="text-[11px] text-gray-500 truncate">{{ $stat['guru'] ?? '-' }} · <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $stat['total_santri'] }}</span> santri</p>
                    </div>
                    <a href="{{ route('admin.monitoring.kelas', $stat['kelas_id']) }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
                       class="flex-shrink-0 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Detail →</a>
                </div>
                @empty
                <div class="py-12 text-center">
                    <p class="text-sm text-gray-500">Tidak ada data kelas</p>
                </div>
                @endforelse
            </div>

            {{-- Desktop table --}}
            <div class="hidden lg:block">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            <th class="th" style="width:56px">No</th>
                            <th class="th">Guru Pembimbing</th>
                            <th class="th">Kelas</th>
                            <th class="th" style="width:160px">Jumlah Santri</th>
                            <th class="th text-center" style="width:160px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                        @forelse($statistikKelas as $index => $stat)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors">
                            <td class="px-5 py-3.5 text-sm text-gray-500 dark:text-gray-600" style="width:56px">{{ $index+1 }}</td>
                            <td class="px-5 py-3.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $stat['guru'] ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $stat['kelas'] }}
                            </td>
                            <td class="px-5 py-3.5" style="width:160px">
                                <span class="chip bg-indigo-100 text-indigo-700 border-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-400 dark:border-indigo-800/30">{{ $stat['total_santri'] }} santri</span>
                            </td>
                            <td class="px-5 py-3.5 text-center" style="width:160px">
                                <a href="{{ route('admin.monitoring.kelas', $stat['kelas_id']) }}?tanggal_mulai={{ $tanggalMulai }}&tanggal_akhir={{ $tanggalAkhir }}&period={{ request('period', '30') }}"
                                   class="inline-flex items-center justify-center px-4 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors whitespace-nowrap">
                                    Lihat Detail →
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <p class="text-sm text-gray-500">Tidak ada data kelas</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    .th { padding:10px 20px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    [x-cloak] { display:none !important; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>
@endsection
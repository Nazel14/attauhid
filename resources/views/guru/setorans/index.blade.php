@extends('layouts.app')

@section('content')
<div class="pb-10">

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
    @if(session('error') || $errors->any())
    <div id="notif-err" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-red-600 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1">
                @if(session('error'))
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                @else
                    <p class="text-sm font-semibold mb-1">Terdapat kesalahan:</p>
                    <ul class="text-xs space-y-0.5 opacity-90">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
                @endif
            </div>
            <button onclick="document.getElementById('notif-err').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">

                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate">Daftar Santri — Setoran</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Kelas: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $kelas->name }}</span>
                    <span class="mx-1">•</span>
                    @if($totalIkhwan > 0 && $totalAkhwat > 0)
                        <span class="text-purple-600 dark:text-purple-400 font-semibold">Campuran</span>
                    @elseif($totalIkhwan > 0)
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">Ikhwan</span>
                    @elseif($totalAkhwat > 0)
                        <span class="text-pink-600 dark:text-pink-400 font-semibold">Akhwat</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('guru.input.create') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">Input Setoran</span>
                <span class="sm:hidden">Input</span>
            </a>
        </div>

        {{-- ====================================================== --}}
        {{-- MOBILE CARDS (< lg)                                     --}}
        {{-- ====================================================== --}}
        <div class="lg:hidden space-y-2">
            @forelse($santris as $index => $santri)
            @php
                $targetJuz    = $santri->target_juz ?? 1.00;
                $totalHafalan = $santri->total_hafalan_juz ?? 0;
                $pctM = $santri->juz_progress_percentage ?? 0;
                $barM = match(true) {
                    $pctM >= 80 => 'bg-emerald-500',
                    $pctM >= 50 => 'bg-blue-500',
                    $pctM >= 25 => 'bg-amber-500',
                    default     => 'bg-red-500'
                };
                $bdgM = match(true) {
                    $pctM >= 80 => 'bg-emerald-100 border-emerald-300 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400',
                    $pctM >= 50 => 'bg-blue-100 border-blue-300 text-blue-700 dark:bg-blue-950 dark:border-blue-800/50 dark:text-blue-400',
                    $pctM >= 25 => 'bg-amber-100 border-amber-300 text-amber-700 dark:bg-amber-950 dark:border-amber-800/50 dark:text-amber-400',
                    default     => 'bg-red-100 border-red-300 text-red-700 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400'
                };
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm dark:shadow-none">

                {{-- Row 1: nama + nomor --}}
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                        <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis }}</p>
                    </div>
                    <span class="text-[11px] text-gray-400 dark:text-gray-700 flex-shrink-0">#{{ $loop->iteration }}</span>
                </div>

                {{-- Row 2: 4-col chips --}}
                <div class="grid grid-cols-4 border-t border-gray-200 dark:border-gray-700/50">
                    <div class="px-2 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Gender</p>
                        @if($santri->gender == 'Laki-laki')
                        <p class="text-xs font-bold text-blue-500 dark:text-blue-400">L</p>
                        @else
                            <p class="text-xs font-bold text-pink-500 dark:text-pink-400">P</p>
                        @endif
                    </div>
                    <div class="px-2 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Hafalan</p>
                        <p class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $santri->total_hafalan }}</p>
                    </div>
                    <div class="px-2 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Murajaah</p>
                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $santri->total_murajaah }}</p>
                    </div>
                    <div class="px-2 py-2.5 text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Tilawah</p>
                        <p class="text-xs font-bold text-blue-500 dark:text-blue-400">{{ $santri->total_tilawah }}</p>
                    </div>
                </div>

                {{-- Row 3: progress --}}
                <div class="px-4 py-2.5 border-t border-gray-200 dark:border-gray-700/50">
                    @if(($santri->is_iqro ?? false) && !($santri->iqro_persen_diketahui ?? false))
                        <span class="text-[10px] text-gray-500 dark:text-gray-600 italic">Halaman Jilid {{ $santri->target_jilid ?? '-' }} belum diatur</span>
                    @else
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-1 w-24">
                                    <div class="{{ $barM }} h-1 rounded-full transition-all duration-500" style="width: {{ min($pctM, 100) }}%"></div>
                                </div>
                                @if($santri->is_iqro ?? false)
                                    <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ $santri->total_halaman_iqro ?? 0 }}/{{ $santri->total_halaman_jilid ?? 0 }} hal</span>
                                @else
                                    <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ number_format($santri->total_hafalan_juz ?? 0, 2) }}/{{ number_format($targetJuz, 2) }} juz</span>
                                @endif
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-lg border {{ $bdgM }}">{{ $pctM }}%</span>
                        </div>
                    @endif
                </div>

                {{-- Row 4: action --}}
                <div class="border-t border-gray-200 dark:border-gray-700/50">
                    <a href="{{ route('guru.setorans.detail', $santri->id) }}"
                       class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 active:bg-indigo-100 dark:active:bg-indigo-950/80 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Lihat Detail
                    </a>
                </div>

            </div>
            @empty
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                <div class="text-4xl mb-3">📖</div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Santri belum ditambahkan ke kelas ini</p>
            </div>
            @endforelse
        </div>

        {{-- ====================================================== --}}
        {{-- DESKTOP TABLE (≥ lg)                                    --}}
        {{-- ====================================================== --}}
        <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                        <th class="th">No</th>
                        <th class="th">NIS</th>
                        <th class="th">Nama Santri</th>
                        <th class="th">Gender</th>
                        <th class="th">Total Setoran</th>
                        <th class="th">Capaian</th>
                        <th class="th">Target</th>
                        <th class="th">Progress</th>
                        <th class="th">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse($santris as $index => $santri)
                    @php
                        $targetJuz    = $santri->target_juz ?? 1.00;
                        $totalHafalan = $santri->total_hafalan_juz ?? 0;
                        $pct2         = $santri->juz_progress_percentage ?? 0;
                        $bar2         = match(true) {
                            $pct2 >= 80 => 'bg-emerald-500',
                            $pct2 >= 50 => 'bg-blue-500',
                            $pct2 >= 25 => 'bg-amber-500',
                            default     => 'bg-red-500'
                        };
                        $txt2         = match(true) {
                            $pct2 >= 80 => 'text-emerald-600 dark:text-emerald-400',
                            $pct2 >= 50 => 'text-blue-600 dark:text-blue-400',
                            $pct2 >= 25 => 'text-amber-600 dark:text-amber-400',
                            default     => 'text-red-600 dark:text-red-400'
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors">

                        {{-- No --}}
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $santris->firstItem() + $index }}</td>

                        {{-- NIS --}}
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $santri->nis }}</td>

                        {{-- Nama --}}
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $santri->name }}</span>
                        </td>

                        {{-- Gender --}}
                        <td class="px-4 py-3">
                            @if($santri->gender == 'Laki-laki')
                                <span class="chip bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30">L</span>
                            @else
                                <span class="chip bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30">P</span>
                            @endif
                        </td>

                        {{-- Total Setoran --}}
                        <td class="px-4 py-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-purple-500 dark:text-purple-400/70 w-16">Hafalan:</span>
                                    <span class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $santri->total_hafalan }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-blue-500 dark:text-blue-400/70 w-16">Tilawah:</span>
                                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $santri->total_tilawah }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-emerald-500 dark:text-emerald-400/70 w-16">Murajaah:</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $santri->total_murajaah }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- Capaian --}}
                        <td class="px-4 py-3">
                            @if($santri->is_iqro ?? false)
                                <span class="chip bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30">📗 {{ $santri->total_halaman_iqro ?? 0 }} hal</span>
                            @else
                                <span class="chip bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-950/50 dark:text-purple-400 dark:border-purple-800/30">📖 {{ number_format($santri->total_hafalan_juz ?? 0, 2) }} juz</span>
                            @endif
                        </td>

                        {{-- Target --}}
                        <td class="px-4 py-3">
                            <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">
                                🎯 {{ $santri->target_display ?? '-' }}
                            </span>
                        </td>

                        {{-- Progress --}}
                        <td class="px-4 py-3">
                            @if($santri->is_iqro ?? false)
                                @if($santri->iqro_persen_diketahui ?? false)
                                    <div class="flex items-center gap-2 min-w-[160px]">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="{{ $bar2 }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $pct2 }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold {{ $txt2 }} w-9 text-right shrink-0">{{ $pct2 }}%</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-600 mt-1">(Hal {{ $santri->total_halaman_iqro ?? 0 }}/{{ $santri->total_halaman_jilid ?? 0 }})</p>
                                @else
                                    <span class="text-[10px] text-gray-500 dark:text-gray-600 italic">Halaman Jilid {{ $santri->target_jilid ?? '-' }} belum diatur</span>
                                @endif
                            @else
                                <div class="flex items-center gap-2 min-w-[160px]">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="{{ $bar2 }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($pct2, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $txt2 }} w-9 text-right shrink-0">{{ $pct2 }}%</span>
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-600 mt-1">({{ number_format($santri->total_hafalan_juz ?? 0, 2) }}/{{ number_format($targetJuz, 2) }} juz)</p>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3">
                            <a href="{{ route('guru.setorans.detail', $santri->id) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="py-16 text-center">
                        <div class="text-4xl mb-3">📖</div>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
                        <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Santri belum ditambahkan ke kelas ini</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($santris->hasPages())
        <div class="mt-5">{{ $santris->links() }}</div>
        @endif

    </div>
</div>

<style>
    .th { padding:12px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    @keyframes slide-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .animate-slide-in { animation:slide-in .25s ease-out; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const n = document.getElementById('notif-ok');
    if (n) setTimeout(() => { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); }, 5000);
});
</script>
@endsection
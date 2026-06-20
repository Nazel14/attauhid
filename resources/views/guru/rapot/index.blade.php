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

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> Rapot Santri</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Santri yang Anda ampu: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $santriList->count() }} Santri</span></p>
            </div>
            {{-- Search Bar --}}
            @if($kelas && $santriList->count() > 0)
            <div class="relative w-40 sm:w-64">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" id="searchSantri" placeholder="Cari nama / NIS..."
                       class="w-full pl-9 pr-8 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <button id="clearSearch" type="button" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-600 dark:hover:text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endif
        </div>

        @if($kelas)
            @if($santriList->count() > 0)

            {{-- MOBILE CARDS (< lg) --}}
            <div class="lg:hidden space-y-2" id="mobileList">
                @foreach($santriList as $index => $santri)
                @php
                    $persen    = $santri->hafalan_persen ?? 0;
                    $totalJuz  = $santri->hafalan_total_juz ?? 0;
                    $targetJuz = ($santri->target_juz ?? 0) > 0 ? $santri->target_juz : 30;
                    $barColor  = match(true) {
                        $persen >= 80 => 'bg-emerald-500',
                        $persen >= 50 => 'bg-blue-500',
                        $persen >= 25 => 'bg-amber-500',
                        default       => 'bg-red-500',
                    };
                    $badgeClass = match(true) {
                        $persen >= 80 => 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400',
                        $persen >= 50 => 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-950 dark:border-blue-800/50 dark:text-blue-400',
                        $persen >= 25 => 'bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950 dark:border-amber-800/50 dark:text-amber-400',
                        default       => 'bg-red-50 border-red-200 text-red-700 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400',
                    };
                @endphp
                <div class="santri-row rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden transition-all shadow-sm dark:shadow-none"
                     data-name="{{ strtolower($santri->name) }}"
                     data-nis="{{ strtolower($santri->nis ?? '') }}">

                    {{-- Row 1: name + nomor --}}
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis ?? '-' }}</p>
                        </div>
                        <span class="text-[11px] text-gray-400 dark:text-gray-700 flex-shrink-0">#{{ $index + 1 }}</span>
                    </div>

                    {{-- Row 2: 3-col info chips --}}
                    <div class="grid grid-cols-3 border-t border-gray-200 dark:border-gray-700/50">
                        <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                            <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Gender</p>
                            @if($santri->gender === 'P' || $santri->gender === 'Perempuan')
                                <p class="text-xs font-bold text-pink-500 dark:text-pink-400"> P</p>
                            @else
                                <p class="text-xs font-bold text-blue-500 dark:text-blue-400"> L</p>
                            @endif
                        </div>
                        <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Target</p>
                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $santri->target_display ?? '-' }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Progress</p>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-lg border {{ $badgeClass }}">{{ $persen }}%</span>
                    </div>
                    </div>

                    {{-- Row 3: progress bar --}}
                    <div class="px-4 py-2.5 border-t border-gray-200 dark:border-gray-700/50">
                        @if(($santri->is_iqro ?? false) && !($santri->iqro_persen_diketahui ?? false))
                            <span class="text-[10px] text-gray-500 dark:text-gray-600 italic">Halaman Jilid {{ $santri->target_jilid ?? '-' }} belum diatur</span>
                        @else
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($persen, 100) }}%"></div>
                                </div>
                                @if($santri->is_iqro ?? false)
                                    <span class="text-[11px] text-gray-500 whitespace-nowrap">{{ $santri->total_halaman_iqro ?? 0 }}/{{ $santri->total_halaman_jilid ?? 0 }} hal</span>
                                @else
                                    <span class="text-[11px] text-gray-500 whitespace-nowrap">{{ $totalJuz }}/{{ $targetJuz }} juz</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Row 4: action --}}
                    <div class="border-t border-gray-200 dark:border-gray-700/50">
                        <a href="{{ route('guru.rapot.show', $santri->id) }}"
                           class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 active:bg-indigo-100 dark:active:bg-indigo-950/80 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Lihat Rapot
                        </a>
                    </div>

                </div>
                @endforeach

                {{-- No Result Mobile --}}
                <div id="noResultMobile" class="hidden text-center py-12">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-700 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold text-gray-500">Santri tidak ditemukan</p>
                    <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Coba kata kunci lain</p>
                </div>
            </div>

            {{-- DESKTOP TABLE (≥ lg) --}}
            <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            <th class="th">No</th>
                            <th class="th">Nama Santri</th>
                            <th class="th">NIS</th>
                            <th class="th">Gender</th>
                            <th class="th">Target</th>
                            <th class="th">Progress</th>
                            <th class="th">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50" id="santriTableBody">
                        @foreach($santriList as $index => $santri)
                        @php
                            $persen    = $santri->hafalan_persen ?? 0;
                            $totalJuz  = $santri->hafalan_total_juz ?? 0;
                            $targetJuz = ($santri->target_juz ?? 0) > 0 ? $santri->target_juz : 30;
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
                        <tr class="santri-row hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors"
                            data-name="{{ strtolower($santri->name) }}"
                            data-nis="{{ strtolower($santri->nis ?? '') }}">

                            <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $santri->name }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $santri->nis ?? '-' }}</td>

                            <td class="px-4 py-3">
                                @if($santri->gender === 'P' || $santri->gender === 'Perempuan')
                                    <span class="chip bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30"> P</span>
                                @else
                                    <span class="chip bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30"> L</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">
                                    🎯 {{ $santri->target_display ?? '-' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                @if(($santri->is_iqro ?? false) && !($santri->iqro_persen_diketahui ?? false))
                                    <span class="text-[10px] text-gray-500 dark:text-gray-600 italic">Halaman Jilid {{ $santri->target_jilid ?? '-' }} belum diatur</span>
                                @else
                                    <div class="flex items-center gap-2 min-w-[160px]">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($persen, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold {{ $textColor }} w-10 text-right shrink-0">{{ $persen }}%</span>
                                    </div>
                                    @if($santri->is_iqro ?? false)
                                        <p class="text-[11px] text-gray-500 dark:text-gray-600 mt-1">{{ $santri->total_halaman_iqro ?? 0 }}/{{ $santri->total_halaman_jilid ?? 0 }} hal</p>
                                    @else
                                        <p class="text-[11px] text-gray-500 dark:text-gray-600 mt-1">{{ $totalJuz }}/{{ $targetJuz }} Juz</p>
                                    @endif
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <a href="{{ route('guru.rapot.show', $santri->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Lihat Rapot
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- No Result Desktop --}}
                <div id="noResultDesktop" class="hidden text-center py-16">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-700 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold text-gray-500">Santri tidak ditemukan</p>
                    <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Coba kata kunci lain</p>
                </div>
            </div>

            @else
            {{-- Santri kosong --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Silakan tambahkan santri terlebih dahulu</p>
            </div>
            @endif

        @else
        {{-- Belum ada kelas --}}
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm text-amber-700 dark:text-amber-300">Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator.</p>
        </div>
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
(function () {
    const input = document.getElementById('searchSantri');
    const clearBtn = document.getElementById('clearSearch');
    const noResultMobile = document.getElementById('noResultMobile');
    const noResultDesktop = document.getElementById('noResultDesktop');

    if (!input) return;

    function doSearch(q) {
        const rows = document.querySelectorAll('.santri-row');
        let visible = 0;

        rows.forEach(row => {
            const name = row.dataset.name ?? '';
            const nis  = row.dataset.nis  ?? '';
            const show = name.includes(q) || nis.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        clearBtn.classList.toggle('hidden', q === '');
        if (noResultMobile)  noResultMobile.classList.toggle('hidden', visible > 0);
        if (noResultDesktop) noResultDesktop.classList.toggle('hidden', visible > 0);
    }

    input.addEventListener('input', function () {
        doSearch(this.value.toLowerCase().trim());
    });

    clearBtn.addEventListener('click', function () {
        input.value = '';
        input.focus();
        doSearch('');
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    const n = document.getElementById('notif-ok');
    if (n) setTimeout(() => { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); }, 5000);
});
</script>
@endsection
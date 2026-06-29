@extends('layouts.app')

@section('content')
<div class="min-h-screen pb-10">

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
        <div class="flex items-center gap-3 mb-4 sm:mb-6">
            <a href="{{ route('admin.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> Management Santri per Kelas</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Pilih kelas untuk melihat dan mengelola santri</p>
            </div>
        </div>

        {{-- GRID KELAS CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">

            @forelse($kelasWithStats as $kelas)

            @php
                $isAkhwat = $kelas['gender_restriction'] === 'akhwat';
                $isIkhwan = $kelas['gender_restriction'] === 'ikhwan';
                $isCampuran = $kelas['gender_restriction'] === 'campuran';

                $headerGradient = $isCampuran
                    ? 'from-indigo-600 to-purple-600 dark:from-indigo-900/80 dark:to-purple-900/80'
                    : ($isIkhwan ? 'from-blue-600 to-blue-500 dark:from-blue-900/80 dark:to-blue-800/80' : 'from-pink-600 to-pink-500 dark:from-pink-900/80 dark:to-pink-800/80');

                $cardBorder = $isCampuran
                    ? 'border-indigo-200 dark:border-indigo-800/30'
                    : ($isIkhwan ? 'border-blue-200 dark:border-blue-800/30' : 'border-pink-200 dark:border-pink-800/30');

                $accentColor = $isCampuran
                    ? 'text-indigo-400'
                    : ($isIkhwan ? 'text-blue-400' : 'text-pink-400');

                $badgeBg = $isCampuran
                    ? 'bg-white/15 text-white border-white/25 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-700/40'
                    : ($isIkhwan ? 'bg-white/15 text-white border-white/25 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-700/40' : 'bg-white/15 text-white border-white/25 dark:bg-pink-950/60 dark:text-pink-300 dark:border-pink-700/40');

                $btnColor = $isCampuran
                    ? 'bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-700 dark:hover:bg-indigo-600'
                    : ($isIkhwan ? 'bg-blue-600 hover:bg-blue-500 dark:bg-blue-700 dark:hover:bg-blue-600' : 'bg-pink-600 hover:bg-pink-500 dark:bg-pink-700 dark:hover:bg-pink-600');

                $genderEmoji = $isCampuran ? ' Campuran' : ($isIkhwan ? ' Ikhwan' : ' Akhwat');
            @endphp

            <div class="group bg-white dark:bg-gray-800 border {{ $cardBorder }} rounded-xl overflow-hidden hover:border-opacity-60 transition-all duration-200 hover:shadow-xl hover:shadow-black/10 dark:hover:shadow-black/30 hover:-translate-y-0.5 flex flex-col shadow-sm dark:shadow-none">

                {{-- Card Header --}}
                <div class="bg-gradient-to-br {{ $headerGradient }} px-4 py-4 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/5 -mr-12 -mt-12"></div>

                    <div class="relative z-10">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="text-base sm:text-lg font-bold text-white leading-tight">{{ $kelas['name'] }}</h3>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border flex-shrink-0 {{ $badgeBg }}">
                                {{ $genderEmoji }}
                            </span>
                        </div>
                        <p class="text-[11px] text-white/80">{{ $kelas['guru_name'] ?? 'Belum ada guru' }}</p>

                        <div class="mt-3">
                            <span class="text-3xl sm:text-4xl font-black text-white">{{ $kelas['total_santri'] }}</span>
                            <span class="text-xs text-white/60 ml-1">santri</span>
                        </div>
                    </div>
                </div>

                {{-- Card Body: Gender Stats --}}
                <div class="px-4 py-3 flex-1">
                    @if($isCampuran)
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/30 rounded-lg px-3 py-2 text-center">
                            <p class="text-[10px] text-blue-500 mb-0.5"> Ikhwan</p>
                            <p class="text-lg font-black text-blue-600 dark:text-blue-300">{{ $kelas['laki_laki'] }}</p>
                        </div>
                        <div class="bg-pink-50 dark:bg-pink-950/40 border border-pink-200 dark:border-pink-800/30 rounded-lg px-3 py-2 text-center">
                            <p class="text-[10px] text-pink-500 mb-0.5"> Akhwat</p>
                            <p class="text-lg font-black text-pink-600 dark:text-pink-300">{{ $kelas['perempuan'] }}</p>
                        </div>
                    </div>
                    @elseif($isIkhwan)
                    <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/30 rounded-lg px-3 py-2.5 text-center">
                        <p class="text-[10px] text-blue-500 mb-0.5"> Khusus Ikhwan</p>
                        <p class="text-xl font-black text-blue-600 dark:text-blue-300">{{ $kelas['laki_laki'] }} <span class="text-xs font-normal text-blue-500">santri</span></p>
                    </div>
                    @else
                    <div class="bg-pink-50 dark:bg-pink-950/40 border border-pink-200 dark:border-pink-800/30 rounded-lg px-3 py-2.5 text-center">
                        <p class="text-[10px] text-pink-500 mb-0.5"> Khusus Akhwat</p>
                        <p class="text-xl font-black text-pink-600 dark:text-pink-300">{{ $kelas['perempuan'] }} <span class="text-xs font-normal text-pink-500">santriwati</span></p>
                    </div>
                    @endif
                </div>

                {{-- Card Footer: Button --}}
                <div class="px-4 pb-4">
                    <a href="{{ route('admin.santris.detail', $kelas['id']) }}"
                       class="block w-full {{ $btnColor }} text-white text-center py-2.5 rounded-lg text-xs font-bold transition-all active:scale-95 shadow-sm">
                        Lihat Detail Santri →
                    </a>
                </div>

            </div>

            @empty
            <div class="col-span-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-20 text-center shadow-sm dark:shadow-none">
                <div class="text-5xl mb-4">🏫</div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada kelas yang tersedia</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Silakan tambahkan kelas terlebih dahulu</p>
                <a href="{{ route('admin.kelas.index') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">
                    + Tambah Kelas
                </a>
            </div>
            @endforelse

        </div>

    </div>{{-- end max-w --}}

</div>

<style>
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
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
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white truncate"> Daftar Santri — Setoran</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Kelas: <span class="text-indigo-400 font-semibold">{{ $kelas->name }}</span>
                    <span class="mx-1">•</span>
                    @if($totalIkhwan > 0 && $totalAkhwat > 0)
                        <span class="text-purple-400 font-semibold">Campuran</span>
                    @elseif($totalIkhwan > 0)
                        <span class="text-blue-400 font-semibold">Ikhwan</span>
                    @elseif($totalAkhwat > 0)
                        <span class="text-pink-400 font-semibold">Akhwat</span>
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

        {{-- MOBILE CARDS (< lg) --}}
        <div class="lg:hidden space-y-2">
            @forelse($santris as $index => $santri)
            @php
                $targetJuz = $santri->target_juz ?? 1.00;
                $totalHafalan = $santri->total_hafalan_juz ?? 0;  
                $pct = $santri->juz_progress_percentage ?? 0;
                $barColor = $pct >= 80 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
                $badgeClass = $pct >= 80 ? 'bg-emerald-950 border-emerald-800/50 text-emerald-400' : ($pct >= 50 ? 'bg-amber-950 border-amber-800/50 text-amber-400' : 'bg-red-950 border-red-800/50 text-red-400');
                $textColor = $pct >= 80 ? 'text-emerald-400' : ($pct >= 50 ? 'text-amber-400' : 'text-red-400');
            @endphp
            <div class="rounded-xl border border-gray-800 bg-gray-900 overflow-hidden">

                {{-- Row 1: avatar + nama + nomor --}}
                <div class="flex items-center gap-3 px-4 py-3">
                    
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ $santri->name }}</p>
                        <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis }}</p>
                    </div>
                    <span class="text-[11px] text-gray-700 flex-shrink-0">#{{ $loop->iteration }}</span>
                </div>

                {{-- Row 2: 4-col chips --}}
                <div class="grid grid-cols-4 border-t border-gray-800/50">
                    <div class="px-2 py-2.5 text-center border-r border-gray-800/50">
                        <p class="text-[10px] text-gray-600 mb-1">Gender</p>
                        @if($santri->gender == 'Laki-laki')
                            <p class="text-xs font-bold text-blue-400"> L</p>
                        @else
                            <p class="text-xs font-bold text-pink-400"> P</p>
                        @endif
                    </div>
                    <div class="px-2 py-2.5 text-center border-r border-gray-800/50">
                        <p class="text-[10px] text-gray-600 mb-1">Hafalan</p>
                        <p class="text-xs font-bold text-purple-400">{{ $santri->total_hafalan }}</p>
                    </div>
                    <div class="px-2 py-2.5 text-center border-r border-gray-800/50">
                        <p class="text-[10px] text-gray-600 mb-1">Murajaah</p>
                        <p class="text-xs font-bold text-emerald-400">{{ $santri->total_murajaah }}</p>
                    </div>
                    <div class="px-2 py-2.5 text-center">
                        <p class="text-[10px] text-gray-600 mb-1">Tilawah</p>
                        <p class="text-xs font-bold text-blue-400">{{ $santri->total_tilawah }}</p>
                    </div>
                </div>

                {{-- Row 3: juz + progress --}}
                <div class="px-4 py-2.5 border-t border-gray-800/50">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-gray-500">Juz:</span>
                            <span class="text-xs font-bold text-purple-400">{{ number_format($santri->total_hafalan_juz ?? 0, 2) }}</span>
                            <span class="text-[10px] text-gray-600">/ {{ number_format($targetJuz, 2) }}</span>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-lg border {{ $badgeClass }}">{{ $pct }}%</span>
                    </div>
                    <div class="flex-1 bg-gray-800 rounded-full h-1.5">
                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                </div>

                {{-- Row 4: action --}}
                <div class="border-t border-gray-800/50">
                    <a href="{{ route('guru.setorans.detail', $santri->id) }}"
                       class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-400 hover:bg-indigo-950/50 active:bg-indigo-950/80 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Lihat Detail
                    </a>
                </div>

            </div>
            @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl py-16 text-center">
                <div class="text-4xl mb-3">📖</div>
                <p class="text-sm font-semibold text-gray-400">Belum ada data santri</p>
                <p class="text-xs text-gray-600 mt-1.5">Santri belum ditambahkan ke kelas ini</p>
            </div>
            @endforelse
        </div>

        {{-- DESKTOP TABLE (≥ lg) --}}
        <div class="hidden lg:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-800 bg-gray-800/40">
                        <th class="th">No</th>
                        <th class="th">NIS</th>
                        <th class="th">Nama Santri</th>
                        <th class="th">Gender</th>
                        <th class="th">Total Setoran</th>
                        <th class="th">Total Juz</th>
                        <th class="th">Target Juz</th>
                        <th class="th">Progress Hafalan</th>
                        <th class="th">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($santris as $index => $santri)
                    @php
                        $targetJuz    = $santri->target_juz ?? 1.00;
                        $totalHafalan = $santri->total_hafalan_juz ?? 0;
                        $pct          = $santri->juz_progress_percentage ?? 0;
                        $barColor     = $pct >= 80 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
                        $textColor    = $pct >= 80 ? 'text-emerald-400' : ($pct >= 50 ? 'text-amber-400' : 'text-red-400');
                    @endphp
                    <tr class="hover:bg-gray-800/25 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $santris->firstItem() + $index }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-400">{{ $santri->nis }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                
                                <span class="text-sm font-medium text-gray-200">{{ $santri->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($santri->gender == 'Laki-laki')
                                <span class="chip bg-blue-950/50 text-blue-400 border-blue-800/30"> L</span>
                            @else
                                <span class="chip bg-pink-950/50 text-pink-400 border-pink-800/30"> P</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-purple-400/70 w-16">Hafalan:</span>
                                    <span class="text-xs font-bold text-purple-400">{{ $santri->total_hafalan }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-blue-400/70 w-16">Tilawah:</span>
                                    <span class="text-xs font-bold text-blue-400">{{ $santri->total_tilawah }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-emerald-400/70 w-16">Murajaah:</span>
                                    <span class="text-xs font-bold text-emerald-400">{{ $santri->total_murajaah }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="chip bg-purple-950/50 text-purple-400 border-purple-800/30">📖 {{ number_format($santri->total_hafalan_juz ?? 0, 2) }} juz</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="chip bg-orange-950/50 text-orange-400 border-orange-800/30">🎯 {{ number_format($targetJuz, 2) }} juz</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 min-w-[140px]">
                                <div class="flex-1 bg-gray-800 rounded-full h-1.5">
                                    <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold {{ $textColor }} w-9 text-right shrink-0">{{ $pct }}%</span>
                            </div>
                            <p class="text-[11px] text-gray-600 mt-1">{{ number_format($totalHafalan, 2) }} / {{ number_format($targetJuz, 2) }} juz</p>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('guru.setorans.detail', $santri->id) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/40 border border-indigo-600/30 text-indigo-400 text-xs font-semibold rounded-lg transition-colors">
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
                        <p class="text-sm font-semibold text-gray-400">Belum ada data santri</p>
                        <p class="text-xs text-gray-600 mt-1">Santri belum ditambahkan ke kelas ini</p>
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
    html, body { background:#030712; min-height:100%; }
    .th { padding:12px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    @keyframes slide-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .animate-slide-in { animation:slide-in .25s ease-out; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
    nav[role="navigation"] span, nav[role="navigation"] a { background:#111827 !important; border-color:#374151 !important; color:#9ca3af !important; }
    nav[role="navigation"] a:hover { background:#1f2937 !important; color:#e5e7eb !important; }
    nav[role="navigation"] [aria-current="page"] span { background:#4f46e5 !important; border-color:#4f46e5 !important; color:#fff !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const n = document.getElementById('notif-ok');
    if (n) setTimeout(() => { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); }, 5000);
});
</script>
@endsection
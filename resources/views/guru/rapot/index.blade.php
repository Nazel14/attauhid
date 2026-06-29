@extends('layouts.app')

@section('content')
<div class="pb-10"
     x-data="{
        showNaik: false,
        selected: [],
        selectAll: false,
        kelasTujuan: '',
        naikLabel: '',
        openNaikSingle(id, name) { this.selected = [String(id)]; this.naikLabel = name; this.kelasTujuan=''; this.showNaik=true; },
        openNaikBulk() { if(this.selected.length===0) return; this.naikLabel = this.selected.length + ' santri terpilih'; this.kelasTujuan=''; this.showNaik=true; },
        toggle(id) { id=String(id); const i=this.selected.indexOf(id); if(i>-1){this.selected.splice(i,1);}else{this.selected.push(id);} this.syncAll(); },
        allIds() { return [...new Set([...document.querySelectorAll('.naik-cb')].map(c=>String(c.value)))]; },
        toggleAll() { this.selected = this.selectAll ? this.allIds() : []; },
        syncAll() { const ids=this.allIds(); this.selectAll = ids.length>0 && this.selected.length===ids.length; },
     }">

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

    @if(session('warning') || session('error'))
    @php $wm = session('warning') ?? session('error'); $wc = session('warning') ? 'bg-amber-500 border-amber-400/40' : 'bg-red-600 border-red-500/40'; @endphp
    <div id="notif-warn" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="{{ $wc }} border text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{{ $wm }}</p>
            <button onclick="document.getElementById('notif-warn').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
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

            {{-- BANNER STATUS KENAIKAN --}}
            @if($bolehNaik)
            <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                <p class="text-xs text-emerald-700 dark:text-emerald-300">Periode kenaikan kelas <strong>aktif</strong> (semester genap). Centang santri lalu gunakan <strong>Naikkan Terpilih</strong>, atau tombol <strong>Naik</strong> per santri. Santri yang sudah naik tahun ini tidak muncul tombolnya.</p>
            </div>
            @elseif(isset($alasanTutup) && $alasanTutup)
            <div class="bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700/40 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $alasanTutup }}</p>
            </div>
            @elseif($tglMulaiNaik)
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-amber-700 dark:text-amber-300">Fitur <strong>Naik Kelas</strong> akan aktif mulai <strong>{{ $tglMulaiNaik->translatedFormat('d F Y') }}</strong> (1 bulan sebelum semester berakhir).</p>
            </div>
            @endif

            {{-- BULK ACTION BAR --}}
            @if($bolehNaik)
            <div x-show="selected.length > 0" x-transition class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/40 rounded-xl px-4 py-3 mb-4 flex items-center justify-between gap-3" style="display:none;">
                <div>
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300"><span x-text="selected.length"></span> santri dipilih</p>
                    <button @click="selected=[]; selectAll=false;" class="text-[11px] text-emerald-600 hover:text-emerald-500">Bersihkan pilihan</button>
                </div>
                <button @click="openNaikBulk()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    Naikkan Terpilih
                </button>
            </div>
            @endif

            {{-- MOBILE CARDS --}}
            <div class="lg:hidden space-y-2" id="mobileList">
                @if($bolehNaik)
                <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                    <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-emerald-600 cursor-pointer">
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex-1">Pilih Semua (yang belum naik)</span>
                    <span class="text-[11px] text-gray-500">{{ $santriList->count() }} santri</span>
                </div>
                @endif

                @foreach($santriList as $index => $santri)
                @php
                    $persen    = $santri->hafalan_persen ?? 0;
                    $totalJuz  = $santri->hafalan_total_juz ?? 0;
                    $targetJuz = ($santri->target_juz ?? 0) > 0 ? $santri->target_juz : 30;
                    $barColor  = match(true) { $persen >= 80 => 'bg-emerald-500', $persen >= 50 => 'bg-blue-500', $persen >= 25 => 'bg-amber-500', default => 'bg-red-500' };
                    $badgeClass = match(true) {
                        $persen >= 80 => 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400',
                        $persen >= 50 => 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-950 dark:border-blue-800/50 dark:text-blue-400',
                        $persen >= 25 => 'bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950 dark:border-amber-800/50 dark:text-amber-400',
                        default       => 'bg-red-50 border-red-200 text-red-700 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400',
                    };
                    $sudahNaik = in_array((string) $santri->id, $sudahNaikIds ?? []);
                    $dpNaik    = $bolehNaik && !$sudahNaik;
                @endphp
                <div class="santri-row rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden transition-all shadow-sm dark:shadow-none"
                     data-name="{{ strtolower($santri->name) }}" data-nis="{{ strtolower($santri->nis ?? '') }}">
                    <div class="flex items-center gap-3 px-4 py-3">
                        @if($dpNaik)
                        <input type="checkbox" class="naik-cb w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-emerald-600 flex-shrink-0 cursor-pointer"
                               value="{{ $santri->id }}" :checked="selected.includes('{{ $santri->id }}')" @change="toggle('{{ $santri->id }}')">
                        @elseif($bolehNaik)
                        <span class="w-4 h-4 flex-shrink-0 flex items-center justify-center text-emerald-500" title="Sudah naik">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        @endif
                        @if($santri->user && $santri->user->profile_photo)
                            <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                        @else
                            <span class="w-10 h-10 rounded-full {{ ($santri->gender === 'P' || $santri->gender === 'Perempuan') ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-sm font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis ?? '-' }}</p>
                        </div>
                        @if($sudahNaik && $bolehNaik)
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30 flex-shrink-0">✓ Naik</span>
                        @else
                        <span class="text-[11px] text-gray-400 dark:text-gray-700 flex-shrink-0">#{{ $index + 1 }}</span>
                        @endif
                    </div>

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

                    <div class="border-t border-gray-200 dark:border-gray-700/50 grid {{ $dpNaik ? 'grid-cols-2 divide-x divide-gray-200 dark:divide-gray-700/50' : 'grid-cols-1' }}">
                        <a href="{{ route('guru.rapot.show', $santri->id) }}"
                           class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Lihat Rapot
                        </a>
                        @if($dpNaik)
                        <button type="button" @click="openNaikSingle({{ $santri->id }}, @js($santri->name))"
                                class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Naik
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach

                <div id="noResultMobile" class="hidden text-center py-12">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-700 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold text-gray-500">Santri tidak ditemukan</p>
                </div>
            </div>

            {{-- DESKTOP TABLE --}}
            <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            @if($bolehNaik)
                            <th class="px-4 py-3 w-10"><input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-emerald-600 cursor-pointer"></th>
                            @endif
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
                            $barColor  = match(true) { $persen >= 80 => 'bg-emerald-500', $persen >= 50 => 'bg-blue-500', $persen >= 25 => 'bg-amber-500', default => 'bg-red-500' };
                            $textColor = match(true) { $persen >= 80 => 'text-emerald-600 dark:text-emerald-400', $persen >= 50 => 'text-blue-600 dark:text-blue-400', $persen >= 25 => 'text-amber-600 dark:text-amber-400', default => 'text-red-600 dark:text-red-400' };
                            $sudahNaik = in_array((string) $santri->id, $sudahNaikIds ?? []);
                            $dpNaik    = $bolehNaik && !$sudahNaik;
                        @endphp
                        <tr class="santri-row hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors"
                            :class="selected.includes('{{ $santri->id }}') ? 'bg-emerald-50 dark:bg-emerald-950/20' : ''"
                            data-name="{{ strtolower($santri->name) }}" data-nis="{{ strtolower($santri->nis ?? '') }}">
                            @if($bolehNaik)
                                @if($dpNaik)
                                <td class="px-4 py-3"><input type="checkbox" class="naik-cb w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-emerald-600 cursor-pointer" value="{{ $santri->id }}" :checked="selected.includes('{{ $santri->id }}')" @change="toggle('{{ $santri->id }}')"></td>
                                @else
                                <td class="px-4 py-3 text-center"><span class="text-emerald-500" title="Sudah naik">✓</span></td>
                                @endif
                            @endif
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    @if($santri->user && $santri->user->profile_photo)
                                        <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-9 h-9 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                    @else
                                        <span class="w-9 h-9 rounded-full {{ ($santri->gender === 'P' || $santri->gender === 'Perempuan') ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-xs font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                                    @endif
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
                            <td class="px-4 py-3"><span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">🎯 {{ $santri->target_display ?? '-' }}</span></td>
                            <td class="px-4 py-3">
                                @if(($santri->is_iqro ?? false) && !($santri->iqro_persen_diketahui ?? false))
                                    <span class="text-[10px] text-gray-500 dark:text-gray-600 italic">Halaman Jilid {{ $santri->target_jilid ?? '-' }} belum diatur</span>
                                @else
                                    <div class="flex items-center gap-2 min-w-[160px]">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5"><div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($persen, 100) }}%"></div></div>
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
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('guru.rapot.show', $santri->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Lihat Rapot
                                    </a>
                                    @if($dpNaik)
                                    <button type="button" @click="openNaikSingle({{ $santri->id }}, @js($santri->name))" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 text-emerald-700 dark:bg-emerald-600/20 dark:hover:bg-emerald-600/40 dark:border-emerald-600/30 dark:text-emerald-400 text-xs font-semibold rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                        Naik
                                    </button>
                                    @elseif($bolehNaik)
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-600 text-xs font-semibold rounded-lg">✓ Sudah naik</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="noResultDesktop" class="hidden text-center py-16">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-700 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold text-gray-500">Santri tidak ditemukan</p>
                </div>
            </div>

            @else
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Silakan tambahkan santri terlebih dahulu</p>
            </div>
            @endif

        @else
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="text-sm text-amber-700 dark:text-amber-300">Anda belum ditugaskan sebagai wali kelas. Silakan hubungi administrator.</p>
        </div>
        @endif

    </div>

    {{-- MODAL NAIK KELAS --}}
    <div x-show="showNaik" class="fixed inset-0 z-[200] flex items-center justify-center p-4" style="display:none;" x-cloak>
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showNaik=false"></div>
        <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-md p-5"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-300 dark:border-emerald-600/40 rounded-xl flex items-center justify-center text-lg flex-shrink-0">⬆️</div>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Naik Kelas</h3>
                    <p class="text-xs text-gray-500 truncate" x-text="naikLabel"></p>
                </div>
            </div>
            @if($kelasTujuanList->isEmpty())
                <p class="text-sm text-amber-600 dark:text-amber-400 mb-4">Belum ada kelas tujuan lain. Minta admin membuat kelas tujuan dahulu.</p>
                <button type="button" @click="showNaik=false" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">Tutup</button>
            @else
                <form action="{{ route('guru.naik-kelas') }}" method="POST"
                      @submit="if(!kelasTujuan){ $event.preventDefault(); return; } if(!confirm('Naikkan ' + naikLabel + ' ke kelas yang dipilih?')) $event.preventDefault();">
                    @csrf
                    <template x-for="sid in selected" :key="sid"><input type="hidden" name="santri_ids[]" :value="sid"></template>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Pilih Kelas Tujuan</label>
                    <select name="kelas_tujuan_id" x-model="kelasTujuan" required
                            class="naik-select w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm text-gray-800 dark:text-gray-200 px-3 py-2.5 mb-1">
                        <option value="">— pilih kelas tujuan —</option>
                        @foreach($kelasTujuanList as $k)
                            <option value="{{ $k->id }}">{{ $k->name }} · {{ $k->tahun_ajaran }} · {{ $k->guru->name ?? 'Tanpa Guru' }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-4">Santri langsung pindah ke kelas & wali kelas tujuan, dan tercatat di riwayat.</p>
                    <div class="flex gap-3">
                        <button type="button" @click="showNaik=false" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">Batal</button>
                        <button type="submit" :disabled="!kelasTujuan" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition">Naikkan</button>
                    </div>
                </form>
            @endif
        </div>
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
    .naik-select {
        -webkit-appearance:none; -moz-appearance:none; appearance:none;
        background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") no-repeat right 10px center / 16px !important;
        padding-right:34px !important; cursor:pointer;
    }
    html.dark .naik-select {
        background:#1f2937 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") no-repeat right 10px center / 16px !important;
    }
    .naik-select option { background:#fff; color:#111827; }
    html.dark .naik-select option { background:#1f2937; color:#e5e7eb; }
    [x-cloak] { display:none !important; }
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
    input.addEventListener('input', function () { doSearch(this.value.toLowerCase().trim()); });
    clearBtn.addEventListener('click', function () { input.value=''; input.focus(); doSearch(''); });
})();
document.addEventListener('DOMContentLoaded', () => {
    ['notif-ok','notif-warn'].forEach(id => {
        const n = document.getElementById(id);
        if (n) setTimeout(() => { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); }, 5000);
    });
});
</script>
@endsection
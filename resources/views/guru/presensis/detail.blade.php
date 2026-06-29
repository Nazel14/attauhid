@extends('layouts.app')

@section('content')
<div class="pb-10"
     x-data="{ openEditModal: false, editData: {} }"
     x-effect="openEditModal ? document.body.classList.add('overflow-hidden') : document.body.classList.remove('overflow-hidden')">

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
    @if(session('error'))
    <div id="notif-err" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-red-600 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{{ session('error') }}</p>
            <button onclick="document.getElementById('notif-err').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.presensis.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate">🗓️ Detail Presensi — {{ $santri->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $santri->nis }}</p>
            </div>
            @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
            <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/40 rounded-xl text-[10px]">
                <span class="text-gray-900 dark:text-white font-medium">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
                <span class="px-1.5 py-0.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-full text-[9px] font-bold">Aktif</span>
            </div>
            @endif
        </div>

        {{-- SANTRI INFO --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-3">
                @if($santri->user && $santri->user->profile_photo)
                    <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-12 h-12 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                @else
                    <span class="w-12 h-12 rounded-full {{ $santri->gender === 'Perempuan' ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-base font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                @endif
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $santri->name }}</p>
                    <p class="text-[11px] text-gray-500 font-mono">NIS: {{ $santri->nis }}</p>
                </div>
            </div>
        </div>

       {{-- STATISTIK --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[10px] text-gray-500 font-semibold uppercase">Hadir</p>
                    <span class="w-3 h-3 rounded-full" style="background-color:#10b981;"></span>
                </div>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $statistik['total_hadir'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[10px] text-gray-500 font-semibold uppercase">Alpha</p>
                    <span class="w-3 h-3 rounded-full" style="background-color:#ef4444;"></span>
                </div>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $statistik['total_alpha'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[10px] text-gray-500 font-semibold uppercase">Izin</p>
                    <span class="w-3 h-3 rounded-full" style="background-color:#f59e0b;"></span>
                </div>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $statistik['total_izin'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[10px] text-gray-500 font-semibold uppercase">Sakit</p>
                    <span class="w-3 h-3 rounded-full" style="background-color:#f97316;"></span>
                </div>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $statistik['total_sakit'] }}</p>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4 shadow-sm dark:shadow-none">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Filter Data</p>
            <form method="GET" action="{{ route('guru.presensis.detail', $santri->id) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="fi-lbl">📅 Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai', now()->subDays(30)->format('Y-m-d')) }}" class="fi-inp">
                </div>
                <div>
                    <label class="fi-lbl">📅 Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir', now()->format('Y-m-d')) }}" class="fi-inp">
                </div>
                <div>
                    <label class="fi-lbl">Sesi</label>
                    <select name="sesi" class="fi-inp">
                        <option value="">Semua Sesi</option>
                        @foreach($sesiList as $sesi)
                            <option value="{{ $sesi->sesi_name }}" {{ request('sesi') == $sesi->sesi_name ? 'selected' : '' }}>{{ $sesi->sesi_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- MOBILE CARDS (< lg) --}}
        <div class="lg:hidden mb-4">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider px-1 mb-2">Riwayat Presensi</p>
            <div id="presensi_mobile" class="space-y-2">
                @forelse($presensis as $index => $presensi)
                @php $status = $presensi->status_presensi ?? $presensi->status; @endphp
                <div class="pgrow-presensi-m bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="pg-no-m text-[10px] font-bold text-gray-500 dark:text-gray-600 bg-gray-100 dark:bg-gray-900/40 px-1.5 py-0.5 rounded"></span>
                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($presensi->tanggal)->format('d M Y') }}</p>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="chip bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-950/50 dark:text-purple-400 dark:border-purple-800/30">{{ $presensi->sesi }}</span>
                                @if($status == 'Hadir')
                                    <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">✅ Hadir</span>
                                @elseif($status == 'Alpha')
                                    <span class="chip bg-red-100 text-red-700 border-red-300 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800/30">❌ Alpha</span>
                                @elseif($status == 'Izin')
                                    <span class="chip bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30">📝 Izin</span>
                                @elseif($status == 'Sakit')
                                    <span class="chip bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-950/50 dark:text-orange-400 dark:border-orange-800/30">🤒 Sakit</span>
                                @else
                                    <span class="chip bg-gray-100 text-gray-500 border-gray-300 dark:bg-gray-800 dark:text-gray-500 dark:border-gray-700">— Tidak ada</span>
                                @endif
                            </div>
                            @if($presensi->keterangan)
                            <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1 truncate">{{ $presensi->keterangan }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button"
                                @click="editData = {
                                    id: '{{ $presensi->id }}',
                                    tanggal: '{{ \Carbon\Carbon::parse($presensi->tanggal)->format('Y-m-d') }}',
                                    sesi: '{{ $presensi->sesi }}',
                                    status: '{{ $status }}',
                                    keterangan: '{{ addslashes($presensi->keterangan ?? '') }}',
                                    update_url: '{{ route('guru.presensis.update', $presensi->id) }}'
                                }; openEditModal = true;"
                                class="px-2.5 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-[10px] font-bold rounded-lg transition-colors">
                                Edit
                            </button>
                            <form action="{{ route('guru.presensis.destroy', $presensi->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 border border-red-300 text-red-600 dark:bg-red-950/30 dark:hover:bg-red-950/60 dark:border-red-800/30 dark:text-red-400 text-[10px] font-bold rounded-lg transition-colors">Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-12 text-center shadow-sm dark:shadow-none">
                    <div class="text-3xl mb-2">🗓️</div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data presensi</p>
                </div>
                @endforelse
            </div>
            @if($presensis->count() > 0)
            <div id="pg_presensi_mobile" class="mt-3 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"></div>
            @endif
        </div>

        {{-- DESKTOP TABLE (≥ lg) --}}
        <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Riwayat Presensi</h2>
            </div>
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                        <th class="th">No</th>
                        <th class="th">Tanggal</th>
                        <th class="th">Sesi</th>
                        <th class="th">Status</th>
                        <th class="th">Keterangan</th>
                        <th class="th">Aksi</th>
                    </tr>
                </thead>
                <tbody id="presensi_desktop" class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse($presensis as $index => $presensi)
                    @php $status = $presensi->status_presensi ?? $presensi->status; @endphp
                    <tr class="pgrow-presensi hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500"><span class="pg-no"></span></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ \Carbon\Carbon::parse($presensi->tanggal)->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="chip bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-950/50 dark:text-purple-400 dark:border-purple-800/30">{{ $presensi->sesi }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($status == 'Hadir')
                                <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">✅ Hadir</span>
                            @elseif($status == 'Alpha')
                                <span class="chip bg-red-100 text-red-700 border-red-300 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800/30">❌ Alpha</span>
                            @elseif($status == 'Izin')
                                <span class="chip bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-800/30">📝 Izin</span>
                            @elseif($status == 'Sakit')
                                <span class="chip bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-950/50 dark:text-orange-400 dark:border-orange-800/30">🤒 Sakit</span>
                            @else
                                <span class="chip bg-gray-100 text-gray-500 border-gray-300 dark:bg-gray-800 dark:text-gray-500 dark:border-gray-700">— Tidak ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $presensi->keterangan ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    @click="editData = {
                                        id: '{{ $presensi->id }}',
                                        tanggal: '{{ \Carbon\Carbon::parse($presensi->tanggal)->format('Y-m-d') }}',
                                        sesi: '{{ $presensi->sesi }}',
                                        status: '{{ $status }}',
                                        keterangan: '{{ addslashes($presensi->keterangan ?? '') }}',
                                        update_url: '{{ route('guru.presensis.update', $presensi->id) }}'
                                    }; openEditModal = true;"
                                    class="px-2.5 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">
                                    Edit
                                </button>
                                <form action="{{ route('guru.presensis.destroy', $presensi->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 border border-red-300 text-red-600 dark:bg-red-950/30 dark:hover:bg-red-950/60 dark:border-red-800/30 dark:text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-16 text-center">
                        <div class="text-3xl mb-2">🗓️</div>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data presensi</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($presensis->count() > 0)
            <div id="pg_presensi_desktop" class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40"></div>
            @endif
        </div>

    </div>

    {{-- EDIT MODAL --}}
    <div x-show="openEditModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <div x-show="openEditModal"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 dark:bg-black/80 backdrop-blur-sm"
             @click="openEditModal = false"></div>

        <div x-show="openEditModal"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl w-full max-w-md">

            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">✏️ Edit Presensi</h3>
                <button @click="openEditModal = false" class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="editData.update_url" method="POST" class="p-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="fi-lbl">Tanggal</label>
                        <input type="date" name="tanggal" x-model="editData.tanggal" required class="fi-inp">
                    </div>
                    <div>
                        <label class="fi-lbl">Sesi</label>
                        <select name="sesi" x-model="editData.sesi" required class="fi-inp">
                            @foreach($sesiList as $sesi)
                                <option value="{{ $sesi->sesi_name }}">{{ $sesi->sesi_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="fi-lbl mb-2">Status</label>
                    <div class="grid grid-cols-4 gap-2">
                        @php
                            $statusOptions = [
                                ['Hadir','✅','emerald'],
                                ['Izin','📝','amber'],
                                ['Sakit','🤒','orange'],
                                ['Alpha','❌','red'],
                            ];
                            $activeLight = [
                                'emerald'=>'border-emerald-500 bg-emerald-50',
                                'amber'=>'border-amber-500 bg-amber-50',
                                'orange'=>'border-orange-500 bg-orange-50',
                                'red'=>'border-red-500 bg-red-50',
                            ];
                            $activeDark = [
                                'emerald'=>'dark:border-emerald-500 dark:bg-emerald-950/40',
                                'amber'=>'dark:border-amber-500 dark:bg-amber-950/40',
                                'orange'=>'dark:border-orange-500 dark:bg-orange-950/40',
                                'red'=>'dark:border-red-500 dark:bg-red-950/40',
                            ];
                        @endphp
                        @foreach($statusOptions as [$val,$emoji,$color])
                        <label class="flex flex-col items-center p-2.5 border-2 rounded-xl cursor-pointer transition"
                               :class="editData.status == '{{ $val }}' ? '{{ $activeLight[$color] }} {{ $activeDark[$color] }}' : 'border-gray-200 bg-gray-50 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-gray-600'">
                            <input type="radio" name="status_presensi" value="{{ $val }}" x-model="editData.status" class="hidden">
                            <span class="text-lg mb-1">{{ $emoji }}</span>
                            <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300">{{ $val }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4" x-show="editData.status && editData.status !== 'Hadir'">
                    <label class="fi-lbl">Keterangan</label>
                    <textarea name="keterangan" x-model="editData.keterangan" rows="2"
                              placeholder="Keterangan tambahan (opsional)..."
                              class="fi-inp resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="openEditModal = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    [x-cloak] { display: none !important; }
    .th { padding:12px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#4b5563; margin-bottom:5px; }
    html.dark .fi-lbl { color:#9ca3af; }
    .fi-inp {
        width:100%;
        background:#fff;
        border:1px solid #d1d5db;
        border-radius:10px;
        color:#111827;
        font-size:13px;
        padding:9px 12px;
        outline:none;
        transition:border-color .15s;
        appearance: auto;
        -webkit-appearance: auto;
    }
    html.dark .fi-inp {
        background:#1f2937;
        border:1px solid #374151;
        color:#e5e7eb;
    }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }

    /* Kalender input date */
    input[type="date"].fi-inp { color-scheme: light; }
    html.dark input[type="date"].fi-inp { color-scheme: dark; }
    input[type="date"].fi-inp::-webkit-calendar-picker-indicator { cursor: pointer; opacity:.6; }
    html.dark input[type="date"].fi-inp::-webkit-calendar-picker-indicator { filter: invert(0.7); opacity:1; }

    /* Select dropdown arrow */
    select.fi-inp {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px;
        padding-right: 36px;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }
    select.fi-inp option { background: #fff; color: #111827; }
    html.dark select.fi-inp option { background: #1f2937; color: #e5e7eb; }

    @keyframes slide-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .animate-slide-in { animation:slide-in .25s ease-out; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>

<script>
// ============================================================
// PAGINATION
// ============================================================
var paginationConfig = [
    { tbodyId: 'presensi_desktop', mTblId: 'presensi_mobile', rowClass: 'pgrow-presensi', mRowClass: 'pgrow-presensi-m', pgId: 'pg_presensi_desktop', mPgId: 'pg_presensi_mobile', perPage: 13 }
];

var pgState = {};

function initPagination(cfg) {
    var tbody  = document.getElementById(cfg.tbodyId);
    var mTbody = document.getElementById(cfg.mTblId);
    if (!tbody && !mTbody) return;

    var rows  = tbody  ? Array.prototype.slice.call(tbody.querySelectorAll('.' + cfg.rowClass))  : [];
    var mRows = mTbody ? Array.prototype.slice.call(mTbody.querySelectorAll('.' + cfg.mRowClass)) : [];

    var totalRows  = Math.max(rows.length, mRows.length);
    if (totalRows === 0) return;

    var totalPages = Math.ceil(totalRows / cfg.perPage);

    pgState[cfg.tbodyId] = {
        rows: rows,
        mRows: mRows,
        totalRows: totalRows,
        totalPages: totalPages,
        perPage: cfg.perPage,
        currentPage: 1,
        pgId: cfg.pgId,
        mPgId: cfg.mPgId
    };
    showPage(cfg.tbodyId, 1);
}

function showPage(tbodyId, page) {
    var s = pgState[tbodyId];
    if (!s) return;
    s.currentPage = page;
    var start = (page - 1) * s.perPage;
    var end   = start + s.perPage;

    // Desktop rows
    for (var i = 0; i < s.rows.length; i++) {
        var visible = (i >= start && i < end);
        s.rows[i].style.display = visible ? '' : 'none';
        if (visible) {
            var noCell = s.rows[i].querySelector('.pg-no');
            if (noCell) noCell.textContent = (i + 1);
        }
    }

    // Mobile rows
    for (var j = 0; j < s.mRows.length; j++) {
        var mVisible = (j >= start && j < end);
        s.mRows[j].style.display = mVisible ? '' : 'none';
        if (mVisible) {
            var mNoCell = s.mRows[j].querySelector('.pg-no-m');
            if (mNoCell) mNoCell.textContent = '#' + (j + 1);
        }
    }

    renderPagination(tbodyId);
}

function pgMakeBtn(tbodyId, cp, p) {
    var cls = p === cp ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700';
    return '<button onclick="showPage(\'' + tbodyId + '\',' + p + ')" class="px-2.5 py-1 text-xs rounded-md font-medium ' + cls + '">' + p + '</button>';
}

function pgMakePrev(tbodyId, cp) {
    if (cp > 1) return '<button onclick="showPage(\'' + tbodyId + '\',' + (cp - 1) + ')" class="px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md">&#8249;</button>';
    return '<button disabled class="px-2 py-1 text-xs text-gray-300 dark:text-gray-700 rounded-md">&#8249;</button>';
}

function pgMakeNext(tbodyId, cp, tp) {
    if (cp < tp) return '<button onclick="showPage(\'' + tbodyId + '\',' + (cp + 1) + ')" class="px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md">&#8250;</button>';
    return '<button disabled class="px-2 py-1 text-xs text-gray-300 dark:text-gray-700 rounded-md">&#8250;</button>';
}

function renderPagination(tbodyId) {
    var s = pgState[tbodyId];
    if (!s) return;

    var container = document.getElementById(s.pgId);
    var mContainer = s.mPgId ? document.getElementById(s.mPgId) : null;

    var html;
    if (s.totalPages <= 1) {
        html = '<div class="text-[11px] text-gray-500 dark:text-gray-600 text-center py-1">Menampilkan ' + s.totalRows + ' data</div>';
    } else {
        var cp = s.currentPage;
        var tp = s.totalPages;
        var from = Math.min((cp - 1) * s.perPage + 1, s.totalRows);
        var to   = Math.min(cp * s.perPage, s.totalRows);

        var sp = Math.max(1, cp - 1);
        var ep = Math.min(tp, cp + 1);

        var btns = '';
        if (sp > 1) {
            btns += pgMakeBtn(tbodyId, cp, 1);
            if (sp > 2) btns += '<span class="text-xs text-gray-400 dark:text-gray-700">&hellip;</span>';
        }
        for (var i = sp; i <= ep; i++) btns += pgMakeBtn(tbodyId, cp, i);
        if (ep < tp) {
            if (ep < tp - 1) btns += '<span class="text-xs text-gray-400 dark:text-gray-700">&hellip;</span>';
            btns += pgMakeBtn(tbodyId, cp, tp);
        }

        html =
            '<div class="flex items-center justify-between">' +
                '<span class="text-[11px] text-gray-500 dark:text-gray-600">' + from + '&ndash;' + to + ' / ' + s.totalRows + '</span>' +
                '<div class="flex items-center gap-1">' + pgMakePrev(tbodyId, cp) + btns + pgMakeNext(tbodyId, cp, tp) + '</div>' +
            '</div>';
    }

    if (container) container.innerHTML = html;
    if (mContainer) mContainer.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function () {
    for (var i = 0; i < paginationConfig.length; i++) {
        initPagination(paginationConfig[i]);
    }
});

// ============================================================
// AUTO-DISMISS NOTIF
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const n = document.getElementById('notif-ok');
    if (n) setTimeout(() => { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); }, 5000);
});
</script>
@endsection
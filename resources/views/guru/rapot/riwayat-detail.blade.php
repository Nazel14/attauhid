@extends('layouts.app')

@section('content')
<div class="pb-10">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('guru.riwayat-santri') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate">
                    {{ $tahunAjaran->nama ?? 'Tanpa Tahun Ajaran' }}
                    @if($tahunAjaran && $tahunAjaran->semester)
                    <span class="text-xs font-medium text-gray-400">· {{ $tahunAjaran->semester == 2 ? 'Genap' : 'Ganjil' }}</span>
                    @endif
                </h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Santri yang naik/pindah: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $santriAlumni->count() }}</span></p>
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl p-3 mb-4 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-xs text-blue-700 dark:text-blue-300">Anda tetap dapat melihat rapot mereka. Data ditampilkan terkunci pada periode saat mereka masih di kelas Anda.</p>
        </div>

        @if($santriAlumni->count() > 0)
        <div class="space-y-2">
            @foreach($santriAlumni as $h)
            @php $s = $h->santri; @endphp
            <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm dark:shadow-none">
                @if($s->user && $s->user->profile_photo)
                    <img src="{{ asset('storage/' . $s->user->profile_photo) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                @else
                    <span class="w-10 h-10 rounded-full {{ ($s->gender === 'P' || $s->gender === 'Perempuan') ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-sm font-bold flex-shrink-0">{{ strtoupper(substr($s->name, 0, 1)) }}</span>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $s->name }}</p>
                    <p class="text-[11px] text-gray-500 font-mono">{{ $s->nis ?? '-' }}</p>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-400 dark:border-indigo-800/30">Sekarang: {{ $s->kelas->name ?? '-' }}</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-600">Keluar {{ \Illuminate\Support\Carbon::parse($h->tanggal_keluar)->translatedFormat('d M Y') }}</span>
                    </div>
                </div>
                <a href="{{ route('guru.rapot.show', ['santri' => $s->id, 'from' => 'riwayat', 'ta' => $tahunAjaran->id ?? '']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Lihat Rapot
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Tidak ada santri pada tahun ajaran ini</p>
        </div>
        @endif

    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('guru.presensis.index') }}" class="text-indigo-600 hover:text-indigo-700 mb-3 inline-flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Input Presensi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ $kelas->nama }} • {{ $guru->name }}
            </p>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-3 rounded">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Form -->
        <form action="{{ route('guru.presensis.store') }}" method="POST">
            @csrf

            <!-- Info Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Informasi Presensi</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Tanggal <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', $tanggalHariIni) }}" required
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Sesi <span class="text-red-500">*</span>
                        </label>
                        <select name="sesi" required
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">-- Pilih Sesi --</option>
                            @foreach($sesiList as $sesi)
                                <option value="{{ $sesi->sesi_name }}" {{ old('sesi') == $sesi->sesi_name ? 'selected' : '' }}>
                                    {{ $sesi->sesi_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-2 rounded">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        Hadir, Izin, Sakit: 25 poin • Alpha: 0 poin
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Tandai Semua:</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="setAllStatus('Hadir')" 
                            class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs rounded transition">
                        ✅ Hadir
                    </button>
                    <button type="button" onclick="setAllStatus('Izin')" 
                            class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs rounded transition">
                        📝 Izin
                    </button>
                    <button type="button" onclick="setAllStatus('Sakit')" 
                            class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs rounded transition">
                        🤒 Sakit
                    </button>
                    <button type="button" onclick="setAllStatus('Alpha')" 
                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs rounded transition">
                        ❌ Alpha
                    </button>
                </div>
            </div>

            <!-- Santri List -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                    Daftar Santri ({{ $santriList->count() }})
                </h2>
                
                <div class="space-y-3">
                    @forelse($santriList as $santri)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ strtoupper(substr($santri->name, 0, 2)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $santri->name }}</h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $santri->nis }}</p>
                            </div>
                        </div>

                        <!-- Status Options -->
                        <div class="grid grid-cols-4 gap-1.5">
                            <label class="flex flex-col items-center p-2 border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer transition hover:border-green-400 presensi-option">
                                <input type="radio" name="presensi[{{ $santri->id }}]" value="Hadir" required
                                       class="hidden"
                                       {{ old("presensi.{$santri->id}") == 'Hadir' ? 'checked' : '' }}>
                                <span class="text-lg mb-0.5">✅</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Hadir</span>
                            </label>

                            <label class="flex flex-col items-center p-2 border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer transition hover:border-yellow-400 presensi-option">
                                <input type="radio" name="presensi[{{ $santri->id }}]" value="Izin"
                                       class="hidden"
                                       {{ old("presensi.{$santri->id}") == 'Izin' ? 'checked' : '' }}>
                                <span class="text-lg mb-0.5">📝</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Izin</span>
                            </label>

                            <label class="flex flex-col items-center p-2 border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer transition hover:border-orange-400 presensi-option">
                                <input type="radio" name="presensi[{{ $santri->id }}]" value="Sakit"
                                       class="hidden"
                                       {{ old("presensi.{$santri->id}") == 'Sakit' ? 'checked' : '' }}>
                                <span class="text-lg mb-0.5">🤒</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Sakit</span>
                            </label>

                            <label class="flex flex-col items-center p-2 border-2 border-gray-200 dark:border-gray-600 rounded cursor-pointer transition hover:border-red-400 presensi-option">
                                <input type="radio" name="presensi[{{ $santri->id }}]" value="Alpha"
                                       class="hidden"
                                       {{ old("presensi.{$santri->id}") == 'Alpha' ? 'checked' : '' }}>
                                <span class="text-lg mb-0.5">❌</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Alpha</span>
                            </label>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada santri</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-2">
                <a href="{{ route('guru.presensis.index') }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white text-sm rounded-lg transition">
                    Batal
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                    Simpan Presensi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function setAllStatus(status) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radios.forEach(radio => {
        radio.checked = true;
        updateRadioStyle(radio);
    });
}

function updateRadioStyle(radio) {
    const label = radio.closest('label');
    const allLabels = label.parentElement.querySelectorAll('label');
    
    allLabels.forEach(l => {
        l.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
        l.classList.remove('border-yellow-500', 'bg-yellow-50', 'dark:bg-yellow-900/20');
        l.classList.remove('border-orange-500', 'bg-orange-50', 'dark:bg-orange-900/20');
        l.classList.remove('border-red-500', 'bg-red-50', 'dark:bg-red-900/20');
        l.classList.add('border-gray-200', 'dark:border-gray-600');
    });
    
    if (radio.checked) {
        label.classList.remove('border-gray-200', 'dark:border-gray-600');
        
        if (radio.value === 'Hadir') {
            label.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
        } else if (radio.value === 'Izin') {
            label.classList.add('border-yellow-500', 'bg-yellow-50', 'dark:bg-yellow-900/20');
        } else if (radio.value === 'Sakit') {
            label.classList.add('border-orange-500', 'bg-orange-50', 'dark:bg-orange-900/20');
        } else if (radio.value === 'Alpha') {
            label.classList.add('border-red-500', 'bg-red-50', 'dark:bg-red-900/20');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[type="radio"][name^="presensi"]');
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateRadioStyle(this);
        });
        
        if (radio.checked) {
            updateRadioStyle(radio);
        }
    });
});
</script>
@endsection
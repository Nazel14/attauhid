@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('guru.presensis.detail', $presensi->santri_id) }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Detail Presensi
            </a>
            <h1 class="text-4xl font-bold text-gray-800 dark:text-white">
                ✏️ Edit Presensi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Santri: <strong>{{ $presensi->santri->name }}</strong>
            </p>
        </div>

        <!-- Alert Messages -->
        @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <ul class="list-disc list-inside text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Form Edit -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <form action="{{ route('guru.presensis.update', $presensi->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Info Santri -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Nama Santri</p>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $presensi->santri->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">NIS</p>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $presensi->santri->nis }}</p>
                        </div>
                    </div>
                </div>

                <!-- Input Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tanggal <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', $presensi->tanggal->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sesi <span class="text-red-500">*</span>
                        </label>
                        <select name="sesi" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih Sesi --</option>
                            <option value="Subuh" {{ old('sesi', $presensi->sesi) == 'Subuh' ? 'selected' : '' }}>Subuh</option>
                            <option value="Pagi" {{ old('sesi', $presensi->sesi) == 'Pagi' ? 'selected' : '' }}>Pagi</option>
                            <option value="Sore" {{ old('sesi', $presensi->sesi) == 'Sore' ? 'selected' : '' }}>Sore</option>
                            <option value="Malam" {{ old('sesi', $presensi->sesi) == 'Malam' ? 'selected' : '' }}>Malam</option>
                        </select>
                    </div>
                </div>

                <!-- Status Presensi -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Status Presensi <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <label class="relative flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition hover:border-green-500 {{ old('status', $presensi->status) == 'Hadir' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" name="status" value="Hadir" required
                                   {{ old('status', $presensi->status) == 'Hadir' ? 'checked' : '' }}
                                   class="absolute top-2 right-2 text-green-600 focus:ring-green-500">
                            <span class="text-3xl mb-2">✅</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Hadir</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">25 poin</span>
                        </label>

                        <label class="relative flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition hover:border-yellow-500 {{ old('status', $presensi->status) == 'Izin' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" name="status" value="Izin"
                                   {{ old('status', $presensi->status) == 'Izin' ? 'checked' : '' }}
                                   class="absolute top-2 right-2 text-yellow-600 focus:ring-yellow-500">
                            <span class="text-3xl mb-2">📝</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Izin</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">25 poin</span>
                        </label>

                        <label class="relative flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition hover:border-orange-500 {{ old('status', $presensi->status) == 'Sakit' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" name="status" value="Sakit"
                                   {{ old('status', $presensi->status) == 'Sakit' ? 'checked' : '' }}
                                   class="absolute top-2 right-2 text-orange-600 focus:ring-orange-500">
                            <span class="text-3xl mb-2">🤒</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sakit</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">25 poin</span>
                        </label>

                        <label class="relative flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition hover:border-red-500 {{ old('status', $presensi->status) == 'Alpha' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" name="status" value="Alpha"
                                   {{ old('status', $presensi->status) == 'Alpha' ? 'checked' : '' }}
                                   class="absolute top-2 right-2 text-red-600 focus:ring-red-500">
                            <span class="text-3xl mb-2">❌</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Alpha</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">0 poin</span>
                        </label>
                    </div>
                </div>

                <!-- Info Poin -->
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Catatan:</strong> Status Hadir, Izin, dan Sakit mendapatkan 25 poin. Status Alpha mendapatkan 0 poin.
                    </p>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('guru.presensis.detail', $presensi->santri_id) }}" 
                       class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200">
                        ❌ Batal
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
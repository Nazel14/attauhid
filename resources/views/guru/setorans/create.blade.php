@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12">
    
    <!-- ✅ NOTIFIKASI BARU - Fixed Top Right dengan Auto-Hide 10 Detik -->
    @if(session('success'))
    <div class="success-notification fixed top-4 right-4 z-50 max-w-md animate-fade-in">
        <div class="bg-green-500 text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 border-green-700 flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-sm">{{ session('success') }}</p>
                <p class="text-xs mt-1 opacity-90">Notifikasi akan hilang dalam 10 detik</p>
            </div>
            <button type="button" class="close-notification flex-shrink-0 text-white hover:text-green-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="error-notification fixed top-4 right-4 z-50 max-w-md animate-fade-in">
        <div class="bg-red-500 text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 border-red-700 flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-sm">{{ session('error') }}</p>
                <p class="text-xs mt-1 opacity-90">Klik X untuk menutup</p>
            </div>
            <button type="button" class="close-notification flex-shrink-0 text-white hover:text-red-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="error-notification fixed top-4 right-4 z-50 max-w-md animate-fade-in">
        <div class="bg-red-500 text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 border-red-700">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-sm mb-2">Terdapat kesalahan:</p>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="close-notification flex-shrink-0 text-white hover:text-red-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('guru.setorans.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
            <h1 class="text-4xl font-bold text-gray-800 dark:text-white">
                📝 Catat Setoran & Presensi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Kelas: <strong>{{ $kelas->name }}</strong> | Tanggal: <strong>{{ $tanggalHariIni }}</strong>
            </p>
        </div>

        <form action="{{ route('guru.setorans.store') }}" method="POST" enctype="multipart/form-data" id="setoranForm">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- LEFT PANEL: Form Input -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-4" id="infoSetoranPanel">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 border-b pb-2">
                            📋 Informasi Dasar
                        </h3>

                        <!-- Santri -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nama Santri <span class="text-red-500">*</span>
                            </label>
                            <select name="santri_id" id="santriSelect" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">-- Pilih Santri --</option>
                                @foreach($santriList as $santri)
                                    <option value="{{ $santri->id }}" {{ old('santri_id') == $santri->id ? 'selected' : '' }}>
                                        {{ $santri->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tanggal -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tanggal <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="tanggal" id="tanggalInput" value="{{ old('tanggal', $tanggalHariIni) }}" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Sesi Presensi -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sesi <span class="text-red-500">*</span>
                            </label>
                            <select name="sesi" id="sesiSelect" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">-- Pilih Sesi --</option>
                                @foreach($sesiList as $sesi)
                                    <option value="{{ $sesi->sesi_name }}" {{ old('sesi') == $sesi->sesi_name ? 'selected' : '' }}>
                                        {{ $sesi->sesi_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Presensi -->
                        <div class="mb-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 p-4 rounded-lg border-2 border-indigo-200 dark:border-indigo-700">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white mb-3">
                                Status Kehadiran <span class="text-red-500">*</span>
                            </label>
                            
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer transition hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 status-option">
                                    <input type="radio" name="status_presensi" value="Hadir" required
                                           class="w-4 h-4 text-green-600"
                                           {{ old('status_presensi') == 'Hadir' ? 'checked' : '' }}
                                           onchange="toggleSetoranForm(this.value)">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        ✅ <strong>Hadir</strong> - Lanjut isi setoran
                                    </span>
                                </label>

                                <label class="flex items-center p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer transition hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 status-option">
                                    <input type="radio" name="status_presensi" value="Sakit"
                                           class="w-4 h-4 text-orange-600"
                                           {{ old('status_presensi') == 'Sakit' ? 'checked' : '' }}
                                           onchange="toggleSetoranForm(this.value)">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        🤒 <strong>Sakit</strong> - Presensi saja
                                    </span>
                                </label>

                                <label class="flex items-center p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer transition hover:border-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 status-option">
                                    <input type="radio" name="status_presensi" value="Izin"
                                           class="w-4 h-4 text-yellow-600"
                                           {{ old('status_presensi') == 'Izin' ? 'checked' : '' }}
                                           onchange="toggleSetoranForm(this.value)">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        📝 <strong>Izin</strong> - Presensi saja
                                    </span>
                                </label>

                                <label class="flex items-center p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer transition hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 status-option">
                                    <input type="radio" name="status_presensi" value="Alpha"
                                           class="w-4 h-4 text-red-600"
                                           {{ old('status_presensi') == 'Alpha' ? 'checked' : '' }}
                                           onchange="toggleSetoranForm(this.value)">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        ❌ <strong>Alpha</strong> - Tanpa keterangan
                                    </span>
                                </label>
                            </div>

                            <div class="mt-3 p-2 bg-blue-100 dark:bg-blue-900/30 rounded text-xs">
                                <p class="text-gray-700 dark:text-gray-300">
                                    💡 <strong>Info:</strong> Hadir/Sakit/Izin = 25 poin | Alpha = 0 poin
                                </p>
                            </div>
                        </div>

                        <!-- Keterangan (Hidden, muncul saat Sakit/Izin/Alpha) -->
                        <div id="keteranganSection" class="mb-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📝 Keterangan <span class="text-gray-400 text-xs">(Opsional)</span>
                            </label>
                            <textarea name="keterangan_presensi" rows="3"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                      placeholder="Contoh: Demam tinggi, istirahat di asrama...">{{ old('keterangan_presensi') }}</textarea>
                        </div>

                        <!-- SECTION SETORAN (Hidden by default, muncul saat Hadir) -->
                        <div id="setoranSection" class="hidden">
                            <hr class="my-4 border-gray-300 dark:border-gray-600">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 border-b pb-2">
                                📖 Data Setoran
                            </h3>

                            <!-- Kategori -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <select name="kategori_setoran" id="kategoriSetoran"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">-- Pilih --</option>
                                    <option value="Hafalan" {{ old('kategori_setoran') == 'Hafalan' ? 'selected' : '' }}>📚 Hafalan</option>
                                    <option value="Murajaah" {{ old('kategori_setoran') == 'Murajaah' ? 'selected' : '' }}>🔄 Murajaah</option>
                                    <option value="Tilawah" {{ old('kategori_setoran') == 'Tilawah' ? 'selected' : '' }}>📖 Tilawah</option>
                                </select>
                            </div>

                            <!-- Range Ayat -->
                            <div class="mb-4 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">📖 Range Ayat</h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Surat Mulai <span class="text-red-500">*</span></label>
                                        <select name="surat_start" id="suratStart"
                                                class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                                            <option value="">-- Pilih --</option>
                                            @foreach($daftarSurat as $surat)
                                                <option value="{{ $surat }}" {{ old('surat_start') == $surat ? 'selected' : '' }}>{{ $surat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Ayat Mulai <span class="text-red-500">*</span></label>
                                        <select name="ayat_start" id="ayatStart" disabled
                                                class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white cursor-not-allowed bg-gray-200 dark:bg-gray-700">
                                            <option value="">-- Pilih Surat Dulu --</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Surat Selesai <span class="text-red-500">*</span></label>
                                        <select name="surat_end" id="suratEnd" readonly
                                                class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white bg-gray-200 dark:bg-gray-700 cursor-not-allowed">
                                            <option value="">-- Otomatis --</option>
                                            @foreach($daftarSurat as $surat)
                                                <option value="{{ $surat }}">{{ $surat }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">⚙️ Otomatis mengikuti Surat Mulai</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Ayat Selesai <span class="text-red-500">*</span></label>
                                        <select name="ayat_end" id="ayatEnd" disabled
                                                class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                                            <option value="">-- Pilih Ayat Mulai Dulu --</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Tombol Tampilkan Al-Qur'an -->
                                <button type="button" onclick="loadQuranWithValidation()" 
                                        class="mt-3 w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                                    📖 Tampilkan Al-Qur'an
                                </button>
                            </div>

                            <!-- Hidden Page Inputs (otomatis terisi) -->
                            <input type="hidden" name="page_start" id="pageStart">
                            <input type="hidden" name="page_end" id="pageEnd">
                            <input type="hidden" name="page_start_portion" id="pageStartPortion">  
                            <input type="hidden" name="page_end_portion" id="pageEndPortion"> 
                            <input type="hidden" name="total_page" id="totalPageInput">
                            <input type="hidden" name="juz_start" id="juzStart">
                            <input type="hidden" name="juz_end" id="juzEnd">
                            <input type="hidden" name="total_juz" id="totalJuzInput">

                            <!-- Audio Recording -->
                            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">🎤 Rekaman Audio</h4>
                                
                                <!-- Recording Controls -->
                                <div class="flex gap-2 mb-2">
                                    <button type="button" id="startRecording" 
                                            class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                                        ⏺️ Rekam
                                    </button>
                                    <button type="button" id="stopRecording" disabled
                                            class="flex-1 px-3 py-2 bg-gray-400 text-white text-sm rounded-lg transition disabled:opacity-50">
                                        ⏹️ Stop
                                    </button>
                                </div>
                                
                                <!-- Audio Player -->
                                <audio id="audioPlayback" controls class="w-full mt-2 hidden"></audio>
                                
                                <!-- Clear Recording Button -->
                                <button type="button" id="clearRecording" 
                                        class="w-full mt-2 px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded-lg transition hidden">
                                    🗑️ Hapus Rekaman & Rekam Ulang
                                </button>
                                
                                <input type="hidden" name="audio_data" id="audioData">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1" id="recordingStatus"></p>
                            </div>

                            <!-- Kesalahan (Auto-calculated) -->
                            <div class="mb-4 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-gray-700 dark:to-gray-800 p-4 rounded-lg shadow-sm">
                                <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                    <span class="text-lg">⚠️</span>
                                    Kesalahan Bacaan
                                </h4>
                                <input type="hidden" name="kesalahan_kelancaran" id="kesalahanKelancaran" value="0">
                                <input type="hidden" name="kesalahan_tajwid" id="kesalahanTajwid" value="0">
                                <input type="hidden" name="kesalahan_pelafalan" id="kesalahanPelafalan" value="0">
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between items-center bg-white dark:bg-gray-700 px-3 py-2 rounded-md">
                                        <span class="text-gray-700 dark:text-gray-200 font-medium">🟢 Kelancaran (-0.5/kata):</span>
                                        <strong id="displayKelancaran" class="text-lg text-green-600 dark:text-green-400">0</strong>
                                    </div>

                                    <div class="flex justify-between items-center bg-white dark:bg-gray-700 px-3 py-2 rounded-md">
                                        <span class="text-gray-700 dark:text-gray-200 font-medium">🟡 Tajwid (-1.5/kata):</span>
                                        <strong id="displayTajwid" class="text-lg text-yellow-600 dark:text-yellow-400">0</strong>
                                    </div>

                                    <div class="flex justify-between items-center bg-white dark:bg-gray-700 px-3 py-2 rounded-md">
                                        <span class="text-gray-700 dark:text-gray-200 font-medium">🔴 Pelafalan (-2.5/kata):</span>
                                        <strong id="displayPelafalan" class="text-lg text-red-600 dark:text-red-400">0</strong>
                                    </div>

                                    <div class="flex justify-between items-center bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900/40 dark:to-purple-900/40 px-3 py-3 rounded-md mt-3">
                                        <span class="font-bold text-gray-800 dark:text-gray-100">📊 Estimasi Nilai:</span>
                                        <strong id="displayNilai" class="text-2xl text-indigo-600 dark:text-indigo-300">100</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    📝 Catatan Guru
                                </label>
                                <textarea name="catatan_guru" rows="3"
                                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                          placeholder="Tambahkan catatan...">{{ old('catatan_guru') }}</textarea>
                            </div>

                            <!-- Hidden inputs untuk PDF -->
                            <input type="hidden" name="pdf_html_content" id="pdfHtmlData">
                            <input type="hidden" name="total_kata" id="totalKataInput">
                            <input type="hidden" name="nilai" id="nilaiInput">
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn"
                                class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition">
                            💾 Simpan Data
                        </button>
                    </div>
                </div>

                <!-- RIGHT PANEL: Al-Qur'an Display / Statistics -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 sticky top-4">
                        
                        <!-- DEFAULT VIEW: Petunjuk & Statistik Presensi -->
                        <div id="defaultView" style="font-family: system-ui;">
                            <div class="text-center mb-6">
                                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
                                    📋 Petunjuk Penggunaan
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Ikuti langkah-langkah berikut untuk mencatat setoran & presensi
                                </p>
                            </div>

                            <!-- Langkah-langkah -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-indigo-200 dark:border-indigo-700">
                                    <div class="flex-shrink-0 w-8 h-8 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">1</div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">Pilih Nama Santri</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Pilih santri yang akan dicatat presensi/setorannya</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-indigo-200 dark:border-indigo-700">
                                    <div class="flex-shrink-0 w-8 h-8 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">2</div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">Pilih Sesi</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Pilih sesi presensi (Pagi, Siang, Sore, dll)</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-indigo-200 dark:border-indigo-700">
                                    <div class="flex-shrink-0 w-8 h-8 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">3</div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">Pilih Status Kehadiran</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            ✅ <strong>Hadir:</strong> Lanjut isi setoran<br>
                                            🤒📝❌ <strong>Sakit/Izin/Alpha:</strong> Hanya presensi (langsung klik Simpan)
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-indigo-200 dark:border-indigo-700">
                                    <div class="flex-shrink-0 w-8 h-8 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">4</div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">Isi Data Setoran (jika Hadir)</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Pilih kategori, range ayat, lalu klik "Tampilkan Al-Qur'an"</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistik Presensi Hari Ini -->
                            <div class="border-t border-gray-300 dark:border-gray-600 pt-4">
                                <h4 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                                    <span class="text-lg">📊</span>
                                    Presensi Hari Ini - {{ $tanggalHariIni }}
                                </h4>

                                <div id="presensiHariIniStats" class="grid grid-cols-2 gap-2 mb-4">
                                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="statHadir">0</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">✅ Hadir</div>
                                    </div>

                                    <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400" id="statSakit">0</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">🤒 Sakit</div>
                                    </div>

                                    <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" id="statIzin">0</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">📝 Izin</div>
                                    </div>

                                    <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                        <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="statAlpha">0</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">❌ Alpha</div>
                                    </div>
                                </div>

                                <!-- Daftar Santri yang Sudah Presensi -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Santri yang sudah presensi hari ini:</p>
                                    
                                    <!-- Keterangan Warna -->
                                    <div class="flex flex-wrap gap-2 mb-2 text-xs">
                                        <div class="flex items-center gap-1">
                                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                            <span class="text-gray-600 dark:text-gray-400">Hadir</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                            <span class="text-gray-600 dark:text-gray-400">Sakit</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                            <span class="text-gray-600 dark:text-gray-400">Izin</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                            <span class="text-gray-600 dark:text-gray-400">Alpha</span>
                                        </div>
                                    </div>

                                    <!-- Tabel Presensi -->
                                    <div id="daftarPresensiHariIni" class="max-h-64 overflow-y-auto custom-scrollbar">
                                        <table class="w-full text-xs">
                                            <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                                                <tr id="tableHeaderRow">
                                                    <th class="px-2 py-1 text-left text-gray-700 dark:text-gray-300 border-b dark:border-gray-600">No</th>
                                                    <th class="px-2 py-1 text-left text-gray-700 dark:text-gray-300 border-b dark:border-gray-600">Nama Santri</th>
                                                    <!-- Headers akan di-generate oleh JavaScript -->
                                                </tr>
                                            </thead>
                                            <tbody id="tableBodyPresensi">
                                                <tr>
                                                    <td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400 italic">
                                                        Memuat data...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QURAN PANEL: Tampil setelah klik "Tampilkan Al-Qur'an" -->
                        <div id="quranPanel" class="hidden">
                            <!-- Toolbar -->
                            <div class="mb-4 flex flex-wrap items-center gap-2 border-b pb-4">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex-1">
                                    🕌 Al-Qur'an Digital
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" onclick="clearMarking()"
                                            class="px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm rounded-lg transition">
                                        🗑️ Reset Semua
                                    </button>
                                    <button type="button" onclick="backToDefaultView()"
                                            class="px-3 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm rounded-lg transition">
                                        ← Kembali
                                    </button>
                                </div>
                            </div>

                            <!-- Keterangan Warna Kesalahan (Dropdown) -->
                            <div class="mb-4 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                <button type="button" onclick="toggleColorGuide()" 
                                        class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-left flex justify-between items-center transition">
                                    <span class="font-medium text-sm text-gray-700 dark:text-gray-300">
                                        🎨 Panduan Warna Marking Kesalahan
                                    </span>
                                    <svg id="colorGuideIcon" class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div id="colorGuideContent" class="hidden bg-gray-50 dark:bg-gray-800 p-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center gap-2 p-2 bg-green-100 dark:bg-green-900/30 rounded">
                                            <div class="w-4 h-4 bg-green-400 rounded"></div>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>🟢 Hijau (Kelancaran)</strong> - Bobot: -0.5 poin per kata
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded">
                                            <div class="w-4 h-4 bg-yellow-400 rounded"></div>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>🟡 Kuning (Tajwid)</strong> - Bobot: -1.5 poin per kata
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 p-2 bg-red-100 dark:bg-red-900/30 rounded">
                                            <div class="w-4 h-4 bg-red-400 rounded"></div>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>🔴 Merah (Pelafalan)</strong> - Bobot: -2.5 poin per kata
                                            </span>
                                        </div>
                                        <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-xs">
                                            <p class="text-gray-600 dark:text-gray-400">
                                                💡 <strong>Cara Marking:</strong> Klik kata di Al-Qur'an untuk menandai kesalahan. Klik lagi untuk mengubah warna atau menghapus marking.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quran Display Area -->
                            <div id="quranDisplay" 
                                 class="bg-gray-50 dark:bg-gray-700 p-6 text-right overflow-y-auto custom-scrollbar mb-4 rounded-xl"
                                 style="font-family: 'Amiri', 'Traditional Arabic', 'Arabic Typesetting', serif; min-height: 300px; max-height: 500px; border: 1px solid rgba(255, 255, 255, 0.2);">
                                <!-- Konten Al-Qur'an akan dimuat di sini -->
                            </div>

                            <!-- Statistik Gabungan -->
                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 p-4 border-t-2 border-indigo-500 rounded-lg">
                                <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                    <span class="text-lg">📊</span> 
                                    Statistik Setoran
                                </h4>
                                
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-3">
                                    <!-- Total Kata -->
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Kata</div>
                                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="displayTotalKata">0</div>
                                    </div>
                                    
                                    <!-- Juz Range -->
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Juz Range</div>
                                        <div class="text-xl font-bold text-purple-600 dark:text-purple-400" id="displayJuzRange">-</div>
                                    </div>
                                    
                                    <!-- Total Juz -->
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Juz</div>
                                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-200" id="displayTotalJuz">0.00</div>
                                    </div>
                                </div>

                                <!-- Halaman (Page) - Read Only Display -->
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">📄 Halaman (Mushaf Madinah)</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 italic">⚙️ Otomatis</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded border border-gray-300 dark:border-gray-600">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Mulai</div>
                                            <div class="text-lg font-bold text-orange-600 dark:text-orange-400" id="displayPageStart">-</div>
                                        </div>
                                        <div class="text-gray-400">→</div>
                                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded border border-gray-300 dark:border-gray-600">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Selesai</div>
                                            <div class="text-lg font-bold text-orange-600 dark:text-orange-400" id="displayPageEnd">-</div>
                                        </div>
                                        <div class="flex-1 bg-gradient-to-r from-orange-100 to-orange-50 dark:from-orange-900/30 dark:to-orange-800/20 px-3 py-2 rounded border border-orange-300 dark:border-orange-700">
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-0.5">Total</div>
                                            <div class="text-lg font-bold text-orange-600 dark:text-orange-400" id="displayTotalPage">0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Custom Scrollbar & Animation Style -->
<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(79, 70, 229, 0.5);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(79, 70, 229, 0.8);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.recording-indicator {
    animation: pulse 1.5s ease-in-out infinite;
}

#colorGuideContent {
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
}

.status-option input[type="radio"]:checked + span {
    font-weight: 700;
}

.status-option:has(input[type="radio"]:checked) {
    border-color: currentColor;
    background-color: rgba(59, 130, 246, 0.05);
}
</style>

<!-- JavaScript Functions -->
<script>
/**
 * ✅ FINAL FIXED VERSION 4.0 
 * - Menghilangkan validasi Al-Quran untuk Sakit/Izin/Alpha
 * - Auto-hide notification 10 detik
 * - Redirect berdasarkan status
 */

// ✅ Global state
let quranLoaded = false;

// ✅ Auto-hide success notification after 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.success-notification');
    
    if (successAlert) {
        console.log('✅ Success notification found - will auto-hide in 10 seconds');
        
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-20px)';
            
            setTimeout(function() {
                successAlert.remove();
            }, 500);
        }, 10000);
        
        const closeButton = successAlert.querySelector('.close-notification');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                successAlert.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-20px)';
                
                setTimeout(function() {
                    successAlert.remove();
                }, 300);
            });
        }
    }
    
    const errorAlert = document.querySelector('.error-notification');
    
    if (errorAlert) {
        const closeButton = errorAlert.querySelector('.close-notification');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                errorAlert.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                errorAlert.style.opacity = '0';
                errorAlert.style.transform = 'translateY(-20px)';
                
                setTimeout(function() {
                    errorAlert.remove();
                }, 300);
            });
        }
    }
});

// ✅ Toggle Form Setoran
function toggleSetoranForm(status) {
    const setoranSection = document.getElementById('setoranSection');
    const keteranganSection = document.getElementById('keteranganSection');
    const kategoriSetoran = document.getElementById('kategoriSetoran');
    const submitBtn = document.getElementById('submitBtn');
    
    if (status === 'Hadir') {
        setoranSection.classList.remove('hidden');
        keteranganSection.classList.add('hidden');
        kategoriSetoran.setAttribute('required', 'required');
        submitBtn.innerHTML = '💾 Simpan Setoran & Presensi';
    } else {
        setoranSection.classList.add('hidden');
        keteranganSection.classList.remove('hidden');
        kategoriSetoran.removeAttribute('required');
        submitBtn.innerHTML = '📝 Simpan Presensi';
    }
}

// ✅ Helper toast
function showToast(message, type = 'info') {
    const bgColor = type === 'success' ? 'bg-green-500' : 
                    type === 'error' ? 'bg-red-500' : 
                    type === 'info' ? 'bg-blue-500' : 'bg-gray-500';
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ✅ Back to default view
function backToDefaultView() {
    document.getElementById('quranPanel').classList.add('hidden');
    document.getElementById('defaultView').classList.remove('hidden');
    
    document.getElementById('quranDisplay').innerHTML = '';
    document.getElementById('displayTotalKata').textContent = '0';
    document.getElementById('displayJuzRange').textContent = '-';
    document.getElementById('displayTotalJuz').textContent = '0.00';
    document.getElementById('displayPageStart').textContent = '-';
    document.getElementById('displayPageEnd').textContent = '-';
    document.getElementById('displayTotalPage').textContent = '0.00';
    
    quranLoaded = false;
    showToast('📋 Kembali ke tampilan awal', 'info');
}

async function loadPresensiHariIni() {
    try {
        const tanggalInput = document.getElementById('tanggalInput');
        if (!tanggalInput) {
            console.error('❌ Tanggal input not found');
            return;
        }
        
        const tanggal = tanggalInput.value;
        
        const response = await fetch(`/api/presensi/hari-ini?tanggal=${tanggal}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        document.getElementById('statHadir').textContent = data.hadir || 0;
        document.getElementById('statSakit').textContent = data.sakit || 0;
        document.getElementById('statIzin').textContent = data.izin || 0;
        document.getElementById('statAlpha').textContent = data.alpha || 0;
        
        const sesiHeaders = data.sesi_aktif || [];
        const tableHeaderRow = document.getElementById('tableHeaderRow');
        
        while (tableHeaderRow.children.length > 2) {
            tableHeaderRow.removeChild(tableHeaderRow.lastChild);
        }
        
        sesiHeaders.forEach(sesi => {
            const th = document.createElement('th');
            th.className = 'px-2 py-1 text-center text-gray-700 dark:text-gray-300 border-b dark:border-gray-600 w-16';
            th.textContent = sesi;
            tableHeaderRow.appendChild(th);
        });
        
        const tableBody = document.getElementById('tableBodyPresensi');
        
        if (data.santri_list && data.santri_list.length > 0) {
            tableBody.innerHTML = data.santri_list.map((santri, index) => {
                const sesiCells = sesiHeaders.map(sesi => {
                    const presensiSesi = santri.sesi_data && santri.sesi_data[sesi];
                    
                    if (presensiSesi) {
                        const colorClass = {
                            'Hadir': 'bg-green-500',
                            'Sakit': 'bg-orange-500',
                            'Izin': 'bg-yellow-500',
                            'Alpha': 'bg-red-500'
                        }[presensiSesi.status] || 'bg-gray-400';
                        
                        const waktu = presensiSesi.waktu || '';
                        
                        return `
                            <td class="px-2 py-2 text-center border-b dark:border-gray-700">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <div class="w-5 h-5 rounded-full ${colorClass}" title="${presensiSesi.status} - ${waktu}"></div>
                                    ${waktu ? `<span class="text-[9px] text-gray-500 dark:text-gray-400">${waktu}</span>` : ''}
                                </div>
                            </td>
                        `;
                    } else {
                        return `
                            <td class="px-2 py-2 text-center border-b dark:border-gray-700">
                                <div class="flex items-center justify-center">
                                    <div class="w-5 h-5 rounded-full border-2  border-gray-300 dark:border-gray-600"></div>
                                </div>
                            </td>
                        `;
                    }
                }).join('');
                
                return `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-2 py-2 border-b dark:border-gray-700 text-gray-700 dark:text-gray-300 text-center">${index + 1}</td>
                        <td class="px-2 py-2 border-b dark:border-gray-700 text-gray-700 dark:text-gray-300">${santri.name}</td>
                        ${sesiCells}
                    </tr>
                `;
            }).join('');
        } else {
            const colspan = 2 + sesiHeaders.length;
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center py-4 text-gray-500 dark:text-gray-400 italic">
                        Tidak ada santri di kelas ini.
                    </td>
                </tr>
            `;
        }
        
    } catch (error) {
        console.error('❌ Error:', error);
    }
}

// ✅ Load Quran with validation
async function loadQuranWithValidation() {
    const santriId = document.getElementById('santriSelect').value;
    const sesi = document.getElementById('sesiSelect').value;
    const statusPresensi = document.querySelector('input[name="status_presensi"]:checked');
    const kategori = document.getElementById('kategoriSetoran').value;
    const suratStart = document.getElementById('suratStart').value;
    const ayatStart = document.getElementById('ayatStart').value;
    const ayatEnd = document.getElementById('ayatEnd').value;
    
    if (!santriId) {
        showToast('⚠️ Pilih nama santri terlebih dahulu!', 'error');
        return;
    }
    
    if (!sesi) {
        showToast('⚠️ Pilih sesi terlebih dahulu!', 'error');
        return;
    }
    
    if (!statusPresensi) {
        showToast('⚠️ Pilih status kehadiran terlebih dahulu!', 'error');
        return;
    }
    
    if (statusPresensi.value !== 'Hadir') {
        return;
    }
    
    if (!kategori || !suratStart || !ayatStart || !ayatEnd) {
        showToast('⚠️ Lengkapi semua field terlebih dahulu!', 'error');
        return;
    }
    
    document.getElementById('defaultView').classList.add('hidden');
    document.getElementById('quranPanel').classList.remove('hidden');
    
    try {
        if (typeof window.loadQuran === 'function') {
            await window.loadQuran();
            quranLoaded = true;
            
            setTimeout(() => {
                if (typeof countTotalWords === 'function') {
                    const totalWords = countTotalWords();
                    const totalKataInput = document.getElementById('totalKataInput');
                    if (totalKataInput) {
                        totalKataInput.value = totalWords;
                    }
                }
            }, 1000);
        }
    } catch (error) {
        console.error('❌ Error loading Quran:', error);
        backToDefaultView();
    }
}

// ✅ Toggle color guide
function toggleColorGuide() {
    const content = document.getElementById('colorGuideContent');
    const icon = document.getElementById('colorGuideIcon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

// ✅ Clear marking
function clearMarking() {
    const words = document.querySelectorAll('.word-item, .quran-word');
    
    words.forEach(word => {
        word.classList.remove('bg-green-400', 'bg-yellow-400', 'bg-red-400', 'text-white');
        word.style.backgroundColor = '';
        if (word.dataset.markState !== undefined) {
            word.dataset.markState = '0';
        }
    });
    
    document.getElementById('kesalahanKelancaran').value = 0;
    document.getElementById('kesalahanTajwid').value = 0;
    document.getElementById('kesalahanPelafalan').value = 0;
    
    if (typeof updateStatistikSetoran === 'function') {
        updateStatistikSetoran();
    }
    
    showToast('🗑️ Semua marking dihapus', 'info');
}

// ✅ SUPER CRITICAL FIX - Form validation
function setupFormValidation() {
    const form = document.getElementById('setoranForm');
    
    if (!form) {
        console.warn('⚠️ Form not found');
        return;
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🔥 FORM SUBMIT TRIGGERED');
        
        const statusPresensi = document.querySelector('input[name="status_presensi"]:checked');
        
        if (!statusPresensi) {
            showToast('⚠️ Pilih status kehadiran!', 'error');
            return false;
        }
        
        console.log('📝 Status:', statusPresensi.value);
        
        // ✅ SUPER CRITICAL - Submit langsung untuk Sakit/Izin/Alpha TANPA validasi apapun
        if (statusPresensi.value === 'Sakit' || statusPresensi.value === 'Izin' || statusPresensi.value === 'Alpha') {
            console.log('✅✅✅ LANGSUNG SUBMIT - NO VALIDATION');
            
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '⏳ Menyimpan Presensi...';
            submitBtn.disabled = true;
            
            // LANGSUNG SUBMIT - TIDAK ADA VALIDASI LAIN
            form.submit();
            return;
        }
        
        // ✅ Hanya untuk Hadir - validasi setoran
        console.log('📝 Status Hadir - validasi setoran');
        
        const kategori = document.getElementById('kategoriSetoran').value;
        const quranDisplay = document.getElementById('quranDisplay');
        const hasQuranContent = quranDisplay && quranDisplay.innerHTML.trim() !== '';
        
        if (!kategori) {
            showToast('⚠️ Pilih kategori setoran!', 'error');
            return false;
        }
        
        if (!hasQuranContent) {
            showToast('⚠️ Klik "Tampilkan Al-Qur\'an" terlebih dahulu!', 'error');
            return false;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '⏳ Menyimpan Setoran...';
        submitBtn.disabled = true;
        
        try {
            if (typeof generatePDFContent === 'function') {
                generatePDFContent().then(pdfHtmlContent => {
                    if (pdfHtmlContent) {
                        document.getElementById('pdfHtmlData').value = pdfHtmlContent;
                    }
                    setTimeout(() => form.submit(), 500);
                });
            } else {
                setTimeout(() => form.submit(), 500);
            }
        } catch (error) {
            console.error('❌ Submit error:', error);
            submitBtn.innerHTML = '💾 Simpan Data';
            submitBtn.disabled = false;
            showToast('❌ Error: ' + error.message, 'error');
        }
    });
    
    console.log('✅ Form validation setup complete');
}

// ✅ Update statistik
function updateStatistikSetoran() {
    const kesalahanKelancaran = parseInt(document.getElementById('kesalahanKelancaran')?.value) || 0;
    const kesalahanTajwid = parseInt(document.getElementById('kesalahanTajwid')?.value) || 0;
    const kesalahanPelafalan = parseInt(document.getElementById('kesalahanPelafalan')?.value) || 0;
    
    const nilaiSetoran = 75 - (kesalahanKelancaran * 0.5) - (kesalahanTajwid * 1.5) - (kesalahanPelafalan * 2.5);
    const nilaiTotal = Math.max(0, Math.min(100, nilaiSetoran + 25));
    
    const displayKelancaran = document.getElementById('displayKelancaran');
    const displayTajwid = document.getElementById('displayTajwid');
    const displayPelafalan = document.getElementById('displayPelafalan');
    const displayNilai = document.getElementById('displayNilai');
    const nilaiInput = document.getElementById('nilaiInput');
    
    if (displayKelancaran) displayKelancaran.textContent = kesalahanKelancaran;
    if (displayTajwid) displayTajwid.textContent = kesalahanTajwid;
    if (displayPelafalan) displayPelafalan.textContent = kesalahanPelafalan;
    if (displayNilai) displayNilai.textContent = Math.round(nilaiTotal);
    if (nilaiInput) nilaiInput.value = Math.round(nilaiTotal);
}

// ✅ Setup error observer
function setupErrorObserver() {
    const kesalahanInputs = ['kesalahanKelancaran', 'kesalahanTajwid', 'kesalahanPelafalan'];
    
    kesalahanInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', updateStatistikSetoran);
        }
    });
}

// ✅ DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM Ready - Initializing...');
    
    setupErrorObserver();
    setupFormValidation();
    loadPresensiHariIni();
    
    const tanggalInput = document.getElementById('tanggalInput');
    if (tanggalInput) {
        tanggalInput.addEventListener('change', loadPresensiHariIni);
    }
    
    console.log('✅ Initialization complete');
});

console.log('✅ Script loaded - Version 4.0 FINAL FIXED');
</script>

<!-- Load Custom JS -->
<script src="{{ asset('js/complete-quran-mapping.js') }}"></script>
<script src="{{ asset('js/page-juz-calculator.js') }}"></script>
<script src="{{ asset('js/quran-api.js') }}"></script>
<script src="{{ asset('js/setoran-interactive.js') }}"></script>
<script src="{{ asset('js/audio-recorder.js') }}"></script>
<script src="{{ asset('js/auto-fill-page.js') }}"></script>

@endsection
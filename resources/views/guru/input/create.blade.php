@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-950 py-3 sm:py-6 lg:py-10">

    {{-- ============================================================ --}}
    {{-- NOTIFICATIONS                                                  --}}
    {{-- ============================================================ --}}

    @if(session('success'))
    <div class="success-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-2xl border border-emerald-500/30 flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm">{{ session('success') }}</p>
                
            </div>
            <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="error-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-red-600 text-white px-4 py-3 rounded-xl shadow-2xl border border-red-500/30 flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0"><p class="font-medium text-sm">{{ session('error') }}</p></div>
            <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    @if(session('warning'))
    <div class="warning-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-amber-500 text-white px-4 py-3 rounded-xl shadow-2xl border border-amber-400/30 flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div class="flex-1 min-w-0"><p class="font-medium text-sm">{{ session('warning') }}</p></div>
            <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="error-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-red-600 text-white px-4 py-3 rounded-xl shadow-2xl border border-red-500/30">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm mb-1">Terdapat kesalahan:</p>
                    <ul class="text-xs space-y-0.5 opacity-90">@foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
                </div>
                <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MOBILE: BOTTOM DRAWER (Presensi / Al-Quran)                   --}}
    {{-- ============================================================ --}}
    <div id="mobileDrawerOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[150] hidden lg:hidden" onclick="closeDrawer()"></div>
    <div id="mobileDrawer" class="fixed bottom-0 left-0 right-0 z-[160] lg:hidden translate-y-full transition-transform duration-300 ease-out">
        <div class="bg-gray-900 border-t border-gray-700/50 rounded-t-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700/50 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-1 bg-gray-600 rounded-full mx-auto absolute left-1/2 -translate-x-1/2 top-2"></div>
                    <span id="drawerTitle" class="font-semibold text-white text-sm">📊 Presensi Hari Ini</span>
                </div>
                <button onclick="closeDrawer()" class="w-7 h-7 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center text-gray-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="drawerContent" class="overflow-y-auto flex-1 p-4"></div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT                                                   --}}
    {{-- ============================================================ --}}
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        {{-- PAGE HEADER --}}
        <div class="mb-4 sm:mb-6">
            <div class="flex items-center justify-between mb-3">
                <a href="{{ route('guru.dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-xs font-medium text-gray-300 hover:bg-gray-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
                <button id="btnOpenPresensi" onclick="openDrawer('presensi')"
                    class="hidden lg:hidden items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-xs font-medium text-white transition hidden">
                📊 Lihat Presensi
            </button>   
            </div>
            <h1 class="text-lg sm:text-2xl font-bold text-white leading-tight"> Input Presensi & Setoran</h1>
            <p class="text-xs text-gray-400 mt-1">
                Kelas: <span class="text-indigo-400 font-medium">{{ $kelas->name }}</span>
                <span class="mx-1.5 opacity-30">|</span>
                <span class="text-gray-300">{{ $tanggalHariIni }}</span>
            </p>
            @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-950/50 border border-indigo-800/40 rounded-xl text-xs mt-2">
                <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-white font-medium">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
                <span class="px-1.5 py-0.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-full text-[10px] font-semibold">Aktif</span>
            </div>
            @endif
        </div>

        {{-- LAYOUT --}}
        <div class="lg:grid lg:grid-cols-5 lg:gap-6">

            {{-- ============================================================ --}}
            {{-- LEFT PANEL (col-span-2): Mode Selector + Form                --}}
            {{-- ============================================================ --}}
            <div class="lg:col-span-2">
                <div class="lg:sticky lg:top-4 space-y-3">

                    {{-- MODE SELECTOR --}}
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-3 sm:p-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2.5">🎯 Pilih Mode Input</p>
                        <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                            <label class="mode-card cursor-pointer">
                                <input type="radio" name="input_mode" value="combined" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
                                <div class="mode-card-inner border-2 border-gray-700 rounded-lg p-2 sm:p-3 text-center transition-all hover:border-indigo-500/50 hover:bg-indigo-500/5">
                                    <div class="text-xl sm:text-2xl mb-1">🎯</div>
                                    <div class="text-[10px] sm:text-xs font-semibold text-gray-300 leading-tight">Presensi<br>+ Setoran</div>
                                </div>
                            </label>
                            <label class="mode-card cursor-pointer">
                                <input type="radio" name="input_mode" value="presensi_only" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
                                <div class="mode-card-inner border-2 border-gray-700 rounded-lg p-2 sm:p-3 text-center transition-all hover:border-blue-500/50 hover:bg-blue-500/5">
                                    <div class="text-xl sm:text-2xl mb-1">✅</div>
                                    <div class="text-[10px] sm:text-xs font-semibold text-gray-300 leading-tight">Presensi<br>Saja</div>
                                </div>
                            </label>
                            <label class="mode-card cursor-pointer">
                                <input type="radio" name="input_mode" value="setoran_only" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
                                <div class="mode-card-inner border-2 border-gray-700 rounded-lg p-2 sm:p-3 text-center transition-all hover:border-green-500/50 hover:bg-green-500/5">
                                    <div class="text-xl sm:text-2xl mb-1">📖</div>
                                    <div class="text-[10px] sm:text-xs font-semibold text-gray-300 leading-tight">Setoran<br>Tambahan</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="hintBelumPilih" class="bg-gray-900 border border-gray-800 rounded-xl p-6 text-center">
                        <div class="w-12 h-12 bg-gray-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                        </div>
                        <p class="text-sm text-gray-400">Pilih mode input di atas</p>
                        <p class="text-xs text-gray-600 mt-1">Form akan muncul di sini</p>
                    </div>

                    <hr class="border-gray-800 hidden" id="hrDivider">

                    {{-- ============================================================ --}}
                    {{-- FORM: COMBINED                                               --}}
                    {{-- ============================================================ --}}
                    <form id="formCombined" action="{{ route('guru.input.store-combined') }}" method="POST" enctype="multipart/form-data" class="hidden space-y-3">
                        @csrf

                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 bg-indigo-600 rounded flex items-center justify-center text-[10px]">📋</span>
                                Informasi Dasar
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Nama Santri <span class="text-red-400">*</span></label>
                                    <select name="santri_id" id="santriSelectCombined" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        @foreach($santriList as $santri)
                                            <option value="{{ $santri->id }}">{{ $santri->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Tanggal <span class="text-red-400">*</span></label>
                                        <input type="date" name="tanggal" id="tanggalCombined" value="{{ $tanggalHariIni }}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Sesi <span class="text-red-400">*</span></label>
                                        <select name="sesi" id="sesiSelectCombined" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            @foreach($sesiList as $sesi)
                                                <option value="{{ $sesi->sesi_name }}">{{ $sesi->sesi_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="status" value="Hadir">

                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 bg-emerald-600 rounded flex items-center justify-center text-[10px]">📖</span>
                                Data Setoran
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Kategori <span class="text-red-400">*</span></label>
                                    <select name="kategori_setoran" id="kategoriSetoranCombined" required class="form-select w-full">
                                        <option value="">-- Pilih --</option>
                                        <option value="Hafalan">📚 Hafalan</option>
                                        <option value="Murajaah">🔄 Murajaah</option>
                                        <option value="Tilawah">📖 Tilawah</option>
                                    </select>
                                </div>
                                <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-400 mb-2">📖 Range Ayat</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Mulai *</label>
                                            <select name="surat_start" id="suratStartCombined" required class="form-select-sm w-full">
                                                <option value="">-- Pilih --</option>
                                                @foreach($daftarSurat as $surat)<option value="{{ $surat }}">{{ $surat }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Mulai *</label>
                                            <select name="ayat_start" id="ayatStartCombined" disabled required class="form-select-sm w-full">
                                                <option value="">-- Pilih Surat --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Selesai *</label>
                                            <select name="surat_end" id="suratEndCombined" required class="form-select-sm w-full">
                                                <option value="">-- Otomatis --</option>
                                                @foreach($daftarSurat as $surat)<option value="{{ $surat }}">{{ $surat }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Selesai *</label>
                                            <select name="ayat_end" id="ayatEndCombined" disabled required class="form-select-sm w-full">
                                                <option value="">-- Pilih Ayat --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" onclick="loadQuranCombined(); if(window.innerWidth < 1024) openDrawer('quran');"
                                            class="mt-2.5 w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                                        📖 Tampilkan Al-Qur'an
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Inputs --}}
                        <input type="hidden" name="page_start"           id="pageStartCombined">
                        <input type="hidden" name="page_end"             id="pageEndCombined">
                        <input type="hidden" name="page_start_portion"   id="pageStartPortionCombined">
                        <input type="hidden" name="page_end_portion"     id="pageEndPortionCombined">
                        <input type="hidden" name="total_page"           id="totalPageCombined">
                        <input type="hidden" name="juz_start"            id="juzStartCombined">
                        <input type="hidden" name="juz_end"              id="juzEndCombined">
                        <input type="hidden" name="total_juz"            id="totalJuzCombined">
                        <input type="hidden" name="total_kata"           id="totalKataCombined">
                        <input type="hidden" name="kesalahan_kelancaran" id="kesalahanKelancaranCombined" value="0">
                        <input type="hidden" name="kesalahan_tajwid"     id="kesalahanTajwidCombined"     value="0">
                        <input type="hidden" name="kesalahan_pelafalan"  id="kesalahanPelafalanCombined"  value="0">
                        <input type="hidden" name="nilai"                id="nilaiCombined">
                        <input type="hidden" name="pdf_html_content"     id="pdfHtmlDataCombined">

                        {{-- Audio & Penilaian — Desktop: tampil di form kiri | Mobile: disembunyikan --}}
                        <div class="mobile-hide-audio bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                            <div class="flex items-center p-4 border-b border-gray-800">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-5 h-5 bg-blue-600 rounded flex items-center justify-center text-[10px]">🎤</span>
                                    Audio & Penilaian
                                </span>
                            </div>
                            <div id="sectionAudioCombined" class="px-4 pb-4 pt-3 space-y-3">
                                <div class="flex gap-2">
                                    <button type="button" id="startRecordingCombined" onclick="startRecording('Combined')"
                                            class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1">
                                        ⏺️ Rekam
                                    </button>
                                    <button type="button" id="pauseRecordingCombined" onclick="pauseRecording('Combined')"
                                            class="hidden flex-1 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium rounded-lg transition items-center justify-center gap-1">
                                        ⏸️ Jeda
                                    </button>
                                    <button type="button" id="stopRecordingCombined" onclick="stopRecording('Combined')"
                                            class="hidden flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition items-center justify-center gap-1">
                                        ⏹️ Stop
                                    </button>
                                </div>
                                <div id="recordingTimerCombined" class="hidden text-xs font-bold text-blue-400 text-center py-2 bg-blue-950/50 rounded-lg border border-blue-800/50">⏱️ Durasi: 0:00</div>
                                <audio id="audioPlaybackCombined" controls class="w-full hidden rounded-lg"></audio>
                                <button type="button" id="clearRecordingCombined" onclick="clearRecording('Combined')"
                                        class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition hidden flex items-center justify-center gap-1">
                                    🗑️ Hapus & Rekam Ulang
                                </button>
                                <input type="hidden" name="audio_data" id="audioDataCombined">
                                <p class="text-[10px] text-gray-600 italic">💡 Gunakan Jeda untuk berhenti sementara</p>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between col-span-2">
                                        <span class="text-xs text-gray-400">🟢 Kelancaran</span>
                                        <strong id="displayKelancaranCombined" class="text-base text-emerald-400">0</strong>
                                    </div>
                                    <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                                        <span class="text-xs text-gray-400">🟡 Tajwid</span>
                                        <strong id="displayTajwidCombined" class="text-base text-amber-400">0</strong>
                                    </div>
                                    <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                                        <span class="text-xs text-gray-400">🔴 Pelafalan</span>
                                        <strong id="displayPelafalanCombined" class="text-base text-red-400">0</strong>
                                    </div>
                                    <div class="bg-indigo-950 border border-indigo-800/50 rounded-lg p-2.5 flex items-center justify-between col-span-2">
                                        <span class="text-xs font-bold text-gray-300">📊 Nilai</span>
                                        <strong id="displayNilaiCombined" class="text-2xl text-indigo-400">100</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mobile-hide-bottom bg-gray-900 border border-gray-800 rounded-xl p-4">
                            <label class="block text-xs font-medium text-gray-400 mb-2">📝 Catatan Guru</label>
                            <textarea name="catatan_guru" id="catatanGuruCombined" rows="2" class="form-textarea w-full" placeholder="Tambahkan catatan..."></textarea>
                        </div>

                        <button type="submit" id="submitBtnCombined" class="mobile-hide-bottom w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-900/30 transition">
                            💾 Simpan Presensi & Setoran
                        </button>
                    </form>

                    {{-- ============================================================ --}}
                    {{-- FORM: PRESENSI ONLY                                          --}}
                    {{-- ============================================================ --}}
                    <form id="formPresensiOnly" action="{{ route('guru.input.store-presensi') }}" method="POST" class="hidden space-y-3">
                        @csrf
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 bg-blue-600 rounded flex items-center justify-center text-[10px]">✅</span>
                                Presensi Saja
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Nama Santri <span class="text-red-400">*</span></label>
                                    <select name="santri_id" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        @foreach($santriList as $santri)<option value="{{ $santri->id }}">{{ $santri->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Tanggal <span class="text-red-400">*</span></label>
                                        <input type="date" name="tanggal" value="{{ $tanggalHariIni }}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Sesi <span class="text-red-400">*</span></label>
                                        <select name="sesi" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            @foreach($sesiList as $sesi)<option value="{{ $sesi->sesi_name }}">{{ $sesi->sesi_name }}</option>@endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-2">Status Kehadiran <span class="text-red-400">*</span></label>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Hadir" required class="sr-only"><div class="status-radio-inner border-2 border-gray-700 rounded-lg p-2.5 text-center hover:border-emerald-500/50 transition text-xs font-medium text-gray-300">✅ Hadir</div></label>
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Sakit" class="sr-only"><div class="status-radio-inner border-2 border-gray-700 rounded-lg p-2.5 text-center hover:border-orange-500/50 transition text-xs font-medium text-gray-300">🤒 Sakit</div></label>
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Izin" class="sr-only"><div class="status-radio-inner border-2 border-gray-700 rounded-lg p-2.5 text-center hover:border-amber-500/50 transition text-xs font-medium text-gray-300">📝 Izin</div></label>
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Alpha" class="sr-only"><div class="status-radio-inner border-2 border-gray-700 rounded-lg p-2.5 text-center hover:border-red-500/50 transition text-xs font-medium text-gray-300">❌ Alpha</div></label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">📝 Keterangan <span class="text-gray-600 text-[10px]">(Opsional)</span></label>
                                    <textarea name="keterangan" rows="2" class="form-textarea w-full" placeholder="Contoh: Demam tinggi..."></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl shadow-lg shadow-blue-900/30 transition">💾 Simpan Presensi</button>
                    </form>

                    {{-- ============================================================ --}}
                    {{-- FORM: SETORAN ONLY                                           --}}
                    {{-- ============================================================ --}}
                    <form id="formSetoranOnly" action="{{ route('guru.input.store-setoran') }}" method="POST" enctype="multipart/form-data" class="hidden space-y-3">
                        @csrf
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 bg-emerald-600 rounded flex items-center justify-center text-[10px]">📖</span>
                                Setoran Tambahan
                            </h3>
                            <div class="mb-3 px-3 py-2 bg-amber-950/50 border border-amber-800/30 rounded-lg">
                                <p class="text-[10px] text-amber-300">⚠️ Santri harus sudah presensi <strong>HADIR</strong> di sesi yang sama.</p>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Nama Santri <span class="text-red-400">*</span></label>
                                    <select name="santri_id" id="santriSelectSetoranOnly" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        @foreach($santriList as $santri)<option value="{{ $santri->id }}">{{ $santri->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Tanggal <span class="text-red-400">*</span></label>
                                        <input type="date" name="tanggal" id="tanggalSetoranOnly" value="{{ $tanggalHariIni }}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Sesi <span class="text-red-400">*</span></label>
                                        <select name="sesi" id="sesiSelectSetoranOnly" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            @foreach($sesiList as $sesi)<option value="{{ $sesi->sesi_name }}">{{ $sesi->sesi_name }}</option>@endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Kategori <span class="text-red-400">*</span></label>
                                    <select name="kategori_setoran" id="kategoriSetoranOnly" required class="form-select w-full">
                                        <option value="">-- Pilih --</option>
                                        <option value="Hafalan">📚 Hafalan</option>
                                        <option value="Murajaah">🔄 Murajaah</option>
                                        <option value="Tilawah">📖 Tilawah</option>
                                    </select>
                                </div>
                                <div class="bg-gray-800/50 border border-gray-700/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-400 mb-2">📖 Range Ayat</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Mulai *</label>
                                            <select name="surat_start" id="suratStartSetoranOnly" required class="form-select-sm w-full">
                                                <option value="">-- Pilih --</option>
                                                @foreach($daftarSurat as $surat)<option value="{{ $surat }}">{{ $surat }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Mulai *</label>
                                            <select name="ayat_start" id="ayatStartSetoranOnly" disabled required class="form-select-sm w-full">
                                                <option value="">-- Pilih Surat --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Selesai *</label>
                                            <select name="surat_end" id="suratEndSetoranOnly" required class="form-select-sm w-full">
                                                <option value="">-- Otomatis --</option>
                                                @foreach($daftarSurat as $surat)<option value="{{ $surat }}">{{ $surat }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Selesai *</label>
                                            <select name="ayat_end" id="ayatEndSetoranOnly" disabled required class="form-select-sm w-full">
                                                <option value="">-- Pilih Ayat --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" onclick="loadQuranSetoranOnly(); if(window.innerWidth < 1024) openDrawer('quran');"
                                            class="mt-2.5 w-full px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                                        📖 Tampilkan Al-Qur'an
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Inputs --}}
                        <input type="hidden" name="page_start"           id="pageStartSetoranOnly">
                        <input type="hidden" name="page_end"             id="pageEndSetoranOnly">
                        <input type="hidden" name="page_start_portion"   id="pageStartPortionSetoranOnly">
                        <input type="hidden" name="page_end_portion"     id="pageEndPortionSetoranOnly">
                        <input type="hidden" name="total_page"           id="totalPageSetoranOnly">
                        <input type="hidden" name="juz_start"            id="juzStartSetoranOnly">
                        <input type="hidden" name="juz_end"              id="juzEndSetoranOnly">
                        <input type="hidden" name="total_juz"            id="totalJuzSetoranOnly">
                        <input type="hidden" name="total_kata"           id="totalKataSetoranOnly">
                        <input type="hidden" name="kesalahan_kelancaran" id="kesalahanKelancaranSetoranOnly" value="0">
                        <input type="hidden" name="kesalahan_tajwid"     id="kesalahanTajwidSetoranOnly"     value="0">
                        <input type="hidden" name="kesalahan_pelafalan"  id="kesalahanPelafalanSetoranOnly"  value="0">
                        <input type="hidden" name="nilai"                id="nilaiSetoranOnly">
                        <input type="hidden" name="pdf_html_content"     id="pdfHtmlDataSetoranOnly">

                        <div class="mobile-hide-audio bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                            <div class="flex items-center p-4 border-b border-gray-800">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-5 h-5 bg-blue-600 rounded flex items-center justify-center text-[10px]">🎤</span>
                                    Audio & Penilaian
                                </span>
                            </div>
                            <div id="sectionAudioSetoranOnly" class="px-4 pb-4 pt-3 space-y-3">
                                <div class="flex gap-2">
                                    <button type="button" id="startRecordingSetoranOnly" onclick="startRecording('SetoranOnly')" class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1">⏺️ Rekam</button>
                                    <button type="button" id="pauseRecordingSetoranOnly" onclick="pauseRecording('SetoranOnly')" class="hidden flex-1 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium rounded-lg transition items-center justify-center gap-1">⏸️ Jeda</button>
                                    <button type="button" id="stopRecordingSetoranOnly" onclick="stopRecording('SetoranOnly')" class="hidden flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition items-center justify-center gap-1">⏹️ Stop</button>
                                </div>
                                <div id="recordingTimerSetoranOnly" class="hidden text-xs font-bold text-blue-400 text-center py-2 bg-blue-950/50 rounded-lg border border-blue-800/50">⏱️ Durasi: 0:00</div>
                                <audio id="audioPlaybackSetoranOnly" controls class="w-full hidden rounded-lg"></audio>
                                <button type="button" id="clearRecordingSetoranOnly" onclick="clearRecording('SetoranOnly')" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition hidden flex items-center justify-center gap-1">🗑️ Hapus & Rekam Ulang</button>
                                <input type="hidden" name="audio_data" id="audioDataSetoranOnly">
                                <p class="text-[10px] text-gray-600 italic">💡 Gunakan Jeda untuk berhenti sementara</p>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between col-span-2">
                                        <span class="text-xs text-gray-400">🟢 Kelancaran</span>
                                        <strong id="displayKelancaranSetoranOnly" class="text-base text-emerald-400">0</strong>
                                    </div>
                                    <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                                        <span class="text-xs text-gray-400">🟡 Tajwid</span>
                                        <strong id="displayTajwidSetoranOnly" class="text-base text-amber-400">0</strong>
                                    </div>
                                    <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                                        <span class="text-xs text-gray-400">🔴 Pelafalan</span>
                                        <strong id="displayPelafalanSetoranOnly" class="text-base text-red-400">0</strong>
                                    </div>
                                    <div class="bg-indigo-950 border border-indigo-800/50 rounded-lg p-2.5 flex items-center justify-between col-span-2">
                                        <span class="text-xs font-bold text-gray-300">📊 Nilai</span>
                                        <strong id="displayNilaiSetoranOnly" class="text-2xl text-indigo-400">100</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mobile-hide-bottom bg-gray-900 border border-gray-800 rounded-xl p-4">
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">📝 Catatan Guru</label>
                            <textarea name="catatan_guru" id="catatanGuruSetoranOnly" rows="2" class="form-textarea w-full" placeholder="Tambahkan catatan..."></textarea>
                        </div>

                        <button type="submit" id="submitBtnSetoranOnly" class="mobile-hide-bottom w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl shadow-lg shadow-emerald-900/30 transition">
                            💾 Simpan Setoran Tambahan
                        </button>
                    </form>

                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- RIGHT PANEL (col-span-3): Presensi / Al-Quran               --}}
            {{-- ============================================================ --}}
            <div class="hidden lg:block lg:col-span-3">
                <div class="lg:sticky lg:top-4 bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">

                    {{-- PANEL: Petunjuk --}}
                    <div id="panelPetunjuk" class="p-6">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">📋</div>
                            <h3 class="text-lg font-bold text-white mb-1">Petunjuk Penggunaan</h3>
                            <p class="text-xs text-gray-500">Pilih mode input di kiri untuk memulai</p>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-xl border border-indigo-800/20">
                                <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0 text-base">🎯</div>
                                <div><h4 class="text-sm font-semibold text-white">Presensi + Setoran</h4><p class="text-xs text-gray-400 mt-0.5">Catat kehadiran & setoran sekaligus dalam 1 submit.</p></div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-xl border border-blue-800/20">
                                <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 text-base">✅</div>
                                <div><h4 class="text-sm font-semibold text-white">Presensi Saja</h4><p class="text-xs text-gray-400 mt-0.5">Untuk Hadir tanpa setoran, atau Sakit / Izin / Alpha.</p></div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-xl border border-emerald-800/20">
                                <div class="w-9 h-9 bg-emerald-600 rounded-lg flex items-center justify-center flex-shrink-0 text-base">📖</div>
                                <div><h4 class="text-sm font-semibold text-white">Setoran Tambahan</h4><p class="text-xs text-gray-400 mt-0.5">Setoran ke-2, ke-3, dst. Santri harus sudah hadir.</p></div>
                            </div>
                        </div>
                    </div>

                    {{-- PANEL: Presensi Hari Ini --}}
                    <div id="defaultView" class="hidden p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-sm text-white flex items-center gap-2">📊 Presensi — {{ $tanggalHariIni }}</h4>
                            <button type="button" onclick="loadPresensiHariIni()" class="px-2.5 py-1 text-xs bg-gray-800 hover:bg-gray-700 text-indigo-400 rounded-lg transition font-medium border border-gray-700">🔄 Refresh</button>
                        </div>
                        <div class="grid grid-cols-4 gap-2 mb-4">
                            <div class="bg-emerald-950/50 border border-emerald-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-emerald-400" id="statHadir">0</div><div class="text-[10px] text-gray-500">✅ Hadir</div></div>
                            <div class="bg-orange-950/50 border border-orange-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-orange-400" id="statSakit">0</div><div class="text-[10px] text-gray-500">🤒 Sakit</div></div>
                            <div class="bg-amber-950/50 border border-amber-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-amber-400" id="statIzin">0</div><div class="text-[10px] text-gray-500">📝 Izin</div></div>
                            <div class="bg-red-950/50 border border-red-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-red-400" id="statAlpha">0</div><div class="text-[10px] text-gray-500">❌ Alpha</div></div>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>Hadir</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>Sakit</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>Izin</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>Alpha</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><span class="px-1 bg-indigo-950 text-indigo-400 rounded text-[9px] ">📖</span>Setoran</div>
                        </div>
                        <div id="daftarPresensiHariIni" class="max-h-[420px] overflow-y-auto overflow-x-auto custom-scrollbar rounded-lg border border-gray-800">
                            <table class="w-full text-xs min-w-max">
                                <thead class="bg-gray-800 sticky top-0">
                                    <tr id="tableHeaderRow" class="border-b border-gray-700">
                                        <th class="px-2 py-2 text-center text-gray-400 font-medium w-10">No</th>
                                        <th class="px-2 py-2 text-left text-gray-400 font-medium">Nama Santri</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBodyPresensi" class="divide-y divide-gray-800/50">
                                    <tr><td colspan="7" class="text-center py-6 text-gray-600 italic">Memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- PANEL: Al-Qur'an --}}
                    <div id="quranPanel" class="hidden p-4">
                        <div class="hidden md:flex items-center justify-between mb-3 pb-3 border-b border-gray-800">
                            <h3 class="text-sm font-semibold text-white">🕌 Al-Qur'an Digital</h3>
                            <div class="flex gap-2">
                                <button type="button" onclick="clearMarking()" class="px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs rounded-lg transition">🗑️ Reset</button>
                                <button type="button" onclick="backToDefaultView()" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded-lg transition">← Kembali</button>
                            </div>
                        </div>

                        <div class="mb-3 border border-gray-800 rounded-lg overflow-hidden">
                            <button type="button" onclick="toggleColorGuide(this)" class="w-full px-3 py-2.5 bg-gray-800 hover:bg-gray-750 text-left flex justify-between items-center transition">
                                <span class="text-xs font-medium text-gray-400">🎨 Panduan Warna Marking</span>
                                <svg id="colorGuideIcon" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="colorGuideContent" class="hidden bg-gray-900 p-3 border-t border-gray-800">
                                <div class="space-y-1.5 text-xs">
                                    <div class="flex items-center gap-2 p-2 bg-emerald-950/50 rounded"><div class="w-3 h-3 bg-green-400 rounded flex-shrink-0"></div><span class="text-gray-300"><strong>🟢 Kelancaran</strong> — -1 poin/kata</span></div>
                                    <div class="flex items-center gap-2 p-2 bg-amber-950/50 rounded"><div class="w-3 h-3 bg-yellow-400 rounded flex-shrink-0"></div><span class="text-gray-300"><strong>🟡 Tajwid</strong> — -1 poin/kata</span></div>
                                    <div class="flex items-center gap-2 p-2 bg-red-950/50 rounded"><div class="w-3 h-3 bg-red-400 rounded flex-shrink-0"></div><span class="text-gray-300"><strong>🔴 Pelafalan</strong> — -1 poin/kata</span></div>
                                    <p class="text-[10px] text-gray-600 pt-1">💡 Klik kata untuk marking. Klik lagi untuk ganti/hapus.</p>
                                </div>
                            </div>
                        </div>

                        <div id="quranDisplay"
                             class="bg-gray-800/50 border border-gray-700/50 p-4 text-right overflow-y-auto custom-scrollbar mb-3 rounded-xl"
                             style="font-family: 'Amiri', 'Traditional Arabic', serif; min-height: 280px; max-height: 380px;">
                        </div>

                        <div class="bg-gray-800/50 border border-gray-700/50 p-3 rounded-xl">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">📊 Statistik Setoran</h4>
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="bg-gray-900 p-2.5 rounded-lg"><div class="text-[10px] text-gray-500 mb-0.5">Total Kata</div><div class="text-xl font-bold text-blue-400" id="displayTotalKata">0</div></div>
                                <div class="bg-gray-900 p-2.5 rounded-lg"><div class="text-[10px] text-gray-500 mb-0.5">Juz Range</div><div class="text-lg font-bold text-purple-400" id="displayJuzRange">-</div></div>
                                <div class="bg-gray-900 p-2.5 rounded-lg"><div class="text-[10px] text-gray-500 mb-0.5">Total Juz</div><div class="text-xl font-bold text-white" id="displayTotalJuz">0.00</div></div>
                            </div>
                            <div class="bg-gray-900 p-2.5 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] text-gray-500 font-medium">📄 Halaman Mushaf Madinah</span>
                                    <span class="text-[10px] text-gray-600">⚙️ Otomatis</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-800 px-2.5 py-2 rounded-lg border border-gray-700"><div class="text-[10px] text-gray-500">Mulai</div><div class="text-base font-bold text-orange-400" id="displayPageStart">-</div></div>
                                    <div class="text-gray-600">→</div>
                                    <div class="flex-1 bg-gray-800 px-2.5 py-2 rounded-lg border border-gray-700"><div class="text-[10px] text-gray-500">Selesai</div><div class="text-base font-bold text-orange-400" id="displayPageEnd">-</div></div>
                                    <div class="flex-1 bg-orange-950/50 px-2.5 py-2 rounded-lg border border-orange-900/30"><div class="text-[10px] text-gray-500">Total</div><div class="text-base font-bold text-orange-400" id="displayTotalPage">0.00</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- MOBILE: FAB button --}}
    <div id="mobileFab" class="fixed bottom-4 right-4 z-[100] lg:hidden hidden">
        <button onclick="openDrawer('presensi')"
                class="flex items-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-full shadow-2xl shadow-indigo-900/50 transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Presensi
        </button>
    </div>

</div>

{{-- ============================================================ --}}
{{-- STYLES                                                        --}}
{{-- ============================================================ --}}
<style>
    body { background: #030712; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #6366f1; }
    .form-input, .form-select, .form-select-sm, .form-textarea {
        background: #111827; border: 1px solid #374151; color: #e5e7eb;
        border-radius: 0.5rem; font-size: 0.8125rem; padding: 0.5rem 0.625rem;
        transition: border-color 0.15s;
    }
    .form-select-sm { padding: 0.4rem 0.5rem; font-size: 0.75rem; }
    .form-input:focus, .form-select:focus, .form-select-sm:focus, .form-textarea:focus {
        outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.15);
    }
    .form-textarea { resize: vertical; }
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1); cursor: pointer; opacity: 0.7;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }
    .mode-card input:checked ~ .mode-card-inner { border-color: #6366f1; background: rgba(99,102,241,0.1); }
    .mode-card input[value="presensi_only"]:checked ~ .mode-card-inner { border-color: #3b82f6; background: rgba(59,130,246,0.08); }
    .mode-card input[value="setoran_only"]:checked ~ .mode-card-inner { border-color: #10b981; background: rgba(16,185,129,0.08); }
    .status-radio-label input:checked ~ .status-radio-inner { border-color: #6366f1; background: rgba(99,102,241,0.1); color: #a5b4fc; }
    @keyframes fade-in { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    .animate-fade-in { animation: fade-in 0.25s ease-out; }
    .section-open .section-chevron { transform: rotate(180deg); }

    /* Mobile: sembunyikan Audio, Catatan Guru, dan tombol Submit dari form kiri */
    @media (max-width: 1023px) {
        .mobile-hide-audio  { display: none !important; }
        .mobile-hide-bottom { display: none !important; }
    }

    .cs-wrap { position: relative; width: 100%; }
    .cs-btn {
        width: 100%; text-align: left; padding: 7px 10px;
        border: 1px solid #374151; border-radius: 0.5rem;
        background: #111827; color: #e5e7eb; font-size: 0.8125rem;
        cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        min-height: 36px; box-sizing: border-box; transition: border-color 0.15s;
        font-family: inherit; line-height: 1.25;
    }
    .cs-btn:hover, .cs-btn.open { border-color: #6366f1; }
    .cs-btn.cs-disabled { opacity: 0.4; cursor: not-allowed; }
    .cs-btn .cs-txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; font-size: 0.8125rem; }
    .cs-btn .cs-txt.ph { color: #6b7280; font-style: italic; }
    .cs-btn .cs-arr { font-size: 8px; color: #6b7280; flex-shrink: 0; margin-left: 6px; transition: transform 0.2s; }
    .cs-btn.open .cs-arr { transform: rotate(180deg); }
    .cs-drop {
        display: none; position: absolute; top: calc(100% + 2px); left: 0; right: 0;
        z-index: 99999; background: #1f2937;
        border: 1px solid #374151; border-radius: 0.5rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.6); overflow: hidden;
    }
    .cs-drop.open { display: block; }
    .cs-search {
        width: 100%; padding: 7px 10px; border: none;
        border-bottom: 1px solid #374151; outline: none; font-size: 0.75rem;
        background: #111827; color: #f3f4f6; box-sizing: border-box; font-family: inherit;
    }
    .cs-search::placeholder { color: #6b7280; }
    .cs-list {
        max-height: 180px; overflow-y: auto; margin: 0; padding: 2px 0; list-style: none;
        scrollbar-width: thin; scrollbar-color: #374151 transparent;
    }
    .cs-list li {
        padding: 6px 10px; font-size: 0.75rem; cursor: pointer; color: #e5e7eb;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: background 0.1s;
    }
    .cs-list li:hover, .cs-list li.cs-hi { background: #6366f1 !important; color: white !important; }
    .cs-list li.cs-ph { color: #6b7280; font-style: italic; }
    .cs-list li.cs-gone { display: none; }
    .cs-nores { padding: 8px 10px; font-size: 0.75rem; color: #6b7280; text-align: center; display: none; font-style: italic; }
    #mobileDrawer.open { transform: translateY(0) !important; }

    /* ============================================================ */
    /* ✅ FIX: Font Al-Qur'an di Drawer Mobile - Lebih Kecil & Lega */
    /* ============================================================ */
    @media (max-width: 1023px) {
    /* Container Quran text di drawer */
    #quranDisplayDrawer #quranText {
        font-size: 28px !important;
        line-height: 3.0 !important;     /* ← was 2.2 → naikkan jadi 3.0 untuk jarak antar baris lebih lega */
    }

    /* Setiap kata (kotak hijau di mobile) */
    #quranDisplayDrawer .word-item {
    font-size: 24px !important;
    padding: 4px 6px !important;
    margin: 3px 0 !important;          /* ← horizontal 0, vertikal tetap 3px */
    display: inline-block !important;
}

/* ✅ Kurangi spasi karakter antar word-item di drawer mobile */
#quranDisplayDrawer #quranText {
    word-spacing: -4px !important;     /* ← rapatkan spasi HTML antar span */
}

        /* Nomor ayat dalam tanda kurung Arab */
        #quranDisplayDrawer #quranText > span:not(.word-item) {
            font-size: 16px !important;      /* was 24px */
            margin: 0 4px !important;
        }

        /* Header surat di dalam drawer (سُورَةُ البَقَرَة dan Al-Baqara) */
        #quranDisplayDrawer p[style*="font-size: 38px"] {
            font-size: 26px !important;
        }
        #quranDisplayDrawer p[style*="font-size: 36px"] {
            font-size: 22px !important;
        }
        #quranDisplayDrawer p[style*="font-size: 16px"] {
            font-size: 12px !important;
        }

        /* Box pembungkus kalimat Quran — kurangi padding agar lebih lega */
        #quranDisplayDrawer div[style*="border: 1.5px solid"] {
            padding: 14px !important;        /* was 25px */
            margin-top: 8px !important;
            margin-bottom: 12px !important;
        }

        /* Pastikan max-height drawer cukup untuk lihat banyak ayat */
        #quranDisplayDrawer {
            min-height: 200px !important;
            max-height: 50vh !important;     /* 50% tinggi layar */
        }
    }

    /* Progress bar notif — countdown 10 detik */
.success-notification > div,
.error-notification > div,
.warning-notification > div {
    position: relative;
    overflow: hidden;
}
.success-notification > div::after,
.error-notification > div::after,
.warning-notification > div::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: rgba(255,255,255,0.5);
    animation: notifCountdown 10s linear forwards;
}
@keyframes notifCountdown {
    from { width: 100%; }
    to   { width: 0%; }
}
</style>

{{-- ============================================================ --}}
{{-- JAVASCRIPT                                                    --}}
{{-- ============================================================ --}}
<script>

// ============================================================
// FIX UTAMA: syncStatistikToDrawer
// Menyalin nilai statistik dari elemen asli (desktop hidden)
// ke elemen drawer (dengan suffix Drawer) agar tampil di mobile
// ============================================================
function syncStatistikToDrawer() {
    var pairs = [
        ['displayTotalKata',  'displayTotalKataDrawer'],
        ['displayJuzRange',   'displayJuzRangeDrawer'],
        ['displayTotalJuz',   'displayTotalJuzDrawer'],
        ['displayPageStart',  'displayPageStartDrawer'],
        ['displayPageEnd',    'displayPageEndDrawer'],
        ['displayTotalPage',  'displayTotalPageDrawer'],
    ];
    pairs.forEach(function(pair) {
        var src = document.getElementById(pair[0]);
        var dst = document.getElementById(pair[1]);
        if (src && dst && dst.textContent !== src.textContent) {
            dst.textContent = src.textContent;
        }
    });
}

function openDrawer(type) {
    // ✅ FIX: cegah pemanggilan ganda dalam window 300ms
    const now = Date.now();
    if (window._lastOpenDrawerCall && (now - window._lastOpenDrawerCall.time) < 300 && window._lastOpenDrawerCall.type === type) {
        console.warn('⚠️ openDrawer("' + type + '") dipanggil dua kali dalam <300ms — skip duplikat');
        return;
    }
    window._lastOpenDrawerCall = { time: now, type: type };

    const overlay = document.getElementById('mobileDrawerOverlay');
    const drawer  = document.getElementById('mobileDrawer');
    const title   = document.getElementById('drawerTitle');
    const content = document.getElementById('drawerContent');

    if (type === 'presensi') {
        title.textContent = '📊 Presensi Hari Ini';
        content.innerHTML = document.getElementById('defaultView').innerHTML;
        const btn = content.querySelector('[onclick="loadPresensiHariIni()"]');
        if (btn) btn.onclick = () => loadPresensiHariIniToTarget(content);
        loadPresensiHariIniToTarget(content);

    } else if (type === 'quran') {
        title.innerHTML = '🕌 Al-Qur\'an & Penilaian';

        // Clone quranPanel
        const quranPanelEl = document.getElementById('quranPanel');
        const quranClone   = quranPanelEl.cloneNode(true);
        quranClone.id      = 'quranPanelDrawer';
        quranClone.classList.remove('hidden');
        quranClone.style.display = '';

        // ✅ Ubah ID quranDisplay di dalam clone agar tidak duplikat
const clonedQuranDisplay = quranClone.querySelector('#quranDisplay');
if (clonedQuranDisplay) {
    clonedQuranDisplay.id = 'quranDisplayDrawer';

    const originalDisplay = document.getElementById('quranDisplay');

    // ✅ Fungsi sync konten Al-Quran dari elemen asli ke drawer
    function syncContentToDrawer() {
        if (!originalDisplay) return false;
        const html = originalDisplay.innerHTML.trim();
        // Belum siap kalau kosong atau masih loading
        if (html.length < 100 || html.includes('Memuat')) {
            return false;
        }
        clonedQuranDisplay.innerHTML = html;

        // ✅ FIX MARKING DI MOBILE:
        // Klik di clone drawer akan diproxy ke elemen asli di #quranDisplay,
        // sehingga markWord() bekerja pada elemen asli (yang di-scope counter).
        // Setelah itu, style elemen asli disalin ke clone agar tampak ter-mark.
        const originalWords = originalDisplay.querySelectorAll('.word-item');
        const clonedWords   = clonedQuranDisplay.querySelectorAll('.word-item');

        // Fungsi helper: terapkan style marking ke clone berdasarkan state
function applyMarkStyleToClone(targetEl, state) {
    // Reset dulu
    targetEl.style.cssText = targetEl.style.cssText
        .replace(/background[^;]*;?/gi, '')
        .replace(/border-radius[^;]*;?/gi, '')
        .replace(/padding[^;]*;?/gi, '')
        .replace(/box-shadow[^;]*;?/gi, '')
        .replace(/transform[^;]*;?/gi, '');

    // Pastikan cursor & transition tetap
    targetEl.style.cursor     = 'pointer';
    targetEl.style.transition = 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)';

    if (state === '1') {
        targetEl.style.setProperty('background', 'linear-gradient(135deg, rgba(34, 197, 94, 0.25) 0%, rgba(34, 197, 94, 0.35) 100%)', 'important');
        targetEl.style.setProperty('border-radius', '8px', 'important');
        targetEl.style.setProperty('padding', '3px 8px', 'important');
        targetEl.style.setProperty('box-shadow', '0 2px 8px rgba(34, 197, 94, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1)', 'important');
        targetEl.style.setProperty('transform', 'translateY(-1px)', 'important');
    } else if (state === '2') {
        targetEl.style.setProperty('background', 'linear-gradient(135deg, rgba(234, 179, 8, 0.3) 0%, rgba(234, 179, 8, 0.4) 100%)', 'important');
        targetEl.style.setProperty('border-radius', '8px', 'important');
        targetEl.style.setProperty('padding', '3px 8px', 'important');
        targetEl.style.setProperty('box-shadow', '0 2px 8px rgba(234, 179, 8, 0.35), inset 0 1px 2px rgba(255, 255, 255, 0.15)', 'important');
        targetEl.style.setProperty('transform', 'translateY(-1px)', 'important');
    } else if (state === '3') {
        targetEl.style.setProperty('background', 'linear-gradient(135deg, rgba(239, 68, 68, 0.25) 0%, rgba(239, 68, 68, 0.35) 100%)', 'important');
        targetEl.style.setProperty('border-radius', '8px', 'important');
        targetEl.style.setProperty('padding', '3px 8px', 'important');
        targetEl.style.setProperty('box-shadow', '0 2px 8px rgba(239, 68, 68, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1)', 'important');
        targetEl.style.setProperty('transform', 'translateY(-1px)', 'important');
    }
}

clonedWords.forEach(function(clonedWord, idx) {
    const originalWord = originalWords[idx];
    if (!originalWord) return;

    // ✅ FIX KRITIS: removeAttribute BENAR-BENAR menghapus onclick inline.
    // setAttribute('onclick','') TIDAK efektif — onclick handler tetap aktif sebagai DOM property.
    clonedWord.removeAttribute('onclick');
    clonedWord.onclick = null;

    clonedWord.style.cursor = 'pointer';

    // State awal: ikuti original (kalau sudah pernah di-mark)
    const initState = originalWord.getAttribute('data-mark-state') || '0';
    clonedWord.setAttribute('data-mark-state', initState);
    applyMarkStyleToClone(clonedWord, initState);

    // Handler klik: trigger markWord di original, lalu apply style ke clone
    clonedWord.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();  // ← cegah onclick inline yang mungkin masih nyangkut

    console.log('🖱️ PROXY CLICK fired for word #' + idx);  // ← debug

    // ✅ Manual cycle state agar konsisten (tidak tergantung markWord mana yang aktif)
    const currentState = parseInt(originalWord.getAttribute('data-mark-state') || '0');
    const newState = ((currentState + 1) % 4).toString();

    // Update state di ORIGINAL
    // Update state di ORIGINAL
    originalWord.setAttribute('data-mark-state', newState);
    originalWord.dataset.markState = newState;

    // ✅ DEBUG: verifikasi state benar-benar ter-set
    console.log('✏️ Original word #' + idx + ' state: ' + originalWord.getAttribute('data-mark-state'));

    // ✅ Bersihkan style lama di original (background, classes flat marking)
    originalWord.classList.remove(
        'bg-green-200', 'bg-yellow-200', 'bg-red-200',
        'dark:bg-green-800', 'dark:bg-yellow-800', 'dark:bg-red-800',
        'border-l-4', 'border-green-500', 'border-yellow-500', 'border-red-500'
    );
    originalWord.style.backgroundColor = '';
    originalWord.style.background      = '';
    originalWord.style.borderRadius    = '';
    originalWord.style.padding         = '';
    originalWord.style.boxShadow       = '';
    originalWord.style.transform       = '';

    // ✅ Apply style GRADIENT SMOOTH (sama persis dengan desktop) ke original
    applyMarkStyleToClone(originalWord, newState);

    // ✅ Apply style yang sama ke clone (visual sinkron)
    clonedWord.setAttribute('data-mark-state', newState);
    clonedWord.dataset.markState = newState;
    applyMarkStyleToClone(clonedWord, newState);

    // ✅ Update counter SECARA MANUAL berdasarkan state di #quranDisplay
    updateCounterFromOriginal();
});

// ✅ Fungsi helper: hitung counter dari #quranDisplay & update display + hidden inputs + global kesalahanCount
// ✅ Fungsi helper: hitung counter dari #quranDisplay & update display + hidden inputs + global kesalahanCount
function updateCounterFromOriginal() {
    const quranDisplay = document.getElementById('quranDisplay');
    if (!quranDisplay) return;

    const kelancaranCount = quranDisplay.querySelectorAll('[data-mark-state="1"]').length;
    const tajwidCount     = quranDisplay.querySelectorAll('[data-mark-state="2"]').length;
    const pelafalanCount  = quranDisplay.querySelectorAll('[data-mark-state="3"]').length;
    const totalKesalahan  = kelancaranCount + tajwidCount + pelafalanCount;

    // ✅ FIX: Samakan rumus dengan desktop (setoran-interactive.js → updateKesalahanDisplay)
    // - Total kata < 100 → setiap kesalahan = -1 poin (fix)
    // - Total kata ≥ 100 → kesalahan dihitung sebagai persentase dari total kata
    const totalKata = quranDisplay.querySelectorAll('.word-item').length;
    let nilai = 100;
    if (totalKata > 0) {
        nilai = totalKata < 100
            ? 100 - totalKesalahan
            : 100 - (totalKesalahan / totalKata) * 100;
        nilai = Math.max(0, Math.min(100, Math.round(nilai)));
    }
    console.log(`📊 Mobile nilai calc: totalKata=${totalKata}, kesalahan=${totalKesalahan}, nilai=${nilai}`);

    // ✅ FIX KRITIS: Update juga variabel GLOBAL `kesalahanCount` di setoran-interactive.js
    // generatePDFContent() membaca dari variable global ini untuk render tabel "Detail Koreksi".
    // Tanpa update ini, PDF akan selalu tampilkan "-" di kolom Jumlah & Nilai = 100.
    if (typeof window.kesalahanCount !== 'undefined') {
        window.kesalahanCount.kelancaran = kelancaranCount;
        window.kesalahanCount.tajwid     = tajwidCount;
        window.kesalahanCount.pelafalan  = pelafalanCount;
        console.log('🔢 Global kesalahanCount synced:', window.kesalahanCount);
    }

    // Update untuk kedua mode (Combined & SetoranOnly) — yang aktif saja yang berdampak
    ['Combined', 'SetoranOnly'].forEach(function(mode) {
        const setVal  = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setVal('kesalahanKelancaran' + mode, kelancaranCount);
        setVal('kesalahanTajwid'     + mode, tajwidCount);
        setVal('kesalahanPelafalan'  + mode, pelafalanCount);
        setVal('nilai'               + mode, nilai);

        setText('displayKelancaran' + mode, kelancaranCount);
        setText('displayTajwid'     + mode, tajwidCount);
        setText('displayPelafalan'  + mode, pelafalanCount);
        setText('displayNilai'      + mode, nilai);
    });
}
});

        console.log('✅ Drawer: content synced + click proxy attached for', clonedWords.length, 'words');
        return true;
    }   

            // Referensi drawer untuk timer management
            const drawerEl = document.getElementById('mobileDrawer');

            // ✅ Coba sync langsung (Quran sudah dimuat sebelumnya)
            if (syncContentToDrawer()) {
                // Sync statistik langsung
                syncStatistikToDrawer();
                // Jalankan interval sync statistik selama drawer terbuka (realtime update)
                if (drawerEl) {
                    drawerEl._statSyncTimer = setInterval(syncStatistikToDrawer, 500);
                }
            } else {
                // Belum siap — tampilkan loading dan polling
                clonedQuranDisplay.innerHTML = '<p style="color:#9ca3af;text-align:center;padding:40px 20px;font-family:system-ui;">⏳ Memuat Al-Qur\'an...</p>';

                console.log('⏳ Drawer: waiting for #quranDisplay to be populated...');
                let attempts = 0;
                const maxAttempts = 60; // 30 detik max
                const pollTimer = setInterval(() => {
                    attempts++;
                    if (syncContentToDrawer()) {
                        clearInterval(pollTimer);
                        if (drawerEl) drawerEl._pollTimer = null;
                        // ✅ Sync statistik setelah konten berhasil dimuat
                        syncStatistikToDrawer();
                        // Mulai interval sync statistik
                        if (drawerEl) {
                            drawerEl._statSyncTimer = setInterval(syncStatistikToDrawer, 500);
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(pollTimer);
                        if (drawerEl) drawerEl._pollTimer = null;
                        console.warn('⚠️ Drawer: timeout waiting for Quran content');
                        clonedQuranDisplay.innerHTML = '<p style="color:#ef4444;text-align:center;padding:20px;font-family:system-ui;">⚠️ Gagal memuat Al-Qur\'an. Tutup drawer dan coba lagi.</p>';
                    }
                }, 500);

                // Simpan timer ke drawer agar bisa dibatalkan saat ditutup
                if (drawerEl) drawerEl._pollTimer = pollTimer;
            }
        }

        // ✅ Ubah ID elemen statistik di dalam clone agar tidak duplikat dengan desktop
        // ✅ Ubah ID elemen statistik di dalam clone agar tidak duplikat dengan desktop
        var drawerOnlyIds = [
            'colorGuideContent', 'colorGuideIcon',
            'displayTotalKata', 'displayJuzRange', 'displayTotalJuz',
            'displayPageStart', 'displayPageEnd', 'displayTotalPage'
        ];
        drawerOnlyIds.forEach(function(id) {
            var el = quranClone.querySelector('#' + id);
            if (el) el.id = id + 'Drawer';
        });

        // Hapus header desktop (tombol Kembali & Reset di dalam clone)
        const desktopHeader = quranClone.querySelector('.hidden.md\\:flex');
        if (desktopHeader) desktopHeader.remove();

        // ✅ FIX: Hapus blok Statistik Setoran dari clone agar tidak double
        // Yang dipakai adalah blok Statistik Setoran di audioWrapper (sudah punya nilai langsung)
        const statBlockInClone = quranClone.querySelector('#displayTotalKataDrawer')?.closest('.bg-gray-800\\/50.border.border-gray-700\\/50.p-3.rounded-xl');
        if (statBlockInClone) statBlockInClone.remove();

        // Tentukan mode aktif
        const activeMode = (lastSelectedMode === 'setoran_only') ? 'SetoranOnly' : 'Combined';

        // Ambil nilai kesalahan & nilai dari hidden inputs form utama
        const valKelancaran = document.getElementById('kesalahanKelancaran' + activeMode)?.value || '0';
        const valTajwid     = document.getElementById('kesalahanTajwid'     + activeMode)?.value || '0';
        const valPelafalan  = document.getElementById('kesalahanPelafalan'  + activeMode)?.value || '0';
        const valNilai      = document.getElementById('nilai'               + activeMode)?.value || '100';
        const valTotalKata  = document.getElementById('totalKata'           + activeMode)?.value || '0';

        // Ambil nilai statistik dari elemen display asli untuk ditampilkan langsung
        const valTotalKataDisplay = document.getElementById('displayTotalKata')?.textContent || valTotalKata || '0';
        const valJuzRange         = document.getElementById('displayJuzRange')?.textContent   || '-';
        const valTotalJuz         = document.getElementById('displayTotalJuz')?.textContent   || '0.00';
        const valPageStart        = document.getElementById('displayPageStart')?.textContent  || '-';
        const valPageEnd          = document.getElementById('displayPageEnd')?.textContent    || '-';
        const valTotalPage        = document.getElementById('displayTotalPage')?.textContent  || '0.00';

        // Ambil nilai catatan guru yang sudah ada
        const valCatatan = document.getElementById('catatanGuru' + activeMode)?.value || '';

        // Tentukan warna dan label tombol submit berdasarkan mode
        const isSetoranOnly = activeMode === 'SetoranOnly';
        const submitColor   = isSetoranOnly
            ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-900/30'
            : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-900/30';
        const submitLabel   = isSetoranOnly ? '💾 Simpan Setoran Tambahan' : '💾 Simpan Presensi & Setoran';

        // ✅ Buat section Audio & Penilaian + Statistik Setoran + Catatan Guru + Submit untuk drawer mobile
        const audioWrapper = document.createElement('div');
        audioWrapper.className = 'space-y-3 mt-3';
        audioWrapper.innerHTML = `
            <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 bg-gray-800/60 border-b border-gray-700/50">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-5 h-5 bg-blue-600 rounded flex items-center justify-center text-[10px]">🎤</span>
                        Audio & Penilaian
                    </span>
                    <button type="button" onclick="clearMarking()"
                            class="px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs rounded-lg transition flex items-center gap-1">
                        🗑️ Reset Marking
                    </button>
                </div>
                <div class="px-4 pb-4 pt-3 space-y-3">
                    <div class="flex gap-2">
                        <button type="button" id="startRecording${activeMode}"
                                onclick="startRecording('${activeMode}')"
                                class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                            ⏺️ Rekam
                        </button>
                        <button type="button" id="pauseRecording${activeMode}"
                                onclick="pauseRecording('${activeMode}')"
                                class="hidden flex-1 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium rounded-lg transition items-center justify-center gap-1.5">
                            ⏸️ Jeda
                        </button>
                        <button type="button" id="stopRecording${activeMode}"
                                onclick="stopRecording('${activeMode}')"
                                class="hidden flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition items-center justify-center gap-1.5">
                            ⏹️ Stop
                        </button>
                    </div>
                    <div id="recordingTimer${activeMode}"
                         class="hidden text-xs font-bold text-blue-400 text-center py-2 bg-blue-950/50 rounded-lg border border-blue-800/50">
                        ⏱️ Durasi: 0:00
                    </div>
                    <audio id="audioPlayback${activeMode}" controls class="w-full hidden rounded-lg"></audio>
                    <button type="button" id="clearRecording${activeMode}"
                            onclick="clearRecording('${activeMode}')"
                            class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition hidden flex items-center justify-center gap-1">
                        🗑️ Hapus & Rekam Ulang
                    </button>
                    <input type="hidden" id="audioData${activeMode}">
                    <p class="text-[10px] text-gray-600 italic">💡 Gunakan Jeda untuk berhenti sementara. Marking kata di atas untuk catat kesalahan.</p>

                    {{-- ✅ STATISTIK SETORAN DI DRAWER MOBILE --}}
                    <div class="bg-gray-800/50 border border-gray-700/50 p-3 rounded-xl mt-2">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">📊 Statistik Setoran</h4>
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <div class="bg-gray-900 p-2.5 rounded-lg">
                                <div class="text-[10px] text-gray-500 mb-0.5">Total Kata</div>
                                <div class="text-xl font-bold text-blue-400" id="displayTotalKataDrawer">${valTotalKataDisplay}</div>
                            </div>
                            <div class="bg-gray-900 p-2.5 rounded-lg">
                                <div class="text-[10px] text-gray-500 mb-0.5">Juz Range</div>
                                <div class="text-lg font-bold text-purple-400" id="displayJuzRangeDrawer">${valJuzRange}</div>
                            </div>
                            <div class="bg-gray-900 p-2.5 rounded-lg">
                                <div class="text-[10px] text-gray-500 mb-0.5">Total Juz</div>
                                <div class="text-xl font-bold text-white" id="displayTotalJuzDrawer">${valTotalJuz}</div>
                            </div>
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] text-gray-500 font-medium">📄 Halaman Mushaf Madinah</span>
                                <span class="text-[10px] text-gray-600">⚙️ Otomatis</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-800 px-2.5 py-2 rounded-lg border border-gray-700">
                                    <div class="text-[10px] text-gray-500">Mulai</div>
                                    <div class="text-base font-bold text-orange-400" id="displayPageStartDrawer">${valPageStart}</div>
                                </div>
                                <div class="text-gray-600">→</div>
                                <div class="flex-1 bg-gray-800 px-2.5 py-2 rounded-lg border border-gray-700">
                                    <div class="text-[10px] text-gray-500">Selesai</div>
                                    <div class="text-base font-bold text-orange-400" id="displayPageEndDrawer">${valPageEnd}</div>
                                </div>
                                <div class="flex-1 bg-orange-950/50 px-2.5 py-2 rounded-lg border border-orange-900/30">
                                    <div class="text-[10px] text-gray-500">Total</div>
                                    <div class="text-base font-bold text-orange-400" id="displayTotalPageDrawer">${valTotalPage}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between col-span-2">
                            <span class="text-xs text-gray-400">🟢 Kelancaran</span>
                            <strong id="displayKelancaran${activeMode}" class="text-base text-emerald-400">${valKelancaran}</strong>
                        </div>
                        <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                            <span class="text-xs text-gray-400">🟡 Tajwid</span>
                            <strong id="displayTajwid${activeMode}" class="text-base text-amber-400">${valTajwid}</strong>
                        </div>
                        <div class="bg-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                            <span class="text-xs text-gray-400">🔴 Pelafalan</span>
                            <strong id="displayPelafalan${activeMode}" class="text-base text-red-400">${valPelafalan}</strong>
                        </div>
                        <div class="bg-indigo-950 border border-indigo-800/50 rounded-lg p-2.5 flex items-center justify-between col-span-2">
                            <span class="text-xs font-bold text-gray-300">📊 Nilai</span>
                            <strong id="displayNilai${activeMode}" class="text-2xl text-indigo-400">${valNilai}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <label class="block text-xs font-medium text-gray-400 mb-2">📝 Catatan Guru</label>
                <textarea id="drawerCatatanGuru${activeMode}" rows="3"
                          class="form-textarea w-full"
                          placeholder="Tambahkan catatan...">${valCatatan}</textarea>
            </div>

            <button type="button"
                    onclick="submitFromDrawer('${activeMode}')"
                    class="w-full py-3 ${submitColor} text-white font-semibold text-sm rounded-xl shadow-lg transition active:scale-95">
                ${submitLabel}
            </button>`;

        content.innerHTML = '';
        content.appendChild(quranClone);
        content.appendChild(audioWrapper);

        // Simpan mode untuk sync saat drawer ditutup
        drawer._syncAudioMode = activeMode;

        // ✅ Jalankan sync statistik sekali lagi setelah DOM drawer selesai dirender
        setTimeout(syncStatistikToDrawer, 100);
        setTimeout(syncStatistikToDrawer, 600);
        setTimeout(syncStatistikToDrawer, 1500);
    }

    overlay.classList.remove('hidden');
    drawer.classList.remove('hidden');
    requestAnimationFrame(() => drawer.classList.add('open'));
    document.body.style.overflow = 'hidden';
}

function closeDrawer() {
    const overlay = document.getElementById('mobileDrawerOverlay');
    const drawer  = document.getElementById('mobileDrawer');

    // ✅ Hentikan polling timer konten Al-Quran
    if (drawer._pollTimer) {
        clearInterval(drawer._pollTimer);
        drawer._pollTimer = null;
        console.log('🛑 Drawer poll timer cleared on close');
    }

    // ✅ Hentikan interval sync statistik
    if (drawer._statSyncTimer) {
        clearInterval(drawer._statSyncTimer);
        drawer._statSyncTimer = null;
        console.log('🛑 Drawer stat sync timer cleared on close');
    }

    // Sync audioData dari drawer ke hidden input form utama sebelum tutup
    if (drawer._syncAudioMode) {
        const mode = drawer._syncAudioMode;
        const drawerAudio = document.getElementById('audioData' + mode);
        const formId = mode === 'SetoranOnly' ? 'formSetoranOnly' : 'formCombined';
        const formAudio = document.querySelector('#' + formId + ' [name="audio_data"]');
        if (drawerAudio && formAudio && drawerAudio.value) {
            formAudio.value = drawerAudio.value;
        }
        drawer._syncAudioMode = null;
    }

    drawer.classList.remove('open');
    setTimeout(() => {
        drawer.classList.add('hidden');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

// ✅ Submit dari drawer: drawer TETAP TERBUKA + loading overlay sampai server response.
//    f.submit() native akan navigate halaman, jadi user lihat loading sebelum redirect.
async function submitFromDrawer(mode) {
    const formId = mode === 'SetoranOnly' ? 'formSetoranOnly' : 'formCombined';
    const drawer = document.getElementById('mobileDrawer');
 
    showSubmitLoadingOverlay();
 
    const drawerCatatan = document.getElementById('drawerCatatanGuru' + mode);
    const formCatatan   = document.querySelector('#' + formId + ' [name="catatan_guru"]');
    if (drawerCatatan && formCatatan) {
        formCatatan.value = drawerCatatan.value;
    }
 
    const drawerAudio = document.getElementById('audioData' + mode);
    const formAudio   = document.querySelector('#' + formId + ' [name="audio_data"]');
    if (drawerAudio && formAudio && drawerAudio.value) {
        formAudio.value = drawerAudio.value;
    }
 
    const submitBtnDrawer = document.querySelector(`button[onclick="submitFromDrawer('${mode}')"]`);
    if (submitBtnDrawer) {
        submitBtnDrawer.innerHTML = '⏳ Menyimpan...';
        submitBtnDrawer.disabled  = true;
    }
 
    updateSubmitLoadingText('📸 Membuat PDF dengan marking...');
 
    const deviceType = window.innerWidth <= 768  ? 'mobile'
                     : window.innerWidth <= 1024 ? 'tablet'
                     : 'desktop';
 
    try {
        if (typeof window.generateAndSavePDFToInput === 'function') {
            await window.generateAndSavePDFToInput(mode, deviceType);
        }
    } catch (err) {
        console.warn('⚠️ PDF regenerate failed:', err.message);
    }
 
    updateSubmitLoadingText('💾 Menyimpan ke server...');
 
    // ✅✅✅ FIX v4.0: Schedule notifikasi LANGSUNG SEBELUM form.submit()
    // Ini PASTI jalan karena tidak bergantung pada submit event listener.
    const successMsg = mode === 'SetoranOnly'
        ? ' Setoran tambahan berhasil disimpan!'
        : ' Presensi & setoran berhasil disimpan!';
    if (typeof window.scheduleNotification === 'function') {
        window.scheduleNotification(successMsg, 'success');
    }
 
    // Stop timer drawer
    if (drawer._pollTimer) { clearInterval(drawer._pollTimer); drawer._pollTimer = null; }
    if (drawer._statSyncTimer) { clearInterval(drawer._statSyncTimer); drawer._statSyncTimer = null; }
    drawer._syncAudioMode = null;
 
    // Tutup drawer
    drawer.classList.remove('open');
    const drawerOverlay = document.getElementById('mobileDrawerOverlay');
    if (drawerOverlay) drawerOverlay.classList.add('hidden');
    drawer.classList.add('hidden');
    document.body.style.overflow = '';
 
    setTimeout(() => {
        const form = document.getElementById(formId);
        if (form) {
            form.submit();
        } else {
            hideSubmitLoadingOverlay();
            alert('❌ Form tidak ditemukan');
        }
    }, 300);
}
 

// ✅ Helper: tampilkan loading overlay full screen di atas drawer
function showSubmitLoadingOverlay() {
    // Hapus dulu kalau sudah ada (cegah double)
    const existing = document.getElementById('submitLoadingOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'submitLoadingOverlay';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(4px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: white;
        font-family: system-ui, -apple-system, sans-serif;
    `;
    overlay.innerHTML = `
        <div style="width:56px;height:56px;border:4px solid rgba(99,102,241,0.3);border-top-color:#6366f1;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
        <div id="submitLoadingText" style="font-size:14px;font-weight:500;text-align:center;max-width:280px;padding:0 20px;">
            ⏳ Memproses...
        </div>
        <div style="font-size:11px;color:#9ca3af;text-align:center;padding:0 20px;">
            Mohon tunggu, jangan tutup halaman
        </div>
        <style>
            @keyframes spin { to { transform: rotate(360deg); } }
        </style>
    `;
    document.body.appendChild(overlay);
}

// ✅ Helper: update teks loading
function updateSubmitLoadingText(text) {
    const el = document.getElementById('submitLoadingText');
    if (el) el.textContent = text;
}

// ✅ Helper: hapus loading overlay (untuk fallback error)
function hideSubmitLoadingOverlay() {
    const overlay = document.getElementById('submitLoadingOverlay');
    if (overlay) overlay.remove();
}

let lastSelectedMode = null;

function toggleMode(radio) {
    if (lastSelectedMode === radio.value) {
        setTimeout(() => { radio.checked = false; }, 0);
        lastSelectedMode = null;
        resetToInitialState();
    } else {
        lastSelectedMode = radio.value;
    }
}

function resetToInitialState() {
    ['formCombined', 'formPresensiOnly', 'formSetoranOnly'].forEach(id => {
        const el = document.getElementById(id); if (el) el.classList.add('hidden');
    });
    const hint    = document.getElementById('hintBelumPilih');
    const divider = document.getElementById('hrDivider');
    if (hint)    hint.classList.remove('hidden');
    if (divider) divider.classList.add('hidden');
    showPanel('panelPetunjuk');
    document.getElementById('mobileFab')?.classList.add('hidden');
}

function switchMode(mode) {
    lastSelectedMode = mode;
    ['formCombined', 'formPresensiOnly', 'formSetoranOnly'].forEach(id => {
        const el = document.getElementById(id); if (el) el.classList.add('hidden');
    });
    const hint    = document.getElementById('hintBelumPilih');
    const divider = document.getElementById('hrDivider');
    if (hint)    hint.classList.add('hidden');
    if (divider) divider.classList.remove('hidden');
    const formMap = { combined: 'formCombined', presensi_only: 'formPresensiOnly', setoran_only: 'formSetoranOnly' };
    const formEl = document.getElementById(formMap[mode]);
    if (formEl) formEl.classList.remove('hidden');
    showPanel('defaultView');
    loadPresensiHariIni();
    document.getElementById('mobileFab')?.classList.remove('hidden');
}

function showPanel(panelId) {
    ['panelPetunjuk', 'defaultView', 'quranPanel'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (id === panelId) { el.style.display = ''; el.classList.remove('hidden'); }
        else { el.style.display = 'none'; el.classList.add('hidden'); }
    });
}

function showQuranPanel() {
    showPanel('quranPanel');}

function backToDefaultView() {
    // ✅ FIX: JANGAN clear #quranDisplay & statistik.
    // Marking & konten Al-Qur'an HARUS tetap tersimpan biar saat user balik
    // tidak perlu fetch ulang. loadQuranCombined/SetoranOnly akan tetap reload
    // kalau surat+ayat berubah (lihat fix #2 di bawah).
    showPanel('defaultView');
}

// ✅ Helper: ambil "fingerprint" range Quran aktif (surat & ayat start–end)
// Dipakai untuk deteksi apakah user request surat+ayat yang sama atau berbeda.
function getCurrentQuranKey(mode) {
    const s1 = document.getElementById('suratStart' + mode)?.value || '';
    const a1 = document.getElementById('ayatStart'  + mode)?.value || '';
    const s2 = document.getElementById('suratEnd'   + mode)?.value || '';
    const a2 = document.getElementById('ayatEnd'    + mode)?.value || '';
    return `${s1}|${a1}|${s2}|${a2}`;
}

// ✅ Helper: cek apakah #quranDisplay sudah berisi range yang sama
function isQuranAlreadyLoaded(mode) {
    const quranDisplay = document.getElementById('quranDisplay');
    if (!quranDisplay) return false;
    const html = quranDisplay.innerHTML.trim();

    // Belum ada konten sama sekali
    if (html.length < 100 || html.includes('Memuat')) return false;

    // Cek apakah range yang sekarang dipilih = range yang terakhir di-load
    const currentKey = getCurrentQuranKey(mode);
    const lastKey    = quranDisplay.dataset.loadedKey || '';
    return currentKey === lastKey && currentKey !== '|||';
}

function toggleSection(id) {
    const section = document.getElementById(id);
    const btn = section?.previousElementSibling;
    if (!section) return;
    const isHidden = section.classList.contains('hidden');
    section.classList.toggle('hidden', !isHidden);
    btn?.classList.toggle('section-open', isHidden);
}

async function loadPresensiHariIni() {
    await loadPresensiHariIniToTarget(null);
}

async function loadPresensiHariIniToTarget(target) {
    try {
        const tanggalInput = document.getElementById('tanggalCombined');
        const tanggal = tanggalInput ? tanggalInput.value : '{{ $tanggalHariIni }}';
        const response = await fetch(`/api/presensi/hari-ini?tanggal=${tanggal}`, {
            method: 'GET', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        const getEl = (id) => target ? target.querySelector(`#${id}`) : document.getElementById(id);
        getEl('statHadir').textContent = data.hadir  || 0;
        getEl('statSakit').textContent = data.sakit  || 0;
        getEl('statIzin').textContent  = data.izin   || 0;
        getEl('statAlpha').textContent = data.alpha  || 0;
        const sesiHeaders    = data.sesi_aktif || [];
        const tableHeaderRow = getEl('tableHeaderRow');
        while (tableHeaderRow.children.length > 2) tableHeaderRow.removeChild(tableHeaderRow.lastChild);
        sesiHeaders.forEach(sesi => {
            const th = document.createElement('th');
            th.className = 'px-2 py-2 text-center text-gray-400 font-medium w-14';
            th.textContent = sesi;
            tableHeaderRow.appendChild(th);
        });
        const tableBody = getEl('tableBodyPresensi');
        if (data.santri_list && data.santri_list.length > 0) {
            tableBody.innerHTML = data.santri_list.map((santri, index) => {
                const sesiCells = sesiHeaders.map(sesi => {
                    const p = santri.sesi_data && santri.sesi_data[sesi];
                    if (p) {
                        const colorMap = { Hadir:'bg-emerald-500', Sakit:'bg-orange-500', Izin:'bg-amber-500', Alpha:'bg-red-500' };
                        const colorClass = colorMap[p.status] || 'bg-gray-500';
                        const jumlahSetoran = p.jumlah_setoran || 0;
                        const waktu = p.waktu || '';
                        return `<td class="px-1 py-2"><div class="flex flex-col items-center gap-0.5"><div class="flex items-center gap-1"><div class="w-4 h-4 rounded-full ${colorClass}" title="${p.status}${waktu ? ' - ' + waktu : ''}"></div>${jumlahSetoran > 0 ? `<span class="text-[9px] bg-indigo-950 text-indigo-400 rounded px-0.5">📖${jumlahSetoran}</span>` : ''}</div>${waktu ? `<span class="text-[8px] text-gray-600 font-mono">${waktu}</span>` : ''}</div></td>`;
                    }
                    return `<td class="px-1 py-2"><div class="flex justify-center"><div class="w-4 h-4 rounded-full border border-gray-700"></div></div></td>`;
                }).join('');
                return `<tr class="hover:bg-gray-800/30 transition-colors"><td class="px-2 py-2 text-gray-500 text-center">${index + 1}</td><td class="px-2 py-2 text-gray-300 font-medium">${santri.name}</td>${sesiCells}</tr>`;
            }).join('');
        } else {
            tableBody.innerHTML = `<tr><td colspan="${2 + sesiHeaders.length}" class="text-center py-6 text-gray-600 italic">Tidak ada santri di kelas ini.</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading presensi:', error);
        const el = target ? target.querySelector('#tableBodyPresensi') : document.getElementById('tableBodyPresensi');
        if (el) el.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-400 text-xs">❌ Error memuat data presensi</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', function () {
 
    // ✅ FIX v3.8: Hapus loading overlay yang mungkin masih nyangkut dari submit sebelumnya
    // (kalau browser cache rendering, overlay bisa muncul sebentar dan menutupi notifikasi)
    const staleOverlay = document.getElementById('submitLoadingOverlay');
    if (staleOverlay) {
        staleOverlay.remove();
        console.log('🧹 Stale submit loading overlay removed on page load');
    }
 
    // ✅ FIX v3.8: Tutup drawer mobile yang mungkin masih state "open" dari sesi sebelumnya
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');
    if (drawer)  { drawer.classList.add('hidden');  drawer.classList.remove('open'); }
    if (overlay) { overlay.classList.add('hidden'); }
    document.body.style.overflow = '';
 
    // ============================================================
    // ✅ NOTIFICATION HANDLER — SINGLE SOURCE OF TRUTH
    // Cegah double-init dengan flag _notifHandlerAttached
    // ============================================================
    function setupNotificationHandler() {
        document.querySelectorAll('.success-notification, .error-notification, .warning-notification').forEach(alert => {
 
            // Cegah double-attach
            if (alert._notifHandlerAttached) return;
            alert._notifHandlerAttached = true;
 
            // Pastikan notifikasi terlihat (force display)
            alert.style.display = '';
            alert.style.opacity = '1';
            alert.style.visibility = 'visible';
 
            // Pastikan notifikasi di atas semua elemen lain
            // (z-index inline untuk override apapun)
            alert.style.zIndex = '300';
 
           const closeBtn = alert.querySelector('.close-notification');

            // Auto-hide untuk SEMUA jenis notifikasi setelah 10 detik
            const autoHideTimer = setTimeout(() => {
                if (!alert.isConnected) return; // sudah di-remove manual
                alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (alert.isConnected) alert.remove();
                }, 500);
            }, 10000);
 
            // Tombol close manual
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    if (autoHideTimer) clearTimeout(autoHideTimer);
                    alert.style.transition = 'opacity 0.3s';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        if (alert.isConnected) alert.remove();
                    }, 300);
                }, { once: true });
            }
        });
    }
 
    // ✅ Jalankan SEKARANG (DOM sudah ready)
    setupNotificationHandler();
 
    // ✅ Jalankan lagi setelah 100ms (jaga-jaga kalau ada notifikasi yang
    //    di-inject lewat JS sedikit telat, misalnya dari error handler)
    setTimeout(setupNotificationHandler, 100);
 
    // ✅ Jalankan lagi setelah 500ms (jaga-jaga untuk slow render di mobile)
    setTimeout(setupNotificationHandler, 500);
 
    // Handler tanggal change untuk refresh presensi
    document.getElementById('tanggalCombined')?.addEventListener('change', () => {
        const dv = document.getElementById('defaultView');
        if (dv && !dv.classList.contains('hidden')) loadPresensiHariIni();
    });
 
    console.log('✅ Notification handler v3.8 attached');
});

function toggleColorGuide(triggerBtn) {
    // ✅ FIX: cari elemen panduan warna berdasarkan tombol yang diklik (context-aware).
    // Drawer mobile pakai ID 'colorGuideContentDrawer', desktop pakai 'colorGuideContent'.
    // Tanpa fix ini, klik di drawer akan toggle elemen desktop (hidden) → user lihat nothing happens.
    let content, icon;

    if (triggerBtn) {
        // Cari di container yang sama dengan tombol (drawer ATAU desktop)
        const container = triggerBtn.closest('.border.border-gray-800.rounded-lg.overflow-hidden')
                       || triggerBtn.parentElement;
        if (container) {
            content = container.querySelector('[id^="colorGuideContent"]');
            icon    = container.querySelector('[id^="colorGuideIcon"]');
        }
    }

    // Fallback: deteksi drawer terbuka, prioritaskan elemen drawer
    if (!content || !icon) {
        const drawer = document.getElementById('mobileDrawer');
        const drawerOpen = drawer && drawer.classList.contains('open');

        if (drawerOpen) {
            content = document.getElementById('colorGuideContentDrawer');
            icon    = document.getElementById('colorGuideIconDrawer');
        }

        // Fallback terakhir: elemen desktop
        if (!content || !icon) {
            content = document.getElementById('colorGuideContent');
            icon    = document.getElementById('colorGuideIcon');
        }
    }

    if (content && icon) {
        content.classList.toggle('hidden');
        icon.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    } else {
        console.warn('⚠️ toggleColorGuide: elemen tidak ditemukan');
    }
}

function clearMarking() {
    document.querySelectorAll('.word-item, .quran-word').forEach(word => {
        word.classList.remove('bg-green-400', 'bg-yellow-400', 'bg-red-400', 'text-white');
        word.style.backgroundColor = '';
        if (word.dataset.markState !== undefined) word.dataset.markState = '0';
    });
    [
        ['kesalahanKelancaranCombined',    'displayKelancaranCombined'],
        ['kesalahanTajwidCombined',        'displayTajwidCombined'],
        ['kesalahanPelafalanCombined',     'displayPelafalanCombined'],
        ['kesalahanKelancaranSetoranOnly', 'displayKelancaranSetoranOnly'],
        ['kesalahanTajwidSetoranOnly',     'displayTajwidSetoranOnly'],
        ['kesalahanPelafalanSetoranOnly',  'displayPelafalanSetoranOnly'],
    ].forEach(([inputId, displayId]) => {
        const input   = document.getElementById(inputId);   if (input)   input.value = 0;
        const display = document.getElementById(displayId); if (display) display.textContent = '0';
    });
    ['displayNilaiCombined','displayNilaiSetoranOnly'].forEach(id => { const el = document.getElementById(id); if (el) el.textContent = '100'; });
    ['nilaiCombined','nilaiSetoranOnly'].forEach(id => { const el = document.getElementById(id); if (el) el.value = '100'; });
    if (typeof updateStatistikSetoran === 'function') updateStatistikSetoran();
    alert('🗑️ Semua marking telah dihapus');
}

// ============================================================
// ✅ FIX: Reset counter, display, hidden inputs, dan global
// kesalahanCount saat user pindah ke range setoran berbeda.
// ============================================================
function resetMarkingCounterForMode(mode) {
    console.log(`🔄 Reset marking counter untuk mode: ${mode}`);

    // 1. Reset variabel global kesalahanCount di setoran-interactive.js
    if (typeof window.kesalahanCount !== 'undefined') {
        window.kesalahanCount.kelancaran = 0;
        window.kesalahanCount.tajwid     = 0;
        window.kesalahanCount.pelafalan  = 0;
    }

    // 2. Reset hidden inputs untuk kedua mode (jaga-jaga)
    ['Combined', 'SetoranOnly'].forEach(function(m) {
        const setVal  = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setVal('kesalahanKelancaran' + m, 0);
        setVal('kesalahanTajwid'     + m, 0);
        setVal('kesalahanPelafalan'  + m, 0);
        setVal('nilai'               + m, 100);

        // 3. Reset display di form kiri (desktop)
        setText('displayKelancaran' + m, '0');
        setText('displayTajwid'     + m, '0');
        setText('displayPelafalan'  + m, '0');
        setText('displayNilai'      + m, '100');
    });

    console.log('✅ Counter direset — semua kembali ke 0, nilai 100');
}

// ============================================================
// ✅ FIX: Wrap loadQuranCombined & loadQuranSetoranOnly agar
// SKIP refresh ketika surat+ayat sama dengan yang sudah dimuat.
// Marking tetap utuh, tidak perlu fetch ulang dari API.
// ============================================================
(function() {
    function wrapLoader(fnName, mode) {
        // Tunggu sampai fungsi asli ter-define (dari mode-handler.js)
        const checkAndWrap = setInterval(() => {
            const originalFn = window[fnName];
            if (typeof originalFn !== 'function') return;
            if (originalFn._wrapped) { clearInterval(checkAndWrap); return; }

            window[fnName] = async function(...args) {
            // Cek: apakah surat+ayat sekarang = yang sudah ter-load?
            if (isQuranAlreadyLoaded(mode)) {
                console.log(`✅ ${fnName}: skip refresh — surat+ayat sama, marking tetap`);
                // Pastikan panel Al-Qur'an aktif (kalau user dari panel lain)
                showPanel('quranPanel');
                return; // Skip pemanggilan API
            }

            console.log(`🔄 ${fnName}: load baru — surat+ayat berbeda → reset counter`);

            // ✅ FIX: Reset counter & display SEBELUM load Quran baru
            // Karena range berbeda = setoran baru, jadi semua marking harus mulai dari 0
            resetMarkingCounterForMode(mode);

            // Panggil fungsi asli
            const result = await originalFn.apply(this, args);

            // Setelah loaded, simpan key range yang baru ke dataset
            setTimeout(() => {
                const quranDisplay = document.getElementById('quranDisplay');
                if (quranDisplay) {
                    const newKey = getCurrentQuranKey(mode);
                    quranDisplay.dataset.loadedKey = newKey;
                    console.log(`💾 Quran loaded key saved: ${newKey}`);
                }
                // ✅ Reset sekali lagi setelah load selesai (jaga-jaga kalau ada race condition)
                resetMarkingCounterForMode(mode);
            }, 500);

            return result;
        };
            window[fnName]._wrapped = true;
            console.log(`✅ Wrapped: ${fnName}`);
            clearInterval(checkAndWrap);
        }, 200);

        // Stop checking setelah 10 detik
        setTimeout(() => clearInterval(checkAndWrap), 10000);
    }

    wrapLoader('loadQuranCombined',    'Combined');
    wrapLoader('loadQuranSetoranOnly', 'SetoranOnly');
})();

</script>

{{-- ============================================================ --}}
{{-- JAVASCRIPT: CUSTOM SELECT WIDGET                             --}}
{{-- ============================================================ --}}
<script>
(function () {
    'use strict';
    const ALL_SELECTS = {
        'santriSelectCombined':    { searchable: false, placeholder: '-- Pilih Santri --' },
        'sesiSelectCombined':      { searchable: false, placeholder: '-- Pilih Sesi --' },
        'kategoriSetoranCombined': { searchable: false, placeholder: '-- Pilih --' },
        'suratStartCombined':      { searchable: true,  placeholder: '-- Pilih --',    noResultText: '❌ Tidak ditemukan' },
        'suratEndCombined':        { searchable: true,  placeholder: '-- Otomatis --', noResultText: '❌ Tidak ditemukan' },
        'ayatStartCombined':       { searchable: false, placeholder: '-- Pilih Surat --',  live: true },
        'ayatEndCombined':         { searchable: false, placeholder: '-- Pilih Ayat --',   live: true },
        'santriSelectSetoranOnly': { searchable: false, placeholder: '-- Pilih Santri --' },
        'sesiSelectSetoranOnly':   { searchable: false, placeholder: '-- Pilih Sesi --' },
        'kategoriSetoranOnly':     { searchable: false, placeholder: '-- Pilih --' },
        'suratStartSetoranOnly':   { searchable: true,  placeholder: '-- Pilih --',    noResultText: '❌ Tidak ditemukan' },
        'suratEndSetoranOnly':     { searchable: true,  placeholder: '-- Otomatis --', noResultText: '❌ Tidak ditemukan' },
        'ayatStartSetoranOnly':    { searchable: false, placeholder: '-- Pilih Surat --', live: true },
        'ayatEndSetoranOnly':      { searchable: false, placeholder: '-- Pilih Ayat --',  live: true },
    };
    const PLAIN_SELECTORS = ['#formPresensiOnly select[name="santri_id"]','#formPresensiOnly select[name="sesi"]'];
    const widgets = {};
    let openWidget = null;

    function buildWidget(selectEl, cfg) {
        const id = selectEl.id || ('cs_' + Math.random().toString(36).slice(2));
        if (!selectEl.id) selectEl.id = id;
        if (widgets[id]) return;
        const isDisabled = selectEl.disabled;
        const wrap = document.createElement('div'); wrap.className = 'cs-wrap';
        selectEl.parentNode.insertBefore(wrap, selectEl); wrap.appendChild(selectEl); selectEl.style.display = 'none';
        const btn = document.createElement('button'); btn.type = 'button';
        btn.className = 'cs-btn' + (isDisabled ? ' cs-disabled' : '');
        const txt = document.createElement('span'); txt.className = 'cs-txt ph'; txt.textContent = cfg.placeholder || '-- Pilih --';
        const arr = document.createElement('span'); arr.className = 'cs-arr'; arr.textContent = '▼';
        btn.appendChild(txt); btn.appendChild(arr); wrap.insertBefore(btn, selectEl);
        const drop = document.createElement('div'); drop.className = 'cs-drop';
        let searchEl = null;
        if (cfg.searchable) {
            searchEl = document.createElement('input');
            searchEl.type = 'text'; searchEl.className = 'cs-search'; searchEl.placeholder = '🔍 Ketik...';
            drop.appendChild(searchEl);
        }
        const list = document.createElement('ul'); list.className = 'cs-list'; drop.appendChild(list);
        const noRes = document.createElement('div'); noRes.className = 'cs-nores'; noRes.textContent = cfg.noResultText || 'Tidak ditemukan'; drop.appendChild(noRes);
        wrap.appendChild(drop);
        const w = { id, selectEl, btn, txt, drop, list, noRes, searchEl, cfg };
        widgets[id] = w; rebuildList(w);
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); if (selectEl.disabled) return;
            if (openWidget && openWidget !== w) closeWidget(openWidget);
            drop.classList.contains('open') ? closeWidget(w) : openWidgetFn(w);
        });
        list.addEventListener('click', (e) => {
            const li = e.target.closest('li'); if (!li || li.classList.contains('cs-gone')) return;
            pickItem(w, li.dataset.value, li.textContent.trim());
        });
        if (searchEl) {
            searchEl.addEventListener('input', () => filterList(w, searchEl.value));
            searchEl.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') { closeWidget(w); btn.focus(); return; }
                if (e.key === 'Enter') { const vis = visibleItems(w); const hi = vis.find(l => l.classList.contains('cs-hi')) || vis[0]; if (hi) pickItem(w, hi.dataset.value, hi.textContent.trim()); return; }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault(); const vis = visibleItems(w); const cur = vis.findIndex(l => l.classList.contains('cs-hi'));
                    vis.forEach(l => l.classList.remove('cs-hi'));
                    const nxt = Math.max(0, Math.min(e.key === 'ArrowDown' ? cur + 1 : cur - 1, vis.length - 1));
                    if (vis[nxt]) { vis[nxt].classList.add('cs-hi'); vis[nxt].scrollIntoView({ block: 'nearest' }); }
                }
            });
        }
        new MutationObserver(() => {
            btn.classList.toggle('cs-disabled', selectEl.disabled);
            if (selectEl.disabled) { closeWidget(w); txt.textContent = cfg.placeholder || '-- Pilih --'; txt.classList.add('ph'); }
        }).observe(selectEl, { attributes: true, attributeFilter: ['disabled'] });
        if (cfg.live) {
            new MutationObserver(() => { rebuildList(w); txt.textContent = cfg.placeholder || '-- Pilih --'; txt.classList.add('ph'); })
                .observe(selectEl, { childList: true, subtree: true });
        }
    }
    function rebuildList(w) {
        w.list.innerHTML = '';
        Array.from(w.selectEl.options).forEach((opt, idx) => {
            const li = document.createElement('li'); li.textContent = opt.text; li.dataset.value = opt.value;
            if (idx === 0 || opt.value === '') li.classList.add('cs-ph'); w.list.appendChild(li);
        });
    }
    function openWidgetFn(w) { if (w.selectEl.disabled) return; w.drop.classList.add('open'); w.btn.classList.add('open'); if (w.searchEl) { w.searchEl.value = ''; filterList(w, ''); setTimeout(() => w.searchEl.focus(), 50); } openWidget = w; }
    function closeWidget(w) { if (!w) return; w.drop.classList.remove('open'); w.btn.classList.remove('open'); if (openWidget === w) openWidget = null; }
    function pickItem(w, value, text) {
        w.selectEl.value = value;
        const selOpt = Array.from(w.selectEl.options).find(o => o.value === value);
        w.txt.textContent = selOpt ? selOpt.text : (text || w.cfg.placeholder || '-- Pilih --');
        w.txt.classList.toggle('ph', !value); closeWidget(w);
        w.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function filterList(w, query) {
        const q = query.trim().toLowerCase(); let anyVis = false;
        Array.from(w.list.querySelectorAll('li')).forEach(li => {
            li.classList.remove('cs-hi');
            if (li.classList.contains('cs-ph')) { li.classList.toggle('cs-gone', q.length > 0); return; }
            const match = q === '' || li.textContent.toLowerCase().includes(q);
            li.classList.toggle('cs-gone', !match); if (match) anyVis = true;
        });
        if (q.length > 0) { const first = w.list.querySelector('li:not(.cs-gone):not(.cs-ph)'); if (first) first.classList.add('cs-hi'); }
        w.noRes.style.display = (q.length > 0 && !anyVis) ? 'block' : 'none';
    }
    function visibleItems(w) { return Array.from(w.list.querySelectorAll('li:not(.cs-gone):not(.cs-ph)')); }
    function syncFromNative(id) { const w = widgets[id]; if (!w) return; const opt = w.selectEl.options[w.selectEl.selectedIndex]; if (opt) { w.txt.textContent = opt.text; w.txt.classList.toggle('ph', !opt.value); } }
    window._csSyncFromNative = syncFromNative;
    function patchAutoFill() {
        ['Combined', 'SetoranOnly'].forEach(mode => {
            const startEl = document.getElementById(`suratStart${mode}`);
            if (startEl) startEl.addEventListener('change', () => setTimeout(() => syncFromNative(`suratEnd${mode}`), 80));
        });
    }
    function init() {
        Object.entries(ALL_SELECTS).forEach(([id, cfg]) => { const el = document.getElementById(id); if (el) buildWidget(el, cfg); });
        PLAIN_SELECTORS.forEach(sel => { document.querySelectorAll(sel).forEach(el => { buildWidget(el, { searchable: false, placeholder: el.options[0]?.text || '-- Pilih --' }); }); });
        patchAutoFill();
        document.addEventListener('click', (e) => { if (openWidget && !openWidget.drop.contains(e.target) && !openWidget.btn.contains(e.target)) closeWidget(openWidget); });
        console.log('✅ Custom Select ready');
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 450)) : setTimeout(init, 450);
    window._csWidgets = widgets;
})();
</script>

{{-- ============================================================ --}}
{{-- JAVASCRIPT: HAFALAN INDICATOR                                 --}}
{{-- ============================================================ --}}
<script>
(function () {
    'use strict';
    let hafalanData = {}, currentSantriId = null, fetchPending = false;
    const TOTAL_AYAT = {
        "1. Al-Fatihah":7,"2. Al-Baqarah":286,"3. Ali 'Imran":200,"4. An-Nisa'":176,
        "5. Al-Ma'idah":120,"6. Al-An'am":165,"7. Al-A'raf":206,"8. Al-Anfal":75,
        "9. At-Taubah":129,"10. Yunus":109,"11. Hud":123,"12. Yusuf":111,
        "13. Ar-Ra'd":43,"14. Ibrahim":52,"15. Al-Hijr":99,"16. An-Nahl":128,
        "17. Al-Isra'":111,"18. Al-Kahf":110,"19. Maryam":98,"20. Taha":135,
        "21. Al-Anbiya'":112,"22. Al-Hajj":78,"23. Al-Mu'minun":118,"24. An-Nur":64,
        "25. Al-Furqan":77,"26. Asy-Syu'ara'":227,"27. An-Naml":93,"28. Al-Qasas":88,
        "29. Al-'Ankabut":69,"30. Ar-Rum":60,"31. Luqman":34,"32. As-Sajdah":30,
        "33. Al-Ahzab":73,"34. Saba'":54,"35. Fatir":45,"36. Yasin":83,
        "37. As-Saffat":182,"38. Sad":88,"39. Az-Zumar":75,"40. Ghafir":85,
        "41. Fussilat":54,"42. Asy-Syura":53,"43. Az-Zukhruf":89,"44. Ad-Dukhan":59,
        "45. Al-Jasiyah":37,"46. Al-Ahqaf":35,"47. Muhammad":38,"48. Al-Fath":29,
        "49. Al-Hujurat":18,"50. Qaf":45,"51. Az-Zariyat":60,"52. At-Tur":49,
        "53. An-Najm":62,"54. Al-Qamar":55,"55. Ar-Rahman":78,"56. Al-Waqi'ah":96,
        "57. Al-Hadid":29,"58. Al-Mujadilah":22,"59. Al-Hasyr":24,"60. Al-Mumtahanah":13,
        "61. As-Saff":14,"62. Al-Jumu'ah":11,"63. Al-Munafiqun":11,"64. At-Taghabun":18,
        "65. At-Talaq":12,"66. At-Tahrim":12,"67. Al-Mulk":30,"68. Al-Qalam":52,
        "69. Al-Haqqah":52,"70. Al-Ma'arij":44,"71. Nuh":28,"72. Al-Jinn":28,
        "73. Al-Muzzammil":20,"74. Al-Muddassir":56,"75. Al-Qiyamah":40,"76. Al-Insan":31,
        "77. Al-Mursalat":50,"78. An-Naba'":40,"79. An-Nazi'at":46,"80. 'Abasa":42,
        "81. At-Takwir":29,"82. Al-Infitar":19,"83. Al-Mutaffifin":36,"84. Al-Insyiqaq":25,
        "85. Al-Buruj":22,"86. At-Tariq":17,"87. Al-A'la":19,"88. Al-Ghasyiyah":26,
        "89. Al-Fajr":30,"90. Al-Balad":20,"91. Asy-Syams":15,"92. Al-Lail":21,
        "93. Ad-Duha":11,"94. Asy-Syarh":8,"95. At-Tin":8,"96. Al-'Alaq":19,
        "97. Al-Qadr":5,"98. Al-Bayyinah":8,"99. Az-Zalzalah":8,"100. Al-'Adiyat":11,
        "101. Al-Qari'ah":11,"102. At-Takasur":8,"103. Al-'Asr":3,"104. Al-Humazah":9,
        "105. Al-Fil":5,"106. Quraisy":4,"107. Al-Ma'un":7,"108. Al-Kausar":3,
        "109. Al-Kafirun":6,"110. An-Nasr":3,"111. Al-Masad":5,"112. Al-Ikhlas":4,
        "113. Al-Falaq":5,"114. An-Nas":6
    };
    async function fetchHafalanSantri(santriId) {
        if (!santriId) { hafalanData[null] = {}; return; }
        if (fetchPending) return;
        if (hafalanData[santriId]) { applyAllIndicators(); return; }
        fetchPending = true;
        try {
            const res = await fetch(`/guru/hafalan-santri?santri_id=${encodeURIComponent(santriId)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            hafalanData[santriId] = await res.json() || {};
        } catch (e) { hafalanData[santriId] = {}; }
        finally { fetchPending = false; applyAllIndicators(); }
    }
    function getActiveKategori(mode) { const id = mode === 'SetoranOnly' ? 'kategoriSetoranOnly' : 'kategoriSetoranCombined'; return document.getElementById(id)?.value || ''; }
    function isHafalanMode(mode) { return getActiveKategori(mode) === 'Hafalan'; }
    function getSuratStatus(suratName, santriId) {
        const data = hafalanData[santriId]; if (!data) return 'none';
        const ayatSelesai = data[suratName]; if (!ayatSelesai || ayatSelesai.length === 0) return 'none';
        const totalAyat = TOTAL_AYAT[suratName]; if (!totalAyat) return 'none';
        return [...new Set(ayatSelesai.map(Number))].length >= totalAyat ? 'full' : 'partial';
    }
    function isAyatSelesai(suratName, ayatNo, santriId) { const data = hafalanData[santriId]; if (!data) return false; return (data[suratName] || []).map(Number).includes(Number(ayatNo)); }
    function applyIndicatorToWidget(widgetId, mode) {
        const w = window._csWidgets?.[widgetId]; if (!w) return;
        const santriId = currentSantriId, isHafalan = isHafalanMode(mode);
        Array.from(w.list.querySelectorAll('li')).forEach(li => {
            li.querySelectorAll('.hi-indicator').forEach(el => el.remove());
            if (!isHafalan || !santriId || li.classList.contains('cs-ph') || !li.dataset.value) return;
            if (widgetId.startsWith('suratStart') || widgetId.startsWith('suratEnd')) {
                const status = getSuratStatus(li.dataset.value, santriId); if (status === 'none') return;
                const dot = document.createElement('span'); dot.className = 'hi-indicator';
                dot.style.cssText = `display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px;flex-shrink:0;vertical-align:middle;background:${status === 'full' ? '#22c55e' : '#f97316'};`;
                li.style.display = 'flex'; li.style.alignItems = 'center'; li.insertBefore(dot, li.firstChild);
            }
            if (widgetId.startsWith('ayatStart') || widgetId.startsWith('ayatEnd')) {
                const suratSelectId = widgetId.startsWith('ayatStart') ? widgetId.replace('ayatStart','suratStart') : widgetId.replace('ayatEnd','suratEnd');
                const suratName = document.getElementById(suratSelectId)?.value;
                const ayatNo = parseInt(li.dataset.value);
                if (!suratName || !ayatNo || !isAyatSelesai(suratName, ayatNo, santriId)) return;
                const chk = document.createElement('span'); chk.className = 'hi-indicator'; chk.textContent = '✓';
                chk.style.cssText = 'color:#22c55e;font-weight:bold;font-size:0.8rem;margin-right:4px;flex-shrink:0;';
                li.style.display = 'flex'; li.style.alignItems = 'center'; li.insertBefore(chk, li.firstChild);
            }
        });
    }
    function applyAllIndicators() {
        ['Combined','SetoranOnly'].forEach(mode => {
            ['suratStart','suratEnd','ayatStart','ayatEnd'].forEach(prefix => applyIndicatorToWidget(`${prefix}${mode}`, mode));
        });
    }
    function init() {
        ['santriSelectCombined','santriSelectSetoranOnly'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', function() {
                if (this.value === currentSantriId) return;
                currentSantriId = this.value || null; applyAllIndicators();
                if (currentSantriId) fetchHafalanSantri(currentSantriId);
            });
        });
        ['kategoriSetoranCombined','kategoriSetoranOnly'].forEach(id => document.getElementById(id)?.addEventListener('change', applyAllIndicators));
        ['Combined','SetoranOnly'].forEach(mode => {
            ['suratStart','suratEnd'].forEach(prefix => {
                document.getElementById(`${prefix}${mode}`)?.addEventListener('change', () => setTimeout(() => { applyIndicatorToWidget(`ayatStart${mode}`, mode); applyIndicatorToWidget(`ayatEnd${mode}`, mode); }, 150));
            });
            ['ayatStart','ayatEnd'].forEach(prefix => {
                const el = document.getElementById(`${prefix}${mode}`); if (!el) return;
                new MutationObserver(() => setTimeout(() => applyIndicatorToWidget(`${prefix}${mode}`, mode), 50)).observe(el, { childList: true });
            });
        });
        console.log('✅ Hafalan Indicator ready');
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 700)) : setTimeout(init, 700);
    window._hafalanIndicator = { hafalanData, getSuratStatus, isAyatSelesai, applyAllIndicators };
})();
</script>

<script>
// ============================================================
// ✅ CLIENT-SIDE NOTIFICATION v4.1 — JAMINAN MUNCUL + CEGAH DOUBLE
// ============================================================
(function() {
    'use strict';
 
    const STORAGE_KEY = 'pendingNotification';
 
    function showClientNotification(message, type = 'success') {
        document.querySelectorAll('.client-side-notif').forEach(el => el.remove());
 
        const colors = {
            success: { bg: '#059669', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            error:   { bg: '#dc2626', icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
            warning: { bg: '#d97706', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' }
        };
        const c = colors[type] || colors.success;
 
        const notif = document.createElement('div');
        notif.className = 'client-side-notif';
        notif.style.cssText = `
            position: fixed; top: 12px; right: 12px; left: 12px;
            z-index: 999999; max-width: 380px; margin-left: auto;
            animation: notifSlideIn 0.3s ease-out;
        `;
        notif.innerHTML = `
            <div style="
                background: ${c.bg}; color: white; padding: 14px 16px;
                border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.4);
                display: flex; align-items: flex-start; gap: 12px;
                font-family: system-ui, -apple-system, sans-serif;
            ">
                <svg style="width:20px;height:20px;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${c.icon}"/>
                </svg>
                <div style="flex:1;min-width:0;">
                    <p style="font-weight:500;font-size:14px;margin:0;line-height:1.4;">${message}</p>
                    
                </div>
                <button type="button" style="background:none;border:none;color:white;opacity:0.7;cursor:pointer;padding:0;flex-shrink:0;">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <style>
                @keyframes notifSlideIn  { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
                @keyframes notifSlideOut { from { opacity:1; transform:translateY(0); } to { opacity:0; transform:translateY(-20px); } }
            </style>
        `;
        document.body.appendChild(notif);
 
        const closeBtn = notif.querySelector('button');
        const removeNotif = () => {
            notif.style.animation = 'notifSlideOut 0.3s ease-out forwards';
            setTimeout(() => notif.remove(), 300);
        };
        closeBtn.addEventListener('click', removeNotif);
        setTimeout(removeNotif, 10000);
    }
 
    function scheduleNotification(message, type = 'success') {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                message: message, type: type, timestamp: Date.now()
            }));
            console.log('💾 Notifikasi dijadwalkan:', message);
        } catch (e) {
            console.warn('⚠️ sessionStorage tidak tersedia:', e);
        }
    }
 
    function checkPendingNotification() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return;
 
            const data = JSON.parse(raw);
 
            // Buang yang basi (>60 detik)
            if (Date.now() - (data.timestamp || 0) > 60000) {
                sessionStorage.removeItem(STORAGE_KEY);
                return;
            }
 
            // ✅ FIX v4.1: Cek apakah flash session server SUDAH RENDER notifikasi.
            // Kalau ada, JANGAN tampilkan client-side notif (cegah double).
            const serverNotif = document.querySelector(
                '.success-notification, .error-notification, .warning-notification'
            );
 
            if (serverNotif && serverNotif.isConnected) {
                console.log('ℹ️ Flash session sudah muncul → skip client-side notif (cegah double)');
                sessionStorage.removeItem(STORAGE_KEY);
                return;
            }
 
            // Flash session TIDAK muncul → tampilkan client-side sebagai backup
            sessionStorage.removeItem(STORAGE_KEY);
            console.log('📢 Flash session tidak muncul → tampilkan client-side notif:', data.message);
            showClientNotification(data.message, data.type || 'success');
        } catch (e) {
            console.warn('⚠️ Error membaca notifikasi:', e);
            sessionStorage.removeItem(STORAGE_KEY);
        }
    }
    // ☝️ INI "}" PENUTUP checkPendingNotification — JANGAN LUPA!
 
    // Export ke window
    window.showClientNotification   = showClientNotification;
    window.scheduleNotification     = scheduleNotification;
    window.checkPendingNotification = checkPendingNotification;
 
    // ✅ FIX v4.1: Wrap auto-check dengan delay 300ms agar flash session
    // server sempat ter-render dulu sebelum dicek.
    function delayedCheckPendingNotification() {
        setTimeout(checkPendingNotification, 300);
    }
 
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', delayedCheckPendingNotification);
    } else {
        delayedCheckPendingNotification();
    }
 
    // ✅ Pasang click handler langsung di tombol submit desktop
    function attachDesktopSubmitButtonHandler() {
        // Tombol submit form Combined
        const btnCombined = document.getElementById('submitBtnCombined');
        if (btnCombined && !btnCombined._notifClickAttached) {
            btnCombined._notifClickAttached = true;
            btnCombined.addEventListener('click', function() {
                const form = document.getElementById('formCombined');
                if (!form) return;
                const santriId = form.querySelector('[name="santri_id"]')?.value;
                const tanggal  = form.querySelector('[name="tanggal"]')?.value;
                const sesi     = form.querySelector('[name="sesi"]')?.value;
                if (!santriId || !tanggal || !sesi) return;
 
                window.scheduleNotification(' Presensi & setoran berhasil disimpan!', 'success');
            }, true);
            console.log('✅ Handler submit desktop Combined terpasang');
        }
 
        // Tombol submit form Setoran Only
        const btnSetoran = document.getElementById('submitBtnSetoranOnly');
        if (btnSetoran && !btnSetoran._notifClickAttached) {
            btnSetoran._notifClickAttached = true;
            btnSetoran.addEventListener('click', function() {
                const form = document.getElementById('formSetoranOnly');
                if (!form) return;
                const santriId = form.querySelector('[name="santri_id"]')?.value;
                const tanggal  = form.querySelector('[name="tanggal"]')?.value;
                const sesi     = form.querySelector('[name="sesi"]')?.value;
                if (!santriId || !tanggal || !sesi) return;
 
                window.scheduleNotification('✅ Setoran tambahan berhasil disimpan!', 'success');
            }, true);
            console.log('✅ Handler submit desktop SetoranOnly terpasang');
        }
 
        // Form Presensi Only — submit native
        const formPresensi = document.getElementById('formPresensiOnly');
        if (formPresensi && !formPresensi._notifClickAttached) {
            formPresensi._notifClickAttached = true;
            const submitBtn = formPresensi.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    const santriId = formPresensi.querySelector('[name="santri_id"]')?.value;
                    const tanggal  = formPresensi.querySelector('[name="tanggal"]')?.value;
                    const sesi     = formPresensi.querySelector('[name="sesi"]')?.value;
                    const status   = formPresensi.querySelector('[name="status"]:checked');
                    if (!santriId || !tanggal || !sesi || !status) return;
 
                    window.scheduleNotification(' Presensi berhasil disimpan!', 'success');
                }, true);
                console.log('✅ Handler submit desktop PresensiOnly terpasang');
            }
        }
    }
 
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachDesktopSubmitButtonHandler);
    } else {
        attachDesktopSubmitButtonHandler();
    }
    setTimeout(attachDesktopSubmitButtonHandler, 1000);
 
    console.log('✅ Client-side notification system v4.1 ready');
})();
</script>

{{-- JAVASCRIPT FILES (eksternal) --}}
<script src="{{ asset('js/complete-quran-mapping.js') }}"></script>
<script src="{{ asset('js/complete-quran-mapping-wrapper.js') }}"></script>
<script src="{{ asset('js/auto-fill-page.js') }}"></script>
<script src="{{ asset('js/page-juz-calculator.js') }}"></script>
<script src="{{ asset('js/mode-handler.js') }}"></script>
<script src="{{ asset('js/quran-api.js') }}"></script>
<script src="{{ asset('js/setoran-interactive.js') }}"></script>
<script src="{{ asset('js/audio-recorder.js') }}"></script>
<script src="{{ asset('js/unified-input.js') }}"></script>

@endsection
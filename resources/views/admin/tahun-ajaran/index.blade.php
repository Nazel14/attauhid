@extends('layouts.app')

@section('content')
<div class="py-3 sm:py-6 lg:py-8">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 sm:mb-5">
            <a href="{{ route('admin.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 mb-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Tahun Ajaran</h1>
            <p class="text-xs text-gray-500 mt-0.5">Kelola periode semester dan proses tutup semester</p>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="alert-flash mb-4 px-4 py-3 bg-emerald-50 border border-emerald-300 text-emerald-700 dark:bg-emerald-950/50 dark:border-emerald-700/40 dark:text-emerald-400 rounded-xl text-xs flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {!! session('success') !!}
                </div>
                <button onclick="this.closest('.alert-flash').remove()" class="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        @if(session('updated'))
            <div class="alert-flash mb-4 px-4 py-3 bg-blue-50 border border-blue-300 text-blue-700 dark:bg-blue-950/50 dark:border-blue-700/40 dark:text-blue-400 rounded-xl text-xs flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {!! session('updated') !!}
                </div>
                <button onclick="this.closest('.alert-flash').remove()" class="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        @if(session('deleted'))
            <div class="alert-flash mb-4 px-4 py-3 bg-red-50 border border-red-300 text-red-700 dark:bg-red-950/50 dark:border-red-700/40 dark:text-red-400 rounded-xl text-xs flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    {!! session('deleted') !!}
                </div>
                <button onclick="this.closest('.alert-flash').remove()" class="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert-flash mb-4 px-4 py-3 bg-red-50 border border-red-300 text-red-700 dark:bg-red-950/50 dark:border-red-700/40 dark:text-red-400 rounded-xl text-xs flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {!! session('error') !!}
                </div>
                <button onclick="this.closest('.alert-flash').remove()" class="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert-flash mb-4 px-4 py-3 bg-indigo-50 border border-indigo-300 text-indigo-700 dark:bg-indigo-950/50 dark:border-indigo-700/40 dark:text-indigo-400 rounded-xl text-xs flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {!! session('info') !!}
                </div>
                <button onclick="this.closest('.alert-flash').remove()" class="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Banner Semester Aktif --}}
        @if($aktif)
        <div class="mb-4 sm:mb-5 bg-gradient-to-r from-indigo-600 to-indigo-500 dark:from-indigo-600/90 dark:to-indigo-500/80 rounded-xl border border-indigo-500/30 p-4 shadow-lg shadow-indigo-900/20">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse flex-shrink-0"></div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-indigo-100 dark:text-indigo-300 mb-0.5">Semester Aktif Sekarang</p>
                        <p class="text-sm font-bold text-white truncate">{{ $aktif->label_lengkap }}</p>
                        <p class="text-[10px] text-indigo-100 dark:text-indigo-200 mt-0.5">
                            {{ $aktif->tanggal_mulai->format('d M Y') }} — {{ $aktif->tanggal_selesai->format('d M Y') }}
                        </p>
                    </div>
                </div>
                <button onclick="openTutupModal({{ $aktif->id }}, '{{ $aktif->label_lengkap }}')"
                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 bg-white/15 hover:bg-white/25 dark:bg-white/10 dark:hover:bg-white/20 border border-white/20 text-white text-xs font-semibold rounded-xl transition-colors active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="hidden sm:inline">Tutup Semester</span>
                    <span class="sm:hidden">Tutup</span>
                </button>
            </div>
        </div>
        @endif

        {{-- Tombol Tambah (mobile toggle) --}}
        <div class="lg:hidden mb-3">
            <button onclick="toggleFormMobile()" id="btn-toggle-form"
                    class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:border-indigo-400 dark:hover:border-indigo-700/50 hover:text-indigo-600 dark:hover:text-indigo-300 transition-all">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Tahun Ajaran
                </span>
                <svg id="form-chevron" class="w-4 h-4 text-gray-400 dark:text-gray-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">

            {{-- KOLOM KIRI: Form Tambah --}}
            <div class="lg:col-span-1">
                <div id="form-tambah" class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm dark:shadow-none">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 hidden lg:block">Tambah Tahun Ajaran</h2>
                    <form action="{{ route('admin.tahun-ajaran.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Nama <span class="text-red-500 dark:text-red-400">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama') }}"
                                   placeholder="Contoh: 2025/2026"
                                   class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition @error('nama') border-red-500 @enderror">
                            @error('nama')<p class="text-red-500 dark:text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Semester <span class="text-red-500 dark:text-red-400">*</span></label>
                            <select name="semester"
                                    class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition @error('semester') border-red-500 @enderror">
                                <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                                <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                            </select>
                            @error('semester')<p class="text-red-500 dark:text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tgl Mulai <span class="text-red-500 dark:text-red-400">*</span></label>
                                <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}"
                                       class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition @error('tanggal_mulai') border-red-500 @enderror">
                                @error('tanggal_mulai')<p class="text-red-500 dark:text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tgl Selesai <span class="text-red-500 dark:text-red-400">*</span></label>
                                <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}"
                                       class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition @error('tanggal_selesai') border-red-500 @enderror">
                                @error('tanggal_selesai')<p class="text-red-500 dark:text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Keterangan</label>
                            <input type="text" name="keterangan" value="{{ old('keterangan') }}"
                                   placeholder="Opsional..."
                                   class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 text-white text-xs font-bold rounded-xl transition-all active:scale-95 shadow-lg shadow-indigo-900/30">
                            Tambah Tahun Ajaran
                        </button>
                    </form>
                </div>
            </div>

            {{-- KOLOM KANAN: Daftar --}}
            <div class="lg:col-span-2 space-y-2 sm:space-y-3">
                <div class="hidden lg:flex items-center justify-between mb-1">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Daftar Tahun Ajaran</h2>
                    <span class="text-[10px] text-gray-400 dark:text-gray-600">{{ $tahunAjarans->count() }} periode</span>
                </div>

                @forelse($tahunAjarans as $ta)
                <div class="bg-white dark:bg-gray-800 border {{ $ta->is_aktif ? 'border-indigo-400 dark:border-indigo-500/40' : 'border-gray-200 dark:border-gray-700' }} rounded-xl p-3 sm:p-4 transition-colors hover:border-gray-300 dark:hover:border-gray-600 shadow-sm dark:shadow-none">
                    <div class="flex items-start gap-3">

                        {{-- Icon/Status --}}
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl {{ $ta->is_aktif ? 'bg-indigo-100 border border-indigo-300 dark:bg-indigo-600/20 dark:border-indigo-500/30' : 'bg-gray-100 border border-gray-200 dark:bg-gray-800 dark:border-gray-700' }} flex items-center justify-center">
                                @if($ta->is_aktif)
                                    <span class="w-2 h-2 bg-emerald-500 dark:bg-emerald-400 rounded-full animate-pulse"></span>
                                @else
                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $ta->label_lengkap }}</p>
                                @if($ta->is_aktif)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 border border-emerald-300 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-700/50 dark:text-emerald-400 text-[10px] font-semibold rounded-full">
                                        <span class="w-1 h-1 bg-emerald-500 dark:bg-emerald-400 rounded-full animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 border border-gray-200 text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-600 text-[10px] rounded-full">
                                        Tidak Aktif
                                    </span>
                                @endif
                            </div>
                            <p class="text-[10px] text-gray-500 mt-0.5">
                                {{ $ta->tanggal_mulai->format('d M Y') }} — {{ $ta->tanggal_selesai->format('d M Y') }}
                            </p>
                            @if($ta->keterangan)
                                <p class="text-[10px] text-gray-400 dark:text-gray-600 mt-0.5 italic">{{ $ta->keterangan }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-1.5">
                                <span class="text-[10px] text-gray-400 dark:text-gray-600">
                                    <span class="text-gray-700 dark:text-gray-400 font-medium">{{ $ta->presensis()->count() }}</span> presensi
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-gray-600">
                                    <span class="text-gray-700 dark:text-gray-400 font-medium">{{ $ta->setorans()->count() }}</span> setoran
                                </span>
                            </div>
                        </div>

                        {{-- Aksi --}}
                        <div class="flex items-center gap-1 flex-shrink-0">
                            @if(!$ta->is_aktif)
                            <form action="{{ route('admin.tahun-ajaran.set-aktif', $ta) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        onclick="return confirm('Jadikan {{ $ta->label_lengkap }} sebagai semester aktif?')"
                                        title="Jadikan Aktif"
                                        class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-gray-400 dark:text-gray-600 hover:text-emerald-500 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 border border-transparent hover:border-emerald-300 dark:hover:border-emerald-800/50 rounded-lg transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                            </form>
                            @endif

                            <button onclick="openEditModal({{ $ta->id }}, '{{ $ta->nama }}', {{ $ta->semester }}, '{{ $ta->tanggal_mulai->format('Y-m-d') }}', '{{ $ta->tanggal_selesai->format('Y-m-d') }}', '{{ $ta->keterangan ?? '' }}')"
                                    title="Edit"
                                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-gray-400 dark:text-gray-600 hover:text-indigo-500 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 border border-transparent hover:border-indigo-300 dark:hover:border-indigo-800/50 rounded-lg transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            @if(!$ta->is_aktif)
                            <form action="{{ route('admin.tahun-ajaran.destroy', $ta) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Hapus {{ $ta->label_lengkap }}? Data presensi/setoran yang terikat tidak akan bisa dihapus.')"
                                        title="Hapus"
                                        class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-gray-400 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 border border-transparent hover:border-red-300 dark:hover:border-red-800/50 rounded-lg transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 text-center shadow-sm dark:shadow-none">
                    <svg class="mx-auto w-10 h-10 text-gray-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs text-gray-500">Belum ada tahun ajaran</p>
                    <p class="text-[10px] text-gray-400 dark:text-gray-600 mt-1">Tambahkan menggunakan form di atas.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div id="modal-edit" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/40 dark:bg-black/70 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md p-4 sm:p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <div class="w-6 h-6 bg-indigo-100 dark:bg-indigo-600/30 border border-indigo-300 dark:border-indigo-600/50 rounded-lg flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                Edit Tahun Ajaran
            </h3>
            <button onclick="closeEditModal()" class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors">
                <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-edit" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Nama</label>
                <input type="text" id="edit-nama" name="nama" placeholder="Contoh: 2025/2026"
                       class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Semester</label>
                <select id="edit-semester" name="semester"
                        class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="1">Semester 1 (Ganjil)</option>
                    <option value="2">Semester 2 (Genap)</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tgl Mulai</label>
                    <input type="date" id="edit-tanggal-mulai" name="tanggal_mulai"
                           class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tgl Selesai</label>
                    <input type="date" id="edit-tanggal-selesai" name="tanggal_selesai"
                           class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Keterangan</label>
                <input type="text" id="edit-keterangan" name="keterangan" placeholder="Opsional..."
                       class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <div class="flex gap-2 pt-1 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeEditModal()"
                        class="flex-1 py-2.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 text-white text-xs font-bold rounded-xl transition-all active:scale-95 shadow-lg shadow-indigo-900/30">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL TUTUP SEMESTER --}}
<div id="modal-tutup" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/40 dark:bg-black/70 backdrop-blur-sm" onclick="closeTutupModal()"></div>
    <div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md p-4 sm:p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <div class="w-6 h-6 bg-amber-100 dark:bg-amber-600/20 border border-amber-300 dark:border-amber-600/40 rounded-lg flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                Tutup Semester
            </h3>
            <button onclick="closeTutupModal()" class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors">
                <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="mb-3 p-3 bg-amber-50 border border-amber-300 dark:bg-amber-950/40 dark:border-amber-700/40 rounded-xl">
            <p class="text-[10px] text-amber-700 dark:text-amber-400 font-bold uppercase tracking-wide mb-2">⚠️ Proses ini akan:</p>
            <ul class="text-[10px] text-amber-700/80 dark:text-amber-300/70 space-y-0.5 list-disc list-inside">
                <li>Menutup riwayat kelas semester ini untuk semua santri</li>
                <li>Membuat riwayat kelas baru di semester berikutnya</li>
                <li>Menyalin target hafalan ke semester berikutnya</li>
                <li>Mengaktifkan tahun ajaran yang dipilih</li>
            </ul>
        </div>

        <div class="flex items-center gap-2 mb-3 px-3 py-2 bg-gray-50 border border-gray-200 dark:bg-gray-800/50 dark:border-gray-700 rounded-xl">
            <span class="text-[10px] text-gray-500">Menutup:</span>
            <span id="tutup-label" class="text-xs text-gray-900 dark:text-white font-semibold"></span>
        </div>

        <form id="form-tutup" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Aktifkan Semester Berikutnya <span class="text-red-500 dark:text-red-400">*</span></label>
                <select name="tahun_ajaran_baru_id"
                        class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition">
                    <option value="">— Pilih Tahun Ajaran —</option>
                    @foreach($tahunAjarans as $ta)
                        @if(!$ta->is_aktif)
                        <option value="{{ $ta->id }}">{{ $ta->label_lengkap }}</option>
                        @endif
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-400 dark:text-gray-600 mt-1">Pastikan tahun ajaran tujuan sudah dibuat terlebih dahulu.</p>
            </div>
            <div class="flex gap-2 pt-1 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeTutupModal()"
                        class="flex-1 py-2.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit"
                        onclick="return confirm('Tutup semester ini dan aktifkan semester berikutnya? Proses ini tidak bisa dibatalkan.')"
                        class="flex-1 py-2.5 bg-amber-600 hover:bg-amber-500 border border-amber-500 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                    Tutup Semester
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ---- Auto-dismiss alert flash 5 detik ----
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert-flash').forEach(function (alert) {
            setTimeout(function () {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-8px)';
                setTimeout(function () { alert.remove(); }, 500);
            }, 5000);
        });
    });

    // ---- Toggle Form Mobile ----
    function toggleFormMobile() {
        const form = document.getElementById('form-tambah');
        const chevron = document.getElementById('form-chevron');
        const isHidden = form.classList.contains('hidden');
        if (isHidden) {
            form.classList.remove('hidden');
            form.classList.add('block');
            chevron.style.transform = 'rotate(180deg)';
        } else {
            form.classList.add('hidden');
            form.classList.remove('block');
            chevron.style.transform = 'rotate(0deg)';
        }
    }

    // Auto expand form jika ada error validasi
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-tambah');
            const chevron = document.getElementById('form-chevron');
            form.classList.remove('hidden');
            form.classList.add('block');
            chevron.style.transform = 'rotate(180deg)';
        });
    @endif

    // ---- Modal Edit ----
    function openEditModal(id, nama, semester, tanggalMulai, tanggalSelesai, keterangan) {
        document.getElementById('form-edit').action = `/admin/tahun-ajaran/${id}`;
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-semester').value = semester;
        document.getElementById('edit-tanggal-mulai').value = tanggalMulai;
        document.getElementById('edit-tanggal-selesai').value = tanggalSelesai;
        document.getElementById('edit-keterangan').value = keterangan;
        const modal = document.getElementById('modal-edit');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    function closeEditModal() {
        const modal = document.getElementById('modal-edit');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // ---- Modal Tutup Semester ----
    function openTutupModal(id, label) {
        document.getElementById('form-tutup').action = `/admin/tahun-ajaran/${id}/tutup-semester`;
        document.getElementById('tutup-label').textContent = label;
        const modal = document.getElementById('modal-tutup');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    function closeTutupModal() {
        const modal = document.getElementById('modal-tutup');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }
</script>
<style>
/* Input date picker — ikuti tema */
input[type="date"],
input[type="month"] {
    color-scheme: light;
}
html.dark input[type="date"],
html.dark input[type="month"] {
    color-scheme: dark;
}

input[type="date"]::-webkit-calendar-picker-indicator,
input[type="month"]::-webkit-calendar-picker-indicator {
    background-color: transparent;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 16px;
    width: 20px;
    height: 20px;
    cursor: pointer;
    opacity: 1;
    filter: none;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover,
input[type="month"]::-webkit-calendar-picker-indicator:hover {
    opacity: 0.7;
}

/* Transisi fade-out untuk alert */
.alert-flash {
    transition: opacity 0.5s ease, transform 0.5s ease;
}
</style>
@endsection
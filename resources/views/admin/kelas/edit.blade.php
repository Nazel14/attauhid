@extends('layouts.app')

@section('content')
    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8"> 
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Edit Kelas/Halaqah: {{ $kelas->name }}</h3>
                </div>
                
                <!-- Form Content -->
                <form action="{{ route('admin.kelas.update', $kelas) }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    @method('PUT') {{-- PENTING: Menggunakan metode PUT untuk update --}}
                    
                    <div class="space-y-4">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 border-b pb-2 mb-4">Detail Kelas</p>
                        
                        <!-- Nama Kelas -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Kelas <span class="text-red-500">*</span>
                            </label>
                            {{-- Menampilkan nilai kelas saat ini, atau old data jika ada error validasi --}}
                            <input name="name" type="text" id="name" value="{{ old('name', $kelas->name) }}" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100 placeholder:text-gray-400">
                            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Tahun Ajaran -->
                            <div>
                                <label for="tahun_ajaran" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tahun Ajaran <span class="text-red-500">*</span>
                                </label>
                                <input name="tahun_ajaran" type="text" id="tahun_ajaran" 
                                    value="{{ old('tahun_ajaran', $kelas->tahun_ajaran) }}" required 
                                    placeholder="Contoh: 2024/2025"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100 placeholder:text-gray-400">
                                @error('tahun_ajaran') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Guru ID (Wali Kelas) -->
                            <div>
                                <label for="guru_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Wali Kelas/Murobbi <span class="text-red-500">*</span>
                                </label>
                                <select name="guru_id" id="guru_id" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
                                    <option value="" disabled>Pilih Guru</option>
                                    @foreach ($gurus as $guru)
                                        {{-- Memastikan opsi yang dipilih adalah guru yang saat ini terkait dengan kelas --}}
                                        <option value="{{ $guru->id }}" {{ old('guru_id', $kelas->guru_id) == $guru->id ? 'selected' : '' }}>
                                            {{ $guru->name }} ({{ $guru->nip ?? 'NIP Kosong' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('guru_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4">
                        <a href="{{ route('admin.kelas.index') }}" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Batal
                        </a>
                        <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                            Perbarui Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8"> 
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Edit Kelas/Halaqah: {{ $kelas->name }}</h3>
                </div>
                
                <!-- Form Content -->
                <form action="{{ route('admin.kelas.update', $kelas) }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    @method('PUT') {{-- PENTING: Menggunakan metode PUT untuk update --}}
                    
                    <div class="space-y-4">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 border-b pb-2 mb-4">Detail Kelas</p>
                        
                        <!-- Nama Kelas -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Kelas <span class="text-red-500">*</span>
                            </label>
                            {{-- Menampilkan nilai kelas saat ini, atau old data jika ada error validasi --}}
                            <input name="name" type="text" id="name" value="{{ old('name', $kelas->name) }}" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100 placeholder:text-gray-400">
                            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Tahun Ajaran -->
                            <div>
                                <label for="tahun_ajaran" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tahun Ajaran <span class="text-red-500">*</span>
                                </label>
                                <input name="tahun_ajaran" type="text" id="tahun_ajaran" 
                                    value="{{ old('tahun_ajaran', $kelas->tahun_ajaran) }}" required 
                                    placeholder="Contoh: 2024/2025"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100 placeholder:text-gray-400">
                                @error('tahun_ajaran') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Guru ID (Wali Kelas) -->
                            <div>
                                <label for="guru_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Wali Kelas/Murobbi <span class="text-red-500">*</span>
                                </label>
                                <select name="guru_id" id="guru_id" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
                                    <option value="" disabled>Pilih Guru</option>
                                    @foreach ($gurus as $guru)
                                        {{-- Memastikan opsi yang dipilih adalah guru yang saat ini terkait dengan kelas --}}
                                        <option value="{{ $guru->id }}" {{ old('guru_id', $kelas->guru_id) == $guru->id ? 'selected' : '' }}>
                                            {{ $guru->name }} ({{ $guru->nip ?? 'NIP Kosong' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('guru_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4">
                        <a href="{{ route('admin.kelas.index') }}" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Batal
                        </a>
                        <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                            Perbarui Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
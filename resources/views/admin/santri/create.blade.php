@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-start justify-center py-12 pb-12">
    <div class="w-full max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden">
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                <h3 class="text-lg font-semibold text-white text-center">Tambah Santri Baru</h3>
            </div>

            <!-- Error Messages -->
            @if (session('error'))
                <div class="mx-6 mt-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mx-6 mt-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <p class="text-sm font-medium mb-2">Terdapat beberapa kesalahan:</p>
                    <ul class="text-sm list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('admin.santris.store') }}" method="POST" class="p-6 space-y-1">
                @csrf

                <!-- Data Santri -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b pb-2 mb-4">
                        Data Santri
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Lengkap Santri <span class="text-red-500">*</span>
                            </label>
                            <input name="name" type="text" value="{{ old('name') }}" required
                                class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" 
                                placeholder="Masukkan nama lengkap santri" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nomor Induk Santri (NIS) <span class="text-red-500">*</span>
                                </label>
                                <input name="nis" type="text" value="{{ old('nis') }}" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" 
                                    placeholder="Contoh: 2024001" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Jenis Kelamin <span class="text-red-500">*</span>
                                </label>
                                <select name="gender" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Kelas <span class="text-red-500">*</span>
                                </label>
                                <select id="kelas_id" name="kelas_id" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                    <option value="">Pilih Kelas</option>
                                    @foreach ($kelas as $k)
                                        <option value="{{ $k->id }}" 
                                                data-guru-id="{{ $k->guru_id ?? '' }}"
                                                {{ old('kelas_id') == $k->id ? 'selected' : '' }}>
                                            {{ $k->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Wali Kelas <span class="text-red-500">*</span>
                                </label>
                                <select id="guru_id" name="guru_id" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                    <option value="">Pilih Kelas Terlebih Dahulu</option>
                                    @foreach ($gurus as $g)
                                        <option value="{{ $g->id }}" {{ old('guru_id') == $g->id ? 'selected' : '' }}>
                                            {{ $g->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Terisi otomatis setelah memilih kelas</p>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <!-- Data Wali Santri -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b pb-2 mb-4">
                        Data Wali Santri
                    </p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Wali Santri <span class="text-red-500">*</span>
                                </label>
                                <input name="wali_name" type="text" value="{{ old('wali_name') }}" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" 
                                    placeholder="Masukkan nama wali santri" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Username Login <span class="text-red-500">*</span>
                                </label>
                                <input name="wali_username" type="text" value="{{ old('wali_username') }}" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" 
                                    placeholder="Contoh: Budi_001" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input name="wali_password" type="password" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" 
                                    placeholder="Minimal 8 karakter" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Konfirmasi Password <span class="text-red-500">*</span>
                                </label>
                                <input name="wali_password_confirmation" type="password" required
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" 
                                    placeholder="Ulangi password" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-4 pt-4">
                    <a href="{{ route('admin.santris.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition transform hover:-translate-y-0.5 duration-150">
                        Batal
                    </a>

                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-indigo-600 rounded-lg text-sm text-white hover:bg-indigo-700 transition transform hover:-translate-y-0.5 duration-150">
                         Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kelasSelect = document.getElementById('kelas_id');
    const guruSelect = document.getElementById('guru_id');
    
    kelasSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const guruId = selectedOption.getAttribute('data-guru-id');
        
        if (guruId) {
            guruSelect.value = guruId;
        } else {
            guruSelect.value = '';
        }
    });
    
    // Trigger change event jika ada old value
    if (kelasSelect.value) {
        kelasSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
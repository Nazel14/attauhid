@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-start justify-center py-12">
    <div class="w-full max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-700 overflow-hidden">

            <!-- Header Card -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                <h3 class="text-lg font-semibold text-white text-center">
                    Edit Data Guru: {{ $guru->name }}
                </h3>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.gurus.update', $guru) }}" method="POST" class="p-6 space-y-1">
                @csrf
                @method('PUT')

                <!-- Informasi Pribadi -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b pb-2 mb-4">
                        Informasi Pribadi
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input name="name" type="text" required
                                value="{{ old('name', $guru->name) }}"
                                class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIP</label>
                            <input name="nip" type="text"
                                value="{{ old('nip', $guru->nip) }}"
                                class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                    </div>
                </div>
                <br>
                <!-- Akun Login -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b pb-2 mb-4">
                        Detail Akun Login
                    </p>

                    <div class="space-y-4">
                        @if ($guru->user)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <input name="username" type="text" required
                                    value="{{ old('username', $guru->user->name) }}"
                                    class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                            </div>
                        @else
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                                <p class="font-bold">Info</p>
                                <p class="text-sm">Guru ini belum memiliki akun login.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.gurus.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Batal
                    </a>

                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-indigo-700 hover:shadow-xl transition">
                        💾 Perbarui
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

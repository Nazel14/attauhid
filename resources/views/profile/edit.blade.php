@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Profil Saya
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Kelola informasi profil dan foto Anda
            </p>
        </div>

        {{-- Alert Messages --}}
        @if (session('status') === 'profile-updated')
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800 dark:text-green-200"><strong>Berhasil!</strong> Profil berhasil diperbarui.</p>
                </div>
            </div>
        @endif

        @if (session('status') === 'photo-updated')
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800 dark:text-green-200"><strong>Berhasil!</strong> Foto profil berhasil diperbarui.</p>
                </div>
            </div>
        @endif

        @if (session('status') === 'photo-deleted')
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800 dark:text-green-200"><strong>Berhasil!</strong> Foto profil berhasil dihapus.</p>
                </div>
            </div>
        @endif

        {{-- Profile Sections --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Column - Profile Photo --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Foto Profil</h2>
                    
                    {{-- Profile Photo Display --}}
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            @if($user->profile_photo)
                                <img src="{{ asset('storage/' . $user->profile_photo) }}" 
                                    alt="Profile Photo" 
                                    class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 dark:border-gray-600">
                            @else
                                <div class="w-32 h-32 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center border-4 border-gray-200 dark:border-gray-600">
                                    <svg class="w-16 h-16 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $user->name }}</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                            {{ $user->role === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 
                            ($user->role === 'guru' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 
                            'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>


                   

                    {{-- Upload Form --}}
                    <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="space-y-4" id="photoForm">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="profile_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Pilih Foto Baru
                            </label>
                            <input type="file" 
                                class="block w-full text-sm text-gray-500 dark:text-gray-400
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-medium
                                        file:bg-gray-100 file:text-gray-700
                                        hover:file:bg-gray-200
                                        dark:file:bg-gray-700 dark:file:text-gray-300
                                        cursor-pointer border border-gray-300 dark:border-gray-600 rounded-lg
                                        @error('profile_photo') border-red-500 @enderror" 
                                id="profile_photo" 
                                name="profile_photo" 
                                accept="image/*">
                            @error('profile_photo')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                JPG, JPEG, PNG. Max 2MB.<br>
                                {{-- ✅ HAPUS TEXT tentang batasan waktu --}}
                                <strong>Foto bisa diubah kapan saja</strong>
                            </p>
                        </div>

                            {{-- Preview Section --}}
                            <div id="previewSection" class="hidden">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border-2 border-dashed border-gray-300 dark:border-gray-600">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Preview Foto:</p>
                                    
                                    {{-- Preview Container dengan kontrol posisi --}}
                                    <div class="relative w-full bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden" style="height: 200px;">
                                        <img id="photoPreview" src="" alt="Preview" class="absolute" style="cursor: move;">
                                    </div>

                                    {{-- Controls --}}
                                    <div class="mt-3 space-y-2">
                                        <div>
                                            <label class="text-xs text-gray-600 dark:text-gray-400">Zoom:</label>
                                            <input type="range" id="zoomControl" min="50" max="200" value="100" class="w-full h-2 bg-gray-300 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer">
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" id="resetPosition" class="flex-1 text-xs bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 py-1.5 px-3 rounded transition">
                                                Reset Posisi
                                            </button>
                                            <button type="button" id="cancelPreview" class="flex-1 text-xs bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 py-1.5 px-3 rounded transition">
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                                        Drag foto untuk menyesuaikan posisi
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition duration-150">
                                    Upload Foto
                                </button>

                                @if($user->profile_photo)
                                    <button type="button" 
                                            class="w-full bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-medium py-2 px-4 border border-red-300 dark:border-red-700 rounded-lg transition duration-150"
                                            onclick="if(confirm('Yakin ingin menghapus foto profil?')) { document.getElementById('delete-photo-form').submit(); }">
                                        Hapus Foto
                                    </button>
                                @endif
                            </div>
                        </form>

                        @if($user->profile_photo)
                            <form id="delete-photo-form" 
                                  action="{{ route('profile.photo.delete') }}" 
                                  method="POST" 
                                  class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    
                </div>
            </div>

            {{-- Right Column --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Update Profile Information --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Informasi Profil</h2>
                    
                    @if($user->role === 'admin')
                        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Informasi</p>
                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">Sebagai Admin, Anda tidak dapat mengubah nama profil.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nama
                                </label>
                                <input type="text" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm" value="{{ $user->name }}" disabled>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Role
                                </label>
                                <input type="text" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm" value="{{ ucfirst($user->role) }}" disabled>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nama Lengkap
                                </label>
                                <input type="text" 
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm @error('name') border-red-500 @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}" 
                                       placeholder="Masukkan nama lengkap"
                                       required>
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Role
                                </label>
                                <input type="text" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm" value="{{ ucfirst($user->role) }}" disabled>
                            </div>

                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition duration-150">
                                Simpan Perubahan
                            </button>
                        </form>
                    @endif
                </div>     
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile_photo');
    const previewSection = document.getElementById('previewSection');
    const photoPreview = document.getElementById('photoPreview');
    const zoomControl = document.getElementById('zoomControl');
    const resetBtn = document.getElementById('resetPosition');
    const cancelBtn = document.getElementById('cancelPreview');
    
    let isDragging = false;
    let startX, startY;
    let offsetX = 0, offsetY = 0;
    let currentScale = 1;

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                photoPreview.src = event.target.result;
                previewSection.classList.remove('hidden');
                resetPosition();
            };
            reader.readAsDataURL(file);
        }
    });

    // Zoom control
    zoomControl.addEventListener('input', function() {
        currentScale = this.value / 100;
        updateTransform();
    });

    // Drag functionality
    photoPreview.addEventListener('mousedown', startDrag);
    photoPreview.addEventListener('touchstart', startDrag);

    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag);

    document.addEventListener('mouseup', stopDrag);
    document.addEventListener('touchend', stopDrag);

    function startDrag(e) {
        isDragging = true;
        const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
        startX = clientX - offsetX;
        startY = clientY - offsetY;
        photoPreview.style.cursor = 'grabbing';
    }

    function drag(e) {
        if (!isDragging) return;
        e.preventDefault();
        const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
        offsetX = clientX - startX;
        offsetY = clientY - startY;
        updateTransform();
    }

    function stopDrag() {
        isDragging = false;
        photoPreview.style.cursor = 'move';
    }

    function updateTransform() {
        photoPreview.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${currentScale})`;
    }

    function resetPosition() {
        offsetX = 0;
        offsetY = 0;
        currentScale = 1;
        zoomControl.value = 100;
        photoPreview.style.transform = 'translate(0, 0) scale(1)';
        photoPreview.style.maxWidth = '100%';
        photoPreview.style.maxHeight = '100%';
    }

    resetBtn.addEventListener('click', resetPosition);

    cancelBtn.addEventListener('click', function() {
        fileInput.value = '';
        previewSection.classList.add('hidden');
        photoPreview.src = '';
        resetPosition();
    });
});
</script>
@endsection
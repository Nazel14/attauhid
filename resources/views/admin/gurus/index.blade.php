@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-950 pb-10"
     x-data="{
        isOpen: false,
        isEdit: false,
        isImportModalOpen: false,
        isExportEditModalOpen: false,
        isBulkUpdateModalOpen: false,
        isTemplateModalOpen: false,
        formAction: '{{ route('admin.gurus.store') }}',
        formData: {
            name: '',
            nip: '',
            username: ''
        },

        photoPreview: null,
        deletePhoto: false,
        showNotification: false,
        notificationMessage: '',
        notificationType: '',

        // Bulk Delete
        selectedGurus: [],
        selectAll: false,

get isIndeterminate() {
    const total = this.totalGurus();
    return this.selectedGurus.length > 0 && this.selectedGurus.length < total;
},

totalGurus() {
    const ids = [...new Set([...document.querySelectorAll('.guru-checkbox')].map(cb => cb.value))];
    return ids.length;
},

toggleSelectAll() {
    if (this.selectAll) {
        const ids = [...new Set([...document.querySelectorAll('.guru-checkbox')].map(cb => cb.value))];
        this.selectedGurus = ids;
    } else {
        this.selectedGurus = [];
    }
    this.updateMainCheckbox();
},

toggleSelect(id) {
    const index = this.selectedGurus.indexOf(id);
    if (index > -1) {
        this.selectedGurus.splice(index, 1);
    } else {
        this.selectedGurus.push(id);
    }
    const total = this.totalGurus();
    this.selectAll = this.selectedGurus.length === total;
    this.updateMainCheckbox();
},

updateMainCheckbox() {
    this.$nextTick(() => {
        const total = this.totalGurus();
        document.querySelectorAll('#select-all-checkbox').forEach(cb => {
            cb.indeterminate = this.selectedGurus.length > 0 && this.selectedGurus.length < total;
        });
    });
},

        bulkDelete() {
            if (this.selectedGurus.length === 0) {
                alert('Pilih minimal 1 guru untuk dihapus');
                return;
            }
            if (confirm(`Apakah Anda yakin ingin menghapus ${this.selectedGurus.length} guru yang dipilih?`)) {
                this.$refs.bulkDeleteForm.submit();
            }
        },

        openTemplateModal() { this.isTemplateModalOpen = true; },
        closeTemplateModal() { this.isTemplateModalOpen = false; },

        openCreateModal() {
            this.isEdit = false;
            this.formAction = '{{ route('admin.gurus.store') }}';
            this.formData = { name: '', nip: '', username: '' };
            this.photoPreview = null;
            this.deletePhoto = false;
            this.isOpen = true;
        },

        openEditModal(guru, username) {
            this.isEdit = true;
            this.formAction = `/admin/gurus/${guru.id}`;
            this.formData = {
                name: guru.name,
                nip: guru.nip,
                username: username
            };
            this.isOpen = true;
        },

        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                    this.deletePhoto = false;
                };
                reader.readAsDataURL(file);
            }
        },

        removePhoto() {
            this.photoPreview = null;
            this.deletePhoto = true;
            const input = document.querySelector('input[name=profile_photo]');
            if (input) input.value = '';
        },

        closeModal() {
            this.isOpen = false;
            this.photoPreview = null;
            this.deletePhoto = false;
        },

        openImportModal() { this.isImportModalOpen = true; },
        openExportEditModal() { this.isExportEditModalOpen = true; },
        openBulkUpdateModal() { this.isBulkUpdateModalOpen = true; },
        closeImportModal() { this.isImportModalOpen = false; },
        closeExportEditModal() { this.isExportEditModalOpen = false; },
        closeBulkUpdateModal() { this.isBulkUpdateModalOpen = false; },

        displayNotification(message, type) {
            this.notificationMessage = message;
            this.notificationType = type;
            this.showNotification = true;
            setTimeout(() => { this.showNotification = false; }, 5000);
        },

        smartCapitalizeInput(event) {
            const input = event.target;
            let value = input.value;
            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }
            input.value = value;
            this.formData.name = value;
        }
     }"
     x-init="
        @if(session('success'))
            displayNotification('{{ session('success') }}', 'success')
        @elseif(session('updated'))
            displayNotification('{{ session('updated') }}', 'updated')
        @elseif(session('deleted'))
            displayNotification('{{ session('deleted') }}', 'deleted')
        @elseif(session('error'))
            displayNotification('{!! session('error') !!}', 'error')
        @endif
     "
     x-effect="if (isOpen || isImportModalOpen || isTemplateModalOpen || isExportEditModalOpen || isBulkUpdateModalOpen) { document.body.classList.add('overflow-hidden') } else { document.body.classList.remove('overflow-hidden') }">

    {{-- NOTIFICATION TOAST --}}
    <div x-show="showNotification"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         :class="{
             'bg-emerald-600 border-emerald-500/40': notificationType === 'success',
             'bg-blue-600 border-blue-500/40': notificationType === 'updated',
             'bg-red-600 border-red-500/40': notificationType === 'deleted' || notificationType === 'error'
         }"
         class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] border text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3"
         style="display:none;">
        <div class="flex items-start gap-2 flex-1">
            <svg x-show="notificationType==='success'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="notificationType==='updated'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <svg x-show="notificationType==='deleted'||notificationType==='error'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium" x-html="notificationMessage"></p>
        </div>
        <button @click="showNotification=false" class="opacity-70 hover:opacity-100 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('admin.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white truncate"> Management Guru</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Total: <span class="text-indigo-400 font-semibold">{{ $gurus->count() }} guru</span></p>
            </div>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 mb-4">
            <button @click="openTemplateModal()" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-emerald-700 bg-emerald-700 hover:bg-emerald-600 hover:border-emerald-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Template
            </button>
            <button @click="openImportModal()" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-blue-700 bg-blue-700 hover:bg-blue-600 hover:border-blue-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Import
            </button>
            <button @click="openExportEditModal()" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-purple-700 bg-purple-700 hover:bg-purple-600 hover:border-purple-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>
            <button @click="openBulkUpdateModal()" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-orange-700 bg-orange-700 hover:bg-orange-600 hover:border-orange-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Update
            </button>
            <button @click="openCreateModal()" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-indigo-600 bg-indigo-600 hover:bg-indigo-500 hover:border-indigo-500 transition-all active:scale-95 col-span-2 sm:col-span-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Guru
            </button>
        </div>

        {{-- BULK ACTION BAR --}}
        <div x-show="selectedGurus.length > 0"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-indigo-950/60 border border-indigo-700/40 rounded-xl px-4 py-3 mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-indigo-300">
                    <span x-text="selectedGurus.length"></span> dari <span x-text="totalGurus()"></span> guru dipilih
                </p>
                <button @click="selectedGurus=[]; selectAll=false; updateMainCheckbox();" class="text-[11px] text-indigo-500 hover:text-indigo-400">Bersihkan pilihan</button>
            </div>
            <button @click="bulkDelete()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Terpilih
            </button>
        </div>

        {{-- Form Bulk Delete --}}
        <form x-ref="bulkDeleteForm" action="{{ route('admin.gurus.bulk-destroy') }}" method="POST" class="hidden">
            @csrf @method('DELETE')
            <template x-for="id in selectedGurus" :key="id"><input type="hidden" name="guru_ids[]" :value="id"></template>
        </form>

        {{-- MOBILE CARDS --}}
        <div class="lg:hidden space-y-2">

            @if($gurus->count() > 0)
            <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-900 border border-gray-800 rounded-xl">
                <input type="checkbox" id="select-all-checkbox" x-model="selectAll" @change="toggleSelectAll()"
                    class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="select-all-checkbox" class="text-xs font-semibold text-gray-400 cursor-pointer select-none flex-1">Pilih Semua</label>
                <span class="text-[11px] text-gray-600">{{ $gurus->count() }} data</span>
            </div>
            @endif

            @forelse ($gurus as $index => $guru)
            <div class="rounded-xl border overflow-hidden transition-all"
                 :class="selectedGurus.includes('{{ $guru->id }}') ? 'border-indigo-500/50 ring-1 ring-indigo-500/15 bg-indigo-950/10' : 'border-gray-800 bg-gray-900'">

                <div class="flex items-center gap-3 px-4 py-3">
                    <input type="checkbox" class="guru-checkbox w-4 h-4 rounded border-gray-600 bg-gray-700 text-indigo-600 flex-shrink-0 cursor-pointer"
                        value="{{ $guru->id }}"
                        :checked="selectedGurus.includes('{{ $guru->id }}')"
                        @change="toggleSelect('{{ $guru->id }}')">

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ $guru->name }}</p>
                        <p class="text-[11px] text-gray-500">NIP: {{ $guru->nip ?? '-' }}</p>
                    </div>
                    <span class="text-[11px] text-gray-700 flex-shrink-0">#{{ $index + 1 }}</span>
                </div>

                <div class="grid grid-cols-2 border-t border-gray-800/50">
                    <div class="px-3 py-2.5 text-center border-r border-gray-800/50">
                        <p class="text-[10px] text-gray-600 mb-1">Username</p>
                        <p class="text-xs font-bold text-indigo-400 truncate">{{ $guru->user->name ?? '-' }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center" x-data="{ show: false }">
                        <p class="text-[10px] text-gray-600 mb-1">Password</p>
                        <div class="flex items-center justify-center gap-1">
                            <span x-show="!show" class="font-mono text-[10px] text-gray-700 tracking-widest">••••••</span>
                            <span x-show="show" class="font-mono text-[10px] text-gray-300">{{ $guru->user->plain_password ?? '-' }}</span>
                            @if($guru->user && $guru->user->plain_password)
                            <button @click="show=!show" class="text-gray-600 hover:text-indigo-400 transition-colors">
                                <svg x-show="!show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-3 h-3" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 border-t border-gray-800/50">
                    <button @click="openEditModal({{ json_encode($guru) }}, '{{ $guru->user->name ?? '' }}')"
                        class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-400 hover:bg-indigo-950/50 active:bg-indigo-950/80 transition-colors border-r border-gray-800/50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </button>
                    <form action="{{ route('admin.gurus.destroy', $guru) }}" method="POST"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus Guru {{ addslashes($guru->name) }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-red-400 hover:bg-red-950/40 active:bg-red-950/70 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                    </form>
                </div>

            </div>
            @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl py-16 text-center">
                <div class="text-4xl mb-3"></div>
                <p class="text-sm font-semibold text-gray-400">Belum ada data guru</p>
                <p class="text-xs text-gray-600 mt-1.5">Tap tombol "+ Tambah Guru" di atas</p>
            </div>
            @endforelse
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="hidden lg:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-800 bg-gray-800/40">
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="select-all-checkbox" x-model="selectAll" @change="toggleSelectAll()"
                                class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-indigo-600 cursor-pointer">
                        </th>
                        <th class="th">No</th>
                        <th class="th">Nama Guru</th>
                        <th class="th">NIP</th>
                        <th class="th">Username</th>
                        <th class="th">Password</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse ($gurus as $index => $guru)
                    <tr class="hover:bg-gray-800/25 transition-colors"
                        :class="selectedGurus.includes('{{ $guru->id }}') ? 'bg-indigo-950/20' : ''">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="guru-checkbox w-4 h-4 rounded border-gray-600 bg-gray-700 text-indigo-600 cursor-pointer"
                                value="{{ $guru->id }}"
                                :checked="selectedGurus.includes('{{ $guru->id }}')"
                                @change="toggleSelect('{{ $guru->id }}')">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm font-medium text-gray-200">{{ $guru->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-400">{{ $guru->nip ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="chip bg-indigo-950/50 text-indigo-400 border-indigo-800/30">{{ $guru->user->name ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5" x-data="{ show: false }">
                                <span x-show="!show" class="font-mono text-xs text-gray-700">••••••••</span>
                                <span x-show="show" class="font-mono text-xs text-gray-300">{{ $guru->user->plain_password ?? '-' }}</span>
                                @if($guru->user && $guru->user->plain_password)
                                <button @click="show=!show" class="text-gray-600 hover:text-indigo-400 transition-colors">
                                    <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="show" class="w-3.5 h-3.5" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 justify-center">
                                <button @click="openEditModal({{ json_encode($guru) }}, '{{ $guru->user->name ?? '' }}')"
                                    class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/40 border border-indigo-600/30 text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Edit</button>
                                <form action="{{ route('admin.gurus.destroy', $guru) }}" method="POST"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus Guru {{ addslashes($guru->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-600/20 hover:bg-red-600/40 border border-red-600/30 text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="py-16 text-center">
                        <div class="text-4xl mb-3"></div>
                        <p class="text-sm font-semibold text-gray-400">Belum ada data guru</p>
                        <p class="text-xs text-gray-600 mt-1">Klik "+ Tambah Guru" di atas</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- ====================================================== --}}
    {{-- MODAL: TAMBAH / EDIT GURU (FIXED dengan password edit)  --}}
    {{-- ====================================================== --}}
    <div x-show="isOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeModal()"></div>
        <div class="modal-sheet sm:max-w-2xl"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-white" x-text="isEdit ? '✏️ Edit Data Guru' : '➕ Tambah Guru Baru'"></h3>
                <button @click="closeModal()" class="modal-close-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form :action="formAction" method="POST" enctype="multipart/form-data" class="p-5 space-y-4 overflow-y-auto" style="max-height:72vh;">
                @csrf
                <input type="hidden" name="_method" x-bind:value="isEdit ? 'PUT' : 'POST'">
                <input type="hidden" name="delete_photo" x-bind:value="deletePhoto ? '1' : '0'">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="fi-lbl">Nama Lengkap <span class="text-red-400">*</span></label>
                        <input name="name" type="text" required x-model="formData.name"
                            @input="smartCapitalizeInput($event)"
                            placeholder="Ahmad Zainudin, S.Pd"
                            class="fi-inp">
                        <p class="text-[10px] text-gray-600 mt-1">Huruf awal otomatis kapital</p>
                    </div>
                    <div>
                        <label class="fi-lbl">NIP (Nomor Induk Pegawai)</label>
                        <input name="nip" type="text" x-model="formData.nip"
                            placeholder="198501012010011001"
                            class="fi-inp">
                        <p class="text-[10px] text-gray-600 mt-1">Opsional (boleh dikosongkan)</p>
                    </div>
                </div>

                {{-- Akun Login --}}
                <div class="bg-blue-950/30 border border-blue-800/40 rounded-xl p-3.5">
                    <h4 class="text-xs font-bold text-blue-300 mb-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Data Akun Login
                    </h4>

                    {{-- Username (selalu tampil) --}}
                    <div class="mb-3">
                        <label class="fi-lbl">Username <span class="text-red-400">*</span></label>
                        <input name="username" type="text" required x-model="formData.username"
                            placeholder="ahmad.zainudin"
                            class="fi-inp">
                        <p class="text-[10px] text-gray-600 mt-1">Untuk login ke sistem</p>
                    </div>

                    {{-- ============================================ --}}
                    {{-- Password untuk CREATE mode (wajib)            --}}
                    {{-- ============================================ --}}
                    <div x-show="!isEdit" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div x-data="{ showPassword: false }">
                            <label class="fi-lbl">Password <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <input name="password" :type="showPassword ? 'text' : 'password'" x-bind:required="!isEdit"
                                    placeholder="Minimal 8 karakter"
                                    class="fi-inp pr-9">
                                <button type="button" @click="showPassword=!showPassword" class="eye-btn">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>
                        <div x-data="{ showConfirmPassword: false }">
                            <label class="fi-lbl">Konfirmasi Password <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <input name="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" x-bind:required="!isEdit"
                                    placeholder="Ulangi password"
                                    class="fi-inp pr-9">
                                <button type="button" @click="showConfirmPassword=!showConfirmPassword" class="eye-btn">
                                    <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showConfirmPassword" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================ --}}
                    {{-- ✅ Password untuk EDIT mode (opsional)        --}}
                    {{-- ============================================ --}}
                    <div x-show="isEdit" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div x-data="{ showEditPassword: false }">
                            <label class="fi-lbl">
                                Password Baru
                                <span class="text-gray-500 text-[10px] font-normal">(Kosongkan jika tidak diubah)</span>
                            </label>
                            <div class="relative">
                                <input name="password" :type="showEditPassword ? 'text' : 'password'"
                                    placeholder="Minimal 8 karakter"
                                    class="fi-inp pr-9">
                                <button type="button" @click="showEditPassword=!showEditPassword" class="eye-btn">
                                    <svg x-show="!showEditPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showEditPassword" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-600 mt-1">Minimal 8 karakter</p>
                        </div>
                        <div x-data="{ showEditConfirmPwd: false }">
                            <label class="fi-lbl">Konfirmasi Password Baru</label>
                            <div class="relative">
                                <input name="password_confirmation" :type="showEditConfirmPwd ? 'text' : 'password'"
                                    placeholder="Ulangi password baru"
                                    class="fi-inp pr-9">
                                <button type="button" @click="showEditConfirmPwd=!showEditConfirmPwd" class="eye-btn">
                                    <svg x-show="!showEditConfirmPwd" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showEditConfirmPwd" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-600 mt-1">Sama dengan password baru</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="closeModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Guru'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: TEMPLATE --}}
    <div x-show="isTemplateModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeTemplateModal()"></div>
        <div class="modal-sheet sm:max-w-sm"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-white">📥 Download Template Import Guru</h3>
                <button @click="closeTemplateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="bg-emerald-950/30 border border-emerald-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-emerald-300 mb-2">Template ini berisi:</p>
                    <ul class="text-[11px] text-emerald-300/70 space-y-0.5 list-disc list-inside">
                        <li>Format kolom yang benar</li>
                        <li>Contoh data guru</li>
                        <li>Petunjuk lengkap import</li>
                        <li>Validasi otomatis</li>
                    </ul>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeTemplateModal()" class="btn-secondary flex-1">Batal</button>
                    <a href="{{ route('admin.gurus.template') }}" @click="setTimeout(() => { closeTemplateModal(); }, 500)"
                        class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition-colors text-center">Download</a>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: IMPORT --}}
    <div x-show="isImportModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeImportModal()"></div>
        <div class="modal-sheet sm:max-w-md"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-white">📂 Import Data Guru dari Excel</h3>
                <button @click="closeImportModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.gurus.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4"
                  @submit="setTimeout(() => { window.location.href = '{{ route('admin.gurus.index') }}'; }, 100)">
                @csrf
                <div>
                    <label class="fi-lbl">Pilih File Excel <span class="text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-xs text-gray-400 bg-gray-800 border border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600/20 file:text-blue-400 hover:file:bg-blue-600/30">
                    <p class="text-[10px] text-gray-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                </div>
                <div class="bg-blue-950/30 border border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-blue-300 mb-1.5">📋 Langkah-langkah:</p>
                    <ol class="text-[11px] text-blue-300/70 space-y-0.5 list-decimal list-inside">
                        <li>Download template terlebih dahulu</li>
                        <li>Isi data guru sesuai format</li>
                        <li>Upload file di sini</li>
                    </ol>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeImportModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition-colors">Import Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EXPORT --}}
    <div x-show="isExportEditModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeExportEditModal()"></div>
        <div class="modal-sheet sm:max-w-sm"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-white">📤 Export Data Guru untuk Edit</h3>
                <button @click="closeExportEditModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="bg-purple-950/30 border border-purple-800/30 rounded-xl px-4 py-3 space-y-1.5">
                    <p class="text-xs font-semibold text-purple-300">File Excel ini berisi:</p>
                    <ul class="text-[11px] text-purple-300/70 list-disc list-inside space-y-0.5">
                        <li>Semua data guru yang ada</li>
                        <li>NO URUT untuk identifikasi</li>
                        <li>Username yang bisa diedit</li>
                    </ul>
                </div>
                <div class="bg-amber-950/30 border border-amber-800/30 rounded-xl px-4 py-3">
                    <p class="text-[11px] text-amber-400 font-semibold">⚠️ Penting: Jangan hapus/ubah kolom NO URUT. Password tidak bisa diubah via Excel.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeExportEditModal()" class="btn-secondary flex-1">Batal</button>
                    <a href="{{ route('admin.gurus.export-edit') }}" @click="setTimeout(() => { closeExportEditModal(); }, 500)"
                        class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold rounded-xl transition-colors text-center">Download Excel</a>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: BULK UPDATE --}}
    <div x-show="isBulkUpdateModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeBulkUpdateModal()"></div>
        <div class="modal-sheet sm:max-w-md"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-white">🔄 Bulk Update Data Guru</h3>
                <button @click="closeBulkUpdateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.gurus.bulk-update') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4"
                  @submit="setTimeout(() => { window.location.href = '{{ route('admin.gurus.index') }}'; }, 100)">
                @csrf
                <div class="bg-blue-950/30 border border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-blue-300 mb-1.5">📋 Langkah-Langkah:</p>
                    <ol class="text-[11px] text-blue-300/70 space-y-0.5 list-decimal list-inside">
                        <li>Klik tombol "Export" untuk download data guru</li>
                        <li>Edit data di Excel (JANGAN ubah kolom NO URUT)</li>
                        <li>Upload Excel yang sudah diedit di sini</li>
                    </ol>
                </div>
                <div>
                    <label class="fi-lbl">File Excel yang Sudah Diedit <span class="text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-xs text-gray-400 bg-gray-800 border border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-600/20 file:text-orange-400 hover:file:bg-orange-600/30">
                    <p class="text-[10px] text-gray-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                </div>
                <div class="bg-red-950/30 border border-red-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-red-400 mb-1.5">⚠️ PERINGATAN:</p>
                    <ul class="text-[11px] text-red-300/70 space-y-0.5 list-disc list-inside">
                        <li>NO URUT wajib ada — jangan dihapus/ubah</li>
                        <li>Username & NIP harus unik</li>
                        <li>Password TIDAK bisa diubah via Excel</li>
                    </ul>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeBulkUpdateModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-orange-600 hover:bg-orange-500 text-white text-sm font-bold rounded-xl transition-colors">🔄 Update Semua Data</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    body { background:#030712; }
    .th { padding:12px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    .modal-wrap { position:fixed; inset:0; z-index:70; display:flex; flex-direction:column; justify-content:flex-end; }
    @media (min-width:640px) { .modal-wrap { justify-content:center; align-items:center; padding:16px; } }
    .modal-bg { position:absolute; inset:0; background:rgba(0,0,0,.75); backdrop-filter:blur(4px); }
    .modal-sheet { position:relative; background:#111827; width:100%; border-radius:20px 20px 0 0; border:1px solid rgba(55,65,81,.6); box-shadow:0 25px 50px rgba(0,0,0,.6); overflow:hidden; }
    @media (min-width:640px) { .modal-sheet { border-radius:20px; } }
    .modal-handle { width:40px; height:4px; background:#374151; border-radius:99px; margin:12px auto 4px; }
    @media (min-width:640px) { .modal-handle { display:none; } }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-bottom:1px solid #1f2937; }
    .modal-close-btn { width:28px; height:28px; border-radius:50%; background:#1f2937; display:flex; align-items:center; justify-content:center; color:#9ca3af; transition:background .15s; }
    .modal-close-btn:hover { background:#374151; }
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#9ca3af; margin-bottom:5px; }
    .fi-inp { width:100%; background:#0d1117; border:1px solid #374151; border-radius:10px; color:#e5e7eb; font-size:13px; padding:9px 12px; outline:none; transition:border-color .15s, box-shadow .15s; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    .eye-btn { position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#6b7280; transition:color .15s; }
    .eye-btn:hover { color:#d1d5db; }
    .btn-primary { padding:10px 0; background:#4f46e5; color:#fff; font-size:14px; font-weight:700; border-radius:12px; transition:background .15s; box-shadow:0 4px 14px rgba(79,70,229,.3); }
    .btn-primary:hover { background:#4338ca; }
    .btn-secondary { padding:10px 0; background:#1f2937; border:1px solid #374151; color:#d1d5db; font-size:14px; font-weight:600; border-radius:12px; transition:background .15s; }
    .btn-secondary:hover { background:#374151; }
    nav[role="navigation"] span, nav[role="navigation"] a { background:#111827 !important; border-color:#374151 !important; color:#9ca3af !important; }
    nav[role="navigation"] a:hover { background:#1f2937 !important; color:#e5e7eb !important; }
    nav[role="navigation"] [aria-current="page"] span { background:#4f46e5 !important; border-color:#4f46e5 !important; color:#fff !important; }
    input[type="checkbox"]:indeterminate {
        background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 16 16'%3e%3cpath stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 8h8'/%3e%3c/svg%3e");
        background-color:rgb(79 70 229); border-color:rgb(79 70 229);
    }
    [x-cloak] { display:none !important; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
</style>

@endsection
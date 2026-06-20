@extends('layouts.app')

@section('content')
<div class="min-h-screen pb-10"
     x-data="{
        isOpen: false,
        isEdit: false,
        isTemplateModalOpen: false,
        isExportEditModalOpen: false,
        isImportModalOpen: false,
        isBulkUpdateModalOpen: false,
        formAction: '{{ route('admin.santris.store') }}',
        formData: {
            nis: '',
            name: '',
            gender: 'L',
            target_metode: 'quran',
            target_juz: '1.00',
            target_jilid: '1',
            kelas_id: '',
        },

        showNotification: false,
        notificationMessage: '',
        notificationType: '',
        showPassword: false,
        showPasswordConfirmation: false,

        selectedSantris: [],
        selectAll: false,

        totalSantris() {
            const ids = [...new Set([...document.querySelectorAll('.santri-checkbox')].map(cb => cb.value))];
            return ids.length;
        },

        get isIndeterminate() {
            const total = this.totalSantris();
            return this.selectedSantris.length > 0 && this.selectedSantris.length < total;
        },

        toggleSelectAll() {
            if (this.selectAll) {
                const ids = [...new Set([...document.querySelectorAll('.santri-checkbox')].map(cb => cb.value))];
                this.selectedSantris = ids;
            } else {
                this.selectedSantris = [];
            }
            this.updateMainCheckbox();
        },

        toggleSelect(id) {
            const index = this.selectedSantris.indexOf(id);
            if (index > -1) {
                this.selectedSantris.splice(index, 1);
            } else {
                this.selectedSantris.push(id);
            }
            const total = this.totalSantris();
            this.selectAll = this.selectedSantris.length === total;
            this.updateMainCheckbox();
        },

        updateMainCheckbox() {
            this.$nextTick(() => {
                const total = this.totalSantris();
                document.querySelectorAll('#select-all-checkbox').forEach(cb => {
                    cb.indeterminate = this.selectedSantris.length > 0 && this.selectedSantris.length < total;
                });
            });
        },

        bulkDelete() {
            if (this.selectedSantris.length === 0) {
                alert('Pilih minimal 1 santri untuk dihapus');
                return;
            }
            if (confirm(`Apakah Anda yakin ingin menghapus ${this.selectedSantris.length} santri yang dipilih? Akun login mereka juga akan dihapus.`)) {
                this.$refs.bulkDeleteForm.submit();
            }
        },

        openCreateModal() {
            this.isEdit = false;
            this.formAction = '{{ route('admin.santris.store') }}';
            this.formData = {
                nis: '',
                name: '',
                gender: 'L',
                target_metode: 'quran',
                target_juz: '1.00',
                target_jilid: '1',
                kelas_id: @if($allKelas->isNotEmpty()) '{{ $allKelas->first()->id }}' @else '' @endif
            };
            this.showPassword = false;
            this.showPasswordConfirmation = false;
            this.isOpen = true;
        },

        openEditModal(santri) {
            this.isEdit = true;
            this.formAction = `/admin/santris/${santri.id}`;
            let genderValue = 'L';
            if (santri.gender === 'P' || santri.gender === 'Perempuan') {
                genderValue = 'P';
            } else if (santri.gender === 'L' || santri.gender === 'Laki-laki') {
                genderValue = 'L';
            }
            this.formData = {
                nis: santri.nis,
                name: santri.name,
                gender: genderValue,
                target_juz: santri.target_juz,
                kelas_id: santri.kelas_id,
            };
            this.showPassword = false;
            this.showPasswordConfirmation = false;
            this.isOpen = true;
        },

        openImportModal() { this.isImportModalOpen = true; },
        openTemplateModal() { this.isTemplateModalOpen = true; },
        openExportEditModal() { this.isExportEditModalOpen = true; },
        openBulkUpdateModal() { this.isBulkUpdateModalOpen = true; },
        closeModal() { this.isOpen = false; },
        closeImportModal() { this.isImportModalOpen = false; },
        closeTemplateModal() { this.isTemplateModalOpen = false; },
        closeExportEditModal() { this.isExportEditModalOpen = false; },
        closeBulkUpdateModal() { this.isBulkUpdateModalOpen = false; },

        displayNotification(message, type) {
            this.notificationMessage = message;
            this.notificationType = type;
            this.showNotification = true;
            setTimeout(() => { this.showNotification = false; }, 10000);
        },

        smartCapitalize(event) {
            const input = event.target;
            let value = input.value;
            value = value.replace(/\b\w/g, function(char) { return char.toUpperCase(); });
            input.value = value;
            if (this.formData && event.target.name === 'name') {
                this.formData.name = value;
            }
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
            <a href="{{ route('admin.santris.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> {{ $kelas->name }}</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Guru: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $kelas->guru->name ?? '-' }}</span> · Total: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $santris->total() }} santri</span></p>
            </div>
        </div>

        {{-- GENDER MISMATCH WARNING --}}
        @if($hasGenderMismatch)
        <div class="bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-700/50 rounded-xl p-4 mb-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-red-700 dark:text-red-300 mb-1">⚠️ Peringatan: Gender Tidak Sesuai dengan Kelas!</p>
                    <p class="text-xs text-red-600/90 dark:text-red-400/80 mb-2">
                        Kelas <strong>{{ $kelas->name }}</strong> adalah kelas
                        <strong>{{ $kelas->gender_restriction === 'ikhwan' ? 'Khusus Ikhwan (Laki-laki)' : 'Khusus Akhwat (Perempuan)' }}</strong>,
                        namun terdapat <strong>{{ $mismatchedSantris->count() }} santri</strong> dengan gender yang tidak sesuai.
                    </p>
                    <div class="bg-red-100 dark:bg-red-950/60 border border-red-200 dark:border-red-800/40 rounded-lg p-2.5 mb-2">
                        <p class="text-[11px] font-semibold text-red-700 dark:text-red-300 mb-1.5">Daftar Santri Tidak Sesuai:</p>
                        <ul class="space-y-0.5">
                            @foreach($mismatchedSantris as $santri)
                            <li class="text-[11px] text-red-600/90 dark:text-red-400/80">
                                • <strong>{{ $santri->name }}</strong> ({{ $santri->nis }}) —
                                <span class="bg-red-200 dark:bg-red-900/60 text-red-700 dark:text-red-300 px-1.5 py-0.5 rounded text-[10px] font-bold">{{ $santri->gender }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-[10px] text-red-500 dark:text-red-400">Solusi:</span>
                        <span class="text-[10px] bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-700/30 px-2 py-0.5 rounded-full">1️⃣ Pindahkan santri ke kelas yang sesuai</span>
                        <span class="text-[10px] bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/30 px-2 py-0.5 rounded-full">2️⃣ Ubah kelas menjadi "Campuran" di menu Kelas</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- VALIDATION ERRORS --}}
        @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-700/50 rounded-xl p-4 mb-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-1">Terdapat kesalahan:</p>
                    <ul class="space-y-0.5">
                        @foreach ($errors->all() as $error)
                        <li class="text-xs text-red-600/90 dark:text-red-400/80">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

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
                Tambah Santri
            </button>
        </div>

        {{-- BULK ACTION BAR --}}
        <div x-show="selectedSantris.length > 0"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-300 dark:border-indigo-700/40 rounded-xl px-4 py-3 mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                    <span x-text="selectedSantris.length"></span> dari <span x-text="totalSantris()"></span> santri dipilih
                </p>
                <button @click="selectedSantris=[]; selectAll=false; updateMainCheckbox();" class="text-[11px] text-indigo-500 hover:text-indigo-400">Bersihkan pilihan</button>
            </div>
            <button @click="bulkDelete()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Terpilih
            </button>
        </div>

        {{-- Form Hidden untuk Bulk Delete --}}
        <form x-ref="bulkDeleteForm" action="{{ route('admin.santris.bulk-destroy') }}" method="POST" class="hidden">
            @csrf @method('DELETE')
            <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
            <template x-for="id in selectedSantris" :key="id"><input type="hidden" name="santri_ids[]" :value="id"></template>
        </form>

        {{-- ====================================================== --}}
        {{-- MOBILE CARDS (< lg)                                     --}}
        {{-- ====================================================== --}}
        <div class="lg:hidden space-y-2">

            @if($santris->count() > 0)
            <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                <input type="checkbox" id="select-all-checkbox" x-model="selectAll" @change="toggleSelectAll()"
                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="select-all-checkbox" class="text-xs font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none flex-1">Pilih Semua</label>
                <span class="text-[11px] text-gray-500 dark:text-gray-600">{{ $santris->count() }} data</span>
            </div>
            @endif

            @forelse($santris as $index => $santri)
            @php
                $isMismatch = $hasGenderMismatch && $mismatchedSantris->contains($santri);
                $isPerempuan = $santri->gender === 'P' || $santri->gender === 'Perempuan';
            @endphp
            <div class="rounded-xl border overflow-hidden transition-all {{ $isMismatch ? 'border-red-300 dark:border-red-700/50 bg-red-50 dark:bg-red-950/10' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }}"
                :class="selectedSantris.includes('{{ $santri->id }}') ? 'border-indigo-500/60 ring-1 ring-indigo-500/20 bg-indigo-50 dark:bg-indigo-950/20' : ''">

                {{-- Row 1: checkbox + name + number --}}
                <div class="flex items-center gap-3 px-4 py-3">
                    <input type="checkbox" class="santri-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 flex-shrink-0 cursor-pointer"
                        value="{{ $santri->id }}"
                        :checked="selectedSantris.includes('{{ $santri->id }}')"
                        @change="toggleSelect('{{ $santri->id }}')">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                            @if($isMismatch)
                            <span class="text-[9px] bg-red-100 dark:bg-red-900/60 text-red-600 dark:text-red-300 border border-red-300 dark:border-red-700/40 px-1.5 py-0.5 rounded-full font-bold flex-shrink-0">⚠️</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis }}</p>
                    </div>
                    <span class="text-[11px] text-gray-500 flex-shrink-0">#{{ $santris->firstItem() + $index }}</span>
                </div>

                {{-- Row 2: 3-col info chips --}}
                <div class="grid grid-cols-3 border-t border-gray-200 dark:border-gray-700/60">
                    <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/60">
                        <p class="text-[10px] text-gray-500 mb-1">Gender</p>
                        @if($isPerempuan)
                            <p class="text-xs font-bold text-pink-500 dark:text-pink-400"> P</p>
                        @else
                            <p class="text-xs font-bold text-blue-500 dark:text-blue-400"> L</p>
                        @endif
                    </div>
                    <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/60">
                        <p class="text-[10px] text-gray-500 mb-1">Target</p>
                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $santri->target_display }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center" x-data="{ show: false }">
                        <p class="text-[10px] text-gray-500 mb-0.5">Password</p>
                        <div class="flex items-center justify-center gap-1">
                            <span x-show="!show" class="font-mono text-[10px] text-gray-400 dark:text-gray-500 tracking-widest">••••••</span>
                            <span x-show="show" class="font-mono text-[10px] text-gray-700 dark:text-gray-300">{{ $santri->user->plain_password ?? '-' }}</span>
                            @if($santri->user && $santri->user->plain_password)
                            <button @click="show=!show" class="text-gray-400 hover:text-indigo-500 dark:text-gray-600 dark:hover:text-indigo-400 transition-colors">
                                <svg x-show="!show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-3 h-3" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Row 3: action buttons --}}
                <div class="grid grid-cols-2 border-t border-gray-200 dark:border-gray-700/60">
                    <button @click="openEditModal({{ json_encode([
                            'id'=>$santri->id,
                            'nis'=>$santri->nis,
                            'name'=>$santri->name,
                            'gender'=>$santri->gender,
                            'kelas_id'=>$santri->kelas_id,
                            'target_metode'=>$santri->target_metode?->value ?? 'quran',
                            'target_juz'=>$santri->target_juz,
                            'target_jilid'=>$santri->target_jilid,
                        ]) }})"
                        class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 active:bg-indigo-100 dark:active:bg-indigo-950/80 transition-colors border-r border-gray-200 dark:border-gray-700/60">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </button>
                    <form action="{{ route('admin.santris.destroy', $santri) }}" method="POST"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus Santri {{ addslashes($santri->name) }}? Akun login juga akan dihapus.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 active:bg-red-100 dark:active:bg-red-950/70 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center">
                <div class="text-4xl mb-3">🎓</div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Tap tombol "+ Tambah Santri" di atas</p>
            </div>
            @endforelse
        </div>

        {{-- ====================================================== --}}
        {{-- DESKTOP TABLE (≥ lg)                                    --}}
        {{-- ====================================================== --}}
        <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="select-all-checkbox" x-model="selectAll" @change="toggleSelectAll()"
                                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 cursor-pointer">
                        </th>
                        <th class="th">No</th>
                        <th class="th">NIS</th>
                        <th class="th">Nama Santri</th>
                        <th class="th">Gender</th>
                        <th class="th">Target Juz</th>
                        <th class="th">Password</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse($santris as $index => $santri)
                    @php
                        $isMismatch = $hasGenderMismatch && $mismatchedSantris->contains($santri);
                        $isPerempuan = $santri->gender === 'P' || $santri->gender === 'Perempuan';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors {{ $isMismatch ? 'border-l-2 border-red-500 dark:border-red-600' : '' }}"
                        :class="selectedSantris.includes('{{ $santri->id }}') ? 'bg-indigo-50 dark:bg-indigo-950/20' : ''">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="santri-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 cursor-pointer"
                                value="{{ $santri->id }}"
                                :checked="selectedSantris.includes('{{ $santri->id }}')"
                                @change="toggleSelect('{{ $santri->id }}')">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $santris->firstItem() + $index }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $santri->nis }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $santri->name }}</span>
                                    @if($isMismatch)
                                    <span class="ml-1.5 text-[9px] bg-red-100 dark:bg-red-900/60 text-red-600 dark:text-red-300 border border-red-300 dark:border-red-700/40 px-1.5 py-0.5 rounded-full font-bold">⚠️ Mismatch</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($isPerempuan)
                                <span class="chip bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30"> P</span>
                            @else
                                <span class="chip bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30"> L</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">📖 {{ $santri->target_display }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5" x-data="{ show: false }">
                                <span x-show="!show" class="font-mono text-xs text-gray-400 dark:text-gray-700">••••••••</span>
                                <span x-show="show" class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $santri->user->plain_password ?? '-' }}</span>
                                @if($santri->user && $santri->user->plain_password)
                                <button @click="show=!show" class="text-gray-400 hover:text-indigo-500 dark:text-gray-600 dark:hover:text-indigo-400 transition-colors">
                                    <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="show" class="w-3.5 h-3.5" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 justify-center">
                                <button @click="openEditModal({{ json_encode(['id'=>$santri->id,'nis'=>$santri->nis,'name'=>$santri->name,'gender'=>$santri->gender,'target_juz'=>$santri->target_juz,'kelas_id'=>$santri->kelas_id]) }})"
                                    class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Edit</button>
                                <form action="{{ route('admin.santris.destroy', $santri) }}" method="POST"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus Santri {{ addslashes($santri->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 border border-red-300 text-red-700 dark:bg-red-600/20 dark:hover:bg-red-600/40 dark:border-red-600/30 dark:text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="py-16 text-center">
                        <div class="text-4xl mb-3">🎓</div>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
                        <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Klik "+ Tambah Santri" di atas</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="mt-5">{{ $santris->links() }}</div>

    </div>{{-- end max-w --}}


    {{-- ====================================================== --}}
    {{-- MODAL: TAMBAH / EDIT SANTRI                             --}}
    {{-- ====================================================== --}}
    <div x-show="isOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeModal()"></div>
        <div class="modal-sheet sm:max-w-2xl"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white" x-text="isEdit ? '✏️ Edit Data Santri' : '➕ Tambah Santri Baru'"></h3>
                <button @click="closeModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="formAction" method="POST" enctype="multipart/form-data" class="p-5 space-y-4 overflow-y-auto" style="max-height:72vh;">
                @csrf
                <input type="hidden" name="_method" x-bind:value="isEdit ? 'PUT' : 'POST'">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="fi-lbl">NIS <span class="text-red-500 dark:text-red-400">*</span></label>
                        <input name="nis" type="text" required x-model="formData.nis" class="fi-inp" placeholder="20240001">
                    </div>
                    <div>
                        <label class="fi-lbl">Jenis Kelamin <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="gender" required x-model="formData.gender" class="fi-inp">
                            <option value="L"> Laki-laki</option>
                            <option value="P"> Perempuan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="fi-lbl">Nama Lengkap <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input name="name" type="text" required x-model="formData.name"
                        @input="smartCapitalize($event)"
                        class="fi-inp" placeholder="Ahmad Zaki">
                </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-3">
                    <div>
                        <label class="fi-lbl">Metode Target <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="target_metode" x-model="formData.target_metode" required class="fi-inp">
                            @foreach (\App\Enums\MetodeTarget::options() as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Target Juz (metode Qur'an) --}}
                    <div x-show="formData.target_metode === 'quran'" x-cloak>
                        <label class="fi-lbl">Target Juz</label>
                        <input type="number" name="target_juz" step="0.5" min="0" max="30"
                            x-model="formData.target_juz" class="fi-inp">
                    </div>

                    {{-- Target Jilid (metode Iqro) --}}
                    <div x-show="formData.target_metode === 'iqro'" x-cloak>
                        <label class="fi-lbl">Target Jilid</label>
                        <select name="target_jilid" x-model="formData.target_jilid" class="fi-inp">
                            @for ($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}">Jilid {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                    <div>
                        <label class="fi-lbl">Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="kelas_id" required x-model="formData.kelas_id" class="fi-inp">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($allKelas as $k)
                                <option value="{{ $k->id }}">{{ $k->name }} - {{ $k->guru->name ?? 'Tanpa Guru' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 rounded-xl p-3.5">
                    <h4 class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Akun Login Santri
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="fi-lbl" x-text="isEdit ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password *'"></label>
                            <div class="relative">
                                <input name="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    :required="!isEdit"
                                    placeholder="Minimal 8 karakter"
                                    class="fi-inp pr-9">
                                <button type="button" @click="showPassword=!showPassword" class="eye-btn">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>
                        <template x-if="!isEdit">
                            <div>
                                <label class="fi-lbl">Konfirmasi Password <span class="text-red-500 dark:text-red-400">*</span></label>
                                <div class="relative">
                                    <input name="password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        required
                                        placeholder="Ulangi password"
                                        class="fi-inp pr-9">
                                    <button type="button" @click="showPasswordConfirmation=!showPasswordConfirmation" class="eye-btn">
                                        <svg x-show="!showPasswordConfirmation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPasswordConfirmation" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="closeModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Santri'"></button>
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
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">📥 Download Template Import Santri</h3>
                <button @click="closeTemplateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.santris.download-template') }}" method="GET" class="p-5 space-y-4" @submit="closeTemplateModal()">
                <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Template Excel berisi format kolom yang benar untuk import data santri. Download template ini sebelum melakukan import.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeTemplateModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition-colors">Download Template</button>
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
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">📤 Export Data Santri untuk Edit</h3>
                <button @click="closeExportEditModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.santris.export-for-edit', $kelas->id) }}" method="GET" class="p-5 space-y-4" @submit="closeExportEditModal()">
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs text-blue-700/80 dark:text-blue-300/80">File Excel akan berisi data santri dari kelas <strong class="text-blue-700 dark:text-blue-200">{{ $kelas->name }}</strong>. Edit data lalu upload kembali untuk update massal.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeExportEditModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold rounded-xl transition-colors">Download Excel</button>
                </div>
            </form>
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
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">📂 Import Data Santri dari Excel</h3>
                <button @click="closeImportModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.santris.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="fi-lbl">Pilih File Excel <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-100 dark:file:bg-blue-600/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-200 dark:hover:file:bg-blue-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1.5">📋 Langkah-langkah import:</p>
                    <ol class="text-[11px] text-blue-700/80 dark:text-blue-300/70 space-y-0.5 list-decimal list-inside">
                        <li>Download template Excel</li>
                        <li>Isi data santri di Excel</li>
                        <li>Upload Excel di sini</li>
                    </ol>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeImportModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition-colors">Import Data</button>
                </div>
            </form>
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
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">🔄 Bulk Update Data Santri</h3>
                <button @click="closeBulkUpdateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.santris.bulk-update') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1.5">📋 Langkah-Langkah:</p>
                    <ol class="text-[11px] text-blue-700/80 dark:text-blue-300/70 space-y-0.5 list-decimal list-inside">
                        <li>Klik tombol "Export" untuk download data santri</li>
                        <li>Edit data di Excel (JANGAN ubah kolom NO URUT)</li>
                        <li>Upload Excel yang sudah diedit di sini</li>
                    </ol>
                </div>
                <div>
                    <label class="fi-lbl">File Excel yang Sudah Diedit <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-100 dark:file:bg-orange-600/20 file:text-orange-700 dark:file:text-orange-400 hover:file:bg-orange-200 dark:hover:file:bg-orange-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                </div>
                <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-1.5">⚠️ PERINGATAN:</p>
                    <ul class="text-[11px] text-red-700/80 dark:text-red-300/70 space-y-0.5 list-disc list-inside">
                        <li>NO URUT wajib ada — jangan dihapus/ubah</li>
                        <li>NIS harus unik</li>
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

</div>{{-- end x-data --}}

<style>
    .th { padding:12px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }

    .modal-wrap { position:fixed; inset:0; z-index:70; display:flex; flex-direction:column; justify-content:flex-end; }
    @media (min-width:640px) { .modal-wrap { justify-content:center; align-items:center; padding:16px; } }
    .modal-bg { position:absolute; inset:0; background:rgba(0,0,0,.5); backdrop-filter:blur(4px); }
    html.dark .modal-bg { background:rgba(0,0,0,.75); }
    .modal-sheet { position:relative; background:#fff; width:100%; border-radius:20px 20px 0 0; border:1px solid #e5e7eb; box-shadow:0 25px 50px rgba(0,0,0,.25); overflow:hidden; }
    html.dark .modal-sheet { background:#111827; border:1px solid rgba(55,65,81,.6); box-shadow:0 25px 50px rgba(0,0,0,.6); }
    @media (min-width:640px) { .modal-sheet { border-radius:20px; } }
    .modal-handle { width:40px; height:4px; background:#d1d5db; border-radius:99px; margin:12px auto 4px; }
    html.dark .modal-handle { background:#374151; }
    @media (min-width:640px) { .modal-handle { display:none; } }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-bottom:1px solid #e5e7eb; }
    html.dark .modal-header { border-bottom:1px solid #1f2937; }
    .modal-close-btn { width:28px; height:28px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:#6b7280; transition:background .15s; }
    html.dark .modal-close-btn { background:#1f2937; color:#9ca3af; }
    .modal-close-btn:hover { background:#e5e7eb; }
    html.dark .modal-close-btn:hover { background:#374151; }

    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#4b5563; margin-bottom:5px; }
    html.dark .fi-lbl { color:#9ca3af; }
    .fi-inp { width:100%; background:#fff; border:1px solid #d1d5db; border-radius:10px; color:#111827; font-size:13px; padding:9px 12px; outline:none; transition:border-color .15s, box-shadow .15s; }
    html.dark .fi-inp { background:#0d1117; border:1px solid #374151; color:#e5e7eb; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    .eye-btn { position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#6b7280; transition:color .15s; }
    .eye-btn:hover { color:#9ca3af; }

    .btn-primary { padding:10px 0; background:#4f46e5; color:#fff; font-size:14px; font-weight:700; border-radius:12px; transition:background .15s; box-shadow:0 4px 14px rgba(79,70,229,.3); }
    .btn-primary:hover { background:#4338ca; }
    .btn-secondary { padding:10px 0; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; font-size:14px; font-weight:600; border-radius:12px; transition:background .15s; }
    html.dark .btn-secondary { background:#1f2937; border:1px solid #374151; color:#d1d5db; }
    .btn-secondary:hover { background:#e5e7eb; }
    html.dark .btn-secondary:hover { background:#374151; }

    input[type="checkbox"]:indeterminate {
        background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 16 16'%3e%3cpath stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 8h8'/%3e%3c/svg%3e");
        background-color:rgb(79 70 229); border-color:rgb(79 70 229);
    }
    [x-cloak] { display:none !important; }

    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }

    /* Custom dropdown arrow untuk select */
    select.fi-inp {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px;
        padding-right: 34px;
    }
</style>

@endsection
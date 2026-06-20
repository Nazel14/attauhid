@extends('layouts.app')

@section('content')
<div class="min-h-screen pb-10"
     x-data="{
        showCreateModal: false,
        showEditModal: false,
        isImportModalOpen: false,
        isTemplateModalOpen: false,
        isExportEditModalOpen: false,
        isBulkUpdateModalOpen: false,
        editData: { id: '', name: '', tahun_ajaran: '', guru_id: '', gender_restriction: '' },
        exportGuruId: '',
        showNotification: false,
        notificationMessage: '',
        notificationType: '',
        selectedKelas: [],
        selectAll: false,

        totalKelas() {
            const ids = [...new Set([...document.querySelectorAll('.kelas-checkbox')].map(cb => cb.value))];
            return ids.length;
        },
        get isIndeterminate() {
            const total = this.totalKelas();
            return this.selectedKelas.length > 0 && this.selectedKelas.length < total;
        },
        toggleSelectAll() {
            if (this.selectAll) {
                const ids = [...new Set([...document.querySelectorAll('.kelas-checkbox')].map(cb => cb.value))];
                this.selectedKelas = ids;
            } else { this.selectedKelas = []; }
            this.updateMainCheckbox();
        },
        toggleSelect(id) {
            const index = this.selectedKelas.indexOf(id);
            if (index > -1) { this.selectedKelas.splice(index, 1); } else { this.selectedKelas.push(id); }
            const total = this.totalKelas();
            this.selectAll = this.selectedKelas.length === total;
            this.updateMainCheckbox();
        },
        updateMainCheckbox() {
            this.$nextTick(() => {
                const total = this.totalKelas();
                document.querySelectorAll('#select-all-checkbox').forEach(cb => {
                    cb.indeterminate = this.selectedKelas.length > 0 && this.selectedKelas.length < total;
                });
            });
        },
        bulkDelete() {
            if (this.selectedKelas.length === 0) { alert('Pilih minimal 1 kelas untuk dihapus'); return; }
            if (confirm(`Apakah Anda yakin ingin menghapus ${this.selectedKelas.length} kelas yang dipilih?`)) {
                this.$refs.bulkDeleteForm.submit();
            }
        },
        openTemplateModal() { this.isTemplateModalOpen = true; },
        closeTemplateModal() { this.isTemplateModalOpen = false; },
        openEdit(kelas) { this.editData = { ...kelas }; this.showEditModal = true; },
        openImportModal() { this.isImportModalOpen = true; },
        openExportEditModal() { this.exportGuruId = ''; this.isExportEditModalOpen = true; },
        openBulkUpdateModal() { this.isBulkUpdateModalOpen = true; },
        closeImportModal() { this.isImportModalOpen = false; },
        closeExportEditModal() { this.isExportEditModalOpen = false; },
        closeBulkUpdateModal() { this.isBulkUpdateModalOpen = false; },
        displayNotification(message, type) {
            this.notificationMessage = message; this.notificationType = type; this.showNotification = true;
            setTimeout(() => { this.showNotification = false; }, 10000);
        },
        smartCapitalize(event) {
            const input = event.target; let value = input.value;
            value = value.replace(/(\d+|[a-zA-Z]+)/g, function(match) {
                if (!/\d/.test(match)) { return match.charAt(0).toUpperCase() + match.slice(1).toLowerCase(); }
                return match;
            });
            input.value = value;
            if (this.editData && event.target.name === 'name') { this.editData.name = value; }
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
     x-effect="if (showCreateModal || showEditModal || isImportModalOpen || isTemplateModalOpen || isExportEditModalOpen || isBulkUpdateModalOpen) { document.body.classList.add('overflow-hidden') } else { document.body.classList.remove('overflow-hidden') }">

    {{-- NOTIFICATION TOAST --}}
    <div x-show="showNotification"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         :class="{
             'bg-emerald-600 border-emerald-500/40': notificationType === 'success',
             'bg-blue-600 border-blue-500/40': notificationType === 'updated',
             'bg-red-600 border-red-500/40': notificationType === 'deleted' || notificationType === 'error'
         }"
         class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] border text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3" style="display:none;">
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
            <a href="{{ route('admin.dashboard') }}" class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate">Management Kelas/Halaqah</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Total: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $kelas->count() }} kelas</span></p>
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
            <button @click="showCreateModal = true" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-indigo-600 bg-indigo-600 hover:bg-indigo-500 hover:border-indigo-500 transition-all active:scale-95 col-span-2 sm:col-span-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Kelas
            </button>
        </div>

        {{-- BULK ACTION BAR --}}
        <div x-show="selectedKelas.length > 0"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-300 dark:border-indigo-700/40 rounded-xl px-4 py-3 mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                    <span x-text="selectedKelas.length"></span> dari <span x-text="totalKelas()"></span> kelas dipilih
                </p>
                <button @click="selectedKelas=[]; selectAll=false; updateMainCheckbox();" class="text-[11px] text-indigo-500 hover:text-indigo-400">Bersihkan pilihan</button>
            </div>
            <button @click="bulkDelete()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Terpilih
            </button>
        </div>

        <form x-ref="bulkDeleteForm" action="{{ route('admin.kelas.bulk-destroy') }}" method="POST" class="hidden">
            @csrf @method('DELETE')
            <template x-for="id in selectedKelas" :key="id"><input type="hidden" name="kelas_ids[]" :value="id"></template>
        </form>

        {{-- MOBILE CARDS --}}
        <div class="lg:hidden space-y-2">
            @if($kelas->count() > 0)
            <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                <input type="checkbox" id="select-all-checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="select-all-checkbox" class="text-xs font-semibold text-gray-600 dark:text-gray-400 cursor-pointer select-none flex-1">Pilih Semua</label>
                <span class="text-[11px] text-gray-500 dark:text-gray-600">{{ $kelas->count() }} data</span>
            </div>
            @endif

            @forelse ($kelas as $kela)
            <div class="rounded-xl border overflow-hidden transition-all"
                 :class="selectedKelas.includes('{{ $kela->id }}') ? 'border-indigo-500/50 ring-1 ring-indigo-500/15 bg-indigo-50 dark:bg-indigo-950/10' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'">
                <div class="flex items-center gap-3 px-4 py-3">
                    <input type="checkbox" class="kelas-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 flex-shrink-0 cursor-pointer"
                        value="{{ $kela->id }}" :checked="selectedKelas.includes('{{ $kela->id }}')" @change="toggleSelect('{{ $kela->id }}')">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $kela->name }}</p>
                        <p class="text-[11px] text-gray-500">{{ $kela->tahun_ajaran }}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border flex-shrink-0
                        {{ $kela->gender_restriction === 'akhwat' ? 'bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30' : ($kela->gender_restriction === 'ikhwan' ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30' : 'bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-950/50 dark:text-purple-400 dark:border-purple-800/30') }}">
                        {{ \App\Models\Kelas::GENDER_RESTRICTIONS[$kela->gender_restriction] ?? '-' }}
                    </span>
                </div>
                <div class="grid grid-cols-2 border-t border-gray-200 dark:border-gray-700/50">
                    <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Wali Kelas</p>
                        <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 truncate">{{ $kela->guru->name ?? 'Tidak Ada' }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Tahun Ajaran</p>
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $kela->tahun_ajaran }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 border-t border-gray-200 dark:border-gray-700/50">
                    <button @click="openEdit({ id: '{{ $kela->id }}', name: '{{ $kela->name }}', tahun_ajaran: '{{ $kela->tahun_ajaran }}', guru_id: '{{ $kela->guru_id }}', gender_restriction: '{{ $kela->gender_restriction }}' })"
                        class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 active:bg-indigo-100 dark:active:bg-indigo-950/80 transition-colors border-r border-gray-200 dark:border-gray-700/50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </button>
                    <form action="{{ route('admin.kelas.destroy', $kela->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kelas {{ addslashes($kela->name) }}?')">
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
                <div class="text-4xl mb-3">🏫</div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data kelas</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1.5">Tap tombol "+ Tambah Kelas" di atas</p>
            </div>
            @endforelse
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="select-all-checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 cursor-pointer">
                        </th>
                        <th class="th">No</th>
                        <th class="th">Nama Kelas</th>
                        <th class="th">Tahun Ajaran</th>
                        <th class="th">Wali Kelas/Murobbi</th>
                        <th class="th">Jenis Kelas</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse ($kelas as $kela)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors" :class="selectedKelas.includes('{{ $kela->id }}') ? 'bg-indigo-50 dark:bg-indigo-950/20' : ''">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="kelas-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 cursor-pointer" value="{{ $kela->id }}" :checked="selectedKelas.includes('{{ $kela->id }}')" @change="toggleSelect('{{ $kela->id }}')">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3"><div class="flex items-center gap-2.5"><span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $kela->name }}</span></div></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $kela->tahun_ajaran }}</td>
                        <td class="px-4 py-3"><span class="chip bg-indigo-100 text-indigo-700 border-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-400 dark:border-indigo-800/30">{{ $kela->guru->name ?? 'Tidak Ada Guru' }}</span></td>
                        <td class="px-4 py-3">
                            <span class="chip
                                {{ $kela->gender_restriction === 'ikhwan' ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30' : '' }}
                                {{ $kela->gender_restriction === 'akhwat' ? 'bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30' : '' }}
                                {{ $kela->gender_restriction === 'campuran' ? 'bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-950/50 dark:text-purple-400 dark:border-purple-800/30' : '' }}">
                                {{ \App\Models\Kelas::GENDER_RESTRICTIONS[$kela->gender_restriction] ?? 'Tidak Diketahui' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 justify-center">
                                <button @click="openEdit({ id: '{{ $kela->id }}', name: '{{ $kela->name }}', tahun_ajaran: '{{ $kela->tahun_ajaran }}', guru_id: '{{ $kela->guru_id }}', gender_restriction: '{{ $kela->gender_restriction }}' })" class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Edit</button>
                                <form action="{{ route('admin.kelas.destroy', $kela->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kelas {{ addslashes($kela->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 border border-red-300 text-red-700 dark:bg-red-600/20 dark:hover:bg-red-600/40 dark:border-red-600/30 dark:text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="py-16 text-center">
                        <div class="text-4xl mb-3">🏫</div>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data kelas</p>
                        <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Klik "+ Tambah Kelas" di atas</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>{{-- end max-w --}}

    {{-- MODAL CREATE KELAS --}}
    <div x-show="showCreateModal" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="showCreateModal = false"></div>
        <div class="modal-sheet sm:max-w-lg" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">➕ Tambah Kelas/Halaqah Baru</h3>
                <button @click="showCreateModal = false" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.kelas.store') }}" method="POST" class="p-5 space-y-4 overflow-y-auto" style="max-height:72vh;">
                @csrf
                <div>
                    <label class="fi-lbl">Nama Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input name="name" type="text" required placeholder="Contoh: Kelas 7A" @input="smartCapitalize($event)" class="fi-inp">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Smart capitalize aktif (contoh: Kelas 7A)</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="fi-lbl">Tahun Ajaran <span class="text-red-500 dark:text-red-400">*</span></label>
                        <input name="tahun_ajaran" type="text" required value="{{ date('Y') . '/' . (date('Y') + 1) }}" class="fi-inp">
                    </div>
                    <div>
                        <label class="fi-lbl">Wali Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="guru_id" required class="fi-inp">
                            <option value="" disabled selected>Pilih Guru</option>
                            @foreach ($gurus as $guru)<option value="{{ $guru->id }}">{{ $guru->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="fi-lbl">Jenis Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                    <select name="gender_restriction" required class="fi-inp">
                        <option value="" disabled selected>Pilih Jenis Kelas</option>
                        @foreach ($genderRestrictions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="showCreateModal = false" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT KELAS --}}
    <div x-show="showEditModal" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="showEditModal = false"></div>
        <div class="modal-sheet sm:max-w-lg" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">✏️ Edit Kelas: <span x-text="editData.name"></span></h3>
                <button @click="showEditModal = false" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="`{{ url('admin/kelas') }}/${editData.id}`" method="POST" class="p-5 space-y-4 overflow-y-auto" style="max-height:72vh;">
                @csrf @method('PUT')
                <div>
                    <label class="fi-lbl">Nama Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input name="name" type="text" x-model="editData.name" required @input="smartCapitalize($event)" class="fi-inp">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Smart capitalize aktif (contoh: Kelas 7A)</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="fi-lbl">Tahun Ajaran <span class="text-red-500 dark:text-red-400">*</span></label>
                        <input name="tahun_ajaran" type="text" x-model="editData.tahun_ajaran" required class="fi-inp">
                    </div>
                    <div>
                        <label class="fi-lbl">Wali Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="guru_id" x-model="editData.guru_id" required class="fi-inp">
                            @foreach ($gurus as $guru)<option value="{{ $guru->id }}">{{ $guru->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="fi-lbl">Jenis Kelas <span class="text-red-500 dark:text-red-400">*</span></label>
                    <select name="gender_restriction" x-model="editData.gender_restriction" required class="fi-inp">
                        @foreach ($genderRestrictions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="showEditModal = false" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Perbarui Kelas</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: TEMPLATE --}}
    <div x-show="isTemplateModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeTemplateModal()"></div>
        <div class="modal-sheet sm:max-w-sm" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">📥 Download Template Import Kelas</h3>
                <button @click="closeTemplateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.kelas.download-template') }}" method="GET" class="p-5 space-y-4" @submit="closeTemplateModal()">
                <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Template Excel berisi format kolom yang benar untuk import data kelas. Download template ini sebelum melakukan import.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeTemplateModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition-colors">Download Template</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: IMPORT --}}
    <div x-show="isImportModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeImportModal()"></div>
        <div class="modal-sheet sm:max-w-md" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">📂 Import Data Kelas dari Excel</h3>
                <button @click="closeImportModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.kelas.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4" @submit="closeImportModal()">
                @csrf
                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl px-4 py-3">
                    <p class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold">⚠️ Penting: Download template terlebih dahulu dan isi sesuai format!</p>
                </div>
                <div>
                    <label class="fi-lbl">Pilih File Excel (.xlsx atau .xls) <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-100 dark:file:bg-blue-600/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-200 dark:hover:file:bg-blue-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Maksimal ukuran file: 2MB</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeImportModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition-colors">Import Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EXPORT --}}
    <div x-show="isExportEditModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeExportEditModal()"></div>
        <div class="modal-sheet sm:max-w-sm" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">📤 Export Data Kelas untuk Edit</h3>
                <button @click="closeExportEditModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.kelas.export-for-edit') }}" method="GET" class="p-5 space-y-4" @submit="closeExportEditModal()">
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs text-blue-700/80 dark:text-blue-300/80">File Excel akan berisi data kelas yang sudah ada. Edit data lalu import kembali untuk update massal.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeExportEditModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold rounded-xl transition-colors">Download Excel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: BULK UPDATE --}}
    <div x-show="isBulkUpdateModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeBulkUpdateModal()"></div>
        <div class="modal-sheet sm:max-w-md" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">🔄 Bulk Update Data Kelas</h3>
                <button @click="closeBulkUpdateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.kelas.bulk-update') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4" @submit="closeBulkUpdateModal()">
                @csrf
                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/30 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1.5">📋 Cara Pakai:</p>
                    <ol class="text-[11px] text-amber-700/80 dark:text-amber-300/70 space-y-0.5 list-decimal list-inside"><li>Klik "Export" terlebih dahulu</li><li>Edit data di Excel yang terdownload</li><li>Upload kembali file Excel tersebut di sini</li></ol>
                </div>
                <div>
                    <label class="fi-lbl">Pilih File Excel yang Sudah Diedit <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-100 dark:file:bg-orange-600/20 file:text-orange-700 dark:file:text-orange-400 hover:file:bg-orange-200 dark:hover:file:bg-orange-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">File harus hasil export dari tombol "Export"</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="closeBulkUpdateModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-orange-600 hover:bg-orange-500 text-white text-sm font-bold rounded-xl transition-colors">Update Sekarang</button>
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
</style>
@endsection
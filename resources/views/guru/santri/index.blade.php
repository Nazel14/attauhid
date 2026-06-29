@extends('layouts.app')

@section('content')
<div class="pb-10"
     x-data="{
        isOpen: false,
        isEdit: false,
        isTemplateModalOpen: false,
        isExportEditModalOpen: false,
        isImportModalOpen: false,
        isBulkUpdateModalOpen: false,
        formAction: '{{ route('guru.santris.store') }}',
        formData: { nis: '', name: '', gender: 'L', target_metode: 'quran', target_juz: '1.00', target_jilid: '1', kelas_id: '' },
        photoPreview: null,
        currentPhotoUrl: null,
        deletePhoto: false,
        showPassword: false,
        showPasswordConfirmation: false,
        selectedSantris: [],
        selectAll: false,

        getVisibleCheckboxes() {
            return [...document.querySelectorAll('.santri-checkbox')].filter(cb => cb.offsetParent !== null);
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedSantris = this.getVisibleCheckboxes().map(cb => cb.value);
            } else {
                this.selectedSantris = [];
            }
            this.$nextTick(() => {
                document.querySelectorAll('#select-all-checkbox').forEach(mc => mc.indeterminate = false);
            });
        },

        toggleSelect(id) {
            const idx = this.selectedSantris.indexOf(id);
            if (idx > -1) this.selectedSantris.splice(idx, 1);
            else this.selectedSantris.push(id);
            const total = this.getVisibleCheckboxes().length;
            this.selectAll = this.selectedSantris.length === total;
            this.$nextTick(() => {
                document.querySelectorAll('#select-all-checkbox').forEach(mc => {
                    mc.indeterminate = this.selectedSantris.length > 0 && this.selectedSantris.length < total;
                });
            });
        },

        bulkDelete() {
            if (!this.selectedSantris.length) { alert('Pilih minimal 1 santri'); return; }
            if (confirm('Hapus ' + this.selectedSantris.length + ' santri? Akun login juga akan dihapus.')) this.$refs.bulkForm.submit();
        },

        openCreateModal() {
            this.isEdit = false;
            this.formAction = '{{ route('guru.santris.store') }}';
            this.formData = { nis:'', name:'', gender:'L', target_metode:'quran', target_juz:'1.00', target_jilid:'1', kelas_id: @if($kelas->isNotEmpty())'{{ $kelas->first()->id }}'@else''@endif };
            this.photoPreview = null; this.currentPhotoUrl = null; this.deletePhoto = false;
            this.showPassword = false; this.showPasswordConfirmation = false;
            this.isOpen = true;
        },

        openEditModal(s, photoUrl) {
            this.isEdit = true;
            this.formAction = '/guru/santris/' + s.id;
            this.formData = { nis:s.nis, name:s.name, gender:s.gender, target_metode:s.target_metode||'quran', target_juz:s.target_juz??'1.00', target_jilid:s.target_jilid??'1', kelas_id:s.kelas_id };
            this.photoPreview = null; this.currentPhotoUrl = photoUrl || null; this.deletePhoto = false;
            this.showPassword = false; this.isOpen = true;
        },
        previewPhoto(e) {
            const file = e.target.files[0];
            if (file) { const r = new FileReader(); r.onload = (ev)=>{ this.photoPreview = ev.target.result; this.deletePhoto = false; }; r.readAsDataURL(file); }
        },
        removePhoto() {
            this.photoPreview = null; this.deletePhoto = true; this.currentPhotoUrl = null;
            const input = document.querySelector('input[name=profile_photo]'); if (input) input.value = '';
        },

        closeModal() { this.isOpen = false; },

        capitalizeInput(e) {
            const input = e.target;
            const pos = input.selectionStart;
            const v = input.value.replace(/(^|\s)(\S)/g, (match, space, char) => space + char.toUpperCase());
            input.value = v;
            input.setSelectionRange(pos, pos);
            this.formData.name = v;
        }
     }"
     x-effect="document.body.classList.toggle('overflow-hidden', isOpen||isImportModalOpen||isTemplateModalOpen||isExportEditModalOpen||isBulkUpdateModalOpen)">

    {{-- NOTIFICATIONS --}}
    @if(session('success'))
    <div id="notif-ok" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-emerald-600 border border-emerald-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{!! session('success') !!}</p>
            <button onclick="document.getElementById('notif-ok').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif
    @if(session('updated'))
    <div id="notif-upd" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-emerald-600 border border-emerald-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{!! session('updated') !!}</p>
            <button onclick="document.getElementById('notif-upd').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif
    @if(session('error') || session('deleted'))
    <div id="notif-err" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-red-600 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{!! session('error') ?? session('deleted') !!}</p>
            <button onclick="document.getElementById('notif-err').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif
    @if($errors->any())
    <div id="notif-val" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-red-600 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-2xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1"><p class="font-semibold text-sm mb-1">Terdapat kesalahan:</p><ul class="text-xs space-y-0.5 opacity-90">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul></div>
                <button onclick="document.getElementById('notif-val').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> Management Santri</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Total: <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $santris->total() }} santri</span></p>
            </div>
        </div>

        {{-- ACTION BUTTONS: 2-col on mobile, flex on desktop --}}
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 mb-4">
            <button @click="isTemplateModalOpen=true" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-emerald-700 bg-emerald-700 hover:bg-emerald-600 hover:border-emerald-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Template
            </button>
            <button @click="isImportModalOpen=true" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-blue-700 bg-blue-700 hover:bg-blue-600 hover:border-blue-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Import
            </button>
            <button @click="isExportEditModalOpen=true" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-purple-700 bg-purple-700 hover:bg-purple-600 hover:border-purple-600 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>
            <button @click="isBulkUpdateModalOpen=true" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white border border-orange-700 bg-orange-700 hover:bg-orange-600 hover:border-orange-600 transition-all active:scale-95">
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
                <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300"><span x-text="selectedSantris.length"></span> santri dipilih</p>
                <button @click="selectedSantris=[]; selectAll=false;" class="text-[11px] text-indigo-500 hover:text-indigo-400">Bersihkan</button>
            </div>
            <button @click="bulkDelete()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus
            </button>
        </div>

        {{-- Bulk Delete Form --}}
        <form x-ref="bulkForm" action="{{ route('guru.santris.bulk-destroy') }}" method="POST" class="hidden">
            @csrf @method('DELETE')
            <template x-for="id in selectedSantris" :key="id"><input type="hidden" name="santri_ids[]" :value="id"></template>
        </form>

        {{-- ====================================================== --}}
        {{-- MOBILE CARDS (< lg) — NO TABLE, no horizontal scroll    --}}
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
            <div class="rounded-xl border overflow-hidden transition-all shadow-sm dark:shadow-none"
                 :class="selectedSantris.includes('{{ $santri->id }}') ? 'border-indigo-500/50 ring-1 ring-indigo-500/15 bg-indigo-50 dark:bg-indigo-950/10' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'">

                {{-- Row 1: checkbox + name + number --}}
                <div class="flex items-center gap-3 px-4 py-3">
                    <input type="checkbox" class="santri-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 flex-shrink-0 cursor-pointer"
                        value="{{ $santri->id }}"
                        :checked="selectedSantris.includes('{{ $santri->id }}')"
                        @change="toggleSelect('{{ $santri->id }}')">

                    @if($santri->user && $santri->user->profile_photo)
                        <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                    @else
                        <span class="w-10 h-10 rounded-full {{ ($santri->gender === 'P' || $santri->gender === 'Perempuan') ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-sm font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                    @endif

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                        <p class="text-[11px] text-gray-500 font-mono">{{ $santri->nis }}</p>
                    </div>
                    <span class="text-[11px] text-gray-400 dark:text-gray-700 flex-shrink-0">#{{ $santris->firstItem() + $index }}</span>
                </div>

                {{-- Row 2: 3-col info chips --}}
                <div class="grid grid-cols-3 border-t border-gray-200 dark:border-gray-700/50">
                    <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Kelas</p>
                        <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 truncate">{{ $santri->kelas->name ?? '-' }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center border-r border-gray-200 dark:border-gray-700/50">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Gender</p>
                        @if($santri->gender === 'P' || $santri->gender === 'Perempuan')
                            <p class="text-xs font-bold text-pink-500 dark:text-pink-400"> P</p>
                        @else
                            <p class="text-xs font-bold text-blue-500 dark:text-blue-400"> L</p>
                        @endif
                    </div>
                    <div class="px-3 py-2.5 text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mb-1">Target</p>
                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $santri->target_display }}</p>
                    </div>
                </div>

                {{-- Row 3: password (if available) --}}
                @if($santri->user && $santri->user->plain_password)
                <div class="flex items-center gap-2 px-4 py-2 border-t border-gray-200 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-900/40"
                     x-data="{ show: false }">
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-[11px] text-gray-500 dark:text-gray-600">Password:</span>
                    <span x-show="!show" class="font-mono text-[11px] text-gray-400 dark:text-gray-700 tracking-widest">••••••••</span>
                    <span x-show="show" class="font-mono text-[11px] text-gray-700 dark:text-gray-300">{{ $santri->user->plain_password }}</span>
                    <button @click="show=!show" class="ml-auto p-1 text-gray-400 hover:text-indigo-500 dark:text-gray-600 dark:hover:text-indigo-400 transition-colors">
                        <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @endif

                {{-- Row 4: action buttons --}}
                <div class="grid grid-cols-2 border-t border-gray-200 dark:border-gray-700/50">
                    <button @click="openEditModal({{ json_encode([
                        'id'=>$santri->id,
                        'nis'=>$santri->nis,
                        'name'=>$santri->name,
                        'gender'=>$santri->gender,
                        'kelas_id'=>$santri->kelas_id,
                        'target_metode'=>$santri->target_metode?->value ?? 'quran',
                        'target_juz'=>$santri->target_juz,
                        'target_jilid'=>$santri->target_jilid,
                    ]) }}, '{{ $santri->user && $santri->user->profile_photo ? asset('storage/'.$santri->user->profile_photo) : '' }}')"
                        class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 active:bg-indigo-100 dark:active:bg-indigo-950/80 transition-colors border-r border-gray-200 dark:border-gray-700/50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </button>
                    <form action="{{ route('guru.santris.destroy', $santri->id) }}" method="POST"
                          onsubmit="return confirm('Hapus santri {{ addslashes($santri->name) }}? Akun login juga akan dihapus.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 active:bg-red-100 dark:active:bg-red-950/70 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                    </form>
                </div>

            </div>
            @empty
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                <div class="text-4xl mb-3">👨‍🎓</div>
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
                        <th class="th">Kelas</th>
                        <th class="th">Password</th>
                        <th class="th">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse($santris as $index => $santri)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors"
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
                                @if($santri->user && $santri->user->profile_photo)
                                    <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-9 h-9 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                @else
                                    <span class="w-9 h-9 rounded-full {{ ($santri->gender === 'P' || $santri->gender === 'Perempuan') ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-xs font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                                @endif
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $santri->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($santri->gender === 'P' || $santri->gender === 'Perempuan')
                                <span class="chip bg-pink-100 text-pink-700 border-pink-300 dark:bg-pink-950/50 dark:text-pink-400 dark:border-pink-800/30"> P</span>
                            @else
                                <span class="chip bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-800/30"> L</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">📖 {{ $santri->target_display }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="chip bg-indigo-100 text-indigo-700 border-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-400 dark:border-indigo-800/30">{{ $santri->kelas->name ?? '-' }}</span>
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
                            <div class="flex gap-2">
                                <button @click="openEditModal({{ json_encode(['id'=>$santri->id,'nis'=>$santri->nis,'name'=>$santri->name,'gender'=>$santri->gender,'target_metode'=>$santri->target_metode?->value ?? 'quran','target_juz'=>$santri->target_juz,'target_jilid'=>$santri->target_jilid,'kelas_id'=>$santri->kelas_id]) }}, '{{ $santri->user && $santri->user->profile_photo ? asset('storage/'.$santri->user->profile_photo) : '' }}')"
                                    class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Edit</button>
                                <form action="{{ route('guru.santris.destroy', $santri->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus santri {{ addslashes($santri->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 border border-red-300 text-red-700 dark:bg-red-600/20 dark:hover:bg-red-600/40 dark:border-red-600/30 dark:text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="py-16 text-center">
                        <div class="text-4xl mb-3">👨‍🎓</div>
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
    {{-- MODAL: TAMBAH/EDIT --}}
    {{-- ====================================================== --}}
    <div x-show="isOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeModal()"></div>
        <div class="modal-sheet sm:max-w-lg"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white" x-text="isEdit ? '✏️ Edit Santri' : '➕ Tambah Santri Baru'"></h3>
                <button @click="closeModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="formAction" method="POST" enctype="multipart/form-data" class="p-5 space-y-3 overflow-y-auto" style="max-height:72vh;">
                @csrf
                <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="delete_photo" x-bind:value="deletePhoto ? '1' : '0'">

                {{-- Foto Profil --}}
                <div class="flex items-center gap-4">
                    <div class="relative flex-shrink-0">
                        <template x-if="photoPreview">
                            <img :src="photoPreview" class="w-16 h-16 rounded-full object-cover object-top border-2 border-indigo-300 dark:border-indigo-700">
                        </template>
                        <template x-if="!photoPreview && currentPhotoUrl">
                            <img :src="currentPhotoUrl" class="w-16 h-16 rounded-full object-cover object-top border-2 border-gray-300 dark:border-gray-600">
                        </template>
                        <template x-if="!photoPreview && !currentPhotoUrl">
                            <span class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-950 border-2 border-indigo-200 dark:border-indigo-800 flex items-center justify-center">
                                <svg class="w-7 h-7 text-indigo-400 dark:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                        </template>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-100 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:border-indigo-600/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-600/40 transition-colors">
                            <span x-text="(photoPreview || currentPhotoUrl) ? 'Ganti Foto' : 'Pilih Foto (opsional)'"></span>
                            <input type="file" name="profile_photo" accept="image/*" class="hidden" @change="previewPhoto($event)">
                        </label>
                        <button x-show="photoPreview || currentPhotoUrl" type="button" @click="removePhoto()" class="text-[11px] font-semibold text-red-600 dark:text-red-400 hover:underline text-left">Hapus Foto</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="fi-lbl">NIS <span class="text-red-500 dark:text-red-400">*</span></label><input type="text" name="nis" x-model="formData.nis" required class="fi-inp" placeholder="12345"></div>
                    <div><label class="fi-lbl">Kelamin <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="gender" x-model="formData.gender" required class="fi-inp">
                            <option value="L">Laki-laki</option><option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div><label class="fi-lbl">Nama Lengkap <span class="text-red-500 dark:text-red-400">*</span></label><input type="text" name="name" x-model="formData.name" required @input="capitalizeInput($event)" class="fi-inp" placeholder="Ahmad Zaki"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="fi-lbl">Metode Target <span class="text-red-500 dark:text-red-400">*</span></label>
                        <select name="target_metode" x-model="formData.target_metode" required class="fi-inp">
                            @foreach (\App\Enums\MetodeTarget::options() as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fi-lbl">Kelas</label>
                        <input type="hidden" name="kelas_id" x-model="formData.kelas_id">
                        <div class="fi-inp bg-gray-50 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400 flex items-center">
                            @if($kelas->isNotEmpty()){{ $kelas->first()->name }}@else- belum punya kelas -@endif
                        </div>
                    </div>
                </div>

                {{-- Target Juz (metode Qur'an) --}}
                <div x-show="formData.target_metode === 'quran'" x-cloak>
                    <label class="fi-lbl">Target Juz <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="number" name="target_juz" x-model="formData.target_juz" min="0" max="30" step="0.5" class="fi-inp" placeholder="5.00">
                </div>

                {{-- Target Jilid (metode Iqro) --}}
                <div x-show="formData.target_metode === 'iqro'" x-cloak>
                    <label class="fi-lbl">Target Jilid <span class="text-red-500 dark:text-red-400">*</span></label>
                    <select name="target_jilid" x-model="formData.target_jilid" class="fi-inp">
                        @for ($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}">Jilid {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <template x-if="isEdit">
                    <p class="text-[11px] text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-lg px-3 py-2">💡 Kosongkan password jika tidak ingin mengubah.</p>
                </template>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="fi-lbl" x-text="isEdit ? 'Password Baru' : 'Password *'"></label>
                        <div class="relative"><input :type="showPassword?'text':'password'" name="password" :required="!isEdit" class="fi-inp pr-9" placeholder="••••">
                        <button type="button" @click="showPassword=!showPassword" class="eye-btn"><svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><svg x-show="showPassword" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg></button></div>
                    </div>
                    <div>
                        <label class="fi-lbl" x-text="isEdit ? 'Konfirmasi' : 'Konfirmasi *'"></label>
                        <div class="relative"><input :type="showPasswordConfirmation?'text':'password'" name="password_confirmation" :required="!isEdit" class="fi-inp pr-9" placeholder="••••">
                        <button type="button" @click="showPasswordConfirmation=!showPasswordConfirmation" class="eye-btn"><svg x-show="!showPasswordConfirmation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><svg x-show="showPasswordConfirmation" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg></button></div>
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
        <div class="modal-bg" @click="isTemplateModalOpen=false"></div>
        <div class="modal-sheet sm:max-w-sm" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header"><h3 class="text-sm font-bold text-gray-900 dark:text-white">📥 Download Template</h3><button @click="isTemplateModalOpen=false" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <div class="p-5 space-y-4">
                <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/30 rounded-xl px-4 py-3">Template Excel berisi format kolom yang benar. Santri yang diimport otomatis masuk ke kelas Anda.</p>
                <form action="{{ route('guru.santris.download-template') }}" method="GET" @submit="isTemplateModalOpen=false" class="flex gap-3">
                    <button type="button" @click="isTemplateModalOpen=false" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl transition-colors">Download</button>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: EXPORT --}}
    <div x-show="isExportEditModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="isExportEditModalOpen=false"></div>
        <div class="modal-sheet sm:max-w-sm" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header"><h3 class="text-sm font-bold text-gray-900 dark:text-white">📤 Export Data Santri</h3><button @click="isExportEditModalOpen=false" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <div class="p-5 space-y-4">
                <div class="text-xs text-blue-700/80 dark:text-blue-300/80 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3 space-y-1">
                    <p>Export data santri, edit di Excel, lalu upload kembali untuk update massal.</p>
                    <p class="text-amber-600 dark:text-amber-400 font-semibold">⚠️ Kelas tidak bisa diubah via bulk update.</p>
                </div>
                <form action="{{ route('guru.santris.export-for-edit') }}" method="GET" @submit="isExportEditModalOpen=false" class="flex gap-3">
                    <button type="button" @click="isExportEditModalOpen=false" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold rounded-xl transition-colors">Download Excel</button>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: IMPORT --}}
    <div x-show="isImportModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="isImportModalOpen=false"></div>
        <div class="modal-sheet sm:max-w-md" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header"><h3 class="text-sm font-bold text-gray-900 dark:text-white">📂 Import Data Santri</h3><button @click="isImportModalOpen=false" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form action="{{ route('guru.santris.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <div><label class="fi-lbl">Pilih File Excel <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-100 dark:file:bg-blue-600/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-200 dark:hover:file:bg-blue-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Format: .xlsx / .xls | Maks 2MB</p>
                </div>
                <div><label class="fi-lbl">ZIP Foto (opsional)</label>
                    <input type="file" name="photos_zip" accept=".zip"
                        class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-100 dark:file:bg-indigo-600/20 file:text-indigo-700 dark:file:text-indigo-400 hover:file:bg-indigo-200 dark:hover:file:bg-indigo-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">ZIP berisi kumpulan foto. Nama tiap foto = kolom <b>nama_file_foto</b>. Maks 20MB.</p>
                </div>
                <div class="text-xs text-blue-700/80 dark:text-blue-300/80 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="font-semibold text-blue-700 dark:text-blue-300 mb-1.5">📋 Langkah-langkah:</p>
                    <ol class="space-y-0.5 list-decimal list-inside"><li>Download template, isi + kolom nama_file_foto</li><li>Upload Excel</li><li>Kumpulkan foto jadi 1 ZIP, upload di sini</li></ol>
                </div>
                <div class="flex gap-3"><button type="button" @click="isImportModalOpen=false" class="btn-secondary flex-1">Batal</button><button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition-colors">Import Data</button></div>
            </form>
        </div>
    </div>

    {{-- MODAL: BULK UPDATE --}}
    <div x-show="isBulkUpdateModalOpen" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="isBulkUpdateModalOpen=false"></div>
        <div class="modal-sheet sm:max-w-md" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header"><h3 class="text-sm font-bold text-gray-900 dark:text-white">🔄 Bulk Update Santri</h3><button @click="isBulkUpdateModalOpen=false" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form action="{{ route('guru.santris.bulk-update') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <div class="text-xs text-blue-700/80 dark:text-blue-300/80 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3">
                    <p class="font-semibold text-blue-700 dark:text-blue-300 mb-1">📋 Langkah:</p>
                    <ol class="space-y-0.5 list-decimal list-inside"><li>Export data santri</li><li>Edit di Excel (jangan ubah NO URUT)</li><li>Upload kembali di sini</li></ol>
                </div>
                <div><label class="fi-lbl">File Excel yang Sudah Diedit <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-100 dark:file:bg-orange-600/20 file:text-orange-700 dark:file:text-orange-400 hover:file:bg-orange-200 dark:hover:file:bg-orange-600/30">
                </div>
                <div><label class="fi-lbl">ZIP Foto (opsional)</label>
                    <input type="file" name="photos_zip" accept=".zip"
                        class="w-full text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2.5 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-100 dark:file:bg-indigo-600/20 file:text-indigo-700 dark:file:text-indigo-400 hover:file:bg-indigo-200 dark:hover:file:bg-indigo-600/30">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">ZIP berisi kumpulan foto. Kosongkan kalau tidak ubah foto. Maks 20MB.</p>
                </div>
                <div class="text-xs text-red-700/80 dark:text-red-300/80 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/30 rounded-xl px-4 py-3">
                    <p class="font-semibold text-red-600 dark:text-red-400 mb-1">⚠️ Peringatan:</p>
                    <ul class="space-y-0.5 list-disc list-inside"><li>NO URUT wajib ada</li><li>Kelas tidak bisa diubah</li><li>Password tidak bisa diubah via Excel</li></ul>
                </div>
                <div class="flex gap-3"><button type="button" @click="isBulkUpdateModalOpen=false" class="btn-secondary flex-1">Batal</button><button type="submit" class="flex-1 py-2.5 bg-orange-600 hover:bg-orange-500 text-white text-sm font-bold rounded-xl transition-colors">Update Semua</button></div>
            </form>
        </div>
    </div>

</div>

<style>
    /* Action buttons */
    .act-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border:1px solid; border-radius:10px; font-size:12px; font-weight:700; color:#fff; transition:all .15s; white-space:nowrap; }

    /* Table header */
    .th { padding:12px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }

    /* Chips/badges */
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }

    /* Modal wrappers */
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

    /* Form elements */
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#4b5563; margin-bottom:5px; }
    html.dark .fi-lbl { color:#9ca3af; }
    .fi-inp { width:100%; background:#fff; border:1px solid #d1d5db; border-radius:10px; color:#111827; font-size:13px; padding:9px 12px; outline:none; transition:border-color .15s, box-shadow .15s; }
    html.dark .fi-inp { background:#0d1117; border:1px solid #374151; color:#e5e7eb; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    .eye-btn { position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#6b7280; transition:color .15s; }
    .eye-btn:hover { color:#9ca3af; }

    /* Common buttons */
    .btn-primary { padding:10px 0; background:#4f46e5; color:#fff; font-size:14px; font-weight:700; border-radius:12px; transition:background .15s; box-shadow:0 4px 14px rgba(79,70,229,.3); }
    .btn-primary:hover { background:#4338ca; }
    .btn-secondary { padding:10px 0; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; font-size:14px; font-weight:600; border-radius:12px; transition:background .15s; }
    html.dark .btn-secondary { background:#1f2937; border:1px solid #374151; color:#d1d5db; }
    .btn-secondary:hover { background:#e5e7eb; }
    html.dark .btn-secondary:hover { background:#374151; }

    /* Notification */
    @keyframes slide-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .animate-slide-in { animation:slide-in .25s ease-out; }

    /* Scrollbar */
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }

    /* Custom dropdown arrow untuk select */
    select.fi-inp {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2.5'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 12px center !important;
        background-size: 16px 16px !important;
        padding-right: 36px;
    }
    html.dark select.fi-inp {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' viewBox='0 0 24 24' stroke='%23cbd5e1' stroke-width='2.5'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3e%3c/svg%3e") !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    ['notif-ok', 'notif-upd', 'notif-err', 'notif-val'].forEach(id => {
        const n = document.getElementById(id);
        if (!n) return;
        setTimeout(() => {
            n.style.transition = 'opacity .4s';
            n.style.opacity = '0';
            setTimeout(() => n.remove(), 400);
        }, 10000);
    });
});
</script>
@endsection
@extends('layouts.app')

@section('content')
<div class="min-h-screen pb-10"
     x-data="{
        showCreateModal: false,
        showEditModal: false,
        editData: { id: '', sesi_name: '', urutan: 0, is_active: false },
        showNotification: false,
        notificationMessage: '',
        notificationType: '',
        openCreateModal() { this.showCreateModal = true; },
        closeCreateModal() { this.showCreateModal = false; },
        openEditModal(sesi) {
            this.editData = { id: sesi.id, sesi_name: sesi.sesi_name, urutan: sesi.urutan, is_active: sesi.is_active };
            this.showEditModal = true;
        },
        closeEditModal() { this.showEditModal = false; },
        displayNotification(message, type) {
            this.notificationMessage = message;
            this.notificationType = type;
            this.showNotification = true;
            setTimeout(() => { this.showNotification = false; }, 5000);
        },
        capitalizeInput(event) {
            const input = event.target;
            const words = input.value.split(' ');
            const capitalized = words.map(word => word.length > 0 ? word.charAt(0).toUpperCase() + word.slice(1).toLowerCase() : word).join(' ');
            input.value = capitalized;
            if (this.editData && event.target.name === 'sesi_name') {
                this.editData.sesi_name = capitalized;
            }
        }
     }"
     x-init="
        @if(session('success')) displayNotification('{{ session('success') }}', 'success')
        @elseif(session('updated')) displayNotification('{{ session('updated') }}', 'updated')
        @elseif(session('deleted')) displayNotification('{{ session('deleted') }}', 'deleted')
        @elseif(session('error')) displayNotification('{{ session('error') }}', 'error')
        @endif
     "
     x-effect="if (showCreateModal || showEditModal) { document.body.classList.add('overflow-hidden') } else { document.body.classList.remove('overflow-hidden') }">

    {{-- NOTIFICATION TOAST --}}
    <div x-show="showNotification"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-end="opacity-0"
         :class="{
             'bg-emerald-600 border-emerald-500/40': notificationType === 'success',
             'bg-blue-600 border-blue-500/40': notificationType === 'updated',
             'bg-red-600 border-red-500/40': notificationType === 'deleted' || notificationType === 'error'
         }"
         class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] border text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3"
         style="display:none;">
        <svg x-show="notificationType==='success'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <svg x-show="notificationType==='updated'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <svg x-show="notificationType==='deleted'||notificationType==='error'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="flex-1 text-sm font-medium" x-text="notificationMessage"></p>
        <button @click="showNotification=false" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white">🕐 Kelola Sesi Presensi</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Atur sesi yang tersedia untuk input presensi santri</p>
            </div>
            <button @click="openCreateModal()"
                class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">Tambah Sesi</span><span class="sm:hidden">+</span>
            </button>
        </div>

        {{-- INFO BOX --}}
        <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/30 rounded-xl px-4 py-3 mb-5 flex items-start gap-3">
            <svg class="w-4 h-4 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <p class="text-xs text-blue-700/90 dark:text-blue-300/80">Sesi yang <strong class="text-blue-800 dark:text-blue-200">aktif</strong> akan muncul sebagai pilihan saat guru input presensi. Anda bisa menonaktifkan sesi tanpa menghapusnya.</p>
        </div>

        {{-- MOBILE CARDS (< lg) --}}
        <div class="lg:hidden space-y-2">
            @forelse($sesis as $sesi)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none {{ !$sesi->is_active ? 'opacity-60' : '' }}">
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-9 h-9 rounded-lg flex-shrink-0 flex items-center justify-center font-black text-base
                        {{ $sesi->is_active ? 'bg-indigo-100 border border-indigo-300 text-indigo-600 dark:bg-indigo-500/15 dark:border-indigo-500/30 dark:text-indigo-400' : 'bg-gray-100 border border-gray-200 text-gray-400 dark:bg-gray-700/50 dark:border-gray-700 dark:text-gray-600' }}">
                        {{ $sesi->urutan }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $sesi->sesi_name }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-600 mt-0.5">{{ $sesi->updated_at->format('d M Y H:i') }}</p>
                    </div>
                    @if($sesi->is_active)
                        <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30 flex-shrink-0">Aktif</span>
                    @else
                        <span class="chip bg-gray-100 text-gray-500 border-gray-300 dark:bg-gray-800 dark:text-gray-500 dark:border-gray-700 flex-shrink-0">Nonaktif</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 border-t border-gray-200 dark:border-gray-700/50">
                    <form action="{{ route('admin.presensi-sesis.toggle', $sesi) }}" method="POST" class="contents">
                        @csrf @method('PATCH')
                        <button type="submit" class="py-2.5 text-xs font-semibold text-center border-r border-gray-200 dark:border-gray-700/50 transition-colors {{ $sesi->is_active ? 'text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-950/40' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' }}">
                            {{ $sesi->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                    <button @click='openEditModal(@json($sesi))'
                        class="py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors border-r border-gray-200 dark:border-gray-700/50">Edit</button>
                    <form action="{{ route('admin.presensi-sesis.destroy', $sesi) }}" method="POST"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus sesi {{ $sesi->sesi_name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">Hapus</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                <div class="text-4xl mb-3">🕐</div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada sesi presensi</p>
                <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Tap tombol "+ Tambah Sesi" di atas</p>
            </div>
            @endforelse
        </div>

        {{-- DESKTOP TABLE (>= lg) --}}
        <div class="hidden lg:block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                        <th class="th">Urutan</th>
                        <th class="th">Nama Sesi</th>
                        <th class="th">Status</th>
                        <th class="th">Terakhir Diubah</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @forelse($sesis as $sesi)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/25 transition-colors {{ !$sesi->is_active ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm
                                {{ $sesi->is_active ? 'bg-indigo-100 border border-indigo-300 text-indigo-600 dark:bg-indigo-500/15 dark:border-indigo-500/30 dark:text-indigo-400' : 'bg-gray-100 border border-gray-200 text-gray-400 dark:bg-gray-700/50 dark:border-gray-700 dark:text-gray-600' }}">
                                {{ $sesi->urutan }}
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $sesi->sesi_name }}</td>
                        <td class="px-5 py-3.5">
                            @if($sesi->is_active)
                                <span class="chip bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800/30">✅ Aktif</span>
                            @else
                                <span class="chip bg-gray-100 text-gray-500 border-gray-300 dark:bg-gray-800 dark:text-gray-500 dark:border-gray-700">⏸ Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-sm text-gray-500">{{ $sesi->updated_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.presensi-sesis.toggle', $sesi) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors {{ $sesi->is_active ? 'bg-yellow-50 border-yellow-300 text-yellow-600 hover:bg-yellow-100 dark:bg-yellow-600/15 dark:border-yellow-600/30 dark:text-yellow-400 dark:hover:bg-yellow-600/25' : 'bg-emerald-50 border-emerald-300 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-600/15 dark:border-emerald-600/30 dark:text-emerald-400 dark:hover:bg-emerald-600/25' }}">
                                        {{ $sesi->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <button @click='openEditModal(@json($sesi))'
                                    class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Edit</button>
                                <form action="{{ route('admin.presensi-sesis.destroy', $sesi) }}" method="POST"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus sesi {{ $sesi->sesi_name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 border border-red-300 text-red-700 dark:bg-red-600/20 dark:hover:bg-red-600/40 dark:border-red-600/30 dark:text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-16 text-center">
                        <div class="text-4xl mb-3">🕐</div>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada sesi presensi</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- MODAL CREATE --}}
    <div x-show="showCreateModal" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeCreateModal()"></div>
        <div class="modal-sheet sm:max-w-md"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">➕ Tambah Sesi Presensi</h3>
                <button @click="closeCreateModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.presensi-sesis.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="fi-lbl">Nama Sesi <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="text" name="sesi_name" required placeholder="Contoh: Dzuhur"
                        @input="capitalizeInput($event)" class="fi-inp">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Huruf awal setiap kata otomatis kapital</p>
                </div>
                <div>
                    <label class="fi-lbl">Urutan <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="number" name="urutan" value="0" min="0" required class="fi-inp">
                    <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Urutan sesi dari terkecil ke terbesar</p>
                </div>
                <div class="flex items-center gap-2.5 py-1">
                    <input type="checkbox" name="is_active" value="1" checked id="create_is_active"
                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <label for="create_is_active" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">Aktifkan sesi ini</label>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="closeCreateModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Simpan Sesi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div x-show="showEditModal" class="modal-wrap" style="display:none;" x-cloak>
        <div class="modal-bg" @click="closeEditModal()"></div>
        <div class="modal-sheet sm:max-w-md"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">✏️ Edit Sesi: <span x-text="editData.sesi_name" class="text-indigo-600 dark:text-indigo-300"></span></h3>
                <button @click="closeEditModal()" class="modal-close-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="`{{ url('admin/presensi-sesis') }}/${editData.id}`" method="POST" class="p-5 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="fi-lbl">Nama Sesi <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="text" name="sesi_name" x-model="editData.sesi_name" required
                        @input="capitalizeInput($event)" class="fi-inp">
                </div>
                <div>
                    <label class="fi-lbl">Urutan <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input type="number" name="urutan" x-model="editData.urutan" min="0" required class="fi-inp">
                </div>
                <div class="flex items-center gap-2.5 py-1">
                    <input type="checkbox" name="is_active" x-model="editData.is_active" value="1" id="edit_is_active"
                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <label for="edit_is_active" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">Aktifkan sesi ini</label>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="closeEditModal()" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Perbarui Sesi</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    .th { padding:12px 20px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    .modal-wrap { position:fixed; inset:0; z-index:70; display:flex; flex-direction:column; justify-content:flex-end; }
    @media(min-width:640px){ .modal-wrap { justify-content:center; align-items:center; padding:16px; } }
    .modal-bg { position:absolute; inset:0; background:rgba(0,0,0,.5); backdrop-filter:blur(4px); }
    html.dark .modal-bg { background:rgba(0,0,0,.75); }
    .modal-sheet { position:relative; background:#fff; width:100%; border-radius:20px 20px 0 0; border:1px solid #e5e7eb; box-shadow:0 25px 50px rgba(0,0,0,.25); overflow:hidden; }
    html.dark .modal-sheet { background:#111827; border:1px solid rgba(55,65,81,.6); box-shadow:0 25px 50px rgba(0,0,0,.6); }
    @media(min-width:640px){ .modal-sheet { border-radius:20px; } }
    .modal-handle { width:40px; height:4px; background:#d1d5db; border-radius:99px; margin:12px auto 4px; }
    html.dark .modal-handle { background:#374151; }
    @media(min-width:640px){ .modal-handle { display:none; } }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-bottom:1px solid #e5e7eb; }
    html.dark .modal-header { border-bottom:1px solid #1f2937; }
    .modal-close-btn { width:28px; height:28px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:#6b7280; transition:background .15s; }
    html.dark .modal-close-btn { background:#1f2937; color:#9ca3af; }
    .modal-close-btn:hover { background:#e5e7eb; }
    html.dark .modal-close-btn:hover { background:#374151; }
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#4b5563; margin-bottom:5px; }
    html.dark .fi-lbl { color:#9ca3af; }
    .fi-inp { width:100%; background:#fff; border:1px solid #d1d5db; border-radius:10px; color:#111827; font-size:13px; padding:9px 12px; outline:none; transition:border-color .15s,box-shadow .15s; }
    html.dark .fi-inp { background:#0d1117; border:1px solid #374151; color:#e5e7eb; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    .btn-primary { padding:10px 0; background:#4f46e5; color:#fff; font-size:14px; font-weight:700; border-radius:12px; transition:background .15s; box-shadow:0 4px 14px rgba(79,70,229,.3); }
    .btn-primary:hover { background:#4338ca; }
    .btn-secondary { padding:10px 0; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; font-size:14px; font-weight:600; border-radius:12px; transition:background .15s; }
    html.dark .btn-secondary { background:#1f2937; border:1px solid #374151; color:#d1d5db; }
    .btn-secondary:hover { background:#e5e7eb; }
    html.dark .btn-secondary:hover { background:#374151; }
    [x-cloak] { display:none !important; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>
@endsection
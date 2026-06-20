@extends('layouts.app')

@section('content')
<div class="py-3 sm:py-6 lg:py-8"
     x-data="{
        isOpen: false,
        isEdit: false,
        formAction: '{{ route('admin.admins.store') }}',
        formData: {
            name: '',
            password: '',
            password_confirmation: '',
            is_super_admin: 0
        },
        currentAdminId: null,
        showNotification: false,
        notificationMessage: '',
        notificationType: '',

        openCreateModal() {
            this.isEdit = false;
            this.formAction = '{{ route('admin.admins.store') }}';
            this.formData = { name: '', password: '', password_confirmation: '', is_super_admin: 0 };
            this.isOpen = true;
        },
        openEditModal(admin) {
            this.isEdit = true;
            this.currentAdminId = admin.id;
            this.formAction = `/admin/admins/${admin.id}`;
            this.formData = { name: admin.name, is_super_admin: admin.is_super_admin ? 1 : 0, password: '', password_confirmation: '' };
            this.isOpen = true;
        },
        closeModal() { this.isOpen = false; },
        displayNotification(message, type) {
            this.notificationMessage = message; this.notificationType = type; this.showNotification = true;
            setTimeout(() => { this.showNotification = false; }, 20000);
        },
        smartCapitalizeInput(event) {
            const input = event.target; let value = input.value;
            if (value.length > 0) value = value.charAt(0).toUpperCase() + value.slice(1);
            input.value = value; this.formData.name = value;
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
     x-effect="if (isOpen) { document.body.classList.add('overflow-hidden') } else { document.body.classList.remove('overflow-hidden') }">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        {{-- Notifikasi --}}
        <div x-show="showNotification"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="{
                 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-700/50 dark:text-emerald-300': notificationType === 'success',
                 'bg-red-50 border-red-300 text-red-700 dark:bg-red-950 dark:border-red-700/50 dark:text-red-300': notificationType === 'error'
             }"
             class="border rounded-xl px-4 py-3 mb-4 flex items-center justify-between gap-3"
             role="alert" style="display: none;">
            <div class="flex items-center gap-2">
                <svg x-show="notificationType === 'success'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <svg x-show="notificationType === 'error'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium" x-html="notificationMessage"></span>
            </div>
            <button @click="showNotification = false" class="opacity-70 hover:opacity-100 transition-opacity flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Header --}}
        <div class="mb-4 sm:mb-5">
            <a href="{{ route('admin.dashboard') }}"
            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 mb-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="text-yellow-500 dark:text-yellow-400">👑</span> Management Admin
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">Kelola akun administrator sistem</p>
                    <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-yellow-600 dark:text-yellow-400">
                            <span class="w-1.5 h-1.5 bg-yellow-500 dark:bg-yellow-400 rounded-full"></span>
                            Super Admin: {{ \App\Models\User::where('role', 'admin')->permission('kelola-admin')->count() }}/3
                        </span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-blue-600 dark:text-blue-400">
                            <span class="w-1.5 h-1.5 bg-blue-500 dark:bg-blue-400 rounded-full"></span>
                            Admin: {{ \App\Models\User::where('role', 'admin')->whereDoesntHave('permissions', fn($q) => $q->where('name', 'kelola-admin'))->count() }}/5
                        </span>
                    </div>
                </div>
                <button @click="openCreateModal()"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 rounded-xl text-xs font-semibold text-white transition-all active:scale-95 shadow-lg shadow-indigo-900/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="hidden sm:inline">Tambah Admin</span>
                    <span class="sm:hidden">Tambah</span>
                </button>
            </div>
        </div>

        {{-- Tabel: sm+ --}}
        <div class="hidden sm:block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden mb-4 shadow-sm dark:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/80">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider w-10">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nama Admin</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">Level</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
                        @forelse ($admins as $index => $admin)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-600">{{ $admins->firstItem() + $index }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $admin->name }}
                                            @if($admin->id === auth()->id())
                                                <span class="text-indigo-600 dark:text-indigo-400">(Anda)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2" x-data="{ show: false }">
                                    <span x-show="!show" class="font-mono text-xs text-gray-500">••••••••</span>
                                    <span x-show="show" class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $admin->plain_password ?? '-' }}</span>
                                    @if($admin->plain_password)
                                    <button @click="show = !show" type="button" class="text-gray-400 hover:text-gray-600 dark:text-gray-600 dark:hover:text-gray-400 transition-colors">
                                        <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($admin->hasPermissionTo('kelola-admin'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 border border-yellow-300 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-700/50 dark:text-yellow-400">👑 Super Admin</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 border border-blue-300 text-blue-800 dark:bg-blue-950 dark:border-blue-700/50 dark:text-blue-400">⚡ Admin</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEditModal({{ json_encode(['id' => $admin->id,'name' => $admin->name,'is_super_admin' => $admin->hasPermissionTo('kelola-admin')]) }})"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-indigo-100 border border-indigo-300 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-950 dark:border-indigo-800/50 dark:text-indigo-400 dark:hover:bg-indigo-900/50 transition-colors">Edit</button>
                                    @if(!$admin->hasPermissionTo('kelola-admin') && $admin->id !== auth()->id())
                                    <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="inline" onsubmit="return confirm('Hapus admin {{ $admin->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-red-100 border border-red-300 text-red-700 hover:bg-red-200 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors">Hapus</button>
                                    </form>
                                    @elseif($admin->hasPermissionTo('kelola-admin'))
                                    <span class="text-[10px] text-gray-400 dark:text-gray-700 italic">Terlindungi</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <p class="text-xs text-gray-500 dark:text-gray-600">Tidak ada data admin</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card list: mobile --}}
        <div class="sm:hidden space-y-2 mb-4">
            @forelse ($admins as $index => $admin)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3 shadow-sm dark:shadow-none">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ $admin->name }}
                                @if($admin->id === auth()->id())<span class="text-indigo-600 dark:text-indigo-400 text-[10px]">(Anda)</span>@endif
                            </p>
                            @if($admin->hasPermissionTo('kelola-admin'))
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 border border-yellow-300 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-700/50 dark:text-yellow-400">👑 Super</span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 border border-blue-300 text-blue-800 dark:bg-blue-950 dark:border-blue-700/50 dark:text-blue-400">⚡ Admin</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 mt-1" x-data="{ show: false }">
                            <span class="text-[10px] text-gray-500 dark:text-gray-600">Password:</span>
                            <span x-show="!show" class="font-mono text-[10px] text-gray-500">••••••••</span>
                            <span x-show="show" class="font-mono text-[10px] text-gray-600 dark:text-gray-400">{{ $admin->plain_password ?? '-' }}</span>
                            @if($admin->plain_password)
                            <button @click="show = !show" type="button" class="text-gray-400 hover:text-gray-600 dark:text-gray-600 dark:hover:text-gray-400">
                                <svg x-show="!show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button @click="openEditModal({{ json_encode(['id' => $admin->id,'name' => $admin->name,'is_super_admin' => $admin->hasPermissionTo('kelola-admin')]) }})"
                            class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-indigo-100 border border-indigo-300 text-indigo-700 dark:bg-indigo-950 dark:border-indigo-800/50 dark:text-indigo-400 active:scale-95 transition-all">Edit</button>
                        @if(!$admin->hasPermissionTo('kelola-admin') && $admin->id !== auth()->id())
                        <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" onsubmit="return confirm('Hapus admin {{ $admin->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-red-100 border border-red-300 text-red-700 dark:bg-red-950 dark:border-red-800/50 dark:text-red-400 active:scale-95 transition-all">Hapus</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-600">Tidak ada data admin</p>
            </div>
            @endforelse
        </div>

        @if($admins->hasPages())
        <div class="mt-2">{{ $admins->links() }}</div>
        @endif

    </div>

    {{-- Modal --}}
    <div x-show="isOpen" class="fixed inset-0 z-[60] overflow-y-auto" style="display: none;" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-black/40 dark:bg-black/70 backdrop-blur-sm" @click="closeModal()"></div>

        <div class="relative min-h-screen flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg overflow-hidden"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

                <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-100 dark:bg-indigo-600/30 border border-indigo-300 dark:border-indigo-600/50 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <span x-text="isEdit ? 'Edit Admin' : 'Tambah Admin Baru'"></span>
                    </h3>
                    <button @click="closeModal()" class="w-7 h-7 rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" class="p-4 space-y-4">
                    @csrf
                    <template x-if="isEdit">@method('PUT')</template>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Nama Lengkap <span class="text-red-500 dark:text-red-400">*</span></label>
                        <input name="name" type="text" required x-model="formData.name" @input="smartCapitalizeInput($event)" placeholder="Ahmad Zainudin"
                            class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 mt-1">Nama yang akan ditampilkan di sistem</p>
                    </div>

                    <template x-if="!isEdit">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Password <span class="text-red-500 dark:text-red-400">*</span></label>
                                <div class="relative" x-data="{ showPass: false }">
                                    <input name="password" :type="showPass ? 'text' : 'password'" required x-model="formData.password" placeholder="Min. 8 karakter"
                                        class="w-full px-3 py-2.5 pr-9 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                                    <button @click="showPass = !showPass" type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-600 dark:hover:text-gray-400 transition-colors">
                                        <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Konfirmasi <span class="text-red-500 dark:text-red-400">*</span></label>
                                <div class="relative" x-data="{ showConfirm: false }">
                                    <input name="password_confirmation" :type="showConfirm ? 'text' : 'password'" required x-model="formData.password_confirmation" placeholder="Ulangi password"
                                        class="w-full px-3 py-2.5 pr-9 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                                    <button @click="showConfirm = !showConfirm" type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-600 dark:hover:text-gray-400 transition-colors">
                                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Level Admin <span class="text-red-500 dark:text-red-400">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-start gap-2.5 cursor-pointer p-3 border-2 rounded-xl transition-all"
                                   :class="formData.is_super_admin === 1 ? 'border-yellow-500/60 bg-yellow-50 dark:bg-yellow-950/40' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-gray-300 dark:hover:border-gray-600'">
                                <input type="radio" name="is_super_admin" value="1" x-model.number="formData.is_super_admin" class="w-3.5 h-3.5 mt-0.5 text-yellow-500 flex-shrink-0">
                                <div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block">👑 Super Admin</span>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Akses penuh, maks 3 akun</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 cursor-pointer p-3 border-2 rounded-xl transition-all"
                                   :class="formData.is_super_admin === 0 ? 'border-blue-500/60 bg-blue-50 dark:bg-blue-950/40' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-gray-300 dark:hover:border-gray-600'">
                                <input type="radio" name="is_super_admin" value="0" x-model.number="formData.is_super_admin" class="w-3.5 h-3.5 mt-0.5 text-blue-500 flex-shrink-0">
                                <div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block">⚡ Admin Biasa</span>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Akses terbatas, maks 5 akun</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/40 rounded-xl p-3 flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[10px] text-indigo-700 dark:text-indigo-300 leading-relaxed">Super Admin (maks 3) tidak dapat dihapus. Admin Biasa (maks 5) dapat dikelola sepenuhnya.</p>
                    </div>

                    <div x-show="isEdit" class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/40 rounded-xl p-3 flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[10px] text-amber-700 dark:text-amber-300 leading-relaxed">Password tidak dapat diubah melalui form ini demi keamanan sistem.</p>
                    </div>

                    <div class="flex gap-2 pt-2 border-t border-gray-200 dark:border-gray-800">
                        <button type="button" @click="closeModal()" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 rounded-xl text-xs font-semibold text-white transition-colors shadow-lg shadow-indigo-900/30">
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Admin'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
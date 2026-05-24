@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-950 pb-10">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white"> Log Activity</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Monitor semua aktivitas pengguna di sistem</p>
            </div>
            @if(auth()->user()->hasPermissionTo('kelola-admin'))
            <button onclick="openCleanupModal()"
                class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-red-700 hover:bg-red-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span class="hidden sm:inline">Cleanup Logs</span>
            </button>
            @endif
        </div>

        {{-- ALERT MESSAGES --}}
        @if(session('success'))
        <div class="bg-emerald-950/40 border border-emerald-700/40 text-emerald-300 px-4 py-3 rounded-xl text-sm mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-950/40 border border-red-700/40 text-red-300 px-4 py-3 rounded-xl text-sm mb-4">{{ session('error') }}</div>
        @endif
        @if(session('info'))
        <div class="bg-blue-950/40 border border-blue-700/40 text-blue-300 px-4 py-3 rounded-xl text-sm mb-4">{{ session('info') }}</div>
        @endif

        {{-- STATS CARDS --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-4">
                <p class="text-[11px] text-gray-500 font-medium mb-1">Total Logs</p>
                <p class="text-2xl font-black text-white">{{ number_format($stats['total']) }}</p>
                <div class="w-8 h-8 bg-blue-500/10 border border-blue-500/20 rounded-lg flex items-center justify-center mt-2">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                </div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-4">
                <p class="text-[11px] text-gray-500 font-medium mb-1">Hari Ini</p>
                <p class="text-2xl font-black text-emerald-400">{{ number_format($stats['today']) }}</p>
                <div class="w-8 h-8 bg-emerald-500/10 border border-emerald-500/20 rounded-lg flex items-center justify-center mt-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-4">
                <p class="text-[11px] text-gray-500 font-medium mb-1">Admin</p>
                <p class="text-2xl font-black text-purple-400">{{ number_format($stats['by_role']['admin'] ?? 0) }}</p>
                <div class="w-8 h-8 bg-purple-500/10 border border-purple-500/20 rounded-lg flex items-center justify-center mt-2">
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-4">
                <p class="text-[11px] text-gray-500 font-medium mb-1">Guru</p>
                <p class="text-2xl font-black text-orange-400">{{ number_format($stats['by_role']['guru'] ?? 0) }}</p>
                <div class="w-8 h-8 bg-orange-500/10 border border-orange-500/20 rounded-lg flex items-center justify-center mt-2">
                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            {{-- CARD SANTRI (full-width di mobile, normal di desktop) --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl px-4 py-4 col-span-2 lg:col-span-1">
                <p class="text-[11px] text-gray-500 font-medium mb-1">Santri</p>
                <p class="text-2xl font-black text-emerald-400">{{ number_format($stats['by_role']['santri'] ?? 0) }}</p>
                <div class="w-8 h-8 bg-emerald-500/10 border border-emerald-500/20 rounded-lg flex items-center justify-center mt-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
            </div>
        </div>

        {{-- FILTER SECTION --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-5">
            <p class="text-xs font-bold text-gray-400 mb-3 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter Data
            </p>
            <form method="GET" action="{{ route('admin.logs.index') }}" class="space-y-3">
                <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    <div>
                        <label class="fi-lbl">Role</label>
                        <select name="role" class="fi-inp">
                            <option value="">Semua Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fi-lbl">Module</label>
                        <select name="module" class="fi-inp">
                            <option value="">Semua Module</option>
                            @foreach($modules as $key => $value)
                                <option value="{{ $key }}" {{ request('module') == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fi-lbl">Tipe Aktivitas</label>
                        <select name="activity_type" class="fi-inp">
                            <option value="">Semua Tipe</option>
                            @foreach($activityTypes as $key => $value)
                                <option value="{{ $key }}" {{ request('activity_type') == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fi-lbl">User</label>
                        <select name="user_id" class="fi-inp">
                            <option value="">Semua User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ ucfirst($user->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fi-lbl">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="fi-inp">
                    </div>
                    <div>
                        <label class="fi-lbl">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="fi-inp">
                    </div>
                    <div class="col-span-2">
                        <label class="fi-lbl">Cari Deskripsi</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aktivitas..." class="fi-inp">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                    </button>
                    <a href="{{ route('admin.logs.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- TABS --}}
        @php
            $activeTab = request('tab', 'all');
            $baseQuery = request()->except(['tab', 'page']);

            $tabs = [
                'all'    => 'Semua',
                'auth'   => 'Autentikasi',
                'crud'   => 'CRUD',
                'admin'  => 'Admin Only',
                'guru'   => 'Guru Only',
                'santri' => 'Santri Only',
            ];
        @endphp
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($tabs as $tabKey => $tabLabel)
                @php
                    $isActive = $activeTab === $tabKey;
                    $tabUrl   = route('admin.logs.index', array_merge($baseQuery, ['tab' => $tabKey]));
                @endphp
                <a href="{{ $tabUrl }}"
                   class="px-3 py-1.5 text-xs font-{{ $isActive ? 'bold' : 'semibold' }} rounded-lg transition-colors
                          {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-gray-800 hover:bg-gray-700 text-gray-400' }}">
                    {{ $tabLabel }}
                    @if($isActive)
                        <span class="ml-1 bg-indigo-500/40 text-indigo-200 text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                            {{ number_format($logs->total()) }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

      {{-- LOGS LIST: Mobile Cards + Desktop Table --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">

            {{-- Header info --}}
            <div class="px-4 py-2.5 border-b border-gray-800 flex items-center justify-between">
                <p class="text-[11px] text-gray-500">
                    Menampilkan
                    <span class="text-gray-300 font-semibold">{{ $logs->firstItem() ?? 0 }}</span>–<span class="text-gray-300 font-semibold">{{ $logs->lastItem() ?? 0 }}</span>
                    dari <span class="text-gray-300 font-semibold">{{ number_format($logs->total()) }}</span> log
                </p>
                @if($activeTab !== 'all' || request()->hasAny(['role','module','activity_type','user_id','date_from','date_to','search']))
                <span class="text-[10px] bg-indigo-900/40 border border-indigo-700/40 text-indigo-300 px-2 py-0.5 rounded-full font-semibold">Filter Aktif</span>
                @endif
            </div>

            @php
                $roleColors = [
                    'admin'  => 'bg-purple-950/50 text-purple-400 border-purple-800/30',
                    'guru'   => 'bg-blue-950/50 text-blue-400 border-blue-800/30',
                    'santri' => 'bg-emerald-950/50 text-emerald-400 border-emerald-800/30',
                ];
                $activityColors = [
                    'login'    => 'bg-emerald-950/50 text-emerald-400 border-emerald-800/30',
                    'logout'   => 'bg-gray-800 text-gray-500 border-gray-700',
                    'create'   => 'bg-blue-950/50 text-blue-400 border-blue-800/30',
                    'update'   => 'bg-amber-950/50 text-amber-400 border-amber-800/30',
                    'delete'   => 'bg-red-950/50 text-red-400 border-red-800/30',
                    'download' => 'bg-orange-950/50 text-orange-400 border-orange-800/30',
                    'view'     => 'bg-indigo-950/50 text-indigo-400 border-indigo-800/30',
                    'export'   => 'bg-teal-950/50 text-teal-400 border-teal-800/30',
                    'import'   => 'bg-cyan-950/50 text-cyan-400 border-cyan-800/30',
                    'submit'   => 'bg-violet-950/50 text-violet-400 border-violet-800/30',
                ];
                $activityDot = [
                    'login'    => 'bg-emerald-400',
                    'logout'   => 'bg-gray-500',
                    'create'   => 'bg-blue-400',
                    'update'   => 'bg-amber-400',
                    'delete'   => 'bg-red-400',
                    'download' => 'bg-orange-400',
                    'view'     => 'bg-indigo-400',
                    'export'   => 'bg-teal-400',
                    'import'   => 'bg-cyan-400',
                    'submit'   => 'bg-violet-400',
                ];
            @endphp

            {{-- ================================== --}}
            {{-- MOBILE: COMPACT LIST (< lg)        --}}
            {{-- ================================== --}}
            <div class="lg:hidden divide-y divide-gray-800/50">
                @forelse($logs as $index => $log)
                <a href="{{ route('admin.logs.show', $log) }}" class="block px-4 py-3 hover:bg-gray-800/30 active:bg-gray-800/50 transition-colors">
                    {{-- Top row: dot + user + time --}}
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $activityDot[$log->activity_type] ?? 'bg-gray-500' }}"></span>
                        <p class="text-sm font-bold text-white flex-1 truncate">{{ $log->user?->name ?? 'N/A' }}</p>
                        <span class="text-[10px] text-gray-500 flex-shrink-0">{{ $log->created_at->format('d/m H:i') }}</span>
                    </div>

                    {{-- Middle: description --}}
                    <p class="text-[12px] text-gray-300 mb-2 line-clamp-2 pl-4.5" style="padding-left:18px;">{{ $log->description }}</p>

                    {{-- Bottom: chips --}}
                    <div class="flex items-center gap-1.5 flex-wrap" style="padding-left:18px;">
                        <span class="chip {{ $roleColors[$log->role] ?? 'bg-gray-800 text-gray-500 border-gray-700' }}">{{ ucfirst($log->role) }}</span>
                        <span class="chip {{ $activityColors[$log->activity_type] ?? 'bg-gray-800 text-gray-500 border-gray-700' }}">{{ $log->formatted_activity_type }}</span>
                        <span class="text-[10px] text-gray-500">·</span>
                        <span class="text-[10px] text-gray-500 font-medium">{{ $log->formatted_module }}</span>
                        @if(auth()->user()->hasPermissionTo('kelola-admin'))
                        <span class="flex-1"></span>
                        <button onclick="event.preventDefault(); event.stopPropagation(); if(confirm('Hapus log ini?')) document.getElementById('del-log-{{ $log->id }}').submit();"
                                class="text-[10px] font-bold text-red-400 hover:text-red-300 px-2 py-0.5 rounded">Hapus</button>
                        @endif
                    </div>
                </a>
                @if(auth()->user()->hasPermissionTo('kelola-admin'))
                <form id="del-log-{{ $log->id }}" action="{{ route('admin.logs.destroy', $log) }}" method="POST" class="hidden">
                    @csrf @method('DELETE')
                </form>
                @endif
                @empty
                <div class="py-16 text-center">
                    <div class="text-4xl mb-3">📋</div>
                    <p class="text-sm font-semibold text-gray-400">Tidak ada log activity</p>
                    <p class="text-xs text-gray-600 mt-1">Coba ubah filter atau lakukan aktivitas terlebih dahulu</p>
                </div>
                @endforelse
            </div>

            {{-- ================================== --}}
            {{-- DESKTOP: FULL TABLE (≥ lg)         --}}
            {{-- ================================== --}}
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-800 bg-gray-800/40">
                            <th class="th w-12">No</th>
                            <th class="th">Waktu</th>
                            <th class="th">User</th>
                            <th class="th">Role</th>
                            <th class="th">Aktivitas</th>
                            <th class="th">Module</th>
                            <th class="th">Deskripsi</th>
                            <th class="th text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @forelse($logs as $index => $log)
                        <tr class="hover:bg-gray-800/25 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-600 text-center">{{ $logs->firstItem() + $index }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-300 whitespace-nowrap">{{ $log->user?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="chip {{ $roleColors[$log->role] ?? 'bg-gray-800 text-gray-500 border-gray-700' }}">{{ ucfirst($log->role) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="chip {{ $activityColors[$log->activity_type] ?? 'bg-gray-800 text-gray-500 border-gray-700' }}">{{ $log->formatted_activity_type }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $log->formatted_module }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $log->description }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.logs.show', $log) }}"
                                        class="px-2.5 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/40 border border-indigo-600/30 text-indigo-400 text-xs font-semibold rounded-lg transition-colors">Detail</a>
                                    @if(auth()->user()->hasPermissionTo('kelola-admin'))
                                    <form action="{{ route('admin.logs.destroy', $log) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus log ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 bg-red-600/20 hover:bg-red-600/40 border border-red-600/30 text-red-400 text-xs font-semibold rounded-lg transition-colors">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="py-16 text-center">
                            <div class="text-4xl mb-3">📋</div>
                            <p class="text-sm font-semibold text-gray-400">Tidak ada log activity</p>
                            <p class="text-xs text-gray-600 mt-1">Coba ubah filter atau lakukan aktivitas terlebih dahulu</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 flex justify-center">{{ $logs->links('vendor.pagination.simple-dark') }}</div>
    </div>
</div>

{{-- CLEANUP MODAL --}}
<div id="cleanupModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" onclick="closeCleanupModal()"></div>
    <div class="relative bg-gray-900 border border-gray-700 w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="w-10 h-1 bg-gray-700 rounded-full mx-auto mt-3 mb-1 sm:hidden"></div>
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-800">
            <h3 class="text-sm font-bold text-white">🗑️ Cleanup Logs</h3>
            <button onclick="closeCleanupModal()" class="w-7 h-7 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:text-white"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('admin.logs.cleanup') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="fi-lbl">Hapus log lebih dari:</label>
                <select name="days" class="fi-inp">
                    <option value="1">1 hari</option>
                    <option value="7">7 hari</option>
                    <option value="30">30 hari</option>
                    <option value="60">60 hari</option>
                    <option value="90">90 hari (3 bulan)</option>
                    <option value="180">180 hari (6 bulan)</option>
                    <option value="365">365 hari (1 tahun)</option>
                </select>
            </div>
            <div class="bg-yellow-950/30 border border-yellow-800/40 rounded-xl px-4 py-3">
                <p class="text-xs text-yellow-300/80"><strong>Peringatan:</strong> Aksi ini tidak dapat dibatalkan. Log yang dihapus tidak dapat dikembalikan.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCleanupModal()" class="btn-secondary flex-1">Batal</button>
                <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-xl transition-colors">Hapus Logs</button>
            </div>
        </form>
    </div>
</div>

<style>
    body { background:#030712; }
    .th { padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    .fi-lbl { display:block; font-size:11px; font-weight:600; color:#9ca3af; margin-bottom:5px; }
    .fi-inp { width:100%; background:#0d1117; border:1px solid #374151; border-radius:10px; color:#e5e7eb; font-size:13px; padding:8px 12px; outline:none; transition:border-color .15s; }
    .fi-inp:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.18); }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .btn-secondary { padding:10px 0; background:#1f2937; border:1px solid #374151; color:#d1d5db; font-size:14px; font-weight:600; border-radius:12px; transition:background .15s; }
    .btn-secondary:hover { background:#374151; }
    /* ===== PAGINATION STYLING ===== */
nav[role="navigation"] {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 4px;
}

/* Container "Showing X to Y of Z" — hide on mobile */
nav[role="navigation"] > div:first-child {
    display: none;
}
@media (min-width: 640px) {
    nav[role="navigation"] > div:first-child {
        display: none;
    }
}

/* Container untuk button next/prev */
nav[role="navigation"] > div:last-child {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: center;
}

/* Semua button/link pagination */
nav[role="navigation"] span,
nav[role="navigation"] a {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #9ca3af !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    min-width: 34px !important;
    text-align: center !important;
    transition: all 0.15s ease !important;
    box-shadow: none !important;
    line-height: 1.2 !important;
}

/* Hover state */
nav[role="navigation"] a:hover {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #e5e7eb !important;
    transform: translateY(-1px);
}

/* Active page (halaman yang sedang aktif) */
nav[role="navigation"] [aria-current="page"] span {
    background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
    border-color: #6366f1 !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4) !important;
}

/* Disabled state (Previous saat di halaman 1, Next saat di halaman terakhir) */
nav[role="navigation"] [aria-disabled="true"] span {
    background: #111827 !important;
    border-color: #1f2937 !important;
    color: #4b5563 !important;
    cursor: not-allowed !important;
    opacity: 0.5;
}

/* Previous & Next button — beri padding lebih */
nav[role="navigation"] a[rel="prev"],
nav[role="navigation"] a[rel="next"],
nav[role="navigation"] [aria-disabled="true"]:first-child span,
nav[role="navigation"] [aria-disabled="true"]:last-child span {
    padding: 6px 14px !important;
    font-weight: 700 !important;
}

/* Tampilkan SVG icon dengan warna inheriting */
nav[role="navigation"] svg {
    width: 14px !important;
    height: 14px !important;
}

/* "..." (dots) — gaya berbeda dari link biasa */
nav[role="navigation"] span:not([aria-current]):not([class*="bg-"]) {
    background: transparent !important;
    border: none !important;
    color: #6b7280 !important;
    padding: 6px 4px !important;
}
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
    input[type="date"], input[type="month"] { color-scheme: dark; }
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="month"]::-webkit-calendar-picker-indicator {
        background-color: transparent;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: center; background-size: 16px;
        width: 20px; height: 20px; cursor: pointer; opacity: 1; filter: none;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover,
    input[type="month"]::-webkit-calendar-picker-indicator:hover { opacity: 0.7; }

    
</style>

<script>
function openCleanupModal()  { document.getElementById('cleanupModal').classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
function closeCleanupModal() { document.getElementById('cleanupModal').classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCleanupModal(); });
</script>
@endsection
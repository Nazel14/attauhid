@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-950 pb-10">

    <div class="max-w-5xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('admin.logs.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-white"> Detail Log Activity</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">Informasi lengkap aktivitas pengguna</p>
            </div>
            @if(auth()->user()->hasPermissionTo('kelola-admin'))
            <form action="{{ route('admin.logs.destroy', $log->id) }}" method="POST"
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus log activity ini?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-red-700 hover:bg-red-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span class="hidden sm:inline">Hapus Log</span>
                </button>
            </form>
            @endif
        </div>

        {{-- INFO CARDS GRID --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">

            {{-- User Info --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Informasi User</p>
                <div class="flex items-center gap-3">
                    
                    <div>
                        <p class="text-base font-bold text-white">{{ $log->user?->name ?? 'N/A' }}</p>
                        @php $roleColors = ['admin'=>'bg-purple-950/50 text-purple-400 border-purple-800/30','guru'=>'bg-blue-950/50 text-blue-400 border-blue-800/30','santri'=>'bg-emerald-950/50 text-emerald-400 border-emerald-800/30']; @endphp
                        <span class="chip {{ $roleColors[$log->role] ?? 'bg-gray-800 text-gray-500 border-gray-700' }} mt-1">{{ ucfirst($log->role) }}</span>
                    </div>
                </div>
            </div>

            {{-- Timestamp --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Waktu Aktivitas</p>
                <p class="text-lg font-black text-white">{{ $log->created_at->format('d F Y') }}</p>
                <p class="text-sm font-mono text-indigo-400 mt-0.5">{{ $log->created_at->format('H:i:s') }}</p>
                <p class="text-[11px] text-gray-600 mt-2">{{ $log->created_at->diffForHumans() }}</p>
            </div>

            {{-- Module & Activity --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Module & Aktivitas</p>
                <p class="text-base font-bold text-gray-200 mb-2">{{ $log->formatted_module }}</p>
                @php
                    $activityColors = ['login'=>'bg-emerald-950/50 text-emerald-400 border-emerald-800/30','logout'=>'bg-gray-800 text-gray-500 border-gray-700','create'=>'bg-blue-950/50 text-blue-400 border-blue-800/30','update'=>'bg-amber-950/50 text-amber-400 border-amber-800/30','delete'=>'bg-red-950/50 text-red-400 border-red-800/30','export'=>'bg-teal-950/50 text-teal-400 border-teal-800/30','import'=>'bg-cyan-950/50 text-cyan-400 border-cyan-800/30'];
                @endphp
                <span class="chip {{ $activityColors[$log->activity_type] ?? 'bg-gray-800 text-gray-500 border-gray-700' }}">{{ $log->formatted_activity_type }}</span>
            </div>

            {{-- Network Info --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Informasi Jaringan</p>
                <p class="text-[11px] text-gray-500 mb-1.5">IP Address</p>
                <code class="text-sm font-mono font-bold text-gray-300 bg-gray-800 border border-gray-700 px-3 py-1.5 rounded-lg inline-block">{{ $log->ip_address ?? 'N/A' }}</code>
            </div>
        </div>

        {{-- Description --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-4">
            <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Deskripsi Aktivitas</p>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl px-4 py-3">
                <p class="text-sm text-gray-300 leading-relaxed">{{ $log->description }}</p>
            </div>
        </div>

        {{-- User Agent --}}
        @if($log->user_agent)
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-4">
            <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Device & Browser</p>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl px-4 py-3">
                <p class="text-xs font-mono text-gray-400 break-all">{{ $log->user_agent }}</p>
            </div>
        </div>
        @endif

        {{-- Data Changes --}}
        @if($log->old_values || $log->new_values)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
            @if($log->old_values)
            <div class="bg-gray-900 border border-red-800/30 rounded-xl p-4">
                <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider mb-3">Data Sebelum Perubahan</p>
                <div class="bg-red-950/20 border border-red-800/20 rounded-xl p-3 overflow-x-auto">
                    <pre class="text-xs text-red-300/80 font-mono">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif
            @if($log->new_values)
            <div class="bg-gray-900 border border-emerald-800/30 rounded-xl p-4">
                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider mb-3">Data Setelah Perubahan</p>
                <div class="bg-emerald-950/20 border border-emerald-800/20 rounded-xl p-3 overflow-x-auto">
                    <pre class="text-xs text-emerald-300/80 font-mono">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Model Info --}}
        @if($log->model_type && $log->model_id)
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-3">Informasi Database</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl px-4 py-3">
                    <p class="text-[10px] text-gray-600 mb-1">Model Type</p>
                    <p class="text-sm font-mono font-bold text-gray-300">{{ class_basename($log->model_type) }}</p>
                </div>
                <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl px-4 py-3">
                    <p class="text-[10px] text-gray-600 mb-1">Model ID</p>
                    <p class="text-sm font-mono font-bold text-gray-300">{{ $log->model_id }}</p>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

<style>
    body { background:#030712; }
    .chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:700; border-width:1px; border-style:solid; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#374151; border-radius:99px; }
</style>
@endsection
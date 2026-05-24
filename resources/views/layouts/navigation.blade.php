<nav x-data="{ open: false }" class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
    {{-- Container disamakan dengan dashboard (max-w-7xl) supaya navbar align dengan konten --}}
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">
        <div class="flex justify-between h-14">

            {{-- Kiri: Logo + Desktop Nav --}}
            <div class="flex items-center gap-1">
                <div class="shrink-0 flex items-center mr-2">
    <a href="..."
       class="flex items-center justify-center w-10 h-10 p-1.5 bg-indigo-950/40 border border-indigo-800/40 rounded-lg hover:border-indigo-700/60 transition">
        <img src="/favicon.ico" alt="Logo" class="w-full h-full" style="object-fit:contain; display:block;">
    </a>
</div>

                {{-- Desktop Nav --}}
                <div class="hidden sm:flex sm:items-center sm:gap-0.5">
                    @php
                        if(auth()->user()->role === 'santri') {
                            $dashLink = route('santri.dashboard');
                            $dashActive = request()->routeIs('santri.dashboard');
                        } else {
                            $dashLink = auth()->user()->role === 'admin' ? route('dashboard') : route('guru.dashboard');
                            $dashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard');
                        }
                    @endphp
                    <a href="{{ $dashLink }}"
                       class="px-3 py-2 text-xs font-medium rounded-lg transition
                              {{ $dashActive ? 'text-white bg-gray-800 border border-gray-700' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                        Dashboard
                    </a>

                    @if(auth()->user()->role === 'admin')
                        {{-- Admin: Management dropdown --}}
                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition">
                                Management
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute top-full left-0 mt-1 w-44 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                                @can('kelola-admin')
                                <a href="{{ route('admin.admins.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Kelola Admin
                                </a>
                                <div class="border-t border-gray-700"></div>
                                @endcan
                                @can('kelola-guru')
                                <a href="{{ route('admin.gurus.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    Guru
                                </a>
                                @endcan
                                @can('kelola-kelas')
                                <a href="{{ route('admin.kelas.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Kelas
                                </a>
                                @endcan
                                @can('kelola-santri')
                                <a href="{{ route('admin.santris.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Santri
                                </a>
                                @endcan
                                @can('kelola-presensi-sesi')
                                <a href="{{ route('admin.presensi-sesis.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Sesi Presensi
                                </a>
                                @endcan
                            </div>
                        </div>

                        {{-- Admin: Laporan dropdown --}}
                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition">
                                Laporan & Log
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute top-full left-0 mt-1 w-44 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                                @can('lihat-monitoring')
                                <a href="{{ route('admin.monitoring.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    Monitoring Santri
                                </a>
                                @endcan
                                @can('lihat-log-activity')
                                <a href="{{ route('admin.logs.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    Log Activity
                                </a>
                                @endcan
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->role === 'guru')
                        <a href="{{ route('guru.input.create') }}"
                           class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg transition
                                  {{ request()->routeIs('guru.input.create') ? 'text-white bg-indigo-600 border border-indigo-500' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Input Data
                        </a>

                        {{-- Riwayat dropdown --}}
                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition">
                                Riwayat
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute top-full left-0 mt-1 w-44 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                                <a href="{{ route('guru.setorans.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    Riwayat Setoran
                                </a>
                                <a href="{{ route('guru.presensis.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Riwayat Presensi
                                </a>
                            </div>
                        </div>

                        {{-- Management dropdown --}}
                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition">
                                Management
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute top-full left-0 mt-1 w-44 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                                <a href="{{ route('guru.santris.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Kelola Santri
                                </a>
                                <a href="{{ route('guru.rapot.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Rapot
                                </a>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->role === 'santri')
                        <a href="{{ route('santri.rapot.show') }}"
                           class="px-3 py-2 text-xs font-medium rounded-lg transition
                                  {{ request()->routeIs('santri.rapot.*') ? 'text-white bg-gray-800 border border-gray-700' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Rapot
                        </a>
                        <a href="{{ route('santri.setorans.detail') }}"
                           class="px-3 py-2 text-xs font-medium rounded-lg transition
                                  {{ request()->routeIs('santri.setorans.detail') ? 'text-white bg-gray-800 border border-gray-700' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Detail Setoran
                        </a>
                        <a href="{{ route('santri.presensis.detail') }}"
                           class="px-3 py-2 text-xs font-medium rounded-lg transition
                                  {{ request()->routeIs('santri.presensis.detail') ? 'text-white bg-gray-800 border border-gray-700' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Detail Presensi
                        </a>
                    @endif
                </div>
            </div>

            {{-- Kanan: User + Hamburger --}}
            <div class="flex items-center gap-2">
                {{-- Desktop user dropdown --}}
                <div class="hidden sm:block">
                    <div x-data="{ show: false }" class="relative">
                        <button @click="show = !show" @click.away="show = false"
                                class="inline-flex items-center gap-1.5 px-2 py-2 rounded-lg text-xs font-medium text-gray-400 hover:text-white hover:bg-gray-800 border border-transparent hover:border-gray-700 transition">
                            <span class="hidden lg:block max-w-[100px] truncate text-gray-300">{{ Auth::user()->name }}</span>
                            @if(auth()->user()->role === 'admin')
                                @can('kelola-admin')
                                    <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-yellow-950 border border-yellow-800/50 text-yellow-400">👑 Super</span>
                                @else
                                    <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-blue-950 border border-blue-800/50 text-blue-400">⚡ Admin</span>
                                @endcan
                            @elseif(auth()->user()->role === 'guru')
                                <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-emerald-950 border border-emerald-800/50 text-emerald-400">👨‍🏫 Guru</span>
                            @elseif(auth()->user()->role === 'santri')
                                <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-indigo-950 border border-indigo-800/50 text-indigo-400">🎓 Santri</span>
                            @endif
                            <svg class="w-3 h-3 text-gray-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="show"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute top-full right-0 mt-1 w-40 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-400 hover:text-red-300 hover:bg-gray-700 transition text-left">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Mobile: Hamburger --}}
                <div class="flex items-center gap-1.5 sm:hidden">
                    <button @click="open = !open"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-white bg-gray-800/60 hover:bg-gray-800 border border-gray-700/50 hover:border-gray-600 transition">
                        <svg class="h-4 w-4" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MOBILE MENU - REDESIGNED                                      --}}
    {{-- ============================================================ --}}
    <div :class="{'block': open, 'hidden': !open}"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="hidden sm:hidden border-t border-gray-800 bg-gray-900">

        {{-- User Header dengan avatar --}}
        <div class="px-4 py-3 bg-gradient-to-br from-gray-800/80 to-gray-900 border-b border-gray-700/50">
            <div class="flex items-center gap-3">
                
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] mt-0.5
                        @if(auth()->user()->role === 'admin') @can('kelola-admin') text-yellow-400 @else text-blue-400 @endcan
                        @elseif(auth()->user()->role === 'guru') text-emerald-400
                        @elseif(auth()->user()->role === 'santri') text-indigo-400
                        @endif">
                        @if(auth()->user()->role === 'admin') @can('kelola-admin') 👑 Super Admin @else ⚡ Admin @endcan
                        @elseif(auth()->user()->role === 'guru') 👨‍🏫 Guru
                        @elseif(auth()->user()->role === 'santri') 🎓 Santri
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-red-950/40 hover:bg-red-900/50 border border-red-900/50 hover:border-red-800/60 text-red-400 hover:text-red-300 text-[11px] font-medium transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>

        {{-- Nav Items dengan icon berwarna --}}
        <div class="py-1.5">

            {{-- Dashboard --}}
            @php
                if(auth()->user()->role === 'santri') { $dashLink = route('santri.dashboard'); $dashActive = request()->routeIs('santri.dashboard'); }
                else { $dashLink = auth()->user()->role === 'admin' ? route('dashboard') : route('guru.dashboard'); $dashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard'); }
            @endphp
            <a href="{{ $dashLink }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors
               {{ $dashActive ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                <div class="w-7 h-7 rounded-lg bg-indigo-950/50 border border-indigo-900/50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                Dashboard
            </a>

            @if(auth()->user()->role === 'admin')
                <div class="px-4 pt-3 pb-1 mt-1 border-t border-gray-800/60">
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Management</p>
                </div>
                @can('kelola-admin')
                <a href="{{ route('admin.admins.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-yellow-950/50 border border-yellow-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    Kelola Admin
                </a>
                @endcan
                @can('kelola-guru')
                <a href="{{ route('admin.gurus.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-blue-950/50 border border-blue-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    Guru
                </a>
                @endcan
                @can('kelola-kelas')
                <a href="{{ route('admin.kelas.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-emerald-950/50 border border-emerald-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    Kelas
                </a>
                @endcan
                @can('kelola-santri')
                <a href="{{ route('admin.santris.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-violet-950/50 border border-violet-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    Santri
                </a>
                @endcan
                @can('kelola-presensi-sesi')
                <a href="{{ route('admin.presensi-sesis.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-purple-950/50 border border-purple-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    Sesi Presensi
                </a>
                @endcan

                <div class="px-4 pt-3 pb-1 mt-1 border-t border-gray-800/60">
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Laporan & Log</p>
                </div>
                @can('lihat-monitoring')
                <a href="{{ route('admin.monitoring.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-orange-950/50 border border-orange-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    Monitoring Santri
                </a>
                @endcan
                @can('lihat-log-activity')
                <a href="{{ route('admin.logs.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent transition-colors">
                    <div class="w-7 h-7 rounded-lg bg-pink-950/50 border border-pink-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    Log Activity
                </a>
                @endcan
            @endif

            @if(auth()->user()->role === 'guru')
                <div class="px-4 pt-3 pb-1 mt-1 border-t border-gray-800/60">
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Input Data</p>
                </div>
                <a href="{{ route('guru.input.create') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors
                   {{ request()->routeIs('guru.input.create') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-indigo-950/50 border border-indigo-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    Input Setoran & Presensi
                </a>

                <div class="px-4 pt-3 pb-1 mt-1 border-t border-gray-800/60">
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Riwayat</p>
                </div>
                <a href="{{ route('guru.setorans.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('guru.setorans.*') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-blue-950/50 border border-blue-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    Riwayat Setoran
                </a>
                <a href="{{ route('guru.presensis.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('guru.presensis.*') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-emerald-950/50 border border-emerald-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    Riwayat Presensi
                </a>

                <div class="px-4 pt-3 pb-1 mt-1 border-t border-gray-800/60">
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Management</p>
                </div>
                <a href="{{ route('guru.santris.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('guru.santris.*') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-violet-950/50 border border-violet-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    Kelola Santri
                </a>
                <a href="{{ route('guru.rapot.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('guru.rapot.*') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-pink-950/50 border border-pink-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    Rapot
                </a>
            @endif

            @if(auth()->user()->role === 'santri')
                <div class="px-4 pt-3 pb-1 mt-1 border-t border-gray-800/60">
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Menu Santri</p>
                </div>
                <a href="{{ route('santri.rapot.show') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('santri.rapot.*') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-pink-950/50 border border-pink-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    Rapot
                </a>
                <a href="{{ route('santri.setorans.detail') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('santri.setorans.detail') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-blue-950/50 border border-blue-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    Detail Setoran
                </a>
                <a href="{{ route('santri.presensis.detail') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors {{ request()->routeIs('santri.presensis.detail') ? 'text-white bg-indigo-600/10 border-l-[3px] border-indigo-500' : 'text-gray-300 hover:text-white hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                    <div class="w-7 h-7 rounded-lg bg-emerald-950/50 border border-emerald-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    Detail Presensi
                </a>
            @endif

            <div class="h-2"></div>
        </div>
    </div>
</nav>
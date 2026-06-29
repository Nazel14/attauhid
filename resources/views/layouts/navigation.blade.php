<nav x-data="{
        open: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        }
     }"
     
class="bg-gray-200 dark:bg-gray-950 border-b border-gray-300 dark:border-gray-800 sticky top-0 z-50">


    @php
        if (auth()->user()->role === 'santri') {
            $dashLink = route('santri.dashboard');
            $dashActive = request()->routeIs('santri.dashboard');
        } else {
            $dashLink = auth()->user()->role === 'admin' ? route('dashboard') : route('guru.dashboard');
            $dashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard');
        }

        // Class util reusable
        $navLinkBase   = 'px-3 py-2 text-xs font-medium rounded-lg transition';
        $navLinkIdle   = 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800';
        $navLinkActive = 'text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
        $ddPanel       = 'absolute top-full left-0 mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl shadow-black/10 dark:shadow-black/40 overflow-hidden z-50';
        $ddItem        = 'flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition';
        $ddIcon        = 'w-3.5 h-3.5 text-gray-400 dark:text-gray-500';
    @endphp

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">
        <div class="flex justify-between h-14">

            {{-- Kiri: Logo + Desktop Nav --}}
            <div class="flex items-center gap-1">
                <div class="shrink-0 flex items-center mr-2">
                    <a href="{{ $dashLink }}"
                        class="flex items-center justify-center w-10 h-10 p-1.5 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/40 rounded-lg hover:border-indigo-400 dark:hover:border-indigo-700/60 transition">
                        <img src="/favicon.ico" alt="Logo" class="w-full h-full" style="object-fit:contain; display:block;">
                    </a>
                </div>

                {{-- Desktop Nav --}}
                <div class="hidden sm:flex sm:items-center sm:gap-0.5">

                    <a href="{{ $dashLink }}" class="{{ $navLinkBase }} {{ $dashActive ? $navLinkActive : $navLinkIdle }}">Dashboard</a>

                    @if(auth()->user()->role === 'admin')
                        {{-- Management dropdown --}}
                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg {{ $navLinkIdle }} transition">
                                Management
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show" x-cloak
                                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="{{ $ddPanel }}">
                                @can('kelola-admin')
                                <a href="{{ route('admin.admins.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Kelola Admin
                                </a>
                                <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                @endcan
                                @can('kelola-guru')
                                <a href="{{ route('admin.gurus.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    Guru
                                </a>
                                @endcan
                                @can('kelola-kelas')
                                <a href="{{ route('admin.kelas.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Kelas
                                </a>
                                @endcan
                                @can('kelola-santri')
                                <a href="{{ route('admin.santris.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Santri
                                </a>
                                @endcan
                                @can('kelola-presensi-sesi')
                                <a href="{{ route('admin.presensi-sesis.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Sesi Presensi
                                </a>
                                @endcan
                            </div>
                        </div>

                        {{-- Laporan dropdown --}}
                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg {{ $navLinkIdle }} transition">
                                Laporan & Log
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show" x-cloak
                                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="{{ $ddPanel }}">
                                @can('lihat-monitoring')
                                <a href="{{ route('admin.monitoring.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    Monitoring Santri
                                </a>
                                @endcan
                                @can('lihat-log-activity')
                                <a href="{{ route('admin.logs.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    Log Activity
                                </a>
                                @endcan
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->role === 'guru')
                        <a href="{{ route('guru.input.create') }}"
                           class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg transition
                                  {{ request()->routeIs('guru.input.create') ? 'text-white bg-indigo-600 border border-indigo-500' : $navLinkIdle }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Input Data
                        </a>

                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg {{ $navLinkIdle }} transition">
                                Riwayat
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show" x-cloak
                                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="{{ $ddPanel }}">
                                <a href="{{ route('guru.setorans.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    Riwayat Setoran
                                </a>
                                <a href="{{ route('guru.presensis.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Riwayat Presensi
                                </a>
                                <a href="{{ route('guru.riwayat-santri') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Riwayat Santri
                                </a>
                            </div>
                        </div>

                        <div x-data="{ show: false }" class="relative">
                            <button @click="show = !show" @click.away="show = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg {{ $navLinkIdle }} transition">
                                Management
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="show" x-cloak
                                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="{{ $ddPanel }}">
                                <a href="{{ route('guru.santris.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Kelola Santri
                                </a>
                                <a href="{{ route('guru.rapot.index') }}" class="{{ $ddItem }}">
                                    <svg class="{{ $ddIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Rapot
                                </a>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->role === 'santri')
                        <a href="{{ route('santri.rapot.show') }}" class="{{ $navLinkBase }} {{ request()->routeIs('santri.rapot.*') ? $navLinkActive : $navLinkIdle }}">Rapot</a>
                        <a href="{{ route('santri.setorans.detail') }}" class="{{ $navLinkBase }} {{ request()->routeIs('santri.setorans.detail') ? $navLinkActive : $navLinkIdle }}">Detail Setoran</a>
                        <a href="{{ route('santri.presensis.detail') }}" class="{{ $navLinkBase }} {{ request()->routeIs('santri.presensis.detail') ? $navLinkActive : $navLinkIdle }}">Detail Presensi</a>
                    @endif
                </div>
            </div>

            {{-- Kanan: User + Hamburger --}}
            <div class="flex items-center gap-2">
                {{-- Desktop user dropdown --}}
                <div class="hidden sm:block">
                    <div x-data="{ show: false }" class="relative">
                       <button @click="show = !show" @click.away="show = false"
                                class="inline-flex items-center gap-1.5 px-2 py-2 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 border border-transparent hover:border-gray-200 dark:hover:border-gray-700 transition">
                            @if(Auth::user()->profile_photo)
                                
                               <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Foto" class="w-7 h-7 rounded-full object-cover object-top border border-gray-300 dark:border-gray-600">
                            @else
                                <span class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-[10px] font-bold text-indigo-600 dark:text-indigo-400">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            @endif
                            <span class="hidden lg:block max-w-[100px] truncate text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>
                            @if(auth()->user()->role === 'admin')
                                @can('kelola-admin')
                                    <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-yellow-100 border border-yellow-300 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-800/50 dark:text-yellow-400">👑 Super</span>
                                @else
                                    <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-blue-100 border border-blue-300 text-blue-800 dark:bg-blue-950 dark:border-blue-800/50 dark:text-blue-400">⚡ Admin</span>
                                @endcan
                            @elseif(auth()->user()->role === 'guru')
                                <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-emerald-100 border border-emerald-300 text-emerald-800 dark:bg-emerald-950 dark:border-emerald-800/50 dark:text-emerald-400">👨‍🏫 Guru</span>
                            @elseif(auth()->user()->role === 'santri')
                                <span class="hidden lg:inline px-1.5 py-0.5 text-[9px] font-semibold rounded-md bg-indigo-100 border border-indigo-300 text-indigo-800 dark:bg-indigo-950 dark:border-indigo-800/50 dark:text-indigo-400">🎓 Santri</span>
                            @endif
                            <svg class="w-3 h-3 text-gray-400 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="show" x-cloak
                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="absolute top-full right-0 mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl shadow-black/10 dark:shadow-black/40 overflow-hidden z-50">
                            {{-- Toggle Tema Desktop --}}
                            <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Mode Tema</span>
                                <button type="button"
                                    @click.stop="toggleTheme()"
                                    :class="dark ? 'bg-indigo-600' : 'bg-amber-400'"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none">
                                    <span :class="dark ? 'translate-x-5' : 'translate-x-0'"
                                          class="pointer-events-none relative inline-flex h-5 w-5 transform items-center justify-center rounded-full bg-white shadow-md ring-0 transition-transform duration-300 ease-in-out">
                                        <svg class="absolute w-3.5 h-3.5 text-amber-500 transition-all duration-300 ease-in-out"
                                             :class="dark ? 'opacity-0 -rotate-90 scale-0' : 'opacity-100 rotate-0 scale-100'"
                                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="4"/>
                                            <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                                        </svg>
                                        <svg class="absolute w-3.5 h-3.5 text-indigo-600 transition-all duration-300 ease-in-out"
                                             :class="dark ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 rotate-90 scale-0'"
                                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                           <a href="{{ route('profile.edit') }}" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Profil Saya
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition text-left">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Mobile: Toggle Tema + Hamburger --}}
                <div class="flex items-center gap-1.5 sm:hidden">
                    {{-- Toggle Tema Mobile --}}
                    <button type="button"
                        @click.stop="toggleTheme()"
                        :class="dark ? 'bg-indigo-600' : 'bg-amber-400'"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none">
                        <span :class="dark ? 'translate-x-5' : 'translate-x-0'"
                              class="pointer-events-none relative inline-flex h-5 w-5 transform items-center justify-center rounded-full bg-white shadow-md ring-0 transition-transform duration-300 ease-in-out">
                            <svg class="absolute w-3.5 h-3.5 text-amber-500 transition-all duration-300 ease-in-out"
                                 :class="dark ? 'opacity-0 -rotate-90 scale-0' : 'opacity-100 rotate-0 scale-100'"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="4"/>
                                <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                            </svg>
                            <svg class="absolute w-3.5 h-3.5 text-indigo-600 transition-all duration-300 ease-in-out"
                                 :class="dark ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 rotate-90 scale-0'"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                            </svg>
                        </span>
                    </button>

                    {{-- Hamburger --}}
                    <button @click="open = !open"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white bg-gray-100 dark:bg-gray-800/60 hover:bg-gray-200 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700/50 hover:border-gray-300 dark:hover:border-gray-600 transition">
                        <svg class="h-4 w-4" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ MOBILE MENU ============================ --}}
    @php
        $mItemIdle   = 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/50 border-l-[3px] border-transparent';
        $mItemActive = 'text-indigo-700 dark:text-white bg-indigo-50 dark:bg-indigo-600/10 border-l-[3px] border-indigo-500';
        $mItemBase   = 'flex items-center gap-3 px-4 py-2.5 text-xs font-medium transition-colors';
        $mLabel      = 'text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider';
        $mSection    = 'px-4 pt-3 pb-1 mt-1 border-t border-gray-200 dark:border-gray-800/60';
    @endphp
    <div :class="{'block': open, 'hidden': !open}"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="hidden sm:hidden border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

        {{-- User Header --}}
        <div class="px-4 py-3 bg-gray-50 dark:bg-gradient-to-br dark:from-gray-800/80 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
              @if(Auth::user()->profile_photo)
                    <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover object-top border-2 border-gray-300 dark:border-gray-600 flex-shrink-0">
                @else
                    <span class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-950 border-2 border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-sm font-bold text-indigo-600 dark:text-indigo-400 flex-shrink-0">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] mt-0.5
                        @if(auth()->user()->role === 'admin') @can('kelola-admin') text-yellow-600 dark:text-yellow-400 @else text-blue-600 dark:text-blue-400 @endcan
                        @elseif(auth()->user()->role === 'guru') text-emerald-600 dark:text-emerald-400
                        @elseif(auth()->user()->role === 'santri') text-indigo-600 dark:text-indigo-400
                        @endif">
                        @if(auth()->user()->role === 'admin') @can('kelola-admin') 👑 Super Admin @else ⚡ Admin @endcan
                        @elseif(auth()->user()->role === 'guru') 👨‍🏫 Guru
                        @elseif(auth()->user()->role === 'santri') 🎓 Santri
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/50 border border-red-200 dark:border-red-900/50 hover:border-red-300 dark:hover:border-red-800/60 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-[11px] font-medium transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>

        <div class="py-1.5">
            {{-- Dashboard --}}
            <a href="{{ $dashLink }}" class="{{ $mItemBase }} {{ $dashActive ? $mItemActive : $mItemIdle }}">
                <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-900/50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                Dashboard
            </a>

            {{-- Profil Saya --}}
            <a href="{{ route('profile.edit') }}" class="{{ $mItemBase }} {{ request()->routeIs('profile.edit') ? $mItemActive : $mItemIdle }}">
                <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                Profil Saya
            </a>

            @if(auth()->user()->role === 'admin')
                <div class="{{ $mSection }}"><p class="{{ $mLabel }}">Management</p></div>
                @can('kelola-admin')
                <a href="{{ route('admin.admins.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-yellow-100 dark:bg-yellow-950/50 border border-yellow-200 dark:border-yellow-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
                    Kelola Admin
                </a>
                @endcan
                @can('kelola-guru')
                <a href="{{ route('admin.gurus.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                    Guru
                </a>
                @endcan
                @can('kelola-kelas')
                <a href="{{ route('admin.kelas.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                    Kelas
                </a>
                @endcan
                @can('kelola-santri')
                <a href="{{ route('admin.santris.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
                    Santri
                </a>
                @endcan
                @can('kelola-presensi-sesi')
                <a href="{{ route('admin.presensi-sesis.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    Sesi Presensi
                </a>
                @endcan

                <div class="{{ $mSection }}"><p class="{{ $mLabel }}">Laporan & Log</p></div>
                @can('lihat-monitoring')
                <a href="{{ route('admin.monitoring.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-orange-100 dark:bg-orange-950/50 border border-orange-200 dark:border-orange-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                    Monitoring Santri
                </a>
                @endcan
                @can('lihat-log-activity')
                <a href="{{ route('admin.logs.index') }}" class="{{ $mItemBase }} {{ $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-pink-100 dark:bg-pink-950/50 border border-pink-200 dark:border-pink-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                    Log Activity
                </a>
                @endcan
            @endif

            @if(auth()->user()->role === 'guru')
                <div class="{{ $mSection }}"><p class="{{ $mLabel }}">Input Data</p></div>
                <a href="{{ route('guru.input.create') }}" class="{{ $mItemBase }} {{ request()->routeIs('guru.input.create') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></div>
                    Input Setoran & Presensi
                </a>

                <div class="{{ $mSection }}"><p class="{{ $mLabel }}">Riwayat</p></div>
                <a href="{{ route('guru.setorans.index') }}" class="{{ $mItemBase }} {{ request()->routeIs('guru.setorans.*') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                    Riwayat Setoran
                </a>
                <a href="{{ route('guru.presensis.index') }}" class="{{ $mItemBase }} {{ request()->routeIs('guru.presensis.*') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
                    Riwayat Presensi
                </a>
                <a href="{{ route('guru.riwayat-santri') }}" class="{{ $mItemBase }} {{ request()->routeIs('guru.riwayat-santri*') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                    Riwayat Santri
                </a>

                <div class="{{ $mSection }}"><p class="{{ $mLabel }}">Management</p></div>
                <a href="{{ route('guru.santris.index') }}" class="{{ $mItemBase }} {{ request()->routeIs('guru.santris.*') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
                    Kelola Santri
                </a>
                <a href="{{ route('guru.rapot.index') }}" class="{{ $mItemBase }} {{ request()->routeIs('guru.rapot.*') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-pink-100 dark:bg-pink-950/50 border border-pink-200 dark:border-pink-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    Rapot
                </a>
            @endif

            @if(auth()->user()->role === 'santri')
                <div class="{{ $mSection }}"><p class="{{ $mLabel }}">Menu Santri</p></div>
                <a href="{{ route('santri.rapot.show') }}" class="{{ $mItemBase }} {{ request()->routeIs('santri.rapot.*') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-pink-100 dark:bg-pink-950/50 border border-pink-200 dark:border-pink-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    Rapot
                </a>
                <a href="{{ route('santri.setorans.detail') }}" class="{{ $mItemBase }} {{ request()->routeIs('santri.setorans.detail') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                    Detail Setoran
                </a>
                <a href="{{ route('santri.presensis.detail') }}" class="{{ $mItemBase }} {{ request()->routeIs('santri.presensis.detail') ? $mItemActive : $mItemIdle }}">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900/40 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
                    Detail Presensi
                </a>
            @endif

            <div class="h-2"></div>
        </div>
    </div>
</nav>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Favicon --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}?v=2">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme') || 'dark'; // default dark
                    document.documentElement.classList.toggle('dark', t === 'dark');
                } catch (e) { document.documentElement.classList.add('dark'); }
        })();
        </script>

        {{-- PASTIKAN @vite SUDAH BENAR UNTUK MEMANGGIL CSS & JS --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="w-full">
            @yield('content')
        </main>
    </div>

    @livewireScripts
    @stack('scripts') 
</body>
</html>
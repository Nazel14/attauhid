<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laporan Tahfizh') }}</title>

        {{-- Favicon --}}
        <link rel="icon" href="{{ asset('logo.svg') }}?v=3" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=3" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('logo.svg') }}?v=3">

        {{-- Anti-kedip tema gelap: terapkan SEBELUM React load --}}
        <script>
            try {
                if (localStorage.getItem('theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        {{-- Fonts (opsional) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        {{-- Ziggy --}}
        @routes

                {{-- PWA --}}
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#d97757">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Tahfizh">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        {{-- React + Inertia entry --}}
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-cream-50 dark:bg-coal-800">
        @inertia
                <script>
            // Service worker hanya jalan di HTTPS (localhost dikecualikan).
            // Di http:// biasa, navigator.serviceWorker tidak ada sama sekali,
            // jadi pengecekan ini juga mencegah error di konsol.
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {
                        // Gagal daftar bukan masalah — situs tetap jalan normal.
                    });
                });
            }
        </script>
    </body>
</html>
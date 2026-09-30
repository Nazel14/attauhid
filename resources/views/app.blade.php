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

        {{-- Pratinjau saat link dibagikan (WhatsApp, Telegram, Facebook, LinkedIn).
             Dipakai juga oleh Google sebagai deskripsi di hasil pencarian.

             URL gambar WAJIB absolut — WhatsApp mengambilnya dari servernya
             sendiri, jadi path relatif seperti /og-image.png tidak akan ketemu. --}}
        <meta name="description" content="Pantau hafalan, presensi, dan keuangan santri dalam satu aplikasi. Untuk pesantren tahfizh.">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Laporan Tahfizh">
        <meta property="og:title" content="Sistem Manajemen Tahfizh Pesantren">
        <meta property="og:description" content="Pantau hafalan, presensi, dan keuangan santri dalam satu aplikasi.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('og-image.jpg') }}?v=2">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="id_ID">

        <meta name="twitter:card" content="summary_large_image">

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
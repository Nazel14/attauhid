<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TK Attauhid') }}</title>

        {{-- Favicon — HARUS sama persis dengan yang di resources/views/app.blade.php.

             Kalau dua berkas tata letak menunjuk ikon yang berbeda, ikon di tab
             akan berganti-ganti tergantung halaman mana yang dibuka lebih dulu,
             dan cache favicon membuatnya terlihat acak.

             rel="shortcut icon" yang dulu ada di sini sudah DIBUANG: atributnya
             usang sejak lama dan hanya menambah satu deklarasi lagi yang bersaing
             dengan dua baris di bawah.

             Angka ?v= dinaikkan bersamaan dengan app.blade.php, jangan sendiri. --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=6" sizes="32x32">
        <link rel="icon" href="{{ asset('logo.svg') }}?v=6" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('icons/ikon-180.png') }}?v=6">

        {{-- Anti-kedip tema gelap.

             Sebelumnya baris ini berbunyi (localStorage.getItem('theme') || 'dark'),
             artinya pengunjung baru — yang belum punya nilai apa pun di localStorage —
             mendapat tema GELAP di sini, padahal app.blade.php memberi tema TERANG.
             Akibatnya tema berubah sendiri saat berpindah halaman. Sekarang keduanya
             sama: gelap hanya kalau memang pernah dipilih. --}}
        <script>
            try {
                if (localStorage.getItem('theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#7EB842">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- Latar disamakan dengan palet At-Tauhid, bukan abu-abu bawaan Tailwind. --}}
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[#F6F7F2] dark:bg-[#282C25]">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white shadow dark:bg-[#323730]">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
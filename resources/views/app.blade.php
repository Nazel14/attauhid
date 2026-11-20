<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'TK Attauhid') }}</title>

        {{-- Favicon.

             Urutan disengaja: .ico lebih dulu, SVG sesudahnya. Peramban
             modern memakai SVG, yang lama jatuh ke .ico. Kalau SVG ditaruh
             duluan, sebagian peramban justru memilih .ico dan logo lama
             yang muncul.

             logo.svg KANVASNYA HARUS PERSEGI. Peramban menjejalkan SVG ke
             kotak favicon yang persegi, dan sebagian melakukannya dengan
             meregangkan gambar — bukan memberi ruang kosong. Versi 420x420
             sudah memperhitungkan itu; jangan dikembalikan ke kanvas yang
             lebih tinggi daripada lebar.

             apple-touch-icon HARUS PNG — iOS tidak membaca SVG di sini,
             dan kalau dibiarkan SVG ikonnya kosong saat disimpan ke layar
             utama iPhone.

             Angka ?v= dinaikkan setiap kali gambarnya diganti. Peramban
             menyimpan favicon di cache tersendiri yang tidak terhapus oleh
             Ctrl+Shift+R; hanya URL berbeda yang memaksa ambil ulang. --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=6" sizes="32x32">
        <link rel="icon" href="{{ asset('logo.svg') }}?v=6" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('icons/ikon-180.png') }}?v=6">

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

        {{-- PWA.

             Hanya ADA SATU apple-touch-icon, yaitu yang di blok favicon di
             atas. Sebelumnya ada deklarasi kedua di sini yang menunjuk ikon
             lama, dan dua deklarasi sejenis saling membantah — iOS memakai
             salah satunya tanpa bisa ditebak.

             Nama aplikasi dan daftar ikon saat dipasang ke layar HP dibaca
             dari manifest.json, BUKAN dari tag di sini. Kalau ikon terpasang
             masih yang lama, periksa manifest-nya. --}}
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#7EB842">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="TK Attauhid">

        {{-- Pratinjau saat link dibagikan (WhatsApp, Telegram, Facebook, LinkedIn).
             Dipakai juga oleh Google sebagai deskripsi di hasil pencarian.

             URL gambar WAJIB absolut — WhatsApp mengambilnya dari servernya
             sendiri, jadi path relatif seperti /og-image.png tidak akan ketemu.

             Seluruh teks di bawah hanya memuat keterangan yang tertulis di
             Profil TK Attauhid. Jangan menambah klaim yang tidak ada di sana:
             yang membacanya orang tua calon murid. --}}
        <meta name="description" content="TK Attauhid — lembaga pendidikan anak usia dini di Lembang, Bandung Barat, di bawah Yayasan Islam Lembang At-Tauhid.">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="TK Attauhid">
        <meta property="og:title" content="TK Attauhid — Mendidik dengan Tauhid">
        <meta property="og:description" content="Lembaga pendidikan anak usia dini di Lembang, Bandung Barat.">
        <meta property="og:url" content="{{ url()->current() }}">
        {{-- ?v= WAJIB dinaikkan tiap kali gambarnya diganti. WhatsApp menyimpan
             pratinjau di servernya sendiri dan tidak akan mengambil ulang kalau
             URL-nya sama persis. --}}
        <meta property="og:image" content="{{ asset('og-image.jpg') }}?v=4">
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
    {{-- Latar disamakan dengan palet At-Tauhid supaya tidak ada kedipan warna
         lama sepersekian detik sebelum React selesai memasang halamannya. --}}
    <body class="font-sans antialiased bg-[#F6F7F2] dark:bg-[#282C25]">
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
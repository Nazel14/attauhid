<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name', 'Laporan Tahfizh') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    {{-- Halaman error sengaja berdiri sendiri: tanpa Vite, tanpa font
         eksternal, tanpa JavaScript aplikasi, tanpa query database. Error 500
         sering terjadi justru saat bagian lain situs sedang rusak, jadi
         halaman ini tidak boleh ikut bergantung pada bagian yang bisa rusak.
         Karena itu nama aplikasi diambil dari config, bukan tabel settings. --}}
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    @php
        $kode = trim($__env->yieldContent('code'));

        // Ikon & nada warna per kode. Garis luar 24×24, gaya yang sama
        // dengan ikon di aplikasi.
        $ikon = [
            '403' => ['amber', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
            '404' => ['hijau', 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v3m0 3h.008'],
            '419' => ['biru',  'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            '429' => ['amber', 'M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            '500' => ['merah', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
            '503' => ['hijau', 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437'],
        ][$kode] ?? ['hijau', 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'];

        // Logo lembaga bila ada; tanpa itu dipakai ubin ikon buku.
        $logo = null;
        foreach (['Logo.png', 'logo.png'] as $berkas) {
            if (file_exists(public_path($berkas))) { $logo = asset($berkas); break; }
        }

        // Tautan bantuan hanya untuk error yang memang perlu campur tangan
        // admin. 404/419/429 cukup diselesaikan pengguna sendiri.
        $wa = config('modules.wa_admin');
        $perluBantuan = $wa && in_array($kode, ['403', '500', '503'], true);
        $teksWa = rawurlencode("Assalamu'alaikum, saya mendapat pesan error {$kode} saat membuka " . request()->fullUrl());
    @endphp

    <style>
        :root {
            --latar: #F6F7F2; --kartu: #FFFFFF; --garis: #E1E4D8;
            --teks: #1B1D19; --redup: #6B7066; --samar: #9CA196;
            --aksen: #649634; --aksen-hover: #4D752A; --aksen-lembut: #F4F9EC;
            --bayang: 0 1px 2px rgba(27,29,25,.04), 0 24px 48px -12px rgba(61,92,36,.14);
            --gelembung-a: rgba(126,184,66,.20); --gelembung-b: rgba(176,215,130,.25);
        }
        html.dark {
            --latar: #1B1D19; --kartu: #24271F; --garis: #3A3F35;
            --teks: #F6F7F2; --redup: #A3A89C; --samar: #6F7569;
            --aksen: #7EB842; --aksen-hover: #96C85D; --aksen-lembut: rgba(126,184,66,.12);
            --bayang: 0 1px 2px rgba(0,0,0,.3), 0 24px 48px -12px rgba(0,0,0,.5);
            --gelembung-a: rgba(126,184,66,.12); --gelembung-b: rgba(100,150,52,.10);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh; min-height: 100dvh;
            display: grid; place-items: center; padding: 24px;
            color: var(--teks);
            background:
                radial-gradient(42rem 42rem at 12% -10%, var(--gelembung-a), transparent 60%),
                radial-gradient(36rem 36rem at 110% 110%, var(--gelembung-b), transparent 60%),
                var(--latar);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .kartu {
            position: relative; overflow: hidden;
            width: 100%; max-width: 460px; text-align: center;
            background: var(--kartu); border: 1px solid var(--garis);
            border-radius: 28px; padding: 36px 32px 28px;
            box-shadow: var(--bayang);
        }
        /* Garis aksen tipis di atas kartu — sama dengan dokumen PDF. */
        .kartu::before {
            content: ""; position: absolute; inset: 0 0 auto 0; height: 4px;
            background: linear-gradient(90deg, #96C85D, #649634);
        }

        .merek { display: inline-flex; align-items: center; gap: 10px; font-weight: 700; font-size: 13px; color: var(--redup); letter-spacing: .01em; }
        .merek img { width: 28px; height: 28px; object-fit: contain; }
        .ubin-merek { width: 28px; height: 28px; border-radius: 9px; background: var(--aksen); display: grid; place-items: center; }

        .ikon {
            margin: 30px auto 0; width: 72px; height: 72px; border-radius: 22px;
            display: grid; place-items: center;
        }
        .ikon svg { width: 34px; height: 34px; }
        .nada-hijau { background: #E6F2D5; color: #4D752A; box-shadow: 0 0 0 8px rgba(126,184,66,.10); }
        .nada-amber { background: #FEF3C7; color: #B45309; box-shadow: 0 0 0 8px rgba(245,158,11,.10); }
        .nada-biru  { background: #E0ECFF; color: #1D4ED8; box-shadow: 0 0 0 8px rgba(59,130,246,.10); }
        .nada-merah { background: #FEE2E2; color: #B91C1C; box-shadow: 0 0 0 8px rgba(239,68,68,.10); }
        html.dark .nada-hijau { background: rgba(126,184,66,.16); color: #B0D782; }
        html.dark .nada-amber { background: rgba(245,158,11,.16); color: #FCD34D; }
        html.dark .nada-biru  { background: rgba(59,130,246,.16); color: #93C5FD; }
        html.dark .nada-merah { background: rgba(239,68,68,.16); color: #FCA5A5; }

        .kode {
            display: inline-block; margin-top: 22px;
            padding: 4px 12px; border-radius: 999px;
            background: var(--aksen-lembut); color: var(--aksen);
            font-size: 12px; font-weight: 700; letter-spacing: .12em;
        }
        h1 { margin-top: 12px; font-size: 26px; font-weight: 800; letter-spacing: -.02em; line-height: 1.2; }
        p.pesan { margin: 10px auto 0; max-width: 340px; font-size: 15px; line-height: 1.65; color: var(--redup); }

        .aksi { margin-top: 28px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .tombol {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-width: 140px; padding: 12px 20px; border-radius: 14px;
            font-size: 14px; font-weight: 700; line-height: 1.2;
            text-decoration: none; cursor: pointer; border: 1px solid transparent;
            font-family: inherit; /* <button> tidak mewarisi font secara bawaan */
            transition: background .15s, border-color .15s, transform .1s;
        }
        .tombol:active { transform: scale(.97); }
        .tombol:focus-visible { outline: 3px solid rgba(126,184,66,.45); outline-offset: 2px; }
        .utama { background: var(--aksen); color: #fff; box-shadow: 0 8px 20px -8px rgba(100,150,52,.7); }
        .utama:hover { background: var(--aksen-hover); }
        .kedua { background: transparent; color: var(--teks); border-color: var(--garis); }
        .kedua:hover { border-color: var(--samar); }

        .kaki {
            margin-top: 26px; padding-top: 18px; border-top: 1px solid var(--garis);
            font-size: 12.5px; color: var(--samar);
        }
        .kaki a { color: var(--aksen); font-weight: 700; text-decoration: none; }
        .kaki a:hover { text-decoration: underline; }

        @media (max-width: 420px) {
            body { padding: 16px; }
            .kartu { padding: 30px 20px 22px; border-radius: 24px; }
            h1 { font-size: 23px; }
            .aksi { flex-direction: column; }
            .tombol { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .tombol { transition: none; }
        }
    </style>
</head>
<body>
    <main class="kartu" role="main">
        <div class="merek">
            @if($logo)
                <img src="{{ $logo }}" alt="">
            @else
                <span class="ubin-merek">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5.5C4 4.5 7 4.3 12 6c5-1.7 8-1.5 10-.5v13c-2-1-5-1.2-10 .5-5-1.7-8-1.5-10-.5z"/><path d="M12 6v13.5"/></svg>
                </span>
            @endif
            {{ config('app.name', 'Laporan Tahfizh') }}
        </div>

        <div class="ikon nada-{{ $ikon[0] }}" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $ikon[1] }}"/></svg>
        </div>

        <span class="kode">KODE {{ $kode }}</span>
        <h1>@yield('title')</h1>
        <p class="pesan">@yield('message')</p>

        <div class="aksi">
            @hasSection('action')
                @yield('action')
            @else
                <a href="{{ url('/') }}" class="tombol utama">Ke halaman utama</a>
                <button type="button" onclick="history.back()" class="tombol kedua">Kembali</button>
            @endif
        </div>

        @if($perluBantuan)
            <p class="kaki">
                Masih bermasalah?
                <a href="https://wa.me/{{ $wa }}?text={{ $teksWa }}" target="_blank" rel="noopener">Hubungi admin lewat WhatsApp</a>
            </p>
        @endif
    </main>
</body>
</html>
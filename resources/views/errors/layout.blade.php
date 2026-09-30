<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Laporan Tahfizh</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    {{-- Halaman error sengaja berdiri sendiri: tanpa Vite, tanpa font
         eksternal, tanpa JavaScript aplikasi. Error 500 sering terjadi
         justru saat bagian lain situs sedang rusak, jadi halaman ini
         tidak boleh ikut bergantung pada bagian yang bisa rusak. --}}
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    <style>
        :root {
            --latar: #f6f2ea; --kartu: #ffffff; --garis: #e7e0d4;
            --teks: #1c1917; --redup: #78716c; --samar: #d6cfc4;
            --aksen: #c96442; --aksen-hover: #b5573a;
        }
        html.dark {
            --latar: #26221f; --kartu: #2f2a26; --garis: #3d3731;
            --teks: #faf6ee; --redup: #a8a096; --samar: #4a433c;
            --aksen: #d97757; --aksen-hover: #e8876a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: var(--latar); color: var(--teks);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .kartu {
            width: 100%; max-width: 440px; text-align: center;
            background: var(--kartu); border: 1px solid var(--garis);
            border-radius: 20px; padding: 44px 32px 36px;
            box-shadow: 0 1px 2px rgba(41,37,36,.04), 0 16px 40px rgba(41,37,36,.06);
        }
        .merek { display: inline-flex; align-items: center; gap: 10px; font-weight: 700; font-size: 14px; color: var(--redup); }
        .ubin { width: 30px; height: 30px; border-radius: 9px; background: var(--aksen); display: grid; place-items: center; }
        .kode { margin-top: 28px; font-size: 64px; font-weight: 800; letter-spacing: -.04em; line-height: 1; color: var(--samar); }
        h1 { margin-top: 14px; font-size: 22px; font-weight: 700; letter-spacing: -.01em; }
        p { margin-top: 10px; font-size: 15px; line-height: 1.6; color: var(--redup); }
        .aksi { margin-top: 28px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .tombol {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 11px 20px; border-radius: 12px; font-size: 14px; font-weight: 600;
            text-decoration: none; cursor: pointer; border: 1px solid transparent;
            font-family: inherit; line-height: 1.2; /* <button> tidak mewarisi font secara bawaan */
            transition: background .15s, border-color .15s;
        }
        .utama { background: var(--aksen); color: #fff; }
        .utama:hover { background: var(--aksen-hover); }
        .kedua { background: transparent; color: var(--teks); border-color: var(--garis); }
        .kedua:hover { border-color: var(--redup); }
    </style>
</head>
<body>
    <main class="kartu">
        <div class="merek">
            <span class="ubin">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5.5C4 4.5 7 4.3 12 6c5-1.7 8-1.5 10-.5v13c-2-1-5-1.2-10 .5-5-1.7-8-1.5-10-.5z"/><path d="M12 6v13.5"/></svg>
            </span>
            Laporan Tahfizh
        </div>

        <div class="kode">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>

        <div class="aksi">
            @hasSection('action')
                @yield('action')
            @else
                <a href="{{ url('/') }}" class="tombol utama">Ke halaman utama</a>
                <button type="button" onclick="history.back()" class="tombol kedua">Kembali</button>
            @endif
        </div>
    </main>
</body>
</html>

<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ REGISTER MIDDLEWARE ALIASES
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'kelas.aktif' => \App\Http\Middleware\SetKelasAktif::class,
        ]);

        // ✅ DAFTARKAN INERTIA KE GRUP WEB (ini yang bikin "auth" terkirim ke React)
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        /**
         * ============================================================
         * 419 PAGE EXPIRED — TokenMismatchException
         * ============================================================
         *
         * Ada DUA sebab berbeda yang sama-sama muncul sebagai 419:
         *
         * 1. PAYLOAD MELEBIHI post_max_size.
         *    Saat body request lebih besar dari post_max_size, PHP MEMBUANG
         *    seluruh $_POST — termasuk field _token. Laravel melihat token
         *    hilang lalu melempar TokenMismatchException, padahal sesinya
         *    baik-baik saja. Ini yang terjadi di /guru/input/store-combined
         *    ketika rekaman audio + pdf_html_content menumpuk.
         *    Ciri khas: $_POST kosong TAPI Content-Length besar.
         *
         * 2. SESI BENAR-BENAR KEDALUWARSA / token basi.
         *    Ini kasus logout dan "kadang-kadang" itu.
         *
         * Keduanya diarahkan ke tujuan yang berbeda supaya pesannya jujur.
         */
        $exceptions->render(function (TokenMismatchException $e, Request $request) {

            $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
            $payloadHilang = $request->isMethod('POST')
                && empty($_POST)
                && $contentLength > 0;

            // --- Sebab 1: data terlalu besar ---
            if ($payloadHilang) {
                $ukuranMb   = round($contentLength / 1048576, 1);
                $batasIni   = ini_get('post_max_size');
                $pesan      = "Data terlalu besar untuk dikirim ({$ukuranMb} MB, batas server {$batasIni}). "
                            . "Rekaman audio/PDF koreksi melebihi kapasitas. Coba rekam lebih pendek, "
                            . "atau naikkan post_max_size di php.ini.";

                if ($request->expectsJson()) {
                    return response()->json(['message' => $pesan], 413);
                }

                return back()->with('error', $pesan);
            }

            if ($request->user()) {
                $pesan = 'Halaman sudah kedaluwarsa. Silakan kirim ulang.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $pesan], 419);
                }

                $request->session()->flash('error', $pesan);

                return $request->header('X-Inertia')
                    ? Inertia::location(url()->previous())
                    : back();
            }

            // --- Sebab 2b: sesi benar-benar kedaluwarsa ---
            $pesan = 'Sesi Anda telah berakhir. Silakan login kembali.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 419);
            }

            // Request Inertia WAJIB dijawab 409 + X-Inertia-Location.
            // Redirect 302 biasa akan dianggap response tidak valid oleh klien
            // dan memunculkan modal "419 PAGE EXPIRED".
            if ($request->header('X-Inertia')) {
                $request->session()->flash('error', $pesan);
                return Inertia::location(route('login'));
            }

            return redirect()->route('login')->with('error', $pesan);
        });

        /**
         * ============================================================
         * 401 — Belum/sudah tidak login
         * ============================================================
         * Request yang masih "in-flight" saat logout akan mendarat di sini.
         * Untuk Inertia harus full page visit, bukan redirect XHR.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Belum terautentikasi.'], 401);
            }

            if ($request->header('X-Inertia')) {
                return Inertia::location(route('login'));
            }

            return redirect()->guest(route('login'));
        });

        /**
         * ============================================================
         * 403 — Akses ditolak
         * ============================================================
         * Tiga keadaan yang berbeda, dan ketiganya pernah tampil sebagai
         * hal yang sama:
         *
         * 1. SUDAH TIDAK LOGIN (efek samping logout). Bukan pelanggaran
         *    izin — arahkan ke login, jangan tuduh orang tanpa akses.
         *
         * 2. MASIH LOGIN, PERMINTAAN INERTIA. Inertia tidak bisa
         *    menampilkan halaman Blade: isinya dipampang mentah sebagai
         *    lapisan putih di atas halaman yang sedang dibuka, sementara
         *    halaman aslinya masih terlihat di belakangnya. Kembalikan ke
         *    halaman asal dengan pesan — toast yang menyampaikannya.
         *
         * 3. MASIH LOGIN, KUNJUNGAN LANGSUNG (URL diketik, tab baru).
         *    Di sini halaman 403 memang tempatnya. Biarkan apa adanya.
         */
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            // --- Keadaan 1 ---
            if (! $request->user()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi berakhir.'], 401);
                }

                if ($request->header('X-Inertia')) {
                    return Inertia::location(route('login'));
                }

                return redirect()->route('login');
            }

            // abort(403, 'pesan') menyimpan alasannya di sini. Kosong bila
            // dipanggil tanpa pesan — mis. gerbang permission bawaan.
            $pesan = $e->getMessage() ?: 'Anda tidak memiliki akses untuk tindakan ini.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 403);
            }

            // --- Keadaan 2 ---
            // 303, bukan 302: Inertia mewajibkannya setelah POST/PUT/DELETE
            // supaya browser mengikuti redirect dengan GET, bukan mengulang
            // metode aslinya ke alamat tujuan.
            if ($request->header('X-Inertia')) {
                $request->session()->flash('error', $pesan);

                return back(303);
            }

            // --- Keadaan 3 ---
            return null;
        });

    })->create();
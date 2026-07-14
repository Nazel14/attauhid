<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

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

            // --- Sebab 2: sesi kedaluwarsa ---
            $pesan = 'Sesi Anda telah berakhir. Silakan login kembali.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 419);
            }

            // Request Inertia: kirim redirect 409 agar klien memuat ulang ke login
            if ($request->header('X-Inertia')) {
                return redirect()->guest(route('login'))->with('error', $pesan);
            }

            return redirect()->route('login')->with('error', $pesan);
        });

    })->create();
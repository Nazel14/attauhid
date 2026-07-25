<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Pipa = OR, mengikuti sintaks PermissionMiddleware bawaan Spatie.
        // Modul keuangan memakainya di beberapa route, mis.
        // 'permission:tunjangan.lihat|potongan.lihat'.
        foreach (explode('|', $permission) as $izin) {
            $izin = trim($izin);

            // Pakai can(), bukan hasPermissionTo() — can() mengembalikan
            // false untuk permission yang tidak terdaftar, sedangkan
            // hasPermissionTo() melempar PermissionDoesNotExist sehingga
            // salah ketik di web.php jadi error 500, bukan 403.
            if ($izin !== '' && $user->can($izin)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses untuk fitur ini.');
    }
}
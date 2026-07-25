<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        foreach ($roles as $role) {
            // 1. Spatie role — sumber kebenaran utama.
            //    Modul keuangan hanya punya jalur ini (bendahara, hrd, dst.)
            //    karena role-nya tidak ada padanannya di kolom users.role.
            if ($user->hasRole($role)) {
                return $next($request);
            }

            // 2. Fallback kolom users.role — dipertahankan agar user lama
            //    yang belum ter-assign Spatie role tetap bisa masuk.
            if ($user->role === $role) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki izin yang diperlukan.');
    }
}
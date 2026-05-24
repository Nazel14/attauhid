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

        // ✅ CEK APAKAH USER PUNYA SALAH SATU ROLE YANG DIMINTA
        foreach ($roles as $role) {
            // Cek native role (users.role column)
            if ($user->role === $role) {
                return $next($request);
            }
            
            // ✅ BACKWARD COMPATIBILITY: 
            // Jika route minta 'super-admin', cek permission 'kelola-admin'
            if ($role === 'super-admin' && $user->hasPermissionTo('kelola-admin')) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki izin yang diperlukan.');
    }
}
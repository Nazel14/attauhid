<?php

namespace App\Http\Middleware;

use App\Models\Kelas;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetKelasAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'guru') {
            return $next($request);
        }

        // Halaman pilih kelas tidak boleh dicegat → infinite redirect
        if ($request->routeIs('guru.pilih-kelas') || $request->routeIs('guru.pilih-kelas.set')) {
            return $next($request);
        }

        $guru = $user->guru;
        if (! $guru) {
            abort(403, 'Akun Anda belum terhubung ke data guru. Hubungi admin.');
        }

        $kelasIds = Kelas::where('guru_id', $guru->id)->orderBy('name')->pluck('id');

        if ($kelasIds->isEmpty()) {
            abort(403, 'Anda belum ditugaskan ke kelas mana pun. Hubungi admin.');
        }

        $aktif = session('kelas_aktif_id');

        // JANGAN percaya session mentah — kelas bisa saja sudah dicabut admin
        if ($aktif && ! $kelasIds->contains((int) $aktif)) {
            session()->forget('kelas_aktif_id');
            $aktif = null;
        }

        if (! $aktif) {
            if ($kelasIds->count() === 1) {
                session(['kelas_aktif_id' => $kelasIds->first()]);
            } else {
                return redirect()->route('guru.pilih-kelas');
            }
        }

        return $next($request);
    }
}
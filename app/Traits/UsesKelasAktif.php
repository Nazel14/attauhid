<?php

namespace App\Traits;

use App\Models\Guru;
use App\Models\Kelas;
use Illuminate\Support\Facades\Auth;

trait UsesKelasAktif
{
    protected function guruAktif(): ?Guru
    {
        return Guru::where('user_id', Auth::id())->first();
    }

    protected function kelasAktifId(): ?int
    {
        $id = session('kelas_aktif_id');
        return $id ? (int) $id : null;
    }

    /**
     * Kelas yang sedang aktif DAN benar-benar milik guru yang login.
     * Middleware SetKelasAktif sudah memvalidasi, ini lapis kedua.
     */
    protected function kelasAktif(): ?Kelas
    {
        $guru = $this->guruAktif();
        $id   = $this->kelasAktifId();
        if (! $guru || ! $id) return null;

        return Kelas::where('id', $id)->where('guru_id', $guru->id)->first();
    }
}
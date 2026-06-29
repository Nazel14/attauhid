<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\KelasNotifikasi;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    public function baca(KelasNotifikasi $notifikasi)
    {
        $guru = Guru::where('user_id', Auth::id())->first();
        if (!$guru || (int) $notifikasi->guru_id !== (int) $guru->id) {
            return back()->with('error', 'Tidak berwenang.');
        }
        if (is_null($notifikasi->dibaca_pada)) {
            $notifikasi->update(['dibaca_pada' => now()]);
        }
        return back();
    }

    public function bacaSemua()
    {
        $guru = Guru::where('user_id', Auth::id())->first();
        if ($guru) {
            KelasNotifikasi::where('guru_id', $guru->id)
                ->whereNull('dibaca_pada')
                ->update(['dibaca_pada' => now()]);
        }
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
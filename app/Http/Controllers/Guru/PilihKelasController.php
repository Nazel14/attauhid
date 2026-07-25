<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PilihKelasController extends Controller
{
    public function index()
    {
        $guru = Auth::user()->guru;
        abort_if(! $guru, 403, 'Akun Anda belum terhubung ke data guru.');

        $kelasList = Kelas::where('guru_id', $guru->id)
            ->withCount('santris')
            ->orderBy('name')
            ->get(['id', 'name', 'tahun_ajaran', 'gender_restriction']);

        abort_if($kelasList->isEmpty(), 403, 'Anda belum ditugaskan ke kelas mana pun.');

        // Cuma 1 kelas → tidak perlu memilih, langsung masuk
        if ($kelasList->count() === 1) {
            session(['kelas_aktif_id' => $kelasList->first()->id]);
            return redirect()->route('guru.dashboard');
        }

        return Inertia::render('Guru/PilihKelas', [
            'kelasList'    => $kelasList,
            'kelasAktifId' => session('kelas_aktif_id'),
            'guruName'     => $guru->name,
        ]);
    }

    public function set(Request $request)
    {
        $guru = Auth::user()->guru;
        abort_if(! $guru, 403);

        $validated = $request->validate([
            'kelas_id' => [
                'required',
                'integer',
                Rule::exists('kelas', 'id')->where('guru_id', $guru->id),
            ],
        ], [
            'kelas_id.exists' => 'Kelas tersebut bukan kelas yang Anda ampu.',
        ]);

        session(['kelas_aktif_id' => (int) $validated['kelas_id']]);

        $nama = Kelas::whereKey($validated['kelas_id'])->value('name');

        return redirect()->route('guru.dashboard')
            ->with('success', "Kelas aktif: {$nama}");
    }
}
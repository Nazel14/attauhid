<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setoran;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        Log::info('Dashboard Guru - User ID: ' . $userId);

        $guru = Guru::where('user_id', $userId)->first();

        if ($guru) {
            Log::info('Guru ditemukan - ID: ' . $guru->id . ', Name: ' . $guru->name);
        } else {
            Log::warning('Guru tidak ditemukan untuk user_id: ' . $userId);
        }

        // ✅ Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();

        $kelas        = null;
        $jumlahSantri = 0;
        $santriList   = collect();

        if ($guru) {
            $kelas = Kelas::where('guru_id', $guru->id)->first();

            if ($kelas) {
                Log::info('Kelas ditemukan - ID: ' . $kelas->id . ', Name: ' . $kelas->name);

                $jumlahSantri = Santri::where('kelas_id', $kelas->id)->count();

                $santriList = Santri::where('kelas_id', $kelas->id)
                    ->orderBy('name')
                    ->get();

                // ✅ Ambil total juz hafalan per santri
                // Jika ada tahun ajaran aktif, filter berdasarkan tahun ajaran
                // Jika tidak ada, ambil all time (backward compatible)
                $santriIds = $santriList->pluck('id');

                $hafalanQuery = Setoran::whereIn('santri_id', $santriIds)
                    ->where('kategori_setoran', 'Hafalan');

                if ($tahunAjaranAktif) {
                    $hafalanQuery->where('tahun_ajaran_id', $tahunAjaranAktif->id);
                }

                $hafalanPerSantri = $hafalanQuery
                    ->selectRaw('santri_id, SUM(total_juz) as total_juz_hafalan')
                    ->groupBy('santri_id')
                    ->pluck('total_juz_hafalan', 'santri_id');

                // Inject progress ke setiap santri
                $santriList = $santriList->map(function ($santri) use ($hafalanPerSantri, $tahunAjaranAktif) {
                    $totalJuz  = round($hafalanPerSantri[$santri->id] ?? 0, 2);

                    // ✅ Gunakan target per tahun ajaran jika tersedia, fallback ke target_juz santri
                    $targetJuz = $tahunAjaranAktif
                        ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranAktif->id) ?? ($santri->target_juz > 0 ? $santri->target_juz : 30))
                        : ($santri->target_juz > 0 ? $santri->target_juz : 30);

                    $persen = min(100, round(($totalJuz / $targetJuz) * 100, 1));

                    $santri->hafalan_total_juz = $totalJuz;
                    $santri->hafalan_persen    = $persen;
                    $santri->target_juz_aktif  = $targetJuz; // ✅ inject target yang dipakai
                    return $santri;
                });

            } else {
                Log::warning('Kelas tidak ditemukan untuk guru_id: ' . $guru->id);
                $semuaKelas = Kelas::all();
                Log::info('Semua kelas di database:', $semuaKelas->toArray());
            }
        }

        return view('guru.dashboard', compact(
            'kelas',
            'jumlahSantri',
            'santriList',
            'guru',
            'tahunAjaranAktif' // ✅ BARU: dikirim ke view
        ));
    }
}
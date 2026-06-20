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

                    // ============================================
                    // SANTRI IQRO: progress = halaman tersetor / total halaman jilid
                    // ============================================
                    if ($santri->isIqro()) {
                        $jilid    = (int) $santri->target_jilid;
                        $totalHal = (int) config("iqro.halaman_per_jilid.$jilid", 0);
                        $halSudah = count($santri->halamanIqroTersetor($jilid));

                        if ($totalHal > 0) {
                            $persenIqro = min(100, round(($halSudah / $totalHal) * 100, 1));
                        } else {
                            $persenIqro = null; // jumlah halaman jilid belum diketahui → jangan tebak
                        }

                        $santri->is_iqro_progress  = true;
                        $santri->iqro_jilid         = $jilid;
                        $santri->iqro_hal_sudah     = $halSudah;
                        $santri->iqro_hal_total     = $totalHal;
                        $santri->hafalan_persen     = $persenIqro ?? 0;
                        $santri->iqro_persen_diketahui = $totalHal > 0;
                        return $santri;
                    }

                    // ============================================
                    // SANTRI QUR'AN: progress = juz tersetor / target juz (logika lama)
                    // ============================================
                    $totalJuz  = round($hafalanPerSantri[$santri->id] ?? 0, 2);

                    $targetJuz = $tahunAjaranAktif
                        ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranAktif->id) ?: ($santri->target_juz > 0 ? $santri->target_juz : 30))
                        : ($santri->target_juz > 0 ? $santri->target_juz : 30);

                    if (!$targetJuz || $targetJuz <= 0) {
                        $targetJuz = 30;
                    }

                    $persen = $targetJuz > 0
                        ? min(100, round(($totalJuz / $targetJuz) * 100, 1))
                        : 0;

                    $santri->is_iqro_progress  = false;
                    $santri->hafalan_total_juz = $totalJuz;
                    $santri->hafalan_persen    = $persen;
                    $santri->target_juz_aktif  = $targetJuz;
                    return $santri;
                });

                 // ✅ Deteksi santri Iqro yang SIAP NAIK JILID (1 query agregat, bukan per-santri)
                $santriSiapNaik = $this->deteksiSiapNaikJilid($santriList);

            } else {
                Log::warning('Kelas tidak ditemukan untuk guru_id: ' . $guru->id);
                $semuaKelas = Kelas::all();
                Log::info('Semua kelas di database:', $semuaKelas->toArray());
            }
        }

        // default kosong bila guru/kelas tidak ada
        $santriSiapNaik = $santriSiapNaik ?? collect();

        return view('guru.dashboard', compact(
            'kelas',
            'jumlahSantri',
            'santriList',
            'guru',
            'tahunAjaranAktif',
            'santriSiapNaik' // ✅ BARU: santri Iqro siap naik jilid
        ));
    }

    /**
     * Naikkan jilid untuk santri-santri yang dicentang guru.
     * Versi minimal: hanya menaikkan target_jilid +1 (tanpa tabel riwayat).
     */
    public function naikkanJilid(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'santri_ids'   => 'required|array|min:1',
            'santri_ids.*' => 'integer|exists:santris,id',
        ]);

        $guru = Guru::where('user_id', Auth::id())->first();
        if (!$guru) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = Kelas::where('guru_id', $guru->id)->first();
        if (!$kelas) {
            return redirect()->route('guru.dashboard')->with('error', 'Kelas tidak ditemukan.');
        }

        $maxJilid = (int) config('iqro.max_jilid', 6);

        // Hanya santri di kelas guru ini, metode Iqro, belum jilid terakhir
        $santris = Santri::whereIn('id', $validated['santri_ids'])
            ->where('kelas_id', $kelas->id)
            ->get();

        $naik = 0;
        foreach ($santris as $s) {
            if (!$s->isIqro()) continue;
            $jilid = (int) $s->target_jilid;
            if ($jilid < 1 || $jilid >= $maxJilid) continue;

            $s->target_jilid = $jilid + 1;
            $s->save();
            $naik++;
        }

        if ($naik === 0) {
            return redirect()->route('guru.dashboard')
                ->with('warning', 'Tidak ada santri yang dinaikkan.');
        }

        return redirect()->route('guru.dashboard')
            ->with('success', "{$naik} santri berhasil dinaikkan ke jilid berikutnya.");
    }

    /**
     * Deteksi santri Iqro yang sudah menyelesaikan SEMUA halaman jilidnya.
     * Pakai 1 query agregat untuk semua setoran Iqro sekaligus (hindari N+1).
     */
    private function deteksiSiapNaikJilid($santriList)
    {
        // Hanya proses santri yang metode-nya Iqro & belum di jilid terakhir
        $maxJilid = (int) config('iqro.max_jilid', 6);

        $kandidat = $santriList->filter(function ($s) use ($maxJilid) {
            return $s->isIqro()
                && (int) $s->target_jilid >= 1
                && (int) $s->target_jilid < $maxJilid;
        });

        if ($kandidat->isEmpty()) {
            return collect();
        }

        // Ambil SEMUA setoran Iqro untuk para kandidat dalam 1 query
        $santriIds = $kandidat->pluck('id')->all();

        $rows = Setoran::whereIn('santri_id', $santriIds)
            ->where(function ($q) {
                $q->where('surat_start', 'like', 'Iqro Jilid %')
                  ->orWhere('surat_end', 'like', 'Iqro Jilid %');
            })
            ->get(['santri_id', 'surat_start', 'surat_end', 'ayat_start', 'ayat_end']);

        // Kelompokkan halaman tersetor per (santri_id, jilid)
        // struktur: $tersetor[santri_id][jilid] = [set halaman]
        $tersetor = [];
        foreach ($rows as $r) {
            // Ambil nomor jilid dari label "Iqro Jilid N"
            $label = $r->surat_start ?: $r->surat_end;
            if (!preg_match('/Iqro Jilid (\d+)/', (string) $label, $m)) {
                continue;
            }
            $jilid = (int) $m[1];

            $start = (int) $r->ayat_start;
            $end   = (int) ($r->ayat_end ?: $r->ayat_start);
            if ($start <= 0) continue;
            if ($end < $start) { [$start, $end] = [$end, $start]; }

            for ($p = $start; $p <= $end; $p++) {
                $tersetor[$r->santri_id][$jilid][$p] = true;
            }
        }

        // Tentukan siapa yang lengkap halaman jilidnya
        $siap = collect();
        foreach ($kandidat as $s) {
            $jilid    = (int) $s->target_jilid;
            $totalHal = config("iqro.halaman_per_jilid.$jilid");
            if (!$totalHal) continue; // jumlah halaman belum diketahui → skip

            $halSantri = $tersetor[$s->id][$jilid] ?? [];
            $lengkap = true;
            for ($p = 1; $p <= $totalHal; $p++) {
                if (!isset($halSantri[$p])) { $lengkap = false; break; }
            }

            if ($lengkap) {
                $s->jilid_sekarang   = $jilid;
                $s->jilid_berikutnya = $jilid + 1;
                $siap->push($s);
            }
        }

        return $siap;
    }
}
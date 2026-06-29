<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setoran;
use App\Models\TahunAjaran;
use App\Models\KenaikanUsulan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\KelasNotifikasi;

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

                $santriList = Santri::with('user')
                    ->where('kelas_id', $kelas->id)
                    ->orderBy('name')
                    ->get();

                $santriIds = $santriList->pluck('id');

                // Progress hafalan per santri DI KELAS INI (kebal-tanggal via kelas_id).
                // Reset otomatis saat naik kelas (setoran lama ber-kelas_id kelas lama).
                $hafalanPerSantri = Setoran::whereIn('santri_id', $santriIds)
                    ->where('kelas_id', $kelas->id)
                    ->where('kategori_setoran', 'Hafalan')
                    ->selectRaw('santri_id, ROUND(SUM(total_juz), 2) as total')
                    ->groupBy('santri_id')
                    ->pluck('total', 'santri_id');

                // ✅ Status usulan kenaikan (dipakai saat hitung target & badge)
                $usulanIds = $tahunAjaranAktif
                    ? KenaikanUsulan::where('tahun_ajaran_id', $tahunAjaranAktif->id)
                        ->whereIn('santri_id', $santriList->pluck('id'))
                        ->pluck('status', 'santri_id')
                    : collect();

                // Inject progress ke setiap santri
                $santriList = $santriList->map(function ($santri) use ($hafalanPerSantri, $tahunAjaranAktif, $usulanIds) {
                    $kelasId  = $santri->kelas_id;
                    $baruNaik = ($usulanIds[$santri->id] ?? null) === 'disetujui';

                    // ============================================
                    // SANTRI IQRO: progress = halaman tersetor (di kelas ini) / total halaman jilid
                    // ============================================
                    if ($santri->isIqro()) {
                        $jilid    = (int) $santri->target_jilid;
                        $totalHal = (int) config("iqro.halaman_per_jilid.$jilid", 0);

                        // Per kelas → reset otomatis saat naik kelas
                        $halSudah = count($santri->halamanIqroTersetorKelas($jilid, $kelasId));

                        if ($totalHal > 0) {
                            $persenIqro = min(100, round(($halSudah / $totalHal) * 100, 1));
                        } else {
                            $persenIqro = null; // jumlah halaman jilid belum diketahui → jangan tebak
                        }

                        $santri->is_iqro_progress      = true;
                        $santri->iqro_jilid            = $jilid;
                        $santri->iqro_hal_sudah        = $halSudah;
                        $santri->iqro_hal_total        = $totalHal;
                        $santri->hafalan_persen        = $persenIqro ?? 0;
                        $santri->iqro_persen_diketahui = $totalHal > 0;
                        $santri->baru_naik             = $baruNaik;
                        $santri->target_badge          = 'Jilid ' . $jilid;
                        return $santri;
                    }

                    // ============================================
                    // SANTRI QUR'AN: progress = juz tersetor (di kelas ini) / target juz
                    // ============================================
                    $totalJuz = round($hafalanPerSantri[$santri->id] ?? 0, 2);

                    // Target dari per-tahun-ajaran; fallback target_juz bila kosong.
                    $targetPerTa = $tahunAjaranAktif
                        ? $santri->getTargetJuzByTahunAjaran($tahunAjaranAktif->id)
                        : null;
                    $targetJuz = ($targetPerTa && $targetPerTa > 0)
                        ? (float) $targetPerTa
                        : (float) ($santri->target_juz ?? 0);

                    $persen = $targetJuz > 0
                        ? min(100, round(($totalJuz / $targetJuz) * 100, 1))
                        : 0;

                    $santri->is_iqro_progress  = false;
                    $santri->hafalan_total_juz = $totalJuz;
                    $santri->hafalan_persen    = $persen;
                    $santri->target_juz_aktif  = $targetJuz;
                    $santri->target_badge      = number_format($targetJuz, 1) . ' Juz';
                    $santri->baru_naik         = $baruNaik;
                    return $santri;
                });

                $santriList->each(function ($s) use ($usulanIds) {
                    $s->usulan_status = $usulanIds[$s->id] ?? null; // null | diusulkan | disetujui | ditolak
                });

                // ✅ Deteksi santri Iqro yang SIAP NAIK JILID — dihitung per KELAS sekarang
                $santriSiapNaik = $this->deteksiSiapNaikJilid($santriList, $kelas->id);

            } else {
                Log::warning('Kelas tidak ditemukan untuk guru_id: ' . $guru->id);
                $semuaKelas = Kelas::all();
                Log::info('Semua kelas di database:', $semuaKelas->toArray());
            }
        }

        // default kosong bila guru/kelas tidak ada
        $santriSiapNaik = $santriSiapNaik ?? collect();

        // ✅ Notifikasi guru
        $notifikasi      = collect();
        $notifikasiBelum = 0;
        if ($guru) {
            $notifikasi = KelasNotifikasi::where('guru_id', $guru->id)
                ->orderByDesc('created_at')->limit(10)->get();
            $notifikasiBelum = $notifikasi->whereNull('dibaca_pada')->count();
        }

        return view('guru.dashboard', compact(
            'kelas',
            'jumlahSantri',
            'santriList',
            'guru',
            'tahunAjaranAktif',
            'santriSiapNaik',
            'notifikasi',
            'notifikasiBelum'
        ));
    }

    /**
     * Naikkan jilid untuk santri-santri yang dicentang guru.
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
     * Deteksi santri Iqro yang sudah menyelesaikan SEMUA halaman jilidnya
     * DI KELAS sekarang (kelas_id). Pakai 1 query agregat (hindari N+1).
     */
    private function deteksiSiapNaikJilid($santriList, ?int $kelasId = null)
    {
        $maxJilid = (int) config('iqro.max_jilid', 6);

        $kandidat = $santriList->filter(function ($s) use ($maxJilid) {
            return $s->isIqro()
                && (int) $s->target_jilid >= 1
                && (int) $s->target_jilid < $maxJilid;
        });

        if ($kandidat->isEmpty()) {
            return collect();
        }

        $santriIds = $kandidat->pluck('id')->all();

        $rows = Setoran::whereIn('santri_id', $santriIds)
            ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
            ->where(function ($q) {
                $q->where('surat_start', 'like', 'Iqro Jilid %')
                  ->orWhere('surat_end', 'like', 'Iqro Jilid %');
            })
            ->get(['santri_id', 'surat_start', 'surat_end', 'ayat_start', 'ayat_end']);

        // $tersetor[santri_id][jilid] = [set halaman]
        $tersetor = [];
        foreach ($rows as $r) {
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

        $siap = collect();
        foreach ($kandidat as $s) {
            $jilid    = (int) $s->target_jilid;
            $totalHal = config("iqro.halaman_per_jilid.$jilid");
            if (!$totalHal) continue;

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
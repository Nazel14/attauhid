<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Setoran;
use App\Models\Santri;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        /* ============================================================
         * 1. AMBIL SANTRI YANG DIAMPU GURU
         * ============================================================ */
        $guru = $user->guru;

        $santris = $guru
            ? $guru->santriBimbingan()->orderBy('name')->get()
            : Santri::query()->orderBy('name')->get();

        $ids = $santris->pluck('id');

        /* ============================================================
         * 2. TAHUN AJARAN AKTIF
         * ============================================================ */
        $ta = TahunAjaran::where('is_aktif', true)->first();

        /* Total hafalan per santri — juz (Al-Quran).
         * Untuk santri jilid-based (Iqro/Aisar) progress dihitung
         * lewat helper model, bukan agregat SQL ini. */
        $hafalanPerSantri = Setoran::whereIn('santri_id', $ids)
            ->where('kategori_setoran', 'Hafalan')
            ->selectRaw('santri_id, SUM(total_juz) as total_juz')
            ->groupBy('santri_id')
            ->get()
            ->keyBy('santri_id');

        /* ============================================================
         * 3. BENTUK santriList (chart metode + progress)
         * ============================================================ */
        $santriList = $santris->map(function (Santri $s) use ($hafalanPerSantri) {
            return [
                'id'              => $s->id,
                'name'            => $s->name,
                'nis'             => $s->nis ?? null,
                'target_metode'   => $s->target_metode?->value ?? 'quran',
                'target_jilid'    => $s->target_jilid ?? null,
                'progress_persen' => $this->hitungProgressPersen($s, $hafalanPerSantri->get($s->id)),
            ];
        })->values();

        /* ============================================================
         * 4. PRESENSI HARI INI + JUMLAH SETORAN HARI INI
         * ============================================================ */
        $today = Carbon::today()->toDateString();

        $presensiToday = Presensi::whereIn('santri_id', $ids)
            ->whereDate('created_at', $today)
            ->get()
            ->keyBy('santri_id');

        $setoranToday = Setoran::whereIn('santri_id', $ids)
            ->whereDate('created_at', $today)
            ->selectRaw('santri_id, COUNT(*) as jml, MIN(created_at) as waktu')
            ->groupBy('santri_id')
            ->get()
            ->keyBy('santri_id');

        $rows = $santris->map(function (Santri $s) use ($presensiToday, $setoranToday) {
            $p   = $presensiToday->get($s->id);
            $set = $setoranToday->get($s->id);

            $waktu = null;
            if ($p) {
                $waktu = $p->created_at ? Carbon::parse($p->created_at)->format('H:i') : null;
            } elseif ($set && $set->waktu) {
                $waktu = Carbon::parse($set->waktu)->format('H:i');
            }

            return [
                'id'             => $s->id,
                'name'           => $s->name,
                'status'         => $p ? strtolower(trim($p->status)) : null,
                'waktu'          => $waktu,
                'jumlah_setoran' => (int) ($set->jml ?? 0),
            ];
        })->values();

        $summary = [
            'hadir' => $rows->where('status', 'hadir')->count(),
            'sakit' => $rows->where('status', 'sakit')->count(),
            'izin'  => $rows->where('status', 'izin')->count(),
            'alpha' => $rows->where('status', 'alpha')->count(),
        ];

        /* ============================================================
         * 4b. USULAN NAIK JILID (Iqro / Aisar / metode jilid lainnya)
         * ============================================================ */
        $santriSiapNaik = $santris
            ->filter(fn (Santri $s) => $s->isSiapNaikJilid())
            ->map(function (Santri $s) {
                $jilid = (int) $s->target_jilid;

                return [
                    'id'               => $s->id,
                    'name'             => $s->name,
                    'metode'           => $s->isAisar() ? 'Aisar' : 'Iqro',
                    'jilid_sekarang'   => $jilid,
                    'jilid_berikutnya' => $jilid + 1,
                    'max_jilid'        => $s->maxJilid(),
                    'halaman_tersetor' => count($s->halamanIqroTersetor($jilid)),
                    'total_halaman'    => (int) config($s->jilidConfigKey() . ".halaman_per_jilid.$jilid", 0),
                ];
            })->values();

        $santriTamat = $santris
            ->filter(fn (Santri $s) => $s->isJilidTamat())
            ->map(fn (Santri $s) => [
                'id'     => $s->id,
                'name'   => $s->name,
                'metode' => $s->isAisar() ? 'Aisar' : 'Iqro',
                'jilid'  => (int) $s->target_jilid,
            ])->values();

        /* ============================================================
         * 5. RENDER
         * ============================================================ */
        return Inertia::render('Guru/Dashboard', [
            'santriList'      => $santriList,
            'santriSiapNaik'  => $santriSiapNaik,
            'santriTamat'     => $santriTamat,
            'presensiHariIni' => [
                'tanggal' => $today,
                'summary' => $summary,
                'rows'    => $rows,
            ],
        ]);
    }

    /**
     * Guru mengesahkan kenaikan jilid santri bimbingannya.
     * Tidak ada tabel riwayat — cukup naikkan target_jilid.
     */
    public function naikkanJilid(Request $request, Santri $santri)
    {
        $guru = Auth::user()->guru;

        if (! $guru || ! $guru->santriBimbingan()->whereKey($santri->id)->exists()) {
            return back()->with('error', 'Santri ini bukan bimbingan Anda.');
        }

        if (! $santri->isJilidBased()) {
            return back()->with('error', 'Santri ini tidak memakai metode berbasis jilid.');
        }

        if ($santri->isJilidTamat()) {
            return back()->with('error', $santri->name . ' sudah menyelesaikan jilid terakhir.');
        }

        if (! $santri->isSiapNaikJilid()) {
            return back()->with('error', $santri->name . ' belum memenuhi syarat naik jilid.');
        }

        $lama  = (int) $santri->target_jilid;
        $baru  = $lama + 1;
        $label = $santri->isAisar() ? 'Aisar' : 'Iqro';

        $santri->target_jilid = $baru;
        $santri->save();

        return back()->with('success', "{$santri->name} naik ke {$label} Jilid {$baru}.");
    }

    /* ================================================================
     * HELPER: persentase progress satu santri (0..100)
     * ================================================================ */
    private function hitungProgressPersen(Santri $santri, $agg): int
    {
        if ($santri->isJilidBased()) { // iqro ATAU aisar
            $jilid  = (int) ($santri->target_jilid ?? 1);
            $target = (int) (config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $jilid) ?? 0);
            if ($target <= 0) return 0; // belum diatur → 0, jangan tebak

            $totalHalaman = count($santri->halamanIqroTersetorKelas($jilid, $santri->kelas_id));
            return (int) round(min($totalHalaman / $target * 100, 100));
        }

        // AL-QURAN → basisnya JUZ
        $totalJuz = (float) ($agg->total_juz ?? 0);
        $target   = (float) ($santri->target_juz ?? 30);
        if ($target <= 0) return 0;

        return (int) round(min($totalJuz / $target * 100, 100));
    }
}
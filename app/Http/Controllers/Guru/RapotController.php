<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Setoran;
use App\Models\Presensi;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RapotController extends Controller
{
    /**
     * Mendapatkan default tanggal mulai dan akhir berdasarkan TahunAjaran aktif di database.
     * Fallback ke semester manual jika tidak ada tahun ajaran aktif.
     */
    private function getDefaultSemesterRange(): array
{
    $tahunAjaran = TahunAjaran::getAktif();

    if ($tahunAjaran) {
        $tanggalMulai = Carbon::parse($tahunAjaran->tanggal_mulai)->format('Y-m-d');

        // Gunakan tanggal_akhir (konsisten dengan SetoranController)
        // Default akhir = hari ini jika masih dalam periode tahun ajaran,
        // atau tanggal_akhir jika sudah lewat — supaya data terbaru selalu masuk
        $tanggalAkhirTa = Carbon::parse($tahunAjaran->tanggal_akhir);
        $tanggalAkhir   = now()->lt($tanggalAkhirTa)
            ? now()->format('Y-m-d')
            : $tanggalAkhirTa->format('Y-m-d');

        return [$tanggalMulai, $tanggalAkhir];
    }

    // Fallback jika tidak ada tahun ajaran aktif
    $bulan = now()->month;
    if ($bulan <= 6) {
        return [
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
        ];
    }
    return [
        now()->setMonth(7)->startOfMonth()->format('Y-m-d'),
        now()->format('Y-m-d'),
    ];
}

    /**
     * Daftar santri untuk dipilih laporannya
     */
    public function index()
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $kelas = $guru ? Kelas::where('guru_id', $guru->id)->first() : null;

        // Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        $santriList = $kelas
            ? Santri::where('kelas_id', $kelas->id)->orderBy('name')->get()
            : collect();

        // Hitung progress hafalan tiap santri berdasarkan tahun ajaran aktif
        $santriList->each(function ($santri) use ($tahunAjaranAktif, $tahunAjaranId) {
            $isIqro = ($santri->target_metode?->value ?? 'quran') === 'iqro';
            $santri->is_iqro = $isIqro;

            if ($isIqro) {
                // Hitung halaman Iqro Hafalan
                $setoranIqroQuery = Setoran::where('santri_id', $santri->id)
                    ->where('kategori_setoran', 'Hafalan')
                    ->where('surat_start', 'like', 'Iqro Jilid %');
                if ($tahunAjaranId) {
                    $setoranIqroQuery->where('tahun_ajaran_id', $tahunAjaranId);
                }
                $targetJilid   = (int)($santri->target_jilid ?? 1);
                $totalHalaman = 0;
                foreach ($setoranIqroQuery->get() as $s) {
                    // Hanya hitung halaman dari jilid target sekarang,
                    // agar progress reset ke nol saat santri naik jilid.
                    if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
                    if ((int)$mj[1] !== $targetJilid) continue;
                    $h1 = (int)$s->ayat_start;
                    $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                    if ($h1 > 0 && $h2 >= $h1) $totalHalaman += ($h2 - $h1 + 1);
                }
                $totalHalJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);

                $santri->hafalan_total_juz    = 0;
                $santri->total_halaman_iqro   = $totalHalaman;
                $santri->total_halaman_jilid  = $totalHalJilid;
                $santri->iqro_persen_diketahui = $totalHalJilid > 0;
                $santri->target_display        = 'Jilid ' . $targetJilid;
                $santri->hafalan_persen        = $totalHalJilid > 0
                    ? min(100, round(($totalHalaman / $totalHalJilid) * 100, 1))
                    : 0;
            } else {
                $targetJuz = $tahunAjaranAktif
                    ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?? ($santri->target_juz > 0 ? $santri->target_juz : 30))
                    : (($santri->target_juz ?? 0) > 0 ? $santri->target_juz : 30);

                $hafalanQuery = Setoran::where('santri_id', $santri->id)
                    ->where('kategori_setoran', 'Hafalan');
                if ($tahunAjaranId) {
                    $hafalanQuery->where('tahun_ajaran_id', $tahunAjaranId);
                }
                $totalJuz = $hafalanQuery->sum('total_juz') ?? 0;

                $santri->hafalan_total_juz    = round($totalJuz, 2);
                $santri->total_halaman_iqro   = 0;
                $santri->total_halaman_jilid  = 0;
                $santri->iqro_persen_diketahui = false;
                $santri->target_display        = number_format($targetJuz, 1) . ' Juz';
                $santri->hafalan_persen        = $targetJuz > 0
                    ? min(100, round(($totalJuz / $targetJuz) * 100, 1))
                    : 0;
            }
        });

        return view('guru.rapot.index', compact(
            'kelas',
            'santriList',
            'guru',
            'tahunAjaranAktif'
        ));
    }

    /**
     * Detail rapot satu santri
     */
    public function show(Request $request, Santri $santri)
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $kelas = $guru ? Kelas::where('guru_id', $guru->id)->first() : null;

        if (!$kelas || $santri->kelas_id !== $kelas->id) {
            abort(403, 'Anda tidak berwenang mengakses rapot santri ini.');
        }

        $santri->load(['kelas', 'guru']);

        // Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ✅ Default range dari TahunAjaran aktif di database
        [$defaultMulai, $defaultAkhir] = $this->getDefaultSemesterRange();
        $tanggalMulai = $request->input('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = $request->input('tanggal_akhir', $defaultAkhir);

        // Setoran dengan filter tahun ajaran
        $setoranQuery = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc');

        if ($tahunAjaranId) {
            $setoranQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $setoransRaw = $setoranQuery->get();
        $setorans    = $this->groupSetoranByKategoriSurat($setoransRaw);

        // Presensi dengan filter tahun ajaran
        $presensiQuery = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc');

        if ($tahunAjaranId) {
            $presensiQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $presensis = $presensiQuery->get();

        // Gunakan target per tahun ajaran jika ada
        $targetJuz = $tahunAjaranAktif
            ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?? ($santri->target_juz ?? 30))
            : ($santri->target_juz ?? 30);

        $totalJuzHafalan  = $setoransRaw->where('kategori_setoran', 'Hafalan')->sum('total_juz') ?? 0;
        $persentaseTarget = $targetJuz > 0
            ? min(100, round(($totalJuzHafalan / $targetJuz) * 100, 1))
            : 0;

        // Deteksi Iqro
        $isIqro = ($santri->target_metode?->value ?? 'quran') === 'iqro';
        $totalHalamanIqro  = 0;
        $totalHalamanJilid = 0;
        $progressIqro      = 0;
        if ($isIqro) {
            $targetJilid       = (int)($santri->target_jilid ?? 1);
            foreach ($setoransRaw->where('kategori_setoran', 'Hafalan') as $s) {
                // Hanya hitung halaman dari jilid target sekarang,
                // agar progress reset ke nol saat santri naik jilid.
                if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
                if ((int)$mj[1] !== $targetJilid) continue;
                $h1 = (int)$s->ayat_start;
                $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                if ($h1 > 0 && $h2 >= $h1) $totalHalamanIqro += ($h2 - $h1 + 1);
            }
            $totalHalamanJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);
            $progressIqro      = $totalHalamanJilid > 0
                ? min(100, round(($totalHalamanIqro / $totalHalamanJilid) * 100, 1))
                : 0;
        }

        $statistikSetoran = [
            'total_setoran'        => $setoransRaw->count(),
            'rata_nilai'           => round($setoransRaw->avg('nilai') ?? 0, 2),
            'total_juz'            => round($setoransRaw->sum('total_juz') ?? 0, 2),
            'total_juz_hafalan'    => round($totalJuzHafalan, 2),
            'total_kata'           => $setoransRaw->sum('total_kata') ?? 0,
            'hafalan'              => $setoransRaw->where('kategori_setoran', 'Hafalan')->count(),
            'murajaah'             => $setoransRaw->where('kategori_setoran', 'Murajaah')->count(),
            'tilawah'              => $setoransRaw->where('kategori_setoran', 'Tilawah')->count(),
            'target_juz'           => $targetJuz,
            'persentase_target'    => $persentaseTarget,
            // Iqro
            'is_iqro'              => $isIqro,
            'total_halaman_iqro'   => $totalHalamanIqro,
            'total_halaman_jilid'  => $totalHalamanJilid,
            'progress_iqro'        => $progressIqro,
            'target_jilid'         => $santri->target_jilid ?? 1,
            'iqro_persen_diketahui' => $totalHalamanJilid > 0,
        ];

        $statistikPresensi = [
            'total_hari' => $presensis->count(),
            'hadir'      => $presensis->where('status', 'Hadir')->count(),
            'izin'       => $presensis->where('status', 'Izin')->count(),
            'sakit'      => $presensis->where('status', 'Sakit')->count(),
            'alpha'      => $presensis->where('status', 'Alpha')->count(),
        ];

        $statistikPresensi['persentase_kehadiran'] = $statistikPresensi['total_hari'] > 0
            ? round(($statistikPresensi['hadir'] / $statistikPresensi['total_hari']) * 100, 1)
            : 0;

        return view('guru.rapot.show', compact(
            'santri',
            'setorans',
            'presensis',
            'statistikSetoran',
            'statistikPresensi',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif'
        ));
    }

    /**
     * Download / Preview PDF rapot santri
     */
    public function downloadPdf(Request $request, Santri $santri)
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $kelas = $guru ? Kelas::where('guru_id', $guru->id)->first() : null;

        if (!$kelas || $santri->kelas_id !== $kelas->id) {
            abort(403, 'Anda tidak berwenang mengakses rapot santri ini.');
        }

        $santri->load(['kelas', 'guru']);

        // Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ✅ Default range dari TahunAjaran aktif di database
        [$defaultMulai, $defaultAkhir] = $this->getDefaultSemesterRange();
        $tanggalMulai = $request->input('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = $request->input('tanggal_akhir', $defaultAkhir);

        // Setoran dengan filter tahun ajaran
        $setoranQuery = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc');

        if ($tahunAjaranId) {
            $setoranQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $setoransRaw = $setoranQuery->get();
        $setorans    = $this->groupSetoranByKategoriSurat($setoransRaw);

        // Presensi dengan filter tahun ajaran
        $presensiQuery = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc');

        if ($tahunAjaranId) {
            $presensiQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $presensis = $presensiQuery->get();

        // Gunakan target per tahun ajaran jika ada
        $targetJuz = $tahunAjaranAktif
            ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?? ($santri->target_juz ?? 30))
            : ($santri->target_juz ?? 30);

        $totalJuzHafalan  = $setoransRaw->where('kategori_setoran', 'Hafalan')->sum('total_juz') ?? 0;
        $persentaseTarget = $targetJuz > 0
            ? min(100, round(($totalJuzHafalan / $targetJuz) * 100, 1))
            : 0;

        $statistikSetoran = [
            'total_setoran'     => $setoransRaw->count(),
            'rata_nilai'        => round($setoransRaw->avg('nilai') ?? 0, 2),
            'total_juz'         => round($setoransRaw->sum('total_juz') ?? 0, 2),
            'total_juz_hafalan' => round($totalJuzHafalan, 2),
            'total_kata'        => $setoransRaw->sum('total_kata') ?? 0,
            'hafalan'           => $setoransRaw->where('kategori_setoran', 'Hafalan')->count(),
            'murajaah'          => $setoransRaw->where('kategori_setoran', 'Murajaah')->count(),
            'tilawah'           => $setoransRaw->where('kategori_setoran', 'Tilawah')->count(),
            'target_juz'        => $targetJuz,
            'persentase_target' => $persentaseTarget,
        ];

        $statistikPresensi = [
            'total_hari' => $presensis->count(),
            'hadir'      => $presensis->where('status', 'Hadir')->count(),
            'izin'       => $presensis->where('status', 'Izin')->count(),
            'sakit'      => $presensis->where('status', 'Sakit')->count(),
            'alpha'      => $presensis->where('status', 'Alpha')->count(),
        ];

        $statistikPresensi['persentase_kehadiran'] = $statistikPresensi['total_hari'] > 0
            ? round(($statistikPresensi['hadir'] / $statistikPresensi['total_hari']) * 100, 1)
            : 0;

        $pdf = Pdf::loadView('santri.rapot.pdf', compact(
            'santri',
            'setorans',
            'presensis',
            'statistikSetoran',
            'statistikPresensi',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif'
        ));

        $pdf->setPaper('A4', 'portrait');

        $isPreview = $request->boolean('preview');

        if ($isPreview) {
            return $pdf->stream();
        }

        $filename = 'Rapot_' . str_replace(' ', '_', $santri->name) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Mengelompokkan setoran berdasarkan kategori dan surat
     */
    private function groupSetoranByKategoriSurat($setorans): array
    {
        $grouped = [];

        foreach ($setorans as $setoran) {
            $kategori = $setoran->kategori_setoran;
            $suratKey = $setoran->surat_start
                . ($setoran->surat_start != $setoran->surat_end ? ' - ' . $setoran->surat_end : '');

            if (!isset($grouped[$kategori])) {
                $grouped[$kategori] = [];
            }

            if (!isset($grouped[$kategori][$suratKey])) {
                $grouped[$kategori][$suratKey] = [
                    'surat_start'    => $setoran->surat_start,
                    'surat_end'      => $setoran->surat_end,
                    'ayat_start_min' => $setoran->ayat_start,
                    'ayat_end_max'   => $setoran->ayat_end,
                    'jumlah_setoran' => 0,
                    'total_nilai'    => 0,
                    'nilai_list'     => [],
                ];
            }

            $grouped[$kategori][$suratKey]['jumlah_setoran']++;
            $grouped[$kategori][$suratKey]['total_nilai']    += $setoran->nilai;
            $grouped[$kategori][$suratKey]['nilai_list'][]    = $setoran->nilai;
            $grouped[$kategori][$suratKey]['ayat_start_min']  = min(
                $grouped[$kategori][$suratKey]['ayat_start_min'],
                $setoran->ayat_start
            );
            $grouped[$kategori][$suratKey]['ayat_end_max'] = max(
                $grouped[$kategori][$suratKey]['ayat_end_max'],
                $setoran->ayat_end
            );
        }

        foreach ($grouped as $kategori => $surats) {
            foreach ($surats as $suratKey => $data) {
                $grouped[$kategori][$suratKey]['rata_nilai'] = round(
                    $data['total_nilai'] / $data['jumlah_setoran'],
                    1
                );
            }
        }

        return $grouped;
    }
}
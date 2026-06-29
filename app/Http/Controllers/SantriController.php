<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\Setoran;
use App\Models\Presensi;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SantriController extends Controller
{
    /**
     * Default tanggal mulai/akhir berdasarkan semester aktif (1 semester).
     * Masih dipakai sebagai FALLBACK ketika santri belum punya setoran di kelasnya.
     */
    private function getDefaultSemesterRange(): array
    {
        $tahunAjaran = TahunAjaran::getAktif();

        if ($tahunAjaran) {
            $tanggalMulai   = Carbon::parse($tahunAjaran->tanggal_mulai)->format('Y-m-d');
            $tanggalAkhirTa = Carbon::parse($tahunAjaran->tanggal_akhir);
            // Default akhir = hari ini jika masih dalam periode, supaya data terbaru masuk
            $tanggalAkhir = now()->lt($tanggalAkhirTa)
                ? now()->format('Y-m-d')
                : $tanggalAkhirTa->format('Y-m-d');
            return [$tanggalMulai, $tanggalAkhir];
        }

        // Fallback tanpa tahun ajaran aktif
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
     * Default tanggal mulai/akhir berdasarkan KELAS santri sekarang.
     *
     * Mengembalikan rentang [mulai, akhir] yang mencakup SELURUH masa santri di
     * kelas ini — otomatis dua semester (1 tahun akademik), karena diambil dari
     * tanggal setoran paling awal & paling akhir yang ber-kelas_id = kelas santri.
     *
     * Kebal-tanggal: rentang ini RESET sendiri saat santri naik kelas (karena
     * kelas_id berubah), persis seperti progress di dashboard.
     *
     * Jika santri belum punya setoran di kelas ini (kelas baru / belum setor),
     * jatuh ke getDefaultSemesterRange() sebagai fallback.
     */
    private function getDefaultKelasRange(Santri $santri): array
    {
        $kelasId = $santri->kelas_id;

        if ($kelasId) {
            $range = Setoran::where('santri_id', $santri->id)
                ->where('kelas_id', $kelasId)
                ->selectRaw('MIN(tanggal) as mulai, MAX(tanggal) as akhir')
                ->first();

            if ($range && $range->mulai) {
                $mulai = Carbon::parse($range->mulai)->format('Y-m-d');
                // Akhir = hari ini bila lebih besar dari setoran terakhir,
                // supaya setoran terbaru hari ini ikut masuk default.
                $akhirSetoran = Carbon::parse($range->akhir);
                $akhir = now()->gt($akhirSetoran)
                    ? now()->format('Y-m-d')
                    : $akhirSetoran->format('Y-m-d');
                return [$mulai, $akhir];
            }
        }

        // Fallback: belum ada setoran di kelas ini
        return $this->getDefaultSemesterRange();
    }

    /**
     * Dashboard Santri: Menampilkan data diri santri yang sedang login
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Via relasi di Model User
        $santri = $user->santri()->with(['kelas', 'guru'])->first();

        // Fallback jika relasi tidak ada
        if (!$santri) {
            $santri = Santri::where('user_id', $user->id)
                ->with(['kelas', 'guru'])
                ->first();
        }

        // Jika santri tidak ditemukan
        if (!$santri) {
            return view('santri.dashboard', [
                'santris'          => collect([]),
                'tahunAjaranAktif' => TahunAjaran::getAktif(),
                'message'          => 'Data santri tidak ditemukan. Silakan hubungi administrator.'
            ]);
        }

        // ✅ Ambil tahun ajaran aktif (dipakai header + filter presensi)
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ✅ Kelas santri sekarang — dipakai untuk progress & statistik setoran.
        //    Kebal-tanggal: mencakup KEDUA semester dalam satu kelas (1 tahun),
        //    dan reset otomatis saat santri naik kelas. Sama persis dgn dashboard guru.
        $kelasIdSantri = $santri->kelas_id;

        // ✅ Ambil setoran — filter PER KELAS (bukan per tahun ajaran/semester)
        $setorans = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->get();

        // Pisahkan per kategori
        $setoranHafalan  = $setorans->where('kategori_setoran', 'Hafalan');
        $setoranMurajaah = $setorans->where('kategori_setoran', 'Murajaah');
        $setoranTilawah  = $setorans->where('kategori_setoran', 'Tilawah');

        // Deteksi Iqro
        $isIqro = ($santri->target_metode?->value ?? 'quran') === 'iqro';

        // Hitung halaman Iqro jika perlu
        $totalHalamanIqro  = 0;
        $totalHalamanJilid = 0;
        if ($isIqro) {
            $targetJilid       = (int)($santri->target_jilid ?? 1);
            foreach ($setoranHafalan as $s) {
                // Hanya hitung halaman dari jilid target sekarang,
                // agar progress reset ke nol saat santri naik jilid.
                if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
                if ((int)$mj[1] !== $targetJilid) continue;
                $h1 = (int)$s->ayat_start;
                $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                if ($h1 > 0 && $h2 >= $h1) $totalHalamanIqro += ($h2 - $h1 + 1);
            }
            $totalHalamanJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);
        }

        // Hitung statistik setoran
        $santri->stats_setoran = (object)[
            'total_setoran'        => $setorans->count(),
            'rata_nilai'           => round($setorans->avg('nilai') ?? 0, 1),
            'total_juz'            => round($setorans->sum('total_juz') ?? 0, 2),
            'total_juz_hafalan'    => round($setoranHafalan->sum('total_juz') ?? 0, 2),
            'setoran_terakhir'     => $setorans->max('tanggal'),
            'rata_nilai_hafalan'   => round($setoranHafalan->avg('nilai') ?? 0, 1),
            'rata_nilai_murajaah'  => round($setoranMurajaah->avg('nilai') ?? 0, 1),
            'rata_nilai_tilawah'   => round($setoranTilawah->avg('nilai') ?? 0, 1),
            // Iqro
            'is_iqro'              => $isIqro,
            'total_halaman_iqro'   => $totalHalamanIqro,
            'total_halaman_jilid'  => $totalHalamanJilid,
            'iqro_persen_diketahui' => $totalHalamanJilid > 0,
            'target_jilid'         => $santri->target_jilid ?? 1,
        ];

        // ✅ Ambil presensi — TETAP filter tahun ajaran aktif.
        //    Tabel presensi belum punya kolom kelas_id (konsisten dgn seluruh app).
        $presensis = Presensi::where('santri_id', $santri->id)
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->get();

        // Hitung statistik presensi
        $santri->stats_presensi = (object)[
            'total_hari'  => $presensis->count(),
            'total_hadir' => $presensis->where('status', 'Hadir')->count(),
            'total_izin'  => $presensis->where('status', 'Izin')->count(),
            'total_sakit' => $presensis->where('status', 'Sakit')->count(),
            'total_alpha' => $presensis->where('status', 'Alpha')->count(),
        ];

        // Hitung persentase kehadiran
        $santri->persentase_kehadiran = $santri->stats_presensi->total_hari > 0
            ? round(($santri->stats_presensi->total_hadir / $santri->stats_presensi->total_hari) * 100, 1)
            : 0;

        // Wrap dalam collection agar view bisa pakai foreach
        $santris = collect([$santri]);

        return view('santri.dashboard', compact('santris', 'tahunAjaranAktif'));
    }

    /**
     * ========================================
     * DETAIL SETORAN
     * ========================================
     * Opsi A: filter PER KELAS (kelas_id) — dua semester dalam 1 kelas.
     * Filter tanggal manual tetap berfungsi (whereBetween).
     * Default range = sepanjang setoran santri di kelas ini (dua semester).
     */
    public function setorans(Request $request)
    {
        $user = Auth::user();
        $santri = $user->santri()->with(['kelas', 'guru'])->first()
            ?? Santri::where('user_id', $user->id)->with(['kelas', 'guru'])->first();

        if (!$santri) {
            abort(404, 'Data santri tidak ditemukan.');
        }

        // ✅ Ambil tahun ajaran aktif (hanya untuk header tampilan)
        $tahunAjaranAktif = TahunAjaran::getAktif();

        // ✅ Kelas santri sekarang — filter utama (kebal-tanggal, dua semester)
        $kelasIdSantri = $santri->kelas_id;

        $kategori = $request->input('kategori');

        // ✅ Default range = sepanjang setoran kelas ini (otomatis dua semester)
        [$defaultMulai, $defaultAkhir] = $this->getDefaultKelasRange($santri);
        $tanggalMulai = $request->input('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = $request->input('tanggal_akhir', $defaultAkhir);

        // ✅ Filter PER KELAS + rentang tanggal (tanggal hanya mempersempit)
        $query = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru');

        if ($kategori) {
            $query->where('kategori_setoran', $kategori);
        }

        $setoransAll = $query->orderBy('tanggal', 'desc')->get();

        // Pisahkan per kategori
        $setoranHafalan  = $setoransAll->where('kategori_setoran', 'Hafalan')->values();
        $setoranMurajaah = $setoransAll->where('kategori_setoran', 'Murajaah')->values();
        $setoranTilawah  = $setoransAll->where('kategori_setoran', 'Tilawah')->values();

        // Hitung statistik
        $statistik = [
            'total_setoran' => $setoransAll->count(),
            'rata_nilai'    => round($setoransAll->avg('nilai') ?? 0, 2),
            'total_juz'     => round($setoransAll->sum('total_juz') ?? 0, 2),
        ];

        $isIqro = ($santri->target_metode?->value ?? 'quran') === 'iqro';

        // Hitung total halaman Iqro untuk stat card
        $totalHalamanIqro = 0;
        if ($isIqro) {
            $targetJilid = (int)($santri->target_jilid ?? 1);
            foreach ($setoransAll->where('kategori_setoran', 'Hafalan') as $s) {
                // Hanya hitung halaman dari jilid target sekarang,
                // agar konsisten dengan progress yang reset saat naik jilid.
                if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
                if ((int)$mj[1] !== $targetJilid) continue;
                $h1 = (int)$s->ayat_start;
                $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                if ($h1 > 0 && $h2 >= $h1) $totalHalamanIqro += ($h2 - $h1 + 1);
            }
        }

        return view('santri.setorans.detail', compact(
            'santri',
            'setoranHafalan',
            'setoranMurajaah',
            'setoranTilawah',
            'statistik',
            'kategori',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif',
            'isIqro',
            'totalHalamanIqro'
        ));
    }

    /**
     * ========================================
     * DETAIL PRESENSI
     * ========================================
     * TIDAK diubah ke kelas_id — tabel presensi belum punya kolom kelas_id.
     * Tetap per-bulan + tahun ajaran aktif (konsisten dgn seluruh app).
     */
    public function presensis(Request $request)
    {
        $user = Auth::user();
        $santri = $user->santri()->with(['kelas', 'guru'])->first()
            ?? Santri::where('user_id', $user->id)->with(['kelas', 'guru'])->first();

        if (!$santri) {
            abort(404, 'Data santri tidak ditemukan.');
        }

        // ✅ Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        $bulan  = $request->input('bulan', now()->format('Y-m'));
        $status = $request->input('status');

        $tanggalMulai = Carbon::parse($bulan . '-01')->startOfMonth();
        $tanggalAkhir = Carbon::parse($bulan . '-01')->endOfMonth();

        // ✅ Filter berdasarkan tahun ajaran aktif
        $queryAll = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->orderBy('tanggal', 'desc')
            ->orderBy('sesi');

        $presensisAll = $queryAll->get();

        // Statistik selalu dari semua data (tanpa filter status)
        $statistik = [
            'total' => $presensisAll->count(),
            'hadir' => $presensisAll->where('status', 'Hadir')->count(),
            'izin'  => $presensisAll->where('status', 'Izin')->count(),
            'sakit' => $presensisAll->where('status', 'Sakit')->count(),
            'alpha' => $presensisAll->where('status', 'Alpha')->count(),
        ];

        if ($status) {
            $presensisHadir = $status === 'Hadir'
                ? $presensisAll->where('status', 'Hadir')->values()
                : collect();

            $presensisAbsen = in_array($status, ['Izin', 'Sakit', 'Alpha'])
                ? $presensisAll->where('status', $status)->values()
                : collect();
        } else {
            $presensisHadir = $presensisAll->where('status', 'Hadir')->values();
            $presensisAbsen = $presensisAll->whereIn('status', ['Izin', 'Sakit', 'Alpha'])->values();
        }

        return view('santri.presensis.detail', compact(
            'santri',
            'presensisHadir',
            'presensisAbsen',
            'statistik',
            'bulan',
            'status',
            'tahunAjaranAktif'
        ));
    }

    /**
     * ========================================
     * RAPOT SHOW
     * ========================================
     * Opsi A: setoran filter PER KELAS (dua semester). Filter tanggal tetap jalan.
     * Presensi tetap pakai rentang tanggal (tabel tak punya kelas_id).
     */
    public function rapotShow(Request $request)
    {
        $user = Auth::user();
        $santri = $user->santri()->with(['kelas', 'guru'])->first()
            ?? Santri::where('user_id', $user->id)->with(['kelas', 'guru'])->first();

        if (!$santri) {
            abort(404, 'Data santri tidak ditemukan.');
        }

        // ✅ Ambil tahun ajaran aktif (untuk header + filter presensi)
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ✅ Kelas santri sekarang — filter utama setoran
        $kelasIdSantri = $santri->kelas_id;

        // ✅ Default range = sepanjang setoran kelas ini (dua semester)
        [$defaultMulai, $defaultAkhir] = $this->getDefaultKelasRange($santri);
        $tanggalMulai = $request->input('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = $request->input('tanggal_akhir', $defaultAkhir);

        // ✅ Setoran: filter PER KELAS + rentang tanggal
        $setoransRaw = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc')
            ->get();

        $setorans = $this->groupSetoranByKategoriSurat($setoransRaw);

        // ✅ Presensi: rentang tanggal + tahun ajaran aktif (tak punya kelas_id)
        $presensis = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->with('guru')
            ->orderBy('tanggal', 'desc')
            ->get();

        $targetJuz       = $santri->target_juz ?? 30;
        $totalJuzHafalan = $setoransRaw->where('kategori_setoran', 'Hafalan')->sum('total_juz') ?? 0;
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
            'total_setoran'         => $setoransRaw->count(),
            'rata_nilai'            => round($setoransRaw->avg('nilai') ?? 0, 2),
            'total_juz'             => round($setoransRaw->sum('total_juz') ?? 0, 2),
            'total_juz_hafalan'     => round($totalJuzHafalan, 2),
            'total_kata'            => $setoransRaw->sum('total_kata') ?? 0,
            'hafalan'               => $setoransRaw->where('kategori_setoran', 'Hafalan')->count(),
            'murajaah'              => $setoransRaw->where('kategori_setoran', 'Murajaah')->count(),
            'tilawah'               => $setoransRaw->where('kategori_setoran', 'Tilawah')->count(),
            'target_juz'            => $targetJuz,
            'persentase_target'     => $persentaseTarget,
            // Iqro
            'is_iqro'               => $isIqro,
            'total_halaman_iqro'    => $totalHalamanIqro,
            'total_halaman_jilid'   => $totalHalamanJilid,
            'progress_iqro'         => $progressIqro,
            'target_jilid'          => $santri->target_jilid ?? 1,
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

        return view('santri.rapot.show', compact(
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
     * Mengelompokkan setoran berdasarkan kategori dan surat
     */
    private function groupSetoranByKategoriSurat($setorans)
    {
        $grouped = [];

        foreach ($setorans as $setoran) {
            $kategori = $setoran->kategori_setoran;
            $suratKey = $setoran->surat_start . ($setoran->surat_start != $setoran->surat_end ? ' - ' . $setoran->surat_end : '');

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
            $grouped[$kategori][$suratKey]['ayat_start_min']  = min($grouped[$kategori][$suratKey]['ayat_start_min'], $setoran->ayat_start);
            $grouped[$kategori][$suratKey]['ayat_end_max']    = max($grouped[$kategori][$suratKey]['ayat_end_max'], $setoran->ayat_end);
        }

        foreach ($grouped as $kategori => $surats) {
            foreach ($surats as $suratKey => $data) {
                $grouped[$kategori][$suratKey]['rata_nilai'] = round($data['total_nilai'] / $data['jumlah_setoran'], 1);
            }
        }

        return $grouped;
    }

    /**
     * Download Rapot PDF
     * Opsi A: PDF mengikuti tampilan layar — setoran PER KELAS (dua semester),
     * range default kelas, filter tanggal tetap dihormati bila dikirim.
     */
    public function downloadRapotPdf(Request $request)
    {
        $user = Auth::user();
        $santri = $user->santri()->with(['kelas', 'guru'])->first()
            ?? Santri::where('user_id', $user->id)->with(['kelas', 'guru'])->first();

        if (!$santri) {
            abort(404, 'Data santri tidak ditemukan.');
        }

        // ✅ Ambil tahun ajaran aktif (untuk header + filter presensi)
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ✅ Kelas santri sekarang — filter utama setoran
        $kelasIdSantri = $santri->kelas_id;

        // Jendela unduh PDF: hanya sampai 7 hari setelah tahun ajaran selesai
        $jendela = $this->cekJendelaPdf($tahunAjaranAktif);
        if (!$jendela['boleh']) {
            $batasTxt = $jendela['batas'] ? $jendela['batas']->translatedFormat('d F Y') : '-';
            abort(403, 'Masa unduh PDF rapot sudah berakhir (batas: ' . $batasTxt . '). Statistik tetap dapat dilihat.');
        }

        // ✅ Default range = sepanjang setoran kelas ini (dua semester)
        [$defaultMulai, $defaultAkhir] = $this->getDefaultKelasRange($santri);
        $tanggalMulai = $request->input('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = $request->input('tanggal_akhir', $defaultAkhir);

        // ✅ Setoran: filter PER KELAS + rentang tanggal
        $setoransRaw = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc')
            ->get();

        $setorans = $this->groupSetoranByKategoriSurat($setoransRaw);

        // ✅ Presensi: rentang tanggal + tahun ajaran aktif (tak punya kelas_id)
        $presensis = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->with('guru')
            ->orderBy('tanggal', 'desc')
            ->get();

        $targetJuz       = $santri->target_juz ?? 30;
        $totalJuzHafalan = $setoransRaw->where('kategori_setoran', 'Hafalan')->sum('total_juz') ?? 0;
        $persentaseTarget = $targetJuz > 0
            ? min(100, round(($totalJuzHafalan / $targetJuz) * 100, 1))
            : 0;

        // Iqro untuk PDF (mirror rapotShow)
        $isIqro = ($santri->target_metode?->value ?? 'quran') === 'iqro';
        $totalHalamanIqro  = 0;
        $totalHalamanJilid = 0;
        $progressIqro      = 0;
        if ($isIqro) {
            $targetJilid = (int)($santri->target_jilid ?? 1);
            foreach ($setoransRaw->where('kategori_setoran', 'Hafalan') as $s) {
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
            'total_setoran'         => $setoransRaw->count(),
            'rata_nilai'            => round($setoransRaw->avg('nilai') ?? 0, 2),
            'total_juz'             => round($setoransRaw->sum('total_juz') ?? 0, 2),
            'total_juz_hafalan'     => round($totalJuzHafalan, 2),
            'total_kata'            => $setoransRaw->sum('total_kata') ?? 0,
            'hafalan'               => $setoransRaw->where('kategori_setoran', 'Hafalan')->count(),
            'murajaah'              => $setoransRaw->where('kategori_setoran', 'Murajaah')->count(),
            'tilawah'               => $setoransRaw->where('kategori_setoran', 'Tilawah')->count(),
            'target_juz'            => $targetJuz,
            'persentase_target'     => $persentaseTarget,
            // Iqro
            'is_iqro'               => $isIqro,
            'total_halaman_iqro'    => $totalHalamanIqro,
            'total_halaman_jilid'   => $totalHalamanJilid,
            'progress_iqro'         => $progressIqro,
            'target_jilid'          => $santri->target_jilid ?? 1,
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

        $kelasRapot = $santri->kelas;
        $guruRapot  = $santri->guru;

        $pdf = Pdf::loadView('santri.rapot.pdf', compact(
            'santri',
            'setorans',
            'presensis',
            'statistikSetoran',
            'statistikPresensi',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif',
            'kelasRapot',
            'guruRapot'
        ));

        $pdf->setPaper('A4', 'portrait');

        $isPreview = $request->has('preview') && $request->preview == 1;

        if ($isPreview) {
            return $pdf->stream();
        } else {
            $filename = 'Rapot_' . str_replace(' ', '_', $santri->name) . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        }
    }

    /**
     * Get audio file untuk setoran
     */
    public function getSetoranAudio($id)
    {
        $setoran = Setoran::findOrFail($id);

        $user   = Auth::user();
        $santri = $user->santri()->first() ?? Santri::where('user_id', $user->id)->first();

        if ($setoran->santri_id !== $santri->id) {
            abort(403, 'Unauthorized');
        }

        if (!$setoran->audio_path) {
            abort(404, 'Audio tidak ditemukan');
        }

        $path = storage_path('app/public/' . $setoran->audio_path);

        if (!file_exists($path)) {
            abort(404, 'File audio tidak ditemukan');
        }

        return response()->file($path);
    }

    /**
     * Get PDF file untuk setoran
     */
    public function getSetoranPdf($id)
    {
        $setoran = Setoran::findOrFail($id);

        $user   = Auth::user();
        $santri = $user->santri()->first() ?? Santri::where('user_id', $user->id)->first();

        if ($setoran->santri_id !== $santri->id) {
            abort(403, 'Unauthorized');
        }

        if (!$setoran->koreksi_pdf_path) {
            abort(404, 'PDF tidak ditemukan');
        }

        $path = storage_path('app/public/' . $setoran->koreksi_pdf_path);

        if (!file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan');
        }

        return response()->file($path);
    }

    private function cekJendelaPdf(?TahunAjaran $ta): array
    {
        if (!$ta) return ['boleh' => true, 'batas' => null];
        $selesai = $ta->tanggal_selesai ?? ($ta->tanggal_akhir ?? null);
        if (!$selesai) return ['boleh' => true, 'batas' => null];
        $selesaiC = $selesai instanceof Carbon ? $selesai : Carbon::parse($selesai);
        $batas    = $selesaiC->copy()->addDays(7)->endOfDay();
        return ['boleh' => now()->lessThanOrEqualTo($batas), 'batas' => $batas];
    }
}
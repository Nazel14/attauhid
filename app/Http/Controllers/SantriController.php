<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\Setoran;
use App\Models\Presensi;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class SantriController extends Controller
{
    /* ============================================================
     * HELPER TAHUN AJARAN / SEMESTER
     * Sumber kebenaran = tabel `tahun_ajarans`:
     *   nama            → "2027/2028"
     *   semester        → 1 = Ganjil, 2 = Genap
     *   tanggal_mulai   → awal periode
     *   tanggal_selesai → akhir periode
     * ============================================================ */

    /** Label semester dari kolom `semester` (1/2). */
    private function semesterLabel($semester): string
    {
        return ((int) $semester) === 1 ? 'Ganjil' : 'Genap';
    }

    /** Tanggal akhir tahun ajaran — kolomnya `tanggal_selesai`. */
    private function taTanggalSelesai(TahunAjaran $ta)
    {
        return $ta->tanggal_selesai ?? ($ta->tanggal_akhir ?? null);
    }

    /** Rentang [mulai, akhir] dari satu baris tahun ajaran. */
    private function getRangeTahunAjaran(TahunAjaran $ta): array
    {
        $selesai = $this->taTanggalSelesai($ta);

        return [
            Carbon::parse($ta->tanggal_mulai)->format('Y-m-d'),
            $selesai ? Carbon::parse($selesai)->format('Y-m-d') : now()->format('Y-m-d'),
        ];
    }

    /**
     * Daftar tahun ajaran untuk dropdown filter (dikirim ke React).
     * Terbaru di atas.
     */
    private function tahunAjaranOptions(): array
    {
        return TahunAjaran::orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get()
            ->map(function (TahunAjaran $ta) {
                $selesai = $this->taTanggalSelesai($ta);
                $label   = $this->semesterLabel($ta->semester);

                return [
                    'id'              => $ta->id,
                    'nama'            => $ta->nama,
                    'semester'        => (int) $ta->semester,
                    'semester_label'  => $label,
                    'label'           => $ta->nama . ' — Semester ' . $label,
                    'tanggal_mulai'   => Carbon::parse($ta->tanggal_mulai)->format('Y-m-d'),
                    'tanggal_selesai' => $selesai ? Carbon::parse($selesai)->format('Y-m-d') : null,
                    'is_aktif'        => (bool) $ta->is_aktif,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Rentang tanggal tahun ajaran AKTIF (dipakai rapot).
     * Fallback ke kalender semester kalau data tahun ajaran belum lengkap.
     * @return array [tanggalMulai, tanggalAkhir, label]
     */
    private function getSemesterAktifRange(): array
    {
        $ta = TahunAjaran::getAktif();

        if ($ta && $ta->tanggal_mulai && $this->taTanggalSelesai($ta)) {
            [$mulai, $akhir] = $this->getRangeTahunAjaran($ta);
            return [$mulai, $akhir, 'Semester ' . $this->semesterLabel($ta->semester) . ' ' . $ta->nama];
        }

        // Fallback: kalender (Ganjil = Jul–Des, Genap = Jan–Jun)
        $now = now();
        if ($now->month >= 7) {
            $mulai = $now->copy()->setMonth(7)->startOfMonth();
            $akhir = $now->copy()->setMonth(12)->endOfMonth();
            $label = 'Semester Ganjil';
        } else {
            $mulai = $now->copy()->setMonth(1)->startOfMonth();
            $akhir = $now->copy()->setMonth(6)->endOfMonth();
            $label = 'Semester Genap';
        }

        return [$mulai->format('Y-m-d'), $akhir->format('Y-m-d'), $label];
    }

    /**
     * Default tanggal mulai/akhir berdasarkan KELAS santri sekarang.
     * Mencakup seluruh masa santri di kelas ini, reset saat naik kelas.
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
                $akhirSetoran = Carbon::parse($range->akhir);
                $akhir = now()->gt($akhirSetoran)
                    ? now()->format('Y-m-d')
                    : $akhirSetoran->format('Y-m-d');
                return [$mulai, $akhir];
            }
        }

        // FIX: dulu memanggil getDefaultSemesterRange() yang tidak pernah ada.
        [$mulai, $akhir] = $this->getSemesterAktifRange();
        return [$mulai, $akhir];
    }

    /**
     * Ambil santri milik user yang login (dengan relasi standar + user untuk foto).
     */
    private function resolveSantri($user): ?Santri
    {
        return $user->santri()->with(['kelas', 'guru', 'user'])->first()
            ?? Santri::where('user_id', $user->id)->with(['kelas', 'guru', 'user'])->first();
    }

    /* ============================================================
     * HELPER METODE JILID (Iqro / Aisar)
     * ============================================================ */

    /** Label metode jilid untuk tampilan: "Iqro" / "Aisar" */
    private function metodeJilidLabel(Santri $santri): string
    {
        return $santri->isAisar() ? 'Aisar' : 'Iqro';
    }

    /**
     * Hitung halaman jilid (Iqro/Aisar) yang sudah disetor.
     * - HANYA kategori Hafalan
     * - Dedup halaman (halaman yang diulang tidak dihitung dua kali)
     * - Hanya jilid yang sedang ditargetkan
     */
    private function hitungHalamanJilid(Santri $santri, $setorans): int
    {
        if (! $santri->isJilidBased()) return 0;

        $targetJilid = (int) ($santri->target_jilid ?? 1);
        $label       = $santri->jilidLabelPrefix() . $targetJilid; // "Aisar Jilid 1" / "Iqro Jilid 1"

        $pages = [];
        foreach ($setorans as $s) {
            if ($s->kategori_setoran !== 'Hafalan') continue;
            if (strcasecmp(trim((string) $s->surat_start), $label) !== 0) continue;

            $h1 = (int) $s->ayat_start;
            $h2 = (int) ($s->ayat_end ?: $s->ayat_start);
            if ($h1 < 1 || $h2 < $h1) continue;

            for ($h = $h1; $h <= $h2; $h++) {
                $pages[$h] = true;
            }
        }

        return count($pages);
    }

    /** Total halaman jilid aktif dari config (config/iqro.php atau config/aisar.php) */
    private function totalHalamanJilid(Santri $santri): int
    {
        if (! $santri->isJilidBased()) return 0;

        $cfg   = $santri->jilidConfigKey();               // 'iqro' | 'aisar'
        $jilid = (int) ($santri->target_jilid ?? 1);

        return (int) (config("$cfg.halaman_per_jilid.$jilid") ?? 0);
    }

    /**
     * Dashboard Santri
     */
    public function dashboard()
    {
        $user = Auth::user();
        $santri = $this->resolveSantri($user);

        $tahunAjaranAktif = TahunAjaran::getAktif();

        if (!$santri) {
            return Inertia::render('Santri/Dashboard', [
                'santris'          => [],
                'tahunAjaranAktif' => $tahunAjaranAktif,
                'message'          => 'Data santri tidak ditemukan. Silakan hubungi administrator.',
            ]);
        }

        $tahunAjaranId = $tahunAjaranAktif?->id;
        $kelasIdSantri = $santri->kelas_id;

        // Setoran — filter PER KELAS
        $setorans = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->get();

        $setoranHafalan  = $setorans->where('kategori_setoran', 'Hafalan');
        $setoranMurajaah = $setorans->where('kategori_setoran', 'Murajaah');
        $setoranTilawah  = $setorans->where('kategori_setoran', 'Tilawah');

        // ── Metode jilid (Iqro / Aisar) ──
        $isIqro            = $santri->isJilidBased();
        $totalHalamanIqro  = $this->hitungHalamanJilid($santri, $setorans);
        $totalHalamanJilid = $this->totalHalamanJilid($santri);

        $santri->stats_setoran = (object)[
            'total_setoran'         => $setorans->count(),
            'rata_nilai'            => round($setorans->avg('nilai') ?? 0, 1),
            'total_juz'             => round($setorans->sum('total_juz') ?? 0, 2),
            'total_juz_hafalan'     => round($setoranHafalan->sum('total_juz') ?? 0, 2),
            'setoran_terakhir'      => $setorans->max('tanggal'),
            'rata_nilai_hafalan'    => round($setoranHafalan->avg('nilai') ?? 0, 1),
            'rata_nilai_murajaah'   => round($setoranMurajaah->avg('nilai') ?? 0, 1),
            'rata_nilai_tilawah'    => round($setoranTilawah->avg('nilai') ?? 0, 1),
            'is_iqro'               => $isIqro,
            'total_halaman_iqro'    => $totalHalamanIqro,
            'total_halaman_jilid'   => $totalHalamanJilid,
            'iqro_persen_diketahui' => $totalHalamanJilid > 0,
            'target_jilid'          => $santri->target_jilid ?? 1,
            'metode_label'          => $this->metodeJilidLabel($santri),
        ];

        $presensis = Presensi::where('santri_id', $santri->id)
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->get();

        $santri->stats_presensi = (object)[
            'total_hari'  => $presensis->count(),
            'total_hadir' => $presensis->where('status', 'Hadir')->count(),
            'total_izin'  => $presensis->where('status', 'Izin')->count(),
            'total_sakit' => $presensis->where('status', 'Sakit')->count(),
            'total_alpha' => $presensis->where('status', 'Alpha')->count(),
        ];

        $santri->persentase_kehadiran = $santri->stats_presensi->total_hari > 0
            ? round(($santri->stats_presensi->total_hadir / $santri->stats_presensi->total_hari) * 100, 1)
            : 0;

        return Inertia::render('Santri/Dashboard', [
            'santris'          => [$santri],
            'tahunAjaranAktif' => $tahunAjaranAktif,
        ]);
    }

    /**
     * DETAIL SETORAN
     * Filter: pilih TAHUN AJARAN + SEMESTER (dari tabel tahun_ajarans),
     * atau rentang tanggal manual bila tidak ada yang dipilih.
     */
    public function setorans(Request $request)
    {
        $user   = Auth::user();
        $santri = $this->resolveSantri($user);
        if (!$santri) abort(404, 'Data santri tidak ditemukan.');

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $kelasIdSantri    = $santri->kelas_id;
        $kategori         = $request->input('kategori');

        // Tahun ajaran terpilih (kosong = pakai rentang tanggal manual)
        $tahunAjaranId = $request->input('tahun_ajaran_id', '');
        $taPilih       = $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : null;

        [$defaultMulai, $defaultAkhir] = $this->getDefaultKelasRange($santri);
        $tanggalMulai = $request->input('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = $request->input('tanggal_akhir', $defaultAkhir);

        // Tahun ajaran dipilih → rentang tanggal mengikuti periode tahun ajaran itu
        if ($taPilih) {
            [$tanggalMulai, $tanggalAkhir] = $this->getRangeTahunAjaran($taPilih);
        }

        $query = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru');

        if ($kategori) {
            $query->where('kategori_setoran', $kategori);
        }

        $setoransAll = $query->orderBy('tanggal', 'desc')->get();

        $setoranHafalan  = $setoransAll->where('kategori_setoran', 'Hafalan')->values();
        $setoranMurajaah = $setoransAll->where('kategori_setoran', 'Murajaah')->values();
        $setoranTilawah  = $setoransAll->where('kategori_setoran', 'Tilawah')->values();

        $statistik = [
            'total_setoran' => $setoransAll->count(),
            'rata_nilai'    => round($setoransAll->avg('nilai') ?? 0, 2),
            'total_juz'     => round($setoransAll->sum('total_juz') ?? 0, 2),
        ];

        // ── Metode jilid (Iqro / Aisar) ──
        $isIqro           = $santri->isJilidBased();
        $totalHalamanIqro = $this->hitungHalamanJilid($santri, $setoransAll);

        return Inertia::render('Santri/Setorans/Detail', [
            'santri'           => $santri,
            'setoranHafalan'   => $setoranHafalan,
            'setoranMurajaah'  => $setoranMurajaah,
            'setoranTilawah'   => $setoranTilawah,
            'statistik'        => $statistik,
            'kategori'         => $kategori,
            'tanggalMulai'     => $tanggalMulai,
            'tanggalAkhir'     => $tanggalAkhir,
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'isIqro'           => $isIqro,
            'totalHalamanIqro' => $totalHalamanIqro,
            'metodeLabel'      => $this->metodeJilidLabel($santri),

            // Filter tahun ajaran / semester
            'tahunAjaranList'  => $this->tahunAjaranOptions(),
            'tahunAjaranId'    => $taPilih ? (string) $taPilih->id : '',
        ]);
    }

    /**
     * DETAIL PRESENSI
     * Filter: pilih TAHUN AJARAN + SEMESTER (dari tabel tahun_ajarans),
     * atau per bulan bila tidak ada yang dipilih.
     */
    public function presensis(Request $request)
    {
        $user   = Auth::user();
        $santri = $this->resolveSantri($user);
        if (!$santri) abort(404, 'Data santri tidak ditemukan.');

        $tahunAjaranAktif = TahunAjaran::getAktif();

        $bulan  = $request->input('bulan', now()->format('Y-m'));
        $status = $request->input('status');

        $tahunAjaranId = $request->input('tahun_ajaran_id', '');
        $taPilih       = $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : null;

        if ($taPilih) {
            // Periode = rentang tahun ajaran terpilih, sekaligus dikunci ke FK-nya
            [$m, $a] = $this->getRangeTahunAjaran($taPilih);
            $tanggalMulai  = Carbon::parse($m)->startOfDay();
            $tanggalAkhir  = Carbon::parse($a)->endOfDay();
            $filterTahunId = $taPilih->id;
        } else {
            // Per bulan, dibatasi tahun ajaran aktif
            $tanggalMulai  = Carbon::parse($bulan . '-01')->startOfMonth();
            $tanggalAkhir  = Carbon::parse($bulan . '-01')->endOfMonth();
            $filterTahunId = $tahunAjaranAktif?->id;
        }

        $presensisAll = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($filterTahunId, fn($q) => $q->where('tahun_ajaran_id', $filterTahunId))
            ->orderBy('tanggal', 'desc')
            ->orderBy('sesi')
            ->get();

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

        return Inertia::render('Santri/Presensis/Detail', [
            'santri'           => $santri,
            'presensisHadir'   => $presensisHadir,
            'presensisAbsen'   => $presensisAbsen,
            'statistik'        => $statistik,
            'bulan'            => $bulan,
            'status'           => $status,
            'tahunAjaranAktif' => $tahunAjaranAktif,

            // Filter tahun ajaran / semester
            'tahunAjaranList'  => $this->tahunAjaranOptions(),
            'tahunAjaranId'    => $taPilih ? (string) $taPilih->id : '',
            'periodeMulai'     => $tanggalMulai->format('Y-m-d'),
            'periodeAkhir'     => $tanggalAkhir->format('Y-m-d'),
        ]);
    }

    /**
     * RAPOT SHOW — otomatis pakai TAHUN AJARAN AKTIF.
     */
    public function rapotShow(Request $request)
    {
        $user   = Auth::user();
        $santri = $this->resolveSantri($user);
        if (!$santri) abort(404, 'Data santri tidak ditemukan.');

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;
        $kelasIdSantri    = $santri->kelas_id;

        [$tanggalMulai, $tanggalAkhir, $semesterLabel] = $this->getSemesterAktifRange();

        $setoransRaw = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('guru')
            ->orderBy('tanggal', 'desc')
            ->get();

        $setorans = $this->groupSetoranByKategoriSurat($setoransRaw);

        $presensis = Presensi::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->with('guru')
            ->orderBy('tanggal', 'desc')
            ->get();

        $statistikSetoran  = $this->buildStatistikSetoran($santri, $setoransRaw);
        $statistikPresensi = $this->buildStatistikPresensi($presensis);

        return Inertia::render('Santri/Rapot/Show', [
            'santri'            => $santri,
            'setorans'          => $setorans,
            'presensis'         => $presensis,
            'statistikSetoran'  => $statistikSetoran,
            'statistikPresensi' => $statistikPresensi,
            'tanggalMulai'      => $tanggalMulai,
            'tanggalAkhir'      => $tanggalAkhir,
            'semesterLabel'     => $semesterLabel,
            'tahunAjaranAktif'  => $tahunAjaranAktif,
        ]);
    }

    /**
     * Statistik setoran (dipakai rapotShow & downloadRapotPdf).
     */
    private function buildStatistikSetoran(Santri $santri, $setoransRaw): array
    {
        $targetJuz        = $santri->target_juz ?? 30;
        $totalJuzHafalan  = $setoransRaw->where('kategori_setoran', 'Hafalan')->sum('total_juz') ?? 0;
        $persentaseTarget = $targetJuz > 0 ? min(100, round(($totalJuzHafalan / $targetJuz) * 100, 1)) : 0;

        // ── Metode jilid (Iqro / Aisar) ──
        $isIqro            = $santri->isJilidBased();
        $totalHalamanIqro  = $this->hitungHalamanJilid($santri, $setoransRaw);
        $totalHalamanJilid = $this->totalHalamanJilid($santri);
        $progressIqro      = $totalHalamanJilid > 0
            ? min(100, round(($totalHalamanIqro / $totalHalamanJilid) * 100, 1))
            : 0;

        return [
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
            'is_iqro'               => $isIqro,
            'total_halaman_iqro'    => $totalHalamanIqro,
            'total_halaman_jilid'   => $totalHalamanJilid,
            'progress_iqro'         => $progressIqro,
            'target_jilid'          => $santri->target_jilid ?? 1,
            'iqro_persen_diketahui' => $totalHalamanJilid > 0,
            'metode_label'          => $this->metodeJilidLabel($santri),
        ];
    }

    /**
     * Statistik presensi (dipakai rapotShow & downloadRapotPdf).
     */
    private function buildStatistikPresensi($presensis): array
    {
        $stat = [
            'total_hari' => $presensis->count(),
            'hadir'      => $presensis->where('status', 'Hadir')->count(),
            'izin'       => $presensis->where('status', 'Izin')->count(),
            'sakit'      => $presensis->where('status', 'Sakit')->count(),
            'alpha'      => $presensis->where('status', 'Alpha')->count(),
        ];
        $stat['persentase_kehadiran'] = $stat['total_hari'] > 0
            ? round(($stat['hadir'] / $stat['total_hari']) * 100, 1)
            : 0;
        return $stat;
    }

    /**
     * Mengelompokkan setoran berdasarkan kategori dan surat.
     */
    private function groupSetoranByKategoriSurat($setorans)
    {
        $grouped = [];

        foreach ($setorans as $setoran) {
            $kategori = $setoran->kategori_setoran;
            $suratKey = $setoran->surat_start . ($setoran->surat_start != $setoran->surat_end ? ' - ' . $setoran->surat_end : '');

            if (!isset($grouped[$kategori])) $grouped[$kategori] = [];

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

    public function downloadRapotPdf(Request $request)
    {
        $user   = Auth::user();
        $santri = $this->resolveSantri($user);
        if (!$santri) abort(404, 'Data santri tidak ditemukan.');

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $isPreview = $request->boolean('preview');

        // Jendela hanya batasi DOWNLOAD, bukan preview
        if (!$isPreview) {
            $jendela = $this->cekJendelaPdf($tahunAjaranAktif);
            if (!$jendela['boleh']) {
                $batasTxt = $jendela['batas'] ? $jendela['batas']->translatedFormat('d F Y') : '-';
                abort(403, 'Masa unduh PDF rapot sudah berakhir (batas: ' . $batasTxt . '). Statistik tetap dapat dilihat.');
            }
        }

        [$tanggalMulai, $tanggalAkhir, $semesterLabel] = $this->getSemesterAktifRange();

        // Versi data (query ringan) → cache otomatis kadaluarsa saat ada perubahan
        // Metode/jilid ikut masuk versi supaya ganti metode (iqro ⇄ aisar) tidak menyajikan PDF lama.
        $ver = Setoran::where('santri_id', $santri->id)->max('updated_at')
            . '|' . Presensi::where('santri_id', $santri->id)->max('updated_at')
            . '|' . ($santri->target_metode?->value ?? 'quran')
            . '|' . ($santri->target_jilid ?? 0)
            . '|' . filemtime(resource_path('views/santri/rapot/pdf.blade.php'));
        $cacheKey = 'rapot_pdf:' . $santri->id . ':' . md5($tanggalMulai . $tanggalAkhir . $ver);

        // Render berat HANYA saat cache miss
        $pdfBytes = Cache::store('file')->remember($cacheKey, now()->addHours(6), function () use (
            $santri, $tahunAjaranAktif, $tanggalMulai, $tanggalAkhir, $semesterLabel
        ) {
            $tahunAjaranId = $tahunAjaranAktif?->id;
            $kelasIdSantri = $santri->kelas_id;

            $setoransRaw = Setoran::where('santri_id', $santri->id)
                ->whereNotNull('kategori_setoran')
                ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                ->with('guru')->orderBy('tanggal', 'desc')->get();

            $setorans = $this->groupSetoranByKategoriSurat($setoransRaw);

            $presensis = Presensi::where('santri_id', $santri->id)
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
                ->with('guru')->orderBy('tanggal', 'desc')->get();

            $statistikSetoran  = $this->buildStatistikSetoran($santri, $setoransRaw);
            $statistikPresensi = $this->buildStatistikPresensi($presensis);
            $kelasRapot = $santri->kelas;
            $guruRapot  = $santri->guru;

            $pdf = Pdf::setOptions([
                'isFontSubsettingEnabled' => true,   // output lebih ramping
                'isHtml5ParserEnabled'    => true,
                'dpi'                     => 96,      // render lebih cepat
                'defaultMediaType'        => 'print',
                // 'isRemoteEnabled'      => false,   // aktifkan kalau semua gambar sudah base64 lokal
            ])->loadView('santri.rapot.pdf', compact(
                'santri', 'setorans', 'presensis',
                'statistikSetoran', 'statistikPresensi',
                'tanggalMulai', 'tanggalAkhir', 'semesterLabel',
                'tahunAjaranAktif', 'kelasRapot', 'guruRapot'
            ));
            $pdf->setPaper('A4', 'portrait');

            return $pdf->output();
        });

        if ($isPreview) {
            return response($pdfBytes, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="rapot-preview.pdf"',
            ]);
        }

        $filename = 'Rapot_' . str_replace(' ', '_', $santri->name) . '_' . date('Y-m-d') . '.pdf';
        return response($pdfBytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Audio setoran.
     */
    public function getSetoranAudio($id)
    {
        $setoran = Setoran::findOrFail($id);
        $user    = Auth::user();
        $santri  = $user->santri()->first() ?? Santri::where('user_id', $user->id)->first();

        if ($setoran->santri_id !== $santri->id) abort(403, 'Unauthorized');
        if (!$setoran->audio_path) abort(404, 'Audio tidak ditemukan');

        $path = storage_path('app/public/' . $setoran->audio_path);
        if (!file_exists($path)) abort(404, 'File audio tidak ditemukan');

        return response()->file($path);
    }

    /**
     * PDF koreksi setoran.
     */
    public function getSetoranPdf($id)
    {
        $setoran = Setoran::findOrFail($id);
        $user    = Auth::user();
        $santri  = $user->santri()->first() ?? Santri::where('user_id', $user->id)->first();

        if ($setoran->santri_id !== $santri->id) abort(403, 'Unauthorized');
        if (!$setoran->koreksi_pdf_path) abort(404, 'PDF tidak ditemukan');

        $path = storage_path('app/public/' . $setoran->koreksi_pdf_path);
        if (!file_exists($path)) abort(404, 'File PDF tidak ditemukan');

        return response()->file($path);
    }

    private function cekJendelaPdf(?TahunAjaran $ta): array
    {
        if (!$ta) return ['boleh' => true, 'batas' => null];
        $selesai = $this->taTanggalSelesai($ta);
        if (!$selesai) return ['boleh' => true, 'batas' => null];
        $selesaiC = $selesai instanceof Carbon ? $selesai : Carbon::parse($selesai);
        $batas    = $selesaiC->copy()->addDays(7)->endOfDay();
        return ['boleh' => now()->lessThanOrEqualTo($batas), 'batas' => $batas];
    }
}
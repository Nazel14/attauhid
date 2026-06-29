<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Setoran;
use App\Models\Presensi;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\TahunAjaran;
use App\Models\SantriKelasHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RapotController extends Controller
{
    /**
     * Default tanggal mulai & akhir berdasarkan TahunAjaran aktif.
     * Dipakai sebagai dasar rentang PRESENSI (presensi tak punya kolom kelas_id).
     */
    private function getDefaultSemesterRange(): array
    {
        $tahunAjaran = TahunAjaran::getAktif();

        if ($tahunAjaran) {
            $tanggalMulai   = Carbon::parse($tahunAjaran->tanggal_mulai)->format('Y-m-d');
            $tanggalAkhirTa = Carbon::parse($tahunAjaran->tanggal_selesai);
            $tanggalAkhir   = now()->lt($tanggalAkhirTa)
                ? now()->format('Y-m-d')
                : $tanggalAkhirTa->format('Y-m-d');

            return [$tanggalMulai, $tanggalAkhir];
        }

        $bulan = now()->month;
        if ($bulan <= 6) {
            return [now()->startOfYear()->format('Y-m-d'), now()->format('Y-m-d')];
        }
        return [now()->setMonth(7)->startOfMonth()->format('Y-m-d'), now()->format('Y-m-d')];
    }

    /**
     * Tentukan akses rapot santri & konteks kelas.
     * - Santri masih di kelas guru  → akses AKTIF (kelas = kelas sekarang).
     * - Santri pernah di kelas guru → akses HISTORI (kelas = kelas lama).
     * Return null jika tidak berwenang.
     */
    private function resolveAksesRapot(Guru $guru, Santri $santri): ?array
    {
        $kelasIdsGuru = Kelas::where('guru_id', $guru->id)->pluck('id');
        if ($kelasIdsGuru->isEmpty()) return null;

        // Akses aktif
        if ($kelasIdsGuru->contains($santri->kelas_id)) {
            return [
                'kelas'       => Kelas::find($santri->kelas_id),
                'tahunAjaran' => TahunAjaran::getAktif(),
                'isHistori'   => false,
                'rangeMulai'  => null,
                'rangeAkhir'  => null,
            ];
        }

        // Akses histori (sudah pindah)
        $histori = SantriKelasHistory::where('santri_id', $santri->id)
            ->whereIn('kelas_id', $kelasIdsGuru)
            ->orderByDesc('tanggal_masuk')
            ->first();

        if ($histori) {
            $ta = $histori->tahun_ajaran_id ? TahunAjaran::find($histori->tahun_ajaran_id) : null;
            return [
                'kelas'       => Kelas::find($histori->kelas_id),
                'tahunAjaran' => $ta ?: TahunAjaran::getAktif(),
                'isHistori'   => true,
                'rangeMulai'  => $histori->tanggal_masuk ?? now()->startOfYear()->toDateString(),
                'rangeAkhir'  => $histori->tanggal_keluar ?? now()->toDateString(),
            ];
        }

        return null;
    }

    /**
     * Cek apakah PDF rapot masih boleh diunduh.
     * Jendela unduh = sampai 7 hari setelah tanggal_selesai tahun ajaran (konteks rapot).
     */
    private function cekJendelaPdf(?TahunAjaran $ta): array
    {
        if (!$ta) {
            return ['boleh' => true, 'batas' => null];
        }

        $selesai = $ta->tanggal_selesai ?? ($ta->tanggal_akhir ?? null);
        if (!$selesai) {
            return ['boleh' => true, 'batas' => null];
        }

        $selesaiC = $selesai instanceof Carbon ? $selesai : Carbon::parse($selesai);
        $batas    = $selesaiC->copy()->addDays(7)->endOfDay();

        return ['boleh' => now()->lessThanOrEqualTo($batas), 'batas' => $batas];
    }

    /**
     * Hitung jumlah halaman Iqro UNIK (dedup) dari koleksi setoran (sudah ter-scope kelas),
     * untuk satu jilid target.
     */
    private function hitungHalamanIqroUnik($setoransHafalan, int $targetJilid): int
    {
        $pages = [];
        foreach ($setoransHafalan as $s) {
            if (!preg_match('/^Iqro Jilid (\d+)/i', (string) $s->surat_start, $mj)) continue;
            if ((int) $mj[1] !== $targetJilid) continue;
            $h1 = (int) $s->ayat_start;
            $h2 = (int) ($s->ayat_end ?: $s->ayat_start);
            if ($h1 <= 0) continue;
            if ($h2 < $h1) { [$h1, $h2] = [$h2, $h1]; }
            for ($p = $h1; $p <= $h2; $p++) $pages[$p] = true;
        }
        return count($pages);
    }

    /**
     * Daftar santri untuk dipilih laporannya.
     * Progress dihitung per KELAS (kelas_id) — kebal-tanggal, reset otomatis saat naik kelas,
     * akumulatif lintas semester selama di kelas yang sama. Konsisten dengan Dashboard & Setoran.
     */
    public function index()
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $kelas = $guru ? Kelas::where('guru_id', $guru->id)->first() : null;

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // Jendela kenaikan: HANYA semester genap (2) & mulai 1 bulan sebelum semester berakhir
        $bolehNaik     = false;
        $tglMulaiNaik  = null;
        $alasanTutup   = null;
        if ($tahunAjaranAktif) {
            $isGenap = (int) $tahunAjaranAktif->semester === 2;

            if (!$isGenap) {
                $alasanTutup = 'Kenaikan kelas hanya dilakukan pada semester genap (akhir tahun ajaran).';
            } else {
                $selesai = $tahunAjaranAktif->tanggal_selesai ?? ($tahunAjaranAktif->tanggal_akhir ?? null);
                if ($selesai) {
                    $selesaiC     = $selesai instanceof Carbon ? $selesai : Carbon::parse($selesai);
                    $tglMulaiNaik = $selesaiC->copy()->subMonth();
                    $bolehNaik    = now()->greaterThanOrEqualTo($tglMulaiNaik);
                } else {
                    $bolehNaik = true;
                }
            }
        }

        $kelasTujuanList = $kelas
            ? Kelas::with('guru')->where('id', '!=', $kelas->id)->orderBy('name')->get()
            : collect();

        $sudahNaikIds = ($kelas && $tahunAjaranId)
            ? \App\Models\KenaikanUsulan::where('tahun_ajaran_id', $tahunAjaranId)
                ->where('status', 'disetujui')
                ->pluck('santri_id')
                ->map(fn($v) => (string) $v)
                ->all()
            : [];

        $santriList = $kelas
            ? Santri::with('user')->where('kelas_id', $kelas->id)->orderBy('name')->get()
            : collect();

        // Progress per KELAS (kelas_id). Target tetap level-kelas (per TA / fallback target_juz).
        $santriList->each(function ($santri) use ($tahunAjaranAktif, $tahunAjaranId) {
            $kelasId = $santri->kelas_id;
            $isIqro  = ($santri->target_metode?->value ?? 'quran') === 'iqro';
            $santri->is_iqro = $isIqro;

            if ($isIqro) {
                $targetJilid   = (int)($santri->target_jilid ?? 1);
                $totalHalaman  = count($santri->halamanIqroTersetorKelas($targetJilid, $kelasId));
                $totalHalJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);

                $santri->hafalan_total_juz     = 0;
                $santri->total_halaman_iqro    = $totalHalaman;
                $santri->total_halaman_jilid   = $totalHalJilid;
                $santri->iqro_persen_diketahui = $totalHalJilid > 0;
                $santri->target_display        = 'Jilid ' . $targetJilid;
                $santri->hafalan_persen        = $totalHalJilid > 0
                    ? min(100, round(($totalHalaman / $totalHalJilid) * 100, 1))
                    : 0;
            } else {
                $targetJuz = $tahunAjaranAktif
                    ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?: ($santri->target_juz > 0 ? $santri->target_juz : 30))
                    : (($santri->target_juz ?? 0) > 0 ? $santri->target_juz : 30);

                // Akumulatif per kelas (kebal-tanggal), TANPA filter tahun ajaran
                $totalJuz = Setoran::where('santri_id', $santri->id)
                    ->where('kelas_id', $kelasId)
                    ->where('kategori_setoran', 'Hafalan')
                    ->sum('total_juz') ?? 0;

                $santri->hafalan_total_juz     = round($totalJuz, 2);
                $santri->total_halaman_iqro    = 0;
                $santri->total_halaman_jilid   = 0;
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
            'tahunAjaranAktif',
            'bolehNaik',
            'tglMulaiNaik',
            'kelasTujuanList',
            'sudahNaikIds',
            'alasanTutup'
        ));
    }

    /**
     * Halaman utama Riwayat Santri: rekap per TAHUN AJARAN.
     */
    public function riwayatSantri()
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $kelas = $guru ? Kelas::where('guru_id', $guru->id)->first() : null;

        $ringkasan = collect();
        if ($guru) {
            $kelasIdsGuru = Kelas::where('guru_id', $guru->id)->pluck('id');
            if ($kelasIdsGuru->isNotEmpty()) {
                $histori = SantriKelasHistory::whereIn('kelas_id', $kelasIdsGuru)
                    ->whereNotNull('tanggal_keluar')
                    ->get();

                $taIds = $histori->pluck('tahun_ajaran_id')->filter()->unique()->all();
                $taMap = $taIds
                    ? TahunAjaran::whereIn('id', $taIds)->get()->keyBy('id')
                    : collect();

                $ringkasan = $histori
                    ->groupBy('tahun_ajaran_id')
                    ->map(function ($items, $taId) use ($taMap) {
                        $ta = $taId ? ($taMap[$taId] ?? null) : null;
                        return [
                            'tahun_ajaran_id' => $taId ?: null,
                            'nama'            => $ta->nama ?? 'Tanpa Tahun Ajaran',
                            'semester'        => $ta->semester ?? null,
                            'jumlah'          => $items->unique('santri_id')->count(),
                            'terakhir'        => $items->max('tanggal_keluar'),
                        ];
                    })
                    ->sortByDesc('terakhir')
                    ->values();
            }
        }

        return view('guru.rapot.riwayat', compact('guru', 'kelas', 'ringkasan'));
    }

    /**
     * Detail Riwayat Santri untuk SATU tahun ajaran.
     */
    public function detailRiwayatSantri($tahunAjaranId)
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        if (!$guru) abort(403, 'Data guru tidak ditemukan.');

        $kelasIdsGuru = Kelas::where('guru_id', $guru->id)->pluck('id');

        $tahunAjaran = TahunAjaran::find($tahunAjaranId);

        $santriAlumni = SantriKelasHistory::with(['santri.user', 'santri.kelas'])
            ->whereIn('kelas_id', $kelasIdsGuru)
            ->whereNotNull('tanggal_keluar')
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->orderByDesc('tanggal_keluar')
            ->get()
            ->filter(fn($h) => $h->santri)
            ->unique('santri_id')
            ->values();

        return view('guru.rapot.riwayat-detail', compact('guru', 'tahunAjaran', 'santriAlumni'));
    }

    /**
     * Rentang tanggal untuk PRESENSI (presensi tak punya kelas_id).
     * - HISTORI → terkunci ke periode kelas lama.
     * - AKTIF   → sejak masuk kelas (floor) s/d hari ini; guru boleh mempersempit.
     */
    private function resolveRentangPresensi(Request $request, Santri $santri, array $akses): array
    {
        if ($akses['isHistori']) {
            return [$akses['rangeMulai'], $akses['rangeAkhir']];
        }

        $floorMasuk = $santri->getTanggalMasukKelasAktif();
        $tanggalMulai = $request->input('tanggal_mulai', $floorMasuk);
        $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());

        return [$tanggalMulai, $tanggalAkhir];
    }

    private function terapkanRentang($query, ?string $mulai, ?string $akhir)
    {
        if (!empty($mulai)) $query->where('tanggal', '>=', $mulai);
        if (!empty($akhir)) $query->where('tanggal', '<=', $akhir);
        return $query;
    }

    /**
     * Detail rapot satu santri
     */
    public function show(Request $request, Santri $santri)
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $akses = $guru ? $this->resolveAksesRapot($guru, $santri) : null;

        if (!$akses) {
            abort(403, 'Anda tidak berwenang mengakses rapot santri ini.');
        }

        $santri->load(['kelas', 'guru', 'user']);

        $isHistori        = $akses['isHistori'];
        $kelasKonteks     = $akses['kelas'];
        $kelasRapotId     = $akses['kelas']?->id;
        $tahunAjaranAktif = $akses['tahunAjaran'];
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // Rentang tanggal (untuk presensi & form filter)
        [$tanggalMulai, $tanggalAkhir] = $this->resolveRentangPresensi($request, $santri, $akses);

        // Setoran — scoped per KELAS (kebal-tanggal). Filter tanggal hanya untuk penyempitan manual.
        $setoranQuery = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasRapotId, fn($q) => $q->where('kelas_id', $kelasRapotId))
            ->with('guru')
            ->orderBy('tanggal', 'desc');
        if ($request->filled('tanggal_mulai') || $request->filled('tanggal_akhir')) {
            $this->terapkanRentang($setoranQuery, $tanggalMulai, $tanggalAkhir);
        }
        $setoransRaw = $setoranQuery->get();
        $setorans    = $this->groupSetoranByKategoriSurat($setoransRaw);

        // Presensi — berdasarkan rentang tanggal (presensi tak punya kelas_id)
        $presensiQuery = Presensi::where('santri_id', $santri->id)
            ->with('guru')
            ->orderBy('tanggal', 'desc');
        $this->terapkanRentang($presensiQuery, $tanggalMulai, $tanggalAkhir);
        $presensis = $presensiQuery->get();

        // Target level-kelas
        $targetJuz = $tahunAjaranAktif
            ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?: ($santri->target_juz ?? 30))
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
            $totalHalamanIqro  = $this->hitungHalamanIqroUnik(
                $setoransRaw->where('kategori_setoran', 'Hafalan'),
                $targetJilid
            );
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

        $jendela  = $this->cekJendelaPdf($tahunAjaranAktif);
        $pdfBoleh = $jendela['boleh'];
        $pdfBatas = $jendela['batas'];

        return view('guru.rapot.show', compact(
            'santri',
            'setorans',
            'presensis',
            'statistikSetoran',
            'statistikPresensi',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif',
            'isHistori',
            'kelasKonteks',
            'pdfBoleh',
            'pdfBatas'
        ));
    }

    /**
     * Download / Preview PDF rapot santri
     */
    public function downloadPdf(Request $request, Santri $santri)
    {
        $guru  = Guru::where('user_id', Auth::id())->first();
        $akses = $guru ? $this->resolveAksesRapot($guru, $santri) : null;

        if (!$akses) {
            abort(403, 'Anda tidak berwenang mengakses rapot santri ini.');
        }

        $santri->load(['kelas', 'guru', 'user']);

        $kelasRapotId     = $akses['kelas']?->id;
        $tahunAjaranAktif = $akses['tahunAjaran'];
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // Jendela unduh PDF: hanya sampai 7 hari setelah tahun ajaran selesai
        $jendela = $this->cekJendelaPdf($tahunAjaranAktif);
        if (!$jendela['boleh']) {
            $batasTxt = $jendela['batas'] ? $jendela['batas']->translatedFormat('d F Y') : '-';
            abort(403, 'Masa unduh PDF rapot sudah berakhir (batas: ' . $batasTxt . '). Statistik tetap dapat dilihat di halaman rapot.');
        }

        [$tanggalMulai, $tanggalAkhir] = $this->resolveRentangPresensi($request, $santri, $akses);

        // Setoran — scoped per KELAS
        $setoranQuery = Setoran::where('santri_id', $santri->id)
            ->whereNotNull('kategori_setoran')
            ->when($kelasRapotId, fn($q) => $q->where('kelas_id', $kelasRapotId))
            ->with('guru')
            ->orderBy('tanggal', 'desc');
        if ($request->filled('tanggal_mulai') || $request->filled('tanggal_akhir')) {
            $this->terapkanRentang($setoranQuery, $tanggalMulai, $tanggalAkhir);
        }
        $setoransRaw = $setoranQuery->get();
        $setorans    = $this->groupSetoranByKategoriSurat($setoransRaw);

        // Presensi — berdasarkan rentang tanggal
        $presensiQuery = Presensi::where('santri_id', $santri->id)
            ->with('guru')
            ->orderBy('tanggal', 'desc');
        $this->terapkanRentang($presensiQuery, $tanggalMulai, $tanggalAkhir);
        $presensis = $presensiQuery->get();

        $targetJuz = $tahunAjaranAktif
            ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?: ($santri->target_juz ?? 30))
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

        $kelasRapot = $akses['kelas'] ?? $santri->kelas;
        $guruRapot  = ($kelasRapot && $kelasRapot->guru_id)
            ? Guru::find($kelasRapot->guru_id)
            : $santri->guru;

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

        if ($request->boolean('preview')) {
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
            $grouped[$kategori][$suratKey]['total_nilai'] += $setoran->nilai;
            $grouped[$kategori][$suratKey]['nilai_list'][] = $setoran->nilai;
            $grouped[$kategori][$suratKey]['ayat_start_min'] = min(
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
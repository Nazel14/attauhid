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
use App\Models\RapotAkses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class RapotController extends Controller
{
    use \App\Traits\UsesKelasAktif;

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
     * ============================================================
     * SUMBER TUNGGAL RIWAYAT SANTRI (ALUMNI KELAS GURU)
     * ============================================================
     *
     * PENTING: `tanggal_keluar` TIDAK cukup untuk menentukan santri "keluar
     * dari kelas". tutupSemester() mengisi tanggal_keluar pada SEMUA baris
     * history setiap kali semester ditutup — termasuk untuk santri yang
     * sebenarnya masih duduk di kelas yang sama. Kalau hanya mengandalkan
     * whereNotNull('tanggal_keluar'), guru kelas 8A ikut melihat santri-nya
     * sendiri sebagai "riwayat", padahal mereka belum pindah ke mana-mana.
     *
     * Kriteria yang benar: baris history dianggap riwayat bila santri
     * SEKARANG sudah tidak berada di kelas tersebut (santri.kelas_id berbeda
     * dari history.kelas_id). Lalu diambil satu baris TERBARU per santri
     * agar tidak dobel di beberapa tahun ajaran.
     *
     * @return \Illuminate\Support\Collection<SantriKelasHistory>
     */
    private function historiAlumniGuru(Guru $guru)
    {
        // Riwayat di-scope ke KELAS AKTIF saja — guru yang sedang membuka 8A
        // tidak boleh melihat alumni 6A.
        $kelasIdsGuru = collect(array_filter([$this->kelasAktifId()]));
        if ($kelasIdsGuru->isEmpty()) {
            return collect();
        }

        return SantriKelasHistory::with(['santri.user', 'santri.kelas'])
            ->whereIn('kelas_id', $kelasIdsGuru)
            ->whereNotNull('tanggal_keluar')
            ->orderByDesc('tanggal_keluar')
            ->orderByDesc('id')
            ->get()
            // Hanya santri yang BENAR-BENAR sudah tidak di kelas itu lagi.
            ->filter(fn ($h) => $h->santri
                && (int) $h->santri->kelas_id !== (int) $h->kelas_id)
            // Sudah terurut desc → unique() menyisakan kepindahan TERBARU per santri.
            ->unique('santri_id')
            ->values();
    }

    /**
     * Tentukan akses rapot santri & konteks kelas.
     * - Santri masih di kelas guru  → akses AKTIF (kelas = kelas sekarang).
     * - Santri pernah di kelas guru → akses HISTORI (kelas = kelas lama).
     * Return null jika tidak berwenang.
     */
    private function resolveAksesRapot(Guru $guru, Santri $santri): ?array
    {
        $kelasIdsGuru = collect(array_filter([$this->kelasAktifId()]));
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

        // Akses histori (sudah pindah) — hanya kelas yang benar-benar ditinggalkan
        $histori = SantriKelasHistory::where('santri_id', $santri->id)
            ->whereIn('kelas_id', $kelasIdsGuru)
            ->where('kelas_id', '!=', $santri->kelas_id)
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
     * Hitung jumlah halaman jilid (Iqro/Aisar) UNIK (dedup) dari koleksi setoran
     * (sudah ter-scope kelas), untuk satu jilid target.
     * $labelPrefix: "Iqro Jilid " / "Aisar Jilid " — ikut metode santri.
     */
    private function hitungHalamanIqroUnik($setoransHafalan, int $targetJilid, string $labelPrefix = 'Iqro Jilid '): int
    {
        $pages   = [];
        $pattern = '/^' . preg_quote(trim($labelPrefix), '/') . ' ?(\d+)/i';

        foreach ($setoransHafalan as $s) {
            if (!preg_match($pattern, (string) $s->surat_start, $mj)) continue;
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
     * Tentukan URL & label tombol "Kembali" di halaman Show.
     * Dua jalur:
     *  - dari Riwayat Santri (?from=riwayat&ta=X) → kembali ke detail riwayat TA tsb
     *  - dari Rapot Index → kembali ke rapot index
     * Fallback: jika akses histori (tanpa param from), tetap ke riwayat.
     */
    private function resolveBackNavigation(Request $request, bool $isHistori, ?int $tahunAjaranId): array
    {
        $fromRiwayat = $request->query('from') === 'riwayat';
        $taBack      = (int) $request->query('ta') ?: $tahunAjaranId;

        if ($fromRiwayat && $taBack) {
            return [route('guru.riwayat-santri.detail', $taBack), 'Kembali ke Riwayat Santri'];
        }

        if ($isHistori && $tahunAjaranId) {
            return [route('guru.riwayat-santri.detail', $tahunAjaranId), 'Kembali ke Riwayat Santri'];
        }

        return [route('guru.rapot.index'), 'Kembali ke Rapot'];
    }

    /**
     * Daftar santri untuk dipilih laporannya.
     * Progress dihitung per KELAS (kelas_id) — kebal-tanggal, reset otomatis saat naik kelas,
     * akumulatif lintas semester selama di kelas yang sama. Konsisten dengan Dashboard & Setoran.
     */
    public function index()
    {
        $guru  = $this->guruAktif();
        $kelas = $this->kelasAktif();

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // Jendela kenaikan: HANYA semester genap (2) & mulai 1 bulan sebelum semester berakhir
        $bolehNaik             = false;
        $tglMulaiNaikFormatted = null;
        $alasanTutup           = null;
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

                    // Format untuk ditampilkan sebagai string di JSX
                    $tglMulaiNaikFormatted = $tglMulaiNaik->translatedFormat('d F Y');
                } else {
                    $bolehNaik = true;
                }
            }
        }

        // Kelas tidak memiliki relasi tahunAjaran(), jadi label tahun ajaran di dropdown
        // kelas tujuan memakai tahun ajaran AKTIF saat ini.
        $namaTahunAjaranAktif = $tahunAjaranAktif
            ? ($tahunAjaranAktif->nama ?? $tahunAjaranAktif->name ?? '-')
            : '-';

        $kelasTujuanList = $kelas
            ? Kelas::with('guru')
                ->where('id', '!=', $kelas->id)
                ->orderBy('name')
                ->get()
                ->map(function ($k) use ($namaTahunAjaranAktif) {
                    $k->tahun_ajaran = $namaTahunAjaranAktif;
                    return $k;
                })
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

        // ── Kunci PDF rapot santri ──
        // Tidak adanya baris di rapot_akses = TERKUNCI, jadi tidak perlu seeding.
        $jendelaRapot   = RapotAkses::jendela($tahunAjaranAktif);
        $bolehAturRapot = $jendelaRapot['boleh'];

        $aksesMap = ($tahunAjaranId && $santriList->isNotEmpty())
            ? RapotAkses::where('tahun_ajaran_id', $tahunAjaranId)
                ->whereIn('santri_id', $santriList->pluck('id'))
                ->get()
                ->keyBy('santri_id')
            : collect();

        // Progress per KELAS (kelas_id). Target tetap level-kelas (per TA / fallback target_juz).
        $santriList->each(function ($santri) use ($tahunAjaranAktif, $tahunAjaranId, $aksesMap) {
            $kelasId = $santri->kelas_id;
            $isIqro  = $santri->isJilidBased(); // iqro ATAU aisar
            $santri->is_iqro = $isIqro;

            $santri->rapot_dibuka = (bool) ($aksesMap[$santri->id]->dibuka_at ?? null);

           if ($isIqro) {
                $targetJilid   = (int)($santri->target_jilid ?? 1);
                $totalHalaman  = count($santri->halamanIqroTersetorKelas($targetJilid, $kelasId));
                $totalHalJilid = (int)(config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $targetJilid) ?? 0);

                $santri->hafalan_total_juz     = 0;
                $santri->total_halaman_iqro    = $totalHalaman;
                $santri->total_halaman_jilid   = $totalHalJilid;
                $santri->iqro_persen_diketahui = $totalHalJilid > 0;
                $santri->target_display        = 'Jilid ' . $targetJilid;
                $santri->hafalan_persen        = $totalHalJilid > 0
                    ? min(100, round(($totalHalaman / $totalHalJilid) * 100, 1))
                    : 0;

                // Target Al-Qur'an sekunder (metode 2 = quran)
                $hasQuran2 = $santri->hasQuranSecondary();
                $santri->has_quran_secondary = $hasQuran2;
                if ($hasQuran2) {
                    $targetJuz2 = (float)($santri->target_juz ?? 0);
                    // Setoran jilid ber-total_juz null → sum ini murni porsi Al-Qur'an
                    $capaianJuz2 = Setoran::where('santri_id', $santri->id)
                        ->where('kelas_id', $kelasId)
                        ->where('kategori_setoran', 'Hafalan')
                        ->sum('total_juz') ?? 0;

                    $santri->target_juz_2     = $targetJuz2;
                    $santri->capaian_juz_2    = round($capaianJuz2, 2);
                    $santri->hafalan_persen_2 = $targetJuz2 > 0
                        ? min(100, round(($capaianJuz2 / $targetJuz2) * 100, 1))
                        : 0;
                    $santri->target_display  .= ' + ' . rtrim(rtrim(number_format($targetJuz2, 2), '0'), '.') . ' Juz';
                } else {
                    $santri->target_juz_2     = 0;
                    $santri->capaian_juz_2    = 0;
                    $santri->hafalan_persen_2 = 0;
                }
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

                $santri->has_quran_secondary = false;
                $santri->target_juz_2        = 0;
                $santri->capaian_juz_2       = 0;
                $santri->hafalan_persen_2    = 0;
            }
        });

        return Inertia::render('Guru/Rapot/Index', [
            'kelas'                 => $kelas,
            'santriList'            => $santriList,
            'guru'                  => $guru,
            'tahunAjaranAktif'      => $tahunAjaranAktif,
            'bolehNaik'             => $bolehNaik,
            'tglMulaiNaikFormatted' => $tglMulaiNaikFormatted,
            'kelasTujuanList'       => $kelasTujuanList,
            'sudahNaikIds'          => $sudahNaikIds,
            'alasanTutup'           => $alasanTutup,

            // Kunci PDF rapot
            'bolehAturRapot'   => $bolehAturRapot,
            'rapotJendelaText' => $jendelaRapot['teks'],
            'rapotJendelaInfo' => $jendelaRapot['pesan'],
        ]);
    }

    /**
     * Buka / kunci akses PDF rapot untuk satu santri.
     * Hanya boleh dilakukan dalam jendela 1 bulan sebelum s/d 7 hari setelah
     * semester selesai — di luar itu tombolnya mati dan request ditolak.
     */
    public function toggleAksesRapot(Request $request, Santri $santri)
    {
        $guru     = $this->guruAktif();
        $kelasIds = collect(array_filter([$this->kelasAktifId()]));

        abort_unless($guru && $kelasIds->contains((int) $santri->kelas_id), 403,
            'Santri ini tidak ada di kelas yang sedang aktif.');

        $ta = TahunAjaran::getAktif();
        if (! $ta) {
            return back()->with('error', 'Tahun ajaran aktif belum diatur.');
        }

        $jendela = RapotAkses::jendela($ta);
        if (! $jendela['boleh']) {
            return back()->with('error', $jendela['pesan']);
        }

        $akses = RapotAkses::firstOrNew([
            'santri_id'       => $santri->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        if ($akses->dibuka_at) {
            $akses->dibuka_at   = null;
            $akses->dibuka_oleh = null;
            $akses->save();

            return back()->with('success', "Akses PDF rapot {$santri->name} dikunci kembali.");
        }

        $akses->dibuka_at   = now();
        $akses->dibuka_oleh = Auth::id();
        $akses->save();

        return back()->with('success', "Akses PDF rapot {$santri->name} dibuka.");
    }

    /**
     * Buka / kunci akses PDF rapot untuk BANYAK santri sekaligus.
     * Berbeda dengan toggle per-santri: di sini aksinya eksplisit
     * ('buka' / 'kunci'), bukan membalik status. Kalau membalik, santri yang
     * sudah terbuka justru ikut terkunci saat guru menekan "Buka Terpilih".
     */
    public function bulkAksesRapot(Request $request)
    {
        $data = $request->validate([
            'santri_ids'   => 'required|array|min:1',
            'santri_ids.*' => 'integer|exists:santris,id',
            'aksi'         => 'required|in:buka,kunci',
        ]);

        $guru     = $this->guruAktif();
        $kelasIds = collect(array_filter([$this->kelasAktifId()]));

        abort_unless($guru && $kelasIds->isNotEmpty(), 403, 'Kelas aktif tidak valid.');

        $ta = TahunAjaran::getAktif();
        if (! $ta) {
            return back()->with('error', 'Tahun ajaran aktif belum diatur.');
        }

        $jendela = RapotAkses::jendela($ta);
        if (! $jendela['boleh']) {
            return back()->with('error', $jendela['pesan']);
        }

        // Hanya santri bimbingan guru ini yang boleh diproses.
        $santriIds = Santri::whereIn('id', $data['santri_ids'])
            ->whereIn('kelas_id', $kelasIds)
            ->pluck('id');

        if ($santriIds->isEmpty()) {
            return back()->with('error', 'Tidak ada santri bimbingan Anda pada pilihan tersebut.');
        }

        $buka = $data['aksi'] === 'buka';

        foreach ($santriIds as $id) {
            $akses = RapotAkses::firstOrNew([
                'santri_id'       => $id,
                'tahun_ajaran_id' => $ta->id,
            ]);

            $akses->dibuka_at   = $buka ? now() : null;
            $akses->dibuka_oleh = $buka ? Auth::id() : null;
            $akses->save();
        }

        $kata = $buka ? 'dibuka' : 'dikunci kembali';

        return back()->with('success', "Akses PDF rapot {$santriIds->count()} santri {$kata}.");
    }

    /**
     * Halaman utama Riwayat Santri: rekap per TAHUN AJARAN.
     * Hanya santri yang BENAR-BENAR sudah meninggalkan kelas guru ini.
     */
    public function riwayatSantri()
    {
        $guru = $this->guruAktif();

        $ringkasan = collect();

        if ($guru) {
            $alumni = $this->historiAlumniGuru($guru);

            if ($alumni->isNotEmpty()) {
                $taIds = $alumni->pluck('tahun_ajaran_id')->filter()->unique()->all();
                $taMap = $taIds
                    ? TahunAjaran::whereIn('id', $taIds)->get()->keyBy('id')
                    : collect();

                $ringkasan = $alumni
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

        return Inertia::render('Guru/RiwayatSantri/Index', [
            'ringkasan' => $ringkasan->map(fn ($r) => [
                'tahun_ajaran_id' => $r['tahun_ajaran_id'],
                'nama'            => $r['nama'],
                'semester'        => $r['semester'],
                'jumlah'          => $r['jumlah'],
                'terakhir'        => $r['terakhir']
                    ? \Illuminate\Support\Carbon::parse($r['terakhir'])->translatedFormat('d M Y')
                    : null,
            ])->values(),
        ]);
    }

    /**
     * Detail Riwayat Santri untuk SATU tahun ajaran.
     * Memakai sumber data yang sama dengan riwayatSantri() supaya jumlahnya konsisten.
     */
    public function detailRiwayatSantri($tahunAjaranId)
    {
        $guru = $this->guruAktif();
        if (!$guru) abort(403, 'Data guru tidak ditemukan.');

        $tahunAjaran = TahunAjaran::find($tahunAjaranId);

        $santriAlumni = $this->historiAlumniGuru($guru)
            ->filter(fn ($h) => (int) $h->tahun_ajaran_id === (int) $tahunAjaranId)
            ->values();

        return Inertia::render('Guru/RiwayatSantri/Detail', [
            'tahunAjaran' => $tahunAjaran ? [
                'id'       => $tahunAjaran->id,
                'nama'     => $tahunAjaran->nama,
                'semester' => $tahunAjaran->semester,
            ] : null,
            'santriAlumni' => $santriAlumni->map(function ($h) use ($tahunAjaran) {
                $s = $h->santri;
                return [
                    'id'             => $s->id,
                    'name'           => $s->name,
                    'nis'            => $s->nis,
                    'gender'         => $s->gender,
                    'photo_url'      => $s->user?->profile_photo ? asset('storage/' . $s->user->profile_photo) : null,
                    'kelas_now'      => $s->kelas->name ?? '-',
                    'kelas_asal'     => $h->kelas->name ?? '-',
                    'tanggal_keluar' => $h->tanggal_keluar
                        ? \Illuminate\Support\Carbon::parse($h->tanggal_keluar)->translatedFormat('d M Y')
                        : null,
                    // Jalur eksplisit: from=riwayat + ta → tombol kembali di Show ke halaman ini
                    'rapot_url'      => route('guru.rapot.show', ['santri' => $s->id, 'from' => 'riwayat', 'ta' => $tahunAjaran->id ?? '']),
                ];
            })->values(),
        ]);
    }

    /**
     * Rentang tanggal untuk PRESENSI (presensi tak punya kelas_id).
     * - HISTORI → terkunci ke periode kelas lama.
     * - AKTIF   → FULL semester aktif: mulai TA s/d min(hari ini, selesai TA).
     */
    private function resolveRentangPresensi(Request $request, Santri $santri, array $akses): array
    {
        // Histori → terkunci ke periode kelas lama
        if ($akses['isHistori']) {
            return [$akses['rangeMulai'], $akses['rangeAkhir']];
        }

        // Aktif → FULL semester aktif: mulai TA s/d min(hari ini, selesai TA)
        $ta = $akses['tahunAjaran'];
        if ($ta && $ta->tanggal_mulai) {
            $mulai      = Carbon::parse($ta->tanggal_mulai);
            $selesaiRaw = $ta->tanggal_selesai ?? ($ta->tanggal_akhir ?? null);
            $selesai    = $selesaiRaw ? Carbon::parse($selesaiRaw) : now();
            $akhir      = now()->lt($selesai) ? now() : $selesai;
            return [$mulai->format('Y-m-d'), $akhir->format('Y-m-d')];
        }

        // Fallback jika TA tak punya tanggal
        return [now()->startOfYear()->toDateString(), now()->toDateString()];
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
        $guru = $this->guruAktif();
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
        $this->terapkanRentang($setoranQuery, $tanggalMulai, $tanggalAkhir);
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

        // Deteksi metode jilid (Iqro / Aisar)
        $isIqro = $santri->isJilidBased();
        $totalHalamanIqro  = 0;
        $totalHalamanJilid = 0;
        $progressIqro      = 0;
        if ($isIqro) {
            $targetJilid       = (int)($santri->target_jilid ?? 1);
            $totalHalamanIqro  = $this->hitungHalamanIqroUnik(
                $setoransRaw->where('kategori_setoran', 'Hafalan'),
                $targetJilid,
                $santri->jilidLabelPrefix()   // "Iqro Jilid " / "Aisar Jilid "
            );
            $totalHalamanJilid = (int)(config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $targetJilid) ?? 0);
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
            'metode_label'          => $santri->isAisar() ? 'Aisar' : 'Iqro',
            // Target Al-Qur'an sekunder — untuk santri jilid + quran.
            // total_juz_hafalan sudah = porsi Al-Qur'an (entri jilid total_juz null).
            'has_quran_secondary'   => $santri->hasQuranSecondary(),
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

        $jendela           = $this->cekJendelaPdf($tahunAjaranAktif);
        $pdfBoleh          = $jendela['boleh'];
        $pdfBatasFormatted = $jendela['batas']
            ? $jendela['batas']->translatedFormat('d F Y')
            : null;

        // ===== DUA JALUR KEMBALI =====
        [$backUrl, $backLabel] = $this->resolveBackNavigation($request, $isHistori, $tahunAjaranId);

        return Inertia::render('Guru/Rapot/Show', compact(
            'santri',
            'setorans',
            'statistikSetoran',
            'statistikPresensi',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif',
            'isHistori',
            'kelasKonteks',
            'pdfBoleh',
            'pdfBatasFormatted',
            'backUrl',
            'backLabel'
        ));
    }

    /**
     * Download / Preview PDF rapot santri
     */
    public function downloadPdf(Request $request, Santri $santri)
    {
        $guru = $this->guruAktif();
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
        $this->terapkanRentang($setoranQuery, $tanggalMulai, $tanggalAkhir);
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

        // Metode jilid (Iqro / Aisar) — supaya PDF juga benar untuk santri jilid-based
        $isIqro = $santri->isJilidBased();
        $totalHalamanIqro  = 0;
        $totalHalamanJilid = 0;
        $progressIqro      = 0;
        if ($isIqro) {
            $targetJilid       = (int)($santri->target_jilid ?? 1);
            $totalHalamanIqro  = $this->hitungHalamanIqroUnik(
                $setoransRaw->where('kategori_setoran', 'Hafalan'),
                $targetJilid,
                $santri->jilidLabelPrefix()
            );
            $totalHalamanJilid = (int)(config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $targetJilid) ?? 0);
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
            'metode_label'          => $santri->isAisar() ? 'Aisar' : 'Iqro',
            // Target Al-Qur'an sekunder — untuk santri jilid + quran.
            // total_juz_hafalan sudah = porsi Al-Qur'an (entri jilid total_juz null).
            'has_quran_secondary'   => $santri->hasQuranSecondary(),
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

        // Status kenaikan kelas — hanya terisi pada semester genap & setelah
        // keputusan kenaikan diambil. null = tidak dicetak di PDF.
        $statusKenaikan = SantriKelasHistory::statusKenaikan($santri, $tahunAjaranAktif, $kelasRapotId);

        // Cache-busting: ikut perubahan data setoran/presensi/history + file blade
        $stamp = max(
            (string) ($setoransRaw->max('updated_at') ?? '0'),
            (string) ($presensis->max('updated_at') ?? '0'),
            (string) (SantriKelasHistory::where('santri_id', $santri->id)->max('updated_at') ?? '0'),
            (string) filemtime(resource_path('views/santri/rapot/pdf.blade.php'))
        );
        $cacheKey = "rapot_pdf_{$santri->id}_{$kelasRapotId}_{$tanggalMulai}_{$tanggalAkhir}_" . md5($stamp);

        $pdfOutput = \Illuminate\Support\Facades\Cache::store('file')->remember($cacheKey, now()->addDays(7), function () use (
            $santri, $setorans, $presensis, $statistikSetoran, $statistikPresensi,
            $tanggalMulai, $tanggalAkhir, $tahunAjaranAktif, $kelasRapot, $guruRapot,
            $statusKenaikan
        ) {
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
                'guruRapot',
                'statusKenaikan'
            ));
            $pdf->setPaper('A4', 'portrait');
            return $pdf->output();
        });

        if ($request->boolean('preview')) {
            return response($pdfOutput, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="rapot.pdf"',
            ]);
        }

        $filename = 'Rapot_' . str_replace(' ', '_', $santri->name) . '_' . date('Y-m-d') . '.pdf';
        return response($pdfOutput, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
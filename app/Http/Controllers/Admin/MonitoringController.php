<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setoran;
use App\Models\Santri;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\TahunAjaran;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    use LogsActivity;

    /**
     * Dashboard Overview - Gabungan Setoran & Presensi
     */
    public function index(Request $request)
    {
        $tahunAjaranId = (int) ($request->input('tahun_ajaran_id') ?: TahunAjaran::getAktifId());
        $kelasId       = $request->input('kelas_id');

        $daftarTahunAjaran = TahunAjaran::orderByDesc('tanggal_mulai')->get()->map(fn ($ta) => [
            'id'    => $ta->id,
            'label' => $ta->label_lengkap,
            'aktif' => (bool) $ta->is_aktif,
        ]);

        // Total setoran per kelas — kebal-tanggal via kelas_id.
        // Hitung hanya setoran yang kelas_id-nya = kelas santri sekarang,
        // sehingga ikut "reset" otomatis saat santri naik kelas.
        $statistikKelas = Kelas::with(['santris' => function ($q) use ($tahunAjaranId) {
            $q->withCount([
                'setorans as total_setoran' => function ($sq) use ($tahunAjaranId) {
                    $sq->whereColumn('setorans.kelas_id', 'santris.kelas_id')
                        ->when($tahunAjaranId, fn ($qq) => $qq->where('setorans.tahun_ajaran_id', $tahunAjaranId));
                },
            ]);
        }, 'guru.user'])
            ->when($kelasId, fn ($q) => $q->where('id', $kelasId))
            ->get()
            ->map(function ($kelas) {
                return [
                    'kelas_id'      => $kelas->id,
                    'kelas'         => $kelas->name,
                    'guru'          => $kelas->guru->name ?? 'Belum ada guru',
                    'guru_photo'    => ($kelas->guru && $kelas->guru->user && $kelas->guru->user->profile_photo)
                                        ? $kelas->guru->user->profile_photo : null,
                    'total_santri'  => $kelas->santris->count(),
                    'total_setoran' => $kelas->santris->sum('total_setoran'),
                ];
            });

        // ✅ FIXED: view() -> Inertia::render()
        return Inertia::render('Admin/Monitoring/Index', [
            'statistikKelas'    => $statistikKelas,
            'daftarTahunAjaran' => $daftarTahunAjaran,
            'tahunAjaranId'     => $tahunAjaranId,
        ]);
    }

    /**
     * List Santri per Kelas
     */
    public function kelasSantri($id, Request $request)
    {
        $tahunAjaranId = (int) ($request->input('tahun_ajaran_id') ?: TahunAjaran::getAktifId());

        $kelas = Kelas::with('guru')->findOrFail($id);

        $santriList = Santri::with('user')
            ->where('kelas_id', $id)
            ->orderBy('name')
            ->get()
            ->map(function (Santri $santri) {
                [$totalHafalan, $progressPercent, $isIqro] = $this->computeSantriProgress($santri);

                return [
                    'id' => $santri->id,
                    'nis' => $santri->nis,
                    'name' => $santri->name,
                    'gender' => $santri->gender,
                    'photo' => $santri->user->profile_photo ?? null,
                    'is_iqro' => $isIqro,
                    'target_jilid' => $santri->target_jilid,
                    'target_juz' => (float) ($santri->target_juz ?? 0),
                    'total_hafalan' => $totalHafalan,
                    'progress_percent' => round(min($progressPercent, 100), 0),
                        'metode_label' => $isIqro ? ($santri->isAisar() ? 'Aisar' : 'Iqro') : null,

                ];
            });

        // ✅ FIXED: view() -> Inertia::render()
        return Inertia::render('Admin/Monitoring/KelasSantri', [
            'kelas' => [
                'id' => $kelas->id,
                'name' => $kelas->name,
                'guru_name' => $kelas->guru->name ?? null,
            ],
            'santriList'    => $santriList,
            'tahunAjaranId' => $tahunAjaranId,
        ]);
    }

    /**
     * Detail Santri - Gabungan Setoran & Presensi
     */
    public function detailSantri($id, Request $request)
    {
        $santri = Santri::with(['kelas', 'user'])->findOrFail($id);

        $tahunAjaranId = (int) ($request->input('tahun_ajaran_id') ?: TahunAjaran::getAktifId());

        // Semua tahun ajaran untuk dropdown
        $daftarTahunAjaran = TahunAjaran::orderByDesc('tanggal_mulai')->get()->map(fn ($ta) => [
            'id'    => $ta->id,
            'label' => $ta->label_lengkap,
            'aktif' => (bool) $ta->is_aktif,
        ]);

        // Rentang tanggal presensi diambil dari semester terpilih
        $taObj = TahunAjaran::find($tahunAjaranId);
        $tanggalMulai = $taObj?->tanggal_mulai?->format('Y-m-d') ?? now()->startOfYear()->format('Y-m-d');
        $tanggalAkhir = $taObj?->tanggal_selesai?->format('Y-m-d') ?? now()->format('Y-m-d');

        // ✅ Target Juz — coba dari tabel target per tahun ajaran, fallback ke kolom lama
        $targetJuz = null;
        if ($tahunAjaranId && method_exists($santri, 'getTargetJuzByTahunAjaran')) {
            $targetJuz = $santri->getTargetJuzByTahunAjaran($tahunAjaranId);
        }
        if (!$targetJuz || $targetJuz <= 0) {
            $targetJuz = $santri->target_juz ?? 30;
        }
        $targetJuz = (float) $targetJuz;

        // ✅ Statistik Setoran per Kategori — per kelas (kebal-tanggal)
        $setoranStatsRaw = Setoran::where('santri_id', $id)
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->select(
                'kategori_setoran',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(total_juz) as total_juz'),
                DB::raw('AVG(nilai) as rata_nilai')
            )
            ->groupBy('kategori_setoran')
            ->get()
            ->keyBy('kategori_setoran');

        $setoranStats = [];
        foreach (['Hafalan', 'Murajaah', 'Tilawah'] as $kat) {
            $row = $setoranStatsRaw->get($kat);
            $setoranStats[$kat] = [
                'total' => $row->total ?? 0,
                'total_juz' => (float) ($row->total_juz ?? 0),
                'rata_nilai' => $row && $row->rata_nilai !== null ? round((float) $row->rata_nilai, 1) : null,
            ];
        }

        // ✅ Progress Hafalan — akumulatif per kelas (kebal-tanggal), reset saat naik kelas
        $totalHafalanJuz = (float) Setoran::where('santri_id', $id)
            ->where('kategori_setoran', 'Hafalan')
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->sum('total_juz');

        $progressHafalan = $targetJuz > 0
            ? min(100, round(($totalHafalanJuz / $targetJuz) * 100, 2))
            : 0;

        // Iqro-aware: hitung ulang jika santri Iqro
        $halamanIqroPerKategori = ['Murajaah' => 0, 'Tilawah' => 0];
        $isIqro = $santri->isJilidBased(); // iqro ATAU aisar
        $totalHalamanJilid = 0;
        $targetJilid = (int) ($santri->target_jilid ?? 1);
        $jilidPattern = '/^' . preg_quote(trim($santri->jilidLabelPrefix()), '/') . ' ?(\d+)/i'; // "Iqro Jilid" / "Aisar Jilid"

        if ($isIqro) {
            $totalHalamanJilid = (int) (config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $targetJilid) ?? 0);
            $totalHafalanJuz = 0; // reuse variabel — di Iqro ini = total halaman

            $setoransIqroAll = Setoran::where('santri_id', $id)
                ->where('kategori_setoran', 'Hafalan')
                ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
                ->get();

            $pagesUnik = [];
            foreach ($setoransIqroAll as $s) {
                if (!preg_match($jilidPattern, (string) $s->surat_start, $mj)) continue;
                if ((int) $mj[1] !== $targetJilid) continue;
                $h1 = (int) $s->ayat_start;
                $h2 = (int) ($s->ayat_end ?: $s->ayat_start);
                if ($h1 <= 0) continue;
                if ($h2 < $h1) { [$h1, $h2] = [$h2, $h1]; }
                for ($p = $h1; $p <= $h2; $p++) $pagesUnik[$p] = true;
            }
            $totalHafalanJuz = count($pagesUnik); // di jilid = jumlah halaman unik

            $progressHafalan = $totalHalamanJilid > 0
                ? min(100, round(($totalHafalanJuz / $totalHalamanJilid) * 100, 2))
                : 0;

            foreach (['Murajaah', 'Tilawah'] as $kat) {
                $rows = Setoran::where('santri_id', $id)
                    ->where('kategori_setoran', $kat)
                    ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
                    ->get();
                $hal = 0;
                foreach ($rows as $s) {
                    if (!preg_match($jilidPattern, (string) $s->surat_start)) continue;
                    $h1 = (int) $s->ayat_start;
                    $h2 = (int) ($s->ayat_end ?: $s->ayat_start);
                    if ($h1 > 0 && $h2 >= $h1) $hal += ($h2 - $h1 + 1);
                }
                $halamanIqroPerKategori[$kat] = $hal;
            }
        }

        // ✅ Data Setoran per Kategori dengan pagination — per kelas (kebal-tanggal)
        $baseSetoran = Setoran::where('santri_id', $id)
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->with('guru.user')
            ->orderBy('tanggal', 'desc');

        $setoranHafalan  = (clone $baseSetoran)->where('kategori_setoran', 'Hafalan')->paginate(15, ['*'], 'hafalan_page')->withQueryString();
        $setoranMurajaah = (clone $baseSetoran)->where('kategori_setoran', 'Murajaah')->paginate(8, ['*'], 'murajaah_page')->withQueryString();
        $setoranTilawah  = (clone $baseSetoran)->where('kategori_setoran', 'Tilawah')->paginate(8, ['*'], 'tilawah_page')->withQueryString();

        $mapSetoran = function (Setoran $setoran) {
            return [
                'id' => $setoran->id,
                'tanggal' => $setoran->tanggal->format('d/m/Y'),
                'surat_start' => $setoran->surat_start,
                'ayat_start' => $setoran->ayat_start,
                'surat_end' => $setoran->surat_end,
                'ayat_end' => $setoran->ayat_end,
                'total_juz' => (float) $setoran->total_juz,
                'nilai' => $setoran->nilai,
                'guru_name' => $setoran->guru->user->name ?? null,
                'has_audio' => $setoran->audio_path && \Storage::disk('public')->exists($setoran->audio_path),
                'has_pdf'   => $setoran->koreksi_pdf_path && \Storage::disk('public')->exists($setoran->koreksi_pdf_path),
                'catatan_guru' => $setoran->catatan_guru,
            ];
        };
        $setoranHafalan->getCollection()->transform($mapSetoran);
        $setoranMurajaah->getCollection()->transform($mapSetoran);
        $setoranTilawah->getCollection()->transform($mapSetoran);

        // ✅ Data Presensi — TETAP per tanggal + tahun ajaran.
        $presensiData = Presensi::where('santri_id', $id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->orderBy('tanggal', 'desc')
            ->get();

        $presensiStats = [
            'Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0,
        ];
        foreach ($presensiData->groupBy('status') as $status => $rows) {
            $presensiStats[$status] = $rows->count();
        }

        // ✅ Kelompokkan presensi per tanggal (dipakai kalender + list view)
        $presensiByDate = [];
        foreach ($presensiData as $p) {
            $key = $p->tanggal->format('Y-m-d');
            $presensiByDate[$key][] = [
                'sesi' => $p->sesi,
                'status' => $p->status,
            ];
        }

        $presensiList = [];
        foreach ($presensiByDate as $dateKey => $items) {
            $presensiList[] = [
                'date' => $dateKey,
                'date_label' => Carbon::parse($dateKey)->locale('id')->isoFormat('dddd, D MMM Y'),
                'items' => $items,
            ];
        }

        // ✅ Bangun struktur kalender multi-bulan di server (butuh locale 'id')
        $start = Carbon::parse($tanggalMulai);
        $end = Carbon::parse($tanggalAkhir);
        $statusPriority = ['Alpha' => 4, 'Sakit' => 3, 'Izin' => 2, 'Hadir' => 1];

        $calendarMonths = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $daysInMonth = $monthEnd->day;

            $days = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateObj = $monthStart->copy()->day($day);
                $dateKey = $dateObj->format('Y-m-d');
                $dayPresensi = $presensiByDate[$dateKey] ?? [];

                $primaryStatus = null;
                $highestPriority = 0;
                foreach ($dayPresensi as $dp) {
                    $prio = $statusPriority[$dp['status']] ?? 0;
                    if ($prio > $highestPriority) {
                        $highestPriority = $prio;
                        $primaryStatus = $dp['status'];
                    }
                }

                $days[] = [
                    'day' => $day,
                    'date' => $dateKey,
                    'date_label' => $dateObj->locale('id')->isoFormat('dddd, D MMMM Y'),
                    'is_in_range' => $dateObj->between($start, $end),
                    'presensi' => $dayPresensi,
                    'primary_status' => $primaryStatus,
                ];
            }

            $calendarMonths[] = [
                'label' => $monthStart->locale('id')->isoFormat('MMMM Y'),
                'first_day_of_week' => $monthStart->dayOfWeek,
                'days' => $days,
            ];

            $cursor->addMonth();
        }

        // ✅ Navigasi Prev / Next dalam kelas yang sama
        $semuaSantriIds = Santri::where('kelas_id', $santri->kelas_id)
            ->orderBy('name')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($santri->id, $semuaSantriIds);

        $prevSantri = ($currentIndex !== false && $currentIndex > 0)
            ? Santri::find($semuaSantriIds[$currentIndex - 1])
            : null;

        $nextSantri = ($currentIndex !== false && $currentIndex < count($semuaSantriIds) - 1)
            ? Santri::find($semuaSantriIds[$currentIndex + 1])
            : null;

        // ✅ FIXED: view() -> Inertia::render()
        return Inertia::render('Admin/Monitoring/DetailSantri', [
            'santri' => [
                'id' => $santri->id,
                'name' => $santri->name,
                'nis' => $santri->nis,
                'gender' => $santri->gender,
                'kelas_id' => $santri->kelas_id,
                'kelas_name' => $santri->kelas->name ?? '-',
                'photo' => $santri->user->profile_photo ?? null,
            ],
            'isIqro' => $isIqro,
            'metodeLabel' => $isIqro ? ($santri->isAisar() ? 'Aisar' : 'Iqro') : null,
            'targetJuz' => $targetJuz,
            'targetJilid' => $targetJilid,
            'totalHalamanJilid' => $totalHalamanJilid,
            'totalHafalanJuz' => $totalHafalanJuz,
            'progressHafalan' => $progressHafalan,
            'setoranStats' => $setoranStats,
            'halamanIqroPerKategori' => $halamanIqroPerKategori,
            'setoranHafalan' => $setoranHafalan,
            'setoranMurajaah' => $setoranMurajaah,
            'setoranTilawah' => $setoranTilawah,
            'presensiStats' => $presensiStats,
            'presensiCount' => $presensiData->count(),
            'presensiList' => $presensiList,
            'calendarMonths' => $calendarMonths,
            'tanggalMulai' => $tanggalMulai,
            'tanggalAkhir' => $tanggalAkhir,
            'tahunAjaranId' => $tahunAjaranId,
            'daftarTahunAjaran' => $daftarTahunAjaran,
            'prevSantri' => $prevSantri ? ['id' => $prevSantri->id, 'name' => $prevSantri->name] : null,
            'nextSantri' => $nextSantri ? ['id' => $nextSantri->id, 'name' => $nextSantri->name] : null,
        ]);
    }

    /**
     * Stream Audio
     */
    public function audio($id)
    {
        $setoran = Setoran::findOrFail($id);

        if (!$setoran->audio_path || !\Storage::disk('public')->exists($setoran->audio_path)) {
            abort(404, 'Audio file not found');
        }

        $this->logView('setoran', "Audio setoran {$setoran->santri->name}", $setoran);

        $path     = \Storage::disk('public')->path($setoran->audio_path);
        $mimeType = \Storage::disk('public')->mimeType($setoran->audio_path);

        return response()->file($path, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="setoran_' . $setoran->id . '.webm"'
        ]);
    }

    /**
     * Download / Preview PDF
     */
    public function pdf($id)
    {
        $setoran = Setoran::findOrFail($id);

        if (!$setoran->koreksi_pdf_path || !\Storage::disk('public')->exists($setoran->koreksi_pdf_path)) {
            abort(404, 'PDF file not found');
        }

        $this->logView('setoran', "PDF koreksi {$setoran->santri->name}", $setoran);

        $path = \Storage::disk('public')->path($setoran->koreksi_pdf_path);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="koreksi_' . $setoran->santri->name . '_' . $setoran->tanggal->format('Y-m-d') . '.pdf"'
        ]);
    }

    /**
     * Hitung total hafalan (juz atau halaman iqro) + persentase progress untuk 1 santri.
     * Dipakai oleh kelasSantri() untuk list ringkas per kelas.
     *
     * @return array{0: float, 1: float, 2: bool} [totalHafalan, progressPercent, isIqro]
     */
    private function computeSantriProgress(Santri $santri): array
    {
        $isIqro = $santri->isJilidBased(); // iqro ATAU aisar

        if ($isIqro) {
            $targetJilid       = (int) ($santri->target_jilid ?? 1);
            $totalHalamanJilid = (int) (config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $targetJilid) ?? 0);

            // Dedup + scope kelas + label otomatis ikut metode — konsisten dgn panel Guru
            $totalHafalan = count($santri->halamanIqroTersetorKelas($targetJilid, $santri->kelas_id));

            $progressPercent = $totalHalamanJilid > 0
                ? min(100, ($totalHafalan / $totalHalamanJilid) * 100)
                : 0;
        } else {
            $totalHafalan = (float) $santri->setorans()
                ->where('kategori_setoran', 'Hafalan')
                ->when($santri->kelas_id, fn ($q) => $q->where('kelas_id', $santri->kelas_id))
                ->sum('total_juz');

            $progressPercent = $santri->target_juz > 0
                ? ($totalHafalan / $santri->target_juz) * 100
                : 0;
        }

        return [$totalHafalan, $progressPercent, $isIqro];
    }
}
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

class MonitoringController extends Controller
{
    use LogsActivity;

    /**
     * Dashboard Overview - Gabungan Setoran & Presensi
     */
    public function index(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', now()->subDays(30)->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));
        $kelasId      = $request->input('kelas_id');

        $tahunAjaranId    = $request->input('tahun_ajaran_id', TahunAjaran::getAktifId());
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $semuaTahunAjaran = TahunAjaran::terbaru()->get();

        // Total setoran per kelas — kebal-tanggal via kelas_id.
        // Hitung hanya setoran yang kelas_id-nya = kelas santri sekarang,
        // sehingga ikut "reset" otomatis saat santri naik kelas.
        $statistikKelas = Kelas::with(['santris' => function($q) {
            $q->withCount([
                'setorans as total_setoran' => function($sq) {
                    $sq->whereColumn('setorans.kelas_id', 'santris.kelas_id');
                },
            ]);
        }, 'guru.user'])
        ->when($kelasId, fn($q) => $q->where('id', $kelasId))
        ->get()
        ->map(function($kelas) {
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

        $kelasList = Kelas::orderBy('name')->get();

        return view('admin.monitoring.index', compact(
            'statistikKelas',
            'kelasList',
            'tanggalMulai',
            'tanggalAkhir',
            'kelasId',
            'tahunAjaranId',
            'tahunAjaranAktif',
            'semuaTahunAjaran'
        ));
    }

    /**
     * List Santri per Kelas
     */
    public function kelasSantri($id, Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', now()->subDays(30)->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));

        $kelas = Kelas::with('guru')->findOrFail($id);

        $tahunAjaranId = $request->input('tahun_ajaran_id')
            ? (int) $request->input('tahun_ajaran_id')
            : TahunAjaran::getAktifId();

        $santriList = Santri::with('user')
            ->where('kelas_id', $id)
            ->orderBy('name')
            ->get();

        return view('admin.monitoring.kelas-santri', compact(
            'kelas',
            'santriList',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranId'
        ));
    }

    /**
     * Detail Santri - Gabungan Setoran & Presensi
     */
    public function detailSantri($id, Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', now()->subDays(30)->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', now()->format('Y-m-d'));

        $santri = Santri::with(['kelas', 'user'])->findOrFail($id);

        // ✅ Kelas santri sekarang — dipakai SEMUA query setoran (kebal-tanggal).
        //    Didefinisikan paling atas agar tersedia untuk seluruh query di bawah.
        $kelasIdSantri = $santri->kelas_id;

        // ✅ Ambil tahun ajaran — prioritas dari request, fallback ke aktif
        //    (masih dipakai untuk target juz & data presensi)
        $tahunAjaranId    = $request->input('tahun_ajaran_id')
            ? (int) $request->input('tahun_ajaran_id')
            : TahunAjaran::getAktifId();
        $semuaTahunAjaran = TahunAjaran::terbaru()->get();

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
        $setoranStats = Setoran::where('santri_id', $id)
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->select(
                'kategori_setoran',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(total_juz) as total_juz'),
                DB::raw('AVG(nilai) as rata_nilai')
            )
            ->groupBy('kategori_setoran')
            ->get()
            ->keyBy('kategori_setoran');

        // ✅ Progress Hafalan — akumulatif per kelas (kebal-tanggal), reset saat naik kelas
        $totalHafalanJuz = (float) Setoran::where('santri_id', $id)
            ->where('kategori_setoran', 'Hafalan')
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->sum('total_juz');

        $progressHafalan = $targetJuz > 0
            ? min(100, round(($totalHafalanJuz / $targetJuz) * 100, 2))
            : 0;

        // Iqro-aware: hitung ulang jika santri Iqro
        $halamanIqroPerKategori = ['Murajaah' => 0, 'Tilawah' => 0];
        $isIqro = ($santri->target_metode?->value ?? 'quran') === 'iqro';
        if ($isIqro) {
            $targetJilid       = (int)($santri->target_jilid ?? 1);
            $totalHalamanJilid = (int)(config('iqro.halaman_per_jilid.' . $targetJilid) ?? 0);
            $totalHafalanJuz   = 0; // reuse variabel — di Iqro ini = total halaman

            $setoransIqroAll = Setoran::where('santri_id', $id)
                ->where('kategori_setoran', 'Hafalan')
                ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
                ->get();

            foreach ($setoransIqroAll as $s) {
                // Hanya hitung halaman yang jilidnya SAMA dengan target jilid sekarang,
                // supaya progress mulai dari nol lagi saat santri naik jilid.
                if (!preg_match('/^Iqro Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
                if ((int)$mj[1] !== $targetJilid) continue;
                $h1 = (int)$s->ayat_start;
                $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                if ($h1 > 0 && $h2 >= $h1) $totalHafalanJuz += ($h2 - $h1 + 1);
            }

            $progressHafalan = $totalHalamanJilid > 0
                ? min(100, round(($totalHafalanJuz / $totalHalamanJilid) * 100, 2))
                : 0;

            // Total halaman Iqro per kategori (untuk kartu Murajaah & Tilawah)
            $halamanIqroPerKategori = [];
            foreach (['Murajaah', 'Tilawah'] as $kat) {
                $rows = Setoran::where('santri_id', $id)
                    ->where('kategori_setoran', $kat)
                    ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
                    ->get();
                $hal = 0;
                foreach ($rows as $s) {
                    if (!preg_match('/^Iqro Jilid \d+/i', (string)$s->surat_start)) continue;
                    $h1 = (int)$s->ayat_start;
                    $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                    if ($h1 > 0 && $h2 >= $h1) $hal += ($h2 - $h1 + 1);
                }
                $halamanIqroPerKategori[$kat] = $hal;
            }
        }

        // ✅ Data Setoran per Kategori dengan pagination — per kelas (kebal-tanggal)
        $baseSetoran = Setoran::where('santri_id', $id)
            ->when($kelasIdSantri, fn($q) => $q->where('kelas_id', $kelasIdSantri))
            ->with('guru.user')
            ->orderBy('tanggal', 'desc');

        $setoranHafalan  = (clone $baseSetoran)->where('kategori_setoran', 'Hafalan')->paginate(15, ['*'], 'hafalan_page');
        $setoranMurajaah = (clone $baseSetoran)->where('kategori_setoran', 'Murajaah')->paginate(8, ['*'], 'murajaah_page');
        $setoranTilawah  = (clone $baseSetoran)->where('kategori_setoran', 'Tilawah')->paginate(8, ['*'], 'tilawah_page');

        // ✅ Data Presensi — TETAP per tanggal + tahun ajaran.
        //    Tabel presensi belum punya kolom kelas_id, jadi tidak bisa
        //    difilter via kelas_id seperti setoran (konsisten dgn bagian lain app).
        $presensiData = Presensi::where('santri_id', $id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($tahunAjaranId, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->orderBy('tanggal', 'desc')
            ->get();

        $presensiStats = $presensiData->groupBy('status')->map->count();

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

        return view('admin.monitoring.detail-santri', compact(
            'santri',
            'halamanIqroPerKategori',
            'setoranStats',
            'targetJuz',
            'totalHafalanJuz',
            'progressHafalan',
            'setoranHafalan',
            'setoranMurajaah',
            'setoranTilawah',
            'presensiData',
            'presensiStats',
            'tanggalMulai',
            'tanggalAkhir',
            'prevSantri',
            'nextSantri',
            'tahunAjaranId',
            'semuaTahunAjaran'
        ));
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
}
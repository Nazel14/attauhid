<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Setoran;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SetoranController extends Controller
{
    /**
     * Tampilkan daftar setoran
     */
    public function index()
    {
        $userId = Auth::id();
        $guru = Guru::where('user_id', $userId)->first();

        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = Kelas::where('guru_id', $guru->id)->first();

        if (!$kelas) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        $santris = Santri::where('kelas_id', $kelas->id)
            ->with(['guru', 'kelas'])
            ->get()
            ->map(function ($santri) use ($tahunAjaranId) {

                $setoranQuery = Setoran::where('santri_id', $santri->id);
                if ($tahunAjaranId) {
                    $setoranQuery->where('tahun_ajaran_id', $tahunAjaranId);
                }
                $setorans = $setoranQuery->get();

                // Hitung per-kategori (jumlah setoran)
                $santri->total_hafalan  = $setorans->where('kategori_setoran', 'Hafalan')->count();
                $santri->total_tilawah  = $setorans->where('kategori_setoran', 'Tilawah')->count();
                $santri->total_murajaah = $setorans->where('kategori_setoran', 'Murajaah')->count();
                $santri->total_setoran  = $setorans->count();

                // total_juz untuk kolom display (semua kategori)
                $santri->total_juz = round($setorans->sum('total_juz'), 2);

                // total_hafalan_juz HANYA dari kategori Hafalan
                $totalHafalanJuz = round(
                    $setorans->where('kategori_setoran', 'Hafalan')->sum('total_juz'),
                    2
                );
                $santri->total_hafalan_juz = $totalHafalanJuz;

                // Progress berdasarkan hafalan juz vs target juz
                $targetJuz = $santri->target_juz ?? 1.00;
                $santri->juz_progress_percentage = $targetJuz > 0
                    ? min(round(($totalHafalanJuz / $targetJuz) * 100), 100)
                    : 0;

                // Presensi 30 hari terakhir
                $presensiQuery = Presensi::where('santri_id', $santri->id)
                    ->where('tanggal', '>=', now()->subDays(30));
                if ($tahunAjaranId) {
                    $presensiQuery->where('tahun_ajaran_id', $tahunAjaranId);
                }
                $presensis = $presensiQuery->get();

                $santri->total_hadir = $presensis->where('status', 'Hadir')->count();
                $santri->total_sakit = $presensis->where('status', 'Sakit')->count();
                $santri->total_izin  = $presensis->where('status', 'Izin')->count();
                $santri->total_alpha = $presensis->where('status', 'Alpha')->count();

                return $santri;
            })
            ->sortBy('name');

        $totalSantri = $santris->count();
        $totalIkhwan = $santris->where('gender', 'Laki-laki')->count();
        $totalAkhwat = $santris->where('gender', 'Perempuan')->count();

        $setoranHariIniQuery = Setoran::whereIn('santri_id', $santris->pluck('id'))
            ->whereDate('tanggal', today());
        if ($tahunAjaranId) {
            $setoranHariIniQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }
        $setoranHariIni         = $setoranHariIniQuery->get();
        $setoranHariIniHafalan  = $setoranHariIni->where('kategori_setoran', 'Hafalan')->count();
        $setoranHariIniTilawah  = $setoranHariIni->where('kategori_setoran', 'Tilawah')->count();
        $setoranHariIniMurajaah = $setoranHariIni->where('kategori_setoran', 'Murajaah')->count();

        $perPage           = 15;
        $currentPage       = request()->get('page', 1);
        $santrisCollection = collect($santris);
        $santrisPaginated  = new \Illuminate\Pagination\LengthAwarePaginator(
            $santrisCollection->forPage($currentPage, $perPage),
            $santrisCollection->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('guru.setorans.index', [
            'santris'                => $santrisPaginated,
            'kelas'                  => $kelas,
            'guru'                   => $guru,
            'totalSantri'            => $totalSantri,
            'totalIkhwan'            => $totalIkhwan,
            'totalAkhwat'            => $totalAkhwat,
            'setoranHariIniHafalan'  => $setoranHariIniHafalan,
            'setoranHariIniTilawah'  => $setoranHariIniTilawah,
            'setoranHariIniMurajaah' => $setoranHariIniMurajaah,
            'tahunAjaranAktif'       => $tahunAjaranAktif,
        ]);
    }

    /**
     * Tampilkan detail setoran per santri
     */
    public function detail(int $santriId)
    {
        $userId = Auth::id();
        $guru = Guru::where('user_id', $userId)->first();

        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = Kelas::where('guru_id', $guru->id)->first();

        if (!$kelas) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        $santri = Santri::with(['kelas', 'guru'])
            ->where('id', $santriId)
            ->where('kelas_id', $kelas->id)
            ->firstOrFail();

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ✅ FIX: Default range = seluruh periode tahun ajaran aktif
        // Sebelumnya hanya 30 hari terakhir, sehingga data lama tidak tampil
        $defaultMulai = $tahunAjaranAktif
            ? Carbon::parse($tahunAjaranAktif->tanggal_mulai)->format('Y-m-d')
            : now()->startOfYear()->format('Y-m-d');

        $defaultAkhir = $tahunAjaranAktif
            ? Carbon::parse($tahunAjaranAktif->tanggal_akhir)->format('Y-m-d')
            : now()->format('Y-m-d');

        $tanggalMulai = request('tanggal_mulai', $defaultMulai);
        $tanggalAkhir = request('tanggal_akhir', $defaultAkhir);

        $setoranQuery = Setoran::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->with('presensi')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');
        if ($tahunAjaranId) {
            $setoranQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }
        $setorans = $setoranQuery->get();

        $setoranStats = collect([
            'Hafalan'  => (object)['kategori_setoran' => 'Hafalan',  'total' => 0, 'total_juz' => 0],
            'Murajaah' => (object)['kategori_setoran' => 'Murajaah', 'total' => 0, 'total_juz' => 0],
            'Tilawah'  => (object)['kategori_setoran' => 'Tilawah',  'total' => 0, 'total_juz' => 0],
        ]);

        $setorans->groupBy('kategori_setoran')
            ->each(function ($items, $kategori) use ($setoranStats) {
                if ($setoranStats->has($kategori)) {
                    $setoranStats->put($kategori, (object)[
                        'kategori_setoran' => $kategori,
                        'total'            => $items->count(),
                        'total_juz'        => $items->sum('total_juz'),
                    ]);
                }
            });

        $presensiQuery = Presensi::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
        if ($tahunAjaranId) {
            $presensiQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }
        $presensis = $presensiQuery->get();

        $presensiStats = [
            'total_hadir' => $presensis->where('status', 'Hadir')->count(),
            'total_sakit' => $presensis->where('status', 'Sakit')->count(),
            'total_izin'  => $presensis->where('status', 'Izin')->count(),
            'total_alpha' => $presensis->where('status', 'Alpha')->count(),
        ];

        // Progress HANYA dari hafalan juz
        $totalHafalanJuz = round(
            $setorans->where('kategori_setoran', 'Hafalan')->sum('total_juz'),
            2
        );

        $targetJuz = $tahunAjaranAktif
            ? ($santri->getTargetJuzByTahunAjaran($tahunAjaranId) ?? ($santri->target_juz ?? 1.00))
            : ($santri->target_juz ?? 1.00);

        $progressHafalan = $targetJuz > 0
            ? min(($totalHafalanJuz / $targetJuz) * 100, 100)
            : 0;

        $setoranHafalan  = $setorans->where('kategori_setoran', 'Hafalan');
        $setoranMurajaah = $setorans->where('kategori_setoran', 'Murajaah');
        $setoranTilawah  = $setorans->where('kategori_setoran', 'Tilawah');

        return view('guru.setorans.detail', compact(
            'santri',
            'guru',
            'kelas',
            'setoranStats',
            'presensiStats',
            'totalHafalanJuz',
            'targetJuz',
            'progressHafalan',
            'setoranHafalan',
            'setoranMurajaah',
            'setoranTilawah',
            'tanggalMulai',
            'tanggalAkhir',
            'tahunAjaranAktif'
        ));
    }

    /**
     * Tampilkan form edit setoran
     */
    public function edit(int $id)
    {
        $userId = Auth::id();
        $guru = Guru::where('user_id', $userId)->first();

        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = Kelas::where('guru_id', $guru->id)->first();

        if (!$kelas) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        $setoran = Setoran::with('santri', 'presensi')->findOrFail($id);

        if ($setoran->santri->kelas_id !== $kelas->id) {
            return redirect()->route('guru.setorans.index')
                ->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        $daftarSurat = $this->getDaftarSurat();

        return view('guru.setorans.edit', compact('setoran', 'guru', 'kelas', 'daftarSurat'));
    }

    /**
     * Update setoran
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'kategori_setoran'     => 'required|in:Hafalan,Murajaah,Tilawah',
            'surat_start'          => 'required|string',
            'ayat_start'           => 'required|integer',
            'surat_end'            => 'required|string',
            'ayat_end'             => 'required|integer',
            'page_start'           => 'nullable|integer',
            'page_end'             => 'nullable|integer',
            'total_page'           => 'nullable|numeric',
            'juz_start'            => 'nullable|integer',
            'juz_end'              => 'nullable|integer',
            'total_juz'            => 'nullable|numeric',
            'total_kata'           => 'nullable|integer',
            'kesalahan_kelancaran' => 'nullable|integer',
            'kesalahan_tajwid'     => 'nullable|integer',
            'kesalahan_pelafalan'  => 'nullable|integer',
            'nilai'                => 'nullable|integer',
            'catatan_guru'         => 'nullable|string',
            'audio_data'           => 'nullable|string',
            'pdf_html_content'     => 'nullable|string',
        ]);

        $userId = Auth::id();
        $guru = Guru::where('user_id', $userId)->first();

        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = Kelas::where('guru_id', $guru->id)->first();

        if (!$kelas) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        $setoran = Setoran::with('santri')->findOrFail($id);

        if ($setoran->santri->kelas_id !== $kelas->id) {
            return redirect()->route('guru.setorans.index')
                ->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        DB::beginTransaction();

        try {
            $setoran->update([
                'kategori_setoran'     => $request->kategori_setoran,
                'surat_start'          => $request->surat_start,
                'ayat_start'           => $request->ayat_start,
                'surat_end'            => $request->surat_end,
                'ayat_end'             => $request->ayat_end,
                'page_start'           => $request->page_start,
                'page_end'             => $request->page_end,
                'total_page'           => $request->total_page,
                'juz_start'            => $request->juz_start,
                'juz_end'              => $request->juz_end,
                'total_juz'            => $request->total_juz,
                'total_kata'           => $request->total_kata ?? 0,
                'kesalahan_kelancaran' => $request->kesalahan_kelancaran ?? 0,
                'kesalahan_tajwid'     => $request->kesalahan_tajwid ?? 0,
                'kesalahan_pelafalan'  => $request->kesalahan_pelafalan ?? 0,
                'nilai'                => $request->nilai ?? 100,
                'catatan_guru'         => $request->catatan_guru,
            ]);

            if ($request->filled('audio_data')) {
                try {
                    $audioData = $request->audio_data;

                    if (preg_match('/^data:audio\/(\w+);base64,/', $audioData, $type)) {
                        if ($setoran->audio_path && Storage::disk('public')->exists($setoran->audio_path)) {
                            Storage::disk('public')->delete($setoran->audio_path);
                        }

                        $audioData = substr($audioData, strpos($audioData, ',') + 1);
                        $audioData = base64_decode($audioData);

                        if ($audioData === false) {
                            throw new \Exception('Failed to decode audio data');
                        }

                        $extension = strtolower($type[1]);
                        if ($extension === 'webm') $extension = 'webm';

                        $fileName = 'audio_' . $setoran->id . '_' . time() . '.' . $extension;
                        $path     = 'audio_setorans/' . $fileName;

                        if (!Storage::disk('public')->exists('audio_setorans')) {
                            Storage::disk('public')->makeDirectory('audio_setorans');
                        }

                        Storage::disk('public')->put($path, $audioData);
                        $setoran->update(['audio_path' => $path]);

                        Log::info('✅ Audio updated successfully', ['path' => $path]);
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Gagal update audio: ' . $e->getMessage());
                }
            }

            if ($request->filled('pdf_html_content')) {
                try {
                    $pdfHtml = $request->pdf_html_content;

                    if (strlen($pdfHtml) < 100) {
                        throw new \Exception('PDF HTML content is too short');
                    }

                    if ($setoran->koreksi_pdf_path && Storage::disk('public')->exists($setoran->koreksi_pdf_path)) {
                        Storage::disk('public')->delete($setoran->koreksi_pdf_path);
                    }

                    $fileName = 'pdf_' . $setoran->id . '_' . time() . '.pdf';
                    $path     = 'pdf_setorans/' . $fileName;

                    if (!Storage::disk('public')->exists('pdf_setorans')) {
                        Storage::disk('public')->makeDirectory('pdf_setorans');
                    }

                    $pdf = Pdf::loadHTML($pdfHtml)
                        ->setPaper('a4', 'portrait')
                        ->setOption('margin-top', 2)
                        ->setOption('margin-right', 2)
                        ->setOption('margin-bottom', 2)
                        ->setOption('margin-left', 2);

                    $pdfOutput = $pdf->output();

                    if (empty($pdfOutput)) {
                        throw new \Exception('PDF output is empty');
                    }

                    Storage::disk('public')->put($path, $pdfOutput);

                    if (!Storage::disk('public')->exists($path)) {
                        throw new \Exception('PDF file not found after save');
                    }

                    $setoran->update(['koreksi_pdf_path' => $path]);

                    Log::info('✅ PDF updated successfully', ['path' => $path]);

                } catch (\Exception $e) {
                    Log::error('❌ Failed to update PDF', [
                        'setoran_id' => $setoran->id,
                        'error'      => $e->getMessage()
                    ]);

                    session()->flash('warning', 'Setoran berhasil diupdate, namun PDF gagal dibuat. Error: ' . $e->getMessage());
                }
            }

            DB::commit();

            return redirect()->route('guru.setorans.detail', $setoran->santri_id)
                ->with('success', 'Setoran berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error update setoran', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus setoran
     */
    public function destroy(int $id)
    {
        $userId = Auth::id();
        $guru = Guru::where('user_id', $userId)->first();

        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $setoran = Setoran::with('santri')->findOrFail($id);

        if ($setoran->guru_id !== $guru->id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus data ini.');
        }

        $santriId = $setoran->santri_id;

        DB::beginTransaction();

        try {
            if ($setoran->audio_path && Storage::disk('public')->exists($setoran->audio_path)) {
                Storage::disk('public')->delete($setoran->audio_path);
            }

            if ($setoran->koreksi_pdf_path && Storage::disk('public')->exists($setoran->koreksi_pdf_path)) {
                Storage::disk('public')->delete($setoran->koreksi_pdf_path);
            }

            $setoran->delete();

            DB::commit();

            return redirect()->route('guru.setorans.detail', $santriId)
                ->with('success', 'Setoran berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus setoran: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    /**
     * Download audio
     */
    public function audio(int $setoranId)
    {
        $setoran = Setoran::findOrFail($setoranId);
        $userId  = Auth::id();
        $guru    = Guru::where('user_id', $userId)->first();

        if ($setoran->guru_id !== $guru->id) {
            abort(403, 'Unauthorized access');
        }

        if (!$setoran->audio_path || !Storage::disk('public')->exists($setoran->audio_path)) {
            abort(404, 'Audio file not found');
        }

        $path     = Storage::disk('public')->path($setoran->audio_path);
        $mimeType = Storage::disk('public')->mimeType($setoran->audio_path);

        return response()->file($path, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="setoran_' . $setoran->id . '.webm"',
        ]);
    }

    /**
     * Download PDF
     */
    public function downloadPdf(int $setoranId)
    {
        $setoran = Setoran::findOrFail($setoranId);
        $userId  = Auth::id();
        $guru    = Guru::where('user_id', $userId)->first();

        if (!$guru || $setoran->guru_id !== $guru->id) {
            abort(403, 'Unauthorized access');
        }

        if (!$setoran->koreksi_pdf_path || !Storage::disk('public')->exists($setoran->koreksi_pdf_path)) {
            abort(404, 'PDF file not found');
        }

        return Storage::disk('public')->download(
            $setoran->koreksi_pdf_path,
            'koreksi_' . $setoran->santri->name . '_' . $setoran->tanggal->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Helper: Daftar Surat
     */
    private function getDaftarSurat()
    {
        return [
            '1. Al-Fatihah', '2. Al-Baqarah', '3. Ali \'Imran', '4. An-Nisa\'', '5. Al-Ma\'idah',
            '6. Al-An\'am', '7. Al-A\'raf', '8. Al-Anfal', '9. At-Taubah', '10. Yunus',
            '11. Hud', '12. Yusuf', '13. Ar-Ra\'d', '14. Ibrahim', '15. Al-Hijr',
            '16. An-Nahl', '17. Al-Isra\'', '18. Al-Kahf', '19. Maryam', '20. Taha',
            '21. Al-Anbiya\'', '22. Al-Hajj', '23. Al-Mu\'minun', '24. An-Nur', '25. Al-Furqan',
            '26. Asy-Syu\'ara\'', '27. An-Naml', '28. Al-Qasas', '29. Al-\'Ankabut', '30. Ar-Rum',
            '31. Luqman', '32. As-Sajdah', '33. Al-Ahzab', '34. Saba\'', '35. Fatir',
            '36. Yasin', '37. As-Saffat', '38. Sad', '39. Az-Zumar', '40. Ghafir',
            '41. Fussilat', '42. Asy-Syura', '43. Az-Zukhruf', '44. Ad-Dukhan', '45. Al-Jasiyah',
            '46. Al-Ahqaf', '47. Muhammad', '48. Al-Fath', '49. Al-Hujurat', '50. Qaf',
            '51. Az-Zariyat', '52. At-Tur', '53. An-Najm', '54. Al-Qamar', '55. Ar-Rahman',
            '56. Al-Waqi\'ah', '57. Al-Hadid', '58. Al-Mujadilah', '59. Al-Hasyr', '60. Al-Mumtahanah',
            '61. As-Saff', '62. Al-Jumu\'ah', '63. Al-Munafiqun', '64. At-Taghabun', '65. At-Talaq',
            '66. At-Tahrim', '67. Al-Mulk', '68. Al-Qalam', '69. Al-Haqqah', '70. Al-Ma\'arij',
            '71. Nuh', '72. Al-Jinn', '73. Al-Muzzammil', '74. Al-Muddassir', '75. Al-Qiyamah',
            '76. Al-Insan', '77. Al-Mursalat', '78. An-Naba\'', '79. An-Nazi\'at', '80. \'Abasa',
            '81. At-Takwir', '82. Al-Infitar', '83. Al-Mutaffifin', '84. Al-Insyiqaq', '85. Al-Buruj',
            '86. At-Tariq', '87. Al-A\'la', '88. Al-Ghasyiyah', '89. Al-Fajr', '90. Al-Balad',
            '91. Asy-Syams', '92. Al-Lail', '93. Ad-Duha', '94. Asy-Syarh', '95. At-Tin',
            '96. Al-\'Alaq', '97. Al-Qadr', '98. Al-Bayyinah', '99. Az-Zalzalah', '100. Al-\'Adiyat',
            '101. Al-Qari\'ah', '102. At-Takasur', '103. Al-\'Asr', '104. Al-Humazah', '105. Al-Fil',
            '106. Quraisy', '107. Al-Ma\'un', '108. Al-Kausar', '109. Al-Kafirun', '110. An-Nasr',
            '111. Al-Masad', '112. Al-Ikhlas', '113. Al-Falaq', '114. An-Nas',
        ];
    }
}
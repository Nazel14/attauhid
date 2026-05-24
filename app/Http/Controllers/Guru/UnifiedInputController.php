<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\Presensi;
use App\Models\Setoran;
use App\Models\Santri;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PresensiSesiConfig;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class UnifiedInputController extends Controller
{
    public function create()
    {
        $userId = Auth::id();
        $guru   = Guru::where('user_id', $userId)->first();
        
        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = Kelas::where('guru_id', $guru->id)->first();
        
        if (!$kelas) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        $santriList = Santri::where('kelas_id', $kelas->id)
            ->orderBy('name')
            ->get();
        
        $tanggalHariIni = Carbon::now()->format('Y-m-d');
        $daftarSurat    = $this->getDaftarSurat();
        
        $sesiList = PresensiSesiConfig::where('is_active', 1)
            ->orderBy('urutan', 'asc')
            ->get();
        
        if ($sesiList->isEmpty()) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Tidak ada sesi presensi yang aktif.');
        }

        $tahunAjaranAktif = TahunAjaran::getAktif();

        return view('guru.input.create', compact(
            'santriList',
            'tanggalHariIni',
            'guru',
            'kelas',
            'daftarSurat',
            'sesiList',
            'tahunAjaranAktif'
        ));
    }

    public function storePresensi(Request $request)
    {
        $validated = $request->validate([
            'santri_id'  => 'required|exists:santris,id',
            'tanggal'    => 'required|date',
            'sesi'       => 'required|string',
            'status'     => 'required|in:Hadir,Sakit,Izin,Alpha',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $guru = Guru::where('user_id', Auth::id())->firstOrFail();

        $exists = Presensi::where('santri_id', $request->santri_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('sesi', $request->sesi)
            ->exists();

        if ($exists) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Presensi untuk santri ini di sesi dan tanggal yang sama sudah tercatat.');
        }

        DB::beginTransaction();
        
        try {
            $presensi = Presensi::create([
                'santri_id'  => $request->santri_id,
                'guru_id'    => $guru->id,
                'tanggal'    => $request->tanggal,
                'sesi'       => $request->sesi,
                'status'     => $request->status,
                'keterangan' => $request->keterangan,
            ]);

            DB::commit();

            $santri = Santri::find($request->santri_id);

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'submit',
                    'module'        => 'presensi',
                    'description'   => "Submit presensi {$request->status} untuk {$santri->name} - Sesi {$request->sesi} - Tanggal {$request->tanggal}",
                    'model_type'    => Presensi::class,
                    'model_id'      => $presensi->id,
                    'new_values'    => [
                        'santri_name' => $santri->name,
                        'status'      => $request->status,
                        'sesi'        => $request->sesi,
                        'tanggal'     => $request->tanggal,
                    ],
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log presensi activity: ' . $e->getMessage());
            }
            
            return redirect()->route('guru.input.create')
                ->with('success', " Presensi santri \"{$santri->name}\" ({$request->status}) berhasil disimpan!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error save presensi: ' . $e->getMessage());
            
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function storeSetoran(Request $request)
    {
        $validated = $request->validate([
            'santri_id'            => 'required|exists:santris,id',
            'tanggal'              => 'required|date',
            'sesi'                 => 'required|string',
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

        $guru = Guru::where('user_id', Auth::id())->firstOrFail();

        $presensi = Presensi::where('santri_id', $request->santri_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('sesi', $request->sesi)
            ->where('status', 'Hadir')
            ->first();

        if (!$presensi) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Santri harus presensi HADIR terlebih dahulu sebelum menambah setoran.');
        }

        DB::beginTransaction();
        
        try {
            $audioPath = null;
            if ($request->filled('audio_data')) {
                $audioPath = $this->processAudioData($request->audio_data);
            }

            $pdfPath = null;
            if ($request->filled('pdf_html_content')) {
                $pdfPath = $this->processPdfData($request->pdf_html_content);
            }

            $setoran = Setoran::create([
                'santri_id'            => $request->santri_id,
                'guru_id'              => $guru->id,
                'presensi_id'          => $presensi->id,
                'tanggal'              => $request->tanggal,
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
                'audio_path'           => $audioPath,
                'koreksi_pdf_path'     => $pdfPath,
            ]);

            DB::commit();

            $santri = Santri::find($request->santri_id);

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'submit',
                    'module'        => 'setoran',
                    'description'   => "Submit setoran tambahan {$request->kategori_setoran} untuk {$santri->name} - {$request->surat_start} ayat {$request->ayat_start} s/d {$request->surat_end} ayat {$request->ayat_end} - Nilai: " . ($request->nilai ?? 100),
                    'model_type'    => Setoran::class,
                    'model_id'      => $setoran->id,
                    'new_values'    => [
                        'santri_name' => $santri->name,
                        'kategori'    => $request->kategori_setoran,
                        'surat_start' => $request->surat_start,
                        'ayat_start'  => $request->ayat_start,
                        'surat_end'   => $request->surat_end,
                        'ayat_end'    => $request->ayat_end,
                        'nilai'       => $request->nilai ?? 100,
                        'has_audio'   => !is_null($audioPath),
                        'has_pdf'     => !is_null($pdfPath),
                    ],
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log setoran activity: ' . $e->getMessage());
            }
            
            return redirect()->route('guru.input.create')
                ->with('success', " Setoran Tambahan santri \"{$santri->name}\" berhasil disimpan!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($audioPath) && $audioPath) Storage::disk('public')->delete($audioPath);
            if (isset($pdfPath) && $pdfPath) Storage::disk('public')->delete($pdfPath);
            Log::error('❌ Error save setoran: ' . $e->getMessage());
            
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function storeCombined(Request $request)
    {
        $validated = $request->validate([
            'santri_id'            => 'required|exists:santris,id',
            'tanggal'              => 'required|date',
            'sesi'                 => 'required|string',
            'status'               => 'required|in:Hadir',
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

        $guru = Guru::where('user_id', Auth::id())->firstOrFail();

        $exists = Presensi::where('santri_id', $request->santri_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('sesi', $request->sesi)
            ->exists();

        if ($exists) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Presensi sudah tercatat. Gunakan form "Setoran Tambahan" untuk menambah setoran.');
        }

        DB::beginTransaction();
        
        try {
            $presensi = Presensi::create([
                'santri_id' => $request->santri_id,
                'guru_id'   => $guru->id,
                'tanggal'   => $request->tanggal,
                'sesi'      => $request->sesi,
                'status'    => 'Hadir',
            ]);

            $audioPath = null;
            if ($request->filled('audio_data')) {
                $audioPath = $this->processAudioData($request->audio_data);
            }

            $pdfPath = null;
            if ($request->filled('pdf_html_content')) {
                $pdfPath = $this->processPdfData($request->pdf_html_content);
            }

            $setoran = Setoran::create([
                'santri_id'            => $request->santri_id,
                'guru_id'              => $guru->id,
                'presensi_id'          => $presensi->id,
                'tanggal'              => $request->tanggal,
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
                'audio_path'           => $audioPath,
                'koreksi_pdf_path'     => $pdfPath,
            ]);

            DB::commit();

            $santri = Santri::find($request->santri_id);

            // Log presensi
            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'submit',
                    'module'        => 'presensi',
                    'description'   => "Submit presensi Hadir untuk {$santri->name} - Sesi {$request->sesi} - Tanggal {$request->tanggal}",
                    'model_type'    => Presensi::class,
                    'model_id'      => $presensi->id,
                    'new_values'    => [
                        'santri_name' => $santri->name,
                        'status'      => 'Hadir',
                        'sesi'        => $request->sesi,
                        'tanggal'     => $request->tanggal,
                    ],
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log presensi activity: ' . $e->getMessage());
            }

            // Log setoran
            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'submit',
                    'module'        => 'setoran',
                    'description'   => "Submit setoran {$request->kategori_setoran} untuk {$santri->name} - {$request->surat_start} ayat {$request->ayat_start} s/d {$request->surat_end} ayat {$request->ayat_end} - Nilai: " . ($request->nilai ?? 100),
                    'model_type'    => Setoran::class,
                    'model_id'      => $setoran->id,
                    'new_values'    => [
                        'santri_name' => $santri->name,
                        'kategori'    => $request->kategori_setoran,
                        'surat_start' => $request->surat_start,
                        'ayat_start'  => $request->ayat_start,
                        'surat_end'   => $request->surat_end,
                        'ayat_end'    => $request->ayat_end,
                        'nilai'       => $request->nilai ?? 100,
                        'has_audio'   => !is_null($audioPath),
                        'has_pdf'     => !is_null($pdfPath),
                    ],
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log setoran activity: ' . $e->getMessage());
            }
            
            return redirect()->route('guru.input.create')
                ->with('success', " Presensi & Setoran santri \"{$santri->name}\" berhasil disimpan!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($audioPath) && $audioPath) Storage::disk('public')->delete($audioPath);
            if (isset($pdfPath) && $pdfPath) Storage::disk('public')->delete($pdfPath);
            Log::error('❌ Error save combined: ' . $e->getMessage());
            
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function processAudioData(string $audioData): ?string
    {
        try {
            if (!preg_match('/^data:audio\/([a-zA-Z0-9\-]+)(?:;codecs=[a-zA-Z0-9\-,]+)?;base64,/', $audioData, $type)) {
                throw new \Exception('Invalid audio format');
            }
            
            $audioData = substr($audioData, strpos($audioData, ',') + 1);
            $audioData = base64_decode($audioData);
            
            if ($audioData === false || strlen($audioData) < 100) {
                throw new \Exception('Failed to decode audio data');
            }
            
            $extension    = strtolower($type[1] ?? 'webm');
            $extensionMap = ['webm' => 'webm', 'ogg' => 'ogg', 'mp3' => 'mp3', 'mpeg' => 'mp3', 'wav' => 'wav', 'x-wav' => 'wav'];
            $extension    = $extensionMap[$extension] ?? 'webm';
            
            $fileName = "audio_" . time() . "_" . substr(md5(uniqid()), 0, 8) . ".{$extension}";
            $path     = 'audio_setorans/' . $fileName;
            
            if (!Storage::disk('public')->exists('audio_setorans')) {
                Storage::disk('public')->makeDirectory('audio_setorans');
            }
            
            Storage::disk('public')->put($path, $audioData);
            
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('Audio file not found after save');
            }
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to save audio: ' . $e->getMessage());
            return null;
        }
    }

    private function processPdfData(string $pdfHtml): ?string
    {
        try {
            if (strlen($pdfHtml) < 100 || stripos($pdfHtml, '<html') === false) {
                throw new \Exception('Invalid HTML format');
            }
            
            $fileName = "pdf_" . time() . "_" . substr(md5(uniqid()), 0, 8) . ".pdf";
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
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to generate PDF: ' . $e->getMessage());
            return null;
        }
    }

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
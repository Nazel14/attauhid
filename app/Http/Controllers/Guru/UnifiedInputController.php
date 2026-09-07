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
use Inertia\Inertia;

class UnifiedInputController extends Controller
{
    use \App\Traits\UsesKelasAktif;

    public function create()
    {
        $guru = $this->guruAktif();

        if (!$guru) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        $kelas = $this->kelasAktif();

        if (!$kelas) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Kelas aktif tidak valid. Silakan pilih kelas kembali.');
        }

        $santriList = Santri::where('kelas_id', $kelas->id)
            ->orderBy('name')
            ->get();

        // Agregat juz hafalan — dasar progress santri Al-Qur'an. Santri
        // Iqro/Aisar dihitung lewat helper model, bukan dari agregat ini.
        $hafalanPerSantri = Setoran::whereIn('santri_id', $santriList->pluck('id'))
            ->where('kategori_setoran', 'Hafalan')
            ->selectRaw('santri_id, SUM(total_juz) as total_juz')
            ->groupBy('santri_id')
            ->get()
            ->keyBy('santri_id');

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

        return \Inertia\Inertia::render('Guru/InputSetoran', [
            'santriList' => $santriList->map(fn ($s) => [
                'id'              => $s->id,
                'name'            => $s->name,
                'target_metode'   => $s->target_metode?->value ?? 'quran',
                'target_metode_2' => $s->target_metode_2?->value,
                'target_jilid'    => $s->target_jilid,
                'progress_persen' => $this->hitungProgressPersen($s, $hafalanPerSantri->get($s->id)),
                'progress_juz2'   => $this->hitungProgressJuz2($s, $hafalanPerSantri->get($s->id)),
            ])->values(),
            'sesiList'       => $sesiList->pluck('sesi_name')->values(),
            'daftarSurat'    => $daftarSurat,
            'tanggalHariIni' => $tanggalHariIni,
            'kelasName'      => $kelas->name,
            'tahunAjaran'    => $tahunAjaranAktif ? ($tahunAjaranAktif->nama ?? $tahunAjaranAktif->name) : null,
        ]);
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

        if (! $this->santriDiKelasAktif($request->santri_id)) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Santri tersebut tidak ada di kelas yang sedang aktif.');
        }

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
            'metode'               => 'nullable|string',
            'surat_start' => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|string',
            'ayat_start'  => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|integer',
            'surat_end'   => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|string',
            'ayat_end'    => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|integer',
            'iqro_jilid'           => 'nullable|string',
            'iqro_halaman'         => 'nullable|integer',
            'iqro_halaman_end'     => 'nullable|integer',
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

        if (! $this->santriDiKelasAktif($request->santri_id)) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Santri tersebut tidak ada di kelas yang sedang aktif.');
        }

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

        // Petakan field setoran (Iqro pakai ulang kolom surat/ayat)
        $map = $this->mapSetoranFields($request);

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
                'metode'               => $map['metode'],
                'surat_start'          => $map['surat_start'],
                'ayat_start'           => $map['ayat_start'],
                'surat_end'            => $map['surat_end'],
                'ayat_end'             => $map['ayat_end'],
                'page_start'           => $map['page_start'],
                'page_end'             => $map['page_end'],
                'total_page'           => $map['total_page'],
                'juz_start'            => $map['juz_start'],
                'juz_end'              => $map['juz_end'],
                'total_juz'            => $map['total_juz'],
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
                    'description'   => "Submit setoran tambahan {$request->kategori_setoran} untuk {$santri->name} - {$map['surat_start']} ({$map['ayat_start']} - {$map['ayat_end']}) - Nilai: " . ($request->nilai ?? 100),
                    'model_type'    => Setoran::class,
                    'model_id'      => $setoran->id,
                    'new_values'    => [
                        'santri_name' => $santri->name,
                        'kategori'    => $request->kategori_setoran,
                        'metode'      => $map['metode'],
                        'surat_start' => $map['surat_start'],
                        'ayat_start'  => $map['ayat_start'],
                        'surat_end'   => $map['surat_end'],
                        'ayat_end'    => $map['ayat_end'],
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
            'metode'               => 'nullable|string',
            'surat_start' => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|string',
            'ayat_start'  => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|integer',
            'surat_end'   => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|string',
            'ayat_end'    => 'required_unless:metode,Iqro,Ummi,Aisar|nullable|integer',
            'iqro_jilid'           => 'nullable|string',
            'iqro_halaman'         => 'nullable|integer',
            'iqro_halaman_end'     => 'nullable|integer',
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

        if (! $this->santriDiKelasAktif($request->santri_id)) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Santri tersebut tidak ada di kelas yang sedang aktif.');
        }

        $exists = Presensi::where('santri_id', $request->santri_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('sesi', $request->sesi)
            ->exists();

        if ($exists) {
            return redirect()->route('guru.input.create')
                ->withInput()
                ->with('error', 'Presensi sudah tercatat. Gunakan form "Setoran Tambahan" untuk menambah setoran.');
        }

        // Petakan field setoran (Iqro pakai ulang kolom surat/ayat)
        $map = $this->mapSetoranFields($request);

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
                'metode'               => $map['metode'],
                'surat_start'          => $map['surat_start'],
                'ayat_start'           => $map['ayat_start'],
                'surat_end'            => $map['surat_end'],
                'ayat_end'             => $map['ayat_end'],
                'page_start'           => $map['page_start'],
                'page_end'             => $map['page_end'],
                'total_page'           => $map['total_page'],
                'juz_start'            => $map['juz_start'],
                'juz_end'              => $map['juz_end'],
                'total_juz'            => $map['total_juz'],
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
                    'description'   => "Submit setoran {$request->kategori_setoran} untuk {$santri->name} - {$map['surat_start']} ({$map['ayat_start']} - {$map['ayat_end']}) - Nilai: " . ($request->nilai ?? 100),
                    'model_type'    => Setoran::class,
                    'model_id'      => $setoran->id,
                    'new_values'    => [
                        'santri_name' => $santri->name,
                        'kategori'    => $request->kategori_setoran,
                        'metode'      => $map['metode'],
                        'surat_start' => $map['surat_start'],
                        'ayat_start'  => $map['ayat_start'],
                        'surat_end'   => $map['surat_end'],
                        'ayat_end'    => $map['ayat_end'],
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

    /**
     * Petakan field setoran ke kolom database.
     * - Al-Qur'an: pakai surat_start/ayat_start/dst apa adanya + page/juz.
     * - Iqro/Ummi: surat_* = "Iqro Jilid N", ayat_* = nomor halaman, page/juz null.
     */
    private function mapSetoranFields(Request $request): array
    {
        $metode  = $request->metode ?: 'Al-Qur\'an';
        $isJilid = in_array($metode, ['Iqro', 'Ummi', 'Aisar']);

        if ($isJilid) {
            $jilid    = $request->iqro_jilid ?: '1';
            $halStart = (int) ($request->iqro_halaman ?: 0);
            $halEnd   = (int) ($request->iqro_halaman_end ?: $halStart);
            if ($halEnd < $halStart) {
                $halEnd = $halStart;
            }
            $label = match ($metode) {
                'Ummi'  => 'Ummi Jilid ',
                'Aisar' => 'Aisar Jilid ',
                default => 'Iqro Jilid ',
            } . $jilid;

            return [
                'metode'      => $metode,
                'surat_start' => $label,
                'surat_end'   => $label,
                'ayat_start'  => $halStart,
                'ayat_end'    => $halEnd,
                // Iqro tidak memakai halaman/juz mushaf
                'page_start'  => null,
                'page_end'    => null,
                'total_page'  => null,
                'juz_start'   => null,
                'juz_end'     => null,
                'total_juz'   => null,
            ];
        }

        return [
            'metode'      => 'Al-Qur\'an',
            'surat_start' => $request->surat_start,
            'surat_end'   => $request->surat_end,
            'ayat_start'  => $request->ayat_start,
            'ayat_end'    => $request->ayat_end,
            'page_start'  => $request->page_start,
            'page_end'    => $request->page_end,
            'total_page'  => $request->total_page,
            'juz_start'   => $request->juz_start,
            'juz_end'     => $request->juz_end,
            'total_juz'   => $request->total_juz,
        ];
    }

    /**
     * Pastikan santri benar-benar ada di kelas yang sedang aktif.
     */
    private function santriDiKelasAktif($santriId): bool
    {
        return Santri::whereKey($santriId)
            ->where('kelas_id', $this->kelasAktifId())
            ->exists();
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
        $pdfHtml = trim($pdfHtml);

        if (strlen($pdfHtml) < 30) {
            throw new \Exception('HTML content too short (' . strlen($pdfHtml) . ' chars)');
        }

        // Kalau HTML fragmen (tanpa tag <html>), bungkus jadi dokumen lengkap
        // supaya Murajaah/Tilawah yang mengirim potongan HTML tetap bisa jadi PDF.
        if (stripos($pdfHtml, '<html') === false) {
            $pdfHtml = '<!DOCTYPE html><html><head><meta charset="utf-8">'
                . '<style>body{font-family:DejaVu Sans, sans-serif;font-size:12px;}</style>'
                . '</head><body>' . $pdfHtml . '</body></html>';
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

        /**
     * Riwayat setoran santri (Hafalan / Murajaah / Tilawah) untuk panel
     * "Riwayat Setoran" di form input. Dibatasi santri di kelas aktif.
     */
    public function riwayatSetoranSantri(Request $request)
    {
        $santriId = (int) $request->query('santri_id');

        if (! $santriId || ! $this->santriDiKelasAktif($santriId)) {
            return response()->json(['data' => []]);
        }

        $rows = Setoran::where('santri_id', $santriId)
            ->whereNotNull('kategori_setoran')
            ->with('guru')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $data = $rows->map(function ($s) {
            $metode  = $s->metode ?: "Al-Qur'an";
            $isJilid = in_array($metode, ['Iqro', 'Ummi', 'Aisar']);

            if ($isJilid) {
                $hal = (int) $s->ayat_start;
                $end = (int) $s->ayat_end;
                $materi = $s->surat_start . ' — Hal. ' . $hal
                        . ($end && $end !== $hal ? '–' . $end : '');
            } else {
                $materi = $s->surat_start . ' : ' . $s->ayat_start
                        . ' – ' . $s->surat_end . ' : ' . $s->ayat_end;
            }

            return [
                'id'       => $s->id,
                'tanggal'  => optional($s->tanggal)->format('d/m/Y'),
                'kategori' => $s->kategori_setoran,
                'metode'   => $metode,
                'materi'   => $materi,
                'nilai'    => $s->nilai,
                'guru'     => $s->guru->name ?? null,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /* ================================================================
     * HELPER PROGRESS — sengaja disalin dari Guru\DashboardController
     * agar rumusnya identik. Kalau salah satu diubah, ubah keduanya.
     * ================================================================ */
    private function hitungProgressPersen(Santri $santri, $agg): int
    {
        if ($santri->isJilidBased()) { // iqro ATAU aisar
            $jilid  = (int) ($santri->target_jilid ?? 1);
            $target = (int) (config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $jilid) ?? 0);
            if ($target <= 0) return 0;

            $totalHalaman = count($santri->halamanIqroTersetorKelas($jilid, $santri->kelas_id));
            return (int) round(min($totalHalaman / $target * 100, 100));
        }

        $totalJuz = (float) ($agg->total_juz ?? 0);
        $target   = (float) ($santri->target_juz ?? 30);
        if ($target <= 0) return 0;

        return (int) round(min($totalJuz / $target * 100, 100));
    }

    private function hitungProgressJuz2(Santri $santri, $agg): int
    {
        if (! $santri->hasQuranSecondary()) return 0;

        $target = (float) ($santri->target_juz ?? 0);
        if ($target <= 0) return 0;

        $totalJuz = (float) ($agg->total_juz ?? 0);
        return (int) round(min($totalJuz / $target * 100, 100));
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
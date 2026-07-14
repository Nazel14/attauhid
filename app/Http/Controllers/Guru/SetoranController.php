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
use Inertia\Inertia;

class SetoranController extends Controller
{
    /**
     * Tampilkan daftar setoran.
     * Progress per KELAS (kelas_id) — kebal-tanggal, reset saat naik kelas,
     * akumulatif lintas semester. Konsisten dengan Dashboard & Rapot.
     */
   public function index()
{
    $userId = Auth::id();
    $guru   = Guru::where('user_id', $userId)->first();
    if (!$guru)  return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan.');

    $kelas = Kelas::where('guru_id', $guru->id)->first();
    if (!$kelas) return redirect()->route('guru.dashboard')->with('error', 'Anda belum ditugaskan sebagai wali kelas.');

    $tahunAjaranAktif = TahunAjaran::getAktif();
    $tahunAjaranId    = $tahunAjaranAktif?->id;

    $santris = Santri::where('kelas_id', $kelas->id)
        ->with(['guru', 'kelas', 'user'])
        ->get()
        ->map(function ($santri) {
            $kelasId  = $santri->kelas_id;
            $setorans = Setoran::where('santri_id', $santri->id)->where('kelas_id', $kelasId)->get();

            
            $isIqro       = $santri->isJilidBased(); // iqro ATAU aisar
            $metodeSantri = $santri->jilidConfigKey(); // 'iqro' / 'aisar'

            if ($isIqro) {
                $targetJilid   = (int) ($santri->target_jilid ?? 1);
                $totalHalaman  = count($santri->halamanIqroTersetorKelas($targetJilid, $kelasId)); // dedup, scope kelas, label otomatis ikut metode santri
                $totalHalJilid = (int) (config($santri->jilidConfigKey() . '.halaman_per_jilid.' . $targetJilid) ?? 0);
                $persenTahu    = $totalHalJilid > 0;
                $progress      = $persenTahu ? min(round(($totalHalaman / $totalHalJilid) * 100), 100) : 0;
                $targetDisplay = 'Jilid ' . $targetJilid;
                $capaianJuz    = 0.0;
            } else {
                $totalHafalanJuz = round($setorans->where('kategori_setoran', 'Hafalan')->sum('total_juz'), 2);
                $targetJuz       = $santri->target_juz ?? 1.00;
                $totalHalaman    = 0;
                $totalHalJilid   = 0;
                $persenTahu      = true;
                $progress        = $targetJuz > 0 ? min(round(($totalHafalanJuz / $targetJuz) * 100), 100) : 0;
                $targetDisplay   = number_format($targetJuz, 2) . ' juz';
                $capaianJuz      = $totalHafalanJuz;
            }

            return [
                'id'                    => $santri->id,
                'name'                  => $santri->name,
                'nis'                   => $santri->nis,
                'gender'                => $santri->gender,
                'photo_url'             => $santri->user?->profile_photo ? asset('storage/' . $santri->user->profile_photo) : null,
                'is_iqro'               => $isIqro,
                'total_hafalan'         => $setorans->where('kategori_setoran', 'Hafalan')->count(),
                'total_murajaah'        => $setorans->where('kategori_setoran', 'Murajaah')->count(),
                'total_tilawah'         => $setorans->where('kategori_setoran', 'Tilawah')->count(),
                'total_setoran'         => $setorans->count(),
                'capaian_juz'           => $capaianJuz,
                'total_halaman_iqro'    => $totalHalaman,
                'total_halaman_jilid'   => $totalHalJilid,
                'iqro_persen_diketahui' => $persenTahu,
                'target_jilid'          => $santri->target_jilid,
                'target_display'        => $targetDisplay,
                'progress_persen'       => (int) $progress,
            ];
        })
        ->sortBy('name')->values();

    $setoranHariIniQuery = Setoran::whereIn('santri_id', $santris->pluck('id'))->whereDate('tanggal', today());
    if ($tahunAjaranId) $setoranHariIniQuery->where('tahun_ajaran_id', $tahunAjaranId);
    $setoranHariIni = $setoranHariIniQuery->get();

    return Inertia::render('Guru/Setoran/Index', [
        'kelas'          => ['id' => $kelas->id, 'name' => $kelas->name],
        'genderSummary'  => [
            'ikhwan' => $santris->where('gender', 'Laki-laki')->count(),
            'akhwat' => $santris->where('gender', 'Perempuan')->count(),
            'total'  => $santris->count(),
        ],
        'setoranHariIni' => [
            'hafalan'  => $setoranHariIni->where('kategori_setoran', 'Hafalan')->count(),
            'tilawah'  => $setoranHariIni->where('kategori_setoran', 'Tilawah')->count(),
            'murajaah' => $setoranHariIni->where('kategori_setoran', 'Murajaah')->count(),
        ],
        'tahunAjaranAktif' => $tahunAjaranAktif ? ['nama' => $tahunAjaranAktif->nama, 'semester' => $tahunAjaranAktif->semester] : null,
        'santris'          => $santris,
    ]);
}

    /**
     * Tampilkan detail setoran per santri.
     * CATATAN: halaman ini sengaja PER-SEMESTER (dropdown tahun ajaran),
     * jadi tetap memfilter via tahun_ajaran_id — bukan kelas_id. Tidak diubah.
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

        $santri = Santri::with(['kelas', 'guru', 'user'])
            ->where('id', $santriId)
            ->where('kelas_id', $kelas->id)
            ->firstOrFail();

        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // ===== Daftar semester (TA) yang punya data untuk santri ini =====
        $taIds = Setoran::where('santri_id', $santriId)
                ->whereNotNull('tahun_ajaran_id')->distinct()->pluck('tahun_ajaran_id')
            ->merge(
                Presensi::where('santri_id', $santriId)
                    ->whereNotNull('tahun_ajaran_id')->distinct()->pluck('tahun_ajaran_id')
            )
            ->unique()->filter()->values();

        if ($tahunAjaranId && !$taIds->contains($tahunAjaranId)) {
            $taIds->push($tahunAjaranId);
        }

        $historyByTa = \App\Models\SantriKelasHistory::where('santri_id', $santriId)
            ->whereIn('tahun_ajaran_id', $taIds)
            ->with('kelas')
            ->get()
            ->keyBy('tahun_ajaran_id');

        $taList = TahunAjaran::whereIn('id', $taIds)
            ->orderByDesc('tanggal_mulai')
            ->get()
            ->map(function ($ta) use ($historyByTa) {
                $h = $historyByTa->get($ta->id);
                $ta->kelas_konteks = $h?->kelas?->name;
                return $ta;
            });

        $taTerpilih = request('ta');
        if (!$taTerpilih) {
            $taTerpilih = ($tahunAjaranId && $taIds->contains($tahunAjaranId))
                ? $tahunAjaranId
                : $taList->first()?->id;
        }
        $taTerpilih = $taTerpilih ? (int) $taTerpilih : null;

        $taObjekTerpilih = $taList->firstWhere('id', $taTerpilih);
        $isSemesterAktif = $taTerpilih && $taTerpilih === $tahunAjaranId;
        $kelasKonteks    = $taObjekTerpilih->kelas_konteks ?? ($santri->kelas->name ?? '-');

        $tanggalMulai = request('tanggal_mulai');
        $tanggalAkhir = request('tanggal_akhir');

        // ===== Setoran (difilter per semester) =====
        $setoranQuery = Setoran::where('santri_id', $santriId)
            ->with('presensi')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');
        if ($taTerpilih) {
            $setoranQuery->where('tahun_ajaran_id', $taTerpilih);
        }
        if ($tanggalMulai && $tanggalAkhir) {
            $setoranQuery->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
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

        // ===== Presensi (difilter per semester) =====
        $presensiQuery = Presensi::where('santri_id', $santriId);
        if ($taTerpilih) {
            $presensiQuery->where('tahun_ajaran_id', $taTerpilih);
        }
        if ($tanggalMulai && $tanggalAkhir) {
            $presensiQuery->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
        }
        $presensis = $presensiQuery->get();

        $presensiStats = [
            'total_hadir' => $presensis->where('status', 'Hadir')->count(),
            'total_sakit' => $presensis->where('status', 'Sakit')->count(),
            'total_izin'  => $presensis->where('status', 'Izin')->count(),
            'total_alpha' => $presensis->where('status', 'Alpha')->count(),
        ];

        $totalHafalanJuz = round(
            $setorans->where('kategori_setoran', 'Hafalan')->sum('total_juz'),
            2
        );

        $targetJuz = $santri->getTargetJuzByTahunAjaran($taTerpilih) ?: ($santri->target_juz ?? 1.00);

        $progressHafalan = $targetJuz > 0
            ? min(($totalHafalanJuz / $targetJuz) * 100, 100)
            : 0;

        $setoranHafalan  = $setorans->where('kategori_setoran', 'Hafalan');
        $setoranMurajaah = $setorans->where('kategori_setoran', 'Murajaah');
        $setoranTilawah  = $setorans->where('kategori_setoran', 'Tilawah');

        $metodeSantri = $santri->target_metode?->value ?? 'quran';
        $isIqro       = in_array($metodeSantri, ['iqro', 'aisar']);

        $halamanIqroPerKategori = ['Hafalan' => 0, 'Murajaah' => 0, 'Tilawah' => 0];
        $totalHalamanIqro = 0;
        if ($isIqro) {
            $targetJilid = (int)($santri->target_jilid ?? 1);
            $mapKat = ['Hafalan' => $setoranHafalan, 'Murajaah' => $setoranMurajaah, 'Tilawah' => $setoranTilawah];
            foreach ($mapKat as $kat => $col) {
                $jumlah = 0;
                foreach ($col as $s) {
                    if (!preg_match('/^(Iqro|Aisar) Jilid (\d+)/i', (string)$s->surat_start, $mj)) continue;
                    if (strtolower($mj[1]) !== $metodeSantri) continue; // jangan campur Iqro vs Aisar
                    if ($kat === 'Hafalan' && (int)$mj[2] !== $targetJilid) continue;
                    $h1 = (int)$s->ayat_start;
                    $h2 = (int)($s->ayat_end ?: $s->ayat_start);
                    if ($h1 > 0 && $h2 >= $h1) $jumlah += ($h2 - $h1 + 1);
                }
                $halamanIqroPerKategori[$kat] = $jumlah;
            }
            $totalHalamanIqro = $halamanIqroPerKategori['Hafalan'];
        }

        $fmt = fn ($col) => $col->values()->map(fn ($s) => [
        'id'        => $s->id,
        'tanggal'   => $s->tanggal->format('d/m/Y'),
        'range'     => $this->formatRangeSetoran($s),
        'total_juz' => (float) $s->total_juz,
        'nilai'     => $s->nilai,
        'has_audio' => (bool) $s->audio_path,
        'audio_url' => $s->audio_path ? route('guru.setorans.audio', $s->id) : null,
        'pdf_url'   => $s->koreksi_pdf_path ? asset('storage/' . $s->koreksi_pdf_path) : null,
        'catatan'   => $s->catatan_guru,
    ]);

    return Inertia::render('Guru/Setoran/Detail', [
        'santri' => [
            'id'           => $santri->id,
            'name'         => $santri->name,
            'nis'          => $santri->nis,
            'gender'       => $santri->gender,
            'photo_url'    => $santri->user?->profile_photo ? asset('storage/' . $santri->user->profile_photo) : null,
            'kelas_name'   => $santri->kelas->name ?? '-',
            'target_jilid' => $santri->target_jilid,
        ],
        'isIqro' => $isIqro,
        'taList' => $taList->map(fn ($ta) => [
            'id'            => $ta->id,
            'nama'          => $ta->nama,
            'semester'      => $ta->semester,
            'kelas_konteks' => $ta->kelas_konteks,
            'aktif'         => $ta->id === optional($tahunAjaranAktif)->id,
        ])->values(),
        'taTerpilih'      => $taTerpilih,
        'taObjekTerpilih' => $taObjekTerpilih ? ['nama' => $taObjekTerpilih->nama, 'semester' => $taObjekTerpilih->semester] : null,
        'kelasKonteks'    => $kelasKonteks,
        'isSemesterAktif' => $isSemesterAktif,
        'filter'          => ['tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalAkhir],
        'progress'        => [
            'total_hafalan_juz'  => $totalHafalanJuz,
            'target_juz'         => $targetJuz,
            'progress_persen'    => round($progressHafalan, 2),
            'total_halaman_iqro' => $totalHalamanIqro,
        ],
        'stats' => [
            'hafalan'  => ['total' => $setoranStats->get('Hafalan')->total,  'total_juz' => $setoranStats->get('Hafalan')->total_juz,  'halaman' => $halamanIqroPerKategori['Hafalan']],
            'murajaah' => ['total' => $setoranStats->get('Murajaah')->total, 'total_juz' => $setoranStats->get('Murajaah')->total_juz, 'halaman' => $halamanIqroPerKategori['Murajaah']],
            'tilawah'  => ['total' => $setoranStats->get('Tilawah')->total,  'total_juz' => $setoranStats->get('Tilawah')->total_juz,  'halaman' => $halamanIqroPerKategori['Tilawah']],
        ],
        'setoran' => [
            'hafalan'  => $fmt($setoranHafalan),
            'murajaah' => $fmt($setoranMurajaah),
            'tilawah'  => $fmt($setoranTilawah),
        ],
    ]);

    }

    private function formatRangeSetoran($s): string
{
    if (preg_match('/^(Iqro|Aisar) Jilid (\d+)/i', (string) $s->surat_start, $m)) {
        $metode = $m[1];
        $jilid  = $m[2];
        $h1 = $s->ayat_start;
        $h2 = $s->ayat_end;
        $sep = ($h1 == $h2) ? '' : '–' . $h2;
        return "{$metode} Jilid {$jilid} · Hal {$h1}{$sep}";
    }
    return "{$s->surat_start}:{$s->ayat_start} → {$s->surat_end}:{$s->ayat_end}";
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
            '1. Al-Fatihah',
            '2. Al-Baqarah',
            '3. Ali \'Imran',
            '4. An-Nisa\'',
            '5. Al-Ma\'idah',
            '6. Al-An\'am',
            '7. Al-A\'raf',
            '8. Al-Anfal',
            '9. At-Taubah',
            '10. Yunus',
            '11. Hud',
            '12. Yusuf',
            '13. Ar-Ra\'d',
            '14. Ibrahim',
            '15. Al-Hijr',
            '16. An-Nahl',
            '17. Al-Isra\'',
            '18. Al-Kahf',
            '19. Maryam',
            '20. Taha',
            '21. Al-Anbiya\'',
            '22. Al-Hajj',
            '23. Al-Mu\'minun',
            '24. An-Nur',
            '25. Al-Furqan',
            '26. Asy-Syu\'ara\'',
            '27. An-Naml',
            '28. Al-Qasas',
            '29. Al-\'Ankabut',
            '30. Ar-Rum',
            '31. Luqman',
            '32. As-Sajdah',
            '33. Al-Ahzab',
            '34. Saba\'',
            '35. Fatir',
            '36. Yasin',
            '37. As-Saffat',
            '38. Sad',
            '39. Az-Zumar',
            '40. Ghafir',
            '41. Fussilat',
            '42. Asy-Syura',
            '43. Az-Zukhruf',
            '44. Ad-Dukhan',
            '45. Al-Jasiyah',
            '46. Al-Ahqaf',
            '47. Muhammad',
            '48. Al-Fath',
            '49. Al-Hujurat',
            '50. Qaf',
            '51. Az-Zariyat',
            '52. At-Tur',
            '53. An-Najm',
            '54. Al-Qamar',
            '55. Ar-Rahman',
            '56. Al-Waqi\'ah',
            '57. Al-Hadid',
            '58. Al-Mujadilah',
            '59. Al-Hasyr',
            '60. Al-Mumtahanah',
            '61. As-Saff',
            '62. Al-Jumu\'ah',
            '63. Al-Munafiqun',
            '64. At-Taghabun',
            '65. At-Talaq',
            '66. At-Tahrim',
            '67. Al-Mulk',
            '68. Al-Qalam',
            '69. Al-Haqqah',
            '70. Al-Ma\'arij',
            '71. Nuh',
            '72. Al-Jinn',
            '73. Al-Muzzammil',
            '74. Al-Muddassir',
            '75. Al-Qiyamah',
            '76. Al-Insan',
            '77. Al-Mursalat',
            '78. An-Naba\'',
            '79. An-Nazi\'at',
            '80. \'Abasa',
            '81. At-Takwir',
            '82. Al-Infitar',
            '83. Al-Mutaffifin',
            '84. Al-Insyiqaq',
            '85. Al-Buruj',
            '86. At-Tariq',
            '87. Al-A\'la',
            '88. Al-Ghasyiyah',
            '89. Al-Fajr',
            '90. Al-Balad',
            '91. Asy-Syams',
            '92. Al-Lail',
            '93. Ad-Duha',
            '94. Asy-Syarh',
            '95. At-Tin',
            '96. Al-\'Alaq',
            '97. Al-Qadr',
            '98. Al-Bayyinah',
            '99. Az-Zalzalah',
            '100. Al-\'Adiyat',
            '101. Al-Qari\'ah',
            '102. At-Takasur',
            '103. Al-\'Asr',
            '104. Al-Humazah',
            '105. Al-Fil',
            '106. Quraisy',
            '107. Al-Ma\'un',
            '108. Al-Kausar',
            '109. Al-Kafirun',
            '110. An-Nasr',
            '111. Al-Masad',
            '112. Al-Ikhlas',
            '113. Al-Falaq',
            '114. An-Nas',
        ];
    }
}
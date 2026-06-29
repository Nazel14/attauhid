<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\KenaikanUsulan;
use App\Models\KelasNotifikasi;
use App\Models\SantriKelasHistory;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KenaikanController extends Controller
{
    /**
     * Guru langsung menaikkan satu / banyak santri ke kelas tujuan.
     * Mencatat history kelas (asal & tujuan) + notifikasi ke wali kelas tujuan.
     */
    public function naikkan(Request $request)
    {
        $validated = $request->validate([
            'santri_ids'      => 'required|array|min:1',
            'santri_ids.*'    => 'integer|exists:santris,id',
            'kelas_tujuan_id' => 'required|integer|exists:kelas,id',
        ]);

        $guru = Guru::where('user_id', Auth::id())->first();
        if (!$guru) return back()->with('error', 'Data guru tidak ditemukan.');

        $kelasAsal = Kelas::where('guru_id', $guru->id)->first();
        if (!$kelasAsal) return back()->with('error', 'Kelas asal tidak ditemukan.');

        $tahunAjaran = TahunAjaran::getAktif();
        if (!$tahunAjaran) return back()->with('error', 'Belum ada tahun ajaran aktif.');

        // Hanya semester genap
        if ((int) $tahunAjaran->semester !== 2) {
            return back()->with('error', 'Kenaikan kelas hanya dapat dilakukan pada semester genap (akhir tahun ajaran).');
        }

        // Jendela waktu: 1 bulan sebelum semester berakhir
        $selesai = $tahunAjaran->tanggal_selesai ?? ($tahunAjaran->tanggal_akhir ?? null);
        if ($selesai) {
            $selesaiC = $selesai instanceof Carbon ? $selesai : Carbon::parse($selesai);
            if (now()->lt($selesaiC->copy()->subMonth())) {
                return back()->with('error', 'Kenaikan kelas baru bisa dilakukan mulai 1 bulan sebelum semester berakhir.');
            }
        }

        $kelasTujuan = Kelas::find($validated['kelas_tujuan_id']);
        if (!$kelasTujuan) return back()->with('error', 'Kelas tujuan tidak ditemukan.');
        if ((int) $kelasTujuan->id === (int) $kelasAsal->id) {
            return back()->with('warning', 'Kelas tujuan sama dengan kelas asal.');
        }

        // ID santri yang sudah dinaikkan tahun ini → tidak boleh naik lagi
        $sudahNaik = KenaikanUsulan::where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('status', 'disetujui')
            ->pluck('santri_id')
            ->all();

        // Hanya santri yang benar-benar di kelas guru ini & belum naik tahun ini
        $santriList = Santri::whereIn('id', $validated['santri_ids'])
            ->where('kelas_id', $kelasAsal->id)
            ->whereNotIn('id', $sudahNaik)
            ->get();

        if ($santriList->isEmpty()) {
            return back()->with('warning', 'Tidak ada santri valid untuk dinaikkan (mungkin sudah dinaikkan tahun ini).');
        }

        $berhasil = 0;

        try {
            DB::transaction(function () use ($santriList, $kelasAsal, $kelasTujuan, $tahunAjaran, $guru, &$berhasil) {
                $tglMasukDefault = $tahunAjaran->tanggal_mulai
                    ? Carbon::parse($tahunAjaran->tanggal_mulai)->toDateString()
                    : now()->startOfYear()->toDateString();

                foreach ($santriList as $santri) {
                    $kelasAsalId = $santri->kelas_id;

                    // 1) Tutup history kelas asal yang masih terbuka
                    $ditutup = SantriKelasHistory::where('santri_id', $santri->id)
                        ->whereNull('tanggal_keluar')
                        ->update(['tanggal_keluar' => now()->toDateString()]);

                    // 2) Backfill history asal jika belum ada (santri lama tanpa history)
                    if ($ditutup === 0 && $kelasAsalId) {
                        SantriKelasHistory::create([
                            'santri_id'       => $santri->id,
                            'kelas_id'        => $kelasAsalId,
                            'tahun_ajaran_id' => $tahunAjaran->id,
                            'tanggal_masuk'   => $tglMasukDefault,
                            'tanggal_keluar'  => now()->toDateString(),
                            'keterangan'      => 'Kelas asal (tercatat otomatis saat naik kelas)',
                        ]);
                    }

                    // 3) Buka history baru di kelas tujuan
                    SantriKelasHistory::create([
                        'santri_id'       => $santri->id,
                        'kelas_id'        => $kelasTujuan->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'tanggal_masuk'   => now()->toDateString(),
                        'tanggal_keluar'  => null,
                        'keterangan'      => 'Naik kelas dari ' . $kelasAsal->name,
                    ]);

                    // 4) Pindahkan santri + ikut wali kelas tujuan
                    $santri->kelas_id = $kelasTujuan->id;
                    $santri->guru_id  = $kelasTujuan->guru_id;
                    $santri->save();

                    // 5) Catat log kenaikan
                    KenaikanUsulan::updateOrCreate(
                        ['santri_id' => $santri->id, 'tahun_ajaran_id' => $tahunAjaran->id],
                        ['guru_id' => $guru->id, 'status' => 'disetujui', 'catatan' => 'Naik ke ' . $kelasTujuan->name]
                    );

                    $berhasil++;
                }

                // 6) Notifikasi ke wali kelas tujuan (bukan diri sendiri)
                if ($kelasTujuan->guru_id && (int) $kelasTujuan->guru_id !== (int) $guru->id) {
                    KelasNotifikasi::create([
                        'guru_id'  => $kelasTujuan->guru_id,
                        'kelas_id' => $kelasTujuan->id,
                        'tipe'     => 'kedatangan_santri',
                        'judul'    => $berhasil . ' santri baru dari ' . $kelasAsal->name,
                        'pesan'    => 'Kelas ' . $kelasTujuan->name . ' menerima ' . $berhasil . ' santri dari ' . $kelasAsal->name . '.',
                        'data'     => [
                            'jumlah'       => $berhasil,
                            'kelas_asal'   => $kelasAsal->name,
                            'kelas_tujuan' => $kelasTujuan->name,
                            'santri'       => $santriList->pluck('name')->values()->all(),
                            'oleh'         => $guru->name ?? null,
                        ],
                    ]);
                }
            });
        } catch (\Exception $e) {
            \Log::error('Gagal naik kelas: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses kenaikan kelas.');
        }

        return redirect()->route('guru.rapot.index')
            ->with('success', $berhasil . ' santri berhasil dinaikkan ke ' . $kelasTujuan->name . '.');
    }
}
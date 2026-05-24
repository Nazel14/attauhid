<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PresensiSesiConfig;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PresensiController extends Controller
{
    /**
     * Tampilkan daftar presensi
     */
    public function index(Request $request)
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

        // ✅ Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // Filter parameters
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->subDays(7)->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->format('Y-m-d'));
        $sesiFilter   = $request->input('sesi');

        // Ambil santri dengan statistik presensi
        $santriList = Santri::where('kelas_id', $kelas->id)
            ->with(['presensis' => function($query) use ($tanggalMulai, $tanggalAkhir, $sesiFilter, $tahunAjaranId) {
                $query->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                if ($sesiFilter) {
                    $query->where('sesi', $sesiFilter);
                }
                // ✅ Filter tahun ajaran
                if ($tahunAjaranId) {
                    $query->where('tahun_ajaran_id', $tahunAjaranId);
                }
            }])
            ->get()
            ->map(function($santri) {
                $presensis = $santri->presensis;
                
                $santri->total_hadir    = $presensis->where('status', 'Hadir')->count();
                $santri->total_sakit    = $presensis->where('status', 'Sakit')->count();
                $santri->total_izin     = $presensis->where('status', 'Izin')->count();
                $santri->total_alpha    = $presensis->where('status', 'Alpha')->count();
                $santri->total_presensi = $presensis->count();
                
                return $santri;
            })
            ->sortBy('name');

        // Statistik untuk cards
        $totalSantri = $santriList->count();
        $totalIkhwan = $santriList->where('gender', 'Laki-laki')->count();
        $totalAkhwat = $santriList->where('gender', 'Perempuan')->count();

        // Pagination
        $perPage           = 15;
        $currentPage       = request()->get('page', 1);
        $santrisCollection = collect($santriList);
        $santrisPaginated  = new \Illuminate\Pagination\LengthAwarePaginator(
            $santrisCollection->forPage($currentPage, $perPage),
            $santrisCollection->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Data untuk calendar (6 bulan terakhir)
        $presensiData      = [];
        $startDateCalendar = now()->subMonths(6)->startOfMonth();
        $endDateCalendar   = now()->endOfMonth();
        
        foreach ($santriList as $santri) {
            $calendarQuery = Presensi::where('santri_id', $santri->id)
                ->whereBetween('tanggal', [$startDateCalendar, $endDateCalendar]);

            // ✅ Filter tahun ajaran pada calendar juga
            if ($tahunAjaranId) {
                $calendarQuery->where('tahun_ajaran_id', $tahunAjaranId);
            }

            $presensis = $calendarQuery->get();
            
            $presensiData[$santri->id] = [];
            
            foreach ($presensis as $presensi) {
                $tanggalKey = $presensi->tanggal->format('Y-m-d');
                
                if (!isset($presensiData[$santri->id][$tanggalKey])) {
                    $presensiData[$santri->id][$tanggalKey] = [];
                }
                
                $presensiData[$santri->id][$tanggalKey][$presensi->sesi] = $presensi->status;
            }
        }

        $sesiList = PresensiSesiConfig::active()->ordered()->get();

        return view('guru.presensis.index', [
            'santris'          => $santrisPaginated,
            'santriList'       => $santriList,
            'kelas'            => $kelas,
            'guru'             => $guru,
            'totalSantri'      => $totalSantri,
            'totalIkhwan'      => $totalIkhwan,
            'totalAkhwat'      => $totalAkhwat,
            'presensiData'     => $presensiData,
            'sesiList'         => $sesiList,
            'tanggalMulai'     => $tanggalMulai,
            'tanggalAkhir'     => $tanggalAkhir,
            'sesiFilter'       => $sesiFilter,
            'tahunAjaranAktif' => $tahunAjaranAktif, // ✅ BARU
        ]);
    }

    /**
     * Tampilkan detail presensi per santri
     */
    public function detail(Request $request, $santriId)
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

        $santri = Santri::where('id', $santriId)
            ->where('kelas_id', $kelas->id)
            ->with('kelas')
            ->firstOrFail();

        // ✅ Ambil tahun ajaran aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $tahunAjaranId    = $tahunAjaranAktif?->id;

        // Filter
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->subDays(30)->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->format('Y-m-d'));
        $sesiFilter   = $request->input('sesi');

        // ✅ Presensi dengan relasi setorans + filter tahun ajaran
        $presensiQuery = Presensi::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->when($sesiFilter, function($query) use ($sesiFilter) {
                return $query->where('sesi', $sesiFilter);
            })
            ->with('setorans')
            ->orderBy('tanggal', 'desc')
            ->orderBy('sesi');

        if ($tahunAjaranId) {
            $presensiQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $presensis = $presensiQuery->paginate(20);

        // ✅ Statistik dengan filter tahun ajaran
        $statistikBase = Presensi::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);

        if ($tahunAjaranId) {
            $statistikBase->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $statistik = [
            'total_hadir' => (clone $statistikBase)->where('status', 'Hadir')->count(),
            'total_sakit' => (clone $statistikBase)->where('status', 'Sakit')->count(),
            'total_izin'  => (clone $statistikBase)->where('status', 'Izin')->count(),
            'total_alpha' => (clone $statistikBase)->where('status', 'Alpha')->count(),
        ];

        $sesiList = PresensiSesiConfig::active()->ordered()->get();

        return view('guru.presensis.detail', compact(
            'santri',
            'guru',
            'kelas',
            'presensis',
            'statistik',
            'sesiList',
            'tanggalMulai',
            'tanggalAkhir',
            'sesiFilter',
            'tahunAjaranAktif' // ✅ BARU
        ));
    }

    /**
     * Tampilkan form edit presensi
     */
    public function edit($id)
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

        $presensi = Presensi::with('santri')->findOrFail($id);

        // Pastikan presensi milik santri di kelas guru
        if ($presensi->santri->kelas_id !== $kelas->id) {
            return redirect()->route('guru.presensis.index')
                ->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        $sesiList = PresensiSesiConfig::active()->ordered()->get();

        return view('guru.presensis.edit', compact('presensi', 'guru', 'kelas', 'sesiList'));
    }

    /**
     * Update presensi
     */
    public function update(Request $request, $id)
    {
        $activeSesiNames = PresensiSesiConfig::getActiveSesiNames();
        
        if (empty($activeSesiNames)) {
            return redirect()->back()
                ->with('error', 'Tidak ada sesi presensi yang aktif.')
                ->withInput();
        }

        $request->validate([
            'tanggal'    => 'required|date',
            'sesi'       => 'required|in:' . implode(',', $activeSesiNames),
            'status'     => 'required|in:Hadir,Sakit,Izin,Alpha',
            'keterangan' => 'nullable|string|max:500',
        ]);

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

        $presensi = Presensi::with('santri')->findOrFail($id);

        // Pastikan presensi milik santri di kelas guru
        if ($presensi->santri->kelas_id !== $kelas->id) {
            return redirect()->route('guru.presensis.index')
                ->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        // Cek duplikasi jika tanggal/sesi berubah
        if ($request->tanggal != $presensi->tanggal || $request->sesi != $presensi->sesi) {
            $exists = Presensi::where('santri_id', $presensi->santri_id)
                ->where('tanggal', $request->tanggal)
                ->where('sesi', $request->sesi)
                ->where('id', '!=', $id)
                ->exists();
            
            if ($exists) {
                return redirect()->back()
                    ->with('error', 'Presensi untuk tanggal dan sesi ini sudah ada.')
                    ->withInput();
            }
        }

        // ✅ PENTING: Jika status berubah dari Hadir ke yang lain, cek apakah ada setoran
        if ($presensi->status === 'Hadir' && $request->status !== 'Hadir') {
            if ($presensi->setorans()->exists()) {
                return redirect()->back()
                    ->with('error', 'Tidak dapat mengubah status karena sudah ada setoran terkait. Hapus setoran terlebih dahulu.')
                    ->withInput();
            }
        }

        DB::beginTransaction();
        
        try {
            $presensi->update([
                'tanggal'    => $request->tanggal,
                'sesi'       => $request->sesi,
                'status'     => $request->status,
                'keterangan' => $request->keterangan,
            ]);

            DB::commit();

            return redirect()->route('guru.presensis.detail', $presensi->santri_id)
                ->with('success', 'Presensi berhasil diperbarui.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update presensi: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus presensi
     */
    public function destroy($id)
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

        $presensi = Presensi::with('santri', 'setorans')->findOrFail($id);

        // Pastikan presensi milik santri di kelas guru
        if ($presensi->santri->kelas_id !== $kelas->id) {
            return redirect()->route('guru.presensis.index')
                ->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        // ✅ PENTING: Cek apakah ada setoran terkait
        if ($presensi->setorans()->exists()) {
            return redirect()->back()
                ->with('error', 'Tidak dapat menghapus presensi karena masih ada setoran terkait. Hapus setoran terlebih dahulu.');
        }

        $santriId = $presensi->santri_id;

        DB::beginTransaction();
        
        try {
            $presensi->delete();
            
            DB::commit();
            
            return redirect()->route('guru.presensis.detail', $santriId)
                ->with('success', 'Presensi berhasil dihapus.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delete presensi: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }
}
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
use Inertia\Inertia;

class PresensiController extends Controller
{
    /**
     * Tampilkan daftar presensi
     */
    public function index(Request $request)
{
    $userId = Auth::id();
    $guru   = Guru::where('user_id', $userId)->first();
    if (!$guru)  return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan.');

    $kelas = Kelas::where('guru_id', $guru->id)->first();
    if (!$kelas) return redirect()->route('guru.dashboard')->with('error', 'Anda belum ditugaskan sebagai wali kelas.');

    $tahunAjaranAktif = TahunAjaran::getAktif();
    $tahunAjaranId    = $tahunAjaranAktif?->id;

    $sesiList = PresensiSesiConfig::active()->ordered()->get()->pluck('sesi_name')->values();

    $santriModels = Santri::where('kelas_id', $kelas->id)->with('user')->orderBy('name')->get();

    // Kalender 6 bulan terakhir per santri
    $startCal = now()->subMonths(6)->startOfMonth();
    $endCal   = now()->endOfMonth();

    $santris = $santriModels->map(function ($santri) use ($startCal, $endCal, $tahunAjaranId) {
        $calQuery = Presensi::where('santri_id', $santri->id)->whereBetween('tanggal', [$startCal, $endCal]);
        if ($tahunAjaranId) $calQuery->where('tahun_ajaran_id', $tahunAjaranId);

        $calendar = [];
        foreach ($calQuery->get() as $p) {
            $calendar[$p->tanggal->format('Y-m-d')][$p->sesi] = $p->status;
        }

        return [
            'id'        => $santri->id,
            'name'      => $santri->name,
            'nis'       => $santri->nis,
            'gender'    => $santri->gender,
            'photo_url' => $santri->user?->profile_photo ? asset('storage/' . $santri->user->profile_photo) : null,
            'calendar'  => $calendar ?: (object) [],   // pastikan {} bukan [] di JSON
        ];
    })->values();

    return Inertia::render('Guru/Presensi/Index', [
        'kelas'            => ['id' => $kelas->id, 'name' => $kelas->name ?? $kelas->nama],
        'guruName'         => $guru->name,
        'genderSummary'    => [
            'total'  => $santris->count(),
            'ikhwan' => $santriModels->where('gender', 'Laki-laki')->count(),
            'akhwat' => $santriModels->where('gender', 'Perempuan')->count(),
        ],
        'tahunAjaranAktif' => $tahunAjaranAktif ? ['nama' => $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name] : null,
        'sesiList'         => $sesiList,
        'santris'          => $santris,
    ]);
}

    /**
     * Tampilkan detail presensi per santri
     */
    public function detail(Request $request, $santriId)
{
    $userId = Auth::id();
    $guru   = Guru::where('user_id', $userId)->first();
    if (!$guru)  return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan.');

    $kelas = Kelas::where('guru_id', $guru->id)->first();
    if (!$kelas) return redirect()->route('guru.dashboard')->with('error', 'Anda belum ditugaskan sebagai wali kelas.');

    $santri = Santri::where('id', $santriId)->where('kelas_id', $kelas->id)->with(['kelas', 'user'])->firstOrFail();

    $tahunAjaranAktif = TahunAjaran::getAktif();
    $tahunAjaranId    = $tahunAjaranAktif?->id;

    $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->subDays(30)->format('Y-m-d'));
    $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->format('Y-m-d'));
    $sesiFilter   = $request->input('sesi');

    $q = Presensi::where('santri_id', $santriId)
        ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
        ->when($sesiFilter, fn ($qq) => $qq->where('sesi', $sesiFilter))
        ->withCount('setorans')
        ->orderBy('tanggal', 'desc')->orderBy('sesi');
    if ($tahunAjaranId) $q->where('tahun_ajaran_id', $tahunAjaranId);
    $presensis = $q->get();

    $statBase = Presensi::where('santri_id', $santriId)->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
    if ($tahunAjaranId) $statBase->where('tahun_ajaran_id', $tahunAjaranId);

    $sesiList = PresensiSesiConfig::active()->ordered()->get()->pluck('sesi_name')->values();

    return Inertia::render('Guru/Presensi/Detail', [
        'santri' => [
            'id'         => $santri->id,
            'name'       => $santri->name,
            'nis'        => $santri->nis,
            'gender'     => $santri->gender,
            'photo_url'  => $santri->user?->profile_photo ? asset('storage/' . $santri->user->profile_photo) : null,
            'kelas_name' => $santri->kelas->name ?? ($santri->kelas->nama ?? '-'),
        ],
        'tahunAjaranAktif' => $tahunAjaranAktif ? ['nama' => $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name] : null,
        'statistik' => [
            'hadir' => (clone $statBase)->where('status', 'Hadir')->count(),
            'sakit' => (clone $statBase)->where('status', 'Sakit')->count(),
            'izin'  => (clone $statBase)->where('status', 'Izin')->count(),
            'alpha' => (clone $statBase)->where('status', 'Alpha')->count(),
        ],
        'sesiList' => $sesiList,
        'filter'   => ['tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalAkhir, 'sesi' => $sesiFilter],
        'presensi' => $presensis->map(fn ($p) => [
            'id'              => $p->id,
            'tanggal'         => $p->tanggal->format('Y-m-d'),
            'tanggal_display' => $p->tanggal->format('d M Y'),
            'sesi'            => $p->sesi,
            'status'          => $p->status,
            'keterangan'      => $p->keterangan,
            'has_setoran'     => $p->setorans_count > 0,
        ])->values(),
    ]);
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
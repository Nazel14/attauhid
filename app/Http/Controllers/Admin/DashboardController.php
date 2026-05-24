<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\TahunAjaran; // ← TAMBAHAN BARU
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ✅ PERMISSION-BASED DETECTION (BUKAN ROLE-BASED)
        
        // Total Admin (semua user dengan role admin)
        $totalAdmins = User::where('role', 'admin')->count();
        
        // Total Super Admin (yang punya permission 'kelola-admin')
        $totalSuperAdmins = User::where('role', 'admin')
            ->permission('kelola-admin')
            ->count();
        
        // Total Regular Admin (yang tidak punya permission 'kelola-admin')
        $totalRegularAdmins = User::where('role', 'admin')
            ->whereDoesntHave('permissions', function($query) {
                $query->where('name', 'kelola-admin');
            })
            ->count();

        // Total Guru
        $totalGurus = Guru::count();

        // Total Kelas
        $totalKelas = Kelas::count();

        // Total Santri
        $totalSantris = Santri::count();

        // ✅ Cek apakah current user adalah Super Admin
        $isSuperAdmin = auth()->user()->hasPermissionTo('kelola-admin');

        // =========================================
        // TAMBAHAN BARU: Info Tahun Ajaran Aktif
        // =========================================

        // Ambil tahun ajaran yang sedang aktif
        $tahunAjaranAktif = TahunAjaran::getAktif();

        // Semua tahun ajaran untuk dropdown di dashboard (jika perlu)
        $semualTahunAjaran = TahunAjaran::terbaru()->get();

        return view('admin.dashboard', compact(
            'totalAdmins',
            'totalSuperAdmins',
            'totalRegularAdmins',
            'totalGurus',
            'totalKelas',
            'totalSantris',
            'isSuperAdmin',
            'tahunAjaranAktif',   // ← TAMBAHAN BARU
            'semualTahunAjaran'   // ← TAMBAHAN BARU
        ));
    }

    /**
     * Get statistics for dashboard charts (optional).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        // Statistik per gender untuk santri
        $santriByGender = Santri::selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->get();

        // Statistik per kelas
        $santriPerKelas = Kelas::withCount('santris')
            ->orderBy('santris_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'santriByGender' => $santriByGender,
            'santriPerKelas' => $santriPerKelas
        ]);
    }
}
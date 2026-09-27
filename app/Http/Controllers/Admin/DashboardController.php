<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\TahunAjaran;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard (Inertia + React).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Statistik master data + tren login. Keduanya berubah pelan
        // (tambah guru, tambah santri) dan hanya dibaca, tidak ditindaklanjuti —
        // jadi aman disimpan 10 menit.
        //
        // Tren login paling mahal di sini: 7 query terpisah ke log_activities,
        // tabel yang terus bertambah seumur hidup sistem. Justru itu yang
        // paling terasa nanti.
        $ringkasan = Cache::remember(
            'admin.dashboard.ringkasan',
            now()->addMinutes(10),
            function () {
                // ✅ PERMISSION-BASED DETECTION (sama seperti versi lama)
                $totalAdmins = User::where('role', 'admin')->count();

                $totalSuperAdmins = User::where('role', 'admin')
                    ->permission('kelola-admin')
                    ->count();

                $totalRegularAdmins = User::where('role', 'admin')
                    ->whereDoesntHave('permissions', function ($query) {
                        $query->where('name', 'kelola-admin');
                    })
                    ->count();

                return [
                    'stats' => [
                        'totalAdmins'        => $totalAdmins,
                        'totalSuperAdmins'   => $totalSuperAdmins,
                        'totalRegularAdmins' => $totalRegularAdmins,
                        'totalGurus'         => Guru::count(),
                        'totalKelas'         => Kelas::count(),
                        'totalSantris'       => Santri::count(),
                    ],
                    // Untuk tiap hari (6 hari lalu s.d. hari ini) hitung jumlah aktivitas login.
                    'loginTrend' => collect(range(6, 0))->map(function ($daysAgo) {
                        $date = Carbon::today()->subDays($daysAgo);

                        return [
                            'date'  => $date->toDateString(),
                            'count' => LogActivity::where('activity_type', 'login')
                                ->whereDate('created_at', $date)
                                ->count(),
                        ];
                    })->values(),
                ];
            }
        );

        $stats      = $ringkasan['stats'];
        $loginTrend = $ringkasan['loginTrend'];

        // Tahun ajaran aktif — di luar cache, karena pergantian semester
        // harus langsung terlihat begitu super-admin menekan "Set Aktif".
        $tahunAjaranAktif = TahunAjaran::getAktif();

        // =========================================
        // KOMPONEN PENDUKUNG: Aktivitas terkini
        // =========================================
        $recentActivities = LogActivity::latest()
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'type'        => $log->activity_type,
                    'description' => $log->description,
                    'role'        => $log->role,
                    'created_at'  => optional($log->created_at)->toIso8601String(),
                ];
            })
            ->values();

        // Permission untuk menampilkan menu di React (dievaluasi di server)
        $gateKeys = [
            'kelola-admin',
            'kelola-guru',
            'kelola-kelas',
            'kelola-santri',
            'kelola-presensi-sesi',
            'lihat-monitoring',
            'lihat-log-activity',
        ];
        $permissions = collect($gateKeys)
            ->filter(fn ($p) => $user->can($p))
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'tahunAjaranAktif' => $tahunAjaranAktif ? [
                'label_lengkap' => $tahunAjaranAktif->label_lengkap,
                'label_pendek'  => $tahunAjaranAktif->label_pendek,
            ] : null,
            'loginTrend'       => $loginTrend,
            'recentActivities' => $recentActivities,
            'permissions'      => $permissions,
        ]);
    }

    /**
     * Get statistics for dashboard charts (optional, tetap dipertahankan).
     */
    public function getStats()
    {
        $santriByGender = Santri::selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->get();

        $santriPerKelas = Kelas::withCount('santris')
            ->orderBy('santris_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'santriByGender' => $santriByGender,
            'santriPerKelas' => $santriPerKelas,
        ]);
    }
}
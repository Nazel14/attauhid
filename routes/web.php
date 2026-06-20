<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// --- Imports Controller Root ---
use App\Http\Controllers\SantriController;

// --- Imports Controller Admin ---
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GuruController as AdminGuruController;
use App\Http\Controllers\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Admin\SantriController as AdminSantriController;
use App\Http\Controllers\Admin\PresensiSesiController as AdminPresensiSesiController;
use App\Http\Controllers\Admin\MonitoringController as AdminMonitoringController;
use App\Http\Controllers\Admin\LogActivityController as AdminLogActivityController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\TahunAjaranController;

// --- Imports Controller Guru ---
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\UnifiedInputController;
use App\Http\Controllers\Guru\PresensiController;
use App\Http\Controllers\Guru\SetoranController;
use App\Http\Controllers\Guru\SantriController as GuruSantriController;
use App\Http\Controllers\Guru\RapotController as GuruRapotController;

/* --------------------------------------
 * 1. Rute Default & Auth (Login/Logout)
 * -------------------------------------- */
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = Auth::user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    return match ($user->role) {
        'admin'       => redirect()->route('admin.dashboard'),
        'guru'        => redirect()->route('guru.dashboard'),
        'santri'      => redirect()->route('santri.dashboard'),
        default       => Auth::logout() ?? redirect()->route('login')->with('error', 'Role tidak valid'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});

require __DIR__.'/auth.php';

/* --------------------------------------
 * 2. Rute ADMIN & SUPER ADMIN
 * -------------------------------------- */
Route::middleware(['auth', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('permission:lihat-dashboard-admin')
        ->name('dashboard');
    
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats'])
        ->middleware('permission:lihat-dashboard-admin')
        ->name('dashboard.stats');

    Route::middleware('permission:kelola-admin')->group(function() {
        Route::resource('admins', AdminController::class)->except(['show']);

        // Tahun Ajaran (hanya Super Admin)
        Route::prefix('tahun-ajaran')->name('tahun-ajaran.')->group(function () {
            Route::get('/',                              [TahunAjaranController::class, 'index'])        ->name('index');
            Route::post('/',                             [TahunAjaranController::class, 'store'])        ->name('store');
            Route::put('/{tahunAjaran}',                 [TahunAjaranController::class, 'update'])       ->name('update');
            Route::delete('/{tahunAjaran}',              [TahunAjaranController::class, 'destroy'])      ->name('destroy');
            Route::patch('/{tahunAjaran}/set-aktif',     [TahunAjaranController::class, 'setAktif'])     ->name('set-aktif');
            Route::post('/{tahunAjaran}/tutup-semester', [TahunAjaranController::class, 'tutupSemester'])->name('tutup-semester');
        });
    });

    Route::middleware('permission:kelola-guru')->group(function() {
        Route::controller(AdminGuruController::class)->prefix('gurus')->name('gurus.')->group(function() {
            Route::get('/template', 'downloadTemplate')->name('template');
            Route::post('/import', 'import')->name('import');
            Route::get('/export-edit', 'exportForEdit')->name('export-edit');
            Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
            Route::delete('/bulk-destroy', 'bulkDestroy')->name('bulk-destroy');
        });
        Route::resource('gurus', AdminGuruController::class);
    });

    Route::middleware('permission:kelola-kelas')->group(function() {
        Route::controller(AdminKelasController::class)->prefix('kelas')->name('kelas.')->group(function() {
            Route::get('/download-template', 'downloadTemplate')->name('download-template');
            Route::post('/import', 'import')->name('import');
            Route::get('/export-for-edit', 'exportForEdit')->name('export-for-edit');
            Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
            Route::delete('/bulk-destroy', 'bulkDestroy')->name('bulk-destroy');
        });
        Route::resource('kelas', AdminKelasController::class);
    });

    Route::middleware('permission:kelola-santri')->group(function() {
        Route::controller(AdminSantriController::class)->prefix('santris')->name('santris.')->group(function() {
            Route::get('/', 'index')->name('index');
            Route::get('/download-template', 'downloadTemplate')->name('download-template');
            Route::post('/import', 'import')->name('import');
            Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
            Route::delete('/bulk-destroy', 'bulkDestroy')->name('bulk-destroy');
            Route::get('/kelas/{kelasId}', 'detail')->name('detail');
            Route::get('/export-for-edit/{kelasId}', 'exportForEdit')->name('export-for-edit');
            Route::post('/', 'store')->name('store');
            Route::put('/{santri}', 'update')->name('update');
            Route::delete('/{santri}', 'destroy')->name('destroy');
        });
    });

    Route::middleware('permission:kelola-presensi-sesi')->group(function() {
        Route::controller(AdminPresensiSesiController::class)->prefix('presensi-sesis')->name('presensi-sesis.')->group(function() {
            Route::patch('/{presensiSesi}/toggle', 'toggleActive')->name('toggle');
        });
        Route::resource('presensi-sesis', AdminPresensiSesiController::class);
    });

    Route::middleware('permission:lihat-monitoring')->group(function() {
        Route::controller(AdminMonitoringController::class)->prefix('monitoring')->name('monitoring.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/kelas/{id}', 'kelasSantri')->name('kelas');
            Route::get('/santri/{id}', 'detailSantri')->name('santri');
            Route::get('/setoran/{id}/audio', 'audio')->name('audio');
            Route::get('/setoran/{id}/pdf', 'pdf')->name('pdf');
        });
    });

    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [AdminLogActivityController::class, 'index'])
            ->middleware('permission:lihat-log-activity')
            ->name('index');
        
        Route::get('/{log}', [AdminLogActivityController::class, 'show'])
            ->middleware('permission:lihat-log-activity')
            ->name('show');
        
        Route::delete('/{log}', [AdminLogActivityController::class, 'destroy'])
            ->middleware('permission:hapus-log-activity')
            ->name('destroy');
        
        Route::post('/cleanup', [AdminLogActivityController::class, 'cleanup'])
            ->middleware('permission:cleanup-log-activity')
            ->name('cleanup');
    });
});

/* --------------------------------------
 * 3. Rute GURU
 * -------------------------------------- */
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    // Naik Jilid Iqro (konfirmasi guru)
    Route::post('/naik-jilid', [GuruDashboardController::class, 'naikkanJilid'])->name('naik-jilid');
    
    // Manajemen Santri
    Route::controller(GuruSantriController::class)->prefix('santris')->name('santris.')->group(function () {
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::post('/import', 'import')->name('import');
        Route::get('/export-for-edit', 'exportForEdit')->name('export-for-edit');
        Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
        Route::delete('/bulk-destroy', 'bulkDestroy')->name('bulk-destroy');
    });
    Route::resource('santris', GuruSantriController::class)->only(['index', 'store', 'update', 'destroy']);

    // Hafalan Indicator
    Route::get('/hafalan-santri', function(\Illuminate\Http\Request $request) {
        $santriId = $request->query('santri_id');
        if (!$santriId) {
            return response()->json([]);
        }

        $setorans = \App\Models\Setoran::where('santri_id', $santriId)
            ->where('kategori_setoran', 'Hafalan')
            ->whereNotNull('surat_start')
            ->whereNotNull('ayat_start')
            ->whereNotNull('surat_end')
            ->whereNotNull('ayat_end')
            ->get(['surat_start', 'ayat_start', 'surat_end', 'ayat_end']);

        $totalAyatMap = [
            "1. Al-Fatihah"=>7,"2. Al-Baqarah"=>286,"3. Ali 'Imran"=>200,"4. An-Nisa'"=>176,
            "5. Al-Ma'idah"=>120,"6. Al-An'am"=>165,"7. Al-A'raf"=>206,"8. Al-Anfal"=>75,
            "9. At-Taubah"=>129,"10. Yunus"=>109,"11. Hud"=>123,"12. Yusuf"=>111,
            "13. Ar-Ra'd"=>43,"14. Ibrahim"=>52,"15. Al-Hijr"=>99,"16. An-Nahl"=>128,
            "17. Al-Isra'"=>111,"18. Al-Kahf"=>110,"19. Maryam"=>98,"20. Taha"=>135,
            "21. Al-Anbiya'"=>112,"22. Al-Hajj"=>78,"23. Al-Mu'minun"=>118,"24. An-Nur"=>64,
            "25. Al-Furqan"=>77,"26. Asy-Syu'ara'"=>227,"27. An-Naml"=>93,"28. Al-Qasas"=>88,
            "29. Al-'Ankabut"=>69,"30. Ar-Rum"=>60,"31. Luqman"=>34,"32. As-Sajdah"=>30,
            "33. Al-Ahzab"=>73,"34. Saba'"=>54,"35. Fatir"=>45,"36. Yasin"=>83,
            "37. As-Saffat"=>182,"38. Sad"=>88,"39. Az-Zumar"=>75,"40. Ghafir"=>85,
            "41. Fussilat"=>54,"42. Asy-Syura"=>53,"43. Az-Zukhruf"=>89,"44. Ad-Dukhan"=>59,
            "45. Al-Jasiyah"=>37,"46. Al-Ahqaf"=>35,"47. Muhammad"=>38,"48. Al-Fath"=>29,
            "49. Al-Hujurat"=>18,"50. Qaf"=>45,"51. Az-Zariyat"=>60,"52. At-Tur"=>49,
            "53. An-Najm"=>62,"54. Al-Qamar"=>55,"55. Ar-Rahman"=>78,"56. Al-Waqi'ah"=>96,
            "57. Al-Hadid"=>29,"58. Al-Mujadilah"=>22,"59. Al-Hasyr"=>24,"60. Al-Mumtahanah"=>13,
            "61. As-Saff"=>14,"62. Al-Jumu'ah"=>11,"63. Al-Munafiqun"=>11,"64. At-Taghabun"=>18,
            "65. At-Talaq"=>12,"66. At-Tahrim"=>12,"67. Al-Mulk"=>30,"68. Al-Qalam"=>52,
            "69. Al-Haqqah"=>52,"70. Al-Ma'arij"=>44,"71. Nuh"=>28,"72. Al-Jinn"=>28,
            "73. Al-Muzzammil"=>20,"74. Al-Muddassir"=>56,"75. Al-Qiyamah"=>40,"76. Al-Insan"=>31,
            "77. Al-Mursalat"=>50,"78. An-Naba'"=>40,"79. An-Nazi'at"=>46,"80. 'Abasa"=>42,
            "81. At-Takwir"=>29,"82. Al-Infitar"=>19,"83. Al-Mutaffifin"=>36,"84. Al-Insyiqaq"=>25,
            "85. Al-Buruj"=>22,"86. At-Tariq"=>17,"87. Al-A'la"=>19,"88. Al-Ghasyiyah"=>26,
            "89. Al-Fajr"=>30,"90. Al-Balad"=>20,"91. Asy-Syams"=>15,"92. Al-Lail"=>21,
            "93. Ad-Duha"=>11,"94. Asy-Syarh"=>8,"95. At-Tin"=>8,"96. Al-'Alaq"=>19,
            "97. Al-Qadr"=>5,"98. Al-Bayyinah"=>8,"99. Az-Zalzalah"=>8,"100. Al-'Adiyat"=>11,
            "101. Al-Qari'ah"=>11,"102. At-Takasur"=>8,"103. Al-'Asr"=>3,"104. Al-Humazah"=>9,
            "105. Al-Fil"=>5,"106. Quraisy"=>4,"107. Al-Ma'un"=>7,"108. Al-Kausar"=>3,
            "109. Al-Kafirun"=>6,"110. An-Nasr"=>3,"111. Al-Masad"=>5,"112. Al-Ikhlas"=>4,
            "113. Al-Falaq"=>5,"114. An-Nas"=>6,
        ];

        $allSuratNames = array_keys($totalAyatMap);
        $result = [];

        foreach ($setorans as $setoran) {
            $suratStart = $setoran->surat_start;
            $ayatStart  = (int) $setoran->ayat_start;
            $suratEnd   = $setoran->surat_end;
            $ayatEnd    = (int) $setoran->ayat_end;

            if ($suratStart === $suratEnd) {
                if (!isset($result[$suratStart])) $result[$suratStart] = [];
                for ($a = $ayatStart; $a <= $ayatEnd; $a++) $result[$suratStart][] = $a;
            } else {
                $startNo = (int) explode('.', $suratStart)[0];
                $endNo   = (int) explode('.', $suratEnd)[0];
                foreach ($allSuratNames as $suratName) {
                    $no = (int) explode('.', $suratName)[0];
                    if ($no < $startNo || $no > $endNo) continue;
                    if (!isset($result[$suratName])) $result[$suratName] = [];
                    $totalAyat = $totalAyatMap[$suratName] ?? 0;
                    if ($no === $startNo) {
                        $dari = $ayatStart; $sampai = $totalAyat;
                    } elseif ($no === $endNo) {
                        $dari = 1; $sampai = $ayatEnd;
                    } else {
                        $dari = 1; $sampai = $totalAyat;
                    }
                    for ($a = $dari; $a <= $sampai; $a++) $result[$suratName][] = $a;
                }
            }
        }

        foreach ($result as $surat => &$list) {
            $list = array_values(array_unique($list));
            sort($list);
        }

        return response()->json($result);
    })->name('guru.hafalan-santri');

    // Halaman Iqro yang sudah disetor santri (untuk indikator centang)
    Route::get('/iqro-santri', function (\Illuminate\Http\Request $request) {
        $santriId = $request->query('santri_id');
        if (!$santriId) {
            return response()->json([]);
        }

        // Ambil semua setoran Iqro santri (deteksi via label "Iqro Jilid X")
        $rows = \App\Models\Setoran::where('santri_id', $santriId)
            ->where(function ($q) {
                $q->where('surat_start', 'like', 'Iqro Jilid %')
                  ->orWhere('surat_end', 'like', 'Iqro Jilid %');
            })
            ->get(['surat_start', 'surat_end', 'ayat_start', 'ayat_end']);

        // Hasil: { "1": [1,2,3,...], "2": [...] }  (jilid => daftar halaman)
        $result = [];
        foreach ($rows as $r) {
            $label = $r->surat_start ?: $r->surat_end;
            if (!preg_match('/Iqro Jilid (\d+)/', (string) $label, $m)) {
                continue;
            }
            $jilid = (string) ((int) $m[1]);

            $start = (int) $r->ayat_start;
            $end   = (int) ($r->ayat_end ?: $r->ayat_start);
            if ($start <= 0) continue;
            if ($end < $start) { [$start, $end] = [$end, $start]; }

            for ($p = $start; $p <= $end; $p++) {
                $result[$jilid][] = $p;
            }
        }

        foreach ($result as $jilid => &$list) {
            $list = array_values(array_unique($list));
            sort($list);
        }

        return response()->json($result);
    })->name('guru.iqro-santri');
    
    // Unified Input
    Route::controller(UnifiedInputController::class)->prefix('input')->name('input.')->group(function () {
        Route::get('/', 'create')->name('create');
        Route::post('/store-presensi', 'storePresensi')->name('store-presensi');
        Route::post('/store-setoran', 'storeSetoran')->name('store-setoran');
        Route::post('/store-combined', 'storeCombined')->name('store-combined');
    });
    
    // Presensi
    Route::controller(PresensiController::class)->prefix('presensis')->name('presensis.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/santri/{santri}', 'detail')->name('detail');
        Route::get('/{presensi}/edit', 'edit')->name('edit');
        Route::put('/{presensi}', 'update')->name('update');
        Route::delete('/{presensi}', 'destroy')->name('destroy');
    });
    
    // Setoran
    Route::controller(SetoranController::class)->prefix('setorans')->name('setorans.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/santri/{santri}', 'detail')->name('detail');
        Route::get('/{setoran}/edit', 'edit')->name('edit');
        Route::put('/{setoran}', 'update')->name('update');
        Route::delete('/{setoran}', 'destroy')->name('destroy');
        Route::get('/{setoran}/audio', 'audio')->name('audio');
        Route::get('/{setoran}/pdf', 'downloadPdf')->name('pdf');
    });

    // Rapot
    Route::controller(GuruRapotController::class)->prefix('rapot')->name('rapot.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{santri}', 'show')->name('show');
        Route::get('/{santri}/pdf', 'downloadPdf')->name('pdf');
    });
});

/* --------------------------------------
 * 4. Rute SANTRI
 * -------------------------------------- */
Route::middleware(['auth', 'role:santri'])->prefix('santri')->name('santri.')->group(function () {
    Route::get('/dashboard', [SantriController::class, 'dashboard'])->name('dashboard');
    Route::get('/rapot', [SantriController::class, 'rapotShow'])->name('rapot.show');
    Route::get('/rapot/pdf', [SantriController::class, 'downloadRapotPdf'])->name('rapot.pdf');
    Route::get('/setorans', [SantriController::class, 'setorans'])->name('setorans.detail');
    Route::get('/presensis', [SantriController::class, 'presensis'])->name('presensis.detail');
    Route::get('/setorans/{setoran}/audio', [SantriController::class, 'getSetoranAudio'])->name('setorans.audio');
    Route::get('/setorans/{setoran}/pdf', [SantriController::class, 'getSetoranPdf'])->name('setorans.pdf');
});

/* --------------------------------------
 * 5. API Routes (Internal AJAX/Fetch)
 * -------------------------------------- */
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    
    Route::get('/santris/kelas/{kelas}', function($kelasId) {
        $santris = \App\Models\Santri::where('kelas_id', $kelasId)
            ->orderBy('name')
            ->get(['id', 'name', 'nis']);
        return response()->json($santris);
    })->name('santris.by-kelas');
    
    Route::get('/presensi-sesis/active', function() {
        $sesis = \App\Models\PresensiSesiConfig::where('is_active', true)
            ->orderBy('urutan')
            ->get();
        return response()->json($sesis);
    })->name('presensi-sesis.active');
    
    Route::get('/presensi/check-exists', function(Request $request) {
        $exists = \App\Models\Presensi::where('santri_id', $request->santri_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('sesi', $request->sesi)
            ->exists();
        return response()->json(['exists' => $exists]);
    })->name('presensi.check-exists');
    
    Route::get('/presensi/check-hadir', function(Request $request) {
        $presensi = \App\Models\Presensi::where('santri_id', $request->santri_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('sesi', $request->sesi)
            ->where('status', 'Hadir')
            ->first();
        return response()->json([
            'exists'      => $presensi ? true : false,
            'presensi_id' => $presensi ? $presensi->id : null,
        ]);
    })->name('presensi.check-hadir');
    
    Route::get('/presensi/hari-ini', function(Request $request) {
        try {
            $tanggal = $request->input('tanggal', today()->format('Y-m-d'));
            $userId  = Auth::id();
            $guru    = \App\Models\Guru::where('user_id', $userId)->first();
            
            if (!$guru) {
                return response()->json([
                    'error' => 'Guru not found',
                    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0,
                    'santri_list' => [], 'sesi_aktif' => []
                ], 404);
            }
            
            $kelas = \App\Models\Kelas::where('guru_id', $guru->id)->first();
            
            if (!$kelas) {
                return response()->json([
                    'error' => 'Kelas not found',
                    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0,
                    'santri_list' => [], 'sesi_aktif' => []
                ], 404);
            }
            
            $allSantri = \App\Models\Santri::where('kelas_id', $kelas->id)
                ->orderBy('name')
                ->get();
            
            $presensiHariIni = \App\Models\Presensi::whereIn('santri_id', $allSantri->pluck('id'))
                ->whereDate('tanggal', $tanggal)
                ->with('setorans')
                ->get();
            
            $stats = [
                'hadir' => $presensiHariIni->where('status', 'Hadir')->count(),
                'sakit' => $presensiHariIni->where('status', 'Sakit')->count(),
                'izin'  => $presensiHariIni->where('status', 'Izin')->count(),
                'alpha' => $presensiHariIni->where('status', 'Alpha')->count(),
            ];
            
            $sesiAktif = \App\Models\PresensiSesiConfig::where('is_active', 1)
                ->orderBy('urutan')
                ->pluck('sesi_name')
                ->toArray();
            
            $presensiGrouped = $presensiHariIni->groupBy('santri_id');
            
            $santriPresensi = $allSantri->map(function($santri) use ($presensiGrouped, $sesiAktif) {
                $sesiData = [];
                foreach ($sesiAktif as $sesi) {
                    $sesiData[$sesi] = null;
                }
                if (isset($presensiGrouped[$santri->id])) {
                    foreach ($presensiGrouped[$santri->id] as $presensi) {
                        $waktuPresensi = \Carbon\Carbon::parse($presensi->created_at)->format('H:i');
                        $jumlahSetoran = $presensi->setorans()
                            ->whereNotNull('kategori_setoran')
                            ->count();
                        $sesiData[$presensi->sesi] = [
                            'status'         => $presensi->status,
                            'waktu'          => $waktuPresensi,
                            'jumlah_setoran' => $jumlahSetoran,
                        ];
                    }
                }
                return [
                    'id'        => $santri->id,
                    'name'      => $santri->name,
                    'sesi_data' => $sesiData
                ];
            })->values()->toArray();
            
            return response()->json([
                'hadir'       => $stats['hadir'],
                'sakit'       => $stats['sakit'],
                'izin'        => $stats['izin'],
                'alpha'       => $stats['alpha'],
                'santri_list' => $santriPresensi,
                'sesi_aktif'  => $sesiAktif
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getPresensiHariIni: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0,
                'santri_list' => [], 'sesi_aktif' => []
            ], 500);
        }
    })->name('presensi.hari-ini');
});

/* --------------------------------------
 * 6. SERVE STORAGE FILES (Alternative to symlink)
 * Untuk shared hosting Hostinger yang block fungsi symlink()
 * Route ini handle akses ke /storage/* dan baca file dari storage/app/public/
 * -------------------------------------- */
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404, 'File not found: ' . $path);
    }
    
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');
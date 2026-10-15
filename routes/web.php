<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/* --------------------------------------
 * 1. Rute Default & Auth (Login/Logout)
 * -------------------------------------- */
Route::get('/', function () {
    // User yang sudah login langsung ke dashboard.
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    // Landing page hanya tampil di instalasi demo/marketing.
    // Di instalasi milik klien (LANDING_PAGE=false), tamu langsung
    // diarahkan ke login supaya wali murid dan guru tidak disambut
    // halaman penawaran produk.
    if (! config('modules.landing')) {
        return redirect()->route('login');
    }

    return Inertia::render('Landing');
})->name('home');

Route::get('/dashboard', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login');
    }

    // Pengguna eksternal tidak mengenal konsep panel. Dicek paling awal
    // supaya session modul_aktif dari login tidak pernah membelokkan
    // mereka ke panel internal.
    if ($user->isEksternal()) {
        return redirect()->route('santri.dashboard');
    }

    $modules = $user->availableModules();

    // Panel yang dipilih di halaman login. Dihormati lebih dulu supaya
    // super-admin yang memilih Keuangan tidak terlempar ke panel admin
    // hanya karena kolom users.role-nya 'admin'.
    $pilihan = session('modul_aktif');

    if ($pilihan === 'keuangan' && in_array('keuangan', $modules, true)) {
        return redirect()->route('keuangan.dashboard');
    }

    // Modul raport: tujuannya ditentukan role raport di kolom users.role.
    // 'super-admin' ikut diarahkan ke panel admin — ia termasuk role raport
    // (lihat UserController::ROLE_RAPORT), hanya dengan hak akses lebih luas.
    if (in_array('raport', $modules, true)) {
        $target = match ($user->role) {
            'admin', 'super-admin' => 'admin.dashboard',
            'guru'                 => 'guru.dashboard',
            default                => null,
        };

        if ($target) {
            return redirect()->route($target);
        }
    }

    // Role keuangan tidak punya padanan di kolom users.role, jadi
    // dialihkan langsung ke panel keuangan.
    if (in_array('keuangan', $modules, true)) {
        return redirect()->route('keuangan.dashboard');
    }

    Auth::logout();

    return redirect()->route('login')->with('error', 'Role tidak valid');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Pindah panel tanpa logout, untuk akun yang punya akses ke keduanya.
    Route::get('/pindah-modul/{modul}', function (string $modul) {
        abort_unless(in_array($modul, ['raport', 'keuangan'], true), 404);

        $user = Auth::user();

        if (!in_array($modul, $user->availableModules(), true)) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak punya akses ke panel tersebut.');
        }

        session(['modul_aktif' => $modul]);

        return redirect()->route('dashboard');
    })->name('pindah-modul');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});

require __DIR__.'/auth.php';

/* --------------------------------------
 * Login demo — hanya terdaftar bila DEMO_MODE menyala.
 * Di instalasi klien, route ini tidak pernah ada.
 * -------------------------------------- */
if (config('modules.demo')) {
    Route::post('/demo/masuk', function (\Illuminate\Http\Request $request) {
        $peran = $request->input('peran');

        $akun = collect(config('modules.demo_akun', []))->firstWhere('peran', $peran);
        abort_unless($akun, 404);

        // Jaring pengaman: apa pun isi konfigurasinya, hanya akun yang
        // namanya berawalan 'demo.' yang boleh dimasuki lewat jalur ini —
        // jadi tombol demo tidak akan pernah bisa membuka akun sungguhan.
        abort_unless(str_starts_with($akun['username'], 'demo.'), 403);

        $user = \App\Models\User::where('name', $akun['username'])->first();

        if (! $user) {
            return back()->with('error', "Akun demo '{$akun['username']}' belum di-seed di server ini.");
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Login demo dicatat seperti login biasa. AuthenticatedSessionController
        // menulisnya manual (bukan lewat event listener), jadi jalur ini harus
        // menulis sendiri — kalau tidak, aktivitas demo hilang dari log.
        try {
            $displayRole = $user->role;

            if ($user->role === 'admin') {
                $displayRole = $user->hasPermissionTo('kelola-admin')
                    ? 'Admin Super'
                    : 'Admin Biasa';
            }

            \App\Models\LogActivity::create([
                'user_id'       => $user->id,
                'role'          => $user->role,
                'activity_type' => 'login',
                'module'        => 'auth',
                'description'   => 'Login demo sebagai ' . $displayRole,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to log demo login activity: ' . $e->getMessage());
        }

        $modules = $user->availableModules();

        // Hormati kelompok tombol yang diklik di halaman login — itu
        // satu-satunya cara pengunjung menyatakan panel mana yang ingin
        // ia lihat. Versi lama selalu memilih raport bila tersedia,
        // sehingga tombol Admin di kelompok Keuangan tetap mendarat di
        // panel tahfizh.
        //
        // Tetap tidak dipercaya mentah-mentah: akun yang tidak punya akses
        // ke modul yang diminta diarahkan ke modul yang memang boleh ia
        // buka, bukan ditolak.
        $diminta = $request->input('modul');
        session(['modul_aktif' => in_array($diminta, $modules, true)
            ? $diminta
            : (in_array('raport', $modules, true) ? 'raport' : 'keuangan')]);

        return redirect()->route('dashboard');
    })->name('demo.masuk');
}

// Ambil CSRF token terbaru — dipakai form native (InputSetoran) sebelum submit
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->middleware('web')
    ->name('csrf.token');

/* --------------------------------------
 * 2. Modul
 * -------------------------------------- */
if (config('modules.raport')) {
    require __DIR__.'/raport.php';
}

if (config('modules.keuangan')) {
    require __DIR__.'/keuangan.php';
}

// Portal eksternal (wali/santri) — di luar gerbang modul, karena
// wali tetap perlu akses meski salah satu modul dimatikan.
require __DIR__.'/portal.php';

/* --------------------------------------
 * 3. SERVE STORAGE FILES (Alternative to symlink)
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
<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

    // Dispatch ke dashboard modul raport.
    // Nanti (step 6) blok ini diganti pemilih modul untuk user multi-modul.
    if (config('modules.raport')) {
        $target = match ($user->role) {
            'admin'  => 'admin.dashboard',
            'guru'   => 'guru.dashboard',
            'santri' => 'santri.dashboard',
            default  => null,
        };

        if ($target) {
            return redirect()->route($target);
        }
    }

    Auth::logout();

    return redirect()->route('login')->with('error', 'Role tidak valid');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});

require __DIR__.'/auth.php';

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

// Diaktifkan pada step 4, setelah modul keuangan di-port.
// if (config('modules.keuangan')) {
//     require __DIR__.'/keuangan.php';
// }

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
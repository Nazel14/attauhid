<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Daftar modul yang aktif, dipakai form Login (default tab & validasi
     * server). Dikirim eksplisit di sini — tidak bergantung pada shared
     * props HandleInertiaRequests — supaya halaman login tetap benar
     * meski shared props belum sempat diperbarui/di-cache.
     */
    private function modulAktif(): array
    {
        return array_keys(array_filter([
            'raport'   => config('modules.raport'),
            'keuangan' => config('modules.keuangan'),
        ]));
    }

    /**
     * Tampilkan halaman login (Inertia + React).
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status'  => session('status'),
            'modules' => [
                'raport'   => config('modules.raport'),
                'keuangan' => config('modules.keuangan'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $modulTersedia = $this->modulAktif();

        // 'modul' dibuat nullable di sini karena wajib-tidaknya baru bisa
        // diputuskan setelah tahu siapa usernya: role eksternal (wali/santri)
        // tidak mengenal panel. Pengecekan wajibnya ada di bawah.
        $request->validate([
            'name'     => ['required', 'string'],
            'password' => ['required', 'string'],
            'modul'    => ['nullable', 'in:' . implode(',', $modulTersedia)],
        ], [
            'modul.in' => 'Panel yang dipilih sedang tidak aktif di sistem ini.',
        ]);

        $name     = $request->input('name');
        $password = $request->input('password');
        $modul    = $request->input('modul');

        $user = User::where('name', $name)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'name'     => 'Username tidak ditemukan.',
                'password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        // Wali/santri: apa pun tab yang tersentuh di form, diabaikan.
        // Mereka punya satu tujuan, yaitu portal.
        if ($user->isEksternal()) {
            $modul = null;
        } elseif (!$modul) {
            throw ValidationException::withMessages([
                'modul' => 'Pilih dulu panel yang ingin dibuka.',
            ]);
        }

        // Diperiksa sebelum Auth::login supaya tidak perlu login lalu logout.
        $tersedia = $user->availableModules();

        if (!$user->isEksternal() && !in_array($modul, $tersedia, true)) {
            $label = $modul === 'keuangan' ? 'Laporan Keuangan' : 'Tahfizh';

            throw ValidationException::withMessages([
                'modul' => empty($tersedia)
                    ? 'Akun ini belum diberi hak akses ke panel mana pun. Hubungi admin.'
                    : "Akun ini tidak punya akses ke panel {$label}.",
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('kelas_aktif_id');

        // Dipakai /dashboard untuk memilih panel. Disimpan di session, bukan
        // langsung redirect, supaya logika tujuan raport (admin/guru/santri)
        // tetap satu tempat.
        // Untuk user eksternal, nilainya 'portal' — bukan nama modul,
        // supaya /dashboard maupun /pindah-modul tidak salah baca.
        $request->session()->put('modul_aktif', $modul ?? 'portal');    

        try {
            $displayRole = $user->role;

            if ($user->role === 'admin') {
                $displayRole = $user->hasPermissionTo('kelola-admin')
                    ? 'Admin Super'
                    : 'Admin Biasa';
            }

            LogActivity::create([
                'user_id'       => $user->id,
                'role'          => $user->role,
                'activity_type' => 'login',
                'module'        => 'auth',
                'description'   => 'Login ke sistem sebagai ' . $displayRole,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log login activity: ' . $e->getMessage());
        }

        // intended() sengaja tidak dipakai: user sudah memilih panel secara
        // eksplisit, jadi pilihannya yang menang atas URL yang tersimpan.
        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        try {
            $user = Auth::user();

            if ($user) {
                $displayRole = $user->role;

                if ($user->role === 'admin') {
                    $displayRole = $user->hasPermissionTo('kelola-admin')
                        ? 'Admin Super'
                        : 'Admin Biasa';
                }

                LogActivity::create([
                    'user_id'       => $user->id,
                    'role'          => $user->role,
                    'activity_type' => 'logout',
                    'module'        => 'auth',
                    'description'   => 'Logout dari sistem (role: ' . $displayRole . ')',
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log logout activity: ' . $e->getMessage());
        }

        Auth::guard('web')->logout();
        $request->session()->forget('modul_aktif');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
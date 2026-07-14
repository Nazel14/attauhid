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
     * Tampilkan halaman login (Inertia + React).
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $name     = $request->input('name');
        $password = $request->input('password');

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

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

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

        return redirect()->intended(route('dashboard', absolute: false));
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
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
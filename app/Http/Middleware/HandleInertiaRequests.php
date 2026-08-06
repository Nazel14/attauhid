<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            // Muat relasi profil lalu tempelkan accessor display_name,
            // supaya UI bisa pakai nama asli (bukan username).
            $user->loadMissing(['guru', 'santri']);
            $user->append('display_name');
            // Relasi tidak perlu ikut dikirim ke React — hemat payload.
            $user->makeHidden(['guru', 'santri']);
        }

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user,
                // Kirim semua permission user ke React (gating menu/tombol)
                'permissions' => $user
                    ? $user->getAllPermissions()->pluck('name')
                    : [],
            ],

            // Modul yang aktif — dipakai React untuk gating menu lintas modul.
            // WAJIB dicek sebelum memanggil route() milik modul lain,
            // karena Ziggy melempar error kalau route-nya tidak terdaftar.
            'modules' => [
                'raport'   => (bool) config('modules.raport'),
                'keuangan' => (bool) config('modules.keuangan'),
            ],

            // Modul yang boleh diakses user ini + panel yang sedang dibuka.
            // Dipakai layout untuk menampilkan tombol pindah panel.
            'modulUser' => [
                'tersedia' => $user ? $user->availableModules() : [],
                'aktif'    => $request->session()->get('modul_aktif'),
            ],

            // Flash message (opsional, berguna untuk notifikasi sukses/gagal)
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            // Badge jumlah pesan laporan belum dibaca (sidebar)
            'unreadLaporan' => fn () => $user
                ? \App\Models\Laporan::totalUnreadFor($user)
                : 0,

            // Konteks kelas aktif untuk guru (switcher di header)
            'kelasAktif' => fn () => ($user && $user->role === 'guru' && session('kelas_aktif_id'))
                ? \App\Models\Kelas::select('id', 'name')->find(session('kelas_aktif_id'))
                : null,

            'kelasDiampu' => fn () => ($user && $user->role === 'guru' && $user->guru)
                ? \App\Models\Kelas::where('guru_id', $user->guru->id)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
        ];
    }
}
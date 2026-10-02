<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const SUPER = 'super-admin';

    /**
     * Role yang menentukan akses modul raport. Kolom users.role hanya bisa
     * menampung satu nilai dan seluruh raport membacanya ($user->role ===
     * 'santri', isGuru(), RoleMiddleware), jadi dari sekian role Spatie yang
     * dipilih, satu harus ditunjuk untuk kolom itu.
     *
     * Urutan array = urutan prioritas.
     */
    private const ROLE_RAPORT = ['santri', 'guru', 'admin', 'super-admin'];

    /**
     * Tentukan isi kolom users.role dari daftar role yang dipilih.
     * Role raport diprioritaskan karena aksesnya paling mudah putus;
     * akses keuangan tetap aman lewat relasi Spatie.
     *
     * @param  array<string>  $roles
     */
    private function roleUtama(array $roles, string $fallback): string
    {
        foreach (self::ROLE_RAPORT as $kandidat) {
            if (in_array($kandidat, $roles, true)) {
                return $kandidat;
            }
        }

        return $roles[0] ?? $fallback;
    }

    /**
     * Panel ini memakai tabel `users` sebagai satu-satunya sumber identitas:
     * guru yang diinput dari panel raport langsung muncul di sini, begitu pula
     * sebaliknya. Tidak ada filter role — semua akun ditampilkan.
     *
     * Catatan penting soal `users.role`:
     * kolom itu dibaca RoleMiddleware dan seluruh role-gating raport
     * ($user->role === 'santri'), sementara relasi Spatie dipakai untuk
     * permission. Keduanya WAJIB sinkron, jadi setiap perubahan role
     * di sini selalu menulis kolom DAN syncRoles() sekaligus.
     */
    public function index(Request $request)
    {
        // guru & santri di-load untuk menandai akun yang punya baris profil,
        // supaya tombol hapusnya bisa dimatikan di UI.
        $query = User::with(['roles:id,name', 'guru:id,user_id,name', 'santri:id,user_id,name']);

        // Tabel users TIDAK punya kolom email — `name` adalah username login.
        $query->when($request->search, function ($q, $search) {
            $q->where('name', 'like', "%{$search}%");
        });

        // Filter berdasarkan kolom role (bukan relasi Spatie), karena kolom
        // itulah yang menentukan akses sebenarnya.
        $query->when($request->roleFilter, function ($q, $roleName) {
            $q->where('role', $roleName);
        });

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = strtolower($request->input('sort_direction', 'desc'));

        if (in_array($sortField, ['name', 'role', 'created_at'], true)) {
            $query->orderBy(
                $sortField,
                in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc'
            );
        }

        $users = $query->paginate(10)->withQueryString();

        // Satu-satunya halaman yang memang perlu membaca password: admin
        // membacakannya ulang ke pengguna yang lupa. Dibuka di sini saja,
        // bukan di $hidden model, supaya halaman lain tidak ikut terbuka.
        $users->through(fn (User $u) => $u->makeVisible('plain_password'));

        $roles = Role::orderBy('name')->get(['id', 'name']);

        // Dipakai frontend untuk memutuskan role mana yang boleh dilepas:
        // role raport menentukan kolom users.role, sisanya bebas.
        $rolesRaport = ['admin', 'guru', 'santri', 'super-admin'];

        return Inertia::render('Sistem/User/Index', [
            'users'       => $users,
            'roles'       => $roles,
            'rolesRaport' => $rolesRaport,
            'filters'     => $request->only(['search', 'roleFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:users,name',
            'password' => 'required|string|min:8',
            // Array: satu akun boleh memegang lebih dari satu peran,
            // mis. guru yang merangkap bendahara.
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'exists:roles,name',
        ], [
            'name.unique'    => 'Username ini sudah dipakai akun lain.',
            'password.min'   => 'Password minimal harus 8 karakter.',
            'roles.required' => 'Minimal satu hak akses wajib dipilih.',
            'roles.min'      => 'Minimal satu hak akses wajib dipilih.',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->role = $this->roleUtama($validated['roles'], $validated['roles'][0]);
        $user->password = Hash::make($validated['password']);

        // Raport menyimpan plain_password terenkripsi agar admin bisa
        // membacakan ulang password ke pengguna.
        $user->plain_password = $validated['password'];

        $user->save();

        // Hook booted() sudah meng-assign role utama saat `created`.
        // syncRoles() di sini yang menetapkan daftar lengkapnya.
        $user->syncRoles($validated['roles']);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        // Cegah user mengubah role-nya sendiri (lockout tidak sengaja).
        $isSelf = Auth::id() === $user->id;

        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:users,name,' . $user->id,
            'password' => 'nullable|string|min:8',
            'roles'    => $isSelf ? 'nullable|array' : 'required|array|min:1',
            'roles.*'  => 'exists:roles,name',
        ], [
            'name.unique'    => 'Username ini sudah dipakai akun lain.',
            'password.min'   => 'Password minimal harus 8 karakter.',
            'roles.required' => 'Minimal satu hak akses wajib dipilih.',
        ]);

        $rolesBaru = $validated['roles'] ?? [];

        // Akun dengan baris profil guru/santri: role raport-nya tidak boleh
        // dilepas dari sini, karena baris di gurus/santris akan jadi yatim
        // dan dashboard raport-nya rusak tanpa pesan error. Role keuangan
        // tetap bebas ditambah — itu justru yang berguna di panel ini.
        $punyaProfil = $user->guru()->exists() || $user->santri()->exists();

        if (! $isSelf && $punyaProfil && ! in_array($user->role, $rolesBaru, true)) {
            return back()->withErrors([
                'roles' => "Akun ini terhubung ke data {$user->role} di modul raport. Role \"{$user->role}\" tidak boleh dilepas dari sini — kelola dari panel admin raport.",
            ]);
        }

        $user->name = $validated['name'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->plain_password = $validated['password'];
        }

        if (! $isSelf && ! empty($rolesBaru)) {
            // Kolom dan relasi Spatie diubah bersamaan — jangan pernah salah satu saja.
            $user->role = $this->roleUtama($rolesBaru, $user->role);
            $user->syncRoles($rolesBaru);
        }

        $user->save();

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return back()->withErrors([
                'delete_error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.',
            ]);
        }

        // Penghapusan akun guru/santri harus lewat panel raport, karena di sana
        // baris profil dan relasinya (kelas, setoran, presensi) ikut ditangani.
        if ($user->guru()->exists() || $user->santri()->exists()) {
            return back()->withErrors([
                'delete_error' => 'Akun ini terhubung ke data guru/santri. Hapus dari panel admin raport agar data terkaitnya ikut dibersihkan.',
            ]);
        }

        if ($user->role === self::SUPER && User::where('role', self::SUPER)->count() <= 1) {
            return back()->withErrors([
                'delete_error' => 'Gagal! Harus ada minimal 1 akun super-admin di dalam sistem.',
            ]);
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
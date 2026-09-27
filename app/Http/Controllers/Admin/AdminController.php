<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\LogActivity;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display a listing of admin users (Inertia + React).
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'admin')->with('roles');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $admins = $query->latest()->paginate(10)->withQueryString()
            ->through(fn ($admin) => [
                'id'                 => $admin->id,
                'name'               => $admin->name,
                'plain_password'     => $admin->plain_password,
                'profile_photo_url'  => $admin->profile_photo ? asset('storage/' . $admin->profile_photo) : null,
                'profile_photo_name' => $admin->profile_photo_name,
                'is_super_admin'     => $admin->hasRole('super-admin'),
                'is_self'            => $admin->id === auth()->id(),
            ]);

        // Hitung apa adanya: semua akun pengelola di tabel users, tanpa
        // memandang isi kolom role. Pemilik sistem menyimpan 'super-admin'
        // di kolom itu, sehingga filter lama membuatnya tidak pernah terhitung.
        $akunAdmin = fn () => User::whereIn('role', ['admin', 'super-admin']);

        $superAdminCount = $akunAdmin()
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->count();

        $regularAdminCount = $akunAdmin()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->count();

        $pemilik = User::where('role', 'super-admin')
            ->orderBy('id')
            ->get()
            ->map(fn (User $u) => [
                'id'                 => $u->id,
                'name'               => $u->name,
                'plain_password'     => null,
                'profile_photo_url'  => $u->profile_photo ? asset('storage/' . $u->profile_photo) : null,
                'profile_photo_name' => $u->profile_photo_name,
                'is_self'            => $u->id === auth()->id(),
            ]);

        return Inertia::render('Admin/Admins/Index', [
            'admins'  => $admins,
            'pemilik' => $pemilik,
            'counts' => [
                'superAdmin'   => $superAdminCount,
                'regularAdmin' => $regularAdminCount,
            ],
            'filters' => [
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created admin in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'password' => ['required', 'confirmed', 'min:8'],
            'is_super_admin' => ['required', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        // ✅ VALIDASI BATASAN JUMLAH ADMIN
        if ($validated['is_super_admin']) {
            $currentSuperAdminCount = User::where('role', 'admin')
                ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
                ->count();

            if ($currentSuperAdminCount >= 3) {
                return back()
                    ->withInput()
                    ->with('error', 'Maksimal hanya 3 Super Admin yang diperbolehkan!');
            }
        } else {
            $currentAdminCount = User::where('role', 'admin')
                ->whereDoesntHave('roles', function($q) {
                    $q->where('name', 'super-admin');
                })
                ->count();

            if ($currentAdminCount >= 5) {
                return back()
                    ->withInput()
                    ->with('error', 'Maksimal hanya 5 Admin biasa yang diperbolehkan!');
            }
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'role' => 'admin',
            ]);

            $user->assignRole('admin');

            // BUG LAMA: is_super_admin divalidasi & dicatat ke log, tapi tidak
            // pernah diterapkan, jadi admin baru selalu jadi admin biasa.
            if ($validated['is_super_admin']) {
                $user->assignRole('super-admin');
            }

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                $user->profile_photo            = $this->processProfilePhoto($file);
                $user->profile_photo_name       = $file->getClientOriginalName();
                $user->profile_photo_updated_at = now();
                $user->save();
            }

            LogActivity::create([
                'user_id'       => auth()->id(),
                'role'          => 'admin',
                'activity_type' => 'create',
                'module'        => 'admin',
                'description'   => 'Membuat admin baru: ' . $user->name,
                'model_type'    => User::class,
                'model_id'      => $user->id,
                'new_values'    => [
                    'name'           => $validated['name'],
                    'is_super_admin' => $validated['is_super_admin'],
                ],
                'ip_address'    => request()->ip(),
                'user_agent'    => (string) request()->userAgent(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.admins.index')
                ->with('success', 'Admin berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan admin: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified admin in storage.
     */
    public function update(Request $request, $id)
    {
        $admin = User::findOrFail($id);

        if ($admin->role !== 'admin') {
            abort(404, 'User bukan admin');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name,' . $admin->id],
            'is_super_admin' => ['required', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'delete_photo' => ['nullable', 'boolean'],
        ]);

        $currentIsSuperAdmin = $admin->hasRole('super-admin');

        if ($currentIsSuperAdmin !== $validated['is_super_admin']) {
            if ($validated['is_super_admin']) {
                $currentSuperAdminCount = User::where('role', 'admin')
                    ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
                    ->count();

                if ($currentSuperAdminCount >= 3) {
                    return back()
                        ->withInput()
                        ->with('error', 'Maksimal hanya 3 Super Admin yang diperbolehkan!');
                }
            } else {
                $currentAdminCount = User::where('role', 'admin')
                    ->whereDoesntHave('roles', function($q) {
                        $q->where('name', 'super-admin');
                    })
                    ->count();

                if ($currentAdminCount >= 5) {
                    return back()
                        ->withInput()
                        ->with('error', 'Maksimal hanya 5 Admin biasa yang diperbolehkan!');
                }
            }
        }

        DB::beginTransaction();
        try {
            $oldData = [
                'name' => $admin->name,
                'is_super_admin' => $admin->hasRole('super-admin'),
            ];

            $admin->update([
                'name' => $validated['name'],
            ]);

            if ($validated['is_super_admin']) {
                $admin->assignRole('super-admin');
            } else {
                $admin->removeRole('super-admin');
                // Bersihkan sisa assignment langsung dari skema lama, kalau ada.
                $admin->revokePermissionTo('kelola-admin');
            }

            // Hapus foto jika diminta (jadi polosan)
            if ($request->boolean('delete_photo') && $admin->profile_photo) {
                if (Storage::disk('public')->exists($admin->profile_photo)) {
                    Storage::disk('public')->delete($admin->profile_photo);
                }
                $admin->profile_photo      = null;
                $admin->profile_photo_name = null;
                $admin->save();
            }

            if ($request->hasFile('profile_photo')) {
                if ($admin->profile_photo && Storage::disk('public')->exists($admin->profile_photo)) {
                    Storage::disk('public')->delete($admin->profile_photo);
                }
                $file = $request->file('profile_photo');
                $admin->profile_photo            = $this->processProfilePhoto($file);
                $admin->profile_photo_name       = $file->getClientOriginalName();
                $admin->profile_photo_updated_at = now();
                $admin->save();
            }

            LogActivity::create([
                'user_id'       => auth()->id(),
                'role'          => 'admin',
                'activity_type' => 'update',
                'module'        => 'admin',
                'description'   => 'Mengubah data admin: ' . $admin->name,
                'model_type'    => User::class,
                'model_id'      => $admin->id,
                'old_values'    => $oldData,
                'new_values'    => [
                    'name'           => $validated['name'],
                    'is_super_admin' => $validated['is_super_admin'],
                ],
                'ip_address'    => request()->ip(),
                'user_agent'    => (string) request()->userAgent(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.admins.index')
                ->with('success', 'Data admin berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data admin: ' . $e->getMessage());
        }
    }

    /**
     * Reset password admin menjadi password acak baru.
     *
     * Sebelum ini tidak ada cara mengganti password admin sama sekali:
     * update() sengaja tidak menyentuh kolom password, sehingga satu-satunya
     * jalan saat admin lupa password adalah mengintip plain_password.
     */
    public function resetPassword($id)
    {
        $admin = User::findOrFail($id);

        // Guard yang sama dengan update() dan destroy(): akun pemilik sistem
        // (kolom role = 'super-admin') tidak dikelola dari halaman ini.
        if ($admin->role !== 'admin') {
            abort(404, 'User bukan admin');
        }

        // Tanpa simbol: nilainya disalin manual, dan karakter seperti '#'
        // sudah pernah bikin masalah saat disalin ke tempat lain.
        $passwordBaru = Str::password(12, symbols: false);

        DB::beginTransaction();
        try {
            $admin->password       = Hash::make($passwordBaru);
            $admin->plain_password = $passwordBaru;
            $admin->save();

            // Password TIDAK ikut dicatat di log — log dibaca banyak orang.
            LogActivity::create([
                'user_id'       => auth()->id(),
                'role'          => 'admin',
                'activity_type' => 'update',
                'module'        => 'admin',
                'description'   => 'Mereset password admin: ' . $admin->name,
                'model_type'    => User::class,
                'model_id'      => $admin->id,
                'ip_address'    => request()->ip(),
                'user_agent'    => (string) request()->userAgent(),
            ]);

            DB::commit();

            return back()->with('success', "Password {$admin->name} sekarang: {$passwordBaru}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mereset password: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified admin from storage.
     */
    public function destroy($id)
    {
        $admin = User::findOrFail($id);

        if ($admin->role !== 'admin') {
            abort(404, 'User bukan admin');
        }

        if ($admin->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
        }

        if ($admin->hasRole('super-admin')) {
            return back()->with('error', 'Super Admin tidak dapat dihapus!');
        }

        DB::beginTransaction();
        try {
            $userName = $admin->name;
            $isSuperAdmin = $admin->hasRole('super-admin');

            LogActivity::create([
                'user_id'       => auth()->id(),
                'role'          => 'admin',
                'activity_type' => 'delete',
                'module'        => 'admin',
                'description'   => 'Menghapus admin: ' . $userName,
                'model_type'    => User::class,
                'model_id'      => $admin->id,
                'old_values'    => [
                    'name'           => $userName,
                    'is_super_admin' => $isSuperAdmin,
                ],
                'ip_address'    => request()->ip(),
                'user_agent'    => (string) request()->userAgent(),
            ]);

            // Hapus file foto profil agar tidak jadi sampah di storage
            if ($admin->profile_photo && Storage::disk('public')->exists($admin->profile_photo)) {
                Storage::disk('public')->delete($admin->profile_photo);
            }

            $admin->delete();

            DB::commit();

            return redirect()
                ->route('admin.admins.index')
                ->with('success', 'Admin berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus admin: ' . $e->getMessage());
        }
    }

    /**
     * Resize & crop foto jadi persegi (square) agar avatar tajam.
     */
    private function processProfilePhoto($file): string
    {
        $maxSize = 400;
        $path = $file->store('profile-photos', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $info = getimagesize($fullPath);
        if (!$info) return $path;

        [$width, $height] = $info;
        $mime = $info['mime'];

        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($fullPath),
            'image/png'  => imagecreatefrompng($fullPath),
            default      => null,
        };
        if (!$src) return $path;

        $min  = min($width, $height);
        $srcX = (int) (($width - $min) / 2);
        $srcY = ($height > $width) ? 0 : (int) (($height - $min) / 2);

        $size  = min($min, $maxSize);
        $dst   = imagecreatetruecolor($size, $size);
        $isPng = $mime === 'image/png';

        if ($isPng) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $min, $min);

        if ($isPng) {
            imagepng($dst, $fullPath, 6);
        } else {
            imagejpeg($dst, $fullPath, 95);
        }

        imagedestroy($src);
        imagedestroy($dst);

        return $path;
    }
}
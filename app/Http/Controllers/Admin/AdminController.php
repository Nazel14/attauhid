<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\LogActivity;

class AdminController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        // ✅ PERMISSION-BASED: Hanya tampilkan user dengan role 'admin'
        $query = User::where('role', 'admin')
            ->with('permissions'); // Load permissions, bukan roles

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $admins = $query->latest()->paginate(10)->withQueryString();

        return view('admin.admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create()
    {
        return view('admin.admins.create');
    }

    /**
     * Store a newly created admin in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'password' => ['required', 'confirmed', 'min:8'],
            'is_super_admin' => ['required', 'boolean'], // ✅ Checkbox, bukan dropdown
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        // ✅ VALIDASI BATASAN JUMLAH ADMIN
        if ($validated['is_super_admin']) {
            // Hitung super admin = yang punya permission 'kelola-admin'
            $currentSuperAdminCount = User::where('role', 'admin')
                ->permission('kelola-admin')
                ->count();

            if ($currentSuperAdminCount >= 3) {
                return back()
                    ->withInput()
                    ->with('error', 'Maksimal hanya 3 Super Admin yang diperbolehkan!');
            }
        } else {
            // Hitung admin biasa = yang tidak punya permission 'kelola-admin'
            $currentAdminCount = User::where('role', 'admin')
                ->whereDoesntHave('permissions', function($q) {
                    $q->where('name', 'kelola-admin');
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
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'role' => 'admin', // ✅ Semua admin punya role 'admin'
            ]);

            // ✅ ASSIGN ROLE 'admin' (bukan 'super-admin')
            $user->assignRole('admin');

            // ✅ Upload foto profil jika ada (resize jadi persegi 300x300)
            if ($request->hasFile('profile_photo')) {
                $user->profile_photo = $this->processProfilePhoto($request->file('profile_photo'));
                $user->save();
            }

            
            // Log activity
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
     * Show the form for editing the specified admin.
     */
    public function edit($id)
    {
        $admin = User::findOrFail($id);
        
        // Pastikan user yang diedit adalah admin
        if ($admin->role !== 'admin') {
            abort(404, 'User bukan admin');
        }

        // ✅ Cek apakah super admin berdasarkan permission
        $isSuperAdmin = $admin->hasPermissionTo('kelola-admin');

        return view('admin.admins.edit', compact('admin', 'isSuperAdmin'));
    }

    /**
     * Update the specified admin in storage.
     */
    public function update(Request $request, $id)
    {
        $admin = User::findOrFail($id);
        
        // Pastikan user yang diedit adalah admin
        if ($admin->role !== 'admin') {
            abort(404, 'User bukan admin');
        }

        // Validasi
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name,' . $admin->id],
            'is_super_admin' => ['required', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        // ✅ VALIDASI BATASAN SAAT MENGUBAH STATUS
        $currentIsSuperAdmin = $admin->hasPermissionTo('kelola-admin');
        
        if ($currentIsSuperAdmin !== $validated['is_super_admin']) {
            if ($validated['is_super_admin']) {
                // Berubah jadi super admin
                $currentSuperAdminCount = User::where('role', 'admin')
                    ->permission('kelola-admin')
                    ->count();
                
                if ($currentSuperAdminCount >= 3) {
                    return back()
                        ->withInput()
                        ->with('error', 'Maksimal hanya 3 Super Admin yang diperbolehkan!');
                }
            } else {
                // Berubah jadi admin biasa
                $currentAdminCount = User::where('role', 'admin')
                    ->whereDoesntHave('permissions', function($q) {
                        $q->where('name', 'kelola-admin');
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
                'is_super_admin' => $admin->hasPermissionTo('kelola-admin'),
            ];

            // Update user
            $admin->update([
                'name' => $validated['name'],
            ]);

            // ✅ Update permission (bukan role!)
            if ($validated['is_super_admin']) {
                $admin->givePermissionTo('kelola-admin');
            } else {
                $admin->revokePermissionTo('kelola-admin');
            }
            // ✅ Ganti foto profil jika ada upload baru
            if ($request->hasFile('profile_photo')) {
                if ($admin->profile_photo && Storage::disk('public')->exists($admin->profile_photo)) {
                    Storage::disk('public')->delete($admin->profile_photo);
                }
                $admin->profile_photo = $this->processProfilePhoto($request->file('profile_photo'));
                $admin->save();
            }

            // Log activity
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
     * Remove the specified admin from storage.
     */
    public function destroy($id)
    {
        $admin = User::findOrFail($id);
        
        // Pastikan user yang dihapus adalah admin
        if ($admin->role !== 'admin') {
            abort(404, 'User bukan admin');
        }

        // Tidak bisa hapus diri sendiri
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
        }

        // ✅ SUPER ADMIN (punya permission kelola-admin) TIDAK BOLEH DIHAPUS
        if ($admin->hasPermissionTo('kelola-admin')) {
            return back()->with('error', 'Super Admin tidak dapat dihapus!');
        }

        DB::beginTransaction();
        try {
            $userName = $admin->name;
            $isSuperAdmin = $admin->hasPermissionTo('kelola-admin');

            // Log activity sebelum dihapus
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
    $srcY = ($height > $width) ? 0 : (int) (($height - $min) / 2); // ✅ potret crop dari atas

    $size  = min($min, $maxSize); // ✅ jangan upscale (anti-buram)
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
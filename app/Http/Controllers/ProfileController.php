<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /** Jeda minimal antar-perubahan foto profil guru (hari). */
    private const PHOTO_COOLDOWN_DAYS = 3;

   public function edit(Request $request): Response
    {
        $user = $request->user();

        // Aturan:
        // - santri : read-only penuh (tidak bisa ubah nama maupun foto)
        // - guru   : hanya foto, dengan cooldown 3 hari
        // - admin  : hanya foto, tanpa cooldown
        // - nama   : tidak dapat diubah oleh siapa pun
        $canUpdatePhoto = true;
        $nextUpdateDate = null;

        if ($user->role === 'santri') {
            $canUpdatePhoto = false;
        } elseif ($user->role === 'guru' && $user->profile_photo_updated_at) {
            $next = Carbon::parse($user->profile_photo_updated_at)->addDays(self::PHOTO_COOLDOWN_DAYS);

            if ($next->isFuture()) {
                $canUpdatePhoto = false;
                $nextUpdateDate = $next->locale('id')->isoFormat('D MMMM YYYY, HH:mm');
            }
        }

        // Super Admin dibedakan lewat permission 'kelola-admin' (samakan dgn AuthenticatedLayout)
        $isSuperAdmin = $user->role === 'admin' && $user->can('kelola-admin');

        $roleLabel = match (true) {
            $isSuperAdmin           => 'Super Admin',
            $user->role === 'admin' => 'Admin',
            $user->role === 'guru'  => 'Guru',
            default                 => 'Santri',
        };

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'role'          => $user->role,
                'role_label'    => $roleLabel,
                'profile_photo' => $user->profile_photo
                    ? asset('storage/' . $user->profile_photo)
                    : null,
            ],
            'canUpdatePhoto'    => $canUpdatePhoto,
            'nextUpdateDate'    => $nextUpdateDate,
            // 0 = tanpa cooldown (admin)
            'photoCooldownDays' => $user->role === 'guru' ? self::PHOTO_COOLDOWN_DAYS : 0,
        ]);
    }

    /**
     * Nama profil tidak dapat diubah sendiri oleh role mana pun.
     * Route dipertahankan agar tidak memutus link/redirect yang sudah ada.
     */
    public function update(Request $request): RedirectResponse
    {
        return Redirect::route('profile.edit')
            ->with('error', 'Nama profil tidak dapat diubah sendiri. Silakan hubungi admin.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'santri') {
            return Redirect::route('profile.edit')
                ->with('error', 'Santri tidak dapat mengubah foto profil.');
        }

        if ($user->role === 'guru' && $user->profile_photo_updated_at) {
            $next = Carbon::parse($user->profile_photo_updated_at)->addDays(self::PHOTO_COOLDOWN_DAYS);

            if ($next->isFuture()) {
                return Redirect::route('profile.edit')->with('error',
                    'Foto profil hanya bisa diubah 1x per ' . self::PHOTO_COOLDOWN_DAYS . ' hari. Coba lagi pada '
                    . $next->locale('id')->isoFormat('D MMMM YYYY, HH:mm') . '.');
            }
        }

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'profile_photo.required' => 'Silakan pilih file foto terlebih dahulu.',
            'profile_photo.image'    => 'File harus berupa gambar.',
            'profile_photo.mimes'    => 'Format foto harus JPG atau PNG.',
            'profile_photo.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $file = $request->file('profile_photo');

        $user->profile_photo            = $this->processProfilePhoto($file);
        $user->profile_photo_name       = $file->getClientOriginalName();
        $user->profile_photo_updated_at = Carbon::now();
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'santri') {
            return Redirect::route('profile.edit')
                ->with('error', 'Santri tidak dapat menghapus foto profil.');
        }

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo      = null;
        $user->profile_photo_name = null;
        // profile_photo_updated_at sengaja TIDAK direset,
        // supaya guru tidak bisa bypass cooldown lewat hapus → upload ulang.
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Foto profil dihapus.');
    }

    /**
     * Resize & crop foto jadi persegi (bias atas agar kepala tidak terpotong).
     * Disamakan dengan GuruController & AdminController agar avatar konsisten.
     */
    private function processProfilePhoto($file): string
    {
        $maxSize  = 400;
        $path     = $file->store('profile-photos', 'public');
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
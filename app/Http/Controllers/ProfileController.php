<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        
        // ✅ Hapus batasan waktu - foto bisa diupdate kapan saja
        $canUpdatePhoto = true;
        $nextUpdateDate = null;
        
        return view('profile.edit', [
            'user' => $user,
            'canUpdatePhoto' => $canUpdatePhoto,
            'nextUpdateDate' => $nextUpdateDate
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Admin tidak bisa edit nama
        if ($request->user()->role === 'admin') {
            return Redirect::route('profile.edit')
                ->withErrors(['name' => 'Admin tidak dapat mengubah nama.']);
        }

        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 🔒 Santri: tidak boleh ubah foto sama sekali
        if ($user->role === 'santri') {
            return Redirect::route('profile.edit')
                ->with('error', 'Foto profil tidak dapat diubah.');
        }

        // 🔒 Guru: maksimal 1x per 7 hari
        if ($user->role === 'guru' && $user->profile_photo_updated_at) {
            $next = Carbon::parse($user->profile_photo_updated_at)->addDays(7);
            if ($next->isFuture()) {
                return Redirect::route('profile.edit')->with('error',
                    'Foto hanya bisa diubah seminggu sekali. Coba lagi pada '
                    . $next->locale('id')->isoFormat('D MMMM YYYY, HH:mm') . '.');
            }
        }
        // admin: lolos tanpa batasan

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048']
        ]);

        // Hapus foto lama jika ada
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        // Upload foto baru
        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        // Update database
        $user->profile_photo = $path;
        $user->profile_photo_updated_at = Carbon::now();
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 🔒 Santri tidak boleh menghapus foto (mencegah bypass aturan)
        if ($user->role === 'santri') {
            return Redirect::route('profile.edit')
                ->with('error', 'Foto profil tidak dapat diubah.');
        }

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo = null;
        // ⚠️ Catatan: cooldown TIDAK direset di sini (lihat penjelasan di bawah)
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'photo-deleted');
    }
}
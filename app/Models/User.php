<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'password',
        'plain_password',
        'role',
        'profile_photo',
        'profile_photo_name',
        'profile_photo_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'plain_password',
    ];

    /**
     * plain_password tetap tersembunyi untuk semua orang, kecuali:
     *  - Super Admin di panel admin (route admin.*): semua akun.
     *  - Guru di panel guru (route guru.*): hanya akun SANTRI.
     * Guru tidak pernah menerima password guru/admin lain.
     */
    public function getHidden()
    {
        $hidden = parent::getHidden();

        if ($this->viewerCanSeePlainPassword()) {
            $hidden = array_values(array_diff($hidden, ['plain_password']));
        }

        return $hidden;
    }

    protected function viewerCanSeePlainPassword(): bool
    {
        $viewer = auth()->user();

        if (! $viewer instanceof self) {
            return false;
        }

        if (request()->routeIs('admin.*') && $viewer->isSuperAdmin()) {
            return true;
        }

        return request()->routeIs('guru.*')
            && $viewer->role === 'guru'
            && $this->role === 'santri';
    }

    protected $casts = [
        'profile_photo_updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $user) {
            if (!$user->role) {
                return;
            }

            // Jangan lempar RoleDoesNotExist bila role belum di-seed.
            $role = Role::where('guard_name', 'web')
                ->where('name', $user->role)
                ->first();

            if ($role) {
                $user->assignRole($role);
            }
        });
    }

    public function getPlainPasswordAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setPlainPasswordAttribute($value)
    {
        $this->attributes['plain_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }
    
    // ✅ Relasi untuk SANTRI yang login (singular - hasOne)
    public function santri(): HasOne
    {
        return $this->hasOne(Santri::class, 'user_id');
    }

    // ✅ TAMBAHKAN: Relasi untuk WALI (plural - hasMany)
    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class, 'wali_user_id');
    }

    public function username()
    {
        return 'name';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isSantri(): bool
    {
        return $this->role === 'santri';
    }

       /**
     * Modul yang boleh diakses user ini.
     *
     * Daftarnya dibaca dari config/modules.php, TIDAK ditulis ulang di
     * sini. Versi lama menyalin kedua daftar ke dalam method ini, sehingga
     * menambah role di config tidak berpengaruh apa pun — perubahannya
     * diam-diam tidak terpakai, dan tidak ada pesan error yang memberi
     * tahu bahwa itu yang terjadi.
     */
    public function availableModules(): array
    {
        $map = [
            // Nilai kedua hanya jaring pengaman bila kunci config-nya
            // hilang — bukan sumber kebenaran. Tanpa ini, instalasi yang
            // config-nya tertinggal akan mengunci semua orang keluar.
            'raport'   => config('modules.raport_roles', ['super-admin', 'admin', 'guru', 'santri']),
            'keuangan' => config('modules.keuangan_roles', ['super-admin', 'bendahara']),
        ];

        $modules = [];

        foreach ($map as $module => $roles) {
            if (! config("modules.$module") || empty($roles)) {
                continue;
            }

            // Cek Spatie dulu, lalu fallback kolom — konsisten dengan RoleMiddleware.
            if ($this->hasAnyRole($roles) || in_array($this->role, $roles, true)) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * Pengguna luar pesantren (wali/santri). Tidak memilih panel saat
     * login dan tidak boleh menyentuh rute internal.
     */
    public function isEksternal(): bool
    {
        return in_array($this->role, config('modules.role_eksternal', []), true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }


    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        return null;
    }

    public function hasProfilePhoto(): bool
    {
        return !is_null($this->profile_photo);
    }

    /**
     * Nama asli untuk ditampilkan di UI (navbar, dashboard, sapaan).
     * guru   → gurus.name   (nama lengkap + gelar)
     * santri → santris.name
     * admin  → users.name   (admin tidak punya tabel profil)
     */
    public function getDisplayNameAttribute(): string
    {
        return match ($this->role) {
            'guru'   => $this->guru?->name   ?: $this->name,
            'santri' => $this->santri?->name ?: $this->name,
            default  => $this->name,
        };
    }
}
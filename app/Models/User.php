<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany; // ✅ TAMBAHKAN
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'password',
        'plain_password',
        'role',
        'profile_photo',
        'profile_photo_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'profile_photo_updated_at' => 'datetime',
    ];

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

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isAdminSenior(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAdminBiasa(): bool
    {
        return $this->hasRole('admin');
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
}
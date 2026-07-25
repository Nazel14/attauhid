<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guru extends Model
{
    protected $fillable = ['user_id', 'name', 'nip'];

    // Relasi ke User (untuk login)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Kelas (sebagai wali kelas)
    // Pastikan di tabel 'kelas' ada kolom 'guru_id'
    public function kelas(): HasOne
    {
        return $this->hasOne(Kelas::class, 'guru_id');
    }

    // Semua kelas yang diampu guru ini (bisa lebih dari satu)
    public function kelasDiampu(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    // Santri yang dibimbing
    public function santriBimbingan(): HasMany
    {
        return $this->hasMany(Santri::class, 'guru_id');
    }

    public function setorans(): HasMany
    {
        return $this->hasMany(Setoran::class);
    }
}
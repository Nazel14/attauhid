<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresensiSesiConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'sesi_name',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * ✅ Scope untuk sesi aktif saja
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ FIXED: Scope untuk sorting by urutan ASCENDING (eksplisit)
     * Urutan harus dari kecil ke besar: 1, 2, 3, 4, 5
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan', 'asc');
    }

    /**
     * ✅ Get all active sesi names as array (terurut)
     */
    public static function getActiveSesiNames()
    {
        return self::active()->ordered()->pluck('sesi_name')->toArray();
    }

    /**
     * ✅ Get all active sesi with full data (terurut)
     */
    public static function getActiveSessions()
    {
        return self::active()->ordered()->get();
    }

    /**
     * ✅ Get next available urutan number
     */
    public static function getNextUrutan()
    {
        $maxUrutan = self::max('urutan');
        return $maxUrutan !== null ? $maxUrutan + 1 : 1;
    }

    /**
     * ✅ Check if urutan is available (belum dipakai)
     */
    public static function isUrutanAvailable($urutan, $excludeId = null)
    {
        $query = self::where('urutan', $urutan);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }

    /**
     * Relasi dengan Presensi
     */
    public function presensis()
    {
        return $this->hasMany(Presensi::class, 'sesi', 'sesi_name');
    }
}
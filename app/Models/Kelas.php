<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tahun_ajaran',
        'guru_id',
        'gender_restriction',
    ];

    const GENDER_RESTRICTIONS = [
    'ikhwan' => 'Ikhwan (Laki-laki)',
    'akhwat' => 'Akhwat (Perempuan)',
    'campuran' => 'Campuran (Ikhwan & Akhwat)'
];

    /**
     * Relasi ke Guru (Satu kelas memiliki satu guru)
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Relasi ke Santri (Satu kelas memiliki banyak santri)
     */
    public function santris()
    {
        return $this->hasMany(Santri::class);
    }

    /**
     * Cek apakah ada santri dengan gender tidak sesuai restriction kelas
     */
    public function hasGenderMismatch()
    {
        if ($this->gender_restriction === 'campuran') {
            return false;
        }
        
        $allowedGender = $this->gender_restriction === 'ikhwan' ? 'Laki-laki' : 'Perempuan';
        
        return $this->santris()->where('gender', '!=', $allowedGender)->exists();
    }

    /**
     * Ambil santri yang gender-nya tidak sesuai restriction
     */
    public function getMismatchedSantris()
    {
        if ($this->gender_restriction === 'campuran') {
            return collect();
        }
        
        $allowedGender = $this->gender_restriction === 'ikhwan' ? 'Laki-laki' : 'Perempuan';
        
        return $this->santris()->where('gender', '!=', $allowedGender)->get();
    }
}
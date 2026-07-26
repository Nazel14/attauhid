<?php

namespace App\Models\Keuangan\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jabatan extends Model
{
    use SoftDeletes;

    // Atribut #[Table] / #[Fillable] versi asli dibuang — fitur Laravel 12+.
    protected $table = 'positions';

    protected $fillable = ['nama_jabatan', 'description', 'gaji_pokok_default'];

    protected $casts = [
        'gaji_pokok_default' => 'decimal:2',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'position_id');
    }
}
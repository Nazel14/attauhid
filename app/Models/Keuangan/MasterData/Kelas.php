<?php

namespace App\Models\Keuangan\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    // Tanpa SoftDeletes — tabel `classes` tidak punya kolom deleted_at.
    protected $table = 'classes';

    protected $fillable = ['nama_kelas', 'tingkat', 'kapasitas', 'is_active'];

    protected $casts = [
        'kapasitas' => 'integer',
        'is_active' => 'boolean',
    ];

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'class_id');
    }

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'class_id');
    }
}
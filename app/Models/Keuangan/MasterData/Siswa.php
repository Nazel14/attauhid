<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use SoftDeletes;

    // Atribut #[Table] / #[Fillable] versi asli dibuang — fitur Laravel 12+.
    protected $table = 'students';

    protected $fillable = [
        'nis', 'name', 'parent_name', 'parent_contact', 'student_type',
        'class', 'is_active', 'is_raport_held', 'class_id',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_raport_held' => 'boolean',
    ];

    // Foreign key WAJIB disebut. Versi asli mengosongkannya, sehingga
    // Eloquent menebak 'siswa_id' — padahal kolomnya 'student_id'.
    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'student_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'student_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'class_id');
    }
}
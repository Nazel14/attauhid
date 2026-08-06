<?php

namespace App\Models;

use App\Models\Keuangan\MasterData\Jabatan;
use App\Models\Keuangan\Payroll\Absensi;
use App\Models\Keuangan\MasterData\Potongan;
use App\Models\Keuangan\MasterData\Tunjangan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tabel `gurus` adalah satu-satunya master pegawai untuk KEDUA modul.
 * Guru yang diinput dari panel raport langsung terbaca sebagai pegawai
 * di panel keuangan, tanpa sinkronisasi manual.
 *
 * Tabel `employees` sudah tidak dipakai; model Pegawai di namespace
 * Keuangan menunjuk ke tabel ini juga.
 */
class Guru extends Model
{
    use SoftDeletes;

    // Wajib eksplisit: subclass Pegawai akan menebak 'pegawais'
    // kalau nama tabelnya diserahkan ke konvensi Eloquent.
    protected $table = 'gurus';

    protected $fillable = [
        'user_id',
        'name',
        'nip',
        'position_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ---------- RELASI RAPORT ----------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Wali kelas — satu kelas per guru pada tahun ajaran berjalan
    public function kelas(): HasOne
    {
        return $this->hasOne(Kelas::class, 'guru_id');
    }

    // Semua kelas yang pernah/sedang diampu (lintas tahun ajaran)
    public function kelasDiampu(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    public function santriBimbingan(): HasMany
    {
        return $this->hasMany(Santri::class, 'guru_id');
    }

    public function setorans(): HasMany
    {
        return $this->hasMany(Setoran::class);
    }

    // ---------- RELASI KEUANGAN ----------

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'position_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'employee_id');
    }

    public function defaultAllowances(): BelongsToMany
    {
        return $this->belongsToMany(Tunjangan::class, 'employee_allowance', 'employee_id', 'allowance_id')
            ->withPivot('nominal_override')
            ->withTimestamps();
    }

    public function defaultDeductions(): BelongsToMany
    {
        return $this->belongsToMany(Potongan::class, 'employee_deduction', 'employee_id', 'deduction_id')
            ->withPivot('nominal_override')
            ->withTimestamps();
    }

    /** Guru yang benar-benar mengajar (punya kelas), bukan staf administratif */
    public function scopeMengajar($query)
    {
        return $query->whereHas('kelasDiampu');
    }
}
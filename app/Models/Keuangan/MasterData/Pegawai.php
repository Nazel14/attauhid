<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Keuangan\Payroll\Absensi;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use SoftDeletes;

    // Atribut #[Table] / #[Fillable] versi asli dibuang — fitur Laravel 12+.
    protected $table = 'employees';

    protected $fillable = ['user_id', 'position_id', 'nip', 'name', 'is_active', 'class_id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'position_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'class_id');
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
}
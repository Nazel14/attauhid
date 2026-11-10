<?php

namespace App\Models\Keuangan\Payroll;

use App\Models\Keuangan\MasterData\Pegawai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $table = 'attendances';

    protected $fillable = ['employee_id', 'date', 'status'];

    protected $casts = [
        'date' => 'date',
    ];

    public const STATUS = ['Hadir', 'Sakit', 'Izin', 'Alfa'];

    /** Status yang dihitung sebagai tidak masuk saat penggajian. */
    public const TIDAK_MASUK = ['Sakit', 'Izin', 'Alfa'];

    // di model Absensi
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'employee_id')->withTrashed();
    }
}
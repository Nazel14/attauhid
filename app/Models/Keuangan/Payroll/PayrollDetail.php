<?php

namespace App\Models\Keuangan\Payroll;

use App\Models\Keuangan\MasterData\Pegawai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollDetail extends Model
{
    use SoftDeletes;

    // Namespace versi asli: App\Models\PayRoll (huruf R besar). Di Windows
    // lolos, di Linux autoloader tidak menemukannya sama sekali.
    protected $table = 'payroll_details';

    protected $fillable = [
        'payroll_header_id', 'employee_id', 'gaji_pokok',
        'nominal_tambahan', 'keterangan_tambahan',
        'total_tunjangan', 'total_potongan', 'take_home_pay', 'is_paid',
    ];

    protected $casts = [
        'gaji_pokok'       => 'decimal:2',
        'total_tunjangan'  => 'decimal:2',
        'total_potongan'   => 'decimal:2',
        'nominal_tambahan' => 'integer',
        'take_home_pay'    => 'decimal:2',
        'is_paid'          => 'boolean',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(PayrollHeader::class, 'payroll_header_id');
    }

    // Foreign key wajib disebut — tanpa ini Eloquent menebak 'pegawai_id'.
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'employee_id');
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(PayrollTunjangan::class, 'payroll_detail_id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollPotongan::class, 'payroll_detail_id');
    }
}
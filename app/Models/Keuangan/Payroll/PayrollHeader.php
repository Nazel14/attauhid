<?php

namespace App\Models\Keuangan\Payroll;

use App\Models\Keuangan\MasterData\Rekening;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollHeader extends Model
{
    use SoftDeletes;

    protected $table = 'payroll_headers';

    protected $fillable = [
        'periode_bulan', 'periode_tahun',
        'total_gaji_pokok', 'total_tunjangan', 'total_potongan', 'total_tambahan', 'grand_total',
        'status_approval', 'account_id', 'created_by',
        'diperiksa_by', 'diperiksa_at', 'disetujui_by', 'disetujui_at', 'dibayar_by', 'dibayar_at',
    ];

    protected $casts = [
        'total_gaji_pokok' => 'decimal:2',
        'total_tunjangan'  => 'decimal:2',
        'total_potongan'   => 'decimal:2',
        'grand_total'      => 'decimal:2',
        'diperiksa_at'     => 'datetime',
        'disetujui_at'     => 'datetime',
        'dibayar_at'       => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class, 'payroll_header_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'account_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function diperiksaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_by');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_by');
    }

    public function pembayar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }
}
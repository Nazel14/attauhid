<?php

namespace App\Models\Keuangan\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salinan nominal tunjangan saat penggajian dibuat. Sengaja disimpan
 * terpisah agar slip gaji lama tidak berubah ketika master komponen
 * diedit atau dihapus.
 */
class PayrollTunjangan extends Model
{
    protected $table = 'payroll_allowances';

    protected $fillable = ['payroll_detail_id', 'nama_tunjangan_snapshot', 'nominal_snapshot'];

    protected $casts = ['nominal_snapshot' => 'decimal:2'];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(PayrollDetail::class, 'payroll_detail_id');
    }
}
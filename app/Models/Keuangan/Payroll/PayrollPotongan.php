<?php

namespace App\Models\Keuangan\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPotongan extends Model
{
    protected $table = 'payroll_deductions';

    protected $fillable = ['payroll_detail_id', 'nama_potongan_snapshot', 'nominal_snapshot'];

    protected $casts = ['nominal_snapshot' => 'decimal:2'];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(PayrollDetail::class, 'payroll_detail_id');
    }
}
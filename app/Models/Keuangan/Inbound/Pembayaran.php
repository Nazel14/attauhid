<?php

namespace App\Models\Keuangan\Inbound;

use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Keuangan\MasterData\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sebagian besar dilengkapi pada tahap 4d.
 * Relasi di bawah dipakai lebih dulu oleh dashboard.
 */
class Pembayaran extends Model
{
    use SoftDeletes;

    protected $table = 'inbound_payments';

    protected $guarded = ['id'];

    protected $casts = [
        'nominal'      => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'account_id');
    }
}
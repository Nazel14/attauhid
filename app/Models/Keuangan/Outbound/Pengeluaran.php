<?php

namespace App\Models\Keuangan\Outbound;

use App\Models\Keuangan\MasterData\Rekening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sebagian besar dilengkapi pada tahap 4e.
 * Relasi di bawah dipakai lebih dulu oleh dashboard.
 */
class Pengeluaran extends Model
{
    use SoftDeletes;

    protected $table = 'outbound_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'nominal_pengajuan' => 'decimal:2',
        'nominal_realisasi' => 'decimal:2',
        'tanggal_pengajuan' => 'date',
    ];

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'account_id');
    }
}
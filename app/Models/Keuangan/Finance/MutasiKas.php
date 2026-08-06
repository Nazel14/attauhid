<?php

namespace App\Models\Keuangan\Finance;

use App\Models\Keuangan\MasterData\Rekening;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MutasiKas extends Model
{
    protected $table = 'mutasi_kas';

    protected $fillable = [
        'account_id', 'tanggal_transaksi', 'keterangan', 'jenis', 'nominal',
        'saldo_setelah_transaksi', 'reference_type', 'reference_id', 'created_by',
    ];

    protected $casts = [
        'tanggal_transaksi'       => 'datetime',
        'nominal'                 => 'decimal:2',
        'saldo_setelah_transaksi' => 'decimal:2',
    ];

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'account_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}
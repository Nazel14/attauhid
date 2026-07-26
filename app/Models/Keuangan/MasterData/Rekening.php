<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Outbound\Pengeluaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rekening extends Model
{
    use SoftDeletes;

    // Atribut #[Table] / #[Fillable] versi asli dibuang — itu fitur
    // Laravel 12+, tidak ada di Laravel 11.
    protected $table = 'accounts';

    protected $fillable = ['account_name', 'type', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function pemasukan(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'account_id');
    }

    public function pengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'account_id');
    }
}
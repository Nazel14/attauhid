<?php

namespace App\Models\Keuangan\Inbound;

use App\Models\Keuangan\MasterData\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPenagihan extends Model
{
    protected $table = 'riwayat_penagihan';

    protected $fillable = [
        'student_id', 'invoice_id', 'tanggal_penagihan',
        'metode', 'keterangan', 'ditagih_oleh',
    ];

    protected $casts = [
        'tanggal_penagihan' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'invoice_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditagih_oleh');
    }
}
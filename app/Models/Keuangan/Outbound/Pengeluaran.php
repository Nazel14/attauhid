<?php

namespace App\Models\Keuangan\Outbound;

use App\Models\Keuangan\MasterData\Rekening;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pengeluaran extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'outbound_requests';

    protected $fillable = [
        'rkas_id', 'account_id', 'tanggal_pengajuan', 'nominal_pengajuan', 'deskripsi',
        'nominal_realisasi', 'nominal_refund', 'is_refund_completed', 'is_over_budget_warning',
        'status_approval', 'requested_by',
        'diperiksa_by', 'diperiksa_at', 'disetujui_by', 'disetujui_at',
    ];

    protected $casts = [
        'tanggal_pengajuan'      => 'date',
        'diperiksa_at'           => 'datetime',
        'disetujui_at'           => 'datetime',
        'nominal_pengajuan'      => 'decimal:2',
        'nominal_realisasi'      => 'decimal:2',
        'nominal_refund'         => 'decimal:2',
        'is_refund_completed'    => 'boolean',
        'is_over_budget_warning' => 'boolean',
    ];

    public function rkas(): BelongsTo
    {
        return $this->belongsTo(RkasDetail::class, 'rkas_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'account_id');
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function diperiksaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_by');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_by');
    }

    public function registerMediaCollections(): void
    {
        // Disk 'local' — bukti pengeluaran tidak boleh diakses publik.
        $this->addMediaCollection('bukti_pengeluaran')->useDisk('local');
    }
}
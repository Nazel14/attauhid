<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantriTargetHafalan extends Model
{
    use HasFactory;

    protected $table = 'santri_target_hafalan';

    protected $fillable = [
        'santri_id',
        'tahun_ajaran_id',
        'target_juz',
        'target_halaman',
        'keterangan',
    ];

    protected $casts = [
        'target_juz'    => 'decimal:2',
        'target_halaman' => 'integer',
    ];

    // =====================================================
    // RELASI
    // =====================================================

    /**
     * withTrashed: alasan sama dengan relasi santri() di model lain —
     * target hafalan ikut tercetak di rapor historis, dan rapor itu
     * tetap harus bernama walau santrinya sudah diarsipkan.
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    // =====================================================
    // SCOPES
    // =====================================================

    /**
     * Scope: target untuk tahun ajaran aktif
     */
    public function scopeAktif($query)
    {
        return $query->whereHas('tahunAjaran', fn($q) => $q->where('is_aktif', true));
    }

    /**
     * Scope: berdasarkan tahun ajaran tertentu
     */
    public function scopeByTahunAjaran($query, int $tahunAjaranId)
    {
        return $query->where('tahun_ajaran_id', $tahunAjaranId);
    }
}
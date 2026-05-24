<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Santri extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kelas_id',
        'guru_id',
        'nis',
        'name',
        'gender',
        'target_juz',
    ];

    protected $casts = [
        'target_juz' => 'decimal:2',
    ];

    // =====================================================
    // RELASI LAMA (tidak berubah)
    // =====================================================

    /**
     * Relasi One-to-One: Santri has one User (akun login santri).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi Many-to-One: Santri belongs to one Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Relasi Many-to-One: Santri belongs to one Guru (Wali Kelas).
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Relasi One-to-Many: Santri has many Presensis
     */
    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class, 'santri_id');
    }

    /**
     * Relasi One-to-Many: Santri has many Setorans
     */
    public function setorans(): HasMany
    {
        return $this->hasMany(Setoran::class, 'santri_id');
    }

    // =====================================================
    // RELASI BARU (tambahan untuk sistem tahun ajaran)
    // =====================================================

    /**
     * Riwayat kelas santri per tahun ajaran
     */
    public function kelasHistories(): HasMany
    {
        return $this->hasMany(SantriKelasHistory::class);
    }

    /**
     * Target hafalan per tahun ajaran
     */
    public function targetHafalans(): HasMany
    {
        return $this->hasMany(SantriTargetHafalan::class);
    }

    // =====================================================
    // HELPER METHODS LAMA (tidak berubah)
    // =====================================================

    /**
     * Get total poin presensi santri di tanggal tertentu
     */
    public function getTotalPoinHarian($tanggal)
    {
        return $this->presensis()
                    ->where('tanggal', $tanggal)
                    ->sum('poin');
    }

    /**
     * Get rata-rata nilai setoran santri
     */
    public function getRataNilaiSetoran()
    {
        return $this->setorans()->avg('nilai');
    }

    /**
     * Accessor untuk gender icon
     */
    public function getGenderIconAttribute()
    {
        return $this->gender === 'Laki-laki' ? '👨' : '👩';
    }

    /**
     * Accessor untuk gender label
     */
    public function getGenderLabelAttribute()
    {
        return $this->gender;
    }

    /**
     * Accessor untuk gender code (L/P)
     */
    public function getGenderCodeAttribute()
    {
        return $this->gender === 'Laki-laki' ? 'L' : 'P';
    }

    // =====================================================
    // HELPER METHODS BARU (tambahan untuk sistem tahun ajaran)
    // =====================================================

    /**
     * Ambil target juz santri untuk tahun ajaran tertentu
     * Jika tidak ada di santri_target_hafalan, fallback ke target_juz lama
     */
    public function getTargetJuzByTahunAjaran(?int $tahunAjaranId = null): float
    {
        $taId = $tahunAjaranId ?? TahunAjaran::getAktifId();

        if (!$taId) {
            return (float) ($this->target_juz ?? 0);
        }

        $target = $this->targetHafalans()
                       ->where('tahun_ajaran_id', $taId)
                       ->first();

        return (float) ($target?->target_juz ?? $this->target_juz ?? 0);
    }

    /**
     * Ambil total juz yang sudah disetorkan dalam satu tahun ajaran
     */
    public function getTotalJuzByTahunAjaran(?int $tahunAjaranId = null): float
    {
        $taId = $tahunAjaranId ?? TahunAjaran::getAktifId();

        return (float) $this->setorans()
            ->when($taId, fn($q) => $q->where('tahun_ajaran_id', $taId))
            ->whereNotNull('kategori_setoran')
            ->sum('total_juz');
    }

    /**
     * Hitung persentase hafalan dalam satu tahun ajaran
     */
    public function getPersenHafalanByTahunAjaran(?int $tahunAjaranId = null): float
    {
        $target = $this->getTargetJuzByTahunAjaran($tahunAjaranId);

        if ($target <= 0) return 0;

        $total = $this->getTotalJuzByTahunAjaran($tahunAjaranId);

        return round(min(($total / $target) * 100, 100), 1);
    }
}
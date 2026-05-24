<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    use HasFactory;

    protected $table = 'tahun_ajaran';

    protected $fillable = [
        'nama',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_aktif',
        'keterangan',
    ];

    protected $casts = [
        'semester'        => 'integer',
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'is_aktif'        => 'boolean',
    ];

    // =====================================================
    // RELASI
    // =====================================================

    /**
     * Relasi ke Presensi
     */
    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }

    /**
     * Relasi ke Setoran
     */
    public function setorans(): HasMany
    {
        return $this->hasMany(Setoran::class);
    }

    /**
     * Relasi ke SantriKelasHistory
     */
    public function santriKelasHistories(): HasMany
    {
        return $this->hasMany(SantriKelasHistory::class);
    }

    /**
     * Relasi ke SantriTargetHafalan
     */
    public function santriTargetHafalans(): HasMany
    {
        return $this->hasMany(SantriTargetHafalan::class);
    }

    // =====================================================
    // SCOPES
    // =====================================================

    /**
     * Scope: hanya yang aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Scope: urutkan terbaru
     */
    public function scopeTerbaru($query)
    {
        return $query->orderByDesc('tanggal_mulai');
    }

    // =====================================================
    // STATIC HELPERS
    // =====================================================

    /**
     * Ambil tahun ajaran yang sedang aktif
     * Dipakai di mana-mana: controller, form, dsb
     */
    public static function getAktif(): ?self
    {
        return self::where('is_aktif', true)->first();
    }

    /**
     * Ambil ID tahun ajaran aktif
     * Shortcut agar tidak perlu ->getAktif()?->id berulang
     */
    public static function getAktifId(): ?int
    {
        return self::where('is_aktif', true)->value('id');
    }

    /**
     * Set tahun ajaran aktif (nonaktifkan yang lain dulu)
     */
    public static function setAktif(int $id): void
    {
        // Nonaktifkan semua
        self::query()->update(['is_aktif' => false]);
        // Aktifkan yang dipilih
        self::where('id', $id)->update(['is_aktif' => true]);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    /**
     * Label lengkap: "2025/2026 - Semester 1 (Ganjil)"
     */
    public function getLabelLengkapAttribute(): string
    {
        $semesterLabel = $this->semester == 1 ? 'Ganjil' : 'Genap';
        return "{$this->nama} - Semester {$this->semester} ({$semesterLabel})";
    }

    /**
     * Label pendek: "2025/2026 Sem.1"
     */
    public function getLabelPendekAttribute(): string
    {
        return "{$this->nama} Sem.{$this->semester}";
    }

    /**
     * Semester dalam teks
     */
    public function getSemesterLabelAttribute(): string
    {
        return $this->semester == 1 ? 'Ganjil' : 'Genap';
    }

    /**
     * Apakah tahun ajaran ini sedang berjalan hari ini
     */
    public function getIsBerjalanAttribute(): bool
    {
        $today = now()->toDateString();
        return $today >= $this->tanggal_mulai->toDateString()
            && $today <= $this->tanggal_selesai->toDateString();
    }
}
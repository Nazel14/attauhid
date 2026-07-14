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
        'ditutup_pada',
        'keterangan',
    ];

    protected $casts = [
        'semester'        => 'integer',
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'is_aktif'        => 'boolean',
        'ditutup_pada'    => 'datetime',
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

    /**
     * Scope: belum pernah dipakai — kandidat yang masih boleh diaktifkan
     */
    public function scopeBelumDipakai($query)
    {
        return $query->whereNull('ditutup_pada');
    }

    /**
     * Scope: sudah ditutup — arsip permanen
     */
    public function scopeArsip($query)
    {
        return $query->whereNotNull('ditutup_pada');
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
     * Set tahun ajaran aktif (nonaktifkan yang lain dulu).
     * Tahun ajaran yang sudah ditutup TIDAK BOLEH diaktifkan lagi —
     * penjagaan terakhir di level model, kalau-kalau ada pemanggil
     * yang lupa mengecek.
     */
    public static function setAktif(int $id): void
    {
        $target = self::findOrFail($id);

        if ($target->ditutup_pada) {
            throw new \RuntimeException(
                "Tahun ajaran {$target->label_lengkap} sudah ditutup dan tidak dapat diaktifkan kembali."
            );
        }

        // Nonaktifkan semua
        self::query()->update(['is_aktif' => false]);
        // Aktifkan yang dipilih
        self::where('id', $id)->update(['is_aktif' => true]);
    }

    /**
     * Tutup tahun ajaran ini secara permanen (jadi arsip).
     */
    public function tutup(): void
    {
        $this->update([
            'is_aktif'     => false,
            'ditutup_pada' => now(),
        ]);
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
     * Sudah pernah dipakai & ditutup → terkunci permanen.
     */
    public function getSudahDipakaiAttribute(): bool
    {
        return ! is_null($this->ditutup_pada);
    }

    /**
     * Boleh dipakai / diaktifkan? (belum pernah ditutup)
     */
    public function getBolehDiaktifkanAttribute(): bool
    {
        return is_null($this->ditutup_pada) && ! $this->is_aktif;
    }

    /**
     * Status ringkas: 'aktif' | 'arsip' | 'belum_dipakai'
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_aktif) return 'aktif';
        if ($this->ditutup_pada) return 'arsip';
        return 'belum_dipakai';
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
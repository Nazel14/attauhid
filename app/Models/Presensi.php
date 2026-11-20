<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran_id', 
        'santri_id',
        'guru_id',
        'tanggal',
        'sesi',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($presensi) {
            // Auto-isi tahun_ajaran_id dari yang aktif jika belum diisi
            if (empty($presensi->tahun_ajaran_id)) {
                $presensi->tahun_ajaran_id = TahunAjaran::getAktifId();
            }
        });
    }

    // =====================================================
    // RELASI LAMA (tidak berubah)
    // =====================================================

    /**
     * Relasi ke Santri.
     *
     * withTrashed: rekap presensi bulan-bulan lalu tetap menampilkan nama,
     * meski santrinya sudah diarsipkan lewat tombol Hapus di Data Siswa.
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    /**
     * Relasi ke Guru
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Relasi One-to-Many ke Setoran
     * Satu presensi bisa punya banyak setoran
     */
    public function setorans(): HasMany
    {
        return $this->hasMany(Setoran::class, 'presensi_id');
    }

    /**
     * Check apakah sudah ada setoran
     */
    public function hasSetoran(): bool
    {
        return $this->setorans()->exists();
    }

    /**
     * Get total setoran count
     */
    public function getTotalSetoranAttribute(): int
    {
        return $this->setorans()->count();
    }

    /**
     * Accessor untuk status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'Hadir' => '✅',
            'Sakit' => '🤒',
            'Izin' => '📝',
            'Alpha' => '❌',
            default => '❓'
        };
    }

    /**
     * Accessor untuk status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Hadir' => 'green',
            'Sakit' => 'orange',
            'Izin' => 'yellow',
            'Alpha' => 'red',
            default => 'gray'
        };
    }

    /**
     * Accessor untuk sesi color - DINAMIS
     */
    public function getSesiColorAttribute(): string
    {
        $colorPalette = [
            'blue', 'yellow', 'orange', 'purple', 'green', 'red',
            'pink', 'indigo', 'teal', 'cyan', 'lime', 'amber'
        ];

        $sesiConfig = PresensiSesiConfig::where('sesi_name', $this->sesi)->first();

        if ($sesiConfig) {
            $colorIndex = ($sesiConfig->urutan - 1) % count($colorPalette);
            return $colorPalette[$colorIndex];
        }

        return 'gray';
    }

    /**
     * Helper static untuk get semua sesi dengan warnanya
     */
    public static function getSesiColors(): array
    {
        $colorPalette = [
            'blue', 'yellow', 'orange', 'purple', 'green', 'red',
            'pink', 'indigo', 'teal', 'cyan', 'lime', 'amber'
        ];

        $sesis = PresensiSesiConfig::active()->ordered()->get();
        $result = [];

        foreach ($sesis as $sesi) {
            $colorIndex = ($sesi->urutan - 1) % count($colorPalette);
            $result[$sesi->sesi_name] = $colorPalette[$colorIndex];
        }

        return $result;
    }

    /**
     * Scope untuk hari ini
     */
    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * Scope berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope berdasarkan sesi
     */
    public function scopeBySesi($query, $sesi)
    {
        return $query->where('sesi', $sesi);
    }

    /**
     * Scope periode tertentu
     */
    public function scopePeriode($query, $days = 30)
    {
        return $query->where('tanggal', '>=', now()->subDays($days));
    }

    // =====================================================
    // RELASI & SCOPE BARU (tambahan untuk sistem tahun ajaran)
    // =====================================================

    /**
     * Relasi ke TahunAjaran
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Scope: filter berdasarkan tahun ajaran
     */
    public function scopeByTahunAjaran($query, ?int $tahunAjaranId = null)
    {
        $taId = $tahunAjaranId ?? TahunAjaran::getAktifId();
        return $query->where('tahun_ajaran_id', $taId);
    }
}
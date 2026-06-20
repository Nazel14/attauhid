<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setoran extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran_id', 
        'santri_id',
        'guru_id',
        'presensi_id',
        'tanggal',
        'kategori_setoran',
        'metode',
        'surat_start',
        'ayat_start',
        'surat_end',
        'ayat_end',
        'total_kata',
        'page_start',
        'page_end',
        'total_page',
        'juz_start',
        'juz_end',
        'total_juz',
        'nilai',
        'kesalahan_kelancaran',
        'kesalahan_tajwid',
        'kesalahan_pelafalan',
        'catatan_guru',
        'audio_path',
        'koreksi_pdf_path',
    ];

    protected $casts = [
        'tanggal'    => 'date',
        'total_page' => 'decimal:2',
        'total_juz'  => 'decimal:2',
        'nilai'      => 'integer',
    ];

    // =====================================================
    // RELASI LAMA (tidak berubah)
    // =====================================================

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function presensi(): BelongsTo
    {
        return $this->belongsTo(Presensi::class);
    }

    // =====================================================
    // RELASI BARU (tambahan untuk sistem tahun ajaran)
    // =====================================================

    /**
     * Relasi ke TahunAjaran
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    // =====================================================
    // HELPER METHODS LAMA (tidak berubah)
    // =====================================================

    /**
     * Check apakah record ini punya setoran (bukan hanya presensi)
     */
    public function hasSetoran(): bool
    {
        return !empty($this->kategori_setoran);
    }

    /**
     * Check apakah hanya presensi (tanpa setoran)
     */
    public function isPresensiOnly(): bool
    {
        return empty($this->kategori_setoran);
    }

    /**
     * Get sesi dari presensi
     */
    public function getSesiAttribute(): ?string
    {
        return $this->presensi?->sesi;
    }

    /**
     * Get status presensi
     */
    public function getStatusPresensiAttribute(): ?string
    {
        return $this->presensi?->status;
    }

    /**
     * Get status presensi icon
     */
    public function getStatusPresensiIconAttribute(): string
    {
        $status = $this->status_presensi;
        return match($status) {
            'Hadir' => '✅',
            'Sakit' => '🤒',
            'Izin'  => '📝',
            'Alpha' => '❌',
            default => '❓'
        };
    }

    /**
     * Get keterangan presensi
     */
    public function getKeteranganPresensiAttribute(): ?string
    {
        return $this->presensi?->keterangan;
    }

    /**
     * Get poin presensi
     */
    public function getPoinPresensiAttribute(): int
    {
        $status = $this->status_presensi;
        return match($status) {
            'Hadir' => 10,
            'Sakit' => 5,
            'Izin'  => 3,
            'Alpha' => 0,
            default => 0
        };
    }

    // =====================================================
    // SETORAN CALCULATION METHODS (tidak berubah)
    // =====================================================

    /**
     * Hitung total page dari ayat yang disetorkan
     */
    public function hitungTotalPage(): float
    {
        if (!$this->page_start || !$this->page_end) {
            return 0;
        }

        $totalPage = $this->page_end - $this->page_start + 1;
        return round($totalPage, 2);
    }

    /**
     * Hitung juz dari halaman
     */
    public function hitungJuz(): array
    {
        $pageStart = $this->page_start ?? 0;
        $pageEnd   = $this->page_end   ?? 0;

        if ($pageStart <= 0 || $pageEnd <= 0) {
            return ['start' => null, 'end' => null, 'total' => 0];
        }

        $PAGES_PER_JUZ = 20;

        $juzStart  = (int) ceil($pageStart / $PAGES_PER_JUZ);
        $juzEnd    = (int) ceil($pageEnd   / $PAGES_PER_JUZ);
        $totalPages = $this->total_page ?? $this->hitungTotalPage();
        $totalJuz  = round($totalPages / $PAGES_PER_JUZ, 2);

        return [
            'start' => $juzStart,
            'end'   => $juzEnd,
            'total' => $totalJuz
        ];
    }

    /**
     * Hitung nilai berdasarkan PERSENTASE kesalahan dari total kata
     */
    public function hitungNilai(): int
    {
        if (!$this->hasSetoran()) {
            return 0;
        }

        $totalKata = $this->total_kata ?? 0;

        if ($totalKata <= 0) {
            return 0;
        }

        $bobotKelancaran = 1.0;
        $bobotTajwid     = 1.0;
        $bobotPelafalan  = 1.0;

        $kesalahanKelancaran = ($this->kesalahan_kelancaran ?? 0) * $bobotKelancaran;
        $kesalahanTajwid     = ($this->kesalahan_tajwid     ?? 0) * $bobotTajwid;
        $kesalahanPelafalan  = ($this->kesalahan_pelafalan  ?? 0) * $bobotPelafalan;

       $totalKesalahanBerbobot = $kesalahanKelancaran + $kesalahanTajwid + $kesalahanPelafalan;
        $poinPerKesalahan = 4; // ← bobot pengurangan per kesalahan
        $totalPenalti = $totalKesalahanBerbobot * $poinPerKesalahan;

        if ($totalKata < 100) {
            $nilaiAkhir = 100 - $totalPenalti;
        } else {
            $persentaseKesalahan = ($totalPenalti / $totalKata) * 100;
            $nilaiAkhir = 100 - $persentaseKesalahan;
        }

        return max(0, min(100, round($nilaiAkhir)));
    }

    /**
     * Get total kesalahan
     */
    public function getTotalKesalahanAttribute(): int
    {
        return ($this->kesalahan_kelancaran ?? 0)
             + ($this->kesalahan_tajwid     ?? 0)
             + ($this->kesalahan_pelafalan  ?? 0);
    }

    // =====================================================
    // AUTO-CALCULATION ON SAVE (tidak berubah)
    // =====================================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($setoran) {
            // Auto-isi tahun_ajaran_id dari yang aktif jika belum diisi
            if (empty($setoran->tahun_ajaran_id)) {
                $setoran->tahun_ajaran_id = TahunAjaran::getAktifId();
            }

            if ($setoran->hasSetoran()) {
                $setoran->nilai = $setoran->hitungNilai();

                if ($setoran->page_start && $setoran->page_end) {
                    $setoran->total_page = $setoran->hitungTotalPage();
                }

                $juzData = $setoran->hitungJuz();
                $setoran->juz_start = $juzData['start'];
                $setoran->juz_end   = $juzData['end'];
                $setoran->total_juz = $juzData['total'];
            } else {
                $setoran->nilai      = null;
                $setoran->total_page = null;
                $setoran->juz_start  = null;
                $setoran->juz_end    = null;
                $setoran->total_juz  = null;
            }
        });
    }

    // =====================================================
    // GRADING METHODS (tidak berubah)
    // =====================================================

    public function getGradeAttribute(): string
    {
        if (!$this->nilai) return '-';

        if ($this->nilai >= 90) return 'A';
        if ($this->nilai >= 80) return 'B';
        if ($this->nilai >= 70) return 'C';
        if ($this->nilai >= 60) return 'D';
        return 'E';
    }

    public function getGradeColorAttribute(): string
    {
        return match($this->grade) {
            'A' => 'green',
            'B' => 'blue',
            'C' => 'yellow',
            'D' => 'orange',
            'E' => 'red',
            default => 'gray'
        };
    }

    // =====================================================
    // TARGET ACHIEVEMENT METHODS (tidak berubah)
    // =====================================================

    public function isTargetAchieved(): bool
    {
        $targetJuz = $this->santri->target_juz ?? 0;

        if (!$targetJuz) {
            return false;
        }

        $totalJuz = Setoran::where('santri_id', $this->santri_id)
            ->where('tanggal', '>=', now()->subDays(30))
            ->whereNotNull('kategori_setoran')
            ->sum('total_juz');

        return $totalJuz >= $targetJuz;
    }

    public function getTargetPercentageAttribute(): float
    {
        $targetJuz = $this->santri->target_juz ?? 0;

        if (!$targetJuz || $targetJuz == 0) {
            return 0;
        }

        $totalJuz = Setoran::where('santri_id', $this->santri_id)
            ->where('tanggal', '>=', now()->subDays(30))
            ->whereNotNull('kategori_setoran')
            ->sum('total_juz');

        return round(($totalJuz / $targetJuz) * 100, 2);
    }

    // =====================================================
    // QUERY SCOPES LAMA (tidak berubah)
    // =====================================================

    public function scopePeriode($query, $days = 30)
    {
        return $query->where('tanggal', '>=', now()->subDays($days));
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori_setoran', $kategori);
    }

    public function scopeBySantri($query, $santriId)
    {
        return $query->where('santri_id', $santriId);
    }

    public function scopeByGuru($query, $guruId)
    {
        return $query->where('guru_id', $guruId);
    }

    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    public function scopeWithSetoran($query)
    {
        return $query->whereNotNull('kategori_setoran');
    }

    public function scopePresensiOnly($query)
    {
        return $query->whereNull('kategori_setoran');
    }

    public function scopeBySesi($query, $sesi)
    {
        return $query->whereHas('presensi', function ($q) use ($sesi) {
            $q->where('sesi', $sesi);
        });
    }

    public function scopeByStatusPresensi($query, $status)
    {
        return $query->whereHas('presensi', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    public function scopeHadir($query)
    {
        return $query->whereHas('presensi', function ($q) {
            $q->where('status', 'Hadir');
        });
    }

    public function scopeTidakHadir($query)
    {
        return $query->whereHas('presensi', function ($q) {
            $q->whereIn('status', ['Sakit', 'Izin', 'Alpha']);
        });
    }

    // =====================================================
    // SCOPE BARU (tambahan untuk sistem tahun ajaran)
    // =====================================================

    /**
     * Filter berdasarkan tahun ajaran tertentu (default: aktif)
     */
    public function scopeByTahunAjaran($query, ?int $tahunAjaranId = null)
    {
        $taId = $tahunAjaranId ?? TahunAjaran::getAktifId();
        return $query->where('tahun_ajaran_id', $taId);
    }

    // =====================================================
    // FORMATTED ATTRIBUTES (tidak berubah)
    // =====================================================

    public function getRangeAyatAttribute(): string
    {
        if (!$this->surat_start || !$this->ayat_start) {
            return '-';
        }

        return "{$this->surat_start}:{$this->ayat_start} - {$this->surat_end}:{$this->ayat_end}";
    }

    public function getRangeHalamanAttribute(): string
    {
        if (!$this->page_start || !$this->page_end) {
            return '-';
        }

        if ($this->page_start == $this->page_end) {
            return "Hal. {$this->page_start}";
        }

        return "Hal. {$this->page_start} - {$this->page_end}";
    }

    public function getRangeJuzAttribute(): string
    {
        if (!$this->juz_start || !$this->juz_end) {
            return '-';
        }

        if ($this->juz_start == $this->juz_end) {
            return "Juz {$this->juz_start}";
        }

        return "Juz {$this->juz_start} - {$this->juz_end}";
    }

    // =====================================================
    // FILE CHECKING METHODS (tidak berubah)
    // =====================================================

    public function hasAudio(): bool
    {
        return !empty($this->audio_path);
    }

    public function hasPdf(): bool
    {
        return !empty($this->koreksi_pdf_path);
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (!$this->hasAudio()) {
            return null;
        }

        return route('guru.setorans.audio', $this->id);
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->hasPdf()) {
            return null;
        }

        return asset('storage/' . $this->koreksi_pdf_path);
    }

    // =====================================================
    // DISPLAY HELPERS (tidak berubah)
    // =====================================================

    public function getDescriptionAttribute(): string
    {
        if ($this->isPresensiOnly()) {
            $desc = "{$this->status_presensi_icon} {$this->status_presensi}";
            if ($this->keterangan_presensi) {
                $desc .= " - {$this->keterangan_presensi}";
            }
            return $desc;
        }

        return "{$this->status_presensi_icon} {$this->status_presensi} - {$this->kategori_setoran} ({$this->range_ayat})";
    }

    public function getSummaryAttribute(): array
    {
        return [
            'id'              => $this->id,
            'tanggal'         => $this->tanggal->format('d M Y'),
            'sesi'            => $this->sesi,
            'status_presensi' => $this->status_presensi,
            'poin_presensi'   => $this->poin_presensi,
            'has_setoran'     => $this->hasSetoran(),
            'kategori_setoran'=> $this->kategori_setoran,
            'nilai'           => $this->nilai,
            'grade'           => $this->grade,
            'total_juz'       => $this->total_juz,
        ];
    }
}
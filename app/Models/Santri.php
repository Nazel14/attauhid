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
        'target_metode',
        'target_jilid',
        'status',
    ];

    protected $casts = [
    'target_juz'    => 'decimal:2',
    'target_jilid'  => 'integer',
    'target_metode' => \App\Enums\MetodeTarget::class,
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

    public function kenaikanUsulans()
    {
        return $this->hasMany(KenaikanUsulan::class);
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

    public function getTargetDisplayAttribute(): string
{
    return match ($this->target_metode) {
        \App\Enums\MetodeTarget::Iqro,
        \App\Enums\MetodeTarget::Aisar => 'Jilid ' . ($this->target_jilid ?? '-'),
        default                        => rtrim(rtrim((string) $this->target_juz, '0'), '.') . ' Juz',
    };
}

public function isIqro(): bool
{
    return $this->target_metode === \App\Enums\MetodeTarget::Iqro;
}

public function isAisar(): bool
{
    return $this->target_metode === \App\Enums\MetodeTarget::Aisar;
}

/** Metode berbasis jilid (Iqro / Aisar) */
public function isJilidBased(): bool
{
    return $this->target_metode instanceof \App\Enums\MetodeTarget
        && $this->target_metode->isJilid();
}

/** Prefix label jilid sesuai metode target santri: "Iqro Jilid " / "Aisar Jilid " */
public function jilidLabelPrefix(): string
{
    return $this->isAisar() ? 'Aisar Jilid ' : 'Iqro Jilid ';
}

/** Key config metode jilid: 'iqro' atau 'aisar' */
public function jilidConfigKey(): string
{
    return $this->isAisar() ? 'aisar' : 'iqro';
}

    /**
     * Daftar halaman (unik) yang SUDAH disetor untuk satu jilid Iqro.
     * Deteksi via label "Iqro Jilid X" di surat_start/surat_end.
     */
    public function halamanIqroTersetor(int $jilid, ?string $sejak = null): array
{
    $label = $this->jilidLabelPrefix() . $jilid;

    $rows = $this->setorans()
        ->where('kategori_setoran', 'Hafalan')   // ← tambah
        ->where(function ($q) use ($label) {
            $q->where('surat_start', $label)->orWhere('surat_end', $label);
        })
        ->when($sejak, fn ($q) => $q->where('tanggal', '>=', $sejak))
        ->get(['ayat_start', 'ayat_end']);

        $pages = [];
        foreach ($rows as $r) {
            $start = (int) $r->ayat_start;
            $end   = (int) ($r->ayat_end ?: $r->ayat_start);
            if ($start <= 0) continue;
            if ($end < $start) { [$start, $end] = [$end, $start]; }
            for ($p = $start; $p <= $end; $p++) {
                $pages[$p] = true;
            }
        }

        return array_keys($pages);
    }

    /**
     * Apakah santri sudah menyelesaikan SEMUA halaman jilid target-nya
     * dan siap naik ke jilid berikutnya. Tanpa syarat nilai.
     */
    public function isSiapNaikJilid(): bool
{
    if (! $this->isJilidBased()) return false;

    $cfg      = $this->jilidConfigKey(); // 'iqro' / 'aisar'
    $jilid    = (int) $this->target_jilid;
    $maxJilid = (int) config("$cfg.max_jilid", 6);

    if ($jilid < 1 || $jilid >= $maxJilid) return false;

    $totalHal = config("$cfg.halaman_per_jilid.$jilid");
        if (! $totalHal) return false; // jumlah halaman belum diketahui → jangan tebak

        $tersetor = $this->halamanIqroTersetor($jilid);

        for ($p = 1; $p <= $totalHal; $p++) {
            if (! in_array($p, $tersetor, true)) {
                return false;
            }
        }

        return true;
    }
    /** Jilid tertinggi untuk metode aktif (iqro/aisar) */
public function maxJilid(): int
{
    if (! $this->isJilidBased()) return 0;

    $cfg = $this->jilidConfigKey();
    $max = (int) config("$cfg.max_jilid", 0);

    // fallback: hitung dari daftar halaman_per_jilid
    return $max > 0 ? $max : count((array) config("$cfg.halaman_per_jilid", []));
}

/** Sudah di jilid terakhir DAN jilid itu tuntas → tidak ada jilid berikutnya */
public function isJilidTamat(): bool
{
    if (! $this->isJilidBased()) return false;

    $jilid = (int) $this->target_jilid;
    $max   = $this->maxJilid();
    if ($max < 1 || $jilid < $max) return false;

    $totalHal = (int) config($this->jilidConfigKey() . ".halaman_per_jilid.$jilid", 0);
    if ($totalHal < 1) return false;

    return count($this->halamanIqroTersetor($jilid)) >= $totalHal;
}

    /**
     * Scope: hanya santri aktif (belum lulus / keluar)
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
 * Tanggal masuk paling awal di kelas yang sedang ditempati santri.
 * Dipakai sebagai "floor" progress: lanjut antar-semester (tidak reset),
 * reset saat naik/pindah kelas. Null jika tak ada riwayat (fallback all-time).
 */
public function getTanggalMasukKelasAktif(): ?string
{
    return $this->kelasHistories()
        ->where('kelas_id', $this->kelas_id)
        ->min('tanggal_masuk');
}

/**
 * Daftar halaman Iqro unik tersetor untuk satu jilid, DI KELAS tertentu.
 * Versi kebal-tanggal: filter via kelas_id (bukan floor tanggal_masuk).
 */
public function halamanIqroTersetorKelas(int $jilid, ?int $kelasId = null): array
{
    $label = $this->jilidLabelPrefix() . $jilid;

    $rows = $this->setorans()
        ->where('kategori_setoran', 'Hafalan')   // ← tambah
        ->where(function ($q) use ($label) {
            $q->where('surat_start', $label)->orWhere('surat_end', $label);
        })
        ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
        ->get(['ayat_start', 'ayat_end']);

    $pages = [];
    foreach ($rows as $r) {
        $start = (int) $r->ayat_start;
        $end   = (int) ($r->ayat_end ?: $r->ayat_start);
        if ($start <= 0) continue;
        if ($end < $start) { [$start, $end] = [$end, $start]; }
        for ($p = $start; $p <= $end; $p++) {
            $pages[$p] = true;
        }
    }

    return array_keys($pages);
}

}
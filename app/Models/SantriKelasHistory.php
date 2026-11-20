<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantriKelasHistory extends Model
{
    use HasFactory;

    protected $table = 'santri_kelas_history';

    protected $fillable = [
        'santri_id',
        'kelas_id',
        'tahun_ajaran_id',
        'tanggal_masuk',
        'tanggal_keluar',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_masuk'   => 'date',
        'tanggal_keluar'  => 'date',
    ];

    // =====================================================
    // RELASI
    // =====================================================

    /**
     * withTrashed: riwayat kelas dipakai saat mencetak rapor tahun-tahun
     * sebelumnya. Santri yang sudah diarsipkan tetap harus terbaca namanya
     * di sana, karena rapornya adalah dokumen historis.
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    // =====================================================
    // SCOPES
    // =====================================================

    /**
     * Scope: yang masih aktif (belum keluar)
     */
    public function scopeAktif($query)
    {
        return $query->whereNull('tanggal_keluar');
    }

    /**
     * Scope: berdasarkan tahun ajaran
     */
    public function scopeByTahunAjaran($query, int $tahunAjaranId)
    {
        return $query->where('tahun_ajaran_id', $tahunAjaranId);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    /**
     * Apakah santri masih aktif di kelas ini
     */
    public function getIsAktifAttribute(): bool
    {
        return is_null($this->tanggal_keluar);
    }

    // =====================================================
    // STATUS KENAIKAN (untuk rapot)
    // =====================================================

    /**
     * Status kenaikan kelas untuk rapot. HANYA semester GENAP (2).
     *
     * Penentu "naik" BUKAN tanggal_keluar — tutupSemester() mengisi kolom itu
     * pada SEMUA baris, termasuk santri yang tinggal kelas. Yang dipakai:
     * santri sekarang sudah tidak berada di kelas konteks rapot.
     *
     * Return null = belum ada keputusan → jangan cetak apa pun.
     */
    public static function statusKenaikan(?Santri $santri, $tahunAjaran, ?int $kelasKonteksId = null): ?array
    {
        if (! $santri || ! $tahunAjaran) return null;
        if ((int) ($tahunAjaran->semester ?? 0) !== 2) return null;

        $taId        = (int) $tahunAjaran->id;
        $kelasAsalId = (int) ($kelasKonteksId ?: $santri->kelas_id);
        if (! $kelasAsalId) return null;

        $namaKelasAsal = Kelas::find($kelasAsalId)?->name ?? '-';

        // KEPUTUSAN hanya dikenali dari keterangan yang ditulis saat guru
        // menaikkan/memindahkan/meluluskan. Baris "Otomatis dari tutup semester"
        // TIDAK dihitung — baris itu ditulis untuk SEMUA santri, termasuk yang
        // tinggal kelas, jadi tanggal_keluar tidak bisa dipakai sebagai penanda.
        $filterKeputusan = function ($q) {
            $q->where('keterangan', 'like', 'Naik kelas%')
              ->orWhere('keterangan', 'like', 'Pindah%')
              ->orWhere('keterangan', 'like', 'Lulus%');
        };

        $keputusan = static::with('kelas')
            ->where('santri_id', $santri->id)
            ->where('tahun_ajaran_id', $taId)
            ->where($filterKeputusan)
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->first();

        if ($keputusan) {
            $ket = (string) $keputusan->keterangan;

            // "Naik kelas dari Kelas 7A" → kelas asal diambil dari keterangan,
            // supaya tetap benar walau konteks yang dikirim adalah kelas BARU.
            if (preg_match('/dari\s+(.+)$/i', $ket, $m)) {
                $namaKelasAsal = trim($m[1]);
            }

            $lulus       = stripos($ket, 'lulus')  !== false;
            $pindah      = stripos($ket, 'pindah') !== false;
            $kelasTujuan = $keputusan->kelas?->name ?? $santri->kelas?->name ?? '-';

            return [
                'naik'         => true,
                'lulus'        => $lulus,
                'kelas_asal'   => $namaKelasAsal,
                'kelas_tujuan' => $kelasTujuan,
                'keterangan'   => $ket,
                'judul'        => $lulus ? 'Lulus' : ($pindah ? 'Pindah Kelas' : 'Naik Kelas'),
                'teks'         => $lulus
                    ? 'Dinyatakan LULUS dari kelas ' . $namaKelasAsal
                    : ($pindah
                        ? 'Pindah ke kelas ' . $kelasTujuan
                        : 'Naik ke kelas ' . $kelasTujuan),
            ];
        }

        // Belum ada keputusan untuk santri ini. Baru boleh disebut "tinggal kelas"
        // kalau proses kenaikan di kelas asal MEMANG sudah dijalankan — ditandai
        // adanya teman sekelas dengan keterangan "Naik kelas dari <kelas asal>".
        $prosesSudahJalan = static::where('tahun_ajaran_id', $taId)
            ->where('keterangan', 'like', 'Naik kelas dari ' . $namaKelasAsal . '%')
            ->exists();

        if (! $prosesSudahJalan) return null;

        return [
            'naik'         => false,
            'lulus'        => false,
            'kelas_asal'   => $namaKelasAsal,
            'kelas_tujuan' => null,
            'keterangan'   => null,
            'judul'        => 'Tidak Naik Kelas',
            'teks'         => 'Tetap tinggal di kelas ' . $namaKelasAsal,
        ];
    }
}
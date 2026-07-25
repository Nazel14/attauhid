<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Status "kunci" PDF rapot per santri per tahun ajaran.
 *
 * Baris hanya dibuat saat guru pertama kali membuka akses. Tidak adanya baris
 * = terkunci, jadi default seluruh santri adalah TERKUNCI tanpa perlu seeding.
 */
class RapotAkses extends Model
{
    protected $table = 'rapot_akses';

    protected $fillable = ['santri_id', 'tahun_ajaran_id', 'dibuka_at', 'dibuka_oleh'];

    protected $casts = ['dibuka_at' => 'datetime'];

    /** Berapa lama sebelum/sesudah semester selesai kunci boleh diatur & dipakai. */
    public const BUKA_SEBELUM_BULAN = 1;
    public const TUTUP_SETELAH_HARI = 7;

    /** Tanggal selesai tahun ajaran — nama kolom bisa berbeda di data lama. */
    private static function tanggalSelesai(TahunAjaran $ta)
    {
        return $ta->tanggal_selesai ?? ($ta->tanggal_akhir ?? null);
    }

    /**
     * Jendela waktu: 1 bulan sebelum s/d 7 hari setelah semester selesai.
     * Di luar jendela ini guru tidak bisa membuka kunci, dan santri tidak
     * bisa mengakses PDF meski kuncinya sempat terbuka.
     *
     * @return array{boleh:bool, mulai:?Carbon, batas:?Carbon, pesan:?string, teks:?string}
     */
    public static function jendela(?TahunAjaran $ta): array
    {
        if (! $ta) {
            return ['boleh' => false, 'mulai' => null, 'batas' => null,
                    'pesan' => 'Tahun ajaran aktif belum diatur.', 'teks' => null];
        }

        $selesai = self::tanggalSelesai($ta);

        // Tanpa tanggal selesai, tidak ada acuan jendela — biarkan terbuka
        // agar fitur tidak macet total pada data yang belum lengkap.
        if (! $selesai) {
            return ['boleh' => true, 'mulai' => null, 'batas' => null, 'pesan' => null, 'teks' => null];
        }

        $selesaiC = $selesai instanceof Carbon ? $selesai : Carbon::parse($selesai);
        $mulai    = $selesaiC->copy()->subMonths(self::BUKA_SEBELUM_BULAN)->startOfDay();
        $batas    = $selesaiC->copy()->addDays(self::TUTUP_SETELAH_HARI)->endOfDay();

        $teks = $mulai->translatedFormat('d F Y') . ' – ' . $batas->translatedFormat('d F Y');

        if (now()->lessThan($mulai)) {
            return ['boleh' => false, 'mulai' => $mulai, 'batas' => $batas, 'teks' => $teks,
                    'pesan' => 'Akses PDF rapot baru dapat dibuka mulai ' . $mulai->translatedFormat('d F Y') . '.'];
        }

        if (now()->greaterThan($batas)) {
            return ['boleh' => false, 'mulai' => $mulai, 'batas' => $batas, 'teks' => $teks,
                    'pesan' => 'Masa akses PDF rapot sudah berakhir (batas: ' . $batas->translatedFormat('d F Y') . ').'];
        }

        return ['boleh' => true, 'mulai' => $mulai, 'batas' => $batas, 'teks' => $teks, 'pesan' => null];
    }

    /** Kunci sedang terbuka untuk santri ini di tahun ajaran ini? */
    public static function terbukaUntuk(?int $santriId, ?int $tahunAjaranId): bool
    {
        if (! $santriId || ! $tahunAjaranId) return false;

        return self::where('santri_id', $santriId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereNotNull('dibuka_at')
            ->exists();
    }
}
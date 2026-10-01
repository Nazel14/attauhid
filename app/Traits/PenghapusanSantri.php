<?php

namespace App\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemeriksaan sebelum santri dihapus.
 *
 * Dipakai Admin\SantriController dan Guru\SantriController supaya keduanya
 * menolak dengan alasan yang sama. Sebelumnya yang satu berkata "Gagal
 * menghapus data." tanpa sebab apa pun, dan yang lain menampilkan pesan SQL
 * mentah lengkap dengan nama constraint ke muka guru.
 */
trait PenghapusanSantri
{
    /**
     * Jumlah tagihan per santri: [santri_id => jumlah].
     *
     * deleted_at sengaja TIDAK disaring. Tagihan yang "dihapus" lewat UI
     * hanya di-soft-delete — barisnya masih ada di tabel invoices dan
     * foreign key-nya tetap menahan penghapusan santri. Tanpa ini, orang
     * akan menghapus tagihannya, mencoba lagi, dan bingung kenapa tetap
     * gagal dengan pesan yang sama.
     *
     * Tabelnya dicek dulu: instalasi raport-only tidak punya `invoices`.
     */
    protected function tagihanPerSantri(array $santriIds): array
    {
        if (empty($santriIds) || ! Schema::hasTable('invoices')) {
            return [];
        }

        return DB::table('invoices')
            ->whereIn('student_id', $santriIds)
            ->selectRaw('student_id, COUNT(*) as jumlah')
            ->groupBy('student_id')
            ->pluck('jumlah', 'student_id')
            ->all();
    }

    /** Penolakan untuk satu santri. */
    protected function pesanSantriTerkunci(string $nama, int $jumlah): string
    {
        $tagihan = $jumlah === 1 ? '1 tagihan' : "{$jumlah} tagihan";

        return "<strong>{$nama}</strong> tidak bisa dihapus karena masih terkait {$tagihan} "
            . 'di modul keuangan. Minta bendahara membatalkan tagihannya dulu, '
            . 'baru santrinya bisa dihapus.';
    }

    /** Catatan untuk santri yang dilewati saat penghapusan massal. */
    protected function pesanSantriDilewati(array $nama): string
    {
        $jumlah = count($nama);
        $daftar = implode(', ', array_slice($nama, 0, 5));

        if ($jumlah > 5) {
            $daftar .= ', dan ' . ($jumlah - 5) . ' lainnya';
        }

        return "{$jumlah} santri dilewati karena masih terkait tagihan di modul keuangan: "
            . "<strong>{$daftar}</strong>. Minta bendahara membatalkan tagihannya dulu.";
    }

    /**
     * Kegagalan yang sumbernya bukan tagihan — presensi, setoran, atau relasi
     * lain yang belum terpetakan. Pesan aslinya tidak pernah ditampilkan ke
     * pengguna: isinya nama tabel dan constraint yang hanya berguna di log.
     */
    protected function pesanGagalHapusSantri(\Throwable $e, string $nama): string
    {
        // 1451 = baris induk masih dirujuk tabel lain.
        $foreignKey = $e instanceof QueryException && ($e->errorInfo[1] ?? null) === 1451;

        if ($foreignKey) {
            return "<strong>{$nama}</strong> tidak bisa dihapus karena datanya masih dipakai "
                . 'di bagian lain sistem. Hapus dulu data yang terkait dengannya.';
        }

        return "Gagal menghapus {$nama}. Coba lagi beberapa saat lagi; "
            . 'bila terus terjadi, beri tahu admin.';
    }
}
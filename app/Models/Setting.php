<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Penyimpanan konfigurasi yang boleh diubah dari dalam aplikasi.
 *
 * Kenapa tidak lewat .env seperti config('app.name')?
 * Karena di hosting bersama .env hanya bisa disentuh lewat File Manager,
 * dan setiap perubahannya baru berlaku setelah cache config dibersihkan
 * secara manual. Pemilik pesantren tidak akan pernah melakukan itu. Apa
 * pun yang perlu diubah oleh pengguna harus tinggal di database.
 *
 * Nilai disimpan sebagai LONGTEXT supaya gambar tanda tangan bisa ikut
 * masuk dalam bentuk data-URI base64. Itu disengaja: file yang diunggah
 * ke folder tidak ikut dalam backup database, jadi saat restore tanda
 * tangannya hilang tanpa ada yang sadar. Di dalam tabel, ia ikut ke mana
 * pun datanya dibawa.
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    // Tabel ini tidak punya created_at; updated_at diisi manual di set().
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'updated_at', 'updated_by'];

    /** Cache 24 jam — nilainya jarang berubah tapi dibaca tiap cetak kwitansi. */
    private const TTL = 86400;

    public static function get(string $key, $default = null)
    {
        $nilai = Cache::remember(
            "setting:{$key}",
            self::TTL,
            fn () => static::query()->where('key', $key)->value('value')
        );

        // Nilai kosong diperlakukan sebagai belum diisi, supaya pemanggil
        // cukup menulis Setting::get('ttd_nama', 'Bendahara').
        return ($nilai === null || $nilai === '') ? $default : $nilai;
    }

    public static function set(string $key, $value, ?int $userId = null): void
    {
        static::query()->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'updated_by' => $userId],
        );

        // WAJIB. Tanpa baris ini perubahan baru terlihat 24 jam kemudian,
        // dan di hosting cache file hanya bisa dihapus lewat File Manager.
        Cache::forget("setting:{$key}");
    }
}
<?php

namespace App\Support;

class Terbilang
{
    private const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function of(int $angka): string
    {
        $angka = abs($angka);

        if ($angka < 12) {
            return self::SATUAN[$angka] ?: 'nol';
        }

        if ($angka < 20) {
            return self::of($angka - 10) . ' belas';
        }

        if ($angka < 100) {
            return self::of(intdiv($angka, 10)) . ' puluh ' . self::sisa($angka % 10);
        }

        if ($angka < 200) {
            return 'seratus ' . self::sisa($angka - 100);
        }

        if ($angka < 1000) {
            return self::of(intdiv($angka, 100)) . ' ratus ' . self::sisa($angka % 100);
        }

        if ($angka < 2000) {
            return 'seribu ' . self::sisa($angka - 1000);
        }

        if ($angka < 1_000_000) {
            return self::of(intdiv($angka, 1000)) . ' ribu ' . self::sisa($angka % 1000);
        }

        if ($angka < 1_000_000_000) {
            return self::of(intdiv($angka, 1_000_000)) . ' juta ' . self::sisa($angka % 1_000_000);
        }

        return self::of(intdiv($angka, 1_000_000_000)) . ' miliar ' . self::sisa($angka % 1_000_000_000);
    }

    private static function sisa(int $angka): string
    {
        return $angka > 0 ? trim(self::of($angka)) : '';
    }
}
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jumlah Jilid Maksimal
    |--------------------------------------------------------------------------
    | Batas jilid tertinggi. Santri di jilid ini tidak bisa "naik" lagi
    | karena sudah jilid terakhir.
    */
    'max_jilid' => 6,

    /*
    |--------------------------------------------------------------------------
    | Jumlah Halaman per Jilid
    |--------------------------------------------------------------------------
    | Dipakai untuk mendeteksi apakah santri sudah menyelesaikan SEMUA
    | halaman di jilidnya (syarat "siap naik jilid").
    |
    | PENTING: jilid yang belum diisi di sini akan membuat deteksi
    | "siap naik" mengembalikan false (sengaja — supaya tidak menebak).
    | Lengkapi angka jilid 2–6 sesuai buku Iqro yang dipakai pesantren.
    */
    'halaman_per_jilid' => [
        1 => 31,
        2 => 30,
        3 => 30,
        4 => 30,
        5 => 30,
        6 => 31,
    ],

        'halaman_mulai' => [
        1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1,
    ],

];
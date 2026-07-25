<?php

return [

    /*
    |----------------------------------------------------------------------
    | Modul Aktif
    |----------------------------------------------------------------------
    | Menentukan modul mana yang di-load. Route, menu, dan migrasi milik
    | modul yang non-aktif tidak akan pernah terdaftar.
    |
    | Diatur lewat .env agar satu codebase bisa dijual dalam beberapa paket:
    |   - Raport saja       : MODULE_KEUANGAN=false
    |   - Raport + Keuangan : MODULE_KEUANGAN=true
    */

    'raport'   => env('MODULE_RAPORT', true),
    'keuangan' => env('MODULE_KEUANGAN', false),

];
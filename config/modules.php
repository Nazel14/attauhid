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
    |
    | Nilai di bawah hanya DEFAULT bila baris terkait tidak ada di .env.
    | Catatan: setelah `php artisan config:cache`, perubahan .env tidak
    | berpengaruh sampai `php artisan config:clear` dijalankan.
    */

    'raport'   => env('MODULE_RAPORT', true),
    'keuangan' => env('MODULE_KEUANGAN', false),

    /*
    |----------------------------------------------------------------------
    | Halaman Landing
    |----------------------------------------------------------------------
    | Halaman penawaran produk di route '/'. Hanya untuk instalasi
    | demo/marketing. Di instalasi milik klien harus false, supaya wali
    | murid dan guru yang membuka domain langsung diarahkan ke login,
    | bukan disambut halaman jualan.
    |
    | Default false: bila .env instalasi klien lupa diisi, yang terjadi
    | adalah landing tidak muncul — arah kesalahan yang lebih aman.
    */

    'landing' => env('LANDING_PAGE', false),

    /*
    |----------------------------------------------------------------------
    | Role Modul Keuangan
    |----------------------------------------------------------------------
    | Himpunan role yang boleh masuk dan boleh dikelola dari panel keuangan.
    | Dipakai di tiga tempat:
    |   1. routes/keuangan.php  -> gerbang 'role:' di level grup
    |   2. UserController@index -> filter daftar user
    |   3. UserController store/update/destroy -> Rule::in()
    |
    | Guru & santri sengaja tidak ada di sini: keduanya tetap dikelola
    | dari AdminController raport, bukan dari panel keuangan.
    |
    | 'ketua-yayasan' WAJIB ada di sini — tanpa itu pemegang role tersebut
    | ditolak gerbang 'role:' dan tidak bisa membuka panel keuangan sama
    | sekali, padahal dialah pemberi persetujuan akhir pengeluaran.
    */

    'keuangan_roles' => [
        'super-admin',
        'bendahara',
        'kepala-sekolah',
        'ketua-yayasan',
        'pihak-yayasan',
        'hrd',
    ],

    /*
    |----------------------------------------------------------------------
    | Role Terkunci
    |----------------------------------------------------------------------
    | Nama role di bawah ini dipakai sebagai string literal oleh middleware
    | dan role-gating raport. Mengubah namanya = memutus akses diam-diam,
    | menghapusnya = mengunci pengguna keluar.
    |
    | Efek di UI Manajemen Role:
    |   - semua role di sini : nama tidak bisa diubah, tidak bisa dihapus
    |   - khusus super-admin : permission-nya juga dikunci (lihat is_super)
    |
    | Sesuaikan dengan hasil: SELECT name FROM roles ORDER BY name;
    */

    'roles_terkunci' => [
        'super-admin',
        'admin',
        'guru',
        'santri',
        'bendahara',
        'kepala-sekolah',
        'ketua-yayasan',
        'pihak-yayasan',
        'hrd',
    ],

    /*
    |----------------------------------------------------------------------
    | Pemetaan Jabatan ke Hak Akses
    |----------------------------------------------------------------------
    | Saat menambah pegawai, hak akses akunnya diisi otomatis dari jabatan
    | supaya bendahara tidak perlu memilih dua hal yang sebenarnya sudah
    | saling menentukan. Isian ini tetap bisa diubah manual bila ada
    | pengecualian — misalnya guru yang merangkap memegang kas.
    |
    | Key   : nama_jabatan di tabel positions, HURUF KECIL semua
    | Value : nama role di tabel roles
    |
    | Jabatan yang tidak terdaftar di sini tidak mendapat isian otomatis;
    | hak aksesnya harus dipilih manual. Itu memang disengaja: lebih baik
    | bendahara memilih sadar daripada sistem menebak akses yang salah.
    |
    | PENTING: setiap value di bawah harus benar-benar ada di tabel roles.
    | Menunjuk role yang tidak ada membuat assignRole() melempar
    | RoleDoesNotExist saat pegawai dengan jabatan itu disimpan.
    |
    | Cocokkan dengan: SELECT nama_jabatan FROM positions ORDER BY nama_jabatan;
    */

    'jabatan_ke_role' => [
        'guru'           => 'guru',
        'bendahara'      => 'bendahara',
        'kepala sekolah' => 'kepala-sekolah',
        'ketua yayasan'  => 'ketua-yayasan',
        'hrd'            => 'hrd',
        'yayasan'        => 'pihak-yayasan',
        'pihak yayasan'  => 'pihak-yayasan',
    ],
];
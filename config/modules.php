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
    | Role Eksternal (Portal Wali/Santri)
    |----------------------------------------------------------------------
    | Pengguna di luar pesantren. Berbeda dari role internal dalam dua hal:
    |
    |   1. Tidak memilih panel saat login. Bagi wali, hafalan dan tagihan
    |      anaknya adalah satu urusan yang sama — pemisahan raport/keuangan
    |      itu cara sistem membagi pekerjaan staf, bukan cara orang tua
    |      memandang anaknya.
    |   2. Portalnya berdiri di luar gerbang modul (routes/portal.php),
    |      supaya wali tetap bisa masuk meski salah satu modul dimatikan.
    |      Yang menyesuaikan adalah isi menunya, bukan seluruh portalnya.
    |
    | Setiap role di sini harus juga ada di 'roles_terkunci' di bawah,
    | karena namanya dipakai sebagai string literal oleh gerbang 'role:'
    | di routes/portal.php.
    */

    'role_eksternal' => [
        'santri',
    ],

    /*
    |----------------------------------------------------------------------
    | Nomor WhatsApp Admin Keuangan
    |----------------------------------------------------------------------
    | Tujuan tombol "Konfirmasi Pembayaran" di portal wali. Format 62,
    | tanpa tanda plus dan tanpa spasi — itu yang diterima wa.me.
    |
    | Dikosongkan bila pesantren tidak melayani konfirmasi lewat WhatsApp;
    | tombolnya otomatis tidak dirender, bukan mengarah ke nomor kosong.
    */

    'wa_admin' => env('WA_ADMIN', null),

    /*
    |----------------------------------------------------------------------
    | Role Modul Keuangan
    |----------------------------------------------------------------------
    | Himpunan role yang boleh masuk dan boleh dikelola dari panel keuangan.
    | Dipakai di tiga tempat:
    |   1. routes/keuangan.php  -> gerbang 'role:' di level grup
    |   2. UserController@index -> filter daftar user
    |   3. UserController store/update/destroy -> Rule::in()
        4. User::availableModules()  -> menentukan panel yang boleh dibuka
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
        'admin',
        'bendahara',
        'kepala-sekolah',
        'ketua-yayasan',
        'pihak-yayasan',
        'hrd',
    ],

        /*
    |----------------------------------------------------------------------
    | Role Modul Raport
    |----------------------------------------------------------------------
    | Pasangan dari 'keuangan_roles' di bawah. Keduanya dibaca oleh
    | User::availableModules() untuk menentukan panel mana yang boleh
    | dibuka seseorang.
    |
    | Santri ada di sini meski portalnya berdiri di luar gerbang modul —
    | tanpa ini ia tidak punya modul sama sekali setelah login.
    */

    'raport_roles' => [
        'super-admin',
        'admin',
        'guru',
        'santri',
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
    

        /*
    |----------------------------------------------------------------------
    | Mode Demo
    |----------------------------------------------------------------------
    | Menyalakan tombol "Masuk sebagai…" di halaman login, supaya calon
    | pembeli bisa mencoba tiap sudut pandang tanpa dikirimi akun.
    |
    | WAJIB false di instalasi klien. Bila true, siapa pun bisa masuk
    | sebagai akun demo tanpa password — aman HANYA karena akun-akun ini
    | berisi data contoh, bukan data pesantren sungguhan.
    */

    'demo' => env('DEMO_MODE', false),

    'demo_akun' => [
        // Urutan di sini menentukan urutan tombol di halaman login.
        // Modulnya sengaja TIDAK ditulis di sini — dibaca dari
        // availableModules() tiap akun, supaya tidak ada dua sumber
        // kebenaran yang suatu hari berselisih.
        ['peran' => 'admin',          'label' => 'Admin',          'username' => 'demo.admin'],
        ['peran' => 'kepala-sekolah', 'label' => 'Kepala Sekolah', 'username' => 'demo.kepsek'],
        ['peran' => 'guru',           'label' => 'Guru',           'username' => 'demo.guru1'],
        ['peran' => 'santri',         'label' => 'Santri',         'username' => 'demo.santri1'],
        ['peran' => 'bendahara',      'label' => 'Bendahara',      'username' => 'demo.bendahara'],
        
        ['peran' => 'hrd',            'label' => 'HRD',            'username' => 'demo.hrd'],
        ['peran' => 'ketua-yayasan',  'label' => 'Ketua Yayasan',  'username' => 'demo.ketuayayasan'],
        ['peran' => 'pihak-yayasan',  'label' => 'Pihak Yayasan',  'username' => 'demo.yayasan'],
    ],
];
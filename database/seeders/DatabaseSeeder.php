<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan wajib: AdminUserSeeder membuat role admin/super-admin/guru/
     * santri, RolePermissionSeeder melebarkan permission-nya, lalu dua
     * seeder keuangan menambah permission modul keuangan dan menyinkronkan
     * ulang super-admin agar ikut mendapatkannya.
     *
     * SuperAdminSeeder, SuperAdminRoleSeeder, dan BackfillUserRolesSeeder
     * sengaja TIDAK dipanggil — ketiganya seeder perbaikan satu kali dari
     * instalasi lama, bukan bagian dari setup rutin. Jalankan manual bila
     * memang diperlukan.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RolePermissionSeeder::class,
            KeuanganPermissionSeeder::class,
            KeuanganRolePermissionSeeder::class,
        ]);

        // Data contoh — JANGAN dijalankan di instalasi klien.
        if (app()->environment('local')) {
            $this->call([
                DemoUserSeeder::class,
                DemoRaportSeeder::class,
                DemoKeuanganSeeder::class,
            ]);
        }
    }
}
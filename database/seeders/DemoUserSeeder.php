<?php
// database/seeders/DemoUserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Akun contoh untuk instalasi DEMO — satu akun per role.
 *
 * Hanya role yang tidak terikat baris profil yang dibuat di sini.
 * Role 'guru' dan 'santri' ditangani DemoRaportSeeder, karena keduanya
 * wajib punya baris di tabel gurus/santris; tanpa itu dashboard-nya
 * rusak tanpa pesan error.
 *
 * Jalankan SETELAH KeuanganPermissionSeeder, supaya seluruh role
 * keuangan sudah ada saat akun di-assign.
 *
 * JANGAN dijalankan di instalasi milik klien — password-nya sengaja
 * mudah ditebak supaya bisa dipamerkan ke calon pembeli.
 */
class DemoUserSeeder extends Seeder
{
    private const PASSWORD = 'demo12345';

    /** username => role. Harus selaras dengan config('modules.keuangan_roles'). */
    private const AKUN = [
        'demo.admin'        => 'admin',
        'demo.bendahara'    => 'bendahara',
        'demo.kepsek'       => 'kepala-sekolah',
        'demo.ketuayayasan' => 'ketua-yayasan',
        'demo.yayasan'      => 'pihak-yayasan',
        'demo.hrd'          => 'hrd',
    ];

    public function run(): void
    {
        $dibuat   = 0;
        $dilewati = 0;

        foreach (self::AKUN as $username => $role) {
            // Role dicek dulu: assignRole ke role yang belum ada melempar
            // RoleDoesNotExist dan menggagalkan seluruh sisa seeder.
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                $this->command->warn("Role '{$role}' belum ada — akun {$username} dilewati.");
                $dilewati++;
                continue;
            }

            // firstOrCreate: aman dijalankan berulang tanpa duplikat.
            // Lewat Eloquent karena cast plain_password harus dilalui.
            $user = User::firstOrCreate(
                ['name' => $username],
                [
                    'password'       => Hash::make(self::PASSWORD),
                    'plain_password' => self::PASSWORD,
                    // Kolom users.role dan relasi Spatie WAJIB sinkron —
                    // kolom dibaca RoleMiddleware, relasi dibaca permission.
                    'role'           => $role,
                ]
            );

            $user->syncRoles([$role]);
            $dibuat++;

            $this->command->info(str_pad($username, 20) . $role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info("Akun demo siap : {$dibuat} dibuat, {$dilewati} dilewati");
        $this->command->info('Password semua akun: ' . self::PASSWORD);
        $this->command->info('=================================================');
    }
}
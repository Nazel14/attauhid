<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /* ======================================================
     * UBAH TIGA BARIS INI SESUAI KEINGINAN ANDA
     * ====================================================== */
    private const USERNAME = 'superadmin';
    private const PASSWORD = 'ABC123superadmin';
    private const ROLE     = 'super-admin';

    public function run(): void
    {
        // Permission modul raport. Permission keuangan (master.*, rkas.*,
        // pembayaran.*, dst) dibuat oleh seeder keuangan yang terpisah.
        $semuaPermission = [
            'lihat-dashboard-admin',
            'kelola-admin',
            'kelola-guru',
            'kelola-kelas',
            'kelola-santri',
            'kelola-presensi-sesi',
            'lihat-monitoring',
            'lihat-log-activity',
            'hapus-log-activity',
            'cleanup-log-activity',
        ];

        foreach ($semuaPermission as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Role admin: semua kecuali 'kelola-admin', yang khusus super-admin.
        $permissionAdmin = array_values(array_diff($semuaPermission, ['kelola-admin']));

        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions($permissionAdmin);

        // Role super-admin dibuat eksplisit — dipakai middleware
        // 'role:super-admin' di route Pengaturan Sistem panel Keuangan,
        // dan terdaftar di config('modules.roles_terkunci').
        $roleSuper = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleSuper->syncPermissions(Permission::where('guard_name', 'web')->get());

        Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'santri', 'guard_name' => 'web']);

        // Dibuat lewat Eloquent, bukan SQL langsung, supaya cast
        // plain_password tersimpan dalam bentuk terenkripsi yang benar.
        $user = User::create([
            'name'           => self::USERNAME,
            'password'       => Hash::make(self::PASSWORD),
            'plain_password' => self::PASSWORD,
            'role'           => self::ROLE,
        ]);

        $user->syncRoles([self::ROLE]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('=========================================');
        $this->command->info('Akun super admin berhasil dibuat');
        $this->command->info('=========================================');
        $this->command->info('Username : ' . self::USERNAME);
        $this->command->info('Password : ' . self::PASSWORD);
        $this->command->info('Role     : ' . self::ROLE);
        $this->command->info('=========================================');
    }
}
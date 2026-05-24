<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat SEMUA permissions
        $allPermissions = [
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

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2. Role 'admin' dapat 9 permission (TANPA kelola-admin)
        $adminPermissions = [
            'lihat-dashboard-admin',
            'kelola-guru',
            'kelola-kelas',
            'kelola-santri',
            'kelola-presensi-sesi',
            'lihat-monitoring',
            'lihat-log-activity',
            'hapus-log-activity',
            'cleanup-log-activity',
        ];

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($adminPermissions);

        // 3. Buat role guru & santri
        Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'santri', 'guard_name' => 'web']);

        // 4. Buat user admin pertama (Super Admin)
        $admin = User::create([
            'name' => 'admin',
            'password' => Hash::make('password123'),
            'plain_password' => 'password123',
            'role' => 'admin',
        ]);

        // 5. Assign role 'admin' (otomatis dapat 9 permission via role)
        $admin->assignRole('admin');

        // 6. ⭐ KUNCI SUPER ADMIN: berikan 'kelola-admin' DIRECT
        $admin->givePermissionTo('kelola-admin');

        // 7. Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('=========================================');
        $this->command->info('✅ Super Admin berhasil dibuat!');
        $this->command->info('=========================================');
        $this->command->info('Username : admin');
        $this->command->info('Password : password123');
        $this->command->info('Status   : Super Admin (1/3)');
        $this->command->info('=========================================');
    }
}
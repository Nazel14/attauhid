<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================
        // BUAT PERMISSIONS
        // =====================================================
        $permissions = [
            // Manajemen Admin
            'kelola-admin',
            'lihat-admin',
            'tambah-admin',
            'edit-admin',
            'hapus-admin',
            
            // Manajemen Guru
            'kelola-guru',
            'lihat-guru',
            'tambah-guru',
            'edit-guru',
            'hapus-guru',
            'import-guru',
            'export-guru',
            
            // Manajemen Kelas
            'kelola-kelas',
            'lihat-kelas',
            'tambah-kelas',
            'edit-kelas',
            'hapus-kelas',
            
            // Manajemen Santri
            'kelola-santri',
            'lihat-santri',
            'tambah-santri',
            'edit-santri',
            'hapus-santri',
            'import-santri',
            'export-santri',
            
            // Monitoring
            'lihat-monitoring',
            'kelola-setoran',
            'kelola-presensi',
            
            // Presensi Sesi
            'kelola-presensi-sesi',
            
            // Log Activity
            'lihat-log-activity',
            'hapus-log-activity',
            'cleanup-log-activity',
            
            // Dashboard
            'lihat-dashboard-admin',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // =====================================================
        // BUAT ROLES & ASSIGN PERMISSIONS
        // =====================================================
        
        // 1. SUPER ADMIN - Full Access (Raja Admin)
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all()); // Semua permission
        
        // 2. ADMIN - Full access kecuali kelola admin & hapus log
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'lihat-admin', // Bisa lihat daftar admin tapi tidak bisa kelola
            
            // Guru - Full Access
            'kelola-guru',
            'lihat-guru',
            'tambah-guru',
            'edit-guru',
            'hapus-guru',
            'import-guru',
            'export-guru',
            
            // Kelas - Full Access
            'kelola-kelas',
            'lihat-kelas',
            'tambah-kelas',
            'edit-kelas',
            'hapus-kelas',
            
            // Santri - Full Access
            'kelola-santri',
            'lihat-santri',
            'tambah-santri',
            'edit-santri',
            'hapus-santri',
            'import-santri',
            'export-santri',
            
            // Monitoring & Presensi - Full Access
            'lihat-monitoring',
            'kelola-setoran',
            'kelola-presensi',
            'kelola-presensi-sesi',
            
            // Log Activity - Hanya Lihat (tidak bisa hapus/cleanup)
            'lihat-log-activity',
            
            // Dashboard
            'lihat-dashboard-admin',
        ]);

        // 4. GURU - Role untuk guru (tetap pakai role lama)
        $guru = Role::create(['name' => 'guru', 'guard_name' => 'web']);
        $guru->givePermissionTo([
            'kelola-setoran',
            'kelola-presensi',
            'lihat-santri',
        ]);

        // 5. SANTRI - Role untuk santri
        $santri = Role::create(['name' => 'santri', 'guard_name' => 'web']);
        // Santri tidak perlu permission khusus, hanya bisa lihat data sendiri

        // =====================================================
        // ASSIGN ROLE KE USER EXISTING
        // =====================================================
        
        // Cari user admin pertama dan jadikan Super Admin
        $firstAdmin = User::where('role', 'admin')->first();
        if ($firstAdmin) {
            $firstAdmin->assignRole('super-admin');
            echo "✅ User '{$firstAdmin->name}' dijadikan Super Admin\n";
        }

        // Jika ada admin lainnya, jadikan role 'admin' biasa
        User::where('role', 'admin')
            ->where('id', '!=', $firstAdmin?->id)
            ->each(function($user) {
                $user->assignRole('admin');
                echo "✅ User '{$user->name}' dijadikan Admin\n";
            });

        // Assign role ke semua guru
        User::where('role', 'guru')->each(function($user) {
            $user->assignRole('guru');
        });

        // Assign role ke semua santri
        User::where('role', 'santri')->each(function($user) {
            $user->assignRole('santri');
        });

        echo "\n🎉 Roles & Permissions berhasil dibuat!\n";
        echo "📊 Struktur: Super Admin (1) + Admin (staff) + Guru + Santri\n";
    }
}
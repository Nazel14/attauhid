<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ======================================
        // 1. BUAT ROLES JIKA BELUM ADA
        // ======================================
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web']
        );
        
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );

        // ======================================
        // 2. BUAT SEMUA PERMISSIONS
        // ======================================
        $permissions = [
            // Dashboard
            'lihat-dashboard-admin',
            
            // Super Admin - Kelola Admin
            'kelola-admin',
            
            // Manajemen Data Master
            'kelola-guru',
            'kelola-kelas',
            'kelola-santri',
            'kelola-presensi-sesi',
            
            // Monitoring & Laporan
            'lihat-monitoring',
            'lihat-log-activity',
            
            // Log Activity Actions (Super Admin Only)
            'hapus-log-activity',
            'cleanup-log-activity',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // ======================================
        // 3. ASSIGN PERMISSIONS KE SUPER ADMIN
        // ======================================
        // Super Admin mendapat SEMUA permission
        $superAdminRole->syncPermissions($permissions);

        // ======================================
        // 4. ASSIGN PERMISSIONS KE ADMIN BIASA
        // ======================================
        // Admin biasa mendapat permission kecuali yang eksklusif super admin
        $adminPermissions = [
            'lihat-dashboard-admin',
            'kelola-guru',
            'kelola-kelas',
            'kelola-santri',
            'kelola-presensi-sesi',
            'lihat-monitoring',
            'lihat-log-activity',
        ];
        
        $adminRole->syncPermissions($adminPermissions);

        // ======================================
        // 5. UPGRADE ADMIN YANG ADA MENJADI SUPER ADMIN
        // ======================================
        // Ambil admin pertama berdasarkan role atau ID
        $existingAdmin = User::where('role', 'admin')
            ->orderBy('id', 'asc')
            ->first();

        if ($existingAdmin) {
            // Hapus role lama jika ada
            $existingAdmin->roles()->detach();
            
            // Assign role super-admin
            $existingAdmin->assignRole('super-admin');
            
            $this->command->info("✅ User '{$existingAdmin->name}' (ID: {$existingAdmin->id}) berhasil diupgrade menjadi Super Admin!");
        } else {
            $this->command->warn("⚠️ User dengan role 'admin' tidak ditemukan.");
            
            // Coba upgrade user dengan ID 1
            $userById = User::find(1);
            if ($userById) {
                // Update role menjadi admin jika belum
                if ($userById->role !== 'admin') {
                    $userById->update(['role' => 'admin']);
                }
                
                $userById->roles()->detach();
                $userById->assignRole('super-admin');
                $this->command->info("✅ User ID 1 '{$userById->name}' berhasil diupgrade menjadi Super Admin!");
            } else {
                $this->command->error("❌ Tidak ada user yang bisa diupgrade. Silakan buat admin manual.");
            }
        }

        // ======================================
        // 6. INFORMASI TAMBAHAN
        // ======================================
        $this->command->info("\n📋 SUMMARY:");
        $this->command->info("- Role 'super-admin' dibuat dengan " . count($permissions) . " permissions");
        $this->command->info("- Role 'admin' dibuat dengan " . count($adminPermissions) . " permissions");
        $this->command->info("\n🔐 Perbedaan Super Admin vs Admin:");
        $this->command->info("  Super Admin dapat:");
        $this->command->info("    ✓ Kelola admin lain");
        $this->command->info("    ✓ Hapus log activity");
        $this->command->info("    ✓ Cleanup log activity");
        $this->command->info("  Admin biasa dapat:");
        $this->command->info("    ✓ Kelola guru, kelas, santri, presensi");
        $this->command->info("    ✓ Lihat monitoring dan log (tanpa hapus)");
    }
}
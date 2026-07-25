<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class SuperAdminRoleSeeder extends Seeder
{
    /**
     * Membuat role 'super-admin' dan memindahkan permission 'kelola-admin'
     * dari assignment langsung (model_has_permissions) ke role.
     *
     * can('kelola-admin') tetap true untuk user yang sama, jadi seluruh
     * gating yang ada (4 call site) tidak berubah perilakunya.
     * Idempoten — aman dijalankan berulang.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $kelolaAdmin = Permission::where('name', 'kelola-admin')
            ->where('guard_name', 'web')
            ->first();

        if (!$kelolaAdmin) {
            $this->command->error("Permission 'kelola-admin' tidak ditemukan. Dibatalkan.");
            return;
        }

        // Siapa yang selama ini jadi Super Admin: pemegang kelola-admin langsung.
        $superAdmins = User::whereHas('permissions', fn ($q) => $q->where('name', 'kelola-admin'))->get();

        if ($superAdmins->isEmpty()) {
            $this->command->warn('Tidak ada user dengan permission kelola-admin langsung. Dibatalkan.');
            return;
        }

        // Role super-admin memuat SEMUA permission yang ada saat ini (10 buah).
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->get());

        foreach ($superAdmins as $user) {
            $user->assignRole($role);           // role 'admin' miliknya TIDAK dicabut
            $user->revokePermissionTo($kelolaAdmin); // sekarang datang dari role
            $this->command->info("Super Admin: {$user->name} (id {$user->id})");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
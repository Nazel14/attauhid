<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class BackfillUserRolesSeeder extends Seeder
{
    /**
     * Memberi Spatie role ke user lama yang dibuat sebelum hook
     * booted() di User model ada. Idempoten.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $users = User::doesntHave('roles')->whereNotNull('role')->get();

        if ($users->isEmpty()) {
            $this->command->info('Semua user sudah punya role. Tidak ada yang diproses.');
            return;
        }

        $roles = Role::where('guard_name', 'web')->get()->keyBy('name');
        $skipped = [];

        foreach ($users as $user) {
            if (!$roles->has($user->role)) {
                $skipped[] = "{$user->name} (role '{$user->role}' tidak ada)";
                continue;
            }

            $user->assignRole($roles[$user->role]);
            $this->command->info("{$user->id} | {$user->name} → {$user->role}");
        }

        if ($skipped) {
            $this->command->warn('Dilewati: ' . implode(', ', $skipped));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
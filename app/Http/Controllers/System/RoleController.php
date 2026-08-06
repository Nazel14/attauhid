<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const SUPER = 'super-admin';

    private function rolesTerkunci(): array
    {
        return config('modules.roles_terkunci', [self::SUPER]);
    }

    /**
     * Tempel flag ke model supaya frontend tidak perlu hardcode nama role.
     */
    private function tandai(Role $role): Role
    {
        $role->is_locked = in_array($role->name, $this->rolesTerkunci(), true);
        $role->is_super  = $role->name === self::SUPER;

        return $role;
    }

    public function index(Request $request)
    {
        $query = Role::withCount('users');

        // 'ilike' hanya ada di PostgreSQL. MySQL pakai 'like'.
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $roles = $query->orderBy('name')->paginate(10)->withQueryString();
        $roles->getCollection()->transform(fn ($role) => $this->tandai($role));

        return Inertia::render('Sistem/Role/Index', [
            'roles'   => $roles,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Sistem/Role/Form', [
            'role'               => null,
            'rolePermissions'    => [],
            'groupedPermissions' => $this->getGroupedPermissions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name',
            'permissions'   => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name'       => $validated['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($validated['permissions'] ?? []);
        });

        return redirect()->route('keuangan.pengaturan.role.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return Inertia::render('Sistem/Role/Form', [
            'role'               => $this->tandai($role),
            'rolePermissions'    => $rolePermissions,
            'groupedPermissions' => $this->getGroupedPermissions(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        // super-admin: dikunci total. Nama maupun permission tidak bisa diubah.
        // Ini yang mencegah Super Admin mencabut aksesnya sendiri lalu
        // terkunci keluar dari panel raport dan hanya bisa pulih via tinker.
        if ($role->name === self::SUPER) {
            return back()->withErrors([
                'name' => 'Role super-admin adalah bawaan sistem dan tidak dapat dimodifikasi.',
            ]);
        }

        $terkunci = in_array($role->name, $this->rolesTerkunci(), true);

        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions'   => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        // Role terkunci lain (admin/guru/santri): permission boleh disesuaikan,
        // nama tidak — string-nya dipakai middleware & role-gating raport.
        if ($terkunci && $validated['name'] !== $role->name) {
            return back()->withErrors([
                'name' => "Nama role '{$role->name}' dipakai middleware dan tidak boleh diubah.",
            ]);
        }

        DB::transaction(function () use ($role, $validated, $terkunci) {
            if (! $terkunci) {
                $role->update(['name' => $validated['name']]);
            }

            // syncPermissions dengan array kosong = cabut semua.
            $role->syncPermissions($validated['permissions'] ?? []);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('keuangan.pengaturan.role.edit', $role->id)
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, $this->rolesTerkunci(), true)) {
            return back()->withErrors([
                'delete_error' => "Role '{$role->name}' adalah bawaan sistem dan tidak dapat dihapus.",
            ]);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors([
                'delete_error' => 'Gagal menghapus! Role ini masih digunakan oleh beberapa pengguna.',
            ]);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role berhasil dihapus.');
    }

    /**
     * Kelompokkan permission berdasarkan prefix untuk tampilan kartu di frontend.
     * Permission raport (setoran.*, presensi.*, dll) jatuh ke 'Lainnya' —
     * sengaja tetap ditampilkan supaya sync tidak diam-diam mencabutnya.
     */
    private function getGroupedPermissions()
    {
        $permissions = Permission::orderBy('name')->get();

        $grouped = [
            'Pemasukan (Tagihan & Pembayaran)' => [],
            'Pengeluaran & RKAS'               => [],
            'Penggajian & SDM'                 => [],
            'Laporan Keuangan'                 => [],
            'Master Data'                      => [],
            'Pengaturan Sistem'                => [],
        ];

        foreach ($permissions as $perm) {
            $prefix = explode('.', $perm->name)[0];

            if (in_array($prefix, ['pembayaran', 'tagihan'])) {
                $grouped['Pemasukan (Tagihan & Pembayaran)'][] = $perm;
            } elseif (in_array($prefix, ['pengeluaran', 'rkas'])) {
                $grouped['Pengeluaran & RKAS'][] = $perm;
            } elseif (in_array($prefix, ['penggajian', 'absensi', 'tunjangan', 'potongan'])) {
                $grouped['Penggajian & SDM'][] = $perm;
            } elseif ($prefix === 'laporan') {
                $grouped['Laporan Keuangan'][] = $perm;
            } elseif ($prefix === 'master') {
                $grouped['Master Data'][] = $perm;
            } elseif ($prefix === 'sistem') {
                $grouped['Pengaturan Sistem'][] = $perm;
            } else {
                if (! isset($grouped['Lainnya'])) {
                    $grouped['Lainnya'] = [];
                }
                $grouped['Lainnya'][] = $perm;
            }
        }

        return array_filter($grouped, fn ($group) => count($group) > 0);
    }
}
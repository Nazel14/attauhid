<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class KeuanganRolePermissionSeeder extends Seeder
{
    /**
     * 90 permission + 4 role modul keuangan.
     *
     * Role 'Super Admin' (id 6 di dump edufinance) sengaja TIDAK dibuat —
     * duplikat dengan 'super-admin' milik raport. Yang sudah ada diberi
     * tambahan seluruh permission keuangan.
     *
     * Nama role dibuat kebab-case (bukan 'Kepala Sekolah') karena spasi
     * di dalam parameter middleware 'role:...' rawan salah parsing.
     *
     * Idempoten.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'pembayaran.lihat', 'pembayaran.buat', 'pembayaran.edit', 'pembayaran.hapus',
            'pembayaran.cetak', 'pembayaran.approve_diperiksa', 'pembayaran.approve_disetujui',

            'tagihan.lihat', 'tagihan.buat_batch', 'tagihan.edit_batch', 'tagihan.hapus_batch',
            'tagihan.buat_rincian', 'tagihan.edit_rincian', 'tagihan.hapus_rincian',
            'tagihan.publish', 'tagihan.cetak_massal', 'tagihan.tutup_batch', 'tagihan.catat_log',

            'pengeluaran.lihat', 'pengeluaran.buat', 'pengeluaran.edit', 'pengeluaran.hapus',
            'pengeluaran.buat_lpj', 'pengeluaran.approve_diperiksa',
            'pengeluaran.approve_disetujui', 'pengeluaran.approve_selesai',

            'rkas.lihat', 'rkas.buat_batch', 'rkas.buat_rincian', 'rkas.edit_batch',
            'rkas.edit_rincian', 'rkas.hapus_batch', 'rkas.hapus_rincian', 'rkas.buat_lpj',
            'rkas.approve_diperiksa', 'rkas.approve_final', 'rkas.approve_tutup_batch',

            'absensi.lihat', 'absensi.kelola',

            'tunjangan.lihat', 'tunjangan.buat', 'tunjangan.edit', 'tunjangan.hapus',
            'potongan.lihat', 'potongan.buat', 'potongan.edit', 'potongan.hapus',

            'penggajian.lihat', 'penggajian.generate_batch', 'penggajian.hapus_batch',
            'penggajian.cetak', 'penggajian.buat_rincian', 'penggajian.edit_rincian',
            'penggajian.hapus_rincian', 'penggajian.approve_diperiksa',
            'penggajian.approve_disetujui', 'penggajian.approve_dibayar',

            'laporan.buku_kas', 'laporan.rkas', 'laporan.tunggakan',

            'master.kelas.lihat', 'master.kelas.buat', 'master.kelas.edit', 'master.kelas.hapus',
            'master.siswa.lihat', 'master.siswa.buat', 'master.siswa.edit',
            'master.jabatan.lihat', 'master.jabatan.buat', 'master.jabatan.edit', 'master.jabatan.hapus',
            'master.pegawai.lihat', 'master.pegawai.buat', 'master.pegawai.edit',
            'master.pegawai.kelola_gaji', 'master.pegawai.hapus',
            'master.rekening.lihat', 'master.rekening.buat', 'master.rekening.edit', 'master.rekening.hapus',
            'master.tarif.lihat', 'master.tarif.buat', 'master.tarif.edit', 'master.tarif.hapus',
            'master.rkas.lihat', 'master.rkas.buat', 'master.rkas.edit', 'master.rkas.hapus',

            'sistem.kelola_role', 'sistem.kelola_user',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roles = [
            'bendahara' => [
                'pembayaran.lihat', 'pembayaran.buat', 'pembayaran.edit', 'pembayaran.cetak',
                'tagihan.lihat', 'tagihan.buat_batch', 'tagihan.edit_batch', 'tagihan.hapus_batch',
                'tagihan.buat_rincian', 'tagihan.edit_rincian', 'tagihan.hapus_rincian',
                'tagihan.publish', 'tagihan.cetak_massal', 'tagihan.catat_log',
                'pengeluaran.lihat', 'pengeluaran.buat', 'pengeluaran.edit', 'pengeluaran.buat_lpj',
                'rkas.lihat', 'rkas.buat_batch', 'rkas.buat_rincian', 'rkas.edit_batch', 'rkas.edit_rincian',
                'penggajian.lihat', 'penggajian.cetak',
                'laporan.buku_kas', 'laporan.rkas', 'laporan.tunggakan',
                'master.kelas.lihat', 'master.siswa.lihat', 'master.rekening.lihat',
                'master.tarif.lihat', 'master.rkas.lihat',
            ],

            'kepala-sekolah' => [
                'pembayaran.lihat', 'pembayaran.approve_diperiksa',
                'tagihan.lihat', 'tagihan.publish',
                'pengeluaran.lihat', 'pengeluaran.approve_diperiksa',
                'rkas.lihat', 'rkas.approve_diperiksa',
                'absensi.lihat',
                'penggajian.lihat', 'penggajian.approve_diperiksa',
                'laporan.buku_kas', 'laporan.rkas', 'laporan.tunggakan',
                'master.kelas.lihat', 'master.siswa.lihat', 'master.jabatan.lihat', 'master.pegawai.lihat',
            ],

            'pihak-yayasan' => [
                'pembayaran.lihat', 'pembayaran.approve_disetujui',
                'tagihan.tutup_batch',
                'pengeluaran.lihat', 'pengeluaran.approve_disetujui', 'pengeluaran.approve_selesai',
                'rkas.lihat', 'rkas.approve_final', 'rkas.approve_tutup_batch',
                'penggajian.lihat', 'penggajian.approve_disetujui', 'penggajian.approve_dibayar',
                'laporan.buku_kas', 'laporan.rkas', 'laporan.tunggakan',
                'master.rekening.lihat', 'master.rekening.buat', 'master.rekening.edit', 'master.rekening.hapus',
                'sistem.kelola_role', 'sistem.kelola_user',
            ],

            'hrd' => [
                'absensi.lihat', 'absensi.kelola',
                'tunjangan.lihat', 'tunjangan.buat', 'tunjangan.edit', 'tunjangan.hapus',
                'potongan.lihat', 'potongan.buat', 'potongan.edit', 'potongan.hapus',
                'penggajian.lihat', 'penggajian.generate_batch', 'penggajian.hapus_batch',
                'penggajian.cetak', 'penggajian.buat_rincian', 'penggajian.edit_rincian',
                'penggajian.hapus_rincian',
                'master.kelas.lihat', 'master.kelas.buat', 'master.kelas.edit', 'master.kelas.hapus',
                'master.siswa.lihat', 'master.siswa.buat', 'master.siswa.edit',
                'master.jabatan.lihat', 'master.jabatan.buat', 'master.jabatan.edit', 'master.jabatan.hapus',
                'master.pegawai.lihat', 'master.pegawai.buat', 'master.pegawai.edit',
                'master.pegawai.kelola_gaji', 'master.pegawai.hapus',
            ],
        ];

        foreach ($roles as $nama => $izin) {
            $role = Role::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
            $role->syncPermissions($izin);
            $this->command->info("{$nama}: " . count($izin) . " permission");
        }

        // super-admin raport dapat SELURUH permission keuangan, tanpa
        // mengganggu 10 permission raport yang sudah dimilikinya.
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
            $this->command->info('super-admin: total ' . $superAdmin->permissions()->count() . ' permission');
        } else {
            $this->command->warn("Role 'super-admin' tidak ditemukan — jalankan SuperAdminRoleSeeder dulu.");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
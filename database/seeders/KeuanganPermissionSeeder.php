<?php
// database/seeders/KeuanganPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission & role modul Keuangan.
 *
 * Daftar permission disusun dari middleware di routes/keuangan.php DITAMBAH
 * permission bertahap yang digerbang di dalam controller (approve_diperiksa,
 * approve_final, approve_tutup_batch) — yang tidak muncul sebagai middleware
 * karena satu route melayani beberapa stage sekaligus.
 *
 * Kelima role di bawah harus sama persis dengan config('modules.keuangan_roles');
 * role yang ada di config tapi tidak ada di sini akan membuat pemetaan
 * jabatan_ke_role melempar RoleDoesNotExist saat pegawai disimpan.
 *
 * Idempoten: aman dijalankan berulang.
 */
class KeuanganPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        // ---------- Master Data ----------
        'master.rekening.lihat', 'master.rekening.buat', 'master.rekening.edit', 'master.rekening.hapus',
        'master.tarif.lihat', 'master.tarif.buat', 'master.tarif.edit', 'master.tarif.hapus',
        'master.jabatan.lihat', 'master.jabatan.buat', 'master.jabatan.edit', 'master.jabatan.hapus',
        'master.kelas.lihat', 'master.kelas.buat', 'master.kelas.edit', 'master.kelas.hapus',
        // Siswa sengaja tanpa .hapus — penghapusan santri lewat panel raport.
        'master.siswa.lihat', 'master.siswa.buat', 'master.siswa.edit',
        'master.pegawai.lihat', 'master.pegawai.buat', 'master.pegawai.edit',
        'master.pegawai.hapus', 'master.pegawai.kelola_gaji',
        'master.rkas.lihat', 'master.rkas.buat', 'master.rkas.edit', 'master.rkas.hapus',

        // ---------- RKAS ----------
        'rkas.lihat', 'rkas.buat_batch', 'rkas.edit_batch', 'rkas.hapus_batch',
        'rkas.buat_rincian', 'rkas.edit_rincian', 'rkas.hapus_rincian',
        'rkas.approve_diperiksa', 'rkas.approve_final', 'rkas.approve_tutup_batch',

        // ---------- Pengeluaran ----------
        'pengeluaran.lihat', 'pengeluaran.buat', 'pengeluaran.edit', 'pengeluaran.hapus',
        'pengeluaran.buat_lpj',
        'pengeluaran.approve_diperiksa', 'pengeluaran.approve_final', 'pengeluaran.approve_selesai',

        // ---------- Tagihan ----------
        'tagihan.lihat', 'tagihan.buat_batch', 'tagihan.edit_batch', 'tagihan.hapus_batch',
        'tagihan.buat_rincian', 'tagihan.edit_rincian', 'tagihan.hapus_rincian',
        'tagihan.publish', 'tagihan.tutup_batch', 'tagihan.cetak_massal', 'tagihan.catat_log',

        // ---------- Pembayaran ----------
        'pembayaran.lihat', 'pembayaran.buat', 'pembayaran.edit', 'pembayaran.hapus',
        'pembayaran.cetak', 'pembayaran.approve_diperiksa', 'pembayaran.approve_final',

        // ---------- Penggajian & SDM ----------
        'absensi.lihat', 'absensi.kelola',
        'tunjangan.lihat', 'tunjangan.buat', 'tunjangan.edit', 'tunjangan.hapus',
        'potongan.lihat', 'potongan.buat', 'potongan.edit', 'potongan.hapus',
        'penggajian.lihat', 'penggajian.generate_batch', 'penggajian.hapus_batch', 'penggajian.cetak',
        'penggajian.buat_rincian', 'penggajian.edit_rincian', 'penggajian.hapus_rincian',
        'penggajian.approve_diperiksa', 'penggajian.approve_final', 'penggajian.approve_tutup_batch',

        // ---------- Laporan ----------
        'laporan.buku_kas', 'laporan.tunggakan', 'laporan.rkas',

        // ---------- Pengaturan Sistem ----------
        // Route-nya masih digembok 'role:super-admin'. Permission ini
        // disiapkan supaya kartu "Pengaturan Sistem" muncul di form role,
        // dan gerbangnya bisa dipindah ke permission kapan saja.
        'sistem.role.lihat', 'sistem.role.kelola',
        'sistem.user.lihat', 'sistem.user.kelola',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        /* ==========================================================
         * BENDAHARA — pelaksana harian.
         * Boleh membuat dan mengubah, TIDAK boleh menyetujui.
         * Pemisahan tugas dijaga di sini: yang mengajukan tidak
         * boleh sekaligus menyetujui pengajuannya sendiri.
         * ========================================================== */
        $bendahara = array_values(array_filter(
            self::PERMISSIONS,
            fn ($p) => ! str_contains($p, '.approve_')
                    && ! str_starts_with($p, 'sistem.')
        ));

        /* ==========================================================
         * KEPALA SEKOLAH — pemeriksa tahap pertama.
         * ========================================================== */
        $kepalaSekolah = array_merge($this->hanyaLihat(), [
            'rkas.approve_diperiksa',
            'pengeluaran.approve_diperiksa',
            'pembayaran.approve_diperiksa',
            'penggajian.approve_diperiksa',
            'tagihan.cetak_massal',
            'pembayaran.cetak',
            'penggajian.cetak',
        ]);

        /* ==========================================================
         * KETUA YAYASAN — persetujuan akhir dan penutupan batch.
         * ========================================================== */
        $ketuaYayasan = array_merge($this->hanyaLihat(), [
            'rkas.approve_final', 'rkas.approve_tutup_batch',
            'pengeluaran.approve_final', 'pengeluaran.approve_selesai',
            'pembayaran.approve_final',
            'penggajian.approve_final', 'penggajian.approve_tutup_batch',
            'tagihan.publish', 'tagihan.tutup_batch',
        ]);

        /* ==========================================================
         * PIHAK YAYASAN — pengawas, hanya membaca.
         * Bedanya dengan ketua yayasan adalah wewenang persetujuan,
         * yang sengaja TIDAK diberikan: pengurus yayasan lain boleh
         * memantau laporan tanpa ikut menandatangani pencairan.
         * ========================================================== */
        $pihakYayasan = $this->hanyaLihat();

        /* ==========================================================
         * HRD — kepegawaian dan penggajian saja.
         * Tagihan santri, RKAS, dan pengeluaran umum di luar wewenangnya.
         * Tidak diberi hak approve: pencairan gaji tetap lewat
         * kepala sekolah lalu ketua yayasan.
         * ========================================================== */
        $hrd = [
            'master.pegawai.lihat', 'master.pegawai.buat', 'master.pegawai.edit',
            'master.pegawai.hapus', 'master.pegawai.kelola_gaji',
            'master.jabatan.lihat', 'master.jabatan.buat', 'master.jabatan.edit', 'master.jabatan.hapus',
            'absensi.lihat', 'absensi.kelola',
            'tunjangan.lihat', 'tunjangan.buat', 'tunjangan.edit', 'tunjangan.hapus',
            'potongan.lihat', 'potongan.buat', 'potongan.edit', 'potongan.hapus',
            'penggajian.lihat', 'penggajian.generate_batch', 'penggajian.hapus_batch',
            'penggajian.cetak', 'penggajian.buat_rincian', 'penggajian.edit_rincian',
            'penggajian.hapus_rincian',
        ];

        Role::firstOrCreate(['name' => 'bendahara', 'guard_name' => 'web'])
            ->syncPermissions($bendahara);

        Role::firstOrCreate(['name' => 'kepala-sekolah', 'guard_name' => 'web'])
            ->syncPermissions($kepalaSekolah);

        Role::firstOrCreate(['name' => 'ketua-yayasan', 'guard_name' => 'web'])
            ->syncPermissions($ketuaYayasan);

        Role::firstOrCreate(['name' => 'pihak-yayasan', 'guard_name' => 'web'])
            ->syncPermissions($pihakYayasan);

        Role::firstOrCreate(['name' => 'hrd', 'guard_name' => 'web'])
            ->syncPermissions($hrd);

        // super-admin disinkronkan ulang: saat AdminUserSeeder jalan,
        // permission keuangan belum ada, jadi role-nya baru memuat
        // 10 permission raport saja.
        Role::where('name', 'super-admin')->where('guard_name', 'web')->first()
            ?->syncPermissions(Permission::where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Permission keuangan  : ' . count(self::PERMISSIONS));
        $this->command->info('Total permission     : ' . Permission::count());
        $this->command->info('-------------------------------------------------');
        $this->command->info('Role bendahara       : ' . count($bendahara) . ' izin');
        $this->command->info('Role kepala-sekolah  : ' . count($kepalaSekolah) . ' izin');
        $this->command->info('Role ketua-yayasan   : ' . count($ketuaYayasan) . ' izin');
        $this->command->info('Role pihak-yayasan   : ' . count($pihakYayasan) . ' izin');
        $this->command->info('Role hrd             : ' . count($hrd) . ' izin');
        $this->command->info('=================================================');
    }

    /** Semua permission berakhiran .lihat plus seluruh laporan — dasar akses baca. */
    private function hanyaLihat(): array
    {
        return array_values(array_filter(
            self::PERMISSIONS,
            fn ($p) => str_ends_with($p, '.lihat')
                    || str_starts_with($p, 'laporan.')
        ));
    }
}   
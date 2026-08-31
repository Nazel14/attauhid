<?php
// database/seeders/DemoKeuanganSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh modul keuangan untuk instalasi DEMO.
 *
 * Isi: rekening kas, tarif, jabatan, pegawai, komponen gaji,
 * template & batch RKAS beserta rinciannya, pengajuan pengeluaran
 * di berbagai tahap persetujuan, tagihan SPP tiga bulan terakhir,
 * pembayaran masuk, dan mutasi kas yang menurunkannya.
 *
 * Jalankan SETELAH DemoRaportSeeder dan DemoUserSeeder — tagihan
 * menunjuk ke santris, dan pegawai menunjuk ke users yang dibuat
 * kedua seeder itu.
 *
 * Catatan: data payroll (payroll_headers/details) sengaja TIDAK dibuat.
 * Batch gaji lebih baik dibuat lewat tombol "Generate" di panel saat
 * demo — itu justru memperlihatkan fitur yang ingin dijual.
 *
 * JANGAN dijalankan di instalasi milik klien.
 */
class DemoKeuanganSeeder extends Seeder
{
    private const TAHUN_AJARAN = '2025/2026';

    public function run(): void
    {
        DB::transaction(function () {
            $userIds = $this->userIds();
            $akun    = $this->rekening();
            $tarif   = $this->tarif();
            $jabatan = $this->jabatan();

            $this->pegawai($jabatan, $userIds);
            $this->komponenGaji();

            $template = $this->rkasTemplate();
            $rkas     = $this->rkas($template, $userIds);

            $this->pengeluaran($rkas, $akun, $userIds);
            $this->tagihanDanPembayaran($tarif, $akun, $userIds);

            // Dijalankan paling akhir: membaca inbound_payments dan
            // outbound_requests yang sudah dibuat di atas.
            $this->mutasiKas($akun, $userIds);
        });

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Data demo modul keuangan selesai dibuat');
        $this->command->info('=================================================');
    }

    /* ============================================================
     * ID AKUN PENGGUNA
     * Dipakai untuk mengisi kolom created_by / diperiksa_by / dst,
     * supaya riwayat persetujuan menampilkan nama, bukan kosong.
     * ============================================================ */
    private function userIds(): array
    {
        $ambil = fn (string $nama) => DB::table('users')->where('name', $nama)->value('id');

        return [
            'super'     => $ambil('superadmin'),
            'bendahara' => $ambil('demo.bendahara') ?? $ambil('superadmin'),
            'kepsek'    => $ambil('demo.kepsek') ?? $ambil('superadmin'),
            'ketua'     => $ambil('demo.ketuayayasan') ?? $ambil('superadmin'),
            'hrd'       => $ambil('demo.hrd') ?? $ambil('superadmin'),
            'yayasan'   => $ambil('demo.yayasan') ?? $ambil('superadmin'),
        ];
    }

    /* ============================================================
     * REKENING KAS
     * Saldo awal 0 — nilainya dihitung ulang di mutasiKas() dari
     * seluruh transaksi, supaya saldo rekening dan buku kas cocok.
     * ============================================================ */
    private function rekening(): array
    {
        $daftar = [
            ['account_name' => 'Kas Tunai Pesantren',             'type' => 'Tunai'],
            ['account_name' => 'Bank Syariah - Operasional',      'type' => 'Bank'],
            ['account_name' => 'Bank Syariah - Dana Pembangunan', 'type' => 'Bank'],
        ];

        $ids = [];

        foreach ($daftar as $d) {
            $id = DB::table('accounts')->where('account_name', $d['account_name'])->value('id');

            if (! $id) {
                $id = DB::table('accounts')->insertGetId($d + [
                    'balance'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /* ============================================================
     * TARIF
     * ============================================================ */
    private function tarif(): array
    {
        $daftar = [
            'spp_reguler' => ['billing_name' => 'SPP Bulanan Reguler',      'nominal' => 350000,  'target_student_type' => 'Reguler', 'period' => 'Bulanan'],
            'spp_fullday' => ['billing_name' => 'SPP Bulanan Fullday',      'nominal' => 500000,  'target_student_type' => 'Fullday', 'period' => 'Bulanan'],
            'pangkal'     => ['billing_name' => 'Uang Pangkal Santri Baru', 'nominal' => 2500000, 'target_student_type' => 'All',     'period' => 'Sekali Bayar'],
            'infaq'       => ['billing_name' => 'Infaq Pembangunan Asrama', 'nominal' => 1000000, 'target_student_type' => 'Donasi',  'period' => 'Sekali Bayar'],
        ];

        $ids = [];

        foreach ($daftar as $kunci => $d) {
            $id = DB::table('tariffs')->where('billing_name', $d['billing_name'])->value('id');

            if (! $id) {
                $id = DB::table('tariffs')->insertGetId($d + [
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ids[$kunci] = $id;
        }

        return $ids;
    }

    /* ============================================================
     * JABATAN
     * nama_jabatan harus cocok dengan key di config
     * modules.jabatan_ke_role (huruf kecil) agar hak akses pegawai
     * terisi otomatis saat ditambahkan lewat panel.
     * ============================================================ */
    private function jabatan(): array
    {
        $daftar = [
            'guru'      => ['nama_jabatan' => 'Guru',           'description' => 'Pengajar tahfizh',           'gaji_pokok_default' => 2500000],
            'bendahara' => ['nama_jabatan' => 'Bendahara',      'description' => 'Pengelola kas dan tagihan',  'gaji_pokok_default' => 3000000],
            'kepsek'    => ['nama_jabatan' => 'Kepala Sekolah', 'description' => 'Pimpinan satuan pendidikan', 'gaji_pokok_default' => 4500000],
            'hrd'       => ['nama_jabatan' => 'HRD',            'description' => 'Kepegawaian dan penggajian', 'gaji_pokok_default' => 3200000],
            'ketua'     => ['nama_jabatan' => 'Ketua Yayasan',  'description' => 'Penanggung jawab yayasan',   'gaji_pokok_default' => 5000000],
        ];

        $ids = [];

        foreach ($daftar as $kunci => $d) {
            $id = DB::table('positions')->where('nama_jabatan', $d['nama_jabatan'])->value('id');

            if (! $id) {
                $id = DB::table('positions')->insertGetId($d + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ids[$kunci] = $id;
        }

        return $ids;
    }

    /* ============================================================
     * PEGAWAI
     * Guru yang sudah ada di tabel `gurus` ikut didaftarkan sebagai
     * pegawai supaya penggajian punya objek yang nyata.
     * class_id sengaja dibiarkan null — pengisiannya tidak wajib
     * dan tabel rujukannya berbeda antar instalasi.
     * ============================================================ */
    private function pegawai(array $jabatan, array $userIds): void
    {
        $daftar = [
            ['nip' => 'P-1001', 'name' => 'Ustadz Abdurrahman Hakim', 'position_id' => $jabatan['guru'],      'user' => 'demo.guru1'],
            ['nip' => 'P-1002', 'name' => 'Ustadz Ilham Maulana',     'position_id' => $jabatan['guru'],      'user' => 'demo.guru2'],
            ['nip' => 'P-1003', 'name' => 'Ustadzah Nadia Salsabila', 'position_id' => $jabatan['guru'],      'user' => 'demo.guru3'],
            ['nip' => 'P-2001', 'name' => 'Bapak Sulaiman Hadi',      'position_id' => $jabatan['bendahara'], 'user' => 'demo.bendahara'],
            ['nip' => 'P-2002', 'name' => 'Bapak Zainuddin Arif',     'position_id' => $jabatan['kepsek'],    'user' => 'demo.kepsek'],
            ['nip' => 'P-2003', 'name' => 'Ibu Halimah Rosyidah',     'position_id' => $jabatan['hrd'],       'user' => 'demo.hrd'],
            ['nip' => 'P-2004', 'name' => 'Bapak Mahmud Yunus',       'position_id' => $jabatan['ketua'],     'user' => 'demo.ketuayayasan'],
        ];

        foreach ($daftar as $d) {
            if (DB::table('employees')->where('nip', $d['nip'])->exists()) {
                continue;
            }

            DB::table('employees')->insert([
                'user_id'     => DB::table('users')->where('name', $d['user'])->value('id'),
                'nip'         => $d['nip'],
                'name'        => $d['name'],
                'position_id' => $d['position_id'],
                'class_id'    => null,
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    /* ============================================================
     * KOMPONEN GAJI
     * ============================================================ */
    private function komponenGaji(): void
    {
        $tunjangan = [
            ['nama_tunjangan' => 'Tunjangan Jabatan',   'jenis' => 'Tetap',     'nominal_default' => 500000],
            ['nama_tunjangan' => 'Tunjangan Transport', 'jenis' => 'Kehadiran', 'nominal_default' => 25000],
            ['nama_tunjangan' => 'Tunjangan Konsumsi',  'jenis' => 'Kehadiran', 'nominal_default' => 15000],
        ];

        foreach ($tunjangan as $t) {
            if (! DB::table('allowances')->where('nama_tunjangan', $t['nama_tunjangan'])->exists()) {
                DB::table('allowances')->insert($t + ['created_at' => now(), 'updated_at' => now()]);
            }
        }

        $potongan = [
            ['nama_potongan' => 'Iuran BPJS Kesehatan',   'jenis' => 'Tetap',     'nominal_default' => 100000],
            ['nama_potongan' => 'Potongan Keterlambatan', 'jenis' => 'Kehadiran', 'nominal_default' => 10000],
        ];

        foreach ($potongan as $p) {
            if (! DB::table('deductions')->where('nama_potongan', $p['nama_potongan'])->exists()) {
                DB::table('deductions')->insert($p + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    /* ============================================================
     * TEMPLATE RKAS
     * Dua level: judul besar (level 1) dan sub-judul (level 2).
     * ============================================================ */
    private function rkasTemplate(): array
    {
        $struktur = [
            ['kode' => '1', 'nama' => 'Belanja Pegawai', 'anak' => [
                ['kode' => '1.1', 'nama' => 'Gaji dan Tunjangan Pegawai'],
                ['kode' => '1.2', 'nama' => 'Honor Kegiatan'],
            ]],
            ['kode' => '2', 'nama' => 'Belanja Operasional', 'anak' => [
                ['kode' => '2.1', 'nama' => 'Listrik, Air, dan Internet'],
                ['kode' => '2.2', 'nama' => 'Konsumsi Santri'],
                ['kode' => '2.3', 'nama' => 'Alat Tulis dan Cetak'],
            ]],
            ['kode' => '3', 'nama' => 'Belanja Modal', 'anak' => [
                ['kode' => '3.1', 'nama' => 'Sarana dan Prasarana'],
            ]],
        ];

        $hasil = [];

        foreach ($struktur as $induk) {
            $indukId = DB::table('rkas_templates')->where('kode_judul', $induk['kode'])->value('id');

            if (! $indukId) {
                $indukId = DB::table('rkas_templates')->insertGetId([
                    'parent_id'  => null,
                    'kode_judul' => $induk['kode'],
                    'nama_judul' => $induk['nama'],
                    'level'      => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($induk['anak'] as $anak) {
                $anakId = DB::table('rkas_templates')->where('kode_judul', $anak['kode'])->value('id');

                if (! $anakId) {
                    $anakId = DB::table('rkas_templates')->insertGetId([
                        'parent_id'  => $indukId,
                        'kode_judul' => $anak['kode'],
                        'nama_judul' => $anak['nama'],
                        'level'      => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $hasil[$anak['kode']] = $anakId;
            }
        }

        return $hasil;
    }

    /**
     * Rincian kegiatan RKAS. Urutan array ini dipakai sebagai indeks
     * oleh method pengeluaran(), supaya deskripsi pengajuan menempel
     * pada mata anggaran yang benar.
     *
     * Format: [kode template, kode kegiatan, nama, volume, satuan, harga]
     */
    private function daftarKegiatan(): array
    {
        return [
            0 => ['1.1', '1.1.1', 'Gaji pokok pegawai tetap',       12, 'bulan',    21200000],
            1 => ['1.1', '1.1.2', 'Tunjangan jabatan struktural',   12, 'bulan',     2000000],
            2 => ['1.2', '1.2.1', 'Honor pengawas ujian tahfizh',    2, 'kegiatan',  3500000],
            3 => ['2.1', '2.1.1', 'Tagihan listrik asrama',         12, 'bulan',     2800000],
            4 => ['2.1', '2.1.2', 'Langganan internet',             12, 'bulan',      750000],
            5 => ['2.2', '2.2.1', 'Konsumsi harian santri',         12, 'bulan',    18500000],
            6 => ['2.3', '2.3.1', 'Cetak rapor dan administrasi',    2, 'semester',  1750000],
            7 => ['3.1', '3.1.1', 'Pengadaan rak mushaf',           20, 'unit',       450000],
            8 => ['3.1', '3.1.2', 'Perbaikan ruang halaqah',         1, 'paket',    15000000],
        ];
    }

    /* ============================================================
     * BATCH RKAS + RINCIAN KEGIATAN
     * ============================================================ */
    private function rkas(array $template, array $userIds): array
    {
        $kegiatan = $this->daftarKegiatan();

        $headerId = DB::table('rkas_headers')
            ->where('tahun_ajaran', self::TAHUN_AJARAN)
            ->value('id');

        if ($headerId) {
            // Urut kode_kegiatan supaya indeksnya tetap sama dengan
            // daftarKegiatan() saat seeder dijalankan ulang.
            $detail = DB::table('rkas_details')
                ->where('rkas_header_id', $headerId)
                ->where('is_parent', 0)
                ->orderBy('kode_kegiatan')
                ->pluck('id')
                ->all();

            return ['header' => $headerId, 'detail' => $detail];
        }

        $totalPagu = 0;
        foreach ($kegiatan as $k) {
            $totalPagu += $k[3] * $k[5];
        }

        // Batch dibuat berstatus Final supaya laporan realisasi RKAS
        // langsung punya isi. Riwayat pemeriksa & penyetuju ikut diisi
        // agar jejak persetujuannya terlihat saat didemokan.
        $headerId = DB::table('rkas_headers')->insertGetId([
            'tahun_ajaran'    => self::TAHUN_AJARAN,
            'budget_limit'    => 750000000,
            'total_allocated' => $totalPagu,
            'status'          => 'Final',
            'created_by'      => $userIds['bendahara'],
            'diperiksa_by'    => $userIds['kepsek'],
            'diperiksa_at'    => now()->subMonths(3)->addDays(2),
            'final_by'        => $userIds['ketua'],
            'final_at'        => now()->subMonths(3)->addDays(5),
            'created_at'      => now()->subMonths(3),
            'updated_at'      => now()->subMonths(3)->addDays(5),
        ]);

        mt_srand(20260902);
        $detailIds = [];

        foreach ($kegiatan as $i => $k) {
            [$kodeTemplate, $kodeKegiatan, $nama, $volume, $satuan, $harga] = $k;
            $pagu = $volume * $harga;

            // Realisasi 0-70% dari pagu — sebagian pos sengaja belum
            // terserap sama sekali supaya laporan realisasi tidak rata.
            $realisasi = (int) round($pagu * (mt_rand(0, 70) / 100));

            $detailIds[$i] = DB::table('rkas_details')->insertGetId([
                'rkas_header_id'    => $headerId,
                'rkas_template_id'  => $template[$kodeTemplate] ?? null,
                'parent_id'         => null,
                'kode_kegiatan'     => $kodeKegiatan,
                'nama_kegiatan'     => $nama,
                'volume_1'          => $volume,
                'satuan_1'          => $satuan,
                'harga_satuan'      => $harga,
                'pagu_anggaran'     => $pagu,
                'nominal_realisasi' => $realisasi,
                'is_parent'         => 0,
                'created_at'        => now()->subMonths(3),
                'updated_at'        => now()->subMonths(3),
            ]);
        }

        return ['header' => $headerId, 'detail' => $detailIds];
    }

    /* ============================================================
     * PENGAJUAN PENGELUARAN
     * Dibuat di beberapa tahap berbeda supaya alur persetujuan
     * terlihat utuh: ada yang masih draft, diperiksa, diketahui,
     * disetujui, dan sudah selesai ber-LPJ.
     * ============================================================ */
    private function pengeluaran(array $rkas, array $akun, array $userIds): void
    {
        if (DB::table('outbound_requests')->exists()) {
            return;
        }

        // Kolom rkas_id bisa menunjuk ke rkas_details ATAU rkas_headers,
        // tergantung instalasi. Dicek dari information_schema agar
        // seeder tidak menebak dan menghasilkan FK yang gagal.
        $rujukan = DB::selectOne(
            'SELECT referenced_table_name AS t
             FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?
               AND referenced_table_name IS NOT NULL',
            ['outbound_requests', 'rkas_id']
        );

        $pakaiDetail = ! $rujukan || $rujukan->t === 'rkas_details';
        $detail      = $rkas['detail'];

        if ($pakaiDetail && empty($detail)) {
            $this->command->warn('Rincian RKAS kosong — pengajuan pengeluaran dilewati.');
            return;
        }

        // 'ke' menunjuk indeks kegiatan di daftarKegiatan(), supaya
        // keperluan pengajuan cocok dengan mata anggarannya. Tanpa ini
        // pengajuan "cetak rapor" bisa menempel pada pos "konsumsi
        // santri" — janggal begitu dilihat calon pembeli.
        $daftar = [
            ['ke' => 3, 'nominal' => 2800000,  'desc' => 'Pembayaran listrik asrama bulan berjalan', 'status' => 'selesai',   'hari' => 62],
            ['ke' => 5, 'nominal' => 18500000, 'desc' => 'Belanja konsumsi santri bulan berjalan',   'status' => 'selesai',   'hari' => 48],
            ['ke' => 7, 'nominal' => 9000000,  'desc' => 'Pengadaan rak mushaf tahap pertama',       'status' => 'disetujui', 'hari' => 25],
            ['ke' => 4, 'nominal' => 750000,   'desc' => 'Perpanjangan langganan internet',          'status' => 'diketahui', 'hari' => 12],
            ['ke' => 2, 'nominal' => 3500000,  'desc' => 'Honor pengawas ujian tahfizh semester 1',  'status' => 'diperiksa', 'hari' => 5],
            ['ke' => 6, 'nominal' => 1750000,  'desc' => 'Cetak rapor semester ganjil',              'status' => 'draft',     'hari' => 1],
        ];

        foreach ($daftar as $d) {
            $selesai   = $d['status'] === 'selesai';
            $disetujui = in_array($d['status'], ['disetujui', 'selesai'], true);
            $diperiksa = $disetujui || in_array($d['status'], ['diperiksa', 'diketahui'], true);
            $tanggal   = now()->subDays($d['hari']);

            DB::table('outbound_requests')->insert([
                'rkas_id' => $pakaiDetail
                    ? ($detail[$d['ke']] ?? reset($detail))
                    : $rkas['header'],
                'tanggal_pengajuan'      => $tanggal->toDateString(),
                'nominal_pengajuan'      => $d['nominal'],
                'deskripsi'              => $d['desc'],
                // Realisasi sedikit di bawah pengajuan pada yang selesai,
                // sisanya jadi refund — memperlihatkan alur pengembalian dana.
                'nominal_realisasi'      => $selesai ? (int) round($d['nominal'] * 0.94) : 0,
                'nominal_refund'         => $selesai ? (int) round($d['nominal'] * 0.06) : 0,
                'is_refund_completed'    => $selesai ? 1 : 0,
                'is_over_budget_warning' => 0,
                'status_approval'        => $d['status'],
                'requested_by'           => $userIds['bendahara'],
                'diperiksa_by'           => $diperiksa ? $userIds['kepsek'] : null,
                'diperiksa_at'           => $diperiksa ? $tanggal->copy()->addDay() : null,
                'disetujui_by'           => $disetujui ? $userIds['ketua'] : null,
                'disetujui_at'           => $disetujui ? $tanggal->copy()->addDays(2) : null,
                'account_id'             => $akun[1],
                'created_at'             => $tanggal,
                'updated_at'             => $tanggal,
            ]);
        }
    }

    /* ============================================================
     * TAGIHAN SPP + PEMBAYARAN
     * Tiga bulan terakhir. Sebagian lunas, sebagian cicilan,
     * sebagian menunggak — supaya laporan tunggakan ada isinya.
     * ============================================================ */
    private function tagihanDanPembayaran(array $tarif, array $akun, array $userIds): void
    {
        if (DB::table('invoice_headers')->exists()) {
            return;
        }

        mt_srand(20260901);

        $santri = DB::table('santris')
            ->where('status', 'aktif')
            ->get(['id', 'name', 'student_type']);

        if ($santri->isEmpty()) {
            $this->command->warn('Belum ada santri — tagihan dilewati. Jalankan DemoRaportSeeder dulu.');
            return;
        }

        $nomorKwitansi = 1;

        for ($k = 2; $k >= 0; $k--) {
            // startOfMonth() DULU baru dikurangi: mengurangi bulan dari
            // tanggal 31 bisa jatuh ke bulan yang sama setelah dinormalkan,
            // dan batch_number-nya bentrok karena unique.
            $bulan = now()->startOfMonth()->subMonthsNoOverflow($k);
            $label = $bulan->translatedFormat('F Y');
            $jatuh = $bulan->copy()->day(10);

            $nomorBatch = 'INV/' . $bulan->format('Y/m') . '/SPP';

            if (DB::table('invoice_headers')->where('batch_number', $nomorBatch)->exists()) {
                continue;
            }

            $totalHarapan = 0;
            foreach ($santri as $s) {
                $totalHarapan += $s->student_type === 'Fullday' ? 500000 : 350000;
            }

            $headerId = DB::table('invoice_headers')->insertGetId([
                'batch_number'          => $nomorBatch,
                'tariff_id'             => $tarif['spp_reguler'],
                'title'                 => 'SPP Bulan ' . $label,
                'period'                => $bulan->format('Y-m'),
                'due_date'              => $jatuh->toDateString(),
                'total_students'        => $santri->count(),
                'total_expected_amount' => $totalHarapan,
                // Batch bulan berjalan dibiarkan Published, bulan lalu Closed.
                'status'                => $k === 0 ? 'Published' : 'Closed',
                'created_at'            => $bulan,
                'updated_at'            => $bulan,
            ]);

            foreach ($santri as $urut => $s) {
                $fullday = $s->student_type === 'Fullday';
                $nominal = $fullday ? 500000 : 350000;
                $tarifId = $fullday ? $tarif['spp_fullday'] : $tarif['spp_reguler'];

                // Bulan makin lama makin banyak yang lunas; bulan berjalan
                // menyisakan tunggakan supaya laporan tunggakan ada isinya.
                $undi = mt_rand(1, 100);
                if ($k === 0) {
                    $kondisi = $undi <= 55 ? 'lunas' : ($undi <= 75 ? 'cicil' : 'belum');
                } elseif ($k === 1) {
                    $kondisi = $undi <= 80 ? 'lunas' : ($undi <= 92 ? 'cicil' : 'belum');
                } else {
                    $kondisi = $undi <= 92 ? 'lunas' : 'cicil';
                }

                $dibayar = match ($kondisi) {
                    'lunas' => $nominal,
                    'cicil' => (int) round($nominal / 2),
                    default => 0,
                };

                $invoiceId = DB::table('invoices')->insertGetId([
                    'invoice_number'    => sprintf('INV-%s-%03d', $bulan->format('Ym'), $urut + 1),
                    'invoice_header_id' => $headerId,
                    'student_id'        => $s->id,
                    'total_amount'      => $nominal,
                    'remaining_balance' => $nominal - $dibayar,
                    'status'            => $kondisi === 'lunas' ? 'Paid' : ($kondisi === 'cicil' ? 'Installment' : 'Unpaid'),
                    'created_at'        => $bulan,
                    'updated_at'        => $bulan,
                ]);

                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'tariff_id'  => $tarifId,
                    'deskripsi'  => 'SPP ' . ($fullday ? 'Fullday' : 'Reguler') . ' - ' . $label,
                    'nominal'    => $nominal,
                    'diskon'     => 0,
                    'created_at' => $bulan,
                    'updated_at' => $bulan,
                ]);

                if ($dibayar > 0) {
                    $tanggalBayar = $bulan->copy()->addDays(mt_rand(2, 20));

                    DB::table('inbound_payments')->insert([
                        'receipt_number'  => sprintf('KW-%s-%04d', $bulan->format('Ym'), $nomorKwitansi++),
                        'student_id'      => $s->id,
                        'invoice_id'      => $invoiceId,
                        'kategori'        => 'tagihan',
                        'nama_pihak'      => $s->name,
                        'account_id'      => mt_rand(0, 1) === 1 ? $akun[0] : $akun[1],
                        'nominal'         => $dibayar,
                        'payment_date'    => $tanggalBayar->toDateString(),
                        'payment_method'  => mt_rand(0, 1) === 1 ? 'Tunai' : 'Transfer',
                        'catatan'         => null,
                        // Pembayaran bulan berjalan sebagian masih menunggu
                        // verifikasi, supaya tombol approve ada yang bisa dicoba.
                        'status_approval' => ($k === 0 && mt_rand(1, 100) <= 30) ? 'diperiksa' : 'disetujui',
                        'created_by'      => $userIds['bendahara'],
                        'diperiksa_by'    => $userIds['kepsek'],
                        'diperiksa_at'    => $tanggalBayar->copy()->addDay(),
                        'disetujui_by'    => $userIds['ketua'],
                        'disetujui_at'    => $tanggalBayar->copy()->addDays(2),
                        'created_at'      => $tanggalBayar,
                        'updated_at'      => $tanggalBayar,
                    ]);
                }
            }
        }

        // Beberapa pemasukan di luar tagihan santri, supaya kategori
        // selain 'tagihan' juga terwakili di buku kas.
        $lainnya = [
            ['kategori' => 'donatur',    'nama' => 'H. Abdul Karim',           'nominal' => 5000000,  'hari' => 40],
            ['kategori' => 'donatur',    'nama' => 'Keluarga Alm. Hj. Aminah', 'nominal' => 10000000, 'hari' => 22],
            ['kategori' => 'koperasi',   'nama' => 'Koperasi Santri',          'nominal' => 1850000,  'hari' => 15],
            ['kategori' => 'uang_masuk', 'nama' => 'Pendaftaran santri baru',  'nominal' => 2500000,  'hari' => 8],
        ];

        foreach ($lainnya as $l) {
            $tanggal = now()->subDays($l['hari']);

            DB::table('inbound_payments')->insert([
                'receipt_number'  => sprintf('KW-LAIN-%04d', $nomorKwitansi++),
                'student_id'      => null,
                'invoice_id'      => null,
                'kategori'        => $l['kategori'],
                'nama_pihak'      => $l['nama'],
                'account_id'      => $akun[2],
                'nominal'         => $l['nominal'],
                'payment_date'    => $tanggal->toDateString(),
                'payment_method'  => 'Transfer',
                'catatan'         => 'Pemasukan contoh untuk demo',
                'status_approval' => 'disetujui',
                'created_by'      => $userIds['bendahara'],
                'diperiksa_by'    => $userIds['kepsek'],
                'diperiksa_at'    => $tanggal->copy()->addDay(),
                'disetujui_by'    => $userIds['ketua'],
                'disetujui_at'    => $tanggal->copy()->addDays(2),
                'created_at'      => $tanggal,
                'updated_at'      => $tanggal,
            ]);
        }

        $this->command->info('Tagihan dibuat    : ' . DB::table('invoices')->count());
        $this->command->info('Pembayaran dibuat : ' . DB::table('inbound_payments')->count());
    }

    /* ============================================================
     * MUTASI KAS
     * Buku Kas Umum membaca tabel ini, bukan langsung dari
     * inbound_payments/outbound_requests. Tanpa baris di sini,
     * halaman buku kas tampil kosong meski pembayaran sudah ada.
     *
     * Saldo dihitung berjalan per rekening, lalu nilai akhirnya
     * disalin ke accounts.balance supaya keduanya cocok.
     * ============================================================ */
    private function mutasiKas(array $akun, array $userIds): void
    {
        if (DB::table('mutasi_kas')->exists()) {
            return;
        }

        $transaksi = [];

        // Pemasukan yang sudah disetujui saja — yang masih 'diperiksa'
        // belum boleh menambah saldo kas.
        foreach (DB::table('inbound_payments')->where('status_approval', 'disetujui')->get() as $p) {
            $transaksi[] = [
                'account_id' => $p->account_id,
                'tanggal'    => Carbon::parse($p->payment_date),
                // Nama enum diterjemahkan supaya 'uang_masuk' tidak
                // muncul apa adanya di dokumen cetak.
                'keterangan' => 'Penerimaan ' . str_replace('_', ' ', $p->kategori)
                    . ' - ' . ($p->nama_pihak ?: 'tanpa nama'),
                'jenis'      => 'Debit',
                'nominal'    => (float) $p->nominal,
            ];
        }

        // Pengeluaran yang sudah cair. Yang berstatus 'selesai' memakai
        // nominal realisasi; yang 'disetujui' memakai nominal pengajuan.
        foreach (DB::table('outbound_requests')->whereIn('status_approval', ['disetujui', 'selesai'])->get() as $o) {
            $nominal = $o->status_approval === 'selesai'
                ? (float) $o->nominal_realisasi
                : (float) $o->nominal_pengajuan;

            if ($nominal <= 0) {
                continue;
            }

            $transaksi[] = [
                'account_id' => $o->account_id ?: $akun[1],
                'tanggal'    => Carbon::parse($o->tanggal_pengajuan)->addDays(3),
                'keterangan' => 'Pencairan - ' . $o->deskripsi,
                'jenis'      => 'Kredit',
                'nominal'    => $nominal,
            ];
        }

        if (empty($transaksi)) {
            $this->command->warn('Tidak ada transaksi — mutasi kas dilewati.');
            return;
        }

        // Diurutkan berdasarkan tanggal supaya saldo berjalannya masuk akal.
        usort($transaksi, fn ($a, $b) => $a['tanggal']->timestamp <=> $b['tanggal']->timestamp);

        $saldo = [];
        $baris = [];

        foreach ($transaksi as $t) {
            $id = $t['account_id'];
            $saldo[$id] = ($saldo[$id] ?? 0) + ($t['jenis'] === 'Debit' ? $t['nominal'] : -$t['nominal']);

            $baris[] = [
                'account_id'              => $id,
                'tanggal_transaksi'       => $t['tanggal']->toDateTimeString(),
                'keterangan'              => mb_substr($t['keterangan'], 0, 190),
                'jenis'                   => $t['jenis'],
                'nominal'                 => $t['nominal'],
                'saldo_setelah_transaksi' => $saldo[$id],
                // reference_type/id sengaja null: nama kelas model yang
                // dipakai aplikasi tidak dapat dipastikan dari struktur
                // tabel, dan buku kas tidak membutuhkannya untuk tampil.
                'reference_type'          => null,
                'reference_id'            => null,
                'created_by'              => $userIds['bendahara'],
                'created_at'              => $t['tanggal'],
                'updated_at'              => $t['tanggal'],
            ];
        }

        foreach (array_chunk($baris, 300) as $bagian) {
            DB::table('mutasi_kas')->insert($bagian);
        }

        // Saldo rekening disamakan dengan hasil akhir mutasi.
        foreach ($akun as $id) {
            DB::table('accounts')->where('id', $id)->update([
                'balance'    => $saldo[$id] ?? 0,
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Mutasi kas dibuat : ' . count($baris));
    }
}
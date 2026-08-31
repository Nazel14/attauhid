<?php
// database/seeders/DemoRaportSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh modul raport untuk instalasi DEMO.
 *
 * Isi: 1 tahun ajaran aktif, 3 guru (+akun), 3 kelas, 24 santri (+akun),
 * presensi & setoran 3 bulan ke belakang.
 *
 * Tabel domain sengaja diisi lewat DB::table, bukan model Eloquent:
 * nama model dan $fillable-nya bisa berbeda antar instalasi, sedangkan
 * nama kolom sudah pasti. Hanya tabel `users` yang lewat Eloquent,
 * karena cast plain_password wajib dilewati.
 *
 * JANGAN dijalankan di instalasi milik klien.
 */
class DemoRaportSeeder extends Seeder
{
    private const PASSWORD = 'demo12345';

    /**
     * Harus sama persis dengan kolom sesi_name di presensi_sesi_configs.
     * Presensi dengan nama sesi di luar daftar itu tetap tersimpan,
     * tapi tidak dikenali halaman presensi yang membaca dari config.
     */
    private const SESI = ['Materi'];

    private const TAHUN_AJARAN = '2025/2026';

    /** Berapa bulan riwayat presensi & setoran yang dibuat. */
    private const BULAN_RIWAYAT = 3;

    private const GURU = [
        ['nama' => 'Ustadz Abdurrahman Hakim', 'nip' => 'G-2401', 'user' => 'demo.guru1'],
        ['nama' => 'Ustadz Ilham Maulana',     'nip' => 'G-2402', 'user' => 'demo.guru2'],
        ['nama' => 'Ustadzah Nadia Salsabila', 'nip' => 'G-2403', 'user' => 'demo.guru3'],
    ];

    /**
     * Nilai 'gender' di bawah adalah isi kolom kelas.gender_restriction,
     * yang divalidasi KelasController dengan in:ikhwan,akhwat,campuran.
     * Kolomnya varchar — database menerima nilai apa pun, tapi form
     * akan menolaknya saat kelas dibuka untuk diedit.
     *
     * Jangan tertukar dengan santris.gender yang enum
     * 'Laki-laki'/'Perempuan'. Keduanya dijembatani GENDER_SANTRI.
     */
    private const KELAS = [
        ['nama' => 'Tahfizh 7A', 'tingkat' => '7', 'gender' => 'ikhwan'],
        ['nama' => 'Tahfizh 8B', 'tingkat' => '8', 'gender' => 'ikhwan'],
        ['nama' => 'Tahfizh 7C', 'tingkat' => '7', 'gender' => 'akhwat'],
    ];

    /** Jembatan kelas.gender_restriction -> santris.gender. */
    private const GENDER_SANTRI = [
        'ikhwan' => 'Laki-laki',
        'akhwat' => 'Perempuan',
    ];

    private const NAMA_PUTRA = [
        'Ahmad Fauzi Ramadhan', 'Muhammad Rizki Aditya', 'Abdullah Zaki Pratama',
        'Umar Faruq Hidayat', 'Bilal Arkan Nugraha', 'Yusuf Hafizh Maulana',
        'Ibrahim Naufal Saputra', 'Hamzah Rafi Setiawan',
    ];

    private const NAMA_PUTRI = [
        'Siti Aisyah Nurhaliza', 'Fatimah Az-Zahra Putri', 'Khadijah Aulia Rahma',
        'Maryam Salsabila Utami', 'Hafshah Nabila Anggraini', 'Zainab Kirana Dewi',
        'Ruqayyah Alifia Sari', 'Ummu Kultsum Azzahra',
    ];

    /** Surat juz 30 beserta jumlah ayatnya, urut mundur seperti urutan hafalan. */
    private const SURAT = [
        ['An-Naba', 40], ['An-Naziat', 46], ['Abasa', 42], ['At-Takwir', 29],
        ['Al-Infitar', 19], ['Al-Mutaffifin', 36], ['Al-Insyiqaq', 25], ['Al-Buruj', 22],
        ['At-Tariq', 17], ['Al-Ala', 19], ['Al-Ghasyiyah', 26], ['Al-Fajr', 30],
        ['Al-Balad', 20], ['Asy-Syams', 15], ['Al-Lail', 21], ['Ad-Duha', 11],
        ['Asy-Syarh', 8], ['At-Tin', 8], ['Al-Alaq', 19], ['Al-Qadr', 5],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $tahunAjaranId = $this->tahunAjaran();
            $guruIds       = $this->guru();
            $kelasIds      = $this->kelas($guruIds);
            $santri        = $this->santri($kelasIds, $guruIds);

            $this->riwayat($santri, $tahunAjaranId);
        });

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Data demo modul raport selesai dibuat');
        $this->command->info('=================================================');
        $this->command->info('Guru   : demo.guru1, demo.guru2, demo.guru3');
        $this->command->info('Santri : demo.santri1 ... demo.santri24');
        $this->command->info('Password semua akun: ' . self::PASSWORD);
        $this->command->info('=================================================');
    }

    /* ============================================================
     * TAHUN AJARAN
     * ============================================================ */
    private function tahunAjaran(): int
    {
        // Unique-nya (nama, semester), jadi keduanya dipakai sebagai kunci cari.
        $ada = DB::table('tahun_ajaran')
            ->where('nama', self::TAHUN_AJARAN)
            ->where('semester', 1)
            ->first();

        if ($ada) {
            return $ada->id;
        }

        return DB::table('tahun_ajaran')->insertGetId([
            'nama'            => self::TAHUN_AJARAN,
            'semester'        => 1,
            'tanggal_mulai'   => Carbon::create(2025, 7, 14)->toDateString(),
            'tanggal_selesai' => Carbon::create(2025, 12, 20)->toDateString(),
            'is_aktif'        => 1,
            'keterangan'      => 'Tahun ajaran contoh untuk demo',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /* ============================================================
     * GURU + AKUN
     * ============================================================ */
    private function guru(): array
    {
        $ids = [];

        foreach (self::GURU as $g) {
            $user = $this->akun($g['user'], 'guru');

            $guruId = DB::table('gurus')->where('nip', $g['nip'])->value('id');

            if (! $guruId) {
                $guruId = DB::table('gurus')->insertGetId([
                    'user_id'    => $user->id,
                    'name'       => $g['nama'],
                    'nip'        => $g['nip'],
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ids[] = $guruId;
        }

        return $ids;
    }

    /* ============================================================
     * KELAS
     * ============================================================ */
    private function kelas(array $guruIds): array
    {
        $ids = [];

        foreach (self::KELAS as $i => $k) {
            $kelasId = DB::table('kelas')->where('name', $k['nama'])->value('id');

            if (! $kelasId) {
                $kelasId = DB::table('kelas')->insertGetId([
                    'name'    => $k['nama'],
                    'tingkat' => $k['tingkat'],
                    // kapasitas 20 = default kolom; 8 santri per kelas
                    // menyisakan ruang agar tombol "tambah santri" bisa dicoba.
                    'kapasitas'          => 20,
                    'is_active'          => 1,
                    // Kolom varchar, terpisah dari tabel tahun_ajaran.
                    'tahun_ajaran'       => self::TAHUN_AJARAN,
                    'guru_id'            => $guruIds[$i],
                    'gender_restriction' => $k['gender'],
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            } else {
                // Instalasi yang sempat ter-seed versi lama menyimpan
                // 'Laki-laki'/'Perempuan' di kolom ini, dan form menolaknya.
                // Diperbaiki di sini supaya tidak perlu UPDATE manual.
                DB::table('kelas')
                    ->where('id', $kelasId)
                    ->update(['gender_restriction' => $k['gender'], 'updated_at' => now()]);
            }

            $ids[] = $kelasId;
        }

        return $ids;
    }

    /* ============================================================
     * SANTRI + AKUN
     * ============================================================ */
    private function santri(array $kelasIds, array $guruIds): array
    {
        $hasil = [];
        $nomor = 1;

        foreach (self::KELAS as $i => $k) {
            // SantriController menolak santri putra di kelas akhwat dan
            // sebaliknya, jadi gender santri harus mengikuti kelasnya.
            $genderSantri = self::GENDER_SANTRI[$k['gender']];
            $putra        = $genderSantri === 'Laki-laki';
            $daftar       = $putra ? self::NAMA_PUTRA : self::NAMA_PUTRI;

            foreach ($daftar as $j => $nama) {
                $nis = sprintf('%s%02d%02d', '25', $i + 1, $j + 1);

                $santriId = DB::table('santris')->where('nis', $nis)->value('id');

                if (! $santriId) {
                    $user = $this->akun('demo.santri' . $nomor, 'santri');

                    $santriId = DB::table('santris')->insertGetId([
                        'user_id'        => $user->id,
                        'kelas_id'       => $kelasIds[$i],
                        'guru_id'        => $guruIds[$i],
                        'nis'            => $nis,
                        'name'           => $nama,
                        'parent_name'    => 'Bapak/Ibu ' . explode(' ', $nama)[0],
                        'parent_contact' => '08' . str_pad((string) (1000000000 + $nomor * 137), 10, '0', STR_PAD_LEFT),
                        'student_type'   => $j % 3 === 0 ? 'Fullday' : 'Reguler',
                        'angkatan'       => 2025,
                        'is_raport_held' => 0,
                        'gender'         => $genderSantri,
                        // Target dibuat bervariasi supaya grafik capaian
                        // tidak rata dan monitoring terlihat hidup.
                        'target_juz'     => [1, 2, 3][$j % 3],
                        'target_metode'  => 'quran',
                        'status'         => 'aktif',
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }

                $hasil[] = [
                    'id'       => $santriId,
                    'kelas_id' => $kelasIds[$i],
                    'guru_id'  => $guruIds[$i],
                    'urut'     => $nomor,
                ];

                $nomor++;
            }
        }

        return $hasil;
    }

    /* ============================================================
     * PRESENSI + SETORAN
     * ============================================================ */
    private function riwayat(array $santri, int $tahunAjaranId): void
    {
        // mt_srand dikunci supaya data demo selalu sama tiap kali di-seed —
        // memudahkan bila database demo perlu di-reset dan disamakan lagi.
        mt_srand(20260830);

        $mulai   = now()->startOfDay()->subMonths(self::BULAN_RIWAYAT);
        $selesai = now()->startOfDay();

        $barisPresensi = [];
        $barisSetoran  = [];

        // Posisi hafalan tiap santri, maju sedikit demi sedikit tiap setoran.
        $posisi = [];
        foreach ($santri as $s) {
            $posisi[$s['id']] = ['surat' => 0, 'halaman' => 582 + ($s['urut'] % 5)];
        }

        for ($hari = $mulai->copy(); $hari->lte($selesai); $hari->addDay()) {
            // Jumat libur kegiatan tahfizh.
            if ($hari->isFriday()) {
                continue;
            }

            foreach ($santri as $s) {
                foreach (self::SESI as $sesi) {
                    $undi   = mt_rand(1, 100);
                    $status = $undi <= 88 ? 'Hadir' : ($undi <= 93 ? 'Izin' : ($undi <= 97 ? 'Sakit' : 'Alpha'));

                    $barisPresensi[] = [
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'santri_id'       => $s['id'],
                        'guru_id'         => $s['guru_id'],
                        'tanggal'         => $hari->toDateString(),
                        'sesi'            => $sesi,
                        'status'          => $status,
                        'keterangan'      => $status === 'Hadir' ? null : 'Keterangan contoh',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                // Setoran sekitar 3 kali seminggu, bukan tiap hari —
                // supaya riwayatnya menyerupai kebiasaan setoran sungguhan.
                if (mt_rand(1, 100) > 55) {
                    continue;
                }

                $barisSetoran[] = $this->setoran($s, $hari, $tahunAjaranId, $posisi);
            }
        }

        // insert() dipecah agar tidak melebihi batas placeholder MySQL
        // (setoran punya 20+ kolom, satu batch besar bisa gagal diam-diam).
        foreach (array_chunk($barisPresensi, 500) as $bagian) {
            DB::table('presensis')->insertOrIgnore($bagian);
        }

        foreach (array_chunk($barisSetoran, 200) as $bagian) {
            DB::table('setorans')->insert($bagian);
        }

        $this->command->info('Presensi dibuat : ' . count($barisPresensi));
        $this->command->info('Setoran dibuat  : ' . count($barisSetoran));
    }

    private function setoran(array $s, Carbon $hari, int $tahunAjaranId, array &$posisi): array
    {
        $p      = &$posisi[$s['id']];
        $suratI = $p['surat'] % count(self::SURAT);
        [$namaSurat, $jumlahAyat] = self::SURAT[$suratI];

        $ayatMulai  = mt_rand(1, max(1, $jumlahAyat - 5));
        $ayatSampai = min($jumlahAyat, $ayatMulai + mt_rand(3, 8));

        $halamanMulai  = $p['halaman'];
        $halamanSampai = min(604, $halamanMulai + 1);

        $kategori = ['Hafalan', 'Hafalan', 'Hafalan', 'Murajaah', 'Tilawah'][mt_rand(0, 4)];

        // Maju setengah halaman rata-rata: sebagian setoran mengulang
        // halaman yang sama, seperti kebiasaan setoran sungguhan.
        if (mt_rand(0, 1) === 1) {
            $p['halaman'] = min(604, $p['halaman'] + 1);
        }
        if (mt_rand(1, 6) === 1) {
            $p['surat']++;
        }

        $salahLancar = mt_rand(0, 3);
        $salahTajwid = mt_rand(0, 2);
        $salahLafal  = mt_rand(0, 2);
        $nilai       = max(60, 100 - ($salahLancar * 4) - ($salahTajwid * 3) - ($salahLafal * 3));

        return [
            'tahun_ajaran_id'      => $tahunAjaranId,
            'santri_id'            => $s['id'],
            'kelas_id'             => $s['kelas_id'],
            'guru_id'              => $s['guru_id'],
            'presensi_id'          => null,
            'tanggal'              => $hari->toDateString(),
            'kategori_setoran'     => $kategori,
            'metode'               => "Al-Qur'an",
            'surat_start'          => $namaSurat,
            'ayat_start'           => $ayatMulai,
            'surat_end'            => $namaSurat,
            'ayat_end'             => $ayatSampai,
            'total_kata'           => ($ayatSampai - $ayatMulai + 1) * mt_rand(6, 12),
            'page_start'           => $halamanMulai,
            'page_end'             => $halamanSampai,
            'total_page'           => 1,
            'juz_start'            => 30,
            'juz_end'              => 30,
            'total_juz'            => 0.05,
            'nilai'                => $nilai,
            'kesalahan_kelancaran' => $salahLancar,
            'kesalahan_tajwid'     => $salahTajwid,
            'kesalahan_pelafalan'  => $salahLafal,
            'catatan_guru'         => $nilai >= 90
                ? 'Lancar, pertahankan.'
                : ($nilai >= 75 ? 'Cukup baik, perhatikan panjang pendek.' : "Perlu diulang, tingkatkan muroja'ah."),
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }

    /* ============================================================
     * AKUN
     * Lewat Eloquent karena cast plain_password harus dilalui.
     * ============================================================ */
    private function akun(string $username, string $role): User
    {
        $user = User::firstOrCreate(
            ['name' => $username],
            [
                'password'       => Hash::make(self::PASSWORD),
                'plain_password' => self::PASSWORD,
                // Kolom users.role dan relasi Spatie wajib sinkron:
                // kolom dibaca RoleMiddleware, relasi dibaca permission.
                'role'           => $role,
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }
}
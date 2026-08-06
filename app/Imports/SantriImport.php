<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SantriImport implements ToCollection, WithHeadingRow
{
    private int $successCount = 0;
    private int $failedCount = 0;
    private array $errors = [];
    private array $warnings = [];
    private array $kelasStats = [];

    /** Metode yang valid — quran (juz), iqro & aisar (jilid) */
    private const METODE_VALID = ['quran', 'iqro', 'aisar'];

    /** @var array<string,string> map [namaFileLowercase => pathAsli] hasil ekstrak ZIP */
    private array $photos;

    public function __construct(array $photos = [])
    {
        $this->photos = $photos;
    }

    public function collection(Collection $rows)
    {
        // Urutkan kelas by id supaya "no_kelas" (1-based) konsisten dengan template
        $kelasList = Kelas::orderBy('id')->get()->values();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // baris Excel (header = 1)

            $nis = trim((string) ($row['nis'] ?? ''));

            // Skip baris kosong & baris contoh
            if ($nis === '' && trim((string) ($row['nama'] ?? '')) === '') {
                continue;
            }
            if (stripos($nis, '(Contoh)') !== false || stripos($nis, 'Contoh') !== false) {
                continue;
            }

            $nama       = trim((string) ($row['nama'] ?? ''));
            $gender     = strtoupper(trim((string) ($row['gender'] ?? '')));
            $metode     = strtolower(trim((string) ($row['metode'] ?? 'quran')));
            $metode2    = strtolower(trim((string) ($row['metode_2'] ?? '')));
            $targetJuz  = isset($row['target_juz']) && $row['target_juz'] !== ''
                ? str_replace(',', '.', (string) $row['target_juz'])
                : null;
            $targetJilid = $row['target_jilid'] ?? null;
            $noKelas    = $row['no_kelas'] ?? null;
            $password   = trim((string) ($row['password'] ?? ''));
            $namaFoto   = trim((string) ($row['nama_file_foto'] ?? ''));
            // Data wali — dipakai modul keuangan untuk penagihan.
            $namaWali   = trim((string) ($row['nama_wali'] ?? ''));
            $kontakWali = trim((string) ($row['kontak_wali'] ?? ''));
            $tipe       = trim((string) ($row['tipe'] ?? 'Reguler'));

            // ===== VALIDASI =====
            $rowErrors = [];

            if ($nis === '') {
                $rowErrors[] = "NIS kosong";
            } elseif (Santri::where('nis', $nis)->exists()) {
                $rowErrors[] = "NIS {$nis} sudah terdaftar";
            }

            if ($nama === '') {
                $rowErrors[] = "Nama kosong";
            }

            if (!in_array($gender, ['L', 'P'])) {
                $rowErrors[] = "Gender harus L atau P";
            }

            if (!in_array($metode, self::METODE_VALID)) {
                $rowErrors[] = "Metode harus quran, iqro, atau aisar (ditemukan: {$metode})";
            }

           // Metode 2 (opsional) — hanya boleh kosong atau "quran", dan hanya untuk metode jilid
            if ($metode2 !== '' && $metode2 !== 'quran') {
                $rowErrors[] = "Metode 2 hanya boleh kosong atau 'quran' (ditemukan: {$metode2})";
            }
            if ($metode === 'quran') $metode2 = '';
            $hasQuran2 = $metode2 === 'quran';

            $isJilidBased = in_array($metode, ['iqro', 'aisar'], true);

            if ($isJilidBased) {
                // iqro / aisar → wajib target_jilid
                $maxJilid = (int) config("{$metode}.max_jilid", 6);
                if ($targetJilid === null || $targetJilid === '') {
                    $rowErrors[] = "Target Jilid wajib diisi untuk metode " . ucfirst($metode);
                } elseif (!is_numeric($targetJilid) || (int) $targetJilid < 1 || (int) $targetJilid > $maxJilid) {
                    $rowErrors[] = "Target Jilid harus 1-{$maxJilid} untuk metode " . ucfirst($metode);
                }
                // Target Al-Qur'an sekunder wajib bila metode 2 diisi
                if ($hasQuran2) {
                    if ($targetJuz === null || $targetJuz === '') {
                        $rowErrors[] = "Metode 2 quran wajib mengisi Target Juz (0-30)";
                    } elseif (!is_numeric($targetJuz) || (float) $targetJuz < 0 || (float) $targetJuz > 30) {
                        $rowErrors[] = "Target Juz harus 0-30";
                    }
                }
            } elseif ($metode === 'quran') {
                // quran → wajib target_juz
                if ($targetJuz === null || $targetJuz === '') {
                    $rowErrors[] = "Target Juz wajib diisi untuk metode quran";
                } elseif (!is_numeric($targetJuz) || (float) $targetJuz < 0 || (float) $targetJuz > 30) {
                    $rowErrors[] = "Target Juz harus 0-30";
                }
            }

            // Kelas
            $kelas = null;
            if ($noKelas === null || $noKelas === '' || !is_numeric($noKelas)) {
                $rowErrors[] = "No Kelas kosong / bukan angka";
            } else {
                $kelas = $kelasList->get((int) $noKelas - 1);
                if (!$kelas) {
                    $rowErrors[] = "No Kelas {$noKelas} tidak ditemukan";
                }
            }

            if (strlen($password) < 8) {
                $rowErrors[] = "Password minimal 8 karakter";
            }

            if ($namaWali === '') {
                $rowErrors[] = "Nama Wali kosong";
            }
            if ($kontakWali === '') {
                $rowErrors[] = "Kontak Wali kosong";
            }
            if (!in_array($tipe, ['Reguler', 'Fullday'], true)) {
                $rowErrors[] = "Tipe harus Reguler atau Fullday (ditemukan: {$tipe})";
            }

            // Validasi gender vs restriksi kelas
            if ($kelas && in_array($gender, ['L', 'P'])) {
                $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                if ($kelas->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                    $rowErrors[] = "Kelas {$kelas->name} khusus Ikhwan, santri perempuan tidak bisa masuk";
                }
                if ($kelas->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                    $rowErrors[] = "Kelas {$kelas->name} khusus Akhwat, santri laki-laki tidak bisa masuk";
                }
            }

            // Kapasitas kelas — dihitung ulang tiap baris karena import
            // beruntun bisa mengisi kelas sampai penuh di tengah proses.
            if ($kelas) {
                $terisi = $kelas->santris()->count();
                if ($terisi >= $kelas->kapasitas) {
                    $rowErrors[] = "Kelas {$kelas->name} sudah penuh ({$terisi}/{$kelas->kapasitas})";
                }
            }

            if (!empty($rowErrors)) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNum} ({$nama}): " . implode('; ', $rowErrors);
                continue;
            }

            // ===== SIMPAN =====
            try {
                DB::transaction(function () use ($nis, $nama, $gender, $metode, $metode2, $hasQuran2, $targetJuz, $targetJilid, $kelas, $password, $namaFoto, $namaWali, $kontakWali, $tipe, $rowNum) {                    $name = preg_replace_callback('/\b\w+/', function ($m) {
                        return ucfirst(strtolower($m[0]));
                    }, $nama);

                    $genderFull   = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                    $isJilidBased = in_array($metode, ['iqro', 'aisar'], true);

                    $user = User::create([
                        'name' => $name,
                        'password' => Hash::make($password),
                        'plain_password' => $password,
                        'role' => 'santri',
                    ]);

                    // Foto dari ZIP (opsional)
                    if ($namaFoto !== '') {
                        $key = strtolower(str_replace(' ', '_', $namaFoto));
                        if (isset($this->photos[$key])) {
                            $storedPath = $this->processPhoto($this->photos[$key], 'imp_');
                            if ($storedPath) {
                                $user->profile_photo      = $storedPath;
                                $user->profile_photo_name = $namaFoto;
                                $user->save();
                            } else {
                                $this->warnings[] = "Baris {$rowNum} ({$name}): foto '{$namaFoto}' gagal diproses, santri dibuat tanpa foto";
                            }
                        } else {
                            $this->warnings[] = "Baris {$rowNum} ({$name}): foto '{$namaFoto}' tidak ditemukan di ZIP, santri dibuat tanpa foto";
                        }
                    }

                    Santri::create([
                        'user_id'       => $user->id,
                        'kelas_id'      => $kelas->id,
                        'guru_id'       => $kelas->guru_id,
                        'nis'           => $nis,
                        'name'          => $name,
                        'gender'        => $genderFull,
                        'target_metode'   => $metode,
                        'target_metode_2' => $hasQuran2 ? 'quran' : null,
                        // Metode jilid: juz hanya dipakai bila ada metode 2 = quran
                        'target_juz'      => $isJilidBased ? ($hasQuran2 ? (float) $targetJuz : null) : (float) $targetJuz,
                        'target_jilid'    => $isJilidBased ? (int) $targetJilid : null,
                        // --- data untuk modul keuangan ---
                        'parent_name'    => $namaWali,
                        'parent_contact' => $kontakWali,
                        'student_type'   => $tipe,
                        // Angkatan dari tahun awal TA kelas (2026/2027 → 2026)
                        'angkatan'       => (int) substr((string) $kelas->tahun_ajaran, 0, 4) ?: (int) date('Y'),
                    ]);
                });

                $this->successCount++;
                $this->kelasStats[$kelas->name] = ($this->kelasStats[$kelas->name] ?? 0) + 1;

            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNum} ({$nama}): gagal simpan — " . $e->getMessage();
            }
        }
    }

    /**
     * Salin foto dari ZIP → storage, sekaligus crop persegi & resize (maks 400px).
     * Disamakan dengan SantriController::processProfilePhoto().
     */
    private function processPhoto(string $sourcePath, string $prefix = 'imp_'): ?string
    {
        try {
            $maxSize  = 400;
            $ext      = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
            $path     = 'profile-photos/' . uniqid($prefix) . '.' . $ext;

            Storage::disk('public')->put($path, file_get_contents($sourcePath));
            $fullPath = Storage::disk('public')->path($path);

            $info = getimagesize($fullPath);
            if (!$info) return $path;

            [$width, $height] = $info;

            $src = match ($info['mime']) {
                'image/jpeg' => imagecreatefromjpeg($fullPath),
                'image/png'  => imagecreatefrompng($fullPath),
                default      => null,
            };
            if (!$src) return $path;

            $min  = min($width, $height);
            $srcX = (int) (($width - $min) / 2);
            $srcY = ($height > $width) ? 0 : (int) (($height - $min) / 2);

            $size = min($min, $maxSize);
            $dst  = imagecreatetruecolor($size, $size);

            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $min, $min);
            imagejpeg($dst, $fullPath, 95);

            imagedestroy($src);
            imagedestroy($dst);

            return $path;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getSuccessCount(): int  { return $this->successCount; }
    public function getFailedCount(): int   { return $this->failedCount; }
    public function getErrors(): array      { return $this->errors; }
    public function getWarnings(): array    { return $this->warnings; }
    public function getKelasStats(): array  { return $this->kelasStats; }
}
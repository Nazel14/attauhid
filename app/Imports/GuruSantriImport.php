<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruSantriImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $warnings = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $guruId;
    protected $photos = [];

    public function __construct($guruId, array $photos = [])
    {
        $this->guruId = $guruId;
        $this->photos = $photos;
    }

    public function collection(Collection $rows)
    {
        $kelas = Kelas::where('guru_id', $this->guruId)->first();

        if (!$kelas) {
            throw new \Exception("Guru tidak memiliki kelas yang diampu");
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isExampleRow($row)) {
                \Log::info("Baris {$rowNumber} adalah contoh, di-skip");
                continue;
            }

            if (empty($row['nis']) || empty($row['nama'])) {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $rowNumber, $kelas) {
                    if (empty($row['nis']))      throw new \Exception("NIS tidak boleh kosong");
                    if (empty($row['nama']))     throw new \Exception("Nama tidak boleh kosong");
                    if (empty($row['gender']))   throw new \Exception("Gender tidak boleh kosong");
                    if (empty($row['password'])) throw new \Exception("Password tidak boleh kosong");

                    $gender = strtoupper(trim($row['gender']));
                    if (!in_array($gender, ['L', 'P'])) {
                        throw new \Exception("Gender harus L atau P");
                    }

                    // === METODE & TARGET (quran / iqro / aisar) ===
                    $metode = strtolower(trim($row['metode'] ?? 'quran'));
                    if (!in_array($metode, ['quran', 'iqro', 'aisar'])) {
                        throw new \Exception("Metode harus 'quran', 'iqro', atau 'aisar'");
                    }

                    $isJilidBased = in_array($metode, ['iqro', 'aisar'], true);
                    $targetJuz    = null;
                    $targetJilid  = null;

                    if ($isJilidBased) {
                        if (!isset($row['target_jilid']) || $row['target_jilid'] === '') {
                            throw new \Exception("Metode {$metode} wajib mengisi Target Jilid (1-6)");
                        }
                        $targetJilid = (int) $row['target_jilid'];
                        if ($targetJilid < 1 || $targetJilid > 6) {
                            throw new \Exception("Target Jilid harus angka antara 1-6");
                        }
                    } else { // quran
                        $targetJuz = $row['target_juz'] ?? 1.00;
                        $targetJuz = str_replace(',', '.', (string) $targetJuz);
                        if (!is_numeric($targetJuz) || $targetJuz < 0 || $targetJuz > 30) {
                            throw new \Exception("Target Juz harus angka antara 0-30");
                        }
                    }

                    if (strlen($row['password']) < 4) {
                        throw new \Exception("Password minimal 4 karakter");
                    }

                    if (Santri::where('nis', $row['nis'])->exists()) {
                        throw new \Exception("NIS {$row['nis']} sudah terdaftar");
                    }

                    $name = ucwords(strtolower(trim($row['nama'])));
                    $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';

                    if ($kelas->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                        throw new \Exception("Kelas {$kelas->name} khusus Ikhwan (Laki-laki)");
                    }
                    if ($kelas->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                        throw new \Exception("Kelas {$kelas->name} khusus Akhwat (Perempuan)");
                    }

                    $santriUser = User::create([
                        'name'           => $name,
                        'password'       => Hash::make($row['password']),
                        'plain_password' => $row['password'],
                        'role'           => 'santri',
                    ]);

                    Santri::create([
                        'user_id'       => $santriUser->id,
                        'kelas_id'      => $kelas->id,
                        'guru_id'       => $this->guruId,
                        'nis'           => $row['nis'],
                        'name'          => $name,
                        'gender'        => $genderFull,
                        'target_metode' => $metode,
                        'target_juz'    => $targetJuz,
                        'target_jilid'  => $targetJilid,
                    ]);

                    // ✅ Foto: cocokkan nama_file_foto dengan file di ZIP
                    if (!empty($row['nama_file_foto'])) {
                        $namaFoto = trim($row['nama_file_foto']);
                        $key = strtolower(str_replace(' ', '_', $namaFoto));
                        if (isset($this->photos[$key])) {
                            $path = $this->processPhoto($this->photos[$key]);
                            if ($path) {
                                $santriUser->update([
                                    'profile_photo'      => $path,
                                    'profile_photo_name' => $namaFoto,
                                ]);
                            } else {
                                $this->warnings[] = "Baris {$rowNumber}: foto '{$namaFoto}' gagal diproses, santri tetap dibuat tanpa foto.";
                            }
                        } else {
                            $this->warnings[] = "Baris {$rowNumber}: foto '{$namaFoto}' tidak ditemukan, santri tetap dibuat tanpa foto.";
                        }
                    }

                    $this->successCount++;
                });

            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber} (NIS: {$row['nis']}): " . $e->getMessage();
            }
        }
    }

    protected function isExampleRow($row): bool
    {
        $nis = trim($row['nis'] ?? '');
        if (stripos($nis, '(Contoh)') !== false) return true;
        if (stripos($nis, 'CONTOH:') === 0)      return true;

        $nama = trim($row['nama'] ?? '');
        if (stripos($nama, '(Contoh)') !== false) return true;

        return false;
    }

    private function processPhoto($sourcePath): ?string
    {
        try {
            $maxSize = 400;
            $path = 'profile-photos/' . uniqid('santri_') . '.jpg';
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
            $srcX = (int)(($width - $min) / 2);
            $srcY = ($height > $width) ? 0 : (int)(($height - $min) / 2);
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

    public function getErrors()       { return $this->errors; }
    public function getWarnings()     { return $this->warnings; }
    public function getSuccessCount() { return $this->successCount; }
    public function getFailedCount()  { return $this->failedCount; }
}
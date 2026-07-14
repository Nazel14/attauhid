<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $warnings = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $photos = [];

    public function __construct(array $photos = [])
    {
        $this->photos = $photos; // key: nama file lowercase
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isExampleRow($row)) continue;
            if (empty($row['nama_guru']) || empty($row['username']) || empty($row['password'])) continue;

            try {
                DB::transaction(function () use ($row, $rowNumber) {
                    if (strlen($row['password']) < 8) {
                        throw new \Exception("Password minimal 8 karakter");
                    }

                    $name = $this->smartCapitalizeName(trim($row['nama_guru']));
                    $username = trim($row['username']);
                    $nip = !empty($row['nip']) ? trim($row['nip']) : null;

                    if (User::where('name', $username)->exists()) {
                        throw new \Exception("Username {$username} sudah digunakan");
                    }
                    if ($nip && Guru::where('nip', $nip)->exists()) {
                        throw new \Exception("NIP {$nip} sudah terdaftar");
                    }

                    $user = User::create([
                        'name' => $username,
                        'role' => 'guru',
                        'password' => Hash::make($row['password']),
                        'plain_password' => $row['password'],
                    ]);

                    Guru::create([
                        'name' => $name,
                        'nip' => $nip,
                        'user_id' => $user->id,
                    ]);

                    // ✅ Foto: cocokkan nama_file_foto dengan file yang diupload
                    if (!empty($row['nama_file_foto'])) {
                        $rawFoto = trim($row['nama_file_foto']);
                        $key = strtolower(str_replace(' ', '_', $rawFoto));

                        if (isset($this->photos[$key])) {
                            $path = $this->processPhoto($this->photos[$key]);

                            if ($path) {
                                $user->update([
                                    'profile_photo'            => $path,
                                    // simpan nama file sesuai yang ditulis di Excel,
                                    // supaya cocok saat diekspor kembali
                                    'profile_photo_name'       => $rawFoto,
                                    'profile_photo_updated_at' => now(),
                                ]);
                            } else {
                                $this->warnings[] = "Baris {$rowNumber}: foto '{$rawFoto}' gagal diproses, guru tetap dibuat tanpa foto.";
                            }
                        } else {
                            $this->warnings[] = "Baris {$rowNumber}: foto '{$rawFoto}' tidak ditemukan, guru tetap dibuat tanpa foto.";
                        }
                    }

                    $this->successCount++;
                });
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    protected function isExampleRow($row): bool
    {
        $exampleNames = [
            'Ahmad Zainudin, S.Pd (Contoh)', 'Siti Nurhaliza, M.Pd (Contoh)',
            'ahmad zainudin, s.pd (contoh)', 'siti nurhaliza, m.pd (contoh)',
        ];
        return in_array(trim($row['nama_guru'] ?? ''), $exampleNames);
    }

    private function processPhoto($sourcePath): ?string
    {
        try {
            $maxSize = 400;
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
            $path = 'profile-photos/' . uniqid('guru_') . '.' . $ext;
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

    private function smartCapitalizeName($text)
    {
        $text = trim($text);
        if (strlen($text) > 0) $text = strtoupper(substr($text, 0, 1)) . substr($text, 1);
        return $text;
    }

    public function getErrors() { return $this->errors; }
    public function getWarnings() { return $this->warnings; }
    public function getSuccessCount() { return $this->successCount; }
    public function getFailedCount() { return $this->failedCount; }
}
<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruBulkUpdateImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $warnings = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $gurusByNoUrut = [];
    protected $photos = [];

    public function __construct(array $photos = [])
    {
        $this->photos = $photos;
        // Urutan harus sama persis dengan exportForEdit, karena no_urut
        // di Excel dipetakan berdasarkan posisi.
        // Urutan harus sama persis dengan exportForEdit, karena no_urut
        // di Excel dipetakan berdasarkan posisi.
        $guruList = Guru::with('user')->orderBy('name', 'asc')->get();
        $noUrut = 1;
        foreach ($guruList as $guru) {
            $this->gurusByNoUrut[$noUrut] = $guru;
            $noUrut++;
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            if (empty($row['no_urut'])) continue;

            try {
                DB::transaction(function () use ($row, $rowNumber) {
                    if (!isset($this->gurusByNoUrut[$row['no_urut']])) {
                        throw new \Exception("Guru dengan No Urut {$row['no_urut']} tidak ditemukan");
                    }
                    $guru = $this->gurusByNoUrut[$row['no_urut']];

                    $updateData = [];
                    if (!empty($row['nama_guru'])) {
                        $updateData['name'] = $this->smartCapitalizeName(trim($row['nama_guru']));
                    }
                    if (isset($row['nip'])) {
                        $newNip = !empty($row['nip']) ? trim($row['nip']) : null;
                        if ($newNip) {
                            $exists = Guru::where('nip', $newNip)->where('id', '!=', $guru->id)->exists();
                            if ($exists) throw new \Exception("NIP {$newNip} sudah digunakan guru lain");
                        }
                        $updateData['nip'] = $newNip;
                    }
                    // Jabatan sengaja tidak diubah lewat Excel raport —
                    // itu wilayah panel keuangan.
                    if (!empty($updateData)) $guru->update($updateData);

                    if (!empty($row['username']) && $guru->user) {
                        $newUsername = trim($row['username']);
                        $exists = User::where('name', $newUsername)->where('id', '!=', $guru->user->id)->exists();
                        if ($exists) throw new \Exception("Username {$newUsername} sudah digunakan");
                        $guru->user->update(['name' => $newUsername]);
                    }

                    // ✅ Foto
                    if (!empty($row['nama_file_foto']) && $guru->user) {
                        $rawFoto = trim($row['nama_file_foto']);

                        // Penanda hapus foto: tulis "HAPUS" atau "-" di kolom nama_file_foto
                        if (in_array(strtoupper($rawFoto), ['HAPUS', '-', 'DELETE'])) {
                            if ($guru->user->profile_photo && Storage::disk('public')->exists($guru->user->profile_photo)) {
                                Storage::disk('public')->delete($guru->user->profile_photo);
                            }
                            $guru->user->update([
                                'profile_photo'      => null,
                                'profile_photo_name' => null,
                            ]);
                        } elseif ($rawFoto !== ($guru->user->profile_photo_name ?? '')) {
                            // hanya proses kalau nama foto BERUBAH dari yang tersimpan
                            $key = strtolower(str_replace(' ', '_', $rawFoto));

                            if (isset($this->photos[$key])) {
                                // proses foto baru DULU — kalau gagal, foto lama tetap aman
                                $path = $this->processPhoto($this->photos[$key]);

                                if ($path) {
                                    if ($guru->user->profile_photo && Storage::disk('public')->exists($guru->user->profile_photo)) {
                                        Storage::disk('public')->delete($guru->user->profile_photo);
                                    }
                                    $guru->user->update([
                                        'profile_photo'            => $path,
                                        'profile_photo_name'       => $rawFoto,
                                        'profile_photo_updated_at' => now(),
                                    ]);
                                } else {
                                    $this->warnings[] = "Baris {$rowNumber}: foto '{$rawFoto}' gagal diproses, foto lama dipertahankan.";
                                }
                            } else {
                                $this->warnings[] = "Baris {$rowNumber}: foto '{$rawFoto}' tidak ditemukan, foto guru tidak diubah.";
                            }
                        }
                    }

                    $this->successCount++;
                });
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber} (No Urut: {$row['no_urut']}): " . $e->getMessage();
            }
        }
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
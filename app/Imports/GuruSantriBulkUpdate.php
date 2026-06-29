<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruSantriBulkUpdate implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $warnings = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $guruId;
    protected $totalSantri = 0;
    protected $santrisByNoUrut = [];
    protected $photos = [];

    public function __construct($guruId, array $photos = [])
    {
        $this->guruId = $guruId;
        $this->photos = $photos;
        $this->totalSantri = Santri::where('guru_id', $guruId)->count();

        // Bangun peta no_urut → santri SEKALI di awal, urutan SAMA seperti ekspor.
        $santriList = Santri::where('guru_id', $guruId)
            ->orderBy('name', 'asc')
            ->get();
        $noUrut = 1;
        foreach ($santriList as $s) {
            $this->santrisByNoUrut[$noUrut] = $s;
            $noUrut++;
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['no_urut'])) {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $rowNumber) {
                    $noUrut = (int) $row['no_urut'];
                    if (!isset($this->santrisByNoUrut[$noUrut])) {
                        throw new \Exception("No Urut {$noUrut} tidak ditemukan di kelas Anda.");
                    }

                    $santri = $this->santrisByNoUrut[$noUrut];
                    $updateData = [];

                    // NIS boleh diubah — cek keunikan terhadap santri lain
                    if (isset($row['nis']) && trim((string) $row['nis']) !== '') {
                        $nisBaru = trim((string) $row['nis']);
                        if ($nisBaru !== (string) $santri->nis) {
                            $nisDipakai = Santri::where('nis', $nisBaru)
                                ->where('id', '!=', $santri->id)
                                ->exists();
                            if ($nisDipakai) {
                                throw new \Exception("NIS {$nisBaru} sudah dipakai santri lain");
                            }
                            $updateData['nis'] = $nisBaru;
                        }
                    }

                    if (!empty($row['nama'])) {
                        $updateData['name'] = ucwords(strtolower(trim($row['nama'])));
                    }

                    if (!empty($row['gender'])) {
                        $gender = strtoupper(trim($row['gender']));
                        if (!in_array($gender, ['L', 'P'])) {
                            throw new \Exception("Gender harus L atau P");
                        }
                        $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';

                        $kelas = $santri->kelas;
                        if ($kelas) {
                            if ($kelas->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                                throw new \Exception("Kelas {$kelas->name} khusus Ikhwan (Laki-laki)");
                            }
                            if ($kelas->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                                throw new \Exception("Kelas {$kelas->name} khusus Akhwat (Perempuan)");
                            }
                        }
                        $updateData['gender'] = $genderFull;
                    }

                    // === METODE & TARGET (quran / iqro) ===
                    $metodeInput = !empty($row['metode']) ? strtolower(trim($row['metode'])) : null;
                    if ($metodeInput !== null && !in_array($metodeInput, ['quran', 'iqro'])) {
                        throw new \Exception("Metode harus 'quran' atau 'iqro'");
                    }

                    $metodeEfektif = $metodeInput ?? ($santri->target_metode?->value ?? 'quran');

                    if ($metodeInput !== null) {
                        $updateData['target_metode'] = $metodeInput;
                    }

                    if ($metodeEfektif === 'iqro') {
                        if (isset($row['target_jilid']) && $row['target_jilid'] !== '') {
                            $jilid = (int) $row['target_jilid'];
                            if ($jilid < 1 || $jilid > 6) {
                                throw new \Exception("Target Jilid harus angka antara 1-6");
                            }
                            $updateData['target_jilid'] = $jilid;
                        } elseif ($metodeInput === 'iqro') {
                            throw new \Exception("Metode iqro wajib mengisi Target Jilid (1-6)");
                        }
                        if ($metodeInput === 'iqro') {
                            $updateData['target_juz'] = null;
                        }
                    } else { // quran
                        if (isset($row['target_juz']) && $row['target_juz'] !== '') {
                            if (!is_numeric($row['target_juz']) || $row['target_juz'] < 0 || $row['target_juz'] > 30) {
                                throw new \Exception("Target Juz harus angka antara 0-30");
                            }
                            $updateData['target_juz'] = $row['target_juz'];
                        } elseif ($metodeInput === 'quran') {
                            throw new \Exception("Metode quran wajib mengisi Target Juz (0-30)");
                        }
                        if ($metodeInput === 'quran') {
                            $updateData['target_jilid'] = null;
                        }
                    }

                    // Update santri jika ada perubahan
                    if (!empty($updateData)) {
                        $santri->update($updateData);

                        if (isset($updateData['name']) && $santri->user) {
                            $santri->user->update(['name' => $updateData['name']]);
                        }
                    }

                    // ✅ Foto: ganti / hapus
                    if ($santri->user && !empty($row['nama_file_foto'])) {
                        $rawFoto = trim((string) $row['nama_file_foto']);
                        $fotoUpper = strtoupper(preg_replace('/\s+/', '', $rawFoto));
                        if (in_array($fotoUpper, ['HAPUS', '-', 'DELETE'])) {
                            if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                                Storage::disk('public')->delete($santri->user->profile_photo);
                            }
                            $santri->user->update(['profile_photo' => null]);
                        } else {
                            $key = strtolower(str_replace(' ', '_', $rawFoto));
                            if (isset($this->photos[$key])) {
                                if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                                    Storage::disk('public')->delete($santri->user->profile_photo);
                                }
                                $path = $this->processPhoto($this->photos[$key]);
                                if ($path) $santri->user->update(['profile_photo' => $path]);
                            } else {
                                $this->warnings[] = "Baris {$rowNumber}: foto '{$rawFoto}' tidak ditemukan, foto santri tidak diubah.";
                            }
                        }
                    }

                    $this->successCount++;
                });

            } catch (\Exception $e) {
                $this->failedCount++;
                $nisInfo = isset($row['nis']) ? $row['nis'] : 'N/A';
                $this->errors[] = "Baris {$rowNumber} (No Urut: {$row['no_urut']}, NIS: {$nisInfo}): " . $e->getMessage();
            }
        }
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
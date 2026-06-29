<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class SantriBulkUpdateImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $kelasId;
    protected $santrisByNoUrut = [];
    protected $warnings = [];
    protected $photos = [];

    public function __construct($kelasId, array $photos = [])
    {
        $this->kelasId = $kelasId;
        $this->photos = $photos;

        // Bangun peta no_urut → santri SEKALI di awal, urutan SAMA seperti
        // exportForEdit (where kelas_id + orderBy name asc). Peta ini "beku":
        // edit nama/NIS di tengah loop tidak menggeser posisi baris lain.
        $santriList = Santri::where('kelas_id', $kelasId)
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
                    // Cocokkan santri lewat NO URUT (kolom yang dikunci, tidak boleh
                    // diubah). Peta no_urut dibangun beku di constructor → posisi tidak
                    // bergeser walau nama/NIS diedit di baris lain.
                    $noUrut = (int) $row['no_urut'];
                    if (!isset($this->santrisByNoUrut[$noUrut])) {
                        throw new \Exception("No Urut {$noUrut} tidak ditemukan di kelas ini.");
                    }

                    $santri = $this->santrisByNoUrut[$noUrut];
                    
                    
                    
                    // Siapkan data update
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
                        $updateData['gender'] = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                    }
                    
                    // === METODE & TARGET (quran / iqro) ===
                    $metodeInput = !empty($row['metode']) ? strtolower(trim($row['metode'])) : null;
                    if ($metodeInput !== null && !in_array($metodeInput, ['quran', 'iqro'])) {
                        throw new \Exception("Metode harus 'quran' atau 'iqro'");
                    }

                    // Metode efektif: input baru, atau metode lama santri
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
                        // Pindah ke iqro → kosongkan target_juz
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
                        // Pindah ke quran → kosongkan target_jilid
                        if ($metodeInput === 'quran') {
                            $updateData['target_jilid'] = null;
                        }
                    }
                    
                    // Validasi gender restriction
                    $genderFull = $updateData['gender'] ?? $santri->gender;
                    
                    if (isset($row['no_kelas_baru']) && trim((string) $row['no_kelas_baru']) !== '') {
                        $noKelasBaru = (int) trim((string) $row['no_kelas_baru']);
                        $kelasList = \App\Models\Kelas::with('guru')->orderBy('id')->get();
                        
                        if ($noKelasBaru < 1 || $noKelasBaru > $kelasList->count()) {
                            throw new \Exception("No Kelas Baru {$noKelasBaru} tidak valid");
                        }
                        
                        $kelasBaru = $kelasList->get($noKelasBaru - 1);
                        if (!$kelasBaru) {
                            throw new \Exception("Kelas baru tidak ditemukan");
                        }
                        
                        if ($kelasBaru->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                            throw new \Exception("Kelas {$kelasBaru->name} khusus Ikhwan (Laki-laki)");
                        }
                        
                        if ($kelasBaru->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                            throw new \Exception("Kelas {$kelasBaru->name} khusus Akhwat (Perempuan)");
                        }
                        
                        $updateData['kelas_id'] = $kelasBaru->id;
                        $updateData['guru_id'] = $kelasBaru->guru_id;
                    }
                    else if (isset($updateData['gender']) && $updateData['gender'] !== $santri->gender) {
                        $kelasSekarang = $santri->kelas;
                        
                        if ($kelasSekarang->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                            throw new \Exception("Tidak bisa ubah gender. Kelas {$kelasSekarang->name} khusus Ikhwan");
                        }
                        
                        if ($kelasSekarang->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                            throw new \Exception("Tidak bisa ubah gender. Kelas {$kelasSekarang->name} khusus Akhwat");
                        }
                    }
                    
                    // Update santri
                    if (!empty($updateData)) {
                        $santri->update($updateData);
                    }
                    // UPDATE USER
                    if ($santri->user) {
                        $userUpdateData = [];
                        
                        if (!empty($row['nama'])) {
                            $userUpdateData['name'] = ucwords(strtolower(trim($row['nama'])));
                        }
                        
                        if (!empty($row['password_baru'])) {
                            if (strlen($row['password_baru']) < 8) {
                                throw new \Exception("Password minimal 8 karakter");
                            }
                            $userUpdateData['password'] = Hash::make($row['password_baru']);
                            $userUpdateData['plain_password'] = $row['password_baru'];
                        }
                        
                        if (!empty($userUpdateData)) {
                            $santri->user->update($userUpdateData);
                        }

                        // ✅ Foto: ganti / hapus
                        if (!empty($row['nama_file_foto'])) {
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

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailedCount()
    {
        return $this->failedCount;
    }
}
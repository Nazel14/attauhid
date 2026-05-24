<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruSantriImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $guruId;

    public function __construct($guruId)
    {
        $this->guruId = $guruId;
    }

    public function collection(Collection $rows)
    {
        // Cari kelas yang diampu guru ini
        $kelas = Kelas::where('guru_id', $this->guruId)->first();
        
        if (!$kelas) {
            throw new \Exception("Guru tidak memiliki kelas yang diampu");
        }
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            
            // Skip baris contoh
            if ($this->isExampleRow($row)) {
                \Log::info("Baris {$rowNumber} adalah contoh, di-skip");
                continue;
            }
            
            if (empty($row['nis']) || empty($row['nama'])) {
                continue;
            }
            
            try {
                DB::transaction(function () use ($row, $rowNumber, $kelas) {
                    // Validasi dasar
                    if (empty($row['nis'])) throw new \Exception("NIS tidak boleh kosong");
                    if (empty($row['nama'])) throw new \Exception("Nama tidak boleh kosong");
                    if (empty($row['gender'])) throw new \Exception("Gender tidak boleh kosong");
                    if (empty($row['password'])) throw new \Exception("Password tidak boleh kosong");
                    
                    $gender = strtoupper(trim($row['gender']));
                    if (!in_array($gender, ['L', 'P'])) {
                        throw new \Exception("Gender harus L atau P");
                    }
                    
                    $targetJuz = $row['target_juz'] ?? 1.00;
                    if (!is_numeric($targetJuz) || $targetJuz < 0 || $targetJuz > 30) {
                        throw new \Exception("Target Juz harus angka antara 0-30");
                    }
                    
                    if (strlen($row['password']) < 4) {
                        throw new \Exception("Password minimal 4 karakter");
                    }
                    
                    if (Santri::where('nis', $row['nis'])->exists()) {
                        throw new \Exception("NIS {$row['nis']} sudah terdaftar");
                    }
                    
                    $name = ucwords(strtolower(trim($row['nama'])));
                    $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                    
                    // Validasi gender restriction
                    if ($kelas->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                        throw new \Exception("Kelas {$kelas->name} khusus Ikhwan (Laki-laki)");
                    }
                    
                    if ($kelas->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                        throw new \Exception("Kelas {$kelas->name} khusus Akhwat (Perempuan)");
                    }
                    
                    $santriUser = User::create([
                        'name' => $name,
                        'password' => Hash::make($row['password']),
                        'plain_password' => $row['password'],
                        'role' => 'santri',
                    ]);
                    
                    Santri::create([
                        'user_id' => $santriUser->id,
                        'kelas_id' => $kelas->id,
                        'guru_id' => $this->guruId,
                        'nis' => $row['nis'],
                        'name' => $name,
                        'gender' => $genderFull,
                        'target_juz' => $targetJuz,
                    ]);
                    
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
        
        if (stripos($nis, '(Contoh)') !== false) {
            return true;
        }
        
        if (stripos($nis, 'CONTOH:') === 0) {
            return true;
        }
        
        $nama = trim($row['nama'] ?? '');
        if (stripos($nama, '(Contoh)') !== false) {
            return true;
        }
        
        return false;
    }

    public function getErrors()
    {
        return $this->errors;
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
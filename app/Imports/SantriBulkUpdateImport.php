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
    

    public function __construct($kelasId)
{
    $this->kelasId = $kelasId;
    
    // Mapping santri berdasarkan urutan (sorted by name asc)
    $santris = Santri::where('kelas_id', $kelasId)
        ->orderBy('name', 'asc')
        ->get();
    
    $noUrut = 1;
    foreach ($santris as $santri) {
        $this->santrisByNoUrut[$noUrut] = $santri;
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
                    if (empty($row['no_urut'])) {
                        throw new \Exception("No Urut tidak boleh kosong");
                    }
                    
                    if (!isset($this->santrisByNoUrut[$row['no_urut']])) {
                        throw new \Exception("Santri dengan No Urut {$row['no_urut']} tidak ditemukan di kelas ini");
                    }
                    
                    $santri = $this->santrisByNoUrut[$row['no_urut']];
                    
                    if (!empty($row['nis']) && $row['nis'] != $santri->nis) {
                        $existingNis = Santri::where('nis', $row['nis'])
                            ->where('id', '!=', $santri->id)
                            ->first();
                        
                        if ($existingNis) {
                            throw new \Exception("NIS {$row['nis']} sudah digunakan santri lain");
                        }
                    }
                    
                    if (!empty($row['gender'])) {
                        $gender = strtoupper(trim($row['gender']));
                        if (!in_array($gender, ['L', 'P'])) {
                            throw new \Exception("Gender harus L atau P");
                        }
                    }
                    
                    if (isset($row['target_juz']) && $row['target_juz'] !== '') {
                        if (!is_numeric($row['target_juz']) || $row['target_juz'] < 0 || $row['target_juz'] > 30) {
                            throw new \Exception("Target Juz harus angka antara 0-30");
                        }
                    }
                    
                    // Siapkan data update
                    $updateData = [];
                    
                    if (!empty($row['nis'])) {
                        $updateData['nis'] = trim($row['nis']);
                    }
                    
                    if (!empty($row['nama'])) {
                        $updateData['name'] = ucwords(strtolower(trim($row['nama'])));
                    }
                    
                    if (!empty($row['gender'])) {
                        $gender = strtoupper(trim($row['gender']));
                        $updateData['gender'] = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                    }
                    
                    if (isset($row['target_juz']) && $row['target_juz'] !== '') {
                        $updateData['target_juz'] = $row['target_juz'];
                    }
                    
                    // Validasi gender restriction
                    $genderFull = $updateData['gender'] ?? $santri->gender;
                    
                    if (!empty($row['no_kelas_baru'])) {
                        $noKelasBaru = (int) $row['no_kelas_baru'];
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
                    }
                
                    
                    $this->successCount++;
                });
                
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber} (No Urut: {$row['no_urut']}): " . $e->getMessage();
            }
        }
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
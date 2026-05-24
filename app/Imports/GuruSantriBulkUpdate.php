<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruSantriBulkUpdate implements ToCollection, WithHeadingRow, SkipsEmptyRows
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
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            
            // Skip jika no_urut kosong
            if (empty($row['no_urut'])) {
                continue;
            }
            
            try {
                DB::transaction(function () use ($row, $rowNumber) {
                    // Cari santri berdasarkan no_urut dan guru_id
                    $santri = Santri::where('guru_id', $this->guruId)
                        ->orderBy('name', 'asc')
                        ->skip($row['no_urut'] - 1)
                        ->first();
                    
                    if (!$santri) {
                        throw new \Exception("Santri dengan no urut {$row['no_urut']} tidak ditemukan");
                    }
                    
                    // Persiapkan data update
                    $updateData = [];
                    
                    // Update NIS jika diisi
                    if (!empty($row['nis']) && $row['nis'] !== $santri->nis) {
                        // Cek apakah NIS baru sudah dipakai
                        if (Santri::where('nis', $row['nis'])->where('id', '!=', $santri->id)->exists()) {
                            throw new \Exception("NIS {$row['nis']} sudah digunakan santri lain");
                        }
                        $updateData['nis'] = $row['nis'];
                    }
                    
                    // Update Nama jika diisi
                    if (!empty($row['nama'])) {
                        $name = ucwords(strtolower(trim($row['nama'])));
                        $updateData['name'] = $name;
                    }
                    
                    // Update Gender jika diisi
                    if (!empty($row['gender'])) {
                        $gender = strtoupper(trim($row['gender']));
                        if (!in_array($gender, ['L', 'P'])) {
                            throw new \Exception("Gender harus L atau P");
                        }
                        
                        $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                        
                        // Validasi gender restriction kelas
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
                    
                    // Update Target Juz jika diisi
                    if (isset($row['target_juz']) && $row['target_juz'] !== '') {
                        $targetJuz = $row['target_juz'];
                        if (!is_numeric($targetJuz) || $targetJuz < 0 || $targetJuz > 30) {
                            throw new \Exception("Target Juz harus angka antara 0-30");
                        }
                        $updateData['target_juz'] = $targetJuz;
                    }
                    
                    // Update data santri jika ada perubahan
                    if (!empty($updateData)) {
                        $santri->update($updateData);
                        
                        // Update nama di tabel users jika nama berubah
                        if (isset($updateData['name']) && $santri->user) {
                            $santri->user->update(['name' => $updateData['name']]);
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
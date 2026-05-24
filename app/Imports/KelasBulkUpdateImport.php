<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Guru;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class KelasBulkUpdateImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $kelasByNoUrut = [];

    public function __construct()
    {
        // Ambil semua kelas dan index berdasarkan urutan (A-Z by name)
        $kelasList = Kelas::orderBy('name', 'asc')->get();
        $noUrut = 1;
        foreach ($kelasList as $kelas) {
            $this->kelasByNoUrut[$noUrut] = $kelas;
            $noUrut++;
        }
    }

   public function collection(Collection $rows)
{
    $guruList = Guru::orderBy('id')->get();
    $noGuruToId = [];
    foreach ($guruList as $index => $guru) {
        $noGuruToId[$index + 1] = $guru->id;
    }
    
    // Mapping no_gender ke gender_restriction
    $noGenderToGender = [
        1 => 'campuran',
        2 => 'ikhwan',
        3 => 'akhwat'
    ];
    
    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2;
        
        if (empty($row['no_urut'])) {
            continue;
        }
        
        try {
            DB::transaction(function () use ($row, $rowNumber, $noGuruToId, $noGenderToGender) {
                if (empty($row['no_urut'])) {
                    throw new \Exception("No Urut tidak boleh kosong");
            }
            
            if (!isset($this->kelasByNoUrut[$row['no_urut']])) {
                throw new \Exception("Kelas dengan No Urut {$row['no_urut']} tidak ditemukan");
            }
            
            $kelas = $this->kelasByNoUrut[$row['no_urut']];
            $updateData = [];
            
            if (!empty($row['nama_kelas'])) {
                $newName = $this->smartCapitalize(trim($row['nama_kelas']));
                $tahunAjaran = !empty($row['tahun_ajaran']) ? trim($row['tahun_ajaran']) : $kelas->tahun_ajaran;
                
                $exists = Kelas::where('name', $newName)
                            ->where('tahun_ajaran', $tahunAjaran)
                            ->where('id', '!=', $kelas->id)
                            ->exists();
                
                if ($exists) {
                    throw new \Exception("Kelas {$newName} dengan tahun ajaran {$tahunAjaran} sudah ada");
                }
                
                $updateData['name'] = $newName;
            }
            
            if (!empty($row['tahun_ajaran'])) {
                $updateData['tahun_ajaran'] = trim($row['tahun_ajaran']);
            }
            
            if (!empty($row['no_guru'])) {
                $noGuru = $row['no_guru'];
                
                if (!isset($noGuruToId[$noGuru])) {
                    throw new \Exception("No Guru {$noGuru} tidak ditemukan dalam daftar guru");
                }
                
                $updateData['guru_id'] = $noGuruToId[$noGuru];
            }
            
            // Validasi dan update no_gender
            if (!empty($row['no_gender'])) {
                $noGender = (int) $row['no_gender'];
                
                if (!isset($noGenderToGender[$noGender])) {
                    throw new \Exception("No Gender tidak valid. Harus: 1 (Campuran), 2 (Ikhwan), atau 3 (Akhwat)");
                }
                
                $updateData['gender_restriction'] = $noGenderToGender[$noGender];
            }
            
            if (!empty($updateData)) {
                $kelas->update($updateData);
            }
            
            $this->successCount++;
        });
        
    } catch (\Exception $e) {
        $this->failedCount++;
        $this->errors[] = "Baris {$rowNumber} (No Urut: {$row['no_urut']}): " . $e->getMessage();
    }
}
    
    private function smartCapitalize($text)
    {
        $text = trim($text);
        
        $result = preg_replace_callback('/(\d+|[a-zA-Z]+)/', function($matches) {
            $word = $matches[1];
            if (!is_numeric($word)) {
                return ucfirst(strtolower($word));
            }
            return $word;
        }, $text);
        
        return $result;
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
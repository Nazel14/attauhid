<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Guru;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class KelasImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;

   public function collection(Collection $rows)
{
    $guruList = Guru::orderBy('id')->get();
    
    // Mapping no_gender ke gender_restriction
    $noGenderToGender = [
        1 => 'campuran',
        2 => 'ikhwan',
        3 => 'akhwat'
    ];
    
    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2;
        
        if ($this->isExampleRow($row)) {
            continue;
        }
        
        if (empty($row['nama_kelas']) || empty($row['tahun_ajaran']) || empty($row['no_guru']) || empty($row['no_gender'])) {
            continue;
        }
        
        try {
            DB::transaction(function () use ($row, $rowNumber, $guruList, $noGenderToGender) {
                if (empty($row['nama_kelas'])) {
                    throw new \Exception("Nama kelas tidak boleh kosong");
                }
                
                if (empty($row['tahun_ajaran'])) {
                    throw new \Exception("Tahun ajaran tidak boleh kosong");
                }
                
                if (empty($row['no_guru'])) {
                    throw new \Exception("Nomor guru tidak boleh kosong");
                }
                
                if (empty($row['no_gender'])) {
                    throw new \Exception("Nomor gender tidak boleh kosong");
                }
                
                $noGuru = (int) $row['no_guru'];
                
                if ($noGuru < 1 || $noGuru > $guruList->count()) {
                    throw new \Exception("Nomor Guru {$noGuru} tidak valid. Tersedia 1-{$guruList->count()}");
                }
                
                $guru = $guruList->get($noGuru - 1);
                
                if (!$guru) {
                    throw new \Exception("Guru dengan nomor urut {$noGuru} tidak ditemukan");
                }
                
                // Validasi no_gender
                $noGender = (int) $row['no_gender'];
                if (!isset($noGenderToGender[$noGender])) {
                    throw new \Exception("No Gender tidak valid. Harus: 1 (Campuran), 2 (Ikhwan), atau 3 (Akhwat)");
                }
                
                $gender = $noGenderToGender[$noGender];
                
                $name = $this->smartCapitalize(trim($row['nama_kelas']));
                $tahunAjaran = trim($row['tahun_ajaran']);
                
                $exists = Kelas::where('name', $name)
                               ->where('tahun_ajaran', $tahunAjaran)
                               ->exists();
                
                if ($exists) {
                    throw new \Exception("Kelas {$name} dengan tahun ajaran {$tahunAjaran} sudah ada");
                }
                
                Kelas::create([
                    'name' => $name,
                    'tahun_ajaran' => $tahunAjaran,
                    'guru_id' => $guru->id,
                    'gender_restriction' => $gender,
                ]);
                
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
        $exampleNames = ['Kelas 7A (Contoh)', 'Kelas 8B (Contoh)', 'kelas 7a (contoh)', 'kelas 8b (contoh)'];
        if (in_array(trim($row['nama_kelas'] ?? ''), $exampleNames)) {
            return true;
        }
        
        return false;
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
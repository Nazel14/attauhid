<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            
            // Skip baris contoh
            if ($this->isExampleRow($row)) {
                continue;
            }
            
            // Skip jika kolom penting kosong
            if (empty($row['nama_guru']) || empty($row['username']) || empty($row['password'])) {
                continue;
            }
            
            try {
                DB::transaction(function () use ($row, $rowNumber) {
                    // Validasi field required
                    if (empty($row['nama_guru'])) {
                        throw new \Exception("Nama guru tidak boleh kosong");
                    }
                    
                    if (empty($row['username'])) {
                        throw new \Exception("Username tidak boleh kosong");
                    }
                    
                    if (empty($row['password'])) {
                        throw new \Exception("Password tidak boleh kosong");
                    }
                    
                    // Validasi password minimal 8 karakter
                    if (strlen($row['password']) < 8) {
                        throw new \Exception("Password minimal 8 karakter");
                    }
                    
                    // Smart capitalize nama - huruf pertama kapital, sisanya tetap
                    $name = $this->smartCapitalizeName(trim($row['nama_guru']));
                    $username = trim($row['username']);
                    $nip = !empty($row['nip']) ? trim($row['nip']) : null;
                    
                    // Cek duplikat username
                    if (User::where('name', $username)->exists()) {
                        throw new \Exception("Username {$username} sudah digunakan");
                    }
                    
                    // Cek duplikat NIP jika ada
                    if ($nip && Guru::where('nip', $nip)->exists()) {
                        throw new \Exception("NIP {$nip} sudah terdaftar");
                    }
                    
                    // Buat user
                    $user = User::create([
                        'name' => $username,
                        'role' => 'guru',
                        'password' => Hash::make($row['password']),
                        'plain_password' => $row['password'],
                    ]);
                    
                    // Buat guru
                    Guru::create([
                        'name' => $name,
                        'nip' => $nip,
                        'user_id' => $user->id,
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
        $exampleNames = [
            'Ahmad Zainudin, S.Pd (Contoh)', 
            'Siti Nurhaliza, M.Pd (Contoh)',
            'ahmad zainudin, s.pd (contoh)',
            'siti nurhaliza, m.pd (contoh)'
        ];
        
        if (in_array(trim($row['nama_guru'] ?? ''), $exampleNames)) {
            return true;
        }
        
        return false;
    }
    
    private function smartCapitalizeName($text)
    {
        $text = trim($text);
        
        // Capitalize huruf pertama saja
        if (strlen($text) > 0) {
            $text = strtoupper(substr($text, 0, 1)) . substr($text, 1);
        }
        
        return $text;
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
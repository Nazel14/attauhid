<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuruBulkUpdateImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $gurusByNoUrut = [];

    public function __construct()
    {
        // Ambil semua guru dan index berdasarkan urutan (A-Z by name)
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
            
            // SKIP baris jika no_urut kosong
            if (empty($row['no_urut'])) {
                continue;
            }
            
            try {
                DB::transaction(function () use ($row, $rowNumber) {
                    // Validasi NO URUT ada
                    if (empty($row['no_urut'])) {
                        throw new \Exception("No Urut tidak boleh kosong");
                    }
                    
                    // Cari guru berdasarkan NO URUT
                    if (!isset($this->gurusByNoUrut[$row['no_urut']])) {
                        throw new \Exception("Guru dengan No Urut {$row['no_urut']} tidak ditemukan");
                    }
                    
                    $guru = $this->gurusByNoUrut[$row['no_urut']];
                    
                    // Siapkan data update
                    $updateData = [];
                    
                    // Update nama guru jika diubah
                    if (!empty($row['nama_guru'])) {
                        $newName = $this->smartCapitalizeName(trim($row['nama_guru']));
                        $updateData['name'] = $newName;
                    }
                    
                    // Update NIP jika diubah
                    if (isset($row['nip'])) {
                        $newNip = !empty($row['nip']) ? trim($row['nip']) : null;
                        
                        // Cek duplikat NIP (kecuali guru saat ini)
                        if ($newNip) {
                            $exists = Guru::where('nip', $newNip)
                                        ->where('id', '!=', $guru->id)
                                        ->exists();
                            
                            if ($exists) {
                                throw new \Exception("NIP {$newNip} sudah digunakan guru lain");
                            }
                        }
                        
                        $updateData['nip'] = $newNip;
                    }
                    
                    // Update guru jika ada perubahan
                    if (!empty($updateData)) {
                        $guru->update($updateData);
                    }
                    
                    // Update username jika diubah dan user ada
                    if (!empty($row['username']) && $guru->user) {
                        $newUsername = trim($row['username']);
                        
                        // Cek duplikat username (kecuali user saat ini)
                        $exists = User::where('name', $newUsername)
                                    ->where('id', '!=', $guru->user->id)
                                    ->exists();
                        
                        if ($exists) {
                            throw new \Exception("Username {$newUsername} sudah digunakan");
                        }
                        
                        $guru->user->update(['name' => $newUsername]);
                    }
                    
                    $this->successCount++;
                });
                
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber} (No Urut: {$row['no_urut']}): " . $e->getMessage();
            }
        }
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
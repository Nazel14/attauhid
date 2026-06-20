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
    protected $totalSantri = 0;
    protected $santrisByNoUrut = [];

    public function __construct($guruId)
    {
        $this->guruId = $guruId;
        $this->totalSantri = Santri::where('guru_id', $guruId)->count();

        // Bangun peta no_urut → santri SEKALI di awal, urutan SAMA seperti ekspor
        // (where guru_id + orderBy name asc). Peta ini "beku": edit nama/NIS di
        // tengah loop tidak menggeser posisi baris lain.
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
            
            // Skip jika no_urut kosong
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
                        throw new \Exception("No Urut {$noUrut} tidak ditemukan di kelas Anda.");
                    }

                    $santri = $this->santrisByNoUrut[$noUrut];

                    // Persiapkan data update
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
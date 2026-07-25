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
    protected $kelasId;
    protected $totalSantri = 0;
    protected $santrisByNoUrut = [];
    protected $photos = [];

    public function __construct(int $guruId, ?int $kelasId, array $photos = [])
    {
        if (! $kelasId) {
            throw new \Exception('Kelas aktif tidak valid. Silakan pilih kelas kembali.');
        }

        $this->guruId  = $guruId;
        $this->kelasId = $kelasId;
        $this->photos  = $photos;
        $this->totalSantri = Santri::where('guru_id', $guruId)
            ->where('kelas_id', $kelasId)
            ->count();

        // Bangun peta no_urut → santri SEKALI di awal.
        // Filter & urutan WAJIB identik dengan exportForEdit(), kalau tidak
        // no_urut akan menunjuk santri yang salah.
        $santriList = Santri::where('guru_id', $guruId)
            ->where('kelas_id', $kelasId)
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
                        throw new \Exception("No Urut {$noUrut} tidak ada di kelas yang sedang aktif. Pastikan Excel-nya hasil export dari kelas ini.");
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

                    // === METODE & TARGET (quran / iqro / aisar) + metode_2 opsional ===
                    $metodeInput = !empty($row['metode']) ? strtolower(trim($row['metode'])) : null;
                    if ($metodeInput !== null && !in_array($metodeInput, ['quran', 'iqro', 'aisar'])) {
                        throw new \Exception("Metode harus 'quran', 'iqro', atau 'aisar'");
                    }

                    if ($metodeInput !== null) {
                        // Metode diisi → tetapkan ulang SELURUH state metode/target
                        $isJilidBased = in_array($metodeInput, ['iqro', 'aisar'], true);

                        $metode2 = strtolower(trim((string) ($row['metode_2'] ?? '')));
                        if ($metode2 !== '' && $metode2 !== 'quran') {
                            throw new \Exception("Metode 2 hanya boleh kosong atau 'quran'");
                        }
                        if ($metodeInput === 'quran') $metode2 = '';
                        $hasQuran2 = $metode2 === 'quran';

                        $updateData['target_metode']   = $metodeInput;
                        $updateData['target_metode_2'] = $hasQuran2 ? 'quran' : null;

                        if ($isJilidBased) {
                            if (!isset($row['target_jilid']) || $row['target_jilid'] === '') {
                                throw new \Exception("Metode {$metodeInput} wajib mengisi Target Jilid (1-6)");
                            }
                            $jilid = (int) $row['target_jilid'];
                            if ($jilid < 1 || $jilid > 6) {
                                throw new \Exception("Target Jilid harus angka antara 1-6");
                            }
                            $updateData['target_jilid'] = $jilid;

                            if ($hasQuran2) {
                                $juz = str_replace(',', '.', (string) ($row['target_juz'] ?? ''));
                                if ($juz === '' || !is_numeric($juz) || $juz < 0 || $juz > 30) {
                                    throw new \Exception("Metode 2 quran wajib mengisi Target Juz (0-30)");
                                }
                                $updateData['target_juz'] = (float) $juz;
                            } else {
                                $updateData['target_juz'] = null;
                            }
                        } else { // quran
                            $juz = str_replace(',', '.', (string) ($row['target_juz'] ?? ''));
                            if ($juz === '' || !is_numeric($juz) || $juz < 0 || $juz > 30) {
                                throw new \Exception("Metode quran wajib mengisi Target Juz (0-30)");
                            }
                            $updateData['target_juz']   = (float) $juz;
                            $updateData['target_jilid'] = null;
                        }
                    } else {
                        // Metode kosong → jangan sentuh field metode/metode_2.
                        // Hanya izinkan koreksi juz/jilid ringan sesuai metode yang sudah ada.
                        $metodeEfektif = $santri->target_metode?->value ?? 'quran';
                        $isJilidBased  = in_array($metodeEfektif, ['iqro', 'aisar'], true);

                        if ($isJilidBased) {
                            if (isset($row['target_jilid']) && $row['target_jilid'] !== '') {
                                $jilid = (int) $row['target_jilid'];
                                if ($jilid < 1 || $jilid > 6) {
                                    throw new \Exception("Target Jilid harus angka antara 1-6");
                                }
                                $updateData['target_jilid'] = $jilid;
                            }
                        }
                        if (isset($row['target_juz']) && $row['target_juz'] !== '') {
                            $juz = str_replace(',', '.', (string) $row['target_juz']);
                            if (!is_numeric($juz) || $juz < 0 || $juz > 30) {
                                throw new \Exception("Target Juz harus angka antara 0-30");
                            }
                            $updateData['target_juz'] = (float) $juz;
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
                        $rawFoto   = trim((string) $row['nama_file_foto']);
                        $fotoUpper = strtoupper(preg_replace('/\s+/', '', $rawFoto));
                        if (in_array($fotoUpper, ['HAPUS', '-', 'DELETE'])) {
                            if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                                Storage::disk('public')->delete($santri->user->profile_photo);
                            }
                            $santri->user->update([
                                'profile_photo'      => null,
                                'profile_photo_name' => null,
                            ]);
                        } elseif ($rawFoto !== ($santri->user->profile_photo_name ?? '')) {
                            // hanya proses kalau nama foto BERUBAH dari yang tersimpan
                            $key = strtolower(str_replace(' ', '_', $rawFoto));
                            if (isset($this->photos[$key])) {
                                // proses foto baru DULU — kalau gagal, foto lama tetap aman
                                $path = $this->processPhoto($this->photos[$key]);

                                if ($path) {
                                    if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                                        Storage::disk('public')->delete($santri->user->profile_photo);
                                    }
                                    $santri->user->update([
                                        'profile_photo'      => $path,
                                        'profile_photo_name' => $rawFoto,
                                    ]);
                                } else {
                                    $this->warnings[] = "Baris {$rowNumber}: foto '{$rawFoto}' gagal diproses, foto lama dipertahankan.";
                                }
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
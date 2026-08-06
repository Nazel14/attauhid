<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Guru;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class KelasImport implements ToCollection, SkipsEmptyRows
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
            // BACA PER POSISI KOLOM (bukan nama heading), supaya tabel bantuan
            // di kolom H/J/M/P tidak mengganggu pembacaan.
            // A=0 nama_kelas, B=1 tahun_ajaran, C=2 no_guru, D=3 no_gender,
            // E=4 tingkat, F=5 kapasitas
            $namaKelas  = trim((string) ($row[0] ?? ''));
            $tahunAjaran = trim((string) ($row[1] ?? ''));
            $noGuruRaw  = trim((string) ($row[2] ?? ''));
            $noGenderRaw = trim((string) ($row[3] ?? ''));
            $tingkat     = trim((string) ($row[4] ?? ''));
            $kapasitasRaw = trim((string) ($row[5] ?? ''));

            $rowNumber = $index + 1; // index 0 = baris 1 (Excel)

            // Lewati baris header (baris 1)
            if ($index === 0) {
                continue;
            }

            // Lewati baris contoh
            if ($this->isExampleRow($namaKelas)) {
                continue;
            }

            // Baris benar-benar kosong → skip diam-diam
            if ($namaKelas === '' && $tahunAjaran === '' && $noGuruRaw === '' && $noGenderRaw === '') {
                continue;
            }

            // Baris terisi sebagian → laporkan sebagai error
            if ($namaKelas === '' || $tahunAjaran === '' || $noGuruRaw === '' || $noGenderRaw === '') {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber}: Data belum lengkap. Pastikan kolom nama_kelas, tahun_ajaran, no_guru, dan no_gender semuanya terisi.";
                continue;
            }

            try {
                DB::transaction(function () use ($namaKelas, $tahunAjaran, $noGuruRaw, $noGenderRaw, $tingkat, $kapasitasRaw, $guruList, $noGenderToGender) {
                    $noGuru = (int) $noGuruRaw;

                    if ($noGuru < 1 || $noGuru > $guruList->count()) {
                        throw new \Exception("Nomor Guru {$noGuru} tidak valid. Tersedia 1-{$guruList->count()}");
                    }

                    $guru = $guruList->get($noGuru - 1);

                    if (!$guru) {
                        throw new \Exception("Guru dengan nomor urut {$noGuru} tidak ditemukan");
                    }

                    // Validasi no_gender
                    $noGender = (int) $noGenderRaw;
                    if (!isset($noGenderToGender[$noGender])) {
                        throw new \Exception("No Gender tidak valid. Harus: 1 (Campuran), 2 (Ikhwan), atau 3 (Akhwat)");
                    }

                    $gender = $noGenderToGender[$noGender];

                    $name = $this->smartCapitalize($namaKelas);
                    $ta   = trim($tahunAjaran);

                    $exists = Kelas::where('name', $name)
                                   ->where('tahun_ajaran', $ta)
                                   ->exists();

                    if ($exists) {
                        throw new \Exception("Kelas {$name} dengan tahun ajaran {$ta} sudah ada");
                    }

                    // Kapasitas opsional di Excel; kosong → 20 (default kolom).
                    $kapasitas = 20;
                    if ($kapasitasRaw !== '') {
                        $kapasitas = (int) $kapasitasRaw;
                        if ($kapasitas < 1 || $kapasitas > 100) {
                            throw new \Exception("Kapasitas {$kapasitasRaw} tidak valid. Harus antara 1-100");
                        }
                    }

                    Kelas::create([
                        'name' => $name,
                        'tahun_ajaran' => $ta,
                        'guru_id' => $guru->id,
                        'gender_restriction' => $gender,
                        'tingkat' => $tingkat !== '' ? $tingkat : null,
                        'kapasitas' => $kapasitas,
                        'is_active' => true,
                    ]);

                    $this->successCount++;
                });

            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    protected function isExampleRow(string $namaKelas): bool
    {
        $exampleNames = ['kelas 7a (contoh)', 'kelas 8b (contoh)'];
        return in_array(strtolower(trim($namaKelas)), $exampleNames);
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
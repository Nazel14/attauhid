<?php

namespace App\Imports;

use App\Models\Santri;
use App\Models\Kelas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SantriBulkUpdateImport implements ToCollection, WithHeadingRow
{
    private int $successCount = 0;
    private int $failedCount = 0;
    private array $errors = [];
    private array $warnings = [];

    private int $kelasId;

    /** Metode yang valid — quran (juz), iqro & aisar (jilid) */
    private const METODE_VALID = ['quran', 'iqro', 'aisar'];

    /** @var array<string,string> map [namaFileLowercase => pathAsli] hasil ekstrak ZIP */
    private array $photos;

    public function __construct(int $kelasId, array $photos = [])
    {
        $this->kelasId = $kelasId;
        $this->photos  = $photos;
    }

    public function collection(Collection $rows)
    {
        // Urutan santri harus sama dgn exportForEdit (orderBy name)
        $santris = Santri::with('user')
            ->where('kelas_id', $this->kelasId)
            ->orderBy('name', 'asc')
            ->get()
            ->values();

        // Urutkan kelas by id — konsisten dgn tabel "KELAS & GURU" di file export
        $kelasList = Kelas::orderBy('id')->get()->values();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $noUrut = $row['no_urut'] ?? null;
            if ($noUrut === null || $noUrut === '' || !is_numeric($noUrut)) {
                // baris kosong / rusak — lewati diam-diam jika seluruh baris kosong
                if (trim((string) ($row['nis'] ?? '')) === '' && trim((string) ($row['nama'] ?? '')) === '') {
                    continue;
                }
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNum}: kolom no_urut hilang / bukan angka — jangan ubah kolom ini";
                continue;
            }

            $santri = $santris->get((int) $noUrut - 1);
            if (!$santri) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNum}: no_urut {$noUrut} tidak cocok dengan data santri di kelas ini (jangan tambah/hapus baris)";
                continue;
            }

            $nis         = trim((string) ($row['nis'] ?? ''));
            $nama        = trim((string) ($row['nama'] ?? ''));
            $gender      = strtoupper(trim((string) ($row['gender'] ?? '')));
             $metode      = strtolower(trim((string) ($row['metode'] ?? '')));
            $metode2     = strtolower(trim((string) ($row['metode_2'] ?? '')));
            $targetJuz   = $row['target_juz'] ?? null;
            $targetJilid = $row['target_jilid'] ?? null;
            $noKelasBaru = $row['no_kelas_baru'] ?? null;
            $namaFoto    = trim((string) ($row['nama_file_foto'] ?? ''));

            // ===== VALIDASI =====
            $rowErrors = [];

            if ($nis === '') {
                $rowErrors[] = "NIS kosong";
            } elseif (Santri::where('nis', $nis)->where('id', '!=', $santri->id)->exists()) {
                $rowErrors[] = "NIS {$nis} sudah dipakai santri lain";
            }

            if ($nama === '') {
                $rowErrors[] = "Nama kosong";
            }

            if (!in_array($gender, ['L', 'P'])) {
                $rowErrors[] = "Gender harus L atau P";
            }

            // Metode: kosong → pertahankan metode lama
            $metodeEfektif = $metode !== ''
                ? $metode
                : ($santri->target_metode?->value ?? 'quran');

            if (!in_array($metodeEfektif, self::METODE_VALID)) {
                $rowErrors[] = "Metode harus quran, iqro, atau aisar (ditemukan: {$metodeEfektif})";
            }

            // Metode 2 (opsional) — kosong berarti tidak ada target Al-Qur'an sekunder
            if ($metode2 !== '' && $metode2 !== 'quran') {
                $rowErrors[] = "Metode 2 hanya boleh kosong atau 'quran' (ditemukan: {$metode2})";
            }
            if ($metodeEfektif === 'quran') $metode2 = '';
            $hasQuran2 = $metode2 === 'quran';

            $isJilidBased = in_array($metodeEfektif, ['iqro', 'aisar'], true);

            if ($isJilidBased) {
                $maxJilid = (int) config("{$metodeEfektif}.max_jilid", 6);
                if ($targetJilid === null || $targetJilid === '') {
                    $rowErrors[] = "Target Jilid wajib diisi untuk metode " . ucfirst($metodeEfektif);
                } elseif (!is_numeric($targetJilid) || (int) $targetJilid < 1 || (int) $targetJilid > $maxJilid) {
                    $rowErrors[] = "Target Jilid harus 1-{$maxJilid} untuk metode " . ucfirst($metodeEfektif);
                }
                if ($hasQuran2) {
                    if ($targetJuz === null || $targetJuz === '') {
                        $rowErrors[] = "Metode 2 quran wajib mengisi Target Juz (0-30)";
                    } elseif (!is_numeric($targetJuz) || (float) $targetJuz < 0 || (float) $targetJuz > 30) {
                        $rowErrors[] = "Target Juz harus 0-30";
                    }
                }
            } elseif ($metodeEfektif === 'quran') {
                if ($targetJuz === null || $targetJuz === '') {
                    $rowErrors[] = "Target Juz wajib diisi untuk metode quran";
                } elseif (!is_numeric($targetJuz) || (float) $targetJuz < 0 || (float) $targetJuz > 30) {
                    $rowErrors[] = "Target Juz harus 0-30";
                }
            }

            // Pindah kelas (opsional)
            $kelasTujuan = null;
            if ($noKelasBaru !== null && $noKelasBaru !== '') {
                if (!is_numeric($noKelasBaru)) {
                    $rowErrors[] = "No Kelas Baru bukan angka";
                } else {
                    $kelasTujuan = $kelasList->get((int) $noKelasBaru - 1);
                    if (!$kelasTujuan) {
                        $rowErrors[] = "No Kelas Baru {$noKelasBaru} tidak ditemukan";
                    }
                }
            }

            // Validasi gender vs restriksi kelas (kelas tujuan, atau kelas sekarang)
            if (in_array($gender, ['L', 'P'])) {
                $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';
                $kelasCek = $kelasTujuan ?: $santri->kelas;
                if ($kelasCek) {
                    if ($kelasCek->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
                        $rowErrors[] = "Kelas {$kelasCek->name} khusus Ikhwan";
                    }
                    if ($kelasCek->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
                        $rowErrors[] = "Kelas {$kelasCek->name} khusus Akhwat";
                    }
                }
            }

            if (!empty($rowErrors)) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNum} ({$nama}): " . implode('; ', $rowErrors);
                continue;
            }

            // ===== UPDATE =====
            try {
                DB::transaction(function () use ($santri, $nis, $nama, $gender, $metodeEfektif, $hasQuran2, $isJilidBased, $targetJuz, $targetJilid, $kelasTujuan, $namaFoto, $rowNum) {                    $name = preg_replace_callback('/\b\w+/', function ($m) {
                        return ucfirst(strtolower($m[0]));
                    }, $nama);

                    $genderFull = $gender === 'L' ? 'Laki-laki' : 'Perempuan';

                    $data = [
                        'nis'           => $nis,
                        'name'          => $name,
                        'gender'        => $genderFull,
                        'target_metode'   => $metodeEfektif,
                        'target_metode_2' => $hasQuran2 ? 'quran' : null,
                        'target_juz'      => $isJilidBased ? ($hasQuran2 ? (float) $targetJuz : null) : (float) $targetJuz,
                        'target_jilid'    => $isJilidBased ? (int) $targetJilid : null,
                    ];

                    if ($kelasTujuan) {
                        $data['kelas_id'] = $kelasTujuan->id;
                        $data['guru_id']  = $kelasTujuan->guru_id;
                    }

                    $santri->update($data);

                    if ($santri->user) {
                        $santri->user->update(['name' => $name]);

                        // Foto: kosong = tidak diubah; HAPUS = hapus; nama file = ganti dari ZIP
                        if ($namaFoto !== '') {
                            if (in_array(strtoupper($namaFoto), ['HAPUS', '-', 'DELETE'], true)) {
                                if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                                    Storage::disk('public')->delete($santri->user->profile_photo);
                                }
                                $santri->user->profile_photo      = null;
                                $santri->user->profile_photo_name = null;
                                $santri->user->save();
                            } elseif ($namaFoto !== ($santri->user->profile_photo_name ?? '')) {
                                // hanya proses kalau nama foto BERUBAH dari yang tersimpan
                                $key = strtolower(str_replace(' ', '_', $namaFoto));
                                if (isset($this->photos[$key])) {
                                    $storedPath = $this->processPhoto($this->photos[$key], 'upd_');

                                    if ($storedPath) {
                                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                                            Storage::disk('public')->delete($santri->user->profile_photo);
                                        }
                                        $santri->user->profile_photo      = $storedPath;
                                        $santri->user->profile_photo_name = $namaFoto;
                                        $santri->user->save();
                                    } else {
                                        $this->warnings[] = "Baris {$rowNum} ({$name}): foto '{$namaFoto}' gagal diproses, foto lama dipertahankan";
                                    }
                                } else {
                                    $this->warnings[] = "Baris {$rowNum} ({$name}): foto '{$namaFoto}' tidak ditemukan di ZIP, foto lama dipertahankan";
                                }
                            }
                        }
                    }
                });

                $this->successCount++;

            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNum} ({$nama}): gagal update — " . $e->getMessage();
            }
        }
    }

    /**
     * Salin foto dari ZIP → storage, sekaligus crop persegi & resize (maks 400px).
     */
    private function processPhoto(string $sourcePath, string $prefix = 'upd_'): ?string
    {
        try {
            $maxSize  = 400;
            $ext      = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
            $path     = 'profile-photos/' . uniqid($prefix) . '.' . $ext;

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
            $srcX = (int) (($width - $min) / 2);
            $srcY = ($height > $width) ? 0 : (int) (($height - $min) / 2);

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

    public function getSuccessCount(): int { return $this->successCount; }
    public function getFailedCount(): int  { return $this->failedCount; }
    public function getErrors(): array     { return $this->errors; }
    public function getWarnings(): array   { return $this->warnings; }
}
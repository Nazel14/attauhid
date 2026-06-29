<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guru;
use App\Models\User;
use App\Models\Kelas;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GuruImport;
use App\Imports\GuruBulkUpdateImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class GuruController extends Controller
{
    use LogsActivity;
    
    /**
     * Menampilkan daftar semua Guru.
     */
    public function index()
    {
        $gurus = Guru::with('user')->get();
        return view('admin.gurus.index', compact('gurus'));
    }

    /**
     * Menampilkan form untuk membuat Guru baru.
     */
    public function create()
    {
        return view('admin.gurus.create');
    }

    /**
     * Menyimpan Guru baru (TANPA FOTO PROFIL)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:255|unique:gurus,nip',
            'username' => 'required|string|max:255|unique:users,name',
            'password' => 'required|string|min:8|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'username.unique' => 'Username ini sudah digunakan. Silakan pilih username lain.',
            'password.min' => 'Password minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        try {
            DB::beginTransaction();

            // Smart capitalize
            $name = $this->smartCapitalizeName(trim($validated['name']));

            // Buat user tanpa foto
            $user = User::create([
                'name' => $validated['username'], 
                'role' => 'guru',
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
            ]);

            // ✅ Upload foto profil jika ada
            if ($request->hasFile('profile_photo')) {
                $user->profile_photo = $this->processProfilePhoto($request->file('profile_photo'));
                $user->save();
            }

            Guru::create([
                'name' => $name,
                'nip' => $validated['nip'],
                'user_id' => $user->id,
            ]);
            
            $guru = Guru::where('user_id', $user->id)->first();
            $this->logCreate('guru', "{$guru->name}" . ($guru->nip ? " (NIP: {$guru->nip})" : ""), $guru);

            DB::commit();

            return redirect()->route('admin.gurus.index')
                ->with('success', 'Guru dan akun login berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan Guru. ' . $e->getMessage());
        }
    }

    /**
     * Update Guru (TANPA FOTO PROFIL)
     */
    public function update(Request $request, Guru $guru)
    {
        $oldValues = $guru->only(['name', 'nip']);
        $oldUsername = $guru->user?->name;
        
        $validatedGuru = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => ['nullable', 'string', 'max:255', Rule::unique('gurus', 'nip')->ignore($guru->id)],
        ]);

        return DB::transaction(function () use ($request, $validatedGuru, $guru, $oldValues, $oldUsername) {
            try {
                $name = $this->smartCapitalizeName(trim($validatedGuru['name']));
                
                $guru->update([
                    'name' => $name,
                    'nip' => $validatedGuru['nip']
                ]);

                if ($guru->user) {
                $validatedUser = $request->validate([
                    'username' => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($guru->user->id)],
                ], [
                    'username.unique' => 'Username ini sudah digunakan. Silakan pilih username lain.',
                ]);
                
                $guru->user->update(['name' => $validatedUser['username']]);
            }

            // ✅ TAMBAHAN: Update password jika diisi
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ], [
                'password.min' => 'Password baru minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            ]);

            if ($guru->user) {
                $guru->user->update([
                    'password' => Hash::make($request->password),
                    'plain_password' => $request->password,
                ]);
            }
        }

                // ✅ Foto profil (hapus / ganti)
                $request->validate([
                    'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                ]);
                if ($guru->user) {
                    if ($request->boolean('delete_photo') && $guru->user->profile_photo) {
                        if (Storage::disk('public')->exists($guru->user->profile_photo)) {
                            Storage::disk('public')->delete($guru->user->profile_photo);
                        }
                        $guru->user->profile_photo = null;
                        $guru->user->save();
                    }
                    if ($request->hasFile('profile_photo')) {
                        if ($guru->user->profile_photo && Storage::disk('public')->exists($guru->user->profile_photo)) {
                            Storage::disk('public')->delete($guru->user->profile_photo);
                        }
                        $guru->user->profile_photo = $this->processProfilePhoto($request->file('profile_photo'));
                        $guru->user->save();
                    }
                }
                
                $this->logUpdate('guru', $guru->name, $guru, array_merge($oldValues, ['username' => $oldUsername]));

                return redirect()->route('admin.gurus.index')
                    ->with('updated', 'Data Guru dan akun login berhasil diperbarui!');

            } catch (\Exception $e) {
                \Log::error("Error updating Guru: " . $e->getMessage());
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal memperbarui data Guru. ' . $e->getMessage());
            }
        });
    }

    /**
     * Menghapus Guru dan akun User yang terkait.
     */
    public function destroy(Guru $guru)
    {
        try {
            $name = $guru->name;
            $oldValues = $guru->toArray();
            
            DB::beginTransaction();

            // SET guru_id di kelas menjadi NULL (kelas tetap ada, guru dihapus)
            Kelas::where('guru_id', $guru->id)->update(['guru_id' => null]);

            // Hapus user terkait
            if ($guru->user) {
                $guru->user->delete();
            }

            // Hapus guru
            $guru->delete();
            
            $this->logDelete('guru', $name, $oldValues);

            DB::commit();

            return redirect()->route('admin.gurus.index')
                ->with('deleted', 'Guru berhasil dihapus! Kelas yang sebelumnya diajar guru ini tetap ada (tanpa guru).');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menghapus Guru. ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete guru
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'guru_ids' => 'required|array|min:1',
            'guru_ids.*' => 'exists:gurus,id',
        ], [
            'guru_ids.required' => 'Tidak ada guru yang dipilih.',
            'guru_ids.min' => 'Minimal pilih 1 guru untuk dihapus.',
        ]);

        try {
            $deletedCount = count($request->guru_ids);
            $guruNames = Guru::whereIn('id', $request->guru_ids)->pluck('name')->toArray();
            
            DB::transaction(function () use ($request) {
                // SET guru_id menjadi NULL di kelas
                Kelas::whereIn('guru_id', $request->guru_ids)->update(['guru_id' => null]);
                
                $gurus = Guru::whereIn('id', $request->guru_ids)->with('user')->get();
                
                foreach ($gurus as $guru) {
                    if ($guru->user) {
                        $guru->user->delete();
                    }
                    $guru->delete();
                }
            });
            
            $this->logActivity(
                'delete',
                'guru',
                "Bulk delete {$deletedCount} guru: " . implode(', ', array_slice($guruNames, 0, 3)) . ($deletedCount > 3 ? '...' : ''),
                null,
                ['ids' => $request->guru_ids, 'total' => $deletedCount],
                null
            );

            return redirect()->route('admin.gurus.index')
                ->with('deleted', "Berhasil menghapus {$deletedCount} guru! Kelas tetap ada.");
                
        } catch (\Exception $e) {
            \Log::error("Error bulk deleting Guru: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk import guru (TANPA FOTO)
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers (tanpa foto_profil)
        $headers = ['nama_guru', 'nip', 'username', 'password', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => 'D1D5DB']
                ]
            ]
        ]);
        
        // Set kolom B (NIP) sebagai TEXT
        $sheet->getStyle('B:B')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        
        $sheet->getStyle('A2:E3')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFBEB']
            ],
            'font' => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FDE68A']
                ]
            ]
        ]);
        
        // Data contoh
        $exampleData = [
            ['Ahmad Zainudin, S.Pd (Contoh)', '196501011990', 'ahmad.zainudin', 'pass12345678', 'ahmad.jpg'],
            ['Siti Nurhaliza, M.Pd (Contoh)', '197502011995', 'siti.nurhaliza', 'pass87654321', 'siti.jpg'],
        ];
        
        foreach ($exampleData as $rowIndex => $row) {
            $actualRow = $rowIndex + 2;
            $sheet->setCellValue('A' . $actualRow, $row[0]);
            $sheet->setCellValueExplicit('B' . $actualRow, $row[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $actualRow, $row[2]);
            $sheet->setCellValue('D' . $actualRow, $row[3]);
            $sheet->setCellValue('E' . $actualRow, $row[4] ?? '');
        }
        
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(26);
        $sheet->getColumnDimension('F')->setWidth(5);
        $sheet->getColumnDimension('G')->setWidth(48);
        
        // PETUNJUK IMPORT
        $sheet->setCellValue('G1', 'PETUNJUK IMPORT GURU');
        $sheet->setCellValue('G2', '1. Nama Guru: Huruf awal WAJIB kapital, sisanya bebas');
        $sheet->setCellValue('G3', '2. NIP: Nomor Induk Pegawai (opsional)');
        $sheet->setCellValue('G4', '3. Username: Untuk login ke sistem (harus unik)');
        $sheet->setCellValue('G5', '4. Password: Minimal 8 karakter (huruf/angka)');
        $sheet->setCellValue('G6', '5. Nama File Foto: nama file foto, mis: ahmad.jpg (opsional)');
        $sheet->setCellValue('G7', 'CATATAN:');
        $sheet->setCellValue('G8', '• Baris 2-3 adalah CONTOH, hapus sebelum import');
        $sheet->setCellValue('G9', '• Username harus unik (tidak boleh sama)');
        $sheet->setCellValue('G10', '• NIP boleh dikosongkan');
        $sheet->setCellValue('G11', '• Pastikan semua data sudah benar sebelum import');
        
        // Style petunjuk
        $sheet->getStyle('G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        
        $sheet->getStyle('G2:G11')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        
        $sheet->getStyle('G7')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'D97706']],
        ]);
        
        $sheet->getStyle('G1:G11')->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '3B82F6']
                ]
            ]
        ]);
        
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_Guru_' . date('Y-m-d') . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export guru untuk di-edit (TANPA FOTO) - VERSI FINAL
     */
   public function exportForEdit()
    {
        $guruList = Guru::with('user')->orderBy('name', 'asc')->get();

        if ($guruList->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data guru untuk diekspor.');
        }

        $this->logExport('guru', 'Excel (Export for Edit)');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['no_urut', 'nama_guru', 'nip', 'username', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('C:C')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $row = 2;
        $noUrut = 1;
        foreach ($guruList as $guru) {
            // Hanya isi nama_file_foto kalau guru SUDAH punya foto.
            // Guru tanpa foto → dibiarkan kosong.
            $fotoSaran = '';
            if ($guru->user && $guru->user->profile_photo && $guru->user->name) {
                $cleanName = preg_replace('/[^A-Za-z0-9._-]/', '', str_replace(' ', '_', $guru->user->name));
                $fotoSaran = $cleanName . '.jpg';
            }

            $sheet->setCellValue('A' . $row, $noUrut);
            $sheet->setCellValue('B' . $row, $guru->name);
            $sheet->setCellValueExplicit('C' . $row, $guru->nip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $guru->user->name ?? '');
            $sheet->setCellValue('E' . $row, $fotoSaran);

            $row++;
            $noUrut++;
        }

        foreach (['A' => 10, 'B' => 30, 'C' => 18, 'D' => 20, 'E' => 26] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->getColumnDimension('F')->setWidth(5);
        $sheet->getColumnDimension('G')->setWidth(48);

        // PANDUAN UPDATE
        $sheet->setCellValue('G1', 'PANDUAN UPDATE GURU');
        $sheet->setCellValue('G2', '• JANGAN ubah/hapus kolom NO URUT');
        $sheet->setCellValue('G3', '• NIP boleh DIKOSONGKAN jika tidak punya NIP');
        $sheet->setCellValue('G4', '• Username harus tetap unik');
        $sheet->setCellValue('G5', '• Password TIDAK bisa diubah via Excel');
        $sheet->setCellValue('G6', '');
        $sheet->setCellValue('G7', 'FOTO:');
        $sheet->setCellValue('G8', '• Isi nama_file_foto sesuai nama file di ZIP (ganti foto)');
        $sheet->setCellValue('G9', '• Upload foto sebagai 1 ZIP saat update');
        $sheet->setCellValue('G10', '• Kosongkan = foto tidak diubah');
        $sheet->setCellValue('G11', '• Tulis HAPUS = foto dihapus jadi tanpa foto');

        $sheet->getStyle('G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('G7')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
        ]);
        $sheet->getStyle('G2:G11')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('G1:G11')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '059669']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Edit_Guru_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Bulk update guru (TANPA FOTO) - VERSI FINAL
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'photos_zip.mimes' => 'File foto harus berformat ZIP',
            'photos_zip.max' => 'Ukuran ZIP foto maksimal 20MB',
        ]);

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new GuruBulkUpdateImport($photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount = $import->getFailedCount();
            $errors = $import->getErrors();
            $warnings = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

            if ($failedCount > 0) {
                $errorMessage = "Berhasil update: {$successCount} guru. Gagal: {$failedCount} guru.<br><br>";
                $errorMessage .= "<strong>Detail Error:</strong><br>";
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));
                return redirect()->route('admin.gurus.index')->with('error', $errorMessage);
            }

            $this->logActivity('update', 'guru', "Bulk update {$successCount} data guru via Excel", null, null, ['total_updated' => $successCount]);

            $msg = "Berhasil mengupdate {$successCount} data guru!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('admin.gurus.index')->with('updated', $msg);

        } catch (\Exception $e) {
            \Log::error("Error bulk updating guru: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
        }
    }

    /**
     * Import guru dari Excel (TANPA FOTO)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'photos_zip.mimes' => 'File foto harus berformat ZIP',
            'photos_zip.max' => 'Ukuran ZIP foto maksimal 20MB',
        ]);

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new GuruImport($photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount = $import->getFailedCount();
            $errors = $import->getErrors();
            $warnings = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

            if ($failedCount > 0) {
                $errorMessage = "Berhasil import: {$successCount} guru. Gagal: {$failedCount} guru.<br><br>";
                $errorMessage .= "<strong>Detail Error:</strong><br>";
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));
                return redirect()->route('admin.gurus.index')->with('error', $errorMessage);
            }

            $this->logImport('guru', $successCount);

            $msg = "Berhasil mengimport {$successCount} data guru!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('admin.gurus.index')->with('success', $msg);

        } catch (\Exception $e) {
            \Log::error("Error importing guru: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    

    /**
     * Ekstrak ZIP foto jadi map [namaFileLowercase => pathAsli].
     * Return: [photosMap, tmpDir]
     */
    private function extractPhotosZip($zipFile): array
    {
        if (!$zipFile) {
            return [[], null];
        }

        if (!class_exists('ZipArchive')) {
            return [[], null];
        }

        $tmpDir = storage_path('app/tmp_photos_' . uniqid());
        @mkdir($tmpDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            return [[], $tmpDir];
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $photos = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS));
        foreach ($rii as $f) {
            if (!$f->isFile()) continue;
            $ext = strtolower($f->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $key = strtolower(str_replace(' ', '_', $f->getFilename()));
                $photos[$key] = $f->getPathname();
            }
        }

        return [$photos, $tmpDir];
    }

    private function cleanupTmp($tmpDir): void
    {
        if (!$tmpDir || !is_dir($tmpDir)) return;
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($rii as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($tmpDir);
    }
    

    /**
     * Resize & crop foto jadi persegi (bias atas agar kepala tidak terpotong).
     */
    private function processProfilePhoto($file): string
    {
        $maxSize = 400;
        $path = $file->store('profile-photos', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $info = getimagesize($fullPath);
        if (!$info) return $path;

        [$width, $height] = $info;
        $mime = $info['mime'];

        $src = match ($mime) {
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
    }

    /**
     * Smart capitalize name - huruf pertama kapital, sisanya tetap seperti input
     */
    private function smartCapitalizeName($text)
    {
        $text = trim($text);
        
        // Capitalize huruf pertama saja
        if (strlen($text) > 0) {
            $text = strtoupper(substr($text, 0, 1)) . substr($text, 1);
        }
        
        return $text;
    }
}
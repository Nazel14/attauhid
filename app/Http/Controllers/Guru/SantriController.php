<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\Santri;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Enums\MetodeTarget;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GuruSantriImport;
use App\Imports\GuruSantriBulkUpdate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SantriController extends Controller
{
    public function index()
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        $santris = Santri::with(['kelas', 'guru', 'user'])
            ->where('guru_id', $guru->id)
            ->orderBy('name', 'asc')
            ->paginate(15);
        
        $kelas = Kelas::all();
        
        return view('guru.santri.index', compact('santris', 'kelas', 'guru'));
    }

   public function store(Request $request)
{
    $guruUser = Auth::user();
    $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

    $validated = $request->validate([
        'nis'           => 'required|string|unique:santris,nis',
        'name'          => 'required|string|max:255|unique:users,name',
        'gender'        => 'required|in:L,P',
        'target_metode' => ['required', Rule::enum(MetodeTarget::class)],
        'target_juz'    => ['nullable', 'required_if:target_metode,quran', 'numeric', 'min:0', 'max:30'],
        'target_jilid'  => ['nullable', 'required_if:target_metode,iqro', 'integer', 'min:1', 'max:6'],
        'kelas_id'      => 'required|exists:kelas,id',
        'password'      => 'required|string|min:8|confirmed',
    ], [
        'password.min'       => 'Password minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'nis.unique'         => 'NIS sudah terdaftar.',
        'name.unique'        => 'Nama santri sudah terdaftar, gunakan nama lengkap yang berbeda (contoh: tambahkan nama ayah atau bin/binti).',
    ]);

    return DB::transaction(function () use ($validated, $guru, $request) {
        try {
            $name       = ucwords(strtolower(trim($validated['name'])));
            $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';

            // Null-kan field yang tidak dipakai metode terpilih
            if ($validated['target_metode'] === 'quran') {
                $validated['target_jilid'] = null;
            } else {
                $validated['target_juz'] = null;
            }
            
            $santriUser = User::create([
                'name'           => $name,
                'password'       => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'role'           => 'santri',
            ]);
            
            $santri = Santri::create([
                'user_id'       => $santriUser->id,
                'kelas_id'      => $validated['kelas_id'],
                'guru_id'       => $guru->id,
                'nis'           => $validated['nis'],
                'name'          => $name,
                'gender'        => $genderFull,
                'target_metode' => $validated['target_metode'],
                'target_juz'    => $validated['target_juz'] ?? null,
                'target_jilid'  => $validated['target_jilid'] ?? null,
            ]);

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'create',
                    'module'        => 'santri',
                    'description'   => "Menambahkan santri: {$santri->name} (NIS: {$santri->nis})",
                    'model_type'    => Santri::class,
                    'model_id'      => $santri->id,
                    'new_values'    => $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']),
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log santri create activity: ' . $e->getMessage());
            }

            return redirect()->route('guru.santris.index')
                ->with('success', 'Santri dan akun login berhasil ditambahkan!');

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                return redirect()->back()->withInput()
                    ->with('error', 'Nama santri sudah terdaftar di sistem. Gunakan nama lengkap yang berbeda (contoh: tambahkan nama ayah atau bin/binti).');
            }
            Log::error("Error creating Santri by guru: " . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("Error creating Santri by guru: " . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    });
}

    public function update(Request $request, Santri $santri)
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        if ($santri->guru_id !== $guru->id) {
            return redirect()->route('guru.santris.index')
                ->with('error', 'Anda tidak memiliki akses untuk mengubah data santri ini!');
        }

        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']);
        $validated = $request->validate([
                    'nis'           => ['required', 'string', Rule::unique('santris', 'nis')->ignore($santri->id)],
                    'name'          => 'required|string|max:255',
                    'gender'        => 'required|in:L,P',
                    'target_metode' => ['required', Rule::enum(MetodeTarget::class)],
                    'target_juz'    => ['nullable', 'required_if:target_metode,quran', 'numeric', 'min:0', 'max:30'],
                    'target_jilid'  => ['nullable', 'required_if:target_metode,iqro', 'integer', 'min:1', 'max:6'],
                    'kelas_id'      => 'required|exists:kelas,id',
                    'password'      => 'nullable|string|min:8',
                ], [
                    'password.min' => 'Password minimal 8 karakter.',
                ]);

        return DB::transaction(function () use ($validated, $santri, $oldValues, $request) {
            try {
                $name       = ucwords(strtolower(trim($validated['name'])));
                $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';

                // Null-kan field yang tidak dipakai metode terpilih
                if ($validated['target_metode'] === 'quran') {
                    $validated['target_jilid'] = null;
                } else {
                    $validated['target_juz'] = null;
                }
                
                $santri->update([
                    'nis'           => $validated['nis'],
                    'name'          => $name,
                    'gender'        => $genderFull,
                    'target_metode' => $validated['target_metode'],
                    'target_juz'    => $validated['target_juz'] ?? null,
                    'target_jilid'  => $validated['target_jilid'] ?? null,
                    'kelas_id'      => $validated['kelas_id'],
                ]);

                if ($santri->user) {
                    $updateData = ['name' => $name];
                    if (!empty($validated['password'])) {
                        $updateData['password']       = Hash::make($validated['password']);
                        $updateData['plain_password']  = $validated['password'];
                    }
                    $santri->user->update($updateData);
                }

                try {
                    LogActivity::create([
                        'user_id'       => Auth::id(),
                        'role'          => Auth::user()->role,
                        'activity_type' => 'update',
                        'module'        => 'santri',
                        'description'   => "Mengubah data santri: {$santri->name} (NIS: {$santri->nis})",
                        'model_type'    => Santri::class,
                        'model_id'      => $santri->id,
                        'old_values'    => $oldValues,
                        'new_values'    => $santri->fresh()->only(array_keys($oldValues)),
                        'ip_address'    => $request->ip(),
                        'user_agent'    => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log santri update activity: ' . $e->getMessage());
                }

                return redirect()->route('guru.santris.index')
                    ->with('updated', 'Data santri dan akun login berhasil diperbarui!');
                    
            } catch (\Exception $e) {
                Log::error("Error updating Santri by guru: " . $e->getMessage());
                return redirect()->back()->withInput()
                    ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
            }
        });
    }

    public function destroy(Santri $santri)
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        if ($santri->guru_id !== $guru->id) {
            return redirect()->route('guru.santris.index')
                ->with('error', 'Anda tidak memiliki akses untuk menghapus data santri ini!');
        }

        $name      = $santri->name;
        $nis       = $santri->nis;
        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']);

        try {
            DB::transaction(function () use ($santri) {
                $userId = $santri->user_id;
                $santri->delete();
                if ($userId) {
                    User::where('id', $userId)->delete();
                }
            });

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'delete',
                    'module'        => 'santri',
                    'description'   => "Menghapus santri: {$name} (NIS: {$nis})",
                    'old_values'    => $oldValues,
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log santri delete activity: ' . $e->getMessage());
            }

            return redirect()->route('guru.santris.index')
                ->with('deleted', 'Santri dan akun login berhasil dihapus!');
                
        } catch (\Exception $e) {
            Log::error("Error deleting Santri by guru: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }
    }

   public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // 7 kolom: tambah metode & target_jilid
        $headers = ['nis', 'nama', 'gender', 'metode', 'target_juz', 'target_jilid', 'password'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->getStyle('A2:G3')->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            'font'    => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FDE68A']]]
        ]);

        // Satu contoh quran, satu iqro
        $exampleData = [
            ['12345 (Contoh)', 'Ahmad Zaki',       'L', 'quran', '5.50', '',  'pass1234'],
            ['12346 (Contoh)', 'Aisyah Rahmawati', 'P', 'iqro',  '',     '3', 'pass8765'],
        ];
        foreach ($exampleData as $rowIndex => $row) {
            $actualRow = $rowIndex + 2;
            $col = 'A';
            foreach ($row as $val) {
                $sheet->setCellValue($col . $actualRow, $val);
                $col++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(16);  // nis
        $sheet->getColumnDimension('B')->setWidth(28);  // nama
        $sheet->getColumnDimension('C')->setWidth(8);   // gender
        $sheet->getColumnDimension('D')->setWidth(10);  // metode
        $sheet->getColumnDimension('E')->setWidth(11);  // target_juz
        $sheet->getColumnDimension('F')->setWidth(11);  // target_jilid
        $sheet->getColumnDimension('G')->setWidth(16);  // password
        $sheet->getColumnDimension('H')->setWidth(2);   // spacing
        $sheet->getColumnDimension('I')->setWidth(46);  // petunjuk

        $sheet->setCellValue('I1', 'PETUNJUK IMPORT SANTRI');
        $sheet->setCellValue('I2', '1. NIS: Nomor Induk Santri (unik)');
        $sheet->setCellValue('I3', '2. Nama: Nama lengkap santri');
        $sheet->setCellValue('I4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
        $sheet->setCellValue('I5', '4. Metode: quran atau iqro');
        $sheet->setCellValue('I6', '5. Target Juz: 0.00 - 30.00 (isi bila metode quran)');
        $sheet->setCellValue('I7', '6. Target Jilid: 1 - 6 (isi bila metode iqro)');
        $sheet->setCellValue('I8', '7. Password: Minimal 4 karakter');
        $sheet->setCellValue('I9', '');
        $sheet->setCellValue('I10', 'CATATAN:');
        $sheet->setCellValue('I11', '• Metode quran → isi Target Juz, kosongkan Target Jilid');
        $sheet->setCellValue('I12', '• Metode iqro → isi Target Jilid, kosongkan Target Juz');
        $sheet->setCellValue('I13', '• Baris 2-3 adalah CONTOH, hapus sebelum import');
        $sheet->setCellValue('I14', '• NIS yang mengandung "(Contoh)" akan diabaikan');
        $sheet->setCellValue('I15', '• Santri akan otomatis masuk ke kelas Anda');

        $sheet->getStyle('I1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('I2:I8')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('I10')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('I11:I15')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('I1:I15')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B82F6']]]
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        $writer   = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_Santri_Guru_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportForEdit()
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        $santris = Santri::with(['kelas', 'guru', 'user'])
            ->where('guru_id', $guru->id)
            ->orderBy('name', 'asc')
            ->get();

        if ($santris->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data santri untuk diekspor.');
        }

        try {
            LogActivity::create([
                'user_id'       => Auth::id(),
                'role'          => Auth::user()->role,
                'activity_type' => 'download',
                'module'        => 'santri',
                'description'   => "Export data santri ke Excel (untuk edit) - {$santris->count()} santri",
                'new_values'    => ['total_santri' => $santris->count(), 'guru' => $guru->name],
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log export activity: ' . $e->getMessage());
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // 7 kolom: tambah metode, target_juz, target_jilid
        $headers = ['no_urut', 'nis', 'nama', 'gender', 'metode', 'target_juz', 'target_jilid'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $row    = 2;
        $noUrut = 1;
        foreach ($santris as $santri) {
            $metodeVal = $santri->target_metode?->value ?? 'quran';

            $sheet->setCellValue('A' . $row, $noUrut);
            $sheet->setCellValue('B' . $row, $santri->nis);
            $sheet->setCellValue('C' . $row, $santri->name);
            $sheet->setCellValue('D' . $row, $santri->gender === 'Laki-laki' ? 'L' : 'P');
            $sheet->setCellValue('E' . $row, $metodeVal);
            $sheet->setCellValue('F' . $row, $metodeVal === 'iqro' ? '' : $santri->target_juz);
            $sheet->setCellValue('G' . $row, $metodeVal === 'iqro' ? $santri->target_jilid : '');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
            ]);
            $row++;
            $noUrut++;
        }

        $sheet->getColumnDimension('A')->setWidth(10);  // no_urut
        $sheet->getColumnDimension('B')->setWidth(16);  // nis
        $sheet->getColumnDimension('C')->setWidth(28);  // nama
        $sheet->getColumnDimension('D')->setWidth(8);   // gender
        $sheet->getColumnDimension('E')->setWidth(10);  // metode
        $sheet->getColumnDimension('F')->setWidth(11);  // target_juz
        $sheet->getColumnDimension('G')->setWidth(11);  // target_jilid
        $sheet->getColumnDimension('H')->setWidth(2);   // spacing
        $sheet->getColumnDimension('I')->setWidth(46);  // petunjuk

        $sheet->setCellValue('I1', '📋 PANDUAN BULK UPDATE');
        $sheet->setCellValue('I2', '');
        $sheet->setCellValue('I3', '⚠️ ATURAN PENTING:');
        $sheet->setCellValue('I4', '• JANGAN ubah/hapus kolom NO URUT');
        $sheet->setCellValue('I5', '• JANGAN tambah/hapus baris');
        $sheet->setCellValue('I6', '• Kelas TIDAK bisa diubah via Excel');
        $sheet->setCellValue('I7', '• Password tidak bisa diubah via Excel');
        $sheet->setCellValue('I8', '');
        $sheet->setCellValue('I9', '📝 METODE & TARGET:');
        $sheet->setCellValue('I10', '• Metode: quran atau iqro');
        $sheet->setCellValue('I11', '• quran → isi Target Juz (0-30), Jilid kosong');
        $sheet->setCellValue('I12', '• iqro → isi Target Jilid (1-6), Juz kosong');
        $sheet->setCellValue('I13', '');
        $sheet->setCellValue('I14', '📝 CARA UPDATE:');
        $sheet->setCellValue('I15', '1. Edit data yang ingin diubah');
        $sheet->setCellValue('I16', '2. Upload file Excel yang sudah diedit');

        $sheet->getStyle('I1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('I3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('I9')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
        ]);
        $sheet->getStyle('I14')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'D97706']],
        ]);
        $sheet->getStyle('I2:I16')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('I1:I16')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '059669']]]
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        $writer   = new Xlsx($spreadsheet);
        $fileName = 'Edit_Santri_Guru_' . str_replace(' ', '_', $guru->name) . '_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:2048']);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            $import = new GuruSantriImport($guru->id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();

            if ($successCount > 0) {
                try {
                    LogActivity::create([
                        'user_id'       => Auth::id(),
                        'role'          => Auth::user()->role,
                        'activity_type' => 'import',
                        'module'        => 'santri',
                        'description'   => "Import {$successCount} data santri via Excel",
                        'new_values'    => ['success' => $successCount, 'failed' => $failedCount],
                        'ip_address'    => $request->ip(),
                        'user_agent'    => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log import activity: ' . $e->getMessage());
                }
            }

            if ($failedCount > 0) {
                $errorMessage = "Berhasil: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
                $errorMessage .= implode('<br>', $errors);
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            return redirect()->route('guru.santris.index')
                ->with('success', "Berhasil mengimport {$successCount} santri!");

        } catch (\Exception $e) {
            Log::error("Error importing santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:2048']);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            $import = new GuruSantriBulkUpdate($guru->id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();

            if ($successCount > 0) {
                try {
                    LogActivity::create([
                        'user_id'       => Auth::id(),
                        'role'          => Auth::user()->role,
                        'activity_type' => 'update',
                        'module'        => 'santri',
                        'description'   => "Bulk update {$successCount} data santri via Excel",
                        'new_values'    => ['success' => $successCount, 'failed' => $failedCount],
                        'ip_address'    => $request->ip(),
                        'user_agent'    => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log bulk update activity: ' . $e->getMessage());
                }
            }

            if ($failedCount > 0) {
                $errorMessage = "Berhasil update: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
                $errorMessage .= implode('<br>', $errors);
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            return redirect()->route('guru.santris.index')
                ->with('updated', "Berhasil mengupdate {$successCount} data santri!");

        } catch (\Exception $e) {
            Log::error("Error bulk updating santri (guru): " . $e->getMessage());
            return redirect()->route('guru.santris.index')
                ->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'santri_ids'   => 'required|array|min:1',
            'santri_ids.*' => 'exists:santris,id',
        ]);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            $deletedCount = 0;
            $santriNames  = [];
            
            DB::transaction(function () use ($request, $guru, &$deletedCount, &$santriNames) {
                $santris = Santri::with('user')
                    ->whereIn('id', $request->santri_ids)
                    ->where('guru_id', $guru->id)
                    ->get();
                
                foreach ($santris as $santri) {
                    $santriNames[] = $santri->name;
                    if ($santri->user) {
                        $santri->user->delete();
                    }
                    $santri->delete();
                    $deletedCount++;
                }
            });

            if ($deletedCount === 0) {
                return redirect()->route('guru.santris.index')
                    ->with('error', 'Tidak ada santri yang berhasil dihapus!');
            }

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'delete',
                    'module'        => 'santri',
                    'description'   => "Bulk delete {$deletedCount} santri: " . implode(', ', array_slice($santriNames, 0, 3)) . ($deletedCount > 3 ? '...' : ''),
                    'old_values'    => ['total' => $deletedCount, 'names' => $santriNames],
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log bulk delete activity: ' . $e->getMessage());
            }

            return redirect()->route('guru.santris.index')
                ->with('deleted', "Berhasil menghapus {$deletedCount} santri!");
                
        } catch (\Exception $e) {
            Log::error("Error bulk deleting Santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
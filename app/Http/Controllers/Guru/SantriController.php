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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
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
        ->paginate(15)
        ->withQueryString();

    $kelasList = Kelas::where('guru_id', $guru->id)->orderBy('name', 'asc')->get();

    $targetMetodeOptions = collect(MetodeTarget::cases())->map(fn ($case) => [
        'value' => $case->value,
        'label' => ucfirst($case->value),
    ])->values();

    return Inertia::render('Guru/Santri/Index', [
        'santris' => $santris,
        'kelasList' => $kelasList,
        'targetMetodeOptions' => $targetMetodeOptions,
        'guru' => $guru,
    ]);
}

   public function store(Request $request)
{
    $guruUser = Auth::user();
    $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

    $validated = $request->validate([
        'nis'           => 'required|string|unique:santris,nis',
        'name'          => 'required|string|max:255',
        'gender'        => 'required|in:L,P',
        'target_metode' => ['required', Rule::enum(MetodeTarget::class)],
        'target_juz'    => ['nullable', 'required_if:target_metode,quran', 'numeric', 'min:0', 'max:30'],
        'target_jilid'  => ['nullable', 'required_if:target_metode,iqro,aisar', 'integer', 'min:1', 'max:6'],
        'kelas_id'      => ['required', Rule::exists('kelas', 'id')->where('guru_id', $guru->id)],
        'password'      => 'required|string|min:8|confirmed',
        'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ], [
        'password.min'       => 'Password minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'nis.unique'         => 'NIS sudah terdaftar.',
        'kelas_id.exists'    => 'Kelas tidak valid — Anda hanya bisa menambahkan santri ke kelas Anda sendiri.',
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

            if ($request->hasFile('profile_photo')) {
                $foto = $request->file('profile_photo');
                $santriUser->profile_photo      = $this->processProfilePhoto($foto);
                $santriUser->profile_photo_name = $foto->getClientOriginalName();
                $santriUser->save();
            }
            
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
                    'target_jilid'  => ['nullable', 'required_if:target_metode,iqro,aisar', 'integer', 'min:1', 'max:6'],
                    'kelas_id'      => ['required', Rule::exists('kelas', 'id')->where('guru_id', $guru->id)],
                    'password'      => 'nullable|string|min:8',
                    'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                ], [
                    'password.min'    => 'Password minimal 8 karakter.',
                    'kelas_id.exists' => 'Kelas tidak valid — Anda hanya bisa memindahkan santri di dalam kelas Anda sendiri.',
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

                    // Foto profil (hapus / ganti)
                    if ($request->boolean('delete_photo') && $santri->user->profile_photo) {
                        if (Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $santri->user->profile_photo      = null;
                        $santri->user->profile_photo_name = null;
                        $santri->user->save();
                    }
                    if ($request->hasFile('profile_photo')) {
                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $foto = $request->file('profile_photo');
                        $santri->user->profile_photo      = $this->processProfilePhoto($foto);
                        $santri->user->profile_photo_name = $foto->getClientOriginalName();
                        $santri->user->save();
                    }
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
                    ->with('success', 'Data santri dan akun login berhasil diperbarui!');
                    
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
                if ($santri->user && $santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                    Storage::disk('public')->delete($santri->user->profile_photo);
                }
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
                ->with('success', 'Santri dan akun login berhasil dihapus!');
                
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
        $headers = ['nis', 'nama', 'gender', 'metode', 'target_juz', 'target_jilid', 'password', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->getStyle('A2:H4')->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            'font'    => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FDE68A']]]
        ]);

        // Contoh: quran, iqro, aisar
        $exampleData = [
            ['12345 (Contoh)', 'Ahmad',       'L', 'quran', '5.50', '',  'pass1234', 'ahmad.jpg'],
            ['12346 (Contoh)', 'Aisyah', 'P', 'iqro',  '',     '3', 'pass8765', 'aisyah.jpg'],
            ['12347 (Contoh)', 'Bilal',      'L', 'aisar', '',     '1', 'pass5678', 'bilal.jpg'],
        ];
        foreach ($exampleData as $rowIndex => $row) {
            $actualRow = $rowIndex + 2;
            $col = 'A';
            foreach ($row as $val) {
                $sheet->setCellValue($col . $actualRow, $val);
                $col++;
            }
        }

        // Area input baris 5-104: putih bergaris (cegah Excel auto-extend format contoh)
        $sheet->getStyle('A5:H104')->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'font'    => ['italic' => false, 'bold' => false, 'size' => 10, 'color' => ['rgb' => '111827']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->getColumnDimension('A')->setWidth(16);  // nis
        $sheet->getColumnDimension('B')->setWidth(28);  // nama
        $sheet->getColumnDimension('C')->setWidth(8);   // gender
        $sheet->getColumnDimension('D')->setWidth(10);  // metode
        $sheet->getColumnDimension('E')->setWidth(11);  // target_juz
        $sheet->getColumnDimension('F')->setWidth(11);  // target_jilid
        $sheet->getColumnDimension('G')->setWidth(16);  // password
        $sheet->getColumnDimension('H')->setWidth(20);  // nama_file_foto
        $sheet->getColumnDimension('I')->setWidth(2);   // spacing
        $sheet->getColumnDimension('J')->setWidth(46);  // petunjuk

        $sheet->setCellValue('J1', 'PETUNJUK IMPORT SANTRI');
        $sheet->setCellValue('J2', '1. NIS: Nomor Induk Santri (unik)');
        $sheet->setCellValue('J3', '2. Nama: Nama lengkap santri');
        $sheet->setCellValue('J4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
        $sheet->setCellValue('J5', '4. Metode: quran, iqro, atau aisar');
        $sheet->setCellValue('J6', '5. Target Juz: 0.00 - 30.00 (isi bila metode quran)');
        $sheet->setCellValue('J7', '6. Target Jilid: 1 - 6 (isi bila metode iqro/aisar)');
        $sheet->setCellValue('J8', '7. Password: Minimal 4 karakter');
        $sheet->setCellValue('J9', '8. Nama File Foto: tanpa spasi, mis: ahmad.jpg (opsional)');
        $sheet->setCellValue('J10', '');
        $sheet->setCellValue('J11', 'CATATAN:');
        $sheet->setCellValue('J12', '• Metode quran → isi Target Juz, kosongkan Target Jilid');
        $sheet->setCellValue('J13', '• Metode iqro / aisar → isi Target Jilid, kosongkan Target Juz');
        $sheet->setCellValue('J14', '• Foto diupload terpisah sebagai 1 ZIP saat import');
        $sheet->setCellValue('J15', '• Baris 2-4 adalah CONTOH, hapus sebelum import');
        $sheet->setCellValue('J16', '• NIS yang mengandung "(Contoh)" akan diabaikan');
        $sheet->setCellValue('J17', '• Santri akan otomatis masuk ke kelas Anda');

        $sheet->getStyle('J1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('J2:J9')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('J11')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('J12:J17')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('J1:J17')->applyFromArray([
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
        $headers = ['no_urut', 'nis', 'nama', 'gender', 'metode', 'target_juz', 'target_jilid', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $row    = 2;
        $noUrut = 1;
        foreach ($santris as $santri) {
            $metodeVal    = $santri->target_metode?->value ?? 'quran';
            $isJilidBased = in_array($metodeVal, ['iqro', 'aisar'], true);

            $sheet->setCellValue('A' . $row, $noUrut);
            $sheet->setCellValue('B' . $row, $santri->nis);
            $sheet->setCellValue('C' . $row, $santri->name);
            $sheet->setCellValue('D' . $row, $santri->gender === 'Laki-laki' ? 'L' : 'P');
            $sheet->setCellValue('E' . $row, $metodeVal);
            $sheet->setCellValue('F' . $row, $isJilidBased ? '' : $santri->target_juz);
            $sheet->setCellValue('G' . $row, $isJilidBased ? $santri->target_jilid : '');

            // nama_file_foto: nama file asli saat diupload (bukan dikarang dari nama santri)
            $fotoSaran = '';
            if ($santri->user && $santri->user->profile_photo) {
                $fotoSaran = $santri->user->profile_photo_name ?: basename($santri->user->profile_photo);
            }
            $sheet->setCellValue('H' . $row, $fotoSaran);

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
        $sheet->getColumnDimension('H')->setWidth(20);  // nama_file_foto
        $sheet->getColumnDimension('I')->setWidth(2);   // spacing
        $sheet->getColumnDimension('J')->setWidth(46);  // petunjuk

        $sheet->setCellValue('J1', '📋 PANDUAN BULK UPDATE');
        $sheet->setCellValue('J2', '');
        $sheet->setCellValue('J3', '⚠️ ATURAN PENTING:');
        $sheet->setCellValue('J4', '• JANGAN ubah/hapus kolom NO URUT');
        $sheet->setCellValue('J5', '• JANGAN tambah/hapus baris');
        $sheet->setCellValue('J6', '• Kelas TIDAK bisa diubah via Excel');
        $sheet->setCellValue('J7', '• Password tidak bisa diubah via Excel');
        $sheet->setCellValue('J8', '');
        $sheet->setCellValue('J9', '📝 METODE & TARGET:');
        $sheet->setCellValue('J10', '• Metode: quran, iqro, atau aisar');
        $sheet->setCellValue('J11', '• quran → isi Target Juz (0-30), Jilid kosong');
        $sheet->setCellValue('J12', '• iqro / aisar → isi Target Jilid (1-6), Juz kosong');
        $sheet->setCellValue('J13', '');
        $sheet->setCellValue('J14', '📷 FOTO:');
        $sheet->setCellValue('J15', '• Kolom nama_file_foto = nama file foto SAAT INI');
        $sheet->setCellValue('J16', '• Biarkan apa adanya = foto tidak diubah');
        $sheet->setCellValue('J17', '• Ganti dgn nama file lain + upload ZIP = foto diganti');
        $sheet->setCellValue('J18', '• Tulis HAPUS = foto dihapus jadi tanpa foto');

        $sheet->getStyle('J1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('J3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('J9')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
        ]);
        $sheet->getStyle('J14')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '7C3AED']],
        ]);
        $sheet->getStyle('J2:J18')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('J1:J18')->applyFromArray([
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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ]);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new GuruSantriImport($guru->id, $photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();
            $warnings     = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

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
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            $msg = "Berhasil mengimport {$successCount} santri!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('guru.santris.index')->with('success', $msg);

        } catch (\Exception $e) {
            Log::error("Error importing santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ]);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new GuruSantriBulkUpdate($guru->id, $photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();
            $warnings     = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

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
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            $msg = "Berhasil mengupdate {$successCount} data santri!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('guru.santris.index')->with('success', $msg);

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
                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
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
                ->with('success', "Berhasil menghapus {$deletedCount} santri!");
                
        } catch (\Exception $e) {
            Log::error("Error bulk deleting Santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    private function extractPhotosZip($zipFile): array
    {
        if (!$zipFile) return [[], null];
        if (!class_exists('ZipArchive')) return [[], null];

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

    private function processProfilePhoto($file): string
    {
        $maxSize = 400;
        $path = $file->store('profile-photos', 'public');
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
    }
}
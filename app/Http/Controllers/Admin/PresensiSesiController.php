<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PresensiSesiConfig;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PresensiSesiController extends Controller
{
    use LogsActivity;

    /**
     * Display a listing of the resource (Inertia page)
     */
    public function index()
    {
        // ✅ FIXED: Menggunakan scope ordered() yang sudah diperbaiki
        $sesis = PresensiSesiConfig::ordered()
            ->get()
            ->map(function ($sesi) {
                return [
                    'id' => $sesi->id,
                    'sesi_name' => $sesi->sesi_name,
                    'urutan' => (int) $sesi->urutan,
                    'is_active' => (bool) $sesi->is_active,
                    'updated_at' => optional($sesi->updated_at)->format('d M Y H:i'),
                ];
            })
            ->values();

        Log::info('📋 Loaded sessions', [
            'count' => $sesis->count(),
            'order' => $sesis->pluck('sesi_name', 'urutan')->toArray(),
        ]);

        // ✅ FIXED: sebelumnya view('admin.presensi-sesis.index', ...) padahal
        // frontend-nya sudah React/Inertia (bukan Blade). Ini menyebabkan halaman
        // tidak pernah merender komponen React yang benar.
        return Inertia::render('Admin/PresensiSesi/Index', [
            'sesis' => $sesis,
        ]);
    }

    /**
     * ✅ IMPROVED: Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'sesi_name' => 'required|string|max:50|unique:presensi_sesi_configs,sesi_name',
            'urutan' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Capitalize setiap kata di nama sesi
        $sesiName = ucwords(strtolower(trim($request->sesi_name)));
        $newUrutan = (int) $request->urutan;

        DB::beginTransaction();

        try {
            // ✅ IMPROVED: Geser urutan sesi-sesi yang >= urutan baru
            PresensiSesiConfig::where('urutan', '>=', $newUrutan)
                ->increment('urutan');

            Log::info('✅ Shifted existing sessions up', [
                'from_urutan' => $newUrutan,
                'sessions_affected' => PresensiSesiConfig::where('urutan', '>', $newUrutan)->count(),
            ]);

            // Buat sesi baru
            $sesi = PresensiSesiConfig::create([
                'sesi_name' => $sesiName,
                'urutan' => $newUrutan,
                'is_active' => $request->boolean('is_active'),
            ]);

            Log::info('✅ Created new session', [
                'id' => $sesi->id,
                'name' => $sesi->sesi_name,
                'urutan' => $sesi->urutan,
            ]);

            DB::commit();

            $this->logCreate('sesi-presensi', $sesi->sesi_name, $sesi);

            // ✅ Verifikasi hasil akhir
            $finalOrder = PresensiSesiConfig::ordered()
                ->get()
                ->pluck('sesi_name', 'urutan')
                ->toArray();

            Log::info('📊 Final session order after CREATE', [
                'order' => $finalOrder,
            ]);

            return redirect()->route('admin.presensi-sesis.index')
                ->with('success', "Sesi presensi \"{$sesiName}\" berhasil ditambahkan di urutan {$newUrutan}.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Failed to create session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.presensi-sesis.index')
                ->with('error', 'Gagal menambahkan sesi: ' . $e->getMessage());
        }
    }

    /**
     * ✅ IMPROVED: Update the specified resource in storage
     */
    public function update(Request $request, PresensiSesiConfig $presensiSesi)
    {
        $oldValues = $presensiSesi->only(['sesi_name', 'urutan', 'is_active']);

        $request->validate([
            'sesi_name' => 'required|string|max:50|unique:presensi_sesi_configs,sesi_name,' . $presensiSesi->id,
            'urutan' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Capitalize setiap kata di nama sesi
        $sesiName = ucwords(strtolower(trim($request->sesi_name)));
        $newUrutan = (int) $request->urutan;
        $oldUrutan = (int) $presensiSesi->urutan;

        DB::beginTransaction();

        try {
            // ✅ IMPROVED: Hanya shift jika urutan berubah
            if ($newUrutan != $oldUrutan) {
                Log::info('🔄 Shifting sessions for UPDATE', [
                    'sesi_id' => $presensiSesi->id,
                    'old_urutan' => $oldUrutan,
                    'new_urutan' => $newUrutan,
                ]);

                if ($newUrutan < $oldUrutan) {
                    // Pindah ke atas: geser yang di antara new dan old ke bawah
                    $affectedCount = PresensiSesiConfig::where('id', '!=', $presensiSesi->id)
                        ->where('urutan', '>=', $newUrutan)
                        ->where('urutan', '<', $oldUrutan)
                        ->increment('urutan');

                    Log::info('⬇️ Shifted sessions DOWN', [
                        'range' => "[$newUrutan, $oldUrutan)",
                        'count' => $affectedCount,
                    ]);
                } else {
                    // Pindah ke bawah: geser yang di antara old dan new ke atas
                    $affectedCount = PresensiSesiConfig::where('id', '!=', $presensiSesi->id)
                        ->where('urutan', '>', $oldUrutan)
                        ->where('urutan', '<=', $newUrutan)
                        ->decrement('urutan');

                    Log::info('⬆️ Shifted sessions UP', [
                        'range' => "($oldUrutan, $newUrutan]",
                        'count' => $affectedCount,
                    ]);
                }
            }

            // Update sesi
            $presensiSesi->update([
                'sesi_name' => $sesiName,
                'urutan' => $newUrutan,
                'is_active' => $request->boolean('is_active'),
            ]);

            Log::info('✅ Updated session', [
                'id' => $presensiSesi->id,
                'name' => $sesiName,
                'urutan' => $newUrutan,
            ]);

            DB::commit();

            $this->logUpdate('sesi-presensi', $presensiSesi->sesi_name, $presensiSesi, $oldValues);

            // ✅ Verifikasi hasil akhir
            $finalOrder = PresensiSesiConfig::ordered()
                ->get()
                ->pluck('sesi_name', 'urutan')
                ->toArray();

            Log::info('📊 Final session order after UPDATE', [
                'order' => $finalOrder,
            ]);

            return redirect()->route('admin.presensi-sesis.index')
                ->with('success', "Sesi presensi \"{$sesiName}\" berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Failed to update session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.presensi-sesis.index')
                ->with('error', 'Gagal memperbarui sesi: ' . $e->getMessage());
        }
    }

    /**
     * ✅ IMPROVED: Remove the specified resource from storage
     */
    public function destroy(PresensiSesiConfig $presensiSesi)
    {
        // Cek apakah sesi ini masih digunakan di tabel presensi
        $isUsed = \App\Models\Presensi::where('sesi', $presensiSesi->sesi_name)->exists();

        if ($isUsed) {
            return redirect()->route('admin.presensi-sesis.index')
                ->with('error', 'Sesi ini masih digunakan dan tidak dapat dihapus. Nonaktifkan saja jika tidak ingin digunakan.');
        }

        $name = $presensiSesi->sesi_name;
        $oldValues = $presensiSesi->toArray();
        $deletedUrutan = (int) $presensiSesi->urutan;

        DB::beginTransaction();

        try {
            $presensiSesi->delete();

            Log::info('🗑️ Deleted session', [
                'name' => $name,
                'urutan' => $deletedUrutan,
            ]);

            // ✅ IMPROVED: Geser urutan sesi-sesi yang > urutan yang dihapus
            $affectedCount = PresensiSesiConfig::where('urutan', '>', $deletedUrutan)
                ->decrement('urutan');

            Log::info('⬆️ Shifted sessions up after DELETE', [
                'count' => $affectedCount,
                'from_urutan' => $deletedUrutan + 1,
            ]);

            DB::commit();

            $this->logDelete('sesi-presensi', $name, $oldValues);

            // ✅ Verifikasi hasil akhir
            $finalOrder = PresensiSesiConfig::ordered()
                ->get()
                ->pluck('sesi_name', 'urutan')
                ->toArray();

            Log::info('📊 Final session order after DELETE', [
                'order' => $finalOrder,
            ]);

            return redirect()->route('admin.presensi-sesis.index')
                ->with('success', "Sesi presensi \"{$name}\" berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Failed to delete session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.presensi-sesis.index')
                ->with('error', 'Gagal menghapus sesi: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(PresensiSesiConfig $presensiSesi)
    {
        $presensiSesi->update([
            'is_active' => !$presensiSesi->is_active,
        ]);

        $status = $presensiSesi->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->logActivity(
            'update',
            'sesi-presensi',
            "Sesi presensi {$presensiSesi->sesi_name} {$status}",
            $presensiSesi,
            ['is_active' => !$presensiSesi->is_active],
            ['is_active' => $presensiSesi->is_active]
        );

        Log::info('🔄 Toggled session active status', [
            'name' => $presensiSesi->sesi_name,
            'is_active' => $presensiSesi->is_active,
        ]);

        return redirect()->route('admin.presensi-sesis.index')
            ->with('success', "Sesi {$presensiSesi->sesi_name} berhasil {$status}.");
    }
}

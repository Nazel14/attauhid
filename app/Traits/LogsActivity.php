<?php
// app/Traits/LogsActivity.php

namespace App\Traits;

use App\Models\LogActivity;

trait LogsActivity
{
    /**
     * Log activity helper
     */
    protected function logActivity(
        string $activityType,
        string $module,
        string $description,
        $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ) {
        return LogActivity::log(
            $activityType,
            $module,
            $description,
            $model,
            $oldValues,
            $newValues
        );
    }

    /**
     * Log login activity
     */
    protected function logLogin()
    {
        return $this->logActivity(
            'login',
            'auth',
            'Login ke sistem'
        );
    }

    /**
     * Log logout activity
     */
    protected function logLogout()
    {
        return $this->logActivity(
            'logout',
            'auth',
            'Logout dari sistem'
        );
    }

    /**
     * Log create activity
     */
    protected function logCreate(string $module, string $itemName, $model)
    {
        return $this->logActivity(
            'create',
            $module,
            "Menambahkan {$module}: {$itemName}",
            $model,
            null,
            $model->toArray()
        );
    }

    /**
     * Log update activity
     */
    protected function logUpdate(string $module, string $itemName, $model, array $oldValues)
    {
        return $this->logActivity(
            'update',
            $module,
            "Mengubah data {$module}: {$itemName}",
            $model,
            $oldValues,
            $model->fresh()->toArray()
        );
    }

    /**
     * Log delete activity
     */
    protected function logDelete(string $module, string $itemName, array $oldValues)
    {
        return $this->logActivity(
            'delete',
            $module,
            "Menghapus {$module}: {$itemName}",
            null,
            $oldValues,
            null
        );
    }

    /**
     * Log download activity
     */
    protected function logDownload(string $module, string $itemName, $model = null)
    {
        return $this->logActivity(
            'download',
            $module,
            "Download {$module}: {$itemName}",
            $model
        );
    }

    /**
     * Log view activity
     */
    protected function logView(string $module, string $itemName, $model = null)
    {
        return $this->logActivity(
            'view',
            $module,
            "Melihat {$module}: {$itemName}",
            $model
        );
    }

    /**
     * Log submit presensi activity
     */
    protected function logSubmitPresensi(string $santriName, string $kelas, $model = null)
    {
        return $this->logActivity(
            'submit',
            'presensi',
            "Submit presensi untuk {$santriName} - Kelas {$kelas}",
            $model
        );
    }

    /**
     * Log submit setoran activity
     */
    protected function logSubmitSetoran(string $santriName, string $juz, string $halaman, $model = null)
    {
        return $this->logActivity(
            'submit',
            'setoran',
            "Submit setoran untuk {$santriName} - Juz {$juz} Halaman {$halaman}",
            $model
        );
    }

    /**
     * Log approve activity
     */
    protected function logApprove(string $module, string $itemName, $model = null)
    {
        return $this->logActivity(
            'approve',
            $module,
            "Menyetujui {$module}: {$itemName}",
            $model
        );
    }

    /**
     * Log reject activity
     */
    protected function logReject(string $module, string $itemName, $model = null)
    {
        return $this->logActivity(
            'reject',
            $module,
            "Menolak {$module}: {$itemName}",
            $model
        );
    }

    /**
     * Log import activity
     */
    protected function logImport(string $module, int $count)
    {
        return $this->logActivity(
            'import',
            $module,
            "Import {$count} data {$module}"
        );
    }

    /**
     * Log export activity
     */
    protected function logExport(string $module, string $format = 'Excel')
    {
        return $this->logActivity(
            'download',
            $module,
            "Export data {$module} ke format {$format}"
        );
    }
}
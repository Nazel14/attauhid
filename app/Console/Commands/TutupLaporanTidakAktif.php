<?php

namespace App\Console\Commands;

use App\Models\Laporan;
use Illuminate\Console\Command;

class TutupLaporanTidakAktif extends Command
{
    protected $signature = 'laporan:auto-close {--hari=30 : Jumlah hari tanpa aktivitas}';

    protected $description = 'Tutup otomatis laporan yang tidak ada aktivitas dalam N hari';

    public function handle(): int
    {
        $hari  = max(1, (int) $this->option('hari'));
        $batas = now()->subDays($hari);

        $ids = Laporan::where('status', '!=', 'selesai')
            ->where('updated_at', '<', $batas)
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('Tidak ada laporan yang perlu ditutup.');
            return self::SUCCESS;
        }

        // Update massal — updated_at ikut berubah, dan itu memang diinginkan
        // karena laporan baru saja berpindah status.
        Laporan::whereIn('id', $ids)->update([
            'status'         => 'selesai',
            'selesai_at'     => now(),
            'tutup_otomatis' => true,
        ]);

        $this->info("{$ids->count()} laporan ditutup otomatis (tidak aktif > {$hari} hari).");

        return self::SUCCESS;
    }
}
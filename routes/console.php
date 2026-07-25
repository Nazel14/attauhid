<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Tutup laporan yang menganggur lebih dari 30 hari, tiap hari jam 01:00.
Schedule::command('laporan:auto-close')->dailyAt('01:00');
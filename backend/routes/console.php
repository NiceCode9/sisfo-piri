<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek belum-hadir jalan tiap 5 menit; command-nya self-gating via
// pengaturan jam_cek_belum_hadir agar bisa diubah admin.
Schedule::command('absensi:cek-belum-hadir')->everyFiveMinutes();

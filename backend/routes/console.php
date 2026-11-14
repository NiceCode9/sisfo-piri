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

// Tutup sesi CBT yang ditinggalkan siswa. Tanpa ini baris `ongoing` tidak
// pernah berubah status kecuali siswa atau guru menyentuh endpointnya.
Schedule::command('cbt:expire-sessions')->everyMinute()->withoutOverlapping();

// Bersihkan data tracking kunjungan berita yang lebih lama dari 90 hari,
// supaya tabel berita_views tidak tumbuh tanpa batas.
Schedule::command('berita:prune-views')->weekly();

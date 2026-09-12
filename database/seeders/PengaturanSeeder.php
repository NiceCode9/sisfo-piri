<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    /**
     * Nilai default pengaturan sistem.
     */
    public function run(): void
    {
        $bawaan = [
            ['kunci' => 'batas_terlambat', 'nilai' => '07:00', 'keterangan' => 'Batas jam datang (HH:MM); lewat dari ini tercatat terlambat.'],
            ['kunci' => 'jam_cek_belum_hadir', 'nilai' => '08:00', 'keterangan' => 'Jam command cek-belum-hadir jalan (HH:MM, self-gating tiap 5 menit).'],
            ['kunci' => 'cek_belum_hadir_terakhir', 'nilai' => null, 'keterangan' => 'Tanggal terakhir cek-belum-hadir jalan (Y-m-d, anti ganda).'],
            ['kunci' => 'whatsapp_gateway_url', 'nilai' => null, 'keterangan' => 'URL service whatsapp-web.js; kosong = mode log-only.'],
        ];

        foreach ($bawaan as $row) {
            Pengaturan::firstOrCreate(['kunci' => $row['kunci']], ['nilai' => $row['nilai'], 'keterangan' => $row['keterangan']]);
        }
    }
}

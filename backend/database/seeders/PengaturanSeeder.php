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
            ['kunci' => 'semester_aktif', 'nilai' => 'ganjil', 'keterangan' => 'Semester aktif (ganjil/genap), dipakai filter default rekap.'],
            ['kunci' => 'batas_upload_mb', 'nilai' => '2', 'keterangan' => 'Batas upload file (MB).'],
            ['kunci' => 'maintenance_mode', 'nilai' => '0', 'keterangan' => 'Mode pemeliharaan (1=aktif, blokir semua kecuali super-admin).'],
            ['kunci' => 'maintenance_pesan', 'nilai' => null, 'keterangan' => 'Pesan saat maintenance aktif.'],
            ['kunci' => 'rekap_default_periode', 'nilai' => 'bulan', 'keterangan' => 'Periode default rekap (minggu/bulan/ganjil/genap/tahun).'],
            ['kunci' => 'notifikasi_ortu_aktif', 'nilai' => '1', 'keterangan' => 'Global on/off notifikasi WA ortu (1=aktif).'],
        ];

        foreach ($bawaan as $row) {
            Pengaturan::firstOrCreate(['kunci' => $row['kunci']], ['nilai' => $row['nilai'], 'keterangan' => $row['keterangan']]);
        }
    }
}

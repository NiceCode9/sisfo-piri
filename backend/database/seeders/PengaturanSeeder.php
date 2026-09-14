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
        Pengaturan::whereIn('kunci', [
            'whatsapp_gateway_url', 'notifikasi_ortu_aktif', 'batas_upload_mb',
            'rekap_default_periode', 'semester_aktif',
        ])->delete();

        $bawaan = [
            ['kunci' => 'batas_terlambat', 'nilai' => '07:00', 'keterangan' => 'Batas jam datang (HH:MM); lewat dari ini tercatat terlambat.'],
            ['kunci' => 'jam_cek_belum_hadir', 'nilai' => '08:00', 'keterangan' => 'Jam command cek-belum-hadir jalan (HH:MM, self-gating tiap 5 menit).'],
            ['kunci' => 'cek_belum_hadir_terakhir', 'nilai' => null, 'keterangan' => 'Tanggal terakhir cek-belum-hadir jalan (Y-m-d, anti ganda).'],
            ['kunci' => 'semester_ganjil_mulai', 'nilai' => '07-01', 'keterangan' => 'Awal semester ganjil (MM-DD).'],
            ['kunci' => 'semester_ganjil_selesai', 'nilai' => '12-31', 'keterangan' => 'Akhir semester ganjil (MM-DD).'],
            ['kunci' => 'semester_genap_mulai', 'nilai' => '01-01', 'keterangan' => 'Awal semester genap (MM-DD).'],
            ['kunci' => 'semester_genap_selesai', 'nilai' => '06-30', 'keterangan' => 'Akhir semester genap (MM-DD).'],
            ['kunci' => 'maintenance_mode', 'nilai' => '0', 'keterangan' => 'Mode pemeliharaan (1=aktif, blokir semua kecuali super-admin).'],
            ['kunci' => 'maintenance_pesan', 'nilai' => null, 'keterangan' => 'Pesan saat maintenance aktif.'],
        ];

        foreach ($bawaan as $row) {
            Pengaturan::firstOrCreate(['kunci' => $row['kunci']], ['nilai' => $row['nilai'], 'keterangan' => $row['keterangan']]);
        }
    }
}

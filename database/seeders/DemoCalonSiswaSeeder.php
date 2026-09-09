<?php

namespace Database\Seeders;

use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\Pembayaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class DemoCalonSiswaSeeder extends Seeder
{
    /**
     * 5 calon demo (diterima) + tagihan menunggu untuk uji coba pembayaran.
     * Idempotent: lewati bila NIK demo sudah ada. Jalankan manual:
     * php artisan db:seed --class=DemoCalonSiswaSeeder
     */
    public function run(): void
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();
        $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->firstOrFail();
        $biayas = BiayaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('wajib_bayar', true)->get();

        $nama = ['Andi Pratama', 'Budi Santoso', 'Citra Lestari', 'Dewi Anggraini', 'Eko Wijaya'];
        $jk = ['L', 'L', 'P', 'P', 'L'];

        foreach ($nama as $i => $namaLengkap) {
            $n = $i + 1;

            $calon = CalonSiswa::firstOrCreate(
                ['nik' => sprintf('33740101019000%02d', $n)],
                [
                    'jalur_pendaftaran_id' => $jalur->id,
                    'tahun_ajaran_id' => $tahun->id,
                    'no_pendaftaran' => sprintf('PPDB-2026-90%02d', $n),
                    'nisn' => sprintf('0070012%03d', $n),
                    'nama_lengkap' => $namaLengkap,
                    'jenis_kelamin' => $jk[$i],
                    'tempat_lahir' => 'Sleman',
                    'tanggal_lahir' => '2010-05-10',
                    'agama' => 'Islam',
                    'alamat' => 'Jl Demo No. '.$n.', Ngaglik, Sleman',
                    'no_hp' => '081234567'.sprintf('%03d', $n),
                    'email' => sprintf('demo%d@example.com', $n),
                    'asal_sekolah' => 'SMPN 1 Ngaglik',
                    'status_pendaftaran' => 'diterima',
                ]
            );

            foreach ($biayas as $j => $biaya) {
                Pembayaran::firstOrCreate(
                    ['calon_siswa_id' => $calon->id, 'biaya_pendaftaran_id' => $biaya->id],
                    [
                        'kode_pembayaran' => sprintf('PAY-2026-9%02d%01d', $n, $j),
                        'jumlah' => $biaya->jumlah,
                        'metode_pembayaran' => 'transfer',
                        'jenis_pembayaran' => 'penuh',
                        'status' => 'menunggu',
                    ]
                );
            }

            if ($calon->wasRecentlyCreated) {
                KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)
                    ->where('jalur_pendaftaran_id', $jalur->id)
                    ->increment('terisi');
            }
        }

        $this->command->info('5 calon demo + tagihan menunggu berhasil dibuat.');
    }
}

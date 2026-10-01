<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `JadwalPpdb` sebelumnya hanya punya `nama_jadwal` bebas, sehingga tidak ada
     * cara menentukan baris mana yang menjadi jendela pendaftaran. Gate di server
     * butuh penanda eksplisit.
     *
     * Backfill memakai pencocokan nama (data lama) sehingga school tidak perlu
     * mengisi ulang. Nama yang tidak dikenali -> `lainnya` (tidak meng-gate).
     */
    public function up(): void
    {
        Schema::table('jadwal_ppdbs', function (Blueprint $table) {
            $table->enum('tipe', ['pendaftaran', 'verifikasi', 'tes', 'pengumuman', 'daftar_ulang', 'lainnya'])
                ->default('lainnya')
                ->after('nama_jadwal');
        });

        $peta = [
            'pendaftaran' => ['pendaftaran', 'registrasi'],
            'verifikasi' => ['verifikasi', 'berkas'],
            'tes' => ['tes', 'ujian', 'seleksi'],
            'pengumuman' => ['pengumuman', 'hasil'],
            'daftar_ulang' => ['daftar ulang', 'daftarkan ulang'],
        ];

        foreach (DB::table('jadwal_ppdbs')->select(['id', 'nama_jadwal'])->get() as $baris) {
            $nama = mb_strtolower((string) $baris->nama_jadwal);

            $tipe = 'lainnya';

            foreach ($peta as $tipeTarget => $kunci) {
                foreach ($kunci as $kata) {
                    if (str_contains($nama, $kata)) {
                        $tipe = $tipeTarget;
                        break 2;
                    }
                }
            }

            DB::table('jadwal_ppdbs')->where('id', $baris->id)->update(['tipe' => $tipe]);
        }
    }

    public function down(): void
    {
        Schema::table('jadwal_ppdbs', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
    }
};

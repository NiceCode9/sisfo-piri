<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pindahkan tanggal tahap yang tadinya kolom di `gelombangs` menjadi baris
     * di `gelombang_tahapan`.
     *
     * Tanggal yang sudah ada disalin apa adanya — migrasi tidak boleh diam-diam
     * mengubah data. Data contoh yang keliru (tanggal tes yang sudah lewat) akan
     * dikoreksi oleh `PpdbSeeder` yang idempoten, bukan di sini.
     *
     * Dua tahap yang belum punya kolom (`verifikasi` dan `daftar_ulang`) diturunkan
     * dari `tanggal_tutup` memakai rumus yang sama dengan seeder, supaya hasil
     * backfill dan hasil seed agree.
     *
     * `tanggal_selesai` untuk tahap satu hari (tes, pengumuman) diisi NULL karena
     * NULL berarti "sama dengan tanggal_mulai" pada model.
     */
    public function up(): void
    {
        if (! Schema::hasTable('gelombangs')) {
            return;
        }

        $gelombangs = DB::table('gelombangs')->get(['id', 'tanggal_buka', 'tanggal_tutup', 'tanggal_tes', 'tanggal_pengumuman']);
        $sekarang = now();

        foreach ($gelombangs as $gelombang) {
            // Aman dijalankan ulang: lewati gelombang yang sudah punya tahap.
            if (DB::table('gelombang_tahapan')->where('gelombang_id', $gelombang->id)->exists()) {
                continue;
            }

            $tutup = Carbon::parse($gelombang->tanggal_tutup);
            $tes = $gelombang->tanggal_tes ? Carbon::parse($gelombang->tanggal_tes) : null;
            $pengumuman = $gelombang->tanggal_pengumuman ? Carbon::parse($gelombang->tanggal_pengumuman) : null;

            $tahapan = [
                [
                    'tipe' => 'pendaftaran',
                    'nama_tahap' => 'Pendaftaran Online',
                    'urutan' => 1,
                    'tanggal_mulai' => $gelombang->tanggal_buka,
                    'tanggal_selesai' => $gelombang->tanggal_tutup,
                ],
                [
                    'tipe' => 'verifikasi',
                    'nama_tahap' => 'Verifikasi Berkas',
                    'urutan' => 2,
                    'tanggal_mulai' => $tutup->copy()->addDay()->toDateString(),
                    'tanggal_selesai' => $tutup->copy()->addDays(10)->toDateString(),
                ],
            ];

            if ($tes) {
                $tahapan[] = [
                    'tipe' => 'tes',
                    'nama_tahap' => 'Tes Seleksi',
                    'urutan' => 3,
                    'tanggal_mulai' => $tes->toDateString(),
                    // Satu hari: NULL berarti sama dengan tanggal_mulai.
                    'tanggal_selesai' => null,
                ];
            }

            if ($pengumuman) {
                $tahapan[] = [
                    'tipe' => 'pengumuman',
                    'nama_tahap' => 'Pengumuman Hasil',
                    'urutan' => 4,
                    'tanggal_mulai' => $pengumuman->toDateString(),
                    'tanggal_selesai' => null,
                ];

                $tahapan[] = [
                    'tipe' => 'daftar_ulang',
                    'nama_tahap' => 'Daftar Ulang',
                    'urutan' => 5,
                    'tanggal_mulai' => $pengumuman->copy()->addDay()->toDateString(),
                    'tanggal_selesai' => $pengumuman->copy()->addDays(7)->toDateString(),
                ];
            }

            foreach ($tahapan as $tahap) {
                DB::table('gelombang_tahapan')->insert($tahap + [
                    'gelombang_id' => $gelombang->id,
                    'keterangan' => null,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Kolom lama di `gelombangs` masih ada pada titik rollback ini, jadi
     * `down()` cukup menghapus baris hasil backfill. Pengembalian ke kolom
     * dilakukan migrasi terpisah.
     */
    public function down(): void
    {
        DB::table('gelombang_tahapan')->delete();
    }
};

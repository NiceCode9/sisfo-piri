<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Longgarkan keunikan absensi dari (siswa, tanggal) menjadi
 * (siswa, rombel, tanggal).
 *
 * Constraint lama secara struktural menolak satu siswa punya lebih dari satu
 * konteks rombel pada tanggal yang sama. Itu bertentangan dengan kebutuhan
 * histori: memindahkan siswa antar rombel di tengah tahun relinquish
 * keunikan lama harus tetap menyimpan kehadiran di kelas yang ditinggalkan.
 *
 * Konsekuensi yang harus dijaga: satu siswa sekarang bisa punya dua baris
 * pada tanggal sama dengan rombel berbeda. Setiap pembacaan absensi wajib
 * memfilter `rombel_id` supaya kehadiran tidak terhitung dua kali — rekap
 * sudah begitu, tapi ini wajib diperhatikan pada query baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Urutan penting: index baru dibuat lebih dulu. MySQL memakai
        // `absensis_siswa_id_tanggal_unique` sebagai index pendukung foreign
        // key `absensis_siswa_id_foreign` (kolom paling kiri = siswa_id).
        // Melepasnya lebih dulu gagal dengan "needed in a foreign key
        // constraint". Index baru juga diawali siswa_id, jadi FK tetap punya
        // pendukung selama keduanya ada.
        Schema::table('absensis', function (Blueprint $table) {
            $table->unique(['siswa_id', 'rombel_id', 'tanggal'], 'absensis_siswa_rombel_tanggal_unique');
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_siswa_id_tanggal_unique');
        });
    }

    public function down(): void
    {
        // Kembalikan hanya bila tidak ada siswa yang tercatat di lebih dari
        // satu rombel pada tanggal sama — constraint lama tidak bisa dipasang
        // kalau datanya sudah berbeda bentuk.
        $bertabrakan = DB::table('absensis')
            ->select('siswa_id', 'tanggal')
            ->groupBy('siswa_id', 'tanggal')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($bertabrakan) {
            throw new RuntimeException(
                'Tidak bisa dikembalikan: ada siswa dengan lebih dari satu rombel pada tanggal yang sama. '
                    .'Bersihkan data terlebih dahulu.'
            );
        }

        // Sama seperti up(): index lama harus ada sebelum index baru dilepas,
        // supaya FK siswa_id tidak kehilangan pendukungnya.
        Schema::table('absensis', function (Blueprint $table) {
            $table->unique(['siswa_id', 'tanggal'], 'absensis_siswa_id_tanggal_unique');
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_siswa_rombel_tanggal_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cegah riwayat kelas ganda dengan constraint (siswa, kelas, tahun).
 *
 * Yang dikunci adalah tiga kolom itu, bukan (siswa, tahun). Kunci pada
 * (siswa, tahun) tampak logis tapi justru prohibitif terhadap kasus yang
 * sah: siswa yang pindah kelas di tengah tahun tetap punya dua baris riwayat
 * untuk satu tahun ajaran, hanya dengan kelas berbeda. Memakai kunci itu
 * membuat perpindahan kelas di tengah tahun mustahil dicatat, padahal
 * `RiwayatKelas` adalah sumber keanggotaan rombel.
 *
 * Bentuk constraint ini juga simetris dengan unik `rombels(kelas_id,
 * tahun_ajaran_id)`: satu baris per pasangan kelas + tahun, satu baris
 * per siswa di dalamnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_kelas', function (Blueprint $table) {
            $table->unique(
                ['siswa_id', 'kelas_id', 'tahun_ajaran_id'],
                'riwayat_kelas_siswa_kelas_tahun_unique'
            );
        });
    }

    public function down(): void
    {
        $duplikat = DB::table('riwayat_kelas')
            ->select('siswa_id', 'kelas_id', 'tahun_ajaran_id')
            ->groupBy('siswa_id', 'kelas_id', 'tahun_ajaran_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplikat) {
            throw new RuntimeException(
                'Tidak bisa dikembalikan: ada riwayat kelas ganda untuk pasangan siswa/kelas/tahun yang sama. '
                    .'Bersihkan data terlebih dahulu.'
            );
        }

        Schema::table('riwayat_kelas', function (Blueprint $table) {
            $table->dropUnique('riwayat_kelas_siswa_kelas_tahun_unique');
        });
    }
};

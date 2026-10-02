<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buang kolom tanggal yang sudah pindah ke `gelombang_tahapan`.
     *
     * Dipisah dari migrasi pembuatan tabel tahap karena DDL MySQL tidak
     * transaksional: kalau " tambah tabel" dan "hapus kolom" dalam satu migrasi
     * dan gagal di tengah, tabelnya bisa tertinggal dalam keadaan tidak
     * konsisten. Dengan dipecah, titik gagal di mana pun masih menyisakan
     * kolom lama yang utuh, yang tidak dibaca kode mana pun.
     *
     * Semua kode sudah berhenti membaca kolom ini sebelum migrasi ini dijalankan.
     */
    public function up(): void
    {
        Schema::table('gelombangs', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_buka',
                'tanggal_tutup',
                'tanggal_tes',
                'tanggal_pengumuman',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Kolom dikembalikan lalu diisi ulang dari baris tahap, supaya rollback
     * tidak menghilangkan data tanggal.
     */
    public function down(): void
    {
        Schema::table('gelombangs', function (Blueprint $table) {
            $table->date('tanggal_buka')->nullable()->after('badge');
            $table->date('tanggal_tutup')->nullable()->after('tanggal_buka');
            $table->date('tanggal_tes')->nullable()->after('tanggal_tutup');
            $table->date('tanggal_pengumuman')->nullable()->after('tanggal_tes');
        });

        if (! Schema::hasTable('gelombang_tahapan')) {
            return;
        }

        foreach (DB::table('gelombang_tahapan')->get() as $tahap) {
            $kolom = match ($tahap->tipe) {
                'pendaftaran' => ['tanggal_buka' => $tahap->tanggal_mulai, 'tanggal_tutup' => $tahap->tanggal_selesai],
                'tes' => ['tanggal_tes' => $tahap->tanggal_mulai],
                'pengumuman' => ['tanggal_pengumuman' => $tahap->tanggal_mulai],
                default => null,
            };

            if ($kolom) {
                DB::table('gelombangs')->where('id', $tahap->gelombang_id)->update($kolom);
            }
        }
    }
};

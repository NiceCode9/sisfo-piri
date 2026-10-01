<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sambungkan kandidat ke gelombang pendaftaran yang dipilihnya.
     *
     * Sebelumnya `gelombangs` hanya jadi data pajangan: tidak ada kolom di
     * `calon_siswas` yang menunjuknya, tidak ada gate tanggal maupun kuota, dan
     * `diskon_persen` tidak pernah dipakai. Gelombang dibuat penuh supaya
     * admin benar-benar bisa membatasi & memberi diskon per gelombang.
     *
     * Nullable + nullOnDelete: data pendaftar lama tidak punya gelombang, dan
     * administrator yang salah menghapus gelombang tidak boleh ikut menghapus
     * riwayat pendaftar.
     */
    public function up(): void
    {
        Schema::table('calon_siswas', function (Blueprint $table) {
            // MySQL membuat indeks sendiri untuk foreign key, jadi tidak perlu
            // indeks tambahan di sini.
            $table->foreignId('gelombang_id')
                ->nullable()
                ->after('jalur_pendaftaran_id')
                ->constrained('gelombangs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_siswas', function (Blueprint $table) {
            $table->dropForeign(['gelombang_id']);
            $table->dropColumn('gelombang_id');
        });
    }
};

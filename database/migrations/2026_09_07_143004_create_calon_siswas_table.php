<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calon_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jalur_pendaftaran_id')->nullable()->constrained('jalur_pendaftarans')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('no_pendaftaran')->unique(); // Format: PPDB-YYYY-XXXX
            $table->string('nik')->unique();
            $table->string('nisn')->nullable()->unique();
            $table->string('nama_lengkap');
            $table->string('jenis_kelamin'); // L/P
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('agama');
            $table->text('alamat');
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('asal_sekolah')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            $table->string('no_hp_orang_tua')->nullable();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->onDelete('cascade');
            $table->string('status_pendaftaran')->default('menunggu'); // menunggu, diterima, ditolak, daftar_ulang
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calon_siswas');
    }
};

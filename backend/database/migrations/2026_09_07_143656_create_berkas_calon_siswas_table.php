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
        Schema::create('berkas_calon_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calon_siswa_id')->constrained('calon_siswas')->onDelete('cascade');
            $table->string('ijazah_path')->nullable();
            $table->string('kk_path')->nullable();
            $table->string('akta_path')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('skl_path')->nullable(); // Surat Keterangan Lulus
            $table->string('krm_path')->nullable();
            $table->string('kip_path')->nullable();
            $table->text('catatan_berkas')->nullable();
            $table->boolean('status_verifikasi')->default(false);
            $table->json('berkas_perlu_perbaikan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas_calon_siswas');
    }
};

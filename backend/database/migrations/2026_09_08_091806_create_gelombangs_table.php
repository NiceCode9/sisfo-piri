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
        Schema::create('gelombangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->onDelete('cascade');
            $table->string('nama_gelombang');
            $table->integer('nomor_urut');
            $table->string('badge')->nullable();
            $table->date('tanggal_buka');
            $table->date('tanggal_tutup');
            $table->date('tanggal_tes')->nullable();
            $table->date('tanggal_pengumuman')->nullable();
            $table->integer('kuota');
            $table->integer('terisi')->default(0);
            $table->integer('diskon_persen')->nullable();
            $table->json('keuntungan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('warna_border')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->unique(['tahun_ajaran_id', 'nomor_urut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gelombangs');
    }
};

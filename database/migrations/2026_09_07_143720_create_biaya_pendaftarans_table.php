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
        Schema::create('biaya_pendaftarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->onDelete('cascade');
            $table->string('jenis_biaya'); // Contoh: Pendaftaran, Daftar Ulang
            $table->decimal('jumlah', 10, 2);
            $table->string('mata_uang')->default('IDR');
            $table->boolean('wajib_bayar')->default(true);
            $table->boolean('dapat_diangsur')->default(false);
            $table->integer('max_cicilan')->nullable(); // maksimal berapa kali cicilan
            $table->decimal('min_dp', 10, 2)->nullable(); // minimal down payment
            $table->integer('jangka_waktu_hari')->nullable(); // dalam hari
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biaya_pendaftarans');
    }
};

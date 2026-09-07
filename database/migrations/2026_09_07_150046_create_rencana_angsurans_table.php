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
        Schema::create('rencana_angsurans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calon_siswa_id')->constrained('calon_siswas')->onDelete('cascade');
            $table->foreignId('biaya_pendaftaran_id')->constrained('biaya_pendaftarans')->onDelete('cascade');
            $table->string('kode_angsuran')->unique(); // Format: ANG-YYYY-XXXX
            $table->decimal('total_biaya', 10, 2);
            $table->decimal('dp_dibayar', 10, 2)->default(0); // down payment yang sudah dibayar
            $table->decimal('sisa_hutang', 10, 2);
            $table->integer('jumlah_cicilan');
            $table->decimal('nominal_per_cicilan', 10, 2);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['aktif', 'lunas', 'terlambat', 'batal'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Index untuk performa
            $table->index(['calon_siswa_id', 'status']);
            $table->index('tanggal_selesai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_angsurans');
    }
};

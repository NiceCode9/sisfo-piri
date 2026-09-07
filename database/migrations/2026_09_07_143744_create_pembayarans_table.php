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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calon_siswa_id')->constrained('calon_siswas')->onDelete('cascade');
            $table->foreignId('biaya_pendaftaran_id')->nullable()->constrained('biaya_pendaftarans')->onDelete('set null');
            $table->string('kode_pembayaran')->unique();
            $table->decimal('jumlah', 10, 2);
            $table->string('metode_pembayaran'); // transfer, tunai
            $table->string('bukti_pembayaran_path')->nullable();
            $table->date('tanggal_pembayaran')->nullable();
            $table->string('status')->default('menunggu'); // menunggu, berhasil, gagal
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};

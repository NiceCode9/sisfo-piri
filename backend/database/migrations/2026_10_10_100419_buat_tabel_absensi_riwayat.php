<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_id')->constrained('absensis')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('rombel_id')->constrained('rombels')->cascadeOnDelete();

            // Didenormalisasi supaya histori bisa dibaca tanpa join absensi.
            // Absensi bisa terhapus atau berubah rombel, sementara yang perlu
            // awet adalah "koreksi ini pernah terjadi untuk hari tersebut".
            $table->date('tanggal');

            // Nilai sebelum dan sesudah. Keduanya string, bukan enum, supaya
            // baris lama tetap terbaca bila suatu saat statusnya bertambah.
            $table->string('status_sebelum', 20);
            $table->string('status_sesudah', 20);
            $table->time('jam_sebelum')->nullable();
            $table->time('jam_sesudah')->nullable();

            // Wajib diisi saat koreksi dilakukan: memaksa satu kalimat alasan
            // jauh lebih berguna daripada membiarkan perubahan tanpa jejak.
            $table->text('alasan');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['rombel_id', 'tanggal']);
            $table->index('siswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_riwayat');
    }
};

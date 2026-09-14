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
        Schema::create('detail_angsurans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rencana_angsuran_id')->constrained('rencana_angsurans')->onDelete('cascade');
            $table->integer('cicilan_ke'); // cicilan ke-1, ke-2, dst
            $table->decimal('nominal_cicilan', 10, 2);
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('denda', 10, 2)->default(0); // jika ada denda keterlambatan
            $table->decimal('total_bayar', 10, 2)->nullable(); // nominal + denda yang dibayar
            $table->date('tanggal_bayar')->nullable();
            $table->enum('status', ['belum_bayar', 'dibayar', 'terlambat'])->default('belum_bayar');
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Constraint untuk memastikan cicilan_ke unik per rencana_angsuran
            $table->unique(['rencana_angsuran_id', 'cicilan_ke']);

            // Index untuk performa
            $table->index(['status', 'tanggal_jatuh_tempo']);
            $table->index('tanggal_bayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_angsurans');
    }
};

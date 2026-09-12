<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak pengiriman notifikasi (siap untuk gateway WA Fase A4).
     */
    public function up(): void
    {
        Schema::create('notifikasi_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 30)->default('whatsapp');
            $table->string('tujuan', 30);
            $table->text('pesan');
            $table->enum('status', ['antri', 'terkirim', 'gagal'])->default('antri');
            $table->text('respons')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_logs');
    }
};

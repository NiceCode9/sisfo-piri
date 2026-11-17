<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 100);
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->json('fotos')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['jenis', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas');
    }
};

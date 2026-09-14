<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Materi E-Learning per rombel+mapel (dokumen/video file/link).
     */
    public function up(): void
    {
        Schema::create('materis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rombel_id')->constrained('rombels')->onDelete('cascade');
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->onDelete('cascade');
            $table->foreignId('guru_id')->nullable()->constrained('gurus')->onDelete('set null');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->enum('tipe', ['dokumen', 'video', 'link'])->default('dokumen');
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->index(['rombel_id', 'tipe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materis');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['guru_id', 'mata_pelajaran_id', 'nama'], 'question_banks_guru_mapel_nama_unique');
            $table->index(['mata_pelajaran_id', 'guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};

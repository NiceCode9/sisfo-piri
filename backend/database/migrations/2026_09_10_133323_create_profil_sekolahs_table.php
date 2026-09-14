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
        Schema::create('profil_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah');
            $table->string('npsn')->nullable()->unique();
            $table->text('alamat')->nullable();
            $table->string('telp')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->unsignedSmallInteger('tahun_berdiri')->nullable();
            $table->enum('akreditasi', ['A', 'B', 'C'])->nullable();
            $table->string('logo_path')->nullable();
            $table->string('foto_gedung_path')->nullable();
            $table->text('sambutan')->nullable();
            $table->text('visi')->nullable();
            $table->json('misi')->nullable();
            $table->string('nama_kepala')->nullable();
            $table->string('foto_kepala_path')->nullable();
            $table->text('maps_embed_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_sekolahs');
    }
};

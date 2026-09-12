<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautan akun orang-tua ke siswa. Satu akun boleh menaungi
     * kakak-beradik (satu baris per anak).
     */
    public function up(): void
    {
        Schema::create('wali_murids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
            $table->string('no_whatsapp', 20)->nullable();
            $table->string('hubungan', 20)->default('orang-tua');
            $table->timestamps();
            $table->unique(['user_id', 'siswa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wali_murids');
    }
};

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
        Schema::create('log_status_pendaftarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calon_siswa_id')->constrained('calon_siswas')->onDelete('cascade');
            $table->string('status_sebelumnya')->nullable();
            $table->string('status_baru')->nullable();
            $table->foreignId('user_id')->nullable()->constrained(); // Admin yang mengubah
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_status_pendaftarans');
    }
};

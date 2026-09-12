<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto profil siswa (dapat diubah sendiri dari area siswa).
     */
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('qr_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};

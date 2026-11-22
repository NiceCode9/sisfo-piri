<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_riwayat', function (Blueprint $table) {
            $table->dropForeign(['absensi_id']);
        });

        Schema::table('absensi_riwayat', function (Blueprint $table) {
            $table->foreignId('absensi_id')->nullable()->change();
        });

        Schema::table('absensi_riwayat', function (Blueprint $table) {
            $table->foreign('absensi_id')->references('id')->on('absensis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Mengembalikan cascade akan menghapus histori yang sudah diselamatkan.
    }
};

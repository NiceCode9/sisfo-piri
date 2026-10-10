<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Satu berkas per catatan: satu hari sakit/izin cukup satu surat.
            // Disimpan di disk privat `berkas` dan hanya bisa dibaca lewat route
            // yang memeriksa hak akses, sama seperti dokumen PPDB.
            $table->string('berkas_path')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn('berkas_path');
        });
    }
};

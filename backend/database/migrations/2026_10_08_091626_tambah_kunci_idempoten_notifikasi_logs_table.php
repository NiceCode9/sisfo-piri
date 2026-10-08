<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_logs', function (Blueprint $table) {
            // Kunci stabil per (tipe, siswa, rombel, tanggal, nomor). Absensi
            // diantrekan ulang setiap kali batch disimpan, jadi tanpa kunci ini
            // menyimpan dua kali akan membuat orang tua menerima pesan "alpa"
            // dua kali. Nullable + unique: MySQL mengizinkan banyak NULL, sehingga
            // log lama dan notifikasi tipe lain yang tidak memakai kunci tetap aman.
            $table->string('kunci', 64)->nullable()->unique()->after('tipe');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_logs', function (Blueprint $table) {
            $table->dropUnique(['kunci']);
            $table->dropColumn('kunci');
        });
    }
};

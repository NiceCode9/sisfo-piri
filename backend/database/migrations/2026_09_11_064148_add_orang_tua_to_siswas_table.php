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
        Schema::table('siswas', function (Blueprint $table) {
            $table->string('nama_ayah')->nullable()->after('kelas_id');
            $table->string('pekerjaan_ayah', 50)->nullable()->after('nama_ayah');
            $table->string('nama_ibu')->nullable()->after('pekerjaan_ayah');
            $table->string('pekerjaan_ibu', 50)->nullable()->after('nama_ibu');
            $table->string('no_hp_orang_tua', 20)->nullable()->after('pekerjaan_ibu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn(['nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu', 'no_hp_orang_tua']);
        });
    }
};

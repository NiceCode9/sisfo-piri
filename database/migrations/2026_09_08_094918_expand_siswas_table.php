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
            $table->foreignId('calon_siswa_id')->nullable()->constrained('calon_siswas')->onDelete('cascade')->after('id');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->after('calon_siswa_id');
            $table->string('nis')->nullable()->unique()->after('user_id');
            $table->string('nisn')->nullable()->after('nis');
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->onDelete('set null')->after('nisn');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onDelete('set null')->after('tahun_ajaran_id');
            $table->date('tanggal_diterima')->nullable()->after('kelas_id');
            $table->boolean('is_aktif')->default(true)->after('tanggal_diterima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('calon_siswa_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('nis');
            $table->dropColumn('nisn');
            $table->dropConstrainedForeignId('tahun_ajaran_id');
            $table->dropConstrainedForeignId('kelas_id');
            $table->dropColumn(['tanggal_diterima', 'is_aktif']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah status 'mengulang' (tinggal kelas) ke enum riwayat_kelas.
     * MySQL: MODIFY. SQLite membuat CHECK constraint untuk enum
     * sehingga tabel dibangun ulang agar nilai baru lolos.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE riwayat_kelas MODIFY status ENUM('aktif','lulus','pindah','dropout','mengulang') NOT NULL DEFAULT 'aktif'");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE riwayat_kelas RENAME TO riwayat_kelas_lama');
            Schema::create('riwayat_kelas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
                $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
                $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->onDelete('cascade');
                $table->enum('status', ['aktif', 'lulus', 'pindah', 'dropout', 'mengulang'])->default('aktif');
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
            DB::statement('INSERT INTO riwayat_kelas (id, siswa_id, kelas_id, tahun_ajaran_id, status, keterangan, created_at, updated_at) SELECT id, siswa_id, kelas_id, tahun_ajaran_id, status, keterangan, created_at, updated_at FROM riwayat_kelas_lama');
            Schema::drop('riwayat_kelas_lama');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE riwayat_kelas MODIFY status ENUM('aktif','lulus','pindah','dropout') NOT NULL DEFAULT 'aktif'");
        }
    }
};

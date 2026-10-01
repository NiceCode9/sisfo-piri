<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuota sebelumnya hanya punya satu pasangan angka (`kuota`/`terisi`) yang
     * dihitung saat admin menerima calon, padahal pendaftaran publik juga
     * memakainya sebagai pemblokir. Akibatnya batas "jumlah pendaftar" tidak
     * pernah bisa ditegakkan karena pencounternya baru bergerak di tahap admin.
     *
     * Dipisahkan menjadi dua:
     *   - `kuota_pendaftaran` / `terisi_pendaftaran` — batas jumlah pendaftar.
     *     `kuota_pendaftaran` NULL = tidak dibatasi.
     *   - `kuota` / `terisi` — batas penerimaan (tetap seperti sebelumnya).
     */
    public function up(): void
    {
        Schema::table('kuota_pendaftarans', function (Blueprint $table) {
            $table->unsignedInteger('kuota_pendaftaran')->nullable()->after('kuota');
            $table->unsignedInteger('terisi_pendaftaran')->default(0)->after('kuota_pendaftaran');
        });
    }

    public function down(): void
    {
        Schema::table('kuota_pendaftarans', function (Blueprint $table) {
            $table->dropColumn(['kuota_pendaftaran', 'terisi_pendaftaran']);
        });
    }
};

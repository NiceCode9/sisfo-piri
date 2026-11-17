<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calon_siswas', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->string('nik_hash', 64)->nullable()->unique()->after('nik');
        });

        // Enkripsi NIK yang masih tersimpan apa adanya (plaintext) dan isi
        // nik_hash-nya untuk keperluan pencarian/keunikan. Baris yang sudah
        // punya nik_hash (sudah terenkripsi) dilewati.
        DB::table('calon_siswas')
            ->whereNull('nik_hash')
            ->orderBy('id')
            ->chunk(100, function ($rows): void {
                foreach ($rows as $row) {
                    if ($row->nik === null || $row->nik === '') {
                        continue;
                    }

                    DB::table('calon_siswas')
                        ->where('id', $row->id)
                        ->update([
                            'nik' => Crypt::encryptString($row->nik),
                            'nik_hash' => hash('sha256', $row->nik),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('calon_siswas', function (Blueprint $table) {
            $table->dropColumn('nik_hash');
        });
    }
};

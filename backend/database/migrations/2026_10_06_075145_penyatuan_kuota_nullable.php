<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan penanda "tanpa batas" menjadi `NULL` di dua tabel.
 *
 * Sebelumnya `gelombangs` memakai `kuota <= 0` sedangkan
 * `kuota_pendaftarans` memakai `kuota IS NULL`. Dua penanda untuk satu konsep
 * di dua tempat yang berbeda, dan `0` punya arti yang berlawanan di keduanya:
 * di Gelombang berarti bebas, di KuotaPendaftaran berarti **selalu penuh**
 * (`0 >= 0`). Admin yang mengira `0` berarti "tanpa batas" malah menutup
 * jalurnya sendiri.
 *
 * Setelah migration ini satu aturan berlaku di seluruh sistem:
 *   NULL = tanpa batas, dan `0` ditolak oleh validasi.
 *
 * Yang TIDAK berubah: `kuota_pendaftaran` sudah nullable sejak migration
 * split kuota, jadi jalur yang tidak membatasi jumlah pendaftar tetap bisa
 * dikosongkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gelombangs', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable()->change();
        });

        Schema::table('kuota_pendaftarans', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Baris NULL tidak bisa kembali NOT NULL tanpa keputusan. Arahi ke 0
        // karena itu penanda "tanpa batas" pada semantik Gelombang lama, supaya
        // jalur yang tadinya bebas tetap bebas setelah rollback.
        DB::table('gelombangs')->whereNull('kuota')->update(['kuota' => 0]);
        DB::table('kuota_pendaftarans')->whereNull('kuota')->update(['kuota' => 0]);

        Schema::table('gelombangs', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable(false)->change();
        });

        Schema::table('kuota_pendaftarans', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable(false)->change();
        });
    }
};

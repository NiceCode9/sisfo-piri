<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan yang sudah dibatalkan tidak boleh dihitung sebagai tunggakan.
     * Enum `status` pada pembayarans perlu nilai `batal` supaya status tersebut
     * bisa disimpan (MySQL menolak nilai di luar daftar enum).
     */
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'berhasil', 'gagal', 'batal'])
                ->default('menunggu')
                ->change();
        });

        Schema::table('pembayaran_lainnyas', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'berhasil', 'gagal', 'batal'])
                ->default('menunggu')
                ->change();
        });
    }

    public function down(): void
    {
        // Baris yang sudah dibatalkan tidak muat di enum lama, jadi dibersihkan
        // lebih dulu agar rollback tidak gagal.
        DB::table('pembayarans')->where('status', 'batal')->update(['status' => 'gagal']);
        DB::table('pembayaran_lainnyas')->where('status', 'batal')->update(['status' => 'gagal']);

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'berhasil', 'gagal'])
                ->default('menunggu')
                ->change();
        });

        Schema::table('pembayaran_lainnyas', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'berhasil', 'gagal'])
                ->default('menunggu')
                ->change();
        });
    }
};

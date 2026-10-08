<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus `jadwal_ppdbs`.
 *
 * Tabel ini berhenti punya fungsi sejak status pendaftaran dipusatkan ke
 * `Gelombang` (lihat `app/Support/StatusPendaftaran.php`). Isinya juga bukan
 * data mandiri: `PpdbSeeder::seedJadwal()` menyalin `gelombang_tahapan`
 * milik Gelombang 1 ke sini, jadi setiap baris punya kembarannya yang
 * justru dipakai sistem.
 *
 * Dua sisa yang perlu dibongkar bersama migration ini:
 *
 *  1. Admin masih bisa mengedit jadwal yang tidak dibaca halaman mana pun.
 *     Kalau tanggalnya diubah, halaman publik tetap memakai tanggal Gelombang
 *     — perubahan itu hilang tanpa satu pun tanda error.
 *  2. `TahunAjaranController::destroy()` memakai keberadaan baris di tabel ini
 *     sebagai alasan menolak hapus tahun ajaran. Padahal baris itu salinan
 *     otomatis yang dibuat ulang tiap `db:seed`, sehingga tahun ajaran dengan
 *     Gelombang praktis mustahil dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('jadwal_ppdbs');
    }

    public function down(): void
    {
        Schema::create('jadwal_ppdbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->onDelete('cascade');
            $table->string('nama_jadwal');
            $table->enum('tipe', ['pendaftaran', 'verifikasi', 'tes', 'pengumuman', 'daftar_ulang', 'lainnya'])->default('lainnya');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }
};

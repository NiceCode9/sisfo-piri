<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahapan (fase) tiap gelombang pendaftaran.
     *
     * Sebelumnya tanggal tiap tahap disimpan sebagai kolom di `gelombangs`
     * (`tanggal_buka`, `tanggal_tutup`, `tanggal_tes`, `tanggal_pengumuman`).
     * Akibatnya menambah tahap baru selalu butuh migrasi, dan tahap yang tidak
     * diisi menyisakan kolom NULL yang harus dijaga satu per satu di view.
     *
     * Dipindah ke tabel anak supaya:
     *  - satu tahap = satu baris, jadi "tidak diisi" berarti "tidak ada baris"
     *    dan tidak mungkin ada tahap yang separuh terisi;
     *  - menambah jenis tahap cukup dengan menambah baris, bukan kolom;
     *  - bentuknya sama dengan `jadwal_ppdbs` yang sudah ada.
     *
     * Kolom lama di `gelombangs` sengaja dibiarkan dulu dan baru di-*drop* di
     * migrasi terpisah, setelah semua kode berhenti membacanya. DDL MySQL tidak
     * transaksional, jadi memisahkan "tambah" dari "hapus" membuat kegagalan di
     * tengah tidak meninggalkan tabel yang rusak.
     */
    public function up(): void
    {
        Schema::create('gelombang_tahapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gelombang_id')->constrained('gelombangs')->cascadeOnDelete();

            // Enum yang sama persis dengan `jadwal_ppdbs.tipe` supaya kedua
            // tabel memakai kosakata tahap yang sama.
            $table->enum('tipe', [
                'pendaftaran',
                'verifikasi',
                'tes',
                'pengumuman',
                'daftar_ulang',
                'lainnya',
            ])->default('lainnya');

            $table->string('nama_tahap');

            // Urutan tampil, bukan urutan kronologis. Timeline publik bergantian
            // kiri-kanan berdasarkan urutan, jadi penentuannya harus eksplisit.
            $table->unsignedSmallInteger('urutan');

            $table->date('tanggal_mulai');

            // Nullable: tahap satu hari (tes, pengumuman) cukup tanggal awal.
            // null berarti tahap berlangsung satu hari, sama dengan tanggal_mulai.
            $table->date('tanggal_selesai')->nullable();

            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['gelombang_id', 'urutan']);
            $table->index(['gelombang_id', 'tipe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gelombang_tahapan');
    }
};

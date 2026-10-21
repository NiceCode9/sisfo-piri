<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur QR absen mandiri (Fase A5): tiga kunci jendela scan ditambahkan,
     * batas terlambat digeser ke 07:15 karena siswa kini menentukan sendiri
     * saatanya melewati gerbang waktu tanpa antre perangkat piket, dan kartu
     * QR statis milik siswa dibuang bersama kolomnya karena tidak ada lagi
     * pemindai kartu.
     */
    public function up(): void
    {
        DB::table('pengaturans')->insert([
            ['kunci' => 'jam_buka_absen', 'nilai' => '07:00', 'keterangan' => 'Jam scan absen mandiri dibuka (HH:MM).', 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'jam_tutup_absen', 'nilai' => '07:45', 'keterangan' => 'Jam scan absen mandiri ditutup (HH:MM).', 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'menit_ganti_qr', 'nilai' => '10', 'keterangan' => 'Rentang menit QR harian guru berganti (anti foto QR).', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Nilai kustom admin dijaga: hanya bawaan lama 07:00 yang dinaikkan.
        $batas = DB::table('pengaturans')->where('kunci', 'batas_terlambat')->first();

        if ($batas === null) {
            DB::table('pengaturans')->insert([
                ['kunci' => 'batas_terlambat', 'nilai' => '07:15', 'keterangan' => 'Batas jam datang (HH:MM); lewat dari ini tercatat terlambat.', 'created_at' => now(), 'updated_at' => now()],
            ]);
        } elseif ($batas->nilai === '07:00') {
            DB::table('pengaturans')->where('kunci', 'batas_terlambat')->update(['nilai' => '07:15', 'updated_at' => now()]);
        }

        // SQLite menolak `drop column` bila masih ada indeks yang memuat
        // kolomnya. Indeks unik qr_token dibongkar dulu secara eksplisit;
        // MySQL ikut dijalankan karena `drop column` di sana memang
        // menghapus indeks satu kolom bersanding dengan kolomnya, tapi
        // menukar dependensi itu dengan perintah eksplisit membuat urutan
        // tidak bergantung pada perilaku dialect.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS siswas_qr_token_unique');
        } else {
            DB::statement('ALTER TABLE siswas DROP INDEX siswas_qr_token_unique');
        }

        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique();
        });
    }
};

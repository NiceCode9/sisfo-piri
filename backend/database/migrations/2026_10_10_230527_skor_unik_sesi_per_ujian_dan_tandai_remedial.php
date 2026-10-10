<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menutup celah ujian berulang dan memberi cara remedial yang eksplisit.
 *
 * SEBELUM: `unique(['exam_token_id', 'user_id'])` membuat "sudah menyelesaikan
 * ujian ini" hanya berlaku per token. Guru membuat token kedua untuk jendela
 * waktu berikutnya, siswa memakai token itu, dan boleh ikut lagi.
 *
 * SESUDAH: uniqueness pindah ke `exam_id`, sehingga satu siswa hanya punya
 * satu sesi per ujian, token berapa pun yang ia pakai.
 *
 * Remedial sekarang harus dibuat lewat baris `exams` baru yang ditandai
 * `is_remedial` + menunjuk `remedial_of_id`. Tanpa penanda itu, rapor tidak
 * bisa membedakan UH-1 dari UH-2 (dua penilaian biasa) dari ujian remedial —
 * mengelompokkan per mapel saja akan membuang nilai sah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // `exam_sessions_exam_token_id_user_id_unique` harus dilepas sebelum FK
            // `exam_token_id`. SQLite tidak complained, jadi test suite
            // tidak menangkap ini.
            $table->dropForeign(['exam_token_id']);
            $table->dropUnique(['exam_token_id', 'user_id']);

            $table->unique(['exam_id', 'user_id']);

            $table->foreign('exam_token_id')
                ->references('id')
                ->on('exam_tokens')
                ->cascadeOnDelete();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('is_remedial')->default(false)->after('status');
            $table->foreignId('remedial_of_id')
                ->nullable()
                ->after('is_remedial')
                ->constrained('exams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('remedial_of_id');
            $table->dropColumn('is_remedial');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            // Kedua FK harus dilepas lebih dulu: index unik yang baru
            // (`exam_id, user_id`) menjadi index pendukung FK `exam_id`, jadi
            // MySQL menolak melepasnya selama FK itu masih ada (error 1553).
            $table->dropForeign(['exam_id']);
            $table->dropForeign(['exam_token_id']);
            $table->dropUnique(['exam_id', 'user_id']);

            $table->unique(['exam_token_id', 'user_id']);

            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->cascadeOnDelete();

            $table->foreign('exam_token_id')
                ->references('id')
                ->on('exam_tokens')
                ->cascadeOnDelete();
        });
    }
};

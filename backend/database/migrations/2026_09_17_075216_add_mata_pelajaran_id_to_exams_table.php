<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('mata_pelajaran_id')->nullable()->after('rombel_id')->constrained('mata_pelajarans')->nullOnDelete();
            $table->index(['rombel_id', 'mata_pelajaran_id'], 'exams_rombel_mapel_index');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['mata_pelajaran_id']);
            $table->dropIndex('exams_rombel_mapel_index');
            $table->dropColumn('mata_pelajaran_id');
        });
    }
};

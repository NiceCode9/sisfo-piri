<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengampus', function (Blueprint $table) {
            $table->foreignId('rombel_id')->nullable()->after('tahun_ajaran_id')
                ->constrained('rombels')->onDelete('cascade');
        });

        // Backfill: tiap pasangan unik (kelas, tahun) dari pengampus,
        // wali_kelas, dan riwayat_kelas menjadi 1 baris rombel,
        // lalu tautkan pengampus yang cocok. Idempoten.
        $pasangan = DB::table('pengampus')->select('kelas_id', 'tahun_ajaran_id')->distinct()->get()
            ->concat(DB::table('wali_kelas')->select('kelas_id', 'tahun_ajaran_id')->distinct()->get())
            ->concat(DB::table('riwayat_kelas')->select('kelas_id', 'tahun_ajaran_id')->distinct()->get())
            ->unique(fn ($row) => $row->kelas_id.'-'.$row->tahun_ajaran_id)
            ->values();

        foreach ($pasangan as $row) {
            $wali = DB::table('wali_kelas')
                ->where('kelas_id', $row->kelas_id)
                ->where('tahun_ajaran_id', $row->tahun_ajaran_id)
                ->value('guru_id');

            $rombelId = DB::table('rombels')->insertGetId([
                'kelas_id' => $row->kelas_id,
                'tahun_ajaran_id' => $row->tahun_ajaran_id,
                'wali_guru_id' => $wali,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('pengampus')
                ->where('kelas_id', $row->kelas_id)
                ->where('tahun_ajaran_id', $row->tahun_ajaran_id)
                ->whereNull('rombel_id')
                ->update(['rombel_id' => $rombelId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengampus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rombel_id');
        });
    }
};

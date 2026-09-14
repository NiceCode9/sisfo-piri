<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cutover pengampus ke rombel: tautkan baris yang masih null,
     * kunci unique per rombel, lalu lepas kolom pasangan lama.
     */
    public function up(): void
    {
        // 1. Tautkan sisa pengampus tanpa rombel (buat rombel bila perlu).
        foreach (DB::table('pengampus')->whereNull('rombel_id')->get() as $p) {
            $rombelId = DB::table('rombels')->where('kelas_id', $p->kelas_id)
                ->where('tahun_ajaran_id', $p->tahun_ajaran_id)
                ->value('id');

            if (! $rombelId) {
                $rombelId = DB::table('rombels')->insertGetId([
                    'kelas_id' => $p->kelas_id,
                    'tahun_ajaran_id' => $p->tahun_ajaran_id,
                    'wali_guru_id' => DB::table('wali_kelas')
                        ->where('kelas_id', $p->kelas_id)
                        ->where('tahun_ajaran_id', $p->tahun_ajaran_id)
                        ->value('guru_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('pengampus')->where('id', $p->id)->update(['rombel_id' => $rombelId]);
        }

        // 2. Kunci baru: satu mapel satu guru per rombel.
        Schema::table('pengampus', function (Blueprint $table) {
            $table->unique(['mata_pelajaran_id', 'rombel_id'], 'pengampu_mapel_rombel_unique');
        });

        // 3. Lepas FK + unique lama + kolom pasangan lama.
        // SQLite memproses dropForeign/dropIndex saat rebuild tabel,
        // jadi semuanya digabung sebelum drop kolom.
        // MySQL: JANGAN drop index komposit eksplisit (8.0 menolak walau
        // tanpa FK) — cukup lepas FK-nya, drop kolom langsung,
        // MySQL menyesuaikan index sendiri.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pengampus', function (Blueprint $table) {
                $table->dropIndex(['guru_id', 'tahun_ajaran_id']);
                $table->dropForeign(['kelas_id']);
                $table->dropForeign(['tahun_ajaran_id']);
            });
        } else {
            Schema::table('pengampus', function (Blueprint $table) {
                $table->dropForeign(['kelas_id']);
                $table->dropForeign(['tahun_ajaran_id']);
            });
        }
        Schema::table('pengampus', function (Blueprint $table) {
            $table->dropUnique('pengampu_mapel_kelas_tahun_unique');
            $table->dropColumn(['kelas_id', 'tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengampus', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onDelete('cascade');
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->onDelete('cascade');
        });

        foreach (DB::table('pengampus')->whereNotNull('rombel_id')->get() as $p) {
            $rombel = DB::table('rombels')->find($p->rombel_id);

            if ($rombel) {
                DB::table('pengampus')->where('id', $p->id)->update([
                    'kelas_id' => $rombel->kelas_id,
                    'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
                ]);
            }
        }

        Schema::table('pengampus', function (Blueprint $table) {
            $table->dropUnique('pengampu_mapel_rombel_unique');
            $table->unique(['mata_pelajaran_id', 'kelas_id', 'tahun_ajaran_id'], 'pengampu_mapel_kelas_tahun_unique');
        });
    }
};

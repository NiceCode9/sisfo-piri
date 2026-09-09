<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('berkas_calon_siswas', function (Blueprint $table) {
            $table->string('krm_path')->nullable()->after('skl_path');
            $table->string('kip_path')->nullable()->after('krm_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas_calon_siswas', function (Blueprint $table) {
            $table->dropColumn(['krm_path', 'kip_path']);
        });
    }
};

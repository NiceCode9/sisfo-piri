<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('nama');
            $table->date('tanggal_lahir')->nullable()->after('foto_path');
            $table->string('jabatan', 100)->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn(['foto_path', 'tanggal_lahir', 'jabatan']);
        });
    }
};

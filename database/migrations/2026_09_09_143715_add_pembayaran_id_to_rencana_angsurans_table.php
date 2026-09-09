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
        Schema::table('rencana_angsurans', function (Blueprint $table) {
            // Tagihan induk tempat rencana ini dibuat
            $table->foreignId('pembayaran_id')->nullable()->after('biaya_pendaftaran_id')
                ->constrained('pembayarans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rencana_angsurans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pembayaran_id');
        });
    }
};

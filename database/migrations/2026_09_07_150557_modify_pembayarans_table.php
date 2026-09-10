<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengecualian konsolidasi: FK ke detail_angsurans harus di file
     * terpisah karena tabel detail_angsurans dibuat SETELAH pembayarans
     * (urutan timestamp). Kolom non-FK sudah di file create.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('pembayarans', 'detail_angsuran_id')) {
            Schema::table('pembayarans', function (Blueprint $table) {
                $table->foreignId('detail_angsuran_id')->nullable()->after('biaya_pendaftaran_id')
                    ->constrained('detail_angsurans')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detail_angsuran_id');
        });
    }
};

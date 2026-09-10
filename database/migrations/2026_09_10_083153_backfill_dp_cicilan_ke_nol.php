<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Buatkan baris detail cicilan_ke = 0 (DP) untuk rencana lama
     * yang DP-nya belum terhubung. Idempoten: lewati bila sudah ada.
     */
    public function up(): void
    {
        $rencanas = DB::table('rencana_angsurans')
            ->where('dp_dibayar', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('detail_angsurans')
                    ->whereColumn('detail_angsurans.rencana_angsuran_id', 'rencana_angsurans.id')
                    ->where('detail_angsurans.cicilan_ke', 0);
            })
            ->get();

        foreach ($rencanas as $rencana) {
            $detailId = DB::table('detail_angsurans')->insertGetId([
                'rencana_angsuran_id' => $rencana->id,
                'cicilan_ke' => 0,
                'nominal_cicilan' => $rencana->dp_dibayar,
                'tanggal_jatuh_tempo' => $rencana->created_at,
                'denda' => 0,
                'total_bayar' => $rencana->dp_dibayar,
                'tanggal_bayar' => $rencana->created_at,
                'status' => 'dibayar',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('pembayarans')
                ->where('calon_siswa_id', $rencana->calon_siswa_id)
                ->where('biaya_pendaftaran_id', $rencana->biaya_pendaftaran_id)
                ->where('jenis_pembayaran', 'dp_angsuran')
                ->whereNull('detail_angsuran_id')
                ->update(['detail_angsuran_id' => $detailId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus baris DP ke-0 hasil backfill beserta tautannya.
        $ids = DB::table('detail_angsurans')->where('cicilan_ke', 0)->pluck('id');

        DB::table('pembayarans')->whereIn('detail_angsuran_id', $ids)->update(['detail_angsuran_id' => null]);
        DB::table('detail_angsurans')->whereIn('id', $ids)->delete();
    }
};

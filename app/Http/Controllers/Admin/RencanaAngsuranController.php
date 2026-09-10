<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRencanaAngsuranRequest;
use App\Models\DetailAngsuran;
use App\Models\Pembayaran;
use App\Models\RencanaAngsuran;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class RencanaAngsuranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:rencana-angsurans.create', only: ['store']),
            new Middleware('permission:rencana-angsurans.edit', only: ['updateDenda']),
            new Middleware('permission:rencana-angsurans.delete', only: ['batal']),
        ];
    }

    /**
     * Buat rencana angsuran dari tagihan + catat DP.
     */
    public function store(StoreRencanaAngsuranRequest $request, Pembayaran $pembayaran): RedirectResponse
    {
        $biaya = $pembayaran->biayaPendaftaran;

        if (! $biaya || ! $biaya->dapat_diangsur) {
            return back()->with('error', 'Biaya ini tidak dapat diangsur.')->withInput();
        }

        if ($pembayaran->status !== 'menunggu') {
            return back()->with('error', 'Hanya tagihan menunggu yang dapat dijadikan angsuran.')->withInput();
        }

        $aktif = RencanaAngsuran::where('calon_siswa_id', $pembayaran->calon_siswa_id)
            ->where('biaya_pendaftaran_id', $pembayaran->biaya_pendaftaran_id)
            ->where('status', 'aktif')
            ->exists();

        if ($aktif) {
            return back()->with('error', 'Sudah ada rencana angsuran aktif untuk tagihan ini.')->withInput();
        }

        $validated = $request->validated();
        $total = (float) $pembayaran->jumlah;
        $dp = (float) $validated['dp_dibayar'];
        $n = (int) $validated['jumlah_cicilan'];

        if ($dp < (float) ($biaya->min_dp ?? 0)) {
            return back()->withErrors(['dp_dibayar' => 'DP minimal Rp '.number_format($biaya->min_dp, 0, ',', '.').'.'])->withInput();
        }

        if ($dp > $total) {
            return back()->withErrors(['dp_dibayar' => 'DP tidak boleh melebihi total tagihan.'])->withInput();
        }

        if ($biaya->max_cicilan && $n > $biaya->max_cicilan) {
            return back()->withErrors(['jumlah_cicilan' => "Maksimal {$biaya->max_cicilan} cicilan untuk biaya ini."])->withInput();
        }

        $sisa = $total - $dp;

        if ($sisa <= 0) {
            return back()->with('error', 'DP sudah melunasi tagihan, tidak perlu angsuran.')->withInput();
        }

        DB::transaction(function () use ($validated, $pembayaran, $total, $dp, $n, $sisa) {
            // Catat DP sebagai pembayaran (detail_angsuran_id diisi setelah baris DP ke-0 dibuat)
            $dpBayar = Pembayaran::create([
                'calon_siswa_id' => $pembayaran->calon_siswa_id,
                'biaya_pendaftaran_id' => $pembayaran->biaya_pendaftaran_id,
                'kode_pembayaran' => $this->generateKode(),
                'jumlah' => $dp,
                'metode_pembayaran' => $pembayaran->metode_pembayaran,
                'jenis_pembayaran' => 'dp_angsuran',
                'tanggal_pembayaran' => now()->toDateString(),
                'status' => 'berhasil',
                'keterangan_angsuran' => 'DP angsuran untuk '.$pembayaran->kode_pembayaran,
            ]);

            $rencana = RencanaAngsuran::create([
                'calon_siswa_id' => $pembayaran->calon_siswa_id,
                'biaya_pendaftaran_id' => $pembayaran->biaya_pendaftaran_id,
                'pembayaran_id' => $pembayaran->id,
                'kode_angsuran' => $this->generateKodeAngsuran(),
                'total_biaya' => $total,
                'dp_dibayar' => $dp,
                'sisa_hutang' => $sisa,
                'jumlah_cicilan' => $n,
                'nominal_per_cicilan' => $sisa / $n,
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => Carbon::parse($validated['tanggal_mulai'])->addMonthsNoOverflow($n - 1)->toDateString(),
                'status' => 'aktif',
                'catatan' => $validated['catatan'] ?? null,
            ]);

            // Baris ke-0 = DP, ikut termasuk sebagai angsuran (sudah dibayar)
            $dpDetail = null;
            if ($dp > 0) {
                $dpDetail = $rencana->detailAngsuran()->create([
                    'cicilan_ke' => 0,
                    'nominal_cicilan' => $dp,
                    'tanggal_jatuh_tempo' => now()->toDateString(),
                    'denda' => 0,
                    'total_bayar' => $dp,
                    'tanggal_bayar' => now()->toDateString(),
                    'status' => 'dibayar',
                ]);
                $dpBayar->update(['detail_angsuran_id' => $dpDetail->id]);
            }

            // Nominal per cicilan sama besar, selisih pembulatan di cicilan terakhir
            $perCicilan = floor($sisa / $n);
            $mulai = Carbon::parse($validated['tanggal_mulai']);

            for ($i = 1; $i <= $n; $i++) {
                $nominal = $i === $n ? $sisa - ($perCicilan * ($n - 1)) : $perCicilan;

                $rencana->detailAngsuran()->create([
                    'cicilan_ke' => $i,
                    'nominal_cicilan' => $nominal,
                    'tanggal_jatuh_tempo' => $mulai->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                    'denda' => 0,
                    'status' => 'belum_bayar',
                ]);
            }
        });

        return redirect()->route('admin.pembayarans.show', $pembayaran)->with('success', 'Rencana angsuran berhasil dibuat beserta jadwal cicilan.');
    }

    /**
     * Ubah denda satu cicilan (manual oleh admin).
     */
    public function updateDenda(Request $request, DetailAngsuran $detail): RedirectResponse
    {
        $request->validate(['denda' => ['required', 'numeric', 'min:0']]);

        if ($detail->status === 'dibayar') {
            return back()->with('error', 'Cicilan yang sudah dibayar tidak dapat diubah dendanya.');
        }

        $detail->update(['denda' => $request->input('denda')]);

        return back()->with('success', 'Denda cicilan ke-'.$detail->cicilan_ke.' diperbarui.');
    }

    /**
     * Batalkan rencana (hanya bila belum ada cicilan dibayar).
     */
    public function batal(RencanaAngsuran $rencana): RedirectResponse
    {
        if ($rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->where('status', 'dibayar')->exists()) {
            return back()->with('error', 'Rencana tidak dapat dibatalkan karena sudah ada cicilan yang dibayar.');
        }

        $rencana->update(['status' => 'batal']);

        return back()->with('success', "Rencana {$rencana->kode_angsuran} dibatalkan.");
    }

    private function generateKode(): string
    {
        $year = date('Y');
        $count = Pembayaran::whereYear('created_at', $year)->count() + 1;

        return sprintf('PAY-%s-%04d', $year, $count);
    }

    private function generateKodeAngsuran(): string
    {
        $year = date('Y');
        $count = RencanaAngsuran::whereYear('created_at', $year)->count() + 1;

        return sprintf('ANG-%s-%04d', $year, $count);
    }
}

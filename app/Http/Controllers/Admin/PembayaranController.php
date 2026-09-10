<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PembayaransExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePembayaranRequest;
use App\Http\Requests\Admin\UpdatePembayaranRequest;
use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\DetailAngsuran;
use App\Models\Pembayaran;
use App\Models\RencanaAngsuran;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PembayaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pembayarans.view', only: ['index', 'show', 'exportExcel', 'exportPdf']),
            new Middleware('permission:pembayarans.create', only: ['create', 'store']),
            new Middleware('permission:pembayarans.edit', only: ['edit', 'update', 'updateStatus']),
            new Middleware('permission:pembayarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $pembayarans = $this->filteredQuery()->latest()->paginate(10)->withQueryString();

        return view('admin.pembayarans.index', compact('pembayarans'));
    }

    /**
     * Query daftar pembayaran mengikuti filter aktif
     * (dipakai index + export agar konsisten).
     */
    protected function filteredQuery(): Builder
    {
        return Pembayaran::with(['calonSiswa', 'biayaPendaftaran', 'detailAngsuran'])
            ->when(request('search'), fn ($q, $s) => $q->where('kode_pembayaran', 'like', "%{$s}%")
                ->orWhereHas('calonSiswa', fn ($qq) => $qq->where('nama_lengkap', 'like', "%{$s}%")))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->when(request('tanggal_mulai'), fn ($q, $v) => $q->whereDate('tanggal_pembayaran', '>=', $v))
            ->when(request('tanggal_sampai'), fn ($q, $v) => $q->whereDate('tanggal_pembayaran', '<=', $v));
    }

    public function exportExcel(): BinaryFileResponse
    {
        $pembayarans = $this->filteredQuery()->latest()->get();

        return Excel::download(
            new PembayaransExport($pembayarans),
            'pembayaran-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf(): Response
    {
        $pembayarans = $this->filteredQuery()->latest()->get();

        return Pdf::loadView('admin.pembayarans.pdf', [
            'pembayarans' => $pembayarans,
            'total' => $pembayarans->sum('jumlah'),
            'filters' => request()->only(['search', 'status', 'tanggal_mulai', 'tanggal_sampai']),
        ])->download('pembayaran-'.now()->format('Ymd-His').'.pdf');
    }

    public function create(): View
    {
        return view('admin.pembayarans.create', [
            'calons' => $this->calonBelumLunas(),
            'biayas' => BiayaPendaftaran::orderBy('jenis_biaya')->get(),
        ]);
    }

    public function store(StorePembayaranRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Validasi tautan cicilan: milik calon yang sama dan belum dibayar
        if (! empty($validated['detail_angsuran_id'])) {
            $detail = DetailAngsuran::with('rencanaAngsuran')->findOrFail($validated['detail_angsuran_id']);

            if ($detail->rencanaAngsuran->calon_siswa_id !== (int) $validated['calon_siswa_id']) {
                return back()->with('error', 'Cicilan tidak milik calon siswa ini.')->withInput();
            }

            if ($detail->status === 'dibayar') {
                return back()->with('error', 'Cicilan ini sudah dibayar.')->withInput();
            }

            $validated['jenis_pembayaran'] = 'cicilan_angsuran';
        }

        if ($request->boolean('buat_angsuran')) {
            if (! empty($validated['detail_angsuran_id'])) {
                return back()->with('error', 'Pilih salah satu: bayar cicilan yang ada atau buat rencana angsuran baru.')->withInput();
            }

            return $this->storeDenganAngsuran($request, $validated);
        }

        $validated['kode_pembayaran'] = $this->generateKode();
        // Input admin selalu langsung berhasil (bukti wajib untuk transfer).
        $validated['status'] = 'berhasil';
        $validated['jenis_pembayaran'] = $validated['jenis_pembayaran'] ?? 'penuh';

        if ($request->hasFile('bukti_pembayaran_path')) {
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran_path')->store('bukti', 'public');
            $validated['tanggal_pembayaran'] = $validated['tanggal_pembayaran'] ?? now()->toDateString();
        }

        $pembayaran = Pembayaran::create(collect($validated)->except(['buat_angsuran', 'dp_dibayar', 'jumlah_cicilan', 'tanggal_mulai', 'redirect_to'])->toArray());

        // Pembayaran cicilan yang langsung berhasil menutup detail + rencana
        // (tutupCicilan melunasi induk otomatis saat cicilan terakhir dibayar).
        if ($pembayaran->jenis_pembayaran === 'cicilan_angsuran'
            && $pembayaran->detail_angsuran_id
            && $pembayaran->status === 'berhasil'
        ) {
            $this->tutupCicilan($pembayaran->fresh());
        }

        return $this->redirectAfterStore($request, "Pembayaran {$pembayaran->kode_pembayaran} berhasil dibuat.");
    }

    /**
     * Satu langkah: buat tagihan induk + DP + rencana + jadwal cicilan.
     */
    protected function storeDenganAngsuran(StorePembayaranRequest $request, array $validated): RedirectResponse
    {
        $biaya = BiayaPendaftaran::find($validated['biaya_pendaftaran_id'] ?? null);

        if (! $biaya || ! $biaya->dapat_diangsur) {
            return back()->with('error', 'Angsuran hanya untuk biaya yang dapat diangsur.')->withInput();
        }

        $total = (float) $validated['jumlah'];
        $dp = (float) ($validated['dp_dibayar'] ?? 0);
        $n = (int) ($validated['jumlah_cicilan'] ?? 0);

        if ($dp < (float) ($biaya->min_dp ?? 0)) {
            return back()->withErrors(['dp_dibayar' => 'DP minimal Rp '.number_format($biaya->min_dp, 0, ',', '.').'.'])->withInput();
        }

        if ($dp >= $total) {
            return back()->withErrors(['dp_dibayar' => 'DP harus lebih kecil dari total tagihan.'])->withInput();
        }

        if ($biaya->max_cicilan && $n > $biaya->max_cicilan) {
            return back()->withErrors(['jumlah_cicilan' => "Maksimal {$biaya->max_cicilan} cicilan untuk biaya ini."])->withInput();
        }

        $duplikat = RencanaAngsuran::where('calon_siswa_id', $validated['calon_siswa_id'])
            ->where('biaya_pendaftaran_id', $biaya->id)
            ->where('status', 'aktif')
            ->exists();

        if ($duplikat) {
            return back()->with('error', 'Sudah ada rencana angsuran aktif untuk calon dan biaya ini.')->withInput();
        }

        $sisa = $total - $dp;

        DB::transaction(function () use ($request, $validated, $biaya, $total, $dp, $n, $sisa) {
            $induk = Pembayaran::create([
                'calon_siswa_id' => $validated['calon_siswa_id'],
                'biaya_pendaftaran_id' => $biaya->id,
                'kode_pembayaran' => $this->generateKode(),
                'jumlah' => $total,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'jenis_pembayaran' => 'penuh',
                'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? null,
                'status' => 'menunggu',
                'catatan' => $validated['catatan'] ?? null,
                'keterangan_angsuran' => $validated['keterangan_angsuran'] ?? null,
            ]);

            $dpBayar = null;
            if ($dp > 0) {
                $bukti = $request->hasFile('bukti_pembayaran_path')
                    ? $request->file('bukti_pembayaran_path')->store('bukti', 'public')
                    : null;

                $dpBayar = Pembayaran::create([
                    'calon_siswa_id' => $validated['calon_siswa_id'],
                    'biaya_pendaftaran_id' => $biaya->id,
                    'kode_pembayaran' => $this->generateKode(),
                    'jumlah' => $dp,
                    'metode_pembayaran' => $validated['metode_pembayaran'],
                    'jenis_pembayaran' => 'dp_angsuran',
                    'bukti_pembayaran_path' => $bukti,
                    'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? now()->toDateString(),
                    'status' => 'berhasil',
                    'keterangan_angsuran' => 'DP angsuran untuk '.$induk->kode_pembayaran,
                ]);
            }

            $rencana = RencanaAngsuran::create([
                'calon_siswa_id' => $validated['calon_siswa_id'],
                'biaya_pendaftaran_id' => $biaya->id,
                'pembayaran_id' => $induk->id,
                'kode_angsuran' => $this->generateKodeAngsuran(),
                'total_biaya' => $total,
                'dp_dibayar' => $dp,
                'sisa_hutang' => $sisa,
                'jumlah_cicilan' => $n,
                'nominal_per_cicilan' => $sisa / $n,
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => Carbon::parse($validated['tanggal_mulai'])->addMonthsNoOverflow($n - 1)->toDateString(),
                'status' => 'aktif',
            ]);

            // Baris ke-0 = DP, ikut termasuk sebagai angsuran (sudah dibayar)
            if ($dpBayar) {
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

            $perCicilan = floor($sisa / $n);
            $mulai = Carbon::parse($validated['tanggal_mulai']);

            for ($i = 1; $i <= $n; $i++) {
                $rencana->detailAngsuran()->create([
                    'cicilan_ke' => $i,
                    'nominal_cicilan' => $i === $n ? $sisa - ($perCicilan * ($n - 1)) : $perCicilan,
                    'tanggal_jatuh_tempo' => $mulai->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                    'denda' => 0,
                    'status' => 'belum_bayar',
                ]);
            }
        });

        return $this->redirectAfterStore($request, "Tagihan + rencana angsuran {$n}x berhasil dibuat.");
    }

    /**
     * Kembali ke show calon bila simpan via modal show,
     * selain itu ke index pembayaran.
     */
    protected function redirectAfterStore(Request $request, string $message): RedirectResponse
    {
        $redirectTo = $request->input('redirect_to');

        if ($redirectTo && Str::startsWith($redirectTo, url('/admin/calon-siswas/'))) {
            return redirect($redirectTo)->with('success', $message);
        }

        return redirect()->route('admin.pembayarans.index')->with('success', $message);
    }

    public function show(Pembayaran $pembayaran): View
    {
        $pembayaran->load(['calonSiswa.jalurPendaftaran', 'biayaPendaftaran', 'detailAngsuran']);

        $rencana = RencanaAngsuran::with('detailAngsuran')
            ->where('pembayaran_id', $pembayaran->id)
            ->latest()
            ->first();

        return view('admin.pembayarans.show', compact('pembayaran', 'rencana'));
    }

    public function edit(Pembayaran $pembayaran): View
    {
        $calons = $this->calonBelumLunas();

        // Calon pemilik tagihan ini tetap tampil walau sudah lunas
        if (! $calons->contains('id', $pembayaran->calon_siswa_id)) {
            $pemilik = CalonSiswa::with(['jalurPendaftaran', 'tahunAjaran.biayaPendaftaran', 'pembayaran'])
                ->find($pembayaran->calon_siswa_id);

            if ($pemilik) {
                $calons->prepend($pemilik);
            }
        }

        return view('admin.pembayarans.edit', [
            'pembayaran' => $pembayaran,
            'calons' => $calons,
            'biayas' => BiayaPendaftaran::orderBy('jenis_biaya')->get(),
        ]);
    }

    public function update(UpdatePembayaranRequest $request, Pembayaran $pembayaran): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('bukti_pembayaran_path')) {
            if ($pembayaran->bukti_pembayaran_path) {
                Storage::disk('public')->delete($pembayaran->bukti_pembayaran_path);
            }
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran_path')->store('bukti', 'public');
            // Upload bukti baru oleh admin berarti pembayaran terverifikasi.
            $validated['status'] = 'berhasil';
        }

        $pembayaran->update($validated);

        return redirect()->route('admin.pembayarans.index')->with('success', "Pembayaran {$pembayaran->kode_pembayaran} diperbarui.");
    }

    public function updateStatus(Pembayaran $pembayaran): RedirectResponse
    {
        request()->validate(['status' => ['required', 'in:menunggu,berhasil,gagal']]);
        $pembayaran->update(['status' => request('status')]);

        // Sinkron cicilan: pembayaran cicilan yang terverifikasi menutup detail + rencana
        if (request('status') === 'berhasil'
            && $pembayaran->jenis_pembayaran === 'cicilan_angsuran'
            && $pembayaran->detail_angsuran_id
        ) {
            $this->tutupCicilan($pembayaran->fresh());
        }

        return back()->with('success', 'Status pembayaran diubah ke '.request('status').'.');
    }

    public function destroy(Pembayaran $pembayaran): RedirectResponse
    {
        if ($pembayaran->detailAngsuran && $pembayaran->detailAngsuran->status === 'dibayar') {
            return back()->with('error', 'Pembayaran cicilan yang sudah terverifikasi tidak dapat dihapus.');
        }

        if (RencanaAngsuran::where('pembayaran_id', $pembayaran->id)->where('status', 'aktif')->exists()) {
            return back()->with('error', 'Tagihan induk dengan rencana angsuran aktif tidak dapat dihapus. Batalkan rencananya dulu.');
        }

        if ($pembayaran->bukti_pembayaran_path) {
            Storage::disk('public')->delete($pembayaran->bukti_pembayaran_path);
        }
        $pembayaran->delete();

        return redirect()->route('admin.pembayarans.index')->with('success', 'Pembayaran dihapus.');
    }

    /**
     * Tandai detail cicilan dibayar + hitung ulang rencana.
     * Bila semua lunas: rencana lunas + tagihan induk berhasil.
     */
    protected function tutupCicilan(Pembayaran $pembayaran): void
    {
        $detail = $pembayaran->detailAngsuran;

        if (! $detail || $detail->status === 'dibayar') {
            return;
        }

        DB::transaction(function () use ($pembayaran, $detail) {
            $detail->update([
                'total_bayar' => $pembayaran->jumlah,
                'tanggal_bayar' => $pembayaran->tanggal_pembayaran ?? now()->toDateString(),
                'status' => 'dibayar',
            ]);

            $rencana = $detail->rencanaAngsuran()->lockForUpdate()->first();
            // Baris ke-0 (DP) dikecualikan: DP sudah dihitung via kolom dp_dibayar.
            $terbayar = (float) $rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->where('status', 'dibayar')->sum('total_bayar');
            $rencana->update(['sisa_hutang' => max(0, $rencana->total_biaya - $rencana->dp_dibayar - $terbayar)]);

            $belum = $rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->where('status', '!=', 'dibayar')->count();

            if ($belum === 0) {
                $rencana->update(['status' => 'lunas', 'sisa_hutang' => 0]);
                $rencana->pembayaran()->update(['status' => 'berhasil']);
            }
        });
    }

    /**
     * Calon dengan sisa tagihan (semua status, kecuali yang
     * total berhasilnya sudah menutup semua biaya wajib).
     */
    protected function calonBelumLunas()
    {
        $calons = CalonSiswa::belumLunas()
            ->with(['jalurPendaftaran', 'tahunAjaran.biayaPendaftaran', 'pembayaran'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $calons->each(function ($calon) {
            $terbayar = $calon->pembayaran->where('status', 'berhasil')->sum('jumlah');
            $wajib = $calon->tahunAjaran?->biayaPendaftaran->where('wajib_bayar', true)->sum('jumlah') ?? 0;
            $calon->sisa_tagihan = max(0, $wajib - $terbayar);
        });

        return $calons;
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

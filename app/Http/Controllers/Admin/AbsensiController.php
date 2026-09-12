<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAbsensiBatchRequest;
use App\Models\Absensi;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AbsensiController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:absensis.view', only: ['index', 'scan']),
            new Middleware('permission:absensis.create', only: ['storeBatch', 'storeScan']),
        ];
    }

    /**
     * Grid input manual per rombel + tanggal.
     */
    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])
            ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();

        $rombelId = request()->query('rombel_id', $rombels->first()?->id);
        $tanggal = request()->query('tanggal', now()->toDateString());
        $rombel = $rombels->firstWhere('id', (int) $rombelId);

        $siswas = collect();
        $tercatat = collect();

        if ($rombel) {
            $siswas = Siswa::with('user')
                ->whereIn('id', $rombel->anggotaIds())
                ->orderBy('nis')
                ->get();
            $tercatat = Absensi::where('rombel_id', $rombel->id)
                ->where('tanggal', $tanggal)
                ->get()
                ->keyBy('siswa_id');
        }

        return view('admin.absensis.index', [
            'rombels' => $rombels,
            'rombel' => $rombel,
            'tanggal' => $tanggal,
            'siswas' => $siswas,
            'tercatat' => $tercatat,
        ]);
    }

    /**
     * Simpan grid manual sekaligus (upsert per siswa+tanggal).
     */
    public function storeBatch(StoreAbsensiBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $jumlah = DB::transaction(function () use ($validated) {
            $count = 0;

            foreach ($validated['status'] as $siswaId => $status) {
                $absensi = Absensi::firstOrNew([
                    'siswa_id' => $siswaId,
                    'tanggal' => $validated['tanggal'],
                ]);
                $absensi->rombel_id = $validated['rombel_id'];
                $absensi->status = $status;
                $absensi->metode = 'manual';
                $absensi->dicatat_oleh = auth()->id();

                if (! $absensi->exists && in_array($status, ['hadir', 'terlambat'], true) && ! $absensi->jam_datang) {
                    $absensi->jam_datang = now()->format('H:i:s');
                }

                $absensi->save();
                $count++;
            }

            return $count;
        });

        return redirect()->route('admin.absensis.index', [
            'rombel_id' => $validated['rombel_id'],
            'tanggal' => $validated['tanggal'],
        ])->with('success', "{$jumlah} absensi tersimpan.");
    }

    /**
     * Halaman scan QR (kamera device sekolah).
     */
    public function scan(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])
            ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();

        return view('admin.absensis.scan', [
            'rombels' => $rombels,
            'rombelId' => request()->query('rombel_id', $rombels->first()?->id),
        ]);
    }

    /**
     * Catat hasil scan (JSON). Idempoten: scan ulang = update jam.
     */
    public function storeScan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
        ]);

        $siswa = Siswa::with('user')->where('qr_token', $data['token'])->first();

        if (! $siswa) {
            return response()->json(['message' => 'QR tidak dikenal.'], 422);
        }

        $rombel = Rombel::find($data['rombel_id']);

        if (! in_array($siswa->id, $rombel->anggotaIds(), true)) {
            return response()->json(['message' => 'Siswa bukan anggota rombel ini.'], 422);
        }

        $batas = Pengaturan::nilai('batas_terlambat', '07:00');
        $sekarang = now();
        $status = $sekarang->format('H:i') > $batas ? 'terlambat' : 'hadir';

        $absensi = Absensi::updateOrCreate(
            ['siswa_id' => $siswa->id, 'tanggal' => $sekarang->toDateString()],
            [
                'rombel_id' => $rombel->id,
                'status' => $status,
                'jam_datang' => $sekarang->format('H:i:s'),
                'metode' => 'qr',
                'dicatat_oleh' => auth()->id(),
            ],
        );

        return response()->json([
            'nama' => $siswa->user?->name ?? '-',
            'nis' => $siswa->nis ?? $siswa->nisn ?? '-',
            'status' => $status,
            'jam' => $absensi->jam_datang,
            'baru' => $absensi->wasRecentlyCreated,
        ]);
    }
}

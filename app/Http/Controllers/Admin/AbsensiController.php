<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AbsensiRekapExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAbsensiBatchRequest;
use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AbsensiController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:absensis.view', only: ['index', 'scan', 'rekap', 'exportExcel', 'exportPdf']),
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

    /**
     * Rentang tanggal satu periode rekap.
     * Semester memakai konvensi kalender pendidikan (Jul–Des ganjil,
     * Jan–Jun genap); siap diganti entitas bila client meminta.
     *
     * @return array{mulai: string, selesai: string}
     */
    public static function rentangPeriode(string $periode, ?string $acuan = null, ?int $tahunAjaranId = null): array
    {
        $acuan ??= now()->toDateString();
        $tahun = $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : TahunAjaran::aktif()->first();

        return match ($periode) {
            'minggu' => [
                'mulai' => Carbon::parse($acuan)->startOfWeek()->toDateString(),
                'selesai' => Carbon::parse($acuan)->endOfWeek()->toDateString(),
            ],
            'bulan' => [
                'mulai' => substr($acuan, 0, 7).'-01',
                'selesai' => Carbon::parse(substr($acuan, 0, 7).'-01')->endOfMonth()->toDateString(),
            ],
            'ganjil' => [
                'mulai' => $tahun ? substr($tahun->tanggal_mulai, 0, 4).'-07-01' : substr($acuan, 0, 4).'-07-01',
                'selesai' => $tahun ? substr($tahun->tanggal_mulai, 0, 4).'-12-31' : substr($acuan, 0, 4).'-12-31',
            ],
            'genap' => [
                'mulai' => $tahun ? substr($tahun->tanggal_selesai, 0, 4).'-01-01' : substr($acuan, 0, 4).'-01-01',
                'selesai' => $tahun ? substr($tahun->tanggal_selesai, 0, 4).'-06-30' : substr($acuan, 0, 4).'-06-30',
            ],
            default => [
                'mulai' => $tahun ? (string) $tahun->tanggal_mulai : substr($acuan, 0, 4).'-07-01',
                'selesai' => $tahun ? (string) $tahun->tanggal_selesai : substr($acuan, 0, 4).'-06-30',
            ],
        };
    }

    /**
     * Rombel yang boleh dilihat user. Wali dikunci ke rombel ampuan;
     * lainnya bebas memilih.
     *
     * @return array{semua: Collection, terkunci: bool}
     */
    protected function rombelTerjangkau(): array
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $semua = Rombel::with(['kelas', 'tahunAjaran'])
            ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();

        $guruId = Guru::where('user_id', auth()->id())->first()?->id;
        $ampuan = $guruId ? $semua->where('wali_guru_id', $guruId)->values() : collect();

        if ($ampuan->isNotEmpty()) {
            return ['semua' => $ampuan, 'terkunci' => true];
        }

        return ['semua' => $semua, 'terkunci' => false];
    }

    /**
     * Data rekap satu rombel + rentang (dipakai layar + ekspor).
     */
    public function dataRekap(int $rombelId, string $mulai, string $selesai): array
    {
        $rombel = Rombel::with(['kelas', 'tahunAjaran'])->findOrFail($rombelId);

        $siswas = Siswa::with('user')
            ->whereIn('id', $rombel->anggotaIds())
            ->orderBy('nis')
            ->get();

        $catatan = Absensi::where('rombel_id', $rombel->id)
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->get();

        $matriks = [];
        $total = [];

        foreach ($siswas as $siswa) {
            $perTanggal = $catatan->where('siswa_id', $siswa->id)->keyBy('tanggal');
            $hitung = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'terlambat' => 0];

            foreach ($perTanggal as $row) {
                $hitung[$row->status] = ($hitung[$row->status] ?? 0) + 1;
            }

            $terisi = array_sum($hitung);
            $matriks[$siswa->id] = [
                'siswa' => $siswa,
                'perTanggal' => $perTanggal,
                'hitung' => $hitung,
                'persen' => $terisi ? round((($hitung['hadir'] + $hitung['terlambat']) / $terisi) * 100) : null,
            ];

            foreach ($hitung as $status => $jumlah) {
                $total[$status] = ($total[$status] ?? 0) + $jumlah;
            }
        }

        $tanggals = [];
        $periode = CarbonPeriod::create($mulai, $selesai);
        foreach ($periode as $tanggal) {
            $tanggals[] = $tanggal->toDateString();
        }

        return compact('rombel', 'siswas', 'matriks', 'total', 'tanggals', 'mulai', 'selesai');
    }

    /**
     * Halaman rekap kehadiran.
     */
    public function rekap(): View
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombelId = request()->query('rombel_id', $rombels->first()?->id);
        $periode = request()->query('periode', 'bulan');
        $acuan = request()->query('acuan', now()->toDateString());
        $tahunAjaranId = request()->query('tahun_ajaran_id', TahunAjaran::aktif()->first()?->id) ?: null;

        $rombel = $rombels->firstWhere('id', (int) $rombelId);

        if ($terkunci && ! $rombel) {
            abort(403, 'Rombel ini bukan ampuan Anda.');
        }

        $data = null;

        if ($rombel) {
            $rentang = static::rentangPeriode($periode, $acuan, $tahunAjaranId ? (int) $tahunAjaranId : null);
            $data = $this->dataRekap($rombel->id, $rentang['mulai'], $rentang['selesai']);
            $data['rinci'] = count($data['tanggals']) <= 45;
        }

        return view('admin.absensis.rekap', array_merge([
            'rombels' => $rombels,
            'terkunci' => $terkunci,
            'rombel' => $rombel,
            'periode' => $periode,
            'acuan' => $acuan,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAjaranId' => $tahunAjaranId,
        ], $data ? ['rekap' => $data] : []));
    }

    public function exportExcel(): BinaryFileResponse
    {
        $rekap = $this->rekapTerfilter();

        return Excel::download(
            new AbsensiRekapExport($rekap),
            'rekap-absensi-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf(): Response
    {
        $rekap = $this->rekapTerfilter();

        return Pdf::loadView('admin.absensis.pdf', [
            'rekap' => $rekap,
        ])->download('rekap-absensi-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Rekap sesuai filter request ( dipakai ekspor ).
     */
    protected function rekapTerfilter(): array
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombel = $rombels->firstWhere('id', (int) request('rombel_id', $rombels->first()?->id));

        if (! $rombel || ($terkunci && ! $rombels->contains('id', $rombel->id))) {
            abort(404, 'Rombel tidak ditemukan.');
        }

        $periode = request('periode', 'bulan');
        $tahunAjaranId = request('tahun_ajaran_id', TahunAjaran::aktif()->first()?->id) ?: null;
        $rentang = static::rentangPeriode($periode, request('acuan', now()->toDateString()), $tahunAjaranId ? (int) $tahunAjaranId : null);

        $data = $this->dataRekap($rombel->id, $rentang['mulai'], $rentang['selesai']);
        $data['rinci'] = count($data['tanggals']) <= 45;
        $data['periode'] = $periode;

        return $data;
    }
}

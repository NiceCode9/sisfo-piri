<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AbsensiRekapExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AbsensiFilterRequest;
use App\Http\Requests\Admin\StoreAbsensiBatchRequest;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Absensi;
use App\Models\Guru;
use App\Models\NotifikasiLog;
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
    /**
     * Batas hari untuk menampilkan rincian per tanggal.
     *
     * Melewati batas ini tabel melebar jadi tidak terbaca dan satu baris siswa
     * bisa menembus ratusan kolom. Di atas batas, layar dan ekspor sama-sama
     * turun ke ringkasan per status.
     */
    public const BATAS_RINCI_HARI = 45;

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
    public function index(AbsensiFilterRequest $request): View
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombelDiminta = $request->query('rombel_id');
        $rombel = $this->pilihRombelTerjangkau($rombels, $terkunci, $rombelDiminta, $rombelDiminta ?? $rombels->first()?->id);
        $tanggal = $request->query('tanggal', now()->toDateString());

        $siswas = collect();
        $tercatat = collect();

        if ($rombel) {
            $siswas = Siswa::with('user')
                ->whereIn('id', $rombel->anggotaIdsAktif())
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
     * Baris alpa mengantrekan notifikasi WA ke orang-tua.
     */
    public function storeBatch(StoreAbsensiBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $jumlah = DB::transaction(function () use ($validated) {
            $count = 0;

            foreach ($validated['status'] as $siswaId => $status) {
                // Kunci baris harus memuat rombel_id, bukan hanya
                // (siswa_id, tanggal). Tanpa rombel_id, siswa yang pindah kelas
                // di tengah hariin memakai baris kelas lamanya lalu menimpa
                // absensi kelas lama dengan rombel yang baru.
                $absensi = Absensi::firstOrNew([
                    'siswa_id' => $siswaId,
                    'rombel_id' => $validated['rombel_id'],
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

        $this->antrekanNotifikasiAlpa($validated['rombel_id'], $validated['tanggal'], array_keys(
            array_filter($validated['status'], fn ($status) => $status === 'alpa')
        ));

        return redirect()->route('admin.absensis.index', [
            'rombel_id' => $validated['rombel_id'],
            'tanggal' => $validated['tanggal'],
        ])->with('success', "{$jumlah} absensi tersimpan.");
    }

    /**
     * Buat log + dispatch WA untuk siswa alpa (satu per no WA).
     *
     * @param  array<int>  $siswaIds
     */
    protected function antrekanNotifikasiAlpa(int $rombelId, string $tanggal, array $siswaIds): void
    {
        if ($siswaIds === []) {
            return;
        }

        $rombel = Rombel::with('kelas')->find($rombelId);

        $siswas = Siswa::with(['user', 'waliMurids'])
            ->whereIn('id', $siswaIds)
            ->get();

        foreach ($siswas as $siswa) {
            foreach ($siswa->waliMurids->pluck('no_whatsapp')->filter()->unique() as $nomor) {
                // Idempoten per (siswa, rombel, tanggal, nomor). Guru sering
                // menyimpan batch berulang untuk koreksi; tanpa kunci, tiap
                // simpanan mengirim ulang pesan "alpa" yang sama ke orang tua.
                $kunci = hash('sha256', "whatsapp|alpa|{$siswa->id}|{$rombelId}|{$tanggal}|{$nomor}");

                $log = NotifikasiLog::firstOrCreate(
                    ['kunci' => $kunci],
                    [
                        'tipe' => 'whatsapp',
                        'tujuan' => $nomor,
                        'pesan' => KirimNotifikasiWhatsapp::pesanAlpa(
                            $siswa->user?->name ?? '-',
                            $rombel->kelas->nama_kelas ?? '-',
                            $tanggal
                        ),
                    ],
                );

                if ($log->wasRecentlyCreated) {
                    KirimNotifikasiWhatsapp::dispatch($log->id);
                }
            }
        }
    }

    /**
     * Halaman scan QR (kamera device sekolah).
     */
    public function scan(AbsensiFilterRequest $request): View
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombelDiminta = $request->query('rombel_id');
        $rombel = $this->pilihRombelTerjangkau($rombels, $terkunci, $rombelDiminta, $rombelDiminta ?? $rombels->first()?->id);

        return view('admin.absensis.scan', [
            'rombels' => $rombels,
            'rombelId' => $rombel?->id,
        ]);
    }

    /**
     * Catat hasil scan (JSON). Idempoten: scan ulang tidak menimpa arrival
     * pertama, hanya mencatat bahwa siswa hadir.
     */
    public function storeScan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
        ]);

        $rombel = $this->rombelAbsensiTerjangkau((int) $data['rombel_id']);

        $siswa = Siswa::with('user')->where('qr_token', $data['token'])->first();

        if (! $siswa) {
            return response()->json(['message' => 'QR tidak dikenal.'], 422);
        }

        if (! in_array($siswa->id, $rombel->anggotaIdsAktif(), true)) {
            return response()->json(['message' => 'Siswa bukan anggota rombel ini.'], 422);
        }

        $batas = Pengaturan::nilai('batas_terlambat', '07:00');
        $sekarang = now();
        $status = $sekarang->format('H:i') > $batas ? 'terlambat' : 'hadir';

        // `updateOrCreate` tidak bisa dipakai di sini karena selalu menulis
        // `jam_datang`. Siswa yang scan 06:55 lalu scan lagi 08:05 akan
        // kehilangan catatan kehadiran tepat waktunya dan justru ditandai
        // terlambat. Arrival pertama yang dihitung: jam hanya diisi bila
        // masih kosong, dan status `hadir` tidak pernah diturunkan ke
        // `terlambat` oleh scan susulan.
        $absensi = Absensi::firstOrNew([
            // rombel_id masuk kunci, bukan hanya atribut. Tanpa itu, scan
            // untuk kelas yang berbeda pada tanggal sama akan menimpa baris
            // kelas lama alih-alih membuat baris sendiri.
            'siswa_id' => $siswa->id,
            'rombel_id' => $rombel->id,
            'tanggal' => $sekarang->toDateString(),
        ]);

        if (! $absensi->exists) {
            $absensi->status = $status;
            $absensi->jam_datang = $sekarang->format('H:i:s');
        } else {
            $absensi->status = $absensi->status === 'hadir' ? 'hadir' : $status;

            if (! $absensi->jam_datang) {
                $absensi->jam_datang = $sekarang->format('H:i:s');
            }
        }

        $absensi->rombel_id = $rombel->id;
        $absensi->metode = 'qr';
        $absensi->dicatat_oleh = auth()->id();
        $absensi->save();

        return response()->json([
            'nama' => $siswa->user?->name ?? '-',
            'nis' => $siswa->nis ?? $siswa->nisn ?? '-',
            'status' => $absensi->status,
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
                'mulai' => $tahun ? substr($tahun->tanggal_mulai, 0, 4).'-'.Pengaturan::nilai('semester_ganjil_mulai', '07-01') : substr($acuan, 0, 4).'-'.Pengaturan::nilai('semester_ganjil_mulai', '07-01'),
                'selesai' => $tahun ? substr($tahun->tanggal_mulai, 0, 4).'-'.Pengaturan::nilai('semester_ganjil_selesai', '12-31') : substr($acuan, 0, 4).'-'.Pengaturan::nilai('semester_ganjil_selesai', '12-31'),
            ],
            'genap' => [
                'mulai' => $tahun ? substr($tahun->tanggal_selesai, 0, 4).'-'.Pengaturan::nilai('semester_genap_mulai', '01-01') : substr($acuan, 0, 4).'-'.Pengaturan::nilai('semester_genap_mulai', '01-01'),
                'selesai' => $tahun ? substr($tahun->tanggal_selesai, 0, 4).'-'.Pengaturan::nilai('semester_genap_selesai', '06-30') : substr($acuan, 0, 4).'-'.Pengaturan::nilai('semester_genap_selesai', '06-30'),
            ],
            default => [
                'mulai' => $tahun ? (string) $tahun->tanggal_mulai : substr($acuan, 0, 4).'-07-01',
                'selesai' => $tahun ? (string) $tahun->tanggal_selesai : substr($acuan, 0, 4).'-06-30',
            ],
        };
    }

    /**
     * Rombel yang boleh dilihat user.
     *
     * Versi lama hanya menghitung rombel yang diwali lalu jatuh ke
     * "kalau kosong, semua rombel" saat hasilnya kosong. Pola fail-open
     * seperti itu sama dengan yang sudah dihapus di modul tugas: guru
     * tanpa kelas wali bisa membuka rekap kehadiran kelas mana pun.
     * Sekarang memakai {@see Rombel::terjangkauUser()} sehingga cekunya
     * adalah wali ATAU pengampu mapel, dan kosong berarti nol.
     *
     * Grid input dan scan memakai helper yang sama. Semula keduanya memuat
     * seluruh rombel tanpa penyaring apa pun, sehingga setiap pemegang
     * permission `absensis.create` bisa membaca roster dan mencatat kehadiran
     * di kelas yang bukan ampunya.
     *
     * @return array{semua: Collection, terkunci: bool}
     */
    protected function rombelTerjangkau(): array
    {
        $tahunAktif = TahunAjaran::aktif()->first();

        $semua = Rombel::with(['kelas', 'tahunAjaran'])
            ->terjangkauUser(request()->user(), Rombel::ROLE_ABSENSI_UNIVERSAL)
            ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();

        return [
            'semua' => $semua,
            'terkunci' => ! request()->user()->hasRole(Rombel::ROLE_ABSENSI_UNIVERSAL),
        ];
    }

    /**
     * Pilih satu rombel dari daftar terjangkau, tolak 403 bila yang diminta
     * bukan ampuan user.
     *
     * Tanpa guard ini user yang belum punya rombel mendapat halaman error
     * alih-alih tampilan kosong yang wajar, karena daftar ampuan kosong
     * sehingga tidak ada rombel yang terpilih.
     *
     * @param  Collection<int, Rombel>  $rombels
     * @param  mixed  $diminta  Nilai mentah dari query string, null bila tidak disebut.
     * @param  mixed  $id  Kandidat rombel terpilih.
     */
    protected function pilihRombelTerjangkau(Collection $rombels, bool $terkunci, mixed $diminta, mixed $id): ?Rombel
    {
        $rombel = $rombels->firstWhere('id', (int) $id);

        if ($terkunci && $diminta && ! $rombel) {
            abort(403, 'Rombel ini bukan ampuan Anda.');
        }

        return $rombel;
    }

    /**
     * Satu rombel yang pasti terjangkau user, untuk jalur tulis.
     *
     * Jalur tulis tidak boleh diam-diam jatuh kerombel lain seperti tampilan
     * grid, jadi di sini rombel di luar jangkauan selalu 403, bukan sekadar
     * tidak ditemukan.
     */
    protected function rombelAbsensiTerjangkau(int $rombelId): Rombel
    {
        abort_unless(
            Rombel::terjangkauOleh(request()->user(), $rombelId, Rombel::ROLE_ABSENSI_UNIVERSAL),
            403,
            'Rombel ini bukan ampuan Anda.',
        );

        return Rombel::with('kelas')->findOrFail($rombelId);
    }

    /**
     * Data rekap satu rombel + rentang (dipakai layar + ekspor).
     */
    public function dataRekap(int $rombelId, string $mulai, string $selesai): array
    {
        $rombel = Rombel::with(['kelas', 'tahunAjaran'])->findOrFail($rombelId);

        $siswas = Siswa::with('user')
            ->whereIn('id', $rombel->anggotaIdsUntukRekap())
            ->orderBy('nis')
            ->get();

        $catatan = Absensi::where('rombel_id', $rombel->id)
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->get();

        // Dikelompokkan sekali, bukan dicari ulang per siswa: `Collection::where`
        // di dalam loop menyapu seluruh catatan untuk tiap siswa.
        $catatanPerSiswa = $catatan->groupBy('siswa_id');

        $matriks = [];
        $total = [];

        foreach ($siswas as $siswa) {
            $perTanggal = $catatanPerSiswa->get($siswa->id, collect())->keyBy('tanggal');
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
    public function rekap(AbsensiFilterRequest $request): View
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombelDiminta = $request->query('rombel_id');
        $periode = $request->query('periode', 'bulan');
        $acuan = $request->query('acuan', now()->toDateString());
        $tahunAjaranId = $request->query('tahun_ajaran_id') ?: (TahunAjaran::aktif()->first()?->id) ?: null;

        $rombel = $this->pilihRombelTerjangkau($rombels, $terkunci, $rombelDiminta, $rombelDiminta ?? $rombels->first()?->id);

        $data = null;

        if ($rombel) {
            $rentang = static::rentangPeriode($periode, $acuan, $tahunAjaranId ? (int) $tahunAjaranId : null);
            $data = $this->dataRekap($rombel->id, $rentang['mulai'], $rentang['selesai']);
            $data['rinci'] = count($data['tanggals']) <= self::BATAS_RINCI_HARI;
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

    public function exportExcel(AbsensiFilterRequest $request): BinaryFileResponse
    {
        $rekap = $this->rekapTerfilter($request);

        return Excel::download(
            new AbsensiRekapExport($rekap),
            'rekap-absensi-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf(AbsensiFilterRequest $request): Response
    {
        $rekap = $this->rekapTerfilter($request);

        return Pdf::loadView('admin.absensis.pdf', [
            'rekap' => $rekap,
        ])->download('rekap-absensi-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Rekap sesuai filter request (dipakai ekspor).
     *
     * Filter yang sama divalidasi oleh `AbsensiFilterRequest`, jadi ekspor tidak
     * bisa menghasilkan rekap periode berbeda dari yang tampil di layar.
     */
    protected function rekapTerfilter(AbsensiFilterRequest $request): array
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombelDiminta = $request->query('rombel_id');
        $rombel = $this->pilihRombelTerjangkau($rombels, $terkunci, $rombelDiminta, $rombelDiminta ?? $rombels->first()?->id);

        if (! $rombel) {
            abort(404, 'Rombel tidak ditemukan.');
        }

        $periode = $request->query('periode', 'bulan');
        $tahunAjaranId = $request->query('tahun_ajaran_id') ?: (TahunAjaran::aktif()->first()?->id) ?: null;
        $rentang = static::rentangPeriode($periode, $request->query('acuan', now()->toDateString()), $tahunAjaranId ? (int) $tahunAjaranId : null);

        $data = $this->dataRekap($rombel->id, $rentang['mulai'], $rentang['selesai']);
        $data['rinci'] = count($data['tanggals']) <= self::BATAS_RINCI_HARI;
        $data['periode'] = $periode;

        return $data;
    }
}

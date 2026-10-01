<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CalonDiterimaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCalonSiswaRequest;
use App\Http\Requests\Admin\UpdateCalonSiswaRequest;
use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\LogStatusPendaftaran;
use App\Models\Pembayaran;
use App\Models\SertifikatPrestasi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\WaliMurid;
use App\Support\Berkas;
use App\Support\Penomor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CalonSiswaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:calon-siswas.view', only: ['index', 'show', 'exportDiterima']),
            new Middleware('permission:calon-siswas.create', only: ['create', 'store']),
            new Middleware('permission:calon-siswas.edit', only: ['edit', 'update', 'updateStatus', 'verifyBerkas', 'destroySertifikat']),
            new Middleware('permission:calon-siswas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $calons = CalonSiswa::with(['jalurPendaftaran', 'tahunAjaran', 'berkasCalonSiswa'])
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($qq) => $qq
                ->where('no_pendaftaran', 'like', "%{$s}%")
                ->orWhere('nama_lengkap', 'like', "%{$s}%")
                ->orWhere('nik', 'like', "%{$s}%")
            ))
            ->when(request('jalur'), fn ($q, $v) => $q->where('jalur_pendaftaran_id', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status_pendaftaran', $v))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.calon-siswas.index', [
            'calons' => $calons,
            'jalurs' => JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.calon-siswas.create', [
            'jalurs' => JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StoreCalonSiswaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? TahunAjaran::aktif()->first()?->id;

        if (! $tahunAjaranId) {
            return back()->with('error', 'Tahun ajaran aktif belum diatur.')->withInput();
        }

        $validated['tahun_ajaran_id'] = $tahunAjaranId;
        $validated['no_pendaftaran'] = Penomor::placeholder('PPDB');
        // Pendaftaran baru selalu mulai 'menunggu'. Penerimaan hanya lewat updateStatus()
        // agar kuota, log status, akun siswa, dan tagihan tidak terlewat.
        $validated['status_pendaftaran'] = 'menunggu';

        $berkasFields = ['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'];
        $calonData = collect($validated)->except([...$berkasFields, 'sertifikat'])->toArray();
        $berkasUploads = collect($validated)->only($berkasFields)->filter()->toArray();
        $sertifikatUploads = $request->file('sertifikat', []);

        // Kuota check with lock before create
        try {
            DB::transaction(function () use ($calonData, $berkasUploads, $sertifikatUploads, $request) {
                $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $calonData['tahun_ajaran_id'])
                    ->where('jalur_pendaftaran_id', $calonData['jalur_pendaftaran_id'])
                    ->lockForUpdate()
                    ->first();

                if ($kuota && $kuota->terisi >= $kuota->kuota) {
                    throw new \RuntimeException('Kuota jalur ini sudah penuh.');
                }

                $calon = CalonSiswa::create($calonData);

                if (! empty($berkasUploads)) {
                    $berkasData = [];
                    foreach ($berkasUploads as $field => $file) {
                        $berkasData[$field] = $file->store('berkas', 'berkas');
                    }
                    $calon->berkasCalonSiswa()->create($berkasData);
                }

                foreach ($sertifikatUploads as $i => $item) {
                    $calon->sertifikatPrestasis()->create([
                        'nama_sertifikat' => $request->input("sertifikat.{$i}.nama", 'Sertifikat Prestasi'),
                        'file_path' => $item['file']->store('berkas/sertifikat', 'berkas'),
                    ]);
                }

                LogStatusPendaftaran::create([
                    'calon_siswa_id' => $calon->id,
                    'status_sebelumnya' => null,
                    'status_baru' => $calon->status_pendaftaran,
                    'user_id' => auth()->id(),
                    'catatan' => 'Pendaftaran dibuat via admin',
                ]);

                Penomor::calon($calon);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.calon-siswas.index')->with('success', 'Calon siswa berhasil ditambahkan.');
    }

    public function show(CalonSiswa $calonSiswa): View
    {
        $calonSiswa->load(['jalurPendaftaran', 'tahunAjaran', 'tahunAjaran.biayaPendaftaran', 'berkasCalonSiswa', 'sertifikatPrestasis', 'logStatusPendaftaran.user', 'pembayaran.biayaPendaftaran', 'pembayaranLainnya', 'rencanaAngsuran.detailAngsuran']);

        return view('admin.calon-siswas.show', [
            'calon' => $calonSiswa,
        ]);
    }

    public function edit(CalonSiswa $calonSiswa): View
    {
        return view('admin.calon-siswas.edit', [
            'calon' => $calonSiswa,
            'jalurs' => JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdateCalonSiswaRequest $request, CalonSiswa $calonSiswa): RedirectResponse
    {
        $validated = $request->validated();

        // ID jalur/tahun bertipe integer di DB tetapi string dari form POST.
        // Tanpa normalisasi, `!==` selalu bernilai true sehingga setiap edit
        // calon `diterima` ditolak walau jalurnya tidak berubah sama sekali.
        $oldJalur = (int) $calonSiswa->jalur_pendaftaran_id;
        $oldTahun = (int) $calonSiswa->tahun_ajaran_id;
        $oldStatus = $calonSiswa->status_pendaftaran;
        $newJalur = (int) $validated['jalur_pendaftaran_id'];
        $newTahun = (int) ($validated['tahun_ajaran_id'] ?? $oldTahun);

        if (($oldJalur !== $newJalur || $oldTahun !== $newTahun) && $oldStatus === 'diterima') {
            return back()->with('error', 'Tidak dapat mengganti jalur/tahun untuk calon yang sudah diterima. Ubah status dulu.')->withInput();
        }

        $berkasFields = ['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'];
        $calonData = collect($validated)->except([...$berkasFields, 'sertifikat', 'status_pendaftaran'])->toArray();
        $berkasUploads = collect($validated)->only($berkasFields)->filter()->toArray();
        $sertifikatUploads = $request->file('sertifikat', []);

        $berkasFiles = new Berkas;

        try {
            DB::transaction(function () use ($calonSiswa, $calonData, $berkasUploads, $sertifikatUploads, $request, $berkasFiles) {
                $calonSiswa->update($calonData);

                if (! empty($berkasUploads)) {
                    $berkas = $calonSiswa->berkasCalonSiswa()->firstOrCreate([]);
                    foreach ($berkasUploads as $field => $file) {
                        // File lama dicatat dulu, baru disimpan. Penghapusan
                        // baru dijalankan setelah commit supaya baris tidak
                        // pernah menunjuk file yang sudah hilang.
                        $pathBaru = $file->store('berkas', 'berkas');
                        $berkasFiles->ganti($pathBaru, $berkas->$field);
                        $berkas->$field = $pathBaru;
                    }
                    $berkas->save();
                }

                foreach ($sertifikatUploads as $i => $item) {
                    $calonSiswa->sertifikatPrestasis()->create([
                        'nama_sertifikat' => $request->input("sertifikat.{$i}.nama", 'Sertifikat Prestasi'),
                        'file_path' => $item['file']->store('berkas/sertifikat', 'berkas'),
                    ]);
                }
            });
        } catch (Throwable $e) {
            $berkasFiles->buangYangBaru();

            throw $e;
        }

        $berkasFiles->hapusYangSudahTidakDipakai();

        return redirect()->route('admin.calon-siswas.show', $calonSiswa)->with('success', 'Data calon siswa diperbarui.');
    }

    public function exportDiterima(): BinaryFileResponse
    {
        return Excel::download(new CalonDiterimaExport, 'penempatan-calon-diterima.xlsx');
    }

    public function updateStatus(Request $request, CalonSiswa $calonSiswa): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:menunggu,diterima,ditolak,daftar_ulang'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $request->input('status');
        $oldStatus = $calonSiswa->status_pendaftaran;

        if ($newStatus === $oldStatus) {
            return back()->with('error', 'Status tidak berubah.');
        }

        try {
            DB::transaction(function () use ($calonSiswa, $oldStatus, $newStatus, $request) {
                // Lock kuota row for this jalur/tahun
                $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $calonSiswa->tahun_ajaran_id)
                    ->where('jalur_pendaftaran_id', $calonSiswa->jalur_pendaftaran_id)
                    ->lockForUpdate()
                    ->first();

                if ($kuota) {
                    if ($oldStatus !== 'diterima' && $newStatus === 'diterima') {
                        if ($kuota->terisi >= $kuota->kuota) {
                            throw new \RuntimeException('Kuota jalur ini sudah penuh, tidak dapat menerima.');
                        }
                        $kuota->increment('terisi');
                    } elseif ($oldStatus === 'diterima' && $newStatus !== 'diterima') {
                        $kuota->decrement('terisi');
                    }
                }

                $calonSiswa->update(['status_pendaftaran' => $newStatus]);

                LogStatusPendaftaran::create([
                    'calon_siswa_id' => $calonSiswa->id,
                    'status_sebelumnya' => $oldStatus,
                    'status_baru' => $newStatus,
                    'user_id' => auth()->id(),
                    'catatan' => $request->input('catatan'),
                ]);

                if ($oldStatus !== 'diterima' && $newStatus === 'diterima' && $calonSiswa->user_id) {
                    // Data ortu ikut dicopy agar akun WaliMurid formeduk lengkap (tidak degraded).
                    // Penempatan kelas/rombel menyusul lewat export+import penempatan (SiswaImport upsert).
                    Siswa::firstOrCreate(
                        ['calon_siswa_id' => $calonSiswa->id],
                        [
                            'user_id' => $calonSiswa->user_id,
                            'nisn' => $calonSiswa->nisn,
                            'tahun_ajaran_id' => $calonSiswa->tahun_ajaran_id,
                            'tanggal_diterima' => now()->toDateString(),
                            'is_aktif' => true,
                            'nama_ayah' => $calonSiswa->nama_ayah,
                            'pekerjaan_ayah' => $calonSiswa->pekerjaan_ayah,
                            'nama_ibu' => $calonSiswa->nama_ibu,
                            'pekerjaan_ibu' => $calonSiswa->pekerjaan_ibu,
                            'no_hp_orang_tua' => $calonSiswa->no_hp_orang_tua,
                        ]
                    );

                    // Auto-create pembayaran untuk semua biaya wajib tahun tersebut
                    $biayasWajib = BiayaPendaftaran::where('tahun_ajaran_id', $calonSiswa->tahun_ajaran_id)
                        ->where('wajib_bayar', true)
                        ->get();

                    foreach ($biayasWajib as $biaya) {
                        $pembayaran = Pembayaran::firstOrCreate(
                            [
                                'calon_siswa_id' => $calonSiswa->id,
                                'biaya_pendaftaran_id' => $biaya->id,
                            ],
                            [
                                'kode_pembayaran' => Penomor::placeholder('PAY'),
                                'jumlah' => $biaya->jumlah,
                                'metode_pembayaran' => 'transfer',
                                'jenis_pembayaran' => 'penuh',
                                'status' => 'menunggu',
                            ]
                        );

                        if (str_starts_with($pembayaran->kode_pembayaran, 'PAY-')) {
                            Penomor::pembayaran($pembayaran);
                        }
                    }
                }

                if ($newStatus === 'ditolak') {
                    $this->batalkanTagihan($calonSiswa);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Status diubah {$oldStatus} → {$newStatus}.");
    }

    /**
     * Callee saat status berubah menjadi ditolak.
     *
     * Menolak calon tidak boleh meninggalkan sisa tagihan aktif: invoice yang
     * masih `menunggu` dan rencana angsuran `aktif` akan terus muncul sebagai
     * tunggakan. Invoice yang sudah `berhasil` dibiarkan karena uangnya sudah
     * masuk (biaya yang sudah terlanjur dibayar tetap tercatat).
     *
     * Siswa hasil penerimaan dinonaktifkan dan tautan walinya dilepas supaya
     * dashboard orang tua tidak lagi menampilkan anak yang sudah ditolak.
     */
    private function batalkanTagihan(CalonSiswa $calonSiswa): void
    {
        $calonSiswa->pembayaran()
            ->where('status', 'menunggu')
            ->update(['status' => 'batal']);

        $calonSiswa->pembayaranLainnya()
            ->where('status', 'menunggu')
            ->update(['status' => 'batal']);

        $calonSiswa->rencanaAngsuran()
            ->where('status', 'aktif')
            ->update(['status' => 'batal']);

        $siswa = $calonSiswa->siswa;

        if ($siswa) {
            $siswa->update(['is_aktif' => false]);
            WaliMurid::where('siswa_id', $siswa->id)->delete();
        }
    }

    public function verifyBerkas(Request $request, CalonSiswa $calonSiswa): RedirectResponse
    {
        $request->validate([
            'status_verifikasi' => ['required', 'boolean'],
            'berkas_perlu_perbaikan' => ['nullable', 'array'],
            'berkas_perlu_perbaikan.*' => ['string', 'in:ijazah_path,kk_path,akta_path,foto_path,skl_path,krm_path,kip_path,sertifikat'],
            'alasan_penolakan' => ['nullable', 'string', 'max:1000'],
            'catatan_berkas' => ['nullable', 'string', 'max:1000'],
            'ijazah_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'kk_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'akta_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'foto_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'skl_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'krm_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'kip_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $berkas = $calonSiswa->berkasCalonSiswa;

        if (! $berkas) {
            $berkas = $calonSiswa->berkasCalonSiswa()->create([
                'status_verifikasi' => $request->boolean('status_verifikasi'),
            ]);
        }

        $data = [
            'status_verifikasi' => $request->boolean('status_verifikasi'),
            'berkas_perlu_perbaikan' => $request->input('berkas_perlu_perbaikan'),
            'alasan_penolakan' => $request->input('alasan_penolakan'),
            'catatan_berkas' => $request->input('catatan_berkas'),
        ];

        $berkasFiles = new Berkas;

        foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'] as $field) {
            if ($request->hasFile($field)) {
                // File lama baru dihapus setelah commit supaya baris tidak
                // pernah menunjuk file yang sudah hilang.
                $pathBaru = $request->file($field)->store('berkas', 'berkas');
                $berkasFiles->ganti($pathBaru, $berkas->$field);
                $data[$field] = $pathBaru;
            }
        }

        try {
            $berkas->update($data);
        } catch (Throwable $e) {
            $berkasFiles->buangYangBaru();

            throw $e;
        }

        $berkasFiles->hapusYangSudahTidakDipakai();

        return back()->with('success', 'Verifikasi berkas diperbarui.');
    }

    public function destroySertifikat(SertifikatPrestasi $sertifikat): RedirectResponse
    {
        // File dihapus setelah baris benar-benar terhapus.
        $berkas = new Berkas;
        $berkas->hapus($sertifikat->file_path);
        $nama = $sertifikat->nama_sertifikat;
        $sertifikat->delete();

        $berkas->hapusYangSudahTidakDipakai();

        return back()->with('success', "Sertifikat {$nama} dihapus.");
    }

    public function destroy(CalonSiswa $calonSiswa): RedirectResponse
    {
        // Calon yang sudah jadi siswa punya riwayat kelas/absensi/tugas yang
        // ikut cascade terhapus. Arahkan admin ke Master Siswa (nonaktifkan).
        if ($calonSiswa->siswa()->exists()) {
            return back()->with(
                'error',
                'Calon ini sudah menjadi siswa aktif beserta riwayat kelas dan absensinya. '
                .'Gunakan Master Siswa → nonaktifkan bila perlu dikeluarkan dari daftar aktif.'
            );
        }

        // Kumpulkan path dulu, hapus file SETELAH baris benar-benar terhapus.
        // Kalau transaksi di-rollback, baris tetap utuh dan file tidak hilang.
        $berkasFiles = new Berkas;

        if ($calonSiswa->berkasCalonSiswa) {
            foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'] as $field) {
                $berkasFiles->hapus($calonSiswa->berkasCalonSiswa->$field);
            }
        }

        $calonSiswa->sertifikatPrestasis->each(fn ($sertifikat) => $berkasFiles->hapus($sertifikat->file_path));
        // Bukti pembayaran induk sempat terlewat: ikut terhapus bersama kandidat.
        $calonSiswa->pembayaran->each(fn ($pembayaran) => $berkasFiles->hapus($pembayaran->bukti_pembayaran_path));
        $calonSiswa->pembayaranLainnya->each(fn ($lain) => $berkasFiles->hapus($lain->bukti_pembayaran_path));

        DB::transaction(function () use ($calonSiswa) {
            if ($calonSiswa->status_pendaftaran === 'diterima') {
                $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $calonSiswa->tahun_ajaran_id)
                    ->where('jalur_pendaftaran_id', $calonSiswa->jalur_pendaftaran_id)
                    ->lockForUpdate()
                    ->first();
                if ($kuota && $kuota->terisi > 0) {
                    $kuota->decrement('terisi');
                }
            }

            $calonSiswa->delete();
        });

        $berkasFiles->hapusYangSudahTidakDipakai();

        return redirect()->route('admin.calon-siswas.index')->with('success', 'Calon siswa dihapus.');
    }
}

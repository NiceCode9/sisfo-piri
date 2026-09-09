<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CalonSiswaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:calon-siswas.view', only: ['index', 'show']),
            new Middleware('permission:calon-siswas.create', only: ['create', 'store']),
            new Middleware('permission:calon-siswas.edit', only: ['edit', 'update', 'updateStatus', 'verifyBerkas', 'destroySertifikat']),
            new Middleware('permission:calon-siswas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $calons = CalonSiswa::with(['jalurPendaftaran', 'tahunAjaran', 'berkasCalonSiswa'])
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($qq) => $qq
                ->where('no_pendaftaran', 'like', "%{$s}%")
                ->orWhere('nama_lengkap', 'like', "%{$s}%")
                ->orWhere('nik', 'like', "%{$s}%")
            ))
            ->when(request('jalur'), fn ($q, $v) => $q->where('jalur_pendaftaran_id', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status_pendaftaran', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.calon-siswas.index', [
            'calons' => $calons,
            'jalurs' => JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get(),
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
        $validated['no_pendaftaran'] = $this->generateNoPendaftaran();
        $validated['status_pendaftaran'] = $validated['status_pendaftaran'] ?? 'menunggu';

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
                        $berkasData[$field] = $file->store('berkas', 'public');
                    }
                    $calon->berkasCalonSiswa()->create($berkasData);
                }

                foreach ($sertifikatUploads as $i => $item) {
                    $calon->sertifikatPrestasis()->create([
                        'nama_sertifikat' => $request->input("sertifikat.{$i}.nama", 'Sertifikat Prestasi'),
                        'file_path' => $item['file']->store('berkas/sertifikat', 'public'),
                    ]);
                }

                LogStatusPendaftaran::create([
                    'calon_siswa_id' => $calon->id,
                    'status_sebelumnya' => null,
                    'status_baru' => $calon->status_pendaftaran,
                    'user_id' => auth()->id(),
                    'catatan' => 'Pendaftaran dibuat via admin',
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.calon-siswas.index')->with('success', 'Calon siswa berhasil ditambahkan.');
    }

    public function show(CalonSiswa $calonSiswa): View
    {
        $calonSiswa->load(['jalurPendaftaran', 'tahunAjaran', 'berkasCalonSiswa', 'sertifikatPrestasis', 'logStatusPendaftaran.user', 'pembayaran.biayaPendaftaran', 'rencanaAngsuran.detailAngsuran']);

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

        // If jalur/tahun changed and current status is diterima, adjust kuota?
        $oldJalur = $calonSiswa->jalur_pendaftaran_id;
        $oldTahun = $calonSiswa->tahun_ajaran_id;
        $oldStatus = $calonSiswa->status_pendaftaran;
        $newJalur = $validated['jalur_pendaftaran_id'];
        $newTahun = $validated['tahun_ajaran_id'] ?? $oldTahun;

        if (($oldJalur !== $newJalur || $oldTahun != $newTahun) && $oldStatus === 'diterima') {
            return back()->with('error', 'Tidak dapat mengganti jalur/tahun untuk calon yang sudah diterima. Ubah status dulu.')->withInput();
        }

        $berkasFields = ['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'];
        $calonData = collect($validated)->except([...$berkasFields, 'sertifikat'])->toArray();
        $berkasUploads = collect($validated)->only($berkasFields)->filter()->toArray();
        $sertifikatUploads = $request->file('sertifikat', []);

        DB::transaction(function () use ($calonSiswa, $calonData, $berkasUploads, $sertifikatUploads, $request) {
            $calonSiswa->update($calonData);

            if (! empty($berkasUploads)) {
                $berkas = $calonSiswa->berkasCalonSiswa()->firstOrCreate([]);
                foreach ($berkasUploads as $field => $file) {
                    if ($berkas->$field) {
                        Storage::disk('public')->delete($berkas->$field);
                    }
                    $berkas->$field = $file->store('berkas', 'public');
                }
                $berkas->save();
            }

            foreach ($sertifikatUploads as $i => $item) {
                $calonSiswa->sertifikatPrestasis()->create([
                    'nama_sertifikat' => $request->input("sertifikat.{$i}.nama", 'Sertifikat Prestasi'),
                    'file_path' => $item['file']->store('berkas/sertifikat', 'public'),
                ]);
            }
        });

        return redirect()->route('admin.calon-siswas.show', $calonSiswa)->with('success', 'Data calon siswa diperbarui.');
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
                    Siswa::firstOrCreate(
                        ['calon_siswa_id' => $calonSiswa->id],
                        [
                            'user_id' => $calonSiswa->user_id,
                            'nisn' => $calonSiswa->nisn,
                            'tahun_ajaran_id' => $calonSiswa->tahun_ajaran_id,
                            'tanggal_diterima' => now()->toDateString(),
                            'is_aktif' => true,
                        ]
                    );

                    // Auto-create pembayaran untuk semua biaya wajib tahun tersebut
                    $biayasWajib = BiayaPendaftaran::where('tahun_ajaran_id', $calonSiswa->tahun_ajaran_id)
                        ->where('wajib_bayar', true)
                        ->get();

                    foreach ($biayasWajib as $biaya) {
                        Pembayaran::firstOrCreate(
                            [
                                'calon_siswa_id' => $calonSiswa->id,
                                'biaya_pendaftaran_id' => $biaya->id,
                            ],
                            [
                                'kode_pembayaran' => $this->generateKodePembayaran(),
                                'jumlah' => $biaya->jumlah,
                                'metode_pembayaran' => 'transfer',
                                'jenis_pembayaran' => 'penuh',
                                'status' => 'menunggu',
                            ]
                        );
                    }
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Status diubah {$oldStatus} → {$newStatus}.");
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

        foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'] as $field) {
            if ($request->hasFile($field)) {
                if ($berkas->$field) {
                    Storage::disk('public')->delete($berkas->$field);
                }
                $data[$field] = $request->file($field)->store('berkas', 'public');
            }
        }

        $berkas->update($data);

        return back()->with('success', 'Verifikasi berkas diperbarui.');
    }

    public function destroySertifikat(SertifikatPrestasi $sertifikat): RedirectResponse
    {
        Storage::disk('public')->delete($sertifikat->file_path);
        $nama = $sertifikat->nama_sertifikat;
        $sertifikat->delete();

        return back()->with('success', "Sertifikat {$nama} dihapus.");
    }

    public function destroy(CalonSiswa $calonSiswa): RedirectResponse
    {
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
            if ($calonSiswa->berkasCalonSiswa) {
                foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'] as $field) {
                    if ($calonSiswa->berkasCalonSiswa->$field) {
                        Storage::disk('public')->delete($calonSiswa->berkasCalonSiswa->$field);
                    }
                }
            }
            foreach ($calonSiswa->sertifikatPrestasis as $sertifikat) {
                Storage::disk('public')->delete($sertifikat->file_path);
            }
            $calonSiswa->delete();
        });

        return redirect()->route('admin.calon-siswas.index')->with('success', 'Calon siswa dihapus.');
    }

    private function generateNoPendaftaran(): string
    {
        $year = date('Y');
        $count = CalonSiswa::whereYear('created_at', $year)->count() + 1;

        return sprintf('PPDB-%s-%04d', $year, $count);
    }

    private function generateKodePembayaran(): string
    {
        $year = date('Y');
        $count = Pembayaran::whereYear('created_at', $year)->count() + 1;

        return sprintf('PAY-%s-%04d', $year, $count);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SiswaTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiswaRequest;
use App\Http\Requests\Admin\UpdateSiswaRequest;
use App\Imports\SiswaImport;
use App\Models\Kelas;
use App\Models\ProfilSekolah;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiswaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:siswas.view', only: ['index', 'show', 'kartu']),
            new Middleware('permission:siswas.create', only: ['create', 'store', 'template', 'import']),
            new Middleware('permission:siswas.edit', only: ['edit', 'update', 'regenerateQr']),
            new Middleware('permission:siswas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $siswas = Siswa::with(['user', 'kelas', 'tahunAjaran'])
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($qq) => $qq
                ->where('nis', 'like', "%{$s}%")
                ->orWhere('nisn', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
            ))
            ->when(request('kelas'), fn ($q, $v) => $q->where('kelas_id', $v))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.siswas.index', [
            'siswas' => $siswas,
            'kelases' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.siswas.create', [
            'kelases' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StoreSiswaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['nisn'],
                'name' => $validated['nama'],
                'password' => $validated['nisn'],
            ]);
            $user->assignRole('siswa');

            $siswa = Siswa::create([
                'user_id' => $user->id,
                'nis' => $validated['nis'] ?? null,
                'nisn' => $validated['nisn'],
                'tahun_ajaran_id' => $validated['tahun_ajaran_id'] ?? TahunAjaran::aktif()->first()?->id,
                'kelas_id' => $validated['kelas_id'] ?? null,
                'tanggal_diterima' => $validated['tanggal_diterima'] ?? now()->toDateString(),
                'is_aktif' => $validated['is_aktif'],
                'nama_ayah' => $validated['nama_ayah'] ?? null,
                'pekerjaan_ayah' => $validated['pekerjaan_ayah'] ?? null,
                'nama_ibu' => $validated['nama_ibu'] ?? null,
                'pekerjaan_ibu' => $validated['pekerjaan_ibu'] ?? null,
                'no_hp_orang_tua' => $validated['no_hp_orang_tua'] ?? null,
            ]);

            if ($siswa->kelas_id) {
                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $siswa->kelas_id,
                    'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
                    'status' => 'aktif',
                ]);
            }
        });

        return redirect()->route('admin.siswas.index')->with('success', "Siswa {$validated['nama']} berhasil ditambahkan beserta akun login (password = NISN).");
    }

    public function show(Siswa $siswa): View
    {
        $siswa->load(['user', 'kelas', 'tahunAjaran', 'calonSiswa.pembayaran', 'calonSiswa.pembayaranLainnya', 'riwayatKelas.kelas', 'riwayatKelas.tahunAjaran']);

        return view('admin.siswas.show', compact('siswa'));
    }

    public function edit(Siswa $siswa): View
    {
        return view('admin.siswas.edit', [
            'siswa' => $siswa->load('user'),
            'kelases' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdateSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $validated = $request->validated();
        $kelasLama = $siswa->kelas_id;
        $tahunLama = $siswa->tahun_ajaran_id;
        $tahunBaru = $validated['tahun_ajaran_id'] ?? $tahunLama;
        $kelasBaru = $validated['kelas_id'] ?? $kelasLama;

        DB::transaction(function () use ($validated, $siswa, $kelasLama, $tahunLama, $kelasBaru, $tahunBaru) {
            $siswa->user?->update([
                'username' => $validated['nisn'],
                'name' => $validated['nama'],
            ]);

            $siswa->update([
                'nis' => $validated['nis'] ?? null,
                'nisn' => $validated['nisn'],
                'tahun_ajaran_id' => $validated['tahun_ajaran_id'] ?? $siswa->tahun_ajaran_id,
                'kelas_id' => $validated['kelas_id'] ?? null,
                'tanggal_diterima' => $validated['tanggal_diterima'] ?? $siswa->tanggal_diterima,
                'is_aktif' => $validated['is_aktif'],
                'nama_ayah' => $validated['nama_ayah'] ?? null,
                'pekerjaan_ayah' => $validated['pekerjaan_ayah'] ?? null,
                'nama_ibu' => $validated['nama_ibu'] ?? null,
                'pekerjaan_ibu' => $validated['pekerjaan_ibu'] ?? null,
                'no_hp_orang_tua' => $validated['no_hp_orang_tua'] ?? null,
            ]);

            // Ganti kelas atau tahun ajaran: tutup baris riwayat lama dan
            // buka yang baru.
            //
            // Sebelumnya baris lama dibiarkan `aktif` begitu saja. Karena
            // Rombel::anggotaIds() sengaja mengabaikan `status` (agar riwayat
            // utuh), siswa itu tetap terhitung anggota kelas lamanya SEKALIGUS
            // anggota kelas baru — muncul di dua grid absensi sekaligus.
            // Menutup baris lama sekaligus menghidupkan enum `pindah` yang
            // selama ini tidak pernah ditulis kode mana pun.
            if ($kelasBaru !== $kelasLama || $tahunBaru !== $tahunLama) {
                RiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('kelas_id', $kelasLama)
                    ->where('tahun_ajaran_id', $tahunLama)
                    ->where('status', 'aktif')
                    ->update(['status' => 'pindah']);

                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $kelasBaru,
                        'tahun_ajaran_id' => $tahunBaru,
                    ],
                    ['status' => 'aktif']
                );
            }
        });

        return redirect()->route('admin.siswas.show', $siswa)->with('success', 'Data siswa diperbarui.');
    }

    /**
     * Halaman cetak kartu pelajar (1 siswa, print via browser).
     */
    public function kartu(Siswa $siswa): View
    {
        $siswa->load(['user', 'calonSiswa.berkasCalonSiswa', 'kelas', 'tahunAjaran']);

        if (! $siswa->qr_token) {
            $siswa->update(['qr_token' => Str::random(32)]);
        }

        // Foto diambil dari berkas PPDB yang privat, jadi diservis lewat route
        // terotorisasi (admin sudah lewat middleware permission di route).
        $foto = $siswa->calonSiswa?->berkasCalonSiswa?->foto_path
            ? route('dokumen.berkas', [$siswa->calonSiswa->berkasCalonSiswa, 'foto_path'])
            : null;

        $siswaList = collect([(object) [
            'nama' => $siswa->user?->name ?? $siswa->calonSiswa?->nama_lengkap ?? '-',
            'nisn' => $siswa->nisn ?? '-',
            'qr_token' => $siswa->qr_token,
            'kelas' => $siswa->kelas?->nama_kelas ?? '-',
            'foto' => $foto,
        ]]);

        return view('admin.siswas.kartu-siswa', [
            'siswaList' => $siswaList,
            'tahunAjaran' => $siswa->tahunAjaran?->nama_tahun_ajaran,
            'sekolah' => ProfilSekolah::aktif(),
        ]);
    }

    /**
     * Unduh template Excel import siswa.
     */
    public function template(): BinaryFileResponse
    {
        return Excel::download(new SiswaTemplateExport, 'template-import-siswa.xlsx');
    }

    /**
     * Import data siswa dari Excel.
     *
     * Dua mode dalam satu file:
     * - NISN sudah ada di `siswas` (hasil terima PPDB) → mode PENEMPATAN: isi
     *   nis/kelas/tahun/ortu, buat RiwayatKelas, password tidak diubah.
     * - NISN baru → mode PEMBUATAN: buat User (password = NISN) + Siswa + RiwayatKelas.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new SiswaImport;
        Excel::import($import, $request->file('file'));

        $gagal = $import->failures();
        $ringkas = "dibuat {$import->imported}, diperbarui {$import->updated}";
        $jumlahGagal = $gagal->count() + count($import->customFailures);

        if ($jumlahGagal > 0) {
            $daftar = $gagal->take(10)
                ->map(fn ($f) => 'Baris '.$f->row().': '.implode(', ', $f->errors()))
                ->merge($import->customFailures)
                ->take(10)
                ->implode(' | ');

            return back()->with('error', "Import selesai: {$ringkas}, {$jumlahGagal} gagal. {$daftar}");
        }

        return redirect()->route('admin.siswas.index')->with('success', "Import selesai: {$ringkas}.");
    }

    /**
     * Generate ulang token QR absensi (token lama hangus).
     */
    public function regenerateQr(Siswa $siswa): RedirectResponse
    {
        $siswa->update(['qr_token' => Str::random(32)]);

        return back()->with('success', 'QR absensi diperbarui. Cetak ulang kartu siswa.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        if ($siswa->riwayatKelas()->exists()) {
            return back()->with('error', 'Siswa memiliki riwayat kelas dan tidak dapat dihapus. Nonaktifkan saja.');
        }

        DB::transaction(function () use ($siswa) {
            $user = $siswa->user;
            $siswa->delete();
            $user?->delete();
        });

        return redirect()->route('admin.siswas.index')->with('success', 'Siswa dihapus beserta akunnya.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Koreksi riwayat kelas.
 *
 * `RiwayatKelas` adalah sumber keanggotaan rombel sekaligus sumber
 * rekonstruksi histori. Baris yang salah merusak rekap absensi, nilai tugas,
 * dan nilai ujian sekaligus, dan sebelum halaman ini ada satu-satunya cara
 * memperbaikinya adalah mengetik SQL di server.
 *
 * Karena inilah satu-satunya tempat di sistem yang dapat menghapus data
 * histori. `destroy()` karena itu memeriksa dampaknya dan tidak hanya
 * meminta konfirmasi.
 */
class RiwayatKelasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:riwayat-kelas.manage'),
        ];
    }

    public function index(Request $request): View
    {
        $siswas = Siswa::with(['user', 'kelas', 'tahunAjaran'])
            ->orderBy('nis')
            ->when($request->query('search'), fn ($q, $s) => $q->whereHas(
                'user',
                fn ($uq) => $uq->where('name', 'like', "%{$s}%")
            ))
            ->paginate(20)
            ->withQueryString();

        return view('admin.riwayat-kelas.index', [
            'siswas' => $siswas,
        ]);
    }

    /**
     * Riwayat satu siswa.
     */
    public function show(Siswa $siswa): View
    {
        $riwayats = $siswa->riwayatKelas()
            ->with(['kelas', 'tahunAjaran'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderByDesc('id')
            ->get();

        return view('admin.riwayat-kelas.show', [
            'siswa' => $siswa->load(['user', 'kelas', 'tahunAjaran']),
            'riwayats' => $riwayats,
            'kelases' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(Request $request, RiwayatKelas $riwayatKelas): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'status' => ['required', 'in:aktif,lulus,pindah,dropout,mengulang'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Constraint unik (siswa, kelas, tahun) yang baru dipasang akan
        // menolak bentrok. Dicek di sini supaya pesannya bisa dibaca alih-alih
        // menjadi QueryException.
        $bentrok = RiwayatKelas::where('siswa_id', $riwayatKelas->siswa_id)
            ->where('kelas_id', $validated['kelas_id'])
            ->where('tahun_ajaran_id', $validated['tahun_ajaran_id'])
            ->where('id', '!=', $riwayatKelas->id)
            ->exists();

        if ($bentrok) {
            return back()->withErrors([
                'kelas_id' => 'Siswa ini sudah punya baris riwayat untuk kelas dan tahun ajaran tersebut.',
            ]);
        }

        // Satu siswa hanya boleh punya satu baris `aktif` per tahun ajaran.
        // Mengubah baris ini menjadi `aktif` berarti melepas baris aktif lain
        // di tahun yang sama, supaya ia tidak jadi anggota dua rombel sekaligus.
        if ($validated['status'] === 'aktif') {
            RiwayatKelas::where('siswa_id', $riwayatKelas->siswa_id)
                ->where('id', '!=', $riwayatKelas->id)
                ->where('status', 'aktif')
                ->where('tahun_ajaran_id', $validated['tahun_ajaran_id'])
                ->update(['status' => 'pindah']);
        }

        $riwayatKelas->update($validated);

        return back()->with('success', 'Riwayat kelas diperbarui.');
    }

    public function destroy(Request $request, RiwayatKelas $riwayatKelas): RedirectResponse
    {
        // Menghapus baris riwayat berarti menarik siswa ini dari rekap kelas
        // tersebut, karena keanggotaan rombel diturunkan dari tabel ini.
        // Kehadiran, tugas, dan nilai ujian yang sudah tercatat tidak akan
        // lagi muncul di rekap, meski baris aslinya masih ada di tabelnya.
        $dampak = $this->ringkasanDampak($riwayatKelas);

        if (! $request->boolean('sadar')) {
            return back()->with(
                'error',
                'Baris riwayat tidak dihapus. Tandai kotak konfirmasi terlebih dahulu. '.$dampak
            );
        }

        $riwayatKelas->delete();

        return back()->with('success', 'Baris riwayat dihapus.'.$dampak);
    }

    /**
     * Ringkasan data yang akan hilang dari rekap, untuk pesan sebelum hapus.
     */
    protected function ringkasanDampak(RiwayatKelas $riwayat): string
    {
        $rombel = Rombel::where('kelas_id', $riwayat->kelas_id)
            ->where('tahun_ajaran_id', $riwayat->tahun_ajaran_id)
            ->first();

        if (! $rombel) {
            return '';
        }

        $punya = array_filter([
            $rombel->absensis()->where('siswa_id', $riwayat->siswa_id)->exists() ? 'kehadiran' : null,
            Tugas::where('rombel_id', $rombel->id)
                ->whereHas('pengumpulans', fn ($q) => $q->where('siswa_id', $riwayat->siswa_id))
                ->exists() ? 'nilai tugas' : null,
        ]);

        if ($punya === []) {
            return '';
        }

        return ' Rekap '.implode(' dan ', $punya).' siswa ini untuk kelas tersebut langsung tidak akan muncul lagi.';
    }
}

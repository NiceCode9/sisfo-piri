<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGelombangRequest;
use App\Http\Requests\Admin\UpdateGelombangRequest;
use App\Models\Gelombang;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GelombangController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:gelombangs.view', only: ['index']),
            new Middleware('permission:gelombangs.create', only: ['create', 'store']),
            new Middleware('permission:gelombangs.edit', only: ['edit', 'update']),
            new Middleware('permission:gelombangs.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $gelombangs = Gelombang::with(['tahunAjaran', 'tahapan'])
            ->when(request('search'), fn ($q, $s) => $q->where('nama_gelombang', 'like', "%{$s}%"))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->orderBy('nomor_urut')
            ->paginate(10)
            ->withQueryString();

        return view('admin.gelombangs.index', [
            'gelombangs' => $gelombangs,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.gelombangs.create', [
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StoreGelombangRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $gelombang = Gelombang::create($request->safe()->except('tahapan'));

            $this->simpanTahapan($gelombang, $request->validated('tahapan'));
        });

        return redirect()->route('admin.gelombangs.index')->with('success', 'Gelombang berhasil ditambahkan.');
    }

    public function edit(Gelombang $gelombang): View
    {
        return view('admin.gelombangs.edit', [
            'gelombang' => $gelombang->load('tahapan'),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdateGelombangRequest $request, Gelombang $gelombang): RedirectResponse
    {
        DB::transaction(function () use ($request, $gelombang) {
            $gelombang->update($request->safe()->except('tahapan'));

            $this->simpanTahapan($gelombang, $request->validated('tahapan'));
        });

        return redirect()->route('admin.gelombangs.index')->with('success', "Gelombang {$gelombang->nama_gelombang} diperbarui.");
    }

    /**
     * Simpan baris tahap untuk sebuah gelombang.
     *
     * Tahap diperlakukan sebagai satu kesatuan: form admin mengirim seluruh
     * daftar setiap kali disimpan, jadi baris yang tidak ada lagi di form ikut
     * dihapus. Dideduplikasi per `urutan` karena `tahapan` punya unique
     * (gelombang_id, urutan) dan index form bisa mengirim urutan yang sama dua
     * kali.
     *
     * Transaksi di dalamnya bersifat nested (savepoint) supaya pemanggil tetap
     * bisa membungkus baris induk dan baris tahapnya jadi satu kesatuan.
     *
     * @param  array<int, array<string, mixed>>  $tahapan
     */
    private function simpanTahapan(Gelombang $gelombang, array $tahapan): void
    {
        DB::transaction(function () use ($gelombang, $tahapan) {
            $gelombang->tahapan()->delete();

            foreach ($tahapan as $index => $tahap) {
                $gelombang->tahapan()->create([
                    'tipe' => $tahap['tipe'],
                    'nama_tahap' => $tahap['nama_tahap'],
                    'urutan' => $index + 1,
                    'tanggal_mulai' => $tahap['tanggal_mulai'],
                    'tanggal_selesai' => $tahap['tanggal_selesai'] ?? null,
                    'keterangan' => $tahap['keterangan'] ?? null,
                ]);
            }
        });
    }

    public function destroy(Gelombang $gelombang): RedirectResponse
    {
        $gelombang->delete();

        return redirect()->route('admin.gelombangs.index')->with('success', "Gelombang {$gelombang->nama_gelombang} dihapus.");
    }
}

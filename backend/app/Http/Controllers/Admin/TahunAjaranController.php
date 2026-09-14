<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTahunAjaranRequest;
use App\Http\Requests\Admin\UpdateTahunAjaranRequest;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TahunAjaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tahun-ajarans.view', only: ['index']),
            new Middleware('permission:tahun-ajarans.create', only: ['create', 'store']),
            new Middleware('permission:tahun-ajarans.edit', only: ['edit', 'update']),
            new Middleware('permission:tahun-ajarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAjarans = TahunAjaran::withCount(['jadwalPpdb', 'kuotaPendaftaran', 'calonSiswa'])
            ->when(request('search'), fn ($q, $s) => $q->where('nama_tahun_ajaran', 'like', "%{$s}%"))
            ->orderByDesc('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tahun-ajarans.index', compact('tahunAjarans'));
    }

    public function create(): View
    {
        return view('admin.tahun-ajarans.create');
    }

    public function store(StoreTahunAjaranRequest $request): RedirectResponse
    {
        $tahunAjaran = DB::transaction(function () use ($request) {
            $this->nonaktifkanLainnya($request->boolean('status_aktif'));

            return TahunAjaran::create($request->validated());
        });

        return redirect()->route('admin.tahun-ajarans.index')->with('success', "Tahun ajaran {$tahunAjaran->nama_tahun_ajaran} berhasil ditambahkan.");
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('admin.tahun-ajarans.edit', compact('tahunAjaran'));
    }

    public function update(UpdateTahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        DB::transaction(function () use ($request, $tahunAjaran) {
            $this->nonaktifkanLainnya($request->boolean('status_aktif'), $tahunAjaran->id);

            $tahunAjaran->update($request->validated());
        });

        return redirect()->route('admin.tahun-ajarans.index')->with('success', "Tahun ajaran {$tahunAjaran->nama_tahun_ajaran} diperbarui.");
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if ($tahunAjaran->jadwalPpdb()->exists() || $tahunAjaran->kuotaPendaftaran()->exists() || $tahunAjaran->calonSiswa()->exists()) {
            return back()->with('error', "Tahun ajaran {$tahunAjaran->nama_tahun_ajaran} masih memiliki data terkait dan tidak dapat dihapus.");
        }

        $tahunAjaran->delete();

        return redirect()->route('admin.tahun-ajarans.index')->with('success', "Tahun ajaran {$tahunAjaran->nama_tahun_ajaran} dihapus.");
    }

    /**
     * Nonaktifkan semua tahun ajaran lain saat satu tahun diaktifkan.
     */
    protected function nonaktifkanLainnya(bool $aktifkan, ?int $kecualiId = null): void
    {
        if (! $aktifkan) {
            return;
        }

        TahunAjaran::where('status_aktif', true)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->update(['status_aktif' => false]);
    }
}
